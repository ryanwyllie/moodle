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
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\serializers\discussion as discussion_serializer;
use mod_forum\local\serializers\forum as forum_serializer;
use mod_forum\local\vaults\author as author_vault;
use context;
use stdClass;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Forum class.
 */
class post implements db_serializer_interface {
    private $authorvault;
    private $discussionserializer;
    private $forumserializer;

    public function __construct(
        author_vault $authorvault,
        discussion_serializer $discussionserializer,
        forum_serializer $forumserializer
    ) {
        $this->authorvault = $authorvault;
        $this->discussionserializer = $discussionserializer;
        $this->forumserializer = $forumserializer;
    }

    public function from_db_records(array $records) : array {
        $authorids = array_keys(array_reduce($records, function($carry, $record) {
            $carry[$record->userid] = true;
            return $carry;
        }, []));
        $authors = $this->authorvault->get_from_ids($authorids);
        $authorsbyid = array_reduce($authors, function($carry, $author) {
            $carry[$author->get_id()] = $author;
            return $carry;
        }, []);

        return array_map(function(stdClass $record) use ($authorsbyid) {
            return new post_entity(
                $record->id,
                $record->discussion,
                $record->parent,
                $authorsbyid[$record->userid],
                $record->created,
                $record->modified,
                $record->mailed,
                $record->subject,
                $record->message,
                $record->messageformat,
                $record->messagetrust,
                $record->attachment,
                $record->totalscore,
                $record->mailnow,
                $record->deleted
            );
        }, $records);
    }

    public function to_db_records(array $posts) : array {
        return array_map(function(post_entity $post) {
            return (object) [
                'id' => $post->get_id(),
                'discussion' => $post->get_discussion_id(),
                'parent' => $post->get_parent_id(),
                'userid' => $post->get_author()->get_id(),
                'created' => $post->get_time_created(),
                'modified' => $post->get_time_modified(),
                'mailed' => $post->has_been_mailed(),
                'subject' => $post->get_subject(),
                'message' => $post->get_message(),
                'messageformat' => $post->get_message_format(),
                'messagetrust' => $post->is_message_trusted(),
                'attachment' => $post->get_attachment(),
                'totalscore' => $post->get_total_score(),
                'mailnow' => $post->should_mail_now(),
                'deleted' => $post->is_deleted()
            ];
        }, $posts);
    }

    public function for_display(
        stdClass $user,
        context $context,
        forum_entity $forum,
        discussion_entity $discussion,
        array $posts
    ) {
        $forumrecord = $this->forumserializer->to_db_records([$forum])[0];
        $discussionrecord = $this->discussionserializer->to_db_records([$discussion])[0];
        $sortedposts = $this->sort_posts_into_replies($posts);
        $coursemodule = get_coursemodule_from_instance('forum', $forum->get_id(), $forum->get_course_id());

        return array_map(function($sortedpost) use ($user, $forumrecord, $discussionrecord, $coursemodule) {
            list($post, $replies) = $sortedpost;
            return $this->transform_posts_to_display_object(
                $user,
                $forumrecord,
                $discussionrecord,
                $coursemodule,
                $post,
                $replies
            );
        }, $sortedposts);
    }

    private function sort_posts_into_replies($posts) : array {
        $postids = array_map(function($post) {
            return $post->get_id();
        }, $posts);

        list($parents, $replies) = array_reduce($posts, function($carry, $post) use ($postids) {
            $parentid = $post->get_parent_id();

            if (in_array($parentid, $postids)) {
                // This post is a reply to another post in the list so add it to the replies list.
                $carry[1][] = $post;
            } else {
                // This post isn't replying to anything in our list so it's a parent.
                $carry[0][] = $post;
            }

            return $carry;
        }, [[], []]);

        if (empty($replies)) {
            return array_map(function($parent) {
                return [$parent, []];
            }, $parents);
        }

        $sortedreplies = $this->sort_posts_into_replies($replies);

        return array_map(function($parent) use ($sortedreplies) {
            return [
                $parent,
                array_filter($sortedreplies, function($replydata) use ($parent) {
                    return $replydata[0]->get_parent_id() == $parent->get_id();
                })
            ];
        }, $parents);
    }

    private function transform_posts_to_display_object(
        stdClass $user,
        stdClass $forumrecord,
        stdClass $discussionrecord,
        stdClass $coursemodule,
        post_entity $post,
        array $replies
    ) : array {
        $author = $post->get_author();
        $postrecord = $this->to_db_records([$post])[0];
        $canseepost = forum_user_can_see_post($forumrecord, $discussionrecord, $postrecord, $user, $coursemodule);

        $subject = $canseepost ? $post->get_subject() : get_string('forumsubjecthidden','forum');
        $message = $canseepost ? format_text($post->get_message(), $post->get_message_format()) : get_string('forumbodyhidden','forum');
        $authorid = $canseepost ? $author->get_id() : null;
        $fullname = $canseepost ? $author->get_full_name() : get_string('forumauthorhidden', 'forum');
        $profileurl = $canseepost ? $author->get_profile_url()->out(false) : null;
        $profileimageurl = $canseepost ? $author->get_profile_image_url()->out(false) : null;
        $timecreated = $canseepost ? $post->get_time_created() : null;

        return [
            'id' => $post->get_id(),
            'subject' => $subject,
            'message' => $message,
            'author' => [
                'id' => $authorid,
                'fullname' => $fullname,
                'profileurl' => $profileurl,
                'profileimageurl' => $profileimageurl
            ],
            'parentid' => $post->has_parent() ? $post->get_parent_id() : null,
            'timecreated' => $timecreated,
            'cansee' => $canseepost,
            'replies' => array_map(function($replydata) use ($user, $forumrecord, $discussionrecord, $coursemodule) {
                list($reply, $replyreplies) = $replydata;
                return $this->transform_posts_to_display_object(
                    $user,
                    $forumrecord,
                    $discussionrecord,
                    $coursemodule,
                    $reply,
                    $replyreplies
                );
            }, $replies)
        ];
    }
}
