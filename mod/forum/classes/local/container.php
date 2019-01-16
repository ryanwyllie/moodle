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
 * Vault class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\factories\renderer as renderer_factory;
use mod_forum\local\factories\database_data_mapper as database_data_mapper_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\vault as vault_factory;

/**
 * Vault class.
 */
class container {
    public static function get_renderer_factory() : renderer_factory {
        return new renderer_factory(
            self::get_database_data_mapper_factory(),
            self::get_exporter_factory(),
            self::get_vault_factory()
        );
    }

    public static function get_database_data_mapper_factory() : database_data_mapper_factory {
        return new database_data_mapper_factory();
    }

    public static function get_exporter_factory() : exporter_factory {
        return new exporter_factory(
            self::get_database_data_mapper_factory()
        );
    }

    public static function get_vault_factory() : vault_factory {
        return new vault_factory(
            self::get_database_data_mapper_factory()
        );
    }
}
