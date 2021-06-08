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
 * @author    Robert Schrenk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_webuntis;

defined('MOODLE_INTERNAL') || die;

class usermap {
    private static $userinfo;
    private static $usermap;
    private static $debug;

    public static function __load($userinfo) {
        global $debug; self::$debug = $debug;
        \local_webuntis\locallib::cache_set('session', 'userinfo', $userinfo);
        if (self::$debug) echo "Userinfo";
        if (self::$debug) echo "<pre>" . print_r($userinfo, 1) . "</pre>";
        if (self::$debug) echo "Token:<br />";
        $token = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $userinfo->id_token)[1]))));
        if (self::$debug) echo "<pre>" . print_r($token, 1) . "</pre>";

        \local_webuntis\locallib::cache_set('session', 'token', $token);
        self::do_userlogin($token->sub);

        /*
        // Try to receive the users role.
        $path = "https://api-integration.webuntis.com/ims/oneroster/v1p1/users"; ///$token->sub";
        echo "Path $path<br />";
        $postparams = [ 'access_token' => "$userinfo->token_type $userinfo->access_token" ];
        //$postparams = [ 'access_token' => "$userinfo->access_token" ];
        $headerparams = [ 'Authorization' => "$userinfo->token_type $userinfo->access_token" ];
        //$headerparams = [ 'Authorization' => "$userinfo->access_token" ];
        if (self::$debug) echo "Getuser (via header):<br /><pre>" . print_r($headerparams, 1) . "</pre>";
        $getuser = \local_webuntis\locallib::curl($path, [], $headerparams);
        $getuser = json_decode($getuser);
        if (self::$debug) echo "<pre>" . print_r($getuser, 1) . "</pre>";

        if (self::$debug) echo "Getuser (via post):<br /><pre>" . print_r($postparams, 1) . "</pre>";
        $getuser = \local_webuntis\locallib::curl($path, $postparams);
        $getuser = json_decode($getuser);
        if (self::$debug) echo "<pre>" . print_r($getuser, 1) . "</pre>";
        */

        // For tests we force a specific role.
        $foundrole = "Administrator";

        if (self::$usermap->remoteuserrole != $foundrole) {
            self::$usermap->remoteuserrole = $foundrole;
            global $DB;
            $DB->set_field('local_webuntis_usermap', 'remoteuserrole', $foundrole, array('id' => self::get_id()));
        }
    }

    /**
     * Do the user login based on the "sub"-value.
     * @param sub user-identificator in webuntis.
     */
    private static function do_userlogin($sub) {
        global $DB, $USER;

        $params = array('tenant_id' => \local_webuntis\tenant::get_tenant_id(), 'remoteuserid' => $sub);
        self::$usermap = $DB->get_record('local_webuntis_usermap', $params);
        if (empty(self::$usermap->id) && !empty($token->sub)) {
            self::$usermap = (object) array(
                'tenant_id' => self::get_tenant_id(),
                'school' => self::get_school(),
                'remoteuserid' => $token->sub,
                'remoteuserrole' => 'unknown',
                'timecreated' => time(),
                'timemodified' => time(),
                'lastaccess' => time(),
            );
            self::$usermap->id = $DB->insert_record('local_webuntis_usermap', $usermap);
        } else {
            $DB->set_field('local_webuntis_usermap', 'lastaccess', time(), $params);
        }

        if (!empty(self::$usermap->userid)) {
            $user = \core_user::get_user(self::$usermap->userid);
            \complete_user_login($user);
        } else {
            if (!isloggedin() || isguestuser()) {
                require_login();
            } else {
                self::$usermap->userid = $USER->id;
                $DB->set_field('local_webuntis_usermap', 'userid', $USER->id, array('id' => self::$usermap->id));
            }
        }
    }

    public static function get_id() {
        return self::$usermap->id;
    }
    public static function get_userid() {
        return self::$usermap->userid;
    }
    public static function get_remoteuserrole() {
        return self::$usermap->remoteuserrole;
    }
    public static function is_parent() {
        return (self::$usermap->remoteuserrole == 'Parent');
    }
    public static function is_student() {
        return (self::$usermap->remoteuserrole == 'Student');
    }
    public static function is_teacher() {
        return (self::$usermap->remoteuserrole == 'Teacher');
    }
    public static function is_administrator() {
        return (self::$usermap->remoteuserrole == 'Administrator');
    }

    public static function release() {
        global $DB;
        self::$usermap->userid = 0;
        $DB->set_field('local_webuntis_usermap', 'userid', 0, array('id' => self::get_id()));
    }

}
