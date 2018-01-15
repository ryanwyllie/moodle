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
 *
 * @package   core_question
 * @copyright 2018 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_question\bank;

/**
 *
 * @copyright 2018 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class searcher {
    protected $database = null;

    public function __construct(\moodle_database $database) {
        $this->database = $database;
    }

    public function search_by_tag_names_in_contexts(array $names, array $contexts) {
        $items = [
            [
                'component' => 'core_question',
                'itemtype' => 'question'
            ]
        ];
        $taginstances = \core_tag_tag::get_tag_instances_by_names_in_contexts($names, $contexts, $items);

        if (empty($taginstances)) {
            return [];
        }

        $questionids = array_unique(array_map(function($taginstance) {
            return $taginstance->itemid;
        }, $taginstances));

        list($idsql, $params) = $this->database->get_in_or_equal($questionids);
        $sql = "SELECT * FROM {question} WHERE id {$idsql}";

        return $this->database->get_records_sql($sql, $params);
    }
}
