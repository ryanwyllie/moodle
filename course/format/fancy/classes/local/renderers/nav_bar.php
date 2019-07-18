<?php
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
 * Nav bar renderer.
 *
 * @package    mod_forum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_fancy\local\renderers;

defined('MOODLE_INTERNAL') || die();

use action_menu;
use action_link;
use moodle_page;
use moodle_url;
use navigation_node;
use pix_icon;
use renderer_base;
use stdClass;

/**
 * Nav bar renderer class.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class nav_bar {
    private $renderer;

    public function __construct(renderer_base $renderer) {
        $this->renderer = $renderer;
    }

    public function render(moodle_page $page, stdClass $user) {
        $renderer = $this->renderer;
        $settingsmenu = new action_menu();
        $settingsnode = $page->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
        $courseid = $page->course->id;

        if ($settingsnode) {
            foreach ($settingsnode->children as $menuitem) {
                if ($menuitem->display) {
                    if ($menuitem->children->count()) {
                        // Only take top level nodes.
                        continue;
                    }

                    if ($menuitem->action) {
                        if ($menuitem->action instanceof action_link) {
                            $link = $menuitem->action;
                            // Give preference to setting icon over action icon.
                            if (!empty($menuitem->icon)) {
                                $link->icon = $menuitem->icon;
                            }
                        } else {
                            $link = new action_link($menuitem->action, $menuitem->text, null, null, $menuitem->icon);
                        }

                        if ($menuitem->key !== 'turneditingonoff') {
                            $settingsmenu->add_secondary_action($link);
                        }
                    }
                }
            }

            $text = get_string('morenavigationlinks');
            $url = new moodle_url('/course/admin.php', ['courseid' => $courseid]);
            $link = new action_link($url, $text, null, null, new pix_icon('t/edit', $text));
            $settingsmenu->add_secondary_action($link);

            $settingsmenucontext = $settingsmenu->export_for_template($renderer);
            $settingsmenucontext->primary->rawicon = get_string('settings');
            $settingsmenucontext->primary->triggerextraclasses = 'btn btn-outline-secondary';

            $url = new moodle_url('/course/view.php', [
                'id' => $courseid,
                'sesskey' => sesskey(),
                'edit' => $user->editing ? 'off' : 'on'
            ]);
            $editbuttoncontext = [
                'url' => $url->out(false),
                'text' => $user->editing ? get_string('save') : get_string('edit'),
                'icon' => [
                    'key' => $user->editing ? 'e/save' : 'i/edit',
                    'component' => 'core'
                ]
            ];

            return $renderer->render_from_template('format_fancy/nav_bar', [
                'settingsmenu' => $settingsmenucontext,
                'editbutton' => $editbuttoncontext
            ]);
        } else {
            return '';
        }
    }
}
