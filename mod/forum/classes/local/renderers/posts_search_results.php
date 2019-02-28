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
 * Posts search results renderer.
 *
 * @package    mod_forum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\renderers;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\builders\exported_posts as exported_posts_builder;
use mod_forum\local\factories\manager as manager_factory;
use renderer_base;
use stdClass;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Posts search results renderer class. This class is designed to render a
 * list of posts from various different discussions and forums which occurs
 * when rendering the search results.
 */
class posts_search_results {
    /** @var renderer_base $renderer Renderer base */
    private $renderer;
    /** @var exported_posts_builder $exportedpostsbuilder Exported posts builder */
    private $exportedpostsbuilder;
    /** @var manager_factory $managerfactory Manager factory */
    private $managerfactory;

    /**
     * Constructor.
     *
     * @param renderer_base $renderer Renderer base
     * @param exported_posts_builder $exportedpostsbuilder Exported posts builder
     * @param manager_factory $managerfactory Manager factory
     */
    public function __construct(
        renderer_base $renderer,
        exported_posts_builder $exportedpostsbuilder,
        manager_factory $managerfactory
    ) {
        $this->renderer = $renderer;
        $this->exportedpostsbuilder = $exportedpostsbuilder;
        $this->managerfactory = $managerfactory;
    }

    /**
     * Render the posts for the given user from one or more forums and
     * discussions.
     *
     * @param stdClass $user The user viewing the posts
     * @param forum_entity[] $forumsbyid A list of all forums for these posts, indexed by forum id
     * @param discussion_entity[] $discussionsbyid A list of all discussions for these posts, indexed by discussion id
     * @param post_entities[] $posts A list of posts to render
     * @param string[] $searchterms The search terms to be highlighted in the posts
     * @return string
     */
    public function render(
        stdClass $user,
        array $forumsbyid,
        array $discussionsbyid,
        array $posts,
        array $searchterms
    ) : string {
        $exportedposts = $this->exportedpostsbuilder->build(
            $user,
            $forumsbyid,
            $discussionsbyid,
            $posts
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
                $exportedpost->discussionname = highlight($highlightwords, format_string($discussion->get_name(), true));
                $exportedpost->showdiscussionname = $forum->get_type() != 'single';

                // Identify search terms only found in HTML markup, and add a warning about them to
                // the start of the message text. This logic was copied exactly as is from the previous
                // implementation.
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
