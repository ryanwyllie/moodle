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
        newState.messages = state.messages.slice();
        newState.members = Object.assign({}, state.members);
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

    var buildHeaderPatch = function(state, newState) {
        var oldMemberIds = Object.keys(state.members);
        var newMemberIds = Object.keys(newState.members);
        oldMemberIds.sort();
        newMemberIds.sort();

        if (oldMemberIds == newMemberIds) {
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

    var getScrollToMessage = function(state, newState) {
        var oldMessages = state.messages;
        var newMessages = newState.messages;

        if (newMessages.length < 1) {
            return null;
        }

        if (oldMessages.length < 1) {
            return newMessages[newMessages.length - 1].id;
        }

        var previousNewest = oldMessages[state.messages.length - 1];
        var currentNewest = newMessages[allMessages.length - 1];
        var previousOldest = oldMessages[0];
        var currentOldest = newMessages[0];

        if (previousNewest != currentNewest) {
            return currentNewest.id;
        } else if (previousOldest != currentOldest) {
            return previousOldest.id;
        }

        return null;
    };

    var buildPatch = function(state, newState) {
        var patch = {
            days: {add: [], remove: []},
            messages: {add: [], remove: []},
            header: buildHeaderPatch(state, newState),
            scrollToMessage: getScrollToMessage(state, newState),
            showHeaderPlaceholder: newState.loadingMembers,
            showContentPlaceholder: state.messages.length < 1 && newState.loadingMessages,
            loadingMessages: newState.loadingMessages,
            sendingMessage: newState.sendingMessage,
            hasSentMessage: state.sendingMessage && !newState.sendingMessage
        }

        if (state.messages != newState.messages) {
            var current = sortMessagesByDay(state.messages, state.midnight);
            var next = sortMessagesByDay(newState.messages, newState.midnight);
            var daysDiff = diffArrays(current, next, function(dayCurrent, dayNext) {
                return dayCurrent.timestamp == dayNext.timestamp;
            });

            patch.days = buildDaysPatch(current, daysDiff);
            patch.messages = buildMessagesPatch(daysDiff.matches);
        }

        return patch;
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
            loadingMembers: true
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

    return {
        buildPatch: buildPatch,
        buildInitialState: buildInitialState,
        addMessagesToState: addMessagesToState,
        addMembersToSate: addMembersToSate,
        setLoadingMessages: setLoadingMessages,
        setSendingMessage: setSendingMessage,
        setLoadingMembers: setLoadingMembers
    };
});
