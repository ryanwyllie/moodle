<?php
require('config.php');
require_once("$CFG->libdir/formslib.php");

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('embedded');

class MDL59667form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('date_selector', 'datepick', get_string('date'));
    }
}

$f = new MDL59667form();

echo $OUTPUT->header();
$f->display();
echo $OUTPUT->footer();
