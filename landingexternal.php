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
 * @copyright  2022 Zentrum für Lernmanagement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('webuntis_no_action', 1);
require_once('../../config.php');

$url = optional_param('url', '', PARAM_TEXT);

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/webuntis/landingexternal.php', array('url' => $url)));
$PAGE->set_title(get_string('landingexternal:pagetitle', 'local_webuntis'));
$PAGE->set_heading(get_string('landingexternal:pagetitle', 'local_webuntis'));
$PAGE->set_pagelayout('popup');

$PAGE->navbar->add(get_string('landingexternal:pagetitle', 'local_webuntis'), $PAGE->url);
$PAGE->requires->css('/local/webuntis/style/main.css');

$url = new \moodle_url($url);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_webuntis/landingexternal', [
    'url' => $url,
]);
echo $OUTPUT->footer();
