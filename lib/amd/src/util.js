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
 * A list of utility functions.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const SELECTORS = {
    CAN_RECEIVE_FOCUS: 'input:not([type="hidden"]), a[href], button, textarea, select, [tabindex]'
};

/**
 * Find the first element within the given element that can receive focus.
 *
 * @param {Object} root The element to begin the search from.
 * @return {Object|null}
 */
export const findFirstFocusableElement = (root) => {
    return root.querySelector(SELECTORS.CAN_RECEIVE_FOCUS);
};

/**
 * Find the first element within the given element that can receive focus
 * and give it focus.
 *
 * @param {Object} root The element to begin the search from.
 */
export const focusFirstFocusableElement = (root) => {
    const focusElement = findFirstFocusableElement(root);
    if (focusElement) {
        focusElement.focus();
    }
};
