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
 * EditorJS plugin for a image.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import TemplateBase from 'format_fancy/editorjs_plugin_template_base';

export default class Image extends TemplateBase {
    static get toolbox() {
        return {
            title: 'Image',
            icon: '<svg width="17" height="15" viewBox="0 0 336 276" xmlns="http://www.w3.org/2000/svg">' +
                  '<path d="M291 150V79c0-19-15-34-34-34H79c-19 0-34 15-34 34v42l67-44 81 72 56-29 42 30zm0' +
                  ' 52l-43-30-56 30-81-67-66 39v23c0 19 15 34 34 34h178c17 0 31-13 34-29zM79 0h178c44 0 79' +
                  ' 35 79 79v118c0 44-35 79-79 79H79c-44 0-79-35-79-79V79C0 35 35 0 79 0z"/></svg>'
        };
    }

    constructor(args) {
        super(args);
        this.api = args.api;
        args.data.stretched = args.data.stretched || false;
    }

    registerEventListeners(rootNode, data) {
        rootNode.querySelector('input').addEventListener('paste', (event) => {
            const url = event.clipboardData.getData('text');
            data.url = url;
        });
    }

    renderSettings() {
        const config = {
            name: 'stretched',
            icon: '<svg width="17" height="10" viewBox="0 0 17 10" xmlns="http://www.w3.org/2000/svg">' +
                '<path d="M13.568 5.925H4.056l1.703 1.703a1.125 1.125 0 0 1-1.59 1.591L.962 6.014A1.069' +
                ' 1.069 0 0 1 .588 4.26L4.38.469a1.069 1.069 0 0 1 1.512 1.511L4.084 3.787h9.606l-1.85-1.85a1.069 1.069' +
                ' 0 1 1 1.512-1.51l3.792 3.791a1.069 1.069 0 0 1-.475 1.788L13.514 9.16a1.125 1.125 0 0' +
                ' 1-1.59-1.591l1.644-1.644z"/></svg>'
        };
        const wrapper = document.createElement('div');
        let button = document.createElement('div');

        button.classList.add('cdx-settings-button');
        button.innerHTML = config.icon;
        wrapper.appendChild(button);

        button.addEventListener('click', () => {
            this.data.stretched = !this.data.stretched;
            this.api.blocks.stretchBlock(this.api.blocks.getCurrentBlockIndex(), !!this.data.stretched);
        });

        return wrapper;
    }
}