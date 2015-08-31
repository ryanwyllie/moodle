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
 * TESTING PAGE for autocomplete field.
 */

require('config.php');
require_once("$CFG->libdir/formslib.php");

class test_form extends moodleform {
    /**
     * Generate random pronounceable words
     *
     * @param int $length Word length
     * @return string Random word
     */
    function random_pronounceable_word($length = 6) {

        // Consonant sounds.
        $cons = array(
            // Single consonants. Beware of Q, it's often awkward in words.
            'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm',
            'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'z',
            // Possible combinations excluding those which cannot start a word.
            'pt', 'gl', 'gr', 'ch', 'ph', 'ps', 'sh', 'st', 'th', 'wh',
        );

        // Consonant combinations that cannot start a word.
        $cons_cant_start = array(
            'ck', 'cm',
            'dr', 'ds',
            'ft',
            'gh', 'gn',
            'kr', 'ks',
            'ls', 'lt', 'lr',
            'mp', 'mt', 'ms',
            'ng', 'ns',
            'rd', 'rg', 'rs', 'rt',
            'ss',
            'ts', 'tch',
        );

        // Vowels.
        $vows = array(
            // Single vowels.
            'a', 'e', 'i', 'o', 'u', 'y',
            // Vowel combinations your language allows.
            'ee', 'oa', 'oo',
        );

        // Start by vowel or consonant ?
        $current = ( mt_rand( 0, 1 ) == '0' ? 'cons' : 'vows' );

        $word = '';

        while( strlen( $word ) < $length ) {

            // After first letter, use all consonant combos
            if( strlen( $word ) == 2 )
                $cons = array_merge( $cons, $cons_cant_start );

             // Random sign from either $cons or $vows.
            $rnd = ${$current}[ mt_rand( 0, count( ${$current} ) -1 ) ];

            // Check if random sign fits in word length.
            if( strlen( $word . $rnd ) <= $length ) {
                $word .= $rnd;
                // Alternate sounds.
                $current = ( $current == 'cons' ? 'vows' : 'cons' );
            }
        }

        return $word;
    }

    function definition() {
        $mform  = $this->_form;

        $mform->addElement('header','general', 'Test autocomplete field');

        $options = array();
        for ($i = 0; $i < 100; $i++) {
            array_push($options, $this->random_pronounceable_word());
        }
        $mform->addElement('autocomplete', 'link', 'Single', $options);
        $mform->addRule('link', get_string('required'), 'required', null, 'client');

        $mform->addElement('autocomplete', 'link2', 'Multi', $options, array('multiple'=>'multiple'));
        $mform->addRule('link2', get_string('required'), 'required', null, 'client');

        $mform->addElement('autocomplete', 'link3', 'Tags', $options, array('multiple'=>'multiple', 'tags'=>true));
        $mform->addRule('link3', get_string('required'), 'required', null, 'client');

        $mform->addElement('autocomplete', 'link35', 'Tags Optional', array(), array('multiple'=>'multiple', 'tags'=>true));

        $mform->addElement('autocomplete', 'link4', 'Frozen', $options, array('multiple'=>'multiple', 'tags'=>true));
        $mform->hardFreeze('link4');

        $mform->addElement('autocomplete', 'link5', 'Ajaxify', array(), array('ajax'=>'core/test'));

        $this->add_action_buttons();

    }

    function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);

        var_dump($data);
        var_dump($errors);

        if (isset($data['link35']) && in_array('showerror', $data['link35'])) {
            $errors['link35'] = 'Custom error - does it display?';
        }

        return $errors;
    }
}


$PAGE->set_url('/test.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());
require_login();

$mform = new test_form();

if ($mform->is_cancelled()) {
    die('Cancelled');

} else if ($data = $mform->get_data()) {
    var_dump($data);
    die('Submitted');
}

$PAGE->set_heading('Test');
$PAGE->set_title('Auto complete');

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
