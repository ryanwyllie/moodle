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
 * Controller for the timeline dates view in the myoverview block.
 *
 * @module     block_myoverview/timeline_dates_view_controller
 * @class      controller
 * @package    block_myoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/custom_interaction_events', 'block_myoverview/view_controller',
        'block_myoverview/events', 'block_myoverview/calendar_events_api'],
    function($, CustomEvents, ViewController, MyOverviewEvents, CalendarEventsAPI) {

    var SELECTORS = {
        VIEW_MORE_BUTTON: '[data-action="view-more"]',
        NEXT_7_DAYS_CONTAINER: '[data-region="next-7-days"]',
        NEXT_30_DAYS_CONTAINER: '[data-region="next-30-days"]',
        EVENT_LIST_CONTAINER: '[data-region="event-list-container"]',
    };

    /**
     * Constructor for the controller.
     *
     * @param {object} root The root jQuery element for the controller
     */
    var Controller = function(root) {
        ViewController.call(this, root);

        this.loadMore();
        this.registerEventListeners();
    };

    Controller.prototype = Object.create(ViewController.prototype);
    Controller.prototype.constructor = ViewController;

    Controller.prototype.getViewName = function() {
        return 'timeline-view-dates';
    };

    Controller.prototype.getViewEvent = function() {
        return MyOverviewEvents.TIMELINE_CHANGE_VIEW;
    };

    Controller.prototype.getNext7DaysContainer = function() {
        if (!this.next7DaysContainer) {
            this.next7DaysContainer = this.root.find(SELECTORS.NEXT_7_DAYS_CONTAINER);
        }

        return this.next7DaysContainer;
    };

    Controller.prototype.getNext30DaysContainer = function() {
        if (!this.next30DaysContainer) {
            this.next30DaysContainer = this.root.find(SELECTORS.NEXT_30_DAYS_CONTAINER);
        }

        return this.next30DaysContainer;
    };

    Controller.prototype.loadMore = function() {
        this.loadMoreForNext7Days();
        this.loadMoreForNext30Days();
    };

    Controller.prototype.loadMoreForNext7Days = function() {
        this.getNext7DaysContainer()
            .find(SELECTORS.EVENT_LIST_CONTAINER)
            .trigger(MyOverviewEvents.LOAD_MORE_EVENTS);
    };


    Controller.prototype.loadMoreForNext30Days = function() {
        this.getNext30DaysContainer()
            .find(SELECTORS.EVENT_LIST_CONTAINER)
            .trigger(MyOverviewEvents.LOAD_MORE_EVENTS);
    };

    Controller.prototype.registerEventListeners = function() {
        // Call parent event listeners.
        ViewController.prototype.registerEventListeners.call(this);

        CustomEvents.define(this.root, [CustomEvents.events.activate]);

        this.root.on(CustomEvents.events.activate, SELECTORS.VIEW_MORE_BUTTON, function() {
            this.loadMore();
        }.bind(this));
    };

    return Controller;
});
