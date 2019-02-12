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
use mod_forum\local\factories\exporter as exporter_factory;
use core\external\exporter;
use context;
use core_tag_tag;
use renderer_base;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Forum class.
 */
class post extends exporter {
    private $post;

    public function __construct(post_entity $post, $related = []) {
        $this->post = $post;
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
            'unread' => [
                'type' => PARAM_BOOL,
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'isdeleted' => ['type' => PARAM_BOOL],
            'haswordcount' => ['type' => PARAM_BOOL],
            'wordcount' => [
                'type' => PARAM_INT,
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'capabilities' => [
                'type' => [
                    'view' => ['type' => PARAM_BOOL],
                    'edit' => ['type' => PARAM_BOOL],
                    'delete' => ['type' => PARAM_BOOL],
                    'split' => ['type' => PARAM_BOOL],
                    'reply' => ['type' => PARAM_BOOL],
                    'export' => ['type' => PARAM_BOOL]
                ]
            ],
            'urls' => [
                'type' => [
                    'view' => [
                        'type' => PARAM_URL,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
                    'viewparent' => [
                        'type' => PARAM_URL,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
                    'edit' => [
                        'type' => PARAM_URL,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
                    'delete' => [
                        'type' => PARAM_URL,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
                    'split' => [
                        'type' => PARAM_URL,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
                    'reply' => [
                        'type' => PARAM_URL,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
                    'export' => [
                        'type' => PARAM_URL,
                        'optional' => true,
                        'default' => null,
                        'null' => NULL_ALLOWED
                    ],
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
                    'isimage' => ['type' => PARAM_BOOL],
                    'urls' => [
                        'type' => [
                            'file' => ['type' => PARAM_URL],
                            'export' => [
                                'type' => PARAM_URL,
                                'optional' => true,
                                'default' => null,
                                'null' => NULL_ALLOWED
                            ]
                        ]
                    ],
                    'html' => [
                        'type' => [
                            'icon' => ['type' => PARAM_RAW],
                            'plagiarism' => [
                                'type' => PARAM_RAW,
                                'optional' => true,
                                'default' => null,
                                'null' => NULL_ALLOWED
                            ],
                        ]
                    ]
                ]
            ],
            'tags' => [
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED,
                'multiple' => true,
                'type' => [
                    'id' => ['type' => PARAM_INT],
                    'tagid' => ['type' => PARAM_INT],
                    'isstandard' => ['type' => PARAM_BOOL],
                    'displayname' => ['type' => PARAM_TEXT],
                    'flag' => ['type' => PARAM_BOOL],
                    'urls' => [
                        'type' => [
                            'view' => ['type' => PARAM_URL]
                        ]
                    ]
                ]
            ],
            'rating' => [
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED,
                'type' => exporter_factory::get_rating_export_structure()
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
        $authorgroups = $this->related['authorgroups'];
        $forum = $this->related['forum'];
        $discussion = $this->related['discussion'];
        $author = $post->get_author();
        $user = $this->related['user'];
        $context = $this->related['context'];
        $readreceiptcollection = $this->related['readreceiptcollection'];
        $rating = $this->related['rating'];
        $forumrecord = $this->get_forum_record();
        $discussionrecord = $this->get_discussion_record();
        $postrecord = $this->get_post_record();
        $isdeleted = $post->is_deleted();
        $hasrating = $rating != null;

        $capabilitymanager = $this->related['capabilitymanager'];
        $canview = $capabilitymanager->can_view_post($user, $discussion, $post);
        $canedit = $capabilitymanager->can_edit_post($user, $discussion, $post);
        $candelete = $capabilitymanager->can_delete_post($user, $discussion, $post);
        $cansplit = $capabilitymanager->can_split_post($user, $discussion, $post);
        $canreply = $capabilitymanager->can_reply_to_post($user, $discussion, $post);
        $canexport = $CFG->enableportfolios && $capabilitymanager->can_export_post($user, $post);

        $urlmanager = $this->related['urlmanager'];
        $viewurl = $urlmanager->get_view_post_url_from_post($post);
        $viewparenturl = $post->has_parent() ? $urlmanager->get_view_post_url_from_post_id($post->get_discussion_id(), $post->get_parent_id()) : null;
        $editurl = $urlmanager->get_edit_post_url_from_post($post);
        $deleteurl = $urlmanager->get_delete_post_url_from_post($post);
        $spliturl = $urlmanager->get_split_discussion_at_post_url_from_post($post);
        $replyurl = $urlmanager->get_reply_to_post_url_from_post($post);
        $exporturl = $urlmanager->get_export_post_url_from_post($post);

        $authorexporter = new author_exporter($author, $authorgroups, ($canview && !$isdeleted), $this->related);
        $exportedauthor = $authorexporter->export($output);

        if ($canview && !$isdeleted) {
            $subject = $post->get_subject();
            $timecreated = $post->get_time_created();
            $message = $this->get_message($post);
        } else {
            $subject = $isdeleted ? get_string('forumsubjectdeleted', 'forum') : get_string('forumsubjecthidden','forum');
            $message = $isdeleted ? get_string('forumbodydeleted', 'forum') : get_string('forumbodyhidden','forum');
            $timecreated = null;

            if ($isdeleted) {
                $exportedauthor->fullname = null;
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
            'unread' => $readreceiptcollection ? !$readreceiptcollection->has_user_read_post($user, $post) : null,
            'isdeleted' => $isdeleted,
            'haswordcount' => $forum->should_display_word_count(),
            'wordcount' => $forum->should_display_word_count() ? count_words($message) : null,
            'capabilities' => [
                'view' => $canview,
                'edit' => $canedit,
                'delete' => $candelete,
                'split' => $cansplit,
                'reply' => $canreply,
                'export' => $canexport
            ],
            'urls' => [
                'view' => $canview ? $viewurl->out(false) : null,
                'viewparent' => $viewparenturl ? $viewparenturl->out(false) : null,
                'edit' => $canedit ? $editurl->out(false) : null,
                'delete' => $candelete ? $deleteurl->out(false) : null,
                'split' => $cansplit ? $spliturl->out(false) : null,
                'reply' => $canreply ? $replyurl->out(false) : null,
                'export' => $canexport && $exporturl ? $exporturl->out(false) : null
            ],
            'attachments' => $isdeleted ? [] : $this->get_attachments($post, $output, $canexport),
            'tags' => $isdeleted ? [] : $this->get_tags(),
            'rating' => (!$isdeleted && $hasrating) ? $this->get_rating($output) : null
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
            'exporterfactory' => 'mod_forum\local\factories\exporter',
            'capabilitymanager' => 'mod_forum\local\managers\capability',
            'readreceiptcollection' => 'mod_forum\local\entities\post_read_receipt_collection?',
            'urlmanager' => 'mod_forum\local\managers\url',
            'forum' => 'mod_forum\local\entities\forum',
            'discussion' => 'mod_forum\local\entities\discussion',
            'user' => 'stdClass',
            'context' => 'context',
            'authorgroups' => 'stdClass[]',
            'tags' => '\core_tag_tag[]?',
            'rating' => 'rating?'
        ];
    }

    private function get_message(post_entity $post) {
        $context = $this->related['context'];
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
                'userid' => $post->get_author()->get_id(),
                'content' => $message,
                'cmid' => $forum->get_course_module_record()->id,
                'course' => $forum->get_course_id(),
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

        return $message;
    }

    private function get_attachments(post_entity $post, renderer_base $output, bool $canexport) {
        global $CFG;

        $urlmanager = $this->related['urlmanager'];
        $enableplagiarism = $CFG->enableplagiarism;
        $wwwroot = $CFG->wwwroot;
        $forum = $this->related['forum'];

        if ($enableplagiarism) {
            require_once($CFG->libdir . '/plagiarismlib.php' );
        }

        return array_map(function($attachment) use (
            $output,
            $wwwroot,
            $enableplagiarism,
            $canexport,
            $forum,
            $post,
            $urlmanager
        ) {
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
                $wwwroot . '/pluginfile.php',
                '/' . implode('/', [$contextid, $component, $filearea, $itemid, $filename])
            );
            $exporturl = $canexport ? $urlmanager->get_export_attachment_url_from_post_and_attachment($post, $attachment) : null;
            $isimage = in_array($mimetype, ['image/gif', 'image/jpeg', 'image/png']);

            if ($enableplagiarism) {
                $plagiarismhtml = plagiarism_get_links([
                    'userid' => $post->get_author()->get_id(),
                    'file' => $attachment,
                    'cmid' => $forum->get_course_module_record()->id,
                    'course' => $forum->get_course_id(),
                    'forum' => $forum->get_id()
                ]);
            } else {
                $plagiarismhtml = null;
            }

            return [
                'filename' => $filename,
                'mimetype' => $mimetype,
                'contextid' => $contextid,
                'component' => $component,
                'filearea' => $filearea,
                'itemid' => $itemid,
                'isimage' => $isimage,
                'urls' => [
                    'file' => $fileurl,
                    'export' => $exporturl ? $exporturl->out(false) : null
                ],
                'html' => [
                    'icon' => $iconhtml,
                    'plagiarism' => $plagiarismhtml
                ]
            ];
        }, $post->get_attachments());
    }

    private function get_tags() {
        $user = $this->related['user'];
        $context = $this->related['context'];
        $capabilitymanager = $this->related['capabilitymanager'];
        $canmanagetags = $capabilitymanager->can_manage_tags($user);

        return array_values(array_map(function($tag) use ($context, $canmanagetags) {
            $viewurl = core_tag_tag::make_url($tag->tagcollid, $tag->rawname, 0, $context->id);
            return [
                'id' => $tag->taginstanceid,
                'tagid' => $tag->id,
                'isstandard' => $tag->isstandard,
                'displayname' => $tag->get_display_name(),
                'flag' => $canmanagetags && !empty($tag->flag),
                'urls' => [
                    'view' => $viewurl->out(false)
                ]
            ];
        }, $this->related['tags'] ?: []));
    }

    private function get_rating(renderer_base $rendererbase) {
        $rating = $this->related['rating'];
        $user = $this->related['user'];
        $ratingexporter = $this->related['exporterfactory']->get_rating_exporter($user, $rating);
        return $ratingexporter->export($rendererbase);
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
