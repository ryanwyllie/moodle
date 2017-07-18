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
 * The mform for creating and editing a calendar event
 *
 * @copyright 2009 Sam Hemelryk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package calendar
 */

 /**
  * Always include formslib
  */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * The mform class for creating and editing a calendar
 *
 * @copyright 2009 Sam Hemelryk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class new_event_form extends moodleform {
    /**
     * The form definition
     */
    public function definition () {
        global $USER, $PAGE;

        $mform = $this->_form;
        $haserror = !empty($this->_customdata['haserror']);
        $isnewevent = (empty($this->_customdata['event']) || empty($this->_customdata['event']->id));
        $eventtypes = $this->_customdata['types'];

        if ($isnewevent) {
            $repeatedevents = false;
            $hasduration = false;
        } else {
            $event = $this->_customdata['event'];
            $repeatedevents = $event->eventrepeats > 0;
            $hasduration = $event->timeduration > 0;
        }

        $mform->setDisableShortforms();
        $mform->disable_form_change_checker();

        // Empty string so that the element doesn't get rendered.
        $mform->addElement('header', 'general', '');

        // Add some hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', $USER->id);

        $mform->addElement('hidden', 'modulename');
        $mform->setType('modulename', PARAM_INT);
        $mform->setDefault('modulename', '');

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', 0);

        if (!$isnewevent) {
            $event = $this->_customdata['event'];
            $mform->addElement('hidden', 'eventid');
            $mform->setType('eventid', PARAM_INT);
            $mform->setDefault('eventid', $event->id);
        }

        // Normal fields
        $mform->addElement('text', 'name', get_string('eventname','calendar'), 'size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('date_time_selector', 'timestart', get_string('date'));

        if ($isnewevent && $eventtypes) {
            $options = [];

            if (isset($eventtypes['user'])) {
                $options['user'] = get_string('user');
            }
            if (isset($eventtypes['group'])) {
                $options['group'] = get_string('group');
            }
            if (isset($eventtypes['course'])) {
                $options['course'] = get_string('course');
            }
            if (isset($eventtypes['site'])) {
                $options['site'] = get_string('site');
            }

            if (count(array_keys($eventtypes)) == 1 && isset($eventtypes['user'])) {
                $mform->addElement('hidden', 'eventtype');
                $mform->setType('eventtype', PARAM_TEXT);
                $mform->setDefault('eventtype', 'user');
            } else {
                $mform->addElement('select', 'eventtype', get_string('eventkind', 'calendar'), $options);
            }

            if (isset($eventtypes['course'])) {
                $courseoptions = [];
                foreach ($eventtypes['course'] as $course) {
                    $courseoptions[$course->id] = format_string($course->fullname, true,
                        ['context' => context_course::instance($course->id)]);
                }

                $mform->addElement('select', 'courseid', get_string('course'), $courseoptions);
                $mform->disabledIf('courseid', 'eventtype', 'noteq', 'course');
            }

            if (isset($eventtypes['group'])) {
                $courseoptions = [];
                foreach ($eventtypes['groupcourses'] as $course) {
                    $courseoptions[$course->id] = format_string($course->fullname, true,
                        ['context' => context_course::instance($course->id)]);
                }

                $mform->addElement('select', 'groupcourseid', get_string('course'), $courseoptions);
                $mform->disabledIf('groupcourseid', 'eventtype', 'noteq', 'group');

                $groupoptions = [];
                foreach ($eventtypes['group'] as $group) {
                    $index = "{$group->courseid}-{$group->id}";
                    $groupoptions[$index] = format_string($group->name, true,
                        ['context' => context_course::instance($group->courseid)]);
                }

                $mform->addElement('select', 'groupid', get_string('group'), $groupoptions);
                $mform->disabledIf('groupid', 'eventtype', 'noteq', 'group');
            }
        }

        $mform->addElement('editor', 'description', get_string('eventdescription','calendar'), ['rows' => 3]);
        $mform->setType('description', PARAM_RAW);
        $mform->setAdvanced('description');

        $group = [];
        $group[] =& $mform->createElement('radio', 'duration', null, get_string('durationnone', 'calendar'), 0);
        $group[] =& $mform->createElement('radio', 'duration', null, get_string('durationuntil', 'calendar'), 1);
        $group[] =& $mform->createElement('date_time_selector', 'timedurationuntil', '');
        $group[] =& $mform->createElement('radio', 'duration', null, get_string('durationminutes', 'calendar'), 2);
        $group[] =& $mform->createElement('text', 'timedurationminutes', get_string('durationminutes', 'calendar'));

        $mform->addGroup($group, 'durationgroup', get_string('eventduration', 'calendar'), '<br />', false);
        $mform->setAdvanced('durationgroup');

        $mform->disabledIf('timedurationuntil',         'duration', 'noteq', 1);
        $mform->disabledIf('timedurationuntil[day]',    'duration', 'noteq', 1);
        $mform->disabledIf('timedurationuntil[month]',  'duration', 'noteq', 1);
        $mform->disabledIf('timedurationuntil[year]',   'duration', 'noteq', 1);
        $mform->disabledIf('timedurationuntil[hour]',   'duration', 'noteq', 1);
        $mform->disabledIf('timedurationuntil[minute]', 'duration', 'noteq', 1);

        $mform->setType('timedurationminutes', PARAM_INT);
        $mform->disabledIf('timedurationminutes','duration','noteq', 2);

        $mform->setDefault('duration', ($hasduration) ? 1 : 0);

        if ($isnewevent) {
            $mform->addElement('checkbox', 'repeat', get_string('repeatevent', 'calendar'), null);
            $mform->addElement('text', 'repeats', get_string('repeatweeksl', 'calendar'), 'maxlength="10" size="10"');
            $mform->setType('repeats', PARAM_INT);
            $mform->setDefault('repeats', 1);
            $mform->disabledIf('repeats','repeat','notchecked');
            $mform->setAdvanced('repeat');
            $mform->setAdvanced('repeats');
        } else if ($repeatedevents) {
            $mform->addElement('hidden', 'repeatid');
            $mform->setType('repeatid', PARAM_INT);

            $mform->addElement('radio', 'repeateditall', null, get_string('repeateditall', 'calendar', $this->_customdata->event->eventrepeats), 1);
            $mform->addElement('radio', 'repeateditall', null, get_string('repeateditthis', 'calendar'), 0);

            $mform->setDefault('repeateditall', 1);
            $mform->setAdvanced('repeateditall');
        }

        $PAGE->requires->js_call_amd('core_calendar/event_form', 'init', [$mform->getAttribute('id'), $haserror]);
    }

    /**
     * A bit of custom validation for this form
     *
     * @param array $data An assoc array of field=>value
     * @param array $files An array of files
     * @return array
     */
    public function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        $coursekey = isset($data['groupcourseid']) ? 'groupcourseid' : 'courseid';

        if (isset($data[$coursekey]) && $data[$coursekey] > 0) {
            if ($course = $DB->get_record('course', ['id' => $data[$coursekey]])) {
                if ($data['timestart'] < $course->startdate) {
                    $errors['timestart'] = get_string('errorbeforecoursestart', 'calendar');
                }
            } else {
                $errors[$coursekey] = get_string('invalidcourse', 'error');
            }
        }

        if ($data['duration'] == 1 && $data['timestart'] > $data['timedurationuntil']) {
            $errors['durationgroup'] = get_string('invalidtimedurationuntil', 'calendar');
        } else if ($data['duration'] == 2 && (trim($data['timedurationminutes']) == '' || $data['timedurationminutes'] < 1)) {
            $errors['durationgroup'] = get_string('invalidtimedurationminutes', 'calendar');
        }

        return $errors;
    }

    public function get_data() {
        $data = parent::get_data();

        if ($data) {
            // Undo the form definition work around to allow us to have two different
            // course selectors present depending on which event type the user selects.
            if (isset($data->groupcourseid)) {
                $data->courseid = $data->groupcourseid;
                unset($data->groupcourseid);
            }

            if ($data->duration == 1) {
                $data->timeduration = $data->timedurationuntil- $data->timestart;
            } else if ($data->duration == 2) {
                $data->timeduration = $data->timedurationminutes * MINSECS;
            } else {
                $data->timeduration = 0;
            }
        }

        return $data;
    }
}
