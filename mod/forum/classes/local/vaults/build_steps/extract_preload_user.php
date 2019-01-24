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
 * Build step.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\vaults\build_steps;

defined('MOODLE_INTERNAL') || die();

use user_picture;

/**
 * Build step.
 */
class extract_preload_user {
    private $idalias;
    private $alias;

    public function __construct(string $idalias, string $alias) {
        $this->idalias = $idalias;
        $this->alias = $alias;
    }

    public function execute(array $records) : array {
        $idalias = $this->idalias;
        $alias = $this->alias;

        return array_map(function($record) use ($idalias, $alias) {
            return user_picture::unalias($record, null, $idalias, $alias);
        }, $records);
    }
}
