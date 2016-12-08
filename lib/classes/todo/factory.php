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

class factory {

    private static $types = [];

    public function __construct() {
        if (empty(self::$types)) {
            $this->load_types();
        }
    }

    public function create($type, $object) {
        if (isset(self::$types[$type])) {
            $function = self::$types[$type];
            return $function($object);
        } else {
            return $this->default_build($object);
        }
    }

    private function default_build($object) {
        return new todo(
            $object->id,
            $object->name,
            $object->startdate,
            $object->enddate
        );
    }

    private function load_types() {
        if ($pluginsfunction = get_plugins_with_function('build_todo')) {
            foreach ($pluginsfunction as $plugintype => $plugins) {
                foreach ($plugins as $pluginname => $pluginfunction) {
                    self::$types[$plugintype .'_' . $pluginname] = $pluginfunction;
                }
            }
        }
    }
}
