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
define(['jquery', 'core/notification', 'core/templates',
        'core/custom_interaction_events',
        'block_myoverview/calendar_events_repository',
        'block_myoverview/event_list'],
        function($, Notification, Templates, CustomEvents, CalendarEventsRepository, EventList) {

    var SELECTORS = {
        EVENTS_BY_COURSE_LIST: '[data-region="timeline-view-courses"]',
        EVENTS_BY_COURSE_CONTAINER: '[data-region="course-events-container"]',
        COURSE_INFO_CONTAINER: '[data-region="course-info-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        VIEW_MORE_BUTTON: '[data-action="view-more"]'
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

        return $.when.apply($, $.map(root.find(SELECTORS.EVENTS_BY_COURSE_CONTAINER), function(container) {
            container = $(container);
            var courseId = container.attr('data-course-id');
            var root = $("#course-events-container-" + courseId).find('[data-region="event-list-container"]');
            EventList.init(root);
        })).then(function() {
        });

    };

    return {
        init: function(root) {
            root = $(root);
            load(root);
        }
    };
});
