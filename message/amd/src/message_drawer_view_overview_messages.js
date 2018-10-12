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
        'core/notification',
        'core/pubsub',
        'core/str',
        'core/templates',
        'core/user_date',
        'core_message/message_repository',
        'core_message/message_drawer_events',
        'core_message/message_drawer_view_overview_section'
    ],
    function(
        $,
        Notification,
        PubSub,
        Str,
        Templates,
        UserDate,
        MessageRepository,
        MessageDrawerEvents,
        Section
    ) {

        var SELECTORS = {
            BLOCKED_ICON_CONTAINER: '[data-region="contact-icon-blocked"]',
            CONTENT_CONTAINER: '[data-region="content-container"]',
            LAST_MESSAGE: '[data-region="last-message"]',
            LAST_MESSAGE_DATE: '[data-region="last-message-date"]',
            UNREAD_COUNT: '[data-region="unread-count"]',
            SECTION_TOTAL_COUNT: '[data-region="section-total-count"]',
            SECTION_UNREAD_COUNT: '[data-region="section-unread-count"]'
        };

        var TEMPLATES = {
            MESSAGES_LIST: 'core_message/message_drawer_messages_list'
        };

        var render = function(contentContainer, contacts) {
            return Templates.render(TEMPLATES.MESSAGES_LIST, {messages: contacts})
                .then(function(html) {
                    contentContainer.append(html);
                })
                .catch(Notification.exception);
        };

        var load = function(root, userId) {
            return MessageRepository.query({userid: userId})
                .then(function(result) {
                    return result.contacts;
                })
                .catch(Notification.exception);
        };

        var getTotalConversationCountElement = function(root) {
            return root.find(SELECTORS.SECTION_TOTAL_COUNT);
        };

        var getTotalUnreadConversationCountElement = function(root) {
            return root.find(SELECTORS.SECTION_UNREAD_COUNT);
        };

        var incrementTotalConversationCount = function(root) {
            var element = getTotalConversationCountElement(root);
            var count = parseInt(element.text());
            count = count + 1;
            element.text(count);
        };

        var decrementTotalConversationCount = function(root) {
            var element = getTotalConversationCountElement(root);
            var count = parseInt(element.text());
            count = count - 1;
            element.text(count);
        };

        var incrementTotalUnreadConversationCount = function(root) {
            var element = getTotalUnreadConversationCountElement(root);
            var count = parseInt(element.text());
            count = count + 1;
            element.text(count);
        };

        var decrementTotalUnreadConversationCount = function(root) {
            var element = getTotalUnreadConversationCountElement(root);
            var count = parseInt(element.text());
            count = count - 1;
            element.text(count);

            if (count < 1) {
                element.addClass('hidden');
            }
        };

        var getConversationElement = function(root, conversationId) {
            return root.find('[data-conversation-id="' + conversationId + '"]');
        };

        var blockContact = function(root, userId) {
            // TODO: This will need to change when we have actual conversations.
            getConversationElement(root, userId).find(SELECTORS.BLOCKED_ICON_CONTAINER).removeClass('hidden');
        };

        var unblockContact = function(root, userId) {
            // TODO: This will need to change when we have actual conversations.
            getConversationElement(root, userId).find(SELECTORS.BLOCKED_ICON_CONTAINER).addClass('hidden');
        };

        var updateLastMessage = function(element, message) {
            var youString = '';
            var stringRequests = [
                {key: 'you', component: 'core_message'},
                {key: 'strftimetime24', component: 'core_langconfig'},
            ];
            return Str.get_strings(stringRequests)
                .then(function(strings) {
                    youString = strings[0];
                    return UserDate.get([{timestamp: message.timeCreated, format: strings[1]}])
                })
                .then(function(dates) {
                    return dates[0];
                })
                .then(function(dateString) {
                    var lastMessage = $(message.text).text();

                    if (message.fromLoggedInUser) {
                        lastMessage = youString + ' ' + lastMessage;
                    }

                    element.find(SELECTORS.LAST_MESSAGE).html(lastMessage);
                    element.find(SELECTORS.LAST_MESSAGE_DATE).text(dateString).removeClass('hidden');
                })
                .catch(Notification.exception);
        };

        var createNewConversation = function(root, message) {
            var formattedMessage = Object.assign({
                fullname: message.conversation.title,
                lastmessagedate: message.timeCreated,
                sentfromcurrentuser: message.fromLoggedInUser,
                lastmessage: $(message.text).text(),
                profileimageurl: message.conversation.imageurl,
                userid: message.conversation.id
            }, message);
            return Templates.render(TEMPLATES.MESSAGES_LIST, {messages: [formattedMessage]})
                .then(function(html) {
                    var contentContainer = root.find(SELECTORS.CONTENT_CONTAINER);
                    return contentContainer.prepend(html);
                })
                .then(function() {
                    return incrementTotalConversationCount(root);
                })
                .catch(Notification.exception);
        };

        var deleteConversation = function(root, conversationId) {
            getConversationElement(root, conversationId).remove();
            decrementTotalConversationCount(root);
        };

        var markConversationAsRead = function(root, conversationId) {
            var unreadCount = getConversationElement(root, conversationId).find(SELECTORS.UNREAD_COUNT);
            unreadCount.text('0');
            unreadCount.addClass('hidden');
            decrementTotalUnreadConversationCount(root);
        };

        var registerEventListeners = function(root) {
            PubSub.subscribe(MessageDrawerEvents.CONTACT_BLOCKED, function(userId) {
                blockContact(root, userId);
            });

            PubSub.subscribe(MessageDrawerEvents.CONTACT_UNBLOCKED, function(userId) {
                unblockContact(root, userId);
            });

            PubSub.subscribe(MessageDrawerEvents.CONVERSATION_NEW_LAST_MESSAGE, function(message) {
                var conversationId = message.conversation.id;
                var element = getConversationElement(root, conversationId);
                if (element.length) {
                    updateLastMessage(element, message);
                } else {
                    createNewConversation(root, message);
                }
            });

            PubSub.subscribe(MessageDrawerEvents.CONVERSATION_DELETED, function(conversationId) {
                deleteConversation(root, conversationId);
            });

            PubSub.subscribe(MessageDrawerEvents.CONVERSATION_READ, function(conversationId) {
                markConversationAsRead(root, conversationId);
            });
        };

        var show = function(root) {
            root = $(root);

            if (!root.attr('data-messages-init')) {
                registerEventListeners(root);
                root.attr('data-messages-init', true);
            }

            Section.show(root, load, render);
        };

        return {
            show: show,
        };
    });
