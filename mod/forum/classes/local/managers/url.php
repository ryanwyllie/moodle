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
 * A URL manager for the forum.
 *
 * @package    mod_forum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\managers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\author as author_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use moodle_url;
use user_picture;

/**
 * A URL manager for the forum.
 *
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url {
    private $forum;
    private $legacydatamapperfactory;

    public function __construct(forum_entity $forum, legacy_data_mapper_factory $legacydatamapperfactory) {
        $this->forum = $forum;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
    }

    public function get_course_url_from_courseid(int $courseid) : moodle_url {
        return new moodle_url('/course/view.php', [
            'id' => $courseid,
        ]);
    }

    public function get_course_url_from_forum(forum_entity $forum) : moodle_url {
        return $this->get_course_url_from_courseid($forum->get_course_id());
    }

    public function get_discussion_create_url(forum_entity $forum) : moodle_url {
        return new moodle_url('/mod/forum/post.php', [
            'forum' => $forum->get_id(),
        ]);
    }

    public function get_forum_view_url_from_forum(forum_entity $forum) : moodle_url {
        return $this->get_forum_view_url_from_course_module_id($forum->get_course_module_record()->id);
    }

    public function get_forum_view_url_from_course_module_id(int $coursemoduleid) : moodle_url {
        return new moodle_url('/mod/forum/discussions.php', [
            'id' => $coursemoduleid,
        ]);
    }

    public function get_discussion_view_url_from_discussion_id(int $discussionid) : moodle_url {
        return new moodle_url('/mod/forum/discuss.php', [
            'd' => $discussionid
        ]);
    }

    public function get_discussion_view_url_from_discussion(discussion_entity $discussion) : moodle_url {
        return $this->get_discussion_view_url_from_discussion_id($discussion->get_id());
    }

    public function get_discussion_view_url_from_post(post_entity $post) : moodle_url {
        return $this->get_discussion_view_url_from_discussion_id($post->get_discussion_id());
    }

    public function get_view_post_url_from_post_id(int $discussionid, int $postid) : moodle_url {
        $url = $this->get_discussion_view_url_from_discussion_id($discussionid);
        $url->set_anchor('p' . $postid);
        return $url;
    }

    public function get_view_post_url_from_post(post_entity $post) : moodle_url {
        return $this->get_view_post_url_from_post_id($post->get_discussion_id(), $post->get_id());
    }

    public function get_edit_post_url_from_post(post_entity $post) : moodle_url {
        if ($this->forum->get_type() == 'single') {
            return new moodle_url('/course/modedit.php', [
                'update' => $this->forum->get_course_module_record()->id,
                'sesskey' => sesskey(),
                'return' => 1
            ]);
        } else {
            return new moodle_url('/mod/forum/post.php', [
                'edit' => $post->get_id()
            ]);
        }
    }

    public function get_split_discussion_at_post_url_from_post(post_entity $post) : moodle_url {
        return new moodle_url('/mod/forum/post.php', [
            'prune' => $post->get_id()
        ]);
    }

    public function get_delete_post_url_from_post(post_entity $post) : moodle_url {
        return new moodle_url('/mod/forum/post.php', [
            'delete' => $post->get_id()
        ]);
    }

    public function get_reply_to_post_url_from_post(post_entity $post) : moodle_url {
        return new moodle_url('/mod/forum/post.php#mformforum', [
            'reply' => $post->get_id()
        ]);
    }

    public function get_author_profile_url(author_entity $author) : moodle_url {
        return new moodle_url('/user/view.php', [
            'id' => $author->get_id()
        ]);
    }

    public function get_author_profile_image_url(author_entity $author) : moodle_url {
        global $PAGE;

        $datamapper = $this->legacydatamapperfactory->get_author_data_mapper();
        $record = $datamapper->to_legacy_object($author);
        $userpicture = new user_picture($record);
        $userpicture->size = 2;

        return $userpicture->get_url($PAGE);
    }
}
