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
 * Todo.
 *
 * @package    core
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core;
defined('MOODLE_INTERNAL') || die();

class todo {

    protected $id;
    protected $uniqueid;
    protected $contextname;
    protected $contexturl;
    protected $courseid;
    protected $iconurl;
    protected $startdate = null;
    protected $enddate = null;
    protected $itemcount = null;
    protected $actionname;
    protected $actionurl;
    protected $actionstartdate = null;

    public function __construct($uniqueid,
                                $contextname,
                                $contexturl,
                                $courseid,
                                $iconurl,
                                $startdate = null,
                                $enddate = null,
                                $itemcount = null,
                                $actionname,
                                $actionurl,
                                $actionstartdate = null) {

        $this->uniqueid = $uniqueid;
        $this->contextname = $contextname;
        $this->contexturl = $contexturl;
        $this->courseid = $courseid;
        $this->iconurl = $iconurl;
        $this->startdate = $startdate;
        $this->enddate = $enddate;
        $this->itemcount = $itemcount;
        $this->actionname = $actionname;
        $this->actionurl = $actionurl;
        $this->actionstartdate = $actionstartdate;
    }

    public function get_unique_id() {
        return $this->uniqueid;
    }

    public function equals($object) {
        if (!is_a($object, 'todo')) {
            return false;
        }

        return $this->id == $object->id &&
               $this->contextname == $object->contextname &&
               $this->contexturl == $object->contexturl &&
               $this->courseid == $object->courseid &&
               $this->iconurl == $object->iconurl &&
               $this->startdate == $object->startdate &&
               $this->enddate == $object->enddate &&
               $this->itemcount == $object->itemcount &&
               $this->actionname == $object->actionname &&
               $this->actionurl == $object->actionurl &&
               $this->actionstartdate == $object->actionstartdate;
    }
}
