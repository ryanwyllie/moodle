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

use mod_forum\local\vaults\preprocessors\extract_user;
use mod_forum\local\vaults\preprocessors\load_files;
use file_storage;
use user_picture;

/**
 * Vault class.
 */
class post implements sql_strategy_interface {
    private $table;
    private $filestorage;
    private const USER_ID_ALIAS = 'userpictureid';
    private const USER_ALIAS = 'userrecord';

    public function __construct(file_storage $filestorage) {
        $this->filestorage = $filestorage;
    }

    public function get_table() : string {
        return 'forum_posts';
    }

    public function get_table_alias() : string {
        return 'p';
    }

    public function generate_get_records_sql(string $wheresql = null, string $sortsql = null) : string {
        $table = $this->get_table();
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
        return [
            'user' => new extract_user(self::USER_ID_ALIAS, self::USER_ALIAS),
            'attachments' => new load_files($this->filestorage, 'contextid', 'id')
        ];
    }
}
