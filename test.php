<?php

require_once('./config.php');

require_login(0, false);
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/test_paged_content.php', []);
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Test page");
$PAGE->set_heading("Test page");

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('core/test', []);
echo $OUTPUT->footer();