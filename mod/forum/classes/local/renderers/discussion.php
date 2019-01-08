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
 * Nested discussion renderer.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\renderers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\factories\database_serializer as database_serializer_factory;
use mod_forum\local\factories\exporter_serializer as exporter_serializer_factory;
use mod_forum\local\factories\vault as vault_factory;
use context;
use html_writer;
use moodle_url;
use renderer_base;
use single_select;
use stdClass;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Nested discussion renderer class.
 */
class discussion {
    private $renderer;
    private $displaymode;
    private $databaseserializerfactory;
    private $exporterserializerfactory;
    private $vaultfactory;
    private $baseurl;
    private $canshowdisplaymodeselector;
    private $canshowmovediscussion;
    private $canshowsubscription;
    private $getnotificationscallback;

    public function __construct(
        renderer_base $renderer,
        database_serializer_factory $databaseserializerfactory,
        exporter_serializer_factory $exporterserializerfactory,
        vault_factory $vaultfactory,
        int $displaymode,
        moodle_url $baseurl,
        bool $canshowdisplaymodeselector = true,
        bool $canshowmovediscussion = true,
        bool $canshowsubscription = true,
        callable $getnotificationscallback = null
    ) {
        $this->renderer = $renderer;
        $this->displaymode = $displaymode;
        $this->baseurl = $baseurl;
        $this->canshowdisplaymodeselector = $canshowdisplaymodeselector;
        $this->canshowmovediscussion = $canshowmovediscussion;
        $this->canshowsubscription = $canshowsubscription;
        $this->databaseserializerfactory = $databaseserializerfactory;
        $this->exporterserializerfactory = $exporterserializerfactory;
        $this->vaultfactory = $vaultfactory;

        if (is_null($getnotificationscallback)) {
            $getnotificationscallback = function() {
                return [];
            };
        }

        $this->getnotificationscallback = $getnotificationscallback;
    }

    public function render(stdClass $user, context $context, forum_entity $forum, discussion_entity $discussion) : string {
        global $PAGE, $USER;

        // Make sure we can render.
        $this->validate_render($context);

        $nestedposts = $this->get_exported_posts($user, $context, $forum, $discussion);
        $exporteddiscussion = $this->get_exported_discussion($discussion, $nestedposts);
        $exporteddiscussion = array_merge($exporteddiscussion, [
            'html' => [
                'modeselectorform' => null,
                'notifications' => ($this->getnotificationscallback)($user, $context, $forum, $discussion),
                'subscribe' => null
            ]
        ]);

        if ($this->canshowdisplaymodeselector) {
            $exporteddiscussion['html']['modeselectorform'] = $this->get_display_mode_selector_html($this->baseurl);
        }

        $forumserializer = $this->databaseserializerfactory->get_forum_serializer();
        $forumrecord = $forumserializer->to_db_records([$forum])[0];

        if ($this->canshowsubscription) {
            $exporteddiscussion['html']['subscribe'] = $this->get_subscription_button_html($forumrecord, $discussion);
        }

        return $this->renderer->render_from_template($this->get_template($this->displaymode), $exporteddiscussion);
    }

    private function validate_render(context $context) {
        require_capability('mod/forum:viewdiscussion', $context, NULL, true, 'noviewdiscussionspermission', 'forum');
    }

    private function get_template(int $displaymode) : string {
        switch ($displaymode) {
            case FORUM_MODE_FLATOLDEST:
                return 'mod_forum/forum_discussion_flat_posts';
            case FORUM_MODE_FLATNEWEST:
                return 'mod_forum/forum_discussion_flat_posts';
            case FORUM_MODE_THREADED:
                return 'mod_forum/forum_discussion_threaded_posts';
            case FORUM_MODE_NESTED:
                return 'mod_forum/forum_discussion_nested_posts';
            default;
                return 'mod_forum/forum_discussion_nested_posts';
        }
    }

    private function get_order_by(int $displaymode) : string {
        switch ($displaymode) {
            case FORUM_MODE_FLATNEWEST:
                return 'created DESC';
            default;
                return 'created ASC';
        }
    }

    private function get_exported_posts(
        stdClass $user,
        context $context,
        forum_entity $forum,
        discussion_entity $discussion
    ) : array {
        $postvault = $this->vaultfactory->get_post_vault();
        $posts = $postvault->get_from_discussion_id($discussion->get_id(), $this->get_order_by($this->displaymode));
        $postexporter = $this->exporterserializerfactory->get_posts_exporter(
            $user,
            $context,
            $forum,
            $discussion,
            $posts
        );
        ['posts' => $exportedposts] = (array) $postexporter->export($this->renderer);

        $nestedposts = [];
        $sortintoreplies = function($candidate, $unsorted) use (&$sortintoreplies) {
            if (!isset($candidate->replies)) {
                $candidate->replies = [];
            }

            if (empty($unsorted)) {
                return [$candidate, $unsorted];
            }

            $next = array_shift($unsorted);

            if ($next->parentid == $candidate->id) {
                [$next, $unsorted] = $sortintoreplies($next, $unsorted);
                $candidate->replies[] = $next;

                return $sortintoreplies($candidate, $unsorted);
            } else {
                array_unshift($unsorted, $next);
                return [$candidate, $unsorted];
            }
        };

        do {
            $candidate = array_shift($exportedposts);
            [$candidate, $exportedposts] = $sortintoreplies($candidate, $exportedposts);
            $nestedposts[] = $candidate;
        } while (!empty($exportedposts));

        return $nestedposts;
    }

    private function get_exported_discussion(discussion_entity $discussion, array $posts) : array {
        $discussionexporter = $this->exporterserializerfactory->get_discussion_exporter(
            $discussion,
            $posts
        );

        return (array) $discussionexporter->export($this->renderer);
    }

    private function get_display_mode_selector_html(moodle_url $baseurl) : string {
        $select = new single_select(
            $baseurl,
            'mode',
            forum_get_layout_modes(),
            $this->displaymode,
            null,
            'mode'
        );
        $select->set_label(get_string('displaymode', 'forum'), ['class' => 'accesshide']);

        return $this->renderer->render($select);
    }

    private function get_subscription_button_html(stdClass $forumrecord, discussion_entity $discussion) : string {
        global $PAGE;

        $html = html_writer::div(
            forum_get_discussion_subscription_icon($forumrecord, $discussion->get_id(), null, true),
            'discussionsubscription'
        );
        $html .= forum_get_discussion_subscription_icon_preloaders();
        // Add the subscription toggle JS.
        $PAGE->requires->yui_module('moodle-mod_forum-subscriptiontoggle', 'Y.M.mod_forum.subscriptiontoggle.init');
        return $html;
    }
}
