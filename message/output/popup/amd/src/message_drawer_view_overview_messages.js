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
 * Controls the message drawer.
 *
 * @module     message_popup/message_drawer_view_overview
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    [
        'jquery',
        'core/notification',
        'core/templates',
        'core_message/message_repository',
        'message_popup/message_drawer_view_overview_section'
    ],
    function(
        $,
        Notification,
        Templates,
        MessageRepository,
        Section
    ) {

        var TEMPLATES = {
            MESSAGES_LIST: 'message_popup/message_drawer_messages_list'
        };

        var render = function(contentContainer, contacts) {
            return Templates.render(TEMPLATES.MESSAGES_LIST, {messages: contacts})
                .then(function(html) {
                    contentContainer.append(html);
                })
                .catch(Notification.exception);
        };

        var load = function(root, userId) {
            return MessageRepository.query({userid: userId})
                .then(function(result) {
                    return result.contacts;
                })
                .catch(Notification.exception);
        };

        var show = function(root) {
            root = $(root);
            Section.show(root, load, render);
        };

        return {
            show: show,
        };
    });
