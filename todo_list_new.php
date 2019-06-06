<?php
require_once('./config.php');
require_login(0, false);
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/test.php', []);
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Test page");
$PAGE->set_heading("Test page");
$context = [
    'sortasc' => true,
    'sortdesc' => false,
    'title' => 'Test',
    'items' => [
        [
            'done' => true,
            'task' => 'Create todo list example',
            'author' => 'Ryan',
            'time' => 1,
        ],
        [
            'done' => false,
            'task' => 'Foo',
            'author' => 'Someone',
            'time' => 3
        ],
    ]
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('core/todo_list_new', $context);
echo $OUTPUT->footer();
