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
 * EditorJS plugin base for using template to render.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import VirtualDOM from 'core/virtual-dom';
import Templates from 'core/templates';
import { observable, autorun } from 'core/mobx';
import HTMLToVdom from 'core/html-to-vdom';

const convertHTML = HTMLToVdom.initializeConverter({
    VNode: VirtualDOM.VNode,
    VText: VirtualDOM.VText
});

export default class TemplateBase {
    constructor({ data, config }) {
        this.data = data;
        this.template = config.template;
        this.wrapper = null;
        this.tree = null;
    }

    registerEventListeners() {
        // Override me.
    }

    render() {
        this.data = observable(this.data);
        const html = Templates.syncRender(this.template, this.data);
        this.tree = convertHTML(`<div>${html}</div>`);
        this.wrapper = VirtualDOM.create(this.tree);
        autorun(this.renderFromTemplate.bind(this));
        this.registerEventListeners(this.wrapper, this.data);

        return this.wrapper;
    }

    renderFromTemplate() {
        const html = Templates.syncRender(this.template, this.data);
        // Add our wrapping div so that we don't replace the root node.
        const newTree = convertHTML(`<div>${html}</div>`);
        const patches = VirtualDOM.diff(this.tree, newTree);
        VirtualDOM.patch(this.wrapper, patches);
        this.tree = newTree;
    }

    save() {
        return this.data.toJS();
    }
}