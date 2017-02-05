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
 * Course module value object.
 *
 * @package    core_calendar
 * @copyright  2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\local\event\value_objects;

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\value_objects\course_module_interface;

/**
 * Class representing course module.
 *
 * @copyright 2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class event_course_module implements course_module_interface {
    /**
     * @var int $id The course module's ID.
     */
    private $id;

    /**
     * @var string $name The course module's name.
     */
    private $name;

    /**
     * @var instance The course module's instance ID.
     */
    private $instance;

    /**
     * Constructor.
     *
     * @param int    $id   The course module's ID.
     * @param string $name The course module's name.
     */
    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_instance_id() {
        return $this->id;
    }

    public function get_instance() {
        return $this->instance = $this->instance ? $this->instance : get_coursemodule_from_instance($this->name, $this->id);
    }
}


