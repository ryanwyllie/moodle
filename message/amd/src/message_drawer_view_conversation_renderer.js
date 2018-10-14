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
        CONFIRM_DIALOGUE_HEADER: '[data-region="dialogue-header"]',
        HEADER: '[data-region="header-content"]',
        HEADER_EDIT_MODE: '[data-region="header-edit-mode"]',
        HEADER_PLACEHOLDER_CONTAINER: '[data-region="header-placeholder"]',
        MESSAGE: '[data-region="message"]',
        MESSAGE_NOT_SELECTED: '[data-region="message"][aria-checked="false"]',
        MESSAGE_NOT_SELECTED_ICON: '[data-region="not-selected-icon"]',
        MESSAGE_SELECTED_ICON: '[data-region="selected-icon"]',
        MESSAGES: '[data-region="content-message-container"]',
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
        CONTENT_MESSAGES_FOOTER_REQUIRE_CONTACT_CONTAINER: '[data-region="content-messages-footer-require-contact-container"]',
        CONTENT_MESSAGES_FOOTER_REQUIRE_UNBLOCK_CONTAINER: '[data-region="content-messages-footer-require-unblock-container"]',
        CONTENT_MESSAGES_FOOTER_UNABLE_TO_MESSAGE_CONTAINER: '[data-region="content-messages-footer-unable-to-message"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        MORE_MESSAGES_LOADING_ICON_CONTAINER: '[data-region="more-messages-loading-icon-container"]',
        CONFIRM_DIALOGUE_CONTAINER: '[data-region="confirm-dialogue-container"]',
        CONFIRM_DIALOGUE_BUTTON_TEXT: '[data-region="dialogue-button-text"]',
        CONFIRM_DIALOGUE_CANCEL_BUTTON: '[data-action="cancel-confirm"]',
        PLACEHOLDER_CONTAINER: '[data-region="placeholder-container"]',
        TITLE: '[data-region="title"]',
        TEXT: '[data-region="text"]'
    };
    var TEMPLATES = {
        HEADER: 'core_message/message_drawer_view_conversation_header_content',
        DAY: 'core_message/message_drawer_view_conversation_body_day',
        MESSAGE: 'core_message/message_drawer_view_conversation_body_message',
        MESSAGES: 'core_message/message_drawer_view_conversation_body_messages'
    };

    /**
     * Get the messages container element.
     * 
     * @param  {Object} body Conversation body container element.
     * @return {Object} The messages container element.
     */
    var getMessagesContainer = function(body) {
        return body.find(SELECTORS.CONTENT_MESSAGES_CONTAINER);
    };

    /**
     * Show the messages container element.
     * 
     * @param  {Object} body Conversation body container element.
     */
    var showMessagesContainer = function(body) {
        getMessagesContainer(body).removeClass('hidden');
    };

    /**
     * Hide the messages container element.
     * 
     * @param  {Object} body Conversation body container element.
     */
    var hideMessagesContainer = function(body) {
        getMessagesContainer(body).addClass('hidden');
    };

    /**
     * Get the footer container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     * @return {Object} The footer container element.
     */
    var getFooterContentContainer = function(footer) {
        return footer.find(SELECTORS.CONTENT_MESSAGES_FOOTER_CONTAINER);
    };

    /**
     * Show the footer container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var showFooterContent = function(footer) {
        getFooterContentContainer(footer).removeClass('hidden');
    };

    /**
     * Hide the footer container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var hideFooterContent = function(footer) {
        getFooterContentContainer(footer).addClass('hidden');
    };

    /**
     * Get the footer edit mode container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     * @return {Object} The footer container element.
     */
    var getFooterEditModeContainer = function(footer) {
        return footer.find(SELECTORS.CONTENT_MESSAGES_FOOTER_EDIT_MODE_CONTAINER);
    };

    /**
     * Show the footer edit mode container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var showFooterEditMode = function(footer) {
        getFooterEditModeContainer(footer).removeClass('hidden');
    };

    /**
     * Hide the footer edit mode container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var hideFooterEditMode = function(footer) {
        getFooterEditModeContainer(footer).addClass('hidden');
    };

    /**
     * Get the footer placeholder.
     * 
     * @param  {Object} footer Conversation footer container element.
     * @return {Object} The footer placeholder container element.
     */
    var getFooterPlaceholderContainer = function(footer) {
        return footer.find(SELECTORS.PLACEHOLDER_CONTAINER);
    };

    /**
     * Show the footer placeholder
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var showFooterPlaceholder = function(footer) {
        getFooterPlaceholderContainer(footer).removeClass('hidden');
    };

    /**
     * Hide the footer placeholder
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var hideFooterPlaceholder = function(footer) {
        getFooterPlaceholderContainer(footer).addClass('hidden');
    };

    /**
     * Get the footer Require add as contact container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     * @return {Object} The footer Require add as contact container element.
     */
    var getFooterRequireContactContainer = function(footer) {
        return footer.find(SELECTORS.CONTENT_MESSAGES_FOOTER_REQUIRE_CONTACT_CONTAINER);
    };

    /**
     * Show the footer add as contact dialogue.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var showFooterRequireContact = function(footer) {
        getFooterRequireContactContainer(footer).removeClass('hidden');
    };

    /**
     * Hide the footer add as contact dialogue.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var hideFooterRequireContact = function(footer) {
        getFooterRequireContactContainer(footer).addClass('hidden');
    };

    /**
     * Get the footer Required to unblock contact container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     * @return {Object} The footer Required to unblock contact container element.
     */
    var getFooterRequireUnblockContainer = function(footer) {
        return footer.find(SELECTORS.CONTENT_MESSAGES_FOOTER_REQUIRE_UNBLOCK_CONTAINER);
    };

    /**
     * Show the footer Required to unblock contact container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var showFooterRequireUnblock = function(footer) {
        getFooterRequireUnblockContainer(footer).removeClass('hidden');
    };

    /**
     * Hide the footer Required to unblock contact container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var hideFooterRequireUnblock = function(footer) {
        getFooterRequireUnblockContainer(footer).addClass('hidden');
    };

    /**
     * Get the footer Unable to message contact container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     * @return {Object} The footer Unable to message contact container element.
     */
    var getFooterUnableToMessageContainer = function(footer) {
        return footer.find(SELECTORS.CONTENT_MESSAGES_FOOTER_UNABLE_TO_MESSAGE_CONTAINER);
    };

    /**
     * Show the footer Unable to message contact container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var showFooterUnableToMessage = function(footer) {
        getFooterUnableToMessageContainer(footer).removeClass('hidden');
    };

    /**
     * Hide the footer Unable to message contact container element.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var hideFooterUnableToMessage = function(footer) {
        getFooterUnableToMessageContainer(footer).addClass('hidden');
    };

    /**
     * Hide all footer dialogues and messages.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var hideAllFooterElements = function(footer) {
        hideFooterContent(footer);
        hideFooterEditMode(footer);
        hideFooterPlaceholder(footer);
        hideFooterRequireContact(footer);
        hideFooterRequireUnblock(footer);
        hideFooterUnableToMessage(footer);
    };

    /**
     * Get the content placeholder container element.
     * 
     * @param  {Object} body Conversation body container element.
     * @return {Object} The body placeholder container element.
     */
    var getContentPlaceholderContainer = function(body) {
        return body.find(SELECTORS.CONTENT_PLACEHOLDER_CONTAINER);
    };

    /**
     * Show the content placeholder.
     * 
     * @param  {Object} body Conversation body container element.
     */
    var showContentPlaceholder = function(body) {
        getContentPlaceholderContainer(body).removeClass('hidden');
    };

    /**
     * Hide the content placeholder.
     * 
     * @param  {Object} body Conversation body container element.
     */
    var hideContentPlaceholder = function(body) {
        getContentPlaceholderContainer(body).addClass('hidden');
    };

    /**
     * Get the header content container element.
     * 
     * @param  {Object} header Conversation header container element.
     * @return {Object} The header content container element.
     */ 
    var getHeaderContent = function(header) {
        return header.find(SELECTORS.HEADER);
    };

    /**
     * Show the header content.
     *
     * @param  {Object} header Conversation header container element.
     */
    var showHeaderContent = function(header) {
        getHeaderContent(header).removeClass('hidden');
    };

    /**
     * Hide the header content.
     *
     * @param  {Object} header Conversation header container element.
     */
    var hideHeaderContent = function(header) {
        getHeaderContent(header).addClass('hidden');
    };

    /**
     * Get the header edit mode container element.
     * 
     * @param  {Object} header Conversation header container element.
     * @return {Object} The header content container element.
     */ 
    var getHeaderEditMode = function(header) {
        return header.find(SELECTORS.HEADER_EDIT_MODE);
    };

    /**
     * Show the header edit mode container.
     * 
     * @param  {Object} header Conversation header container element.
     */
    var showHeaderEditMode = function(header) {
        getHeaderEditMode(header).removeClass('hidden');
    };

    /**
     * Hide the header edit mode container.
     * 
     * @param  {Object} header Conversation header container element.
     */
    var hideHeaderEditMode = function(header) {
        getHeaderEditMode(header).addClass('hidden');
    };

    /**
     * Get the header placeholder container element.
     * 
     * @param  {Object} header Conversation header container element.
     * @return {Object} The header placeholder container element.
     */ 
    var getHeaderPlaceholderContainer = function(header) {
        return header.find(SELECTORS.HEADER_PLACEHOLDER_CONTAINER);
    };

    /**
     * Show the header placeholder.
     * 
     * @param  {Object} header Conversation header container element.
     */
    var showHeaderPlaceholder = function(header) {
        getHeaderPlaceholderContainer(header).removeClass('hidden');
    };

    /**
     * Hide the header placeholder.
     * 
     * @param  {Object} header Conversation header container element.
     */
    var hideHeaderPlaceholder = function(header) {
        getHeaderPlaceholderContainer(header).addClass('hidden');
    };

    /**
     * Get the text input area element.
     * 
     * @param  {Object} header Conversation header container element.
     * @return {Object} The header placeholder container element.
     */ 
    var getMessageTextArea = function(footer) {
        return footer.find(SELECTORS.MESSAGE_TEXT_AREA);
    };

    /**
     * Get a message element.
     * 
     * @param  {Object} body Conversation body container element.
     * @param  {Number} messageId the Message id.
     * @return {Object} A message element from the conversation.
     */
    var getMessageElement = function(body, messageId) {
        var messagesContainer = getMessagesContainer(body);
        return messagesContainer.find('[data-message-id="' + messageId + '"]');
    };

    /**
     * Get the day container element. The day container element holds a list of messages for that day.
     * 
     * @param  {Object} body Conversation body container element.
     * @param  {Object} The day container element.
     */
    var getDayElement = function(body, dayTimeCreated) {
        var messagesContainer = getMessagesContainer(body);
        return messagesContainer.find('[data-day-id="' + dayTimeCreated + '"]');
    };

    /**
     * Get the more messages loading icon container element.
     *
     * @param  {Object} body Conversation body container element.
     * @return {Object} The more messages loading container element.
     */
    var getMoreMessagesLoadingIconContainer = function(body) {
        return body.find(SELECTORS.MORE_MESSAGES_LOADING_ICON_CONTAINER);
    };

    /**
     * Show the more messages loading icon.
     * 
     * @param  {Object} body Conversation body container element.
     */
    var showMoreMessagesLoadingIcon = function(body) {
        getMoreMessagesLoadingIconContainer(body).removeClass('hidden');
    };

    /**
     * Hide the more messages loading icon.
     * 
     * @param  {Object} body Conversation body container element.
     */
    var hideMoreMessagesLoadingIcon = function(body) {
        getMoreMessagesLoadingIconContainer(body).addClass('hidden');
    };

    /**
     * Disable the message controls for sending a message.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var disableSendMessage = function(footer) {
        footer.find(SELECTORS.SEND_MESSAGE_BUTTON).prop('disabled', true);
        getMessageTextArea(footer).prop('disabled', true);
    };

    /**
     * Enable the message controls for sending a message.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var enableSendMessage = function(footer) {
        footer.find(SELECTORS.SEND_MESSAGE_BUTTON).prop('disabled', false);
        getMessageTextArea(footer).prop('disabled', false);
    };

    /**
     * Show the sending message loading icon and disable sending more.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var startSendMessageLoading = function(footer) {
        disableSendMessage(footer);
        footer.find(SELECTORS.SEND_MESSAGE_ICON_CONTAINER).addClass('hidden');
        footer.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    /**
     * Hide the sending message loading icon and allow sending new messages.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var stopSendMessageLoading = function(footer) {
        enableSendMessage(footer);
        footer.find(SELECTORS.SEND_MESSAGE_ICON_CONTAINER).removeClass('hidden');
        footer.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    /**
     * Clear out message text input and focus the input element.
     * 
     * @param  {Object} footer Conversation footer container element.
     */
    var hasSentMessage = function(footer) {
        var textArea = getMessageTextArea(footer);
        textArea.val('');
        textArea.focus();
    };

    /**
     * Get the confirm dialogue container element.
     * 
     * @param  {Object} root The container element to search.
     * @return {Object} The confirm dialogue container element.
     */
    var getConfirmDialogueContainer = function(root) {
        return root.find(SELECTORS.CONFIRM_DIALOGUE_CONTAINER);
    };

    /**
     * Show the confirm dialogue container element.
     * 
     * @param  {Object} root The container element containing a dialogue.
     */
    var showConfirmDialogueContainer = function(root) {
        getConfirmDialogueContainer(root).removeClass('hidden');
    };

    /**
     * Hide the confirm dialogue container element.
     * 
     * @param  {Object} root The container element containing a dialogue.
     */
    var hideConfirmDialogueContainer = function(root) {
        getConfirmDialogueContainer(root).addClass('hidden');
    };

    /**
     * Set the number of selected messages.
     * 
     * @param {Object} header The header container element.
     * @param {Number} value The new number to display.
     */
    var setMessagesSelectedCount = function(header, value) {
        getHeaderEditMode(header).find(SELECTORS.MESSAGES_SELECTED_COUNT).text(value);
    };

    /**
     * Format message for the mustache template, transform camelCase properties to lowercase properties.
     * 
     * @param  {Array} messages Array of message objects.
     * @return {Array} Messages formated for mustache template.
     */
    var formatMessagesForTemplate = function(messages) {
        return messages.map(function(message) {
            return {
                id: message.id,
                isread: message.isRead,
                fromloggedinuser: message.fromLoggedInUser,
                userfrom: message.userFrom,
                text: message.text,
                timecreated: parseInt(message.timeCreated, 10)
            };
        });
    };

    /**
     * Create rendering promises for each day containing messages.
     * 
     * @param  {Object} header The header container element.
     * @param  {Object} body The body container element.
     * @param  {Object} footer The footer container element.
     * @param  {Array} days Array of days containing messages.
     * @return {Promise} Days rendering promises.
     */
    var renderAddDays = function(header, body, footer, days) {
        var messagesContainer = getMessagesContainer(body);
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
                        var element = getDayElement(body, data.before.timestamp);
                        return $(html).insertBefore(element);
                    } else {
                        return messagesContainer.append(html);
                    }
                });
            });
        })
        .catch(function(error) {
            console.log(error);
        });
    };

    /**
     * Add (more) messages to day containers.
     * 
     * @param  {Object} header The header container element.
     * @param  {Object} body The body container element.
     * @param  {Object} footer The footer container element.
     * @param  {Array} messages List of messages.
     */
    var renderAddMessages = function(header, body, footer, messages) {
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
                        var element = getMessageElement(body, data.before.id);
                        return $(html).insertBefore(element);
                    } else {
                        var dayContainer = getDayElement(body, data.day.timestamp);
                        var dayMessagesContainer = dayContainer.find(SELECTORS.DAY_MESSAGES_CONTAINER);
                        return dayMessagesContainer.append(html);
                    }
                });
            });
        });
    };

    /**
     * Remove days from conversation.
     * 
     * @param  {Object} body The body container element.
     * @param  {Array} days Array of days to be removed.
     */
    var renderRemoveDays = function(body, days) {
        days.forEach(function(data) {
            getDayElement(body, data.timestamp).remove();
        });
    };

    /**
     * Remove messages from conversation.
     * 
     * @param  {Object} body The body container element.
     * @param  {Array} messages Array of messages to be removed.
     */
    var renderRemoveMessages = function(body, messages) {
        messages.forEach(function(data) {
            getMessageElement(body, data.id).remove();
        });
    };

    /**
     * Render the full conversation base on input from the statemanager.
     *
     * @param  {Object} header The header container element.
     * @param  {Object} body The body container element.
     * @param  {Object} footer The footer container element.
     * @param  {Promise} Rendering promises.
     */
    var renderConversation = function(header, body, footer, data) {
        var renderingPromises = [];

        if (data.days.add.length > 0) {
            renderingPromises = renderingPromises.concat(renderAddDays(header, body, footer, data.days.add));
        }

        if (data.messages.add.length > 0) {
            renderingPromises = renderingPromises.concat(renderAddMessages(header, body, footer, data.messages.add));
        }

        if (data.days.remove.length > 0) {
            renderRemoveDays(body, data.days.remove);
        }

        if (data.messages.remove.length > 0) {
            renderRemoveMessages(body, data.messages.remove);
        }

        return $.when.apply($, renderingPromises);
    };

    /**
     * Render the conversation header.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Object} data Data for header.
     */
    var renderHeader = function(header, body, footer, data) {
        var headerContainer = getHeaderContent(header);
        return Templates.render(TEMPLATES.HEADER, data.context)
            .then(function(html, js) {
                Templates.replaceNodeContents(headerContainer, html, js);
            });
    };

    /**
     * Render the conversation footer.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Object} data Data for footer.
     */
    var renderFooter = function(header, body, footer, data) {
        hideAllFooterElements(footer);

        switch (data.type) {
            case 'placeholder':
                return showFooterPlaceholder(footer);
            case 'add-contact':
                return Str.get_strings([
                        {
                            key: 'requirecontacttomessage',
                            component: 'core_message',
                            param: data.user.fullname
                        },
                        {
                            key: 'isnotinyourcontacts',
                            component: 'core_message',
                            param: data.user.fullname
                        }
                    ])
                    .then(function(strings) {
                        var title = strings[1];
                        var text = strings[0];
                        var footerContainer = getFooterRequireContactContainer(footer);
                        footerContainer.find(SELECTORS.TITLE).text(title);
                        footerContainer.find(SELECTORS.TEXT).text(text);
                        showFooterRequireContact(footer);
                    });
            case 'edit-mode':
                return showFooterEditMode(footer);
            case 'content':
                return showFooterContent(footer);
            case 'unblock':
                return showFooterRequireUnblock(footer);
            case 'unable-to-message':
                return showFooterUnableToMessage(footer);
        }

        return true;
    };

    /**
     * Scroll to a message in the conversation.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Number} messageId Message id.
     */
    var renderScrollToMessage = function(header, body, footer, messageId) {
        var messagesContainer = getMessagesContainer(body);
        var messageElement = getMessageElement(body, messageId);
        // Scroll the message container down to the top of the message element.
        var scrollTop = messagesContainer.scrollTop() + messageElement.position().top;
        messagesContainer.scrollTop(scrollTop);
    };

    /**
     * Hide or show the conversation header.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Bool} isLoadingMembers Members loading.
     */
    var renderLoadingMembers = function(header, body, footer, isLoadingMembers) {
        if (isLoadingMembers) {
            hideHeaderContent(header);
            showHeaderPlaceholder(header);
        } else {
            showHeaderContent(header);
            hideHeaderPlaceholder(header);
        }
    };

    /**
     * Hide or show loading conversation messages.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Bool} isLoadingFirstMessages Messages loading.
     */
    var renderLoadingFirstMessages = function(header, body, footer, isLoadingFirstMessages) {
        if (isLoadingFirstMessages) {
            hideMessagesContainer(body);
            showContentPlaceholder(body);
        } else {
            showMessagesContainer(body);
            hideContentPlaceholder(body);
        }
    };

    /**
     * Hide or show loading more messages.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Bool} isLoadingFirstMessages Messages loading.
     */
    var renderLoadingMessages = function(header, body, footer, isLoading) {
        if (isLoading) {
            showMoreMessagesLoadingIcon(body);
        } else {
            hideMoreMessagesLoadingIcon(body);
        }
    };

    /**
     * activate or deactivate send message controls.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Bool} isSending Message sending.
     */
    var renderSendingMessage = function(header, body, footer, isSending) {
        if (isSending) {
            startSendMessageLoading(footer);
        } else {
            stopSendMessageLoading(footer);
            hasSentMessage(footer);
        }
    };

    /**
     * Show a confirmation dialogue 
     * 
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {String} buttonSelector The confirm button.
     * @param {String} bodyText Text to show in dialogue.
     * @param {String} headerText Text to show in dialogue header.
     * @param {Bool} canCancel Can this dialogue be cancelled.
     */
    var showConfirmDialogue = function(
        body,
        footer,
        buttonSelector,
        bodyText,
        headerText,
        canCancel
    ) {
        var dialogue = getConfirmDialogueContainer(body);
        var button = dialogue.find(buttonSelector);
        var cancelButton = dialogue.find(SELECTORS.CONFIRM_DIALOGUE_CANCEL_BUTTON);
        var text = dialogue.find(SELECTORS.CONFIRM_DIALOGUE_TEXT);
        var header = dialogue.find(SELECTORS.CONFIRM_DIALOGUE_HEADER);

        dialogue.find('button').addClass('hidden');

        if (canCancel) {
            cancelButton.removeClass('hidden');
        } else {
            cancelButton.addClass('hidden');
        }

        if (headerText) {
            header.removeClass('hidden');
            header.text(headerText);
        } else {
            header.addClass('hidden');
            header.text('');
        }

        button.removeClass('hidden');
        text.text(bodyText);
        showConfirmDialogueContainer(footer);
        showConfirmDialogueContainer(body);
    };

    /**
     * Hide the dialogue
     * 
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Bool} always true.
     */
    var hideConfirmDialogue = function(body, footer) {
        var dialogue = getConfirmDialogueContainer(body);
        var cancelButton = dialogue.find(SELECTORS.CONFIRM_DIALOGUE_CANCEL_BUTTON);
        var text = dialogue.find(SELECTORS.CONFIRM_DIALOGUE_TEXT);
        var header = dialogue.find(SELECTORS.CONFIRM_DIALOGUE_HEADER);

        hideConfirmDialogueContainer(body);
        hideConfirmDialogueContainer(footer);
        dialogue.find('button').addClass('hidden');
        cancelButton.removeClass('hidden');
        text.text('');
        header.addClass('hidden');
        header.text('');
        return true;
    };

    /**
     * Render the confirm block user dialogue.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Object} user User to block.
     */
    var renderConfirmBlockUser = function(header, body, footer, user) {
        if (user) {
            return Str.get_string('blockuserconfirm', 'core_message', user.fullname)
                .then(function(string) {
                    return showConfirmDialogue(body, footer, SELECTORS.ACTION_CONFIRM_BLOCK, string, '', true);
                });
        } else {
            return hideConfirmDialogue(body, footer);
        }
    };

    /**
     * Render the confirm unblock user dialogue.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Object} user User to unblock.
     */
    var renderConfirmUnblockUser = function(header, body, footer, user) {
        if (user) {
            return Str.get_string('unblockuserconfirm', 'core_message', user.fullname)
                .then(function(string) {
                    showConfirmDialogue(body, footer, SELECTORS.ACTION_CONFIRM_UNBLOCK, string, '', true);
                });
        } else {
            return hideConfirmDialogue(body, footer);
        }
    };

    /**
     * Render the add user as contact dialogue.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Object} user User to add as contact.
     */
    var renderConfirmAddContact = function(header, body, footer, user) {
        if (user) {
            return Str.get_string('addcontactconfirm', 'core_message', user.fullname)
                .then(function(string) {
                    return showConfirmDialogue(body, footer, SELECTORS.ACTION_CONFIRM_ADD_CONTACT, string, '', true);
                });
        } else {
            return hideConfirmDialogue(body, footer);
        }
    };

    /**
     * Render the remove user from contacts dialogue.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Object} user User to remove from contacts.
     */
    var renderConfirmRemoveContact = function(header, body, footer, user) {
        if (user) {
            return Str.get_string('removecontactconfirm', 'core_message', user.fullname)
                .then(function(string) {
                    return showConfirmDialogue(body, footer, SELECTORS.ACTION_CONFIRM_REMOVE_CONTACT, string, '', true);
                });
        } else {
            return hideConfirmDialogue(body, footer);
        }
    };

    /**
     * Render the delete selected messages dialogue.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Bool} show.
     */
    var renderConfirmDeleteSelectedMessages = function(header, body, footer, show) {
        if (show) {
            return Str.get_string('deleteselectedmessagesconfirm', 'core_message')
                .then(function(string) {
                    return showConfirmDialogue(body, footer, SELECTORS.ACTION_CONFIRM_DELETE_SELECTED_MESSAGES, string, '', true);
                });
        } else {
            return hideConfirmDialogue(body, footer);
        }
    };

    /**
     * Render the confirm delete conversation dialogue.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Bool} show.
     */
    var renderConfirmDeleteConversation = function(header, body, footer, show) {
        if (show) {
            return Str.get_string('deleteallconfirm', 'core_message')
                .then(function(string) {
                    return showConfirmDialogue(body, footer, SELECTORS.ACTION_CONFIRM_DELETE_CONVERSATION, string, '', true);
                });
        } else {
            return hideConfirmDialogue(body, footer);
        }
    };

    /**
     * Show or hide the block / unblock option in the header dropdown menu.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Bool} isBlocked is user blocked.
     */
    var renderIsBlocked = function(header, body, footer, isBlocked) {
        if (isBlocked) {
            header.find(SELECTORS.ACTION_REQUEST_BLOCK).addClass('hidden');
            header.find(SELECTORS.ACTION_REQUEST_UNBLOCK).removeClass('hidden');
        } else {
            header.find(SELECTORS.ACTION_REQUEST_BLOCK).removeClass('hidden');
            header.find(SELECTORS.ACTION_REQUEST_UNBLOCK).addClass('hidden');
        }
    };

    /**
     * Show or hide the add / remove user as contact option in the header dropdown menu.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Bool} isContact is user contact.
     */
    var renderIsContact = function(header, body, footer, isContact) {
        if (isContact) {
            header.find(SELECTORS.ACTION_REQUEST_ADD_CONTACT).addClass('hidden');
            header.find(SELECTORS.ACTION_REQUEST_REMOVE_CONTACT).removeClass('hidden');
        } else {
            header.find(SELECTORS.ACTION_REQUEST_ADD_CONTACT).removeClass('hidden');
            header.find(SELECTORS.ACTION_REQUEST_REMOVE_CONTACT).addClass('hidden');
        }
    };

    /**
     * Show or hide confirm action from confirm dialogue is loading.
     * 
     * @param {Object} header The header container element.
     * @param {Object} body The body container element.
     * @param {Object} footer The footer container element.
     * @param {Bool} isLoading confirm action is loading.
     */
    var renderLoadingConfirmAction = function(header, body, footer, isLoading) {
        var dialogue = getConfirmDialogueContainer(body);
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

    var renderInEditMode = function(header, body, footer, inEditMode) {
        if (inEditMode) {
            var messages = body.find(SELECTORS.MESSAGE_NOT_SELECTED);
            messages.find(SELECTORS.MESSAGE_NOT_SELECTED_ICON).removeClass('hidden');
            hideHeaderContent(header);
            showHeaderEditMode(header);
        } else {
            var messages = getMessagesContainer(body);
            messages.find(SELECTORS.MESSAGE_NOT_SELECTED_ICON).addClass('hidden');
            messages.find(SELECTORS.MESSAGE_SELECTED_ICON).addClass('hidden');
            showHeaderContent(header);
            hideHeaderEditMode(header);
        }
    };

    var renderSelectedMessages = function(header, body, footer, data) {
        var hasSelectedMessages = data.count > 0;

        if (data.add.length) {
            data.add.forEach(function(messageId) {
                var message = getMessageElement(body, messageId);
                message.find(SELECTORS.MESSAGE_NOT_SELECTED_ICON).addClass('hidden');
                message.find(SELECTORS.MESSAGE_SELECTED_ICON).removeClass('hidden');
                message.attr('aria-checked', true);
            });
        }

        if (data.remove.length) {
            data.remove.forEach(function(messageId) {
                var message = getMessageElement(body, messageId);

                if (hasSelectedMessages) {
                    message.find(SELECTORS.MESSAGE_NOT_SELECTED_ICON).removeClass('hidden');
                }

                message.find(SELECTORS.MESSAGE_SELECTED_ICON).addClass('hidden');
                message.attr('aria-checked', false);
            });
        }

        setMessagesSelectedCount(header, data.count);
    };

    var renderRequireAddContact = function(header, body, footer, data) {
        if (data.show && !data.hasMessages) {
            return Str.get_strings([
                    {
                        key: 'requirecontacttomessage',
                        component: 'core_message',
                        param: data.user.fullname
                    },
                    {
                        key: 'isnotinyourcontacts',
                        component: 'core_message',
                        param: data.user.fullname
                    }
                ])
                .then(function(strings) {
                    var title = strings[1];
                    var text = strings[0];
                    showConfirmDialogue(
                        body,
                        footer,
                        SELECTORS.ACTION_REQUEST_ADD_CONTACT,
                        text,
                        title,
                        false
                    );
                });
        } else {
            return hideConfirmDialogue(body, footer);
        }
    };

    var render = function(header, body, footer, patch) {
        var configs = [
            {
                // Any async rendering (stuff that requires templates, strings etc) should
                // go in here.
                conversation: renderConversation,
                header: renderHeader,
                footer: renderFooter,
                confirmBlockUser: renderConfirmBlockUser,
                confirmUnblockUser: renderConfirmUnblockUser,
                confirmAddContact: renderConfirmAddContact,
                confirmRemoveContact: renderConfirmRemoveContact,
                confirmDeleteSelectedMessages: renderConfirmDeleteSelectedMessages,
                confirmDeleteConversation: renderConfirmDeleteConversation,
                requireAddContact: renderRequireAddContact
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
                    results.push(renderFunc(header, body, footer, patchValue));
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
