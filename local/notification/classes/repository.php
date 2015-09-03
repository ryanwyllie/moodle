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
define('DEFAULT_SORT', NOTIFICATION_TABLE.'.created desc');

class repository {

    public function retrieve_all_for_user($user, $limit = 0, $offset = 0, DateTime $before = null, DateTime $after = null) {
        global $DB;

        $params = array('user_id' => $user->id);
        $where_clauses = array('un.notification_id = u.id', 'un.user_id = :user_id');

        if (!is_null($before)) {
            $where_clauses[] = 'n.created_date <= :before';
            $params['before'] = strtotime($before->format(DateTime::ATOM));
        }

        if (!is_null($after)) {
            $where_clauses[] = 'n.created_date >= :after';
            $params['after'] = strtotime($after->format(DateTime::ATOM));
        }

        $sql = sprintf("SELECT * FROM {%s} as n, {%S} as un WHERE %s ORDER BY %s",
            NOTIFICATION_TABLE, NOTIFICATION_USER_TABLE, 
            implode(' AND ', $where_clauses), DEFAULT_SORT);

        $records = $DB->get_records_sql($sql, $params, $offset, $limit);

        return array_map($this->unpack_from_db_record, $records);
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
