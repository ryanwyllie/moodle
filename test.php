<?php
require_once('./config.php');
require_login(0, false);
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/test.php', []);
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Test page");
$PAGE->set_heading("Test page");
$context = [
    'title' => 'Test',
    'items' => [1, 2, 3]
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('core/test', $context);
echo $OUTPUT->footer();
