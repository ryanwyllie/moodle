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

/**
 * Vault class.
 */
class single_table_with_module_context implements sql_strategy_interface {
    private $table;
    private $modulename;

    public function __construct(string $table, string $modulename) {
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
        $fields = $alias . '.*, ' . context_helper::get_preload_record_columns_sql('c');
        $tables = '{' . $this->get_table() . '} ' . $alias;
        $tables .= ' JOIN {modules} m ON m.name = \'' . $this->modulename . '\'';
        $tables .= ' JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = ' . $alias . '.id';
        $tables .= ' JOIN {context} c ON c.contextlevel = ' . CONTEXT_MODULE .  ' AND c.instanceid = cm.id';

        $selectsql = 'SELECT ' . $fields . ' FROM ' . $tables;
        $selectsql .= $wheresql ? ' WHERE ' . $wheresql : '';
        $selectsql .= $sortsql ? ' ORDER BY ' . $sortsql : '';

        return $selectsql;
    }
}
