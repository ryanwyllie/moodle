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
 * Controller for the courses view in the myoverview block.
 *
 * @module     block_myoverview/courses_view_controller
 * @class      controller
 * @package    block_myoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/templates', 'core/notification', 'core/custom_interaction_events',
        'block_myoverview/view_controller', 'block_myoverview/events'],
     function($, Templates, Notification, CustomEvents, ViewController, MyOverviewEvents) {

    var SELECTORS = {
    };

    /**
     * Constructor for the controller.
     *
     * @param {object} root The root jQuery element for the controller
     */
    var Controller = function(root) {
        ViewController.call(this, root);
    };

    Controller.prototype = Object.create(ViewController.prototype);
    Controller.prototype.constructor = ViewController;

    Controller.prototype.getViewName = function() {
        return 'courses-view';
    };

    return Controller;
});
