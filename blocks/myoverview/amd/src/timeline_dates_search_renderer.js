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
 * @module     block_myoverview/timeline_dates_search_renderer
 * @package    block_myoverview
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/templates'], function($, Templates) {

    var SELECTORS = {
        TIMELINE_TAB_DATES: '[data-region="timeline-view-dates"]',
        EVENT_LIST_CONTAINER: '[data-region="event-list-container"]',
        SEARCH_RESULTS_CONTAINER: '[data-region="search-results-container"]',
        SEARCH_RESULTS_CONTENT: '[data-region="content"]',
        LOADING_ICON_CONTAINER: '[data-region="loading-icon-container"]',
    };

    var TEMPLATES = {
        EVENT_LIST_ITEMS: 'block_myoverview/event-list-items',
    };

    var SearchRenderer = function(root) {
        root = $(root);

        if (root.is(SELECTORS.TIMELINE_TAB_DATES)) {
            this.root = root;
        } else {
            this.root = root.find(SELECTORS.TIMELINE_TAB_DATES);
        }
    };

    SearchRenderer.prototype.getAllSearchRegions = function() {
        return this.root.find(SELECTORS.SEARCH_RESULTS_CONTAINER);
    };

    SearchRenderer.prototype.showAllSearchRegions = function() {
        this.getAllSearchRegions().removeClass('hidden');
    };

    SearchRenderer.prototype.hideAllSearchRegions = function() {
        this.getAllSearchRegions().addClass('hidden');
    };

    SearchRenderer.prototype.clearAllSearchResults = function() {
        this.getAllSearchRegions().find(SELECTORS.SEARCH_RESULTS_CONTENT).empty();
    };

    SearchRenderer.prototype.showAllContentRegions = function() {
        this.root.find(SELECTORS.EVENT_LIST_CONTAINER).removeClass('hidden');
    };

    SearchRenderer.prototype.hideAllContentRegions = function() {
        this.root.find(SELECTORS.EVENT_LIST_CONTAINER).addClass('hidden');
    };

    SearchRenderer.prototype.showLoadingIcons = function() {
        this.getAllSearchRegions().find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    SearchRenderer.prototype.hideLoadingIcons = function() {
        this.getAllSearchRegions().find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    SearchRenderer.prototype.searchStarted = function(searchingPromise, searchValue) {
        this.clearAllSearchResults();
        this.hideAllContentRegions();
        this.showAllSearchRegions();
        this.showLoadingIcons();

        searchingPromise.always(function() {
            this.hideLoadingIcons();
        }.bind(this));
    };

    SearchRenderer.prototype.searchStopped = function() {
        this.showAllContentRegions();
        this.hideAllSearchRegions();
        this.clearAllSearchResults();
        this.hideLoadingIcons();
    };

    SearchRenderer.prototype.render = function(resultsPromise) {
        resultsPromise.done(function(results) {
            var timelineDatesSearchResults = this.root.find(SELECTORS.SEARCH_RESULTS_CONTAINER +
                                                            ' ' + SELECTORS.SEARCH_RESULTS_CONTENT);

            Templates.render(TEMPLATES.EVENT_LIST_ITEMS, {events: results}).then(function(html, js) {
                Templates.appendNodeContents(timelineDatesSearchResults, html, js);
            });
        }.bind(this));
    };

    return SearchRenderer;
});
