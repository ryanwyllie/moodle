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
// Only show this post and it's children.
$parentpostid = optional_param('parent', 0, PARAM_INT);
$url = new moodle_url('/mod/forum/discuss.php', ['d' => $discussionid]);

$PAGE->set_url($url);

if ($displaymode) {
    set_user_preference('forum_displaymode', $displaymode);
}

$displaymode = get_user_preferences('forum_displaymode', $CFG->forum_displaymode);
$vaultfactory = mod_forum\local\container::get_vault_factory();

$discussionvault = $vaultfactory->get_discussion_vault();
$discussion = $discussionvault->get_from_id($discussionid);

if (!$discussion) {
    throw new \moodle_exception('Unable to find discussion with id ' . $discussionid);
}

$forumvault = $vaultfactory->get_forum_vault();
$forum = $forumvault->get_from_id($discussion->get_forum_id());

if (!$forum) {
    throw new \moodle_exception('Unable to find forum with id ' . $discussion->get_forum_id());
}

$managerfactory = mod_forum\local\container::get_manager_factory();
$capabilitymanager = $managerfactory->get_capability_manager($forum);

// Make sure we can render.
if (!$capabilitymanager->can_view_discussions($USER)) {
    throw new moodle_exception('noviewdiscussionspermission', 'mod_forum');
}

$course = $forum->get_course_record();
$cm = $forum->get_course_module_record();

require_course_login($course, true, $cm);
// move this down fix for MDL-6926
require_once($CFG->dirroot.'/mod/forum/lib.php');

if (empty($parentpostid)) {
    // Default to the first post of the discussion if no other post was specified.
    $parentpostid = $discussion->get_first_post_id();
}

$postvault = $vaultfactory->get_post_vault();
$post = $postvault->get_from_id($parentpostid);
$modcontext = $forum->get_context();

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
$eventmanager = $managerfactory->get_event_manager();
$eventmanager->mark_discussion_as_viewed($forum, $discussion);

unset($SESSION->fromdiscussion);

$PAGE->set_title("$course->shortname: " . format_string($discussion->get_name()));
$PAGE->set_heading($course->fullname);
$PAGE->set_button(forum_search_form($course));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($forum->get_name()), 2);
echo $OUTPUT->heading(format_string($discussion->get_name()), 3, 'discussionname');

$datamapperfactory = mod_forum\local\container::get_legacy_data_mapper_factory();
$forumdatamapper = $datamapperfactory->get_forum_data_mapper();
$forumrecord = $forumdatamapper->to_legacy_object($forum);
$istracked = forum_tp_is_tracked($forumrecord, $USER);

$rendererfactory = mod_forum\local\container::get_renderer_factory();
$discussionrenderer = $rendererfactory->get_discussion_renderer($forum, $discussion);
$orderpostsby = $displaymode == FORUM_MODE_FLATNEWEST ? 'created DESC' : 'created ASC';
$posts = $postvault->get_from_discussion_id($discussion->get_id(), $orderpostsby);
$postids = array_map(function($post) {
    return $post->get_id();
}, $posts);

if ($istracked) {
    $readreceiptvault = $vaultfactory->get_post_read_receipt_collection_vault();
    $readreceiptcollection = $readreceiptvault->get_from_user_id_and_post_ids($USER->id, $postids);
} else {
    $readreceiptcollection = null;
}

echo $discussionrenderer->render($USER, $displaymode, $posts, $readreceiptcollection);
echo $OUTPUT->footer();

if ($istracked && !$CFG->forum_usermarksread) {
    $unreadpostids = array_reduce($posts, function($carry, $post) use ($USER, $readreceiptcollection) {
        if ($readreceiptcollection->has_user_read_post($USER, $post)) {
            $carry[] = $post->get_id();
        }
        return $carry;
    }, []);

    if (!empty($unreadpostids)) {
        forum_tp_mark_posts_read($user, $unreadpostids);
    }
}