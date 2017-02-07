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
define(['jquery', 'core/custom_interaction_events', 'block_myoverview/event_list', 'block_myoverview/calendar_events_repository'],
        function($, CustomEvents, EventList, CalendarEventsRepository) {

    var SELECTORS = {
        EVENTS_BY_COURSE_LIST: '[data-region="timeline-view-courses"]',
        EVENTS_BY_COURSE_CONTAINER: '[data-region="course-events-container"]'
    };

    /**
     * Retrieve a list of calendar events, render and append them to the end of the
     * existing list. The events will be loaded based on the set of data attributes
     * on the root element.
     *
     * @method load
     * @param {object} The root element of the event list
     * @param {promise} A jquery promise
     */
    var load = function(root) {

        root.find(SELECTORS.EVENTS_BY_COURSE_CONTAINER).each(function(index, container) {
            container = $(container);
            var eventListContainer = container.find('[data-region="event-list-container"]');
            EventList.load(eventListContainer);
        });
    };

    return {
        init: function(root) {
            root = $(root);
            load(root);
        }
    };
});
