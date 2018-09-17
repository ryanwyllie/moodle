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
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    /**
     * Retrieve a list of enrolled courses.
     *
     * Valid args are:
     * string classification    future, inprogress, past
     * int limit                number of records to retreive
     * int Offset               offset for pagination
     * int sort                 sort by lastaccess or name
     *
     * @method getEnrolledCourses
     * @param {object} args The request arguments
     * @return {promise} Resolved with an array of courses
     */
    var getEnrolledCoursesByTimeline = function(args) {

        var request = {
            methodname: 'block_myoverview_mycourses',
            args: args
        };

        var promise = Ajax.call([request])[0];

        return promise;
    };

    return {
        getEnrolledCoursesByTimeline: getEnrolledCoursesByTimeline
    };
});
