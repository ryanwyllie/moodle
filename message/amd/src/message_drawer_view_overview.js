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
 * @module     core_message/message_drawer_view_overview
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'core_message/message_drawer_view_overview_messages',
    'core_message/message_drawer_router',
    'core_message/message_drawer_routes'
],
function(
    Messages,
    Router,
    Routes
) {

    var SELECTORS = {
        MESSAGES: '[data-region="view-overview-messages"]',
        SEARCH_INPUT: '[data-region="view-overview-search-input"]',
    };

    /**
     * Get the search input text element.
     * 
     * @param  {Object} header Overview header container element.
     * @return {Object} The search input element.
     */
    var getSearchInput = function(header) {
        return header.find(SELECTORS.SEARCH_INPUT);
    };

    /**
     * Listen to, and handle event in the overview header.
     *
     * @param {Object} header Conversation header container element.
     */
    var registerEventListeners = function(header) {
        var searchInput = getSearchInput(header);
        searchInput.on('focus', function() {
            Router.go(Routes.VIEW_SEARCH);
        });
    };

    /**
     * Setup the overview page.
     *
     * @param {Object} header Overview header container element.
     * @param {Object} body Overview body container element.
     */
    var show = function(header, body) {
        if (!header.attr('data-init')) {
            registerEventListeners(header);
            header.attr('data-init', true);
        }

        getSearchInput(header).val('');
        Messages.show(body.find(SELECTORS.MESSAGES));
    };

    return {
        show: show,
    };
});
