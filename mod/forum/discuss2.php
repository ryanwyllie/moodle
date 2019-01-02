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

if (!$discussion) {
    throw new \moodle_exception('Unable to find discussion with id ' . $discussionid);
}

$course = $DB->get_record('course', array('id' => $discussion->get_course_id()), '*', MUST_EXIST);
$forumvault = mod_forum\local\factories\vault::get_forum_vault();
$forum = $forumvault->get_from_id($discussion->get_forum_id());

if (!$forum) {
    throw new \moodle_exception('Unable to find forum with id ' . $discussion->get_forum_id());
}

$cm = get_coursemodule_from_instance('forum', $forum->get_id(), $course->id, false, MUST_EXIST);

require_course_login($course, true, $cm);

$modcontext = context_module::instance($cm->id);

$forumnode = $PAGE->navigation->find($cm->id, navigation_node::TYPE_ACTIVITY);
if (empty($forumnode)) {
    $forumnode = $PAGE->navbar;
} else {
    $forumnode->make_active();
}
$node = $forumnode->add(format_string($discussion->get_name()), new moodle_url('/mod/forum/discuss2.php', ['d' => $discussion->get_id()]));
$node->display = false;

/*
if ($node && $post->id != $discussion->firstpost) {
    $node->add(format_string($post->subject), $PAGE->url);
}
*/

$PAGE->set_title("$course->shortname: " . format_string($discussion->get_name()));
$PAGE->set_heading($course->fullname);
$PAGE->set_button(forum_search_form($course));

$renderer = $PAGE->get_renderer('mod_forum');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($forum->get_name()), 2);
echo $OUTPUT->heading(format_string($discussion->get_name()), 3, 'discussionname');

$discussionrenderer = mod_forum\local\factories\renderer::get_discussion_renderer($forum, $discussion, $displaymode, $renderer);
echo $discussionrenderer->render($modcontext, $forum, $discussion);

echo $OUTPUT->footer();
