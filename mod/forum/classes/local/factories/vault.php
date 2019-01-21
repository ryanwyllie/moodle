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

use mod_forum\local\factories\database_data_mapper as data_mapper_factory;
use mod_forum\local\vaults\author as author_vault;
use mod_forum\local\vaults\discussion as discussion_vault;
use mod_forum\local\vaults\forum as forum_vault;
use mod_forum\local\vaults\post as post_vault;
use mod_forum\local\vaults\sql_strategies\single_table as single_table_strategy;
use mod_forum\local\vaults\sql_strategies\single_table_with_module_context as module_context_strategy;
use mod_forum\local\vaults\sql_strategies\single_table_with_user as with_user_strategy;

/**
 * Vault factory.
 */
class vault {
    private $datamapperfactory;

    public function __construct(data_mapper_factory $datamapperfactory) {
        $this->datamapperfactory = $datamapperfactory;
    }

    public function get_forum_vault() : forum_vault {
        global $DB;
        $strategy = new module_context_strategy($DB, 'forum', 'forum');
        return new forum_vault(
            $DB,
            $strategy,
            $this->datamapperfactory->get_forum_data_mapper($DB)
        );
    }

    public function get_discussion_vault() : discussion_vault {
        global $DB;
        $strategy = new single_table_strategy('forum_discussions');
        return new discussion_vault($DB, $strategy, $this->datamapperfactory->get_discussion_data_mapper());
    }

    public function get_post_vault() : post_vault {
        global $DB;
        $strategy = new with_user_strategy('forum_posts');
        return new post_vault($DB, $strategy, $this->datamapperfactory->get_post_data_mapper());
    }

    public function get_author_vault() : author_vault {
        global $DB;
        $strategy = new single_table_strategy('user');
        return new author_vault($DB, $strategy, $this->datamapperfactory->get_author_data_mapper());
    }
}
