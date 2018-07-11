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
 * Returns a JSON encoded list of AMD source files and their component
 * names.
 *
 * This is used by the babel transpiler to add the component names to the
 * AMD modules as they are being built.
 *
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// These values are hardcoded so that we don't require an installed
// Moodle site in order to run the Grunt build task.
define('CLI_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);
/** Used by library scripts to check they are being called by Moodle */
define('MOODLE_INTERNAL', true);
// Disables all caching.
define('CACHE_DISABLE_ALL', true);
define('PHPUNIT_TEST', false);
define('IGNORE_COMPONENT_CACHE', true);
// Hard code a minimal configuration.
global $CFG;
$CFG = new stdClass();
$CFG->dirroot = __DIR__;
$CFG->libdir = "$CFG->dirroot/lib";
$CFG->admin = 'admin';

require_once("{$CFG->libdir}/classes/requirejs.php");
require_once("{$CFG->libdir}/classes/component.php");

$jsfiles = core_requirejs::find_all_amd_modules(true, true);
$jsfiles = array_flip($jsfiles);
echo json_encode($jsfiles);