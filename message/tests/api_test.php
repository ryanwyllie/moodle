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
 * Test message API.
 *
 * @package core_message
 * @category test
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/message/tests/messagelib_test.php');

/**
 * Test message API.
 *
 * @package core_message
 * @category test
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_message_api_testcase extends core_message_messagelib_testcase {

    public function test_message_mark_all_read_for_user_touser() {
        $sender = $this->getDataGenerator()->create_user(array('firstname' => 'Test1', 'lastname' => 'User1'));
        $recipient = $this->getDataGenerator()->create_user(array('firstname' => 'Test2', 'lastname' => 'User2'));

        $this->send_fake_message($sender, $recipient, 'Notification', 1);
        $this->send_fake_message($sender, $recipient, 'Notification', 1);
        $this->send_fake_message($sender, $recipient, 'Notification', 1);
        $this->send_fake_message($sender, $recipient);
        $this->send_fake_message($sender, $recipient);
        $this->send_fake_message($sender, $recipient);

        \core_message\api::mark_all_read_for_user($recipient->id);
        $this->assertEquals(message_count_unread_messages($recipient), 0);
    }

    public function test_message_mark_all_read_for_user_touser_with_fromuser() {
        $sender1 = $this->getDataGenerator()->create_user(array('firstname' => 'Test1', 'lastname' => 'User1'));
        $sender2 = $this->getDataGenerator()->create_user(array('firstname' => 'Test3', 'lastname' => 'User3'));
        $recipient = $this->getDataGenerator()->create_user(array('firstname' => 'Test2', 'lastname' => 'User2'));

        $this->send_fake_message($sender1, $recipient, 'Notification', 1);
        $this->send_fake_message($sender1, $recipient, 'Notification', 1);
        $this->send_fake_message($sender1, $recipient, 'Notification', 1);
        $this->send_fake_message($sender1, $recipient);
        $this->send_fake_message($sender1, $recipient);
        $this->send_fake_message($sender1, $recipient);
        $this->send_fake_message($sender2, $recipient, 'Notification', 1);
        $this->send_fake_message($sender2, $recipient, 'Notification', 1);
        $this->send_fake_message($sender2, $recipient, 'Notification', 1);
        $this->send_fake_message($sender2, $recipient);
        $this->send_fake_message($sender2, $recipient);
        $this->send_fake_message($sender2, $recipient);

        \core_message\api::mark_all_read_for_user($recipient->id, $sender1->id);
        $this->assertEquals(message_count_unread_messages($recipient), 6);
    }

    public function test_message_mark_all_read_for_user_touser_with_type() {
        $sender = $this->getDataGenerator()->create_user(array('firstname' => 'Test1', 'lastname' => 'User1'));
        $recipient = $this->getDataGenerator()->create_user(array('firstname' => 'Test2', 'lastname' => 'User2'));

        $this->send_fake_message($sender, $recipient, 'Notification', 1);
        $this->send_fake_message($sender, $recipient, 'Notification', 1);
        $this->send_fake_message($sender, $recipient, 'Notification', 1);
        $this->send_fake_message($sender, $recipient);
        $this->send_fake_message($sender, $recipient);
        $this->send_fake_message($sender, $recipient);

        \core_message\api::mark_all_read_for_user($recipient->id, 0, MESSAGE_TYPE_NOTIFICATION);
        $this->assertEquals(message_count_unread_messages($recipient), 3);

        \core_message\api::mark_all_read_for_user($recipient->id, 0, MESSAGE_TYPE_MESSAGE);
        $this->assertEquals(message_count_unread_messages($recipient), 0);
    }

    /**
     * Test count_blocked_users.
     *
     */
    public function test_message_count_blocked_users() {
        // Set this user as the admin.
        $this->setAdminUser();

        // Create users to add to the admin's contact list.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->assertEquals(0, \core_message\api::count_blocked_users());

        // Add 1 blocked and 1 normal contact to admin's contact list.
        message_add_contact($user1->id);
        message_add_contact($user2->id, 1);

        $this->assertEquals(0, \core_message\api::count_blocked_users($user2));
        $this->assertEquals(1, \core_message\api::count_blocked_users());
    }

    /**
     * Test retrieving messages by providing a minimum timecreated value.
     */
    public function test_get_messages_created_from_only() {
        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        $this->send_fake_message($user1, $user2, 'Message 1', 0, $time + 1);
        $this->send_fake_message($user2, $user1, 'Message 2', 0, $time + 2);
        $this->send_fake_message($user1, $user2, 'Message 3', 0, $time + 3);
        $this->send_fake_message($user2, $user1, 'Message 4', 0, $time + 4);

        // Retrieve the messages.
        $messages = \core_message\api::get_messages($user1->id, $user2->id, 0, 0, 'timecreated ASC', $time);

        // Confirm the message data is correct.
        $this->assertEquals(4, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];
        $message3 = $messages[2];
        $message4 = $messages[3];

        $this->assertContains('Message 1', $message1->text);
        $this->assertContains('Message 2', $message2->text);
        $this->assertContains('Message 3', $message3->text);
        $this->assertContains('Message 4', $message4->text);

        // Retrieve the messages.
        $messages = \core_message\api::get_messages($user1->id, $user2->id, 0, 0, 'timecreated ASC', $time + 3);

        // Confirm the message data is correct.
        $this->assertEquals(2, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];

        $this->assertContains('Message 3', $message1->text);
        $this->assertContains('Message 4', $message2->text);
    }

    /**
     * Test retrieving messages by providing a maximum timecreated value.
     */
    public function test_get_messages_created_to_only() {
        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        $this->send_fake_message($user1, $user2, 'Message 1', 0, $time + 1);
        $this->send_fake_message($user2, $user1, 'Message 2', 0, $time + 2);
        $this->send_fake_message($user1, $user2, 'Message 3', 0, $time + 3);
        $this->send_fake_message($user2, $user1, 'Message 4', 0, $time + 4);

        // Retrieve the messages.
        $messages = \core_message\api::get_messages($user1->id, $user2->id, 0, 0, 'timecreated ASC', 0, $time + 4);

        // Confirm the message data is correct.
        $this->assertEquals(4, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];
        $message3 = $messages[2];
        $message4 = $messages[3];

        $this->assertContains('Message 1', $message1->text);
        $this->assertContains('Message 2', $message2->text);
        $this->assertContains('Message 3', $message3->text);
        $this->assertContains('Message 4', $message4->text);

        // Retrieve the messages.
        $messages = \core_message\api::get_messages($user1->id, $user2->id, 0, 0, 'timecreated ASC', 0, $time + 2);

        // Confirm the message data is correct.
        $this->assertEquals(2, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];

        $this->assertContains('Message 1', $message1->text);
        $this->assertContains('Message 2', $message2->text);
    }

    /**
     * Test retrieving messages by providing a minimum and maximum timecreated value.
     */
    public function test_get_messages_created_from_and_to() {
        // Create some users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // The person doing the search.
        $this->setUser($user1);

        // Send some messages back and forth.
        $time = 1;
        $this->send_fake_message($user1, $user2, 'Message 1', 0, $time + 1);
        $this->send_fake_message($user2, $user1, 'Message 2', 0, $time + 2);
        $this->send_fake_message($user1, $user2, 'Message 3', 0, $time + 3);
        $this->send_fake_message($user2, $user1, 'Message 4', 0, $time + 4);

        // Retrieve the messages.
        $messages = \core_message\api::get_messages($user1->id, $user2->id, 0, 0, 'timecreated ASC', $time + 2, $time + 3);

        // Confirm the message data is correct.
        $this->assertEquals(2, count($messages));

        $message1 = $messages[0];
        $message2 = $messages[1];

        $this->assertContains('Message 2', $message1->text);
        $this->assertContains('Message 3', $message2->text);
    }
}
