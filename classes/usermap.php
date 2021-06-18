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
    private static $debug;
    private static $isloaded = false;
    private static $token;
    private static $userinfo;
    private static $usermap;

    public static function __load($userinfo = "") {
        if (!empty(\local_webuntis\locallib::cache_get('session', 'fakemode'))) return;
        global $DB, $USER;
        global $debug; self::$debug = $debug;

        if (!empty($userinfo)) {
            self::$userinfo = $userinfo;
            if (empty(self::$userinfo->id_token)) redirect(\local_webuntis\tenant::get_init_url());
            self::$token = self::extract_token(self::$userinfo->id_token);

            if (self::$debug) {
                echo "Userinfo:<pre>" . print_r(self::$userinfo, 1) . "</pre>";
                echo "Token:<pre>" . print_r(self::$token, 1) . "</pre>";
            }

            if (!empty(self::$token->sub)) {
                $params = array(
                    'tenant_id' => \local_webuntis\tenant::get_tenant_id(),
                    'remoteuserid' => self::$token->sub
                );
                self::$usermap = $DB->get_record('local_webuntis_usermap', $params);
                if (empty(self::$usermap->id) && !empty(self::$token->sub)) {
                    self::$usermap = (object) array(
                        'tenant_id' => \local_webuntis\tenant::get_tenant_id(),
                        'school' => \local_webuntis\tenant::get_school(),
                        'remoteuserid' => self::$token->sub,
                        'remoteuserrole' => 'unknown',
                        'timecreated' => time(),
                        'timemodified' => time(),
                        'lastaccess' => time(),
                        'userinfo' => json_encode(self::$userinfo, JSON_NUMERIC_CHECK),
                    );
                    self::$usermap->id = $DB->insert_record('local_webuntis_usermap', self::$usermap);
                } else {
                    $DB->set_field('local_webuntis_usermap', 'lastaccess', time(), $params);
                    $DB->set_field('local_webuntis_usermap', 'userinfo', json_encode(self::$userinfo, JSON_NUMERIC_CHECK), $params);
                }

                // ATTENTION: In this section you must not call functions like ::get_id, this will cause a loop.
                // Try to receive the users role.
                $path = "https://api-integration.webuntis.com/ims/oneroster/v1p1/users/" . self::$token->sub;
                if (self::$debug) echo "Path $path<br />";
                //$postparams = [ 'access_token' => "$userinfo->token_type $userinfo->id_token" ];
                $postparams = [ 'access_token' => "$userinfo->access_token" ];
                $headerparams = [ 'Authorization' => "$userinfo->token_type $userinfo->id_token" ];
                //$headerparams = [ 'Authorization' => "$userinfo->access_token" ];
                if (self::$debug) echo "Getuser (via header):<br /><pre>" . print_r($headerparams, 1) . "</pre>";
                $getuser = \local_webuntis\locallib::curl($path, [], $headerparams);
                $getuser = json_decode($getuser);
                if (self::$debug) echo "<pre>" . print_r($getuser, 1) . "</pre>";

                if (!empty($getuser->identifier)) {
                    self::save_user($getuser);
                }

                $foundrole = !empty($getuser->role) ? $getuser->role : 'student';

                if (self::$usermap->remoteuserrole != $foundrole) {
                    self::$usermap->remoteuserrole = $foundrole;
                    global $DB;
                    $DB->set_field('local_webuntis_usermap', 'remoteuserrole', $foundrole, array('id' => self::$usermap->id));
                }
            }
        } else {
            self::$usermap = \local_webuntis\locallib::cache_get('session', 'usermap');
            if (!empty(self::$usermap) && !empty(self::$usermap->userinfo)) {
                self::$userinfo = json_decode(self::$usermap->userinfo);
                if (!empty(self::$userinfo->id_token)) {
                    self::$token = self::extract_token(self::$userinfo->id_token);
                }
            }
        }
        self::$isloaded = true;
        self::set_cache();
        // Ensure the user is logged in.
        if (!empty(self::$usermap->userid)) {
            self::do_userlogin();
            if (\local_webuntis\usermap::is_administrator()) {
                \local_webuntis\orgmaps::load_from_eduvidual();
            }
        } else {
            if ($_SERVER['PHP_SELF'] != '/local/webuntis/landinguser.php') {
                $url = new \moodle_url('/local/webuntis/landinguser.php', array());
                redirect($url);
            }
        }
    }
    /**
     * Checks if enough profile data is present from webuntis for
     * creation of a user account.
     */
    public static function check_data_prior_usercreate() {
        self::is_loaded();
        if (empty(self::get_firstname())) return false;
        if (empty(self::get_lastname())) return false;
        if (!\local_webuntis\locallib::uses_eduvidual()) {
            if (empty(self::get_email())) return false;
            if (empty(self::get_username())) return false;
        }
        return true;
    }
    /**
     * Do the user login based on the "sub"-value.
     * @param sub user-identificator in webuntis.
     */
    private static function do_userlogin() {
        global $DB, $USER;

        if (!empty(self::$usermap->userid) && self::$usermap->userid != $USER->id) {
            \local_webuntis\locallib::cache_preserve(true);
            $user = \core_user::get_user(self::$usermap->userid);
            \complete_user_login($user);
            \local_webuntis\locallib::cache_preserve(false);
            redirect(\local_webuntis\tenant::get_init_url());
        } else {
            if (!isloggedin() || isguestuser()) {
                require_login();
            } else {
                self::$usermap->userid = $USER->id;
                $DB->set_field('local_webuntis_usermap', 'userid', $USER->id, array('id' => self::$usermap->id));
            }
        }
    }
    private static function extract_token($strtoken) {
        return json_decode(
            base64_decode(
                str_replace(
                    '_', '/', str_replace(
                        '-','+', explode('.', $strtoken)[1]
                    )
                )
            )
        );
    }
    public static function get_email() {
        self::is_loaded();
        if (empty(self::$usermap->email)) return;
        return self::$usermap->email;
    }
    public static function get_firstname() {
        self::is_loaded();
        if (empty(self::$usermap->firstname)) return;
        return self::$usermap->firstname;
    }
    public static function get_id() {
        self::is_loaded();
        if (empty(self::$usermap->id)) return;
        return self::$usermap->id;
    }
    public static function get_lastname() {
        self::is_loaded();
        if (empty(self::$usermap->lastname)) return;
        return self::$usermap->lastname;
    }
    public static function get_map_url() {
        return new \moodle_url('/local/webuntis/landinguser.php');
    }
    public static function get_userid() {
        self::is_loaded();
        if (empty(self::$usermap->userid)) return;
        return self::$usermap->userid;
    }
    public static function get_userinfo() {
        self::is_loaded();
        if (empty(self::$userinfo)) return;
        return self::$userinfo;
    }
    public static function get_usermap() {
        self::is_loaded();
        if (empty(self::$usermap)) return;
        return self::$usermap;
    }
    public static function get_username() {
        self::is_loaded();
        if (empty(self::$usermap->username)) return;
        return self::$usermap->username;
    }
    public static function get_remoteuserrole() {
        self::is_loaded();
        if (empty(self::$usermap->remoteuserrole)) return;
        return self::$usermap->remoteuserrole;
    }
    public static function get_token() {
        self::is_loaded();
        if (empty(self::$token)) return;
        return self::$token;
    }
    public static function is_loaded() {
        if (!self::$isloaded) self::__load();
    }
    public static function is_parent() {
        self::is_loaded();
        return (self::get_remoteuserrole() == 'parent');
    }
    public static function is_student() {
        self::is_loaded();
        return (self::get_remoteuserrole() == 'student');
    }
    public static function is_teacher() {
        self::is_loaded();
        return (self::get_remoteuserrole() == 'teacher');
    }
    public static function is_administrator() {
        self::is_loaded();
        return (self::get_remoteuserrole() == 'administrator');
    }

    public static function release() {
        self::is_loaded();
        global $DB;
        self::$usermap->userid = 0;
        $DB->set_field('local_webuntis_usermap', 'userid', 0, array('id' => self::get_id()));
        self::set_cache();
    }

    /**
     * Store a webuntis user to our database.
     * @param user object.
     */
    private static function save_user($user) {
        if (empty($user->identifier)) return;
        global $DB;

        $dbparams = [
            'tenant_id' => \local_webuntis\tenant::get_tenant_id(),
            'remoteuserid' => $user->identifier,
        ];
        $usermap = $DB->get_record('local_webuntis_usermap', $dbparams);

        if (empty($usermap->id)) {
            $usermap = (object) [
                'tenant_id' => \local_webuntis\tenant::get_tenant_id(),
                'school' => \local_webuntis\tenant::get_school(),
                'remoteuserid' => $user->identifier,
                'timecreated' => time(),
            ];
            $usermap->id = $DB->insert_record('local_webuntis_usermap', $usermap);
        }
        $webuntisfields = [ 'role', 'username', 'givenName', 'familyName', 'email' ];
        $usermapfields = [ 'remoteuserrole', 'username', 'firstname', 'lastname', 'email' ];

        $changed = false;
        for ($a = 0; $a < count($webuntisfields); $a++) {
            if (!empty($user->{$webuntisfields[$a]}) && $user->{$webuntisfields[$a]} != $usermap->{$usermapfields[$a]}) {
                $usermap->{$usermapfields[$a]} = $user->{$webuntisfields[$a]};
                $changed = true;
            }
        }
        if ($changed) {
            $DB->update_record('local_webuntis_usermap', $usermap);
        }

        // Map user role.
        \local_webuntis\orgmaps::map_role($user);
    }

    /**
     * Ensures all data is written to cache.
     */
    private static function set_cache() {
        \local_webuntis\locallib::cache_set('session', 'token', self::$token);
        \local_webuntis\locallib::cache_set('session', 'usermap', self::$usermap);
        \local_webuntis\locallib::cache_set('session', 'userinfo', self::$userinfo);
    }

    /**
     * Set the current user in this usermap.
     */
    public static function set_userid() {
        self::is_loaded();
        global $DB, $USER;
        self::$usermap->userid = $USER->id;
        $DB->set_field('local_webuntis_usermap', 'userid', self::$usermap->userid, array('id' => self::get_id()));
        self::set_cache();
    }

    public static function sync($chance = 1) {
        $userinfo = self::get_userinfo();
        $token = self::get_token();
        $path = "https://api-integration.webuntis.com/ims/oneroster/v1p1/users";
        if (self::$debug) echo "Path $path<br />";
        $headerparams = [
            'Authorization' => "$userinfo->token_type $userinfo->id_token",
        ];
        if (self::$debug) echo "<pre>" . print_r($headerparams, 1) . "</pre>";
        $getuser = \local_webuntis\locallib::curl($path, [], $headerparams);
        $getuser = json_decode($getuser);
        if (self::$debug) echo "<pre>" . print_r($getuser, 1) . "</pre>";

        if (!empty($getuser->users)) {
            foreach ($getuser->users as $user) {
                self::save_user($user);
            }
        } else {
            if (!empty($getuser[0]) && !empty($getuser[0]->errorCode)) {
                switch ($getuser[0]->errorCode) {
                    case 401: // token expired.
                        if (self::$debug) echo "TOKEN IS EXPIRED\n";
                        \local_webuntis\tenant::auth_token();
                        if ($chance == 1) {
                            if (self::$debug) echo "SECOND CHANCE\n";
                            self::sync(2);
                        }
                    break;
                }
            }
        }
    }
}
