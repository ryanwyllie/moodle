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

namespace mod_forum\local\vaults;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\vault;
use mod_forum\local\factories\entity as entity_factory;
use mod_forum\local\vaults\preprocessors\extract_user as extract_user_preprocessor;
use mod_forum\local\vaults\preprocessors\load_files as load_files_preprocessor;
use mod_forum\local\vaults\preprocessors\post_read_user_list as post_read_user_list_preprocessor;
use user_picture;
use moodle_database;
use file_storage;

/**
 * Vault class.
 */
class post extends vault {
    private const TABLE = 'forum_posts';
    private const USER_ID_ALIAS = 'userpictureid';
    private const USER_ALIAS = 'userrecord';
    private $filestorage;

    public function __construct(
        moodle_database $db,
        entity_factory $entityfactory,
        file_storage $filestorage = null
    ) {
        $this->filestorage = $filestorage ?: get_file_storage();
        parent::__construct($db, $entityfactory);
    }

    protected function get_table_alias() : string {
        return 'p';
    }

    protected function generate_get_records_sql(string $wheresql = null, string $sortsql = null) : string {
        $table = self::TABLE;
        $alias = $this->get_table_alias();
        $fields = $alias . '.*, ctx.id as contextid, ' . user_picture::fields('u', null, self::USER_ID_ALIAS, self::USER_ALIAS);
        $tables = "{{$table}} {$alias}";
        $tables .= " JOIN {user} u ON u.id = {$alias}.userid";
        $tables .= " JOIN {forum_discussions} d ON {$alias}.discussion = d.id";
        $tables .= " JOIN {forum} f ON d.forum = f.id";
        $tables .= " JOIN {modules} m ON m.name = 'forum'";
        $tables .= " JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = f.id";
        $tables .= " JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = " . CONTEXT_MODULE;

        $selectsql = "SELECT {$fields} FROM {$tables}";
        $selectsql .= $wheresql ? ' WHERE ' . $wheresql : '';
        $selectsql .= $sortsql ? ' ORDER BY ' . $sortsql : '';

        return $selectsql;
    }

    public function get_preprocessors() : array {
        return array_merge(
            parent::get_preprocessors(),
            [
                'user' => new extract_user_preprocessor(self::USER_ID_ALIAS, self::USER_ALIAS),
                'attachments' => new load_files_preprocessor($this->filestorage, 'contextid', 'id'),
                'useridreadlist' => new post_read_user_list_preprocessor($this->get_db())
            ]
        );
    }

    protected function from_db_records(array $results) {
        $entityfactory = $this->get_entity_factory();

        return array_map(function(array $result) use ($entityfactory) {
            [
                'record' => $record,
                'user' => $authorrecord,
                'attachments' => $attachments
            ] = $result;
            $author = $entityfactory->get_author_from_stdClass($authorrecord);

            return $entityfactory->get_post_from_stdClass($record, $author, $attachments);
        }, $results);
    }

    public function get_from_discussion_id(int $discussionid, string $orderby = 'created ASC') : array {
        $alias = $this->get_table_alias();
        $wheresql = $alias . '.discussion = ?';
        $orderbysql = $alias . '.' . $orderby;
        $sql = $this->generate_get_records_sql($wheresql, $orderbysql);
        $records = $this->get_db()->get_records_sql($sql, [$discussionid]);

        return $this->transform_db_records_to_entities($records);
    }

    public function get_from_discussion_ids(array $discussionids) : array {
        if (empty($discussionids)) {
            return [];
        }

        $alias = $this->get_table_alias();

        list($insql, $params) = $this->get_db()->get_in_or_equal($discussionids);

        $wheresql = "{$alias}.discussion {$insql}";

        $sql = $this->generate_get_records_sql($wheresql, '');
        $records = $this->get_db()->get_records_sql($sql, $params);

        return $this->transform_db_records_to_entities($records);

    }
}
