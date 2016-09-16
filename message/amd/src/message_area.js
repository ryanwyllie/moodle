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
 * This module instantiates the functionality of the messaging area.
 *
 * @module     core_message/message_area
 * @package    core_message
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core_message/message_area_contacts', 'core_message/message_area_messages',
        'core_message/message_area_profile', 'core_message/message_area_tabs', 'core_message/message_area_search'],
    function($, Contacts, Messages, Profile, Tabs, Search) {

        /**
         * Messagearea class.
         *
         * @param {String} selector The selector for the page region containing the message area.
         */
        function Messagearea(selector) {
            this.node = $(selector);
            this._init();
        }

        /** @type {Object} The list of selectors for the message area. */
        Messagearea.prototype.SELECTORS = {
            ACTIVECONTACTSTAB: "[data-region='contacts-area'] [role='tab'][aria-selected='true']",
            BLOCKTIME: "[data-region='blocktime']",
            CANCELDELETEMESSAGES: "[data-action='cancel-delete-messages']",
            CONTACT: "[data-region='contact']",
            CONTACTICONBLOCKED: "[data-region='contact-icon-blocked']",
            CONTACTICONONLINE: "[data-region='contact-icon-online']",
            CONTACTS: "[data-region='contacts'][data-region-content='contacts']",
            CONTACTSAREA: "[data-region='contacts-area']",
            CONTACTSPANELS: "[data-region='contacts']",
            CONVERSATIONS: "[data-region='contacts'][data-region-content='conversations']",
            DELETEALLMESSAGES: "[data-action='delete-all-messages']",
            DELETEMESSAGES: "[data-action='delete-messages']",
            DELETEMESSAGECHECKBOX: "[data-region='delete-message-checkbox']",
            DELETESEARCHFILTER: "[data-action='search-filter-delete']",
            LASTMESSAGEAREA: "[data-region='last-message-area']",
            LASTMESSAGEUSER: "[data-region='last-message-user']",
            LASTMESSAGETEXT: "[data-region='last-message-text']",
            LOADINGICON: '.loading-icon',
            MENU: "[data-region='menu']",
            MESSAGE: "[data-region='message']",
            MESSAGES: "[data-region='messages']",
            MESSAGESAREA: "[data-region='messages-area']",
            MESSAGESHEADERACTIONS: "[data-region='messages-header-actions']",
            MESSAGERESPONSE: "[data-region='response']",
            MESSAGETEXT: "[data-region='message-text']",
            MESSAGINGAREA: "[data-region='messaging-area']",
            NOCONTACTS: "[data-region=no-contacts]",
            PROFILE: "[data-region='profile']",
            PROFILEADDCONTACT: "[data-action='profile-add-contact']",
            PROFILEBLOCKCONTACT: "[data-action='profile-block-contact']",
            PROFILEREMOVECONTACT: "[data-action='profile-remove-contact']",
            PROFILESENDMESSAGE: "[data-action='profile-send-message']",
            PROFILEUNBLOCKCONTACT: "[data-action='profile-unblock-contact']",
            PROFILEVIEW: "[data-action='profile-view']",
            SEARCHBOX: "[data-region='search-box']",
            SEARCHFILTER: "[data-region='search-filter']",
            SEARCHFILTERAREA: "[data-region='search-filter-area']",
            SEARCHPEOPLEINCOURSE : "[data-action='search-people-in-course']",
            SEARCHRESULTSAREA: "[data-region='search-results-area']",
            SEARCHTEXTAREA: "[data-region='search-text-area']",
            SELECTEDVIEWPROFILE: "[data-action='view-contact-profile'].selected",
            SELECTEDVIEWCONVERSATION: "[data-action='view-contact-msg'].selected",
            SENDMESSAGE: "[data-action='send-message']",
            SENDMESSAGETEXT: "[data-region='send-message-txt']",
            SHOWCONTACTS: "[data-action='show-contacts']",
            SHOWMESSAGES: "[data-action='show-messages']",
            STARTDELETEMESSAGES: "[data-action='start-delete-messages']",
            VIEWCONTACTS: "[data-action='contacts-view']",
            VIEWCONVERSATION: "[data-action='view-contact-msg']",
            VIEWCONVERSATIONS: "[data-action='conversations-view']",
            VIEWPROFILE: "[data-action='view-contact-profile']"
        };

        /** @type {Object} The list of events triggered in the message area. */
        Messagearea.prototype.EVENTS = {
            CANCELDELETEMESSAGES: 'cancel-delete-messages',
            CHOOSEMESSAGESTODELETE: 'choose-messages-to-delete',
            CONTACTADDED: 'contact-added',
            CONTACTBLOCKED: 'contact-blocked',
            CONTACTREMOVED: 'contact-removed',
            CONTACTSELECTED: 'contact-selected',
            CONTACTSSELECTED: 'contacts-selected',
            CONTACTUNBLOCKED: 'contact-unblocked',
            CONVERSATIONDELETED: 'conversation-deleted',
            CONVERSATIONSELECTED: 'conversation-selected',
            CONVERSATIONSSELECTED: 'conversations-selected',
            MESSAGESDELETED: 'messages-deleted',
            MESSAGESEARCHCANCELED: 'message-search-canceled',
            MESSAGESENT: 'message-sent',
            PEOPLESEARCHCANCELED: 'people-search-canceled',
            SENDMESSAGE: 'message-send'
        };

        /** @type {jQuery} The jQuery node for the page region containing the message area. */
        Messagearea.prototype.node = null;

        /**
         * Initialise the other objects we require.
         */
        Messagearea.prototype._init = function() {
            new Contacts(this);
            new Messages(this);
            new Profile(this);
            new Tabs(this);
            new Search(this);
        };

        /**
         * Handles adding a delegate event to the messaging area node.
         *
         * @param {String} action The action we are listening for
         * @param {String} selector The selector for the page we are assigning the action to
         * @param {Function} callable The function to call when the event happens
         */
        Messagearea.prototype.onDelegateEvent = function(action, selector, callable) {
            this.node.on(action, selector, callable);
        };

        /**
         * Handles adding a custom event to the messaging area node.
         *
         * @param {String} action The action we are listening for
         * @param {Function} callable The function to call when the event happens
         */
        Messagearea.prototype.onCustomEvent = function(action, callable) {
            this.node.on(action, callable);
        };

        /**
         * Handles triggering an event on the messaging area node.
         *
         * @param {String} event The selector for the page region containing the message area
         * @param {Object=} data The data to pass when we trigger the event
         */
        Messagearea.prototype.trigger = function(event, data) {
            if (typeof data == 'undefined') {
                data = '';
            }
            this.node.trigger(event, data);
        };

        /**
         * Handles finding a node in the messaging area.
         *
         * @param {String} selector The selector for the node we are looking for
         * @returns {jQuery} The node
         */
        Messagearea.prototype.find = function(selector) {
            return this.node.find(selector);
        };

        /**
         * Returns the ID of the user whose message area we are viewing.
         *
         * @returns {int} The user id
         */
        Messagearea.prototype.getCurrentUserId = function() {
            return this.node.data('userid');
        };

        return Messagearea;
    }
);
