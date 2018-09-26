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
    var SECONDS_IN_DAY = 86400;
    var LOAD_MESSAGE_LIMIT = 100;

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

    /**
     * Calculated the midnight timestamp of a given timestamp using the user's
     * midnight timestamp. Calculations are based on the user's midnight so that
     * timezone's are preserved.
     *
     * @param {int} timestamp The timestamp to calculate from
     * @param {int} midnight The user's midnight timestamp
     * @return {int} The midnight value of the user's timestamp
     */
    var getDayTimestamp = function(timestamp, midnight) {
        var future = timestamp > midnight;
        var diffSeconds = Math.abs(timestamp - midnight);
        var diffDays = future ? Math.floor(diffSeconds / SECONDS_IN_DAY) : Math.ceil(diffSeconds / SECONDS_IN_DAY);
        var diffDaysInSeconds = diffDays * SECONDS_IN_DAY;
        // Is the timestamp in the future or past?
        var dayTimestamp = future ? midnight + diffDaysInSeconds : midnight - diffDaysInSeconds;
        return dayTimestamp;
    };

    var formatMessages = function(messages, loggedInUserId, members) {
        return messages.map(function(message) {
            return {
                id: message.id,
                isread: message.isread,
                fromloggedinuser: message.useridfrom == loggedInUserId,
                userfrom: members[message.useridfrom],
                userto: members[message.useridto],
                text: message.text,
                timecreated: parseInt(message.timecreated, 10)
            };
        });
    };

    var sortMessagesByDay = function(messages, midnight) {
        var messagesByDay = messages.reduce(function(carry, message) {
            var dayTimestamp = getDayTimestamp(message.timecreated, midnight)

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
                missingFromB.push(dayCurrent);
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

        if (previousNewest != currentNewest) {
            return currentNewest.id;
        } else if (previousOldest != currentOldest) {
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
        if (state.messages.length < 1 && newState.loadingMessages) {
            return true;
        } else if (state.messages.length < 1 && state.loadingMessages && !newState.loadingMessages) {
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
        if (newState.pendingBlockUsers.length) {
            // We currently only support a single user;
            var userId = newState.pendingBlockUsers[0];
            return newState.members[userId];
        } else if (state.pendingBlockUsers.length) {
            return false;
        }

        return null;
    };

    var buildConfirmUnblockUser = function(state, newState) {
        if (newState.pendingUnblockUsers.length) {
            // We currently only support a single user;
            var userId = newState.pendingUnblockUsers[0];
            return newState.members[userId];
        } else if (state.pendingUnblockUsers.length) {
            return false;
        }

        return null;
    };

    var buildConfirmAddContact = function(state, newState) {
        if (newState.pendingAddContacts.length) {
            // We currently only support a single user;
            var userId = newState.pendingAddContacts[0];
            return newState.members[userId];
        } else if (state.pendingAddContacts.length) {
            return false;
        }

        return null;
    };

    var buildConfirmRemoveContact = function(state, newState) {
        if (newState.pendingRemoveContacts.length) {
            // We currently only support a single user;
            var userId = newState.pendingRemoveContacts[0];
            return newState.members[userId];
        } else if (state.pendingRemoveContacts.length) {
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
            isBlocked: buildIsBlocked,
            isContact: buildIsContact
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

    var buildInitialState = function(midnight, loggedInUserId) {
        return {
            midnight: midnight,
            loggedInUserId: loggedInUserId,
            members: {},
            messages: [],
            limit: LOAD_MESSAGE_LIMIT,
            offset: 0,
            loadingMessages: true,
            sendingMessage: false,
            loadingMembers: true,
            pendingBlockUsers: [],
            pendingUnblockUsers: [],
            pendingRemoveContacts: [],
            pendingAddContacts: []
        };
    };

    var addMessagesToState = function(state, messages) {
        var newState = cloneState(state);
        var formattedMessages = formatMessages(messages, state.loggedInUserId, state.members);
        var allMessages = state.messages.concat(formattedMessages);
        var newOffset = state.offset + state.limit;
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
        newState.offset = newOffset;

        return newState;
    };

    var addMembersToSate = function(state, members) {
        var newState = cloneState(state);
        members.forEach(function(member) {
            newState.members[member.userid] = member;
        });
        return newState;
    };

    var setLoadingMessages = function(state, value) {
        var newState = cloneState(state);
        newState.loadingMessages = value;
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

    var addPendingBlockUsers = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            newState.pendingBlockUsers.push(id);
        });
        return newState;
    };

    var addPendingRemoveContacts = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            newState.pendingRemoveContacts.push(id);
        });
        return newState;
    };

    var addPendingUnblockUsers = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            newState.pendingUnblockUsers.push(id);
        });
        return newState;
    };

    var addPendingAddContacts = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            newState.pendingAddContacts.push(id);
        });
        return newState;
    };

    var removePendingBlockUsers = function(state, userIds) {
        var newState = cloneState(state);
        newState.pendingBlockUsers = newState.pendingBlockUsers.filter(function(id) {
            return userIds.indexOf(id) < 0;
        });
        return newState;
    };

    var removePendingRemoveContacts = function(state, userIds) {
        var newState = cloneState(state);
        newState.pendingRemoveContacts = newState.pendingRemoveContacts.filter(function(id) {
            return userIds.indexOf(id) < 0;
        });
        return newState;
    };

    var removePendingUnblockUsers = function(state, userIds) {
        var newState = cloneState(state);
        newState.pendingUnblockUsers = newState.pendingUnblockUsers.filter(function(id) {
            return userIds.indexOf(id) < 0;
        });
        return newState;
    };

    var removePendingAddContacts = function(state, userIds) {
        var newState = cloneState(state);
        newState.pendingAddContacts = newState.pendingAddContacts.filter(function(id) {
            return userIds.indexOf(id) < 0;
        });
        return newState;
    };

    var blockUsers = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            if (newState.members.hasOwnProperty(id)) {
                newState.members[id].isblocked = true;
            }
        });
    };

    var unblockUsers = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            if (newState.members.hasOwnProperty(id)) {
                newState.members[id].isblocked = false;
            }
        });
    };

    var removeContacts = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            if (newState.members.hasOwnProperty(id)) {
                newState.members[id].iscontact = false;
            }
        });
    };

    var addContacts = function(state, userIds) {
        var newState = cloneState(state);
        userIds.forEach(function(id) {
            if (newState.members.hasOwnProperty(id)) {
                newState.members[id].iscontact = true;
            }
        });
    };

    return {
        buildPatch: buildPatch,
        buildInitialState: buildInitialState,
        addMessagesToState: addMessagesToState,
        addMembersToSate: addMembersToSate,
        setLoadingMessages: setLoadingMessages,
        setSendingMessage: setSendingMessage,
        setLoadingMembers: setLoadingMembers,
        addPendingBlockUsers: addPendingBlockUsers,
        addPendingRemoveContacts: addPendingRemoveContacts,
        addPendingUnblockUsers: addPendingUnblockUsers,
        addPendingAddContacts: addPendingAddContacts,
        removePendingBlockUsers: removePendingBlockUsers,
        removePendingRemoveContacts: removePendingRemoveContacts,
        removePendingUnblockUsers: removePendingUnblockUsers,
        removePendingAddContacts: removePendingAddContacts,
        blockUsers: blockUsers,
        unblockUsers: unblockUsers,
        removeContacts: removeContacts,
        addContacts: addContacts
    };
});
