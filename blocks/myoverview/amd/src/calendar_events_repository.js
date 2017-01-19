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
 * A javascript module to retrieve calendar events from the server.
 *
 * @module     block_myoverview/calendar_events_repository
 * @class      repository
 * @package    block_myoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    var DEFAULT_LIMIT = 20;

    /**
     * Retrieve a list of calendar events for the logged in user after the given
     * time.
     *
     * @method queryFromTime
     * @param {int}         startTime    Only get events after this time
     * @param {int}         limit        Limit the number of results returned
     * @param {int}         afterEventId Offset the result set from the given id
     * @return {promise}    Resolved with an array of the calendar events
     */
    var queryFromTime = function(startTime, limit, afterEventId) {
        limit = (typeof limit === 'undefined') ? DEFAULT_LIMIT : limit;

        var args = {
            timesortfrom: startTime,
            limitnum: limit,
        };

        if (typeof afterEventId !== 'undefined') {
            args.aftereventid = afterEventId;
        }

        var request = {
            methodname: 'core_calendar_get_action_events_by_timesort',
            args: args
        };

        var promise = Ajax.call([request])[0];

        promise.fail(Notification.exception);

        return promise;
    };

    return {
        queryFromTime: queryFromTime,
    };
});
