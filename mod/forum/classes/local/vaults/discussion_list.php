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
 * Vault class for a discussion list.
 *
 * @package    mod_forum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum\local\vaults;

defined('MOODLE_INTERNAL') || die();

use mod_forum\local\vault;
use mod_forum\local\vaults\preprocessors\extract_record as extract_record_preprocessor;
use mod_forum\local\vaults\preprocessors\extract_user as extract_user_preprocessor;
use mod_forum\local\renderers\discussion_list as discussion_list_renderer;
use stdClass;

/**
 * Discussion list vault.
 *
 * @package    mod_forum
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 */
class discussion_list extends vault {
    private const TABLE = 'forum_discussions';
    private const FIRST_AUTHOR_ID_ALIAS = 'userpictureid';
    private const FIRST_AUTHOR_ALIAS = 'userrecord';
    private const LATEST_AUTHOR_ID_ALIAS = 'userpictureid';
    private const LATEST_AUTHOR_ALIAS = 'userrecord';

    protected function get_table_alias() : string {
        return 'd';
    }

    protected function generate_get_records_sql(string $wheresql = null, ?string $sortsql = null) : string {
        $alias = $this->get_table_alias();
        $db = $this->get_db();

        // Fetch:
        // - Discussion
        // - First post
        // - Author
        // - Most recent editor
        $tablefields = $db->get_preload_columns(self::TABLE, $alias);
        $postfields = $db->get_preload_columns('forum_posts', 'p_');
        $firstauthorfields = \user_picture::fields('fa', null, self::FIRST_AUTHOR_ID_ALIAS, self::FIRST_AUTHOR_ALIAS);
        $latestuserfields = \user_picture::fields('la', null, self::LATEST_AUTHOR_ID_ALIAS, self::LATEST_AUTHOR_ALIAS);

        $fields = implode(', ', [
            $db->get_preload_columns_sql($tablefields, $alias),
            $db->get_preload_columns_sql($postfields, 'fp'),
            $firstauthorfields,
            $latestuserfields,
        ]);


        $tables = '{' . self::TABLE . '} ' . $alias;
        $tables .= ' JOIN {user} fa ON fa.id = ' . $alias . '.userid';
        $tables .= ' JOIN {user} la ON la.id = ' . $alias . '.usermodified';
        $tables .= ' JOIN {forum_posts} fp ON fp.id = ' . $alias . '.firstpost';

        $selectsql = 'SELECT ' . $fields . ' FROM ' . $tables;
        $selectsql .= $wheresql ? ' WHERE ' . $wheresql : '';
        $selectsql .= $sortsql ? ' ORDER BY ' . $sortsql : '';

        return $selectsql;
    }

    protected function get_preprocessors() : array {
        return array_merge(
            parent::get_preprocessors(),
            [
                'discussion' => new extract_record_preprocessor($this->get_db(), self::TABLE, $this->get_table_alias()),
                'firstpost' => new extract_record_preprocessor($this->get_db(), 'forum_posts', 'p_'),
                'firstpostauthor' => new extract_user_preprocessor(self::FIRST_AUTHOR_ID_ALIAS, self::FIRST_AUTHOR_ALIAS),
                'latestpostauthor' => new extract_user_preprocessor(self::LATEST_AUTHOR_ID_ALIAS, self::LATEST_AUTHOR_ALIAS),
            ]
        );
    }

    protected function from_db_records(array $results) {
        $entityfactory = $this->get_entity_factory();

        return array_map(function(array $result) use ($entityfactory) {
            [
                'discussion' => $discussion,
                'firstpost' => $firstpost,
                'firstpostauthor' => $firstpostauthor,
                'latestpostauthor' => $latestpostauthor,
            ] = $result;
            return $entityfactory->get_discussion_summary_from_stdClass(
                $discussion,
                $firstpost,
                $firstpostauthor,
                $latestpostauthor
            );
        }, $results);
    }

    public function get_sort_order(?int $sortmethod) : string {
        global $CFG;

        $alias = $this->get_table_alias();

        // TODO consider user favourites...
        $keyfield = "{$alias}.timemodified";
        $direction = "DESC";

        if ($sortmethod == discussion_list_renderer::SORTORDER_OLDEST_FIRST) {
            $direction = "ASC";
        }

        if (!empty($CFG->forum_enabletimedposts)) {
            $keyfield = "CASE WHEN {$keyfield} < {$alias}.timestart THEN {$alias}.timestart ELSE {$keyfield} END";
        }

        return "{$alias}.pinned DESC, {$keyfield} {$direction}";
    }

    /**
     * Fetch any required SQL to respect timed posts.
     *
     * @param   bool        $includehiddendiscussions Whether to include hidden discussions or not
     * @param   int         $includepostsforuser Which user to include posts for, if any
     * @return  array       The SQL and parameters to include
     */
    protected function get_hidden_post_sql(bool $includehiddendiscussions, ?int $includepostsforuser) {
        $wheresql = '';
        $params = [];
        if (!$includehiddendiscussions) {
            $now = time();
            $wheresql = " AND ((d.timestart <= :timestart AND (d.timeend = 0 OR d.timeend > :timeend))";
            $params['timestart'] = $now;
            $params['timeend'] = $now;
            if (null !== $includepostsforuser) {
                $wheresql .= " OR d.userid = :byuser";
                $params['byuser'] = $includepostsforuser;
            }
            $wheresql .= ")";
        }

        return [
            'wheresql' => $wheresql,
            'params' => $params,
        ];
    }

    /**
     * Get each discussion, first post, first and last post author for the given forum, considering timed posts, and
     * pagination.
     *
     * @param   int         $forumid The forum to fetch the discussion set for
     * @param   bool        $includehiddendiscussions Whether to include hidden discussions or not
     * @param   int         $includepostsforuser Which user to include posts for, if any
     * @param   int         $sortorder The sort order to use
     * @param   int         $limit The number of discussions to fetch
     * @param   int         $offset The record offset
     * @return  array       The set of data fetched
     */
    public function get_from_forum_id(int $forumid, bool $includehiddendiscussions, int $includepostsforuser, ?int $sortorder, int $limit, int $offset) {
        $alias = $this->get_table_alias();
        $wheresql = "{$alias}.forum = :forumid";
        [
            'wheresql' => $hiddensql,
            'params' =>  $hiddenparams
        ] = $this->get_hidden_post_sql($includehiddendiscussions, $includepostsforuser);
        $wheresql .= $hiddensql;

        $params = array_merge($hiddenparams, [
            'forumid' => $forumid,
        ]);

        $sql = $this->generate_get_records_sql($wheresql, $this->get_sort_order($sortorder));
        $records = $this->get_db()->get_records_sql($sql, $params, $offset, $limit);

        return $this->transform_db_records_to_entities($records);
    }

    /**
     * Get each discussion, first post, first and last post author for the given forum, and the set of groups to display
     * considering timed posts, and pagination.
     *
     * @param   int         $forumid The forum to fetch the discussion set for
     * @param   int[]       $groupids The list of real groups to filter on
     * @param   bool        $includehiddendiscussions Whether to include hidden discussions or not
     * @param   int         $includepostsforuser Which user to include posts for, if any
     * @param   int         $sortorder The sort order to use
     * @param   int         $limit The number of discussions to fetch
     * @param   int         $offset The record offset
     * @return  array       The set of data fetched
     */
    public function get_from_forum_id_and_group_id(int $forumid, array $groupids, bool $includehiddendiscussions, int $includepostsforuser, ?int $sortorder, int $limit, int $offset) {
        $alias = $this->get_table_alias();

        $wheresql = "{$alias}.forum = :forumid AND ";
        $groupparams = [];
        if (empty($groupids)) {
            $wheresql .= "{$alias}.groupid = :allgroupsid";
        } else {
            list($insql, $groupparams) = $this->get_db()->get_in_or_equal($groupids, SQL_PARAMS_NAMED, 'gid');
            $wheresql .= "({$alias}.groupid = :allgroupsid OR {$alias}.groupid {$insql})";
        }

        [
            'wheresql' => $hiddensql,
            'params' =>  $hiddenparams
        ] = $this->get_hidden_post_sql($includehiddendiscussions, $includepostsforuser);
        $wheresql .= $hiddensql;

        $params = array_merge($hiddenparams, $groupparams, [
            'forumid' => $forumid,
            'allgroupsid' => -1,
        ]);

        $sql = $this->generate_get_records_sql($wheresql, $this->get_sort_order($sortorder));
        $records = $this->get_db()->get_records_sql($sql, $params, $offset, $limit);

        return $this->transform_db_records_to_entities($records);
    }

    /**
     * Get a mapping of the total number of posts to the specified discussions.
     *
     * @param   int[]       $discussionids The list of discussions to fetch counts for
     * @return  int[]       The number of replies for each discussion returned in an associative array
     */
    public function get_post_count_for_discussion_ids(array $discussionids) : array {
       if (empty($discussionids)) {
            return [];
        }
        list($insql, $params) = $this->get_db()->get_in_or_equal($discussionids);
        $sql = "SELECT discussion, COUNT(1) FROM {forum_posts} p WHERE p.discussion {$insql} GROUP BY discussion";

        return $this->get_db()->get_records_sql_menu($sql, $params);
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
        $sql = "SELECT discussion, COUNT(1) FROM {forum_posts} p WHERE p.discussion {$insql} AND p.parent > 0 GROUP BY discussion";

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
        $sql = "SELECT p.discussion, COUNT(p.id) FROM {forum_posts} p
             LEFT JOIN {forum_read} r ON r.postid = p.id AND r.userid = :userid
                 WHERE p.discussion {$insql} AND p.modified > :cutofftime AND r.id IS NULL
              GROUP BY p.discussion";

        $params['userid'] = $user->id;
        $params['cutofftime'] = floor((new \DateTime())
            ->sub(new \DateInterval("P{$CFG->forum_oldpostdays}D"))
            ->format('U') / 60) * 60;

        return $this->get_db()->get_records_sql_menu($sql, $params);
    }
}
