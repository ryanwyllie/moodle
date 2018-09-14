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
    Repository,
    Router,
    Routes
) {

    var RESULT_LIMIT = 20;

    var SELECTORS = {
        CANCEL_SEARCH_BUTTON: '[data-action="cancel-search"]',
        EMPTY_MESSAGE_CONTAINER: '[data-region="empty-message-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        LOADING_PLACEHOLDER: '[data-region="loading-placeholder"]',
        SEARCH_ICON_CONTAINER: '[data-region="search-icon-container"]',
        SEARCH_INPUT: '[data-region="search-input"]',
        SEARCH_RESULTS_CONTAINER: '[data-region="search-results-container"]'
    };

    var TEMPLATES = {
        SEARCH_RESULTS: 'message_popup/message_drawer_view_search_results_content'
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

    var renderSearchResults = function(root, results) {
        var hascontacts = (results.contacts.length > 0);
        var hasnoncontacts = (results.noncontacts.length > 0);
        var hasmessages = (results.messages.length > 0);
        var hasresults = hascontacts || hasnoncontacts || hasmessages;

        var context = {
            hascontacts: hascontacts,
            contacts: results.contacts,
            hasnoncontacts: hasnoncontacts,
            noncontacts: results.noncontacts,
            hasmessages: hasmessages,
            messages: results.messages,
            hasresults: hasresults,
        };

        return Templates.render(TEMPLATES.SEARCH_RESULTS, context)
            .then(function(html) {
                getSearchResultsContainer(root).empty().append(html);
                return html;
            });
    };

    var search = function(root, searchString) {
        var loggedInUserId = getLoggedInUserId(root);
        startLoading(root);
        $.when(
            Repository.searchUsers(loggedInUserId, searchString, RESULT_LIMIT),
            Repository.searchMessages(loggedInUserId, searchString, RESULT_LIMIT)
        )
        .then(function(usersResults, messagesResults) {
            usersResults.messages = messagesResults.contacts;
            return renderSearchResults(root, usersResults);
        })
        .then(function() {
            stopLoading(root);
            return;
        })
        .catch(function(error) {
            Notification.exception(error);
            stopLoading(root);
        });
    };

    var registerEventListeners = function(root) {
        var searchInput = getSearchInput(root);
        CustomEvents.define(searchInput, [CustomEvents.events.enter]);
        CustomEvents.define(root, [CustomEvents.events.activate]);

        searchInput.on(CustomEvents.events.enter, function(e, data) {
            var searchString = searchInput.val().trim();

            if (searchString !== '') {
                search(root, searchString);
            }

            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.CANCEL_SEARCH_BUTTON, function() {
            clearSearchInput(root);
            showEmptyMessage(root);
            showSearchIcon(root);
            hideSearchResults(root);
            hideLoadingIcon(root);
            hideLoadingPlaceholder(root);
        });
    };

    var show = function(root) {
        if (!root.attr('data-init')) {
            registerEventListeners(root);
            root.attr('data-init', true);
        }

        getSearchInput(root).focus();
    };

    return {
        show: show,
    };
});
