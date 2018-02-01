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
    protected $selectedtagids;

    /**
     * Constructor.
     * @param context[] $contexts List of contexts
     */
    public function __construct(array $contexts, array $selectedtagids = []) {
        global $DB;

        $this->contexts = $contexts;

        if ($selectedtagids) {
            list($tagsql, $tagparams) = $DB->get_in_or_equal($selectedtagids, SQL_PARAMS_NAMED);
            $tagparams['tagcount'] = count($selectedtagids);
            $this->selectedtagids = $selectedtagids;
            $this->params = $tagparams;
            $this->where = "q.id IN (SELECT ti.itemid
                                     FROM {tag_instance} ti
                                     WHERE ti.tagid {$tagsql}
                                     GROUP BY ti.itemid
                                     HAVING COUNT(itemid) = :tagcount)";

        } else {
            $this->selectedtagids = [];
            $this->params = [];
            $this->where = '';
        }
    }

    public function where() {
        return $this->where;
    }

    public function params() {
        return $this->params;
    }

    /**
     * Print HTML to display the "Also show old questions" checkbox
     */
    public function display_options() {
        global $OUTPUT;

        $tags = \core_tag_tag::get_tags_by_area_in_contexts('core_question', 'question', $this->contexts);
        $tagoptions = array_map(function($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'selected' => in_array($tag->id, $this->selectedtagids)
            ];
        }, array_values($tags));
        $context = [
            'tagoption' => $tagoptions
        ];

        $stuff = $OUTPUT->render_from_template('core_question/tag_condition', $context);

        echo $stuff;
    }
}
