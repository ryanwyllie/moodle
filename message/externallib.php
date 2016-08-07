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
 * External message API
 *
 * @package    core_message
 * @category   external
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . "/message/lib.php");

/**
 * Message external functions
 *
 * @package    core_message
 * @category   external
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.2
 */
class core_message_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function send_instant_messages_parameters() {
        return new external_function_parameters(
            array(
                'messages' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'touserid' => new external_value(PARAM_INT, 'id of the user to send the private message'),
                            'text' => new external_value(PARAM_RAW, 'the text of the message'),
                            'textformat' => new external_format_value('text', VALUE_DEFAULT),
                            'clientmsgid' => new external_value(PARAM_ALPHANUMEXT, 'your own client id for the message. If this id is provided, the fail message id will be returned to you', VALUE_OPTIONAL),
                        )
                    )
                )
            )
        );
    }

    /**
     * Send private messages from the current USER to other users
     *
     * @param array $messages An array of message to send.
     * @return array
     * @since Moodle 2.2
     */
    public static function send_instant_messages($messages = array()) {
        global $CFG, $USER, $DB;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        // Ensure the current user is allowed to run this function
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:sendmessage', $context);

        $params = self::validate_parameters(self::send_instant_messages_parameters(), array('messages' => $messages));

        //retrieve all tousers of the messages
        $receivers = array();
        foreach($params['messages'] as $message) {
            $receivers[] = $message['touserid'];
        }
        list($sqluserids, $sqlparams) = $DB->get_in_or_equal($receivers, SQL_PARAMS_NAMED, 'userid_');
        $tousers = $DB->get_records_select("user", "id " . $sqluserids . " AND deleted = 0", $sqlparams);
        $blocklist   = array();
        $contactlist = array();
        $sqlparams['contactid'] = $USER->id;
        $rs = $DB->get_recordset_sql("SELECT *
                                        FROM {message_contacts}
                                       WHERE userid $sqluserids
                                             AND contactid = :contactid", $sqlparams);
        foreach ($rs as $record) {
            if ($record->blocked) {
                // $record->userid is blocking current user
                $blocklist[$record->userid] = true;
            } else {
                // $record->userid have current user as contact
                $contactlist[$record->userid] = true;
            }
        }
        $rs->close();

        $canreadallmessages = has_capability('moodle/site:readallmessages', $context);

        $resultmessages = array();
        foreach ($params['messages'] as $message) {
            $resultmsg = array(); //the infos about the success of the operation

            //we are going to do some checking
            //code should match /messages/index.php checks
            $success = true;

            //check the user exists
            if (empty($tousers[$message['touserid']])) {
                $success = false;
                $errormessage = get_string('touserdoesntexist', 'message', $message['touserid']);
            }

            //check that the touser is not blocking the current user
            if ($success and !empty($blocklist[$message['touserid']]) and !$canreadallmessages) {
                $success = false;
                $errormessage = get_string('userisblockingyou', 'message');
            }

            // Check if the user is a contact
            //TODO MDL-31118 performance improvement - edit the function so we can pass an array instead userid
            $blocknoncontacts = get_user_preferences('message_blocknoncontacts', NULL, $message['touserid']);
            // message_blocknoncontacts option is on and current user is not in contact list
            if ($success && empty($contactlist[$message['touserid']]) && !empty($blocknoncontacts)) {
                // The user isn't a contact and they have selected to block non contacts so this message won't be sent.
                $success = false;
                $errormessage = get_string('userisblockingyounoncontact', 'message',
                        fullname(core_user::get_user($message['touserid'])));
            }

            //now we can send the message (at least try)
            if ($success) {
                //TODO MDL-31118 performance improvement - edit the function so we can pass an array instead one touser object
                $success = message_post_message($USER, $tousers[$message['touserid']],
                        $message['text'], external_validate_format($message['textformat']));
            }

            //build the resultmsg
            if (isset($message['clientmsgid'])) {
                $resultmsg['clientmsgid'] = $message['clientmsgid'];
            }
            if ($success) {
                $resultmsg['msgid'] = $success;
            } else {
                // WARNINGS: for backward compatibility we return this errormessage.
                //          We should have thrown exceptions as these errors prevent results to be returned.
                // See http://docs.moodle.org/dev/Errors_handling_in_web_services#When_to_send_a_warning_on_the_server_side .
                $resultmsg['msgid'] = -1;
                $resultmsg['errormessage'] = $errormessage;
            }

            $resultmessages[] = $resultmsg;
        }

        return $resultmessages;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function send_instant_messages_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'msgid' => new external_value(PARAM_INT, 'test this to know if it succeeds:  id of the created message if it succeeded, -1 when failed'),
                    'clientmsgid' => new external_value(PARAM_ALPHANUMEXT, 'your own id for the message', VALUE_OPTIONAL),
                    'errormessage' => new external_value(PARAM_TEXT, 'error message - if it failed', VALUE_OPTIONAL)
                )
            )
        );
    }

    /**
     * Create contacts parameters description.
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function create_contacts_parameters() {
        return new external_function_parameters(
            array(
                'userids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'User ID'),
                    'List of user IDs'
                ),
                'userid' => new external_value(PARAM_INT, 'The id of the user we are creating the contacts for, 0 for the
                    current user', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Create contacts.
     *
     * @param array $userids array of user IDs.
     * @param int $userid The id of the user we are creating the contacts for
     * @return external_description
     * @since Moodle 2.5
     */
    public static function create_contacts($userids, $userid = 0) {
        global $CFG;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        $params = array('userids' => $userids, 'userid' => $userid);
        $params = self::validate_parameters(self::create_contacts_parameters(), $params);

        $warnings = array();
        foreach ($params['userids'] as $id) {
            if (!message_add_contact($id, 0, $userid)) {
                $warnings[] = array(
                    'item' => 'user',
                    'itemid' => $id,
                    'warningcode' => 'contactnotcreated',
                    'message' => 'The contact could not be created'
                );
            }
        }
        return $warnings;
    }

    /**
     * Create contacts return description.
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function create_contacts_returns() {
        return new external_warnings();
    }

    /**
     * Delete contacts parameters description.
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function delete_contacts_parameters() {
        return new external_function_parameters(
            array(
                'userids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'User ID'),
                    'List of user IDs'
                ),
                'userid' => new external_value(PARAM_INT, 'The id of the user we are deleting the contacts for, 0 for the
                    current user', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Delete contacts.
     *
     * @param array $userids array of user IDs.
     * @param int $userid The id of the user we are deleting the contacts for
     * @return null
     * @since Moodle 2.5
     */
    public static function delete_contacts($userids, $userid = 0) {
        global $CFG;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        $params = array('userids' => $userids, 'userid' => $userid);
        $params = self::validate_parameters(self::delete_contacts_parameters(), $params);

        foreach ($params['userids'] as $id) {
            message_remove_contact($id, $userid);
        }

        return null;
    }

    /**
     * Delete contacts return description.
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function delete_contacts_returns() {
        return null;
    }

    /**
     * Block contacts parameters description.
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function block_contacts_parameters() {
        return new external_function_parameters(
            array(
                'userids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'User ID'),
                    'List of user IDs'
                ),
                'userid' => new external_value(PARAM_INT, 'The id of the user we are blocking the contacts for, 0 for the
                    current user', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Block contacts.
     *
     * @param array $userids array of user IDs.
     * @param int $userid The id of the user we are blocking the contacts for
     * @return external_description
     * @since Moodle 2.5
     */
    public static function block_contacts($userids, $userid = 0) {
        global $CFG;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        $params = array('userids' => $userids, 'userid' => $userid);
        $params = self::validate_parameters(self::block_contacts_parameters(), $params);

        $warnings = array();
        foreach ($params['userids'] as $id) {
            if (!message_block_contact($id, $userid)) {
                $warnings[] = array(
                    'item' => 'user',
                    'itemid' => $id,
                    'warningcode' => 'contactnotblocked',
                    'message' => 'The contact could not be blocked'
                );
            }
        }
        return $warnings;
    }

    /**
     * Block contacts return description.
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function block_contacts_returns() {
        return new external_warnings();
    }

    /**
     * Unblock contacts parameters description.
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function unblock_contacts_parameters() {
        return new external_function_parameters(
            array(
                'userids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'User ID'),
                    'List of user IDs'
                ),
                'userid' => new external_value(PARAM_INT, 'The id of the user we are unblocking the contacts for, 0 for the
                    current user', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Unblock contacts.
     *
     * @param array $userids array of user IDs.
     * @param int $userid The id of the user we are unblocking the contacts for
     * @return null
     * @since Moodle 2.5
     */
    public static function unblock_contacts($userids, $userid = 0) {
        global $CFG;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        $params = array('userids' => $userids, 'userid' => $userid);
        $params = self::validate_parameters(self::unblock_contacts_parameters(), $params);

        foreach ($params['userids'] as $id) {
            message_unblock_contact($id, $userid);
        }

        return null;
    }

    /**
     * Unblock contacts return description.
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function unblock_contacts_returns() {
        return null;
    }

    /**
     * Get messagearea conversations parameters.
     *
     * @return external_function_parameters
     */
    public static function data_for_messagearea_conversations_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'The id of the user who we are viewing conversations for'),
                'limitfrom' => new external_value(PARAM_INT, 'Limit from', VALUE_DEFAULT, 0),
                'limitnum' => new external_value(PARAM_INT, 'Limit number', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Get messagearea conversations.
     *
     * @param int $userid The id of the user who we are viewing conversations for
     * @param int $limitfrom
     * @param int $limitnum
     * @return external_function_parameters
     */
    public static function data_for_messagearea_conversations($userid, $limitfrom = 0, $limitnum = 0) {
        global $CFG, $PAGE;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        $params = array(
            'userid' => $userid,
            'limitfrom' => $limitfrom,
            'limitnum' => $limitnum
        );
        self::validate_parameters(self::data_for_messagearea_conversations_parameters(), $params);

        self::validate_context(context_user::instance($userid));

        $contacts = \core_message\api::get_conversations($userid, 0, $limitfrom, $limitnum);

        $renderer = $PAGE->get_renderer('core_message');
        return $contacts->export_for_template($renderer);
    }

    /**
     * Get messagearea conversations returns.
     *
     * @return external_function_parameters
     */
    public static function data_for_messagearea_conversations_returns() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'The id of the user who we are viewing conversations for'),
                'conversationsselected' => new external_value(PARAM_BOOL, 'Determines if conversations were selected,
                    otherwise contacts were'),
                'contacts' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'userid' => new external_value(PARAM_INT, 'The user\'s id'),
                            'fullname' => new external_value(PARAM_NOTAGS, 'The user\'s name'),
                            'profileimageurl' => new external_value(PARAM_URL, 'User picture URL'),
                            'profileimageurlsmall' => new external_value(PARAM_URL, 'Small user picture URL'),
                            'lastmessage' => new external_value(PARAM_NOTAGS, 'The user\'s last message', VALUE_OPTIONAL),
                            'isonline' => new external_value(PARAM_BOOL, 'The user\'s online status', VALUE_OPTIONAL)
                        )
                    )
                )
            )
        );
    }

    /**
     * Get messagearea contacts parameters.
     *
     * @return external_function_parameters
     */
    public static function data_for_messagearea_contacts_parameters() {
        return self::data_for_messagearea_conversations_parameters();
    }

    /**
     * Get messagearea contacts parameters.
     *
     * @param int $userid The id of the user who we are viewing conversations for
     * @param int $limitfrom
     * @param int $limitnum
     * @return external_function_parameters
     */
    public static function data_for_messagearea_contacts($userid, $limitfrom = 0, $limitnum = 0) {
        global $CFG, $PAGE;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        $params = array(
            'userid' => $userid,
            'limitfrom' => $limitfrom,
            'limitnum' => $limitnum
        );
        self::validate_parameters(self::data_for_messagearea_contacts_parameters(), $params);

        self::validate_context(context_user::instance($userid));

        $contacts = \core_message\api::get_contacts($userid, $limitfrom, $limitnum);

        $renderer = $PAGE->get_renderer('core_message');
        return $contacts->export_for_template($renderer);
    }

    /**
     * Get messagearea contacts returns.
     *
     * @return external_function_parameters
     */
    public static function data_for_messagearea_contacts_returns() {
        return self::data_for_messagearea_conversations_returns();
    }

    /**
     * Get messagearea messages parameters.
     *
     * @return external_function_parameters
     */
    public static function data_for_messagearea_messages_parameters() {
        return new external_function_parameters(
            array(
                'currentuserid' => new external_value(PARAM_INT, 'The current user\'s id'),
                'otheruserid' => new external_value(PARAM_INT, 'The other user\'s id'),
                'limitfrom' => new external_value(PARAM_INT, 'Limit from', VALUE_DEFAULT, 0),
                'limitnum' => new external_value(PARAM_INT, 'Limit number', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Get messagearea messages.
     *
     * @param int $currentuserid The current user's id
     * @param int $otheruserid The other user's id
     * @param int $limitfrom
     * @param int $limitnum
     * @return external_description
     */
    public static function data_for_messagearea_messages($currentuserid, $otheruserid, $limitfrom = 0, $limitnum = 0) {
        global $CFG, $PAGE;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        $params = array(
            'currentuserid' => $currentuserid,
            'otheruserid' => $otheruserid,
            'limitfrom' => $limitfrom,
            'limitnum' => $limitnum
        );
        self::validate_parameters(self::data_for_messagearea_messages_parameters(), $params);

        self::validate_context(context_user::instance($currentuserid));

        $messages = \core_message\api::get_messages($currentuserid, $otheruserid, $limitfrom, $limitnum);

        $renderer = $PAGE->get_renderer('core_message');
        return $messages->export_for_template($renderer);
    }

    /**
     * Get messagearea messages returns.
     *
     * @return external_description
     */
    public static function data_for_messagearea_messages_returns() {
        return new external_function_parameters(
            array(
                'iscurrentuser' => new external_value(PARAM_BOOL, 'Is the currently logged in user the user we are viewing the messages on behalf of?'),
                'currentuserid' => new external_value(PARAM_INT, 'The current user\'s id'),
                'otheruserid' => new external_value(PARAM_INT, 'The other user\'s id'),
                'otheruserfullname' => new external_value(PARAM_NOTAGS, 'The other user\'s fullname'),
                'messages' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'The id of the message'),
                            'text' => new external_value(PARAM_RAW, 'The text of the message'),
                            'displayblocktime' => new external_value(PARAM_BOOL, 'Should we display the block time?'),
                            'blocktime' => new external_value(PARAM_NOTAGS, 'The time to display above the message'),
                            'position' => new external_value(PARAM_ALPHA, 'The position of the text'),
                            'timesent' => new external_value(PARAM_NOTAGS, 'The time the message was sent'),
                            'isread' => new external_value(PARAM_INT, 'Determines if the message was read or not'),
                        )
                    )
                )
            )
        );
    }

    /**
     * Get the most recent message in a conversation parameters.
     *
     * @return external_function_parameters
     */
    public static function data_for_messagearea_get_most_recent_message_parameters() {
        return new external_function_parameters(
            array(
                'currentuserid' => new external_value(PARAM_INT, 'The current user\'s id'),
                'otheruserid' => new external_value(PARAM_INT, 'The other user\'s id'),
            )
        );
    }

    /**
     * Get the most recent message in a conversation.
     *
     * @param int $currentuserid The current user's id
     * @param int $otheruserid The other user's id
     * @return external_single_structure
     */
    public static function data_for_messagearea_get_most_recent_message($currentuserid, $otheruserid) {
        global $CFG, $PAGE;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        $params = array(
            'currentuserid' => $currentuserid,
            'otheruserid' => $otheruserid
        );
        self::validate_parameters(self::data_for_messagearea_get_most_recent_message_parameters(), $params);

        self::validate_context(context_user::instance($currentuserid));

        $message = \core_message\api::get_most_recent_message($currentuserid, $otheruserid);

        $renderer = $PAGE->get_renderer('core_message');
        return $message->export_for_template($renderer);
    }

    /**
     * Get messagearea get most recent message returns.
     *
     * @return external_single_structure
     */
    public static function data_for_messagearea_get_most_recent_message_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'The id of the message'),
                'text' => new external_value(PARAM_RAW, 'The text of the message'),
                'displayblocktime' => new external_value(PARAM_BOOL, 'Should we display the block time?'),
                'blocktime' => new external_value(PARAM_NOTAGS, 'The time to display above the message'),
                'position' => new external_value(PARAM_ALPHA, 'The position of the text'),
                'timesent' => new external_value(PARAM_NOTAGS, 'The time the message was sent'),
                'isread' => new external_value(PARAM_INT, 'Determines if the message was read or not'),
            )
        );
    }

    /**
     * The get profile parameters.
     *
     * @return external_function_parameters
     */
    public static function data_for_messagearea_get_profile_parameters() {
        return new external_function_parameters(
            array(
                'currentuserid' => new external_value(PARAM_INT, 'The current user\'s id'),
                'otheruserid' => new external_value(PARAM_INT, 'The id of the user whose profile we want to view'),
            )
        );
    }

    /**
     * Get the profile information for a contact.
     *
     * @param int $currentuserid The current user's id
     * @param int $otheruserid The id of the user whose profile we are viewing
     * @return external_single_structure
     */
    public static function data_for_messagearea_get_profile($currentuserid, $otheruserid) {
        global $CFG, $PAGE;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        $params = array(
            'currentuserid' => $currentuserid,
            'otheruserid' => $otheruserid
        );
        self::validate_parameters(self::data_for_messagearea_get_profile_parameters(), $params);

        self::validate_context(context_user::instance($otheruserid));

        $profile = \core_message\api::get_profile($currentuserid, $otheruserid);

        $renderer = $PAGE->get_renderer('core_message');
        return $profile->export_for_template($renderer);
    }

    /**
     * Get profile returns.
     *
     * @return external_single_structure
     */
    public static function data_for_messagearea_get_profile_returns() {
        return new external_single_structure(
            array(
                'iscurrentuser' => new external_value(PARAM_BOOL, 'Is the currently logged in user the user we are viewing the profile on behalf of?'),
                'currentuserid' => new external_value(PARAM_INT, 'The current user\'s id'),
                'otheruserid' => new external_value(PARAM_INT, 'The id of the user whose profile we are viewing'),
                'email' => new external_value(core_user::get_property_type('email'), 'An email address'),
                'country' => new external_value(core_user::get_property_type('country'), 'Home country code of the user'),
                'city' => new external_value(core_user::get_property_type('city'), 'Home city of the user'),
                'fullname' => new external_value(PARAM_NOTAGS, 'The user\'s name'),
                'profileimageurl' => new external_value(PARAM_URL, 'User picture URL'),
                'profileimageurlsmall' => new external_value(PARAM_URL, 'Small user picture URL'),
                'isblocked' => new external_value(PARAM_BOOL, 'Is the user blocked?'),
                'iscontact' => new external_value(PARAM_BOOL, 'Is the user a contact?')
            )
        );
    }

    /**
     * Get contacts parameters description.
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function get_contacts_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Get contacts.
     *
     * @param array $userids array of user IDs.
     * @return external_description
     * @since Moodle 2.5
     */
    public static function get_contacts() {
        global $CFG, $PAGE;

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        require_once($CFG->dirroot . '/user/lib.php');

        list($online, $offline, $strangers) = message_get_contacts();
        $allcontacts = array('online' => $online, 'offline' => $offline, 'strangers' => $strangers);
        foreach ($allcontacts as $mode => $contacts) {
            foreach ($contacts as $key => $contact) {
                $newcontact = array(
                    'id' => $contact->id,
                    'fullname' => fullname($contact),
                    'unread' => $contact->messagecount
                );

                $userpicture = new user_picture($contact);
                $userpicture->size = 1; // Size f1.
                $newcontact['profileimageurl'] = $userpicture->get_url($PAGE)->out(false);
                $userpicture->size = 0; // Size f2.
                $newcontact['profileimageurlsmall'] = $userpicture->get_url($PAGE)->out(false);

                $allcontacts[$mode][$key] = $newcontact;
            }
        }
        return $allcontacts;
    }

    /**
     * Get contacts return description.
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function get_contacts_returns() {
        return new external_single_structure(
            array(
                'online' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'User ID'),
                            'fullname' => new external_value(PARAM_NOTAGS, 'User full name'),
                            'profileimageurl' => new external_value(PARAM_URL, 'User picture URL', VALUE_OPTIONAL),
                            'profileimageurlsmall' => new external_value(PARAM_URL, 'Small user picture URL', VALUE_OPTIONAL),
                            'unread' => new external_value(PARAM_INT, 'Unread message count')
                        )
                    ),
                    'List of online contacts'
                ),
                'offline' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'User ID'),
                            'fullname' => new external_value(PARAM_NOTAGS, 'User full name'),
                            'profileimageurl' => new external_value(PARAM_URL, 'User picture URL', VALUE_OPTIONAL),
                            'profileimageurlsmall' => new external_value(PARAM_URL, 'Small user picture URL', VALUE_OPTIONAL),
                            'unread' => new external_value(PARAM_INT, 'Unread message count')
                        )
                    ),
                    'List of offline contacts'
                ),
                'strangers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'User ID'),
                            'fullname' => new external_value(PARAM_NOTAGS, 'User full name'),
                            'profileimageurl' => new external_value(PARAM_URL, 'User picture URL', VALUE_OPTIONAL),
                            'profileimageurlsmall' => new external_value(PARAM_URL, 'Small user picture URL', VALUE_OPTIONAL),
                            'unread' => new external_value(PARAM_INT, 'Unread message count')
                        )
                    ),
                    'List of users that are not in the user\'s contact list but have sent a message'
                )
            )
        );
    }

    /**
     * Search contacts parameters description.
     *
     * @return external_function_parameters
     * @since Moodle 2.5
     */
    public static function search_contacts_parameters() {
        return new external_function_parameters(
            array(
                'searchtext' => new external_value(PARAM_CLEAN, 'String the user\'s fullname has to match to be found'),
                'onlymycourses' => new external_value(PARAM_BOOL, 'Limit search to the user\'s courses',
                    VALUE_DEFAULT, false)
            )
        );
    }

    /**
     * Search contacts.
     *
     * @param string $searchtext query string.
     * @param bool $onlymycourses limit the search to the user's courses only.
     * @return external_description
     * @since Moodle 2.5
     */
    public static function search_contacts($searchtext, $onlymycourses = false) {
        global $CFG, $USER, $PAGE;
        require_once($CFG->dirroot . '/user/lib.php');

        // Check if messaging is enabled.
        if (!$CFG->messaging) {
            throw new moodle_exception('disabled', 'message');
        }

        require_once($CFG->libdir . '/enrollib.php');

        $params = array('searchtext' => $searchtext, 'onlymycourses' => $onlymycourses);
        $params = self::validate_parameters(self::search_contacts_parameters(), $params);

        // Extra validation, we do not allow empty queries.
        if ($params['searchtext'] === '') {
            throw new moodle_exception('querystringcannotbeempty');
        }

        $courseids = array();
        if ($params['onlymycourses']) {
            $mycourses = enrol_get_my_courses(array('id'));
            foreach ($mycourses as $mycourse) {
                $courseids[] = $mycourse->id;
            }
        } else {
            $courseids[] = SITEID;
        }

        // Retrieving the users matching the query.
        $users = message_search_users($courseids, $params['searchtext']);
        $results = array();
        foreach ($users as $user) {
            $results[$user->id] = $user;
        }

        // Reorganising information.
        foreach ($results as &$user) {
            $newuser = array(
                'id' => $user->id,
                'fullname' => fullname($user)
            );

            // Avoid undefined property notice as phone not specified.
            $user->phone1 = null;
            $user->phone2 = null;

            $userpicture = new user_picture($user);
            $userpicture->size = 1; // Size f1.
            $newuser['profileimageurl'] = $userpicture->get_url($PAGE)->out(false);
            $userpicture->size = 0; // Size f2.
            $newuser['profileimageurlsmall'] = $userpicture->get_url($PAGE)->out(false);

            $user = $newuser;
        }

        return $results;
    }

    /**
     * Search contacts return description.
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function search_contacts_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_NOTAGS, 'User full name'),
                    'profileimageurl' => new external_value(PARAM_URL, 'User picture URL', VALUE_OPTIONAL),
                    'profileimageurlsmall' => new external_value(PARAM_URL, 'Small user picture URL', VALUE_OPTIONAL)
                )
            ),
            'List of contacts'
        );
    }

    /**
     * Get messages parameters description.
     *
     * @return external_function_parameters
     * @since 2.8
     */
    public static function get_messages_parameters() {
        return new external_function_parameters(
            array(
                'useridto' => new external_value(PARAM_INT, 'the user id who received the message, 0 for any user', VALUE_REQUIRED),
                'useridfrom' => new external_value(
                    PARAM_INT, 'the user id who send the message, 0 for any user. -10 or -20 for no-reply or support user',
                    VALUE_DEFAULT, 0),
                'type' => new external_value(
                    PARAM_ALPHA, 'type of message to return, expected values are: notifications, conversations and both',
                    VALUE_DEFAULT, 'both'),
                'read' => new external_value(PARAM_BOOL, 'true for getting read messages, false for unread', VALUE_DEFAULT, true),
                'newestfirst' => new external_value(
                    PARAM_BOOL, 'true for ordering by newest first, false for oldest first',
                    VALUE_DEFAULT, true),
                'limitfrom' => new external_value(PARAM_INT, 'limit from', VALUE_DEFAULT, 0),
                'limitnum' => new external_value(PARAM_INT, 'limit number', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Get messages function implementation.
     *
     * @since  2.8
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @param  int      $useridto       the user id who received the message
     * @param  int      $useridfrom     the user id who send the message. -10 or -20 for no-reply or support user
     * @param  string   $type           type of message to return, expected values: notifications, conversations and both
     * @param  bool     $read           true for retreiving read messages, false for unread
     * @param  bool     $newestfirst    true for ordering by newest first, false for oldest first
     * @param  int      $limitfrom      limit from
     * @param  int      $limitnum       limit num
     * @return external_description
     */
    public static function get_messages($useridto, $useridfrom = 0, $type = 'both', $read = true,
                                        $newestfirst = true, $limitfrom = 0, $limitnum = 0) {
        global $CFG, $USER;

        $warnings = array();

        $params = array(
            'useridto' => $useridto,
            'useridfrom' => $useridfrom,
            'type' => $type,
            'read' => $read,
            'newestfirst' => $newestfirst,
            'limitfrom' => $limitfrom,
            'limitnum' => $limitnum
        );

        $params = self::validate_parameters(self::get_messages_parameters(), $params);

        $context = context_system::instance();
        self::validate_context($context);

        $useridto = $params['useridto'];
        $useridfrom = $params['useridfrom'];
        $type = $params['type'];
        $read = $params['read'];
        $newestfirst = $params['newestfirst'];
        $limitfrom = $params['limitfrom'];
        $limitnum = $params['limitnum'];

        $allowedvalues = array('notifications', 'conversations', 'both');
        if (!in_array($type, $allowedvalues)) {
            throw new invalid_parameter_exception('Invalid value for type parameter (value: ' . $type . '),' .
                'allowed values are: ' . implode(',', $allowedvalues));
        }

        // Check if private messaging between users is allowed.
        if (empty($CFG->messaging)) {
            // If we are retreiving only conversations, and messaging is disabled, throw an exception.
            if ($type == "conversations") {
                throw new moodle_exception('disabled', 'message');
            }
            if ($type == "both") {
                $warning = array();
                $warning['item'] = 'message';
                $warning['itemid'] = $USER->id;
                $warning['warningcode'] = '1';
                $warning['message'] = 'Private messages (conversations) are not enabled in this site.
                    Only notifications will be returned';
                $warnings[] = $warning;
            }
        }

        if (!empty($useridto)) {
            if (core_user::is_real_user($useridto)) {
                $userto = core_user::get_user($useridto, '*', MUST_EXIST);
            } else {
                throw new moodle_exception('invaliduser');
            }
        }

        if (!empty($useridfrom)) {
            // We use get_user here because the from user can be the noreply or support user.
            $userfrom = core_user::get_user($useridfrom, '*', MUST_EXIST);
        }

        // Check if the current user is the sender/receiver or just a privileged user.
        if ($useridto != $USER->id and $useridfrom != $USER->id and
             !has_capability('moodle/site:readallmessages', $context)) {
            throw new moodle_exception('accessdenied', 'admin');
        }

        // Which type of messages to retrieve.
        $notifications = -1;
        if ($type != 'both') {
            $notifications = ($type == 'notifications') ? 1 : 0;
        }

        $orderdirection = $newestfirst ? 'DESC' : 'ASC';
        $sort = "mr.timecreated $orderdirection";

        if ($messages = message_get_messages($useridto, $useridfrom, $notifications, $read, $sort, $limitfrom, $limitnum)) {
            $canviewfullname = has_capability('moodle/site:viewfullnames', $context);

            // In some cases, we don't need to get the to/from user objects from the sql query.
            $userfromfullname = '';
            $usertofullname = '';

            // In this case, the useridto field is not empty, so we can get the user destinatary fullname from there.
            if (!empty($useridto)) {
                $usertofullname = fullname($userto, $canviewfullname);
                // The user from may or may not be filled.
                if (!empty($useridfrom)) {
                    $userfromfullname = fullname($userfrom, $canviewfullname);
                }
            } else {
                // If the useridto field is empty, the useridfrom must be filled.
                $userfromfullname = fullname($userfrom, $canviewfullname);
            }
            foreach ($messages as $mid => $message) {

                // Do not return deleted messages.
                if (($useridto == $USER->id and $message->timeusertodeleted) or
                        ($useridfrom == $USER->id and $message->timeuserfromdeleted)) {

                    unset($messages[$mid]);
                    continue;
                }

                // We need to get the user from the query.
                if (empty($userfromfullname)) {
                    // Check for non-reply and support users.
                    if (core_user::is_real_user($message->useridfrom)) {
                        $user = new stdClass();
                        $user = username_load_fields_from_object($user, $message, 'userfrom');
                        $message->userfromfullname = fullname($user, $canviewfullname);
                    } else {
                        $user = core_user::get_user($message->useridfrom);
                        $message->userfromfullname = fullname($user, $canviewfullname);
                    }
                } else {
                    $message->userfromfullname = $userfromfullname;
                }

                // We need to get the user from the query.
                if (empty($usertofullname)) {
                    $user = new stdClass();
                    $user = username_load_fields_from_object($user, $message, 'userto');
                    $message->usertofullname = fullname($user, $canviewfullname);
                } else {
                    $message->usertofullname = $usertofullname;
                }

                // This field is only available in the message_read table.
                if (!isset($message->timeread)) {
                    $message->timeread = 0;
                }

                $message->text = message_format_message_text($message);
                $messages[$mid] = (array) $message;
            }
        }

        $results = array(
            'messages' => $messages,
            'warnings' => $warnings
        );

        return $results;
    }

    /**
     * Get messages return description.
     *
     * @return external_single_structure
     * @since 2.8
     */
    public static function get_messages_returns() {
        return new external_single_structure(
            array(
                'messages' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Message id'),
                            'useridfrom' => new external_value(PARAM_INT, 'User from id'),
                            'useridto' => new external_value(PARAM_INT, 'User to id'),
                            'subject' => new external_value(PARAM_TEXT, 'The message subject'),
                            'text' => new external_value(PARAM_RAW, 'The message text formated'),
                            'fullmessage' => new external_value(PARAM_RAW, 'The message'),
                            'fullmessageformat' => new external_format_value('fullmessage'),
                            'fullmessagehtml' => new external_value(PARAM_RAW, 'The message in html'),
                            'smallmessage' => new external_value(PARAM_RAW, 'The shorten message'),
                            'notification' => new external_value(PARAM_INT, 'Is a notification?'),
                            'contexturl' => new external_value(PARAM_RAW, 'Context URL'),
                            'contexturlname' => new external_value(PARAM_TEXT, 'Context URL link name'),
                            'timecreated' => new external_value(PARAM_INT, 'Time created'),
                            'timeread' => new external_value(PARAM_INT, 'Time read'),
                            'usertofullname' => new external_value(PARAM_TEXT, 'User to full name'),
                            'userfromfullname' => new external_value(PARAM_TEXT, 'User from full name')
                        ), 'message'
                    )
                ),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Get notifications parameters description.
     *
     * @return external_function_parameters
     * @since 3.2
     */
    public static function get_notifications_parameters() {
        return new external_function_parameters(
            array(
                'useridto' => new external_value(PARAM_INT, 'the user id who received the message, 0 for any user', VALUE_REQUIRED),
                'useridfrom' => new external_value(
                    PARAM_INT, 'the user id who send the message, 0 for any user. -10 or -20 for no-reply or support user',
                    VALUE_DEFAULT, 0),
                'status' => new external_value(
                    PARAM_ALPHA, 'filter the results to just "read" or "unread" notifications',
                    VALUE_DEFAULT, ''),
                'embeduserto' => new external_value(
                    PARAM_BOOL, 'true for returning user details for the recipient in each notification',
                    VALUE_DEFAULT, false),
                'embeduserfrom' => new external_value(
                    PARAM_BOOL, 'true for returning user details for the sender in each notification',
                    VALUE_DEFAULT, false),
                'newestfirst' => new external_value(
                    PARAM_BOOL, 'true for ordering by newest first, false for oldest first',
                    VALUE_DEFAULT, true),
                'markasread' => new external_value(
                    PARAM_BOOL, 'mark notifications as read when they are returned by this function',
                    VALUE_DEFAULT, false),
                'limit' => new external_value(PARAM_INT, 'the number of results to return', VALUE_DEFAULT, 0),
                'offset' => new external_value(PARAM_INT, 'offset the result set by a given amount', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Get notifications function.
     *
     * @since  3.2
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @param  int      $useridto       the user id who received the message
     * @param  int      $useridfrom     the user id who send the message. -10 or -20 for no-reply or support user
     * @param  string   $status         filter the results to only read or unread notifications
     * @param  bool     $embeduserto    true to embed the recipient user details in the record for each notification
     * @param  bool     $embeduserfrom  true to embed the send user details in the record for each notification
     * @param  bool     $newestfirst    true for ordering by newest first, false for oldest first
     * @param  bool     $markasread     mark notifications as read when they are returned by this function
     * @param  int      $limit          the number of results to return
     * @param  int      $offset         offset the result set by a given amount
     * @return external_description
     */
    public static function get_notifications($useridto, $useridfrom, $status, $embeduserto, $embeduserfrom, $newestfirst, $markasread, $limit, $offset) {
        global $CFG, $USER, $OUTPUT;

        $params = self::validate_parameters(
            self::get_notifications_parameters(),
            array(
                'useridto' => $useridto,
                'useridfrom' => $useridfrom,
                'status' => $status,
                'embeduserto' => $embeduserto,
                'embeduserfrom' => $embeduserfrom,
                'newestfirst' => $newestfirst,
                'markasread' => $markasread,
                'limit' => $limit,
                'offset' => $offset,
            )
        );

        $context = context_system::instance();
        self::validate_context($context);

        $useridto = $params['useridto'];
        $useridfrom = $params['useridfrom'];
        $status = $params['status'];
        $embeduserto = $params['embeduserto'];
        $embeduserfrom = $params['embeduserfrom'];
        $newestfirst = $params['newestfirst'];
        $markasread = $params['markasread'];
        $limit = $params['limit'];
        $offset = $params['offset'];

        if (!empty($useridto)) {
            if (core_user::is_real_user($useridto)) {
                if ($embeduserto) {
                    $userto = core_user::get_user($useridto, '*', MUST_EXIST);
                }
            } else {
                throw new moodle_exception('invaliduser');
            }
        }

        if (!empty($useridfrom) && $embeduserfrom) {
            // We use get_user here because the from user can be the noreply or support user.
            $userfrom = core_user::get_user($useridfrom, '*', MUST_EXIST);
        }

        // Check if the current user is the sender/receiver or just a privileged user.
        if ($useridto != $USER->id and $useridfrom != $USER->id and
             !has_capability('moodle/site:readallmessages', $context)) {
            throw new moodle_exception('accessdenied', 'admin');
        }

        $sort = $newestfirst ? 'DESC' : 'ASC';
        $notifications = message_get_notifications($useridto, $useridfrom, $status, $embeduserto, $embeduserfrom, $sort, $limit, $offset);

        if ($notifications) {
            // In some cases, we don't need to get the to/from user objects from the sql query.
            $userfromfullname = '';
            $usertofullname = '';

            // In this case, the useridto field is not empty, so we can get the user destinatary fullname from there.
            if (!empty($useridto) && $embeduserto) {
                $usertofullname = fullname($userto);
                // The user from may or may not be filled.
                if (!empty($useridfrom) && $embeduserfrom) {
                    $userfromfullname = fullname($userfrom);
                }
            } else if (!empty($useridfrom) && $embeduserfrom) {
                // If the useridto field is empty, the useridfrom must be filled.
                $userfromfullname = fullname($userfrom);
            }

            foreach ($notifications as $notification) {

                if (($useridto == $USER->id and $notification->timeusertodeleted) or
                        ($useridfrom == $USER->id and $notification->timeuserfromdeleted)) {

                    $notification->deleted = true;
                } else {
                    $notification->deleted = false;
                }

                // We need to get the user from the query.
                if ($embeduserfrom) {
                    if (empty($userfromfullname)) {
                        // Check for non-reply and support users.
                        if (core_user::is_real_user($notification->useridfrom)) {
                            $user = new stdClass();
                            $user = username_load_fields_from_object($user, $notification, 'userfrom');
                            $profileurl = new moodle_url('/user/profile.php', array('id' => $notification->useridfrom));
                            $notification->userfromfullname = fullname($user);
                            $notification->userfromprofileurl = $profileurl->out();
                        } else {
                            $notification->userfromfullname = get_string('coresystem');
                        }
                    } else {
                        $notification->userfromfullname = $userfromfullname;
                    }
                }

                // We need to get the user from the query.
                if ($embeduserto) {
                    if (empty($usertofullname)) {
                        $user = new stdClass();
                        $user = username_load_fields_from_object($user, $notification, 'userto');
                        $notification->usertofullname = fullname($user);
                    } else {
                        $notification->usertofullname = $usertofullname;
                    }
                }

                $notification->timecreatedpretty = get_string('ago', 'message', format_time(time() - $notification->timecreated));
                $notification->text = message_format_message_text($notification);
                $notification->read = $notification->timeread ? true : false;

                if (!empty($notification->component) && substr($notification->component, 0, 4) == 'mod_') {
                    $iconurl = $OUTPUT->pix_url('icon', $notification->component);
                } else {
                    $iconurl = $OUTPUT->pix_url('i/marker', 'core');
                }

                $notification->iconurl = $iconurl->out();

                if ($markasread && !$notification->read) {
                    // Have to clone here because this function mutates the given data. Naughty, naughty...
                    message_mark_message_read(clone $notification, time());
                }
            }
        }

        return array(
            'notifications' => $notifications,
            'unreadcount' => message_count_unread_notifications($useridto, $useridfrom),
        );
    }

    /**
     * Get notifications return description.
     *
     * @return external_single_structure
     * @since 3.2
     */
    public static function get_notifications_returns() {
        return new external_single_structure(
            array(
                'notifications' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Notification id (this is not guaranteed to be unique within this result set)'),
                            'useridfrom' => new external_value(PARAM_INT, 'User from id'),
                            'useridto' => new external_value(PARAM_INT, 'User to id'),
                            'subject' => new external_value(PARAM_TEXT, 'The notification subject'),
                            'text' => new external_value(PARAM_RAW, 'The message text formated'),
                            'fullmessage' => new external_value(PARAM_RAW, 'The message'),
                            'fullmessageformat' => new external_format_value('fullmessage'),
                            'fullmessagehtml' => new external_value(PARAM_RAW, 'The message in html'),
                            'smallmessage' => new external_value(PARAM_RAW, 'The shorten message'),
                            'contexturl' => new external_value(PARAM_RAW, 'Context URL'),
                            'contexturlname' => new external_value(PARAM_TEXT, 'Context URL link name'),
                            'timecreated' => new external_value(PARAM_INT, 'Time created'),
                            'timecreatedpretty' => new external_value(PARAM_TEXT, 'Time created in a pretty format'),
                            'timeread' => new external_value(PARAM_INT, 'Time read'),
                            'usertofullname' => new external_value(PARAM_TEXT, 'User to full name', VALUE_OPTIONAL),
                            'userfromfullname' => new external_value(PARAM_TEXT, 'User from full name', VALUE_OPTIONAL),
                            'userfromprofileurl' => new external_value(PARAM_URL, 'User from profile url', VALUE_OPTIONAL),
                            'read' => new external_value(PARAM_BOOL, 'notification read status'),
                            'deleted' => new external_value(PARAM_BOOL, 'notification deletion status'),
                            'iconurl' => new external_value(PARAM_URL, 'URL for notification icon'),
                            'component' => new external_value(PARAM_TEXT, 'The component that generated the notification', VALUE_OPTIONAL),
                            'eventtype' => new external_value(PARAM_TEXT, 'The type of notification', VALUE_OPTIONAL),
                        ), 'message'
                    )
                ),
                'unreadcount' => new external_value(PARAM_INT, 'the user whose blocked users we want to retrieve'),
            )
        );
    }

    /**
     * Mark all notifications as read parameters description.
     *
     * @return external_function_parameters
     * @since 3.2
     */
    public static function mark_all_notifications_as_read_parameters() {
        return new external_function_parameters(
            array(
                'useridto' => new external_value(PARAM_INT, 'the user id who received the message, 0 for any user', VALUE_REQUIRED),
                'useridfrom' => new external_value(
                    PARAM_INT, 'the user id who send the message, 0 for any user. -10 or -20 for no-reply or support user',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Mark all notifications as read function.
     *
     * @since  3.2
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @param  int      $useridto       the user id who received the message
     * @param  int      $useridfrom     the user id who send the message. -10 or -20 for no-reply or support user
     * @return external_description
     */
    public static function mark_all_notifications_as_read($useridto, $useridfrom) {
        global $CFG, $USER;

        $params = self::validate_parameters(
            self::mark_all_notifications_as_read_parameters(),
            array(
                'useridto' => $useridto,
                'useridfrom' => $useridfrom,
            )
        );

        $context = context_system::instance();
        self::validate_context($context);

        $useridto = $params['useridto'];
        $useridfrom = $params['useridfrom'];

        if (!empty($useridto)) {
            if (core_user::is_real_user($useridto)) {
                $userto = core_user::get_user($useridto, '*', MUST_EXIST);
            } else {
                throw new moodle_exception('invaliduser');
            }
        }

        if (!empty($useridfrom)) {
            // We use get_user here because the from user can be the noreply or support user.
            $userfrom = core_user::get_user($useridfrom, '*', MUST_EXIST);
        }

        // Check if the current user is the sender/receiver or just a privileged user.
        if ($useridto != $USER->id and $useridfrom != $USER->id and
            // deleteanymessage seems more reasonable here than readallmessages.
             !has_capability('moodle/site:deleteanymessage', $context)) {
            throw new moodle_exception('accessdenied', 'admin');
        }

        message_mark_all_read_for_user($useridto, $useridfrom, 'notification');

        return true;
    }

    /**
     * Mark all notifications as read return description.
     *
     * @return external_single_structure
     * @since 3.2
     */
    public static function mark_all_notifications_as_read_returns() {
        return new external_value(PARAM_BOOL, 'True if the messages were marked read, false otherwise');
    }

    /**
     * Get unread notification count parameters description.
     *
     * @return external_function_parameters
     * @since 3.2
     */
    public static function get_unread_notification_count_parameters() {
        return new external_function_parameters(
            array(
                'useridto' => new external_value(PARAM_INT, 'the user id who received the message, 0 for any user', VALUE_REQUIRED),
                'useridfrom' => new external_value(
                    PARAM_INT, 'the user id who send the message, 0 for any user. -10 or -20 for no-reply or support user',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Get unread notification count function.
     *
     * @since  3.2
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @param  int      $useridto       the user id who received the message
     * @param  int      $useridfrom     the user id who send the message. -10 or -20 for no-reply or support user
     * @return external_description
     */
    public static function get_unread_notification_count($useridto, $useridfrom) {
        global $CFG, $USER;

        $params = self::validate_parameters(
            self::get_unread_notification_count_parameters(),
            array(
                'useridto' => $useridto,
                'useridfrom' => $useridfrom,
            )
        );

        $context = context_system::instance();
        self::validate_context($context);

        $useridto = $params['useridto'];
        $useridfrom = $params['useridfrom'];

        if (!empty($useridto)) {
            if (core_user::is_real_user($useridto)) {
                $userto = core_user::get_user($useridto, '*', MUST_EXIST);
            } else {
                throw new moodle_exception('invaliduser');
            }
        }

        if (!empty($useridfrom)) {
            // We use get_user here because the from user can be the noreply or support user.
            $userfrom = core_user::get_user($useridfrom, '*', MUST_EXIST);
        }

        // Check if the current user is the sender/receiver or just a privileged user.
        if ($useridto != $USER->id and $useridfrom != $USER->id and
             !has_capability('moodle/site:readallmessages', $context)) {
            throw new moodle_exception('accessdenied', 'admin');
        }

        return message_count_unread_notifications($useridto, $useridfrom);
    }

    /**
     * Get unread notification count return description.
     *
     * @return external_single_structure
     * @since 3.2
     */
    public static function get_unread_notification_count_returns() {
        return new external_value(PARAM_INT, 'the user whose blocked users we want to retrieve');
    }

    /**
     * Get blocked users parameters description.
     *
     * @return external_function_parameters
     * @since 2.9
     */
    public static function get_blocked_users_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT,
                                'the user whose blocked users we want to retrieve',
                                VALUE_REQUIRED),
            )
        );
    }

    /**
     * Retrieve a list of users blocked
     *
     * @param  int $userid the user whose blocked users we want to retrieve
     * @return external_description
     * @since 2.9
     */
    public static function get_blocked_users($userid) {
        global $CFG, $USER, $PAGE;

        // Warnings array, it can be empty at the end but is mandatory.
        $warnings = array();

        // Validate params.
        $params = array(
            'userid' => $userid
        );
        $params = self::validate_parameters(self::get_blocked_users_parameters(), $params);
        $userid = $params['userid'];

        // Validate context.
        $context = context_system::instance();
        self::validate_context($context);

        // Check if private messaging between users is allowed.
        if (empty($CFG->messaging)) {
            throw new moodle_exception('disabled', 'message');
        }

        $user = core_user::get_user($userid, '*', MUST_EXIST);
        core_user::require_active_user($user);

        // Check if we have permissions for retrieve the information.
        if ($userid != $USER->id and !has_capability('moodle/site:readallmessages', $context)) {
            throw new moodle_exception('accessdenied', 'admin');
        }

        // Now, we can get safely all the blocked users.
        $users = message_get_blocked_users($user);

        $blockedusers = array();
        foreach ($users as $user) {
            $newuser = array(
                'id' => $user->id,
                'fullname' => fullname($user),
            );

            $userpicture = new user_picture($user);
            $userpicture->size = 1; // Size f1.
            $newuser['profileimageurl'] = $userpicture->get_url($PAGE)->out(false);

            $blockedusers[] = $newuser;
        }

        $results = array(
            'users' => $blockedusers,
            'warnings' => $warnings
        );
        return $results;
    }

    /**
     * Get blocked users return description.
     *
     * @return external_single_structure
     * @since 2.9
     */
    public static function get_blocked_users_returns() {
        return new external_single_structure(
            array(
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'User ID'),
                            'fullname' => new external_value(PARAM_NOTAGS, 'User full name'),
                            'profileimageurl' => new external_value(PARAM_URL, 'User picture URL', VALUE_OPTIONAL)
                        )
                    ),
                    'List of blocked users'
                ),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since 2.9
     */
    public static function mark_message_read_parameters() {
        return new external_function_parameters(
            array(
                'messageid' => new external_value(PARAM_INT, 'id of the message (in the message table)'),
                'timeread' => new external_value(PARAM_INT, 'timestamp for when the message should be marked read')
            )
        );
    }

    /**
     * Mark a single message as read, trigger message_viewed event
     *
     * @param  int $messageid id of the message (in the message table)
     * @param  int $timeread timestamp for when the message should be marked read
     * @return external_description
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @since 2.9
     */
    public static function mark_message_read($messageid, $timeread) {
        global $CFG, $DB, $USER;

        // Check if private messaging between users is allowed.
        if (empty($CFG->messaging)) {
            throw new moodle_exception('disabled', 'message');
        }

        // Warnings array, it can be empty at the end but is mandatory.
        $warnings = array();

        // Validate params.
        $params = array(
            'messageid' => $messageid,
            'timeread' => $timeread
        );
        $params = self::validate_parameters(self::mark_message_read_parameters(), $params);

        // Validate context.
        $context = context_system::instance();
        self::validate_context($context);

        $message = $DB->get_record('message', array('id' => $params['messageid']), '*', MUST_EXIST);

        if ($message->useridto != $USER->id) {
            throw new invalid_parameter_exception('Invalid messageid, you don\'t have permissions to mark this message as read');
        }

        $messageid = message_mark_message_read($message, $params['timeread']);

        $results = array(
            'messageid' => $messageid,
            'warnings' => $warnings
        );
        return $results;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since 2.9
     */
    public static function mark_message_read_returns() {
        return new external_single_structure(
            array(
                'messageid' => new external_value(PARAM_INT, 'the id of the message in the message_read table'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     * @since 3.2
     */
    public static function delete_conversation_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'The user id of who we want to delete the conversation for'),
                'otheruserid' => new external_value(PARAM_INT, 'The user id of the other user in the conversation'),
            )
        );
    }

    /**
     * Deletes a conversation.
     *
     * @param int $userid The user id of who we want to delete the conversation for
     * @param int $otheruserid The user id of the other user in the conversation
     * @return array
     * @throws moodle_exception
     * @since 3.2
     */
    public static function delete_conversation($userid, $otheruserid) {
        global $CFG;

        // Check if private messaging between users is allowed.
        if (empty($CFG->messaging)) {
            throw new moodle_exception('disabled', 'message');
        }

        // Warnings array, it can be empty at the end but is mandatory.
        $warnings = array();

        // Validate params.
        $params = array(
            'userid' => $userid,
            'otheruserid' => $otheruserid,
        );
        $params = self::validate_parameters(self::delete_conversation_parameters(), $params);

        // Validate context.
        $context = context_system::instance();
        self::validate_context($context);

        $user = core_user::get_user($params['userid'], '*', MUST_EXIST);
        core_user::require_active_user($user);

        if (\core_message\api::can_delete_conversation($user->id)) {
            $status = \core_message\api::delete_conversation($user->id, $otheruserid);
        } else {
            throw new moodle_exception('You do not have permission to delete messages');
        }

        $results = array(
            'status' => $status,
            'warnings' => $warnings
        );

        return $results;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since 3.2
     */
    public static function delete_conversation_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'True if the conversation was deleted, false otherwise'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since 3.1
     */
    public static function delete_message_parameters() {
        return new external_function_parameters(
            array(
                'messageid' => new external_value(PARAM_INT, 'The message id'),
                'userid' => new external_value(PARAM_INT, 'The user id of who we want to delete the message for'),
                'read' => new external_value(PARAM_BOOL, 'If is a message read', VALUE_DEFAULT, true)
            )
        );
    }

    /**
     * Deletes a message
     *
     * @param  int $messageid the message id
     * @param  int $userid the user id of who we want to delete the message for
     * @param  bool $read if is a message read (default to true)
     * @return external_description
     * @throws moodle_exception
     * @since 3.1
     */
    public static function delete_message($messageid, $userid, $read = true) {
        global $CFG, $DB;

        // Check if private messaging between users is allowed.
        if (empty($CFG->messaging)) {
            throw new moodle_exception('disabled', 'message');
        }

        // Warnings array, it can be empty at the end but is mandatory.
        $warnings = array();

        // Validate params.
        $params = array(
            'messageid' => $messageid,
            'userid' => $userid,
            'read' => $read
        );
        $params = self::validate_parameters(self::delete_message_parameters(), $params);

        // Validate context.
        $context = context_system::instance();
        self::validate_context($context);

        $messagestable = $params['read'] ? 'message_read' : 'message';
        $message = $DB->get_record($messagestable, array('id' => $params['messageid']), '*', MUST_EXIST);

        $user = core_user::get_user($params['userid'], '*', MUST_EXIST);
        core_user::require_active_user($user);

        $status = false;
        if (message_can_delete_message($message, $user->id)) {
            $status = message_delete_message($message, $user->id);;
        } else {
            throw new moodle_exception('You do not have permission to delete this message');
        }

        $results = array(
            'status' => $status,
            'warnings' => $warnings
        );
        return $results;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since 3.1
     */
    public static function delete_message_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'True if the message was deleted, false otherwise'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since 3.2
     */
    public static function message_processor_config_form_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'id of the user, 0 for current user', VALUE_REQUIRED),
                'name' => new external_value(PARAM_TEXT, 'The name of the message processor'),
                'formvalues' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_TEXT, 'name of the form element', VALUE_REQUIRED),
                            'value' => new external_value(PARAM_RAW, 'value of the form element', VALUE_REQUIRED),
                        )
                    ),
                    'Config form values',
                    VALUE_REQUIRED
                ),
            )
        );
    }

    /**
     * Processes a message processor config form.
     *
     * @param  int $userid the user id
     * @param  string $name the name of the processor
     * @param  array $formvalues the form values
     * @return external_description
     * @throws moodle_exception
     * @since 3.2
     */
    public static function message_processor_config_form($userid, $name, $formvalues) {
        $params = self::validate_parameters(
            self::message_processor_config_form_parameters(),
            array(
                'userid' => $userid,
                'name' => $name,
                'formvalues' => $formvalues,
            )
        );

        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        $user = core_user::get_user($params['userid'], '*', MUST_EXIST);
        core_user::require_active_user($user);

        $processor = get_message_processor($name);
        $preferences = [];
        $form = new stdClass();

        foreach ($formvalues as $formvalue) {
            $form->$formvalue['name'] = $formvalue['value'];
        }

        $processor->process_form($form, $preferences);

        if (!empty($preferences)) {
            set_user_preferences($preferences, $userid);
        }
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since 3.2
     */
    public static function message_processor_config_form_returns() {
        return null;
    }
}
