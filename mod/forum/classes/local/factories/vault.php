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
use mod_forum\local\vaults\post_read_receipt_collection as post_read_receipt_collection_vault;
use mod_forum\local\vaults\preprocessors\load_files as load_files_preprocessor;
use mod_forum\local\vaults\preprocessors\post_read_user_list as post_read_user_list_preprocessor;
use mod_forum\local\vaults\sql_strategies\single_table as single_table_strategy;
use mod_forum\local\vaults\sql_strategies\discussions_in_forum as discussions_in_forum;
use mod_forum\local\vaults\sql_strategies\single_table_with_module_context_course as module_context_course_strategy;
use mod_forum\local\vaults\sql_strategies\post as post_sql_strategy;
use file_storage;
use moodle_database;

/**
 * Vault factory.
 */
class vault {
    private $datamapperfactory;
    private $db;
    private $filestorage;

    public function __construct(moodle_database $db, data_mapper_factory $datamapperfactory, file_storage $filestorage) {
        $this->db = $db;
        $this->datamapperfactory = $datamapperfactory;
        $this->filestorage = $filestorage;
    }

    public function get_forum_vault() : forum_vault {
        $strategy = new module_context_course_strategy($this->db, 'forum', 'forum');
        return new forum_vault(
            $this->db,
            $strategy,
            $this->datamapperfactory->get_forum_data_mapper(),
            $strategy->get_preprocessors()
        );
    }

    public function get_discussion_vault() : discussion_vault {
        $strategy = new single_table_strategy('forum_discussions');
        return new discussion_vault(
            $this->db,
            $strategy,
            $this->datamapperfactory->get_discussion_data_mapper(),
            $strategy->get_preprocessors()
        );
    }

    public function get_discussions_in_forum_vault() : discussion_vault {
        $strategy = new discussions_in_forum($this->db, 'forum_discussions');
        return new discussion_vault(
            $this->db,
            $strategy,
            $this->datamapperfactory->get_discussion_summary_data_mapper(),
            $strategy->get_preprocessors()
        );
    }

    public function get_post_vault() : post_vault {
        $strategy = new post_sql_strategy();
        return new post_vault(
            $this->db,
            $strategy,
            $this->datamapperfactory->get_post_data_mapper(),
            array_merge(
                $strategy->get_preprocessors(),
                [
                    'attachments' => new load_files_preprocessor($this->filestorage, 'contextid', 'id'),
                    'useridreadlist' => new post_read_user_list_preprocessor($this->db)
                ]
            )
        );
    }

    public function get_author_vault() : author_vault {
        $strategy = new single_table_strategy('user');
        return new author_vault(
            $this->db,
            $strategy,
            $this->datamapperfactory->get_author_data_mapper(),
            $strategy->get_preprocessors()
        );
    }

    public function get_post_read_receipt_collection_vault() : post_read_receipt_collection_vault {
        $strategy = new single_table_strategy('forum_read');
        return new post_read_receipt_collection_vault(
            $this->db,
            $strategy,
            $this->datamapperfactory->get_post_read_receipt_collection_data_mapper(),
            $strategy->get_preprocessors()
        );
    }
}
