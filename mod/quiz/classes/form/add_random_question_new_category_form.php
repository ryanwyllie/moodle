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
 * Add a random question from a new category.
 *
 * @package    mod_quiz
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_quiz\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Class add_random_question_new_category_form.
 *
 * @package    mod_quiz
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_random_question_new_category_form extends \moodleform {

    /**
     * Form definiton.
     */
    public function definition() {
        $mform =& $this->_form;
        $mform->setDisableShortforms();

        $contexts = $this->_customdata['contexts'];
        $usablecontexts = $contexts->having_cap('moodle/question:useall');

        // Hidden elements.
        $mform->addElement('hidden', 'addonpage', 0, 'id="rform_qpage"');
        $mform->setType('addonpage', PARAM_SEQUENCE);
        $mform->addElement('hidden', 'cmid', 0);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'returnurl', 0);
        $mform->setType('returnurl', PARAM_LOCALURL);

        // Random from existing category section.
        // Random from a new category section.
        $mform->addElement('header', 'categoryheader',
                get_string('randomquestionusinganewcategory', 'quiz'));

        $mform->addElement('text', 'name', get_string('name'), 'maxlength="254" size="50"');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('questioncategory', 'parent', get_string('parentcategory', 'question'),
                array('contexts' => $usablecontexts, 'top' => true));
        $mform->addHelpButton('parent', 'parentcategory', 'question');

        $mform->addElement('submit', 'newcategory',
                get_string('createcategoryandaddrandomquestion', 'quiz'));

        // Cancel button.
        $mform->addElement('cancel');
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        if (!empty($fromform['newcategory']) && trim($fromform['name']) == '') {
            $errors['name'] = get_string('categorynamecantbeblank', 'question');
        }

        return $errors;
    }
}
