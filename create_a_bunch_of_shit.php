<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/mod/chat/lib.php');

class assign_2 extends assign {
    public function update_submission() {
        return parent::update_submission(...func_get_args());
    }
}

set_config('enablecompletion', COMPLETION_ENABLED);

// Admin user biaaaatch.
$USER = get_admin();
$ONEDAY = 60 * 60 * 24;
$ONEMONTH = $ONEDAY * 30;
$numberofteachers = 10;
$numberofstudents = 100;
$numberofcourses = 10;
$numberofassignments = 10;
$numberofchats = 10;
$numberofforums = 10;
$groupids = [];
$courses = [];
$students = [];
$assignments = [];
$chats = [];
$forums = [];
$now = time();
$generator = \phpunit_util::get_data_generator();
$coursecategory = array_shift(array_values($DB->get_records('course_categories')));

for ($i = 0; $i < $numberofcourses; $i++) {
    printf("Creating %d of %d courses\n", $i + 1, $numberofcourses);
    $data = [
        'category' => $coursecategory->id,
        'fullname' => sprintf('Course %d', $i),
        'shortname' => sprintf('C%d', $i),
        'enablecompletion' => COMPLETION_ENABLED,
    ];

    if ($i < ($numberofcourses / 4)) {
        $data['startdate'] = $now - $ONEMONTH;
        $data['enddate'] = $now - $ONEDAY;
    } else if ($i < ($numberofcourses / 2)) {
        $data['startdate'] = $now + $ONEMONTH;
    } else {
        $data['startdate'] = $now;
    }

    $courses[] = $generator->create_course($data);
}

for ($i = 1; $i <= $numberofstudents; $i++) {
    printf("Creating %d of %d students\n", $i, $numberofstudents);
    $record = [
        'username' => sprintf('s%d', $i),
        'password' => 'test'
    ];

    $students[] = $generator->create_user($record);
}

for ($i = 1; $i <= $numberofteachers; $i++) {
    printf("Creating %d of %d teachers\n", $i, $numberofteachers);
    $record = [
        'username' => sprintf('t%d', $i),
        'password' => 'test'
    ];

    $teachers[] = $generator->create_user($record);
}

$enrolplugin = enrol_get_plugin('manual');
$studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
$teacherrole = $DB->get_record('role', array('shortname' => 'teacher'), '*', MUST_EXIST);
foreach ($courses as $course) {
     $instances = enrol_get_instances($course->id, true);

     foreach ($instances as $instance) {
        if ($instance->enrol = 'manual') {
            foreach ($students as $student) {
                printf("Enrolling student %s in course %s\n", $student->username, $course->shortname);
                $enrolplugin->enrol_user($instance, $student->id, $studentrole->id);
            }

            foreach ($teachers as $teacher) {
                printf("Enrolling teacher %s in course %s\n", $teacher->username, $course->shortname);
                $enrolplugin->enrol_user($instance, $teacher->id, $teacherrole->id);
            }

            break;
        }
     }
}

for ($i = 0; $i < ($numberofcourses / 4); $i++) {
    printf("Creating %d of %d groups\n", $i+1, ($numberofcourses / 4));
    $groupname = sprintf('g%d', $i);
    $groupid = groups_create_group((object) [
        'courseid' => $courses[$i]->id,
        'name' => $groupname,
    ]);

    if (count($students) > (($i * 3) + 3)) {
        for ($j = $i * 3; $j < (($i * 3) + 3); $j++) {
            $student = $students[$j];
            printf("Adding user %s to group %s\n", $student->username, $groupname);
            groups_add_member($groupid, $student->id);
        }
    }

    $groupids[] = $groupid;
}

$assigngenerator = $generator->get_plugin_generator('mod_assign');
$numberofgroups = count($groupids);
for ($i = 0; $i < $numberofcourses; $i++) {
    $course = $courses[$i];
    $groupsoverridden = 0;
    $usersoverridden = 0;

    for ($j = 0; $j < $numberofassignments; $j++) {
        printf("Creating assignment %d of %d in course %s\n", $j+1, $numberofassignments, $course->shortname);
        $record = [
            'name' => sprintf('Assignment %d %d', $course->id, $j),
            'course' => $course,
            'assignsubmission_onlinetext_enabled' => 1,
        ];

        if (($j + 1) % 10 != 0) {
            $record['duedate'] = $course->startdate + ($ONEDAY * 7);
            $record['gradingduedate'] = $course->startdate + ($ONEDAY * 14);
        }

        $instance = $assigngenerator->create_instance((object) $record);
        $assignment = new assign_2(context_module::instance($instance->cmid), null, null);

        if ($numberofgroups && $groupsoverridden < ($numberofassignments / 4)) {
            $count = 0;
            $maxoverrides = ceil($numberofgroups / 4);
            $maxoverrides = $maxoverrides > 40 ? 40 : $maxoverrides;

            foreach ($groupids as $groupid) {
                if ($count >= $maxoverrides) {
                    break;
                }

                printf("Creating group override %s (%d of %d)\n", sprintf("g%d", $groupid), $count+1, $maxoverrides);

                $DB->insert_record('assign_overrides', (object) [
                    'assignid' => $instance->id,
                    'sortorder' => $count + 1,
                    'groupid' => $groupid,
                    'duedate' => $course->startdate + ($ONEDAY * 8),
                ]);

                $count++;
            }

            $groupsoverridden++;
        }

        if ($usersoverridden < ($numberofassignments / 4) && $groupsoverridden > ($numberofgroups / 2)) {
            $count = 0;
            $maxoverrides = ceil(count($students) / 4);
            $maxoverrides = $maxoverrides > 40 ? 40 : $maxoverrides;

            foreach ($students as $student) {
                if ($count >= $maxoverrides) {
                    break;
                }

                printf("Creating user override %s (%d of %d)\n", $student->username, $count+1, $maxoverrides);

                $DB->insert_record('assign_overrides', (object) [
                    'assignid' => $instance->id,
                    'userid' => $student->id,
                    'duedate' => $course->startdate + ($ONEDAY * 8),
                ]);

                $count++;
            }

            $usersoverriden++;
        }

        assign_update_events($assignment);
        $assignment->update_calendar($instance->cmid);

        $plugin = $assignment->get_submission_plugin_by_type('onlinetext');
        for ($k = 0; $k < $numberofstudents; $k += 10) {
            $student = $students[$k];
            printf("Creating submission for student %s\n", $student->username);
            $submission = $assignment->get_user_submission($student->id, true);
            $data = (object) [
                'onlinetext_editor' => [
                    'itemid' => file_get_unused_draft_itemid(),
                    'text' => 'Submission text',
                    'format' => FORMAT_HTML,
                ]
            ];

            $plugin->save($submission, $data);
            $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
            $assignment->update_submission($submission, $student->id, true, false);
        }
    }
}

//print("Refreshing assignment events\n");
//assign_refresh_events();

$chatgenerator = $generator->get_plugin_generator('mod_chat');
foreach ($courses as $course) {
    for ($i = 0; $i < $numberofchats; $i++) {
        printf("Creating chat %d of %d in course %s\n", $i+1, $numberofchats, $course->shortname);
        if ($i < ($numberofchats / 4)) {
            $chattime = $course->startdate + $ONEMONTH;
        } else {
            $chattime = $course->startdate + (60 * 60);
        }

        $chats[] = $chatgenerator->create_instance([
            'name' => sprintf('Chat %d %d', $course->id, $j),
            'course' => $course,
            'chattime' => $chattime,
            'schedule' => 1,
        ]);
    }
}

print("Refreshing chat events\n");
chat_refresh_events();

$forumgenerator = $generator->get_plugin_generator('mod_forum');
foreach ($courses as $course) {
    // Mark the activity as completed.
    $completion = new completion_info($course);

    for ($i = 0; $i < $numberofforums; $i++) {
        printf("Creating forum %d of %d in course %s\n", $i+1, $numberofforums, $course->shortname);
        if ($i < ($numberofforums / 4)) {
            $completionexpected = $course->startdate + $ONEMONTH;
        } else {
            $completionexpected = $course->startdate + $ONEDAY;
        }

        $forum = $forumgenerator->create_instance([
            'name' => sprintf('Forum %d %d', $course->id, $j),
            'course' => $course,
            'completion' => 2,
            'completionview' => 1,
            'completionexpected' => $completionexpected,
        ]);

        if ($i < ($numberofforums / 4)) {
            print("Marking this forum as viewed to satisfy completion\n");
            $cm = get_coursemodule_from_instance('forum', $forum->id);
            $completion->set_module_viewed($cm);
        }

        $forums[] = $forum;
    }
}
