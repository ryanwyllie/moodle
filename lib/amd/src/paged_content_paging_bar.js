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
 * Javascript to load and render the paging bar.
 *
 * @module     core/paging_bar
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/custom_interaction_events'],
        function($, CustomEvents) {

    var SELECTORS = {
        ROOT: '[data-region="paging-bar"]',
        PAGE_ITEM: '[data-region="page-item"]',
        ACTIVE_PAGE_ITEM: '[data-region="page-item"].active'
    };

    var EVENTS = {
        PAGE_SELECTED: 'core-paging-bar-page-selected',
    };

    /**
     * Get the page element by number.
     *
     * @param {object} root The root element.
     * @param {Number} pageNumber The page number.
     * @returns {*}
     */
    var getPageByNumber = function(root, pageNumber) {
        return root.find(SELECTORS.PAGE_ITEM + '[data-page-number="' + pageNumber + '"]');
    };

    /**
     * Get the page number.
     *
     * @param {object} root The root element.
     * @param {object} page The page.
     * @returns {*} the page number
     */
    var getPageNumber = function(root, page) {
        var pageNumber = page.attr('data-page-number');

        switch(pageNumber) {
            case 'first':
                pageNumber = 1;
                break;

            case 'last':
                pageNumber = parseInt(root.attr('data-page-count'), 10);
                break;

            case 'next':
                activePageNumber = getActivePageNumber(root);
                lastPage = parseInt(root.attr('data-page-count'), 10);
                if (activePageNumber && activePageNumber < lastPage) {
                    pageNumber = activePageNumber + 1;
                } else {
                    pageNumber = lastPage;
                }
                break;

            case 'previous':
                activePageNumber = getActivePageNumber(root);
                if (activePageNumber && activePageNumber > 1) {
                    pageNumber = activePageNumber - 1;
                } else {
                    pageNumber = 1;
                }
                break;
        }

        return parseInt(pageNumber, 10);
    };

    var getActivePageNumber = function(root) {
        var activePage = root.find(SELECTORS.ACTIVE_PAGE_ITEM);

        if (activePage.length) {
            return getPageNumber(root, activePage);
        } else {
            return null;
        }
    };

    /**
     * Register event listeners for the module.
     * @param {object} root The root element.
     */
    var init = function(root) {
        root = $(root);
        CustomEvents.define(root, [
            CustomEvents.events.activate
        ]);

        root.on(CustomEvents.events.activate, SELECTORS.PAGE_ITEM, function(e, data) {
            var page = $(e.target).closest(SELECTORS.PAGE_ITEM);
            var activePage = root.find(SELECTORS.ACTIVE_PAGE_ITEM);
            var pageNumber = getPageNumber(root, page);
            var isSamePage = pageNumber == getPageNumber(root, activePage);

            if (!isSamePage) {
                root.find(SELECTORS.PAGE_ITEM).removeClass('active');
                getPageByNumber(root, pageNumber).addClass('active');
            }

            root.trigger(EVENTS.PAGE_SELECTED, [{
                pageNumber: pageNumber,
                isSamePage: isSamePage,
            }]);

            data.originalEvent.preventDefault();
            data.originalEvent.stopPropagation();
        });
    };

    return {
        init: init,
        events: EVENTS,
        getActivePageNumber: getActivePageNumber,
        rootSelector: SELECTORS.ROOT,
    };
});
