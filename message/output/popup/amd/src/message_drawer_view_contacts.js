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
    'message_popup/message_drawer_events',
    'message_popup/message_drawer_view_contacts_section'
],
function(
    $,
    Notification,
    Templates,
    MessageRepository,
    Events,
    Section
) {

    var SELECTORS = {
        BLOCK_ICON_CONTAINER: '[data-region="block-icon-container"]'
    };

    var TEMPLATES = {
        CONTACTS_LIST: 'message_popup/message_drawer_contacts_list'
    };

    var render = function(contentContainer, contacts) {
        return Templates.render(TEMPLATES.CONTACTS_LIST, {contacts: contacts})
            .then(function(html) {
                contentContainer.append(html);
            })
            .catch(Notification.exception);
    };

    var loadContacts = function(root, userId) {
        return MessageRepository.getContacts(userId)
            .then(function(result) {
                return result.contacts;
            })
            .catch(Notification.exception);
    };

    var findContact = function(root, userId) {
        return root.find('[data-contact-user-id="' + userId + '"]');
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
        var body = $('body');

        body.on(Events.CONTACT_REMOVED, function(e, userId) {
            removeContact(root, userId);
        });

        body.on(Events.CONTACT_BLOCKED, function(e, userId) {
            blockContact(root, userId);
        });

        body.on(Events.CONTACT_UNBLOCKED, function(e, userId) {
            unblockContact(root, userId);
        });
    };

    var show = function(root) {
        root = $(root);

        if (!root.attr('data-contacts-init')) {
            registerEventListeners(root);
            root.attr('data-contacts-init', true);
        }
        Section.show(root, loadContacts, render);
    };

    return {
        show: show,
    };
});
