<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class containing data for index page
 *
 * @package    local_notification
 * @copyright  2015 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_notification;

require_once(__DIR__."/notification.php");

use DateTime;
use \local_notification\notification as notification;

define('NOTIFICATION_TABLE', 'local_notification');
define('NOTIFICATION_USER_TABLE', 'local_notification_user');
define('DEFAULT_SORT', 'created_date DESC');

class repository {

    public function create(notification &$notification) {
        global $DB;

        if ($notification->has_id()) {
            return;
        }

        $returnid = true;
        list($notification_data, $notification_user_data) = $this->pack_for_db($notification);

        error_log("NOTIFICATION_DATA:".var_export($notification_data, true));
        error_log("NOTIFICATION_USER_DATA:".var_export($notification_user_data, true));

        $id = $DB->insert_record(NOTIFICATION_TABLE, $notification_data, $returnid);
        $notification->set_id($id);

        $notification_user_data['notification_id'] = $id;

        $DB->insert_record(NOTIFICATION_USER_TABLE, $notification_user_data, !$returnid);
    }

    public function retrieve_all_for_user($user, $limit = 0, $offset = 0, DateTime $before = null, DateTime $after = null) {
        global $DB;

        $params = array('user_id' => $user->id);
        $where_clauses = array('un.notification_id = n.id', 'un.user_id = :user_id');

        if (!is_null($before)) {
            $where_clauses[] = 'n.created_date <= :before';
            $params['before'] = strtotime($before->format(DateTime::ATOM));
        }

        if (!is_null($after)) {
            $where_clauses[] = 'n.created_date >= :after';
            $params['after'] = strtotime($after->format(DateTime::ATOM));
        }

        $where_string = implode(' AND ', $where_clauses);

        $sql = sprintf("SELECT n.*, un.user_id, un.seen, un.actioned FROM {%s} AS n, {%s} AS un WHERE %s ORDER BY %s",
            NOTIFICATION_TABLE, NOTIFICATION_USER_TABLE, $where_string, 'n.'.DEFAULT_SORT);

        $records = $DB->get_records_sql($sql, $params, $offset, $limit);

        return array_map(array($this, "unpack_from_db_record"), array_values($records));
    }

    public function count_all_for_user($user) {
        global $DB;

        return $DB->count_records_select(NOTIFICATION_USER_TABLE,
            "user_id = :user_id", array('user_id' => $user->id));
    }

    public function count_all_unseen_for_user($user) {
        global $DB;

        return $DB->count_records_select(NOTIFICATION_USER_TABLE,
            "user_id = :user_id AND seen = 0", array('user_id' => $user->id));
    }

    public function update_multiple(array $notifications) {
        // TODO: Make this more performant.
        foreach ($notifications as $notification) {
            $this->update_single($notification);
        }
    }

    public function update_single(notification $notification) {
        // TODO: Make this more performant.
        global $DB;

        list($notification_data, $notification_user_data) = $this->pack_for_db($notification);

        $DB->update_record(NOTIFICATION_TABLE, $notification_data);
        $DB->update_record(NOTIFICATION_USER_TABLE, $notification_user_data);
    }

    private function pack_for_db(notification $notification) {
        global $DB;

        $notification_data = array(
            'type' => $notification->get_type(),
            'description' => $notification->get_description(),
            'created_date' => strtotime($notification->get_created_date()->format(DateTime::ATOM)),
            'url' => $notification->get_url()
        );

        $notification_user_data = array(
            'user_id' => $notification->get_user_id(),
            'seen' => $notification->has_been_seen() ? 1 : 0,
            'actioned' => $notification->has_been_actioned() ? 1 : 0
        );

        if ($notification->has_id()) {
            $notification_data['id'] = $notification->get_id();

            $notification_user_id = $DB->get_field(NOTIFICATION_USER_TABLE, 'id',
                array('notification_id' => $notification->get_id(), 'user_id' => $notification->get_user_id()));

            if (isset($notification_user_id)) {
                $notification_user_data['id'] = $notification_user_id;
            }
        }

        return array($notification_data, $notification_user_data);
    }

    private function unpack_from_db_record($record) {
        $createddate = new DateTime();
        $createddate->setTimestamp($record->created_date);

        $seen = $record->seen ? true : false;
        $actioned = $record->actioned ? true : false;

        return new notification($record->id, $record->type,
            $record->url, $record->user_id, $record->description,
            $seen, $actioned, $createddate);
    }
}
