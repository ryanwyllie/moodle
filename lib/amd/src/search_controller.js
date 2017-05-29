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
 * Contain the logic for search.
 *
 * @module     core/search_controller
 * @package    core
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    var DEFAULT_TIMEOUT = 400;

    /**
     * Constructor for the search controller.
     *
     * @param {object} input The jQuery element to listen for input
     * @param {object} keys The keys to search for in the index records
     * @param {object} options The search configuration options
     */
    var SearchController = function(input, searchEngines, searchRenderers, timeout) {
        this.input = $(input);
        this.engines = [];
        this.renderers = [];
        var timeoutId;

        if (typeof searchEngines != 'undefined') {
            this.engines = searchEngines;
        }

        if (typeof searchRenderers != 'undefined') {
            this.renderers = searchRenderers;
        }

        if (typeof timeout == 'undefined') {
            timeout = DEFAULT_TIMEOUT;
        }

        this.input.keyup(function(e) {
            // TODO: Ignore meta only keys.

            var searchValue = this.input.val().trim();
            var searching = $.Deferred();

            if (searchValue == '') {
                this.renderers.forEach(function(renderer) {
                    renderer.searchStopped();
                });

                return;
            } else {
                this.renderers.forEach(function(renderer) {
                    renderer.searchStarted(searching.promise(), searchValue);
                });
            }

            if (typeof timeoutId != 'undefined') {
                clearTimeout(timeoutId);
            }

            timeoutId = setTimeout(function() {
                var promises = [];

                this.engines.forEach(function(engine) {
                    var resultsPromise = engine.search(searchValue);

                    this.renderers.forEach(function(renderer) {
                        renderer.render(resultsPromise);
                    });

                    promises.push(resultsPromise);
                }.bind(this));

                $.when.apply($, promises).done(function() {
                    searching.resolve();
                });
            }.bind(this), timeout);
        }.bind(this));
    };

    return SearchController;
});
