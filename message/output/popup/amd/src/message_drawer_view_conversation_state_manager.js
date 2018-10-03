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
define(
    [
        'core/user_date'
    ],
    function(
        UserDate
    )
{
    var SECONDS_IN_DAY = 86400;

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

    var sortMessagesByDay = function(messages, midnight) {
        var messagesByDay = messages.reduce(function(carry, message) {
            var dayTimestamp = UserDate.getUserMidnightForTimestamp(message.timeCreated, midnight)

            if (carry.hasOwnProperty(dayTimestamp)) {
                carry[dayTimestamp].push(message);
            } else {
                carry[dayTimestamp] = [message];
            }

            return carry;
        }, {});

        return Object.keys(messagesByDay).map(function(dayTimestamp) {
            return {
                timestamp: dayTimestamp,
                messages: messagesByDay[dayTimestamp]
            };
        });
    };

    var diffArrays = function(a, b, matchFunction) {
        // Make copy of it.
        b = b.slice();
        var missingFromA = [];
        var missingFromB = [];
        var matches = [];

        a.forEach(function(current) {
            var found = false;
            var index = 0;

            for (; index < b.length; index++) {
                var next = b[index];

                if (matchFunction(current, next)) {
                    found = true;
                    matches.push({
                        a: current,
                        b: next
                    });
                    break;
                }
            }

            if (found) {
                // This day has been processed so removed it from the list.
                b.splice(index, 1);
            } else {
                // If we couldn't find it in the next messages then it means
                // it needs to be added.
                missingFromB.push(current);
            }
        });

        missingFromA = b;

        return {
            missingFromA: missingFromA,
            missingFromB: missingFromB,
            matches: matches
        };
    };

    var findPositionInArray = function(array, breakFunction) {
        var before = null;

        for (var i = 0; i < array.length; i++) {
            var candidate = array[i];

            if (breakFunction(candidate)) {
                return candidate;
            }
        }

        return before;
    };

    var isArrayEqual = function(a, b) {
        a.sort();
        b.sort();
        var aLength = a.length;
        var bLength = b.length;

        if (aLength < 1 && bLength < 1) {
            return true;
        }

        if (aLength != bLength) {
            return false;
        }

        return a.every(function(item, index) {
            return item == b[index];
        });
    };

    var buildDaysPatch = function(current, daysDiff) {
        return {
            remove: daysDiff.missingFromB,
            add: daysDiff.missingFromA.map(function(day) {
                // Any days left over in the "next" list weren't in the "current" list
                // so they will need to be added.
                var before = findPositionInArray(current, function(candidate) {
                    return day.timestamp < candidate.timestamp;
                });

                return {
                    before: before,
                    value: day
                };
            })
        };
    };

    var buildMessagesPatch = function(matchingDays) {
        var remove = [];
        var add = [];

        matchingDays.forEach(function(days) {
            var dayCurrent = days.a;
            var dayNext = days.b;
            var messagesDiff = diffArrays(dayCurrent.messages, dayNext.messages, function(messageCurrent, messageNext) {
                return messageCurrent.id == messageNext.id;
            });

            remove = remove.concat(messagesDiff.missingFromB);

            messagesDiff.missingFromA.forEach(function(message) {
                var before = findPositionInArray(dayCurrent.messages, function(candidate) {
                    return message.timecreated < candidate.timecreated;
                });

                add.push({
                    before: before,
                    value: message,
                    day: dayCurrent
                });
            });
        });

        return {
            add: add,
            remove: remove
        };
    };

    var buildConversationPatch = function(state, newState) {
        var oldMessageIds = state.messages.map(function(message) {
            return message.id;
        });
        var newMessageIds = newState.messages.map(function(message) {
            return message.id;
        });

        if (!isArrayEqual(oldMessageIds, newMessageIds)) {
            var current = sortMessagesByDay(state.messages, state.midnight);
            var next = sortMessagesByDay(newState.messages, newState.midnight);
            var daysDiff = diffArrays(current, next, function(dayCurrent, dayNext) {
                return dayCurrent.timestamp == dayNext.timestamp;
            });

            return {
                days: buildDaysPatch(current, daysDiff),
                messages: buildMessagesPatch(daysDiff.matches)
            };
        } else {
            return null;
        }
    };

    var buildHeaderPatch = function(state, newState) {
        var oldMemberIds = Object.keys(state.members);
        var newMemberIds = Object.keys(newState.members);

        if (isArrayEqual(oldMemberIds, newMemberIds)) {
            return null;
        }

        var loggedInUserId = newState.loggedInUserId;
        var otherUserIds = newMemberIds.filter(function(id) {
            return id != loggedInUserId;
        });

        if (!otherUserIds.length) {
            return null;
        }

        var context = {};
        var isGroupMessage = otherUserIds.length > 1;

        if (!isGroupMessage) {
            var otherUserId = otherUserIds[0];
            context = newState.members[otherUserId];
        }

        return {
            isGroupMessage: isGroupMessage,
            context: context,
        }
    };

    var buildScrollToMessagePatch = function(state, newState) {
        var oldMessages = state.messages;
        var newMessages = newState.messages;

        if (newMessages.length < 1) {
            return null;
        }

        if (oldMessages.length < 1) {
            return newMessages[newMessages.length - 1].id;
        }

        var previousNewest = oldMessages[state.messages.length - 1];
        var currentNewest = newMessages[newMessages.length - 1];
        var previousOldest = oldMessages[0];
        var currentOldest = newMessages[0];

        if (previousNewest.id != currentNewest.id) {
            return currentNewest.id;
        } else if (previousOldest.id != currentOldest.id) {
            return previousOldest.id;
        }

        return null;
    };

    var buildLoadingMembersPatch = function(state, newState) {
        if (!state.loadingMembers && newState.loadingMembers) {
            return true;
        } else if (state.loadingMembers && !newState.loadingMembers) {
            return false;
        } else {
            return null;
        }
    };

    var buildLoadingFirstMessages = function(state, newState) {
        if (state.hasTriedToLoadMessages === newState.hasTriedToLoadMessages) {
            return null;
        } else if (!newState.hasTriedToLoadMessages && newState.loadingMessages) {
            return true;
        } else if (newState.hasTriedToLoadMessages && !newState.loadingMessages) {
            return false;
        } else {
            return null;
        }
    };

    var buildLoadingMessages = function(state, newState) {
        if (!state.loadingMessages && newState.loadingMessages) {
            return true;
        } else if (state.loadingMessages && !newState.loadingMessages) {
            return false;
        } else {
            return null;
        }
    };

    var buildSendingMessage = function(state, newState) {
        if (!state.sendingMessage && newState.sendingMessage) {
            return true;
        } else if (state.sendingMessage && !newState.sendingMessage) {
            return false;
        } else {
            return null;
        }
    };

    var buildConfirmBlockUser = function(state, newState) {
        if (newState.pendingBlockUserIds.length) {
            // We currently only support a single user;
            var userId = newState.pendingBlockUserIds[0];
            return newState.members[userId];
        } else if (state.pendingBlockUserIds.length) {
            return false;
        }

        return null;
    };

    var buildConfirmUnblockUser = function(state, newState) {
        if (newState.pendingUnblockUserIds.length) {
            // We currently only support a single user;
            var userId = newState.pendingUnblockUserIds[0];
            return newState.members[userId];
        } else if (state.pendingUnblockUserIds.length) {
            return false;
        }

        return null;
    };

    var buildConfirmAddContact = function(state, newState) {
        if (newState.pendingAddContactIds.length) {
            // We currently only support a single user;
            var userId = newState.pendingAddContactIds[0];
            return newState.members[userId];
        } else if (state.pendingAddContactIds.length) {
            return false;
        }

        return null;
    };

    var buildConfirmRemoveContact = function(state, newState) {
        if (newState.pendingRemoveContactIds.length) {
            // We currently only support a single user;
            var userId = newState.pendingRemoveContactIds[0];
            return newState.members[userId];
        } else if (state.pendingRemoveContactIds.length) {
            return false;
        }

        return null;
    };

    var buildConfirmDeleteSelectedMessages = function(state, newState) {
        if (newState.pendingDeleteMessageIds.length) {
            return true;
        } else if (state.pendingDeleteMessageIds.length) {
            return false;
        }

        return null;
    };

    var buildConfirmDeleteConversation = function(state, newState) {
        if (!state.pendingDeleteConversation && newState.pendingDeleteConversation) {
            return true;
        } else if (state.pendingDeleteConversation && !newState.pendingDeleteConversation) {
            return false;
        }

        return null;
    };

    var buildIsBlocked = function(state, newState) {
        var oldMemberIds = Object.keys(state.members);
        var newMemberIds = Object.keys(newState.members);
        var loggedInUserId = newState.loggedInUserId;
        var otherUserIds = newMemberIds.filter(function(id) {
            return id != loggedInUserId;
        });
        var isGroupMessage = otherUserIds.length > 1;

        if (isGroupMessage || !oldMemberIds.length || !newMemberIds.length) {
            // No rendering required for this state in group messages.
            return null;
        }

        var otherUserId = otherUserIds[0];
        var before = state.members[otherUserId];
        var after = newState.members[otherUserId];

        if (before.isblocked && !after.isblocked) {
            return false;
        } else if (!before.isblocked && after.isblocked) {
            return true;
        } else {
            return null;
        }
    };

    var buildIsContact = function(state, newState) {
        var oldMemberIds = Object.keys(state.members);
        var newMemberIds = Object.keys(newState.members);
        var loggedInUserId = newState.loggedInUserId;
        var otherUserIds = newMemberIds.filter(function(id) {
            return id != loggedInUserId;
        });
        var isGroupMessage = otherUserIds.length > 1;

        if (isGroupMessage || !oldMemberIds.length || !newMemberIds.length) {
            // No rendering required for this state in group messages.
            return null;
        }

        var otherUserId = otherUserIds[0];
        var before = state.members[otherUserId];
        var after = newState.members[otherUserId];

        if (before.iscontact && !after.iscontact) {
            return false;
        } else if (!before.iscontact && after.iscontact) {
            return true;
        } else {
            return null;
        }
    };

    var buildLoadingConfirmationAction = function(state, newState) {
        if (!state.loadingConfirmAction && newState.loadingConfirmAction) {
            return true;
        } else if (state.loadingConfirmAction && !newState.loadingConfirmAction) {
            return false;
        } else {
            return null;
        }
    };

    var buildInEditMode = function(state, newState) {
        var oldHasSelectedMessages = state.selectedMessageIds.length > 0;
        var newHasSelectedMessages = newState.selectedMessageIds.length > 0;
        var numberOfMessagesHasChanged = state.messages.length != newState.messages.length;

        if (!oldHasSelectedMessages && newHasSelectedMessages) {
            return true;
        } else if (oldHasSelectedMessages && !newHasSelectedMessages) {
            return false;
        } else if (oldHasSelectedMessages && numberOfMessagesHasChanged) {
            return true;
        } else {
            return null;
        }
    };

    var buildSelectedMessages = function(state, newState) {
        var oldSelectedMessages = state.selectedMessageIds;
        var newSelectedMessages = newState.selectedMessageIds;

        if (isArrayEqual(oldSelectedMessages, newSelectedMessages)) {
            return null;
        }

        var diff = diffArrays(oldSelectedMessages, newSelectedMessages, function(a, b) {
            return a == b;
        })

        return {
            count: newSelectedMessages.length,
            add: diff.missingFromA,
            remove: diff.missingFromB
        }
    };

    var buildPatch = function(state, newState) {
        var config = {
            conversation: buildConversationPatch,
            header: buildHeaderPatch,
            scrollToMessage: buildScrollToMessagePatch,
            loadingMembers: buildLoadingMembersPatch,
            loadingFirstMessages: buildLoadingFirstMessages,
            loadingMessages: buildLoadingMessages,
            sendingMessage: buildSendingMessage,
            confirmBlockUser: buildConfirmBlockUser,
            confirmUnblockUser: buildConfirmUnblockUser,
            confirmAddContact: buildConfirmAddContact,
            confirmRemoveContact: buildConfirmRemoveContact,
            confirmDeleteSelectedMessages: buildConfirmDeleteSelectedMessages,
            confirmDeleteConversation: buildConfirmDeleteConversation,
            isBlocked: buildIsBlocked,
            isContact: buildIsContact,
            loadingConfirmAction: buildLoadingConfirmationAction,
            inEditMode: buildInEditMode,
            selectedMessages: buildSelectedMessages
        }

        return Object.keys(config).reduce(function(patch, key) {
            var buildFunc = config[key];
            var value = buildFunc(state, newState);

            if (value !== null) {
                patch[key] = value;
            }

            return patch;
        }, {});
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
            if (a.timecreated < b.timecreated) {
                return -1;
            } else if (a.timecreated > b.timecreated) {
                return 1;
            } else {
                return 0;
            }
        });

        newState.messages = allMessages;

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
        buildPatch: buildPatch,
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
