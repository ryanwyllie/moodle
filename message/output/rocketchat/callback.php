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
 * Contains callback functions for embedded iframe rocket chat app.
 *
 * @copyright 2019 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
$fromiphrame = optional_param('fromiphrame', false, PARAM_BOOL);
$isloggedin = isloggedin();
$isguest = isguestuser();
$authtoken = $SESSION->rocketchatauthtoken ?? null;
$canauthenticate = $isloggedin && $authtoken && !$isguestuser;
$header = '';
$contenttype = !$fromiphrame ? 'Content-Type: application/json' : null;
$content = '';
//$rocketchatserver = $CFG->rocketchatserver;
$rocketchatserver = 'http://rocketchat.localhost:8888';

if (!$canauthenticate) {
    $header = 'HTTP/1.1 401 Unauthorized';

    if (!$isloggedin) {
        $content = 'User not logged in';
    } else if ($isguestuser) {
        $content = 'Guest users can not use messaging';
    } else {
        $content = 'Messaging not configured for user';
    }


    if (!$fromiphrame) {
        $content = json_encode(['message' => $content]);
    }
} else {
    $header = 'HTTP/1.1 200 OK';
    if ($fromiphrame) {
        $content = "<script>
                        window.parent.postMessage({
                            event: 'login-with-token',
                            loginToken: '{$authtoken}'
                        }, '{$rocketchatserver}');
                    </script>";
    } else {
        $content = json_encode(['loginToken' => $authtoken]);
    }
}

header("Access-Control-Allow-Origin: {$rocketchatserver}");
header($header);
if ($contenttype) {
    header($contenttype);
}
echo $content;
die();