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
 * Javascript to load and render the list of calendar events for a
 * given day range.
 *
 * @module     block_myoverview/event_list
 * @package    block_myoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    ['core/mustache_component'],
    function(MustacheComponent)
{
    var BlockMyOverviewComponent = function(rootElement, templateName, templateContext) {
        MustacheComponent.call(this, rootElement, templateName, templateContext);
    };

    BlockMyOverviewComponent.prototype = Object.create(MustacheComponent.prototype);
    BlockMyOverviewComponent.prototype.constructor = MustacheComponent;

    BlockMyOverviewComponent.prototype.registerEventListeners = function() {
        root = $(this.rootElement);

        root.on('click', '[data-tabname]', function(e) {
            var target = $(e.target);
            if (target.attr('data-tabname') == 'timeline') {
                this.state.viewingtimeline = true;
                this.state.viewingcourses = false;
            } else {
                this.state.viewingtimeline = false;
                this.state.viewingcourses = true;
            }
        }.bind(this));
    };

    return BlockMyOverviewComponent;
});
