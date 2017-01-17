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
 * Contains event class for displaying a calendar event action.
 *
 * @package   core_calendar
 * @copyright 2016 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use \core_calendar\local\event\entities\action_interface;

/**
 * Class for displaying a calendar event action.
 *
 * @package   core_calendar
 * @copyright 2016 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_action implements templatable, renderable {

    /**
     * @var action_interface The action.
     */
    protected $action;

    /**
     * Constructor.
     *
     * @param \core_calendar\local\event\entities\action_interface $action
     */
    public function __construct(action_interface $action) {
        $this->action = $action;
    }

    /**
     * Get the output for a template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        return [
            'name' => $this->action->get_name(),
            'url' => $this->action->get_url()->out(),
            'itemcount' => $this->action->get_item_count()
        ];
    }
}
