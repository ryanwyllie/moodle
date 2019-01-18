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
 * Forum class.
 *
 * @package    mod_forum
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\data_mappers\database;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\factories\entity as entity_factory;
use context;
use stdClass;

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Forum class.
 */
class post implements db_data_mapper_interface {
    private $entityfactory;

    public function __construct(entity_factory $entityfactory) {
        $this->entityfactory = $entityfactory;
    }

    public function from_db_records(array $records) : array {
        $authorids = array_keys(array_reduce($records, function($carry, $record) {
            $carry[$record->userid] = true;
            return $carry;
        }, []));
        // TODO: Remove this dependency!
        $authors = $this->authorvault->get_from_ids($authorids);
        $authorsbyid = array_reduce($authors, function($carry, $author) {
            $carry[$author->get_id()] = $author;
            return $carry;
        }, []);

        $entityfactory = $this->entityfactory;

        return array_map(function(stdClass $record) use ($authorsbyid, $entityfactory) {
            return $entityfactory->get_post_from_stdClass($record);
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
    }

    public function to_db_records(array $posts) : array {
        return array_map(function(post_entity $post) {
            return (object) [
                'id' => $post->get_id(),
                'discussion' => $post->get_discussion_id(),
                'parent' => $post->get_parent_id(),
                'userid' => $post->get_author()->get_id(),
                'created' => $post->get_time_created(),
                'modified' => $post->get_time_modified(),
                'mailed' => $post->has_been_mailed(),
                'subject' => $post->get_subject(),
                'message' => $post->get_message(),
                'messageformat' => $post->get_message_format(),
                'messagetrust' => $post->is_message_trusted(),
                'attachment' => $post->get_attachment(),
                'totalscore' => $post->get_total_score(),
                'mailnow' => $post->should_mail_now(),
                'deleted' => $post->is_deleted()
            ];
        }, $posts);
    }
}
