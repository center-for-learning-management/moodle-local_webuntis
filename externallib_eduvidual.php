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

class local_webuntis_external_eduvidual extends external_api {
    public static function orgmap_parameters() {
        return new external_function_parameters(array(
            'orgid' => new external_value(PARAM_INT, 'the orgid'),
            'field' => new external_value(PARAM_ALPHANUM, 'the field'),
            'status' => new external_value(PARAM_INT, '1 or 0'),
        ));
    }

    /**
     * Toggle status.
     */
    public static function orgmap($orgid, $field, $status) {
        global $DB, $USER;
        if (!\local_webuntis\locallib::uses_eduvidual()) {
            throw new \moodle_exception('not using eduvidual');
        }
        $params = self::validate_parameters(
            self::orgmap_parameters(),
            array(
                'orgid' => $orgid,
                'field' => $field,
                'status' => $status
            )
        );

        $orgrole = \local_eduvidual\locallib::get_orgrole($params['orgid']);
        if ($orgrole != 'Manager' && !is_siteadmin() || !\local_webuntis\lessonmap::can_edit()) {
            throw new \moodle_exception(get_string('missing_permission', 'local_eduvidual'));
        }

        $dbparams = [
            'orgid' => $params['orgid'],
            'tenant_id' => \local_webuntis\tenant::get_tenant_id(),
        ];
        $orgmap = $DB->get_record('local_webuntis_orgmap', $dbparams);
        $DB->set_field('local_webuntis_orgmap', $params['field'], $params['status'], $dbparams);
        return $params;
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function orgmap_returns() {
        return new external_single_structure(array(
            'orgid' => new external_value(PARAM_INT, 'orgid or 0 if failed'),
            'field' => new external_value(PARAM_ALPHANUM, 'the field'),
            'status' => new external_value(PARAM_INT, 'current status'),
        ));
    }
}
