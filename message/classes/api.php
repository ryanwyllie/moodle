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
 * Contains class used to return information to display for the message area.
 *
 * @package    core_message
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_message;

require_once($CFG->dirroot . '/lib/messagelib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Class used to return information to display for the message area.
 *
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /**
     * Returns the contacts and their conversation to display in the contacts area.
     *
     * @param \stdClass $user The user
     * @param int $limitfrom
     * @param int $limitto
     * @return \core_message\output\contacts
     */
    public static function get_conversations($user, $limitfrom = 0, $limitto = 100) {
        global $CFG, $OUTPUT;

        $arrcontacts = array();
        if ($conversations = message_get_recent_conversations($user, $limitfrom, $limitto)) {
            // Variables to check if we consider this user online or not.
            $timetoshowusers = 300; // Seconds default.
            if (isset($CFG->block_online_users_timetosee)) {
                $timetoshowusers = $CFG->block_online_users_timetosee * 60;
            }
            $lastonlinetime = time() - $timetoshowusers;
            foreach ($conversations as $conversation) {
                $userfields = \user_picture::unalias($conversation, array('lastaccess'));
                $contact = new \stdClass();
                $contact->userid = $userfields->id;
                $contact->name = fullname($userfields);
                $contact->picture = $OUTPUT->user_picture($userfields, array('size' => 64, 'link' => false));
                $contact->lastmessage = message_shorten_message($conversation->smallmessage, 60) . '...';
                $contact->isonline = $userfields->lastaccess >= $lastonlinetime;
                $arrcontacts[] = new \core_message\output\contact($contact);
            }
        }

        return new \core_message\output\contacts($arrcontacts);
    }

    /**
     * Returns the contacts to display in the contacts area.
     *
     * @param \stdClass $user The user
     * @param int $limitfrom
     * @param int $limitto
     * @return \core_message\output\contacts
     */
    public static function get_contacts($user, $limitfrom = 0, $limitto = 100) {
        global $CFG, $DB, $OUTPUT;

        $arrcontacts = array();

        $sql = "SELECT u.*
                  FROM {message_contacts} mc
                  JOIN {user} u
                    ON mc.contactid = u.id
                 WHERE mc.userid = :userid";
        if ($contacts = $DB->get_records_sql($sql, array('userid' => $user->id), $limitfrom, $limitto)) {
            // Variables to check if we consider this user online or not.
            $timetoshowusers = 300; // Seconds default.
            if (isset($CFG->block_online_users_timetosee)) {
                $timetoshowusers = $CFG->block_online_users_timetosee * 60;
            }
            $time = time() - $timetoshowusers;
            foreach ($contacts as $contact) {
                $userfields = \user_picture::unalias($contact);
                $contact = new \stdClass();
                $contact->userid = $userfields->id;
                $contact->name = fullname($userfields);
                $contact->picture = $OUTPUT->user_picture($userfields, array('size' => 64, 'link' => false));
                $contact->lastmessage = null;
                $contact->isonline = $userfields->lastaccess >= $time;
                $arrcontacts[] = new \core_message\output\contact($contact);
            }
        }

        return new \core_message\output\contacts($arrcontacts, false);
    }

    /**
     * Returns the messages to display in the message area.
     *
     * @param \stdClass $user1 the current user
     * @param \stdClass $user2 the other user
     * @param int $limitnum
     * @return \core_message\output\messages
     */
    public static function get_messages($user1, $user2, $limitnum = 0) {
        $arrmessages = array();
        if ($messages = message_get_history($user1, $user2, $limitnum)) {
            // Keeps track of the last day, month and year combo we were viewing.
            $day = '';
            $month = '';
            $year = '';
            foreach ($messages as $message) {
                // Check if we are now viewing a different block period.
                $blocktime = 0;
                $date = usergetdate($message->timecreated);
                if ($day != $date['mday'] || $month != $date['month'] || $year != $date['year']) {
                    $day = $date['mday'];
                    $month = $date['month'];
                    $year = $date['year'];
                    $blocktime = userdate($message->timecreated, get_string('strftimedaydate'));
                }
                // Store the message to pass to the renderable.
                $msg = new \stdClass();
                $msg->text = message_format_message_text($message);
                $msg->currentuser = $user1;
                $msg->useridfrom = $message->useridfrom;
                $msg->useridto = $message->useridto;
                $msg->blocktime = null;
                if (!empty($blocktime)) {
                    $msg->blocktime = $blocktime;
                }
                $msg->timesent = userdate($message->timecreated, get_string('strftimetime'));
                $arrmessages[] = new \core_message\output\message($msg);
            }
        }

        return new \core_message\output\messages($arrmessages, $user2);
    }
}
