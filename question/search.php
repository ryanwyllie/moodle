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

require_once('../config.php');

require_login();

$PAGE->set_url(new moodle_url('/tag/search.php', []));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');

$PAGE->set_title('Question search');
$PAGE->set_heading($SITE->fullname);

$courses = enrol_get_my_courses();
$contexts = array_map(function($course) {
    return \context_course::instance($course->id);
}, $courses);
$contextids = [];

foreach ($contexts as $context) {
    $contextids = array_merge($contextids, $context->get_parent_context_ids(true));
}
$contextids = array_unique($contextids);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('core_question/search_page', [
    'contextids' => json_encode($contextids)
]);
echo $OUTPUT->footer();
