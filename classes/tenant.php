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
    public $tenantdata;

    public static function last_tenant_id() {
        return \local_webuntis\locallib::cache_get('session', 'last_tenant_id');
    }

    public static function load($tenantid = 0) {
        global $CFG, $debug, $DB;

        if (empty($tenantid)) {
            $tenantid = \local_webuntis\locallib::cache_get('session', 'last_tenant_id');
        }
        if (empty($tenantid)) {
            \local_webuntis\locallib::cache_print(true);
            throw new \moodle_exception('invalidwebuntisinstance', 'local_webuntis', $CFG->wwwroot);
        }
        return new \local_webuntis\tenant($tenantid);
    }

    public function __construct($tenantid) {
        global $DB;

        $sql = "SELECT *
            FROM {local_webuntis_tenant}
            WHERE tenant_id = :tenant_id";
        $params = [ 'tenant_id' => $tenantid ];
        $this->tenantdata = $DB->get_record_sql($sql, $params);

        \local_webuntis\locallib::cache_set('session', 'last_tenant_id', $tenantid);
    }

    /**
     * Ensure the user was authenticated against WebUntis.
     */
    public function auth() {
        global $CFG, $PAGE;
        $endpoints = $this->get_endpoints();
        if (empty($endpoints->authorization_endpoint)) {
            throw new \moodle_exception('endpointmissing', 'local_webuntis', $CFG->wwwroot);
        }

        $uuid = self::get_uuid();
        if (empty($uuid)) {
            $code = optional_param('code', '', PARAM_TEXT);
            if (!empty($code)) {
                $this->auth_token($code);
            } else {
                $url = new \moodle_url($endpoints->authorization_endpoint, [
                    'response_type' => 'code',
                    'scope' => 'openid roster-core.readonly',
                    'client_id' => self::get_client(),
                    'redirect_uri' => $CFG->wwwroot . '/local/webuntis/index.php',
                ]);
                redirect($url);
            }
        }
    }

    public function auth_token($code) {
        global $CFG, $debug;
        $endpoints = $this->get_endpoints();
        $path = $endpoints->token_endpoint;
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->get_client(),
            'client_secret' => $this->get_consumerkey(),
            'code' => $code,
            'redirect_uri' => $CFG->wwwroot . '/local/webuntis/index.php',
        ];
        if ($debug) {
            echo "calling $path using the following params<br />";
            echo "<pre>" . print_r($params, 1) . "</pre>";
        }

        $userinfo = \local_webuntis\locallib::curl($path, $params);
        if (!empty($userinfo)) {
            $userinfo = json_decode($userinfo);
            $usermap = new \local_webuntis\usermap($userinfo);
        }
    }

    public function get_autocreate() {
        return $this->tenantdata->autocreate;
    }
    public function get_client() {
        return $this->tenantdata->client;
    }
    public function get_consumerkey() {
        return $this->tenantdata->consumerkey;
    }
    public function get_consumersecret() {
        return $this->tenantdata->consumersecret;
    }
    private function get_endpoints() {
        $endpoints = \local_webuntis\locallib::cache_get('application', 'endpoints-' . $this->get_tenant_id());
        if (empty($endpoints) || empty($endpoints->authorization_endpoint)) {
            $host = $this->get_host();
            $school = $this->get_school(false);
            if (empty($host) || empty($school)) {
                throw new \moodle_exception('invalid_webuntis_instance', 'local_webuntis', $CFG->wwwroot);
            }
            $path = "https://$host/WebUntis/api/sso/$school/.well-known/openid-configuration";
            $endpoints = json_decode(\local_webuntis\locallib::curl($path));
            \local_webuntis\locallib::cache_set('application', 'endpoints-' . $this->get_tenant_id(), $endpoints);
        }
        return $endpoints;
    }
    public function get_host() {
        return $this->tenantdata->host;
    }
    public function get_id() {
        return $this->tenantdata->id;
    }
    public function get_init_url() {
        $params = [
            'tenant_id' => $this->get_tenant_id(),
            'school' => $this->get_school(),
            'lesson_id' => \local_webuntis\lessonmap::get_lesson_id(),
        ];
        return new \moodle_url('/local/webuntis/index.php', $params);
    }
    public function get_school($lcase = false) {
        if ($lcase) {
            return strtolower($this->tenantdata->school);
        } else {
            return $this->tenantdata->school;
        }
    }
    public function get_tenant_id() {
        return $this->tenantdata->tenant_id;
    }
    /**
     * @return the webuntis users uuid.
     */
    public function get_uuid() {
        return \local_webuntis\locallib::cache_get('session', 'uuid_' . $this->tenantdata->id);
    }

    public function set_autocreate($to) {
        global $DB;
        $USERMAP = new \local_webuntis\usermap();
        if (!$USERMAP->is_administrator()) {
            throw new \moodle_error('nopermission');
        }
        $this->tenantdata->autocreate = $to;
        $DB->set_field('local_webuntis_tenant', 'autocreate', $to, [ 'tenant_id' => $this->get_tenant_id() ]);
        return $to;
    }

    public function set_oauth_keys($consumerkey, $consumersecret) {
        global $DB;
        $this->tenantdata->consumerkey = $consumerkey;
        $this->tenantdata->consumersecret = $consumersecret;
        $DB->update_record('local_webuntis_tenant', $this->tenantdata);
    }

    /**
     * Set the webuntis uuid for this session.
     * @param uuid of webuntis.
     */
    public function set_uuid($uuid) {
        \local_webuntis\locallib::cache_set('session', 'uuid_' . $this->tenantdata->id, $uuid);
    }

    /**
     * Indicates something has changed within this tenant, that requires invalidation of all chaches.
     */
    public function touch() {
        global $DB;
        $DB->set_field('local_webuntis_tenant', 'timemodified', time(), [ 'tenant_id' => $this->get_tenant_id() ]);
    }
}
