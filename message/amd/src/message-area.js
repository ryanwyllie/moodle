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
 * This module instantiates the functionality of the messaging area.
 *
 * @module     core_message/message-area
 * @package    core_message
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core_message/actions', 'core_message/contacts', 'core_message/messages',
        'core_message/profile', 'core_message/tabs'],
    function($, Actions, Contacts, Messages, Profile, Tabs) {

        /**
         * Messagearea class.
         *
         * @param {String} selector The selector for the page region containing the message area.
         */
        function Messagearea(selector) {
            this.node = $(selector);
            this._init();
        }

        /** @type {jQuery} The jQuery node for the page region containing the message area. */
        Messagearea.prototype.node = null;

        /**
         * Initialise the other objects we require.
         */
        Messagearea.prototype._init = function() {
            new Contacts(this);
            new Messages(this);
            new Profile(this);
            new Tabs(this);
        };

        /**
         * Handles adding a delegate event to the messaging area node.
         *
         * @param {String} action The action we are listening for
         * @param {String} selector The selector for the page we are assigning the action to
         * @param {Function} callable The function to call when the event happens
         */
        Messagearea.prototype.onDelegateEvent = function(action, selector, callable) {
            this.node.on(action, selector, callable);
        };

        /**
         * Handles adding a custom event to the messaging area node.
         *
         * @param {String} action The action we are listening for
         * @param {Function} callable The function to call when the event happens
         */
        Messagearea.prototype.onCustomEvent = function(action, callable) {
            this.node.on(action, callable);
        };

        /**
         * Handles triggering an event on the messaging area node.
         *
         * @param {String} event The selector for the page region containing the message area
         * @param {Object=} data The data to pass when we trigger the event
         */
        Messagearea.prototype.trigger = function(event, data) {
            if (typeof data == 'undefined') {
                data = '';
            }
            this.node.trigger(event, data);
        };

        /**
         * Handles finding a node in the messaging area.
         *
         * @param {String} selector The selector for the node we are looking for
         * @returns {jQuery} The node
         */
        Messagearea.prototype.find = function(selector) {
            return this.node.find(selector);
        };

        /**
         * Returns the ID of the logged in user.
         *
         * @returns {int} The user id
         */
        Messagearea.prototype.getLoggedInUserId = function() {
            return this.node.data('loggedinuserid');
        };

        /**
         * Returns the ID of the user whose message area we are viewing.
         *
         * @returns {int} The user id
         */
        Messagearea.prototype.getCurrentUserId = function() {
            return this.node.data('userid');
        };

        return Messagearea;
    }
);
