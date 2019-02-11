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
 * Forum class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\exporters;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\author as author_entity;
use core\external\exporter;
use renderer_base;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Forum class.
 */
class author extends exporter {
    private $author;
    private $authorgroups;
    private $canview;

    public function __construct(author_entity $author, array $authorgroups = [], $canview = true, $related = []) {
        $this->author = $author;
        $this->authorgroups = $authorgroups;
        $this->canview = $canview;
        return parent::__construct([], $related);
    }

    /**
     * Return the list of additional properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'fullname' => [
                'type' => PARAM_TEXT,
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'groups' => [
                'multiple' => true,
                'optional' => true,
                'type' => [
                    'id' => ['type' => PARAM_INT],
                    'urls' => [
                        'type' => [
                            'image' => [
                                'type' => PARAM_URL,
                                'optional' => true,
                                'default' => null,
                                'null' => NULL_ALLOWED
                            ]
                        ]
                    ]
                ]
            ],
            'urls' => [
                'type' => [
                    'profile' => [
                        'type' => PARAM_URL,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
                    'profileimage' => [
                        'type' => PARAM_URL,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
                ]
            ]
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $author = $this->author;
        $urlmanager = $this->related['urlmanager'];

        if ($this->canview) {
            $groups = array_map(function($group) {
                $imageurl = get_group_picture_url($group, $group->courseid);
                return [
                    'id' => $group->id,
                    'urls' => [
                        'image' => $imageurl ? $imageurl->out() : null
                    ]
                ];
            }, $this->authorgroups);

            return [
                'id' => $author->get_id(),
                'fullname' => $author->get_full_name(),
                'groups' => $groups,
                'urls' => [
                    'profile' => ($urlmanager->get_author_profile_url($author))->out(),
                    'profileimage' => ($urlmanager->get_author_profile_image_url($author))->out()
                ]
            ];
        } else {
            return [
                'id' => null,
                'fullname' => get_string('forumauthorhidden', 'mod_forum'),
                'groups' => [],
                'urls' => [
                    'profile' => null,
                    'profileimage' => null
                ]
            ];
        }
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'urlmanager' => 'mod_forum\local\managers\url',
            'context' => 'context'
        ];
    }
}
