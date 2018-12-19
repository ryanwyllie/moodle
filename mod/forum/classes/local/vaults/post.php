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

namespace mod_forum\local\vaults;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\entities\post as post_entity;
use mod_forum\local\factories\vault as vault_factory;
use mod_forum\local\vault;

/**
 * Vault class.
 */
class post extends vault {
    public function __construct(\moodle_database $db, string $table = 'forum_posts', callable $transformtoentities = null) {
        if (is_null($transformtoentities)) {
            $transformtoentities = function(array $records) {
                $authorvault = vault_factory::get_author_vault();
                $authorids = array_keys(array_reduce($records, function($carry, $record) {
                    $carry[$record->userid] = true;
                    return $carry;
                }, []));
                $authors = $authorvault->get_from_ids($authorids);
                $authorsbyid = array_reduce($authors, function($carry, $author) {
                    $carry[$author->get_id()] = $author;
                    return $carry;
                }, []);

                return array_map(function($record) use ($authorsbyid) {
                    return new post_entity(
                        $record->id,
                        $record->discussion,
                        $record->parent,
                        $authorsbyid[$record->userid],
                        $record->created,
                        $record->modified,
                        $record->mailed,
                        $record->subject,
                        $record->message,
                        $record->messageformat,
                        $record->messagetrust,
                        $record->attachment,
                        $record->totalscore,
                        $record->mailnow,
                        $record->deleted
                    );
                }, $records);
            };
        }

        return parent::__construct($db, $table, $transformtoentities);
    }

    public function get_from_discussion_id(int $discussionid, string $orderby = 'created ASC') : array {
        $records = $this->get_db()->get_records($this->get_table(), ['discussion' => $discussionid], $orderby);
        return $this->transform_db_records_to_entities($records);
    }
}
