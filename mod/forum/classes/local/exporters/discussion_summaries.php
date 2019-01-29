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
 * Forum Discussion List Exporter.
 *
 * @package     mod_forum
 * @copyright   2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\exporters;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\exporters\post as post_exporter;
use core\external\exporter;
use renderer_base;

/**
 * Forum class.
 */
class discussion_summaries extends exporter {
    private $discussions;
    private $groupsbyauthorid;

    public function __construct(array $discussions, $groupsbyauthorid, $related = []) {
        $this->discussions = $discussions;
        $this->groupsbyauthorid = $groupsbyauthorid;
        return parent::__construct([], $related);
    }

    /**
     * Return the list of additional properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'discussions' => [
                'type' => discussion_summary::read_properties_definition(),
                'multiple' => true
            ],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $exporteddiscussions = [];
        $related = $this->related;

        foreach ($this->discussions as $discussion) {
            $exporter = new discussion_summary($discussion, $this->groupsbyauthorid, $related);
            $exporteddiscussions[] = $exporter->export($output);
        }

        return [
            'discussions' => $exporteddiscussions,
        ];
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'forum' => 'mod_forum\local\entities\forum',
            'capabilitymanager' => 'mod_forum\local\managers\capability',
            'urlmanager' => 'mod_forum\local\managers\url',
            'user' => 'stdClass',
        ];
    }
}
