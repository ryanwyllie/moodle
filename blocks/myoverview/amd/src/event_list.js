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
 * Javascript to load and render the list of calendar events for a
 * given day range.
 *
 * @module     block_myoverview/event_list
 * @package    block_myoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/notification',
    'core/templates',
    'core/paged_content_factory',
    'core/custom_interaction_events',
    'block_myoverview/calendar_events_repository'
],
function(
    $,
    Notification,
    Templates,
    PagedContentFactory,
    CustomEvents,
    CalendarEventsRepository
) {

    var SECONDS_IN_DAY = 60 * 60 * 24;

    var SELECTORS = {
        EMPTY_MESSAGE: '[data-region="empty-message"]',
        ROOT: '[data-region="event-list-container"]',
        EVENT_LIST: '[data-region="event-list"]',
        EVENT_LIST_CONTENT: '[data-region="event-list-content"]',
        EVENT_LIST_GROUP_CONTAINER: '[data-region="event-list-group-container"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
        VIEW_MORE_BUTTON: '[data-action="view-more"]'
    };

    var TEMPLATES = {
        EVENT_LIST_ITEMS: 'block_myoverview/event-list-items',
        COURSE_EVENT_LIST_ITEMS: 'block_myoverview/course-event-list-items'
    };

    /**
     * Flag the root element to remember that it contains events.
     *
     * @method setHasContent
     * @private
     * @param {object} root The container element
     */
    var setHasContent = function(root) {
        root.attr('data-has-events', true);
    };

    /**
     * Check if the root element has had events loaded.
     *
     * @method hasContent
     * @private
     * @param {object} root The container element
     * @return {bool}
     */
    var hasContent = function(root) {
        return root.attr('data-has-events') ? true : false;
    };

    /**
     * Update the visibility of the content area. The content area
     * is hidden if we have no events.
     *
     * @method updateContentVisibility
     * @private
     * @param {object} root The container element
     * @param {int} eventCount A count of the events we just received.
     */
    var updateContentVisibility = function(root, eventCount) {
        if (eventCount) {
            // We've rendered some events, let's remember that.
            setHasContent(root);
        } else {
            // If this is the first time trying to load events and
            // we don't have any then there isn't any so let's show
            // the empty message.
            if (!hasContent(root)) {
                hideContent(root);
            }
        }
    };

    /**
     * Hide the content area and display the empty content message.
     *
     * @method hideContent
     * @private
     * @param {object} root The container element
     */
    var hideContent = function(root) {
        root.find(SELECTORS.EVENT_LIST_CONTENT).addClass('hidden');
        root.find(SELECTORS.EMPTY_MESSAGE).removeClass('hidden');
    };

    /**
     * Render the given calendar events in the container element. The container
     * elements must have a day range defined using data attributes that will be
     * used to group the calendar events according to their order time.
     *
     * @method render
     * @private
     * @param {object}  root            The container element
     * @param {array}   calendarEvents  A list of calendar events
     * @return {promise} Resolved with a count of the number of rendered events
     */
    var render = function(root, calendarEvents) {
        var templateName = TEMPLATES.EVENT_LIST_ITEMS;

        if (root.attr('data-course-id')) {
            templateName = TEMPLATES.COURSE_EVENT_LIST_ITEMS;
        }

        return Templates.render(templateName, { events: calendarEvents });
    };

    /**
     * Retrieve a list of calendar events, render and append them to the end of the
     * existing list. The events will be loaded based on the set of data attributes
     * on the root element.
     *
     * This function can be provided with a jQuery promise. If it is then it won't
     * attempt to load data by itself, instead it will use the given promise.
     *
     * The provided promise must resolve with an an object that has an events key
     * and value is an array of calendar events.
     * E.g.
     * { events: ['event 1', 'event 2'] }
     *
     * @method load
     * @param {object} root The root element of the event list
     * @return {promise} A jquery promise
     */
    var load = function(root, limit, daysOffset, daysLimit, lastId, courseId) {
        root = $(root);
        var midnight = root.attr('data-midnight'),
            startTime = midnight - (daysOffset * SECONDS_IN_DAY);
            endTime = midnight + (daysLimit * SECONDS_IN_DAY);

        var args = {
            starttime: startTime,
            endtime: endTime,
            limit: limit,
        };

        if (lastId) {
            args.aftereventid = lastId;
        }

        // If we have a course id then we only want events from that course.
        if (courseId) {
            args.courseid = courseId;
            return CalendarEventsRepository.queryByCourse(args);
        } else {
            // Otherwise we want events from any course.
            return CalendarEventsRepository.queryByTime(args);
        }
    };

    var init = function(root) {
        root = $(root);
        var eventListContent = root.find(SELECTORS.EVENT_LIST_CONTENT),
            courseId =  root.attr('data-course-id'),
            daysOffset = root.attr('data-days-offset'),
            daysLimit = root.attr('data-days-limit'),
            lastIds = { 1: 0 };

        PagedContentFactory.createFromAjax(
            function(pagesData, actions) {   
                var promises = [];

                pagesData.forEach(function(pageData) {
                    var pageNumber = pageData.pageNumber;
                    var limit = pageData.limit;
                    var lastPageNumber = pageNumber;
                    
                    // This is here to protect us if, for some reason, the pages
                    // are loaded out of order somehow and we don't have a reference
                    // to the previous page. In that case, scan back to find the most
                    // recent page we've seen.
                    while (!lastIds.hasOwnProperty(lastPageNumber)) {
                        lastPageNumber--;
                    }
                    // Use the last id of the most recent page.
                    var lastId = lastIds[lastPageNumber];

                    promises.push(
                        load(root, limit, daysOffset, daysLimit, lastId, courseId)
                            .then(function(result) {
                                if (!result.events.length) {
                                    actions.allItemsLoaded(pageNumber);
                                    return;
                                }

                                var calendarEvents = result.events;
                                // Remember the last id we've seen.
                                var lastEventId = calendarEvents[calendarEvents.length - 1].id;
                                // Record the id that the next page will need to start from.
                                lastIds[pageNumber + 1] = lastEventId;
                                // Show the empty event message, if necessary.
                                updateContentVisibility(root, calendarEvents.length);
                                // Tell the pagination that everything is loaded.
                                if (calendarEvents.length < limit) {
                                    actions.allItemsLoaded(pageNumber);
                                }
                                // Get the HTML and JS for these calendar events.                                
                                return render(root, calendarEvents);
                            })
                            .catch(Notification.exception)
                    );
                });
    
                return promises;
            },
            {
                ignoreControlWhileLoading: true,
                controlPlacementBottom: true
            }
        )
        .then(function(html, js) {
            Templates.replaceNodeContents(eventListContent, html, js);
        });
    };

    return {
        init: init,
        rootSelector: SELECTORS.ROOT,
    };
});
