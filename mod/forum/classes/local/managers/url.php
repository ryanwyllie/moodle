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
 * A URL manager for the forum.
 *
 * @package    mod_forum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\managers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\forum as forum_entity;

/**
 * A URL manager for the forum.
 *
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url {
    public function get_course_url_from_courseid(int $courseid) : \moodle_url {
        return new \moodle_url('/course/view.php', [
            'id' => $courseid,
        ]);
    }

    public function get_course_url_from_forum(forum_entity $forum) : \moodle_url {
        return $this->get_course_url_from_courseid($forum->get_course_id());
    }

    public function get_forum_view_url_from_forum(forum_entity $forum) : \moodle_url {
        return new \moodle_url('/mod/forum/discussions.php', [
            'f' => $forum->get_id(),
        ]);
    }

    public function get_forum_view_url_from_course_module_id(int $coursemoduleid) : \moodle_url {
        return new \moodle_url('/mod/forum/discussions.php', [
            'id' => $coursemoduleid,
        ]);
    }
}
