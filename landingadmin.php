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

define('WEBUNTIS_NO_ORGMAP_REDIRECT', true);

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/webuntis/landingadmin.php', array()));
$PAGE->set_title(get_string('settings'));
$PAGE->set_heading(get_string('settings'));
$PAGE->set_pagelayout('standard');

$url = new \moodle_url('/local/webuntis/landingedit.php', array());
$PAGE->navbar->add(get_string('landing:pagetitle', 'local_webuntis'), $url);
$PAGE->navbar->add(get_string('settings'), $PAGE->url);
$PAGE->requires->css('/local/webuntis/style/main.css');

\local_webuntis\tenant::load();
$LESSONMAP = new \local_webuntis\lessonmap();

echo $OUTPUT->header();

if (!$LESSONMAP->can_edit()) {
    throw new \moodle_exception(get_string('missing_permission', 'local_eduvidual'));
}

if (\local_webuntis\locallib::uses_eduvidual()) {
    $orgs = array_values(\local_eduvidual\locallib::get_organisations('*', false));
    if (count($orgs) > 0) {
        $actions = [
            [
                'isheader' => 1,
                'label' => '',
                'orgs' => [],
            ],
            [
                'field' => 'connected',
                'label' => get_string('eduvidual:orgs', 'local_webuntis'),
                'orgs' => [],
            ],
        ];

        for ($a = 0; $a < count($orgs); $a++) {
            $org = $orgs[$a];
            $dbparams = array(
                'orgid' => $org->orgid,
                'tenant_id' => $TENANT->get_tenant_id(),
            );
            $orgmap = $DB->get_record('local_webuntis_orgmap', $dbparams);
            for ($b = 0; $b < count($actions); $b++) {
                if ($b == 0) {
                    $actions[$b]['orgs'][] = (object) [
                        'orgid' => $org->orgid,
                        'name' => $org->name,
                    ];
                } else {
                    $actions[$b]['orgs'][] = (object) [
                        'enabled' => $orgmap->{$actions[$b]['field']},
                        'orgid' => $org->orgid,
                        'name' => $org->name,
                    ];
                }
            }
        }

        $params = [
            'actions' => $actions,
            'header' => get_string('eduvidual:connect_org', 'local_webuntis'),
            'wwwroot' => $CFG->wwwroot,
        ];

        echo $OUTPUT->render_from_template('local_webuntis/landingeduvidual', $params);
    }
} else {
    $params = [
        'autocreate' => $TENANT->get_autocreate(),
        'sysenabledautocreate' => get_config('local_webuntis', 'autocreate'),
    ];
    echo $OUTPUT->render_from_template('local_webuntis/landingadmin', $params);
}



echo $OUTPUT->footer();
