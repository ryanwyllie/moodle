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
 * Test module for autocomplete ajax test page.
 *
 * @module     core/test
 * @class      test
 * @package    core
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax'], function(ajax) {


    return /** @alias module:core/test */ {
        // Public variables and functions.
        processResults: function(selector, data) {
            // Mangle the results into an array of objects for "Select2".
            var results = [], i = 0;
            for (i = 0; i < data.length; i++) {
                results[i] = { value: data[i], label: data[i] };
            }
            return results;
        },

        transport: function(selector, query, success, failure) {
            var promise = ajax.call([{
                methodname: 'tool_templatelibrary_list_templates', args: { search: query }
            }]);

            promise[0].done(success);
            promise[0].fail(failure);

            return promise;
        }
    };
});
