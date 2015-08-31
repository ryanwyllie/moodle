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
 * Class containing data for index page
 *
 * @package    local_notification
 * @copyright  2015 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_notification;

require_once("$CFG->dirroot/webservice/externallib.php");
require_once(__DIR__."/notification.php");

use renderable;
use templatable;
use renderer_base;
use stdClass;
use \local_notification\repository as repository;
use \local_notification\serialiser as serialiser;

/**
 * Class containing data for index page
 *
 * @copyright  2015 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller implements renderable, templatable {

    private $serialiser;

    public function __construct() {
        $this->serialiser = new serialiser();
    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        return new stdClass;
    }

    public function retrieve($limit = 0, $offset = 0, DateTime $before = null, DateTime $after = null) {
        $repository = new repository();
        $total = $repository->count_all();
        $total_unseen = $repository->count_all_unseen();
        $notifications = $repository->retrieve($limit, $offset, $before, $after);

        $serialise_notification = function($notification) {
            return $this->serialiser->to_array($notification);
        };

        return array(
            'total_count' => $total,
            'total_unseen_count' => $total_unseen,
            'notifications' => array_map($serialise_notification, $notifications)
        );
    }

    public function update() {

    }
}
