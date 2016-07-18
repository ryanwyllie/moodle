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
 * This module handles the contacts area of the messaging area.
 *
 * @module     core_message/contacts
 * @package    core_message
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification'],
    function($, ajax, templates, notification) {

        /**
         * Contacts class.
         *
         * @param {Messagearea} messageArea The messaging area object.
         */
        function Contacts(messageArea) {
            this.messageArea = messageArea;
            this._init();
        }

        /** @type {Messagearea} The messaging area object. */
        Contacts.prototype.messageArea = null;

        /**
         * Initialise the event listeners.
         *
         * @private
         */
        Contacts.prototype._init = function() {
            this.messageArea.onCustomEvent('conversations-selected', this._viewConversations.bind(this));
            this.messageArea.onCustomEvent('contacts-selected', this._viewContacts.bind(this));
            this.messageArea.onCustomEvent('messages-deleted', this._deleteConversations.bind(this));
            this.messageArea.onCustomEvent('message-send', this._viewConversationsWithUserSelected.bind(this));
            this.messageArea.onCustomEvent('message-sent', this._viewConversationsWithUserSelected.bind(this));
            this.messageArea.onCustomEvent('contact-removed', this._removeContact.bind(this));
            this.messageArea.onCustomEvent('contact-added', this._viewContacts.bind(this));
            this.messageArea.onCustomEvent('choose-messages-to-delete', this._chooseConversationsToDelete.bind(this));
            this.messageArea.onCustomEvent('cancel-messages-deleted', this._cancelConversationsToDelete.bind(this));
            this.messageArea.onDelegateEvent('click', "[data-action='view-contact-msg']", this._viewConversation.bind(this));
            this.messageArea.onDelegateEvent('click', "[data-action='view-contact-profile']", this._viewContact.bind(this));
        };

        /**
         * Handles viewing the list of conversations.
         *
         * @private
         */
        Contacts.prototype._viewConversations = function() {
            return this._loadContactArea('core_message_data_for_messagearea_conversations');
        };

        /**
         * Handles viewing the list of conversations and selecting a user.
         *
         * @param {Event} event The message sent event
         * @param {int} userid The id of the user who the message was sent to
         * @returns {Promise} The promise resolved when the message sent after actions are done
         * @private
         */
        Contacts.prototype._viewConversationsWithUserSelected = function(event, userid) {
            return this._viewConversations().then(function() {
                this._setSelectedUser(userid);
            }.bind(this));
        };

        /**
         * Handles viewing the list of contacts.
         *
         * @private
         */
        Contacts.prototype._viewContacts = function() {
            return this._loadContactArea('core_message_data_for_messagearea_contacts');
        };

        /**
         * Handles viewing a particular conversation.
         *
         * @param {Event} event
         * @private
         */
        Contacts.prototype._viewConversation = function(event) {
            // We do not want to select the conversation if we chose the conversation to delete.
            var node = $(event.target);
            if (node.parent().data('region') == 'delete-conversation-checkbox') {
                return;
            }

            var userid = $(event.currentTarget).data('userid');
            this._setSelectedUser(userid);
            this.messageArea.trigger('conversation-selected', userid);
        };

        /**
         * Handles viewing a particular contact.
         *
         * @param {Event} event
         * @private
         */
        Contacts.prototype._viewContact = function(event) {
            var userid = $(event.currentTarget).data('userid');
            this._setSelectedUser(userid);
            this.messageArea.trigger('contact-selected', userid);
        };

        /**
         * Handles loading the contact area.
         *
         * @param {String} webservice The web service to call
         * @returns {Promise} The promise resolved when the contact area has been rendered
         * @private
         */
        Contacts.prototype._loadContactArea = function(webservice) {
            // Show loading template.
            templates.render('core_message/loading', {}).done(function(html, js) {
                templates.replaceNodeContents("[data-region='contacts']", html, js);
            });

            // Call the web service to return the data we want to view.
            var promises = ajax.call([{
                methodname: webservice,
                args: {
                    userid: this.messageArea.getCurrentUserId()
                }
            }]);

            // After the request render the contacts area.
            return promises[0].then(function(data) {
                // We have the data - lets re-render the template with it.
                return templates.render('core_message/contacts', data);
            }).then(function(html, js) {
                templates.replaceNodeContents("[data-region='contacts-area']", html, js);
            }).fail(notification.exception);
        };

        /**
         * Handles selecting conversations to delete.
         *
         * @returns {Promise} The promise resolved when the conversations to delete have been selected.
         * @private
         */
        Contacts.prototype._chooseConversationsToDelete = function() {
            // Only show the checkboxes for the contact if we are also deleting messages.
            if (this.messageArea.find("[data-region='delete-message-checkbox']").length !== 0) {
                this.messageArea.find("[data-region='delete-conversation-checkbox']").show();
            }
        };

        /**
         * Handles canceling conversations to delete.
         *
         * @returns {Promise} The promise resolved when the conversations to delete have been selected.
         * @private
         */
        Contacts.prototype._cancelConversationsToDelete = function() {
            // Uncheck all checkboxes.
            this.messageArea.find("[data-region='delete-conversation-checkbox'] input:checked").removeAttr('checked');
            // Hide the checkboxes.
            this.messageArea.find("[data-region='delete-conversation-checkbox']").hide();
        };

        /**
         * Handles deleting conversations.
         *
         * @private
         */
        Contacts.prototype._deleteConversations = function() {
            var userid = this.messageArea.getCurrentUserId();
            var checkboxes = this.messageArea.find("[data-region='delete-conversation-checkbox'] input:checked");
            var requests = [];
            var conversationstoremove = [];

            // Go through all the checked checkboxes and prepare them for deletion.
            checkboxes.each(function(id, element) {
                var node = $(element);
                var otheruserid = node.parents("[data-region='contact']").data('userid');
                var conversation = this.messageArea.find("[data-region='contact'][data-id='" + otheruserid + "']");
                conversationstoremove.push(conversation);
                requests.push({
                    methodname: 'core_message_delete_conversation',
                    args: {
                        userid: userid,
                        otheruserid: otheruserid
                    }
                });
            }.bind(this));

            if (requests.length > 0) {
                ajax.call(requests)[requests.length - 1].then(function() {
                    // Remove the conversations from the DOM.
                    $.each(conversationstoremove, function(key, message) {
                        // Remove the message.
                        message.remove();
                    });
                    // Simple perform the same action as canceling to delete.
                    this._cancelConversationsToDelete();
                }.bind(this), notification.exception);
            }

            this._viewConversations();
        };

        /**
         * Handles removing a contact from the list.
         *
         * @param {Event} event
         * @param {int} userid
         * @private
         */
        Contacts.prototype._removeContact = function(event, userid) {
            this.messageArea.find("[data-region='contact'][data-userid='" + userid + "']").remove();
        };

        /**
         * Handles selecting a contact in the list.
         *
         * @param {int} userid
         * @private
         */
        Contacts.prototype._setSelectedUser = function(userid) {
            // Remove the 'selected' class from any other contact.
            this.messageArea.find("[data-region='contact']").removeClass('selected');
            // Set the tab for the user to selected.
            this.messageArea.find("[data-region='contact'][data-userid='" + userid + "']").addClass('selected');
        };

        return Contacts;
    }
);