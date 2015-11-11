<?php

require('config.php');

$PAGE->set_url('/test-form.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());
require_login();

$PAGE->set_heading('Test Form');
$PAGE->set_title('Auto complete');

$renderer = new renderer_base($PAGE, '');

echo $OUTPUT->header();

echo $renderer->render_from_template('core/form_test', array());

echo $OUTPUT->footer();
