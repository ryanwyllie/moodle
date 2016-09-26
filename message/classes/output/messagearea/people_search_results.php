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
 * Contains class used to display people search results.
 *
 * @package   core_message
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_message\output\messagearea;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;

/**
 * Class used to display people search results.
 *
 * @package   core_message
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class people_search_results implements templatable, renderable {

    /**
     * @var \core_message\output\messagearea\contact[] The list of contacts.
     */
    public $contacts;

    /**
     * @var array The list of courses.
     */
    public $courses;

    /**
     * @var \core_message\output\messagearea\contact[] The list of non-contacts.
     */
    public $noncontacts;

    /**
     * Constructor.
     *
     * @param \core_message\output\messagearea\contact[] $contacts
     * @param array $courses
     * @param \core_message\output\messagearea\contact[] $noncontacts
     */
    public function __construct($contacts, $courses = array(), $noncontacts = array()) {
        $this->contacts = $contacts;
        $this->courses = $courses;
        $this->noncontacts = $noncontacts;
    }

    public function export_for_template(\renderer_base $output) {
        // Set the defaults for the data we are going to export.
        $data = new \stdClass();
        $data->hascontacts = false;
        $data->contacts = array();
        $data->hascourses = false;
        $data->courses = array();
        $data->hasnoncontacts = false;
        $data->noncontacts = array();

        // Check if there are any contacts.
        if (!empty($this->contacts)) {
            $data->hascontacts = true;
            foreach ($this->contacts as $contact) {
                $data->contacts[] = $contact->export_for_template($output);
            }
        }

        // Check if there are any courses.
        if (!empty($this->courses)) {
            $data->hascourses = true;
            $data->courses = $this->courses;
        }

        // Check if there are any non-contacts.
        if (!empty($this->noncontacts)) {
            $data->hasnoncontacts = true;
            foreach ($this->noncontacts as $noncontact) {
                $data->noncontacts[] = $noncontact->export_for_template($output);
            }
        }

        return $data;
    }
}