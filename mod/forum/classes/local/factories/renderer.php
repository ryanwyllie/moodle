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
 * Vault factory.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\factories;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\manager as manager_factory;
use mod_forum\local\renderers\discussion as discussion_renderer;
use mod_forum\local\renderers\discussion_list as discussion_list_renderer;
use context;
use moodle_page;
use moodle_url;
use renderer_base;
use stdClass;

/**
 * Vault factory.
 */
class renderer {
    private $legacydatamapperfactory;
    private $exporterfactory;
    private $vaultfactory;
    private $managerfactory;
    private $rendererbase;
    private $page;

    public function __construct(
        legacy_data_mapper_factory $legacydatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory,
        manager_factory $managerfactory,
        moodle_page $page
    ) {
        $this->legacydatamapperfactory = $legacydatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->managerfactory = $managerfactory;
        $this->page = $page;
        $this->rendererbase = $page->get_renderer('mod_forum');
    }

    public function get_discussion_renderer(
        forum_entity $forum,
        discussion_entity $discussion
    ) : discussion_renderer {

        $capabilitymanager = $this->managerfactory->get_capability_manager($forum);
        $ratingmanager = $this->managerfactory->get_rating_manager();
        $rendererbase = $this->rendererbase;
        $baseurl = new moodle_url("/mod/forum/discuss2.php", ['d' => $discussion->get_id()]);
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
            $this->page,
            $this->legacydatamapperfactory,
            $this->exporterfactory,
            $this->vaultfactory,
            $capabilitymanager,
            $ratingmanager,
            $baseurl,
            $notifications
        );
    }

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
