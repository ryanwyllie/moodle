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
    'core/custom_interaction_events',
    'core_message/message_repository',
    'message_popup/message_drawer_events',
    'message_popup/message_drawer_view_contacts_renderer'
],
function(
    $,
    Notification,
    PubSub,
    CustomEvents,
    MessageRepository,
    Events,
    Renderer
) {

    var LOAD_CONTACTS_LIMIT = 100;

    var viewState = {
        loadedAllContacts: false,
        contacts: [],
        contactsOffset: 0
    }

    var SELECTORS = {
        BLOCK_ICON_CONTAINER: '[data-region="block-icon-container"]',
        CONTACTS: '[data-region="view-contacts-container"]'
    };

    var render = function(root, viewState) {
        return Renderer.render(root, viewState);
    };

    var loadContacts = function(root, userId) {
        return MessageRepository.getContacts(userId, LOAD_CONTACTS_LIMIT + 1, viewState.contactsOffset)
            .then(function(result) {
                return result.contacts;
            })
            .then(function(contacts) {
                if (contacts.length > LOAD_CONTACTS_LIMIT) {
                    contacts = contacts.slice(1);
                } else {
                    viewState.loadedAllContacts = true;
                }
                return contacts;
            })
            .then(function(contacts) {
                viewState.contacts = contacts;
                viewState.contactsOffset = viewState.contactsOffset + LOAD_CONTACTS_LIMIT;
                return render(root, viewState);
            })
            .catch(Notification.exception);
    }

    var findContact = function(root, userId) {
        return root.find('[data-contact-user-id="' + userId + '"]');
    };

    var getUserId = function(root) {
        return root.attr('data-user-id');
    };

    var getcontactsContainer = function(root) {
        return root.find(SELECTORS.CONTACTS);
    };

    var removeContact = function(root, userId) {
        findContact(root, userId).remove();
    };

    var blockContact = function(root, userId) {
        var contact = findContact(root, userId);
        if (contact.length) {
            contact.find(SELECTORS.BLOCK_ICON_CONTAINER).removeClass('hidden');
        }
    };

    var unblockContact = function(root, userId) {
        var contact = findContact(root, userId);
        if (contact.length) {
            contact.find(SELECTORS.BLOCK_ICON_CONTAINER).addClass('hidden');
        }
    };

    var registerEventListeners = function(root) {
        // FIX THIS ONE
        PubSub.subscribe(Events.CONTACT_ADDED, function(contact) {
            loadContacts(root, getUserId(root), LOAD_CONTACTS_LIMIT, viewState.contactsOffset);
        });

        PubSub.subscribe(Events.CONTACT_REMOVED, function(userId) {
            removeContact(root, userId);
        });

        PubSub.subscribe(Events.CONTACT_BLOCKED, function(userId) {
            blockContact(root, userId);
        });

        PubSub.subscribe(Events.CONTACT_UNBLOCKED, function(userId) {
            unblockContact(root, userId);
        });


        var contactsContainer = getcontactsContainer(root);

        // What is scrollLock for?
        CustomEvents.define(contactsContainer, [
            CustomEvents.events.scrollBottom,
            CustomEvents.events.scrollLock
        ]);

        contactsContainer.on(CustomEvents.events.scrollBottom, function(e, data) {
            hasContacts = Object.keys(viewState.contacts).length > 1;
            if (!viewState.loadedAllContacts && hasContacts) {
                loadContacts(root, getUserId(root), LOAD_CONTACTS_LIMIT, viewState.contactsOffset);
            }
            data.originalEvent.preventDefault();
        });
    };

    var show = function(header, body) {
        viewState.contactsOffset = 0;

        if (!body.attr('data-contacts-init')) {
            registerEventListeners(body);
            body.attr('data-contacts-init', true);
        }

        if (!viewState.loadedAllContacts) {
            Renderer.showPlaceholder(body);
            loadContacts(body, getUserId(body), LOAD_CONTACTS_LIMIT, viewState.contactsOffset);
        }
    };

    return {
        show: show,
    };
});
