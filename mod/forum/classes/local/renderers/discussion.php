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

use mod_forum\local\entities\discussion as discussion_entity;
use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\serializers\forum as forum_serializer;
use mod_forum\local\serializers\serializer_interface;
use mod_forum\local\vaults\post as post_vault;
use context;
use html_writer;
use moodle_url;
use renderer_base;
use single_select;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Nested discussion renderer class.
 */
class discussion {
    private $renderer;
    private $displaymode;
    private $template;
    private $orderby;
    private $canrendercallback;
    private $serializer;
    private $postvault;

    public function __construct(
        renderer_base $renderer,
        serializer_interface $serializer,
        // TODO: change this to an interface dependency.
        post_vault $postvault,
        int $displaymode,
        string $template,
        string $orderby = 'created ASC',
        callable $validaterendercallback = null
    ) {
        $this->renderer = $renderer;
        $this->displaymode = $displaymode;
        $this->template = $template;
        $this->orderby = $orderby;
        $this->serializer = $serializer;
        $this->postvault = $postvault;

        if (is_null($validaterendercallback)) {
            $validaterendercallback = function($context, $discussion) {
                // No errors.
                return;
            };
        }

        $this->validaterendercallback = $validaterendercallback;
    }

    public function render(context $context, forum_entity $forum, discussion_entity $discussion) : string {
        global $PAGE, $USER;

        // Make sure we can render.
        $this->validate_render($context, $forum, $discussion);

        $posts = $this->postvault->get_from_discussion_id($discussion->get_id(), $this->orderby);
        $serializeddiscussion = $this->serializer->for_display($USER, $context, $forum, $discussion, $posts);
        $serializeddiscussion = array_merge($serializeddiscussion, [
            'html' => [
                'modeselectorform' => null,
                'notifications' => [],
                'subscribe' => null
            ]
        ]);

        $selectclasses = [];
        $selecturl = new moodle_url("/mod/forum/discuss2.php", ['d' => $discussion->get_id()]);

        if ($forum->get_type() == 'single') {
            $selecturl = new moodle_url("/mod/forum/view.php", ['f' => $forum->get_id()]);
            $selectclasses[] = "forummode";
        }

        $select = new single_select(
            $selecturl,
            'mode',
            forum_get_layout_modes(),
            $this->displaymode,
            null,
            'mode'
        );
        $select->set_label(get_string('displaymode', 'forum'), ['class' => 'accesshide']);
        $select->class = implode(' ', $selectclasses);
        $serializeddiscussion['html']['modeselectorform'] = $this->renderer->render($select);

        if (
            $forum->get_type() == 'qanda' &&
            !has_capability('mod/forum:viewqandawithoutposting', $context) &&
            !forum_user_has_posted($forum->get_id(), $discussion->get_id(), $USER->id)
        ) {
            $serializeddiscussion['html']['notifications'][] = $this->renderer->notification(get_string('qandanotify', 'forum'));
        }

        // is_guest should be used here as this also checks whether the user is a guest in the current course.
        // Guests and visitors cannot subscribe - only enrolled users.
        if ((!is_guest($context, $USER) && isloggedin()) && has_capability('mod/forum:viewdiscussion', $context)) {
            $forumserializer = new forum_serializer();
            $forumrecord = $forumserializer->to_db_records([$forum])[0];
            // Discussion subscription.
            if (\mod_forum\subscriptions::is_subscribable($forumrecord)) {
                $html = html_writer::div(
                    forum_get_discussion_subscription_icon($forumrecord, $discussion->get_id(), null, true),
                    'discussionsubscription'
                );
                $html .= forum_get_discussion_subscription_icon_preloaders();
                $serializeddiscussion['html']['subscribe'] = $html;
                // Add the subscription toggle JS.
                $PAGE->requires->yui_module('moodle-mod_forum-subscriptiontoggle', 'Y.M.mod_forum.subscriptiontoggle.init');
            }
        }

        return $this->renderer->render_from_template($this->template, $serializeddiscussion);
    }

    private function validate_render(context $context, forum_entity $forum, discussion_entity $discussion) {
        ($this->validaterendercallback)($context, $forum, $discussion);
    }
}
