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

class tenant {
    private static $debug;
    private static $isloaded;
    private static $tenant;
    private static $usermap;

    public static function load($tenant_id = 0, $school = "") {
        global $debug; self::$debug = $debug;
        $tenant = \local_webuntis\locallib::cache_get('session', 'tenant');
        if (!empty($tenant->tenant_id)) {
            self::$tenant = $tenant;
        }

        if (!empty($tenant->tenant_id) && ($tenant->tenant_id == $tenant_id || empty($tenant_id))) {
            // Tenant was loaded from cache and we are done.
            self::$isloaded = true;
            return;
        }

        global $DB;
        $sql = "SELECT *
            FROM {local_webuntis_tenant}
            WHERE tenant_id = :tenant_id
                OR school LIKE :school";
        $params = [ 'school' => $school, 'tenant_id' => $tenant_id ];
        self::$tenant = $DB->get_record_sql($sql, $params);

        if (empty(self::$tenant->id) && !empty($tenant_id)) {
            self::$tenant = (object) $params;
            self::$tenant->client = optional_param('client', '', PARAM_TEXT);
            self::$tenant->consumerkey = optional_param('consumerkey', '', PARAM_TEXT);
            self::$tenant->consumersecret = optional_param('consumersecret', '', PARAM_TEXT);
            self::$tenant->autocreate = 0;
            self::$tenant->id = $DB->insert_record('local_webuntis_tenant', self::$tenant);
        }
        if (!empty(self::$tenant->id) && !empty($school) && self::$tenant->school != $school) {
            self::$tenant->school = $school;
            $DB->set_field('local_webuntis_tenant', 'school', $school, array('id' => self::$tenant->id));
        }

        if (!empty(self::$tenant->id) && empty(self::$tenant->host)) {
            global $_SERVER;
            self::$tenant->host = $_SERVER['HTTP_REFERER'];
            self::$tenant->host = str_replace('https://', '', self::$tenant->host);
            self::$tenant->host = str_replace('.webuntis.com', '', self::$tenant->host);
            self::$tenant->host = str_replace('/', '', self::$tenant->host);
            $DB->set_field('local_webuntis_tenant', 'host', self::$tenant->host, array('id' => self::$tenant->id));
        }

        \local_webuntis\locallib::cache_set('session', 'tenant', self::$tenant);
        self::$isloaded = true;
    }

    /**
     * Ensure the user was authenticated against WebUntis.
     */
    public static function auth() {
        self::is_loaded();
        global $CFG, $PAGE;
        $endpoints = self::get_endpoints();
        if (empty($endpoints->authorization_endpoint)) {
            throw new \moodle_exception('endpointmissing', 'local_webuntis', $CFG->wwwroot);
        }

        $uuid = self::get_uuid();
        if (empty($uuid)) {
            $code = optional_param('code', '', PARAM_TEXT);
            if (!empty($code)) {
                \local_webuntis\locallib::cache_set('session', 'code', $code);
                self::auth_token();
            } else {
                $url = new \moodle_url($endpoints->authorization_endpoint, [
                    'response_type' => 'code',
                    'scope' => 'openid roster-core.readonly',
                    'client_id' => self::get_client(),
                    'school' => self::get_school(true),
                    'redirect_uri' => $CFG->wwwroot . '/local/webuntis/index.php',
                ]);
                redirect($url);
            }

        }
    }

    public static function auth_token() {
        global $CFG;
        $code = \local_webuntis\locallib::cache_get('session', 'code');
        $endpoints = self::get_endpoints();
        $path = $endpoints->token_endpoint;
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => self::get_client(),
            'client_secret' => self::get_consumerkey(),
            'code' => $code,
            'redirect_uri' => $CFG->wwwroot . '/local/webuntis/index.php',
        ];
        if (self::$debug) {
            echo "calling $path using the following params<br />";
            echo "<pre>" . print_r($params, 1) . "</pre>";
        }

        $userinfo = \local_webuntis\locallib::curl($path, $params);

        if (!empty($userinfo)) {
            $userinfo = json_decode($userinfo);
            \local_webuntis\usermap::load($userinfo);
        }
    }


    public static function get_autocreate() {
        self::is_loaded();
        if (empty(self::$tenant->autocreate)) {
            return;
        }
        return self::$tenant->autocreate;
    }
    public static function get_client() {
        self::is_loaded();
        if (empty(self::$tenant->client)) {
            return;
        }
        return self::$tenant->client;
    }
    public static function get_consumerkey() {
        self::is_loaded();
        if (empty(self::$tenant->consumerkey)) {
            return;
        }
        return self::$tenant->consumerkey;
    }
    public static function get_consumersecret() {
        self::is_loaded();
        if (empty(self::$tenant->consumersecret)) {
            return;
        }
        return self::$tenant->consumersecret;
    }
    private static function get_endpoints() {
        self::is_loaded();
        $endpoints = \local_webuntis\locallib::cache_get('application', 'endpoints-' . self::get_tenant_id());
        if (empty($endpoints) || empty($endpoints->authorization_endpoint)) {
            $host = self::get_host();
            $school = self::get_school(true);
            if (empty($host) || empty($school)) {
                throw new \moodle_exception('invalid_webuntis_instance', 'local_webuntis', $CFG->wwwroot);
            }
            $path = "https://$host.webuntis.com/WebUntis/api/sso/$school/.well-known/openid-configuration";
            $endpoints = json_decode(\local_webuntis\locallib::curl($path));
            \local_webuntis\locallib::cache_set('application', 'endpoints-' . self::get_tenant_id(), $endpoints);
        }
        return $endpoints;
    }
    public static function get_host() {
        self::is_loaded();
        if (empty(self::$tenant->host)) {
            return;
        }
        return self::$tenant->host;
    }
    public static function get_id() {
        self::is_loaded();
        if (empty(self::$tenant->id)) {
            return;
        }
        return self::$tenant->id;
    }
    public static function get_init_url() {
        self::is_loaded();
        $params = [
            'tenant_id' => self::get_tenant_id(),
            'school' => self::get_school(),
            'lesson_id' => \local_webuntis\lessonmap::get_lesson_id(),
        ];
        return new \moodle_url('/local/webuntis/index.php', $params);
    }
    public static function get_school($lcase = false) {
        self::is_loaded();
        if ($lcase) {
            return strtolower(self::$tenant->school);
        } else {
            return self::$tenant->school;
        }
    }
    public static function get_tenant_id() {
        self::is_loaded();
        if (empty(self::$tenant->tenant_id)) {
            return;
        }
        return self::$tenant->tenant_id;
    }
    /**
     * @return the webuntis users uuid.
     */
    public static function get_uuid() {
        self::is_loaded();
        return \local_webuntis\locallib::cache_get('session', 'uuid');
    }

    public static function is_loaded() {
        if (!self::$isloaded) {
            return;
        }
    }

    public static function set_autocreate($to) {
        self::is_loaded();
        global $DB;
        if (empty(self::get_tenant_id())) {
            return;
        }

        if (!\local_webuntis\usermap::is_administrator()) {
            throw new \moodle_error('nopermission');
        }
        self::$tenant->autocreate = $to;
        $DB->set_field('local_webuntis_tenant', 'autocreate', $to, [ 'tenant_id' => self::get_tenant_id() ]);
        \local_webuntis\locallib::cache_set('session', 'tenant', self::$tenant);
        return $to;
    }

    public static function set_init_url() {
        $params = [
            'tenant_id' => self::get_tenant_id(),
            'school' => self::get_school(),
            'lesson_id' => \local_webuntis\lessonmap::get_lesson_id(),
        ];
        $_SESSION['webuntis_init_url'] = new \moodle_url('/local/webuntis/index.php', $params);
    }

    public static function set_oauth_keys($consumerkey, $consumersecret) {
        self::is_loaded();
        global $DB;
        self::$tenant->consumerkey = $consumerkey;
        self::$tenant->consumersecret = $consumersecret;
        $DB->update_record('local_webuntis_tenant', self::$tenant);
        \local_webuntis\locallib::cache_set('session', 'tenant', self::$tenant);
    }
    /**
     * Set the webuntis uuid for this session.
     * @param uuid of webuntis.
     */
    public static function static_uuid($uuid) {
        self::is_loaded();
        \local_webuntis\locallib::cache_set('session', 'uuid', $uuid);
    }
}
