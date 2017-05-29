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
 * Contain the logic for search.
 *
 * @module     core/search_controller
 * @package    core
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification'], function($, Notification) {

    var SearchIndex = function(keys, records) {
        if (typeof keys == 'undefined' || !keys.length) {
            Notification.exception({'message': 'You must provide some keys for the index records'});
        } else {
            this.keys = keys;
        }

        if (typeof records != 'undefined') {
            this.records = records;
        } else {
            this.records = [];
        }

        this.seen = {};
    };

    SearchIndex.prototype.getKeys = function() {
        return this.keys;
    };

    SearchIndex.prototype.getRecords = function() {
        return this.records;
    };

    SearchIndex.prototype.addRecord = function(record) {
        var id = record.id;

        // Ignore duplicate records. It should probably update them
        // rather than ignore but I can't think of a good way to do
        // that for the time being.
        if (this.seen.hasOwnProperty(id) && this.seen[id]) {
            return;
        }

        this.seen[id] = true;
        this.records.push(record);
    };

    return SearchIndex;
});
