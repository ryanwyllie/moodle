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
    'core/custom_interaction_events',
    'core/notification',
    'core/templates',
    'core_message/message_repository',
    'message_popup/message_router',
    'message_popup/message_routes'
],
function(
    $,
    CustomEvents,
    Notification,
    Templates,
    MessageRepository,
    Router,
    Routes
) {

    var SELECTORS = {
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        CONTENT_CONTAINER: '[data-region="view-overview-contacts-content"]',
        CONTACTS_LIST: '[data-region="contacts-list"]'
    };

    var TEMPLATES = {
        CONTACTS_CONTENT: 'message_popup/message_drawer_view_overview_contacts_content',
        CONTACTS_LIST: 'message_popup/message_drawer_view_overview_contacts_list',
        EMPTY_MESSAGE: 'message_popup/message_drawer_view_overview_contacts_empty'
    };

    var startLoading = function(root) {
        root.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var stopLoading = function(root) {
        root.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var getUserId = function(root) {
        return root.attr('data-user-id');
    };

    var getContentContainer = function(root) {
        return root.find(SELECTORS.CONTENT_CONTAINER);
    };

    var renderNoContactsMessage = function(root) {
        Templates.render(TEMPLATES.EMPTY_MESSAGE, {})
            .then(function(html) {
                var contentContainer = getContentContainer(root);
                contentContainer.empty();
                contentContainer.append(html);
            })
            .catch(Notification.exception);
    };

    var renderContacts = function(root, contacts) {
        var contactsList = root.find(SELECTORS.CONTACTS_LIST);
        var context = {contacts: contacts};

        if (contactsList.length > 0) {
            Templates.render(TEMPLATES.CONTACTS_LIST, context)
                .then(function(html) {
                    contactsList.append(html);
                })
                .catch(Notification.exception);
        } else {
            Templates.render(TEMPLATES.CONTACTS_CONTENT, context)
                .then(function(html) {
                    var contentContainer = getContentContainer(root);
                    contentContainer.empty();
                    contentContainer.append(html);
                })
                .catch(Notification.exception);
        }
    };

    var loadMoreContacts = function(root) {
        startLoading(root);
        var userId = getUserId(root);

        return MessageRepository.getContacts(userId)
            .then(function(result) {
                return result.contacts;
            })
            .then(function(contacts) {
                stopLoading(root);
                return contacts;
            })
            .catch(function(error) {
                Notification.exception({message: error});
                stopLoading(root);
            });
    };

    var registerEventListeners = function(root) {
    };

    var show = function(root) {
        if (!root.attr('data-seen')) {
            registerEventListeners(root);
            loadMoreContacts(root)
                .then(function(contacts) {
                    if (contacts.length > 0) {
                        return renderContacts(root, contacts);
                    } else {
                        return renderNoContactsMessage(root);
                    }
                });
            root.attr('data-seen', true);
        }
    };

    return {
        show: show,
    };
});
