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
        HEADER: 'message_popup/message_drawer_view_conversation_header_content',
        DAY: 'message_popup/message_drawer_view_conversation_body_day',
        MESSAGE: 'message_popup/message_drawer_view_conversation_body_message',
        MESSAGES: 'message_popup/message_drawer_view_conversation_body_messages'
    };

    var getMessagesContainer = function(root) {
        return root.find(SELECTORS.CONTENT_MESSAGES_CONTAINER);
    };

    var showMessagesContainer = function(root) {
        getMessagesContainer(root).removeClass('hidden');
    };

    var hideMessagesContainer = function(root) {
        getMessagesContainer(root).addClass('hidden');
    };

    var getFooterContentContainer = function(root) {
        return root.find(SELECTORS.CONTENT_MESSAGES_FOOTER_CONTAINER);
    };

    var showFooterContent = function(root) {
        getFooterContentContainer(root).removeClass('hidden');
    };

    var hideFooterContent = function(root) {
        getFooterContentContainer(root).addClass('hidden');
    };

    var getFooterEditModeContainer = function(root) {
        return root.find(SELECTORS.CONTENT_MESSAGES_FOOTER_EDIT_MODE_CONTAINER);
    };

    var showFooterEditMode = function(root) {
        getFooterEditModeContainer(root).removeClass('hidden');
    };

    var hideFooterEditMode = function(root) {
        getFooterEditModeContainer(root).addClass('hidden');
    };

    var getFooterPlaceholderContainer = function(footer) {
        return footer.find(SELECTORS.PLACEHOLDER_CONTAINER);
    };

    var showFooterPlaceholder = function(footer) {
        getFooterPlaceholderContainer(footer).removeClass('hidden');
    };

    var hideFooterPlaceholder = function(footer) {
        getFooterPlaceholderContainer(footer).addClass('hidden');
    };

    var getFooterRequireContactContainer = function(footer) {
        return footer.find(SELECTORS.CONTENT_MESSAGES_FOOTER_REQUIRE_CONTACT_CONTAINER);
    };

    var showFooterRequireContact = function(footer) {
        getFooterRequireContactContainer(footer).removeClass('hidden');
    };

    var hideFooterRequireContact = function(footer) {
        getFooterRequireContactContainer(footer).addClass('hidden');
    };

    var getFooterRequireUnblockContainer = function(footer) {
        return footer.find(SELECTORS.CONTENT_MESSAGES_FOOTER_REQUIRE_UNBLOCK_CONTAINER);
    };

    var showFooterRequireUnblock = function(footer) {
        getFooterRequireUnblockContainer(footer).removeClass('hidden');
    };

    var hideFooterRequireUnblock = function(footer) {
        getFooterRequireUnblockContainer(footer).addClass('hidden');
    };

    var hideAllFooterElements = function(footer) {
        hideFooterContent(footer);
        hideFooterEditMode(footer);
        hideFooterPlaceholder(footer);
        hideFooterRequireContact(footer);
        hideFooterRequireUnblock(footer);
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
        root.find(SELECTORS.SEND_MESSAGE_ICON_CONTAINER).addClass('hidden');
        root.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var stopSendMessageLoading = function(root) {
        enableSendMessage(root);
        root.find(SELECTORS.SEND_MESSAGE_ICON_CONTAINER).removeClass('hidden');
        root.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var hasSentMessage = function(root) {
        var textArea = getMessageTextArea(root);
        textArea.val('');
        textArea.focus();
    };

    var getConfirmDialogueContainer = function(root) {
        return root.find(SELECTORS.CONFIRM_DIALOGUE_CONTAINER);
    };

    var showConfirmDialogueContainer = function(root) {
        getConfirmDialogueContainer(root).removeClass('hidden');
    };

    var hideConfirmDialogueContainer = function(root) {
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
                userfrom: message.userFrom,
                text: message.text,
                timecreated: parseInt(message.timeCreated, 10)
            };
        });
    };

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

    var renderRemoveDays = function(header, body, footer, days) {
        days.forEach(function(data) {
            getDayElement(body, data.timestamp).remove();
        });
    };

    var renderRemoveMessages = function(header, body, footer, messages) {
        messages.forEach(function(data) {
            getMessageElement(body, data.id).remove();
        });
    };

    var renderConversation = function(header, body, footer, data) {
        var renderingPromises = [];

        if (data.days.add.length > 0) {
            renderingPromises = renderingPromises.concat(renderAddDays(header, body, footer, data.days.add));
        }

        if (data.messages.add.length > 0) {
            renderingPromises = renderingPromises.concat(renderAddMessages(header, body, footer, data.messages.add));
        }

        if (data.days.remove.length > 0) {
            renderRemoveDays(header, body, footer, data.days.remove);
        }

        if (data.messages.remove.length > 0) {
            renderRemoveMessages(header, body, footer, data.messages.remove);
        }

        return $.when.apply($, renderingPromises);
    };

    var renderHeader = function(header, body, footer, data) {
        var headerContainer = getHeaderContent(header);
        return Templates.render(TEMPLATES.HEADER, data.context)
            .then(function(html, js) {
                Templates.replaceNodeContents(headerContainer, html, js);
            });
    };

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
        }

        return true;
    };

    var renderScrollToMessage = function(header, body, footer, messageId) {
        var messagesContainer = getMessagesContainer(body);
        var messageElement = getMessageElement(body, messageId);
        // Scroll the message container down to the top of the message element.
        var scrollTop = messagesContainer.scrollTop() + messageElement.position().top;
        messagesContainer.scrollTop(scrollTop);
    };

    var renderLoadingMembers = function(header, body, footer, isLoadingMembers) {
        if (isLoadingMembers) {
            hideHeaderContent(header);
            showHeaderPlaceholder(header);
        } else {
            showHeaderContent(header);
            hideHeaderPlaceholder(header);
        }
    };

    var renderLoadingFirstMessages = function(header, body, footer, isLoadingFirstMessages) {
        if (isLoadingFirstMessages) {
            hideMessagesContainer(body);
            showContentPlaceholder(body);
        } else {
            showMessagesContainer(body);
            hideContentPlaceholder(body);
        }
    };

    var renderLoadingMessages = function(header, body, footer, isLoading) {
        if (isLoading) {
            showMoreMessagesLoadingIcon(body);
        } else {
            hideMoreMessagesLoadingIcon(body);
        }
    };

    var renderSendingMessage = function(header, body, footer, isSending) {
        if (isSending) {
            startSendMessageLoading(footer);
        } else {
            stopSendMessageLoading(footer);
            hasSentMessage(footer);
        }
    };

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

    var renderIsBlocked = function(header, body, footer, isBlocked) {
        if (isBlocked) {
            header.find(SELECTORS.ACTION_REQUEST_BLOCK).addClass('hidden');
            header.find(SELECTORS.ACTION_REQUEST_UNBLOCK).removeClass('hidden');
        } else {
            header.find(SELECTORS.ACTION_REQUEST_BLOCK).removeClass('hidden');
            header.find(SELECTORS.ACTION_REQUEST_UNBLOCK).addClass('hidden');
        }
    };

    var renderIsContact = function(header, body, footer, isContact) {
        if (isContact) {
            header.find(SELECTORS.ACTION_REQUEST_ADD_CONTACT).addClass('hidden');
            header.find(SELECTORS.ACTION_REQUEST_REMOVE_CONTACT).removeClass('hidden');
        } else {
            header.find(SELECTORS.ACTION_REQUEST_ADD_CONTACT).removeClass('hidden');
            header.find(SELECTORS.ACTION_REQUEST_REMOVE_CONTACT).addClass('hidden');
        }
    };

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
            var messages = body.find(SELECTORS.MESSAGE);
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
