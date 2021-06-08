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
        global $DB, $USER;
        $params = self::validate_parameters(self::selecttarget_parameters(), array('courseid' => $courseid, 'status' => $status));

        $courseid = $params['courseid'];
        if ($params['status'] == 0) $courseid = $courseid*-1;
        \local_webuntis\lessonmap::change_map($courseid);

        $params['canproceed'] = (\local_webuntis\lessonmap::get_count() > 0) ? 1 : 0;
        $params['lesson'] = \local_webuntis\lessonmap::get_lesson();
        $params['tenant_id'] = \local_webuntis\tenant::get_tenant_id();

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
            'lesson' => new external_value(PARAM_TEXT, 'the lesson identifier'),
            'status' => new external_value(PARAM_INT, 'current status'),
            'tenant_id' => new external_value(PARAM_INT, 'the tenant id'),
        ));
    }
}
