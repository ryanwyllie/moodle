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
    'core/notification',
    'core/templates',
    'core_message/message_repository',
    'core/log'
],
function(
    $,
    Notification,
    Templates,
    Repository,
    Log
) {

    var SELECTORS = {
        HEADER: '[data-region="view-conversation-header"]',
        MESSAGES: '[data-region="view-conversation-messages"]',
        PLACEHOLDER: '[data-region="placeholder"]'
    };

    var TEMPLATES = {
        HEADER: 'message_popup/message_drawer_view_conversation_header',
        MESSAGES: 'message_popup/message_drawer_view_conversation_messages'
    };

    // HOW DO I REUSE THIS STUFF FROM OTHER MODULES?
    var getLoggedInUserId = function(root) {
        return root.attr('data-user-id');
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
        messagesContainer = root.find(SELECTORS.MESSAGES);
        return Templates.render(TEMPLATES.MESSAGES, {messages: messages})
            .then(function(html, js) {
                Templates.replaceNodeContents(messagesContainer, html, js);
                messagesContainer.animate({
                    scrollTop: $(document).height() 
                }, "fast");
            })
            .catch(Notification.exception);
    };



    var show = function(root, otherUserId) {
        root = $(root);
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
