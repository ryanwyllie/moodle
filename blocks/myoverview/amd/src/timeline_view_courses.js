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
    'core/custom_interaction_events',
    'block_myoverview/event_list_by_course'
],
function(
    $,
    CustomEvents,
    EventListByCourse
) {

    var SELECTORS = {
        MORE_COURSES_BUTTON: '[data-action="more-courses"]',
        COURSE_BLOCK: '[data-region="course-block"]',
        HIDDEN_COURSE_BLOCK: '[data-region="course-block"].hidden',
    };

    var registerEventListeners = function(root) {
        CustomEvents.define(root, [CustomEvents.events.activate]);
        // Show more courses and load their events when the user clicks the "more courses"
        // button.
        root.on(CustomEvents.events.activate, SELECTORS.MORE_COURSES_BUTTON, function(e, data) {
            var button = $(e.target);
            var blocks = root.find(SELECTORS.HIDDEN_COURSE_BLOCK);

            if (blocks && blocks.length) {
                var block = blocks.first();
                EventListByCourse.init(block);
                block.removeClass('hidden');
            }

            // If there was only one hidden block then we have no more to show now
            // so we can disable the button.
            if (blocks && blocks.length <= 1) {
                button.prop('disabled', true);
            }

            if (data) {
                data.originalEvent.preventDefault();
                data.originalEvent.stopPropagation();
            }
            e.stopPropagation();
        });
    };

    var load = function(root) {
        var blocks = root.find(SELECTORS.COURSE_BLOCK);

        if (blocks) {
            blocks.each(function(index, block) {
                block = $(block);
                if (!block.hasClass('hidden')) {
                    EventListByCourse.init(block);
                }
            });
        }
    };

    var init = function(root) {
        root = $(root);
        var hiddenCourseBlocks = root.find(SELECTORS.HIDDEN_COURSE_BLOCK);

        if (root.hasClass('active')) {
            load(root);
            root.attr('data-seen', true);
        }

        if (!hiddenCourseBlocks.length) {
            var moreCoursesButton = root.find(SELECTORS.MORE_COURSES_BUTTON);
            moreCoursesButton.prop('disabled', true);
        }

        registerEventListeners(root);
    };

    var reset = function(root) {
        root.removeAttr('data-seen');
        if (root.hasClass('active')) {
            load(root);
            root.attr('data-seen', true);
        }
    };

    var shown = function(root) {
        if (!root.attr('data-seen')) {
            load(root);
            root.attr('data-seen', true);
        }
    };

    return {
        init: init,
        reset: reset,
        shown: shown
    };
});
