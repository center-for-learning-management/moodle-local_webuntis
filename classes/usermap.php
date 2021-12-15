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
    private static $userinfos;
    private static $usermaps;
    private $userinfo;
    private $usermap;

    /**
     * Load the usermap.
     * @param userinfo userinfo grabbed from webuntis.
     * @param requirelogin require the user to be logged in in webuntis.
     */
    public function __construct($userinfo = null, $requirelogin = true) {
        global $DB, $TENANT;

        $requirelogin = false;

        if (empty(self::$userinfos)) {
            self::$userinfos = \local_webuntis\locallib::cache_get('session', 'userinfos');
        }
        if (!empty(self::$userinfos[$TENANT->get_tenant_id()])) {
            $this->userinfo = self::$userinfos[$TENANT->get_tenant_id()];
        } else {
            $this->userinfo = (object) [];
        }
        if (empty(self::$usermaps)) {
            self::$usermaps = \local_webuntis\locallib::cache_get('session', 'usermaps');
        }
        if (!empty(self::$usermaps[$TENANT->get_tenant_id()])) {
            $this->usermap = self::$usermaps[$TENANT->get_tenant_id()];
        } else {
            $this->usermap = (object) [];
        }

        if (!empty($userinfo)) {
            $this->set_userinfo($userinfo);
        }

        // Ensure the user is logged in.
        if (!empty($this->usermap->userid)) {
            $this->do_userlogin();
            if ($this->is_administrator()) {
                \local_webuntis\orgmap::load_from_eduvidual();
            }
        } elseif ($requirelogin) {
            //die("REQUIRE $requirelogin");
            if ($_SERVER['PHP_SELF'] != '/local/webuntis/landinguser.php') {
                $url = new \moodle_url('/local/webuntis/landinguser.php', array());
                redirect($url);
            }
        }
    }

    public function can_disconnect() {
        if (!empty($this->usermap->candisconnect)) {
            return $this->usermap->candisconnect;
        }
    }
    /**
     * Checks if enough profile data is present from webuntis for
     * creation of a user account.
     */
    public function check_data_prior_usercreate() {
        if (empty($this->get_firstname())) {
            return false;
        }
        if (empty($this->get_lastname())) {
            return false;
        }
        if (empty($this->get_email())) {
            return false;
        }
        if (empty($this->get_username())) {
            return false;
        }
        return true;
    }
    /**
     * Do the user login based on the "sub"-value.
     * @param sub user-identificator in webuntis.
     */
    private function do_userlogin() {
        global $DB, $TENANT, $USER;

        if (!empty($this->get_userid()) && $this->get_userid() != $USER->id) {
            \local_webuntis\locallib::cache_preserve(true);
            $user = \core_user::get_user($this->get_userid());
            \complete_user_login($user);
            \local_webuntis\locallib::cache_preserve(false);
            redirect($TENANT->get_init_url());
        } else {
            if (!isloggedin() || isguestuser()) {
                require_login();
            } else {
                $this->usermap->userid = $USER->id;
                $DB->set_field('local_webuntis_usermap', 'userid', $USER->id, array('id' => $this->usermap->id));
            }
        }
    }
    public static function extract_token($strtoken) {
        return json_decode(
            base64_decode(
                str_replace(
                    '_', '/', str_replace(
                        '-', '+', explode('.', $strtoken)[1]
                    )
                )
            )
        );
    }
    public static function from_database() {
        global $DB, $TENANT, $USER;
        if (empty(self::$usermaps[$TENANT->get_tenant_id()])) {
            self::$usermaps[$TENANT->get_tenant_id()] = $DB->get_record('local_webuntis_usermap', [ 'userid' => $USER->id, 'tenant_id' => $TENANT->get_tenant_id()]);
            \local_webuntis\locallib::cache_set('session', 'usermaps', self::$usermaps);
        }
        return self::$usermaps[$TENANT->get_tenant_id()];
    }
    public function get_email() {
        if (!empty($this->usermap->email)) {
            return $this->usermap->email;
        }
    }
    public function get_firstname() {
        if (!empty($this->usermap->firstname)) {
            return $this->usermap->firstname;
        }
    }
    public function get_id() {
        if (!empty($this->usermap->id)) {
            return $this->usermap->id;
        }
    }
    public function get_headerparams() {
        return [ 'Authorization' => $this->userinfo->token_type . ' ' .  $this->userinfo->id_token ];
    }
    public function get_lastname() {
        if (!empty($this->usermap->lastname)) {
            return $this->usermap->lastname;
        }
    }
    public static function get_map_url() {
        return new \moodle_url('/local/webuntis/landinguser.php');
    }
    /**
     * Return webuntis role as Moodle-roleid.
     * @param webuntisrole if empty use remoteuserrole of usermap.
     */
    public function get_moodlerole($webuntisrole = 0) {
        if (empty($webuntisrole)) {
            $webuntisrole = $this->get_remoteuserrole();
        }
        // @Todo configure prefereed roles in settings.php
        $webuntisroles = [ 'student', 'parent', 'teacher', 'administrator' ];
        $moodleroles = [ 5, 5, 3, 3];
        for ($a = 0; $a < count($webuntisroles); $a++) {
            if ($webuntisroles[$a] == $webuntisrole) {
                return $moodleroles[$a];
            }
        }
        return 0;
    }
    public function get_userid() {
        if (!empty($this->usermap->userid)) {
            return $this->usermap->userid;
        }
    }
    public function get_userinfo() {
        if (!empty($this->userinfo)) {
            return $this->userinfo;
        }
    }
    public function get_usermap() {
        if (!empty($this->usermap)) {
            return $this->usermap;
        }
    }
    public function get_username() {
        if (!empty($this->usermap->username)) {
            return $this->usermap->username;
        }
    }
    public function get_remoteuserid() {
        if (!empty($this->usermap->remoteuserid)) {
            return $this->usermap->remoteuserid;
        }
    }
    public function get_remoteuserrole() {
        if (!empty($this->usermap->remoteuserrole)) {
            return $this->usermap->remoteuserrole;
        }
    }
    public function get_token() {
        if (!empty($this->token)) {
            return $this->token;
        }
    }
    public function is_parent() {
        return ($this->get_remoteuserrole() == 'parent');
    }
    public function is_student() {
        return ($this->get_remoteuserrole() == 'student');
    }
    public function is_teacher() {
        return ($this->get_remoteuserrole() == 'teacher');
    }
    public function is_administrator() {
        return ($this->get_remoteuserrole() == 'administrator');
    }
    public function release() {
        global $DB, $TENANT;
        $this->usermap->userid = 0;
        $DB->set_field('local_webuntis_usermap', 'userid', 0, array('id' => $this->get_id()));
    }
    /**
     * Store a webuntis user to our database.
     * @param user object.
     */
    private static function save_user($user) {
        if (empty($user->identifier)) {
            return;
        }
        global $DB, $TENANT;

        $dbparams = [
            'tenant_id' => $TENANT->get_tenant_id(),
            'remoteuserid' => $user->identifier,
        ];
        $usermap = $DB->get_record('local_webuntis_usermap', $dbparams);

        if (empty($usermap->id)) {
            $usermap = (object) [
                'tenant_id' => $TENANT->get_tenant_id(),
                'school' => $TENANT->get_school(),
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
        \local_webuntis\orgmap::map_role($user);
    }

    /**
     * Set the current user in this usermap.
     * @param userid if empty use $USER
     */
    public function set_userid($userid = 0) {
        global $DB, $TENANT, $USER;
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $this->usermap->userid = $userid;
        $DB->set_field('local_webuntis_usermap', 'userid', $userid, array('id' => $this->get_id()));
        $TENANT->touch();
    }

    public function set_userinfo($userinfo) {
        global $debug, $DB, $TENANT, $USER;

        $this->userinfo = $userinfo;
        if (empty($this->userinfo->id_token)) {
            redirect($TENANT->get_init_url());
        }
        $token = self::extract_token($this->userinfo->id_token);

        if ($debug) {
            echo "Userinfo:<pre>" . print_r($this->userinfo, 1) . "</pre>";
            echo "Token:<pre>" . print_r($token, 1) . "</pre>";
        }

        if (!empty($token->sub)) {
            $params = array(
                'tenant_id' => $TENANT->get_tenant_id(),
                'remoteuserid' => $token->sub
            );
            $this->usermap = $DB->get_record('local_webuntis_usermap', $params);
            if (empty($this->usermap->id) && !empty($token->sub)) {
                $this->usermap = (object) array(
                    'tenant_id' => $TENANT->get_tenant_id(),
                    'school' => $TENANT->get_school(),
                    'remoteuserid' => $token->sub,
                    'remoteuserrole' => 'unknown',
                    'timecreated' => time(),
                    'timemodified' => time(),
                    'lastaccess' => time(),
                    'userinfo' => json_encode($this->userinfo, JSON_NUMERIC_CHECK),
                );
                $this->usermap->id = $DB->insert_record('local_webuntis_usermap', $this->usermap);
            } else {
                $DB->set_field('local_webuntis_usermap', 'lastaccess', time(), $params);
                $DB->set_field('local_webuntis_usermap', 'userinfo', json_encode($this->userinfo, JSON_NUMERIC_CHECK), $params);
            }

            // ATTENTION: In this section you must not call functions like ::get_id, this will cause a loop.
            // Try to receive the users role.
            $integration = ($TENANT->get_host() == 'https://integration.webuntis.com') ? '-integration' : '';
            $path = "https://api$integration.webuntis.com/ims/oneroster/v1p1/users/" . $token->sub;
            if ($debug) {
                echo "Path $path<br />";
            }
            $headerparams = [ 'Authorization' => $this->userinfo->token_type . ' ' .  $this->userinfo->id_token ];
            if ($debug) {
                echo "Getuser (via header):<br /><pre>" . print_r($headerparams, 1) . "</pre>";
            }
            $getuser = \local_webuntis\locallib::curl($path, [], $headerparams);
            $getuser = json_decode($getuser);
            if (!empty($getuser->user)) {
                $getuser = $getuser->user;
            }
            if ($debug) {
                echo "<pre>" . print_r($getuser, 1) . "</pre>";
            }

            if (!empty($getuser->identifier)) {
                self::save_user($getuser);
            }

            $foundrole = !empty($getuser->role) ? $getuser->role : 'student';

            if ($this->usermap->remoteuserrole != $foundrole) {
                $this->usermap->remoteuserrole = $foundrole;
                global $DB;
                $DB->set_field('local_webuntis_usermap', 'remoteuserrole', $foundrole, array('id' => $this->usermap->id));
            }
        }

        $this->to_cache();
    }

    public function sync($chance = 1) {
        global $debug, $TENANT;

        $lastsync = \local_webuntis\locallib::cache_get('session', 'last_tenant_sync');
        if (!empty($lastsync) && $lastsync > (time() - 600)) {
            return;
        }

        $userinfo = $this->get_userinfo();
        $token = $this->get_token();
        $integration = ($TENANT->get_host() == 'https://integration.webuntis.com') ? '-integration' : '';
        $path = "https://api$integration.webuntis.com/ims/oneroster/v1p1/users";
        if ($debug) {
            echo "Path $path<br />";
        }
        $headerparams = [
            'Authorization' => "$userinfo->token_type $userinfo->id_token",
        ];
        if ($debug) {
            echo "<pre>" . print_r($headerparams, 1) . "</pre>";
        }
        $getuser = \local_webuntis\locallib::curl($path, [], $headerparams);
        $getuser = json_decode($getuser);
        if ($debug) {
            echo "<pre>" . print_r($getuser, 1) . "</pre>";
        }

        if (!empty($getuser->users)) {
            foreach ($getuser->users as $user) {
                $this->save_user($user);
            }
            \local_webuntis\locallib::cache_set('session', 'last_tenant_sync', time());
        } else {
            if (!empty($getuser[0]) && !empty($getuser[0]->errorCode)) {
                switch ($getuser[0]->errorCode) {
                    case 401: // token expired.
                        $TENANT->auth_token();
                        if ($chance == 1) {
                            $this->sync(2);
                        }
                    break;
                }
            }
        }
    }

    public function to_cache() {
        global $TENANT;
        self::$userinfos[$TENANT->get_tenant_id()] = $this->userinfo;
        self::$usermaps[$TENANT->get_tenant_id()]  = $this->usermap;
        \local_webuntis\locallib::cache_set('session', 'userinfos', self::$userinfos);
        \local_webuntis\locallib::cache_set('session', 'usermaps', self::$usermaps);
    }
}
