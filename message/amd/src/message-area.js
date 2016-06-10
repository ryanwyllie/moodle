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
 * This module handles toggling between the 'Conversations' and 'Contacts'
 * tabs in the message area.
 *
 * @module     core_message/message-area
 * @package    core_message
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification'], function($, ajax, templates, notification) {

    function Messagearea(selector) {
        this._node = $(selector);
        this._init();
    }

    Messagearea.prototype.maxstringlength = 60;

    Messagearea.prototype.find = function(selector) {
        return this._node.find(selector);
    };

    Messagearea.prototype._init = function() {
        this._node.on('click', '.tabconversations', this._loadConversations.bind(this));
        this._node.on('click', '.tabcontacts', this._loadContacts.bind(this));
        this._node.on('click', '.contact-msg', this._loadMessages.bind(this));
        this._node.on('click', '.sendmessagebtn', this._sendMessage.bind(this));
    };

    Messagearea.prototype._loadConversations = function() {
        this._loadContactArea('core_message_data_for_messagearea_conversations');
    };

    Messagearea.prototype._loadContacts = function() {
        this._loadContactArea('core_message_data_for_messagearea_contacts');
    };

    Messagearea.prototype._loadContactArea = function(methodname) {
        // Show loading template.
        templates.render('core_message/loading', {}).done(function(html) {
            this.find('.contacts').empty().append(html);
        }.bind(this));

        // Call the web service to get our data.
        var promises = ajax.call([{
            methodname: methodname,
            args: []
        }]);

        // Do stuff when we get data back.
        promises[0].then(function(data) {
            // We have the data - lets re-render the template with it.
            return templates.render('core_message/contacts', data).then(function(html, js) {
                this.find('.contacts-area').empty().append(html);
                // And execute any JS that was in the template.
                templates.runTemplateJS(js);
            }.bind(this));
        }.bind(this)).fail(notification.exception);
    };

    Messagearea.prototype._loadMessages = function(event) {
        // Show loading template.
        templates.render('core_message/loading', {}).done(function(html) {
            this.find('.messages-area').empty().append(html);
        }.bind(this));

        // Get the user that was clicked.
        var userid = event.currentTarget.id.substr(8);

        // Call the web service to get our data.
        var promises = ajax.call([{
            methodname: 'core_message_data_for_messagearea_messages',
            args: {
                'userid' : userid
            }
        }]);

        // Do stuff when we get data back.
        promises[0].then(function(data) {
            // We have the data - lets re-render the template with it.
            return templates.render('core_message/messages', data).then(function(html, js) {
                this.find('.messages-area').empty().append(html);
                // And execute any JS that was in the template.
                templates.runTemplateJS(js);
            }.bind(this));
        }.bind(this)).fail(notification.exception);
    };

    Messagearea.prototype._sendMessage = function(event) {
        // Get the variables we are going to use.
        var userid = event.currentTarget.id.substr(15);
        var text = this.find('#sendmessagetxt').val();

        // Call the web service to save our message.
        var promises = ajax.call([{
            methodname: 'core_message_send_instant_messages',
            args: {
                messages : [
                    {
                        'touserid': userid,
                        'text': text
                    }
                ]
            }
        }]);

        // Update the DOM when we get some data back.
        promises[0].then(function() {
            // Set the data we need to update the messaging area.
            this._addMessageToDom(text, userid);

            // Empty the text area.
            this.find('#sendmessagetxt').val('');

        }.bind(this)).fail(notification.exception);
    };

    Messagearea.prototype._addMessageToDom = function(text, userid) {
        var messagedata = {
            'blocktime' : this._getBlockTime(),
            'position': 'right',
            'text': text,
            'timesent': this._getMessageTime()
        };

        // Add the message.
        templates.render('core_message/message', messagedata).then(function (html, js) {
            this.find('.messages').append(html);
            // And execute any JS that was in the template.
            templates.runTemplateJS(js);
        }.bind(this));

        // Update the conversation on the left.
        var leftmsg = text.substr(0, this.maxstringlength);
        if (text.length > this.maxstringlength) {
            leftmsg += " ...";
        }
        this.find('#contact-' + userid + ' .lastmessage').empty().append(leftmsg);
    };

    Messagearea.prototype._getBlockTime = function() {
        return '';
    };

    Messagearea.prototype._getMessageTime = function() {
        // Get the time to show.
        var date = new Date();
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var ampm = hours >= 12 ? 'pm' : 'am';
        hours = hours % 12;
        hours = hours ? hours : 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;

        return hours + ':' + minutes + ' ' + ampm
    };

    return Messagearea;
});
