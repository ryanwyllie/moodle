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
 * Vault class for a discussion list.
 *
 * @package    mod_forum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\vaults;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\vault;
use mod_forum\local\vaults\preprocessors\extract_record as extract_record_preprocessor;
use mod_forum\local\vaults\preprocessors\extract_user as extract_user_preprocessor;
use mod_forum\local\renderers\discussion_list as discussion_list_renderer;

/**
 * Vault class.
 */
class discussion_list extends vault {
    private const TABLE = 'forum_discussions';
    private const FIRST_AUTHOR_ID_ALIAS = 'userpictureid';
    private const FIRST_AUTHOR_ALIAS = 'userrecord';
    private const LATEST_AUTHOR_ID_ALIAS = 'userpictureid';
    private const LATEST_AUTHOR_ALIAS = 'userrecord';

    protected function get_table_alias() : string {
        return 'd';
    }

    protected function generate_get_records_sql(string $wheresql = null, ?string $sortsql = null) : string {
        $alias = $this->get_table_alias();
        $db = $this->get_db();

        // Fetch:
        // - Discussion
        // - First post
        // - Author
        // - Most recent editor
        $tablefields = $db->get_preload_columns(self::TABLE, $alias);
        $postfields = $db->get_preload_columns('forum_posts', 'p_');
        $firstauthorfields = \user_picture::fields('fa', null, self::FIRST_AUTHOR_ID_ALIAS, self::FIRST_AUTHOR_ALIAS);
        $latestuserfields = \user_picture::fields('la', null, self::LATEST_AUTHOR_ID_ALIAS, self::LATEST_AUTHOR_ALIAS);

        $fields = implode(', ', [
            $db->get_preload_columns_sql($tablefields, $alias),
            $db->get_preload_columns_sql($postfields, 'fp'),
            $firstauthorfields,
            $latestuserfields,
        ]);


        $tables = '{' . self::TABLE . '} ' . $alias;
        $tables .= ' JOIN {user} fa ON fa.id = ' . $alias . '.userid';
        $tables .= ' JOIN {user} la ON la.id = ' . $alias . '.usermodified';
        $tables .= ' JOIN {forum_posts} fp ON fp.id = ' . $alias . '.firstpost';

        $selectsql = 'SELECT ' . $fields . ' FROM ' . $tables;
        $selectsql .= $wheresql ? ' WHERE ' . $wheresql : '';
        $selectsql .= $sortsql ? ' ORDER BY ' . $sortsql : '';

        return $selectsql;
    }

    protected function get_preprocessors() : array {
        return array_merge(
            parent::get_preprocessors(),
            [
                'discussion' => new extract_record_preprocessor($this->get_db(), self::TABLE, $this->get_table_alias()),
                'firstpost' => new extract_record_preprocessor($this->get_db(), 'forum_posts', 'p_'),
                'firstpostauthor' => new extract_user_preprocessor(self::FIRST_AUTHOR_ID_ALIAS, self::FIRST_AUTHOR_ALIAS),
                'latestpostauthor' => new extract_user_preprocessor(self::LATEST_AUTHOR_ID_ALIAS, self::LATEST_AUTHOR_ALIAS),
            ]
        );
    }

    protected function from_db_records(array $results) {
        $entityfactory = $this->get_entity_factory();

        return array_map(function(array $result) use ($entityfactory) {
            [
                'discussion' => $discussion,
                'firstpost' => $firstpost,
                'firstpostauthor' => $firstpostauthor,
                'latestpostauthor' => $latestpostauthor,
            ] = $result;
            return $entityfactory->get_discussion_summary_from_stdClass(
                $discussion,
                $firstpost,
                $firstpostauthor,
                $latestpostauthor
            );
        }, $results);
    }

    public function get_sort_order(?int $sortmethod) : string {
        $alias = $this->get_table_alias();
        // TODO this is actually much more complex because of pinned posts.
        // TODO consider user favourites...
        switch ($sortmethod) {
            case discussion_list_renderer::SORTORDER_OLDEST_FIRST:
                return "{$alias}.pinned DESC, {$alias}.timemodified ASC";
            case discussion_list_renderer::SORTORDER_NEWEST_FIRST:
            default:
                return "{$alias}.pinned DESC, {$alias}.timemodified DESC";
        }
    }

    public function get_from_forum_id(int $forumid, ?int $sortorder, int $limit, int $offset) {
        $alias = $this->get_table_alias();
        $wheresql = "{$alias}.forum = :forumid";
        $params = [
            'forumid' => $forumid,
        ];

        $sql = $this->generate_get_records_sql($wheresql, $this->get_sort_order($sortorder));
        $records = $this->get_db()->get_records_sql($sql, $params, $offset, $limit);

        return $this->transform_db_records_to_entities($records);
    }

    public function get_from_forum_id_and_group_id(int $forumid, int $groupid, ?int $sortorder, int $limit, int $offset) {
        $alias = $this->get_table_alias();
        $wheresql = "{$alias}.forum = :forumid AND {$alias}.groupid = :groupid";
        $params = [
            'forumid' => $forumid,
            'groupid' => $groupid
        ];

        $sql = $this->generate_get_records_sql($wheresql, $this->get_sort_order($sortorder));
        $records = $this->get_db()->get_records_sql($sql, $params, $offset, $limit);

        return $this->transform_db_records_to_entities($records);
    }

    public function get_post_count_for_discussion_ids(array $discussionids) : array {
        list($insql, $params) = $this->get_db()->get_in_or_equal($discussionids);
        $sql = "SELECT discussion, COUNT(1) FROM {forum_posts} p WHERE p.discussion {$insql} GROUP BY discussion";

        return $this->get_db()->get_records_sql_menu($sql, $params);
    }
}
