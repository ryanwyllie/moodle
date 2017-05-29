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
 * A module to index the overview block for the search engine.
 *
 * @module     block_myoverview/client_side_course_search_indexer
 * @package    block_myoverview
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification'],
        function($, Notification) {

    var SELECTORS = {
        COURSES_TAB: '[data-region="courses-view"]',
        COURSE_NAME: '[data-region="course-name"]',
        COURSE_DESCRIPTION: '[data-region="course-description"]',
    };

    var TYPES = {
        COURSES_TAB: 'courses-tab',
    };

    var guid = function() {
        function s4() {
            return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
        }

        return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
        s4() + '-' + s4() + s4() + s4();
    };

    var Indexer = function(searchIndex, root) {
        this.searchIndex = searchIndex;
        this.root = $(root);
        this.guid = guid();
        this.idCount = 0;

        this.indexCoursesTab();
    };

    Indexer.prototype.indexCoursesTab = function() {
        this.root.find(SELECTORS.COURSES_TAB)
            .find(SELECTORS.COURSE_INFO_CONTAINER)
            .each(function (index, element) {

            element = $(element);
            this.setElementId(element);

            this.searchIndex.addRecord({
                type: TYPES.COURSES_TAB,
                selectors: {
                    id: '#' + element.attr('id'),
                    container: SELECTORS.COURSES_TAB,
                },
                event: {
                    name: "",
                    date: "",
                },
                course: {
                    name: element.find(SELECTORS.COURSE_NAME).text().trim(),
                    description: element.find(SELECTORS.COURSE_DESCRIPTION).text().trim(),
                }
            });
        }.bind(this));
    };

    Indexer.prototype.setElementId = function(element) {
        element = $(element);
        var id = element.attr('id');

        if (typeof id == 'undefined') {
            id = this.guid + '-' + this.idCount++;
            element.attr('id', id);
        }
    };

    return Indexer;
});
