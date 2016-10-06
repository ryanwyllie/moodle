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
 * Contains standard functions for message_popup.
 *
 * @package   message_popup
 * @copyright 2016 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function message_popup_render_navbar_output(\renderer_base $renderer) {
    global $USER, $DB, $CFG;

    // Early bail out conditions.
    if (!isloggedin() || isguestuser() || user_not_fully_set_up($USER)) {
        return '';
    }

    $output = '';

    // Add the messages popover.
    if (!empty($CFG->messaging)) {
        $context = [
            'userid' => $USER->id,
            'urls' => [
                'preferences' => (new moodle_url('/message/edit.php', ['id' => $USER->id]))->out(),
            ],
        ];
        $output .= $renderer->render_from_template('message_popup/message_popover', $context);
    }

    // Add the notifications popover.
    $processor = $DB->get_record('message_processors', array('name' => 'popup'));
    if ($processor && $processor->enabled) {
        $context = [
            'userid' => $USER->id,
            'urls' => [
                'preferences' => (new moodle_url('/message/notificationpreferences.php', ['userid' => $USER->id]))->out(),
            ],
        ];
        $output .= $renderer->render_from_template('message_popup/notification_popover', $context);
    }

    return $output;
}
