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
 * @module     core_message/message_drawer_view_overview
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
    'core_message/message_drawer_events',
    'core_message/message_drawer_view_conversation_patcher',
    'core_message/message_drawer_view_conversation_renderer',
    'core_message/message_drawer_view_conversation_state_manager',
    'core_message/message_drawer_routes',
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
    Patcher,
    Renderer,
    StateManager,
    MessageDrawerRoutes
) {

    var viewState = {};
    var loadedAllMessages = false;
    var messagesOffset = 0;
    var newMessagesPollTimer = null;
    var render = null;
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
        FOOTER_CONTAINER: '[data-region="content-messages-footer-container"]',
        MESSAGE: '[data-region="message"]',
        MESSAGES_CONTAINER: '[data-region="content-message-container"]',
        MESSAGE_TEXT_AREA: '[data-region="send-message-txt"]',
        SEND_MESSAGE_BUTTON: '[data-action="send-message"]',
    };

    /**
     * Get the other user userid.
     *
     * @return {Number} Userid.
     */
    var getOtherUserId = function() {
        var loggedInUserId = viewState.loggedInUserId;
        var otherUserIds = Object.keys(viewState.members).filter(function(userId) {
            return loggedInUserId != userId;
        });

        return otherUserIds.length ? otherUserIds[0] : null;
    };

    /**
     * Get profile info for logged in user.
     *
     * @param {Object} body Conversation body container element.
     */
    var getLoggedInUserProfile = function(body) {
        return {
            userid: body.attr('data-user-id'),
            fullname: body.attr('data-full-name'),
            profileimageurl: body.attr('data-profile-url'),
        };
    };

    /**
     * Get the messages container element.
     *
     * @param  {Object} body Conversation body container element.
     * @return {Object} The messages container element.
     */
    var getMessagesContainer = function(body) {
        return body.find(SELECTORS.MESSAGES_CONTAINER);
    };

    /**
     * Reformat the message for rendering event.
     *
     * @param  {Object} message Raw message stored in statemanager.
     * @return {Object} New formatted message with additional attributs.
     */
    var formatMessageForEvent = function(message) {
        return Object.assign({
            conversation: {
                id: viewState.id,
                title: viewState.title,
                imageurl: viewState.members[getOtherUserId()].profileimageurl
            }
        }, message);
    };

    /**
     * Get the other user profile.
     *
     * @param  {Object} loggedInUserProfile The logged in user profile.
     * @param  {Number} otherUserId The other user id.
     * @return {Object} Profile returned from repository.
     */
    var loadProfile = function(loggedInUserProfile, otherUserId) {
        var loggedInUserId = loggedInUserProfile.userid;
        var newState = StateManager.setLoadingMembers(viewState, true);
        return render(newState)
            .then(function() {
                return Repository.getProfile(loggedInUserId, otherUserId);
            })
            .then(function(profile) {
                var newState = StateManager.addMembers(viewState, [profile, loggedInUserProfile]);
                newState = StateManager.setLoadingMembers(newState, false);
                newState = StateManager.setTitle(newState, profile.fullname);
                return render(newState)
                    .then(function() {
                        return profile;
                    });
            })
            .catch(function(error) {
                var newState = StateManager.setLoadingMembers(viewState, false);
                render(newState);
                Notification.exception(error);
            });
    };

    /**
     * Load messages for this conversation and pass them to the renderer.
     *
     * @param  {Number} conversationId Conversation id.
     * @param  {Number} limit Number of messages to load.
     * @param  {Number} offset get messages from offset.
     * @param  {Bool} newestFirst get newest messages first.
     * @return {Promise} renderer promise.
     */
    var loadMessages = function(conversationId, limit, offset, newestFirst) {
        var newState = StateManager.setLoadingMessages(viewState, true);
        return render(newState)
            .then(function() {
                return Repository.getMessages(viewState.loggedInUserId, conversationId, limit + 1, offset, newestFirst);
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
                return render(newState);
            })
            .catch(function(error) {
                var newState = StateManager.setLoadingMessages(viewState, false);
                render(newState);
                return error;
            });
    };

    /**
     * Create a callback function for getting new messages for this conversation.
     *
     * @param  {Number} conversationId Conversation id.
     * @param  {Bool} newestFirst Show newest messages first
     * @return {Function} Callback function that returns a renderer promise.
     */
    var getLoadNewMessagesCallback = function(conversationId, newestFirst) {
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
                for (var i = messages.length - 1; i >= 0; i--) {
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
                        return render(newState)
                            .then(function() {
                                // If we found some results then restart the polling timer
                                // because the other user might be sending messages.
                                newMessagesPollTimer.restart();
                            })
                            .then(function() {
                                return markConversationAsRead(conversationId);
                            });
                    } else {
                        return [];
                    }
                });
            }
        };
    };

    /**
     * Mark a conversation as read.
     *
     * @param  {Number} conversationId The conversation id.
     * @return {Promise} The renderer promise.
     */
    var markConversationAsRead = function(conversationId) {
        var loggedInUserId = viewState.loggedInUserId;

        return Repository.markAllAsRead({
                useridto: loggedInUserId,
                useridfrom: conversationId
            })
            .then(function() {
                var newState = StateManager.markMessagesAsRead(viewState, viewState.messages);
                PubSub.publish(MessageDrawerEvents.CONVERSATION_READ, conversationId);
                return render(newState);
            });
    };

    /**
     * Tell the statemanager there is request to block a user and run the renderer
     * to show the block user dialogue.
     *
     * @param  {Number} userId User id.
     * @return {Promise} Renderer promise.
     */
    var requestBlockUser = function(userId) {
        return cancelRequest(userId).then(function() {
            var newState = StateManager.addPendingBlockUsersById(viewState, [userId]);
            return render(newState);
        });
    };

    /**
     * Send the repository a request to block a user, update the statemanager and publish
     * a contact has been blocked.
     *
     * @param  {Number} userId User id of user to block.
     * @return {Promise} Renderer promise.
     */
    var blockUser = function(userId) {
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(newState)
            .then(function() {
                return Repository.blockContacts(viewState.loggedInUserId, [userId]);
            })
            .then(function() {
                var newState = StateManager.blockUsersById(viewState, [userId]);
                newState = StateManager.removePendingBlockUsersById(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                PubSub.publish(MessageDrawerEvents.CONTACT_BLOCKED, userId);
                return render(newState);
            });
    };

    /**
     * Tell the statemanager there is a request to unblock a user and run the renderer
     * to show the unblock user dialogue.
     *
     * @param  {Number} userId User id of user to unblock.
     * @return {Promise} Renderer promise.
     */
    var requestUnblockUser = function(userId) {
        return cancelRequest(userId).then(function() {
            var newState = StateManager.addPendingUnblockUsersById(viewState, [userId]);
            return render(newState);
        });
    };

    /**
     * Send the repository a request to unblock a user, update the statemanager and publish
     * a contact has been unblocked.
     *
     * @param  {Number} userId User id of user to unblock.
     * @return {Promise} Renderer promise.
     */
    var unblockUser = function(userId) {
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(newState)
            .then(function() {
                return Repository.unblockContacts(viewState.loggedInUserId, [userId]);
            })
            .then(function() {
                var newState = StateManager.unblockUsersById(viewState, [userId]);
                newState = StateManager.removePendingUnblockUsersById(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                PubSub.publish(MessageDrawerEvents.CONTACT_UNBLOCKED, userId);
                return render(newState);
            });
    };

    /**
     * Tell the statemanager there is a request to remove a user from the contact list
     * and run the renderer to show the remove user from contacts dialogue.
     *
     * @param  {Number} userId User id of user to remove from contacts.
     * @return {Promise} Renderer promise.
     */
    var requestRemoveContact = function(userId) {
        return cancelRequest(userId).then(function() {
            var newState = StateManager.addPendingRemoveContactsById(viewState, [userId]);
            return render(newState);
        });
    };

    /**
     * Send the repository a request to remove a user from the contacts list. update the statemanager
     * and publish a contact has been removed.
     *
     * @param  {Number} userId User id of user to remove from contacts.
     * @return {Promise} Renderer promise.
     */
    var removeContact = function(userId) {
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(newState)
            .then(function() {
                return Repository.deleteContacts(viewState.loggedInUserId, [userId]);
            })
            .then(function() {
                var newState = StateManager.removeContactsById(viewState, [userId]);
                newState = StateManager.removePendingRemoveContactsById(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                PubSub.publish(MessageDrawerEvents.CONTACT_REMOVED, userId);
                return render(newState);
            });
    };

    /**
     * Tell the statemanager there is a request to add a user to the contact list
     * and run the renderer to show the add user to contacts dialogue.
     *
     * @param  {Number} userId User id of user to add to contacts.
     * @return {Promise} Renderer promise.
     */
    var requestAddContact = function(userId) {
        return cancelRequest(userId).then(function() {
            var newState = StateManager.addPendingAddContactsById(viewState, [userId]);
            return render(newState);
        });
    };

    /**
     * Send the repository a request to add a user to the contacts list. update the statemanager
     * and publish a contact has been added.
     *
     * @param  {Number} userId User id of user to add to contacts.
     * @return {Promise} Renderer promise.
     */
    var addContact = function(userId) {
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(newState)
            .then(function() {
                return Repository.createContacts(viewState.loggedInUserId, [userId]);
            })
            .then(function() {
                var newState = StateManager.addContactsById(viewState, [userId]);
                newState = StateManager.removePendingAddContactsById(newState, [userId]);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                PubSub.publish(MessageDrawerEvents.CONTACT_ADDED, newState.members[userId]);
                return render(newState);
            });
    };

    /**
     * Tell the statemanager there is a request to delete the selected messages
     * and run the renderer to show confirm delete messages dialogue.
     *
     * @param  {Number} userId User id.
     * @return {Promise} Renderer promise.
     */
    var requestDeleteSelectedMessages = function(userId) {
        var selectedMessageIds = viewState.selectedMessageIds;
        return cancelRequest(userId).then(function() {
            var newState = StateManager.addPendingDeleteMessagesById(viewState, selectedMessageIds);
            return render(newState);
        });
    };

    /**
     * Send the repository a request to delete the messages pending deletion. Update the statemanager
     * and publish a message deletion event.
     *
     * @return {Promise} Renderer promise.
     */
    var deleteSelectedMessages = function() {
        var messageIds = viewState.pendingDeleteMessageIds;
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(newState)
            .then(function() {
                return Repository.deleteMessages(viewState.loggedInUserId, messageIds);
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

                return render(newState);
            });
    };

    /**
     * Tell the statemanager there is a request to delete a conversation
     * and run the renderer to show confirm delete conversation dialogue.
     *
     * @param  {Number} userId User id of other user.
     * @return {Promise} Renderer promise.
     */
    var requestDeleteConversation = function(userId) {
        return cancelRequest(userId).then(function() {
            var newState = StateManager.setPendingDeleteConversation(viewState, true);
            return render(newState);
        });
    };

    /**
     * Send the repository a request to delete a conversation. Update the statemanager
     * and publish a conversation deleted event.
     *
     * @return {Promise} Renderer promise.
     */
    var deleteConversation = function() {
        var newState = StateManager.setLoadingConfirmAction(viewState, true);
        return render(newState)
            .then(function() {
                return Repository.deleteCoversation(viewState.loggedInUserId, getOtherUserId());
            })
            .then(function() {
                var newState = StateManager.removeMessages(viewState, viewState.messages);
                newState = StateManager.removeSelectedMessagesById(newState, viewState.selectedMessageIds);
                newState = StateManager.setPendingDeleteConversation(newState, false);
                newState = StateManager.setLoadingConfirmAction(newState, false);
                PubSub.publish(MessageDrawerEvents.CONVERSATION_DELETED, newState.id);
                return render(newState);
            });
    };

    /**
     * Tell the statemanager to cancel all pending actions.
     *
     * @param  {Number} userId User id.
     * @return {Promise} Renderer promise.
     */
    var cancelRequest = function(userId) {
        var pendingDeleteMessageIds = viewState.pendingDeleteMessageIds;
        var newState = StateManager.removePendingAddContactsById(viewState, [userId]);
        newState = StateManager.removePendingRemoveContactsById(newState, [userId]);
        newState = StateManager.removePendingUnblockUsersById(newState, [userId]);
        newState = StateManager.removePendingBlockUsersById(newState, [userId]);
        newState = StateManager.removePendingDeleteMessagesById(newState, pendingDeleteMessageIds);
        newState = StateManager.setPendingDeleteConversation(newState, false);
        return render(newState);
    };

    /**
     * Send a message to the repository, update the statemanager publish a message send event
     * and call the renderer.
     *
     * @param  {Number} toUserId User id of user to send this message to.
     * @param  {String} text Text to send.
     * @return {Promise} Renderer promise.
     */
    var sendMessage = function(toUserId, text) {
        var loggedInUser = viewState.members[viewState.loggedInUserId];
        var newState = StateManager.setSendingMessage(viewState, true);
        return render(newState)
            .then(function() {
                return Repository.sendMessage(toUserId, text);
            })
            .then(function(result) {
                if (result.errormessage) {
                    throw new Error(result.errormessage);
                }

                return result;
            })
            .then(function(result) {
                return {
                    id: parseInt(result.msgid, 10),
                    fullname: loggedInUser.fullname,
                    profileimageurl: loggedInUser.profileimageurl,
                    text: result.text,
                    timecreated: parseInt(result.timecreated, 10),
                    useridfrom: loggedInUser.userid,
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
                var formattedMessage = formatMessageForEvent(lastMessage[0]);

                PubSub.publish(MessageDrawerEvents.CONVERSATION_NEW_LAST_MESSAGE, formattedMessage);

                return render(newState);
            })
            .catch(function(error) {
                var newState = StateManager.setSendingMessage(viewState, false);
                render(newState);
                Notification.exception(error);
            });
    };

    /**
     * Toggle the selected messages update the statemanager and render the result.
     *
     * @param  {Number} messageId The id of the message to be toggled
     * @return {Promise} Renderer promise.
     */
    var toggleSelectMessage = function(messageId) {
        var newState = viewState;

        if (viewState.selectedMessageIds.indexOf(messageId) > -1) {
            newState = StateManager.removeSelectedMessagesById(viewState, [messageId]);
        } else {
            newState = StateManager.addSelectedMessagesById(viewState, [messageId]);
        }

        return render(newState);
    };

    /**
     * Cancel edit mode (selecting the messages).
     *
     * @return {Promise} Renderer promise.
     */
    var cancelEditMode = function() {
        return cancelRequest(getOtherUserId())
            .then(function() {
                var newState = StateManager.removeSelectedMessagesById(viewState, viewState.selectedMessageIds);
                return render(newState);
            });
    };

    /**
     * Create a function to render the Conversation.
     *
     * @param  {Object} header The conversation header container element.
     * @param  {Object} body The conversation body container element.
     * @param  {Object} footer The conversation footer container element.
     * @return {Promise} Renderer promise.
     */
    var generateRenderFunction = function(header, body, footer) {
        return function(newState) {
            var patch = Patcher.buildPatch(viewState, newState);
            return Renderer.render(header, body, footer, patch)
                .then(function() {
                    viewState = newState;
                });
        };
    };

    /**
     * Create a confirm action function.
     *
     * @callback actionCallback
     * @return {Function} Confirm action handler.
     */
    var generateConfirmActionHandler = function(actionCallback) {
        return function(e, data) {
            if (!viewState.loadingConfirmAction) {
                actionCallback(getOtherUserId()).catch(Notification.exception);
            }
            data.originalEvent.preventDefault();
        };
    };

    /**
     * Send message event handler.
     *
     * @param {Object} e Element this event handler is called on.
     * @param {Object} data Data for this event.
     */
    var handleSendMessage = function(e, data) {
        var target = $(e.target);
        var footerContainer = target.closest(SELECTORS.FOOTER_CONTAINER);
        var textArea = footerContainer.find(SELECTORS.MESSAGE_TEXT_AREA);
        var text = textArea.val().trim();

        if (text !== '') {
            sendMessage(viewState.id, text);
        }

        data.originalEvent.preventDefault();
    };

    /**
     * Select message event handler.
     *
     * @param {Object} e Element this event handler is called on.
     * @param {Object} data Data for this event.
     */
    var handleSelectMessage = function(e, data) {
        var element = $(e.target).closest(SELECTORS.MESSAGE);
        var messageId = parseInt(element.attr('data-message-id'), 10);

        toggleSelectMessage(messageId).catch(Notification.exception);

        data.originalEvent.preventDefault();
    };

    /**
     * Cancel edit mode event handler.
     *
     * @param {Object} e Element this event handler is called on.
     * @param {Object} data Data for this event.
     */
    var handleCancelEditMode = function(e, data) {
        cancelEditMode().catch(Notification.exception);
        data.originalEvent.preventDefault();
    };

    var headerActivateHandlers = [
        [SELECTORS.ACTION_REQUEST_BLOCK, generateConfirmActionHandler(requestBlockUser)],
        [SELECTORS.ACTION_REQUEST_UNBLOCK, generateConfirmActionHandler(requestUnblockUser)],
        [SELECTORS.ACTION_REQUEST_ADD_CONTACT, generateConfirmActionHandler(requestAddContact)],
        [SELECTORS.ACTION_REQUEST_REMOVE_CONTACT, generateConfirmActionHandler(requestRemoveContact)],
        [SELECTORS.ACTION_REQUEST_DELETE_CONVERSATION, generateConfirmActionHandler(requestDeleteConversation)],
        [SELECTORS.ACTION_CANCEL_EDIT_MODE, handleCancelEditMode]
    ];
    var bodyActivateHandlers = [
        [SELECTORS.ACTION_CANCEL_CONFIRM, generateConfirmActionHandler(cancelRequest)],
        [SELECTORS.ACTION_CONFIRM_BLOCK, generateConfirmActionHandler(blockUser)],
        [SELECTORS.ACTION_CONFIRM_UNBLOCK, generateConfirmActionHandler(unblockUser)],
        [SELECTORS.ACTION_CONFIRM_ADD_CONTACT, generateConfirmActionHandler(addContact)],
        [SELECTORS.ACTION_CONFIRM_REMOVE_CONTACT, generateConfirmActionHandler(removeContact)],
        [SELECTORS.ACTION_CONFIRM_DELETE_SELECTED_MESSAGES, generateConfirmActionHandler(deleteSelectedMessages)],
        [SELECTORS.ACTION_CONFIRM_DELETE_CONVERSATION, generateConfirmActionHandler(deleteConversation)],
        [SELECTORS.ACTION_REQUEST_ADD_CONTACT, generateConfirmActionHandler(requestAddContact)],
        [SELECTORS.MESSAGE, handleSelectMessage]
    ];
    var footerActivateHandlers = [
        [SELECTORS.SEND_MESSAGE_BUTTON, handleSendMessage],
        [SELECTORS.ACTION_REQUEST_DELETE_SELECTED_MESSAGES, generateConfirmActionHandler(requestDeleteSelectedMessages)],
        [SELECTORS.ACTION_REQUEST_ADD_CONTACT, generateConfirmActionHandler(requestAddContact)],
        [SELECTORS.ACTION_REQUEST_UNBLOCK, generateConfirmActionHandler(requestUnblockUser)],
    ];

    /**
     * Listen to, and handle events for conversations.
     *
     * @param {Object} header Conversation header container element.
     * @param {Object} body Conversation body container element.
     * @param {Object} footer Conversation footer container element.
     */
    var registerEventListeners = function(header, body, footer) {
        var isLoadingMoreMessages = false;
        var messagesContainer = getMessagesContainer(body);

        AutoRows.init(footer);

        CustomEvents.define(header, [
            CustomEvents.events.activate
        ]);
        CustomEvents.define(body, [
            CustomEvents.events.activate
        ]);
        CustomEvents.define(footer, [
            CustomEvents.events.activate
        ]);
        CustomEvents.define(messagesContainer, [
            CustomEvents.events.scrollTop,
            CustomEvents.events.scrollLock
        ]);

        messagesContainer.on(CustomEvents.events.scrollTop, function(e, data) {
            var hasMembers = Object.keys(viewState.members).length > 1;

            if (!isLoadingMoreMessages && !loadedAllMessages && hasMembers) {
                loadMessages(viewState.id, LOAD_MESSAGE_LIMIT, messagesOffset, NEWEST_FIRST)
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

        headerActivateHandlers.forEach(function(handler) {
            var selector = handler[0];
            var handlerFunction = handler[1];
            header.on(CustomEvents.events.activate, selector, handlerFunction);
        });

        bodyActivateHandlers.forEach(function(handler) {
            var selector = handler[0];
            var handlerFunction = handler[1];
            body.on(CustomEvents.events.activate, selector, handlerFunction);
        });

        footerActivateHandlers.forEach(function(handler) {
            var selector = handler[0];
            var handlerFunction = handler[1];
            footer.on(CustomEvents.events.activate, selector, handlerFunction);
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

    /**
     * Load new messages into the conversation based on a time interval.
     *
     * @param  {Object} body Conversation body container element.
     * @param  {Number} conversationId The conversation id.
     * @return {Promise} Renderer promise.
     */
    var reset = function(body, conversationId) {
        var loggedInUserProfile = getLoggedInUserProfile(body);
        var loggedInUserId = loggedInUserProfile.userid;
        var midnight = parseInt(body.attr('data-midnight'), 10);
        var newState = StateManager.buildInitialState(midnight, loggedInUserId, conversationId, '');
        messagesOffset = 0;

        if (!Object.keys(viewState).length) {
            viewState = newState;
        }

        if (newMessagesPollTimer) {
            newMessagesPollTimer.stop();
        }

        newMessagesPollTimer = new BackOffTimer(
            getLoadNewMessagesCallback(conversationId, NEWEST_FIRST),
            function(time) {
                if (!time) {
                    return INITIAL_NEW_MESSAGE_POLL_TIMEOUT;
                }

                return time * 2;
            }
        );

        newMessagesPollTimer.start();

        return render(newState)
            .then(function() {
                return loadProfile(loggedInUserProfile, conversationId);
            })
            .then(function() {
                return loadMessages(viewState.id, LOAD_MESSAGE_LIMIT, messagesOffset, NEWEST_FIRST);
            })
            .then(function() {
                messagesOffset = messagesOffset + LOAD_MESSAGE_LIMIT;
                return messagesOffset;
            })
            .then(function() {
                return markConversationAsRead(viewState.id);
            })
            .catch(Notification.exception);
    };

    /**
     * Setup the conversation page.
     *
     * @param {Object} header Conversation header container element.
     * @param {Object} body Conversation body container element.
     * @param {Object} footer Conversation footer container element.
     */
    var show = function(header, body, footer, conversationId, action) {
        if (!body.attr('data-init')) {
            render = generateRenderFunction(header, body, footer);
            registerEventListeners(header, body, footer);
            reset(body, conversationId);
            body.attr('data-init', true);
        } else {
            var currentConversationId = viewState.id;
            if (currentConversationId != conversationId) {
                reset(body, conversationId);
            }
        }

        switch(action) {
            case 'block':
                requestBlockUser(conversationId);
                break;
            case 'unblock':
                requestUnblockUser(conversationId);
                break;
            case 'add-contact':
                requestAddContact(conversationId);
                break;
            case 'remove-contact':
                requestRemoveContact(conversationId);
                break;
        }
    };

    return {
        show: show,
    };
});
