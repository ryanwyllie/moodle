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
 * Contains standard functions for message_rocketchat.
 *
 * @package   message_popup
 * @copyright 2019 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use message_rocketchat\local\client as rocket_chat_client;
use renderer_base;

/**
 * Renders rocketchat.
 *
 * @param renderer_base $renderer
 * @return string The HTML
 */
function message_rocketchat_render_navbar_output(renderer_base $renderer) {
    global $USER;

    // Early bail out conditions.
    if (!isloggedin() || isguestuser() || user_not_fully_set_up($USER) ||
        get_user_preferences('auth_forcepasswordchange') ||
        (!$USER->policyagreed && !is_siteadmin() &&
            ($manager = new \core_privacy\local\sitepolicy\manager()) && $manager->is_defined())) {
        return '';
    }

    $output = '';

    $enabled = \core_message\api::is_processor_enabled('rocketchat');
    $enabled = true;

    if ($enabled) {
        $context = ['serverurl' => 'http://rocketchat.localhost:8888'];
        $output .= $renderer->render_from_template('message_rocketchat/nav_drawer', $context);
    }

    return $output;
}

function message_rocketchat_after_user_login(stdClass $user, string $password) {
    global $CFG, $PAGE, $SESSION;

    if (
        empty($CFG->rocketchatserver) ||
        empty($CFG->rocketchatadmintoken) ||
        empty($CFG->rocketchatadminuserid)
    ) {
        return;
    }

    $client = new rocket_chat_client(
        $CFG->rocketchatserver,
        $CFG->rocketchatadmintoken,
        $CFG->rocketchatadminuserid
    );

    [$rocketuser, $error] = $client->fetch_user($user->username);

    if (!$rocketuser) {
        [$rocketuser] = $client->create_user(
            $user->email,
            fullname($user),
            $user->username,
            $password
        );

        if ($rocketuser) {
            $userpicture = new user_picture($user);
            $userpicture->size = 100;
            $userpictureurl = $userpicture->get_url($PAGE);

            $client->set_avatar($user->username, $userpictureurl->out(false));
        }
    }

    if ($rocketuser) {
        [['authToken' => $token, 'userId' => $id], $error] = $client->login_user($user->username, $password);
        $SESSION->rocketchatauthtoken = $token;
        $SESSION->rocketchatuserid = $id;
    }
}

function message_rocketchat_before_user_logout() {
    global $CFG, $SESSION;

    if (
        empty($CFG->rocketchatserver) ||
        empty($CFG->rocketchatadmintoken) ||
        empty($CFG->rocketchatadminuserid)
    ) {
        return;
    }

    if (isset($SESSION->rocketchatauthtoken) && isset($SESSION->rocketchatuserid)) {
        // We've got a rocket chat session that we need to log out of.
        $client = new rocket_chat_client(
            $CFG->rocketchatserver,
            $CFG->rocketchatadmintoken,
            $CFG->rocketchatadminuserid
        );

        try {
            $client->logout_user($SESSION->rocketchatauthtoken, $SESSION->rocketchatuserid);
        } catch (Exception $e) {
            // Do something?
        }
    }
}