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
 * The module handles searching contacts.
 *
 * @module     core_message/message_area_search
 * @package    core_message
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/str', 'core/custom_interaction_events'],
    function($, ajax, templates, notification, str, customEvents) {

    /**
     * Search class.
     *
     * @param {Messagearea} messageArea The messaging area object.
     */
    function Search(messageArea) {
        this.messageArea = messageArea;
        this._init();
    }

    /** @type {Messagearea} The messaging area object. */
    Search.prototype.messageArea = null;

    /** @type {String} The area we are searching in. */
    Search.prototype._searchArea = null;

    /** @type {String} The id of the course we are searching in (if any). */
    Search.prototype._courseid = null;

    /** @type {Boolean} checks if we are currently loading  */
    Search.prototype._isLoading = false;

    /** @type {String} The number of messages displayed. */
    Search.prototype._numMessagesDisplayed = 0;

    /** @type {String} The number of messages to retrieve. */
    Search.prototype._numMessagesToRetrieve = 20;

    /** @type {String} The number of people displayed. */
    Search.prototype._numPeopleDisplayed = 0;

    /** @type {String} The number of people to retrieve. */
    Search.prototype._numPeopleToRetrieve = 20;

    /** @type {Array} The type of available search areas. **/
    Search.prototype._searchAreas = {
        MESSAGES: 'messages',
        PEOPLE: 'people',
        PEOPLEINCOURSE: 'peopleincourse'
    };

    /** @type {int} The timeout before performing an ajax search */
    Search.prototype._requestTimeout = null;

    /**
     * Initialise the event listeners.
     *
     * @private
     */
    Search.prototype._init = function() {
        // Handle searching for text.
        this.messageArea.find(this.messageArea.SELECTORS.SEARCHTEXTAREA).on('input', this._searchRequest.bind(this));

        // Handle clicking on a course in the list of people.
        this.messageArea.onDelegateEvent('click', this.messageArea.SELECTORS.SEARCHPEOPLEINCOURSE, function(e) {
            this._setFilter($(e.currentTarget).html());
            this._setPlaceholderText('searchforperson');
            this._setSearchArea(this._searchAreas.PEOPLEINCOURSE);
            this._courseid = $(e.currentTarget).data('courseid');
            this._searchPeopleInCourse('');
        }.bind(this));

        // Handle deleting the search filter.
        this.messageArea.onDelegateEvent('click', this.messageArea.SELECTORS.DELETESEARCHFILTER, function() {
            this._clearFilters();
            this._hideSearchResults();
            // Filter has been removed, so we don't want to be searching in a course anymore.
            if (this._searchArea === this._searchAreas.PEOPLEINCOURSE) {
                this._setSearchArea(this._searchAreas.PEOPLE);
                this._setPlaceholderText('searchforpersonorcourse');
            }
            // Go back to the messages or the contacts we were viewing.
            if (this._searchArea === this._searchAreas.MESSAGES) {
                this.messageArea.trigger(this.messageArea.EVENTS.MESSAGESEARCHCANCELED);
            } else {
                this.messageArea.trigger(this.messageArea.EVENTS.PEOPLESEARCHCANCELED);
            }
        }.bind(this));

        // Handle events that occur outside this module.
        this.messageArea.onCustomEvent(this.messageArea.EVENTS.CONVERSATIONSSELECTED, function() {
            this._clearFilters();
            this._hideSearchResults();
            this._setSearchArea(this._searchAreas.MESSAGES);
            this._setPlaceholderText('searchmessages');
        }.bind(this));
        this.messageArea.onCustomEvent(this.messageArea.EVENTS.CONTACTSSELECTED, function() {
            this._clearFilters();
            this._hideSearchResults();
            this._setSearchArea(this._searchAreas.PEOPLE);
            this._setPlaceholderText('searchforpersonorcourse');
        }.bind(this));
        this.messageArea.onCustomEvent(this.messageArea.EVENTS.MESSAGESENT, function() {
            this._clearFilters();
            this._hideSearchResults();
            this._setSearchArea(this._searchAreas.MESSAGES);
            this._setPlaceholderText('searchmessages');
        }.bind(this));

        // Set the search area.
        this._setSearchArea(this._searchAreas.MESSAGES);
    };

    /**
     * Handles setting the search area and other fun stuff.
     *
     * @param {String} area The area
     * @private
     */
    Search.prototype._setSearchArea = function(area) {
        this._numMessagesDisplayed = 0;
        this._numPeopleDisplayed = 0;
        this._courseid = 0;
        this._searchArea = area;
    };

    /**
     * Handles when search requests are sent.
     *
     * @private
     */
    Search.prototype._searchRequest = function() {
        var str = this.messageArea.find(this.messageArea.SELECTORS.SEARCHTEXTAREA + ' input').val();

        if (this._requestTimeout) {
            clearTimeout(this._requestTimeout);
        }

        if (str.trim() === '') {
            // If there are no filters then we need to cancel search.
            if (this._searchArea == this._searchAreas.MESSAGES) {
                this._hideSearchResults();
                this.messageArea.trigger(this.messageArea.EVENTS.MESSAGESEARCHCANCELED);
            } else if (this._searchArea == this._searchAreas.PEOPLE) {
                this._hideSearchResults();
                this.messageArea.trigger(this.messageArea.EVENTS.PEOPLESEARCHCANCELED);
            }
            return;
        }

        this.messageArea.find(this.messageArea.SELECTORS.CONVERSATIONS).hide();
        this.messageArea.find(this.messageArea.SELECTORS.CONTACTS).hide();
        this.messageArea.find(this.messageArea.SELECTORS.SEARCHRESULTSAREA).show();

        if (this._searchArea == this._searchAreas.MESSAGES) {
            this._requestTimeout = setTimeout(function () {
                this._searchMessages(str);
            }.bind(this), 300);
        } else if (this._searchArea == this._searchAreas.PEOPLEINCOURSE) {
            this._requestTimeout = setTimeout(function () {
                this._searchPeopleInCourse(str);
            }.bind(this), 300);
        } else { // Must be searching for people and courses
            this._requestTimeout = setTimeout(function() {
                this._searchPeople(str);
            }.bind(this), 300);
        }
    };

    /**
     * Handles searching for messages.
     *
     * @private
     * @param {String} str The string to search for
     * @returns {Promise} The promise resolved when the search area has been rendered
     */
    Search.prototype._searchMessages = function(str) {
        // Keep track of the number of contacts
        var numberreceived = 0;
        // Perform the search and replace the content.
        return templates.render('core/loading', {}).then(function(html, js) {
            templates.replaceNodeContents(this.messageArea.SELECTORS.SEARCHRESULTSAREA,
                "<div style='text-align:center'>" + html + "</div>", js);
            return this._getMessages(str);
        }.bind(this)).then(function(data) {
            numberreceived = data.contacts.length;
            return templates.render('core_message/message_area_contacts', data);
        }).then(function(html, js) {
            templates.replaceNodeContents(this.messageArea.SELECTORS.SEARCHRESULTSAREA, html, js);
            // Check if messages turned up - if so do some stuff.
            if (numberreceived > 0) {
                // Increment the number of messages displayed.
                this._numMessagesDisplayed += numberreceived;
                // Assign the event for scrolling.
                customEvents.define(this.messageArea.SELECTORS.SEARCHRESULTSAREA, [
                    customEvents.events.scrollBottom
                ]);
                this.messageArea.onDelegateEvent(customEvents.events.scrollBottom, this.messageArea.SELECTORS.SEARCHRESULTSAREA,
                    function() {
                        this._loadMessages(str);
                    }.bind(this)
                );
            }
        }.bind(this)).fail(notification.exception);
    };


    /**
     * Handles searching for people.
     *
     * @private
     * @param {String} str The string to search for
     * @returns {Promise} The promise resolved when the search area has been rendered
     */
    Search.prototype._searchPeople = function(str) {
        // Call the web service to get our data.
        var promises = ajax.call([{
            methodname: 'core_message_data_for_messagearea_search_people',
            args: {
                userid: this.messageArea.getCurrentUserId(),
                search: str,
                limitnum: this._numPeopleToRetrieve
            }
        }]);

        // Perform the search and replace the content.
        return templates.render('core/loading', {}).then(function(html, js) {
            templates.replaceNodeContents(this.messageArea.SELECTORS.SEARCHRESULTSAREA,
                "<div style='text-align:center'>" + html + "</div>", js);
            return promises[0];
        }.bind(this)).then(function(data) {
            return templates.render('core_message/message_area_people_search_results', data);
        }).then(function(html, js) {
            templates.replaceNodeContents(this.messageArea.SELECTORS.SEARCHRESULTSAREA, html, js);
        }.bind(this)).fail(notification.exception);
    };

    /**
     * Handles searching for people in a course.
     *
     * @private
     * @param {String} str The string to search for
     * @returns {Promise} The promise resolved when the search area has been rendered
     */
    Search.prototype._searchPeopleInCourse = function(str) {
        // Keep track of the number of contacts
        var numberreceived = 0;
        // Perform the search and replace the content.
        return templates.render('core/loading', {}).then(function(html, js) {
            templates.replaceNodeContents(this.messageArea.SELECTORS.SEARCHRESULTSAREA,
                "<div style='text-align:center'>" + html + "</div>", js);
            return this._getPeopleInCourse(str);
        }.bind(this)).then(function(data) {
            numberreceived = data.contacts.length;
            return templates.render('core_message/message_area_people_search_results', data);
        }).then(function(html, js) {
            templates.replaceNodeContents(this.messageArea.SELECTORS.SEARCHRESULTSAREA, html, js);
            // Check if some people turned up - if so do some stuff.
            if (numberreceived > 0) {
                // Increment the number of contacts displayed.
                this._numPeopleDisplayed += numberreceived;
                // Assign the event for scrolling.
                customEvents.define(this.messageArea.SELECTORS.SEARCHRESULTSAREA, [
                    customEvents.events.scrollBottom
                ]);
                this.messageArea.onDelegateEvent(customEvents.events.scrollBottom, this.messageArea.SELECTORS.SEARCHRESULTSAREA,
                    function() {
                        this._loadPeopleInCourse(str);
                    }.bind(this)
                );
            }

        }.bind(this)).fail(notification.exception);
    };


    /**
     * Handles scrolling through search results.
     *
     * @private
     * @param {String} str The string to search for
     * @returns {Promise} The promise resolved when the search area has been rendered
     */
    Search.prototype._loadMessages = function(str) {
        if (this._isLoading) {
            return;
        }

        // Tell the user we are loading items.
        this._isLoading = true;

        // Keep track of the number of contacts
        var numberreceived = 0;
        // Add loading icon to the end of the list.
        return templates.render('core/loading', {}).then(function(html, js) {
            templates.appendNodeContents(this.messageArea.SELECTORS.SEARCHRESULTSAREA,
                "<div style='text-align:center'>" + html + "</div>", js);
            return this._getMessages(str);
        }.bind(this)).then(function(data) {
            numberreceived = data.contacts.length;
            return templates.render('core_message/message_area_contacts', data);
        }).then(function(html, js) {
            // Remove the loading icon.
            this.messageArea.find(this.messageArea.SELECTORS.SEARCHRESULTSAREA + " " +
                this.messageArea.SELECTORS.LOADINGICON).remove();
            // Only append data if we got data back.
            if (numberreceived > 0) {
                // Show the new content.
                templates.appendNodeContents(this.messageArea.SELECTORS.SEARCHRESULTSAREA, html, js);
                // Increment the number of contacts displayed.
                this._numMessagesDisplayed += numberreceived;
            }
            // Mark that we are no longer busy loading data.
            this._isLoading = false;
        }.bind(this)).fail(notification.exception);

    };

    /**
     * Handles scrolling through search results.
     *
     * @private
     * @param {String} str The string to search for
     * @returns {Promise} The promise resolved when the search area has been rendered
     */
    Search.prototype._loadPeopleInCourse = function(str) {
        if (this._isLoading) {
            return;
        }

        // Tell the user we are loading items.
        this._isLoading = true;

        // Keep track of the number of contacts
        var numberreceived = 0;
        // Add loading icon to the end of the list.
        return templates.render('core/loading', {}).then(function(html, js) {
            templates.appendNodeContents(this.messageArea.SELECTORS.SEARCHRESULTSAREA,
                "<div style='text-align:center'>" + html + "</div>", js);
            return this._getPeopleInCourse(str);
        }.bind(this)).then(function(data) {
            numberreceived = data.contacts.length;
            return templates.render('core_message/message_area_people_search_results', data);
        }).then(function(html, js) {
            // Remove the loading icon.
            this.messageArea.find(this.messageArea.SELECTORS.SEARCHRESULTSAREA + " " +
                this.messageArea.SELECTORS.LOADINGICON).remove();
            // Only append data if we got data back.
            if (numberreceived > 0) {
                // Show the new content.
                templates.appendNodeContents(this.messageArea.SELECTORS.SEARCHRESULTSAREA, html, js);
                // Increment the number of contacts displayed.
                this._numPeopleDisplayed += numberreceived;
            }
            // Mark that we are no longer busy loading data.
            this._isLoading = false;
        }.bind(this)).fail(notification.exception);
    };


    /**
     * Handles returning messages.
     *
     * @private
     * @param {String} str The string to search for
     * @returns {Promise}
     */
    Search.prototype._getMessages = function(str) {
        // Call the web service to get our data.
        var promises = ajax.call([{
            methodname: 'core_message_data_for_messagearea_search_messages',
            args: {
                userid: this.messageArea.getCurrentUserId(),
                search: str,
                limitfrom: this._numMessagesDisplayed,
                limitnum: this._numMessagesToRetrieve
            }
        }]);

        return promises[0];
    };

    /**
     * Handles returning people in a course.
     *
     * @private
     * @param {String} str The string to search for
     * @returns {Promise}
     */
    Search.prototype._getPeopleInCourse = function(str) {
        // Call the web service to get our data.
        var promises = ajax.call([{
            methodname: 'core_message_data_for_messagearea_search_people_in_course',
            args: {
                userid: this.messageArea.getCurrentUserId(),
                courseid: this._courseid,
                search: str,
                limitfrom: this._numPeopleDisplayed,
                limitnum: this._numPeopleToRetrieve
            }
        }]);

        return promises[0];
    };

    /**
     * Sets placeholder text for search input.
     *
     * @private
     * @param {String} text The placeholder text
     * @return {Promise} The promise resolved when the placeholder text has been set
     */
    Search.prototype._setPlaceholderText = function(text) {
        return str.get_string(text, 'message').then(function(s) {
            this.messageArea.find(this.messageArea.SELECTORS.SEARCHTEXTAREA + ' input').attr('placeholder', s);
        }.bind(this));
    };


    /**
     * Sets filter for search input.
     *
     * @private
     * @param {String} text The filter text
     */
    Search.prototype._setFilter = function(text) {
        this.messageArea.find(this.messageArea.SELECTORS.CONTACTSAREA).addClass('searchfilter');
        this.messageArea.find(this.messageArea.SELECTORS.SEARCHFILTERAREA).show();
        this.messageArea.find(this.messageArea.SELECTORS.SEARCHFILTER).html(text);
    };

    /**
     * Hides filter for search input.
     *
     * @private
     */
    Search.prototype._clearFilters = function() {
        this.messageArea.find(this.messageArea.SELECTORS.CONTACTSAREA).removeClass('searchfilter');
        this.messageArea.find(this.messageArea.SELECTORS.SEARCHFILTER).empty();
        this.messageArea.find(this.messageArea.SELECTORS.SEARCHFILTERAREA).hide();
    };

    /**
     * Handles hiding the search area.
     *
     * @private
     */
    Search.prototype._hideSearchResults = function() {
        this._numMessagesDisplayed = 0;
        this._numPeopleDisplayed = 0;
        this._courseid = 0;
        this.messageArea.find(this.messageArea.SELECTORS.SEARCHTEXTAREA + ' input').val('');
        this.messageArea.find(this.messageArea.SELECTORS.SEARCHRESULTSAREA).empty();
        this.messageArea.find(this.messageArea.SELECTORS.SEARCHRESULTSAREA).hide();
    };

    return Search;
});