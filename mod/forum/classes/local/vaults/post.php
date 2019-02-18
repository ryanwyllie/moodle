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

use mod_forum\local\vault;
use mod_forum\local\entities\post as post_entity;
use mod_forum\local\factories\entity as entity_factory;
use mod_forum\local\vaults\preprocessors\extract_user as extract_user_preprocessor;
use mod_forum\local\vaults\preprocessors\load_files as load_files_preprocessor;
use mod_forum\local\vaults\preprocessors\post_read_user_list as post_read_user_list_preprocessor;
use user_picture;
use moodle_database;
use file_storage;
use stdClass;

/**
 * Vault class.
 */
class post extends vault {
    private const TABLE = 'forum_posts';
    private const USER_ID_ALIAS = 'userpictureid';
    private const USER_ALIAS = 'userrecord';
    private $filestorage;

    public function __construct(
        moodle_database $db,
        entity_factory $entityfactory,
        file_storage $filestorage = null
    ) {
        $this->filestorage = $filestorage ?: get_file_storage();
        parent::__construct($db, $entityfactory);
    }

    protected function get_table_alias() : string {
        return 'p';
    }

    protected function generate_get_records_sql(string $wheresql = null, string $sortsql = null) : string {
        $table = self::TABLE;
        $alias = $this->get_table_alias();
        $fields = $alias . '.*, ctx.id as contextid, ' . user_picture::fields('u', null, self::USER_ID_ALIAS, self::USER_ALIAS);
        $tables = "{{$table}} {$alias}";
        $tables .= " JOIN {user} u ON u.id = {$alias}.userid";
        $tables .= " JOIN {forum_discussions} d ON {$alias}.discussion = d.id";
        $tables .= " JOIN {forum} f ON d.forum = f.id";
        $tables .= " JOIN {modules} m ON m.name = 'forum'";
        $tables .= " JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = f.id";
        $tables .= " JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = " . CONTEXT_MODULE;

        $selectsql = "SELECT {$fields} FROM {$tables}";
        $selectsql .= $wheresql ? ' WHERE ' . $wheresql : '';
        $selectsql .= $sortsql ? ' ORDER BY ' . $sortsql : '';

        return $selectsql;
    }

    public function get_preprocessors() : array {
        return array_merge(
            parent::get_preprocessors(),
            [
                'user' => new extract_user_preprocessor(self::USER_ID_ALIAS, self::USER_ALIAS),
                'attachments' => new load_files_preprocessor($this->filestorage, 'contextid', 'id'),
                'useridreadlist' => new post_read_user_list_preprocessor($this->get_db())
            ]
        );
    }

    protected function from_db_records(array $results) {
        $entityfactory = $this->get_entity_factory();

        return array_map(function(array $result) use ($entityfactory) {
            [
                'record' => $record,
                'user' => $authorrecord,
                'attachments' => $attachments
            ] = $result;
            $author = $entityfactory->get_author_from_stdClass($authorrecord);

            return $entityfactory->get_post_from_stdClass($record, $author, $attachments);
        }, $results);
    }

    public function get_from_discussion_id(int $discussionid, string $orderby = 'created ASC') : array {
        $alias = $this->get_table_alias();
        $wheresql = $alias . '.discussion = ?';
        $orderbysql = $alias . '.' . $orderby;
        $sql = $this->generate_get_records_sql($wheresql, $orderbysql);
        $records = $this->get_db()->get_records_sql($sql, [$discussionid]);

        return $this->transform_db_records_to_entities($records);
    }

    public function get_from_discussion_ids(array $discussionids) : array {
        if (empty($discussionids)) {
            return [];
        }

        $alias = $this->get_table_alias();

        list($insql, $params) = $this->get_db()->get_in_or_equal($discussionids);

        $wheresql = "{$alias}.discussion {$insql}";

        $sql = $this->generate_get_records_sql($wheresql, '');
        $records = $this->get_db()->get_records_sql($sql, $params);

        return $this->transform_db_records_to_entities($records);
    }

    public function get_replies_to_post(post_entity $post, string $orderby = 'created ASC') : array {
        $alias = $this->get_table_alias();
        $params = [$post->get_discussion_id(), $post->get_time_created()];
        $wheresql = "{$alias}.discussion = ? and {$alias}.created > ?";
        $orderbysql = $alias . '.' . $orderby;
        $sql = $this->generate_get_records_sql($wheresql, $orderbysql);
        $records = $this->get_db()->get_records_sql($sql, $params);
        $posts = $this->transform_db_records_to_entities($records);
        $sorter = $this->get_entity_factory()->get_posts_sorter();
        $sortedposts = $sorter->sort_into_children($posts);
        $replies = [];

        foreach ($sortedposts as $candidate) {
            [$candidatepost, $candidatereplies] = $candidate;
            if ($candidatepost->has_parent() && $candidatepost->get_parent_id() == $post->get_id()) {
                $replies[] = $candidate;
            }
        }

        if (empty($replies)) {
            return $replies;
        }

        $getreplypostids = function($candidates) use (&$getreplypostids) {
            $ids = [];

            foreach ($candidates as $candidate) {
                [$reply, $replies] = $candidate;
                $ids = array_merge($ids, [$reply->get_id()], $getreplypostids($replies));
            }

            return $ids;
        };
        $replypostids = $getreplypostids($replies);

        return array_filter($posts, function($post) use ($replypostids) {
            return in_array($post->get_id(), $replypostids);
        });
    }

    /**
     * Get a mapping of replies to the specified discussions.
     *
     * @param   int[]       $discussionids The list of discussions to fetch counts for
     * @return  int[]       The number of replies for each discussion returned in an associative array
     */
    public function get_reply_count_for_discussion_ids(array $discussionids) : array {
        if (empty($discussionids)) {
            return [];
        }
        list($insql, $params) = $this->get_db()->get_in_or_equal($discussionids);
        $sql = "SELECT discussion, COUNT(1) FROM {" . self::TABLE . "} p WHERE p.discussion {$insql} AND p.parent > 0 GROUP BY discussion";

        return $this->get_db()->get_records_sql_menu($sql, $params);
    }

    /**
     * Get a mapping of unread post counts for the specified discussions.
     *
     * @param   stdClass    $user The user to fetch counts for
     * @param   int[]       $discussionids The list of discussions to fetch counts for
     * @return  int[]       The count of unread posts for each discussion returned in an associative array
     */
    public function get_unread_count_for_discussion_ids(stdClass $user, array $discussionids) : array {
        global $CFG;

        if (empty($discussionids)) {
            return [];
        }

        $alias = $this->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($discussionids, SQL_PARAMS_NAMED);
        $sql = "SELECT p.discussion, COUNT(p.id) FROM {" . self::TABLE . "} p
             LEFT JOIN {forum_read} r ON r.postid = p.id AND r.userid = :userid
                 WHERE p.discussion {$insql} AND p.modified > :cutofftime AND r.id IS NULL
              GROUP BY p.discussion";

        $params['userid'] = $user->id;
        $params['cutofftime'] = floor((new \DateTime())
            ->sub(new \DateInterval("P{$CFG->forum_oldpostdays}D"))
            ->format('U') / 60) * 60;

        return $this->get_db()->get_records_sql_menu($sql, $params);
    }

    /**
     * Get a mapping of the most recent post in each discussion based on post creation time.
     *
     * @param   int[]       $discussionids The list of discussions to fetch counts for
     * @return  int[]       The post id of the most recent post for each discussions returned in an associative array
     */
    public function get_latest_post_for_discussion_ids(array $discussionids) : array {
        global $CFG;

        if (empty($discussionids)) {
            return [];
        }

        $alias = $this->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($discussionids, SQL_PARAMS_NAMED);

        $sql = "
            SELECT p.discussion, MAX(p.id)
              FROM {" . self::TABLE . "} p
              JOIN (
                SELECT mp.discussion, MAX(mp.created) AS created
                  FROM {" . self::TABLE . "} mp
                 WHERE mp.discussion {$insql}
              GROUP BY mp.discussion
              ) lp ON lp.discussion = p.discussion AND lp.created = p.created
          GROUP BY p.discussion";

        return $this->get_db()->get_records_sql_menu($sql, $params);
    }
}
