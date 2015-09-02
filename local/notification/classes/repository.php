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

class repository {

    private static $data = array();

    public function __construct() {
        $notifications = array();

        for ($i = 0; $i < 20; $i++) {
            $notifications[] = new notification($i, 'user', 'http://www.google.com', 1, 'desc'.rand());
        }

        self::$data = array_reverse($notifications);
    }

    public function retrieve_all_for_user($user, $limit = 0, $offset = 0, DateTime $before = null, DateTime $after = null) {
        $limit = ($limit) ? $limit : count(self::$data);

        $results = array();

        foreach (self::$data as $notification) {
            if (isset($before) && $notification->createddate > $before) {
                continue;
            }

            if (isset($after) && $notification->createddate < $after) {
                continue;
            }

            $results[] = $notification;
        }

        return array_slice($results, $offset, $limit);
    }

    public function count_all_for_user($user) {
        return count(self::$data);
    }

    public function count_all_unseen_for_user($user) {
        $count = 0;

        foreach (self::$data as $notification) {
            if (!$notification->has_been_seen()) {
                $count++;
            }
        }

        return $count;
    }
}
