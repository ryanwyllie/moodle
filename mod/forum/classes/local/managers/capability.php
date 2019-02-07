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
 * Capability manager for the forum.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\managers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\data_mappers\legacy\forum as legacy_forum_data_mapper;
use mod_forum\local\data_mappers\legacy\discussion as legacy_discussion_data_mapper;
use mod_forum\local\data_mappers\legacy\post as legacy_post_data_mapper;
use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\subscriptions;
use context;
use context_system;
use stdClass;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Capability manager for the forum. Defines all the business rules for what
 * a user can and can't do in the forum.
 */
class capability {
    private $forumdatamapper;
    private $discussiondatamapper;
    private $postdatamapper;
    private $forum;
    private $forumrecord;
    private $context;

    public function __construct(
        forum_entity $forum,
        legacy_forum_data_mapper $forumdatamapper,
        legacy_discussion_data_mapper $discussiondatamapper,
        legacy_post_data_mapper $postdatamapper
    ) {
        $this->forumdatamapper = $forumdatamapper;
        $this->discussiondatamapper = $discussiondatamapper;
        $this->postdatamapper = $postdatamapper;
        $this->forum = $forum;
        $this->forumrecord = $forumdatamapper->to_legacy_object($forum);
        $this->context = $forum->get_context();
    }

    public function can_subscribe_to_forum(stdClass $user) : bool {
        return !is_guest($this->get_context(), $user) &&
            subscriptions::is_subscribable($this->get_forum_record());
    }

    public function can_create_discussions(stdClass $user, ?int $groupid) : bool {
        if (isguestuser($user) or !isloggedin()) {
            return false;
        }

        switch ($this->forum->get_type()) {
            case 'news':
                $capability = 'mod/forum:addnews';
                break;
            case 'qanda':
                $capability = 'mod/forum:addquestion';
                break;
            default:
                $capability = 'mod/forum:startdiscussion';
        }

        if (!has_capability($capability, $this->forum->get_context(), $user)) {
            return false;
        }

        return $this->can_post_to_group($user, $groupid);
    }

    public function can_access_all_groups(stdClass $user) {
        return has_capability('moodle/site:accessallgroups', $this->get_context(), $user);
    }

    public function can_post_to_group(\stdClass $user, ?int $groupid) {
        if (empty($this->forum->get_effective_group_mode()) || $this->forum->get_effective_group_mode() === NOGROUPS) {
            // This discussion is not in a group mode.
            return true;
        }

        if ($this->can_access_all_groups($user)) {
            // This user has access to all groups.
            return true;
        }

        if (null === $groupid) {
            return $this->can_access_all_groups($user);
        }

        // This is a group discussion for a forum in separate groups mode.
        // Check if the user is a member.
        // This is the most expensive check.
        return groups_is_member($groupid, $user->id);
    }

    public function can_view_discussions(stdClass $user) : bool {
        return has_capability('mod/forum:viewdiscussion', $this->get_context(), $user);
    }

    public function can_move_discussions(stdClass $user) : bool {
        $forum = $this->get_forum();
        return $forum->get_type() !== 'single' && has_capability('mod/forum:movediscussions', $this->get_context(), $user);
    }

    public function can_view_discussions_without_posting(stdClass $user) : bool {
        $forum = $this->get_forum();

        return $this->can_view_discussion($user, $forum) &&
            (
                $forum->get_type() !== 'qanda' ||
                has_capability('mod/forum:viewqandawithoutposting', $this->get_context(), $user)
            );
    }

    public function can_pin_discussions(stdClass $user) : bool {
        return has_capability('mod/forum:pindiscussions', $this->get_context(), $user);
    }

    public function can_split_discussions(stdClass $user) : bool {
        $forum = $this->get_forum();
        return $forum->get_type() !== 'single' && has_capability('mod/forum:splitdiscussions', $this->get_context(), $user);
    }

    public function can_export_discussions(stdClass $user) : bool {
        return has_capability('mod/forum:exportdiscussion', $this->get_context(), $user);
    }

    public function must_post_before_viewing_discussion(stdClass $user, discussion_entity $discussion) : bool {
        return !$this->can_view_discussions_without_posting($user) &&
            !forum_user_has_posted($this->get_forum()->get_id(), $discussion->get_id(), $user->id);
    }

    public function can_subscribe_to_discussion(stdClass $user, discussion_entity $discussion) : bool {
        return $this->can_subscribe_to_forum($user);
    }

    public function can_move_discussion(stdClass $user, discussion_entity $discussion) : bool {
        return $this->can_move_discussions($user);
    }

    public function can_pin_discussion(stdClass $user, discussion_entity $discussion) : bool {
        return $this->can_pin_discussions($user);
    }

    public function can_post_in_discussion(stdClass $user, discussion_entity $discussion) : bool {
        $forum = $this->get_forum();
        $forumrecord = $this->get_forum_record();
        $discussionrecord = $this->get_discussion_record($discussion);
        $context = $this->get_context();
        $coursemodule = $forum->get_course_module_record();
        $course = $forum->get_course_record();

        return forum_user_can_post($forumrecord, $discussionrecord, $user, $coursemodule, $course, $context);
    }

    public function can_view_post(stdClass $user, discussion_entity $discussion, post_entity $post) : bool {
        $forum = $this->get_forum();
        $forumrecord = $this->get_forum_record();
        $discussionrecord = $this->get_discussion_record($discussion);
        $postrecord = $this->get_post_record($post);
        $coursemodule = $forum->get_course_module_record();
        return forum_user_can_see_post($forumrecord, $discussionrecord, $postrecord, $user, $coursemodule, false);
    }

    public function can_edit_post(stdClass $user, discussion_entity $discussion, post_entity $post) : bool {
        global $CFG;

        $context = $this->get_context();
        $ownpost = $post->is_owned_by_user($user);
        $ineditingtime = $post->get_age() < $CFG->maxeditingtime;

        switch ($this->forum->get_type()) {
            case 'news':
                // Allow editing of news posts once the discussion has started.
                $ineditingtime = !$post->has_parent() && $discussion->has_started();
                break;
            case 'single':
                return $discussion->is_first_post($post) && has_capability('moodle/course:manageactivities', $context, $user);
        }

        return ($ownpost && $ineditingtime) || has_capability('mod/forum:editanypost', $context, $user);
    }

    public function can_delete_post(stdClass $user, discussion_entity $discussion, post_entity $post) : bool {
        global $CFG;

        $forum = $this->get_forum();

        if ($forum->get_type() == 'single' && $discussion->is_first_post($post)) {
            // Do not allow deleting of first post in single simple type.
            return false;
        } else {
            $context = $this->get_context();
            $ownpost = $post->is_owned_by_user($user);
            $ineditingtime = $post->get_age() < $CFG->maxeditingtime;

            return ($ownpost && $ineditingtime && has_capability('mod/forum:deleteownpost', $context, $user)) ||
                has_capability('mod/forum:deleteanypost', $context, $user);
        }
    }

    public function can_split_post(stdClass $user, discussion_entity $discussion, post_entity $post) : bool {
        return $this->can_split_discussions($user) && $post->has_parent();
    }

    public function can_reply_to_post(stdClass $user, discussion_entity $discussion, post_entity $post) : bool {
        return $this->can_post_in_discussion($user, $discussion);
    }

    public function can_export_post(stdClass $user, post_entity $post) : bool {
        $context = $this->get_context();
        return has_capability('mod/forum:exportpost', $context, $user) ||
            ($post->is_owned_by_user($user) && has_capability('mod/forum:exportownpost', $context, $user));
    }

    protected function get_forum() : forum_entity {
        return $this->forum;
    }

    protected function get_forum_record() : stdClass {
        return $this->forumrecord;
    }

    protected function get_context() : context {
        return $this->context;
    }

    protected function get_discussion_record(discussion_entity $discussion) : stdClass {
        return $this->discussiondatamapper->to_legacy_object($discussion);
    }

    protected function get_post_record(post_entity $post) : stdClass {
        return $this->postdatamapper->to_legacy_object($post);
    }

    public function can_view_participants(stdClass $user, discussion_entity $discussion) : bool {
        $result = course_can_view_participants($this->get_context());

        if ($this->forum->get_type() === 'qanda') {
            if (!has_capability('mod/forum:viewqandawithoutposting', $this->get_context(), $user)) {
                $result = false;
            }

            if ($result && !forum_user_has_posted($this->get_forum()->get_id(), $discussion->get_id(), $user->id)) {
                $result = false;
            }
        }

        return $result;
    }

    public function can_view_hidden_posts(stdClass $user) : bool {
        return has_capability('mod/forum:viewhiddentimedposts', $this->get_context(), $user);
    }

    public function can_manage_forum(stdClass $user) {
        return has_capability('moodle/course:manageactivities', $this->get_context(), $user);
    }

    public function can_manage_tags(stdClass $user) : bool {
        return has_capability('moodle/tag:manage', context_system::instance(), $user);
    }
}
