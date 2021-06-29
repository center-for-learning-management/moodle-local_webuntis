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
require_login();

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/webuntis/tenants.php', array()));
$PAGE->set_title(get_string('tenants', 'local_webuntis'));
$PAGE->set_heading(get_string('tenants', 'local_webuntis'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('tenants', 'local_webuntis'), $PAGE->url);

if (!is_siteadmin()) {
    throw new \moodle_exception('permission denied');
}

$PAGE->requires->css('/local/webuntis/style/main.css');

echo $OUTPUT->header();

$params = [
    'tenants' => array_values($DB->get_records('local_webuntis_tenant', [], 'school ASC')),
];

echo $OUTPUT->render_from_template('local_webuntis/tenants', $params);

echo $OUTPUT->footer();
