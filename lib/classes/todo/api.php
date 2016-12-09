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
 * Todo helper.
 *
 * @package    core
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\todo;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/enrollib.php');

class api {

    private static $factory = null;
    private static $repository = null;

    private static function init() {
        if (empty(self::$factory)) {
            self::$factory = new factory();
            self::$repository = new repository();
        }
    }

    public static function get_for_user($user, $courses = []) {
        self::init();

        if (empty($courses)) {
            $courses = enrol_get_users_courses($user->id);
        }

        $events = self::$repository->get_for_user($user->id, array_keys($courses));
        $todos = [];

        foreach ($events as $event) {
             $todos = array_merge($todos, self::$factory->create([$event], $user));
        }

        return $todos;
    }
}
