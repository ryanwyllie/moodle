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
 * Timeline block installation.
 *
 * @package    block_timeline
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

 /**
  * Add the timeline block to the dashboard for all users by default
  * when it is installed.
  */
function xmldb_block_timeline_install() {
    global $DB;

    if ($DB->count_records('block_instances') > 0) {
        // Only add the timeline block if it's being installed on an existing site.
        // For new sites it will be added by blocks_add_default_system_blocks().
        if ($defaultmypage = $DB->get_record('my_pages', array('userid' => null, 'name' => '__default', 'private' => 1))) {
            $subpagepattern = $defaultmypage->id;
        } else {
            $subpagepattern = null;
        }

        $page = new moodle_page();
        $page->set_context(context_system::instance());
        // Add the block to the default /my.
        $page->blocks->add_region(BLOCK_POS_RIGHT);
        $page->blocks->add_block('timeline', BLOCK_POS_RIGHT, 0, false, 'my-index', $subpagepattern);
    }
}
