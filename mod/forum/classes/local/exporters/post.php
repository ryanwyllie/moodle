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
            'parentid' => [
                'type' => PARAM_INT,
                'optional' => true,
                'default' => null,
                'null' => NULL_ALLOWED
            ],
            'timecreated' => ['type' => PARAM_INT],
            'cansee' => ['type' => PARAM_BOOL]
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $post = $this->post;
        $author = $post->get_author();
        $exportedauthor = (new author_exporter($author, ['context' => $this->related['context']]))->export($output);
        $user = $this->related['user'];
        $coursemodule = $this->related['coursemodule'];
        $forumrecord = $this->get_forum_record();
        $discussionrecord = $this->get_discussion_record();
        $postrecord = $this->get_post_record();
        $canseepost = forum_user_can_see_post($forumrecord, $discussionrecord, $postrecord, $user, $coursemodule);

        $subject = $canseepost ? $post->get_subject() : get_string('forumsubjecthidden','forum');
        $message = $canseepost ? format_text($post->get_message(), $post->get_message_format()) : get_string('forumbodyhidden','forum');
        $authorid = $canseepost ? $exportedauthor->id : null;
        $fullname = $canseepost ? $exportedauthor->fullname : get_string('forumauthorhidden', 'forum');
        $profileurl = $canseepost ? $exportedauthor->profileurl : null;
        $profileimageurl = $canseepost ? $exportedauthor->profileimageurl : null;
        $timecreated = $canseepost ? $post->get_time_created() : null;

        return [
            'id' => $post->get_id(),
            'subject' => $subject,
            'message' => $message,
            'messageformat' => $post->get_message_format(),
            'author' => [
                'id' => $authorid,
                'fullname' => $fullname,
                'profileurl' => $profileurl,
                'profileimageurl' => $profileimageurl
            ],
            'parentid' => $post->has_parent() ? $post->get_parent_id() : null,
            'timecreated' => $timecreated,
            'cansee' => $canseepost
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
            'forum' => 'mod_forum\local\entities\forum',
            'discussion' => 'mod_forum\local\entities\discussion',
            'coursemodule' => 'stdClass',
            'user' => 'stdClass',
            'context' => 'context'
        ];
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
