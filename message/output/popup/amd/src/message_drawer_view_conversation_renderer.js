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
    'core/str',
    'core/templates'
],
function(
    $,
    Str,
    Templates
) {
    var SELECTORS = {
        ACTION_CONFIRM_BLOCK: '[data-action="confirm-block"]',
        ACTION_CONFIRM_UNBLOCK: '[data-action="confirm-unblock"]',
        ACTION_CONFIRM_REMOVE_CONTACT: '[data-action="confirm-remove-contact"]',
        ACTION_CONFIRM_ADD_CONTACT: '[data-action="confirm-add-contact"]',
        ACTION_CONFIRM_DELETE_SELECTED_MESSAGES: '[data-action="confirm-delete-selected-messages"]',
        ACTION_CONFIRM_DELETE_CONVERSATION: '[data-action="confirm-delete-conversation"]',
        ACTION_REQUEST_BLOCK: '[data-action="request-block"]',
        ACTION_REQUEST_UNBLOCK: '[data-action="request-unblock"]',
        ACTION_REQUEST_REMOVE_CONTACT: '[data-action="request-remove-contact"]',
        ACTION_REQUEST_ADD_CONTACT: '[data-action="request-add-contact"]',
        CONFIRM_DIALOGUE_TEXT: '[data-region="dialogue-text"]',
        HEADER: '[data-region="header-content"]',
        HEADER_EDIT_MODE: '[data-region="header-edit-mode"]',
        HEADER_PLACEHOLDER_CONTAINER: '[data-region="header-placeholder"]',
        MESSAGE: '[data-region="message"]',
        MESSAGE_NOT_SELECTED_ICON: '[data-region="not-selected-icon"]',
        MESSAGE_SELECTED_ICON: '[data-region="selected-icon"]',
        MESSAGES: '[data-region="view-conversation-messages"]',
        MESSAGES_SELECTED_COUNT: '[data-region="message-selected-court"]',
        CONTENT_PLACEHOLDER_CONTAINER: '[data-region="content-placeholder"]',
        MESSAGE_TEXT_AREA: '[data-region="send-message-txt"]',
        SEND_MESSAGE_BUTTON: '[data-action="send-message"]',
        SEND_MESSAGE_ICON_CONTAINER: '[data-region="send-icon-container"]',
        DAY_MESSAGES_CONTAINER: '[data-region="day-messages-container"]',
        CONTENT_CONTAINER: '[data-region="content-container"]',
        CONTENT_MESSAGES_CONTAINER: '[data-region="content-message-container"]',
        CONTENT_MESSAGES_FOOTER_CONTAINER: '[data-region="content-messages-footer-container"]',
        CONTENT_MESSAGES_FOOTER_EDIT_MODE_CONTAINER: '[data-region="content-messages-footer-edit-mode-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        MORE_MESSAGES_LOADING_ICON_CONTAINER: '[data-region="more-messages-loading-icon-container"]',
        RESPONSE_CONTAINER: '[data-region="response"]',
        CONFIRM_DIALOGUE_CONTAINER: '[data-region="confirm-dialogue-container"]',
        CONFIRM_DIALOGUE_BUTTON_TEXT: '[data-region="dialogue-button-text"]'

    };
    var TEMPLATES = {
        HEADER: 'message_popup/message_drawer_view_conversation_header',
        DAY: 'message_popup/message_drawer_view_conversation_day',
        MESSAGE: 'message_popup/message_drawer_view_conversation_message',
        MESSAGES: 'message_popup/message_drawer_view_conversation_messages',
        ADDCONTACT: 'message_popup/message_drawer_add_contact'
    };

    var getContentMessagesContainer = function(root) {
        return root.find(SELECTORS.CONTENT_MESSAGES_CONTAINER);
    };

    var showContentMessagesContainer = function(root) {
        getContentMessagesContainer(root).removeClass('hidden');
    };

    var hideContentMessagesContainer = function(root) {
        getContentMessagesContainer(root).addClass('hidden');
    };

    var getContentMessagesFooterContainer = function(root) {
        return root.find(SELECTORS.CONTENT_MESSAGES_FOOTER_CONTAINER);
    };

    var showContentMessagesFooterContainer = function(root) {
        getContentMessagesFooterContainer(root).removeClass('hidden');
    };

    var hideContentMessagesFooterContainer = function(root) {
        getContentMessagesFooterContainer(root).addClass('hidden');
    };

    var getContentMessagesFooterEditModeContainer = function(root) {
        return root.find(SELECTORS.CONTENT_MESSAGES_FOOTER_EDIT_MODE_CONTAINER);
    };

    var showContentMessagesFooterEditModeContainer = function(root) {
        getContentMessagesFooterEditModeContainer(root).removeClass('hidden');
    };

    var hideContentMessagesFooterEditModeContainer = function(root) {
        getContentMessagesFooterEditModeContainer(root).addClass('hidden');
    };

    var getContentPlaceholderContainer = function(root) {
        return root.find(SELECTORS.CONTENT_PLACEHOLDER_CONTAINER);
    };

    var showContentPlaceholder = function(root) {
        getContentPlaceholderContainer(root).removeClass('hidden');
    };

    var hideContentPlaceholder = function(root) {
        getContentPlaceholderContainer(root).addClass('hidden');
    };

    var getHeaderContent = function(root) {
        return root.find(SELECTORS.HEADER);
    };

    var showHeaderContent = function(root) {
        getHeaderContent(root).removeClass('hidden');
    };

    var hideHeaderContent = function(root) {
        getHeaderContent(root).addClass('hidden');
    };

    var getHeaderEditMode = function(root) {
        return root.find(SELECTORS.HEADER_EDIT_MODE);
    };

    var showHeaderEditMode = function(root) {
        getHeaderEditMode(root).removeClass('hidden');
    };

    var hideHeaderEditMode = function(root) {
        getHeaderEditMode(root).addClass('hidden');
    };

    var getHeaderPlaceholderContainer = function(root) {
        return root.find(SELECTORS.HEADER_PLACEHOLDER_CONTAINER);
    };

    var showHeaderPlaceholder = function(root) {
        getHeaderPlaceholderContainer(root).removeClass('hidden');
    };

    var hideHeaderPlaceholder = function(root) {
        getHeaderPlaceholderContainer(root).addClass('hidden');
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

    var getMoreMessagesLoadingIconContainer = function(root) {
        return root.find(SELECTORS.MORE_MESSAGES_LOADING_ICON_CONTAINER);
    };

    var showMoreMessagesLoadingIcon = function(root) {
        getMoreMessagesLoadingIconContainer(root).removeClass('hidden');
    };

    var hideMoreMessagesLoadingIcon = function(root) {
        getMoreMessagesLoadingIconContainer(root).addClass('hidden');
    };

    var disableSendMessage = function(root) {
        root.find(SELECTORS.SEND_MESSAGE_BUTTON).prop('disabled', true);
        getMessageTextArea(root).prop('disabled', true);
    };

    var enableSendMessage = function(root) {
        root.find(SELECTORS.SEND_MESSAGE_BUTTON).prop('disabled', false);
        getMessageTextArea(root).prop('disabled', false);
    };

    var startSendMessageLoading = function(root) {
        disableSendMessage(root);
        var responseContainer = root.find(SELECTORS.RESPONSE_CONTAINER);
        responseContainer.find(SELECTORS.SEND_MESSAGE_ICON_CONTAINER).addClass('hidden');
        responseContainer.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var stopSendMessageLoading = function(root) {
        enableSendMessage(root);
        var responseContainer = root.find(SELECTORS.RESPONSE_CONTAINER);
        responseContainer.find(SELECTORS.SEND_MESSAGE_ICON_CONTAINER).removeClass('hidden');
        responseContainer.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var hasSentMessage = function(root) {
        var textArea = getMessageTextArea(root);
        textArea.val('');
        textArea.focus();
    };

    var getConfirmDialogueContainer = function(root) {
        return root.find(SELECTORS.CONFIRM_DIALOGUE_CONTAINER);
    };

    var showConfirmDialogue = function(root) {
        getConfirmDialogueContainer(root).removeClass('hidden');
    };

    var hideConfirmDialogue = function(root) {
        getConfirmDialogueContainer(root).addClass('hidden');
    };

    var setMessagesSelectedCount = function(root, value) {
        getHeaderEditMode(root).find(SELECTORS.MESSAGES_SELECTED_COUNT).text(value);
    };

    var formatMessagesForTemplate = function(messages) {
        return messages.map(function(message) {
            return {
                id: message.id,
                isread: message.isRead,
                fromloggedinuser: message.fromLoggedInUser,
                useridfrom: message.userIdFrom,
                text: message.text,
                timecreated: parseInt(message.timeCreated, 10)
            };
        });
    };

    var renderAddDays = function(root, days) {
        var messagesContainer = getMessagesContainer(root);
        var daysRenderPromises = days.map(function(data) {
            return Templates.render(TEMPLATES.DAY, {
                timestamp: data.value.timestamp,
                messages: formatMessagesForTemplate(data.value.messages)
            });
        });

        return $.when.apply($, daysRenderPromises).then(function() {
            // Wait until all of the rendering is done for each of the days
            // to ensure they are added to the page in the correct order.
            days.forEach(function(data, index) {
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
    };

    var renderAddMessages = function(root, messages) {
        var messagesRenderPromises = messages.map(function(data) {
            var formattedMessages = formatMessagesForTemplate([data.value]);
            return Templates.render(TEMPLATES.MESSAGE, formattedMessages[0]);
        });

        return $.when.apply($, messagesRenderPromises).then(function() {
            // Wait until all of the rendering is done for each of the messages
            // to ensure they are added to the page in the correct order.
            messages.forEach(function(data, index) {
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
    };

    var renderRemoveDays = function(root, days) {
        days.forEach(function(data) {
            getDayElement(root, data.timestamp).remove();
        });
    };

    var renderRemoveMessages = function(root, messages) {
        messages.forEach(function(data) {
            getMessageElement(root, data.id).remove();
        });
    };

    var renderConversation = function(root, data) {
        var renderingPromises = [];

        if (data.days.add.length > 0) {
            renderingPromises = renderingPromises.concat(renderAddDays(root, data.days.add));
        }

        if (data.messages.add.length > 0) {
            renderingPromises = renderingPromises.concat(renderAddMessages(root, data.messages.add));
        }

        if (data.days.remove.length > 0) {
            renderRemoveDays(root, data.days.remove);
        }

        if (data.messages.remove.length > 0) {
            renderRemoveMessages(root, data.messages.remove);
        }

        return $.when.apply($, renderingPromises);
    };

    var renderHeader = function(root, data) {
        var headerContainer = getHeaderContent(root);
        return Templates.render(TEMPLATES.HEADER, data.context)
            .then(function(html, js) {
                Templates.replaceNodeContents(headerContainer, html, js);
            });
    };

    var renderScrollToMessage = function(root, messageId) {
        var messagesContainer = getMessagesContainer(root);
        var messageElement = getMessageElement(root, messageId);
        // Scroll the message container down to the top of the message element.
        var scrollTop = messagesContainer.scrollTop() + messageElement.position().top;
        messagesContainer.scrollTop(scrollTop);
    };

    var renderLoadingMembers = function(root, isLoadingMembers) {
        if (isLoadingMembers) {
            hideHeaderContent(root);
            showHeaderPlaceholder(root);
        } else {
            showHeaderContent(root);
            hideHeaderPlaceholder(root);
        }
    };

    var renderLoadingFirstMessages = function(root, isLoadingFirstMessages) {
        if (isLoadingFirstMessages) {
            hideContentMessagesContainer(root);
            showContentPlaceholder(root);
        } else {
            showContentMessagesContainer(root);
            hideContentPlaceholder(root);
        }
    };

    var renderLoadingMessages = function(root, isLoading) {
        if (isLoading) {
            showMoreMessagesLoadingIcon(root);
        } else {
            hideMoreMessagesLoadingIcon(root);
        }
    };

    var renderSendingMessage = function(root, isSending) {
        if (isSending) {
            startSendMessageLoading(root);
        } else {
            stopSendMessageLoading(root);
            hasSentMessage(root);
        }
    };

    var renderConfirmDialogue = function(root, show, buttonSelector, stringPromise) {
        var dialogue = getConfirmDialogueContainer(root);
        var button = dialogue.find(buttonSelector);
        var text = dialogue.find(SELECTORS.CONFIRM_DIALOGUE_TEXT);
        if (show) {
            return stringPromise.then(function(string) {
                    button.removeClass('hidden');
                    text.text(string);
                    return showConfirmDialogue(root);
                });
        } else {
            hideConfirmDialogue(root);
            button.addClass('hidden');
            text.text('');
            return true;
        }
    };

    var renderConfirmBlockUser = function(root, user) {
        var show = user ? true : false;
        var stringPromise = show ? Str.get_string('blockuserconfirm', 'core_message', user.fullname) : null;
        return renderConfirmDialogue(root, show, SELECTORS.ACTION_CONFIRM_BLOCK, stringPromise);
    };

    var renderConfirmUnblockUser = function(root, user) {
        var show = user ? true : false;
        var stringPromise = show ? Str.get_string('unblockuserconfirm', 'core_message', user.fullname) : null;
        return renderConfirmDialogue(root, show, SELECTORS.ACTION_CONFIRM_UNBLOCK, stringPromise);
    };

    var renderConfirmAddContact = function(root, user) {
        var show = user ? true : false;
        var stringPromise = show ? Str.get_string('addcontactconfirm', 'core_message', user.fullname) : null;
        return renderConfirmDialogue(root, show, SELECTORS.ACTION_CONFIRM_ADD_CONTACT, stringPromise);
    };

    var renderConfirmRemoveContact = function(root, user) {
        var show = user ? true : false;
        var stringPromise = show ? Str.get_string('removecontactconfirm', 'core_message', user.fullname) : null;
        return renderConfirmDialogue(root, show, SELECTORS.ACTION_CONFIRM_REMOVE_CONTACT, stringPromise);
    };

    var renderConfirmDeleteSelectedMessages = function(root, show) {
        var stringPromise = Str.get_string('deleteselectedmessagesconfirm', 'core_message');
        return renderConfirmDialogue(root, show, SELECTORS.ACTION_CONFIRM_DELETE_SELECTED_MESSAGES, stringPromise);
    };

    var renderConfirmDeleteConversation = function(root, show) {
        var stringPromise = Str.get_string('deleteallconfirm', 'core_message');
        return renderConfirmDialogue(root, show, SELECTORS.ACTION_CONFIRM_DELETE_CONVERSATION, stringPromise);
    };

    var renderIsBlocked = function(root, isBlocked) {
        if (isBlocked) {
            root.find(SELECTORS.ACTION_REQUEST_BLOCK).addClass('hidden');
            root.find(SELECTORS.ACTION_REQUEST_UNBLOCK).removeClass('hidden');
        } else {
            root.find(SELECTORS.ACTION_REQUEST_BLOCK).removeClass('hidden');
            root.find(SELECTORS.ACTION_REQUEST_UNBLOCK).addClass('hidden');
        }
    };

    var renderIsContact = function(root, isContact) {
        if (isContact) {
            root.find(SELECTORS.ACTION_REQUEST_ADD_CONTACT).addClass('hidden');
            root.find(SELECTORS.ACTION_REQUEST_REMOVE_CONTACT).removeClass('hidden');
        } else {
            root.find(SELECTORS.ACTION_REQUEST_ADD_CONTACT).removeClass('hidden');
            root.find(SELECTORS.ACTION_REQUEST_REMOVE_CONTACT).addClass('hidden');
        }
    };

    var renderLoadingConfirmAction = function(root, isLoading) {
        var dialogue = getConfirmDialogueContainer(root);
        var buttons = dialogue.find('button');
        var buttonText = dialogue.find(SELECTORS.CONFIRM_DIALOGUE_BUTTON_TEXT);
        var loadingIcon = dialogue.find(SELECTORS.LOADING_ICON_CONTAINER);

        if (isLoading) {
            buttons.prop('disabled', true);
            buttonText.addClass('hidden');
            loadingIcon.removeClass('hidden');
        } else {
            buttons.prop('disabled', false);
            buttonText.removeClass('hidden');
            loadingIcon.addClass('hidden');
        }
    };

    var renderInEditMode = function(root, inEditMode) {
        var messages = root.find(SELECTORS.MESSAGE);

        if (inEditMode) {
            messages.find(SELECTORS.MESSAGE_NOT_SELECTED_ICON).removeClass('hidden');
            hideHeaderContent(root);
            showHeaderEditMode(root);
            hideContentMessagesFooterContainer(root);
            showContentMessagesFooterEditModeContainer(root);
        } else {
            messages.find(SELECTORS.MESSAGE_NOT_SELECTED_ICON).addClass('hidden');
            messages.find(SELECTORS.MESSAGE_SELECTED_ICON).addClass('hidden');
            showHeaderContent(root);
            hideHeaderEditMode(root);
            showContentMessagesFooterContainer(root);
            hideContentMessagesFooterEditModeContainer(root);
        }
    };

    var renderSelectedMessages = function(root, data) {
        var hasSelectedMessages = data.count > 0;

        if (data.add.length) {
            data.add.forEach(function(messageId) {
                var message = getMessageElement(root, messageId);
                message.find(SELECTORS.MESSAGE_NOT_SELECTED_ICON).addClass('hidden');
                message.find(SELECTORS.MESSAGE_SELECTED_ICON).removeClass('hidden');
            });
        }

        if (data.remove.length) {
            data.remove.forEach(function(messageId) {
                var message = getMessageElement(root, messageId);

                if (hasSelectedMessages) {
                    message.find(SELECTORS.MESSAGE_NOT_SELECTED_ICON).removeClass('hidden');
                }

                message.find(SELECTORS.MESSAGE_SELECTED_ICON).addClass('hidden');
            });
        }

        setMessagesSelectedCount(root, data.count);
    };

    var render = function(root, patch) {
        var configs = [
            {
                // Any async rendering (stuff that requires templates) should
                // go in here.
                conversation: renderConversation,
                header: renderHeader,
                confirmBlockUser: renderConfirmBlockUser,
                confirmUnblockUser: renderConfirmUnblockUser,
                confirmAddContact: renderConfirmAddContact,
                confirmRemoveContact: renderConfirmRemoveContact,
                confirmDeleteSelectedMessages: renderConfirmDeleteSelectedMessages,
                confirmDeleteConversation: renderConfirmDeleteConversation,
            },
            {
                loadingMembers: renderLoadingMembers,
                loadingFirstMessages: renderLoadingFirstMessages,
                loadingMessages: renderLoadingMessages,
                sendingMessage: renderSendingMessage,
                isBlocked: renderIsBlocked,
                isContact: renderIsContact,
                loadingConfirmAction: renderLoadingConfirmAction,
                inEditMode: renderInEditMode
            },
            {
                // Scrolling should be last to make sure everything
                // on the page is visible.
                scrollToMessage: renderScrollToMessage,
                selectedMessages: renderSelectedMessages
            }
        ];
        // Helper function to process each of the configs above.
        var processConfig = function(config) {
            var results = [];

            for (var key in patch) {
                if (config.hasOwnProperty(key)) {
                    var renderFunc = config[key];
                    var patchValue = patch[key];
                    results.push(renderFunc(root, patchValue));
                }
            }

            return results;
        };

        // The first config is special because it contains async rendering.
        var renderingPromises = processConfig(configs[0]);

        // Wait for the async rendering to complete before processing the
        // rest of the configs, in order.
        return $.when.apply($, renderingPromises)
            .then(function() {
                for (var i = 1; i < configs.length; i++) {
                    processConfig(configs[i]);
                }
            });
    };

    return {
        render: render,
    };
});
