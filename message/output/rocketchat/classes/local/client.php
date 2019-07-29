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
 * Client library for Rocket chat.
 *
 * @copyright 2019 Ryan Wyllie <ryan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_rocketchat\local;

defined('MOODLE_INTERNAL') || die();

class client {
    private $serverurl = null;
    private $admintoken = null;
    private $adminuserid = null;
    private $adminuserheaders = [];

    public function __construct(string $serverurl, string $admintoken, string $adminuserid) {
        $this->serverurl = $serverurl;
        $this->admintoken = $admintoken;
        $this->adminuserid = $adminuserid;
        $this->adminheaders = [
            "X-Auth-Token: {$admintoken}",
            "X-User-Id: {$adminuserid}"
        ];
    }

    function fetch_user(string $username) {
        [$data, $error] = $this->get("users.info?username={$username}", true);
        return $error ? [null, $error] : [$data['user'], null];
    }

    public function login_user(string $username, string $password) {
        [$data, $error] = $this->post(
            'login',
            [
                'user' => $username,
                'password' => $password
            ],
            false
        );
        return $error ? [null, $error] : [$data['data'], null];
    }

    public function create_user($email, $name, $username, $password) {
        [$data, $error] = $this->post(
            'users.create',
            [
                'email' => $email,
                'name' => $name,
                'username' => $username,
                'password' => $password,
                'verified' => true,
                'sendWelcomeEmail' => false
            ],
            true
        );
        return $error ? [null, $error] : [$data['user'], null];
    }

    /*
    public function get_user_tokens($username, $password) {
        ['authToken' => $token, 'userId' => $id] = rocketchat_login_user($username, $password);
        return [$token, $id];
    }
    */

    private function get(string $apiresource, bool $withadminheaders = true) {
        return $this->send($apiresource, [], $withadminheaders);
    }

    private function post(string $apiresource, array $data, bool $withadminheaders = true) {
        $opts = [
            CURLOPT_HTTPHEADER => ['Content-type:application/json'],
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($data)
        ];
        return $this->send($apiresource, $opts, $withadminheaders);
    }

    private function send(string $apiresource, array $opts = [], bool $withadminheaders = true) {
        $url = "{$this->serverurl}/api/v1/{$apiresource}";
        $opts = array_merge([
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url
        ], $opts);

        if ($withadminheaders && isset($opts[CURLOPT_HTTPHEADER])) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $this->adminuserheaders);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $return = [null, null];

        try {
            $response = json_decode(curl_exec($ch), true);

            if (!empty($response['error'])) {
                $return = [null, $response['message']];
            } else {
                $return = [$response, null];
            }
        } catch(Exception $e) {
            $return = [null, $e->getMessage()];
        }

        curl_close($ch);

        return $return;
    }
}
