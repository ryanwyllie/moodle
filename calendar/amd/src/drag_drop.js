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
 * A javascript module to handle calendar drag and drop.
 *
 * @module     core_calendar/drag_drop
 * @class      drag_drop
 * @package    core_calendar
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
            'jquery',
            'core_calendar/events'
        ],
        function(
            $,
            CalendarEvents
        ) {

    var SELECTORS = {
        ROOT: "[data-region='calendar']",
        DAY_TIMESTAMP: '[data-day-timestamp]',
        WEEK: 'tr',
    };
    var HOVER_CLASS = 'bg-primary';

    // The event id being moved.
    var eventId = null;
    // The number of days the event spans.
    var duration = null;

    var updateHoverState = function(target, hovered, count) {
        var dropZone = $(target).closest(SELECTORS.DAY_TIMESTAMP);
        if (typeof count === 'undefined') {
            // This is how many days we need to highlight.
            count = duration;
        }


        if (hovered) {
            dropZone.addClass(HOVER_CLASS);
        } else {
            dropZone.removeClass(HOVER_CLASS);
        }

        count--;

        // If we've still got days to highlight then we should
        // find the next day.
        if (count > 0) {
            var nextDropZone = dropZone.next();

            // If there are no more days in this week then we
            // need to move down to the next week in the calendar.
            if (!nextDropZone.length) {
                var nextWeek = dropZone.closest(SELECTORS.WEEK).next();

                if (nextWeek.length) {
                    nextDropZone = nextWeek.children(SELECTORS.DAY_TIMESTAMP).first();
                }
            }

            // If we found another day then let's recursively
            // update it's hover state.
            if (nextDropZone.length) {
                updateHoverState(nextDropZone, hovered, count);
            }
        }
    };

    var dragstartHandler = function(e) {
        var eventElement = $(e.target).find('[data-event-id]');
        eventId = eventElement.attr('data-event-id');
        var eventsSelector = SELECTORS.ROOT + ' [data-event-id="' + eventId + '"]';
        duration = $(eventsSelector).length;

        e.dataTransfer.effectAllowed = "move";
        e.dataTransfer.dropEffect = "move";
        e.dropEffect = "move";
    };

    var dragoverHandler = function(e) {
        e.preventDefault();
        updateHoverState(e.target, true);
    };

    var dragleaveHandler = function(e) {
        e.preventDefault();
        updateHoverState(e.target, false);
    };

    var dropHandler = function(e) {
        e.preventDefault();

        var eventElementSelector = SELECTORS.ROOT + ' [data-event-id="' + eventId + '"]';
        var eventElement = $(eventElementSelector);
        var origin = eventElement.closest(SELECTORS.DAY_TIMESTAMP);
        var destination = $(e.target).closest(SELECTORS.DAY_TIMESTAMP);

        updateHoverState(e.target, false);
        $('body').trigger(CalendarEvents.moveEvent, [eventElement, origin, destination]);
    };

    return {
        init: function(root) {
            // HTML5 native drag and drop requires global event
            // handlers unfortunately.
            window.calendarEventDragStartHandler = dragstartHandler;
            window.calendarEventDragOverHandler = dragoverHandler;
            window.calendarEventDragLeaveHandler = dragleaveHandler;
            window.calendarEventDropHandler = dropHandler;
        },
    };
});
