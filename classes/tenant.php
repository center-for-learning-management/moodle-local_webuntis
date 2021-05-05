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
    private $tenant;

    public function __construct($tenant_id, $school = "") {
        global $DB;
        $sql = "SELECT *
            FROM {local_webuntis_tenant}
            WHERE tenant_id = :tenant_id
                OR school LIKE :school";
        $params = [ 'school' => $school, 'tenant_id' => $tenant_id ];
        $this->tenant = $DB->get_record_sql($sql, $params);

        if (empty($this->tenant->id)) {
            $this->tenant = (object) $params;
            $this->tenant->consumerkey = '';
            $this->tenant->consumersecret = '';
            $this->tenant->id = $DB->insert_record('local_webuntis_tenant', $this->tenant);
        }
        if (!empty($this->tenant->id) && !empty($school) && $this->tenant->school != $school) {
            $this->tenant->school = $school;
            $DB->set_field('local_webuntis_tenant', 'school', $school, array('id' => $this->tenant->id));
        }

        if (!empty($this->tenant->id) && empty($this->tenant->host)) {
            global $_SERVER;
            $this->tenant->host = $_SERVER['HTTP_REFERER'];
            $this->tenant->host = str_replace('https://', '', $this->tenant->host);
            $this->tenant->host = str_replace('.webuntis.com', '', $this->tenant->host);
            $this->tenant->host = str_replace('/', '', $this->tenant->host);
            $DB->set_field('local_webuntis_tenant', 'host', $this->tenant->host, array('id' => $this->tenant->id));
        }
    }
    public function get_id() {
        return $this->tenant->id;
    }
    public function get_consumerkey() {
        return $this->tenant->consumerkey;
    }
    public function get_consumersecret() {
        return $this->tenant->consumersecret;
    }
    public function get_school() {
        return $this->tenant->school;
    }
    public function get_tenant_id() {
        return $this->tenant->tenant_id;
    }
    public function set_oauth_keys($consumerkey, $consumersecret) {
        global $DB;
        $this->tenant->consumerkey = $consumerkey;
        $this->tenant->consumersecret = $consumersecret;
        $DB->update_record('local_webuntis_tenant', $this->tenant);
    }
}
