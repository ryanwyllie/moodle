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
use stored_file;
use user_picture;

require_once($CFG->dirroot . '/mod/forum/lib.php');

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

    public function get_forum_view_url_from_forum(forum_entity $forum, ?int $pageno = null) : moodle_url {
        return $this->get_forum_view_url_from_course_module_id($forum->get_course_module_record()->id, $pageno);
    }

    public function get_forum_view_url_from_course_module_id(int $coursemoduleid, ?int $pageno = null) : moodle_url {
        $url = new moodle_url('/mod/forum/discussions.php', [
            'id' => $coursemoduleid,
        ]);

        if (null !== $pageno) {
            $url->param('page', $pageno);
        }

        return $url;
    }

    public function get_discussion_view_url_from_discussion_id(int $discussionid) : moodle_url {
        return new moodle_url('/mod/forum/discuss.php', [
            'd' => $discussionid
        ]);
    }

    public function get_discussion_view_url_from_discussion(discussion_entity $discussion) : moodle_url {
        return $this->get_discussion_view_url_from_discussion_id($discussion->get_id());
    }

    public function get_discussion_view_first_unread_post_url_from_discussion(discussion_entity $discussion) {
        $viewurl = $this->get_discussion_view_url_from_discussion_id($discussion->get_id());
        $viewurl->set_anchor('unread');

        return $viewurl;
    }

    public function get_discussion_view_latest_post_url_from_discussion_and_discussion(discussion_entity $discussion, ?int $latestpost) {
        $viewurl = $this->get_discussion_view_url_from_discussion_id($discussion->get_id());
        if (null === $latestpost) {
            return $viewurl;
        } else {
            return new moodle_url($viewurl, ['parent' => $latestpost]);
        }
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

    public function get_view_isolated_post_url_from_post_id(int $discussionid, int $postid) : moodle_url {
        $url = $this->get_discussion_view_url_from_discussion_id($discussionid);
        $url->params(['parent' => $postid]);
        return $url;
    }

    public function get_view_isolated_post_url_from_post(post_entity $post) : moodle_url {
        return $this->get_view_isolated_post_url_from_post_id($post->get_discussion_id(), $post->get_id());
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

    public function get_export_post_url_from_post(post_entity $post) : ?moodle_url {
        global $CFG;

        require_once($CFG->libdir . '/portfoliolib.php');
        $button = new \portfolio_add_button();
        $button->set_callback_options('forum_portfolio_caller', ['postid' => $post->get_id()], 'mod_forum');
        if ($post->has_attachments()) {
            $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
        } else {
            $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
        }

        $url = $button->to_html(PORTFOLIO_ADD_MOODLE_URL);
        return $url ?: null;
    }

    public function get_mark_post_as_read_url_from_post(post_entity $post, int $displaymode = FORUM_MODE_THREADED) : moodle_url {
        $params = [
            'd' => $post->get_discussion_id(),
            'postid' => $post->get_id(),
            'mark' => 'read'
        ];

        $url = new moodle_url('/mod/forum/discuss.php', $params);

        if ($displaymode == FORUM_MODE_THREADED) {
            $url->param('parent', $post->get_parent_id());
        } else {
            $url->set_anchor('p' . $post->get_id());
        }

        return $url;
    }

    public function get_mark_post_as_unread_url_from_post(post_entity $post, int $displaymode = FORUM_MODE_THREADED) : moodle_url {
        $params = [
            'd' => $post->get_discussion_id(),
            'postid' => $post->get_id(),
            'mark' => 'unread'
        ];

        $url = new moodle_url('/mod/forum/discuss.php', $params);

        if ($displaymode == FORUM_MODE_THREADED) {
            $url->param('parent', $post->get_parent_id());
        } else {
            $url->set_anchor('p' . $post->get_id());
        }

        return $url;
    }

    public function get_export_attachment_url_from_post_and_attachment(post_entity $post, stored_file $attachment) : ?moodle_url {
        global $CFG;

        require_once($CFG->libdir . '/portfoliolib.php');
        $button = new \portfolio_add_button();
        $button->set_callback_options('forum_portfolio_caller', ['postid' => $post->get_id(), 'attachment' => $attachment->get_id()], 'mod_forum');
        $button->set_format_by_file($attachment);
        $url = $button->to_html(PORTFOLIO_ADD_MOODLE_URL);
        return $url ?: null;
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

    public function get_mark_discussion_as_read_url_from_discussion(discussion_entity $discussion) : moodle_url {
        return new moodle_url('/mod/forum/markposts.php', [
            'f' => $discussion->get_forum_id(),
            'd' => $discussion->get_id(),
            'mark' => 'read',
            'sesskey' => sesskey(),
            'return' => $this->get_forum_view_url_from_forum($this->forum)->out(),
        ]);
    }

    public function get_mark_all_discussions_as_read_url() : moodle_url {
        return new moodle_url('/mod/forum/markposts.php', [
            'f' => $this->forum->get_id(),
            'mark' => 'read',
            'sesskey' => sesskey(),
            'return' => $this->get_forum_view_url_from_forum($this->forum)->out(),
        ]);
    }
}
