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
 * Controls the message drawer.
 *
 * @module     message_popup/message_drawer_view_overview
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    var cloneState = function(state) {
        var newState = Object.assign({}, state);
        newState.messages = state.messages.map(function(message) {
            return Object.assign({}, message);
        });
        newState.members = Object.keys(state.members).reduce(function(carry, id) {
            carry[id] = Object.assign({}, state.members[id]);
            return carry;
        }, {});
        return newState;
    };

    var formatMessages = function(messages, loggedInUserId, members) {
        return messages.map(function(message) {
            var fromLoggedInUser = message.useridfrom == loggedInUserId;
            return {
                id: parseInt(message.id, 10),
                isRead: message.isread,
                fromLoggedInUser: fromLoggedInUser,
                userFrom: members[message.useridfrom],
                text: message.text,
                timeCreated: parseInt(message.timecreated, 10)
            };
        });
    };

    var buildInitialState = function(midnight, loggedInUserId, id, title) {
        return {
            midnight: midnight,
            loggedInUserId: loggedInUserId,
            id: id,
            title: title,
            members: {},
            messages: [],
            hasTriedToLoadMessages: false,
            loadingMessages: true,
            sendingMessage: false,
            loadingMembers: true,
            loadingConfirmAction: false,
            pendingBlockUserIds: [],
            pendingUnblockUserIds: [],
            pendingRemoveContactIds: [],
            pendingAddContactIds: [],
            pendingDeleteMessageIds: [],
            pendingDeleteConversation: false,
            selectedMessageIds: []
        };
    };

    var addMessages = function(state, messages) {
        var newState = cloneState(state);
        var formattedMessages = formatMessages(messages, state.loggedInUserId, state.members);
        var allMessages = state.messages.concat(formattedMessages);
        // Sort the messages. Oldest to newest.
        allMessages.sort(function(a, b) {
            if (a.timeCreated < b.timeCreated) {
                return -1;
            } else if (a.timeCreated > b.timeCreated) {
                return 1;
            } else {
                return 0;
            }
        });

        // Filter out any duplicate messages.
        newState.messages = allMessages.filter(function(message, index, sortedMessages) {
            return !index || message.id !== sortedMessages[index - 1].id;
        });

        return newState;
    };

    var removeMessages = function(state, messages) {
        var newState = cloneState(state);
        var removeMessageIds = messages.map(function(message) {
            return message.id;
        });
        newState.messages = newState.messages.filter(function(message) {
            return removeMessageIds.indexOf(message.id) < 0;
        });

        return newState;
    };

    var removeMessagesById = function(state, messagesIds) {
        var newState = cloneState(state);
        newState.messages = newState.messages.filter(function(message) {
            return messagesIds.indexOf(message.id) < 0;
        });

        return newState;
    };

    var addMembers = function(state, members) {
        var newState = cloneState(state);
        members.forEach(function(member) {
            newState.members[member.userid] = member;
        });
        return newState;
    };

    var removeMembers = function(state, members) {
        var newState = cloneState(state);
        members.forEach(function(member) {
            delete newState.members[member.userid];
        });
        return newState;
    };

    var setLoadingMessages = function(state, value) {
        var newState = cloneState(state);
        newState.loadingMessages = value;
        if (state.loadingMessages && !value) {
            // If we're going from loading to not loading then
            // it means we've tried to load.
            newState.hasTriedToLoadMessages = true;
        }
        return newState;
    };

    var setSendingMessage = function(state, value) {
        var newState = cloneState(state);
        newState.sendingMessage = value;
        return newState;
    };

    var setLoadingMembers = function(state, value) {
        var newState = cloneState(state);
        newState.loadingMembers = value;
        return newState;
    };

    var setTitle = function(state, value) {
        var newState = cloneState(state);
        newState.title = value;
        return newState;
    };

    var setLoadingConfirmAction = function(state, value) {
        var newState = cloneState(state);
        newState.loadingConfirmAction = value;
        return newState;
    };

    var setPendingDeleteConversation = function(state, value) {
        var newState = cloneState(state);
        newState.pendingDeleteConversation = value;
        return newState;
    };

    var addPendingBlockUsersById = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            newState.pendingBlockUserIds.push(id);
        });
        return newState;
    };

    var addPendingRemoveContactsById = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            newState.pendingRemoveContactIds.push(id);
        });
        return newState;
    };

    var addPendingUnblockUsersById = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            newState.pendingUnblockUserIds.push(id);
        });
        return newState;
    };

    var addPendingAddContactsById = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            newState.pendingAddContactIds.push(id);
        });
        return newState;
    };

    var addPendingDeleteMessagesById = function(state, messageIds) {
        var newState = cloneState(state);
        messageIds.forEach(function(id) {
            newState.pendingDeleteMessageIds.push(id);
        });
        return newState;
    };

    var removePendingBlockUsersById = function(state, userIds) {
        var newState = cloneState(state);
        newState.pendingBlockUserIds = newState.pendingBlockUserIds.filter(function(id) {
            return userIds.indexOf(id) < 0;
        });
        return newState;
    };

    var removePendingRemoveContactsById = function(state, userIds) {
        var newState = cloneState(state);
        newState.pendingRemoveContactIds = newState.pendingRemoveContactIds.filter(function(id) {
            return userIds.indexOf(id) < 0;
        });
        return newState;
    };

    var removePendingUnblockUsersById = function(state, userIds) {
        var newState = cloneState(state);
        newState.pendingUnblockUserIds = newState.pendingUnblockUserIds.filter(function(id) {
            return userIds.indexOf(id) < 0;
        });
        return newState;
    };

    var removePendingAddContactsById = function(state, userIds) {
        var newState = cloneState(state);
        newState.pendingAddContactIds = newState.pendingAddContactIds.filter(function(id) {
            return userIds.indexOf(id) < 0;
        });
        return newState;
    };

    var removePendingDeleteMessagesById = function(state, messageIds) {
        var newState = cloneState(state);
        newState.pendingDeleteMessageIds = newState.pendingDeleteMessageIds.filter(function(id) {
            return messageIds.indexOf(id) < 0;
        });
        return newState;
    };

    var blockUsersById = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            if (newState.members.hasOwnProperty(id)) {
                newState.members[id].isblocked = true;
            }
        });
        return newState;
    };

    var unblockUsersById = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            if (newState.members.hasOwnProperty(id)) {
                newState.members[id].isblocked = false;
            }
        });
        return newState;
    };

    var removeContactsById = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            if (newState.members.hasOwnProperty(id)) {
                newState.members[id].iscontact = false;
            }
        });
        return newState;
    };

    var addContactsById = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            if (newState.members.hasOwnProperty(id)) {
                newState.members[id].iscontact = true;
            }
        });
        return newState;
    };

    var addSelectedMessagesById = function(state, messageIds) {
        var newState = cloneState(state);
        newState.selectedMessageIds = newState.selectedMessageIds.concat(messageIds);
        return newState;
    };

    var removeSelectedMessagesById = function(state, messageIds) {
        var newState = cloneState(state);
        newState.selectedMessageIds = newState.selectedMessageIds.filter(function(id) {
            return messageIds.indexOf(id) < 0;
        });
        return newState;
    };

    var markMessagesAsRead = function(state, readMessages) {
        var newState = cloneState(state);
        var readMessageIds = readMessages.map(function(message) {
            return message.id;
        });
        newState.messages = newState.messages.map(function(message) {
            if (readMessageIds.indexOf(message.id) >= 0) {
                message.isRead = true;
            }

            return message;
        });
        return newState;
    };

    return {
        buildInitialState: buildInitialState,
        addMessages: addMessages,
        removeMessages: removeMessages,
        removeMessagesById: removeMessagesById,
        addMembers: addMembers,
        removeMembers: removeMembers,
        setLoadingMessages: setLoadingMessages,
        setSendingMessage: setSendingMessage,
        setLoadingMembers: setLoadingMembers,
        setTitle: setTitle,
        setLoadingConfirmAction: setLoadingConfirmAction,
        setPendingDeleteConversation: setPendingDeleteConversation,
        addPendingBlockUsersById: addPendingBlockUsersById,
        addPendingRemoveContactsById: addPendingRemoveContactsById,
        addPendingUnblockUsersById: addPendingUnblockUsersById,
        addPendingAddContactsById: addPendingAddContactsById,
        addPendingDeleteMessagesById: addPendingDeleteMessagesById,
        removePendingBlockUsersById: removePendingBlockUsersById,
        removePendingRemoveContactsById: removePendingRemoveContactsById,
        removePendingUnblockUsersById: removePendingUnblockUsersById,
        removePendingAddContactsById: removePendingAddContactsById,
        removePendingDeleteMessagesById: removePendingDeleteMessagesById,
        blockUsersById: blockUsersById,
        unblockUsersById: unblockUsersById,
        removeContactsById: removeContactsById,
        addContactsById: addContactsById,
        addSelectedMessagesById: addSelectedMessagesById,
        removeSelectedMessagesById: removeSelectedMessagesById,
        markMessagesAsRead: markMessagesAsRead
    };
});
