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
$PAGE->set_url('/local/webuntis/landing.php', array());
$PAGE->set_title(get_string('landing:pagetitle', 'local_webuntis'));
$PAGE->set_heading(get_string('landing:pagetitle', 'local_webuntis'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('landing:pagetitle', 'local_webuntis'), $PAGE->url);
$PAGE->requires->css('/local/webuntis/style/main.css');

\local_webuntis\tenant::load();
$LESSONMAP = new \local_webuntis\lessonmap();

$params = [
    'courses' => $LESSONMAP->get_courses(),
    'editurl' => $LESSONMAP->get_edit_url(),
    'wwwroot' => $CFG->wwwroot,
];

if (count($params['courses']) == 1) {
    $LESSONMAP->redirect();
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_webuntis/landing', $params);
echo $OUTPUT->footer();
