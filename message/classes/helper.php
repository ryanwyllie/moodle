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
 * Contains helper class for the message area.
 *
 * @package    core_message
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_message;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for the message area.
 *
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Helper function to retrieve the messages between two users
     *
     * @param int $userid the current user
     * @param int $otheruserid the other user
     * @param int $limitfrom
     * @param int $limitnum
     * @param string $sort
     * @return array of messages
     */
    public static function get_messages($userid, $otheruserid, $limitfrom = 0, $limitnum = 0, $sort = 'timecreated ASC') {
        global $DB;

        $sql = "SELECT id, useridfrom, useridto, subject, fullmessage, fullmessagehtml, fullmessageformat,
                       smallmessage, notification, timecreated, 0 as timeread
                  FROM {message} m
                 WHERE ((useridto = ? AND useridfrom = ? AND timeusertodeleted = 0)
                    OR (useridto = ? AND useridfrom = ? AND timeuserfromdeleted = 0))
                   AND notification = 0
             UNION ALL
                SELECT id, useridfrom, useridto, subject, fullmessage, fullmessagehtml, fullmessageformat,
                       smallmessage, notification, timecreated, timeread
                  FROM {message_read} mr
                 WHERE ((useridto = ? AND useridfrom = ? AND timeusertodeleted = 0)
                    OR (useridto = ? AND useridfrom = ? AND timeuserfromdeleted = 0))
                   AND notification = 0
              ORDER BY $sort";
        $params = array($userid, $otheruserid, $otheruserid, $userid,
                        $userid, $otheruserid, $otheruserid, $userid);

        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    }

    /**
     * Helper function to return an array of messages renderables to display in the message area.
     *
     * @param int $userid
     * @param array $messages
     * @return \core_message\output\message[]
     */
    public static function create_messages($userid, $messages) {
        // Store the messages.
        $arrmessages = array();

        // Keeps track of the last day, month and year combo we were viewing.
        $day = '';
        $month = '';
        $year = '';
        foreach ($messages as $message) {
            // Check if we are now viewing a different block period.
            $displayblocktime = false;
            $date = usergetdate($message->timecreated);
            if ($day != $date['mday'] || $month != $date['month'] || $year != $date['year']) {
                $day = $date['mday'];
                $month = $date['month'];
                $year = $date['year'];
                $displayblocktime = true;
            }
            // Store the message to pass to the renderable.
            $msg = new \stdClass();
            $msg->id = $message->id;
            $msg->text = message_format_message_text($message);
            $msg->currentuserid = $userid;
            $msg->useridfrom = $message->useridfrom;
            $msg->useridto = $message->useridto;
            $msg->displayblocktime = $displayblocktime;
            $msg->timecreated = $message->timecreated;
            $msg->timeread = $message->timeread;
            $arrmessages[] = new \core_message\output\message($msg);
        }

        return $arrmessages;
    }

    /**
     * Helper function for creating a contact renderable.
     *
     * @param \stdClass $contact
     * @return \core_message\output\contact
     */
    public static function create_contact($contact) {
        global $CFG, $PAGE;

        // Variables to check if we consider this user online or not.
        $timetoshowusers = 300; // Seconds default.
        if (isset($CFG->block_online_users_timetosee)) {
            $timetoshowusers = $CFG->block_online_users_timetosee * 60;
        }
        $time = time() - $timetoshowusers;

        // Create the data we are going to pass to the renderable.
        $userfields = \user_picture::unalias($contact, array('lastaccess'));
        $data = new \stdClass();
        $data->userid = $userfields->id;
        $data->fullname = fullname($userfields);
        // Get the user picture data.
        $userpicture = new \user_picture($userfields);
        $userpicture->size = 1; // Size f1.
        $data->profileimageurl = $userpicture->get_url($PAGE)->out(false);
        $userpicture->size = 0; // Size f2.
        $data->profileimageurlsmall = $userpicture->get_url($PAGE)->out(false);
        // Store the message if we have it.
        if (isset($contact->smallmessage)) {
            $data->lastmessage = $contact->smallmessage;
        } else {
            $data->lastmessage = null;
        }
        // Check if the user is online.
        $data->isonline = $userfields->lastaccess >= $time;

        return new \core_message\output\contact($data);
    }
}