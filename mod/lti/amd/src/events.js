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
 * Standard Ajax wrapper for Moodle. It calls the central Ajax script,
 * which can call any existing webservice using the current session.
 * In addition, it can batch multiple requests and return multiple responses.
 *
 * @module     mod_lti/events
 * @class      events
 * @package    core
 * @copyright  2015 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define([], function() {
    return {
        NEW_TOOL_TYPE: 'lti.tool.type.new',
        START_EXTERNAL_REGISTRATION: 'lti.registration.external.start',
        STOP_EXTERNAL_REGISTRATION: 'lti.registration.external.stop',
        START_CARTRIDGE_REGISTRATION: 'lti.registration.cartridge.start',
        STOP_CARTRIDGE_REGISTRATION: 'lti.registration.cartridge.stop',
        REGISTRATION_FEEDBACK: 'lti.registration.feedback',
        CAPABILITIES_AGREE: 'lti.tool.type.capabilities.agree',
        CAPABILITIES_DECLINE: 'lti.tool.type.capabilities.decline',
    };
});
