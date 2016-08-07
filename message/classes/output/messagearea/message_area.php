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
 * Contains class used to prepare the message area for display.
 *
 * @package   core_message
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_message\output\messagearea;

use renderable;
use templatable;

/**
 * Class to prepare the message area for display.
 *
 * @package   core_message
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_area implements templatable, renderable {

    /**
     * The user id.
     */
    protected $userid;

    /**
     * The contacts for the users.
     */
    protected $contacts;

    /**
     * The messages for the user.
     */
    protected $messages;

    /**
     * Constructor.
     *
     * @param int $userid The ID of the user whose contacts and messages we are viewing
     * @param \core_message\output\messagearea\contacts $contacts
     * @param \core_message\output\messagearea\messages|null $messages
     */
    public function __construct($userid, $contacts, $messages) {
        $this->userid = $userid;
        $this->contacts = $contacts;
        $this->messages = $messages;
    }

    public function export_for_template(\renderer_base $output) {
        global $USER;

        $data = new \stdClass();
        $data->loggedinuserid = $USER->id;
        $data->userid = $this->userid;
        $data->contacts = $this->contacts->export_for_template($output);
        if ($this->messages) {
            $data->messages = $this->messages->export_for_template($output);
        }

        return $data;
    }
}
