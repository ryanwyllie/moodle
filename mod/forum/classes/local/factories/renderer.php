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
 * Renderer factory.
 *
 * @package    mod_forum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\factories;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\entity as entity_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\manager as manager_factory;
use mod_forum\local\factories\builder as builder_factory;
use mod_forum\local\renderers\discussion as discussion_renderer;
use mod_forum\local\renderers\discussion_list as discussion_list_renderer;
use mod_forum\local\renderers\posts as posts_renderer;
use mod_forum\local\renderers\posts_search_results as posts_search_results_renderer;
use moodle_page;
use moodle_url;

/**
 * Renderer factory.
 *
 * See:
 * https://designpatternsphp.readthedocs.io/en/latest/Creational/SimpleFactory/README.html
 */
class renderer {
    /** @var legacy_data_mapper_factory $legacydatamapperfactory Legacy data mapper factory */
    private $legacydatamapperfactory;
    /** @var exporter_factory $exporterfactory Exporter factory */
    private $exporterfactory;
    /** @var vault_factory $vaultfactory Vault factory */
    private $vaultfactory;
    /** @var manager_factory $managerfactory Manager factory */
    private $managerfactory;
    /** @var entity_factory $entityfactory Entity factory */
    private $entityfactory;
    /** @var builder_factory $builderfactory Builder factory */
    private $builderfactory;
    /** @var renderer_base $rendererbase Renderer base */
    private $rendererbase;
    /** @var moodle_page $page Moodle page */
    private $page;

    /**
     * Constructor.
     *
     * @param legacy_data_mapper_factory $legacydatamapperfactory Legacy data mapper factory
     * @param exporter_factory $exporterfactory Exporter factory
     * @param vault_factory $vaultfactory Vault factory
     * @param manager_factory $managerfactory Manager factory
     * @param entity_factory $entityfactory Entity factory
     * @param builder_factory $builderfactory Builder factory
     * @param moodle_page $page Moodle page
     */
    public function __construct(
        legacy_data_mapper_factory $legacydatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory,
        manager_factory $managerfactory,
        entity_factory $entityfactory,
        builder_factory $builderfactory,
        moodle_page $page
    ) {
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->managerfactory = $managerfactory;
        $this->entityfactory = $entityfactory;
        $this->builderfactory = $builderfactory;
        $this->page = $page;
        $this->rendererbase = $page->get_renderer('mod_forum');
    }

    /**
     * Create a discussion renderer for the given forum and discussion.
     *
     * @param forum_entity $forum Forum the discussion belongs to
     * @param discussion_entity $discussion Discussion to render
     * @return discussion_renderer
     */
    public function get_discussion_renderer(
        forum_entity $forum,
        discussion_entity $discussion
    ) : discussion_renderer {

        $capabilitymanager = $this->managerfactory->get_capability_manager($forum);
        $ratingmanager = $this->managerfactory->get_rating_manager();
        $urlmanager = $this->managerfactory->get_url_manager($forum);
        $rendererbase = $this->rendererbase;
        $baseurl = $urlmanager->get_discussion_view_url_from_discussion($discussion);
        $notifications = [];

        switch ($forum->get_type()) {
            case 'single':
                $baseurl = new moodle_url("/mod/forum/view.php", ['f' => $forum->get_id()]);
                break;
            case 'qanda':
                if ($capabilitymanager->must_post_before_viewing_discussion($user, $forum, $discussion)) {
                    $notifications[] = $rendererbase->notification(get_string('qandanotify', 'forum'));
                }
                break;
        }

        return new discussion_renderer(
            $discussion,
            $forum,
            $rendererbase,
            $this->get_posts_renderer($forum, $discussion),
            $this->page,
            $this->legacydatamapperfactory,
            $this->exporterfactory,
            $this->vaultfactory,
            $capabilitymanager,
            $ratingmanager,
            $this->entityfactory->get_exported_posts_sorter(),
            $baseurl,
            $notifications
        );
    }

    /**
     * Create a posts renderer to render a list of posts.
     *
     * @param forum_entity $forum Forum the posts belong to
     * @param discussion_entity $discussion Discussion the posts belong to
     * @return posts_renderer
     */
    public function get_posts_renderer(
        forum_entity $forum,
        discussion_entity $discussion
    ) : posts_renderer {
        return new posts_renderer(
            $discussion,
            $forum,
            $this->rendererbase,
            $this->builderfactory->get_exported_posts_builder(),
            $this->entityfactory->get_exported_posts_sorter(),
            // Function to determine which template should be used for the given
            // display mode.
            function(int $displaymode = null) {
                switch ($displaymode) {
                    case FORUM_MODE_THREADED:
                        return 'mod_forum/forum_discussion_threaded_posts';
                    case FORUM_MODE_NESTED:
                        return 'mod_forum/forum_discussion_nested_posts';
                    default;
                        return 'mod_forum/forum_discussion_posts';
                }
            }
        );
    }

    /**
     * Create a posts renderer that renders posts in a read only format, i.e.
     * no links to reply, edit, delete, etc.
     *
     * @param forum_entity $forum Forum the posts belong to
     * @param discussion_entity $discussion Discussion the posts belong to
     * @return posts_renderer
     */
    public function get_posts_read_only_renderer(
        forum_entity $forum,
        discussion_entity $discussion
    ) : posts_renderer {
        return new posts_renderer(
            $discussion,
            $forum,
            $this->rendererbase,
            $this->builderfactory->get_exported_posts_builder(),
            $this->entityfactory->get_exported_posts_sorter(),
            // Function to determine which template should be used for the given
            // display mode.
            function(int $displaymode = null) {
                switch ($displaymode) {
                    case FORUM_MODE_THREADED:
                        return 'mod_forum/forum_discussion_threaded_posts_read_only';
                    case FORUM_MODE_NESTED:
                        return 'mod_forum/forum_discussion_nested_posts_read_only';
                    default;
                        return 'mod_forum/forum_discussion_posts_read_only';
                }
            }
        );
    }

    /**
     * Create a posts renderer to render posts in the forum search results.
     *
     * @return posts_search_results_renderer
     */
    public function get_posts_search_results_renderer() : posts_search_results_renderer {
        return new posts_search_results_renderer(
            $this->rendererbase,
            $this->builderfactory->get_exported_posts_builder(),
            $this->managerfactory
        );
    }

    /**
     * Create a discussion list renderer.
     *
     * @param forum_entity $forum The forum that the discussions belong to
     * @return discussion_list_renderer
     */
    public function get_discussion_list_renderer(
        forum_entity $forum
    ) : discussion_list_renderer {

        $capabilitymanager = $this->managerfactory->get_capability_manager($forum);
        $urlmanager = $this->managerfactory->get_url_manager($forum);
        $rendererbase = $this->rendererbase;
        $notifications = [];

        return new discussion_list_renderer(
            $forum,
            $rendererbase,
            $this->legacydatamapperfactory,
            $this->exporterfactory,
            $this->vaultfactory,
            $capabilitymanager,
            $urlmanager,
            $notifications
        );
    }
}
