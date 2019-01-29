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
 * Vault class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\vaults\sql_strategies;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\vaults\preprocessors\extract_context;
use mod_forum\local\vaults\preprocessors\extract_record;
use mod_forum\local\vaults\preprocessors\extract_user;
use mod_forum\local\renderers\discussion_list;
use context_helper;
use moodle_database;
use user_picture;

/**
 * Vault class.
 */
class discussions_in_forum implements sql_strategy_interface {
    private $db;
    private $table;
    private $modulename;

    private const FIRST_AUTHOR_ID_ALIAS = 'userpictureid';
    private const FIRST_AUTHOR_ALIAS = 'userrecord';
    private const LATEST_AUTHOR_ID_ALIAS = 'userpictureid';
    private const LATEST_AUTHOR_ALIAS = 'userrecord';

    public function __construct(moodle_database $db, string $table) {
        $this->db = $db;
        $this->table = $table;
    }

    public function get_table() : string {
        return $this->table;
    }

    public function get_table_alias() : string {
        return 'd';
    }

    public function generate_get_records_sql(string $wheresql = null, ?string $sortsql = null) : string {
        $alias = $this->get_table_alias();

        // Fetch:
        // - Discussion
        // - First post
        // - Author
        // - Most recent editor
        $tablefields = $this->db->get_preload_columns($this->get_table(), $alias);
        $postfields = $this->db->get_preload_columns('forum_posts', 'p_');
        $firstauthorfields = user_picture::fields('fa', null, self::FIRST_AUTHOR_ID_ALIAS, self::FIRST_AUTHOR_ALIAS);
        $latestuserfields = user_picture::fields('la', null, self::LATEST_AUTHOR_ID_ALIAS, self::LATEST_AUTHOR_ALIAS);

        $fields = implode(', ', [
            $this->db->get_preload_columns_sql($tablefields, $alias),
            $this->db->get_preload_columns_sql($postfields, 'fp'),
            $firstauthorfields,
            $latestuserfields,
        ]);


        $tables = '{' . $this->get_table() . '} ' . $alias;
        $tables .= ' JOIN {user} fa ON fa.id = ' . $alias . '.userid';
        $tables .= ' JOIN {user} la ON la.id = ' . $alias . '.usermodified';
        $tables .= ' JOIN {forum_posts} fp ON fp.id = ' . $alias . '.firstpost';

        $selectsql = 'SELECT ' . $fields . ' FROM ' . $tables;
        $selectsql .= $wheresql ? ' WHERE ' . $wheresql : '';
        $selectsql .= $sortsql ? ' ORDER BY ' . $sortsql : '';

        return $selectsql;
    }

    public function get_preprocessors() : array {
        return [
            'discussion' => new extract_record($this->db, $this->get_table(), $this->get_table_alias()),
            'firstpost' => new extract_record($this->db, 'forum_posts', 'p_'),
            'firstpostauthor' => new extract_user(self::FIRST_AUTHOR_ID_ALIAS, self::FIRST_AUTHOR_ALIAS),
            'latestpostauthor' => new extract_user(self::LATEST_AUTHOR_ID_ALIAS, self::LATEST_AUTHOR_ALIAS),
        ];
    }

    public function get_sort_order(?int $sortmethod) : string {
        $alias = $this->get_table_alias();
        // TODO this is actually much more complex because of pinned posts.
        // TODO consider user favourites...
        switch ($sortmethod) {
            case discussion_list::SORTORDER_OLDEST_FIRST:
                return "{$alias}.pinned DESC, {$alias}.timemodified ASC";
            case discussion_list::SORTORDER_NEWEST_FIRST:
            default:
                return "{$alias}.pinned DESC, {$alias}.timemodified DESC";
        }
    }
}
