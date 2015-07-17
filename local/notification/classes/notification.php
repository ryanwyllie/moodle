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

use DateTime;

class notification {

    private $id;

    private $type;

    private $description;

    private $seen;

    private $createddate;

    public function __construct($id, $type, $description, $seen, DateTime $createddate = null) {
        $this->createddate = ($createddate) ? $createddate : new DateTime();
        $this->id = $id;
        $this->type = $type;
        $this->description = $description;
        $this->seen = $seen;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_type() {
        return $this->type;
    }

    public function get_description() {
        return $this->description;
    }

    public function get_created_date() {
        return $this->createddate;
    }

    public function has_been_seen() {
        return $this->seen;
    }

    public function mark_as_seen() {
        $this->seen = true;
    }

    public function mark_as_unseen() {
        $this->seen = false;
    }
}

class serialiser {
    public function to_array(notification $notification) {
        return array(
            'id' => $notification->get_id(),
            'type' => $notification->get_type(),
            'description' => $notification->get_description(),
            'seen' => $notification->has_been_seen(),
            'created_date' => $notification->get_created_date()->format(DateTime::ATOM),
        );
    }
}

class repository {

    private static $data = array();

    public function __construct() {
        for ($i = 0; $i < 100; $i++) {
            self::$data[] = new notification($i, 'user', 'desc'.rand(), false);
        }
    }

    public function retrieve($limit = 0, $offset = 0) {
        $limit = ($limit) ? $limit : count(self::$data);
        return array_slice(self::$data, $offset, $limit);
    }
}
