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
 * A module to index the overview block for the search engine.
 *
 * @module     block_myoverview/client_side_events_search_indexer
 * @package    block_myoverview
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification', 'block_myoverview/event_list'],
        function($, Notification, EventList) {

    var Indexer = function(searchIndex, root) {
        this.searchIndex = searchIndex;
        this.root = $(root);
        this.root.on(EventList.events.NEW_EVENTS_RENDERED, function(e, data) {
            var calendarEvents = data.events;
            this.indexEvents(calendarEvents);
        }.bind(this));
    };

    Indexer.prototype.indexEvents = function(calendarEvents) {
        calendarEvents.forEach(function(calendarEvent) {
            var record = calendarEvent;
            this.searchIndex.addRecord(record);
        }.bind(this));
    };

    return Indexer;
});
