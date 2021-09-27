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
    private static $lessonid; // Stores the lessonid of the current request.
    private $lessonmap;

    /**
     * Store the lessonid for this request.
     */
    public static function set_lessonid($lessonid) {
        global $TENANT;
        $lastlessonids = \local_webuntis\locallib::cache_get('session', 'last_lesson_ids');
        if (empty($lastlessonids)) {
            $lastlessonids = [];
        }
        $lastlessonids[$TENANT->get_tenant_id()] = $lessonid;
        self::$lessonid =  $lessonid;
        \local_webuntis\locallib::cache_set('session', 'last_lesson_ids', $lastlessonids);
    }
    /**
     * Load the lessonmap of a tenant.
     * @param tenantid to load the lessonmap for.
     */
    public function __construct($lessonid = -1) {
        global $debug, $TENANT;

        if (empty(self::$lessonmaps)) {
            self::$lessonmaps = \local_webuntis\locallib::cache_get('session', 'lessonmaps');
            if (empty(self::$lessonmaps[$TENANT->get_tenant_id()])) {
                self::$lessonmaps[$TENANT->get_tenant_id()] = [];
            }
        }
        $this->lessonmap = self::$lessonmaps[$TENANT->get_tenant_id()];

        if ($lessonid == -1) {
            $lessonid = self::get_lesson_id();
        }
        if ($lessonid > -1 && empty($this->lessonmap[$lessonid])) {
            $this->get_lessonmap($lessonid);
        }
        if ($lessonid > -1) {
            self::set_lessonid($lessonid);
        }

        if ($debug) {
            echo "Found lessonmap\n";
            echo "<pre>" . print_r($this->lessonmap, 1) . "</pre>\n";
        }
    }

    public static function cache_invalidate($tenantid) {
        unset(self::$lessonmaps[$tenantid]);
        \local_webuntis\locallib::cache_set('session', 'lessonmaps', self::$lessonmaps);
    }

    /**
     * Check if user can edit this lessonmap.
     */
    public function can_edit() {
        global $TENANT;
        $editroles = [ 'administrator' ];
        if (self::get_lesson_id() > 0) {
            $editroles[] = 'teacher';
        }
        $USERMAP = new \local_webuntis\usermap();
        return (in_array($USERMAP->get_remoteuserrole(), $editroles));
    }

    /**
     * Add or remove a course from map.
     */
    public function change_map($courseid) {
        global $DB, $TENANT;

        $dbparams = array(
            'tenant_id' => $TENANT->get_tenant_id(),
            'lesson_id' => self::get_lesson_id(),
            'courseid' => $courseid
        );

        if (!$this->can_edit()) {
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

        $TENANT->touch();
    }

    /**
     * Get the amount of courses in this map.
     */
    public function get_count() {
        return count($this->lessonmap[self::get_lesson_id()]);
    }

    public function get_courses() {
        $courses = array();

        //for ($a = 0; $a < count($this->lessonmap[self::get_lesson_id()]); $a++) {
        foreach ($this->lessonmap[self::get_lesson_id()] as $lessonmap) {
            $courseid = $lessonmap->courseid; //$this->lessonmap[$a]->courseid;
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

    public function get_edit_url() {
        if ($this->can_edit()) {
            $editurl = new \moodle_url('/local/webuntis/landingedit.php', []);
            return $editurl;
        }
        return '';
    }

    /**
     * Get the lesson information from cache.
     */
    public static function get_lesson_id() {
        global $TENANT;
        $lastlessonids = \local_webuntis\locallib::cache_get('session', 'last_lesson_ids');
        if (empty($lastlessonids)) {
            $lastlessonids = [];
        }
        if (empty(self::$lessonid) && !empty($lastlessonids[$TENANT->get_tenant_id()])) {
            self::$lessonid = $lastlessonids[$TENANT->get_tenant_id()];
        }
        return empty(self::$lessonid) ? 0 : self::$lessonid;
    }

    public function get_lessonmap($lessonid = -1) {
        global $DB, $TENANT;
        if ($lessonid == -1) {
            $lessonid = self::get_lesson_id();
        }
        if (empty($this->lessonmap[$lessonid]) || count($this->lessonmap[$lessonid]) == 0) {
            $this->lessonmap[$lessonid] = array_values(
                $DB->get_records(
                    'local_webuntis_coursemap',
                    [
                        'tenant_id' => $TENANT->get_tenant_id(),
                        'lesson_id' => $lessonid,
                    ]
                )
            );
        }

        $this->to_cache();
    }

    /**
     * Check whether or not a course is selected in this mapping.
     * @param courseid
     */
    public function is_selected($courseid) {
        foreach ($this->lessonmap[self::get_lesson_id()] as $lessonmap) {
            if ($lessonmap->courseid == $courseid) {
                return true;
            }
        }
        return false;
    }

    /**
     * Redirect user to appropriate target.
     */
    public function redirect() {
        global $DB, $TENANT, $USER;

        $usermap = new \local_webuntis\usermap();

        if ($usermap->get_userid() != $USER->id || isguestuser() || !isloggedin()) {
            return;
        }

        // We only enrol users once a session.
        $synced = \local_webuntis\locallib::cache_get('session', 'synced_lessonmap-' . $TENANT->get_tenant_id() . '-' . self::get_lesson_id());
        if (empty($synced)) {
            // @todo better implement own enrol-plugin.
            $moodlerole = $usermap->get_moodlerole();
            if (!empty($moodlerole)) {
                $enrol = enrol_get_plugin('manual');
                if (empty($enrol)) {
                    throw new \moodle_exception('manualpluginnotinstalled', 'enrol_manual');
                }
                foreach ($this->lessonmap[self::get_lesson_id()] as $lessonmap) {
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
            \local_webuntis\locallib::cache_set('session', 'synced_lessonmap-' . $TENANT->get_tenant_id() . '-' . self::get_lesson_id(), true);
        }

        if (count($this->lessonmap[self::get_lesson_id()]) == 1) {
            $url = new \moodle_url('/course/view.php', array('id' => $this->lessonmap[self::get_lesson_id()][0]->courseid));
            if (self::can_edit()) {
                $editurl = self::get_edit_url();
                $strparams = array('editurl' => $editurl->__toString());
                \redirect($url, get_string('redirect_edit_landingpage', 'local_webuntis', $strparams), 0, \core\output\notification::NOTIFY_INFO);
            } else {
                \redirect($url);
            }
        } elseif (count($this->lessonmap[self::get_lesson_id()]) > 1) {
            // Redirect to selection list.
            $url = new \moodle_url('/local/webuntis/landing.php', array());
            \redirect($url);
        }
    }

    public function to_cache() {
        global $TENANT;
        self::$lessonmaps[$TENANT->get_tenant_id()] = $this->lessonmap;
        \local_webuntis\locallib::cache_set('session', 'lessonmaps', self::$lessonmaps);
    }
}
