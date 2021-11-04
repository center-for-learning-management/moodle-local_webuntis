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

        if ($lessonid == -1) {
            $lessonid = self::get_lesson_id();
        }
        if ($lessonid > -1 && empty($this->lessonmap[$lessonid])) {
            $this->get_lessonmap($lessonid);
        }
        if ($lessonid > -1) {
            self::set_lessonid($lessonid);
        }
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
     * Checks if a user is enrolled in the target course. If not we have to
     * check via OneRoster, if the user should have access.
     */
    private function check_enrolment() {
        global $USER;
        $usermap = new \local_webuntis\usermap();
        $lessonmaps = self::get_lessonmap();
        $withcapability = (in_array($usermap->get_remoteuserrole(), ['teacher', 'administrator'])) ? 'moodle/course:update' : '';

        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            throw new \moodle_exception('manualpluginnotinstalled', 'enrol_manual');
        }

        foreach ($lessonmaps as $lessonmap) {
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

                if (!is_enrolled($ctx, $USER, $withcapability)) {
                    if (empty($lessonmap->lesson_id)) {
                        $moodlerole = $usermap->get_moodlerole();
                    } else {

                        $lessonrole = $this->get_lesson_role($lessonmap->lesson_id);
                        if (!empty($lessonrole)) {
                            $moodlerole = $usermap->get_moodlerole($lessonrole);
                        } else {
                            $moodlerole = '';
                        }
                    }
                    if (!empty($moodlerole)) {
                        if (!empty($instance->id)) {
                            $enrol->enrol_user($instance, $USER->id, $moodlerole, time(), 0, ENROL_USER_ACTIVE);
                        }
                        role_assign($moodlerole, $USER->id, $ctx);
                    }
                }
            }
        }
    }

    /**
     * Get the amount of courses in this map.
     */
    public function get_count() {
        global $DB, $TENANT;
        $params = [
            'tenant_id' => $TENANT->get_tenant_id(),
            'lesson_id' => self::get_lesson_id(),
        ];
        return $DB->count_records('local_webuntis_coursemap', $params);
    }

    public function get_courses() {
        global $DB, $TENANT;
        $courses = array();

        $params = [
            'tenant_id' => $TENANT->get_tenant_id(),
            'lesson_id' => self::get_lesson_id(),
        ];
        $lessonmaps = $DB->get_records('local_webuntis_coursemap', $params);
        //for ($a = 0; $a < count($this->lessonmap[self::get_lesson_id()]); $a++) {
        foreach ($lessonmaps as $lessonmap) {
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

    /**
     * Check via OneRoster-API if the user is student or teacher for the lesson.
     * @param lesson_id the lesson id from webuntis.
     * @return the webuntis role (only student or teacher)
     */
    private function get_lesson_role($lesson_id) {
        global $debug, $TENANT;
        $usermap = new \local_webuntis\usermap();
        $remoteuserid = $usermap->get_remoteuserid();
        if (empty($remoteuserid)) return;
        $integration = ($TENANT->get_host() == 'https://integration.webuntis.com') ? '-integration' : '';
        $headerparams = $usermap->get_headerparams();

        $calls = [ 'students' => 'student', 'teachers' => 'teacher' ];
        foreach ($calls as $caller => $webuntisrole) {
            $path = "https://api$integration.webuntis.com/ims/oneroster/v1p1/classes/$lesson_id/$caller";
            if ($debug) {
                echo "Path $path<br />";
            }

            if ($debug) {
                echo "Getuser (via header):<br /><pre>" . print_r($headerparams, 1) . "</pre>";
            }
            $users = \local_webuntis\locallib::curl($path, [], $headerparams);
            $users = json_decode($users);
            if (!empty($users->users)) {
                $users = $users->users;
            }
            if ($debug) {
                echo "<pre>" . print_r($users, 1) . "</pre>";
            }
            if (is_array($users)) {
                foreach ($users as $user) {
                    if ($user->identifier == $remoteuserid) {
                        return $webuntisrole;
                    }
                }
            }
        }
    }

    public function get_lessonmap($lessonid = -1) {
        global $DB, $TENANT;
        if ($lessonid == -1) {
            $lessonid = self::get_lesson_id();
        }
        return array_values(
            $DB->get_records(
                'local_webuntis_coursemap',
                [
                    'tenant_id' => $TENANT->get_tenant_id(),
                    'lesson_id' => $lessonid,
                ]
            )
        );
    }

    /**
     * Check whether or not a course is selected in this mapping.
     * @param courseid
     */
    public function is_selected($courseid) {
        global $DB, $TENANT;
        $params = [
            'tenant_id' => $TENANT->get_tenant_id(),
            'lesson_id' => self::get_lesson_id(),
            'courseid' => $courseid,
        ];
        $count = $DB->count_records('local_webuntis_coursemap', $params);
        return ($count > 0);
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

        $lessonmaps = self::get_lessonmap();
        // We only enrol users once a session.
        $synced = \local_webuntis\locallib::cache_get('session', 'synced_lesson_ids');
        if (empty($synced)) {
            $synced = [];
        }
        if (empty($synced[$TENANT->get_tenant_id()])) {
            $synced[$TENANT->get_tenant_id()] = [];
        }
        if (empty($synced[$TENANT->get_tenant_id()])) {
            $synced[$TENANT->get_tenant_id()][self::get_lesson_id()] = false;
        }
        if (empty($synced[$TENANT->get_tenant_id()][self::get_lesson_id()])) {
            // @todo better implement own enrol-plugin.
            $this->check_enrolment();
            $synced[$TENANT->get_tenant_id()][self::get_lesson_id()] = true;
            \local_webuntis\locallib::cache_set('session', 'synced_lesson_ids', $synced);
        }

        if (count($lessonmaps) == 1) {
            $url = new \moodle_url('/course/view.php', array('id' => $lessonmaps[0]->courseid));
            if (self::can_edit()) {
                $editurl = self::get_edit_url();
                $strparams = array('editurl' => $editurl->__toString());
                \redirect($url, get_string('redirect_edit_landingpage', 'local_webuntis', $strparams), 0, \core\output\notification::NOTIFY_INFO);
            } else {
                \redirect($url);
            }
        } elseif (count($lessonmaps) > 1) {
            // Redirect to selection list.
            $url = new \moodle_url('/local/webuntis/landing.php', array());
            \redirect($url);
        }
    }
}
