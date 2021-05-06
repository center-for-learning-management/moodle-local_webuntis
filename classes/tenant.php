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
    private static $tenant;

    public static function __load($tenant_id, $school = "") {
        global $DB;
        $sql = "SELECT *
            FROM {local_webuntis_tenant}
            WHERE tenant_id = :tenant_id
                OR school LIKE :school";
        $params = [ 'school' => $school, 'tenant_id' => $tenant_id ];
        self::$tenant = $DB->get_record_sql($sql, $params);

        if (empty(self::$tenant->id)) {
            self::$tenant = (object) $params;
            self::$tenant->client = optional_param('client', '', PARAM_ALPHANUM);
            self::$tenant->consumerkey = optional_param('consumerkey', '', PARAM_ALPHANUM);
            self::$tenant->consumersecret = optional_param('consumersecret', '', PARAM_ALPHANUM);
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
    }

    /**
     * Ensure the user was authenticated against WebUntis.
     */
    public static function auth() {
        global $CFG, $PAGE;
        $endpoints = self::get_endpoints();
        if (empty($endpoints->authorization_endpoint)) {
            throw new \moodle_exception('endpointmissing', 'local_webuntis', $CFG->wwwroot);
        }
        $uuid = self::get_uuid();
        if (empty($uuid)) {
            $path = $endpoints->authorization_endpoint;
            $path .= '/?response_type=code';
            $path .= '&scope=openid';
            $path .= '&client_id=' . self::get_client();
            $path .= '&school=' . self::get_school(true);
            $path .= '&redirect_url=' . $CFG->wwwroot . '/local/webuntis/index.php';
            redirect($path);
        }

        global $USER;



    }
    private static function get_endpoints() {
        $endpoints = \local_webuntis\locallib::cache_get('application', 'endpoints-' . self::get_tenant_id());
        if (empty($endpoints)) {
            $host = self::get_host();
            $school = self::get_school(true);
            $path = "https://$host.webuntis.com/WebUntis/api/sso/$school/.well-known/openid-configuration";
            $endpoints = json_decode(\local_webuntis\locallib::curl($path));
            \local_webuntis\locallib::cache_set('application', 'endpoints-' . self::get_tenant_id(), $endpoints);
        }
        return $endpoints;
    }

    public static function get_id() {
        return self::$tenant->id;
    }
    public static function get_client() {
        return self::$tenant->client;
    }
    public static function get_consumerkey() {
        return self::$tenant->consumerkey;
    }
    public static function get_consumersecret() {
        return self::$tenant->consumersecret;
    }
    public static function get_host() {
        return self::$tenant->host;
    }
    public static function get_school($lcase = false) {
        if ($lcase) return strtolower(self::$tenant->school);
        else return self::$tenant->school;
    }
    public static function get_tenant_id() {
        return self::$tenant->tenant_id;
    }
    /**
     * @return the webuntis users uuid.
     */
    public static function get_uuid() {
        return \local_webuntis\locallib::cache_get('session', 'uuid');
    }


    public static function set_oauth_keys($consumerkey, $consumersecret) {
        global $DB;
        self::$tenant->consumerkey = $consumerkey;
        self::$tenant->consumersecret = $consumersecret;
        $DB->update_record('local_webuntis_tenant', self::$tenant);
    }
    /**
     * Set the webuntis uuid for this session.
     * @param uuid of webuntis.
     */
    public static function static_uuid($uuid) {
        \local_webuntis\locallib::cache_set('session', 'uuid', $uuid);
    }
}
