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
 * This file contains helper classes and functions for testing.
 *
 * @package core_calendar
 * @copyright 2017 Ryan Wyllie <ryan@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\entities\action_event;
use core_calendar\local\event\entities\event;
use core_calendar\local\event\entities\event_factory_interface;
use core_calendar\local\event\entities\event_vault;
use core_calendar\local\event\entities\repeat_event_collection;
use core_calendar\local\event\value_objects\action;
use core_calendar\local\event\value_objects\event_description;
use core_calendar\local\event\value_objects\event_times;
use core_calendar\local\event\value_objects\event_course_module;
use core_calendar\local\event\proxies\std_proxy;

/**
 * Create a calendar event with the given properties.
 *
 * @param array $properties The properties to set on the event
 * @return \core_calendar\event
 */
function create_event($properties) {
    $record = new \stdClass();
    $record->name = 'event name';
    $record->eventtype = 'global';
    $record->repeat = 0;
    $record->repeats = 0;
    $record->timestart = time();
    $record->timeduration = 0;
    $record->timesort = 0;
    $record->type = CALENDAR_EVENT_TYPE_STANDARD;
    $record->courseid = 0;

    foreach ($properties as $name => $value) {
        $record->$name = $value;
    }

    $event = new \core_calendar\event($record);
    return $event->create($record);
}

/**
 * A test factory that will create action events.
 */
class action_event_test_factory implements event_factory_interface {

    private $callback;

    /**
     * A test factory that will create action events. The factory accepts a callback
     * that will be used to determine if the event should be returned or not.
     *
     * The callback will be given the event and should return true if the event
     * should be returned and false otherwise.
     */
    public function __construct($callback = null) {
        $this->callback = $callback;
    }

    public function create_instance(
        $id,
        $name,
        $descriptionvalue,
        $descriptionformat,
        $courseid,
        $groupid,
        $userid,
        $repeatid,
        $modulename,
        $moduleinstance,
        $type,
        $timestart,
        $timeduration,
        $timemodified,
        $timesort,
        $visible,
        $subscriptionid
    ) {
        $event = new event(
            $id,
            $name,
            new event_description($descriptionvalue, $descriptionformat),
            new std_proxy($courseid, function($id) {
                global $DB;
                return $DB->get_record('course', ['id' => $id]);
            }),
            new std_proxy($groupid, function($id) {
                return groups_get_group($id, 'id,name,courseid');
            }),
            new std_proxy($userid, function($id) {
                global $DB;
                return $DB->get_record('user', ['id' => $id]);
            }),
            new repeat_event_collection($id, $this),
            $moduleinstance && $modulename ? new event_course_module($moduleinstance, $modulename)
                                           : NULL,
            $type,
            new event_times(
                (new \DateTimeImmutable())->setTimestamp($timestart),
                (new \DateTimeImmutable())->setTimestamp($timestart + $timeduration),
                (new \DateTimeImmutable())->setTimestamp($timesort ? $timesort : $timestart),
                (new \DateTimeImmutable())->setTimestamp($timemodified)
            ),
            !empty($visible),
            $subscriptionid
        );

        $action = new action(
            'Test action',
            new \moodle_url('/'),
            1
        );

        $actionevent = new action_event($event, $action);

        if ($callback = $this->callback) {
            return $callback($actionevent) ? $actionevent : false;
        } else {
            return $actionevent;
        }
    }
}
