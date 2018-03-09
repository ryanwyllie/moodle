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
 * Question external functions tests.
 *
 * @package    core_question
 * @category   external
 * @copyright  2016 Pau Ferrer <pau@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

/**
 * Question external functions tests
 *
 * @package    core_question
 * @category   external
 * @copyright  2016 Pau Ferrer <pau@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class core_question_external_testcase extends externallib_advanced_testcase {

    /**
     * Set up for every test
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Setup test data.
        $this->course = $this->getDataGenerator()->create_course();

        // Create users.
        $this->student = self::getDataGenerator()->create_user();

        // Users enrolments.
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $this->studentrole->id, 'manual');
    }

    /**
     * Test update question flag
     */
    public function test_core_question_update_flag() {

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        // Create a question category.
        $cat = $questiongenerator->create_question_category();

        $quba = question_engine::make_questions_usage_by_activity('core_question_update_flag', context_system::instance());
        $quba->set_preferred_behaviour('deferredfeedback');
        $questiondata = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        $question = question_bank::load_question($questiondata->id);
        $slot = $quba->add_question($question);
        $qa = $quba->get_question_attempt($slot);

        self::setUser($this->student);

        $quba->start_all_questions();
        question_engine::save_questions_usage_by_activity($quba);

        $qubaid = $quba->get_id();
        $questionid = $question->id;
        $qaid = $qa->get_database_id();
        $checksum = md5($qubaid . "_" . $this->student->secret . "_" . $questionid . "_" . $qaid . "_" . $slot);

        $flag = core_question_external::update_flag($qubaid, $questionid, $qaid, $slot, $checksum, true);
        $this->assertTrue($flag['status']);

        // Test invalid checksum.
        try {
            // Using random_string to force failing.
            $checksum = md5($qubaid . "_" . random_string(11) . "_" . $questionid . "_" . $qaid . "_" . $slot);

            core_question_external::update_flag($qubaid, $questionid, $qaid, $slot, $checksum, true);
            $this->fail('Exception expected due to invalid checksum.');
        } catch (moodle_exception $e) {
            $this->assertEquals('errorsavingflags', $e->errorcode);
        }
    }

    /**
     * submit_tags_form should throw an exception when the question id doesn't match
     * a question.
     */
    public function test_submit_tags_form_incorrect_question_id() {
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        list ($category, $course, $qcat, $questions) = $questiongenerator->setup_course_and_questions();
        $questioncontext = context::instance_by_id($qcat->contextid);
        $editingcontext = $questioncontext;
        $question = $questions[0];
        // Generate an id for a question that doesn't exist.
        $missingquestionid = $questions[1]->id * 2;
        $question->id = $missingquestionid;
        $formdata = $this->generate_encoded_submit_tags_form_string($question, $qcat, $questioncontext, [], []);

        // We should receive an exception if the question doesn't exist.
        $this->expectException('moodle_exception');
        core_question_external::submit_tags_form($missingquestionid, $editingcontext->id, $formdata);
    }

    /**
     * submit_tags_form should throw an exception when the context id doesn't match
     * a context.
     */
    public function test_submit_tags_form_incorrect_context_id() {
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        list ($category, $course, $qcat, $questions) = $questiongenerator->setup_course_and_questions();
        $questioncontext = context::instance_by_id($qcat->contextid);
        $editingcontext = $questioncontext;
        $question = $questions[0];
        // Generate an id for a context that doesn't exist.
        $missingcontextid = $editingcontext->id * 200;
        $formdata = $this->generate_encoded_submit_tags_form_string($question, $qcat, $questioncontext, [], []);

        // We should receive an exception if the question doesn't exist.
        $this->expectException('moodle_exception');
        core_question_external::submit_tags_form($question->id, $missingcontextid, $formdata);
    }

    /**
     * submit_tags_form should return false when tags are disabled.
     */
    public function test_submit_tags_form_tags_disabled() {
        global $CFG;

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        list ($category, $course, $qcat, $questions) = $questiongenerator->setup_course_and_questions();
        $questioncontext = context::instance_by_id($qcat->contextid);
        $editingcontext = $questioncontext;
        $question = $questions[0];
        $formdata = $this->generate_encoded_submit_tags_form_string($question, $qcat, $questioncontext, [], []);

        $CFG->usetags = false;
        $result = core_question_external::submit_tags_form($question->id, $editingcontext->id, $formdata);
        $CFG->usetags = true;

        $this->assertFalse($result['status']);
    }

    /**
     * submit_tags_form should return false if the user does not have any capability
     * to tag the question.
     */
    public function test_submit_tags_form_no_tag_permissions() {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        list ($category, $course, $qcat, $questions) = $questiongenerator->setup_course_and_questions();
        $questioncontext = context::instance_by_id($qcat->contextid);
        $editingcontext = $questioncontext;
        $question = $questions[0];
        $formdata = $this->generate_encoded_submit_tags_form_string(
            $question,
            $qcat,
            $questioncontext,
            ['foo'],
            ['bar']
        );

        // Prohibit all of the tag capabilities.
        assign_capability('moodle/question:tagmine', CAP_PROHIBIT, $teacherrole->id, $questioncontext->id);
        assign_capability('moodle/question:tagall', CAP_PROHIBIT, $teacherrole->id, $questioncontext->id);

        $generator->enrol_user($user->id, $course->id, $teacherrole->id, 'manual');
        $this->setUser($user);

        $result = core_question_external::submit_tags_form($question->id, $editingcontext->id, $formdata);

        $this->assertFalse($result['status']);
    }

    /**
     * submit_tags_form should return false if the user only has the capability to
     * tag their own questions and the question is not theirs.
     */
    public function test_submit_tags_form_tagmine_permission_non_owner_question() {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        list ($category, $course, $qcat, $questions) = $questiongenerator->setup_course_and_questions();
        $questioncontext = context::instance_by_id($qcat->contextid);
        $editingcontext = $questioncontext;
        $question = $questions[0];
        $formdata = $this->generate_encoded_submit_tags_form_string(
            $question,
            $qcat,
            $questioncontext,
            ['foo'],
            ['bar']
        );

        // Make sure the question isn't created by the user.
        $question->createdby = $user->id + 1;

        // Prohibit all of the tag capabilities.
        assign_capability('moodle/question:tagmine', CAP_ALLOW, $teacherrole->id, $questioncontext->id);
        assign_capability('moodle/question:tagall', CAP_PROHIBIT, $teacherrole->id, $questioncontext->id);

        $generator->enrol_user($user->id, $course->id, $teacherrole->id, 'manual');
        $this->setUser($user);

        $result = core_question_external::submit_tags_form($question->id, $editingcontext->id, $formdata);

        $this->assertFalse($result['status']);
    }

    /**
     * Editing context: course
     * Question context: course
     *
     * Expectation:
     * Should not be able to set course tags.
     */
    public function test_submit_tags_form_course_course() {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        list ($category, $course, $qcat, $questions) = $questiongenerator->setup_course_and_questions('course');
        $questioncontext = context::instance_by_id($qcat->contextid);
        $editingcontext = $questioncontext;
        $question = $questions[0];
        $formdata = $this->generate_encoded_submit_tags_form_string(
            $question,
            $qcat,
            $questioncontext,
            ['foo'], // Question tags.
            ['bar'] // Course tags.
        );

        // Make sure the user has capabilities.
        assign_capability('moodle/question:tagall', CAP_ALLOW, $teacherrole->id, $questioncontext->id);

        $generator->enrol_user($user->id, $course->id, $teacherrole->id, 'manual');
        $this->setUser($user);

        $result = core_question_external::submit_tags_form($question->id, $editingcontext->id, $formdata);

        $this->assertTrue($result['status']);

        $tagobjects = core_tag_tag::get_item_tags('core_question', 'question', $question->id);

        $this->assertCount(1, $tagobjects);
        $tagobject = array_shift($tagobjects);
        $this->assertEquals('foo', $tagobject->name);
        $this->assertEquals($questioncontext->id, $tagobject->taginstancecontextid);
    }

    /**
     * Build the encoded form data expected by the submit_tags_form external function.
     *
     * @param  stdClass $question         The question record
     * @param  stdClass $questioncategory The question category record
     * @param  context  $questioncontext  Context for the question category
     * @param  array  $tags               A list of tag names for the question
     * @param  array  $coursetags         A list of course tag names for the question
     * @return string                    HTML encoded string of the data
     */
    protected function generate_encoded_submit_tags_form_string($question, $questioncategory,
            $questioncontext, $tags = [], $coursetags = []) {
        global $CFG;

        require_once($CFG->dirroot . '/question/type/tags_form.php');

        $data = [
            'id' => $question->id,
            'categoryid' => $questioncategory->id,
            'contextid' => $questioncontext->id,
            'questionname' => $question->name,
            'questioncategory' => $questioncategory->name,
            'context' => $questioncontext->get_context_name(false),
            'tags' => $tags,
            'coursetags' => $coursetags
        ];
        $data = core_question\form\tags::mock_generate_submit_keys($data);

        return http_build_query($data, '', '&');
    }
}
