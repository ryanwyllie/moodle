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
 * Event factory class
 *
 * @package    core_calendar
 * @copyright  2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_calendar\local\event\entities;

use core_calendar\local\event\entities\event_abstract_factory;
use core_calendar\local\event\entities\event_interface;
use core_calendar\local\event\visitors\core_component_visitor_interface;
use core_calendar\local\event\visitors\visitable_interface;
use core_calendar\local\event\facades\core_facade_interface;

/**
 * Event factory class
 *
 * This factory extends the abstract factory, implementing the visit_moodle
 * method. We visit moodle by using facades representing core components.
 *
 * @copyright 2017 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_factory extends event_abstract_factory {
    /**
     * @var core_facade_interface $corefacade Moodle core facade
     */
    private $corefacade;

    /**
     * @var core_component_visitor_interface $corecomponentvisitor Moodle core component visitor
     */
    private $corecomponentvisitor;

    /**
     * Constructor
     *
     * @param core_facade_interface            $corefacade           Moodle core facade
     * @param core_component_visitor_interface $corecomponentvisitor Moodle core component visitor
     */
    public function __construct(core_facade_interface $corefacade, core_component_visitor_interface $corecomponentvisitor) {
        $this->corefacade = $corefacade;
        $this->corecomponentvisitor = $corecomponentvisitor;
    }

    protected function visit_moodle(event_interface $event) {
        if (!$event->get_course_module() || !$this->corefacade instanceof visitable_interface) {
            return $event;
        }

        $callbacks = $this->corefacade
                          ->accept($this->corecomponentvisitor)
                          ->get_callbacks();

        if (!$callbacks['mod'][$event->get_course_module()->get_name()]) {
            return $event;
        }

        $callback = $callbacks['mod'][$event->get_course_module()->get_name()];

        return $callback($event);
    }
}
