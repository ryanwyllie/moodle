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
 * Manage the timeline courses view for the overview block.
 *
 * @package    block_timeline
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
[
    'jquery',
    'core/notification',
    'core/custom_interaction_events',
    'core/templates',
    'block_timeline/event_list',
    'core_course/repository',
    'block_timeline/calendar_events_repository'
],
function(
    $,
    Notification,
    CustomEvents,
    Templates,
    EventList,
    CourseRepository,
    EventsRepository
) {

    var SELECTORS = {
        MORE_COURSES_BUTTON: '[data-action="more-courses"]',
        MORE_COURSES_BUTTON_CONTAINER: '[data-region="more-courses-button-container"]',
        NO_COURSES_EMPTY_MESSAGE: '[data-region="no-courses-empty-message"]',
        COURSES_LIST: '[data-region="courses-list"]',
        COURSE_ITEMS_LOADING_PLACEHOLDER: '[data-region="course-items-loading-placeholder"]'
    };

    var TEMPLATES = {
        COURSE_ITEMS: 'block_timeline/course-items'
    };

    var COURSE_CLASSIFICATION = 'inprogress';
    var COURSE_EVENT_LIMIT = 5;
    var COURSE_LIMIT = 2;
    var SECONDS_IN_DAY = 60 * 60 * 24;

    /**
     * Add event listeners to load more courses for the courses view.
     * 
     * @param {object} root The root element for the timeline courses view.
     */
    var registerEventListeners = function(root) {
        CustomEvents.define(root, [CustomEvents.events.activate]);
        // Show more courses and load their events when the user clicks the "more courses"
        // button.
        root.on(CustomEvents.events.activate, SELECTORS.MORE_COURSES_BUTTON, function(e, data) {
            loadMoreCourses(root);

            if (data) {
                data.originalEvent.preventDefault();
                data.originalEvent.stopPropagation();
            }
            e.stopPropagation();
        });
    };

    /**
     * Hide the loading placeholder elements.
     * 
     * @param {object} root The rool element.
     */
    var hideLoadingPlaceholder = function(root) {
        root.find(SELECTORS.COURSE_ITEMS_LOADING_PLACEHOLDER).addClass('hidden');
    };

    /**
     * Hide the "more courses" button.
     * 
     * @param {object} root The rool element.
     */
    var hideMoreCoursesButton = function(root) {
        root.find(SELECTORS.MORE_COURSES_BUTTON_CONTAINER).addClass('hidden');
    };

    /**
     * Show the "more courses" button.
     * 
     * @param {object} root The rool element.
     */
    var showMoreCoursesButton = function(root) {
        root.find(SELECTORS.MORE_COURSES_BUTTON_CONTAINER).removeClass('hidden');
    };

    /**
     * Display the message for when there are no courses available.
     * 
     * 
     */
    var showNoCoursesEmptyMessage = function(root) {
        root.find(SELECTORS.NO_COURSES_EMPTY_MESSAGE).removeClass('hidden');
    };

    /**
     * Render the course items HTML to the page.
     * 
     * @param {object} root The rool element.
     * @param {string} html The course items HTML to render.
     */
    var renderCourseItemsHTML = function(root, html) {
        var container = root.find(SELECTORS.COURSES_LIST);
        Templates.appendNodeContents(container, html, '');
    };

    /**
     * Get a list of events for the given course ids. Returns a promise that will
     * be resolved with the events.
     * 
     * @param {array} courseIds The list of course ids to fetch events for.
     * @param {int} startTime Timestamp to fetch events from.
     * @param {int} limit Limit to the number of events (this applies per course, not total)
     * @param {int} endTime Timestamp to fetch events to.
     * @return {object} jQuery promise.
     */
    var getEventsForCourseIds = function(courseIds, startTime, limit, endTime) {
        var args = {
            courseids: courseIds,
            starttime: startTime,
            limit: limit
        };

        if (endTime) {
            args.endtime = endTime;
        }

        return EventsRepository.queryByCourses(args);
    };

    var loadEventsForCoursesPromise = function(coursesPromise, startTime, endTime) {
        var eventsDeferred = $.Deferred();

        coursesPromise.then(function(results) {
            if (results.courses) {
                var courseIds = results.courses.map(function(course) {
                    return course.id;
                });

                getEventsForCourseIds(courseIds, startTime, COURSE_EVENT_LIMIT, endTime)
                    .then(function(result) {
                        eventsDeferred.resolve(result);  
                    });                
            }

            return results;
        })
        .catch(function(error) {
          eventsDeferred.reject(error);  
        });

        return eventsDeferred.promise();
    };

    var updateDisplayFromCoursesPromise = function(coursesPromise, root, midnight, daysOffset, daysLimit, noEventsURL) {
        var renderDeferred = $.Deferred();

        coursesPromise.then(function(result) {
            return result.courses;
        })
        .then(function(courses) {
            if (courses.length < COURSE_LIMIT) {
                hideMoreCoursesButton(root);
            } else {
                showMoreCoursesButton(root);
            }

            return courses;
        })
        .then(function(courses) {
            if (courses) {
                return Templates.render(TEMPLATES.COURSE_ITEMS, { 
                    courses: courses, 
                    midnight: midnight,
                    hasdaysoffset: true,
                    hasdayslimit: true,
                    daysoffset: daysOffset,
                    dayslimit: daysLimit,
                    nodayslimit: daysLimit == undefined,
                    urls: {
                        noevents: noEventsURL
                    }
                });
            } else {
                return false;
            }
        })
        .then(function(html) {
            hideLoadingPlaceholder(root);

            if (html) {
                renderCourseItemsHTML(root, html);
            } else {
                showNoCoursesEmptyMessage(root);
            }

            renderDeferred.resolve(html);
            return html;
        })
        .catch(function(error) {
            hideLoadingPlaceholder(root);
            renderDeferred.reject(error);
        });

        return renderDeferred.promise();
    }

    var updateOffsetFromCoursesPromise = function(root, coursesPromise) {
        coursesPromise.then(function(result) {
            root.attr('data-offset', result.nextoffset);
            return result;
        })
    };

    /**
     * Find all of the visible course blocks and initialise the event
     * list module to being loading the events for the course block.
     * 
     * @param {object} root The root element for the timeline courses view.
     */
    var loadMoreCourses = function(root) {
        var offset = parseInt(root.attr('data-offset'), 10);
        var limit = parseInt(root.attr('data-limit'), 10);
        var daysOffset = parseInt(root.attr('data-days-offset'), 10);
        var daysLimit = root.attr('data-days-limit');
        var midnight = parseInt(root.attr('data-midnight'), 10);
        var startTime = midnight + (daysOffset * SECONDS_IN_DAY);
        var endTime = daysLimit != undefined ? midnight + (parseInt(daysLimit, 10) * SECONDS_IN_DAY) : false;
        var noEventsURL = root.attr('data-no-events-url');
        var coursesPromise = CourseRepository.getEnrolledCoursesByTimelineClassification(
            COURSE_CLASSIFICATION,
            limit,
            offset
        );

        var eventsPromise = loadEventsForCoursesPromise(coursesPromise, startTime, endTime);
        var renderPromise = updateDisplayFromCoursesPromise(coursesPromise, root, midnight, daysOffset, daysLimit, noEventsURL);
        updateOffsetFromCoursesPromise(root, coursesPromise);

        coursesPromise.catch(Notification.exception);

        return $.when(coursesPromise, eventsPromise, renderPromise)
            .then(function(coursesByClassification, eventsByCourse) {
                coursesByClassification.courses.forEach(function(course) {
                    var courseId = course.id;
                    var events = [];
                    var courseEventsContainer = root.find('[data-region="course-events-container"][data-course-id="' + courseId + '"]');
                    var eventListRoot = courseEventsContainer.find(EventList.rootSelector);
                    var courseGroups = eventsByCourse.groupedbycourse.filter(function(group) {
                        return group.courseid == courseId;
                    });
                    
                    if (courseGroups.length) {
                        events = courseGroups[0].events;
                    }

                    var pageOnePreload = $.Deferred().resolve({ events: events }).promise();

                    EventList.init(eventListRoot, COURSE_EVENT_LIMIT, { 1: pageOnePreload });
                });
            });
    };

    var reloadCourseEvents = function(root) {
        var daysOffset = parseInt(root.attr('data-days-offset'), 10);
        var daysLimit = root.attr('data-days-limit');
        var midnight = parseInt(root.attr('data-midnight'), 10);
        var startTime = midnight + (daysOffset * SECONDS_IN_DAY);
        var endTime = daysLimit != undefined ? midnight + (parseInt(daysLimit, 10) * SECONDS_IN_DAY) : false;
        var eventLists = root.find(EventList.rootSelector);
        var courseIds = eventLists.map(function() {
            return $(this).attr('data-course-id');
        }).get();

        var eventsPromise = getEventsForCourseIds(courseIds, startTime, COURSE_EVENT_LIMIT, endTime);

        eventsPromise.catch(Notification.exception);

        eventLists.each(function(index, container) {
            container = $(container);
            var courseId = container.attr('data-course-id');
            var pageDeferred = $.Deferred();

            eventsPromise.then(function(eventsByCourse) {
                var events = [];
                var courseGroups = eventsByCourse.groupedbycourse.filter(function(group) {
                    return group.courseid == courseId;
                });
                
                if (courseGroups.length) {
                    events = courseGroups[0].events;
                }

                pageDeferred.resolve({ events: events });
            })
            .catch(function(error) {
                pageDeferred.reject(error);
            });

            EventList.init(container, COURSE_EVENT_LIMIT, { 1: pageDeferred.promise() });
        });
    };

    /**
     * Initialise the timeline courses view. Begin loading the events
     * if this view is active. Add the relevant event listeners.
     * 
     * This function should only be called once per page load because it
     * is adding event listeners to the page.
     * 
     * @param {object} root The root element for the timeline courses view.
     */
    var init = function(root) {
        root = $(root);

        if (root.hasClass('active')) {
            // Only load if this is active otherwise it will be lazy loaded later.
            loadMoreCourses(root);
            root.attr('data-seen', true);
        }

        registerEventListeners(root);
    };

    /**
     * Reset the element back to it's initial state. Begin loading the events again
     * if this view is active.
     * 
     * @param {object} root The root element for the timeline courses view.
     */
    var reset = function(root) {
        root.removeAttr('data-seen');
        if (root.hasClass('active')) {
            reloadCourseEvents(root);
            root.attr('data-seen', true);
        }
    };

    /**
     * If this is the first time this view has been displayed then begin loading
     * the events.
     * 
     * @param {object} root The root element for the timeline courses view.
     */
    var shown = function(root) {
        if (!root.attr('data-seen')) {
            loadMoreCourses(root);
            root.attr('data-seen', true);
        }
    };

    return {
        init: init,
        reset: reset,
        shown: shown
    };
});
