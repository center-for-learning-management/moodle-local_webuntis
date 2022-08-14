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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

class local_webuntis_external extends external_api {
    /**
     * Define parameters.
     */
    public static function autocreate_parameters() {
        return new external_function_parameters(array(
            'status' => new external_value(PARAM_INT, '1 or 0'),
        ));
    }
    /**
     * Toggle status.
     */
    public static function autocreate($status) {
        global $TENANT, $USERMAP;
        \local_webuntis\tenant::load();
        if (!$USERMAP->is_administrator()) {
            throw new \moodle_error('nopermission');
        }
        $params = self::validate_parameters(self::autocreate_parameters(), array('status' => $status));
        $params['status'] = $TENANT->set_autocreate($params['status']);
        return $params;
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function autocreate_returns() {
        return new external_single_structure(array(
            'status' => new external_value(PARAM_INT, 'current status'),
        ));
    }
    /**
     * Define parameters.
     */
    public static function autoenrol_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'the course id'),
            'status' => new external_value(PARAM_INT, '1 or 0'),
        ));
    }
    /**
     * Toggle status.
     */
    public static function autoenrol($courseid, $status) {
        global $DB, $TENANT, $USER;
        $params = self::validate_parameters(self::autoenrol_parameters(), array('courseid' => $courseid, 'status' => $status));

        \local_webuntis\tenant::load();
        $LESSONMAP = new \local_webuntis\lessonmap();

        if ($LESSONMAP->can_edit()) {
            foreach ($LESSONMAP->get_lessonmap() as $lessonmap) {
                if ($lessonmap->courseid == $params['courseid']) {
                    $lessonmap->autoenrol = $params['status'];
                    $DB->set_field('local_webuntis_coursemap', 'autoenrol', $params['status'], [ 'id' => $lessonmap->id ]);
                }
            }
            $params['canproceed'] = ($LESSONMAP->get_count() > 0) ? 1 : 0;
            $params['lesson_id'] = \local_webuntis\lessonmap::get_lesson_id();
            $params['tenant_id'] = $TENANT->get_tenant_id();
        } else {
            $params['canproceed'] = 0;
            $params['lesson_id'] = 0;
            $params['tenant_id'] = 0;
        }
        return $params;
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function autoenrol_returns() {
        return new external_single_structure(array(
            'canproceed' => new external_value(PARAM_INT, '1 if user can proceed'),
            'courseid' => new external_value(PARAM_INT, 'courseid or 0 if failed'),
            'lesson_id' => new external_value(PARAM_INT, 'the lesson id'),
            'status' => new external_value(PARAM_INT, 'current status'),
            'tenant_id' => new external_value(PARAM_INT, 'the tenant id'),
        ));
    }
    /**
     * Define parameters.
     */
    public static function selecttarget_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'the course id'),
            'status' => new external_value(PARAM_INT, '1 or 0'),
        ));
    }
    /**
     * Toggle status.
     */
    public static function selecttarget($courseid, $status) {
        global $DB, $TENANT, $USER;
        $params = self::validate_parameters(self::selecttarget_parameters(), array('courseid' => $courseid, 'status' => $status));

        \local_webuntis\tenant::load();
        $LESSONMAP = new \local_webuntis\lessonmap();

        if ($LESSONMAP->can_edit()) {
            $courseid = $params['courseid'];
            if ($params['status'] == 0) {
                $courseid = $courseid * -1;
            }
            $lessonmap = (object) $LESSONMAP->change_map($courseid);

            $params['autoenrol'] = $lessonmap->autoenrol;
            $params['canproceed'] = ($LESSONMAP->get_count() > 0) ? 1 : 0;
            $params['lesson_id'] = \local_webuntis\lessonmap::get_lesson_id();
            $params['tenant_id'] = $TENANT->get_tenant_id();
        } else {
            $params['autoenrol'] = 0;
            $params['canproceed'] = 0;
            $params['lesson_id'] = 0;
            $params['tenant_id'] = 0;
        }
        return $params;
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function selecttarget_returns() {
        return new external_single_structure(array(
            'autoenrol' => new external_value(PARAM_INT, '1 if autoenrol is enabled'),
            'canproceed' => new external_value(PARAM_INT, '1 if user can proceed'),
            'courseid' => new external_value(PARAM_INT, 'courseid or 0 if failed'),
            'lesson_id' => new external_value(PARAM_INT, 'the lesson id'),
            'status' => new external_value(PARAM_INT, 'current status'),
            'tenant_id' => new external_value(PARAM_INT, 'the tenant id'),
        ));
    }
    /**
     * Define parameters.
     */
    public static function tenantdata_parameters() {
        return new external_function_parameters(array(
            'tenant_id' => new external_value(PARAM_INT, 'the tenant_id'),
            'field' => new external_value(PARAM_TEXT, 'name of field'),
            'value' => new external_value(PARAM_TEXT, 'value of field'),
        ));
    }
    /**
     * Toggle status.
     */
    public static function tenantdata($tenant_id, $field, $value) {
        global $DB;
        $params = self::validate_parameters(
            self::tenantdata_parameters(),
            array(
                'tenant_id' => $tenant_id,
                'field' => $field,
                'value' => $value
            )
        );
        if (!is_siteadmin()) {
            throw new \moodle_exception('permission denied');
        }

        $fields = [ 'tenant_id', 'school', 'host', 'client', 'consumerkey', 'consumersecret' ];
        if (!in_array($params['field'], $fields)) {
            return [ 'status' => 0, 'message' => get_string('exception:invalid_field', 'local_webuntis') ];
        }

        $dbparams = [ 'tenant_id' => $params['tenant_id']];

        if ($field == 'tenant_id') {
            $tables = [ 'local_webuntis_tenant', 'local_webuntis_coursemap', 'local_webuntis_orgmap', 'local_webuntis_usermap' ];
            $exists = $DB->count_records('local_webuntis_tenant', [ 'tenant_id' => $params['value']]);
            if ($exists > 0) {
                return [ 'status' => 0, 'message' => get_string('exception:tenant_id_already_in_use', 'local_webuntis') ];
            }
        } else {
            $tables = [ 'local_webuntis_tenant' ];
        }

        try {
             $transaction = $DB->start_delegated_transaction();
             foreach ($tables as $table) {
                 $DB->set_field($table, $params['field'], $params['value'], $dbparams);
             }
             $transaction->allow_commit();
             return [ 'status' => 1, 'message' => '' ];
        } catch(Exception $e) {
             $transaction->rollback($e);
             throw new \moodle_exception('DB-Exception: ' . $e->getMessage());
        }
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function tenantdata_returns() {
        return new external_single_structure(array(
            'status' => new external_value(PARAM_INT, 'current status'),
            'message' => new external_value(PARAM_TEXT, 'a message'),
        ));
    }

    /**
     * Define parameters.
     */
    public static function usersync_create_parameters() {
        return new external_function_parameters(array(
            'remoteuserid' => new external_value(PARAM_TEXT, 'the remoteuserid'),
        ));
    }
    /**
     * Create the user if possible.
     */
    public static function usersync_create($remoteuserid) {
        global $CFG, $DB, $TENANT, $USERMAP;
        $params = self::validate_parameters(
            self::usersync_create_parameters(),
            array(
                'remoteuserid' => $remoteuserid,
            )
        );
        \local_webuntis\tenant::load();
        $useseduvidual = \local_webuntis\locallib::uses_eduvidual();
        if (!empty($useseduvidual)) {
            $orgs = \local_eduvidual\locallib::get_organisations('Manager', false);
        } else {
            $orgs = [];
        }

        if ($USERMAP->is_administrator()
                && (is_siteadmin()
                    || (!empty($useseduvidual) && \local_eduvidual\locallib::get_highest_role() == 'Manager')
                )) {
            $remoteuser = $DB->get_record('local_webuntis_usermap', $params);
            if (empty($remoteuser->email) || empty($remoteuser->firstname) || empty($remoteuser->email)) {
                throw new \moodle_exception('admin:usersync:missingdata', 'local_webuntis');
            }

            $sql = "SELECT id
                        FROM {user}
                        WHERE username LIKE ? OR email LIKE ?";
            $chk = $DB->get_records_sql($sql, [ $remoteuser->email, $remoteuser->email ]);
            if (count($chk) > 0) {
                throw new \moodle_exception('exception:already_exists', 'local_webuntis');
            }

            require_once("$CFG->dirroot/user/lib.php");
            $u = (object) [
                'confirmed' => 1,
                'mnethostid' => 1,
                'username' => $remoteuser->email,
                'firstname' => $remoteuser->firstname,
                'lastname' => $remoteuser->lastname,
                'email' => $remoteuser->email,
                'auth' => 'manual',
                'password' => uniqid(rand(0,9999)),
            ];
            $u->id = \user_create_user($u);

            if (!empty($useseduvidual)) {
                \local_eduvidual\locallib::get_user_secret($u->id);
                \local_eduvidual\lib_enrol::choose_background($u->id);
            }

            $DB->set_field('local_webuntis_usermap', 'userid', $u->id, [ 'tenant_id' => $remoteuser->tenant_id, 'remoteuserid' => $remoteuser->remoteuserid ]);

            return [ 'message' => get_string('admin:usersync:created', 'local_webuntis'), 'userid' => $u->id ];
        } else {
            throw new \moodle_exception('exception:permission_denied', 'local_webuntis');
        }
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function usersync_create_returns() {
        return new external_single_structure(array(
            'message' => new external_value(PARAM_TEXT, 'message to show to user'),
            'userid' => new external_value(PARAM_INT, 'id of the created user'),
        ));
    }

    /**
     * Define parameters.
     */
    public static function usersync_purge_parameters() {
        return new external_function_parameters(array(
            'userid' => new external_value(PARAM_INT, 'the userid'),
            'orgid' => new external_value(PARAM_INT, 'the orgid (for eduvidual)'),
        ));
    }
    /**
     * Create the user if possible.
     */
    public static function usersync_purge($userid, $orgid) {
        global $CFG, $DB, $TENANT, $USERMAP;
        $params = self::validate_parameters(
            self::usersync_purge_parameters(),
            array(
                'userid' => $userid,
                'orgid' => $orgid,
            )
        );
        \local_webuntis\tenant::load();
        $useseduvidual = \local_webuntis\locallib::uses_eduvidual();

        if (is_siteadmin() ||
                $USERMAP->is_administrator() ||
                (
                    !empty($useseduvidual) &&
                    \local_eduvidual\locallib::get_orgrole($params['orgid']) == 'Manager'
                )
            ) {

            if ($useseduvidual) {
                \local_eduvidual\lib_enrol::role_set($params['userid'], $params['orgid'], 'remove');
            } else {
                require_once("$CFG->dirroot/user/lib.php");
                $user = \core_user::get_user($params['userid']);
                \user_delete_user($user);
            }

            return [ 'message' => get_string('admin:usersync:purged', 'local_webuntis'), 'status' => 1 ];
        } else {
            throw new \moodle_exception('exception:permission_denied', 'local_webuntis');
        }
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function usersync_purge_returns() {
        return new external_single_structure(array(
            'message' => new external_value(PARAM_TEXT, 'message to show to user'),
            'status' => new external_value(PARAM_INT, '1 for success, 0 for error'),
        ));
    }

}
