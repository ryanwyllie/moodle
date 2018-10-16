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

    var CONVERSATION_TYPES = {
        PRIVATE: 1,
        PUBLIC: 2
    };

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

        if (typeof args.type === 'undefined') {
            args.type = null;
        }

        if (typeof args.favouritesonly === 'undefined') {
            args.favouritesonly = false;
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

    /**
     * Get contacts for given user.
     *
     * @param {int} userId The user id
     * @param {int} limit Limit for results
     * @param {int} offset Offset for results
     * @return {object} jQuery promise
     */
    var getContacts = function(userId, limit, offset) {
        var args = {
            userid: userId
        };

        if (typeof limit !== 'undefined') {
            args.limitnum = limit;
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

    /**
     * Request profile information as a user for a given user.
     *
     * @param {int} userId The requesting user
     * @param {int} profileUserId The id of the user who's profile is being requested
     * @return {object} jQuery promise
     */
    var getProfile = function(userId, profileUserId) {
        var request = {
            methodname: 'core_message_data_for_messagearea_get_profile',
            args: {
                currentuserid: userId,
                otheruserid: profileUserId
            }
        };

        return Ajax.call([request])[0];
    };

    /**
     * Block a user.
     *
     * @param {int} userId The requesting user
     * @param {int} blockedUserId Id of user to block
     * @return {object} jQuery promise
     */
    var blockUser = function(userId, blockedUserId) {
        var requests = [
            {
                methodname: 'core_message_block_user',
                args: {
                    userid: userId,
                    blockeduserid: blockedUserId
                }
            },
            {
                methodname: 'core_message_data_for_messagearea_get_profile',
                args: {
                    currentuserid: userId,
                    otheruserid: blockedUserId
                }
            }
        ];

        // Wrap both requests in a single promise so that we can catch an error
        // from either request.
        return  $.when.apply(null, Ajax.call(requests)).then(function(reponse1, profile) {
            // Only return the profile.
            return profile;
        });
    };

    /**
     * Unblock a user.
     *
     * @param {int} userId The requesting user
     * @param {int} unblockedUserId Id of user to unblock
     * @return {object} jQuery promise
     */
    var unblockUser = function(userId, unblockedUserId) {
        var requests = [
            {
                methodname: 'core_message_unblock_user',
                args: {
                    userid: userId,
                    unblockeduserid: unblockedUserId
                }
            },
            {
                methodname: 'core_message_data_for_messagearea_get_profile',
                args: {
                    currentuserid: userId,
                    otheruserid: unblockedUserId
                }
            }
        ];

        // Wrap both requests in a single promise so that we can catch an error
        // from either request.
        return  $.when.apply(null, Ajax.call(requests)).then(function(reponse1, profile) {
            // Only return the profile.
            return profile;
        });
    };

    /**
     * Create a request to add a user as a contact.
     *
     * @param {int} userId The requesting user
     * @param {int[]} requestUserIds List of user ids to add
     * @return {object} jQuery promise
     */
    var createContactRequest = function(userId, requestUserIds) {
        var request = {
            methodname: 'core_message_create_contact_request',
            args: {
                userid: userId,
                requesteduserid: requestUserIds
            }
        };

        return Ajax.call([request])[0];
    };

    /**
     * Remove a list of users as contacts.
     *
     * @param {int} userId The requesting user
     * @param {int[]} contactUserIds List of user ids to add
     * @return {object} jQuery promise
     */
    var deleteContacts = function(userId, contactUserIds) {
        var requests = [
            {
                methodname: 'core_message_delete_contacts',
                args: {
                    userid: userId,
                    userids: contactUserIds
                }
            }
        ];

        contactUserIds.forEach(function(contactUserId) {
            requests.push({
                methodname: 'core_message_data_for_messagearea_get_profile',
                args: {
                    currentuserid: userId,
                    otheruserid: contactUserId
                }
            });
        });

        return $.when.apply(null, Ajax.call(requests)).then(function() {
            // Return all of the profiles as an array.
            return [].slice.call(arguments, 1);
        });
    };

    /**
     * Get messages between two users.
     *
     * @param {int} currentUserId The requesting user
     * @param {int} conversationId Other user in the conversation
     * @param {int} limit Limit for results
     * @param {int} offset Offset for results
     * @param {bool} newestFirst Order results by newest first
     * @param {int} timeFrom Only return messages after this timestamp
     * @return {object} jQuery promise
     */
    var getMessages = function(currentUserId, conversationId, limit, offset, newestFirst, timeFrom) {
        var args = {
            currentuserid: currentUserId,
            convid: conversationId,
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
            methodname: 'core_message_get_conversation_messages',
            args: args
        };
        return Ajax.call([request])[0];
    };

    /**
     * Search for users.
     *
     * @param {int} userId The requesting user
     * @param {string} searchString Search string
     * @param {int} limit Limit for results
     * @param {int} offset Offset for results
     * @return {object} jQuery promise
     */
    var searchUsers = function(userId, searchString, limit, offset) {
        var args = {
            userid: userId,
            search: searchString
        };

        if (typeof limit !== 'undefined') {
            if (typeof offset !== 'undefined') {
                args.limitnum = limit + offset;
            } else {
                args.limitnum = limit;
            }
        }

        var request = {
            methodname: 'core_message_data_for_messagearea_search_users',
            args: args
        };

        return Ajax.call([request])[0].then(function(response) {
            // TODO: Fix the webservice so that we don't need to do this hack.
            if (offset) {
                return {
                    contacts: response.contacts.slice(offset, offset + limit),
                    noncontacts: response.noncontacts.slice(offset, offset + limit),
                    courses: response.courses.slice(offset, offset + limit)
                };
            } else {
                return response;
            }
        });
    };

    /**
     * Search for messages.
     *
     * @param {int} userId The requesting user
     * @param {string} searchString Search string
     * @param {int} limit Limit for results
     * @param {int} offset Offset for results
     * @return {object} jQuery promise
     */
    var searchMessages = function(userId, searchString, limit, offset) {
        var args = {
            userid: userId,
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

    /**
     * Send a list of messages.
     *
     * @param {object[]} messages List of messages to send
     * @return {object} jQuery promise
     */
    var sendMessages = function(messages) {
        var request = {
            methodname: 'core_message_send_instant_messages',
            args: {
                messages: messages
            }
        };

        return Ajax.call([request])[0];
    };

    /**
     * Send a single message.
     *
     * @param {int} toUserId The recipient user id
     * @param {string} text The message text
     * @return {object} jQuery promise
     */
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

    /**
     * Save message preferences.
     *
     * @param {int} userId The owner of the preferences
     * @param {object[]} preferences New preferences values
     * @return {object} jQuery promise
     */
    var savePreferences = function(userId, preferences) {
        var request = {
            methodname: 'core_user_update_user_preferences',
            args: {
                userid: userId,
                preferences: preferences
            }
        };
        return Ajax.call([request])[0];
    };

    /**
     * Get the user's preferences.
     *
     * @param {int} userId The target user
     * @return {object} jQuery promise
     */
    var getPreferences = function(userId) {
        var request = {
            methodname: 'core_user_get_user_preferences',
            args: {
                userid: userId
            }
        };
        return Ajax.call([request])[0];
    };

    /**
     * Delete a list of messages.
     *
     * @param {int} userId The user to delete messages for
     * @param {int[]} messageIds List of message ids to delete
     * @return {object} jQuery promise
     */
    var deleteMessages = function(userId, messageIds) {
        return Ajax.call(messageIds.map(function(messageId) {
            return {
                methodname: 'core_message_delete_message',
                args: {
                    messageid: messageId,
                    userid: userId
                }
            };
        }));
    };

    /**
     * Delete a conversation between two users.
     *
     * @param {int} userId The user to delete messages for
     * @param {int} otherUserId The other member of the conversation
     * @return {object} jQuery promise
     */
    var deleteCoversation = function(userId, otherUserId) {
        var request = {
            methodname: 'core_message_delete_conversation',
            args: {
                userid: userId,
                otheruserid: otherUserId
            }
        };
        return Ajax.call([request])[0];
    };

    /**
     * Get the list of contact requests for a user.
     *
     * @param {int} userId The user id
     * @param {bool} includeConversations Include conversations between the user
     *                                    the sender of the contact request.
     * @return {object} jQuery promise
     */
    var getContactRequests = function(userId, includeConversations) {
        var request = {
            methodname: 'core_message_get_contact_requests',
            args: {
                userid: userId,
                includeconversations: includeConversations
            }
        };
        return Ajax.call([request])[0];
    };

    /**
     * Accept a contact request.
     *
     * @param {int} sendingUserId The user that sent the request
     * @param {int} recipientUserId The user that received the request
     * @return {object} jQuery promise
     */
    var acceptContactRequest = function(sendingUserId, recipientUserId) {
        var requests = [
            {
                methodname: 'core_message_confirm_contact_request',
                args: {
                    userid: sendingUserId,
                    requesteduserid: recipientUserId
                }
            },
            {
                methodname: 'core_message_data_for_messagearea_get_profile',
                args: {
                    currentuserid: recipientUserId,
                    otheruserid: sendingUserId
                }
            }
        ];

        // Wrap both requests in a single promise so that we can catch an error
        // from either request.
        return  $.when.apply(null, Ajax.call(requests)).then(function(reponse1, profile) {
            // Only return the profile.
            return profile;
        });
    };

    /**
     * Decline a contact request.
     *
     * @param {int} sendingUserId The user that sent the request
     * @param {int} recipientUserId The user that received the request
     * @return {object} jQuery promise
     */
    var declineContactRequest = function(sendingUserId, recipientUserId) {
        var requests = [
            {
                methodname: 'core_message_decline_contact_request',
                args: {
                    userid: sendingUserId,
                    requesteduserid: recipientUserId
                }
            },
            {
                methodname: 'core_message_data_for_messagearea_get_profile',
                args: {
                    currentuserid: recipientUserId,
                    otheruserid: sendingUserId
                }
            }
        ];

        // Wrap both requests in a single promise so that we can catch an error
        // from either request.
        return  $.when.apply(null, Ajax.call(requests)).then(function(reponse1, profile) {
            // Only return the profile.
            return profile;
        });
    };

    /**
     * Get a conversation.
     *
     * @param {int} conversationId The conversation id
     * @param {int} loggedInUserId The logged in user
     * @param {int} messageLimit Limit for results
     * @param {int} messageOffset Offset for results
     * @param {bool} newestFirst Order the messages by newest first
     * @return {object} jQuery promise
     */
    var getConversation = function(
        conversationId,
        loggedInUserId,
        messageLimit,
        messageOffset,
        newestFirst
    ) {
        return getConversations(loggedInUserId, 1)
            .then(function(response) {
                var conversations = response.conversations;
                var filteredConversations = conversations.filter(function(conversation) {
                    return conversation.id == conversationId;
                });

                return filteredConversations[0];
            });
    };

    /**
     * Get the conversations for a user.
     *
     * @param {int} userId The logged in user
     * @param {int|null} type The type of conversation to get
     * @param {int} limit Limit for results
     * @param {int} offset Offset for results
     * @param {bool|null} favourites If favourites should be included or not
     * @return {object} jQuery promise
     */
    var getConversations = function(
        userId,
        type,
        limit,
        offset,
        favourites
    ) {
        var args = {
            userid: userId,
            type: type
        };

        if (typeof limit != 'undefined' && limit !== null) {
            args.limitnum = limit;
        }

        if (typeof offset != 'undefined' && offset !== null) {
            args.limitfrom = offset;
        }

        if (typeof favourites != 'undefined' && favourites !== null) {
            args.favourites = favourites;
        }

        var request = {
            methodname: 'core_message_get_conversations',
            args: args
        };

        return Ajax.call([request])[0]
            .then(function(result) {
                if (result.conversations.length) {
                    result.conversations = result.conversations.map(function(conversation) {
                        // This can be removed when the backend returns these values.
                        conversation.members = conversation.members.map(function(member) {
                            member.contactrequests = [];
                            member.requirescontact = false;
                            member.canmessage = true;
                            return member;
                        });

                        if (conversation.type == CONVERSATION_TYPES.PRIVATE) {
                            var otherUser = conversation.members.length ? conversation.members[0] : null;

                            if (otherUser) {
                                conversation.name = conversation.name ? conversation.name : otherUser.fullname;
                                conversation.imageurl = conversation.imageurl ? conversation.imageurl : otherUser.profileimageurl;
                            }
                        }

                        return conversation;
                    });
                }

                return result;
            });
    };

    /**
     * Get the conversations for a user.
     *
     * @param {int} conversationId The conversation id
     * @param {int} loggedInUserId The logged in user
     * @param {int} limit Limit for results
     * @param {int} offset Offset for results
     * @param {bool} includeContactRequests If contact requests should be included in result
     * @return {object} jQuery promise
     */
    var getConversationMembers = function(conversationId, loggedInUserId, limit, offset, incldueContactRequests) {
        var args = {
            userid: loggedInUserId,
            conversationid: conversationId
        };

        if (typeof limit != 'undefined' && limit !== null) {
            args.limitnum = limit;
        }

        if (typeof offset != 'undefined' && offset !== null) {
            args.limitfrom = offset;
        }

        if (typeof incldueContactRequests != 'undefined' && incldueContactRequests !== null) {
            args.includecontactrequests = incldueContactRequests;
        }

        var request = {
            methodname: 'core_message_get_conversation_members',
            args: args
        };

        return Ajax.call([request])[0];
    };

    /**
     * Set a list of conversations to set as favourites for the given user.
     *
     * @param {int} userId The user id
     * @param {array} conversationIds List of conversation ids to set as favourite
     * @return {object} jQuery promise
     */
    var setFavouriteConversations = function(userId, conversationIds) {

        var request = {
            methodname: 'core_message_set_favourite_conversations',
            args: {
                userid: userId,
                conversations: conversationIds
            }
        };
        return Ajax.call([request])[0];
    };

    /**
     * Set a list of conversations to unset as favourites for the given user.
     *
     * @param {int} userId The user id
     * @param {array} conversationIds List of conversation ids to unset as favourite
     * @return {object} jQuery promise
     */
    var unsetFavouriteConversations = function(userId, conversationIds) {

        var request = {
            methodname: 'core_message_unset_favourite_conversations',
            args: {
                userid: userId,
                conversations: conversationIds
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
        blockUser: blockUser,
        unblockUser: unblockUser,
        createContactRequest: createContactRequest,
        deleteContacts: deleteContacts,
        getMessages: getMessages,
        searchUsers: searchUsers,
        searchMessages: searchMessages,
        sendMessages: sendMessages,
        sendMessage: sendMessage,
        savePreferences: savePreferences,
        getPreferences: getPreferences,
        deleteMessages: deleteMessages,
        deleteCoversation: deleteCoversation,
        getContactRequests: getContactRequests,
        acceptContactRequest: acceptContactRequest,
        declineContactRequest: declineContactRequest,
        getConversation: getConversation,
        getConversations: getConversations,
        getConversationMembers: getConversationMembers,
        setFavouriteConversations: setFavouriteConversations,
        unsetFavouriteConversations: unsetFavouriteConversations
    };
});
