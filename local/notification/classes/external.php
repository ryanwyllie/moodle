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
 * This is the external API for this plugin.
 *
 * @package    local_notification
 * @copyright  2015 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_notification;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/webservice/externallib.php");
require_once(__DIR__."/controller.php");

use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;

/**
 * This is the external API for this plugin.
 *
 * @copyright  2015 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Wrap the core function get_site_info.
     *
     * @return external_function_parameters
     */
    public static function retrieve_parameters() {
        return new external_function_parameters(
            array(
                'limit' => new external_value(
                    PARAM_INT,
                    'limit the number of result returned',
                    VALUE_OPTIONAL
                ),
                'offset' => new external_value(
                    PARAM_INT,
                    'offset the result set by a given amount',
                    VALUE_OPTIONAL
                ),
            ),
            'limit and offset parameters',
            VALUE_OPTIONAL
        );
    }

    /**
     * Expose to AJAX
     * @return boolean
     */
    public static function retrieve_is_allowed_from_ajax() {
        return true;
    }

    public static function retrieve($limit = 0, $offset = 0) {
        $controller = new \local_notification\controller();
        return $controller->retrieve($limit, $offset);
    }

    public static function retrieve_returns() {
        return \core_webservice_external::get_site_info_returns();
    }
}
