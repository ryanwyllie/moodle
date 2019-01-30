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
 * Post class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\entities;

defined('MOODLE_INTERNAL') || die();

/**
 * Post class.
 */
class author {
    private $id;
    private $pictureitemid;
    private $firstname;
    private $lastname;
    private $fullname;
    private $email;
    private $middlename;
    private $firstnamephonetic;
    private $lastnamephonetic;
    private $alternatename;
    private $imagealt;

    public function __construct(
        int $id,
        int $pictureitemid,
        string $firstname,
        string $lastname,
        string $fullname,
        string $email,
        string $middlename = null,
        string $firstnamephonetic = null,
        string $lastnamephonetic = null,
        string $alternatename = null,
        string $imagealt = null
    ) {
        $this->id = $id;
        $this->pictureitemid = $pictureitemid;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->fullname = $fullname;
        $this->email = $email;
        $this->middlename = $middlename;
        $this->firstnamephonetic = $firstnamephonetic;
        $this->lastnamephonetic = $lastnamephonetic;
        $this->alternatename = $alternatename;
        $this->imagealt = $imagealt;
    }

    public function get_id() : int {
        return $this->id;
    }

    public function get_picture_item_id() : int {
        return $this->pictureitemid;
    }

    public function get_first_name() : string {
        return $this->firstname;
    }

    public function get_last_name() : string {
        return $this->lastname;
    }

    public function get_full_name() : string {
        return $this->fullname;
    }

    public function get_email() : string {
        return $this->email;
    }

    public function get_middle_name() : ?string {
        return $this->middlename;
    }

    public function get_first_name_phonetic() : ?string {
        return $this->firstnamephonetic;
    }

    public function get_last_name_phonetic() : ?string {
        return $this->lastnamephonetic;
    }

    public function get_alternate_name() : ?string {
        return $this->alternatename;
    }

    public function get_image_alt() : ?string {
        return $this->alternatename;
    }
}
