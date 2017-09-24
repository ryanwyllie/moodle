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
 * Contains class containing unit tests for mod/chat/lib.php.
 *
 * @package mod_chat
 * @category test
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \core_calendar\local\api as calendar_local_api;
use \core_calendar\local\event\container as calendar_event_container;

/**
 * Class containing unit tests for mod/chat/lib.php.
 *
 * @package mod_chat
 * @category test
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_chat_lib_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_chat_core_calendar_provide_event_action_chattime_event_yesterday() {
        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a chat.
        $chat = $this->getDataGenerator()->create_module('chat', array('course' => $course->id,
            'chattime' => time() - DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $chat->id, CHAT_EVENT_TYPE_CHATTIME);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_chat_core_calendar_provide_event_action($event, $factory);

        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    public function test_chat_core_calendar_provide_event_action_chattime_event_today() {
        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a chat.
        $chat = $this->getDataGenerator()->create_module('chat', array('course' => $course->id,
            'chattime' => usergetmidnight(time())));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $chat->id, CHAT_EVENT_TYPE_CHATTIME);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_chat_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('enterchat', 'chat'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_chat_core_calendar_provide_event_action_chattime_event_tonight() {
        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a chat.
        $chat = $this->getDataGenerator()->create_module('chat', array('course' => $course->id,
            'chattime' => usergetmidnight(time()) + (23 * HOURSECS)));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $chat->id, CHAT_EVENT_TYPE_CHATTIME);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_chat_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('enterchat', 'chat'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_chat_core_calendar_provide_event_action_chattime_event_tomorrow() {
        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a chat.
        $chat = $this->getDataGenerator()->create_module('chat', array('course' => $course->id,
            'chattime' => time() + DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $chat->id, CHAT_EVENT_TYPE_CHATTIME);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_chat_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('enterchat', 'chat'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertFalse($actionevent->is_actionable());
    }

    /**
     * An event of unknown type should not update the chat time.
     */
    public function test_mod_chat_core_calendar_event_timestart_updated_unknown_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a chat.
        $now = time();
        $chattime = $now;
        $newchattime = $chattime + DAYSECS;
        $chat = $this->getDataGenerator()->create_module('chat', [
            'course' => $course->id,
            'chattime' => $chattime,
            'timemodified' => $now
        ]);

        $event = new \calendar_event((object) [
            'modulename' => 'chat',
            'instance' => $chat->id,
            'eventtype' => 'SOME RANDOM EVENT',
            'timestart' => $newchattime
        ]);

        mod_chat_core_calendar_event_timestart_updated($event);

        $updatedchat = $DB->get_record('chat', ['id' => $chat->id]);
        // The chat time should not have been updated.
        $this->assertEquals($chattime, $updatedchat->chattime);
        $this->assertEquals($now, $updatedchat->timemodified);
    }

    /**
     * A CHAT_EVENT_TYPE_CHATTIME event should update the chat time
     */
    public function test_mod_chat_core_calendar_event_timestart_updated_update_chattime() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a chat.
        $timemodified = time() - 10;
        $chattime = time();
        $newchattime = $chattime + DAYSECS;
        $chat = $this->getDataGenerator()->create_module('chat', [
            'course' => $course->id,
            'chattime' => $chattime,
            'timemodified' => $timemodified
        ]);

        $event = new \calendar_event((object) [
            'modulename' => 'chat',
            'instance' => $chat->id,
            'eventtype' => CHAT_EVENT_TYPE_CHATTIME,
            'timestart' => $newchattime
        ]);

        mod_chat_core_calendar_event_timestart_updated($event);

        $updatedchat = $DB->get_record('chat', ['id' => $chat->id]);
        // The activity should have been updated.
        $this->assertEquals($newchattime, $updatedchat->chattime);
        $this->assertNotEquals($timemodified, $updatedchat->timemodified);
    }

    /**
     * A student should not be able to update the chat time of the
     * activity if they manage to find a way to update the event.
     */
    public function test_student_role_cant_update_chattime() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $mapper = calendar_event_container::get_event_mapper();
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $context = context_course::instance($course->id);
        $roleid = $generator->create_role();
        // Create a chat.
        $now = time();
        $chattime = new DateTime();
        $newchattime = (new DateTime())->setTimestamp($chattime->getTimestamp() + DAYSECS);
        $chat = $this->getDataGenerator()->create_module('chat', [
            'course' => $course->id,
            'chattime' => $chattime->getTimestamp(),
            'tiemmodified' => $now
        ]);

        $generator->enrol_user($user->id, $course->id, 'student');
        $generator->role_assign($roleid, $user->id, $context->id);

        $event = \calendar_event::create([
            'name' => 'test event',
            'courseid' => $course->id,
            'modulename' => 'chat',
            'instance' => $chat->id,
            'eventtype' => CHAT_EVENT_TYPE_CHATTIME,
            'timestart' => $newchattime->getTimestamp()
        ]);

        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context, true);
        assign_capability('moodle/course:manageactivities', CAP_PROHIBIT, $roleid, $context, true);

        $this->setUser($user);

        calendar_local_api::update_event_start_day(
            $mapper->from_legacy_event_to_event($event),
            $newchattime
        );

        $updatedchat = $DB->get_record('chat', ['id' => $chat->id]);
        // The chat time should not have updated in the activity.
        $this->assertEquals($chattime->getTimestamp(), $updatedchat->chattime);
        $this->assertEquals($now, $updatedchat->timemodified);
    }

    /**
     * A teacher should be able to update the chat time by modifying
     * the chat time event.
     */
    public function test_teacher_role_cant_update_chattime() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $mapper = calendar_event_container::get_event_mapper();
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $context = context_course::instance($course->id);
        $roleid = $generator->create_role();
        // Create a chat.
        $timemodified = time() - 10;
        $chattime = new DateTime();
        $newchattime = (new DateTime())->setTimestamp($chattime->getTimestamp() + DAYSECS);
        $chat = $this->getDataGenerator()->create_module('chat', [
            'course' => $course->id,
            'chattime' => $chattime->getTimestamp(),
            'timemodified' => $timemodified
        ]);

        $generator->enrol_user($user->id, $course->id, 'teacher');
        $generator->role_assign($roleid, $user->id, $context->id);

        $event = \calendar_event::create([
            'name' => 'test event',
            'courseid' => $course->id,
            'modulename' => 'chat',
            'instance' => $chat->id,
            'eventtype' => CHAT_EVENT_TYPE_CHATTIME,
            'timestart' => $newchattime->getTimestamp()
        ]);

        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $context, true);
        assign_capability('moodle/course:manageactivities', CAP_ALLOW, $roleid, $context, true);

        $this->setUser($user);

        calendar_local_api::update_event_start_day(
            $mapper->from_legacy_event_to_event($event),
            $newchattime
        );

        $updatedchat = $DB->get_record('chat', ['id' => $chat->id]);
        // The chat time should have updated in the activity.
        $this->assertEquals($newchattime->getTimestamp(), $updatedchat->chattime);
        $this->assertNotEquals($timemodified, $updatedchat->timemodified);
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid
     * @param int $instanceid The chat id.
     * @param string $eventtype The event type. eg. ASSIGN_EVENT_TYPE_DUE.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype) {
        $event = new stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = 'chat';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return calendar_event::create($event);
    }
}
