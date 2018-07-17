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
 * Manage the timeline view for the overview block.
 *
 * @package    block_myoverview
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
[
    'jquery',
    'block_myoverview/timeline_view_dates',
    'block_myoverview/timeline_view_courses',
],
function(
    $,
    TimelineViewDates,
    TimelineViewCourses,
) {

    var SELECTORS = {
        TIMELINE_DATES_VIEW: '#myoverview_timeline_dates',
        TIMELINE_COURSES_VIEW: '#myoverview_timeline_courses',
    };

    /**
     * Intialise the timeline dates and courses views on page load.
     * This function should only be called once per page load because
     * it can cause event listeners to be added to the page.
     * 
     * @param {object} root The root element for the timeline view.
     */
    var init = function(root) {
        root = $(root);
        var datesViewRoot = root.find(SELECTORS.TIMELINE_DATES_VIEW);
        var coursesViewRoot = root.find(SELECTORS.TIMELINE_COURSES_VIEW);

        TimelineViewDates.init(datesViewRoot);
        TimelineViewCourses.init(coursesViewRoot);
    };

    /**
     * Reset the timeline dates and courses views to their original
     * state on first page load.
     * 
     * This is called when configuration has changed for the event lists
     * to cause them to reload their data.
     * 
     * @param {object} root The root element for the timeline view.
     */
    var reset = function(root) {
        var datesViewRoot = root.find(SELECTORS.TIMELINE_DATES_VIEW);
        var coursesViewRoot = root.find(SELECTORS.TIMELINE_COURSES_VIEW);
        TimelineViewDates.reset(datesViewRoot);
        TimelineViewCourses.reset(coursesViewRoot);
    };

    /**
     * Tell the timeline dates or courses view that it has been displayed.
     * 
     * This is called each time one of the views is displayed and is used to
     * lazy load the data within it on first load.
     * 
     * @param {object} root The root element for the timeline view.
     */
    var shown = function(root) {
        var datesViewRoot = root.find(SELECTORS.TIMELINE_DATES_VIEW);
        var coursesViewRoot = root.find(SELECTORS.TIMELINE_COURSES_VIEW);

        if (datesViewRoot.hasClass('active')) {
            TimelineViewDates.shown(datesViewRoot);
        } else {
            TimelineViewCourses.shown(coursesViewRoot);
        }
    };

    return {
        init: init,
        reset: reset,
        shown: shown,
    };
});
