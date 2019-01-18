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

use mod_forum\local\data_mappers\database\db_data_mapper_interface;
use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\subscriptions;
use stdClass;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Capability manager for the forum. Defines all the business rules for what
 * a user can and can't do in the forum.
 */
class capability {
    private $forumdbdatamapper;
    private $discussiondbdatamapper;

    public function __construct(
        db_data_mapper_interface $forumdbdatamapper,
        db_data_mapper_interface $discussiondbdatamapper
    ) {
        $this->forumdbdatamapper = $forumdbdatamapper;
        $this->discussiondbdatamapper = $discussiondbdatamapper;
    }

    public function can_subscribe(stdClass $user, forum_entity $forum) : bool {
        return !is_guest($forum->get_context(), $user) &&
            subscriptions::is_subscribable($this->get_forum_record($forum));
    }

    public function can_view_discussion(stdClass $user, forum_entity $forum) : bool {
        return has_capability('mod/forum:viewdiscussion', $forum->get_context(), $user);
    }

    public function can_move_discussion(stdClass $user, forum_entity $forum) : bool {
        return $forum->get_type() !== 'single' && has_capability('mod/forum:movediscussions', $forum->get_context());
    }

    public function can_view_discussion_without_posting(stdClass $user, forum_entity $forum) : bool {
        return $this->can_view_discussion($user, $forum) &&
            (
                $forum->get_type() !== 'qanda' ||
                has_capability('mod/forum:viewqandawithoutposting', $forum->get_context(), $user)
            );
    }

    public function can_pin_discussion(stdClass $user, forum_entity $forum) : bool {
        return has_capability('mod/forum:pindiscussions', $forum->get_context());
    }

    public function must_post_before_viewing_discussion(stdClass $user, forum_entity $forum, discussion_entity $discussion) : bool {
        return !$this->can_view_discussion_without_posting($forum, $user) &&
            !forum_user_has_posted($forum->get_id(), $discussion->get_id(), $user->id);
    }

    private function get_forum_record(forum_entity $forum) : stdClass {
        return $this->forumdbdatamapper->to_db_records([$forum])[0];
    }

    private function get_discussion_record(discussion_entity $discussion) : stdClass {
        return $this->discussiondbdatamapper->to_db_records([$discussion])[0];
    }
}
