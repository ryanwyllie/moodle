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
    'core/templates'
],
function(
    $,
    Templates
) {
    var SELECTORS = {
        HEADER: '[data-region="view-conversation-header"]',
        HEADER_PLACEHOLDER_CONTAINER: '[data-region="header-placeholder"]',
        MESSAGES: '[data-region="view-conversation-messages"]',
        CONTENT_PLACEHOLDER_CONTAINER: '[data-region="content-placeholder"]',
        MESSAGE_TEXT_AREA: '[data-region="send-message-txt"]',
        SEND_MESSAGE_BUTTON: '[data-action="send-message"]',
        SEND_MESSAGE_ICON_CONTAINER: '[data-region="send-icon-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        DAY_MESSAGES_CONTAINER: '[data-region="day-messages-container"]',
        CONTENT_CONTAINER: '[data-region="content-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        MORE_MESSAGES_LOADING_ICON_CONTAINER: '[data-region="more-messages-loading-icon-container"]',
        RESPONSE_CONTAINER: '[data-region="response"]'
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
        root.find(SELECTORS.MESSAGE_TEXT_AREA).prop('disabled', true);
    };

    var enableSendMessage = function(root) {
        root.find(SELECTORS.SEND_MESSAGE_BUTTON).prop('disabled', false);
        root.find(SELECTORS.MESSAGE_TEXT_AREA).prop('disabled', false);
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

    var scrollToMessage = function(root, messageId) {
        var messagesContainer = getMessagesContainer(root);
        var messageElement = getMessageElement(root, messageId);
        // Scroll the message container down to the top of the message element.
        var scrollTop = messagesContainer.scrollTop() + messageElement.position().top;
        messagesContainer.scrollTop(scrollTop);
    };

    var renderHeader = function(root, data) {
        var headerContainer = getHeaderContainer(root);
        return Templates.render(TEMPLATES.HEADER, data.context)
            .then(function(html, js) {
                Templates.replaceNodeContents(headerContainer, html, js);
            });
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
            getDayElement(root, data.timecreated).remove();
        });
    };

    var renderRemoveMessages = function(root, messages) {
        messages.forEach(function(data) {
            getMessageElement(root, data.id).remove();
        });
    };

    var render = function(root, patch) {
        var renderingPromises = [];

        if (patch.days.add.length > 0) {
            renderingPromises.concat(renderAddDays(root, patch.days.add));
        }

        if (patch.messages.add.length > 0) {
            renderingPromises.concat(renderAddMessages(root, patch.messages.add));
        }

        if (patch.days.remove.length > 0) {
            renderRemoveDays(root, patch.days.remove);
        }

        if (patch.messages.remove.length > 0) {
            renderRemoveMessages(root, patch.messages.remove);
        }

        if (patch.header) {
            renderingPromises.concat(renderHeader(root, patch.header));
        }

        if (patch.showHeaderPlaceholder) {
            hideHeaderContainer(root);
            showHeaderPlaceholder(root);
        } else {
            showHeaderContainer(root);
            hideHeaderPlaceholder(root);
        }

        if (patch.showContentPlaceholder) {
            hideContentContainer(root);
            showContentPlaceholder(root);
        } else {
            showContentContainer(root);
            hideContentPlaceholder(root);
        }

        if (patch.loadingMessages) {
            showMoreMessagesLoadingIcon(root);
        } else {
            hideMoreMessagesLoadingIcon(root);
        }

        if (patch.sendingMessage) {
            startSendMessageLoading(root);
        } else {
            stopSendMessageLoading(root);
        }

        var renderingPromise = $.when.apply($, renderingPromises);

        if (patch.scrollToMessage) {
            renderingPromise.then(function() {
                scrollToMessage(root, patch.scrollToMessage);
            });
        }

        return renderingPromise;
    };

    return {
        render: render,
    };
});
