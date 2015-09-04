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
require_once(__DIR__."/notification.php");
require_once(__DIR__."/repository.php");

use DateTime;
use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;
use \local_notification\repository as repository;
use \local_notification\serialiser as serialiser;

/**
 * This is the external API for this plugin.
 *
 * @copyright  2015 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    // START query_notifications.
    public static function query_notifications_parameters() {
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

    public static function query_notifications_is_allowed_from_ajax() {
        return true;
    }

    public static function query_notifications($limit = 0, $offset = 0) {
        global $USER;

        // TODO: Remove me.
        sleep(2);

        $serialiser = new serialiser();
        $repository = new repository();
        $total = $repository->count_all_for_user($USER);
        $total_unseen = $repository->count_all_unseen_for_user($USER);
        $notifications = $repository->retrieve_all_for_user($USER, $limit, $offset);

        $serialise_notification = function($notification) use ($serialiser) {
            return $serialiser->to_array($notification);
        };

        return array(
            'total_count' => $total,
            'total_unseen_count' => $total_unseen,
            'notifications' => array_map($serialise_notification, $notifications)
        );
    }

    public static function query_notifications_returns() {
        return \core_webservice_external::get_site_info_returns();
    }
    // END query_notifications.

    // START update_notifications
    public static function update_notifications_parameters() {
        return new external_function_parameters(
            array(
                'notifications' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'notification id'),
                            'type' => new external_value(PARAM_ALPHANUMEXT, 'notification type'),
                            'description' => new external_value(PARAM_TEXT, 'notification description'),
                            'url' => new external_value(PARAM_TEXT, 'notification url'),
                            'user_id' => new external_value(PARAM_INT, 'notification user_id'),
                            'created_date' => new external_value(PARAM_TEXT, 'notification created_date'),
                            'seen' => new external_value(PARAM_BOOL, 'notification seen'),
                            'actioned' => new external_value(PARAM_BOOL, 'notification actioned'),
                        )
                    )
                )
            ),
            'update notifications parameters'
        );
    }

    public static function update_notifications_is_allowed_from_ajax() {
        return true;
    }

    public static function update_notifications($notifications_data) {
        global $USER;

        $notifications = array_map('self::unpack_from_request_data', $notifications_data);

        $serialiser = new serialiser();
        $repository = new repository();

        $repository->update_multiple($notifications);

        $total = $repository->count_all_for_user($USER);
        $total_unseen = $repository->count_all_unseen_for_user($USER);

        $serialise_notification = function($notification) use ($serialiser) {
            return $serialiser->to_array($notification);
        };

        return array(
            'total_count' => $total,
            'total_unseen_count' => $total_unseen,
            'notifications' => array_map($serialise_notification, $notifications)
        );
    }

    public static function update_notifications_returns() {
        return \core_webservice_external::get_site_info_returns();
    }
    // END update_notifications.

    private static function unpack_from_request_data($data) {

        return new notification(
            $data['id'],
            $data['type'],
            $data['url'],
            $data['user_id'],
            $data['description'],
            $data['seen'] ? true : false,
            $data['actioned'] ? true : false,
            DateTime::createFromFormat(DateTime::ATOM, $data['created_date'])
        );
    }
}
