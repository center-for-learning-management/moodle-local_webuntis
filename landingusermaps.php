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
$PAGE->set_url(new \moodle_url('/local/webuntis/landingusermaps.php', [ ]));
$PAGE->set_title(get_string('admin:usermaps:pagetitle', 'local_webuntis'));
$PAGE->set_heading(get_string('admin:usermaps:pagetitle', 'local_webuntis'));
$PAGE->set_pagelayout('standard');

$url = new \moodle_url('/local/webuntis/landingedit.php', array());
$PAGE->navbar->add(get_string('landing:pagetitle', 'local_webuntis'), $url);
$PAGE->navbar->add(get_string('admin:usermaps:pagetitle', 'local_webuntis'), $PAGE->url);
$PAGE->requires->css('/local/webuntis/style/main.css');

\local_webuntis\tenant::load();
$USERMAP->sync();

$params = (object)[
    'sitename' => $CFG->shortname,
    'usermaps' => [],
    'wwwroot' => $CFG->wwwroot,
];

echo $OUTPUT->header();
$dbparams = array('tenant_id' => $TENANT->get_tenant_id());
$params->usermaps = array_values($DB->get_records('local_webuntis_usermap', $dbparams, 'lastname ASC,firstname ASC'));
foreach ($params->usermaps as $usermap) {
    if (!empty($usermap->userid)) {
        $user = \core_user::get_user($usermap->userid);
        $user->fullname = fullname($user);
        $usermap->moodleuser = $user;
    }
}
$actions = \local_webuntis\locallib::get_actions('usermaps', 'landingusermaps');
echo $OUTPUT->render_from_template('local_webuntis/navbar', [ 'actions' => $actions, 'wwwroot' => $CFG->wwwroot ]);
echo $OUTPUT->render_from_template('local_webuntis/landingusermaps', $params);

echo $OUTPUT->footer();
