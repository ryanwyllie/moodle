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

use DateTime;
use \local_notification\notification as notification;
use \local_notification\repository as repository;

class factory {

    private $repository;

    public function __construct() {
        $this->repository = new repository();
    }

    public function create_from_event(\core\event\base $event) {
        // TODO: Need to handle different event types here because
        // the data isn't going to map perfectly.

        $notification = new notification(null, 'user',
            $event->get_url()->out(), $event->relateduserid, $event->get_description());

        $this->repository->create($notification);
    }
}
