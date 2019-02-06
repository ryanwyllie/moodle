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

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\exporters\post as post_exporter;
use core\external\exporter;
use renderer_base;

/**
 * Forum class.
 */
class discussion extends exporter {
    private $discussion;

    public function __construct(discussion_entity $discussion, $related = []) {
        $this->discussion = $discussion;
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
            'forumid' => ['type' => PARAM_INT],
            'pinned' => ['type' => PARAM_BOOL],
            'name' => ['type' => PARAM_TEXT],
            'group' => [
                'optional' => true,
                'type' => [
                    'name' => ['type' => PARAM_TEXT],
                    'urls' => [
                        'type' => [
                            'picture' => [
                                'optional' => true,
                                'type' => PARAM_URL,
                            ],
                            'userlist' => [
                                'optional' => true,
                                'type' => PARAM_URL,
                            ],
                        ],
                    ],
                ],
            ],
            'times' => [
                'type' => [
                    'modified' => ['type' => PARAM_INT],
                    'start' => ['type' => PARAM_INT],
                    'end' => ['type' => PARAM_INT],
                ],
            ],
            'userstate' => [
                'type' => [
                    'subscribed' => ['type' => PARAM_BOOL],
                ],
            ],
            'capabilities' => [
                'type' => [
                    'subscribe' => ['type' => PARAM_BOOL],
                    'move' => ['type' => PARAM_BOOL],
                    'pin' => ['type' => PARAM_BOOL],
                    'post' => ['type' => PARAM_BOOL]
                ]
            ],
            'urls' => [
                'type' => [
                    'view' => ['type' => PARAM_URL],
                    'markasread' => ['type' => PARAM_URL],
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

        $forum = $this->related['forum'];
        $forumrecord = $this->get_forum_record();
        $user = $this->related['user'];
        $discussion = $this->discussion;

        $viewurl = $urlmanager->get_discussion_view_url_from_discussion($discussion);
        $markasread = $urlmanager->get_mark_discussion_as_read_url_from_discussion($discussion);

        // TODO Group exporter.
        $groupdata = null;
        if ($group = groups_get_group($discussion->get_group_id())) {
            $groupdata = [
                'name' => $group->name,
                'urls' => [],
            ];
            $canviewparticipants = $capabilitymanager->can_view_participants($user, $discussion);
            if (!$group->hidepicture) {
                $url = get_group_picture_url($group, $forum->get_course_id());
                if (!empty($url)) {
                    $groupdata['urls']['picture'] = $url->out(false);
                }
            }
            if ($canviewparticipants) {
                $groupdata['urls']['userlist'] = (new \moodle_url('/user/index.php', [
                    'id' => $forum->get_course_id(),
                    'group' => $group->id,
                ]))->out(false);
            }
        }

        $data = [
            'id' => $discussion->get_id(),
            'forumid' => $forum->get_id(),
            'pinned' => $discussion->is_pinned(),
            'name' => format_string($discussion->get_name(), true, [
                'context' => $this->related['forum']->get_context(),
            ]),
            'times' => [
                'modified' => $discussion->get_time_modified(),
                'start' => $discussion->get_time_start(),
                'end' => $discussion->get_time_end(),
            ],
            'userstate' => [
                'subscribed' => \mod_forum\subscriptions::is_subscribed($user->id, $forumrecord, $discussion->get_id()),
            ],
            'capabilities' => [
                'subscribe' => $capabilitymanager->can_subscribe_to_discussion($user, $discussion),
                'move' => $capabilitymanager->can_move_discussion($user, $discussion),
                'pin' => $capabilitymanager->can_pin_discussion($user, $discussion),
                'post' => $capabilitymanager->can_post_in_discussion($user, $discussion)
            ],
            'urls' => [
                'view' => $viewurl,
                'markasread' => $markasread,
            ],
        ];

        if ($groupdata) {
            $data['group'] = $groupdata;
        }

        return $data;
    }

    private function get_forum_record() {
        $forumdbdatamapper = $this->related['legacydatamapperfactory']->get_forum_data_mapper();
        return $forumdbdatamapper->to_legacy_object($this->related['forum']);
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'legacydatamapperfactory' => 'mod_forum\local\factories\legacy_data_mapper',
            'context' => 'context',
            'forum' => 'mod_forum\local\entities\forum',
            'capabilitymanager' => 'mod_forum\local\managers\capability',
            'urlmanager' => 'mod_forum\local\managers\url',
            'user' => 'stdClass',
        ];
    }
}
