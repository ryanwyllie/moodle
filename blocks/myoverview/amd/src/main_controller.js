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
 * Main javascript controller for the myoverview block.
 *
 * @module     block_myoverview/main_controller
 * @class      controller
 * @package    block_myoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/templates', 'core/notification', 'core/custom_interaction_events',
        'block_myoverview/events'],
     function($, Templates, Notification, CustomEvents, MyOverviewEvents) {

    var SELECTORS = {
        SHOW_TIMELINE_BUTTON: '[data-action="show-timeline"]',
        SHOW_COURSES_BUTTON: '[data-action="show-courses"]',
    };

    /**
     * Constructor for the controller.
     *
     * @param {object} root The root jQuery element for the controller
     */
    var Controller = function(root) {
        this.root = $(root);
        this.showTimelineButton = this.root.find(SELECTORS.SHOW_TIMELINE_BUTTON);
        this.showCoursesButton = this.root.find(SELECTORS.SHOW_COURSES_BUTTON);

        this.showTimelineView();
        this.registerEventListeners();
    };

    Controller.prototype.showTimelineView = function() {
        this.showCoursesButton.removeClass('active');
        this.showTimelineButton.addClass('active');
        this.root.trigger(MyOverviewEvents.CHANGE_VIEW, 'timeline-view');
        return this;
    };

    Controller.prototype.showCoursesView = function() {
        this.showTimelineButton.removeClass('active');
        this.showCoursesButton.addClass('active');
        this.root.trigger(MyOverviewEvents.CHANGE_VIEW, 'courses-view');
        return this;
    };

    Controller.prototype.registerEventListeners = function() {
        CustomEvents.define(this.root, [CustomEvents.events.activate]);

        this.root.on(CustomEvents.events.activate, SELECTORS.SHOW_TIMELINE_BUTTON, function(e, data) {
            this.showTimelineView();

            data.originalEvent.preventDefault();
        }.bind(this));

        this.root.on(CustomEvents.events.activate, SELECTORS.SHOW_COURSES_BUTTON, function(e, data) {
            this.showCoursesView();

            data.originalEvent.preventDefault();
        }.bind(this));
    };

    return Controller;
});
