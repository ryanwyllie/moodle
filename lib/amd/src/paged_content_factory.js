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
 * Factory to create a paged content widget.
 *
 * @module     core/paged_content_factory
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/templates',
    'core/paged_content_paging_bar',
    'core/paged_content_pages',
],
function(
    $,
    Templates,
    PagingBar,
    PagingContent
) {
    var TEMPLATES = {
        PAGED_CONTENT: 'core/paged_content'
    };

    var createFromAjax = function(numberOfPages, renderPageContentCallback) {
        var deferred = $.Deferred();
        var templateContext = {
            pagingbar: {
                "pagecount": numberOfPages,
                "previous": {},
                "next": {},
                "pages": []
            },
            skipjs: true
        };

        for (var i = 1; i <= numberOfPages; i++) {
            var page = {
                number: i,
                page: "" + i,
            };

            if (i == 1) {
                page.active = true;
            }

            templateContext.pagingbar.pages.push(page);
        }

        Templates.render(TEMPLATES.PAGED_CONTENT, templateContext)
            .then(function(html, js) {
                html = $(html);

                var pagingBar = html.find(PagingBar.rootSelector);
                var pagingContent = html.find(PagingContent.rootSelector);
                PagingContent.init(pagingContent, pagingBar, renderPageContentCallback);

                deferred.resolve(html, js);
            });

        return deferred;
    };

    var createFromStaticList = function(contentItems, itemsPerPage, renderContentCallback) {
        var numberOfItems = contentItems.length;
        var numberOfPages = 1;

        if (numberOfItems > 0) {
            var partial = numberOfItems % itemsPerPage;

            if (partial) {
                numberOfItems -= partial;
                numberOfPages = (numberOfItems / itemsPerPage) + 1;
            } else {
                numberOfPages = numberOfItems / itemsPerPage;
            }
        }

        return createFromAjax(numberOfPages, function(pageNumber) {
            var begin = (pageNumber - 1) * itemsPerPage;
            var end = begin + itemsPerPage;
            var contentToRender = contentItems.slice(begin, end);

            return renderContentCallback(contentToRender);
        });
    };

    return {
        createFromAjax: createFromAjax,
        createFromStaticList: createFromStaticList
    };
});
