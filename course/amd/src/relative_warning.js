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
 * AMD module responsible for rendering the warning message for the relative dates form element.
 *
 * @module     core_course/relative_warning
 * @package    core
 * @copyright  2019 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/templates', 'core/notification'],
    function($, Templates, Notification) {
        return /** @alias module:core_course/relative_warning */ {
            /**
             * Renders the warning message for the relative dates mode field.
             *
             * @method show
             */
            show: function() {
                Templates.render('core_course/relative_warning', {}).then(function(html) {
                    // Insert the warning message after the relative dates mode select box.
                    var relativeDates = $('[name=relativedatesmode]');
                    relativeDates.after(html);
                    return;
                }).fail(Notification.exception);
            },
        };
    }
);
