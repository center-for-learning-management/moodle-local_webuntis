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

namespace local_webuntis;

defined('MOODLE_INTERNAL') || die;

class orgmaps {
    private static $debug;
    private static $isloaded = false;
    private static $orgmaps;

    public static function load() {
        global $DB;
        global $debug; self::$debug = $debug;

        if (empty(\local_webuntis\tenant::get_tenant_id())) {
            return;
        }

        self::$orgmaps = \local_webuntis\locallib::cache_get('application', 'orgmaps-' . \local_webuntis\tenant::get_tenant_id());
        if (empty(self::$orgmaps) || count(self::$orgmaps) == 0) {
            self::load_from_db();
        }
    }

    /**
     * Convert webuntis role to eduvidual-role.
     * @param webuntisrole
     */
    public static function convert_role($webuntisrole) {
        $roles = [ 'student', 'parent', 'teacher', 'administrator' ];
        $role = $webuntisrole;
        if (!in_array($role, $roles)) {
            $role = 'student';
        }
        if ($role == 'administrator') {
            $role = 'Manager';
        } else {
            $role = ucfirst($role);
        }
        return $role;
    }

    public static function get_orgmaps() {
        self::is_loaded();
        return self::$orgmaps;
    }

    /**
     * Check if at least on orgmap allows autoenrol.
     */
    public static function has_autoenrol() {
        foreach (self::get_orgmaps() as $orgmap) {
            if (!empty($orgmap->autoenrol)) {
                return true;
            }
        }
        return false;
    }

    private static function is_loaded() {
        if (!self::$isloaded) {
            self::load();
        }
    }

    private static function load_from_db() {
        global $DB;
        $params = [ 'tenant_id' => \local_webuntis\tenant::get_tenant_id()];
        self::$orgmaps = array_values($DB->get_records('local_webuntis_orgmap', $params));

        \local_webuntis\locallib::cache_set('application', 'orgmaps-' . $params['tenant_id'], self::$orgmaps);
        self::$isloaded = true;
    }

    public static function load_from_eduvidual() {
        if (!\local_webuntis\locallib::uses_eduvidual()) {
            return;
        }
        if (empty(\local_webuntis\tenant::get_tenant_id())) {
            return;
        }
        global $DB;
        $orgs = \local_eduvidual\locallib::get_organisations('Manager', false);
        foreach ($orgs as $org) {
            $params = [ 'orgid' => $org->orgid, 'tenant_id' => \local_webuntis\tenant::get_tenant_id()];
            $orgmap = $DB->get_record('local_webuntis_orgmap', $params);
            if (empty($orgmap->id)) {
                $orgmap = (object)[
                    'autoenrol' => 0,
                    'orgid' => $org->orgid,
                    'tenant_id' => \local_webuntis\tenant::get_tenant_id(),
                ];
                $orgmap->id = $DB->insert_record('local_webuntis_orgmap', $orgmap);
            }
        }
        self::load_from_db();
    }

    public static function map_role($user) {
        if (!\local_webuntis\locallib::uses_eduvidual()) {
            return;
        }
        if (empty(\local_webuntis\tenant::get_tenant_id())) {
            return;
        }
        if (empty($user->identifier)) {
            return;
        }

        global $DB;
        $params = [
            'tenant_id' => \local_webuntis\tenant::get_tenant_id(),
            'remoteuserid' => $user->identifier,
        ];
        $usermap = $DB->get_record('local_webuntis_usermap', $params);
        if (empty($usermap->userid)) {
            return;
        }

        self::map_role_usermap($usermap);
    }
    /**
     * Map role based on usermap.
     * @param usermap
     */
    public static function map_role_usermap($usermap) {
        if (!\local_webuntis\locallib::uses_eduvidual()) {
            return;
        }
        if (empty(\local_webuntis\tenant::get_tenant_id())) {
            return;
        }
        if (empty($usermap->role)) {
            return;
        }
        $role = self::convert_role($usermap->role);
        foreach (self::get_orgmaps() as $orgmap) {
            if (!empty($orgmap->autoenrol)) {
                \local_eduvidual\lib_enrol::role_set($usermap->userid, $orgmap->orgid, $role);
            }
        }
    }
}
