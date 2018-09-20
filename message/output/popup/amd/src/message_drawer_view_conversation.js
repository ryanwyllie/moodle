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
    'jquery',
    'core/auto_rows',
    'core/custom_interaction_events',
    'core/notification',
    'core/templates',
    'core_message/message_repository'
],
function(
    $,
    AutoRows,
    CustomEvents,
    Notification,
    Templates,
    Repository
) {

    var SECONDS_IN_DAY = 86400;
    var LOAD_MESSAGE_LIMIT = 100;
    var NEWEST_FIRST = true;

    var SELECTORS = {
        HEADER: '[data-region="view-conversation-header"]',
        MESSAGES: '[data-region="view-conversation-messages"]',
        PLACEHOLDER_CONTAINER: '[data-region="placeholder-container"]',
        MESSAGE_TEXT_AREA: '[data-region="send-message-txt"]',
        SEND_MESSAGE_BUTTON: '[data-action="send-message"]',
        SEND_MESSAGE_ICON_CONTAINER: '[data-region="send-icon-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        DAY_CONTAINER: '[data-region="day-container"]',
        DAY_MESSAGES_CONTAINER: '[data-region="day-messages-container"]',
        CONTENT_CONTAINER: '[data-region="content-container"]',
        CONTENT_CONTAINER: '[data-region="content-container"]',
    };

    var TEMPLATES = {
        HEADER: 'message_popup/message_drawer_view_conversation_header',
        DAY: 'message_popup/message_drawer_view_conversation_day',
        MESSAGE: 'message_popup/message_drawer_view_conversation_message',
        MESSAGES: 'message_popup/message_drawer_view_conversation_messages',
        ADDCONTACT: 'message_popup/message_drawer_add_contact'
    };

    var viewState = {};

    var setGlobalViewState = function(state) {
        viewState = state;
    };

    var getGlobalViewState = function() {
        return viewState;
    };

    // HOW DO I REUSE THIS STUFF FROM OTHER MODULES?
    var getLoggedInUserId = function(root) {
        return root.attr('data-user-id');
    };

    var getOtherUserId = function() {
        var state = getGlobalViewState();
        var loggedInUserId = state.loggedInUserId;
        var otherUserIds = Object.keys(state.members).filter(function(userId) {
            return loggedInUserId != userId;
        });

        return otherUserIds.length ? otherUserIds[0] : null;
    };

    var getContentContainer = function(root) {
        return root.find(SELECTORS.CONTENT_CONTAINER);
    };

    var showContentContainer = function(root) {
        getContentContainer(root).removeClass('hidden');
    };

    var hideContentContainer = function(root) {
        getContentContainer(root).addClass('hidden');
    };

    var getPlaceholderContainer = function(root) {
        return root.find(SELECTORS.PLACEHOLDER_CONTAINER);
    };

    var showPlaceholderContainer = function(root) {
        getPlaceholderContainer(root).removeClass('hidden');
    };

    var hidePlaceholderContainer = function(root) {
        getPlaceholderContainer(root).addClass('hidden');
    };

    var getMessageTextArea = function(root) {
        return root.find(SELECTORS.MESSAGE_TEXT_AREA);
    };

    var getMessagesContainer = function(root) {
        return root.find(SELECTORS.MESSAGES);
    };

    var getMessageElement = function(root, messageId) {
        var messagesContainer = getMessagesContainer(root);
        return messagesContainer.find('[data-message-id="' + messageId + '"]');
    };

    var getDayElement = function(root, dayTimeCreated) {
        var messagesContainer = getMessagesContainer(root);
        return messagesContainer.find('[data-day-id="' + dayTimeCreated + '"]');
    };

    var disableSendMessage = function(root) {
        root.find(SELECTORS.SEND_MESSAGE_BUTTON).prop('disabled', true);
        root.find(SELECTORS.MESSAGE_TEXT_AREA).prop('disabled', true);
    };

    var enableSendMessage = function(root) {
        root.find(SELECTORS.SEND_MESSAGE_BUTTON).prop('disabled', false);
        root.find(SELECTORS.MESSAGE_TEXT_AREA).prop('disabled', false);
    };

    var startSendMessageLoading = function(root) {
        disableSendMessage(root);
        root.find(SELECTORS.SEND_MESSAGE_ICON_CONTAINER).addClass('hidden');
        root.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var stopSendMessageLoading = function(root) {
        enableSendMessage(root);
        root.find(SELECTORS.SEND_MESSAGE_ICON_CONTAINER).removeClass('hidden');
        root.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var scrollToMessage = function(root, messageId) {
        var messagesContainer = getMessagesContainer(root);
        var messageElement = getMessageElement(root, messageId);
        // Scroll the message container down to the top of the message element.
        var scrollTop = messagesContainer.scrollTop() + messageElement.position().top;
        messagesContainer.scrollTop(scrollTop);
    };

    var scrollToMostRecentMessage = function(root) {
        var state = getGlobalViewState();

        if (state.messages.length) {
            var lastMessage = state.messages[state.messages.length - 1];
            scrollToMessage(root, lastMessage.id);
        }
    };

    // Header stuff.
    var loadProfile = function(loggedInUserId, otherUserId) {
        return Repository.getProfile(loggedInUserId, otherUserId);
    };

    var renderHeader = function(root, profile) {
        headerContainer = root.find(SELECTORS.HEADER)
        return Templates.render(TEMPLATES.HEADER, profile)
            .then(function(html, js) {
                Templates.replaceNodeContents(headerContainer, html, js);
            });
    };

    // Message loading.
    var loadMessages = function(currentUserId, otherUserId, limit, offset, newestFirst) {
        return Repository.getMessages(currentUserId, otherUserId, limit, offset, newestFirst)
            .then(function(result) {
                return result.messages;
            })
            .catch(Notification.exception);
    };

    var renderMessages = function(root, messages) {
        var state = getGlobalViewState();
        var newState = addMessagesToState(state, messages);
        var patch = getStatePatch(state, newState);
        setGlobalViewState(newState);

        return renderPatch(root, patch);
    };

    var loadAndRenderMessages = function(root, loggedInUserId, otherUserId, limit, offset, newestFirst) {
        disableSendMessage(root);
        var loadedAllMessages = false;

        return loadMessages(loggedInUserId, otherUserId, limit + 1, offset, newestFirst)
            .then(function(messages) {
                if (messages.length <= limit) {
                    loadedAllMessages = true;
                }
                return renderMessages(root, messages.slice(1));
            })
            .then(function() {
                var state = getGlobalViewState();
                state.loadedAllMessages = loadedAllMessages;
                return setGlobalViewState(state);
            })
            .then(function() {
                return enableSendMessage(root);
            })
            .catch(function(error) {
                enableSendMessage(root);
                return error;
            });
    };

    var sendMessage = function(toUserId, text) {
        return Repository.sendMessage(toUserId, text);
    };

    var registerEventListeners = function(root) {
        var loggedInUserId = getLoggedInUserId(root);
        var isLoadingMoreMessages = false;
        AutoRows.init(root);

        CustomEvents.define(root, [
            CustomEvents.events.activate,
            CustomEvents.events.scrollTop
        ]);

        root.on(CustomEvents.events.activate, SELECTORS.SEND_MESSAGE_BUTTON, function(e, data) {
            var textArea = getMessageTextArea(root);
            var text = textArea.val().trim();
            var otherUserId = getOtherUserId();

            if (text !== '') {
                startSendMessageLoading(root);
                sendMessage(otherUserId, text)
                    .then(function(result) {
                        var message = {
                            id: result.msgid,
                            fullname: root.attr('data-full-name'),
                            profileimageurl: root.attr('data-profile-url'),
                            text: result.text,
                            timecreated: parseInt(result.timecreated, 10),
                            useridfrom: loggedInUserId,
                            useridto: otherUserId,
                            isread: true
                        };
                        return renderMessages(root, [message]);
                    })
                    .then(function() {
                        return scrollToMostRecentMessage(root);
                    })
                    .then(function() {
                        return textArea.val('');
                    })
                    .then(function() {
                        return stopSendMessageLoading(root);
                    })
                    .then(function() {
                        return textArea.focus();
                    })
                    .catch(function(error) {
                        Notification.exception(error);
                        stopSendMessageLoading(root);
                    });
            }

            data.originalEvent.preventDefault();
        });


        var messagesContainer = getMessagesContainer(root);
        CustomEvents.define(messagesContainer, [CustomEvents.events.scrollTop]);
        messagesContainer.on(CustomEvents.events.scrollTop, function(e, data) {
            var state = getGlobalViewState();
            var hasMembers = Object.keys(state.members).length > 1;

            if (!isLoadingMoreMessages && !state.loadedAllMessages && hasMembers) {
                var hasMessages = state.messages.length > 0;
                var firstMessageeId = null;
                if (hasMessages) {
                    var firstMessage = state.messages[0];
                    firstMessageId = firstMessage.id;
                }

                loadAndRenderMessages(root, loggedInUserId, getOtherUserId(), state.limit, state.offset, NEWEST_FIRST)
                    .then(function() {
                        isLoadingMoreMessages = false;

                        if (firstMessageId) {
                            scrollToMessage(root, firstMessageId);
                        }

                        return;
                    })
                    .catch(function() {
                        isLoadingMoreMessages = false;
                        return;
                    });
            }

            data.originalEvent.preventDefault();
        })
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

    var getLoggedInUserProfile = function(root) {
        return {
            userid: getLoggedInUserId(root),
            fullname: root.attr('data-full-name'),
            profileimageurl: root.attr('data-profile-url'),
        }
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

    var buildInitialState = function(root, midnight, loggedInUserId) {
        return {
            midnight: midnight,
            loggedInUserId: loggedInUserId,
            members: {},
            messages: [],
            limit: LOAD_MESSAGE_LIMIT,
            offset: 0,
            loadedAllMessages: false
        };
    };

    var cloneState = function(state) {
        var newState = Object.assign({}, state);
        newState.messages = state.messages.slice();
        newState.members = Object.assign({}, state.members);
        return newState;
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

    var getStatePatch = function(state, newState) {
        var patch = {
            days: {
                add: [],
                remove: []
            },
            messages: {
                add: [],
                remove: []
            }
        }

        var current = sortMessagesByDay(state.messages, state.midnight);
        var next = sortMessagesByDay(newState.messages, newState.midnight);
        var daysDiff = diffArrays(current, next, function(dayCurrent, dayNext) {
            return dayCurrent.timestamp == dayNext.timestamp;
        });

        patch.days.remove = daysDiff.missingFromB;

        // Any days left over in the "next" list weren't in the "current" list
        // so they will need to be added.
        daysDiff.missingFromA.forEach(function(day) {
            var before = findPositionInArray(current, function(candidate) {
                return day.timestamp < candidate.timestamp;
            });

            patch.days.add.push({
                before: before,
                value: day
            });
        });

        daysDiff.matches.forEach(function(days) {
            var dayCurrent = days.a;
            var dayNext = days.b;
            var messagesDiff = diffArrays(dayCurrent.messages, dayNext.messages, function(messageCurrent, messageNext) {
                return messageCurrent.id == messageNext.id;
            });

            patch.messages.remove = patch.messages.remove.concat(messagesDiff.missingFromB);

            messagesDiff.missingFromA.forEach(function(message) {
                var before = findPositionInArray(dayCurrent.messages, function(candidate) {
                    return message.timecreated < candidate.timecreated;
                });

                patch.messages.add.push({
                    before: before,
                    value: message,
                    day: dayCurrent
                });
            });
        });

        return patch;
    };

    var renderPatch = function(root, patch) {
        var messagesContainer = getMessagesContainer(root);
        // Begin the rendering first because it's async.
        var daysRenderPromises = patch.days.add.map(function(data) {
            return Templates.render(TEMPLATES.DAY, data.value);
        });
        var messagesRenderPromises = patch.messages.add.map(function(data) {
            return Templates.render(TEMPLATES.MESSAGE, data.value);
        });

        $.when.apply($, daysRenderPromises).then(function() {
            // Wait until all of the rendering is done for each of the days
            // to ensure they are added to the page in the correct order.
            patch.days.add.forEach(function(data, index) {
                daysRenderPromises[index].then(function(html) {
                    if (data.before) {
                        var element = getDayElement(root, data.before.timestamp);
                        return $(html).insertBefore(element);
                    } else {
                        return messagesContainer.append(html);
                    }
                });
            });
        });

        $.when.apply($, messagesRenderPromises).then(function() {
            // Wait until all of the rendering is done for each of the days
            // to ensure they are added to the page in the correct order.
            patch.messages.add.forEach(function(data, index) {
                messagesRenderPromises[index].then(function(html) {
                    if (data.before) {
                        var element = getMessageElement(root, data.before.id);
                        return $(html).insertBefore(element);
                    } else {
                        var dayContainer = getDayElement(root, data.day.timestamp);
                        var dayMessagesContainer = dayContainer.find(SELECTORS.DAY_MESSAGES_CONTAINER);
                        return dayMessagesContainer.append(html);
                    }
                });
            });
        });

        patch.days.remove.forEach(function(data) {
            getDayElement(root, data.timecreated).remove();
        });

        patch.messages.remove.forEach(function(data) {
            getMessageElement(root, data.id).remove();
        });

        return $.when.apply($, daysRenderPromises.concat(messagesRenderPromises));
    };

    var show = function(root, otherUserId) {
        root = $(root);

        var loggedInUserId = getLoggedInUserId(root);
        var loggedInUserProfile = getLoggedInUserProfile(root);
        var midnight = parseInt(root.attr('data-midnight'), 10);
        var initialState = buildInitialState(root, midnight, loggedInUserId);
        setGlobalViewState(initialState);
        getMessagesContainer(root).empty();
        disableSendMessage(root);
        hideContentContainer(root);
        showPlaceholderContainer(root);

        if (!root.attr('data-init')) {
            registerEventListeners(root);
            root.attr('data-init', true);
        }


        return loadProfile(loggedInUserId, otherUserId)
            .then(function(otherUserProfile) {
                var newState = addMembersToSate(initialState, [otherUserProfile, loggedInUserProfile]);
                setGlobalViewState(newState);
                return otherUserProfile;
            })
            .then(function(otherUserProfile) {
                renderHeader(root, otherUserProfile);
                return otherUserProfile;
            })
            .then(function() {
                return loadAndRenderMessages(root, loggedInUserId, otherUserId, LOAD_MESSAGE_LIMIT, 0, NEWEST_FIRST);
            })
            .then(function() {
                hidePlaceholderContainer(root);
                return showContentContainer(root);
            })
            .then(function() {
                return scrollToMostRecentMessage(root);
            })
            .catch(Notification.exception);
    };

    return {
        show: show,
    };
});
