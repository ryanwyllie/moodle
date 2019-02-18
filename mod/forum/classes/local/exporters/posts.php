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

use mod_forum\local\exporters\post as post_exporter;
use core\external\exporter;
use renderer_base;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Forum class.
 */
class posts extends exporter {
    private $posts;
    private $attachmentsbypostid;
    private $groupsbyauthorid;
    private $tagsbypostid;
    private $ratingbypostid;

    public function __construct(
        array $posts,
        array $attachmentsbypostid = [],
        array $groupsbyauthorid = [],
        array $tagsbypostid = [],
        array $ratingbypostid = [],
        $related = []
    ) {
        $this->posts = $posts;
        $this->attachmentsbypostid = $attachmentsbypostid;
        $this->groupsbyauthorid = $groupsbyauthorid;
        $this->tagsbypostid = $tagsbypostid;
        $this->ratingbypostid = $ratingbypostid;
        return parent::__construct([], $related);
    }

    /**
     * Return the list of additional properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'posts' => [
                'type' => post_exporter::read_properties_definition(),
                'multiple' => true
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
        $related = $this->related;
        $attachmentsbypostid = $this->attachmentsbypostid;
        $groupsbyauthorid = $this->groupsbyauthorid;
        $tagsbypostid = $this->tagsbypostid;
        $ratingbypostid = $this->ratingbypostid;
        $exportedposts = array_map(
            function($post) use ($related, $attachmentsbypostid, $groupsbyauthorid, $tagsbypostid, $ratingbypostid, $output) {
                $authorid = $post->get_author()->get_id();
                $postid = $post->get_id();
                $attachments = isset($attachmentsbypostid[$postid]) ? $attachmentsbypostid[$postid] : [];
                $authorgroups = isset($groupsbyauthorid[$authorid]) ? $groupsbyauthorid[$authorid] : [];
                $tags = isset($tagsbypostid[$postid]) ? $tagsbypostid[$postid] : [];
                $rating = isset($ratingbypostid[$postid]) ? $ratingbypostid[$postid] : null;
                $exporter = new post_exporter($post, array_merge($related, [
                    'attachments' => $attachments,
                    'authorgroups' => $authorgroups,
                    'tags' => $tags,
                    'rating' => $rating
                ]));
                return $exporter->export($output);
            },
            $this->posts
        );

        return [
            'posts' => $exportedposts
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
            'forum' => 'mod_forum\local\entities\forum',
            'discussion' => 'mod_forum\local\entities\discussion',
            'readreceiptcollection' => 'mod_forum\local\entities\post_read_receipt_collection?',
            'user' => 'stdClass',
            'context' => 'context'
        ];
    }
}
