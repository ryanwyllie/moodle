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
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\managers\capability as capability_manager;
use core\output\notification;
use context;
use context_module;
use html_writer;
use moodle_exception;
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
    private $discussionrecord;
    private $forum;
    private $forumrecord;
    private $renderer;
    private $legacydatamapperfactory;
    private $exporterfactory;
    private $vaultfactory;
    private $capabilitymanager;
    private $baseurl;
    private $notifications;

    public function __construct(
        discussion_entity $discussion,
        forum_entity $forum,
        renderer_base $renderer,
        legacy_data_mapper_factory $legacydatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory,
        capability_manager $capabilitymanager,
        moodle_url $baseurl,
        array $notifications = []
    ) {
        $this->discussion = $discussion;
        $this->forum = $forum;
        $this->renderer = $renderer;
        $this->baseurl = $baseurl;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->capabilitymanager = $capabilitymanager;
        $this->notifications = $notifications;

        $forumdatamapper = $this->legacydatamapperfactory->get_forum_data_mapper();
        $this->forumrecord = $forumdatamapper->to_legacy_object($forum);

        $discussiondatamapper = $this->legacydatamapperfactory->get_discussion_data_mapper();
        $this->discussionrecord = $discussiondatamapper->to_legacy_object($discussion);
    }

    public function render(stdClass $user, int $displaymode, post_entity $post) : string {
        global $PAGE, $USER;

        $capabilitymanager = $this->capabilitymanager;
        $forum = $this->forum;

        // Make sure we can render.
        if (!$capabilitymanager->can_view_discussions($user)) {
            throw new moodle_exception('noviewdiscussionspermission', 'mod_forum');
        }

        $nestedposts = $this->get_exported_posts($user, $displaymode);
        $exporteddiscussion = $this->get_exported_discussion($nestedposts);
        $exporteddiscussion = array_merge($exporteddiscussion, [
            'html' => [
                'modeselectorform' => $this->get_display_mode_selector_html($displaymode),
                'notifications' => $this->get_notifications(),
                'subscribe' => null,
                'movediscussion' => null,
                'pindiscussion' => null,
                'neighbourlinks' => $this->get_neighbour_links_html()
            ]
        ]);

        if ($capabilitymanager->can_subscribe($user)) {
            $exporteddiscussion['html']['subscribe'] = $this->get_subscription_button_html();
        }

        if ($capabilitymanager->can_move_discussions($user)) {
            $exporteddiscussion['html']['movediscussion'] = $this->get_move_discussion_html();
        }

        if ($capabilitymanager->can_pin_discussions($user)) {
            $exporteddiscussion['html']['pindiscussion'] = $this->get_pin_discussion_html();
        }

        return $this->renderer->render_from_template($this->get_template($displaymode), $exporteddiscussion);
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

    private function get_exported_posts(stdClass $user, int $displaymode) : array {
        $forum = $this->forum;
        $discussion = $this->discussion;
        $context = $forum->get_context();
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

    private function get_exported_discussion(array $posts) : array {
        $discussion = $this->discussion;
        $discussionexporter = $this->exporterfactory->get_discussion_exporter(
            $discussion,
            $posts
        );

        return (array) $discussionexporter->export($this->renderer);
    }

    private function get_display_mode_selector_html(int $displaymode) : string {
        $baseurl = $this->baseurl;
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

    private function get_subscription_button_html() : string {
        global $PAGE;

        $forumrecord = $this->forumrecord;
        $discussion = $this->discussion;
        $html = html_writer::div(
            forum_get_discussion_subscription_icon($forumrecord, $discussion->get_id(), null, true),
            'discussionsubscription'
        );
        $html .= forum_get_discussion_subscription_icon_preloaders();
        // Add the subscription toggle JS.
        $PAGE->requires->yui_module('moodle-mod_forum-subscriptiontoggle', 'Y.M.mod_forum.subscriptiontoggle.init');
        return $html;
    }

    private function get_move_discussion_html() : string {
        global $DB;

        $forum = $this->forum;
        $discussion = $this->discussion;
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

    private function get_pin_discussion_html() : string {
        $discussion = $this->discussion;

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

    private function get_notifications() {
        $notifications = $this->notifications;
        $discussion = $this->discussion;
        $forum = $this->forum;
        $renderer = $this->renderer;

        if ($forum->is_discussion_locked($discussion)) {
            $notifications[] = $renderer->notification(
                get_string('discussionlocked', 'forum'),
                notification::NOTIFY_INFO . ' discussionlocked'
            );
        }

        if ($forum->has_blocking_enabled()) {
            $notifications[] = $renderer->notification(
                get_string('thisforumisthrottled', 'forum', [
                    'blockafter' => $forum->get_block_after(),
                    'blockperiod' => get_string('secondstotime' . $forum->get_block_period())
                ]
            ));
        }

        return $notifications;
    }

    private function get_neighbour_links_html() {
        // TODO: Remove this and use the entity object to get the course module.
        $forum = $this->forum;
        $modinfo = get_fast_modinfo($forum->get_course_id());
        $coursemodule = $modinfo->instances['forum'][$forum->get_id()];
        $neighbours = forum_get_discussion_neighbours($coursemodule, $this->discussionrecord, $this->forumrecord);
        return $this->renderer->neighbouring_discussion_navigation($neighbours['prev'], $neighbours['next']);
    }
}
