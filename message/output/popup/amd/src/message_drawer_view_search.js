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
    'message_popup/message_drawer_events',
],
function(
    $,
    CustomEvents,
    Notification,
    Templates,
    Repository,
    Events
) {

    var LOADMORE = 10;

    var SELECTORS = {
        BLOCK_ICON_CONTAINER: '[data-region="block-icon-container"]',
        CANCEL_SEARCH_BUTTON: '[data-action="cancel-search"]',
        CONTACTS_CONTAINER: '[data-region="contacts-container"]',
        EMPTY_MESSAGE_CONTAINER: '[data-region="empty-message-container"]',
        LIST: '[data-region="list"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        LOADING_PLACEHOLDER: '[data-region="loading-placeholder"]',
        MESSAGES_CONTAINER: '[data-region="messages-container"]',
        NON_CONTACTS_CONTAINER: '[data-region="non-contacts-container"]',
        SEARCH_ICON_CONTAINER: '[data-region="search-icon-container"]',
        SEARCH_ACTION: '[data-action="search"]',
        SEARCH_INPUT: '[data-region="search-input"]',
        SEARCH_RESULTS_CONTAINER: '[data-region="search-results-container"]',
        LOADMOREUSERS: '[data-action="loadmoreusers"]',
        LOADMORECONTACTS: '[data-action="loadmorecontacts"]'
    };

    var TEMPLATES = {
        SEARCH_RESULTS: 'message_popup/message_drawer_view_search_results_content'
    };

    var getMaxUsers = function(root) {
        return parseInt(root.attr('data-max-users'));
    };

    var getMaxMessages = function(root) {
        return parseInt(root.attr('data-max-messages'));
    };

    var setSearchText = function(root, searchText) {
        root.attr('data-searchtext', searchText);
    };

    var getSearchText = function(root) {
        return root.attr('data-searchtext');
    };

    var getLoggedInUserId = function(root) {
        return root.attr('data-user-id');
    };

    var getEmptyMessageContainer = function(root) {
        return root.find(SELECTORS.EMPTY_MESSAGE_CONTAINER);
    };

    var getLoadingIconContainer = function(root) {
        return root.find(SELECTORS.LOADING_ICON_CONTAINER);
    };

    var getLoadingPlaceholder = function(root) {
        return root.find(SELECTORS.LOADING_PLACEHOLDER);
    };

    var getSearchIconContainer = function(root) {
        return root.find(SELECTORS.SEARCH_ICON_CONTAINER);
    };

    var getSearchInput = function(root) {
        return root.find(SELECTORS.SEARCH_INPUT);
    };

    var getSearchResultsContainer = function(root) {
        return root.find(SELECTORS.SEARCH_RESULTS_CONTAINER);
    };

    var getContactsContainer = function(root) {
        return root.find(SELECTORS.CONTACTS_CONTAINER);
    };

    var getNonContactsContainer = function(root) {
        return root.find(SELECTORS.NON_CONTACTS_CONTAINER);
    };

    var getMessagesContainer = function(root) {
        return root.find(SELECTORS.MESSAGES_CONTAINER);
    };

    var showEmptyMessage = function(root) {
        getEmptyMessageContainer(root).removeClass('hidden');
    };

    var hideEmptyMessage = function(root) {
        getEmptyMessageContainer(root).addClass('hidden');
    };

    var showLoadingIcon = function(root) {
        getLoadingIconContainer(root).removeClass('hidden');
    };

    var hideLoadingIcon = function(root) {
        getLoadingIconContainer(root).addClass('hidden');
    };

    var showLoadingPlaceholder = function(root) {
        getLoadingPlaceholder(root).removeClass('hidden');
    };

    var hideLoadingPlaceholder = function(root) {
        getLoadingPlaceholder(root).addClass('hidden');
    };

    var showSearchIcon = function(root) {
        getSearchIconContainer(root).removeClass('hidden');
    };

    var hideSearchIcon = function(root) {
        getSearchIconContainer(root).addClass('hidden');
    };

    var showSearchResults = function(root) {
        getSearchResultsContainer(root).removeClass('hidden');
    };

    var hideSearchResults = function(root) {
        getSearchResultsContainer(root).addClass('hidden');
    };

    var disableSearchInput = function(root) {
        getSearchInput(root).prop('disabled', true);
    };

    var enableSearchInput = function(root) {
        getSearchInput(root).prop('disabled', false);
    };

    var clearSearchInput = function(root) {
        getSearchInput(root).val('');
    };

    var startLoading = function(root) {
        hideSearchIcon(root);
        hideEmptyMessage(root);
        hideSearchResults(root);
        showLoadingIcon(root);
        showLoadingPlaceholder(root);
        disableSearchInput(root);
    };

    var stopLoading = function(root) {
        showSearchIcon(root);
        hideEmptyMessage(root);
        showSearchResults(root);
        hideLoadingIcon(root);
        hideLoadingPlaceholder(root);
        enableSearchInput(root);
    };

    var highlightSearch = function(root, searchText) {
        root.find('[data-region="searchable"]').each(function() {
              var content = $(this).text();
              var regex = new RegExp('(' + searchText + ')', 'gi');
              content = content.replace(regex, '<span class="matchtext">$1</span>');
              $(this).replaceWith(content);
        });
    }

    var findContact = function(root, userId) {
        return root.find('[data-contact-user-id="' + userId + '"]');
    };

    var addContact = function(root, userId) {
        var nonContactsContainer = getNonContactsContainer(root);
        var nonContact = findContact(nonContactsContainer, userId);

        if (nonContact.length) {
            nonContact.remove();
            var contactsContainer = getContactsContainer(root);
            contactsContainer.removeClass('hidden');
            contactsContainer.find(SELECTORS.LIST).append(nonContact);
        }

        if (!nonContactsContainer.find(SELECTORS.LIST).children().length) {
            nonContactsContainer.addClass('hidden');
        }
    };

    var removeContact = function(root, userId) {
        var contactsContainer = getContactsContainer(root);
        var contact = findContact(contactsContainer, userId);

        if (contact.length) {
            contact.remove();
            var nonContactsContainer = getNonContactsContainer(root);
            nonContactsContainer.removeClass('hidden');
            nonContactsContainer.find(SELECTORS.LIST).append(contact);
        }

        if (!contactsContainer.find(SELECTORS.LIST).children().length) {
            contactsContainer.addClass('hidden');
        }
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

    var renderSearchResults = function(root, results) {
        var hascontacts = (results.contacts.length > 0);
        var hasnoncontacts = (results.noncontacts.length > 0);
        var hasmessages = (results.messages.length > 0);
        var hasresults = hascontacts || hasnoncontacts || hasmessages;

        var numusers = results.contacts.length + results.noncontacts.length;
        var nummessages = results.messages.length;

        var loadmoremessages = (nummessages > (getMaxMessages(root) - 1)) ? true : false;
        var loadmoreusers = (numusers > (getMaxUsers(root) - 1)) ? true : false;

        var context = {
            hascontacts: hascontacts,
            contacts: results.contacts,
            hasnoncontacts: hasnoncontacts,
            noncontacts: results.noncontacts,
            hasmessages: hasmessages,
            messages: results.messages,
            hasresults: hasresults,
            loadmoreusers: loadmoreusers,
            loadmoremessages: loadmoremessages,
        };

        return Templates.render(TEMPLATES.SEARCH_RESULTS, context)
            .then(function(html) {
                getSearchResultsContainer(root).empty().append(html);
                return html;
            });
    };

    var search = function(root, searchText) {
        var loggedInUserId = getLoggedInUserId(root);
        startLoading(root);

        var maxUsers = getMaxUsers(root);
        var maxMessages = getMaxMessages(root);

        return $.when(
            Repository.searchUsers(loggedInUserId, searchText, maxUsers),
            Repository.searchMessages(loggedInUserId, searchText, maxMessages)
        )
        .then(function(usersResults, messagesResults) {
            usersResults.messages = messagesResults.contacts;
            return renderSearchResults(root, usersResults);
        })
        .then(function() {
            stopLoading(root);
            highlightSearch(root, searchText);
            return;
        })
        .catch(function(error) {
            Notification.exception(error);
            stopLoading(root);
        });
    };

    var registerEventListeners = function(root) {
        var body = $('body');
        var searchInput = getSearchInput(root);
        CustomEvents.define(searchInput, [CustomEvents.events.enter]);
        CustomEvents.define(root, [CustomEvents.events.activate]);

        searchInput.on(CustomEvents.events.enter, function(e, data) {
            var searchText = searchInput.val().trim();

            if (searchText !== '') {
                search(root, searchText)
                    .then(function() {
                        searchInput.focus();
                    });
            }

            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.LOADMORECONTACTS, function() {
            root.attr('data-max-users', getMaxUsers(root) + LOADMORE);
            search(root, getSearchText(root))
        })

        root.on(CustomEvents.events.activate, SELECTORS.LOADMOREUSERS, function() {
            root.attr('data-max-messages', getMaxMessages(root) + LOADMORE);
            search(root, getSearchText(root))
        })

        root.on(CustomEvents.events.activate, SELECTORS.CANCEL_SEARCH_BUTTON, function() {
            clearSearchInput(root);
            showEmptyMessage(root);
            showSearchIcon(root);
            hideSearchResults(root);
            hideLoadingIcon(root);
            hideLoadingPlaceholder(root);
        });

        root.on(CustomEvents.events.activate, SELECTORS.SEARCH_ACTION, function(e, data) {
            var searchText = searchInput.val().trim();

            if (searchText !== '') {
                search(root, searchText);
            }

            data.originalEvent.preventDefault();
        });

        body.on(Events.CONTACT_ADDED, function(e, userId) {
            addContact(root, userId);
        });

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

    var show = function(root, searchText) {
        if (!root.attr('data-init')) {
            registerEventListeners(root);
            root.attr('data-init', true);
        }

        var searchInput = getSearchInput(root);
        searchInput.focus();

        if (typeof searchText !== 'undefined') {
            root.attr('data-max-users', (LOADMORE / 2));
            root.attr('data-max-messages', LOADMORE);
            
            setSearchText(searchText);
            searchInput.val(searchText);
            search(root, searchText);
        }
    };

    return {
        show: show,
    };
});
