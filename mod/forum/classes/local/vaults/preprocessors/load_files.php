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

namespace mod_forum\local\vaults\preprocessors;

defined('MOODLE_INTERNAL') || die();

use file_storage;
use stdClass;

/**
 * Build step.
 */
class load_files {
    private const COMPONENT = 'mod_forum';
    private const FILE_AREA = 'attachment';
    private const SORT = 'filename';
    private const INCLUDE_DIRECTORIES = false;
    private $filestorage;
    private $contextidpropertyname;
    private $itemidpropertyname;

    public function __construct(file_storage $filestorage, string $contextidpropertyname, string $itemidpropertyname) {
        $this->filestorage = $filestorage;
        $this->contextidpropertyname = $contextidpropertyname;
        $this->itemidpropertyname = $itemidpropertyname;
    }

    public function execute(array $records) : array {
        $itemidsbycontextid = array_reduce($records, function($carry, $record) {
            $contextid = $this->get_context_id($record);
            $itemid = $this->get_item_id($record);

            if (isset($carry[$contextid])) {
                $carry[$contextid] = array_merge($carry[$contextid], [$itemid]);
            } else {
                $carry[$contextid] = [$itemid];
            }

            return $carry;
        }, []);

        $filesbyid = [];

        foreach ($itemidsbycontextid as $contextid => $itemids) {
            $files = $this->filestorage->get_area_files(
                $contextid,
                self::COMPONENT,
                self::FILE_AREA,
                $itemids,
                self::SORT,
                self::INCLUDE_DIRECTORIES
            );

            $filesbyid = array_reduce($files, function($carry, $file) {
                $itemid = $file->get_itemid();
                if (isset($carry[$itemid])) {
                    $carry[$itemid] = array_merge($carry[$itemid], [$file]);
                } else {
                    $carry[$itemid] = [$file];
                }
                return $carry;
            }, $filesbyid);
        }

        return array_map(function($record) use ($filesbyid) {
            $itemid = $this->get_item_id($record);
            return isset($filesbyid[$itemid]) ? $filesbyid[$itemid] : [];
        }, $records);
    }

    private function get_context_id(stdClass $record) : int {
        return $record->{$this->contextidpropertyname};
    }

    private function get_item_id(stdClass $record) : int {
        return $record->{$this->itemidpropertyname};
    }
}
