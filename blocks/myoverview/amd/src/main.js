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
 * Javascript to initialise the myoverview block.
 *
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
[
    'jquery',
    'block_myoverview/tab_preferences',
    'block_myoverview/timeline_view_nav',
    'block_myoverview/timeline_view'
],
function(
    $,
    TabPreferences,
    TimelineViewNav,
    TimelineView
) {

    var SELECTORS = {
        VIEW_CHOICES: '[data-region="block-myoverview-view-choices"]',
        TIMELINE_VIEW: '[data-region="timeline-view"]'
    };

    /**
     * Listen for tab changes between the timeline and courses tab and show
     * the relevant nav controls at the top of the block.
     * 
     * @param {object} root The root element for the overview block.
     * @param {object} tabChoiceRoot Root element for the tab elements.
     */
    var registerTabChangeListener = function(root, tabChoiceRoot) {  
        tabChoiceRoot.on('shown.bs.tab', function(e) {
            var targetTab = $(e.target).attr('data-tabname');
            // Show/hide the relevant nav controls when the user changes tabs
            // between the timeline and courses view.
            root.find('[data-tab-content]').addClass('d-none hidden');
            root.find('[data-tab-content="' + targetTab + '"]').removeClass('d-none hidden');
        });
    };

    /**
     * Initialise all of the modules for the overview block.
     * 
     * @param {object} root The root element for the overview block.
     */
    var init = function(root) {
        root = $(root);
        var tabChoiceRoot = root.find(SELECTORS.VIEW_CHOICES);
        var timelineViewRoot = root.find(SELECTORS.TIMELINE_VIEW);

        // Remember the user's tab selection (timeline / courses).
        TabPreferences.registerEventListeners(tabChoiceRoot);
        // Initialise the timeline navigation elements.
        TimelineViewNav.init(root, timelineViewRoot);
        // Initialise the timeline view modules.
        TimelineView.init(timelineViewRoot);
        // Handle changes between the timeline / courses tabs.
        registerTabChangeListener(root, tabChoiceRoot);
    };

    return {
        init: init
    };
});
