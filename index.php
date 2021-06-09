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

// Only used during development for demonstration purposes of Gruber & Petters.
require_once($CFG->dirroot . '/local/webuntis/fakemode.php');

$debug = true;

if ($debug) {
    echo "Received:<br />";
    echo "<pre>" . print_r($_REQUEST, 1) . "</pre>";
}

$lesson_id    = optional_param('lesson_id', 'main', PARAM_INT);
$school    = optional_param('school', '', PARAM_TEXT);
$tenant_id = optional_param('tenant_id', 0, PARAM_INT);

\local_webuntis\tenant::__load($tenant_id, $school);
\local_webuntis\lessonmap::__load($lesson_id);

echo "Cache_print:<pre>";
print_r(\local_webuntis\locallib::cache_print());
echo "</pre>";
die();
$PAGE->set_context(\context_system::instance());
$params = array(
    'lesson_id'     => \local_webuntis\lessonmap::get_lesson_id(),
    'school'     => \local_webuntis\tenant::get_school(),
    'tenant_id'  => \local_webuntis\tenant::get_tenant_id(),
);
$PAGE->set_url('/local/webuntis/index.php', $params);
$PAGE->set_title(get_string('pluginname', 'local_webuntis'));
$PAGE->set_heading(get_string('pluginname', 'local_webuntis'));
$PAGE->set_pagelayout('standard');

\local_webuntis\tenant::auth();

if (\local_webuntis\lessonmap::get_count() > 0) {
    \local_webuntis\lessonmap::redirect();
}
if (\local_webuntis\lessonmap::can_edit()) {
    redirect(\local_webuntis\lessonmap::get_edit_url());
}

//echo $OUTPUT->header();


if ($debug) {
    echo "Cache_print:<pre>";
    print_r(\local_webuntis\locallib::cache_print());
    echo "</pre>";
}

//echo $OUTPUT->footer();
