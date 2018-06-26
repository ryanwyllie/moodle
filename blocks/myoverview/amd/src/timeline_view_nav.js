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
    'block_myoverview/timeline_view',
    'block_myoverview/event_list',
],
function(
    $,
    CustomEvents,
    TimelineView,
    EventList
) {

    var SELECTORS = {
        TIMELINE_VIEW_DATES: '[data-region="timeline-view-dates"]',
        TIMELINE_DAY_FILTER: '[data-region="timeline-day-filter"]',
        TIMELINE_DAY_FILTER_OPTION: '[data-from]',
        TIMELINE_VIEW_SELECTOR: '[data-region="timeline-view-selector"]'
    };

    var registerTimelineDaySelector = function(root, timelineViewRoot, midnight) {
        var timelineDaySelectorContainer = root.find(SELECTORS.TIMELINE_DAY_FILTER);
        var timelineDaySelector = timelineDaySelectorContainer.find('[data-toggle]');
        var timelineDaySelectorIcon = timelineDaySelector.find('.icon');

        CustomEvents.define(timelineDaySelectorContainer, [CustomEvents.events.activate]);
        timelineDaySelectorContainer.on(
            CustomEvents.events.activate,
            SELECTORS.TIMELINE_DAY_FILTER_OPTION,
            function(e)
            {
                var option = $(e.target);

                if (option.hasClass('active')) {
                    // If it's already active then we don't need to do anything.
                    return;
                } else {
                    // Clear the active class from all other options.
                    option.parent().children().removeClass('active');
                    // Make this option active.
                    option.addClass('active');
                }

                var daysOffset = option.attr('data-from');
                var daysLimit = option.attr('data-to');
                var context = {
                    midnight: midnight,
                    daysoffset: daysOffset,
                    hasdaysoffset: true
                };
                
                if (daysLimit == undefined) {
                    context.nodayslimit = true;
                } else {
                    context.dayslimit = daysLimit;
                    context.hasdayslimit = true;
                }

                timelineDaySelector.html(option.text());
                timelineDaySelector.prepend(timelineDaySelectorIcon);

                var listContainers = root.find(EventList.rootSelector);
                listContainers.attr('data-days-offset', daysOffset);

                if (daysLimit != undefined) {
                    listContainers.attr('data-days-limit', daysLimit);
                } else {
                    listContainers.removeAttr('data-days-limit');
                }

                TimelineView.reset(timelineViewRoot);
            }
        );
    };

    var registerTimelineViewSelector = function(root, timelineViewRoot) {
        // Listen for when the user changes tab so that we can show the first set of courses
        // and load their events when they request the sort by courses view for the first time.
        root.find(SELECTORS.TIMELINE_VIEW_SELECTOR).on('shown shown.bs.tab', function(e) {
            TimelineView.init(timelineViewRoot);
        });
    };

    var init = function(root, timelineViewRoot, midnight) {
        root = $(root);
        registerTimelineDaySelector(root, timelineViewRoot, midnight);
        registerTimelineViewSelector(root, timelineViewRoot);
    };

    return {
        init: init
    };
});
