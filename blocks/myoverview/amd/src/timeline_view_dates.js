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
 * Javascript used to save the user's tab preference.
 *
 * @package    block_myoverview
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
[
    'jquery',
    'block_myoverview/event_list'
],
function(
    $,
    EventList
) {

    var SELECTORS = {
        EVENT_LIST_CONTAINER: '[data-region="event-list-container"]',
    };

    var load = function(root) {
        var eventListContainer = root.find(SELECTORS.EVENT_LIST_CONTAINER);
        EventList.init(eventListContainer, [25, 50]);
    };

    var init = function(root) {
        root = $(root);
        if (root.hasClass('active')) {
            load(root);
            root.attr('data-seen', true);
        }
    };

    var reset = function(root) {
        root.removeAttr('data-seen');
        if (root.hasClass('active')) {
            load(root);
            root.attr('data-seen', true);
        }
    };

    var shown = function(root) {
        if (!root.attr('data-seen')) {
            load(root);
            root.attr('data-seen', true);
        }
    };

    return {
        init: init,
        reset: reset,
        shown: shown
    };
});
