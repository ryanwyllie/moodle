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

use mod_forum\local\entities\discussion;
use mod_forum\local\entities\forum;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\factories\database_data_mapper as database_data_mapper_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\manager as manager_factory;
use mod_forum\local\renderers\discussion as discussion_renderer;
use context;
use moodle_url;
use renderer_base;
use stdClass;

/**
 * Vault factory.
 */
class renderer {
    private $databasedatamapperfactory;
    private $exporterfactory;
    private $vaultfactory;
    private $managerfactory;
    private $rendererbase;

    public function __construct(
        database_data_mapper_factory $databasedatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory,
        manager_factory $managerfactory,
        renderer_base $rendererbase
    ) {
        $this->databasedatamapperfactory = $databasedatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
        $this->managerfactory = $managerfactory;
        $this->rendererbase = $rendererbase;
    }

    public function get_discussion_renderer(
        forum $forum,
        discussion $discussion
    ) : discussion_renderer {

        $capabilitymanager = $this->managerfactory->get_capability_manager();
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
            $this->databasedatamapperfactory,
            $this->exporterfactory,
            $this->vaultfactory,
            $capabilitymanager,
            $baseurl,
            $notifications
        );
    }
}
