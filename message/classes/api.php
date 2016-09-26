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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/messagelib.php');

/**
 * Class used to return information to display for the message area.
 *
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /**
     * Handles searching for messages in the message area.
     *
     * @param int $userid The user id doing the searching
     * @param string $search The string the user is searching
     * @param int $limitfrom
     * @param int $limitnum
     * @return \core_message\output\messagearea\message_search_results
     */
    public static function search_messages($userid, $search, $limitfrom = 0, $limitnum = 0) {
        global $DB;

        // Get the user fields we want.
        $ufields = \user_picture::fields('u', array('lastaccess'), 'userfrom_id', 'userfrom_');
        $ufields2 = \user_picture::fields('u2', array('lastaccess'), 'userto_id', 'userto_');

        // Get all the messages for the user.
        $sql = "SELECT m.id, m.useridfrom, m.useridto, m.subject, m.fullmessage, m.fullmessagehtml, m.fullmessageformat,
                       m.smallmessage, m.notification, m.timecreated, 0 as isread, $ufields, mc.blocked as userfrom_blocked,
                       $ufields2, mc2.blocked as userto_blocked
                  FROM {message} m
                  JOIN {user} u
                    ON m.useridfrom = u.id
             LEFT JOIN {message_contacts} mc
                    ON (mc.contactid = u.id AND mc.userid = ?)
                  JOIN {user} u2
                    ON m.useridto = u2.id
             LEFT JOIN {message_contacts} mc2
                    ON (mc2.contactid = u2.id AND mc2.userid = ?)
                 WHERE ((useridto = ? AND timeusertodeleted = 0)
                    OR (useridfrom = ? AND timeuserfromdeleted = 0))
                   AND notification = 0
                   AND u.deleted = 0
                   AND u2.deleted = 0
                   AND " . $DB->sql_like('smallmessage', '?', false) . "
             UNION ALL
                SELECT mr.id, mr.useridfrom, mr.useridto, mr.subject, mr.fullmessage, mr.fullmessagehtml, mr.fullmessageformat,
                       mr.smallmessage, mr.notification, mr.timecreated, 1 as isread, $ufields, mc.blocked as userfrom_blocked,
                       $ufields2, mc2.blocked as userto_blocked
                  FROM {message_read} mr
                  JOIN {user} u
                    ON mr.useridfrom = u.id
             LEFT JOIN {message_contacts} mc
                    ON (mc.contactid = u.id AND mc.userid = ?)
                  JOIN {user} u2
                    ON mr.useridto = u2.id
             LEFT JOIN {message_contacts} mc2
                    ON (mc2.contactid = u2.id AND mc2.userid = ?)
                 WHERE ((useridto = ? AND timeusertodeleted = 0)
                    OR (useridfrom = ? AND timeuserfromdeleted = 0))
                   AND notification = 0
                   AND u.deleted = 0
                   AND u2.deleted = 0
                   AND " . $DB->sql_like('smallmessage', '?', false) . "
              ORDER BY timecreated DESC";
        $params = array($userid, $userid, $userid, $userid, '%' . $search . '%',
                        $userid, $userid, $userid, $userid, '%' . $search . '%');

        // Convert the messages into searchable contacts with their last message being the message that was searched.
        $contacts = array();
        if ($messages = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum)) {
            foreach ($messages as $message) {
                $prefix = 'userfrom_';
                if ($userid == $message->useridfrom) {
                    $prefix = 'userto_';
                    // If it from the user, then mark it as read, even if it wasn't by the receiver.
                    $message->isread = true;
                }
                $blockedcol = $prefix . 'blocked';
                $message->blocked = $message->$blockedcol;

                $message->messageid = $message->id;
                $contacts[] = \core_message\helper::create_contact($message, $prefix);
            }
        }

        return new \core_message\output\messagearea\message_search_results($userid, $contacts);
    }

    /**
     * Handles searching for people in a particular course in the message area.
     *
     * @param int $userid The user id doing the searching
     * @param int $courseid The id of the course we are searching in
     * @param string $search The string the user is searching
     * @param int $limitfrom
     * @param int $limitnum
     * @return \core_message\output\messagearea\people_search_results
     */
    public static function search_people_in_course($userid, $courseid, $search, $limitfrom = 0, $limitnum = 0) {
        global $DB;

        // Get all the users in the course.
        list($esql, $params) = get_enrolled_sql(\context_course::instance($courseid), '', 0, true);
        $sql = "SELECT u.*, mc.blocked
                  FROM {user} u
                  JOIN ($esql) je
                    ON je.id = u.id
             LEFT JOIN {message_contacts} mc
                    ON (mc.contactid = u.id AND mc.userid = :userid)
                 WHERE u.deleted = 0";
        // Add more conditions.
        $fullname = $DB->sql_fullname();
        $sql .= " AND u.id != :userid2
                  AND " . $DB->sql_like($fullname, ':search', false) . "
             ORDER BY " . $DB->sql_fullname();
        $params = array_merge(array('userid' => $userid, 'userid2' => $userid, 'search' => '%' . $search . '%'), $params);

        // Convert all the user records into contacts.
        $contacts = array();
        if ($users = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum)) {
            foreach ($users as $user) {
                $contacts[] = \core_message\helper::create_contact($user);
            }
        }

        return new \core_message\output\messagearea\people_search_results($contacts);
    }

    /**
     * Handles searching for people in the message area.
     *
     * @param int $userid The user id doing the searching
     * @param string $search The string the user is searching
     * @param int $limitnum
     * @return \core_message\output\messagearea\people_search_results
     */
    public static function search_people($userid, $search, $limitnum = 0) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/coursecatlib.php');

        // Used to search for contacts.
        $fullname = $DB->sql_fullname();
        $ufields = \user_picture::fields('u', array('lastaccess'));

        // Users not to include.
        $excludeusers = array($userid, $CFG->siteguest);
        list($exclude, $excludeparams) = $DB->get_in_or_equal($excludeusers, SQL_PARAMS_NAMED, 'param', false);

        // Ok, let's search for contacts first.
        $contacts = array();
        $sql = "SELECT $ufields, mc.blocked
                  FROM {user} u
                  JOIN {message_contacts} mc
                    ON u.id = mc.contactid
                 WHERE mc.userid = :userid
                   AND u.deleted = 0
                   AND u.confirmed = 1
                   AND " . $DB->sql_like($fullname, ':search', false) . "
                   AND u.id $exclude
              ORDER BY " . $DB->sql_fullname();
        if ($users = $DB->get_records_sql($sql, array('userid' => $userid, 'search' => '%' . $search . '%') + $excludeparams,
            0, $limitnum)) {
            foreach ($users as $user) {
                $contacts[] = \core_message\helper::create_contact($user);
            }
        }

        // Now, let's get the courses.
        $courses = array();
        if ($arrcourses = \coursecat::search_courses(array('search' => $search), array('limit' => $limitnum))) {
            foreach ($arrcourses as $course) {
                $data = new \stdClass();
                $data->id = $course->id;
                $data->shortname = $course->shortname;
                $data->fullname = $course->fullname;
                $courses[] = $data;
            }
        }

        // Let's get those non-contacts. Toast them gears boi.
        // Note - you can only block contacts, so these users will not be blocked, so no need to get that
        // extra detail from the database.
        $noncontacts = array();
        $sql = "SELECT $ufields
                  FROM {user} u
                 WHERE u.deleted = 0
                   AND u.confirmed = 1
                   AND " . $DB->sql_like($fullname, ':search', false) . "
                   AND u.id $exclude
                   AND u.id NOT IN (SELECT contactid
                                      FROM {message_contacts}
                                     WHERE userid = :userid)
              ORDER BY " . $DB->sql_fullname();
        if ($users = $DB->get_records_sql($sql,  array('userid' => $userid, 'search' => '%' . $search . '%') + $excludeparams,
            0, $limitnum)) {
            foreach ($users as $user) {
                $noncontacts[] = \core_message\helper::create_contact($user);
            }
        }

        return new \core_message\output\messagearea\people_search_results($contacts, $courses, $noncontacts);
    }

    /**
     * Returns the contacts and their conversation to display in the contacts area.
     *
     * @param int $userid The user id
     * @param int $otheruserid The id of the user we have selected, 0 if none have been selected
     * @param int $limitfrom
     * @param int $limitnum
     * @return \core_message\output\messagearea\contacts
     */
    public static function get_conversations($userid, $otheruserid = 0, $limitfrom = 0, $limitnum = 0) {
        $arrcontacts = array();
        if ($conversations = message_get_recent_conversations($userid, $limitfrom, $limitnum)) {
            foreach ($conversations as $conversation) {
                $arrcontacts[] = \core_message\helper::create_contact($conversation);
            }
        }

        return new \core_message\output\messagearea\contacts($userid, $otheruserid, $arrcontacts);
    }

    /**
     * Returns the contacts to display in the contacts area.
     *
     * @param int $userid The user id
     * @param int $limitfrom
     * @param int $limitnum
     * @return \core_message\output\messagearea\contacts
     */
    public static function get_contacts($userid, $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $arrcontacts = array();
        $sql = "SELECT u.*, mc.blocked
                  FROM {message_contacts} mc
                  JOIN {user} u
                    ON mc.contactid = u.id
                 WHERE mc.userid = :userid
                   AND u.deleted = 0
              ORDER BY " . $DB->sql_fullname();
        if ($contacts = $DB->get_records_sql($sql, array('userid' => $userid), $limitfrom, $limitnum)) {
            foreach ($contacts as $contact) {
                $arrcontacts[] = \core_message\helper::create_contact($contact);
            }
        }

        return new \core_message\output\messagearea\contacts($userid, 0, $arrcontacts, false);
    }

    /**
     * Returns the messages to display in the message area.
     *
     * @param int $userid the current user
     * @param int $otheruserid the other user
     * @param int $limitfrom
     * @param int $limitnum
     * @param string $sort
     * @return \core_message\output\messagearea\messages
     */
    public static function get_messages($userid, $otheruserid, $limitfrom = 0, $limitnum = 0, $sort = 'timecreated ASC') {
        $arrmessages = array();
        if ($messages = \core_message\helper::get_messages($userid, $otheruserid, 0, $limitfrom, $limitnum, $sort)) {
            $arrmessages = \core_message\helper::create_messages($userid, $messages);
        }

        return new \core_message\output\messagearea\messages($userid, $otheruserid, $arrmessages);
    }

    /**
     * Returns the most recent message between two users.
     *
     * @param int $userid the current user
     * @param int $otheruserid the other user
     * @return \core_message\output\messagearea\message|null
     */
    public static function get_most_recent_message($userid, $otheruserid) {
        // We want two messages here so we get an accurate 'blocktime' value.
        if ($messages = \core_message\helper::get_messages($userid, $otheruserid, 0, 0, 2, 'timecreated DESC')) {
            // Swap the order so we now have them in historical order.
            $messages = array_reverse($messages);
            $arrmessages = \core_message\helper::create_messages($userid, $messages);
            return array_pop($arrmessages);
        }

        return null;
    }

    /**
     * Returns the profile information for a contact for a user.
     *
     * @param int $userid The user id
     * @param int $otheruserid The id of the user whose profile we want to view.
     * @return \core_message\output\messagearea\profile
     */
    public static function get_profile($userid, $otheruserid) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/user/lib.php');

        if ($user = \core_user::get_user($otheruserid)) {
            // Create the data we are going to pass to the renderable.
            $userfields = user_get_user_details($user, null, array('city', 'country', 'email',
                'profileimageurl', 'profileimageurlsmall', 'lastaccess'));
            if ($userfields) {
                $data = new \stdClass();
                $data->userid = $userfields['id'];
                $data->fullname = $userfields['fullname'];
                $data->city = isset($userfields['city']) ? $userfields['city'] : '';
                $data->country = isset($userfields['country']) ? $userfields['country'] : '';
                $data->email = isset($userfields['email']) ? $userfields['email'] : '';
                $data->profileimageurl = isset($userfields['profileimageurl']) ? $userfields['profileimageurl'] : '';
                if (isset($userfields['profileimageurlsmall'])) {
                    $data->profileimageurlsmall = $userfields['profileimageurlsmall'];
                } else {
                    $data->profileimageurlsmall = '';
                }
                if (isset($userfields['lastaccess'])) {
                    $data->isonline = \core_message\helper::is_online($userfields['lastaccess']);
                } else {
                    $data->isonline = 0;
                }
            } else {
                // Technically the access checks in user_get_user_details are correct,
                // but messaging has never obeyed them. In order to keep messaging working
                // we at least need to return a minimal user record.
                $data = new \stdClass();
                $data->userid = $otheruserid;
                $data->fullname = fullname($user);
                $data->city = '';
                $data->country = '';
                $data->email = '';
                $data->profileimageurl = '';
                $data->profileimageurlsmall = '';
                $data->isonline = 0;
            }
            // Check if the contact has been blocked.
            $contact = $DB->get_record('message_contacts', array('userid' => $userid, 'contactid' => $otheruserid));
            if ($contact) {
                $data->isblocked = $contact->blocked;
                $data->iscontact = true;
            } else {
                $data->isblocked = false;
                $data->iscontact = false;
            }

            return new \core_message\output\messagearea\profile($userid, $data);
        }
    }

    /**
     * Checks if a user can delete messages they have either received or sent.
     *
     * @param int $userid The user id of who we want to delete the messages for (this may be done by the admin
     *  but will still seem as if it was by the user)
     * @return bool Returns true if a user can delete the message, false otherwise.
     */
    public static function can_delete_conversation($userid) {
        global $USER;

        $systemcontext = \context_system::instance();

        // Let's check if the user is allowed to delete this message.
        if (has_capability('moodle/site:deleteanymessage', $systemcontext) ||
            ((has_capability('moodle/site:deleteownmessage', $systemcontext) &&
                $USER->id == $userid))) {
            return true;
        }

        return false;
    }

    /**
     * Deletes a conversation.
     *
     * This function does not verify any permissions.
     *
     * @param int $userid The user id of who we want to delete the messages for (this may be done by the admin
     *  but will still seem as if it was by the user)
     * @param int $otheruserid The id of the other user in the conversation
     * @return bool
     */
    public static function delete_conversation($userid, $otheruserid) {
        global $DB, $USER;

        // We need to update the tables to mark all messages as deleted from and to the other user. This seems worse than it
        // is, that's because our DB structure splits messages into two tables (great idea, huh?) which causes code like this.
        // This won't be a particularly heavily used function (at least I hope not), so let's hope MDL-36941 gets worked on
        // soon for the sake of any developers' sanity when dealing with the messaging system.
        $now = time();
        $sql = "UPDATE {message}
                   SET timeuserfromdeleted = :time
                 WHERE useridfrom = :userid
                   AND useridto = :otheruserid
                   AND notification = 0";
        $DB->execute($sql, array('time' => $now, 'userid' => $userid, 'otheruserid' => $otheruserid));

        $sql = "UPDATE {message}
                   SET timeusertodeleted = :time
                 WHERE useridto = :userid
                   AND useridfrom = :otheruserid
                   AND notification = 0";
        $DB->execute($sql, array('time' => $now, 'userid' => $userid, 'otheruserid' => $otheruserid));

        $sql = "UPDATE {message_read}
                   SET timeuserfromdeleted = :time
                 WHERE useridfrom = :userid
                   AND useridto = :otheruserid
                   AND notification = 0";
        $DB->execute($sql, array('time' => $now, 'userid' => $userid, 'otheruserid' => $otheruserid));

        $sql = "UPDATE {message_read}
                   SET timeusertodeleted = :time
                 WHERE useridto = :userid
                   AND useridfrom = :otheruserid
                   AND notification = 0";
        $DB->execute($sql, array('time' => $now, 'userid' => $userid, 'otheruserid' => $otheruserid));

        // Now we need to trigger events for these.
        if ($messages = \core_message\helper::get_messages($userid, $otheruserid, $now)) {
            // Loop through and trigger a deleted event.
            foreach ($messages as $message) {
                $messagetable = 'message';
                if (!empty($message->timeread)) {
                    $messagetable = 'message_read';
                }

                // Trigger event for deleting the message.
                \core\event\message_deleted::create_from_ids($message->useridfrom, $message->useridto,
                    $USER->id, $messagetable, $message->id)->trigger();
            }
        }

        return true;
    }

    /**
     * Returns the count of unread conversations (collection of messages from a single user) for
     * the given user.
     *
     * @param \stdClass $user the user who's conversations should be counted
     * @return int the count of the user's unread conversations
     */
    public static function count_unread_conversations($user = null) {
        global $USER, $DB;

        if (empty($user)) {
            $user = $USER;
        }

        return $DB->count_records_select(
            'message',
            'useridto = ? AND timeusertodeleted = 0 AND notification = 0',
            [$user->id],
            "COUNT(DISTINCT(useridfrom))");
    }

    /**
     * Marks ALL messages being sent from $fromuserid to $touserid as read.
     *
     * Can be filtered by type.
     *
     * @param int $touserid the id of the message recipient
     * @param int $fromuserid the id of the message sender
     * @param string $type filter the messages by type, either MESSAGE_TYPE_NOTIFICATION, MESSAGE_TYPE_MESSAGE or '' for all.
     * @return void
     */
    public static function mark_all_read_for_user($touserid, $fromuserid = 0, $type = '') {
        global $DB;

        $params = array();

        if (!empty($touserid)) {
            $params['useridto'] = $touserid;
        }

        if (!empty($fromuserid)) {
            $params['useridfrom'] = $fromuserid;
        }

        if (!empty($type)) {
            if (strtolower($type) == MESSAGE_TYPE_NOTIFICATION) {
                $params['notification'] = 1;
            } else if (strtolower($type) == MESSAGE_TYPE_MESSAGE) {
                $params['notification'] = 0;
            }
        }

        $sql = sprintf('SELECT m.* FROM {message} m WHERE m.%s = ?', implode('= ? AND m.', array_keys($params)));
        $messages = $DB->get_recordset_sql($sql, array_values($params));

        foreach ($messages as $message) {
            message_mark_message_read($message, time());
        }

        $messages->close();
    }

    /**
     * Get popup notifications for the specified users.
     *
     * @param int $useridto the user id who received the notification
     * @param string $status MESSAGE_READ for retrieving read notifications, MESSAGE_UNREAD for unread, empty for both
     * @param bool $embeduserto embed the to user details in the notification response
     * @param bool $embeduserfrom embed the from user details in the notification response
     * @param string $sort the column name to order by including optionally direction
     * @param int $limit limit the number of result returned
     * @param int $offset offset the result set by this amount
     * @return array notification records
     * @throws \moodle_exception
     * @since 3.2
     */
    public static function get_popup_notifications($useridto = 0, $status = '', $embeduserto = false, $embeduserfrom = false,
                                     $sort = 'DESC', $limit = 0, $offset = 0) {
        global $DB, $USER;

        if (!empty($status) && $status != MESSAGE_READ && $status != MESSAGE_UNREAD) {
            throw new \moodle_exception(sprintf('invalid parameter: status: must be "%s" or "%s"',
                MESSAGE_READ, MESSAGE_UNREAD));
        }

        $sort = strtoupper($sort);
        if ($sort != 'DESC' && $sort != 'ASC') {
            throw new \moodle_exception('invalid parameter: sort: must be "DESC" or "ASC"');
        }

        if (empty($useridto)) {
            $useridto = $USER->id;
        }

        $params = array();

        $buildtablesql = function($table, $prefix, $additionalfields, $messagestatus)
        use ($status, $useridto, $embeduserto, $embeduserfrom) {

            $joinsql = '';
            $fields = "concat('$prefix', $prefix.id) as uniqueid, $prefix.id, $prefix.useridfrom, $prefix.useridto,
            $prefix.subject, $prefix.fullmessage, $prefix.fullmessageformat,
            $prefix.fullmessagehtml, $prefix.smallmessage, $prefix.notification, $prefix.contexturl,
            $prefix.contexturlname, $prefix.timecreated, $prefix.timeuserfromdeleted, $prefix.timeusertodeleted,
            $prefix.component, $prefix.eventtype, $additionalfields";
            $where = " AND $prefix.useridto = :{$prefix}useridto";
            $params = ["{$prefix}useridto" => $useridto];

            if ($embeduserto) {
                $embedprefix = "{$prefix}ut";
                $fields .= ", " . get_all_user_name_fields(true, $embedprefix, '', 'userto');
                $joinsql .= " LEFT JOIN {user} $embedprefix ON $embedprefix.id = $prefix.useridto";
            }

            if ($embeduserfrom) {
                $embedprefix = "{$prefix}uf";
                $fields .= ", " . get_all_user_name_fields(true, $embedprefix, '', 'userfrom');
                $joinsql .= " LEFT JOIN {user} $embedprefix ON $embedprefix.id = $prefix.useridfrom";
            }

            if ($messagestatus == MESSAGE_READ) {
                $isread = '1';
            } else {
                $isread = '0';
            }

            return array(
                sprintf(
                    "SELECT %s
                FROM %s %s %s
                WHERE %s.notification = 1
                AND %s.id IN (SELECT messageid FROM {message_popup} WHERE isread = %s)
                %s",
                    $fields, $table, $prefix, $joinsql, $prefix, $prefix, $isread, $where
                ),
                $params
            );
        };

        switch ($status) {
            case MESSAGE_READ:
                list($sql, $readparams) = $buildtablesql('{message_read}', 'r', 'r.timeread', MESSAGE_READ);
                $params = array_merge($params, $readparams);
                break;
            case MESSAGE_UNREAD:
                list($sql, $unreadparams) = $buildtablesql('{message}', 'u', '0 as timeread', MESSAGE_UNREAD);
                $params = array_merge($params, $unreadparams);
                break;
            default:
                list($readsql, $readparams) = $buildtablesql('{message_read}', 'r', 'r.timeread', MESSAGE_READ);
                list($unreadsql, $unreadparams) = $buildtablesql('{message}', 'u', '0 as timeread', MESSAGE_UNREAD);
                $sql = sprintf("SELECT * FROM (%s UNION %s) f", $readsql, $unreadsql);
                $params = array_merge($params, $readparams, $unreadparams);
        }

        $sql .= " ORDER BY timecreated $sort, timeread $sort, id $sort";

        return array_values($DB->get_records_sql($sql, $params, $offset, $limit));
    }

    /**
     * Count the unread notifications for a user.
     *
     * @param int $useridto the user id who received the notification
     * @return int count of the unread notifications
     * @since 3.2
     */
    public static function count_unread_popup_notifications($useridto = 0) {
        global $USER, $DB;

        if (empty($useridto)) {
            $useridto = $USER->id;
        }

        return $DB->count_records_sql(
            "SELECT count(id)
        FROM {message}
        WHERE id IN (SELECT messageid FROM {message_popup} WHERE isread = 0)
        AND useridto = ?",
            [$useridto]
        );
    }

    /**
     * Returns message preferences.
     *
     * @param array $processors
     * @param array $providers
     * @param \stdClass $user
     * @return \stdClass
     * @since 3.2
     */
    public static function get_all_message_preferences($processors, $providers, $user) {
        $preferences = helper::get_providers_preferences($providers, $user->id);
        $preferences->userdefaultemail = $user->email; // May be displayed by the email processor.

        // For every processors put its options on the form (need to get function from processor's lib.php).
        foreach ($processors as $processor) {
            $processor->object->load_data($preferences, $user->id);
        }

        // Load general messaging preferences.
        $preferences->blocknoncontacts = get_user_preferences('message_blocknoncontacts', '', $user->id);
        $preferences->mailformat = $user->mailformat;
        $preferences->mailcharset = get_user_preferences('mailcharset', '', $user->id);

        return $preferences;
    }
}
