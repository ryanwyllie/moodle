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

    public function __construct(
        database_data_mapper_factory $databasedatamapperfactory,
        exporter_factory $exporterfactory,
        vault_factory $vaultfactory
    ) {
        $this->databasedatamapperfactory = $databasedatamapperfactory;
        $this->exporterfactory = $exporterfactory;
        $this->vaultfactory = $vaultfactory;
    }

    public function get_discussion_renderer(
        forum $forum,
        discussion $discussion,
        renderer_base $renderer
    ) : discussion_renderer {

        $baseurl = new moodle_url("/mod/forum/discuss2.php", ['d' => $discussion->get_id()]);
        $canshowdisplaymodeselector = true;
        $canshowmovediscussion = true;
        $canshowpiniscussion = true;
        $canshowsubscription = true;
        $getnotificationscallback = null;

        switch ($forum->get_type()) {
            case 'single':
                $baseurl = new moodle_url("/mod/forum/view.php", ['f' => $forum->get_id()]);
                $canshowmovediscussion = false;
                break;
            case 'qanda':
                $getnotificationscallback = function($user, $context, $forum, $discussion) use ($renderer) {
                    if (
                        !has_capability('mod/forum:viewqandawithoutposting', $context) &&
                        !forum_user_has_posted($forum->get_id(), $discussion->get_id(), $user->id)
                    ) {
                        return [$renderer->notification(get_string('qandanotify', 'forum'))];
                    }
                };
                break;
        }

        return new discussion_renderer(
            $renderer,
            $this->databasedatamapperfactory,
            $this->exporterfactory,
            $this->vaultfactory,
            $baseurl,
            $canshowdisplaymodeselector,
            $canshowmovediscussion,
            $canshowpiniscussion,
            $canshowsubscription,
            $getnotificationscallback
        );
    }
}
