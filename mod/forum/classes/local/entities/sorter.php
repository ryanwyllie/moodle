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
 * Class to sort posts.
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
class sorter {
    private $getid;
    private $getparentid;

    public function __construct(callable $getid, callable $getparentid) {
        $this->getid = $getid;
        $this->getparentid = $getparentid;
    }

    public function sort_into_children(array $items, array $seenitems = []) : array {
        $ids = array_reduce($items, function($carry, $item) {
            $carry[($this->getid)($item)] = true;
            return $carry;
        }, []);

        [$parents, $replies] = array_reduce($items, function($carry, $item) use ($ids) {
            $parentid = ($this->getparentid)($item);

            if (!empty($ids[$parentid])) {
                // This is a child to another item in the list so add it to the children list.
                $carry[1][] = $item;
            } else {
                // This isn't a child to anything in our list so it's a parent.
                $carry[0][] = $item;
            }

            return $carry;
        }, [[], []]);

        if (empty($replies)) {
            return array_map(function($parent) {
                return [$parent, []];
            }, $parents);
        }

        $sortedreplies = $this->sort_into_children($replies);

        return array_map(function($parent) use ($sortedreplies) {
            $parentid = ($this->getid)($parent);
            return [
                $parent,
                array_values(array_filter($sortedreplies, function($replydata) use ($parentid) {
                    return ($this->getparentid)($replydata[0]) == $parentid;
                }))
            ];
        }, $parents);
    }

    public function flatten_children(array $items) : array {
            $result = [];

            foreach ($items as [$item, $children]) {
                $result[] = $item;
                $result = array_merge($result, $this->flatten_children($children));
            }

            return $result;
    }
}
