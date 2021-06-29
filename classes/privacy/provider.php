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
 * @package    local_webuntis
 * @copyright  2021 Zentrum für Lernmanagement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_webuntis\privacy;
use core_privacy\local\metadata\collection;
use \core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die;

class provider implements
                    \core_privacy\local\metadata\provider,
                    \core_privacy\local\request\core_userlist_provider,
                    \core_privacy\local\request\plugin\provider {

    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'local_webuntis_usermap',
            [
                'tenant_id' => 'privacy:metadata:local_webuntis_usermap:tenant_id',
                'school' => 'privacy:metadata:local_webuntis_usermap:school',
                'remoteuserid' => 'privacy:metadata:local_webuntis_usermap:remoteuserid',
                'remoteuserrole' => 'privacy:metadata:local_webuntis_usermap:remoteuserrole',
                'userid' => 'privacy:metadata:local_webuntis_usermap:userid',
                'timecreated' => 'privacy:metadata:local_webuntis_usermap:timecreated',
                'lastaccess' => 'privacy:metadata:local_webuntis_usermap:lastaccess',
                'firstname' => 'privacy:metadata:local_webuntis_usermap:firstname',
                'lastname' => 'privacy:metadata:local_webuntis_usermap:lastname',
                'email' => 'privacy:metadata:local_webuntis_usermap:email',
                'userinfo' => 'privacy:metadata:local_webuntis_usermap:userinfo',
                'candisconnect' => 'privacy:metadata:local_webuntis_usermap:candisconnect',
            ],
            'privacy:metadata:local_webuntis_usermap'
        );
        return $collection;
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $userids = $userlist->get_userids();
        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $sql = "userid {$userinsql}";
        $DB->delete_records_select('local_webuntis_usermap', $sql, $userinparams);
    }
    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // Data is only stored in global context.
    }
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        $DB->delete_records('local_webuntis_usermap', [ 'userid' => $userid ]);
    }
    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        $context = \context_system::instance();
        $mappings = $DB->get_records('local_webuntis_usermap', [ 'userid' => $userid ]);
        foreach ($mappings as $mapping) {
            writer::with_context($context)
                ->export_data([get_string('pluginname', 'local_webuntis'),get_string('usermapping','local_webuntis')], $mapping);
        }
    }
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        // Data is only stored in global context.
        $contextlist = new \core_privacy\local\request\contextlist();
        return $contextlist;
    }
    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        // Data is only stored in global context.
        $context = $userlist->get_context();

        if (!$context instanceof \context_system) {
            return;
        }
        $sql = "SELECT userid FROM {local_webuntis_usermap}";
        $userlist->add_from_sql('userid', $sql, []);
    }



}
