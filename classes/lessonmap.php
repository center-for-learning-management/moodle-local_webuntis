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
    private static $debug;
    private static $isloaded = false;
    private static $lessonmaps; // stores all lessonmaps of this tenant in cache.

    /**
     * Load a lessonmap.
     * @param lesson the lesson identifier. -1 loads from cache.
     */
    public static function load($lesson_id = -1) {
        global $debug; self::$debug = $debug;
        global $DB;

        $old_lesson_id = \local_webuntis\locallib::cache_get('session', 'lesson_id');
        if ($lesson_id == -1) {
            $lesson_id = \local_webuntis\locallib::cache_get('session', 'lesson_id');
        } else {
            \local_webuntis\locallib::cache_set('session', 'lesson_id', $lesson_id);
        }

        self::get_lesson_maps();

        if (self::$debug) {
            echo "Found lessonmap\n";
            echo "<pre>" . print_r(self::$lessonmaps, 1) . "</pre>\n";
        }
        self::$isloaded = true;
    }

    /**
     * Check if user can edit this lessonmap.
     */
    public static function can_edit() {
        self::is_loaded();

        $editroles = [ 'administrator' ];
        if (self::get_lesson_id() > 0) {
            $editroles[] = 'teacher';
        }
        return (in_array(\local_webuntis\usermap::get_remoteuserrole(), $editroles));
    }

    /**
     * Add or remove a course from map.
     */
    public static function change_map($courseid) {
        self::is_loaded();
        global $DB;

        $dbparams = array(
            'tenant_id' => \local_webuntis\tenant::get_tenant_id(),
            'lesson_id' => self::get_lesson_id(),
            'courseid' => $courseid
        );

        if (!self::can_edit()) {
            return;
        }

        if ($courseid < 0) {
            // We want to remove it.
            $dbparams['courseid'] = $dbparams['courseid'] * -1;
            $DB->delete_records('local_webuntis_coursemap', $dbparams);
        } else {
            if (!$DB->record_exists('local_webuntis_coursemap', $dbparams)) {
                $dbparams['id'] = $DB->insert_record('local_webuntis_coursemap', $dbparams);
            }
        }
        self::get_lesson_maps(true);
        \local_webuntis\tenant::touch();
    }

    /**
     * Get the amount of courses in this map.
     */
    public static function get_count() {
        self::is_loaded();
        return count(self::$lessonmaps[self::get_lesson_id()]);
    }

    public static function get_courses() {
        self::is_loaded();
        $courses = array();
        for ($a = 0; $a < count(self::$lessonmaps[self::get_lesson_id()]); $a++) {
            $courseid = self::$lessonmaps[$a]->courseid;
            $context = \context_course::instance($courseid, IGNORE_MISSING);
            if (empty($context->id)) {
                // Course does not exist anymore.
                self::change_map($courseid * -1);
            } else {
                $course = \get_course($courseid);
                $course = new \core_course_list_element($course);
                $courses[$course->fullname] = (object) array(
                    'courseimage' => \local_webuntis\locallib::get_courseimage($courseid),
                    'fullname' => $course->fullname,
                    'id' => $courseid,
                    'shortname' => $course->shortname,
                );
            }
        }
        ksort($courses);

        return array_values($courses);
    }

    public static function get_edit_url() {
        if (self::can_edit()) {
            $editurl = new \moodle_url('/local/webuntis/landingedit.php', []);
            return $editurl;
        }
        return '';
    }

    /**
     * Get the lesson information from cache.
     */
    public static function get_lesson_id() {
        self::is_loaded();
        return \local_webuntis\locallib::cache_get('session', 'lesson_id');
    }

    /**
     * Get the lesson map for all lessons on this tenant.
     * Only the mapping for the current lesson are loaded and added to a session-cache-object.
     * @param reload from database.
     */
    public static function get_lesson_maps($reload = false) {
        self::$lessonmaps = \local_webuntis\locallib::cache_get('session', 'lessonmaps');
        if ($reload || empty(self::$lessonmaps[self::get_lesson_id()])) {
            self::$lessonmaps = [];
            self::$lessonmaps[self::get_lesson_id()] = array_values(
                $DB->get_records(
                    'local_webuntis_coursemap',
                    [
                        'tenant_id' => \local_webuntis\tenant::get_tenant_id(),
                        'lesson_id' => self::get_lesson_id(),
                    ]
                )
            );
            \local_webuntis\locallib::cache_set('session', 'lessonmaps', self::$lessonmaps);
        }

        return self::$lessonmaps;
    }

    /**
     * Ensure object was loaded.
     */
    public static function is_loaded() {
        if (!self::$isloaded) {
            self::load();
        }
    }

    /**
     * Check whether or not a course is selected in this mapping.
     * @param courseid
     */
    public static function is_selected($courseid) {
        self::is_loaded();
        foreach (self::$lessonmaps[self::get_lesson_id()] as $lessonmap) {
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
        global $DB, $USER;
        if (!self::$isloaded) self::load();
        $lessonmaps = self::$lessonmaps;

        if (\local_webuntis\usermap::get_userid() != $USER->id || isguestuser() || !isloggedin()) {
            return;
        }

        // We only enrol users once a session.
        $synced = \local_webuntis\locallib::cache_get('session', 'synced_lessonmap-' . self::get_lesson_id());
        if (empty($synced)) {
            // @todo better implement own enrol-plugin.
            $moodlerole = \local_webuntis\usermap::get_moodlerole();
            if (!empty($moodlerole)) {
                $enrol = enrol_get_plugin('manual');
                if (empty($enrol)) {
                    throw new \moodle_exception('manualpluginnotinstalled', 'enrol_manual');
                }
                foreach ($lessonmaps[self::get_lesson_id()] as $lessonmap) {
                    $ctx = \context_course::instance($lessonmap->courseid, IGNORE_MISSING);
                    if (!empty($ctx->id)) {
                        $enrolinstances = enrol_get_instances($lessonmap->courseid, false);
                        $instance = 0;
                        foreach ($enrolinstances as $enrolinstance) {
                            if ($enrolinstance->enrol == "manual") {
                                if ($enrolinstance->status == 1) {
                                    // It is inactive - we have to activate it!
                                    $data = (object)array('status' => 0);
                                    $enrol->update_instance($enrolinstance, $data);
                                }
                                $instance = $enrolinstance;
                            }
                        }
                        if (empty($instance->id)) {
                            $instanceid = $enrol->add_default_instance((object)['id' => $lessonmap->courseid]);
                            $instance = $DB->get_record('enrol', [ 'id' => $instanceid ]);
                        }
                        if (!empty($instance->id)) {
                            $enrol->enrol_user($instance, $USER->id, $moodlerole, time(), 0, ENROL_USER_ACTIVE);
                        }
                        role_assign($moodlerole, $USER->id, $ctx);
                    }
                }
            }
            \local_webuntis\locallib::cache_set('session', 'synced_lessonmap-' . self::get_lesson_id(), true);
        }

        if (count(self::$lessonmaps[self::get_lesson_id()]) == 1) {
            $url = new \moodle_url('/course/view.php', array('id' => $courseids[0]));
            if (self::can_edit()) {
                $editurl = self::get_edit_url();
                $strparams = array('editurl' => $editurl->__toString());
                \redirect($url, get_string('redirect_edit_landingpage', 'local_webuntis', $strparams), 0, \core\output\notification::NOTIFY_INFO);
            } else {
                \redirect($url);
            }
        } elseif (count(self::$lessonmaps[self::get_lesson_id()]) > 1) {
            // Redirect to selection list.
            $url = new \moodle_url('/local/webuntis/landing.php', array());
            \redirect($url);
        }
    }
}
