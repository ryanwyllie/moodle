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
    'message_popup/message_drawer_view_overview_contacts',
    'message_popup/message_drawer_view_overview_messages',
    'message_popup/message_drawer_router',
    'message_popup/message_drawer_routes'
],
function(
    $,
    Contacts,
    Messages,
    Router,
    Routes
) {

    var SELECTORS = {
        CONTACTS: '[data-region="view-overview-contacts"]',
        MESSAGES: '[data-region="view-overview-messages"]',
        SEARCH_INPUT: '[data-region="view-overview-search-input"]',
    };

    var getSearchInput = function(root) {
        return root.find(SELECTORS.SEARCH_INPUT);
    };

    var registerEventListeners = function(root) {
        var searchInput = getSearchInput(root);
        searchInput.on('focus', function() {
            Router.go(Routes.VIEW_SEARCH);
        });
    };

    var show = function(root) {
        root = $(root);
        if (!root.attr('data-init')) {
            registerEventListeners(root);
            root.attr('data-init', true);
        }

        getSearchInput(root).val('');
        Contacts.show(root.find(SELECTORS.CONTACTS));
        Messages.show(root.find(SELECTORS.MESSAGES));
    };

    return {
        show: show,
    };
});
