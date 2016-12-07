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

class todo implements Comparable {

    protected $id;
    protected $name;
    protected $startdate = null;
    protected $enddate = null;

    public function __construct($id, $name, $startdate = null, $enddate = null) {
    }

    public function compareTo($value) {
        if (!$value instanceof todo) {
            throw new moodle_exception('You can only compart to other todos');
        }

        return $this->id == $value->id &&
               $this->name == $value->name &&
               $this->startdate == $value->startdate &&
               $this->enddate == $value->enddate;
    }
}
