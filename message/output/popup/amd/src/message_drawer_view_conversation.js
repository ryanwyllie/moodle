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
    'core/backoff_timer',
    'core/custom_interaction_events',
    'core/notification',
    'core/pubsub',
    'core_message/message_repository',
    'message_popup/message_drawer_events',
    'message_popup/message_drawer_view_conversation_renderer',
    'message_popup/message_drawer_view_conversation_state_manager',
    'message_popup/message_drawer_routes',
],
function(
    $,
    AutoRows,
    BackOffTimer,
    CustomEvents,
    Notification,
    PubSub,
    Repository,
    MessageDrawerEvents,
    Renderer,
    StateManager,
    MessageDrawerRoutes
) {

    var viewState = {};
    var loadedAllMessages = false;
    var messagesOffset = 0;
    var newMessagesPollTimer = null;
    var NEWEST_FIRST = true;
    var LOAD_MESSAGE_LIMIT = 100;
    var INITIAL_NEW_MESSAGE_POLL_TIMEOUT = 1000;

    var SELECTORS = {
        ACTION_CANCEL_CONFIRM: '[data-action="cancel-confirm"]',
        ACTION_CANCEL_EDIT_MODE: '[data-action="cancel-edit-mode"]',
        ACTION_CONFIRM_BLOCK: '[data-action="confirm-block"]',
        ACTION_CONFIRM_UNBLOCK: '[data-action="confirm-unblock"]',
        ACTION_CONFIRM_ADD_CONTACT: '[data-action="confirm-add-contact"]',
        ACTION_CONFIRM_REMOVE_CONTACT: '[data-action="confirm-remove-contact"]',
        ACTION_CONFIRM_DELETE_SELECTED_MESSAGES: '[data-action="confirm-delete-selected-messages"]',
        ACTION_CONFIRM_DELETE_CONVERSATION: '[data-action="confirm-delete-conversation"]',
        ACTION_REQUEST_DELETE_CONVERSATION: '[data-action="request-delete-conversation"]',
        ACTION_REQUEST_DELETE_SELECTED_MESSAGES: '[data-action="delete-selected-messages"]',
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

    var formatMessageForEvent = function(message) {
        return Object.assign({
            conversation: {
                id: viewState.id,
                title: viewState.title,
                imageurl: viewState.members[getOtherUserId()].profileimageurl
            }
        }, message);
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
                newState = StateManager.setTitle(newState, profile.fullname);
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

    var loadMessages = function(root, conversationId, limit, offset, newestFirst) {
        var newState = StateManager.setLoadingMessages(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.getMessages(viewState.loggedInUserId, conversationId, limit + 1, offset, newestFirst)
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
                var newState = StateManager.setLoadingMessages(viewState, false);
                render(root, newState);
                return error;
            });
    };

    var getLoadNewMessagesCallback = function(root, conversationId, newestFirst) {
        return function() {
            var messages = viewState.messages;
            var mostRecentMessage = messages.length ? messages[messages.length - 1] : null;

            if (mostRecentMessage) {
                // There may be multiple messages with the same time created value since
                // the accuracy is only down to the second. The server will include these
                // messages in the result (since it does a >= comparison on time from) so
                // we need to filter them back out of the result so that we're left only
                // with the new messages.
                var ignoreMessageIds = [];
                for (var i = messages.length - 1; i > 0; i--) {
                    var message = messages[i];
                    if (message.timeCreated === mostRecentMessage.timeCreated) {
                        ignoreMessageIds.push(message.id);
                    } else {
                        // Since the messages are ordered in ascending order of time created
                        // we can break as soon as we hit a message with a different time created
                        // because we know all other messages will have lower values.
                        break;
                    }
                }

                Repository.getMessages(
                    viewState.loggedInUserId,
                    conversationId,
                    0,
                    0,
                    newestFirst,
                    mostRecentMessage.timeCreated
                )
                .then(function(result) {
                    if (result.messages.length) {
                        return result.messages.filter(function(message) {
                            // Skip any messages in our ignore list.
                            return ignoreMessageIds.indexOf(parseInt(message.id, 10)) < 0;
                        });
                    } else {
                        return [];
                    }
                })
                .then(function(messages) {
                    if (messages.length) {
                        var newState = StateManager.addMessages(viewState, messages);
                        return render(root, newState)
                            .then(function() {
                                // If we found some results then restart the polling timer
                                // because the other user might be sending messages.
                                newMessagesPollTimer.restart();
                            })
                            .then(function() {
                                return markConversationAsRead(root, conversationId);
                            });
                    } else {
                        return [];
                    }
                });
            }
        };
    };

    var markConversationAsRead = function(root, conversationId) {
        var loggedInUserId = viewState.loggedInUserId;

        return Repository.markAllAsRead({
                useridto: loggedInUserId,
                useridfrom: conversationId
            })
            .then(function() {
                var newState = StateManager.markMessagesAsRead(viewState, viewState.messages);
                PubSub.publish(MessageDrawerEvents.CONVERSATION_READ, conversationId);
                return render(root, newState);
            });
    };

    var requestBlockUser = function(root, userId) {
        return cancelRequest(root, userId).then(function() {
            var newState = StateManager.addPendingBlockUsersById(viewState, [userId]);
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
                var newState = StateManager.blockUsersById(viewState, [userId]);
                newState = StateManager.removePendingBlockUsersById(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                PubSub.publish(MessageDrawerEvents.CONTACT_BLOCKED, userId);
                return render(root, newState);
            });
    };

    var requestUnblockUser = function(root, userId) {
        return cancelRequest(root, userId).then(function() {
            var newState = StateManager.addPendingUnblockUsersById(viewState, [userId]);
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
                var newState = StateManager.unblockUsersById(viewState, [userId]);
                newState = StateManager.removePendingUnblockUsersById(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                PubSub.publish(MessageDrawerEvents.CONTACT_UNBLOCKED, userId);
                return render(root, newState);
            });
    };

    var requestRemoveContact = function(root, userId) {
        return cancelRequest(root, userId).then(function() {
            var newState = StateManager.addPendingRemoveContactsById(viewState, [userId]);
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
                var newState = StateManager.removeContactsById(viewState, [userId]);
                newState = StateManager.removePendingRemoveContactsById(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                PubSub.publish(MessageDrawerEvents.CONTACT_REMOVED, userId);
                return render(root, newState);
            });
    };

    var requestAddContact = function(root, userId) {
        return cancelRequest(root, userId).then(function() {
            var newState = StateManager.addPendingAddContactsById(viewState, [userId]);
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
                var newState = StateManager.addContactsById(viewState, [userId]);
                newState = StateManager.removePendingAddContactsById(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                PubSub.publish(MessageDrawerEvents.CONTACT_ADDED, newState.members[userId]);
                return render(root, newState);
            });
    };

    var requestDeleteSelectedMessages = function(root, userId) {
        var selectedMessageIds = viewState.selectedMessageIds;
        return cancelRequest(root, userId).then(function() {
            var newState = StateManager.addPendingDeleteMessagesById(viewState, selectedMessageIds);
            return render(root, newState);
        });
    };

    var deleteSelectedMessages = function(root) {
        var messageIds = viewState.pendingDeleteMessageIds;
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.deleteMessages(viewState.loggedInUserId, messageIds)
            })
            .then(function() {
                var newState = StateManager.removeMessagesById(viewState, messageIds);
                newState = StateManager.removePendingDeleteMessagesById(newState, messageIds);
                newState = StateManager.removeSelectedMessagesById(newState, messageIds);
                newState = StateManager.setLoadingConfirmAction(newState, false);

                var prevLastMessage = viewState.messages[viewState.messages.length - 1];
                var newLastMessage = newState.messages.length ? newState.messages[newState.messages.length - 1] : null;

                if (newLastMessage && newLastMessage.id != prevLastMessage.id) {
                    var formattedMessage = formatMessageForEvent(newLastMessage);
                    PubSub.publish(MessageDrawerEvents.CONVERSATION_NEW_LAST_MESSAGE, formattedMessage);
                } else if (!newState.messages.length) {
                    PubSub.publish(MessageDrawerEvents.CONVERSATION_DELETED, newState.id);
                }

                return render(root, newState);
            });
    };

    var requestDeleteConversation = function(root, userId) {
        return cancelRequest(root, userId).then(function() {
            var newState = StateManager.setPendingDeleteConversation(viewState, true);
            return render(root, newState);
        });
    };

    var deleteConversation = function(root) {
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(root, newState)
            .then(function() {
                return Repository.deleteCoversation(viewState.loggedInUserId, getOtherUserId())
            })
            .then(function() {
                var newState = StateManager.removeMessages(viewState, viewState.messages);
                newState = StateManager.removeSelectedMessagesById(newState, viewState.selectedMessageIds);
                newState = StateManager.setPendingDeleteConversation(newState, false);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                PubSub.publish(MessageDrawerEvents.CONVERSATION_DELETED, newState.id);
                return render(root, newState);
            });
    };

    var cancelRequest = function(root, userId) {
        var pendingDeleteMessageIds = viewState.pendingDeleteMessageIds;
        var newState = StateManager.removePendingAddContactsById(viewState, [userId]);
        newState = StateManager.removePendingRemoveContactsById(newState, [userId]);
        newState = StateManager.removePendingUnblockUsersById(newState, [userId]);
        newState = StateManager.removePendingBlockUsersById(newState, [userId]);
        newState = StateManager.removePendingDeleteMessagesById(newState, pendingDeleteMessageIds);
        newState = StateManager.setPendingDeleteConversation(newState, false);
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
                    id: parseInt(result.msgid, 10),
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

                var lastMessage = newState.messages.filter(function(candidate) {
                    return candidate.id === message.id;
                });
                formattedMessage = formatMessageForEvent(lastMessage[0]);

                PubSub.publish(MessageDrawerEvents.CONVERSATION_NEW_LAST_MESSAGE, formattedMessage);

                return render(root, newState);
            });
    };

    var toggleSelectMessage = function(root, messageId) {
        var newState = viewState;

        if (viewState.selectedMessageIds.indexOf(messageId) > -1) {
            newState = StateManager.removeSelectedMessagesById(viewState, [messageId]);
        } else {
            newState = StateManager.addSelectedMessagesById(viewState, [messageId]);
        }

        return render(root, newState);
    };

    var cancelEditMode = function(root) {
        return cancelRequest(root, getOtherUserId())
            .then(function() {
                var newState = StateManager.removeSelectedMessagesById(viewState, viewState.selectedMessageIds);
                return render(root, newState);
            });
    };

    var generateActivateHandler = function(handlerFunc) {
        return function(root) {
            return function(e, data) {
                return handlerFunc(root, e, data);
            }
        };
    }

    var generateConfirmActionHandler = function(actionCallback) {
        return generateActivateHandler(function(root, e, data) {
            if (!viewState.loadingConfirmAction) {
                actionCallback(root, getOtherUserId()).catch(Notification.exception);
            }
            data.originalEvent.preventDefault();
        });
    };

    var handleSendMessage = function(root, e, data) {
        var textArea = getMessageTextArea(root);
        var text = textArea.val().trim();

        if (text !== '') {
            sendMessage(root, viewState.id, text)
                .catch(Notification.exception);
        }

        data.originalEvent.preventDefault();
    };

    var handleSelectMessage = function(root, e, data) {
        var element = $(e.target).closest(SELECTORS.MESSAGE);
        var messageId = parseInt(element.attr('data-message-id'), 10);

        toggleSelectMessage(root, messageId).catch(Notification.exception);

        data.originalEvent.preventDefault();
    };

    var handleCancelEditMode = function(root, e, data) {
        cancelEditMode(root).catch(Notification.exception);
        data.originalEvent.preventDefault();
    };

    var activateHandlers = [
        [SELECTORS.SEND_MESSAGE_BUTTON, generateActivateHandler(handleSendMessage)],
        [SELECTORS.ACTION_REQUEST_BLOCK, generateConfirmActionHandler(requestBlockUser)],
        [SELECTORS.ACTION_REQUEST_UNBLOCK, generateConfirmActionHandler(requestUnblockUser)],
        [SELECTORS.ACTION_REQUEST_ADD_CONTACT, generateConfirmActionHandler(requestAddContact)],
        [SELECTORS.ACTION_REQUEST_REMOVE_CONTACT, generateConfirmActionHandler(requestRemoveContact)],
        [SELECTORS.ACTION_REQUEST_DELETE_SELECTED_MESSAGES, generateConfirmActionHandler(requestDeleteSelectedMessages)],
        [SELECTORS.ACTION_REQUEST_DELETE_CONVERSATION, generateConfirmActionHandler(requestDeleteConversation)],
        [SELECTORS.ACTION_CANCEL_CONFIRM, generateConfirmActionHandler(cancelRequest)],
        [SELECTORS.ACTION_CONFIRM_BLOCK, generateConfirmActionHandler(blockUser)],
        [SELECTORS.ACTION_CONFIRM_UNBLOCK, generateConfirmActionHandler(unblockUser)],
        [SELECTORS.ACTION_CONFIRM_ADD_CONTACT, generateConfirmActionHandler(addContact)],
        [SELECTORS.ACTION_CONFIRM_REMOVE_CONTACT, generateConfirmActionHandler(removeContact)],
        [SELECTORS.ACTION_CONFIRM_DELETE_SELECTED_MESSAGES, generateConfirmActionHandler(deleteSelectedMessages)],
        [SELECTORS.ACTION_CONFIRM_DELETE_CONVERSATION, generateConfirmActionHandler(deleteConversation)],
        [SELECTORS.MESSAGE, generateActivateHandler(handleSelectMessage)],
        [SELECTORS.ACTION_CANCEL_EDIT_MODE, generateActivateHandler(handleCancelEditMode)],
    ];

    var registerEventListeners = function(root) {
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

        messagesContainer.on(CustomEvents.events.scrollTop, function(e, data) {
            var hasMembers = Object.keys(viewState.members).length > 1;

            if (!isLoadingMoreMessages && !loadedAllMessages && hasMembers) {
                loadMessages(root, viewState.id, LOAD_MESSAGE_LIMIT, messagesOffset, NEWEST_FIRST)
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

        activateHandlers.forEach(function(handler) {
            var selector = handler[0];
            var handlerFunction = handler[1];
            root.on(CustomEvents.events.activate, selector, handlerFunction(root));
        });

        PubSub.subscribe(MessageDrawerEvents.ROUTE_CHANGED, function(newRouteData) {
            if (newMessagesPollTimer) {
                if (newRouteData.route == MessageDrawerRoutes.VIEW_CONVERSATION) {
                    newMessagesPollTimer.restart();
                } else {
                    newMessagesPollTimer.stop();
                }
            }
        });
    };

    var reset = function(root, conversationId) {
        var loggedInUserId = getLoggedInUserId(root);
        var midnight = parseInt(root.attr('data-midnight'), 10);
        var newState = StateManager.buildInitialState(midnight, loggedInUserId, conversationId, '');
        messagesOffset = 0;

        if (!Object.keys(viewState).length) {
            viewState = newState;
        }

        if (newMessagesPollTimer) {
            newMessagesPollTimer.stop();
        }

        newMessagesPollTimer = new BackOffTimer(
            getLoadNewMessagesCallback(root, conversationId, NEWEST_FIRST),
            function(time) {
                if (!time) {
                    return INITIAL_NEW_MESSAGE_POLL_TIMEOUT;
                }

                return time * 2;
            }
        );

        newMessagesPollTimer.start();

        return render(root, newState)
            .then(function() {
                return loadProfile(root, loggedInUserId, conversationId)
            })
            .then(function() {
                return loadMessages(root, viewState.id, LOAD_MESSAGE_LIMIT, messagesOffset, NEWEST_FIRST);
            })
            .then(function() {
                return messagesOffset = messagesOffset + LOAD_MESSAGE_LIMIT;
            })
            .then(function() {
                return markConversationAsRead(root, viewState.id);
            })
            .catch(Notification.exception);;
    };

    var show = function(root, conversationId, action) {
        root = $(root);

        if (!root.attr('data-init')) {
            registerEventListeners(root);
            root.attr('data-init', true);
            reset(root, conversationId);
        } else {
            var currentConversationId = viewState.id;
            if (currentConversationId != conversationId) {
                reset(root, conversationId);
            }
        }

        switch(action) {
            case 'block':
                requestBlockUser(root, conversationId);
                break;
            case 'unblock':
                requestUnblockUser(root, conversationId);
                break;
            case 'add-contact':
                requestAddContact(root, conversationId);
                break;
            case 'remove-contact':
                requestRemoveContact(root, conversationId);
                break;
        }
    };

    return {
        show: show,
    };
});
