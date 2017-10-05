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
 * Unit tests for (some of) mod/quiz/locallib.php.
 *
 * @package    mod_quiz
 * @category   test
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/lib.php');

/**
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class mod_quiz_lib_testcase extends advanced_testcase {
    public function test_quiz_has_grades() {
        $quiz = new stdClass();
        $quiz->grade = '100.0000';
        $quiz->sumgrades = '100.0000';
        $this->assertTrue(quiz_has_grades($quiz));
        $quiz->sumgrades = '0.0000';
        $this->assertFalse(quiz_has_grades($quiz));
        $quiz->grade = '0.0000';
        $this->assertFalse(quiz_has_grades($quiz));
        $quiz->sumgrades = '100.0000';
        $this->assertFalse(quiz_has_grades($quiz));
    }

    public function test_quiz_format_grade() {
        $quiz = new stdClass();
        $quiz->decimalpoints = 2;
        $this->assertEquals(quiz_format_grade($quiz, 0.12345678), format_float(0.12, 2));
        $this->assertEquals(quiz_format_grade($quiz, 0), format_float(0, 2));
        $this->assertEquals(quiz_format_grade($quiz, 1.000000000000), format_float(1, 2));
        $quiz->decimalpoints = 0;
        $this->assertEquals(quiz_format_grade($quiz, 0.12345678), '0');
    }

    public function test_quiz_get_grade_format() {
        $quiz = new stdClass();
        $quiz->decimalpoints = 2;
        $this->assertEquals(quiz_get_grade_format($quiz), 2);
        $this->assertEquals($quiz->questiondecimalpoints, -1);
        $quiz->questiondecimalpoints = 2;
        $this->assertEquals(quiz_get_grade_format($quiz), 2);
        $quiz->decimalpoints = 3;
        $quiz->questiondecimalpoints = -1;
        $this->assertEquals(quiz_get_grade_format($quiz), 3);
        $quiz->questiondecimalpoints = 4;
        $this->assertEquals(quiz_get_grade_format($quiz), 4);
    }

    public function test_quiz_format_question_grade() {
        $quiz = new stdClass();
        $quiz->decimalpoints = 2;
        $quiz->questiondecimalpoints = 2;
        $this->assertEquals(quiz_format_question_grade($quiz, 0.12345678), format_float(0.12, 2));
        $this->assertEquals(quiz_format_question_grade($quiz, 0), format_float(0, 2));
        $this->assertEquals(quiz_format_question_grade($quiz, 1.000000000000), format_float(1, 2));
        $quiz->decimalpoints = 3;
        $quiz->questiondecimalpoints = -1;
        $this->assertEquals(quiz_format_question_grade($quiz, 0.12345678), format_float(0.123, 3));
        $this->assertEquals(quiz_format_question_grade($quiz, 0), format_float(0, 3));
        $this->assertEquals(quiz_format_question_grade($quiz, 1.000000000000), format_float(1, 3));
        $quiz->questiondecimalpoints = 4;
        $this->assertEquals(quiz_format_question_grade($quiz, 0.12345678), format_float(0.1235, 4));
        $this->assertEquals(quiz_format_question_grade($quiz, 0), format_float(0, 4));
        $this->assertEquals(quiz_format_question_grade($quiz, 1.000000000000), format_float(1, 4));
    }

    /**
     * Test deleting a quiz instance.
     */
    public function test_quiz_delete_instance() {
        global $SITE, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Setup a quiz with 1 standard and 1 random question.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(array('course' => $SITE->id, 'questionsperpage' => 3, 'grade' => 100.0));

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $standardq = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));

        quiz_add_quiz_question($standardq->id, $quiz);
        quiz_add_random_questions($quiz, 0, $cat->id, 1, false);

        // Get the random question.
        $randomq = $DB->get_record('question', array('qtype' => 'random'));

        quiz_delete_instance($quiz->id);

        // Check that the random question was deleted.
        $count = $DB->count_records('question', array('id' => $randomq->id));
        $this->assertEquals(0, $count);
        // Check that the standard question was not deleted.
        $count = $DB->count_records('question', array('id' => $standardq->id));
        $this->assertEquals(1, $count);

        // Check that all the slots were removed.
        $count = $DB->count_records('quiz_slots', array('quizid' => $quiz->id));
        $this->assertEquals(0, $count);

        // Check that the quiz was removed.
        $count = $DB->count_records('quiz', array('id' => $quiz->id));
        $this->assertEquals(0, $count);
    }

    /**
     * Test checking the completion state of a quiz.
     */
    public function test_quiz_get_completion_state() {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        $CFG->enablecompletion = true;

        // Create a course and student.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $passstudent = $this->getDataGenerator()->create_user();
        $failstudent = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);

        // Enrol students.
        $this->assertTrue($this->getDataGenerator()->enrol_user($passstudent->id, $course->id, $studentrole->id));
        $this->assertTrue($this->getDataGenerator()->enrol_user($failstudent->id, $course->id, $studentrole->id));

        // Make a scale and an outcome.
        $scale = $this->getDataGenerator()->create_scale();
        $data = array('courseid' => $course->id,
                      'fullname' => 'Team work',
                      'shortname' => 'Team work',
                      'scaleid' => $scale->id);
        $outcome = $this->getDataGenerator()->create_grade_outcome($data);

        // Make a quiz with the outcome on.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $data = array('course' => $course->id,
                      'outcome_'.$outcome->id => 1,
                      'grade' => 100.0,
                      'questionsperpage' => 0,
                      'sumgrades' => 1,
                      'completion' => COMPLETION_TRACKING_AUTOMATIC,
                      'completionpass' => 1);
        $quiz = $quizgenerator->create_instance($data);
        $cm = get_coursemodule_from_id('quiz', $quiz->cmid);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        quiz_add_quiz_question($question->id, $quiz);

        $quizobj = quiz::create($quiz->id, $passstudent->id);

        // Set grade to pass.
        $item = grade_item::fetch(array('courseid' => $course->id, 'itemtype' => 'mod',
                                        'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'outcomeid' => null));
        $item->gradepass = 80;
        $item->update();

        // Start the passing attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $passstudent->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $tosubmit = array(1 => array('answer' => '3.14'));
        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        // Start the failing attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $failstudent->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $tosubmit = array(1 => array('answer' => '0'));
        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        // Check the results.
        $this->assertTrue(quiz_get_completion_state($course, $cm, $passstudent->id, 'return'));
        $this->assertFalse(quiz_get_completion_state($course, $cm, $failstudent->id, 'return'));
    }

    public function test_quiz_get_user_attempts() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $quizgen = $dg->get_plugin_generator('mod_quiz');
        $course = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();
        $role = $DB->get_record('role', ['shortname' => 'student']);

        $dg->enrol_user($u1->id, $course->id, $role->id);
        $dg->enrol_user($u2->id, $course->id, $role->id);
        $dg->enrol_user($u3->id, $course->id, $role->id);
        $dg->enrol_user($u4->id, $course->id, $role->id);

        $quiz1 = $quizgen->create_instance(['course' => $course->id, 'sumgrades' => 2]);
        $quiz2 = $quizgen->create_instance(['course' => $course->id, 'sumgrades' => 2]);

        // Questions.
        $questgen = $dg->get_plugin_generator('core_question');
        $quizcat = $questgen->create_question_category();
        $question = $questgen->create_question('numerical', null, ['category' => $quizcat->id]);
        quiz_add_quiz_question($question->id, $quiz1);
        quiz_add_quiz_question($question->id, $quiz2);

        $quizobj1a = quiz::create($quiz1->id, $u1->id);
        $quizobj1b = quiz::create($quiz1->id, $u2->id);
        $quizobj1c = quiz::create($quiz1->id, $u3->id);
        $quizobj1d = quiz::create($quiz1->id, $u4->id);
        $quizobj2a = quiz::create($quiz2->id, $u1->id);

        // Set attempts.
        $quba1a = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj1a->get_context());
        $quba1a->set_preferred_behaviour($quizobj1a->get_quiz()->preferredbehaviour);
        $quba1b = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj1b->get_context());
        $quba1b->set_preferred_behaviour($quizobj1b->get_quiz()->preferredbehaviour);
        $quba1c = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj1c->get_context());
        $quba1c->set_preferred_behaviour($quizobj1c->get_quiz()->preferredbehaviour);
        $quba1d = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj1d->get_context());
        $quba1d->set_preferred_behaviour($quizobj1d->get_quiz()->preferredbehaviour);
        $quba2a = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj2a->get_context());
        $quba2a->set_preferred_behaviour($quizobj2a->get_quiz()->preferredbehaviour);

        $timenow = time();

        // User 1 passes quiz 1.
        $attempt = quiz_create_attempt($quizobj1a, 1, false, $timenow, false, $u1->id);
        quiz_start_new_attempt($quizobj1a, $quba1a, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj1a, $quba1a, $attempt);
        $attemptobj = quiz_attempt::create($attempt->id);
        $attemptobj->process_submitted_actions($timenow, false, [1 => ['answer' => '3.14']]);
        $attemptobj->process_finish($timenow, false);

        // User 2 goes overdue in quiz 1.
        $attempt = quiz_create_attempt($quizobj1b, 1, false, $timenow, false, $u2->id);
        quiz_start_new_attempt($quizobj1b, $quba1b, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj1b, $quba1b, $attempt);
        $attemptobj = quiz_attempt::create($attempt->id);
        $attemptobj->process_going_overdue($timenow, true);

        // User 3 does not finish quiz 1.
        $attempt = quiz_create_attempt($quizobj1c, 1, false, $timenow, false, $u3->id);
        quiz_start_new_attempt($quizobj1c, $quba1c, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj1c, $quba1c, $attempt);

        // User 4 abandons the quiz 1.
        $attempt = quiz_create_attempt($quizobj1d, 1, false, $timenow, false, $u4->id);
        quiz_start_new_attempt($quizobj1d, $quba1d, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj1d, $quba1d, $attempt);
        $attemptobj = quiz_attempt::create($attempt->id);
        $attemptobj->process_abandon($timenow, true);

        // User 1 attempts the quiz three times (abandon, finish, in progress).
        $quba2a = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj2a->get_context());
        $quba2a->set_preferred_behaviour($quizobj2a->get_quiz()->preferredbehaviour);

        $attempt = quiz_create_attempt($quizobj2a, 1, false, $timenow, false, $u1->id);
        quiz_start_new_attempt($quizobj2a, $quba2a, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj2a, $quba2a, $attempt);
        $attemptobj = quiz_attempt::create($attempt->id);
        $attemptobj->process_abandon($timenow, true);

        $quba2a = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj2a->get_context());
        $quba2a->set_preferred_behaviour($quizobj2a->get_quiz()->preferredbehaviour);

        $attempt = quiz_create_attempt($quizobj2a, 2, false, $timenow, false, $u1->id);
        quiz_start_new_attempt($quizobj2a, $quba2a, $attempt, 2, $timenow);
        quiz_attempt_save_started($quizobj2a, $quba2a, $attempt);
        $attemptobj = quiz_attempt::create($attempt->id);
        $attemptobj->process_finish($timenow, false);

        $quba2a = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj2a->get_context());
        $quba2a->set_preferred_behaviour($quizobj2a->get_quiz()->preferredbehaviour);

        $attempt = quiz_create_attempt($quizobj2a, 3, false, $timenow, false, $u1->id);
        quiz_start_new_attempt($quizobj2a, $quba2a, $attempt, 3, $timenow);
        quiz_attempt_save_started($quizobj2a, $quba2a, $attempt);

        // Check for user 1.
        $attempts = quiz_get_user_attempts($quiz1->id, $u1->id, 'all');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::FINISHED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($quiz1->id, $attempt->quiz);

        $attempts = quiz_get_user_attempts($quiz1->id, $u1->id, 'finished');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::FINISHED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($quiz1->id, $attempt->quiz);

        $attempts = quiz_get_user_attempts($quiz1->id, $u1->id, 'unfinished');
        $this->assertCount(0, $attempts);

        // Check for user 2.
        $attempts = quiz_get_user_attempts($quiz1->id, $u2->id, 'all');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::OVERDUE, $attempt->state);
        $this->assertEquals($u2->id, $attempt->userid);
        $this->assertEquals($quiz1->id, $attempt->quiz);

        $attempts = quiz_get_user_attempts($quiz1->id, $u2->id, 'finished');
        $this->assertCount(0, $attempts);

        $attempts = quiz_get_user_attempts($quiz1->id, $u2->id, 'unfinished');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::OVERDUE, $attempt->state);
        $this->assertEquals($u2->id, $attempt->userid);
        $this->assertEquals($quiz1->id, $attempt->quiz);

        // Check for user 3.
        $attempts = quiz_get_user_attempts($quiz1->id, $u3->id, 'all');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::IN_PROGRESS, $attempt->state);
        $this->assertEquals($u3->id, $attempt->userid);
        $this->assertEquals($quiz1->id, $attempt->quiz);

        $attempts = quiz_get_user_attempts($quiz1->id, $u3->id, 'finished');
        $this->assertCount(0, $attempts);

        $attempts = quiz_get_user_attempts($quiz1->id, $u3->id, 'unfinished');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::IN_PROGRESS, $attempt->state);
        $this->assertEquals($u3->id, $attempt->userid);
        $this->assertEquals($quiz1->id, $attempt->quiz);

        // Check for user 4.
        $attempts = quiz_get_user_attempts($quiz1->id, $u4->id, 'all');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::ABANDONED, $attempt->state);
        $this->assertEquals($u4->id, $attempt->userid);
        $this->assertEquals($quiz1->id, $attempt->quiz);

        $attempts = quiz_get_user_attempts($quiz1->id, $u4->id, 'finished');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::ABANDONED, $attempt->state);
        $this->assertEquals($u4->id, $attempt->userid);
        $this->assertEquals($quiz1->id, $attempt->quiz);

        $attempts = quiz_get_user_attempts($quiz1->id, $u4->id, 'unfinished');
        $this->assertCount(0, $attempts);

        // Multiple attempts for user 1 in quiz 2.
        $attempts = quiz_get_user_attempts($quiz2->id, $u1->id, 'all');
        $this->assertCount(3, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::ABANDONED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($quiz2->id, $attempt->quiz);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::FINISHED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($quiz2->id, $attempt->quiz);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::IN_PROGRESS, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($quiz2->id, $attempt->quiz);

        $attempts = quiz_get_user_attempts($quiz2->id, $u1->id, 'finished');
        $this->assertCount(2, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::ABANDONED, $attempt->state);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::FINISHED, $attempt->state);

        $attempts = quiz_get_user_attempts($quiz2->id, $u1->id, 'unfinished');
        $this->assertCount(1, $attempts);
        $attempt = array_shift($attempts);

        // Multiple quiz attempts fetched at once.
        $attempts = quiz_get_user_attempts([$quiz1->id, $quiz2->id], $u1->id, 'all');
        $this->assertCount(4, $attempts);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::FINISHED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($quiz1->id, $attempt->quiz);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::ABANDONED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($quiz2->id, $attempt->quiz);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::FINISHED, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($quiz2->id, $attempt->quiz);
        $attempt = array_shift($attempts);
        $this->assertEquals(quiz_attempt::IN_PROGRESS, $attempt->state);
        $this->assertEquals($u1->id, $attempt->userid);
        $this->assertEquals($quiz2->id, $attempt->quiz);
    }

    /**
     * Test for quiz_get_group_override_priorities().
     */
    public function test_quiz_get_group_override_priorities() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $quizgen = $dg->get_plugin_generator('mod_quiz');
        $course = $dg->create_course();

        $quiz = $quizgen->create_instance(['course' => $course->id, 'sumgrades' => 2]);

        $this->assertNull(quiz_get_group_override_priorities($quiz->id));

        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        $now = 100;
        $override1 = (object)[
            'quiz' => $quiz->id,
            'groupid' => $group1->id,
            'timeopen' => $now,
            'timeclose' => $now + 20
        ];
        $DB->insert_record('quiz_overrides', $override1);

        $override2 = (object)[
            'quiz' => $quiz->id,
            'groupid' => $group2->id,
            'timeopen' => $now - 10,
            'timeclose' => $now + 10
        ];
        $DB->insert_record('quiz_overrides', $override2);

        $priorities = quiz_get_group_override_priorities($quiz->id);
        $this->assertNotEmpty($priorities);

        $openpriorities = $priorities['open'];
        // Override 2's time open has higher priority since it is sooner than override 1's.
        $this->assertEquals(2, $openpriorities[$override1->timeopen]);
        $this->assertEquals(1, $openpriorities[$override2->timeopen]);

        $closepriorities = $priorities['close'];
        // Override 1's time close has higher priority since it is later than override 2's.
        $this->assertEquals(1, $closepriorities[$override1->timeclose]);
        $this->assertEquals(2, $closepriorities[$override2->timeclose]);
    }

    public function test_quiz_core_calendar_provide_event_action_open() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a quiz.
        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id,
            'timeopen' => time() - DAYSECS, 'timeclose' => time() + DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $quiz->id, QUIZ_EVENT_TYPE_OPEN);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_quiz_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('attemptquiznow', 'quiz'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_quiz_core_calendar_provide_event_action_closed() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a quiz.
        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id,
            'timeclose' => time() - DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $quiz->id, QUIZ_EVENT_TYPE_CLOSE);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Confirm the result was null.
        $this->assertNull(mod_quiz_core_calendar_provide_event_action($event, $factory));
    }

    public function test_quiz_core_calendar_provide_event_action_open_in_future() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a quiz.
        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id,
            'timeopen' => time() + DAYSECS));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $quiz->id, QUIZ_EVENT_TYPE_CLOSE);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_quiz_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('attemptquiznow', 'quiz'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertFalse($actionevent->is_actionable());
    }

    public function test_quiz_core_calendar_provide_event_action_no_capability() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a student.
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Enrol student.
        $this->assertTrue($this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id));

        // Create a quiz.
        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id));

        // Remove the permission to attempt or review the quiz for the student role.
        $coursecontext = context_course::instance($course->id);
        assign_capability('mod/quiz:reviewmyattempts', CAP_PROHIBIT, $studentrole->id, $coursecontext);
        assign_capability('mod/quiz:attempt', CAP_PROHIBIT, $studentrole->id, $coursecontext);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $quiz->id, QUIZ_EVENT_TYPE_OPEN);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Set current user to the student.
        $this->setUser($student);

        // Confirm null is returned.
        $this->assertNull(mod_quiz_core_calendar_provide_event_action($event, $factory));
    }

    public function test_quiz_core_calendar_provide_event_action_already_finished() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a student.
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Enrol student.
        $this->assertTrue($this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id));

        // Create a quiz.
        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id,
            'sumgrades' => 1));

        // Add a question to the quiz.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, array('category' => $cat->id));
        quiz_add_quiz_question($question->id, $quiz);

        // Get the quiz object.
        $quizobj = quiz::create($quiz->id, $student->id);

        // Create an attempt for the student in the quiz.
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $student->id);
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Finish the attempt.
        $attemptobj = quiz_attempt::create($attempt->id);
        $attemptobj->process_finish($timenow, false);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $quiz->id, QUIZ_EVENT_TYPE_OPEN);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Set current user to the student.
        $this->setUser($student);

        // Confirm null is returned.
        $this->assertNull(mod_quiz_core_calendar_provide_event_action($event, $factory));
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid
     * @param int $instanceid The quiz id.
     * @param string $eventtype The event type. eg. QUIZ_EVENT_TYPE_OPEN.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype) {
        $event = new stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = 'quiz';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return calendar_event::create($event);
    }

    /**
     * Test the callback responsible for returning the completion rule descriptions.
     * This function should work given either an instance of the module (cm_info), such as when checking the active rules,
     * or if passed a stdClass of similar structure, such as when checking the the default completion settings for a mod type.
     */
    public function test_mod_quiz_completion_get_active_rule_descriptions() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Two activities, both with automatic completion. One has the 'completionsubmit' rule, one doesn't.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 2]);
        $quiz1 = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'completion' => 2,
            'completionattemptsexhausted' => 1,
            'completionpass' => 1
        ]);
        $quiz2 = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'completion' => 2,
            'completionattemptsexhausted' => 0,
            'completionpass' => 0
        ]);
        $cm1 = cm_info::create(get_coursemodule_from_instance('quiz', $quiz1->id));
        $cm2 = cm_info::create(get_coursemodule_from_instance('quiz', $quiz2->id));

        // Data for the stdClass input type.
        // This type of input would occur when checking the default completion rules for an activity type, where we don't have
        // any access to cm_info, rather the input is a stdClass containing completion and customdata attributes, just like cm_info.
        $moddefaults = new stdClass();
        $moddefaults->customdata = ['customcompletionrules' => [
            'completionattemptsexhausted' => 1,
            'completionpass' => 1
        ]];
        $moddefaults->completion = 2;

        $activeruledescriptions = [
            get_string('completionattemptsexhausteddesc', 'quiz'),
            get_string('completionpassdesc', 'quiz'),
        ];
        $this->assertEquals(mod_quiz_get_completion_active_rule_descriptions($cm1), $activeruledescriptions);
        $this->assertEquals(mod_quiz_get_completion_active_rule_descriptions($cm2), []);
        $this->assertEquals(mod_quiz_get_completion_active_rule_descriptions($moddefaults), $activeruledescriptions);
        $this->assertEquals(mod_quiz_get_completion_active_rule_descriptions(new stdClass()), []);
    }

    /**
     * You can't create a quiz module event when the module doesn't exist.
     */
    public function test_mod_quiz_core_calendar_validate_event_timestart_no_activity() {
        global $CFG;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => 1234,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN,
            'timestart' => time(),
            'timeduration' => 86400,
            'visible' => 1
        ]);

        $this->expectException('moodle_exception');
        mod_quiz_core_calendar_validate_event_timestart($event);
    }

    /**
     * A QUIZ_EVENT_TYPE_OPEN must be before the close time of the quiz activity.
     */
    public function test_mod_quiz_core_calendar_validate_event_timestart_valid_open_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;
        $DB->update_record('quiz', $quiz);

        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN,
            'timestart' => $timeopen,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        mod_quiz_core_calendar_validate_event_timestart($event);
        // The function above will throw an exception if the event is
        // invalid.
        $this->assertTrue(true);
    }

    /**
     * A QUIZ_EVENT_TYPE_OPEN can not have a start time set after the close time
     * of the quiz activity.
     */
    public function test_mod_quiz_core_calendar_validate_event_timestart_invalid_open_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;
        $DB->update_record('quiz', $quiz);

        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN,
            'timestart' => $timeclose + 1,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        $this->expectException('moodle_exception');
        mod_quiz_core_calendar_validate_event_timestart($event);
    }

    /**
     * A QUIZ_EVENT_TYPE_CLOSE must be after the open time of the quiz activity.
     */
    public function test_mod_quiz_core_calendar_validate_event_timestart_valid_close_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;
        $DB->update_record('quiz', $quiz);

        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_CLOSE,
            'timestart' => $timeclose,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        mod_quiz_core_calendar_validate_event_timestart($event);
        // The function above will throw an exception if the event isn't
        // valid.
        $this->assertTrue(true);
    }

    /**
     * A QUIZ_EVENT_TYPE_CLOSE can not have a start time set before the open time
     * of the quiz activity.
     */
    public function test_mod_quiz_core_calendar_validate_event_timestart_invalid_close_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;
        $DB->update_record('quiz', $quiz);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_CLOSE,
            'timestart' => $timeopen - 1,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        $this->expectException('moodle_exception');
        mod_quiz_core_calendar_validate_event_timestart($event);
    }

    /**
     * An unkown event type should not change the quiz instance.
     */
    public function test_mod_quiz_core_calendar_event_timestart_updated_unknown_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;
        $DB->update_record('quiz', $quiz);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN . "SOMETHING ELSE",
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        mod_quiz_core_calendar_event_timestart_updated($event);

        $quiz = $DB->get_record('quiz', ['id' => $quiz->id]);
        $this->assertEquals($timeopen, $quiz->timeopen);
        $this->assertEquals($timeclose, $quiz->timeclose);
    }

    /**
     * A QUIZ_EVENT_TYPE_OPEN event should update the timeopen property of
     * the quiz activity.
     */
    public function test_mod_quiz_core_calendar_event_timestart_updated_open_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $timemodified = 1;
        $newtimeopen = $timeopen - DAYSECS;
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;
        $quiz->timemodified = $timemodified;
        $DB->update_record('quiz', $quiz);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN,
            'timestart' => $newtimeopen,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        mod_quiz_core_calendar_event_timestart_updated($event);

        $quiz = $DB->get_record('quiz', ['id' => $quiz->id]);
        // Ensure the timeopen property matches the event timestart.
        $this->assertEquals($newtimeopen, $quiz->timeopen);
        // Ensure the timeclose isn't changed.
        $this->assertEquals($timeclose, $quiz->timeclose);
        // Ensure the timemodified property has been changed.
        $this->assertNotEquals($timemodified, $quiz->timemodified);
    }

    /**
     * A QUIZ_EVENT_TYPE_CLOSE event should update the timeclose property of
     * the quiz activity.
     */
    public function test_mod_quiz_core_calendar_event_timestart_updated_close_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $timemodified = 1;
        $newtimeclose = $timeclose + DAYSECS;
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;
        $quiz->timemodified = $timemodified;
        $DB->update_record('quiz', $quiz);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_CLOSE,
            'timestart' => $newtimeclose,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        mod_quiz_core_calendar_event_timestart_updated($event);

        $quiz = $DB->get_record('quiz', ['id' => $quiz->id]);
        // Ensure the timeclose property matches the event timestart.
        $this->assertEquals($newtimeclose, $quiz->timeclose);
        // Ensure the timeopen isn't changed.
        $this->assertEquals($timeopen, $quiz->timeopen);
        // Ensure the timemodified property has been changed.
        $this->assertNotEquals($timemodified, $quiz->timemodified);
    }

    /**
     * A QUIZ_EVENT_TYPE_OPEN event should not update the timeopen property of
     * the quiz activity if it's an override.
     */
    public function test_mod_quiz_core_calendar_event_timestart_updated_open_event_override() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $timemodified = 1;
        $newtimeopen = $timeopen - DAYSECS;
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;
        $quiz->timemodified = $timemodified;
        $DB->update_record('quiz', $quiz);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => $user->id,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN,
            'timestart' => $newtimeopen,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        $record = (object) [
            'quiz' => $quiz->id,
            'userid' => $user->id
        ];

        $DB->insert_record('quiz_overrides', $record);

        mod_quiz_core_calendar_event_timestart_updated($event);

        $quiz = $DB->get_record('quiz', ['id' => $quiz->id]);
        // Ensure the timeopen property doesn't change.
        $this->assertEquals($timeopen, $quiz->timeopen);
        // Ensure the timeclose isn't changed.
        $this->assertEquals($timeclose, $quiz->timeclose);
        // Ensure the timemodified property has not been changed.
        $this->assertEquals($timemodified, $quiz->timemodified);
    }

    /**
     * If a student somehow finds a way to update the quiz calendar event
     * then the callback should not be executed to update the quiz activity
     * dates as well otherwise that would be a security issue.
     */
    public function test_student_role_cant_update_quiz_activity() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $context = context_course::instance($course->id);
        $roleid = $generator->create_role();
        $now = time();
        $timeopen = (new DateTime())->setTimestamp($now);
        $newtimeopen = (new DateTime())->setTimestamp($now)->modify('+1 day');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen->getTimestamp();
        $DB->update_record('quiz', $quiz);

        $generator->enrol_user($user->id, $course->id, 'student');
        $generator->role_assign($roleid, $user->id, $context->id);

        $event = \calendar_event::create([
            'name' => 'test event',
            'courseid' => $course->id,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN,
            'timestart' => $timeopen->getTimestamp()
        ]);

        assign_capability('moodle/course:manageactivities', CAP_PROHIBIT, $roleid, $context, true);

        $this->setUser($user);

        mod_quiz_core_calendar_event_timestart_updated($event);

        $newquiz = $DB->get_record('quiz', ['id' => $quiz->id]);
        // The time open shouldn't have changed even though we updated the calendar
        // event.
        $this->assertEquals($timeopen->getTimestamp(), $newquiz->timeopen);
    }

    /**
     * A teacher with the capability to modify a quiz module should be
     * able to update the quiz activity dates by changing the calendar
     * event.
     */
    public function test_teacher_role_can_update_quiz_activity() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $context = context_course::instance($course->id);
        $roleid = $generator->create_role();
        $now = time();
        $timeopen = (new DateTime())->setTimestamp($now);
        $newtimeopen = (new DateTime())->setTimestamp($now)->modify('+1 day');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen->getTimestamp();
        $DB->update_record('quiz', $quiz);

        $generator->enrol_user($user->id, $course->id, 'teacher');
        $generator->role_assign($roleid, $user->id, $context->id);

        $event = \calendar_event::create([
            'name' => 'test event',
            'courseid' => $course->id,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN,
            'timestart' => $newtimeopen->getTimestamp()
        ]);

        assign_capability('moodle/course:manageactivities', CAP_ALLOW, $roleid, $context, true);

        $this->setUser($user);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        mod_quiz_core_calendar_event_timestart_updated($event);

        $triggeredevents = $sink->get_events();
        $moduleupdatedevents = array_filter($triggeredevents, function($e) {
            return is_a($e, 'core\event\course_module_updated');
        });

        $newquiz = $DB->get_record('quiz', ['id' => $quiz->id]);
        // The should be updated along with the event because the user has sufficient
        // capabilities.
        $this->assertEquals($newtimeopen->getTimestamp(), $newquiz->timeopen);
        // Confirm that a module updated event is fired when the module
        // is changed.
        $this->assertNotEmpty($moduleupdatedevents);
    }


    /**
     * An unkown event type should not have any limits
     */
    public function test_mod_quiz_core_calendar_get_valid_event_timestart_range_unknown_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $quiz = new \stdClass();
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => 1,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN . "SOMETHING ELSE",
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        list ($min, $max) = mod_quiz_core_calendar_get_valid_event_timestart_range($event, $quiz);
        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * The open event should be limited by the quiz's timeclose property, if it's set.
     */
    public function test_mod_quiz_core_calendar_get_valid_event_timestart_range_open_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $quiz = new \stdClass();
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => 1,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN,
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        // The max limit should be bounded by the timeclose value.
        list ($min, $max) = mod_quiz_core_calendar_get_valid_event_timestart_range($event, $quiz);

        $this->assertNull($min);
        $this->assertEquals($timeclose, $max[0]);

        // No timeclose value should result in no upper limit.
        $quiz->timeclose = 0;
        list ($min, $max) = mod_quiz_core_calendar_get_valid_event_timestart_range($event, $quiz);

        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * An override event should not have any limits.
     */
    public function test_mod_quiz_core_calendar_get_valid_event_timestart_range_override_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $user = $generator->create_user();
        $course = $generator->create_course();
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;
        $DB->update_record('quiz', $quiz);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => $user->id,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_OPEN,
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        $record = (object) [
            'quiz' => $quiz->id,
            'userid' => $user->id
        ];

        $DB->insert_record('quiz_overrides', $record);

        list ($min, $max) = mod_quiz_core_calendar_get_valid_event_timestart_range($event, $quiz);

        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * The close event should be limited by the quiz's timeopen property, if it's set.
     */
    public function test_mod_quiz_core_calendar_get_valid_event_timestart_range_close_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $quiz = new \stdClass();
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'quiz',
            'instance' => 1,
            'eventtype' => QUIZ_EVENT_TYPE_CLOSE,
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1
        ]);

        // The max limit should be bounded by the timeclose value.
        list ($min, $max) = mod_quiz_core_calendar_get_valid_event_timestart_range($event, $quiz);

        $this->assertEquals($timeopen, $min[0]);
        $this->assertNull($max);

        // No timeclose value should result in no upper limit.
        $quiz->timeopen = 0;
        list ($min, $max) = mod_quiz_core_calendar_get_valid_event_timestart_range($event, $quiz);

        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * Return false when there are not overrides for this quiz instance.
     */
    public function test_quiz_is_override_calendar_event_no_override() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);

        $event = new \calendar_event((object)[
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'userid' => $user->id
        ]);

        $this->assertFalse(quiz_is_override_calendar_event($event));
    }

    /**
     * Return false if the given event isn't an quiz module event.
     */
    public function test_quiz_is_override_calendar_event_no_module_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);

        $event = new \calendar_event((object)[
            'userid' => $user->id
        ]);

        $this->assertFalse(quiz_is_override_calendar_event($event));
    }

    /**
     * Return false if there is overrides for this use but they belong to another quiz
     * instance.
     */
    public function test_quiz_is_override_calendar_event_different_quiz_instance() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz2 = $quizgenerator->create_instance(['course' => $course->id]);

        $event = new \calendar_event((object) [
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'userid' => $user->id
        ]);

        $record = (object) [
            'quiz' => $quiz2->id,
            'userid' => $user->id
        ];

        $DB->insert_record('quiz_overrides', $record);

        $this->assertFalse(quiz_is_override_calendar_event($event));
    }

    /**
     * Return true if there is a user override for this event and quiz instance.
     */
    public function test_quiz_is_override_calendar_event_user_override() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);

        $event = new \calendar_event((object) [
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'userid' => $user->id
        ]);

        $record = (object) [
            'quiz' => $quiz->id,
            'userid' => $user->id
        ];

        $DB->insert_record('quiz_overrides', $record);

        $this->assertTrue(quiz_is_override_calendar_event($event));
    }

    /**
     * Return true if there is a group override for the event and quiz instance.
     */
    public function test_quiz_is_override_calendar_event_group_override() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $group = $this->getDataGenerator()->create_group(array('courseid' => $quiz->course));
        $groupid = $group->id;
        $userid = $user->id;

        $event = new \calendar_event((object) [
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'groupid' => $groupid
        ]);

        $record = (object) [
            'quiz' => $quiz->id,
            'groupid' => $groupid
        ];

        $DB->insert_record('quiz_overrides', $record);

        $this->assertTrue(quiz_is_override_calendar_event($event));
    }

    /**
     * When the close date event is changed and it results in the time close value of
     * the quiz being updated then the open quiz attempts should also be updated.
     */
    public function test_core_calendar_event_timestart_updated_update_quiz_attempt() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $teacher = $generator->create_user();
        $student = $generator->create_user();
        $course = $generator->create_course();
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $context = context_course::instance($course->id);
        $roleid = $generator->create_role();
        $now = time();
        $timelimit = 600;
        $timeopen = (new DateTime())->setTimestamp($now);
        $timeclose = (new DateTime())->setTimestamp($now)->modify('+1 day');
        // The new close time being earlier than the time open + time limit should
        // result in an update to the quiz attempts.
        $newtimeclose = $timeopen->getTimestamp() + $timelimit - 10;
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);
        $quiz->timeopen = $timeopen->getTimestamp();
        $quiz->timeclose = $timeclose->getTimestamp();
        $quiz->timelimit = $timelimit;
        $DB->update_record('quiz', $quiz);

        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'teacher');
        $generator->role_assign($roleid, $teacher->id, $context->id);

        $event = \calendar_event::create([
            'name' => 'test event',
            'courseid' => $course->id,
            'modulename' => 'quiz',
            'instance' => $quiz->id,
            'eventtype' => QUIZ_EVENT_TYPE_CLOSE,
            'timestart' => $newtimeclose
        ]);

        assign_capability('moodle/course:manageactivities', CAP_ALLOW, $roleid, $context, true);

        $attemptid = $DB->insert_record(
            'quiz_attempts',
            [
                'quiz' => $quiz->id,
                'userid' => $student->id,
                'state' => 'inprogress',
                'timestart' => $timeopen->getTimestamp(),
                'timecheckstate' => 0,
                'layout' => '',
                'uniqueid' => 1
            ]
        );

        $this->setUser($teacher);

        mod_quiz_core_calendar_event_timestart_updated($event);

        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid]);
        // When the close date is changed so that it's earlier than the time open
        // plus the time limit of the quiz then the attempt's timecheckstate should
        // be updated to the new time close date of the quiz.
        $this->assertEquals($newtimeclose, $attempt->timecheckstate);
    }
}
