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
        global $CFG, $debug, $DB, $ORGMAP, $TENANT, $USERMAP;

        if (empty($tenantid)) {
            $tenantid = \local_webuntis\locallib::cache_get('session', 'last_tenant_id');
        }
        if (empty($tenantid)) {
            throw new \moodle_exception('invalidwebuntisinstance', 'local_webuntis', $CFG->wwwroot);
        }
        $TENANT = new \local_webuntis\tenant($tenantid);
        $USERMAP = new \local_webuntis\usermap();

        if (\local_webuntis\locallib::uses_eduvidual()) {
            $ORGMAP =\local_webuntis\orgmap::get_orgmap();
        }
    }

    public function __construct($tenantid) {
        global $DB;
        $this->tenantdata = $DB->get_record('local_webuntis_tenant', ['tenant_id' => $tenantid]);

        if (!empty($this->tenantdata->id)) {
            \local_webuntis\locallib::cache_set('session', 'last_tenant_id', $tenantid);
            $this->auth_server();
        } else {
            throw new \moodle_exception('invalidwebuntisinstance', 'local_webuntis', $CFG->wwwroot);
        }
    }

    /**
     * Ensure the user was authenticated against WebUntis.
     */
    public function auth() {
        global $CFG, $PAGE, $USER, $USERMAP;
        $endpoints = $this->get_endpoints();
        if (empty($endpoints->authorization_endpoint)) {
            throw new \moodle_exception('endpointmissing', 'local_webuntis', $CFG->wwwroot);
        }

        $code = optional_param('code', '', PARAM_TEXT);
        if (!empty($code)) {
            \local_webuntis\locallib::cache_set('session', 'code', $code);
            $this->auth_token($code);
        } else {
            $url = new \moodle_url($endpoints->authorization_endpoint, [
                'response_type' => 'code',
                'scope' => 'openid roster-core.readonly',
                'client_id' => self::get_client(),
                'redirect_uri' => $this->get_init_url(true),
            ]);
            redirect($url);
        }
    }

    /**
     * Get a token for server2server api.
     * @return token
     */
    public function auth_server() {
        if (!empty($this->serverinfo) && $this->serverinfo->lifeends < time()) {
            return $this->serverinfo;
        }
        $this->serverinfo = \local_webuntis\locallib::cache_get('application', 'serverinfo-' . $this->get_tenant_id());
        if (empty($this->serverinfo->lifeends) || $this->serverinfo->lifeends < time()) {
            // fetch new token.
            $url = implode('', [ $this->get_host(), '/WebUntis/api/sso/', \rawurlencode($this->get_school()), '/token' ]);
            $post = [
                'grant_type' => 'client_credentials',
                'scope' => 'roster-core.readonly',
            ];
            $ba = implode(':', [ $this->get_client(), $this->get_consumerpassword() ]);
            $serverinfo = \local_webuntis\locallib::curl($url, $post, null, $ba);
            $serverinfo = json_decode($serverinfo);
            // Expires according to Untis GmbH after 3 minutes.
            if (!empty($serverinfo->access_token)) {
                $serverinfo->lifeends = time() + 170;
                $this->serverinfo = $serverinfo;
                \local_webuntis\locallib::cache_set('application', 'serverinfo-' . $this->get_tenant_id(), $this->serverinfo);
                return $this->serverinfo;
            } else {
                throw new \moodle_exception('exception:no_accesstoken_retrieved', 'local_webuntis', 0, print_r($serverinfo, 1));
            }
        } else {
            return $this->serverinfo;
        }
    }

    /**
     * Get token for user-based API.
     * @param code the code retrieved by redirect.
     */
    public function auth_token($code = "") {
        global $CFG, $debug, $USERMAP;

        if (empty($code)) {
            $code = \local_webuntis\locallib::cache_get('session', 'code');
        }
        $endpoints = $this->get_endpoints();
        $path = $endpoints->token_endpoint;
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->get_client(),
            'client_secret' => $this->get_consumersecret(),
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
            $USERMAP = new \local_webuntis\usermap($userinfo);
        }
    }

    /**
     * Returns true if the current tenant is an integration-tenant.
     * @return boolean
     */
    public function is_integration() {
        // As of 27th June 2022 the URL for integration environments will change.
        $integrationenvironments = [
            'https://integration.webuntis.com',
            'https://tom.integration.webuntis.dev'
        ];
        return in_array($this->get_host(), $integrationenvironments);
    }

    public function get_autocreate() {
        return $this->tenantdata->autocreate;
    }
    public function get_client() {
        return $this->tenantdata->client;
    }
    public function get_consumersecret() {
        return $this->tenantdata->consumersecret;
    }
    public function get_consumerpassword() {
        return $this->tenantdata->consumerpassword;
    }
    private function get_endpoints() {
        $endpoints = \local_webuntis\locallib::cache_get('application', 'endpoints-' . $this->get_tenant_id());
        if (empty($endpoints) || empty($endpoints->authorization_endpoint)) {
            $host = $this->get_host();
            $school = $this->get_school(false);
            if (empty($host) || empty($school)) {
                throw new \moodle_exception('invalid_webuntis_instance', 'local_webuntis', $CFG->wwwroot);
            }
            $path = "$host/WebUntis/api/sso/$school/.well-known/openid-configuration";
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
    /**
     * Return the initial URL including tenant_id and lesson_id.
     * @param asstring if true will return string, moodle_url object otherwise.
     * @param includecode include the "code"-parameter for user authentication.
     * @return mixed String or Object
     */
    public function get_init_url($asstring = false, $includecode = false) {
        $params = [
            'tenant_id' => $this->get_tenant_id(),
            'school' => $this->get_school(),
            'lesson_id' => \local_webuntis\lessonmap::get_lesson_id(),
        ];
        if ($includecode) {
            $code = \local_webuntis\locallib::cache_get('session', 'code');
            $params['code'] = $code;
        }
        if ($asstring) {
            global $CFG;
            $fields = array();
            foreach ($params as $key => $value) {
                $fields[] = urlencode($key) . '=' . urlencode($value);
            }
            $fields = implode('&', $fields);
            $url = "$CFG->wwwroot/local/webuntis/index.php?$fields";

            return $url;
        } else {
            return new \moodle_url('/local/webuntis/index.php', $params);
        }
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

    public function set_oauth_keys($consumersecret, $consumerpassword) {
        global $DB;
        $this->tenantdata->consumersecret = $consumersecret;
        $this->tenantdata->consumerpassword = $consumerpassword;
        $DB->update_record('local_webuntis_tenant', $this->tenantdata);
    }

    /**
     * Indicates something has changed within this tenant, that requires invalidation of all chaches.
     */
    public function touch() {
        global $DB;
        $DB->set_field('local_webuntis_tenant', 'timemodified', time(), [ 'tenant_id' => $this->get_tenant_id() ]);
    }
}
