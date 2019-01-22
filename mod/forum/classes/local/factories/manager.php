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
 * Managers factory.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\factories;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\forum as forum_entity;
use mod_forum\local\managers\capability as capability_manager;

/**
 * Managers factory.
 */
class manager {
    private $dbdatamapperfactory;

    public function __construct(database_data_mapper $dbdatamapperfactory) {
        $this->dbdatamapperfactory = $dbdatamapperfactory;
    }

    public function get_capability_manager(forum_entity $forum) {
        return new capability_manager(
            $forum,
            $this->dbdatamapperfactory->get_forum_data_mapper(),
            $this->dbdatamapperfactory->get_discussion_data_mapper(),
            $this->dbdatamapperfactory->get_post_data_mapper()
        );
    }
}
