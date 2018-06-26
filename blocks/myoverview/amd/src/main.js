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
    'block_myoverview/tab_preferences',
    'block_myoverview/timeline_view_nav',
    'block_myoverview/timeline_view',
],
function(
    $,
    TabPreferences,
    TimelineViewNav,
    TimelineView,
) {

    var SELECTORS = {
        VIEW_CHOICES: '[data-region="block-myoverview-view-choices"]',
        TIMELINE_VIEW: '[data-region="timeline-view"]'
    };

    var registerTabChangeListener = function(root, tabChoiceRoot) {  
        tabChoiceRoot.on('shown.bs.tab', function(e) {
            var targetTab = $(e.target).attr('data-tabname');
            root.find('[data-tab-content]').addClass('d-none');
            root.find('[data-tab-content="' + targetTab + '"]').removeClass('d-none');
        });
    };

    var init = function(root, midnight) {
        root = $(root);
        var tabChoiceRoot = root.find(SELECTORS.VIEW_CHOICES);
        var timelineViewRoot = root.find(SELECTORS.TIMELINE_VIEW);

        TabPreferences.registerEventListeners(tabChoiceRoot);
        TimelineViewNav.init(root, timelineViewRoot, midnight);
        TimelineView.init(timelineViewRoot);
        registerTabChangeListener(root, tabChoiceRoot);
    };

    return {
        init: init
    };
});
