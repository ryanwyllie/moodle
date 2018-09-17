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
 * Manage the courses view for the overview block.
 *
 * @package    block_myoverview
 * @copyright  2018 Bas Brands <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
[
    'jquery',
    'core/notification',
    'block_myoverview/repository',
    'core/paged_content_factory',
    'core/templates',
],
function(
    $,
    Notification,
    Repository,
    PagedContentFactory,
    Templates
) {

    var TEMPLATES = {
        COURSES_CARDS: 'block_myoverview/view-cards',
        COURSES_LIST: 'block_myoverview/view-list',
        COURSES_SUMMARY: 'block_myoverview/view-summary',
        NOCOURSES: 'block_myoverview/no-courses'
    };

    var NUMCOURSES_PERPAGE = [12, 24];

    /**
     * Get filter values from DOM.
     *
     * @param {object} root The root element for the courses view.
     */
    var getFilterValues = function(root) {
        var filters = {};
        filters.display = root.attr('data-display');
        filters.grouping = root.attr('data-grouping');
        filters.sort = root.attr('data-sort');
        return filters;
    };

    // We want the paged content controls below the paged content area
    // and the controls should be ignored while data is loading.
    var DEFAULT_PAGED_CONTENT_CONFIG = {
        ignoreControlWhileLoading: true,
        controlPlacementBottom: true,
    };

    /**
     * Get enrolled courses from backend.
     *
     * @param {object} filters The filters for this view.
     * @param {int} limit The number of courses to show.
     * @param {int} pageNumber The pagenumber to view.
     */
    var getMyCourses = function(filters, limit, pageNumber) {
        return Repository.getEnrolledCoursesByTimeline({
            offset:  pageNumber * limit,
            limit: limit,
            classification: filters.grouping,
            sort: filters.sort
        });
    };

    /**
     * Render the dashboard courses.
     *
     * @param {object} root The root element for the courses view.
     * @param {array} coursesData containing array of returned courses.
     * @param {object} filters The filters for this view.
     */
    var renderCourses = function(root, coursesData, filters) {
        if (filters.display == 'cards') {
            var currentTemplate = TEMPLATES.COURSES_CARDS;
        } else if (filters.display == 'list') {
            var currentTemplate = TEMPLATES.COURSES_LIST;
        } else {
            var currentTemplate = TEMPLATES.COURSES_SUMMARY;
        }

        if (coursesData.hascourses) {
            return Templates.render(currentTemplate, {
                courses: coursesData.courses
            });
        } else {
            var nocoursesimg = root.attr('data-nocoursesimg');
            return Templates.render(TEMPLATES.NOCOURSES, {
                nocoursesimg: nocoursesimg
            });
        }
    };

    /**
     * Intialise the courses list and cards views on page load.
     * 
     * @param {object} root The root element for the courses view.
     * @param {object} content The content element for the courses view.
     */
    var init = function(root, content) {

        root = $(root);

        var filters = getFilterValues(root);

        var pagedContentPromise = PagedContentFactory.createWithLimit(
            NUMCOURSES_PERPAGE,
            function (pagesData, actions) {
                var promises = [];

                pagesData.forEach(function(pageData) {
				    var pageNumber = pageData.pageNumber - 1;

				    var pagePromise = getMyCourses(
				    	filters,
				    	pageData.limit,
				    	pageNumber
				    ).then(function(coursesData) {
				        if (coursesData.courses.length < pageData.limit) {
				            actions.allItemsLoaded(pageData.pageNumber);
				        }
				        return renderCourses(root, coursesData, filters);
				    })
				    .catch(Notification.exception);

                    promises.push(pagePromise);
                });

                return promises;
            },
            DEFAULT_PAGED_CONTENT_CONFIG
        );

        pagedContentPromise.then(function(html, js) {
            Templates.replaceNodeContents(content, html, js);
        }).catch(Notification.exception);
    };

    /**
     * Reset the courses views to their original
     * state on first page load.
     * 
     * This is called when configuration has changed for the event lists
     * to cause them to reload their data.
     * 
     * @param {object} root The root element for the timeline view.
     */
    var reset = function(root, content) {
        init(root, content);
    };

    return {
        init: init,
        reset: reset
    };
});
