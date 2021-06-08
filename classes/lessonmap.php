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

class lessonmap {
    private static $lessonmaps;
    private static $cacheidentifier;
    private static $debug;

    /**
     * Load a lessonmap.
     * @param lesson the lesson identifier. Empty for general link.
     */
    public static function __load($lesson) {
        global $debug; self::$debug = $debug;
        global $DB;

        if (empty($lesson)) {
            $lesson = \local_webuntis\locallib::cache_get('session', 'lesson');
        } else {
            \local_webuntis\locallib::cache_set('session', 'lesson', $lesson);
        }
        $params = [
            'tenant_id' => \local_webuntis\tenant::get_tenant_id(),
            'lessonid' => $lesson,
        ];
        if (empty(self::$cacheidentifier)) {
            self::$cacheidentifier = "lessonmaps_{$params['tenant_id']}_{$params['lessonid']}";
        }

        self::$lessonmaps = \local_webuntis\locallib::cache_get('session', self::$cacheidentifier);
        if (count(self::$lessonmaps) == 0) {
            self::$lessonmaps = array_values($DB->get_records('local_webuntis_coursemap', $params));
            \local_webuntis\locallib::cache_set('session', $identifier, self::$cacheidentifier);
        }
        if (self::$debug) {
            echo "Found lessonmap\n";
            echo "<pre>" . print_r(self::$lessonmaps, 1) . "</pre>\n";
        }
    }

    /**
     * Add or remove a course from map.
     */
    public static function change_map($courseid) {
        global $DB;

        $dbparams = array(
            'tenant_id' => $tenant_id,
            'lessonid' => $lesson,
            'courseid' => $courseid
        );
        if ($courseid < 0) {
            // We want to remove it.
            $dbparams['courseid'] = $dbparams['courseid'] * -1;
            unset(self::$lessonmaps[$dbparams['courseid']]);
            $DB->delete_records('local_webuntis_coursemap', $dbparams);
        } else {
            $dbparams['id'] = $DB->insert_record('local_webuntis_coursemap', $dbparams);
            self::$lessonmaps[$dbparams['courseid']] = $dbparams;
        }
        \local_webuntis\locallib::cache_set('session', self::$cacheidentifier, self::$lessonmaps);
    }

    /**
     * Get the lesson information from cache.
     */
    public static function get_lesson() {
        return \local_webuntis\locallib::cache_get('session', 'lesson');
    }

    /**
     * Check whether or not a course is selected in this mapping.
     * @param courseid
     */
    public static function is_selected($courseid) {
        foreach (self::$lessonmaps as $lessonmap) {
            if ($lessonmap->courseid == $courseid) {
                return true;
            }
        }
        return false;
    }

    /**
     * Redirect user to appropriate target.
     */
    public static function redirect() {
        $lessonmaps = array_values(self::$lessonmaps);
        if (!empty($lessonmaps) && count($lessonmaps) > 1) {
            // Redirect to selection list.
            die("redirect not implemented");
        }
        if (!empty($lessonmaps) && !empty($lessonmaps[0]->courseid)) {
            if (\isloggedin() && !\isguestuser()) {
                // @todo check enrolment of user.
            }
            $url = new \moodle_url('/course/view.php', array('id' => $lessonmaps[0]->courseid));
            \redirect($url);
        }
        return false;
    }


}
