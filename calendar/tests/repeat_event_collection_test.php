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
 * Repeat event collection tests.
 *
 * @package    core_calendar
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_calendar\local\event\entities\event;
use core_calendar\local\event\entities\repeat_event_collection;
use core_calendar\local\event\proxies\std_proxy;
use core_calendar\local\event\value_objects\event_description;
use core_calendar\local\event\value_objects\event_times;
use core_calendar\local\interfaces\event_factory_interface;

/**
 * Repeat event collection tests.
 *
 * @copyright 2017 Ryan Wyllie <ryan@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_calendar_repeat_event_collection_testcase extends advanced_testcase {
    /**
     * Test that creating a repeat collection for a parent that doesn't
     * exist throws an exception.
     *
     * @expectedException \core_calendar\local\event\exceptions\no_repeat_parent_exception
     */
    public function test_no_parent_collection() {
        $this->resetAfterTest(true);
        $parentid = 123122131;
        $factory = new core_calendar_repeat_event_collection_event_test_factory();
        $collection = new repeat_event_collection($parentid, $factory);
    }

    /**
     * Test that an empty collection is valid.
     */
    public function test_empty_collection() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $event = $this->create_event([
            // This causes the code to set the repeat id on this record
            // but not create any repeat event records.
            'repeat' => 1,
            'repeats' => 0
        ]);
        $parentid = $event->id;
        $factory = new core_calendar_repeat_event_collection_event_test_factory();

        // Event collection with no repeats.
        $collection = new repeat_event_collection($parentid, $factory);

        $this->assertEquals($parentid, $collection->get_id());
        $this->assertEquals(0, $collection->get_num());
        $this->assertNull($collection->getIterator()->next());
    }

    /**
     * Test that a collection with values behaves correctly.
     */
    public function test_values_collection() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $factory = new core_calendar_repeat_event_collection_event_test_factory();
        $event = $this->create_event([
            // This causes the code to set the repeat id on this record
            // but not create any repeat event records.
            'repeat' => 1,
            'repeats' => 0
        ]);
        $parentid = $event->id;
        $repeats = [];

        for ($i = 1; $i < 4; $i++) {
            $record = $this->create_event([
                'name' => sprintf('repeat %d', $i),
                'repeatid' => $parentid
            ]);

            // Index by name so that we don't have to rely on sorting
            // when doing the comparison later.
            $repeats[$record->name] = $record;
        }

        // Event collection with no repeats.
        $collection = new repeat_event_collection($parentid, $factory);

        $this->assertEquals($parentid, $collection->get_id());
        $this->assertEquals(count($repeats), $collection->get_num());

        foreach ($collection as $index => $event) {
            $name = $event->get_name();
            $this->assertEquals($repeats[$name]->name, $name);
        }
    }

    /**
     * Helper function to create calendar events using the old code.
     *
     * @param array $properties A list of calendar event properties to set
     * @return event
     */
    protected function create_event($properties = []) {
        $record = new \stdClass();
        $record->name = 'event name';
        $record->eventtype = 'global';
        $record->repeat = 0;
        $record->repeats = 0;
        $record->timestart = time();
        $record->timeduration = 0;
        $record->timesort = 0;
        $record->type = 1;
        $record->courseid = 0;

        foreach ($properties as $name => $value) {
            $record->$name = $value;
        }

        $event = new \core_calendar\event($record);
        return $event->create($record, false);
    }
}

/**
 * Test event factory.
 *
 * @copyright 2017 Ryan Wyllie <ryan@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_calendar_repeat_event_collection_event_test_factory implements event_factory_interface {
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
        $identity = function($id) {
            return $id;
        };
        return new event(
            $id,
            $name,
            new event_description($descriptionvalue, $descriptionformat),
            new std_proxy($courseid, $identity),
            new std_proxy($groupid, $identity),
            new std_proxy($userid, $identity),
            new repeat_event_collection($id, $this),
            new std_proxy($moduleinstance, $identity),
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
    }
}
