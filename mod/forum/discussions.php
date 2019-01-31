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
 * Displays the list of discussions in a forum.
 *
 * @package   mod_forum
 * @copyright 2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$managerfactory = mod_forum\local\container::get_manager_factory();

$vaultfactory = mod_forum\local\container::get_vault_factory();
$forumvault = $vaultfactory->get_forum_vault();

// TODO Deprecate the 'f' usage?
$cmid = optional_param('id', null, PARAM_INT);
$forumid = optional_param('f', null, PARAM_INT);
$pageno = optional_param('p', 0, PARAM_INT);
$pagesize = optional_param('s', 0, PARAM_INT);
$sortorder = optional_param('o', null, PARAM_INT);

if ($cmid) {
    $forum = $forumvault->get_from_course_module_id($cmid);
    $urlmanager = $managerfactory->get_url_manager($forum);
    $url = $urlmanager->get_forum_view_url_from_course_module_id($cmid);
} else if ($forumid) {
    $forum = $forumvault->get_from_id($forumid);
    $urlmanager = $managerfactory->get_url_manager($forum);
    $url = $urlmanager->get_forum_view_url_from_forum($forum);
} else {
    required_param('id', PARAM_INT);
    // TODO Improve error.
}

$PAGE->set_url($url);

if (!$forum) {
    throw new \moodle_exception('Unable to find forum with id ' . $forumid);
}

$capabilitymanager = $managerfactory->get_capability_manager($forum);
$eventmanager = $managerfactory->get_event_manager();

$course = $forum->get_course_record();
$cm = $forum->get_course_module_record();

require_course_login($course, true, $cm);

$PAGE->set_context($forum->get_context());
$PAGE->set_title($forum->get_name());
$PAGE->add_body_class('forumtype-' . $forum->get_type());
$PAGE->set_heading($course->fullname);
$PAGE->set_button(forum_search_form($course));

if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    redirect(
            $urlmanager->get_course_url_from_forum($forum),
            get_string('activityiscurrentlyhidden'),
            \core\output\notification::NOTIFY_WARNING
        );
}

if (!$capabilitymanager->can_view_discussions($USER, $forum)) {
    redirect(
            $urlmanager->get_course_url_from_forum($forum),
            get_string('noviewdiscussionspermission', 'fourm'),
            \core\output\notification::NOTIFY_WARNING
        );
}

// Mark viewed and trigger the course_module_viewed event.
$eventmanager->mark_forum_as_viewed($forum);

if (!empty($CFG->enablerssfeeds) && !empty($CFG->forum_enablerssfeeds) && $forum->rsstype && $forum->rssarticles) {
    require_once("{$CFG->libdir}/rsslib.php");

    $rsstitle = format_string($course->shortname, true, [
            'context' => context_course::instance($course->id),
        ]) . ': ' . format_string($forum->name);
    rss_add_http_header($forum->get_context(), 'mod_forum', $forum, $rsstitle);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($forum->get_name()), 2);

$rendererfactory = mod_forum\local\container::get_renderer_factory();
$discussionlistrenderer = $rendererfactory->get_discussion_list_renderer($forum);
$groupid = groups_get_activity_group($cm);
if (false === $groupid) {
    $groupid = null;
}
echo $discussionlistrenderer->render($USER, $groupid, $sortorder, $pageno, $pagesize);

echo $OUTPUT->footer();
