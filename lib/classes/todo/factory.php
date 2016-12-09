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
 * Todo.
 *
 * @package    core
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\todo;
defined('MOODLE_INTERNAL') || die();

use core\todo\builder as builder;
use core\todo\builder\simple as simple_builder;

class factory {

    private static $types = [];

    public function __construct() {
        if (empty(self::$types)) {
            $this->register_types();
        }
    }

    public function create($events, $user) {
        $type = 'mod_' . $events[0]->modulename;

        if (isset(self::$types[$type])) {
            $builder = self::$types[$type];
        } else {
            $builder = new simple_builder();
        }

        return $builder->build($events, $user);
    }

    private function register_types() {
        if ($pluginsfunction = get_plugins_with_function('register_todo_builder')) {
            foreach ($pluginsfunction as $plugintype => $plugins) {
                foreach ($plugins as $pluginname => $pluginfunction) {
                    $builder = $pluginfunction();

                    if ($builder instanceof builder) {
                        self::$types[$plugintype .'_' . $pluginname] = $builder;
                    } else {
                        // Throw exception here?
                    }
                }
            }
        }
    }
}
