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
 * Contains class used to prepare the contacts for display.
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
 * Class to prepare the contacts for display.
 *
 * @package   core_message
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contacts implements templatable, renderable {

    /**
     * @var int The id of the user that the contacts belong to.
     */
    public $userid;

    /**
     * @var int The id of the user that has been selected.
     */
    public $otheruserid;

    /**
     * @var \core_message\output\messagearea\contact[] The contacts.
     */
    public $contacts;

    /**
     * @var bool Are we storing conversations or contacts?
     */
    public $isconversation;

    /**
     * Constructor.
     *
     * @param int $userid The id of the user the contacts belong to
     * @param int $otheruserid The id of the user we are viewing
     * @param \core_message\output\messagearea\contact[] $contacts
     * @param bool $isconversation Are we storing conversations or contacts?
     */
    public function __construct($userid, $otheruserid, $contacts, $isconversation = true) {
        $this->userid = $userid;
        $this->otheruserid = $otheruserid;
        $this->contacts = $contacts;
        $this->isconversation = $isconversation;
    }

    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();
        $data->userid = $this->userid;
        $data->otheruserid = $this->otheruserid;
        $data->contacts = array();
        $userids = array();
        foreach ($this->contacts as $contact) {
            $contactdata = $contact->export_for_template($output);
            $userids[$contactdata->userid] = $contactdata->userid;
            // Check if the contact was selected.
            if ($this->otheruserid == $contactdata->userid) {
                $contactdata->selected = true;
            }
            $data->contacts[] = $contactdata;
        }
        // Check if the other user is not part of the contacts. We may be sending a message to someone
        // we have not had a conversation with, so we want to add a new item to the contacts array.
        if ($this->otheruserid && !isset($userids[$this->otheruserid])) {
            $user = \core_user::get_user($this->otheruserid);
            // Set an empty message so that we know we are messaging the user, and not viewing their profile.
            $user->smallmessage = '';
            $user->useridfrom = $user->id;
            $contact = \core_message\helper::create_contact($user);
            $contactdata = $contact->export_for_template($output);
            $contactdata->selected = true;
            // Put the contact at the front.
            array_unshift($data->contacts, $contactdata);
        }

        $data->isconversation = $this->isconversation;
        return $data;
    }
}
