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

$debug = false;

if ($debug) {
    echo "Received:<br />";
    echo "<pre>" . print_r($_REQUEST, 1) . "</pre>";
}

$lesson_id    = optional_param('lesson_id', -1, PARAM_INT);
$school       = optional_param('school', '', PARAM_TEXT);
$tenant_id    = optional_param('tenant_id', 0, PARAM_INT);

// For some reason, webuntis provides an empty tenant_id...
if (empty($tenant_id)) $tenant_id = 1300;

// If tenant_id and school are given, but not lesson_id, this is link
// from the main menu in Webuntis.
if ($lesson_id == -1 && !empty($tenant_id) && !empty($school)) {
    $lesson_id = 0;
}

\local_webuntis\tenant::__load($tenant_id, $school);
\local_webuntis\lessonmap::__load($lesson_id);

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(\local_webuntis\tenant::get_init_url());
$PAGE->set_title(get_string('pluginname', 'local_webuntis'));
$PAGE->set_heading(get_string('pluginname', 'local_webuntis'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('pluginname', 'local_webuntis'), $PAGE->url);

\local_webuntis\tenant::auth();

//\local_webuntis\locallib::cache_print(true);

if (\local_webuntis\lessonmap::get_count() > 0) {
    \local_webuntis\lessonmap::redirect();
}
if (\local_webuntis\lessonmap::can_edit()) {
    redirect(\local_webuntis\lessonmap::get_edit_url());
}

echo $OUTPUT->header();
$params = [
    'urltodashboard' =>  new \moodle_url('/my'),
];
echo $OUTPUT->render_from_template('local_webuntis/landingmissing', $params);
echo "<details><summary>Show debug information</summary><pre>";
\local_webuntis\locallib::cache_print();
echo "</pre>";
echo $OUTPUT->footer();
