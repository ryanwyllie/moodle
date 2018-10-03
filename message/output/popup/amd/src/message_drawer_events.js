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
 * @module     message_popup/message_drawer
 * @class      notification_area_content_area
 * @package    message
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([],function() {
    return {
        CONTACT_BLOCKED: 'message-drawer-contact-blocked',
        CONTACT_UNBLOCKED: 'message-drawer-contact-unblocked',
        CONTACT_ADDED: 'message-drawer-contact-added',
        CONTACT_REMOVED: 'message-drawer-contact-removed',
        CONVERSATION_NEW_LAST_MESSAGE: 'message-drawer-conversation-new-last-message',
        CONVERSATION_DELETED: 'message-drawer-conversation-deleted',
        CONVERSATION_READ: 'message-drawer-conversation-read',
        ROUTE_CHANGE: 'message-drawer-route-change'
    };
});
