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
use mod_forum\local\entities\post_read_receipt_collection as read_receipt_collection_entity;
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\managers\capability as capability_manager;
use core\output\notification;
use context;
use context_module;
use core_tag_tag;
use html_writer;
use moodle_exception;
use moodle_page;
use moodle_url;
use rating_manager;
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
    private $page;
    private $legacydatamapperfactory;
    private $exporterfactory;
    private $vaultfactory;
    private $capabilitymanager;
    private $ratingmanager;
    private $baseurl;
    private $notifications;

    public function __construct(
        discussion_entity $discussion,
        forum_entity $forum,
        renderer_base $renderer,
        moodle_page $page,
        legacy_data_mapper_factory $legacydatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory,
        capability_manager $capabilitymanager,
        rating_manager $ratingmanager,
        moodle_url $baseurl,
        array $notifications = []
    ) {
        $this->discussion = $discussion;
        $this->forum = $forum;
        $this->renderer = $renderer;
        $this->page = $page;
        $this->baseurl = $baseurl;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->capabilitymanager = $capabilitymanager;
        $this->ratingmanager = $ratingmanager;
        $this->notifications = $notifications;

        $forumdatamapper = $this->legacydatamapperfactory->get_forum_data_mapper();
        $this->forumrecord = $forumdatamapper->to_legacy_object($forum);

        $discussiondatamapper = $this->legacydatamapperfactory->get_discussion_data_mapper();
        $this->discussionrecord = $discussiondatamapper->to_legacy_object($discussion);
    }

    public function render(
        stdClass $user,
        int $displaymode,
        array $posts,
        ?read_receipt_collection_entity $readreceiptcollection
    ) : string {
        global $CFG;

        $capabilitymanager = $this->capabilitymanager;
        $forum = $this->forum;

        // Make sure we can render.
        if (!$capabilitymanager->can_view_discussions($user)) {
            throw new moodle_exception('noviewdiscussionspermission', 'mod_forum');
        }

        $ratingbypostid = $forum->has_rating_aggregate() ? $this->get_ratings_from_posts($user, $posts) : null;
        $nestedposts = $this->get_exported_posts($user, $displaymode, $posts, $ratingbypostid, $readreceiptcollection);
        $nestedposts = array_map(function($exportedpost) use ($forum) {
            if ($forum->get_type() == 'single' && !$exportedpost->hasparent) {
                // Remove the author from any posts
                unset($exportedpost->author);
            }

            return $exportedpost;
        }, $nestedposts);

        $exporteddiscussion = $this->get_exported_discussion($user);
        $exporteddiscussion = array_merge($exporteddiscussion, [
            'notifications' => $this->get_notifications(),
            'posts' => $nestedposts,
            'html' => [
                'modeselectorform' => $this->get_display_mode_selector_html($displaymode),
                'subscribe' => null,
                'movediscussion' => null,
                'pindiscussion' => null,
                'neighbourlinks' => $this->get_neighbour_links_html(),
                'exportdiscussion' => !empty($CFG->enableportfolios) ? $this->get_export_discussion_html() : null
            ]
        ]);
        $capabilities = (array) $exporteddiscussion['capabilities'];

        if ($capabilities['subscribe']) {
            $exporteddiscussion['html']['subscribe'] = $this->get_subscription_button_html();
        }

        if ($capabilities['move']) {
            $exporteddiscussion['html']['movediscussion'] = $this->get_move_discussion_html();
        }

        if ($capabilities['pin']) {
            $exporteddiscussion['html']['pindiscussion'] = $this->get_pin_discussion_html();
        }

        return $this->renderer->render_from_template($this->get_template($displaymode), $exporteddiscussion);
    }

    private function get_template(int $displaymode) : string {
        switch ($displaymode) {
            case FORUM_MODE_FLATOLDEST:
                return 'mod_forum/forum_discussion_flat';
            case FORUM_MODE_FLATNEWEST:
                return 'mod_forum/forum_discussion_flat';
            case FORUM_MODE_THREADED:
                return 'mod_forum/forum_discussion_threaded';
            case FORUM_MODE_NESTED:
                return 'mod_forum/forum_discussion_nested';
            default;
                return 'mod_forum/forum_discussion_nested';
        }
    }

    private function get_exported_posts(
        stdClass $user,
        int $displaymode,
        array $posts,
        ?array $ratingbypostid,
        ?read_receipt_collection_entity $readreceiptcollection
    ) : array {
        $forum = $this->forum;
        $discussion = $this->discussion;
        $groupsbyauthorid = $this->get_author_groups_from_posts($posts);
        $tagsbypostid = $this->get_tags_from_posts($posts);
        $postsexporter = $this->exporterfactory->get_posts_exporter(
            $user,
            $forum,
            $discussion,
            $posts,
            $groupsbyauthorid,
            $readreceiptcollection,
            $tagsbypostid,
            $ratingbypostid
        );
        ['posts' => $exportedposts] = (array) $postsexporter->export($this->renderer);

        $nestedposts = [];
        $seenfirstunread = false;
        $sortintoreplies = function($candidate, $unsorted) use (&$sortintoreplies, $seenfirstunread) {
            if (!isset($candidate->replies)) {
                $candidate->replies = [];
            }

            $candidate->isfirstunread = false;
            if (!$seenfirstunread && $candidate->unread) {
                $candidate->isfirstunread = true;
                $seenfirstunread = true;
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

    private function get_author_groups_from_posts(array $posts) : array {
        $course = $this->forum->get_course_record();
        $coursemodule = $this->forum->get_course_module_record();
        $authorids = array_reduce($posts, function($carry, $post) {
            $carry[$post->get_author()->get_id()] = [];
            return $carry;
        }, []);
        $authorgroups = groups_get_all_groups($course->id, array_keys($authorids), $coursemodule->groupingid, 'g.*, gm.id, gm.groupid, gm.userid');

        return array_reduce($authorgroups, function($carry, $group) {
            // Clean up data returned from groups_get_all_groups.
            $userid = $group->userid;
            $groupid = $group->groupid;

            unset($group->userid);
            unset($group->groupid);
            $group->id = $groupid;

            if (!isset($carry[$userid])) {
                $carry[$userid] = [$group];
            } else {
                $carry[$userid][] = $group;
            }

            return $carry;
        }, $authorids);
    }

    private function get_tags_from_posts(array $posts) : array {
        $postids = array_map(function($post) {
            return $post->get_id();
        }, $posts);
        return core_tag_tag::get_items_tags('mod_forum', 'forum_posts', $postids);
    }

    private function get_ratings_from_posts(stdClass $user, array $posts) {
        $forum = $this->forum;
        $postsdatamapper = $this->legacydatamapperfactory->get_post_data_mapper();

        $items = $postsdatamapper->to_legacy_objects($posts);
        $ratingoptions = (object) [
            'context' => $forum->get_context(),
            'component' => 'mod_forum',
            'ratingarea' => 'post',
            'items' => $items,
            'aggregate' => $forum->get_rating_aggregate(),
            'scaleid' => $forum->get_scale(),
            'userid' => $user->id,
            'assesstimestart' => $forum->get_assess_time_start(),
            'assesstimefinish' => $forum->get_assess_time_finish()
        ];

        $rm = $this->ratingmanager;
        $items = $rm->get_ratings($ratingoptions);

        return array_reduce($items, function($carry, $item) {
            $carry[$item->id] = empty($item->rating) ? null : $item->rating;
            return $carry;
        }, []);
    }

    /**
     * Get the groups details for all groups available to the forum.
     *
     * @return  stdClass[]
     * TODO REmove duplication with discussion_list
     */
    private function get_groups_available_in_forum() : array {
        $course = $this->forum->get_course_record();
        $coursemodule = $this->forum->get_course_module_record();

        return groups_get_all_groups($course->id, 0, $coursemodule->groupingid);
    }

    private function get_exported_discussion(stdClass $user) : array {
        $discussionexporter = $this->exporterfactory->get_discussion_exporter(
            $user,
            $this->forum,
            $this->discussion,
            $this->get_groups_available_in_forum()
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

        return $this->renderer->render($select);
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
                $html = '<div class="movediscussionoption">';
                $select = new url_select($forummenu, '',
                        ['/mod/forum/discuss.php?d=' . $discussion->get_id() => get_string("movethisdiscussionto", "forum")],
                        'forummenu', get_string('move'));
                $html .= $this->renderer->render($select);
                $html .= "</div>";
                return $html;
            }
        }

        return null;
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
        return $this->renderer->render($button);
    }

    private function get_export_discussion_html() {
        global $CFG;

        require_once($CFG->libdir . '/portfoliolib.php');
        $discussion = $this->discussion;
        $button = new \portfolio_add_button();
        $button->set_callback_options('forum_portfolio_caller', ['discussionid' => $discussion->get_id()], 'mod_forum');
        $button = $button->to_html(PORTFOLIO_ADD_FULL_FORM, get_string('exportdiscussion', 'mod_forum'));
        return $button ?: null;
    }

    private function get_notifications() {
        $notifications = $this->notifications;
        $discussion = $this->discussion;
        $forum = $this->forum;
        $renderer = $this->renderer;

        if ($forum->is_discussion_locked($discussion)) {
            $notifications[] = (new notification(
                get_string('discussionlocked', 'forum'),
                notification::NOTIFY_INFO
            ))
            ->set_extra_classes(['discussionlocked'])
            ->set_show_closebutton();
        }

        if ($forum->has_blocking_enabled()) {
            $notifications[] = (new notification(
                get_string('thisforumisthrottled', 'forum', [
                    'blockafter' => $forum->get_block_after(),
                    'blockperiod' => get_string('secondstotime' . $forum->get_block_period())
                ])
            ))->set_show_closebutton();
        }

        return array_map(function($notification) {
            return $notification->export_for_template($this->renderer);
        }, $notifications);
    }

    private function get_neighbour_links_html() {
        $forum = $this->forum;
        $coursemodule = $forum->get_course_module_record();
        $neighbours = forum_get_discussion_neighbours($coursemodule, $this->discussionrecord, $this->forumrecord);
        return $this->renderer->neighbouring_discussion_navigation($neighbours['prev'], $neighbours['next']);
    }
}
