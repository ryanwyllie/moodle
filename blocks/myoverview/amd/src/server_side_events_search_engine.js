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
 * Contain the logic for server side searching.
 *
 * @module     core/server_side_events_search_engine
 * @package    core
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
            'jquery',
            'core/notification',
            'core/fuse',
            'block_myoverview/calendar_events_repository'
        ],
        function($, Notification, Fuse, CalendarEventsRepository) {

    var SearchEngine = function(searchIndex, options) {
        if (typeof searchIndex == 'undefined') {
            Notification.exception({message: 'You must provide a search index'});
        }

        if (typeof options == 'undefined') {
            options = {};
        }

        this.options = Object.assign({
            shouldSort: true,
            threshold: 0.2,
            location: 0,
            distance: 100,
            maxPatternLength: 32,
            minMatchCharLength: 1,
            keys: searchIndex.getKeys()
        }, options);

        this.index = searchIndex;
    };

    SearchEngine.prototype.search = function(searchValue) {
        return CalendarEventsRepository.search({
            name: searchValue,
            description: searchValue,
        })
        .then(function(results) {
            results.events.forEach(function(event) {
                this.index.addRecord(event);
            }.bind(this));

            return results.events;
        }.bind(this));

        var deferred = $.Deferred();
        var results = this.fuse.search(searchValue);
        deferred.resolve(results);
        return deferred.promise();
    };

    return SearchEngine;
});
