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
        ACTION_REQUEST_BLOCK: '[data-action="request-block"]',
        ACTION_REQUEST_UNBLOCK: '[data-action="request-unblock"]',
        ACTION_REQUEST_REMOVE_CONTACT: '[data-action="request-remove-contact"]',
        ACTION_REQUEST_ADD_CONTACT: '[data-action="request-add-contact"]',
        CONFIRM_DIALOGUE_TEXT: '[data-region="dialogue-text"]',
        HEADER: '[data-region="view-conversation-header"]',
        HEADER_PLACEHOLDER_CONTAINER: '[data-region="header-placeholder"]',
        MESSAGES: '[data-region="view-conversation-messages"]',
        CONTENT_PLACEHOLDER_CONTAINER: '[data-region="content-placeholder"]',
        MESSAGE_TEXT_AREA: '[data-region="send-message-txt"]',
        SEND_MESSAGE_BUTTON: '[data-action="send-message"]',
        SEND_MESSAGE_ICON_CONTAINER: '[data-region="send-icon-container"]',
        DAY_MESSAGES_CONTAINER: '[data-region="day-messages-container"]',
        CONTENT_CONTAINER: '[data-region="content-container"]',
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

    var getContentContainer = function(root) {
        return root.find(SELECTORS.CONTENT_CONTAINER);
    };

    var showContentContainer = function(root) {
        getContentContainer(root).removeClass('hidden');
    };

    var hideContentContainer = function(root) {
        getContentContainer(root).addClass('hidden');
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

    var getHeaderContainer = function(root) {
        return root.find(SELECTORS.HEADER);
    };

    var showHeaderContainer = function(root) {
        getHeaderContainer(root).removeClass('hidden');
    };

    var hideHeaderContainer = function(root) {
        getHeaderContainer(root).addClass('hidden');
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

    var renderAddDays = function(root, days) {
        var messagesContainer = getMessagesContainer(root);
        var daysRenderPromises = days.map(function(data) {
            return Templates.render(TEMPLATES.DAY, data.value);
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
            return Templates.render(TEMPLATES.MESSAGE, data.value);
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
        var headerContainer = getHeaderContainer(root);
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
            hideHeaderContainer(root);
            showHeaderPlaceholder(root);
        } else {
            showHeaderContainer(root);
            hideHeaderPlaceholder(root);
        }
    };

    var renderLoadingFirstMessages = function(root, isLoadingFirstMessages) {
        if (isLoadingFirstMessages) {
            hideContentContainer(root);
            showContentPlaceholder(root);
        } else {
            showContentContainer(root);
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

    var renderConfirmDialogue = function(root, user, buttonSelector, stringName) {
        var dialogue = getConfirmDialogueContainer(root);
        var button = dialogue.find(buttonSelector);
        var text = dialogue.find(SELECTORS.CONFIRM_DIALOGUE_TEXT);
        if (user) {
            return Str.get_string(stringName, 'core_message', user.fullname)
                .then(function(string) {
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
        return renderConfirmDialogue(root, user, SELECTORS.ACTION_CONFIRM_BLOCK, 'blockuserconfirm');
    };

    var renderConfirmUnblockUser = function(root, user) {
        return renderConfirmDialogue(root, user, SELECTORS.ACTION_CONFIRM_UNBLOCK, 'unblockuserconfirm');
    };

    var renderConfirmAddContact = function(root, user) {
        return renderConfirmDialogue(root, user, SELECTORS.ACTION_CONFIRM_ADD_CONTACT, 'addcontactconfirm');
    };

    var renderConfirmRemoveContact = function(root, user) {
        return renderConfirmDialogue(root, user, SELECTORS.ACTION_CONFIRM_REMOVE_CONTACT, 'removecontactconfirm');
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

    var render = function(root, patch) {
        var configs = [
            {
                // Any async rendering (stuff that required templates) should
                // go in here.
                conversation: renderConversation,
                header: renderHeader,
                confirmBlockUser: renderConfirmBlockUser,
                confirmUnblockUser: renderConfirmUnblockUser,
                confirmAddContact: renderConfirmAddContact,
                confirmRemoveContact: renderConfirmRemoveContact,
            },
            {
                loadingMembers: renderLoadingMembers,
                loadingFirstMessages: renderLoadingFirstMessages,
                loadingMessages: renderLoadingMessages,
                sendingMessage: renderSendingMessage,
                isBlocked: renderIsBlocked,
                isContact: renderIsContact,
                loadingConfirmAction: renderLoadingConfirmAction
            },
            {
                // Scrolling should be last to make sure everything
                // on the page is visible.
                scrollToMessage: renderScrollToMessage
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
