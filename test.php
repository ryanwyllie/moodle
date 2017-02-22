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

require_once('config.php');

require_login(null, false);
$systemcontext = context_system::instance();
$pageurl = new moodle_url('/test.php');
$context = [
    't1' => 1293876000,
    't2' => 1293876000,
    't3' => 1293876000,
    'f1' => '%A, %d %B %Y, %I:%M %p',
];

$PAGE->set_context($systemcontext);
$PAGE->set_url($pageurl);
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('core/test', $context);
echo $OUTPUT->footer();
