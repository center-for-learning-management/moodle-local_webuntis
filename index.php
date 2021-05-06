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

require_once('../../config.php');

print_r($_REQUEST);

$tenant_id = required_param('tenant_id', PARAM_INT);
$lesson = optional_param('lesson', '', PARAM_ALPHANUM);
$school = optional_param('school', '', PARAM_ALPHANUM);

\local_webuntis\tenant::__load($tenant_id, $school);
\local_webuntis\tenant::auth();

$urlparams = [ 'tenant_id' => $tenand_id ];
// Identify action by parameters.
$action = 'tenant_page';
if (!empty($lesson)) {
    $action = 'lesson_page';
}

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/local/webuntis/index.php', array());
$PAGE->set_title(get_string('pluginname', 'local_webuntis'));
$PAGE->set_heading(get_string('pluginname', 'local_webuntis'));
$PAGE->set_pagelayout('redirect');

echo $OUTPUT->header();
echo "Landing page";
echo $OUTPUT->footer();
