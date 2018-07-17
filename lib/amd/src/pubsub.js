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
 * A simple Javascript PubSub implementation.
 *
 * @module     core/pubsub
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    var PubSub = function() {
        this.events = {};
    };

    /**
     * Subscribe to an event.
     *
     * @param {string} eventName The name of the event to subscribe to.
     * @param {function} callback The callback function to run when eventName occurs.
     */
    PubSub.prototype.subscribe = function(eventName, callback) {
        this.events[eventName] = this.events[eventName] || [];
        this.events[eventName].push(callback);
    };

    /**
     * Unsubscribe from an event.
     *
     * @param {string} eventName The name of the event to unsubscribe from.
     * @param {function} callback The callback to unsubscribe.
     */
    PubSub.prototype.unsubscribe = function(eventName, callback) {
        if (this.events[eventName]) {
            for (var i = 0; i < this.events[eventName].length; i++) {
                if (this.events[eventName][i] === callback) {
                    this.events[eventName].splice(i, 1);
                    break;
                }
            }
        }
    };

    /**
     * Publish an event to all subscribers.
     *
     * @param {string} eventName The name of the event to publish.
     * @param {any} data The data to provide to the subscribed callbacks.
     */
    PubSub.prototype.publish = function(eventName, data) {
        if (this.events[eventName]) {
            this.events[eventName].forEach(function(callback) {
                callback(data);
            });
        }
    };

    return PubSub;
});
