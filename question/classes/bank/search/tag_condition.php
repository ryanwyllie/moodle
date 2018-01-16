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

namespace core_question\bank\search;
defined('MOODLE_INTERNAL') || die();

/**
 *
 * @copyright 2018 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tag_condition extends condition {
    /** @var string SQL fragment to add to the where clause. */
    protected $where;
    /** @var string SQL fragment to add to the where clause. */
    protected $contexts;

    /**
     * Constructor.
     * @param context[] $contexts List of contexts
     */
    public function __construct(array $contexts) {
        $this->where = '';
        $this->contexts = $contexts;
    }

    public function where() {
        return $this->where;
    }

    /**
     * Print HTML to display the "Also show old questions" checkbox
     */
    public function display_options() {
        global $OUTPUT;

        /*
        $contextids = [];
        foreach ($contexts as $context) {
            $contextids = array_merge($contextids, $context->get_parent_context_ids(true));
        }
        $contextids = array_unique($contextids);
        */
        $contextids = array_map(function($context) {
            return $context->id;
        }, $this->contexts);

        $stuff = $OUTPUT->render_from_template('core_question/tag_condition', [
            'contextids' => json_encode($contextids)
        ]);

        echo $stuff;
    }
}
