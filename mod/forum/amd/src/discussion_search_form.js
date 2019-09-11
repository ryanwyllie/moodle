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
 * Discussion search form contorls.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import KeyCodes from 'core/key_codes';

const SELECTORS = {
    ADVANCED_SEARCH_FORM: '[data-region="advanced-search-form"]',
    ADVANCED_SEARCH_FORM_FIRST_FOCUS: 'input[name="words"]',
    ADVANCED_SEARCH_FORM_SHOW_BUTTON: '[data-action="show-advanced-search-form"]',
    QUICK_SEARCH_FORM: '[data-region="quick-search-form"]',
    QUICK_SEARCH_FORM_INPUT: 'input[name="search"]',
    QUICK_SEARCH_FORM_SUBMIT_BUTTON: 'button[type="submit"]',
    TIME_FROM_TOGGLE: "input[name='timefromrestrict']",
    TIME_FROM_DATE_FIELDS: "select[name^=from]",
    TIME_FROM_HIDDEN_DATE_FIELDS: "input[name^=hfrom]",
    TIME_TO_TOGGLE: "input[name='timetorestrict']",
    TIME_TO_DATE_FIELDS: "select[name^=to]",
    TIME_TO_HIDDEN_DATE_FIELDS: "input[name^=hto]",
};

/**
 * Get the advanced search form element.
 *
 * @param {Element} root Search forms root element.
 * @return {Element}
 */
const getAdvancedSearchForm = (root) => {
    return root.querySelector(SELECTORS.ADVANCED_SEARCH_FORM);
};

/**
 * Get the quick search form element.
 *
 * @param {Element} root Search forms root element.
 * @return {Element}
 */
const getQuickSearchForm = (root) => {
    return root.querySelector(SELECTORS.QUICK_SEARCH_FORM);
};

/**
 * Get the "time from" toggle element in the advanced search form.
 *
 * @param {Element} advancedSearchForm Advanced search form element.
 * @return {Element}
 */
const getTimeFromToggle = (advancedSearchForm) => {
    return advancedSearchForm.querySelector(SELECTORS.TIME_FROM_TOGGLE);
};

/**
 * Get the "time to" toggle element in the advanced search form.
 *
 * @param {Element} advancedSearchForm Advanced search form element.
 * @return {Element}
 */
const getTimeToToggle = (advancedSearchForm) => {
    return advancedSearchForm.querySelector(SELECTORS.TIME_TO_TOGGLE);
};

/**
 * Get the show advanced search form button from the quick search form.
 *
 * @param {Element} quickSearchForm Quick search form element.
 * @return {Element}
 */
const getAdvancedSearchFormShowButton = (quickSearchForm) => {
    return quickSearchForm.querySelector(SELECTORS.ADVANCED_SEARCH_FORM_SHOW_BUTTON);
};

/**
 * Get the first element in the advanced search form that can be focused.
 *
 * @param {Element} advancedSearchForm Advanced search form element.
 * @return {Element}
 */
const getAdvancedSearchFormFirstFocusElement = (advancedSearchForm) => {
    return advancedSearchForm.querySelector(SELECTORS.ADVANCED_SEARCH_FORM_FIRST_FOCUS);
};

/**
 * Get the search input from the quick search form.
 *
 * @param {Element} quickSearchForm Quick search form element.
 * @return {Element}
 */
const getQuickSearchFormInput = (quickSearchForm) => {
    return quickSearchForm.querySelector(SELECTORS.QUICK_SEARCH_FORM_INPUT);
};

/**
 * Get the submit button from the quick search form.
 *
 * @param {Element} quickSearchForm Quick search form element.
 * @return {Element}
 */
const getQuickSearchFormSubmitButton = (quickSearchForm) => {
    return quickSearchForm.querySelector(SELECTORS.QUICK_SEARCH_FORM_SUBMIT_BUTTON);
};

/**
 * Update the advanced form date fields disabled status.
 *
 * @param {Element} advancedSearchForm Advanced search form element.
 * @param {String} visibleSelector Selector to find the visible date elements.
 * @param {String} hiddenSelector Selector to find the hidden date elements.
 * @param {Bool} disabled The disabled status to set.
 */
const updateDateFieldsDisabled = (advancedSearchForm, visibleSelector, hiddenSelector, disabled) => {
    advancedSearchForm.querySelectorAll(visibleSelector).forEach(element => {
        if (disabled) {
            element.setAttribute('disabled', true);
        } else {
            element.removeAttribute('disabled');
        }
    });

    advancedSearchForm.querySelectorAll(hiddenSelector).forEach(element => {
        element.value = disabled ? 1 : 0;
    });
};

/**
 * Update the advanced form "time from" date fields disabled status based on
 * the "checked" value of the toggle element.
 *
 * @param {Element} advancedSearchForm Advanced search form element.
 * @param {Element} timeFromToggle The "time from" toggle element
 */
const updateTimeFromDateFieldsDisabled = (advancedSearchForm, timeFromToggle) => {
    updateDateFieldsDisabled(
        advancedSearchForm,
        SELECTORS.TIME_FROM_DATE_FIELDS,
        SELECTORS.TIME_FROM_HIDDEN_DATE_FIELDS,
        !timeFromToggle.checked
    );
};

/**
 * Update the advanced form "time to" date fields disabled status based on
 * the "checked" value of the toggle element.
 *
 * @param {Element} advancedSearchForm Advanced search form element.
 * @param {Element} timeToToggle The "time to" toggle element
 */
const updateTimeToDateFieldsDisabled = (advancedSearchForm, timeToToggle) => {
    updateDateFieldsDisabled(
        advancedSearchForm,
        SELECTORS.TIME_TO_DATE_FIELDS,
        SELECTORS.TIME_TO_HIDDEN_DATE_FIELDS,
        !timeToToggle.checked
    );
};

/**
 * Show the advanced search form.
 *
 * @param {Element} advancedSearchForm Advanced search form element.
 * @param {Element} advancedSearchFormShowButton The button to show the advanced form.
 * @param {Element} quickSearchFormSubmitButton The submit button for the quick search form.
 */
const showAdvancedSearchForm = (advancedSearchForm, advancedSearchFormShowButton, quickSearchFormSubmitButton) => {
    advancedSearchForm.classList.remove('hidden');
    advancedSearchFormShowButton.classList.add('hidden');
    advancedSearchFormShowButton.setAttribute('aria-expanded', 'true');
    quickSearchFormSubmitButton.setAttribute('disabled', true);
    getAdvancedSearchFormFirstFocusElement(advancedSearchForm).focus();
};

/**
 * Hide the advanced search form.
 *
 * @param {Element} advancedSearchForm Advanced search form element.
 * @param {Element} advancedSearchFormShowButton The button to show the advanced form.
 * @param {Element} quickSearchFormSubmitButton The submit button for the quick search form.
 */
const hideAdvancedSearchForm = (advancedSearchForm, advancedSearchFormShowButton, quickSearchFormSubmitButton) => {
    advancedSearchForm.classList.add('hidden');
    advancedSearchFormShowButton.classList.remove('hidden');
    advancedSearchFormShowButton.setAttribute('aria-expanded', 'false');
    quickSearchFormSubmitButton.removeAttribute('disabled');
};

/**
 * Initialise the event listeners for the discussion search form.
 *
 * @param {Element} root Search forms root element.
 */
export default (root) => {
    const advancedSearchForm = getAdvancedSearchForm(root);
    const quickSearchForm = getQuickSearchForm(root);
    const quickSearchFormInput = getQuickSearchFormInput(quickSearchForm);
    const quickSearchFormSubmitButton = getQuickSearchFormSubmitButton(quickSearchForm);
    const timeFromToggle = getTimeFromToggle(advancedSearchForm);
    const timeToToggle = getTimeToToggle(advancedSearchForm);
    const advancedSearchFormShowButton = getAdvancedSearchFormShowButton(quickSearchForm);
    let advancedFormVisible = false;

    updateTimeFromDateFieldsDisabled(advancedSearchForm, timeFromToggle);
    updateTimeToDateFieldsDisabled(advancedSearchForm, timeToToggle);

    timeFromToggle.addEventListener('click', () => updateTimeFromDateFieldsDisabled(advancedSearchForm, timeFromToggle));
    timeToToggle.addEventListener('click', () => updateTimeToDateFieldsDisabled(advancedSearchForm, timeToToggle));
    advancedSearchFormShowButton.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        showAdvancedSearchForm(advancedSearchForm, advancedSearchFormShowButton, quickSearchFormSubmitButton);
        advancedFormVisible = true;
    });
    document.body.addEventListener('keydown', (e) => {
        if (advancedFormVisible && e.keyCode == KeyCodes.escape) {
            hideAdvancedSearchForm(advancedSearchForm, advancedSearchFormShowButton, quickSearchFormSubmitButton);
            quickSearchFormInput.focus();
            advancedFormVisible = false;
        }
    });
    document.body.addEventListener('click', (e) => {
        if (advancedFormVisible && !advancedSearchForm.contains(e.target)) {
            hideAdvancedSearchForm(advancedSearchForm, advancedSearchFormShowButton, quickSearchFormSubmitButton);
            advancedFormVisible = false;
        }
    });
};
