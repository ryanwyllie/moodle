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
 * A javascript module to enhance the override form.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';

export const init = (formId, selectElementName, duplicateActionElementSelector) => {
    let formModified = false;
    const form = document.getElementById(formId);
    // Is this form for duplicating an override?
    const isDuplicate = form.querySelector(duplicateActionElementSelector) !== null;

    $(form).on('change', (e) => {
        // Grab the element that was modified.
        const target = e.target;

        if (target.getAttribute('name') === selectElementName) {
            // If the modified element is the user selector then we need to reload the
            // page in order to update the user default values.

            if (!formModified && !isDuplicate) {
                // If the form hasn't been changed (other than the user picker) and this isn't
                // a duplicate override form then we should also reset the form date elements to
                // the user defaults. Append the "resetbutton" form element to tell the server
                // to reset the values.
                const resetElement = document.createElement('input');
                resetElement.setAttribute('type', 'hidden');
                resetElement.setAttribute('name', 'resetbutton');
                resetElement.setAttribute('value', 'reset');
                form.appendChild(resetElement);
            } else {
                // We add a hidden "userchange" element to the form before submitting
                // to tell the server why the form was submitted.
                const userChangeElement = document.createElement('input');
                userChangeElement.setAttribute('type', 'hidden');
                userChangeElement.setAttribute('name', 'userchange');
                userChangeElement.setAttribute('value', true);
                form.appendChild(userChangeElement);
            }

            if (typeof M.core_formchangechecker !== 'undefined') {
                M.core_formchangechecker.reset_form_dirty_state();
            }

            form.submit();
        } else {
            // The user has modified something other than the user picker so the form is
            // dirty and we shouldn't reset the values if they modify the user selector.
            formModified = true;
        }
    });
};