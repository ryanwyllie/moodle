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
 * Nested discussion renderer.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\renderers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\builders\exported_posts as exported_posts_builder;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\entities\post_read_receipt_collection as read_receipt_collection_entity;
use mod_forum\local\factories\manager as manager_factory;
use renderer_base;
use stdClass;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Posts renderer class.
 */
class posts_search_results {
    private $renderer;
    private $exportedpostsbuilder;
    private $managerfactory;

    public function __construct(
        renderer_base $renderer,
        exported_posts_builder $exportedpostsbuilder,
        manager_factory $managerfactory
    ) {
        $this->renderer = $renderer;
        $this->exportedpostsbuilder = $exportedpostsbuilder;
        $this->managerfactory = $managerfactory;
    }

    public function render(
        stdClass $user,
        array $forumsbyid,
        array $discussionsbyid,
        array $posts,
        array $searchterms,
        array $readreceiptcollectionbyforumid = []
    ) : string {
        $exportedposts = $this->exportedpostsbuilder->build(
            $user,
            $forumsbyid,
            $discussionsbyid,
            $posts,
            $readreceiptcollectionbyforumid
        );

        $highlightwords = implode(' ', $searchterms);
        $exportedposts = array_map(
            function($exportedpost) use (
                $forumsbyid,
                $discussionsbyid,
                $searchterms,
                $highlightwords
            ) {
                $discussion = $discussionsbyid[$exportedpost->discussionid];
                $forum = $forumsbyid[$discussion->get_forum_id()];
                $urlmananger = $this->managerfactory->get_url_manager($forum);

                $exportedpost->urls['viewforum'] = $urlmananger->get_forum_view_url_from_forum($forum);
                $exportedpost->urls['viewdiscussion'] = $urlmananger->get_discussion_view_url_from_discussion($discussion);
                $exportedpost->subject = highlight($highlightwords, $exportedpost->subject);
                $exportedpost->forumname = format_string($forum->get_name(), true);
                $exportedpost->discussionname = format_string(highlight($highlightwords, $discussion->get_name()), true);
                $exportedpost->showdiscussionname = $forum->get_type() != 'single';

                // Identify search terms only found in HTML markup, and add a warning about them to
                // the start of the message text.
                $missingterms = '';
                $exportedpost->message = highlight($highlightwords, $exportedpost->message, 0, '<fgw9sdpq4>', '</fgw9sdpq4>');

                foreach ($searchterms as $searchterm) {
                    if (
                        preg_match("/$searchterm/i", $exportedpost->message) &&
                        !preg_match('/<fgw9sdpq4>' . $searchterm . '<\/fgw9sdpq4>/i', $exportedpost->message)
                    ) {
                        $missingterms .= " $searchterm";
                    }
                }

                $exportedpost->message = str_replace('<fgw9sdpq4>', '<span class="highlight">', $exportedpost->message);
                $exportedpost->message = str_replace('</fgw9sdpq4>', '</span>', $exportedpost->message);

                if ($missingterms) {
                    $strmissingsearchterms = get_string('missingsearchterms', 'forum');
                    $exportedpost->message = '<p class="highlight2">' . $strmissingsearchterms . ' '
                        . $missing_terms . '</p>' . $exportedpost->message;
                }

                return $exportedpost;
            },
            $exportedposts
        );

        return $this->renderer->render_from_template(
            'mod_forum/forum_posts_search_results',
            ['posts' => $exportedposts]
        );
    }
}
