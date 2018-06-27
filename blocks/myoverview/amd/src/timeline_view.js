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
 * Javascript used to save the user's tab preference.
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

    var init = function(root) {
        root = $(root);
        var datesViewRoot = root.find(SELECTORS.TIMELINE_DATES_VIEW);
        var coursesViewRoot = root.find(SELECTORS.TIMELINE_COURSES_VIEW);

        TimelineViewDates.init(datesViewRoot);
        TimelineViewCourses.init(coursesViewRoot);
    };

    var reset = function(root) {
        var datesViewRoot = root.find(SELECTORS.TIMELINE_DATES_VIEW);
        var coursesViewRoot = root.find(SELECTORS.TIMELINE_COURSES_VIEW);
        TimelineViewDates.reset(datesViewRoot);
        TimelineViewCourses.reset(coursesViewRoot);
    };

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
