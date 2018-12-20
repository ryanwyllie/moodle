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
 * Discussion class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\entities;

defined('MOODLE_INTERNAL') || die();

/**
 * Discussion class.
 */
class discussion {
    private $id;
    private $courseid;
    private $forumid;
    private $name;
    private $firstpost;
    private $userid;
    private $groupid;
    private $assessed;
    private $timemodified;
    private $usermodified;
    private $timestart;
    private $timeend;
    private $pinned;

    public function __construct(
        int $id,
        int $courseid,
        int $forumid,
        string $name,
        int $firstpost,
        int $userid,
        int $groupid,
        bool $assessed,
        int $timemodified,
        int $usermodified,
        int $timestart,
        int $timeend,
        bool $pinned
    ) {
        $this->id = $id;
        $this->courseid = $courseid;
        $this->forumid = $forumid;
        $this->name = $name;
        $this->firstpost = $firstpost;
        $this->userid = $userid;
        $this->groupid = $groupid;
        $this->assessed = $assessed;
        $this->timemodified = $timemodified;
        $this->usermodified = $usermodified;
        $this->timestart = $timestart;
        $this->timeend = $timeend;
        $this->pinned = $pinned;
    }

    public function get_id() : int {
        return $this->id;
    }

    public function get_course_id() : int {
        return $this->courseid;
    }

    public function get_forum_id() : int {
        return $this->forumid;
    }

    public function get_name() : string {
        return $this->name;
    }

    // What is this?
    public function get_first_post_id() : int {
        return $this->firstpost;
    }

    public function get_user_id() : int {
        return $this->userid;
    }

    public function get_group_id() : int {
        return $this->groupid;
    }

    public function is_assessed() : bool {
        return $this->assessed;
    }

    public function get_time_modified() : int {
        return $this->timemodified;
    }

    public function get_user_modified() : int {
        return $this->usermodified;
    }

    public function get_time_start() : int {
        return $this->timestart;
    }

    public function get_time_end() : int {
        return $this->timeend;
    }

    public function is_pinned() : bool {
        return $this->pinned;
    }
}
