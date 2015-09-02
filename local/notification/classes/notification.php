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
    private $url;
    private $user_id;
    private $description;
    private $seen;
    private $actioned;
    private $createddate;

    public function __construct($id = null, $type, $url, $user_id, $description,
            $seen = false, $actioned = false, DateTime $createddate = null) {

        $this->id = $id;
        $this->type = $type;
        $this->url = $url;
        $this->user_id = $user_id;
        $this->description = $description;
        $this->seen = $seen;
        $this->action = $actioned;
        $this->createddate = ($createddate) ? $createddate : new DateTime();
    }

    public function get_id() {
        return $this->id;
    }

    public function get_type() {
        return $this->type;
    }

    public function get_url() {
        return $this->url;
    }

    public function get_user_id() {
        return $this->user_id;
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

    public function has_been_actioned() {
        return $this->actioned;
    }

    public function set_id($id) {
        // TODO: Type checking here.
        // Can only set the id once.
        if (!isset($this->id)) {
            $this->id = $id;
        }
    }

    public function mark_as_seen() {
        $this->seen = true;
    }

    public function mark_as_actioned() {
        $this->actioned = true;
    }
}

class serialiser {
    public function to_array(notification $notification) {
        return array(
            'id' => $notification->get_id(),
            'type' => $notification->get_type(),
            'url' => $notification->get_url(),
            'user_id' => $notification->get_user_id(),
            'description' => $notification->get_description(),
            'seen' => $notification->has_been_seen(),
            'actioned' => $notification->has_been_actioned(),
            'created_date' => $notification->get_created_date()->format(DateTime::ATOM),
        );
    }
}
