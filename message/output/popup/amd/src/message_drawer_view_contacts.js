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
    'core/pubsub',
    'core/templates',
    'core/custom_interaction_events',
    'core_message/message_repository',
    'message_popup/message_drawer_events'
],
function(
    $,
    Notification,
    PubSub,
    Templates,
    CustomEvents,
    MessageRepository,
    Events
) {

    var LOAD_CONTACTS_LIMIT = 100;

    var numContacts = 0;
    var contactsOffset = 0;
    var loadedAllContacts = false;
    var numLoads = 0;
    var waitForScrollLoad = false;

    var SELECTORS = {
        BLOCK_ICON_CONTAINER: '[data-region="block-icon-container"]',
        CONTACTS: '[data-region="contacts-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        CONTENT_CONTAINER: '[data-region="contacts-content-container"]',
        EMPTY_MESSAGE: '[data-region="empty-message-container"]',
        PLACEHOLDER: '[data-region="placeholder-container"]'
    };

    var TEMPLATES = {
        CONTACTS_LIST: 'message_popup/message_drawer_contacts_list'
    };

    var startLoading = function(body) {
        body.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var stopLoading = function(body) {
        body.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var getContentContainer = function(body) {
        return body.find(SELECTORS.CONTENT_CONTAINER);
    };

    var getcontactsContainer = function(body) {
        return body.find(SELECTORS.CONTACTS);
    };

    var showEmptyMessage = function(body) {
        getContentContainer(body).addClass('hidden');
        body.find(SELECTORS.EMPTY_MESSAGE).removeClass('hidden');
    };

    var showPlaceholder = function(body) {
        body.find(SELECTORS.PLACEHOLDER).removeClass('hidden');
    };

    var hidePlaceholder = function(body) {
        body.find(SELECTORS.PLACEHOLDER).addClass('hidden');
    };

    var showContent = function(body) {
        getContentContainer(body).removeClass('hidden');
    };

    var findContact = function(body, userId) {
        return body.find('[data-contact-user-id="' + userId + '"]');
    };

    var getUserId = function(body) {
        return body.attr('data-user-id');
    };

    var render = function(body, contacts) {
        var contentContainer = getContentContainer(body);
        return Templates.render(TEMPLATES.CONTACTS_LIST, {contacts: contacts})
            .then(function(html) {
                hidePlaceholder(body);
                contentContainer.append(html);
                showContent(body);
            })
            .catch(Notification.exception);
    };

    var loadContacts = function(body) {
        var userId = getUserId(body);
        return MessageRepository.getContacts(userId, (LOAD_CONTACTS_LIMIT + 1), contactsOffset)
            .then(function(result) {
                return result.contacts;
            })
            .then(function(contacts) {
                if (contacts.length > LOAD_CONTACTS_LIMIT) {
                    contacts.pop();
                } else {
                    loadedAllContacts = true;
                }
                return contacts;
            })
            .then(function(contacts) {
                if (contactsOffset == 0 && contacts.length == 0) {
                    hidePlaceholder(body);
                    showEmptyMessage(body);
                }

                numContacts = numContacts + contacts.length;

                contactsOffset = contactsOffset + LOAD_CONTACTS_LIMIT;
                if (contacts.length > 0) {
                    return render(body, contacts);
                } 
            })
            .catch(Notification.exception);
    }

    var removeContact = function(body, userId) {
        findContact(body, userId).remove();
    };

    var blockContact = function(body, userId) {
        var contact = findContact(body, userId);
        if (contact.length) {
            contact.find(SELECTORS.BLOCK_ICON_CONTAINER).removeClass('hidden');
        }
    };

    var unblockContact = function(body, userId) {
        var contact = findContact(body, userId);
        if (contact.length) {
            contact.find(SELECTORS.BLOCK_ICON_CONTAINER).addClass('hidden');
        }
    };

    var registerEventListeners = function(body) {
        // FIX THIS ONE
        PubSub.subscribe(Events.CONTACT_ADDED, function(contact) {
            contactsOffset = 0;
            loadedAllContacts = false;
            getContentContainer(body).empty();
            loadContacts(body);
        });

        PubSub.subscribe(Events.CONTACT_REMOVED, function(userId) {
            removeContact(body, userId);
        });

        PubSub.subscribe(Events.CONTACT_BLOCKED, function(userId) {
            blockContact(body, userId);
        });

        PubSub.subscribe(Events.CONTACT_UNBLOCKED, function(userId) {
            unblockContact(body, userId);
        });

        var contactsContainer = getcontactsContainer(body);

        // What is scrollLock for?
        CustomEvents.define(contactsContainer, [
            CustomEvents.events.scrollBottom,
            CustomEvents.events.scrollLock
        ]);

        contactsContainer.on(CustomEvents.events.scrollBottom, function(e, data) {
            hasContacts = numContacts > 1;
            if (!loadedAllContacts && hasContacts && !waitForScrollLoad) {
                waitForScrollLoad = true;
                startLoading(body);
                loadContacts(body).then(function() {
                    stopLoading(body);
                    return waitForScrollLoad = false;
                });
            }
            data.originalEvent.preventDefault();
        });
    };

    var show = function(header, body) {
        body = $(body);
        contactsOffset = 0;

        if (!body.attr('data-contacts-init')) {
            registerEventListeners(body);
            body.attr('data-contacts-init', true);
        }

        if (!loadedAllContacts) {
            loadContacts(body);
        }
    };

    return {
        show: show,
    };
});
