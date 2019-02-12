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
    private $groupsbyauthorid;
    private $tagsbypostid;
    private $ratingbypostid;

    public function __construct(
        array $posts,
        array $groupsbyauthorid = [],
        array $tagsbypostid = [],
        array $ratingbypostid = [],
        $related = []
    ) {
        $this->posts = $posts;
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
        $groupsbyauthorid = $this->groupsbyauthorid;
        $tagsbypostid = $this->tagsbypostid;
        $ratingbypostid = $this->ratingbypostid;
        $nestedposts = $this->sort_posts_into_replies($this->posts);
        $exportposts = function($posts) use ($related, $groupsbyauthorid, $tagsbypostid, $ratingbypostid, $output, &$exportposts) {
            $exportedposts = [];

            foreach ($posts as $record) {
                list($post, $replies) = $record;
                $authorid = $post->get_author()->get_id();
                $postid = $post->get_id();
                $authorgroups = isset($groupsbyauthorid[$authorid]) ? $groupsbyauthorid[$authorid] : [];
                $tags = isset($tagsbypostid[$postid]) ? $tagsbypostid[$postid] : [];
                $rating = isset($ratingbypostid[$postid]) ? $ratingbypostid[$postid] : null;
                $exporter = new post_exporter($post, array_merge($related, [
                    'authorgroups' => $authorgroups,
                    'tags' => $tags,
                    'rating' => $rating
                ]));
                $exportedposts[] = $exporter->export($output);
                $exportedposts = array_merge($exportedposts, $exportposts($replies));
            }

            return $exportedposts;
        };

        return [
            'posts' => $exportposts($nestedposts)
        ];
    }

    private function sort_posts_into_replies($posts) : array {
        $postids = array_map(function($post) {
            return $post->get_id();
        }, $posts);

        list($parents, $replies) = array_reduce($posts, function($carry, $post) use ($postids) {
            $parentid = $post->get_parent_id();

            if (in_array($parentid, $postids)) {
                // This post is a reply to another post in the list so add it to the replies list.
                $carry[1][] = $post;
            } else {
                // This post isn't replying to anything in our list so it's a parent.
                $carry[0][] = $post;
            }

            return $carry;
        }, [[], []]);

        if (empty($replies)) {
            return array_map(function($parent) {
                return [$parent, []];
            }, $parents);
        }

        $sortedreplies = $this->sort_posts_into_replies($replies);

        return array_map(function($parent) use ($sortedreplies) {
            return [
                $parent,
                array_filter($sortedreplies, function($replydata) use ($parent) {
                    return $replydata[0]->get_parent_id() == $parent->get_id();
                })
            ];
        }, $parents);
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
