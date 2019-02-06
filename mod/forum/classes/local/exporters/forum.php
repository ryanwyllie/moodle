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
 * Forum Exporter.
 *
 * @package     mod_forum
 * @copyright   2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\exporters;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\exporters\post as post_exporter;
use core\external\exporter;
use renderer_base;
use stdClass;

/**
 * Forum class.
 */
class forum extends exporter {
    private $forum;

    public function __construct(forum_entity $forum, $related = []) {
        $this->forum = $forum;
        return parent::__construct([], $related);
    }

    /**
     * Return the list of additional properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'id' => ['type' => PARAM_INT],
            'state' => [
                'type' => [
                    'groupmode' => ['type' => PARAM_INT],
                ],
            ],
            'userstate' => [
                'type' => [
                    'tracked' => ['type' => PARAM_INT],
                ],
            ],
            // TODO name, description.
            'capabilities' => [
                'type' => [
                    'viewdiscussions' => ['type' => PARAM_BOOL],
                    'create' => ['type' => PARAM_BOOL],
                    'subscribe' => ['type' => PARAM_BOOL],
                ]
            ],
            'urls' => [
                'type' => [
                    'create' => ['type' => PARAM_URL],
                    'markasreade' => ['type' => PARAM_URL],
                ],
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
        $capabilitymanager = $this->related['capabilitymanager'];
        $urlmanager = $this->related['urlmanager'];
        $user = $this->related['user'];
        $currentgroup = $this->related['currentgroup'];

        return [
            'id' => $this->forum->get_id(),
            'state' => [
                'groupmode' => $this->forum->get_effective_group_mode(),
            ],
            'userstate' => [
                'tracked' => forum_tp_is_tracked($this->get_forum_record(), $this->related['user']),
            ],
            'capabilities' => [
                'viewdiscussions' => $capabilitymanager->can_view_discussions($user),
                'create' => $capabilitymanager->can_create_discussions($user, $currentgroup),
                'subscribe' => $capabilitymanager->can_subscribe_to_forum($user),
            ],
            'urls' => [
                'create' => $urlmanager->get_discussion_create_url($this->forum)->out(),
                // TODO Get page no.
                'markasread' => $urlmanager->get_mark_all_discussions_as_read_url()->out(),
            ],
        ];
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'legacydatamapperfactory' => 'mod_forum\local\factories\legacy_data_mapper',
            'capabilitymanager' => 'mod_forum\local\managers\capability',
            'urlmanager' => 'mod_forum\local\managers\url',
            'user' => 'stdClass',
            'currentgroup' => 'int?',
        ];
    }

    /**
     * Get the legacy forum record for this forum.
     *
     * @return  stdClass
     */
    private function get_forum_record() : stdClass {
        $forumdbdatamapper = $this->related['legacydatamapperfactory']->get_forum_data_mapper();
        return $forumdbdatamapper->to_legacy_object($this->forum);
    }
}
