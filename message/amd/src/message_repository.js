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
 * Retrieves messages from the server.
 *
 * @module     core_message/message_repository
 * @class      message_repository
 * @package    message
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    /**
     * Retrieve a list of messages from the server.
     *
     * @param {object} args The request arguments:
     * @return {object} jQuery promise
     */
    var query = function(args) {
        // Normalise the arguments to use limit/offset rather than limitnum/limitfrom.
        if (typeof args.limit === 'undefined') {
            args.limit = 0;
        }

        if (typeof args.offset === 'undefined') {
            args.offset = 0;
        }

        args.limitfrom = args.offset;
        args.limitnum = args.limit;

        delete args.limit;
        delete args.offset;

        var request = {
            methodname: 'core_message_data_for_messagearea_conversations',
            args: args
        };

        var promise = Ajax.call([request])[0];

        promise.fail(Notification.exception);

        return promise;
    };

    /**
     * Count the number of unread conversations (one or more messages from a user)
     * for a given user.
     *
     * @param {object} args The request arguments:
     * @return {object} jQuery promise
     */
    var countUnreadConversations = function(args) {
        var request = {
            methodname: 'core_message_get_unread_conversations_count',
            args: args
        };

        var promise = Ajax.call([request])[0];

        promise.fail(Notification.exception);

        return promise;
    };

    /**
     * Mark all of unread messages for a user as read.
     *
     * @param {object} args The request arguments:
     * @return {object} jQuery promise
     */
    var markAllAsRead = function(args) {
        var request = {
            methodname: 'core_message_mark_all_messages_as_read',
            args: args
        };

        var promise = Ajax.call([request])[0];

        promise.fail(Notification.exception);

        return promise;
    };

    var getContacts = function(userId, limit, offset) {
        var args = {
            userid: userId
        };

        if (typeof limit !== 'undefined') {
            args.liminum = limit;
        }

        if (typeof offset !== 'undefined') {
            args.limitfrom = offset;
        }

        var request = {
            methodname: 'core_message_data_for_messagearea_contacts',
            args: args
        };

        return Ajax.call([request])[0];
    };

    var getProfile = function(loggedInUserId, profileUserId) {
        var request = {
            methodname: 'core_message_data_for_messagearea_get_profile',
            args: {
                currentuserid: loggedInUserId,
                otheruserid: profileUserId
            }
        };

        return Ajax.call([request])[0];
    };

    var blockContacts = function(loggedInUserId, contactUserIds) {
        var request = {
            methodname: 'core_message_block_contacts',
            args: {
                userid: loggedInUserId,
                userids: contactUserIds
            }
        };

        return Ajax.call([request])[0];
    };

    var unblockContacts = function(loggedInUserId, contactUserIds) {
        var request = {
            methodname: 'core_message_unblock_contacts',
            args: {
                userid: loggedInUserId,
                userids: contactUserIds
            }
        };

        return Ajax.call([request])[0];
    };

    var createContacts = function(loggedInUserId, requestUserIds) {
        var request = {
            methodname: 'core_message_create_contacts',
            args: {
                userid: loggedInUserId,
                userids: requestUserIds
            }
        };

        return Ajax.call([request])[0];
    };

    var deleteContacts = function(loggedInUserId, contactUserIds) {
        var request = {
            methodname: 'core_message_delete_contacts',
            args: {
                userid: loggedInUserId,
                userids: contactUserIds
            }
        };

        return Ajax.call([request])[0];
    };

    var getMessages = function(currentUserId, otherUserid, limit, offset, newestFirst, timeFrom) {
        var args = {
            currentuserid: currentUserId,
            otheruserid: otherUserid,
            newest: newestFirst ? true : false
        };

        if (typeof limit !== 'undefined') {
            args.limitnum = limit;
        }

        if (typeof offset !== 'undefined') {
            args.limitfrom = offset;
        }

        if (typeof timeFrom !== 'undefined') {
            args.timefrom = timeFrom;
        }

        var request = {
            methodname: 'core_message_data_for_messagearea_messages',
            args: args
        };

        return Ajax.call([request])[0];
    };

    var searchUsers = function(loggedInUserId, searchString, limit) {
        var args = {
            userid: loggedInUserId,
            search: searchString
        };

        if (typeof limit !== 'undefined') {
            args.limitnum = limit;
        }

        var request = {
            methodname: 'core_message_data_for_messagearea_search_users',
            args: args
        };

        return Ajax.call([request])[0];
    };

    var searchMessages = function(loggedInUserId, searchString, limit, offset) {
        var args = {
            userid: loggedInUserId,
            search: searchString
        };

        if (typeof limit !== 'undefined') {
            args.limitnum = limit;
        }

        if (typeof offset !== 'undefined') {
            args.limitfrom = offset;
        }

        var request = {
            methodname: 'core_message_data_for_messagearea_search_messages',
            args: args
        };

        return Ajax.call([request])[0];
    };

    var sendMessages = function(messages) {
        var request = {
            methodname: 'core_message_send_instant_messages',
            args: {
                messages: messages
            }
        };

        return Ajax.call([request])[0];
    };

    var sendMessage = function(toUserId, text) {
        var message = {
            touserid: toUserId,
            text: text
        };

        return sendMessages([message])
            .then(function(result) {
                return result[0];
            });
    };

    var savePreferences = function(userId, preferences) {
        var request = {
            methodname: 'core_user_update_user_preferences',
            args: {
                userid: userId,
                preferences: preferences
            }
        };
        return Ajax.call([request])[0];
    }

    var getPreferences = function(userId) {
        var request = {
            methodname: 'core_user_get_user_preferences',
            args: {
                userid: userId
            }
        };
        return Ajax.call([request])[0];
    }

    var deleteMessages = function(userId, messageIds) {
        return Ajax.call(messageIds.map(function(messageId) {
            return {
                methodname: 'core_message_delete_message',
                args: {
                    messageid: messageId,
                    userid: userId
                }
            }
        }));
    };

    var deleteCoversation = function(loggedInUserId, otherUserId) {
        var request = {
            methodname: 'core_message_delete_conversation',
            args: {
                userid: loggedInUserId,
                otheruserid: otherUserId
            }
        };
        return Ajax.call([request])[0];
    };

    return {
        query: query,
        countUnreadConversations: countUnreadConversations,
        markAllAsRead: markAllAsRead,
        getContacts: getContacts,
        getProfile: getProfile,
        blockContacts: blockContacts,
        unblockContacts: unblockContacts,
        createContacts: createContacts,
        deleteContacts: deleteContacts,
        getMessages: getMessages,
        searchUsers: searchUsers,
        searchMessages: searchMessages,
        sendMessages: sendMessages,
        sendMessage: sendMessage,
        savePreferences: savePreferences,
        getPreferences: getPreferences,
        deleteMessages: deleteMessages,
        deleteCoversation: deleteCoversation
    };
});
