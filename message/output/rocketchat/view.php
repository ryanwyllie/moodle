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
 * Displays a post, and all the posts below it.
 * If no post is given, displays all posts in a discussion
 *
 * @package   mod_forum
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login(null, false);

$url = new moodle_url('/message/output/rocketchat/view.php');
$PAGE->set_url($url);
$PAGE->set_title('Messaging');
$PAGE->set_heading('Messaging');
$style = implode(';', [
    'position: fixed',
    'top: 50px',
    'left: 0',
    'right: 0',
    'bottom: 0',
    'height: calc(100% - 50px)',
    'width: 100%',
    'z-index: 1'
]);

echo $OUTPUT->header();
echo "<iframe src='http://rocketchat.localhost:8888' frameborder='0' style='{$style}'></iframe>";
echo $OUTPUT->footer();
