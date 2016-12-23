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
 * Base controller for the myoverview block views.
 *
 * @module     block_myoverview/view_controller
 * @class      controller
 * @package    block_myoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'block_myoverview/events'], function($, MyOverviewEvents) {

    var SELECTORS = {
        BLOCK_ROOT: '[data-region="myoverview"]'
    };

    /**
     * Constructor for the controller.
     *
     * @param {object} root The root jQuery element for the controller
     */
    var Controller = function(root) {
        this.root = $(root);
        this.blockRoot = this.root.closest(SELECTORS.BLOCK_ROOT);

        this.registerEventListeners();
    };

    Controller.prototype.getViewName = function() {
        return 'myoverview';
    };

    Controller.prototype.getViewEvent = function() {
        return MyOverviewEvents.CHANGE_VIEW;
    };

    Controller.prototype.show = function() {
        this.root.removeClass('hidden');
        return this;
    };

    Controller.prototype.hide = function() {
        this.root.addClass('hidden');
        return this;
    };

    Controller.prototype.registerEventListeners = function() {
        this.blockRoot.on(this.getViewEvent(), function(e, view) {
            if (view == this.getViewName()) {
                this.show();
            } else {
                this.hide();
            }
        }.bind(this));
    };

    return Controller;
});
