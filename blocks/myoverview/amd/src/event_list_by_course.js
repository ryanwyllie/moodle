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
 * Javascript to load and render the list of calendar events grouping by course.
 *
 * @module     block_myoverview/events_by_course_list
 * @package    block_myoverview
 * @copyright  2016 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'block_myoverview/event_list',
    'block_myoverview/calendar_events_repository'
],
function(
    $,
    EventList,
    EventsRepository
) {

    var SECONDS_IN_DAY = 60 * 60 * 24;

    var SELECTORS = {
        EVENTS_BY_COURSE_CONTAINER: '[data-region="course-events-container"]',
        EVENT_LIST_CONTAINER: '[data-region="event-list-container"]',
    };

    /**
     * Loop through course events containers and load calendar events for that course.
     *
     * @method load
     * @param {Object} root The root element of sort by course list.
     */
    var load = function(root) {
        var courseBlocks = root.find(SELECTORS.EVENTS_BY_COURSE_CONTAINER);

        if (!courseBlocks.length) {
            return;
        }

        // We treat all of the event lists the same in the courses view so we
        // can just grab the first element and pull all of the attributes from that.
        var eventList = courseBlocks.find(SELECTORS.EVENT_LIST_CONTAINER).first();
        var midnight = parseInt(eventList.attr('data-midnight'),10);
        var daysOffset = parseInt(eventList.attr('data-days-offset'), 10);
        var daysLimit = eventList.attr('data-days-limit');
        var startTime = midnight + (daysOffset * SECONDS_IN_DAY);
        var endTime = daysLimit != undefined ? midnight + (parseInt(daysLimit, 10) * SECONDS_IN_DAY) : false;
        // Hard limit all of the course event lists to avoid loading a huge amount
        // of events.
        var limit = 10;
        var courseIds = courseBlocks.map(function() {
            return $(this).attr('data-course-id');
        }).get();

        // Load the first set of events for each course in a single request.
        // We want to avoid sending an individual request for each course because
        // there could be lots of them.
        var coursesPromise = EventsRepository.queryByCourses({
            courseids: courseIds,
            starttime: startTime,
            endtime: endTime,
            // Load one more than the limit so that we can determine if there are
            // any more events to load after this.
            limit: limit + 1
        });

        // Load the events into each course block.
        courseBlocks.each(function(index, container) {
            container = $(container);
            var courseId = container.attr('data-course-id');
            var eventListContainer = container.find(EventList.rootSelector);
            var deferred = $.Deferred();

            // Once all of the course events have been loaded then we need
            // to extract just the ones relevant to this course block and
            // hand them to the event list to render.
            coursesPromise.then(function(result) {
                var events = [];
                // Get this course block's events from the collection returned
                // from the server.
                var courseGroup = result.groupedbycourse.filter(function(group) {
                    return group.courseid == courseId;
                });

                if (courseGroup.length) {
                    events = courseGroup[0].events;
                }

                deferred.resolve({events: events});
            }).catch(function(e) {
                deferred.reject(e);
            });

            // Provide the event list with a promise that will be resolved
            // when we have received the events from the server.
            EventList.init(eventListContainer, limit, { 1: deferred.promise() });
        });
    };

    return {
        init: function(root) {
            root = $(root);
            load(root);
        }
    };
});
