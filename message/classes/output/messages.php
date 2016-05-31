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
 * Contains class used to prepare the messages for display.
 *
 * @package   core_message
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_message\output;

use renderable;
use templatable;

/**
 * Class to prepare the messages for display.
 *
 * @package   core_message
 * @copyright 2016 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class messages implements templatable, renderable {

    /**
     * The messages.
     */
    protected $messages;

    /**
     * The user from.
     */
    protected $userfrom;

    /**
     * Constructor.
     *
     * @param \core_message\output\message[] $messages
     * @param \stdClass $userfrom The user we are wanting to view messages from
     */
    public function __construct($messages, $userfrom) {
        $this->messages = $messages;
        $this->userfrom = $userfrom;
    }

    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();
        $data->userfromfullname = fullname($this->userfrom);
        $data->userfromid = $this->userfrom->id;
        $data->messages = array();
        foreach ($this->messages as $message) {
            $data->messages[] = $message->export_for_template($output);
        }

        return $data;
    }
}
