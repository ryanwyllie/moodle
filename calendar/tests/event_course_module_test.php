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
 * Event course module tests.
 *
 * @package    core_calendar
 * @copyright  2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\value_objects\event_course_module;

/**
 * Event course module testcase.
 *
 * @copyright 2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_calendar_event_course_module_testcase extends advanced_testcase {
    /**
     * Test event course module class getters.
     */
    public function test_getters() {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));
        $eventcoursemodule = new event_course_module($module->id, 'assign');

        $this->assertEquals($eventcoursemodule->get_name(), 'assign');
        $this->assertEquals($eventcoursemodule->get_instance_id(), $module->id);
        $this->assertEquals($eventcoursemodule->get_instance(), get_coursemodule_from_instance('assign', $module->id));
    }
}