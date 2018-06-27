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
    'block_myoverview/calendar_events_repository'
],
function(
    $,
    Notification,
    Templates,
    PagedContentFactory,
    CalendarEventsRepository
) {

    var SECONDS_IN_DAY = 60 * 60 * 24;

    var SELECTORS = {
        EMPTY_MESSAGE: '[data-region="empty-message"]',
        ROOT: '[data-region="event-list-container"]',
        EVENT_LIST_CONTENT: '[data-region="event-list-content"]',
        EVENT_LIST_LOADING_PLACEHOLDER: '[data-region="event-list-loading-placeholder"]',
    };

    var TEMPLATES = {
        EVENT_LIST_ITEMS: 'block_myoverview/event-list-items',
        COURSE_EVENT_LIST_ITEMS: 'block_myoverview/course-event-list-items'
    };

    var DEFAULT_PAGED_CONTENT_CONFIG = {
        ignoreControlWhileLoading: true,
        controlPlacementBottom: true,
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
     * Show the content area and hide the empty content message.
     *
     * @method hideContent
     * @private
     * @param {object} root The container element
     */
    var showContent = function(root) {
        root.find(SELECTORS.EVENT_LIST_CONTENT).removeClass('hidden');
        root.find(SELECTORS.EMPTY_MESSAGE).addClass('hidden');
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
    var render = function(courseId, calendarEvents) {
        var templateName = TEMPLATES.EVENT_LIST_ITEMS;

        if (courseId) {
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
    var load = function(midnight, limit, daysOffset, daysLimit, lastId, courseId) {
        var startTime = midnight + (daysOffset * SECONDS_IN_DAY);
        var endTime = daysLimit != undefined ? midnight + (daysLimit * SECONDS_IN_DAY) : false;

        var args = {
            starttime: startTime,
            limit: limit,
        };

        if (lastId) {
            args.aftereventid = lastId;
        }

        if (endTime) {
            args.endtime = endTime;
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

    var loadEventsFromPageData = function(pageData, actions, midnight, lastIds, preloadedPages, courseId, daysOffset, daysLimit) {
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
        var eventsPromise = null;

        if (preloadedPages && preloadedPages.hasOwnProperty(pageNumber)) {
            eventsPromise = preloadedPages[pageNumber];
        } else {
            // Load one more than the given limit so that we can tell if there
            // is more content to load after this.
            eventsPromise = load(midnight, limit + 1, daysOffset, daysLimit, lastId, courseId);
        }

        return eventsPromise.then(function(result) {
            if (!result.events.length) {
                actions.allItemsLoaded(pageNumber);
                return;
            }

            var calendarEvents = result.events;
            var loadedAll = calendarEvents.length <= limit;
            
            if (loadedAll) {
                // Tell the pagination that everything is loaded.
                actions.allItemsLoaded(pageNumber);
            } else {
                // Remove the last element from the array because it isn't
                // needed in this result set.
                calendarEvents.pop();
            }

            return calendarEvents;
        });
    };

    var createPagedContent = function(
        pageLimit,
        preloadedPages,
        midnight,
        firstLoad,
        courseId,
        daysOffset,
        daysLimit
    ) {      
        // Remember the last event id we loaded on each page because we can't
        // use the offset value since the backend can skip events if the user doesn't
        // have the capability to see them. Instead we load the next page of events
        // based on the last seen event id.
        var lastIds = { 1: 0 };
        var hasContent = false;

        return PagedContentFactory.createFromAjax(
            pageLimit,
            function(pagesData, actions) {   
                var promises = [];

                pagesData.forEach(function(pageData) {
                    var pageNumber = pageData.pageNumber;
                    // Load the page data.
                    var pagePromise = loadEventsFromPageData(
                        pageData,
                        actions,
                        midnight,
                        lastIds,
                        preloadedPages,
                        courseId,
                        daysOffset,
                        daysLimit
                    ).then(function(calendarEvents) {
                        if (calendarEvents) {
                            // Remember that we've loaded content.
                            hasContent = true;
                            // Remember the last id we've seen.
                            var lastEventId = calendarEvents[calendarEvents.length - 1].id;
                            // Record the id that the next page will need to start from.
                            lastIds[pageNumber + 1] = lastEventId;
                            // Get the HTML and JS for these calendar events.
                            return render(courseId, calendarEvents);
                        } else {
                            return;
                        }
                    })
                    .catch(Notification.exception);

                    promises.push(pagePromise);
                });
    
                $.when.apply($, promises).then(function() {
                    // Tell the calling code that the first page has been loaded
                    // and whether it contains any content.
                    firstLoad.resolve(hasContent);
                });

                return promises;
            },
            DEFAULT_PAGED_CONTENT_CONFIG
        );
    };

    var init = function(root, pageLimit, preloadedPages) {
        root = $(root);
        
        var firstLoad = $.Deferred();
        var eventListContent = root.find(SELECTORS.EVENT_LIST_CONTENT);
        var loadingPlaceholder = root.find(SELECTORS.EVENT_LIST_LOADING_PLACEHOLDER);
        var courseId =  root.attr('data-course-id');
        var daysOffset = parseInt(root.attr('data-days-offset'), 10);
        var daysLimit = root.attr('data-days-limit');
        var midnight = parseInt(root.attr('data-midnight'),10)

        showContent(root);
        loadingPlaceholder.removeClass('hidden')

        if (daysLimit != undefined) {
            daysLimit = parseInt(daysLimit, 10);
        }

        createPagedContent(pageLimit, preloadedPages, midnight, firstLoad, courseId, daysOffset, daysLimit)
            .then(function(html, js) {
                html = $(html);
                html.addClass('hidden');
                Templates.replaceNodeContents(eventListContent, html, js);

                firstLoad.then(function(hasContent) {
                    html.removeClass('hidden');
                    loadingPlaceholder.addClass('hidden');

                    if (!hasContent) {
                        hideContent(root);
                    }
                });
            })
            .catch(Notification.exception);
    };

    return {
        init: init,
        rootSelector: SELECTORS.ROOT,
    };
});
