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

require_once('../../config.php');

$discussionid = required_param('d', PARAM_INT);
$displaymode = optional_param('mode', 3, PARAM_INT);
$url = new moodle_url('/mod/forum/discuss2.php', ['d' => $discussionid]);

$PAGE->set_url($url);

$discussionvault = mod_forum\local\factories\vault::get_discussion_vault();
$discussion = $discussionvault->get_from_id($discussionid);
$course = $DB->get_record('course', array('id' => $discussion->get_course_id()), '*', MUST_EXIST);
$forum = $DB->get_record('forum', array('id' => $discussion->get_forum_id()), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('forum', $forum->id, $course->id, false, MUST_EXIST);

require_course_login($course, true, $cm);

// move this down fix for MDL-6926
require_once($CFG->dirroot.'/mod/forum/lib.php');

$modcontext = context_module::instance($cm->id);
require_capability('mod/forum:viewdiscussion', $modcontext, NULL, true, 'noviewdiscussionspermission', 'forum');


$PAGE->set_title("$course->shortname: ".format_string($discussion->get_name()));
$PAGE->set_heading($course->fullname);
$renderer = $PAGE->get_renderer('mod_forum');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($forum->name), 2);
echo $OUTPUT->heading(format_string($discussion->get_name()), 3, 'discussionname');

/// Print the controls across the top
echo '<div class="discussioncontrols clearfix"><div class="controlscontainer m-b-1">';
// groups selector not needed here
echo '<div class="discussioncontrol displaymode">';
forum_print_mode_form($discussion->get_id(), $displaymode);
echo "</div>";

$discussionrenderer = mod_forum\local\factories\renderer::get_discussion_renderer($discussion, $displaymode, $renderer);
echo $discussionrenderer->render($discussion);

echo $OUTPUT->footer();
