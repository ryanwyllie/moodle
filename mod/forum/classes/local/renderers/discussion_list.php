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
 * Discussion list renderer.
 *
 * @package    mod_forum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\renderers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\managers\capability as capability_manager;
use renderer_base;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Nested discussion renderer class.
 */
class discussion_list {
    private $forum;
    private $forumrecord;
    private $renderer;
    private $legacydatamapperfactory;
    private $exporterfactory;
    private $vaultfactory;
    private $capabilitymanager;
    private $notifications;

    public function __construct(
        forum_entity $forum,
        renderer_base $renderer,
        legacy_data_mapper_factory $legacydatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory,
        capability_manager $capabilitymanager,
        array $notifications = []
    ) {
        $this->forum = $forum;
        $this->renderer = $renderer;
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->capabilitymanager = $capabilitymanager;
        $this->notifications = $notifications;

        $forumdatamapper = $this->legacydatamapperfactory->get_forum_data_mapper();
        $this->forumrecord = $forumdatamapper->to_legacy_object($forum);
    }

    public function render(\stdClass $user, int $currentgroup) : string {
        $capabilitymanager = $this->capabilitymanager;
        $forum = $this->forum;

        // Make sure we can render.
        if (!$capabilitymanager->can_view_discussions($user)) {
            throw new moodle_exception('noviewdiscussionspermission', 'mod_forum');
        }

        $forumexporter = $this->exporterfactory->get_forum_exporter(
            $user,
            $this->forum,
            $currentgroup
        );

        $forumview = array_merge((array) $forumexporter->export($this->renderer), [
            'discussions' => [],
        ]);

        return $this->renderer->render_from_template($this->get_template(), $forumview);
    }

    private function get_exported_discussions(\stdClass $user) {
        $forum = $this->forum;
        $discussionvault = $this->vaultfactory->get_discussion_vault();
        $discussions = $discussionvault->get_from_forum_id_and_group($discussion->get_id(), $this->get_order_by($displaymode));
        $postexporter = $this->exporterfactory->get_posts_exporter(
            $user,
            $forum,
            $discussion,
            $posts
        );
        ['posts' => $exportedposts] = (array) $postexporter->export($this->renderer);
    }

    private function get_template() : string {
        switch ($this->forum->get_type()) {
            case 'news':
                return 'mod_forum/news_discussion_list';
                break;
            case 'blog':
                return 'mod_forum/blog_discussion_list';
                break;
            case 'qanda':
                return 'mod_forum/qanda_discussion_list';
                break;
            default:
                return 'mod_forum/discussion_list';
        }
    }
}
