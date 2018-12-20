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
 * Forum class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\serializers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\serializers\post as post_serializer;
use context;
use stdClass;

/**
 * Forum class.
 */
class discussion implements serializer_interface {

    public function from_db_records(array $records) : array {
        return array_map(function(stdClass $record) {
            return new discussion_entity(
                $record->id,
                $record->course,
                $record->forum,
                $record->name,
                $record->firstpost,
                $record->userid,
                $record->groupid,
                $record->assessed,
                $record->timemodified,
                $record->usermodified,
                $record->timestart,
                $record->timeend,
                $record->pinned
            );
        }, $records);
    }

    public function to_db_records(array $discussions) : array {
        return array_map(function(discussion_entity $discussion) {
            return (object) [
                'id' => $discussion->get_id(),
                'course' => $discussion->get_course_id(),
                'forum' => $discussion->get_forum_id(),
                'name' => $discussion->get_name(),
                'firstpost' => $discussion->get_first_post_id(),
                'userid' => $discussion->get_user_id(),
                'groupid' => $discussion->get_group_id(),
                'assessed' => $discussion->is_assessed(),
                'timemodified' => $discussion->get_time_modified(),
                'usermodified' => $discussion->get_user_modified(),
                'timestart' => $discussion->get_time_start(),
                'timeend' => $discussion->get_time_end(),
                'pinned' => $discussion->is_pinned()
            ];
        }, $discussions);
    }

    public function for_display(stdClass $user, context $context, forum_entity $forum, discussion_entity $discussion, array $posts) {
        $postserializer = new post_serializer();
        $serialisedposts = $postserializer->for_display($user, $context, $forum, $discussion, $posts);

        return [
            'id' => $discussion->get_id(),
            'posts' => $serialisedposts
        ];
    }
}
