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
    'core_message/message_repository',
    'core/log'
],
function(
    $,
    AutoRows,
    CustomEvents,
    Notification,
    Templates,
    Repository,
    Log
) {

    var SELECTORS = {
        AUTO_ROWS: '[data-region=""]',
        HEADER: '[data-region="view-conversation-header"]',
        MESSAGES: '[data-region="view-conversation-messages"]',
        PLACEHOLDER: '[data-region="placeholder"]',
        MESSAGE_TEXT_AREA: '[data-region="send-message-txt"]',
        SEND_MESSAGE_BUTTON: '[data-action="send-message"]',
        SEND_MESSAGE_ICON_CONTAINER: '[data-region="send-icon-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]'
    };

    var TEMPLATES = {
        HEADER: 'message_popup/message_drawer_view_conversation_header',
        MESSAGES: 'message_popup/message_drawer_view_conversation_messages'
    };

    // HOW DO I REUSE THIS STUFF FROM OTHER MODULES?
    var getLoggedInUserId = function(root) {
        return root.attr('data-user-id');
    };

    var getMessageTextArea = function(root) {
        return root.find(SELECTORS.MESSAGE_TEXT_AREA);
    };

    var startSendMessageLoading = function(root) {
        root.find(SELECTORS.SEND_MESSAGE_BUTTON).prop('disabled', true);
        root.find(SELECTORS.MESSAGE_TEXT_AREA).prop('disabled', true);
        root.find(SELECTORS.SEND_MESSAGE_ICON_CONTAINER).addClass('hidden');
        root.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var stopSendMessageLoading = function(root) {
        root.find(SELECTORS.SEND_MESSAGE_BUTTON).prop('disabled', false);
        root.find(SELECTORS.MESSAGE_TEXT_AREA).prop('disabled', false);
        root.find(SELECTORS.SEND_MESSAGE_ICON_CONTAINER).removeClass('hidden');
        root.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    // Header stuff.
    var loadHeader = function(root, otherUserId) {
        var currentUserId =  getLoggedInUserId(root);
        return Repository.getProfile(currentUserId, otherUserId);
    };

    var renderHeader = function(root, profile) {
        headerContainer = root.find(SELECTORS.HEADER)
        return Templates.render(TEMPLATES.HEADER, profile)
            .then(function(html, js) {
                Templates.replaceNodeContents(headerContainer, html, js);
            });
    };

    // Message loading.
    var loadMessages = function(root, currentUserId, otherUserId) {
        return Repository.getMessages(currentUserId, otherUserId)
            .then(function(result) {
                return result.messages;
            })
            .catch(Notification.exception);
    };

    var renderMessages = function(root, messages) {
        var messagesContainer = root.find(SELECTORS.MESSAGES);
        return Templates.render(TEMPLATES.MESSAGES, {messages: messages})
            .then(function(html, js) {
                Templates.replaceNodeContents(messagesContainer, html, js);
                messagesContainer.animate({
                    scrollTop: $(document).height()
                }, "fast");
            })
            .catch(Notification.exception);
    };

    var sendMessage = function(toUserId, text) {
        return Repository.sendMessage(toUserId, text);
    };

    var renderSentMessage = function(root, text) {
        var context = {
            messages: [{
                displayblocktime: false,
                fullname: root.attr('data-full-name'),
                profileimageurl: root.attr('data-profile-url'),
                text: text
            }]
        };

        return Templates.render(TEMPLATES.MESSAGES, context)
            .then(function(html) {
                var messagesContainer = root.find(SELECTORS.MESSAGES);
                messagesContainer.append(html);
            });
    };

    var registerEventListeners = function(root, otherUserId) {
        AutoRows.init(root);

        CustomEvents.define(root, [CustomEvents.events.activate]);
        root.on(CustomEvents.events.activate, SELECTORS.SEND_MESSAGE_BUTTON, function(e, data) {
            var textArea = getMessageTextArea(root);
            var text = textArea.val().trim();

            if (text !== '') {
                startSendMessageLoading(root);
                sendMessage(otherUserId, text)
                    .then(function() {
                        return renderSentMessage(root, text);
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
                        startSendMessageLoading(root);
                    });
            }

            data.originalEvent.preventDefault();
        });
    };

    var show = function(root, otherUserId) {
        root = $(root);
        if (!root.attr('data-init')) {
            registerEventListeners(root, otherUserId);
            root.attr('data-init', true);
        }

        var currentUserId = getLoggedInUserId(root);

        // THIS IS DUPLICATING THINGS AGAIN FROM VIEW CONTACT.JS
        loadHeader(root, otherUserId).then(function(profile) {
            // NEEDS LOADING ICON
            renderHeader(root, profile);
        })
        .catch(function(error) {
            Notification.exception(error);
        });

        loadMessages(root, currentUserId, otherUserId).then(function(messages) {
            renderMessages(root, messages);
        })
        .catch(function(error) {
            Notification.exception(error);
        });
    };

    return {
        show: show,
    };
});
