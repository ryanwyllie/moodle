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
    'message_popup/message_drawer_events',
    'message_popup/message_drawer_view_conversation_renderer',
    'message_popup/message_drawer_view_conversation_state_manager'
],
function(
    $,
    AutoRows,
    CustomEvents,
    Notification,
    Repository,
    MessageDrawerEvents,
    Renderer,
    StateManager
) {

    var viewState = {};
    var loadedAllMessages = false;
    var messagesOffset = 0;
    var NEWEST_FIRST = true;
    var LOAD_MESSAGE_LIMIT = 100;

    var SELECTORS = {
        ACTION_CANCEL_CONFIRM: '[data-action="cancel-confirm"]',
        ACTION_CANCEL_EDIT_MODE: '[data-action="cancel-edit-mode"]',
        ACTION_CONFIRM_BLOCK: '[data-action="confirm-block"]',
        ACTION_CONFIRM_UNBLOCK: '[data-action="confirm-unblock"]',
        ACTION_CONFIRM_ADD_CONTACT: '[data-action="confirm-add-contact"]',
        ACTION_CONFIRM_REMOVE_CONTACT: '[data-action="confirm-remove-contact"]',
        ACTION_REQUEST_BLOCK: '[data-action="request-block"]',
        ACTION_REQUEST_UNBLOCK: '[data-action="request-unblock"]',
        ACTION_REQUEST_ADD_CONTACT: '[data-action="request-add-contact"]',
        ACTION_REQUEST_REMOVE_CONTACT: '[data-action="request-remove-contact"]',
        MESSAGE: '[data-region="message"]',
        MESSAGES: '[data-region="view-conversation-messages"]',
        MESSAGE_TEXT_AREA: '[data-region="send-message-txt"]',
        SEND_MESSAGE_BUTTON: '[data-action="send-message"]',
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
        console.log("PREV STATE:", viewState);
        console.log("NEXT STATE:", newState);
        console.log("PATCH: ", patch);
        return Renderer.render(root, patch)
            .then(function() {
                viewState = newState;
            });
    };

    var loadProfile = function(root, loggedInUserId, otherUserId) {
        var loggedInUserProfile = getLoggedInUserProfile(root);
        var newState = StateManager.setLoadingMembers(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.getProfile(loggedInUserId, otherUserId);
            })
            .then(function(profile) {
                var newState = StateManager.addMembers(viewState, [profile, loggedInUserProfile]);
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
                var newState = StateManager.addMessages(viewState, messages);
                newState = StateManager.setLoadingMessages(newState, false);
                return render(root, newState);
            })
            .catch(function(error) {
                var newState = StateManager.setLoadingMessages(newState, false);
                render(root, newState);
                return error;
            });
    };

    var requestBlockUser = function(root, userId) {
        return cancelRequest(root, userId).then(function() {
            var newState = StateManager.addPendingBlockUsers(viewState, [userId]);
            return render(root, newState);
        });
    };

    var blockUser = function(root, userId) {
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.blockContacts(viewState.loggedInUserId, [userId])
            })
            .then(function() {
                var newState = StateManager.blockUsers(viewState, [userId]);
                newState = StateManager.removePendingBlockUsers(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                // TODO: Use proper pubsub thing.
                $('body').trigger(MessageDrawerEvents.CONTACT_BLOCKED, [userId]);
                return render(root, newState);
            });
    };

    var requestUnblockUser = function(root, userId) {
        return cancelRequest(root, userId).then(function() {
            var newState = StateManager.addPendingUnblockUsers(viewState, [userId]);
            return render(root, newState);
        });
    };

    var unblockUser = function(root, userId) {
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.unblockContacts(viewState.loggedInUserId, [userId])
            })
            .then(function() {
                var newState = StateManager.unblockUsers(viewState, [userId]);
                newState = StateManager.removePendingUnblockUsers(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                // TODO: Use proper pubsub thing.
                $('body').trigger(MessageDrawerEvents.CONTACT_UNBLOCKED, [userId]);
                return render(root, newState);
            });
    };

    var requestRemoveContact = function(root, userId) {
        return cancelRequest(root, userId).then(function() {
            var newState = StateManager.addPendingRemoveContacts(viewState, [userId]);
            return render(root, newState);
        });
    };

    var removeContact = function(root, userId) {
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.deleteContacts(viewState.loggedInUserId, [userId])
            })
            .then(function() {
                var newState = StateManager.removeContacts(viewState, [userId]);
                newState = StateManager.removePendingRemoveContacts(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                // TODO: Use proper pubsub thing.
                $('body').trigger(MessageDrawerEvents.CONTACT_REMOVED, [userId]);
                return render(root, newState);
            });
    };

    var requestAddContact = function(root, userId) {
        return cancelRequest(root, userId).then(function() {
            var newState = StateManager.addPendingAddContacts(viewState, [userId]);
            return render(root, newState);
        });
    };

    var addContact = function(root, userId) {
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.createContacts(viewState.loggedInUserId, [userId])
            })
            .then(function() {
                var newState = StateManager.addContacts(viewState, [userId]);
                newState = StateManager.removePendingAddContacts(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                // TODO: Use proper pubsub thing.
                $('body').trigger(MessageDrawerEvents.CONTACT_ADDED, [userId]);
                return render(root, newState);
            });
    };

    var cancelRequest = function(root, userId) {
        var newState = StateManager.removePendingAddContacts(viewState, [userId]);
        newState = StateManager.removePendingRemoveContacts(newState, [userId]);
        newState = StateManager.removePendingUnblockUsers(newState, [userId]);
        newState = StateManager.removePendingBlockUsers(newState, [userId]);
        return render(root, newState);
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
                var newState = StateManager.addMessages(viewState, [message]);
                newState = StateManager.setSendingMessage(newState, false);
                return render(root, newState);
            });
    };

    var toggleSelectMessage = function(root, messageId) {
        var newState = viewState;

        if (viewState.selectedMessages.indexOf(messageId) > -1) {
            newState = StateManager.removeSelectedMessages(viewState, [messageId]);
        } else {
            newState = StateManager.addSelectedMessages(viewState, [messageId]);
        }

        return render(root, newState);
    };

    var cancelEditMode = function(root) {
        var newState = StateManager.removeSelectedMessages(viewState, viewState.selectedMessages);
        return render(root, newState);
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
                    .catch(Notification.exception);
            }

            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ACTION_REQUEST_BLOCK, function(e, data) {
            if (!viewState.loadingConfirmAction) {
                requestBlockUser(root, getOtherUserId());
            }
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ACTION_REQUEST_UNBLOCK, function(e, data) {
            if (!viewState.loadingConfirmAction) {
                requestUnblockUser(root, getOtherUserId());
            }
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ACTION_REQUEST_ADD_CONTACT, function(e, data) {
            if (!viewState.loadingConfirmAction) {
                requestAddContact(root, getOtherUserId());
            }
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ACTION_REQUEST_REMOVE_CONTACT, function(e, data) {
            if (!viewState.loadingConfirmAction) {
                requestRemoveContact(root, getOtherUserId());
            }
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ACTION_CANCEL_CONFIRM, function(e, data) {
            if (!viewState.loadingConfirmAction) {
                cancelRequest(root, getOtherUserId());
            }
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ACTION_CONFIRM_BLOCK, function(e, data) {
            if (!viewState.loadingConfirmAction) {
                blockUser(root, getOtherUserId());
            }
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ACTION_CONFIRM_UNBLOCK, function(e, data) {
            if (!viewState.loadingConfirmAction) {
                unblockUser(root, getOtherUserId());
            }
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ACTION_CONFIRM_ADD_CONTACT, function(e, data) {
            if (!viewState.loadingConfirmAction) {
                addContact(root, getOtherUserId());
            }
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ACTION_CONFIRM_REMOVE_CONTACT, function(e, data) {
            if (!viewState.loadingConfirmAction) {
                removeContact(root, getOtherUserId());
            }
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.MESSAGE, function(e, data) {
            var element = $(e.target).closest(SELECTORS.MESSAGE);
            var messageId = element.attr('data-message-id');

            toggleSelectMessage(root, messageId);

            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.ACTION_CANCEL_EDIT_MODE, function(e, data) {
            cancelEditMode(root);
            data.originalEvent.preventDefault();
        });

        messagesContainer.on(CustomEvents.events.scrollTop, function(e, data) {
            var hasMembers = Object.keys(viewState.members).length > 1;

            if (!isLoadingMoreMessages && !loadedAllMessages && hasMembers) {
                loadMessages(root, loggedInUserId, getOtherUserId(), LOAD_MESSAGE_LIMIT, messagesOffset, NEWEST_FIRST)
                    .then(function() {
                        isLoadingMoreMessages = false;
                        messagesOffset = messagesOffset + LOAD_MESSAGE_LIMIT;
                        return;
                    })
                    .catch(function(error) {
                        isLoadingMoreMessages = false;
                        Notification.exception(error);
                    });
            }

            data.originalEvent.preventDefault();
        });
    };

    var reset = function(root, otherUserId) {
        var loggedInUserId = getLoggedInUserId(root);
        var midnight = parseInt(root.attr('data-midnight'), 10);
        var newState = StateManager.buildInitialState(midnight, loggedInUserId);
        messagesOffset = 0;

        if (!Object.keys(viewState).length) {
            viewState = newState;
        }

        return render(root, newState)
            .then(function() {
                return loadProfile(root, loggedInUserId, otherUserId)
            })
            .then(function() {
                return loadMessages(root, loggedInUserId, otherUserId, LOAD_MESSAGE_LIMIT, messagesOffset, NEWEST_FIRST);
            })
            .then(function() {
                return messagesOffset = messagesOffset + LOAD_MESSAGE_LIMIT;
            })
            .catch(Notification.exception);;
    };

    var show = function(root, otherUserId, action) {
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

        switch(action) {
            case 'block':
                requestBlockUser(root, otherUserId);
                break;
            case 'unblock':
                requestUnblockUser(root, otherUserId);
                break;
            case 'add-contact':
                requestAddContact(root, otherUserId);
                break;
            case 'remove-contact':
                requestRemoveContact(root, otherUserId);
                break;
        }
    };

    return {
        show: show,
    };
});
