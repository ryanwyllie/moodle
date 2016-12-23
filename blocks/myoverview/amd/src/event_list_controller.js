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
 * Controller for handling loading calendar events and rendering them in a list
 * for the myoverview block.
 *
 * @module     block_myoverview/event_list_controller
 * @class      controller
 * @package    block_myoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/templates', 'core/notification', 'block_myoverview/events', 'block_myoverview/calendar_events_api'],
    function($, Templates, Notification, MyOverviewEvents, CalendarEventsAPI) {

    var SELECTORS = {
        EVENT_LIST: '[data-region="event-list"]',
    };

    var TEMPLATES = {
        CALENDAR_EVENT_LIST_ITEMS: 'block_myoverview/event-list-items',
    };

    /**
     * Constructor for the controller.
     *
     * @param {object} root The root jQuery element for the controller
     */
    var Controller = function(root) {
        this.root = $(root);
        this.limit = 10;
        this.offset = 0;
        this.isLoading = false;
        this.hasLoadedAll = false;
        this.startDay = root.attr('data-start-day');
        this.endDay = root.attr('data-end-day');
        this.eventList = root.find(SELECTORS.EVENT_LIST);

        this.registerEventListeners();
    };

    Controller.prototype.loadMore = function() {
        if (this.hasLoadedAll || this.isLoading) {
            return $.Deferred().resolve();
        }

        this.root.addClass('loading');
        this.isLoading = true;

        var promise = CalendarEventsAPI.query_for_user_by_days(this.startDay, this.endDay, this.limit, this.offset);

        promise.then(function(calendarEvents) {
            if (calendarEvents && calendarEvents.length) {
                this.offset += this.limit;
            } else {
                this.hasLoadedAll = true;
            }
        }.bind(this));

        promise.fail(Notification.exception);

        promise.always(function() {
            this.root.removeClass('loading');
            this.isLoading = false;
        }.bind(this));

        return this.renderCalendarEvents(promise);
    };

    Controller.prototype.renderCalendarEvents = function(promise) {
        promise.then(function(calendarEvents) {
            if (calendarEvents && calendarEvents.length) {
                var context = {
                    events: calendarEvents
                };

                return Templates.render(TEMPLATES.CALENDAR_EVENT_LIST_ITEMS, context).done(function(html, js) {
                    Templates.appendNodeContents(this.eventList, html, js);
                }.bind(this));
            }
        }.bind(this));

        return promise;
    };

    Controller.prototype.registerEventListeners = function() {
        this.root.on(MyOverviewEvents.LOAD_MORE_EVENTS, function(e) {
            this.loadMore();

            e.stopPropagation();
            e.stopImmediatePropagation();
        }.bind(this));
    };

    return Controller;
});
