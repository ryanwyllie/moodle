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
    'core/pubsub',
    'core/templates',
    'core_message/message_repository',
    'message_popup/message_drawer_events',
],
function(
    $,
    CustomEvents,
    Notification,
    PubSub,
    Templates,
    Repository,
    Events
) {

    var MESSAGE_SEARCH_LIMIT = 50;
    var USERS_SEARCH_LIMIT = 50;
    var USERS_INITIAL_SEARCH_LIMIT = 3;

    var SELECTORS = {
        BLOCK_ICON_CONTAINER: '[data-region="block-icon-container"]',
        CANCEL_SEARCH_BUTTON: '[data-action="cancel-search"]',
        CONTACTS_CONTAINER: '[data-region="contacts-container"]',
        CONTACTS_LIST: '[data-region="contacts-container"] [data-region="list"]',
        EMPTY_MESSAGE_CONTAINER: '[data-region="empty-message-container"]',
        LIST: '[data-region="list"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        LOADING_PLACEHOLDER: '[data-region="loading-placeholder"]',
        MESSAGES_CONTAINER: '[data-region="messages-container"]',
        MESSAGES_LIST: '[data-region="messages-container"] [data-region="list"]',
        NON_CONTACTS_CONTAINER: '[data-region="non-contacts-container"]',
        NON_CONTACTS_LIST: '[data-region="non-contacts-container"] [data-region="list"]',
        SEARCH_ICON_CONTAINER: '[data-region="search-icon-container"]',
        SEARCH_ACTION: '[data-action="search"]',
        SEARCH_INPUT: '[data-region="search-input"]',
        SEARCH_RESULTS_CONTAINER: '[data-region="search-results-container"]',
        LOAD_MORE_USERS: '[data-action="load-more-users"]',
        LOAD_MORE_MESSAGES: '[data-action="load-more-messages"]',
        BUTTON_TEXT: '[data-region="button-text"]'
    };

    var TEMPLATES = {
        CONTACTS_LIST: 'message_popup/message_drawer_contacts_list',
        NON_CONTACTS_LIST: 'message_popup/message_drawer_non_contacts_list',
        MESSAGES_LIST: 'message_popup/message_drawer_messages_list'
    };

    var getUsersOffset = function(root) {
        return parseInt(root.attr('data-users-offset'), 10);
    };

    var getMessagesOffset = function(root) {
        return parseInt(root.attr('data-max-messages'), 10);
    };

    var setUsersOffset = function(root, value) {
        return root.attr('data-users-offset', value);
    };

    var setMessagesOffset = function(root, value) {
        return root.attr('data-max-messages', value);
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

    var clearAllSearchResults = function(root) {
        root.find(SELECTORS.CONTACTS_LIST).empty();
        root.find(SELECTORS.NON_CONTACTS_LIST).empty();
        root.find(SELECTORS.MESSAGES_LIST).empty();
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

    var showUsersLoadingIcon = function(root) {
        var button = root.find(SELECTORS.LOAD_MORE_USERS);
        button.prop('disabled', true);
        button.find(SELECTORS.BUTTON_TEXT).addClass('hidden');
        button.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var hideUsersLoadingIcon = function(root) {
        var button = root.find(SELECTORS.LOAD_MORE_USERS);
        button.prop('disabled', false);
        button.find(SELECTORS.BUTTON_TEXT).removeClass('hidden');
        button.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var hideLoadMoreUsersButton = function(root) {
        root.find(SELECTORS.LOAD_MORE_USERS).addClass('hidden');
    };

    var showMessagesLoadingIcon = function(root) {
        var button = root.find(SELECTORS.LOAD_MORE_MESSAGES);
        button.prop('disabled', true);
        button.find(SELECTORS.BUTTON_TEXT).addClass('hidden');
        button.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    var hideMessagesLoadingIcon = function(root) {
        var button = root.find(SELECTORS.LOAD_MORE_MESSAGES);
        button.prop('disabled', false);
        button.find(SELECTORS.BUTTON_TEXT).removeClass('hidden');
        button.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    var hideLoadMoreMessagesButton = function(root) {
        root.find(SELECTORS.LOAD_MORE_MESSAGES).addClass('hidden');
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

    var addContact = function(root, contact) {
        var nonContactsContainer = getNonContactsContainer(root);
        var nonContact = findContact(nonContactsContainer, contact.userid);

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

    var renderContacts = function(root, contacts) {
        return Templates.render(TEMPLATES.CONTACTS_LIST, { contacts: contacts })
            .then(function(html) {
                root.find(SELECTORS.CONTACTS_LIST).append(html);
            });
    };

    var renderNonContacts = function(root, nonContacts) {
        return Templates.render(TEMPLATES.NON_CONTACTS_LIST, { noncontacts: nonContacts })
            .then(function(html) {
                root.find(SELECTORS.NON_CONTACTS_LIST).append(html);
            });
    };

    var renderMessages = function(root, messages) {
        return Templates.render(TEMPLATES.MESSAGES_LIST, { messages: messages })
            .then(function(html) {
                root.find(SELECTORS.MESSAGES_LIST).append(html);
            });
    };

    var loadMoreUsers = function(root, loggedInUserId, text, limit, offset) {
        var loadedAll = false;
        showUsersLoadingIcon(root);
        return Repository.searchUsers(loggedInUserId, text, limit + 1, offset)
            .then(function(results) {
                var contacts = results.contacts;
                var noncontacts = results.noncontacts;

                if (contacts.length <= limit && noncontacts.length <= limit) {
                    loadedAll = true;
                    return {
                        contacts: contacts,
                        noncontacts: noncontacts
                    };
                } else {
                    return {
                        contacts: contacts.slice(0, limit),
                        noncontacts: noncontacts.slice(0, limit)
                    };
                }
            })
            .then(function(results) {
                return $.when(
                    renderContacts(root, results.contacts),
                    renderNonContacts(root, results.noncontacts)
                );
            })
            .then(function() {
                setUsersOffset(root, offset + limit);
                hideUsersLoadingIcon(root);

                if (loadedAll) {
                    hideLoadMoreUsersButton(root);
                }

                return;
            })
            .catch(function() {
                hideUsersLoadingIcon(root);
            });
    };

    var loadMoreMessages = function(root, loggedInUserId, text, limit, offset) {
        var loadedAll = false;
        showMessagesLoadingIcon(root);
        return Repository.searchMessages(loggedInUserId, text, limit + 1, offset)
            .then(function(results) {
                var messages = results.contacts;

                if (messages.length <= limit) {
                    loadedAll = true;
                    return messages;
                } else {
                    return messages.slice(0, limit);
                }
            })
            .then(function(messages) {
                return renderMessages(root, messages);
            })
            .then(function() {
                setMessagesOffset(root, offset + limit);
                hideMessagesLoadingIcon(root);

                if (loadedAll) {
                    hideLoadMoreMessagesButton(root);
                }

                return;
            })
            .catch(function() {
                hideMessagesLoadingIcon(root);
            });
    };

    var search = function(root, searchText) {
        var loggedInUserId = getLoggedInUserId(root);
        startLoading(root);
        setUsersOffset(root, 0);
        setMessagesOffset(root, 0);
        clearAllSearchResults(root);

        return $.when(
            loadMoreUsers(root, loggedInUserId, searchText, USERS_INITIAL_SEARCH_LIMIT, 0),
            loadMoreMessages(root, loggedInUserId, searchText, MESSAGE_SEARCH_LIMIT, 0)
        )
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
        var loggedInUserId = getLoggedInUserId(root);
        var searchInput = getSearchInput(root);
        var searchText = '';
        var searchEventHandler = function(e, data) {
            searchText = searchInput.val().trim();

            if (searchText !== '') {
                search(root, searchText)
                    .then(function() {
                        searchInput.focus();
                    });
            }

            data.originalEvent.preventDefault();
        };

        CustomEvents.define(searchInput, [CustomEvents.events.enter]);
        CustomEvents.define(root, [CustomEvents.events.activate]);

        searchInput.on(CustomEvents.events.enter, searchEventHandler);

        root.on(CustomEvents.events.activate, SELECTORS.SEARCH_ACTION, searchEventHandler);

        root.on(CustomEvents.events.activate, SELECTORS.LOAD_MORE_MESSAGES, function(e, data) {
            if (searchText !== '') {
                var offset = getMessagesOffset(root);
                loadMoreMessages(root, loggedInUserId, searchText, MESSAGE_SEARCH_LIMIT, offset);
            }
            data.originalEvent.preventDefault();
        })

        root.on(CustomEvents.events.activate, SELECTORS.LOAD_MORE_USERS, function(e, data) {
            if (searchText !== '') {
                var offset = getUsersOffset(root);
                loadMoreUsers(root, loggedInUserId, searchText, USERS_SEARCH_LIMIT, offset);
            }
            data.originalEvent.preventDefault();
        })

        root.on(CustomEvents.events.activate, SELECTORS.CANCEL_SEARCH_BUTTON, function() {
            clearSearchInput(root);
            showEmptyMessage(root);
            showSearchIcon(root);
            hideSearchResults(root);
            hideLoadingIcon(root);
            hideLoadingPlaceholder(root);
            setUsersOffset(root, 0);
            setMessagesOffset(root, 0);
        });

        PubSub.subscribe(Events.CONTACT_ADDED, function(userId) {
            addContact(root, userId);
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
    };

    var show = function(root) {
        if (!root.attr('data-init')) {
            registerEventListeners(root);
            root.attr('data-init', true);
        }

        var searchInput = getSearchInput(root);
        searchInput.focus();
    };

    return {
        show: show,
    };
});
