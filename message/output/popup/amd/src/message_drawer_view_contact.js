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
    'core_message/message_repository'
],
function(
    $,
    CustomEvents,
    Notification,
    Templates,
    Repository
) {

    var SELECTORS = {
        ACTION_ADD_CONTACT: '[data-action="add-contact"]',
        ACTION_REMOVE_CONTACT: '[data-action="remove-contact"]',
        ACTION_BLOCK: '[data-action="block"]',
        ACTION_UNBLOCK: '[data-action="unblock"]',
        CONTENT_CONTAINER: '[data-region="content-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        LOADING_PLACEHOLDER_CONTAINER: '[data-region="loading-placeholder-container"]',
    };

    var TEMPLATES = {
        CONTENT: 'message_popup/message_drawer_view_contact_content'
    };

    var getLoggedInUserId = function(root) {
        return root.attr('data-user-id');
    };

    var getContentContainer = function(root) {
        return root.find(SELECTORS.CONTENT_CONTAINER);
    };

    var showLoadingIcon = function(root) {
        root.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var hideLoadingIcon = function(root) {
        root.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var showLoadingPlaceholder = function(root) {
        root.find(SELECTORS.LOADING_PLACEHOLDER_CONTAINER).removeClass('hidden');
    };

    var hideLoadingPlaceholder = function(root) {
        root.find(SELECTORS.LOADING_PLACEHOLDER_CONTAINER).addClass('hidden');
    };

    var showAddContactButton = function(root) {
        root.find(SELECTORS.ACTION_ADD_CONTACT).removeClass('hidden');
    }

    var hideAddContactButton = function(root) {
        root.find(SELECTORS.ACTION_ADD_CONTACT).addClass('hidden');
    }

    var showRemoveContactButton = function(root) {
        root.find(SELECTORS.ACTION_REMOVE_CONTACT).removeClass('hidden');
    }

    var hideRemoveContactButton = function(root) {
        root.find(SELECTORS.ACTION_REMOVE_CONTACT).addClass('hidden');
    }

    var showBlockButton = function(root) {
        root.find(SELECTORS.ACTION_BLOCK).removeClass('hidden');
    }

    var hideBlockButton = function(root) {
        root.find(SELECTORS.ACTION_BLOCK).addClass('hidden');
    }

    var showUnblockButton = function(root) {
        root.find(SELECTORS.ACTION_UNBLOCK).removeClass('hidden');
    }

    var hideUnblockButton = function(root) {
        root.find(SELECTORS.ACTION_UNBLOCK).addClass('hidden');
    }

    var startFullLoading = function(root) {
        showLoadingIcon(root);
        showLoadingPlaceholder(root);
    };

    var stopFullLoading = function(root) {
        hideLoadingIcon(root);
        hideLoadingPlaceholder(root);
    };

    var load = function(root, contactUserId) {
        var loggedInUserId =  getLoggedInUserId(root);
        return Repository.getProfile(loggedInUserId, contactUserId);
    };

    var render = function(root, profile) {
        return Templates.render(TEMPLATES.CONTENT, profile)
            .then(function(html) {
                getContentContainer(root).append(html);
            });
    };

    var addContact = function(root, loggedInUserId, profileUserId) {
        return Repository.createContacts(loggedInUserId, [profileUserId])
            .then(function() {
                hideAddContactButton(root);
                showRemoveContactButton(root);
                return;
            });
    };

    var removeContact = function(root, loggedInUserId, profileUserId) {
        return Repository.deleteContacts(loggedInUserId, [profileUserId])
            .then(function() {
                showAddContactButton(root);
                hideRemoveContactButton(root);
                return;
            });
    };

    var blockUser = function(root, loggedInUserId, profileUserId) {
        return Repository.blockContacts(loggedInUserId, [profileUserId])
            .then(function() {
                hideBlockButton(root);
                showUnblockButton(root);
                return;
            });
    };

    var unblockUser = function(root, loggedInUserId, profileUserId) {
        return Repository.unblockContacts(loggedInUserId, [profileUserId])
            .then(function() {
                showBlockButton(root);
                hideUnblockButton(root);
                return;
            });
    };

    var registerEventListeners = function(root) {
        var loggedInUserId = getLoggedInUserId(root);
        var generateHandler = function(selector, callback) {
            return function(e, data) {
                var target = $(e.target).closest(selector);
                var profileUserId = target.attr('data-user-id');

                showLoadingIcon(root);
                callback(root, loggedInUserId, profileUserId)
                    .then(function() {
                        hideLoadingIcon(root);
                    })
                    .catch(function(error) {
                        Notification.exception(error);
                        hideLoadingIcon(root);
                    });

                data.originalEvent.preventDefault();
            }
        };

        CustomEvents.define(root, [CustomEvents.events.activate]);

        root.on(
            CustomEvents.events.activate,
            SELECTORS.ACTION_ADD_CONTACT,
            generateHandler(SELECTORS.ACTION_ADD_CONTACT, addContact)
        );

        root.on(
            CustomEvents.events.activate,
            SELECTORS.ACTION_REMOVE_CONTACT,
            generateHandler(SELECTORS.ACTION_REMOVE_CONTACT, removeContact)
        );

        root.on(
            CustomEvents.events.activate,
            SELECTORS.ACTION_BLOCK,
            generateHandler(SELECTORS.ACTION_BLOCK, blockUser)
        );

        root.on(
            CustomEvents.events.activate,
            SELECTORS.ACTION_UNBLOCK,
            generateHandler(SELECTORS.ACTION_UNBLOCK, unblockUser)
        );
    };

    var show = function(root, contactUserId) {
        root = $(root);

        if (!root.attr('data-init')) {
            registerEventListeners(root);
            root.attr('data-init', true);
        }

        getContentContainer(root).empty();
        startFullLoading(root);
        load(root, contactUserId)
            .then(function(profile) {
                stopFullLoading(root);
                return render(root, profile);
            })
            .catch(function(error) {
                Notification.exception(error);
                stopFullLoading(root);
            });
    };

    return {
        show: show,
    };
});
