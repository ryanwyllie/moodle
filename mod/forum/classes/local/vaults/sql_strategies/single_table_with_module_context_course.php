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

use context_helper;
use moodle_database;

/**
 * Vault class.
 */
class single_table_with_module_context_course implements sql_strategy_interface {
    private $db;
    private $table;
    private $modulename;

    public function __construct(moodle_database $db, string $table, string $modulename) {
        $this->db = $db;
        $this->table = $table;
        $this->modulename = $modulename;
    }

    public function get_table() : string {
        return $this->table;
    }

    public function get_table_alias() : string {
        return 't';
    }

    public function generate_get_records_sql(string $wheresql = null, string $sortsql = null) : string {
        $alias = $this->get_table_alias();

        $forumfields = $this->db->get_preload_columns($this->get_table(), $alias);
        $coursemodulefields = $this->db->get_preload_columns('course_modules', 'cm_');
        $coursefields = $this->db->get_preload_columns('course', 'c_');

        $fields = implode(', ', [
            $this->db->get_preload_columns_sql($forumfields, $alias),
            context_helper::get_preload_record_columns_sql('ctx'),
            $this->db->get_preload_columns_sql($coursemodulefields, 'cm'),
            $this->db->get_preload_columns_sql($coursefields, 'c'),
        ]);

        $tables = '{' . $this->get_table() . '} ' . $alias;
        $tables .= ' JOIN {modules} m ON m.name = \'' . $this->modulename . '\'';
        $tables .= " JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = {$alias}.id";
        $tables .= ' JOIN {context} ctx ON ctx.contextlevel = ' . CONTEXT_MODULE .  ' AND ctx.instanceid = cm.id';
        $tables .= " JOIN {course} c ON c.id = {$alias}.course";

        $selectsql = 'SELECT ' . $fields . ' FROM ' . $tables;
        $selectsql .= $wheresql ? ' WHERE ' . $wheresql : '';
        $selectsql .= $sortsql ? ' ORDER BY ' . $sortsql : '';

        return $selectsql;
    }
}
