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
    'core_message/message_repository',
    'message_popup/message_drawer_view_conversation_renderer',
    'message_popup/message_drawer_view_conversation_state_manager'
],
function(
    $,
    AutoRows,
    CustomEvents,
    Notification,
    Repository,
    Renderer,
    StateManager
) {

    var viewState = {};
    var loadedAllMessages = false;
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
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        MORE_MESSAGES_LOADING_ICON_CONTAINER: '[data-region="more-messages-loading-icon-container"]',
        RESPONSE_CONTAINER: '[data-region="response"]'
    };

    var getLoggedInUserId = function(root) {
        return root.attr('data-user-id');
    };

    var getLoggedInUserFullName = function(root) {
        return root.attr('data-full-name');
    };

    var getLoggedInUserProfileUrl = function(root) {
        return root.attr('data-profile-url');
    };

    var getOtherUserId = function() {
        var loggedInUserId = viewState.loggedInUserId;
        var otherUserIds = Object.keys(viewState.members).filter(function(userId) {
            return loggedInUserId != userId;
        });

        return otherUserIds.length ? otherUserIds[0] : null;
    };

    var getMessageTextArea = function(root) {
        return root.find(SELECTORS.MESSAGE_TEXT_AREA);
    };

    var getMessagesContainer = function(root) {
        return root.find(SELECTORS.MESSAGES);
    };

    var getLoggedInUserProfile = function(root) {
        return {
            userid: getLoggedInUserId(root),
            fullname: getLoggedInUserFullName(root),
            profileimageurl: getLoggedInUserProfileUrl(root),
        }
    };

    var render = function(root, newState) {
        var patch = StateManager.buildPatch(viewState, newState);
        return Renderer.render(root, patch)
            .then(function() {
                viewState = newState;
            });
    };

    // Header stuff.
    var loadProfile = function(root, loggedInUserId, otherUserId) {
        var loggedInUserProfile = getLoggedInUserProfile(root);
        var newState = StateManager.setLoadingMembers(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.getProfile(loggedInUserId, otherUserId);
            })
            .then(function(profile) {
                var newState = StateManager.addMembersToSate(viewState, [profile, loggedInUserProfile]);
                newState = StateManager.setLoadingMembers(newState, false);
                return render(root, newState)
                    .then(function() {
                        return profile;
                    });
            })
            .catch(function(error) {
                var newState = StateManager.setLoadingMembers(viewState, false);
                render(root, newState);
                return error;
            });
    };

    // Message loading.
    var loadMessages = function(root, currentUserId, otherUserId, limit, offset, newestFirst) {
        var newState = StateManager.setLoadingMessages(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.getMessages(currentUserId, otherUserId, limit + 1, offset, newestFirst)
            })
            .then(function(result) {
                return result.messages;
            })
            .then(function(messages) {
                if (messages.length > limit) {
                    messages = messages.slice(1);
                } else {
                    loadedAllMessages = true;
                }
                return messages;
            })
            .then(function(messages) {
                var newState = StateManager.addMessagesToState(viewState, messages);
                newState = StateManager.setLoadingMessages(newState, false);
                return render(root, newState);
            })
            .catch(function(error) {
                var newState = StateManager.setLoadingMessages(newState, false);
                render(root, newState);
                return error;
            });
    };

    var sendMessage = function(root, toUserId, text) {
        var newState = StateManager.setSendingMessage(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.sendMessage(toUserId, text);
            })
            .then(function(result) {
                return {
                    id: result.msgid,
                    fullname: getLoggedInUserFullName(root),
                    profileimageurl: getLoggedInUserProfileUrl(root),
                    text: result.text,
                    timecreated: parseInt(result.timecreated, 10),
                    useridfrom: getLoggedInUserId(root),
                    useridto: toUserId,
                    isread: true
                };
            })
            .then(function(message) {
                var newState = StateManager.addMessagesToState(viewState, [message]);
                newState = StateManager.setSendingMessage(newState, false);
                return render(root, newState);
            });
    };

    var registerEventListeners = function(root) {
        var loggedInUserId = getLoggedInUserId(root);
        var isLoadingMoreMessages = false;
        var messagesContainer = getMessagesContainer(root);
        AutoRows.init(root);

        CustomEvents.define(root, [
            CustomEvents.events.activate
        ]);
        CustomEvents.define(messagesContainer, [
            CustomEvents.events.scrollTop,
            CustomEvents.events.scrollLock,
        ]);

        root.on(CustomEvents.events.activate, SELECTORS.SEND_MESSAGE_BUTTON, function(e, data) {
            var textArea = getMessageTextArea(root);
            var text = textArea.val().trim();
            var otherUserId = getOtherUserId();

            if (text !== '') {
                sendMessage(root, otherUserId, text)
                    .catch(function(error) {
                        Notification.exception(error);
                    });
            }

            data.originalEvent.preventDefault();
        });

        messagesContainer.on(CustomEvents.events.scrollTop, function(e, data) {
            var hasMembers = Object.keys(viewState.members).length > 1;

            if (!isLoadingMoreMessages && !loadedAllMessages && hasMembers) {
                loadMessages(root, loggedInUserId, getOtherUserId(), viewState.limit, viewState.offset, NEWEST_FIRST)
                    .then(function() {
                        isLoadingMoreMessages = false;
                        return;
                    })
                    .catch(function() {
                        isLoadingMoreMessages = false;
                        return;
                    });
            }

            data.originalEvent.preventDefault();
        });
    };

    var reset = function(root, otherUserId) {
        var loggedInUserId = getLoggedInUserId(root);
        var midnight = parseInt(root.attr('data-midnight'), 10);
        viewState = StateManager.buildInitialState(midnight, loggedInUserId);

        return loadProfile(root, loggedInUserId, otherUserId)
            .then(function() {
                return loadMessages(root, loggedInUserId, otherUserId, viewState.limit, viewState.offset, NEWEST_FIRST);
            })
            .catch(Notification.exception);;
    };

    var show = function(root, otherUserId) {
        root = $(root);

        if (!root.attr('data-init')) {
            registerEventListeners(root);
            root.attr('data-init', true);
            reset(root, otherUserId);
        } else {
            var currentOtherUserId = getOtherUserId();
            if (currentOtherUserId && currentOtherUserId != otherUserId) {
                reset(root, otherUserId);
            }
        }
    };

    return {
        show: show,
    };
});
