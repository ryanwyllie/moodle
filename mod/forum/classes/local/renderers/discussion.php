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
use mod_forum\local\factories\database_data_mapper as database_data_mapper_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\vault as vault_factory;
use context;
use context_module;
use html_writer;
use moodle_url;
use renderer_base;
use single_button;
use single_select;
use stdClass;
use url_select;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Nested discussion renderer class.
 */
class discussion {
    private $discussion;
    private $forum;
    private $renderer;
    private $databasedatamapperfactory;
    private $exporterfactory;
    private $vaultfactory;
    private $baseurl;
    private $canshowdisplaymodeselector;
    private $canshowmovediscussion;
    private $canshowpindiscussion;
    private $canshowsubscription;
    private $getnotificationscallback;

    public function __construct(
        discussion_entity $discussion,
        forum_entity $forum,
        renderer_base $renderer,
        database_data_mapper_factory $databasedatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory,
        moodle_url $baseurl,
        bool $canshowdisplaymodeselector = true,
        bool $canshowmovediscussion = true,
        bool $canshowpindiscussion = true,
        bool $canshowsubscription = true,
        callable $getnotificationscallback = null
    ) {
        $this->discussion = $discussion;
        $this->forum = $forum;
        $this->renderer = $renderer;
        $this->baseurl = $baseurl;
        $this->canshowdisplaymodeselector = $canshowdisplaymodeselector;
        $this->canshowmovediscussion = $canshowmovediscussion;
        $this->canshowpindiscussion = $canshowpindiscussion;
        $this->canshowsubscription = $canshowsubscription;
        $this->databasedatamapperfactory = $databasedatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;

        if (is_null($getnotificationscallback)) {
            $getnotificationscallback = function() {
                return [];
            };
        }

        $this->getnotificationscallback = $getnotificationscallback;
    }

    public function render(stdClass $user, int $displaymode) : string {
        global $PAGE, $USER;

        $discussion = $this->discussion;
        $forum = $this->forum;
        $context = $forum->get_context();

        // Make sure we can render.
        $this->validate_render($context);

        $nestedposts = $this->get_exported_posts($user, $context, $forum, $discussion, $displaymode);
        $exporteddiscussion = $this->get_exported_discussion($discussion, $nestedposts);
        $exporteddiscussion = array_merge($exporteddiscussion, [
            'html' => [
                'modeselectorform' => null,
                'notifications' => ($this->getnotificationscallback)($user, $context, $forum, $discussion),
                'subscribe' => null,
                'movediscussion' => null,
                'pindiscussion' => null
            ]
        ]);

        if ($this->should_show_display_mode_selector()) {
            $exporteddiscussion['html']['modeselectorform'] = $this->get_display_mode_selector_html($this->baseurl, $displaymode);
        }

        $forumdatamapper = $this->databasedatamapperfactory->get_forum_data_mapper();
        $forumrecord = $forumdatamapper->to_db_records([$forum])[0];

        if ($this->should_show_subscription_button($user, $context, $forumrecord)) {
            $exporteddiscussion['html']['subscribe'] = $this->get_subscription_button_html($forumrecord, $discussion);
        }

        if ($this->should_show_move_discussion($context)) {
            $exporteddiscussion['html']['movediscussion'] = $this->get_move_discussion_html($forum, $discussion);
        }

        if ($this->should_show_pin_discussion($context)) {
            $exporteddiscussion['html']['pindiscussion'] = $this->get_pin_discussion_html($discussion);
        }

        return $this->renderer->render_from_template($this->get_template($displaymode), $exporteddiscussion);
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
        discussion_entity $discussion,
        int $displaymode
    ) : array {
        $postvault = $this->vaultfactory->get_post_vault();
        $posts = $postvault->get_from_discussion_id($discussion->get_id(), $this->get_order_by($displaymode));
        $postexporter = $this->exporterfactory->get_posts_exporter(
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
        $discussionexporter = $this->exporterfactory->get_discussion_exporter(
            $discussion,
            $posts
        );

        return (array) $discussionexporter->export($this->renderer);
    }

    private function should_show_display_mode_selector() : bool {
        return $this->canshowdisplaymodeselector;
    }

    private function get_display_mode_selector_html(moodle_url $baseurl, int $displaymode) : string {
        $select = new single_select(
            $baseurl,
            'mode',
            forum_get_layout_modes(),
            $displaymode,
            null,
            'mode'
        );
        $select->set_label(get_string('displaymode', 'forum'), ['class' => 'accesshide']);

        $html = '<div class="discussioncontrol displaymode">';
        $html .= $this->renderer->render($select);
        $html .= '</div>';
        return $html;
    }

    private function should_show_subscription_button(stdClass $user, context $context, stdClass $forumrecord) : bool {
        return $this->canshowsubscription &&
                !is_guest($context, $user) &&
                isloggedin() &&
                has_capability('mod/forum:viewdiscussion', $context) &&
                \mod_forum\subscriptions::is_subscribable($forumrecord);
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

    private function should_show_move_discussion(context $context) : bool {
        return $this->canshowmovediscussion && has_capability('mod/forum:movediscussions', $context);
    }

    private function get_move_discussion_html(forum_entity $forum, discussion_entity $discussion) : string {
        global $DB;

        $courseid = $forum->get_course_id();

        $html = '<div class="discussioncontrol movediscussion">';
        // Popup menu to move discussions to other forums. The discussion in a
        // single discussion forum can't be moved.
        $modinfo = get_fast_modinfo($courseid);
        if (isset($modinfo->instances['forum'])) {
            $forummenu = [];
            // Check forum types and eliminate simple discussions.
            $forumcheck = $DB->get_records('forum', ['course' => $courseid],'', 'id, type');
            foreach ($modinfo->instances['forum'] as $forumcm) {
                if (!$forumcm->uservisible || !has_capability('mod/forum:startdiscussion',
                    context_module::instance($forumcm->id))) {
                    continue;
                }
                $section = $forumcm->sectionnum;
                $sectionname = get_section_name($courseid, $section);
                if (empty($forummenu[$section])) {
                    $forummenu[$section] = [$sectionname => []];
                }
                $forumidcompare = $forumcm->instance != $forum->get_id();
                $forumtypecheck = $forumcheck[$forumcm->instance]->type !== 'single';

                if ($forumidcompare and $forumtypecheck) {
                    $url = "/mod/forum/discuss.php?d={$discussion->get_id()}&move=$forumcm->instance&sesskey=".sesskey();
                    $forummenu[$section][$sectionname][$url] = format_string($forumcm->name);
                }
            }
            if (!empty($forummenu)) {
                $html .= '<div class="movediscussionoption">';
                $select = new url_select($forummenu, '',
                        ['/mod/forum/discuss.php?d=' . $discussion->get_id() => get_string("movethisdiscussionto", "forum")],
                        'forummenu', get_string('move'));
                $html .= $this->renderer->render($select);
                $html .= "</div>";
            }
        }
        $html .= "</div>";

        return $html;
    }

    private function should_show_pin_discussion(context $context) : bool {
        return $this->canshowpindiscussion && has_capability('mod/forum:pindiscussions', $context);
    }

    private function get_pin_discussion_html(discussion_entity $discussion) : string {
        if ($discussion->is_pinned()) {
            $pinlink = FORUM_DISCUSSION_UNPINNED;
            $pintext = get_string('discussionunpin', 'forum');
        } else {
            $pinlink = FORUM_DISCUSSION_PINNED;
            $pintext = get_string('discussionpin', 'forum');
        }

        $button = new single_button(new moodle_url('discuss.php', ['pin' => $pinlink, 'd' => $discussion->get_id()]), $pintext, 'post');
        return html_writer::tag('div', $this->renderer->render($button), ['class' => 'discussioncontrol pindiscussion']);
    }
}
