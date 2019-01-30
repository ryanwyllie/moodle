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

use mod_forum\local\entities\post as post_entity;
use mod_forum\local\exporters\author as author_exporter;
use core\external\exporter;
use context;
use renderer_base;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Forum class.
 */
class post extends exporter {
    private $post;
    private $authorgroups;

    public function __construct(post_entity $post, array $authorgroups = [], $related = []) {
        $this->post = $post;
        $this->authorgroups = $authorgroups;
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
            'subject' => ['type' => PARAM_TEXT],
            'message' => ['type' => PARAM_RAW],
            'messageformat' => ['type' => PARAM_INT],
            'author' => ['type' => author_exporter::read_properties_definition()],
            'hasparent' => ['type' => PARAM_BOOL],
            'parentid' => [
                'type' => PARAM_INT,
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'timecreated' => ['type' => PARAM_INT],
            'isread' => ['type' => PARAM_BOOL],
            'capabilities' => [
                'type' => [
                    'view' => ['type' => PARAM_BOOL],
                    'edit' => ['type' => PARAM_BOOL],
                    'delete' => ['type' => PARAM_BOOL],
                    'split' => ['type' => PARAM_BOOL],
                    'reply' => ['type' => PARAM_BOOL]
                ]
            ],
            'urls' => [
                'type' => [
                    'view' => ['type' => PARAM_URL],
                    'viewparent' => [
                        'type' => PARAM_URL,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
                    'edit' => ['type' => PARAM_URL],
                    'delete' => ['type' => PARAM_URL],
                    'split' => ['type' => PARAM_URL],
                    'reply' => ['type' => PARAM_URL]
                ]
            ],
            'attachments' => [
                'multiple' => true,
                'type' => [
                    'filename' => ['type' => PARAM_FILE],
                    'mimetype' => ['type' => PARAM_TEXT],
                    'contextid' => ['type' => PARAM_INT],
                    'component' => ['type' => PARAM_TEXT],
                    'filearea' => ['type' => PARAM_TEXT],
                    'itemid' => ['type' => PARAM_INT],
                    'urls' => [
                        'type' => [
                            'file' => ['type' => PARAM_URL]
                        ]
                    ],
                    'html' => [
                        'type' => [
                            'icon' => ['type' => PARAM_RAW]
                        ]
                    ]
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
        global $CFG;

        $post = $this->post;
        $authorgroups = $this->authorgroups;
        $forum = $this->related['forum'];
        $discussion = $this->related['discussion'];
        $author = $post->get_author();
        $user = $this->related['user'];
        $context = $this->related['context'];
        $readreceiptcollection = $this->related['readreceiptcollection'];
        $forumrecord = $this->get_forum_record();
        $discussionrecord = $this->get_discussion_record();
        $postrecord = $this->get_post_record();
        $isdeleted = $post->is_deleted();

        $capabilitymanager = $this->related['capabilitymanager'];
        $canview = $capabilitymanager->can_view_post($user, $discussion, $post);
        $canedit = $capabilitymanager->can_edit_post($user, $discussion, $post);
        $candelete = $capabilitymanager->can_delete_post($user, $discussion, $post);
        $cansplit = $capabilitymanager->can_split_post($user, $discussion, $post);
        $canreply = $capabilitymanager->can_reply_to_post($user, $discussion, $post);

        $urlmanager = $this->related['urlmanager'];
        $viewurl = $urlmanager->get_view_post_url_from_post($post);
        $viewparenturl = $post->has_parent() ? $urlmanager->get_view_post_url_from_post_id($post->get_discussion_id(), $post->get_parent_id()) : null;
        $editurl = $urlmanager->get_edit_post_url_from_post($post);
        $deleteurl = $urlmanager->get_delete_post_url_from_post($post);
        $spliturl = $urlmanager->get_split_discussion_at_post_url_from_post($post);
        $replyurl = $urlmanager->get_reply_to_post_url_from_post($post);

        $authorexporter = new author_exporter($author, $authorgroups, $canview, $this->related);
        $exportedauthor = $authorexporter->export($output);

        if ($canview && !$isdeleted) {
            $subject = $post->get_subject();
            $timecreated = $post->get_time_created();
            $message = file_rewrite_pluginfile_urls(
                $post->get_message(),
                'pluginfile.php',
                $context->id,
                'mod_forum',
                'post',
                $post->get_id()
            );

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');
                $message .= plagiarism_get_links([
                    'userid' => $post->get_user_id(),
                    'content' => $message,
                    'cmid' => $forum->get_course_module_record()->id,
                    'course' => $post->get_course_id(),
                    'forum' => $forum->get_id()
                ]);
            }

            $message = format_text(
                $message,
                $post->get_message_format(),
                (object) [
                    'para' => false,
                    'trusted' => $post->is_message_trusted(),
                    'context' => $context
                ]
            );
        } else {
            $subject = $isdeleted ? get_string('forumsubjectdeleted', 'forum') : get_string('forumsubjecthidden','forum');
            $message = $isdeleted ? get_string('forumbodydeleted', 'forum') : get_string('forumbodyhidden','forum');
            $timecreated = null;

            if ($isdeleted) {
                $exportedauthor['fullname'] = null;
            }
        }

        return [
            'id' => $post->get_id(),
            'subject' => $subject,
            'message' => $message,
            'messageformat' => $post->get_message_format(),
            'author' => $exportedauthor,
            'hasparent' => $post->has_parent(),
            'parentid' => $post->has_parent() ? $post->get_parent_id() : null,
            'timecreated' => $timecreated,
            'isread' => $readreceiptcollection->has_user_read_post($user, $post),
            'capabilities' => [
                'view' => $canview,
                'edit' => $canedit,
                'delete' => $candelete,
                'split' => $cansplit,
                'reply' => $canreply
            ],
            'urls' => [
                'view' => $viewurl->out(),
                'viewparent' => $viewparenturl ? $viewparenturl->out() : null,
                'edit' => $editurl->out(),
                'delete' => $deleteurl->out(),
                'split' => $spliturl->out(),
                'reply' => $replyurl->out()
            ],
            'attachments' => $this->get_attachments($post, $output)
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
            'readreceiptcollection' => 'mod_forum\local\entities\post_read_receipt_collection',
            'urlmanager' => 'mod_forum\local\managers\url',
            'forum' => 'mod_forum\local\entities\forum',
            'discussion' => 'mod_forum\local\entities\discussion',
            'user' => 'stdClass',
            'context' => 'context'
        ];
    }

    private function get_attachments($post, $output) {
        global $CFG;

        return array_map(function($attachment) use ($output, $CFG) {
            $filename = $attachment->get_filename();
            $mimetype = $attachment->get_mimetype();
            $contextid = $attachment->get_contextid();
            $component = $attachment->get_component();
            $filearea = $attachment->get_filearea();
            $itemid = $attachment->get_itemid();
            $iconhtml = $output->pix_icon(
                file_file_icon($attachment),
                get_mimetype_description($attachment),
                'moodle',
                ['class' => 'icon']
            );
            $fileurl = file_encode_url(
                $CFG->wwwroot . '/pluginfile.php',
                '/' . implode('/', [$contextid, $component, $filearea, $itemid, $filename])
            );
            $isimage = in_array($mimetype, ['image/gif', 'image/jpeg', 'image/png']);
            return [
                'filename' => $filename,
                'mimetype' => $mimetype,
                'contextid' => $contextid,
                'component' => $component,
                'filearea' => $filearea,
                'itemid' => $itemid,
                'isimage' => $isimage,
                'urls' => [
                    'file' => $fileurl
                ],
                'html' => [
                    'icon' => $iconhtml
                ]
            ];
        }, $post->get_attachments());
    }

    private function get_forum_record() {
        $forumdbdatamapper = $this->related['legacydatamapperfactory']->get_forum_data_mapper();
        return $forumdbdatamapper->to_legacy_object($this->related['forum']);
    }

    private function get_discussion_record() {
        $discussiondbdatamapper = $this->related['legacydatamapperfactory']->get_discussion_data_mapper();
        return $discussiondbdatamapper->to_legacy_object($this->related['discussion']);
    }

    private function get_post_record() {
        $postdbdatamapper = $this->related['legacydatamapperfactory']->get_post_data_mapper();
        return $postdbdatamapper->to_legacy_object($this->post);
    }
}
