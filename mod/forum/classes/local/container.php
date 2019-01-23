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
use mod_forum\local\factories\legacy_data_mapper as legacy_data_mapper_factory;
use mod_forum\local\factories\entity as entity_factory;
use mod_forum\local\factories\exporter as exporter_factory;
use mod_forum\local\factories\manager as manager_factory;
use mod_forum\local\factories\vault as vault_factory;

/**
 * Vault class.
 */
class container {
    private static $rendererfactory = null;
    private static $databasedatamapperfactory = null;
    private static $legacydatamapperfactory = null;
    private static $exporterfactory = null;
    private static $vaultfactory = null;
    private static $managerfactory = null;
    private static $entityfactory = null;

    public static function get_renderer_factory() : renderer_factory {
        global $PAGE;

        if (is_null(self::$rendererfactory)) {
            self::$rendererfactory = new renderer_factory(
                self::get_legacy_data_mapper_factory(),
                self::get_exporter_factory(),
                self::get_vault_factory(),
                self::get_manager_factory(),
                $PAGE->get_renderer('mod_forum')
            );
        }

        return self::$rendererfactory;
    }

    public static function get_database_data_mapper_factory() : database_data_mapper_factory {
        global $DB;
        if (is_null(self::$databasedatamapperfactory)) {
            self::$databasedatamapperfactory = new database_data_mapper_factory(
                $DB,
                self::get_entity_factory()
            );
        }

        return self::$databasedatamapperfactory;
    }

    public static function get_legacy_data_mapper_factory() : legacy_data_mapper_factory {
        if (is_null(self::$legacydatamapperfactory)) {
            self::$legacydatamapperfactory = new legacy_data_mapper_factory();
        }

        return self::$legacydatamapperfactory;
    }

    public static function get_exporter_factory() : exporter_factory {
        if (is_null(self::$exporterfactory)) {
            self::$exporterfactory = new exporter_factory(
                self::get_legacy_data_mapper_factory(),
                self::get_manager_factory()
            );
        }

        return self::$exporterfactory;
    }

    public static function get_vault_factory() : vault_factory {
        global $DB;

        if (is_null(self::$vaultfactory)) {
            self::$vaultfactory = new vault_factory(
                $DB,
                self::get_database_data_mapper_factory()
            );
        }

        return self::$vaultfactory;
    }

    public static function get_manager_factory() : manager_factory {
        if (is_null(self::$managerfactory)) {
            self::$managerfactory = new manager_factory(
                self::get_legacy_data_mapper_factory()
            );
        }

        return self::$managerfactory;
    }

    public static function get_entity_factory() : entity_factory {
        if (is_null(self::$entityfactory)) {
            self::$entityfactory = new entity_factory();
        }

        return self::$entityfactory;
    }
}
