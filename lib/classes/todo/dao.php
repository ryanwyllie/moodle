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

use todo;

class dao {

    private static $persistence = [];

    public function __construct() {
    }

    public function create(todo $todo) {
        self::$persistence[$todo->id] = $todo;
    }

    public function retrieve($id) {
        return isset(self::$persistence[$id]) ? self::$persistence[$id] : null;
    }

    public function update(todo $todo) {
        self::$persistence[$todo->id] = $todo;
    }

    public function delete(todo $todo) {
        unset(self::$persistence[$todo->id]);
    }
}
