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
 * Contain the logic for modal backdrops.
 *
 * @module     core/modal_backdrop
 * @class      modal_backdrop
 * @package    core
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/templates', 'core/notification'],
     function($, Templates, Notification) {

    var SELECTORS = {
        ROOT: '[data-region="modal-backdrop"]',
    };

    /**
     * Constructor for ModalBackdrop.
     *
     * @param {jQuery object} root The root element for the modal backdrop
     * @return Modal
     */
    var ModalBackdrop = function(root) {
        this.root = $(root);
        this.isAttached = false;

        if (!this.root.is(SELECTORS.ROOT)) {
            Notification.exception({message: 'Element is not a modal backdrop'});
        }
    };

    /**
     * Get the root element of this modal backdrop.
     *
     * @method getRoot
     * @return jQuery object
     */
    ModalBackdrop.prototype.getRoot = function() {
        return this.root;
    };

    /**
     * Add the modal backdrop to the page, if it hasn't already been added.
     *
     * @method attachToDOM
     */
    ModalBackdrop.prototype.attachToDOM = function() {
        if (this.isAttached) {
            return;
        }

        $('body').append(this.root);
        this.isAttached = true;
    };

    /**
     * Set the z-index value for this backdrop.
     *
     * @method setZIndex
     * @param {int} value The z-index value
     */
    ModalBackdrop.prototype.setZIndex = function(value) {
        this.root.css('z-index', value);
    };

    /**
     * Check if this backdrop is visible.
     *
     * @method isVisible
     * @return bool
     */
    ModalBackdrop.prototype.isVisible = function() {
        return this.root.hasClass('visible');
    };

    /**
     * Display this backdrop. The backdrop will be attached to the DOM if it hasn't
     * already been.
     *
     * @method show
     */
    ModalBackdrop.prototype.show = function() {
        if (this.isVisible()) {
            return;
        }

        if (!this.isAttached) {
            this.attachToDOM();
        }

        this.root.removeClass('invisible').addClass('visible');
    };

    /**
     * Hide this backdrop.
     *
     * @method hide
     */
    ModalBackdrop.prototype.hide = function() {
        if (!this.isVisible()) {
            return;
        }

        this.root.removeClass('visible').addClass('invisible');
    };

    /**
     * Remove this backdrop from the DOM.
     *
     * @method destroy
     */
    ModalBackdrop.prototype.destroy = function() {
        this.root.remove();
    };

    return ModalBackdrop;
});
