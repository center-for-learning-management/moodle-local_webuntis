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

$userid       = optional_param('userid', 0, PARAM_INT);
$confirmed    = optional_param('confirmed', 0, PARAM_INT);
$disconnected = optional_param('disconnected', 0, PARAM_INT);

\local_webuntis\tenant::load();

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/webuntis/disconnect.php', array('userid' => $userid)));
$PAGE->set_title(get_string('disconnect:user', 'local_webuntis'));
$PAGE->set_heading(get_string('disconnect:user', 'local_webuntis'));
$PAGE->set_pagelayout('standard');

if (!empty($disconnected)) {
    $PAGE->set_pagelayout('popup');
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_webuntis/disconnected', []);
    echo $OUTPUT->footer();
}


if ($USER->id != $userid) {
    throw new \moodle_exception('invalidinput', 'local_webuntis', $CFG->wwwroot);
}

// @Todo show confirmation dialog prior to action.
$confirmed = optional_param('confirmed', 0, PARAM_INT);

if (empty($confirmed)) {
    echo $OUTPUT->header();
    $params = [
        'userid' => $USER->id,
        'wwwroot' => $CFG->wwwroot,
    ];
    echo $OUTPUT->render_from_template('local_webuntis/disconnect', $params);
    echo $OUTPUT->footer();
} else {
    if (!empty($userid)) {
        \local_webuntis\usermap::release();
        require_logout();
        $url = new \moodle_url('/local/webuntis/disconnect.php', array('disconnected' => 1));
        redirect($url);
    }

}
