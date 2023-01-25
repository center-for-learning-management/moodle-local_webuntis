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

require_once('../../config.php');

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/webuntis/landingedit.php', array()));
$PAGE->set_title(get_string('landing:pagetitle', 'local_webuntis'));
$PAGE->set_heading(get_string('landing:pagetitle', 'local_webuntis'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('landing:pagetitle', 'local_webuntis'), $PAGE->url);
$PAGE->requires->css('/local/webuntis/style/main.css');

\local_webuntis\tenant::load();
$LESSONMAP = new \local_webuntis\lessonmap();

if (!$LESSONMAP->can_edit()) {
    throw new \moodle_exception(get_string('nopermissions', 'error', 'edit webuntis target'));
}

$allcourses = enrol_get_all_users_courses($USER->id, true);
$courses = [];
foreach ($allcourses as $course) {
    $ctx = \context_course::instance($course->id);
    if (has_capability('moodle/course:update', $ctx)) {
        $course->courseimage = \local_webuntis\locallib::get_courseimage($course->id);
        $course->is_autoenrolenabled = $LESSONMAP->is_autoenrolenabled($course->id);
        $course->is_selected = $LESSONMAP->is_selected($course->id);
        $courses[] = $course;
    }
}

$autoenrolforce = get_config('local_webuntis', 'autoenrolforce');

$params = [
    'autoenrolforce' => !empty($autoenrolforce),
    'canproceed' => ($LESSONMAP->get_count() > 0) ? 1 : 0,
    'courses' => $courses,
    'uses_eduvidual' => \local_webuntis\locallib::uses_eduvidual(),
    'wwwroot' => $CFG->wwwroot,
];

if ($LESSONMAP->get_lesson_id() == 0 && $LESSONMAP->can_edit()) {
    $params['canconfig'] = 1;
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_webuntis/landingedit', $params);
echo $OUTPUT->footer();
