<?php

require_once('config.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/modal_test.php');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('core/test_modal', []);
echo $OUTPUT->footer();
