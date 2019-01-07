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
$displaymode = optional_param('mode', 0, PARAM_INT);
$url = new moodle_url('/mod/forum/discuss2.php', ['d' => $discussionid]);

$PAGE->set_url($url);

if ($displaymode) {
    set_user_preference('forum_displaymode', $displaymode);
}

$displaymode = get_user_preferences('forum_displaymode', $CFG->forum_displaymode);
$vaultfactory = mod_forum\local\container::get_vault_factory();
$rendererfactory = mod_forum\local\container::get_renderer_factory();
$dbserializerfactory = mod_forum\local\container::get_database_serializer_factory();

$discussionvault = $vaultfactory->get_discussion_vault();
$discussion = $discussionvault->get_from_id($discussionid);

if (!$discussion) {
    throw new \moodle_exception('Unable to find discussion with id ' . $discussionid);
}

$course = $DB->get_record('course', ['id' => $discussion->get_course_id()], '*', MUST_EXIST);
$forumvault = $vaultfactory->get_forum_vault();
$forum = $forumvault->get_from_id($discussion->get_forum_id());

if (!$forum) {
    throw new \moodle_exception('Unable to find forum with id ' . $discussion->get_forum_id());
}

$cm = get_coursemodule_from_instance('forum', $forum->get_id(), $course->id, false, MUST_EXIST);

require_course_login($course, true, $cm);
// move this down fix for MDL-6926
require_once($CFG->dirroot.'/mod/forum/lib.php');

$modcontext = context_module::instance($cm->id);

$forumnode = $PAGE->navigation->find($cm->id, navigation_node::TYPE_ACTIVITY);
if (empty($forumnode)) {
    $forumnode = $PAGE->navbar;
} else {
    $forumnode->make_active();
}
$node = $forumnode->add(format_string($discussion->get_name()), $url);
$node->display = false;

/*
if ($node && $post->id != $discussion->firstpost) {
    $node->add(format_string($post->subject), $PAGE->url);
}
*/

// Trigger discussion viewed event.
$discussionserializer = $dbserializerfactory->get_discussion_serializer();
$forumserializer = $dbserializerfactory->get_forum_serializer();
$discussionrecord = $discussionserializer->to_db_records([$discussion])[0];
$forumrecord = $forumserializer->to_db_records([$forum])[0];
forum_discussion_view($modcontext, $forumrecord, $discussionrecord);

unset($SESSION->fromdiscussion);

$PAGE->set_title("$course->shortname: " . format_string($discussion->get_name()));
$PAGE->set_heading($course->fullname);
$PAGE->set_button(forum_search_form($course));

$renderer = $PAGE->get_renderer('mod_forum');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($forum->get_name()), 2);
echo $OUTPUT->heading(format_string($discussion->get_name()), 3, 'discussionname');

$discussionrenderer = $rendererfactory->get_discussion_renderer($forum, $discussion, $displaymode, $renderer);
echo $discussionrenderer->render($modcontext, $forum, $discussion);

echo $OUTPUT->footer();
