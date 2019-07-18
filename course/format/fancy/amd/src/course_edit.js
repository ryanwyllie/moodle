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
 * Module for viewing editing a course.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import EditorJS from 'core/editorjs';
import BannerImage from 'format_fancy/editorjs_plugin_banner_image';

function registerEventListeners() {
    const saveButton = document.querySelector('[data-action="save-course"]');

    saveButton.addEventListener('click', (event) => {
        const loadingIcon = saveButton.querySelector('[data-region="loading-icon-container"]');
        const text = saveButton.querySelector('[data-region="text-container"]');

        loadingIcon.classList.remove('hidden');
        text.classList.add('hidden');

        setTimeout(() => {
            window.location = saveButton.attributes.href.value;
        }, 1000);

        event.preventDefault();
    });
}

export function init(elementId) {
    const editor = new EditorJS({
        holderId: elementId,
        autofocus: true,
        tools: {
            image: BannerImage
        }
    });

    registerEventListeners(editor);
}