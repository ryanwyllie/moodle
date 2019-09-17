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
 * Class for exporting a navigation node.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\external;
defined('MOODLE_INTERNAL') || die();

use navigation_node;

/**
 * Class for exporting a navigation node.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation_node_exporter extends exporter {

    /**
     * @var navigation_node $node
     */
    protected $node;

    public static function define_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null
            ],
            'key' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null
            ],
            'text' => [
                'type' => PARAM_TEXT
            ],
            'shorttext' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null
            ],
            'title' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null
            ],
            'helpbutton' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null
            ],
            'actionurl' => [
                'type' => PARAM_URL,
                'null' => NULL_ALLOWED,
                'default' => null
            ],
            'icon' => [
                'null' => NULL_ALLOWED,
                'default' => null,
                'type' => [
                    'key' => ['type' => PARAM_TEXT],
                    'component' => ['type' => PARAM_TEXT],
                    'alttext' => ['type' => PARAM_TEXT]
                ]
            ],
            'type' => [
                'type' => PARAM_INT
            ],
            'nodetype' => [
                'type' => PARAM_INT
            ],
            'collapse' => [
                'type' => PARAM_BOOL
            ],
            'forceopen' => [
                'type' => PARAM_BOOL
            ],
            'classes' => [
                'type' => PARAM_TEXT,
                'multiple' => true
            ],
            'childrenkeylist' => [
                'type' => PARAM_TEXT,
                'multiple' => true
            ],
            'childrencount' => [
                'type' => PARAM_INT
            ],
            'isactive' => [
                'type' => PARAM_BOOL
            ],
            'hidden' => [
                'type' => PARAM_BOOL
            ],
            'display' => [
                'type' => PARAM_BOOL
            ],
            'isnewsection' => [
                'type' => PARAM_BOOL
            ],
            'mainnavonly' => [
                'type' => PARAM_BOOL
            ],
            'isexpandable' => [
                'type' => PARAM_BOOL
            ],
            'hassiblings' => [
                'type' => PARAM_BOOL
            ],
            'haschildren' => [
                'type' => PARAM_BOOL
            ],
            'containsactivenode' => [
                'type' => PARAM_BOOL
            ],
            'isshortbranch' => [
                'type' => PARAM_BOOL
            ]
        ];
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return ['context' => 'context'];
    }

    public function __construct(navigation_node $node, array $related = []) {
        $this->node = $node;

        $data = [
            'id' => $node->id,
            'key' => $node->key,
            'text' => $node->text,
            'shorttext' => $node->shorttext,
            'title' => $node->get_title(),
            'helpbutton' => $node->helpbutton,
            'actionurl' => $node->has_action() ? $node->action()->out(false) : null,
            'icon' => $node->icon->export_for_pix(),
            'type' => $node->type,
            'nodetype' => $node->nodetype,
            'collapse' => $node->collapse,
            'forceopen' => $node->forceopen,
            'classes' => $node->classes,
            'childrenkeylist' => $node->get_children_key_list(),
            'childrencount' => $node->children->count(),
            'isactive' => $node->isactive,
            'hidden' => $node->is_hidden(),
            'display' => $node->display,
            'isnewsection' => $node->preceedwithhr,
            'mainnavonly' => $node->mainnavonly,
            'isexpandable' => $node->isexpandable,
            'hassiblings' => $node->has_siblings(),
            'haschildren' => $node->has_children(),
            'containsactivenode' => $node->contains_active_node(),
            'isshortbranch' => $node->is_short_branch(),
        ];

        return parent::__construct($data, $related);
    }
}
