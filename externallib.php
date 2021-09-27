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
        global $TENANT;
        $TENANT = \local_webuntis\tenant::load();
        $USERMAP = new \local_webuntis\usermap();
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

        $TENANT = \local_webuntis\tenant::load();
        $LESSONMAP = new \local_webuntis\lessonmap();

        if ($LESSONMAP->can_edit()) {
            $courseid = $params['courseid'];
            if ($params['status'] == 0) {
                $courseid = $courseid * -1;
            }
            $LESSONMAP->change_map($courseid);

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
    public static function selecttarget_returns() {
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

        $fields = [ 'client', 'consumerkey', 'consumersecret' ];
        if (!in_array($params['field'], $fields)) {
            throw new \moodle_exception('invalid field');
        }

        $dbparams = [ 'tenant_id' => $params['tenant_id']];
        $status = $DB->set_field('local_webuntis_tenant', $params['field'], $params['value'], $dbparams);

        return [ 'status' => $status ];
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function tenantdata_returns() {
        return new external_single_structure(array(
            'status' => new external_value(PARAM_INT, 'current status'),
        ));
    }

}
