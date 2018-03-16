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
 * Paging content module.
 *
 * @module     core/paging_content
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/templates', 'core/paging_bar'],
        function($, Templates, PagingBar) {

    var SELECTORS = {
        ROOT: '[data-region="paging-content"]',
        PAGE_REGION: '[data-region="paging-content-item"]',
        ACTIVE_PAGE_REGION: '[data-region="paging-content-item"].active'
    };

    var TEMPLATES = {
        PAGING_CONTENT_ITEM: 'core/paging_content_item',
        LOADING: 'core/overlay_loading'
    };

    /**
     * Find a page by the number.
     *
     * @param {object} root The root element.
     * @param {Number} pageNumber The number of the page to be found.
     * @returns {*} Page root
     */
    var findPage = function(root, pageNumber) {
        return root.find('[data-page="' + pageNumber + '"]');
    };

    /**
     * Find the active page.
     *
     * @param {object} root The root element.
     * @returns {*} Page root
     */
    var findActivePage = function(root) {
        return root.find(SELECTORS.ACTIVE_PAGE_REGION);
    };

    var startLoading = function(root) {
        var deferred = $.Deferred();
        var activePage = findActivePage(root);

        if (!activePage.length) {
            return deferred;
        }

        Templates.render(TEMPLATES.LOADING, {})
            .then(function(html, js) {
                var loadingSpinner = $(html);
                activePage.css('position', 'relative');
                loadingSpinner.appendTo(activePage);

                deferred.then(function() {
                    // Remove the loading spinner when our deferred is resolved
                    // by the calling code.
                    loadingSpinner.remove();
                    activePage.css('position', '');
                });
            })

        return deferred;
    };

    /**
     * Make a page visible.
     *
     * @param {object} root The root element.
     * @param {Number} pageNumber The number of the page to be visible.
     * @param {function} renderPageContentCallback A callback function to render
     *                                             the page content if it doesn't
     *                                             already exist.
     */
    var showPage = function(root, pageNumber, renderPageContentCallback) {
        var existingPage = findPage(pageNumber);

        if (existingPage.length) {
            root.find(SELECTORS.PAGE_REGION).addClass('hidden');
            existingPage.removeClass('hidden');
        } else if (typeof renderPageContentCallback == 'function') {
            var loadingPromise = startLoading(root);
            // Ask the callback to give us the rendered content.
            renderPageContentCallback(pageNumber);
                .then(function(html, js) {
                    return Templates.render(TEMPLATES.PAGING_CONTENT_ITEM, {
                        active: true,
                        page: pageNumber,
                        content: html
                    })
                    .then(function(html) {
                        root.find(SELECTORS.PAGE_REGION).addClass('hidden');
                        Templates.appendNodeContents(root, html, js);
                    });
                })
                .always(function() {
                    loadingPromise.resolve();
                });
        }
    };

    /**
     * Event listeners.
     */
    PagingContent.prototype.registerEventListeners = function(root, pagingBarElement, renderPageContentCallback) {
        root = $(root);
        pagingBarElement = $(pagingBarElement);

        pagingBar.on(PagingBar.events.PAGE_SELECTED, function(e, data) {
            if (!data.isSamePage) {
                showPage(root, data.pageNumber, renderPageContentCallback);
            }
        });
    };

    return {
        registerEventListeners: registerEventListeners,
        rootSelector: SELECTORS.ROOT
    };
});
