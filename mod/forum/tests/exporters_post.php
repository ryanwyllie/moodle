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
 * The exported_posts builder tests.
 *
 * @package    mod_forum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_forum\local\entities\discussion as discussion_entity;
use \mod_forum\local\entities\post as post_entity;
use \mod_forum\local\exporters\post as post_exporter;
use \mod_forum\local\managers\capability as capability_manager;
use stdClass;

global $CFG;
require_once(__DIR__ . '/generator_trait.php');
require_once($CFG->dirroot . '/rating/lib.php');

class exporters_post_testcase extends advanced_testcase {
    // Make use of the test generator trait.
    use mod_forum_tests_generator_trait;

    /**
     * Test the export function returns expected values.
     */
    public function test_export_post() {
        global $CFG, $PAGE;
        $this->resetAfterTest();

        $CFG->enableportfolios = true;
        $filestorage = get_file_storage();
        $renderer = $PAGE->get_renderer('core');
        $datagenerator = $this->getDataGenerator();
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $user = $datagenerator->create_user();
        $course = $datagenerator->create_course();
        $forum = $datagenerator->create_module('forum', ['course' => $course->id]);
        $coursemodule = get_coursemodule_from_instance('forum', $forum->id);
        $context = context_module::instance($coursemodule->id);
        $discussion = $forumgenerator->create_discussion((object) [
            'course' => $forum->course,
            'userid' => $user->id,
            'forum' => $forum->id
        ]);
        $now = time();
        $post = $forumgenerator->create_post((object) [
            'discussion' => $discussion->id,
            'parent' => 0,
            'userid' => $user->id,
            'created' => $now,
            'modified' => $now,
            'subject' => 'This is the subject',
            'message' => 'This is the message',
            'messagetrust' => 1,
            'attachment' => 0,
            'totalscore' => 0,
            'mailnow' => 1,
            'deleted' => 0
        ]);

        \core_tag_tag::set_item_tags('mod_forum', 'forum_posts', $post->id, $context, ['foo', 'bar']);
        $tags = \core_tag_tag::get_item_tags('mod_forum', 'forum_posts', $post->id);
        $attachment = $filestorage->create_file_from_string(
            [
                'contextid' => $context->id,
                'component' => 'mod_forum',
                'filearea'  => 'attachment',
                'itemid'    => $post->id,
                'filepath'  => '/',
                'filename'  => 'example1.jpg',
            ],
            'image contents'
        );

        $canview = true;
        $canedit = true;
        $candelete = true;
        $cansplit = true;
        $canreply = true;
        $canexport = true;
        $cancontrolreadstatus = true;
        $capabilitymanager = new test_capability_manager(
            $canview,
            $canedit,
            $candelete,
            $cansplit,
            $canreply,
            $canexport,
            $cancontrolreadstatus
        );
        $managerfactory = \mod_forum\local\container::get_manager_factory();
        $entityfactory = \mod_forum\local\container::get_entity_factory();
        $forum = $entityfactory->get_forum_from_stdClass($forum, $context, $coursemodule, $course);
        $discussion = $entityfactory->get_discussion_from_stdClass($discussion);
        $post = $entityfactory->get_post_from_stdClass($post);
        $author = $entityfactory->get_author_from_stdClass($user);

        $exporter = new post_exporter($post, [
            'legacydatamapperfactory' => \mod_forum\local\container::get_legacy_data_mapper_factory(),
            'capabilitymanager' => $capabilitymanager,
            'readreceiptcollection' => null,
            'urlmanager' => $managerfactory->get_url_manager($forum),
            'forum' => $forum,
            'discussion' => $discussion,
            'author' => $author,
            'user' => $user,
            'context' => $context,
            'authorgroups' => [],
            'attachments' => [$attachment],
            'tags' => $tags,
            'rating' => null,
            'includehtml' => true
        ]);

        $exportedpost = $exporter->export($renderer);

        $this->assertEquals('This is the subject', $exportedpost->subject);
        $this->assertEquals('This is the message', $exportedpost->message);
        $this->assertEquals($user->id, $exportedpost->author->id);
        $this->assertEquals($discussion->get_id(), $exportedpost->discussionid);
        $this->assertEquals(false, $exportedpost->hasparent);
        $this->assertEquals(null, $exportedpost->parentid);
        $this->assertEquals($now, $exportedpost->timecreated);
        $this->assertEquals(null, $exportedpost->unread);
        $this->assertEquals(false, $exportedpost->isdeleted);
        $this->assertEquals($canview, $exportedpost->capabilities['view']);
        $this->assertEquals($canedit, $exportedpost->capabilities['edit']);
        $this->assertEquals($candelete, $exportedpost->capabilities['delete']);
        $this->assertEquals($cansplit, $exportedpost->capabilities['split']);
        $this->assertEquals($canreply, $exportedpost->capabilities['reply']);
        $this->assertEquals($canexport, $exportedpost->capabilities['export']);
        $this->assertEquals($cancontrolreadstatus, $exportedpost->capabilities['controlreadstatus']);
        $this->assertNotEmpty($exportedpost->urls['view']);
        $this->assertNotEmpty($exportedpost->urls['viewisolated']);
        $this->assertNotEmpty($exportedpost->urls['edit']);
        $this->assertNotEmpty($exportedpost->urls['delete']);
        $this->assertNotEmpty($exportedpost->urls['split']);
        $this->assertNotEmpty($exportedpost->urls['reply']);
        $this->assertNotEmpty($exportedpost->urls['markasread']);
        $this->assertNotEmpty($exportedpost->urls['markasunread']);
        $this->assertCount(1, $exportedpost->attachments);
        $this->assertEquals('example1.jpg', $exportedpost->attachments[0]->filename);
        $this->assertCount(2, $exportedpost->tags);
        $this->assertEquals('foo', $exportedpost->tags[0]['displayname']);
        $this->assertEquals('bar', $exportedpost->tags[1]['displayname']);
        $this->assertEquals(null, $exportedpost->html['rating']);
        $this->assertNotEquals(null, $exportedpost->html['taglist']);
        $this->assertNotEmpty($exportedpost->html['authorsubheading']);
    }

    /**
     * Test exporting of a deleted post.
     */
    public function test_export_deleted_post() {
        global $CFG, $PAGE;
        $this->resetAfterTest();

        $CFG->enableportfolios = true;
        $filestorage = get_file_storage();
        $renderer = $PAGE->get_renderer('core');
        $datagenerator = $this->getDataGenerator();
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $user = $datagenerator->create_user();
        $course = $datagenerator->create_course();
        $forum = $datagenerator->create_module('forum', ['course' => $course->id]);
        $coursemodule = get_coursemodule_from_instance('forum', $forum->id);
        $context = context_module::instance($coursemodule->id);
        $discussion = $forumgenerator->create_discussion((object) [
            'course' => $forum->course,
            'userid' => $user->id,
            'forum' => $forum->id
        ]);
        $now = time();
        $post = $forumgenerator->create_post((object) [
            'discussion' => $discussion->id,
            'parent' => 0,
            'userid' => $user->id,
            'created' => $now,
            'modified' => $now,
            'subject' => 'This is the subject',
            'message' => 'This is the message',
            'messagetrust' => 1,
            'attachment' => 0,
            'totalscore' => 0,
            'mailnow' => 1,
            'deleted' => 1
        ]);

        \core_tag_tag::set_item_tags('mod_forum', 'forum_posts', $post->id, $context, ['foo', 'bar']);
        $tags = \core_tag_tag::get_item_tags('mod_forum', 'forum_posts', $post->id);
        $attachment = $filestorage->create_file_from_string(
            [
                'contextid' => $context->id,
                'component' => 'mod_forum',
                'filearea'  => 'attachment',
                'itemid'    => $post->id,
                'filepath'  => '/',
                'filename'  => 'example1.jpg',
            ],
            'image contents'
        );

        $canview = true;
        $canedit = true;
        $candelete = true;
        $cansplit = true;
        $canreply = true;
        $canexport = true;
        $cancontrolreadstatus = true;
        $capabilitymanager = new test_capability_manager(
            $canview,
            $canedit,
            $candelete,
            $cansplit,
            $canreply,
            $canexport,
            $cancontrolreadstatus
        );
        $managerfactory = \mod_forum\local\container::get_manager_factory();
        $entityfactory = \mod_forum\local\container::get_entity_factory();
        $forum = $entityfactory->get_forum_from_stdClass($forum, $context, $coursemodule, $course);
        $discussion = $entityfactory->get_discussion_from_stdClass($discussion);
        $post = $entityfactory->get_post_from_stdClass($post);
        $author = $entityfactory->get_author_from_stdClass($user);

        $exporter = new post_exporter($post, [
            'legacydatamapperfactory' => \mod_forum\local\container::get_legacy_data_mapper_factory(),
            'capabilitymanager' => $capabilitymanager,
            'readreceiptcollection' => null,
            'urlmanager' => $managerfactory->get_url_manager($forum),
            'forum' => $forum,
            'discussion' => $discussion,
            'author' => $author,
            'user' => $user,
            'context' => $context,
            'authorgroups' => [],
            'attachments' => [$attachment],
            'tags' => $tags,
            'rating' => null,
            'includehtml' => true
        ]);

        $exportedpost = $exporter->export($renderer);

        $this->assertNotEquals('This is the subject', $exportedpost->subject);
        $this->assertNotEquals('This is the message', $exportedpost->message);
        $this->assertEquals(null, $exportedpost->timecreated);
        $this->assertEquals(null, $exportedpost->unread);
        $this->assertEquals(true, $exportedpost->isdeleted);
        $this->assertEquals([], $exportedpost->attachments);
        $this->assertEquals([], $exportedpost->tags);
        $this->assertEquals(null, $exportedpost->html['rating']);
        $this->assertEquals(null, $exportedpost->html['taglist']);
        $this->assertEquals(null, $exportedpost->html['authorsubheading']);
    }

    /**
     * Test exporting of a post the user can't view.
     */
    public function test_export_post_no_view_capability() {
        global $CFG, $PAGE;
        $this->resetAfterTest();

        $CFG->enableportfolios = true;
        $filestorage = get_file_storage();
        $renderer = $PAGE->get_renderer('core');
        $datagenerator = $this->getDataGenerator();
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $user = $datagenerator->create_user();
        $course = $datagenerator->create_course();
        $forum = $datagenerator->create_module('forum', ['course' => $course->id]);
        $coursemodule = get_coursemodule_from_instance('forum', $forum->id);
        $context = context_module::instance($coursemodule->id);
        $discussion = $forumgenerator->create_discussion((object) [
            'course' => $forum->course,
            'userid' => $user->id,
            'forum' => $forum->id
        ]);
        $now = time();
        $post = $forumgenerator->create_post((object) [
            'discussion' => $discussion->id,
            'parent' => 0,
            'userid' => $user->id,
            'created' => $now,
            'modified' => $now,
            'subject' => 'This is the subject',
            'message' => 'This is the message',
            'messagetrust' => 1,
            'attachment' => 0,
            'totalscore' => 0,
            'mailnow' => 1,
            'deleted' => 0
        ]);

        \core_tag_tag::set_item_tags('mod_forum', 'forum_posts', $post->id, $context, ['foo', 'bar']);
        $tags = \core_tag_tag::get_item_tags('mod_forum', 'forum_posts', $post->id);
        $attachment = $filestorage->create_file_from_string(
            [
                'contextid' => $context->id,
                'component' => 'mod_forum',
                'filearea'  => 'attachment',
                'itemid'    => $post->id,
                'filepath'  => '/',
                'filename'  => 'example1.jpg',
            ],
            'image contents'
        );

        $canview = false;
        $canedit = true;
        $candelete = true;
        $cansplit = true;
        $canreply = true;
        $canexport = true;
        $cancontrolreadstatus = true;
        $capabilitymanager = new test_capability_manager(
            $canview,
            $canedit,
            $candelete,
            $cansplit,
            $canreply,
            $canexport,
            $cancontrolreadstatus
        );
        $managerfactory = \mod_forum\local\container::get_manager_factory();
        $entityfactory = \mod_forum\local\container::get_entity_factory();
        $forum = $entityfactory->get_forum_from_stdClass($forum, $context, $coursemodule, $course);
        $discussion = $entityfactory->get_discussion_from_stdClass($discussion);
        $post = $entityfactory->get_post_from_stdClass($post);
        $author = $entityfactory->get_author_from_stdClass($user);

        $exporter = new post_exporter($post, [
            'legacydatamapperfactory' => \mod_forum\local\container::get_legacy_data_mapper_factory(),
            'capabilitymanager' => $capabilitymanager,
            'readreceiptcollection' => null,
            'urlmanager' => $managerfactory->get_url_manager($forum),
            'forum' => $forum,
            'discussion' => $discussion,
            'author' => $author,
            'user' => $user,
            'context' => $context,
            'authorgroups' => [],
            'attachments' => [$attachment],
            'tags' => $tags,
            'rating' => null,
            'includehtml' => true
        ]);

        $exportedpost = $exporter->export($renderer);

        $this->assertNotEquals('This is the subject', $exportedpost->subject);
        $this->assertNotEquals('This is the message', $exportedpost->message);
        $this->assertEquals(null, $exportedpost->timecreated);
        $this->assertEquals(null, $exportedpost->unread);
        $this->assertEquals(false, $exportedpost->isdeleted);
        $this->assertEquals([], $exportedpost->attachments);
        $this->assertEquals([], $exportedpost->tags);
        $this->assertEquals(null, $exportedpost->html['rating']);
        $this->assertEquals(null, $exportedpost->html['taglist']);
        $this->assertEquals(null, $exportedpost->html['authorsubheading']);
    }
}

class test_capability_manager extends capability_manager {
    private $view;
    private $edit;
    private $delete;
    private $split;
    private $reply;
    private $export;
    private $controlreadstatus;

    public function __construct(
        bool $view = true,
        bool $edit = true,
        bool $delete = true,
        bool $split = true,
        bool $reply = true,
        bool $export = true,
        bool $controlreadstatus = true
    ) {
        $this->view = $view;
        $this->edit = $edit;
        $this->delete = $delete;
        $this->split = $split;
        $this->reply = $reply;
        $this->export = $export;
        $this->controlreadstatus = $controlreadstatus;
    }

    public function can_view_post(stdClass $user, discussion_entity $discussion, post_entity $post) : bool {
        return $this->view;
    }

    public function can_edit_post(stdClass $user, discussion_entity $discussion, post_entity $post) : bool {
        return $this->edit;
    }

    public function can_delete_post(stdClass $user, discussion_entity $discussion, post_entity $post) : bool {
        return $this->delete;
    }

    public function can_split_post(stdClass $user, discussion_entity $discussion, post_entity $post) : bool {
        return $this->split;
    }

    public function can_reply_to_post(stdClass $user, discussion_entity $discussion, post_entity $post) : bool {
        return $this->reply;
    }

    public function can_export_post(stdClass $user, post_entity $post) : bool {
        return $this->export;
    }

    public function can_manually_control_post_read_status(stdClass $user) : bool {
        return $this->controlreadstatus;
    }
}
