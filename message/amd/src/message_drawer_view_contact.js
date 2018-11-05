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
 * Controls the contact page in the message drawer.
 *
 * @module     core_message/message_drawer_view_contact
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/templates'
],
function(
    $,
    Templates
) {

    var SELECTORS = {
        CONTENT_CONTAINER: '[data-region="content-container"]'
    };

    var TEMPLATES = {
        CONTENT: 'core_message/message_drawer_view_contact_body_content'
    };

    /**
     * Get the content container of the contact view container.
     *
     * @param {Object} root Contact container element.
     */
    var getContentContainer = function(root) {
        return root.find(SELECTORS.CONTENT_CONTAINER);
    };

    /**
     * Render the contact profile in the content container.
     *
     * @param {Object} root Contact container element.
     * @param {Object} profile Contact profile details.
     */
    var render = function(root, profile) {
        return Templates.render(TEMPLATES.CONTENT, profile)
            .then(function(html) {
                getContentContainer(root).append(html);
            });
    };

    /**
     * Setup the contact page.
     *
     * @param {Object} root Contact container element.
     * @param {Number} contact The contact object.
     */
    var show = function(root, contact) {
        root = $(root);

        getContentContainer(root).empty();
        return render(root, contact);
    };

    return {
        show: show,
    };
});
