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
 * @module     core/paged_content
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'core/paged_content_pages',
    'core/paged_content_paging_bar',
    'core/paged_content_paging_dropdown',
],
function(
    Pages,
    PagingBar,
    Dropdown
) {

    var init = function(root, renderPagesContentCallback) {
        var pagesContainer = root.find(Pages.rootSelector);
        var pagingBarContainer = root.find(PagingBar.rootSelector);
        var dropdownContainer = root.find(Dropdown.rootSelector);

        Pages.init(pagesContainer, root, renderPagesContentCallback);

        if (pagingBarContainer.length) {
            PagingBar.init(pagingBarContainer, root);
        }

        if (dropdownContainer.length) {
            Dropdown.init(dropdownContainer, root);
        }
    };

    return {
        init: init,
        rootSelector: '[data-region="paged-content-container"]'
    };
});
