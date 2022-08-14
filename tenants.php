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
$PAGE->requires->css('/local/webuntis/style/main.css');

if (!is_siteadmin()) {
    throw new \moodle_exception('permission denied');
}

$missing = [];
$item = (object) [
    'tenant_id'      => optional_param('tenant_id', 0, PARAM_INT),
    'school'         => optional_param('school', '', PARAM_ALPHANUM),
    'host'           => optional_param('host', '', PARAM_URL),
    'client'         => optional_param('client', '', PARAM_ALPHANUM),
    'consumerkey'    => optional_param('consumerkey', '', PARAM_TEXT),
    'consumersecret' => optional_param('consumersecret', '', PARAM_TEXT)
];

if ($item->tenant_id > 0) {
    $exists = $DB->count_records('local_webuntis_tenant', [ 'tenant_id' => $item->tenant_id ]);
    if ($exists > 0) {
        $missing[] = 'tenant_id';
    }

    foreach ($item as $field => $value) {
        if (empty($value)) {
            $missing[] = $field;
        }
    }
    if (count($missing) == 0) {
        $item->id = $DB->insert_record('local_webuntis_tenant', $item);
        if ($item->id > 0) {
            redirect(
                $PAGE->url,
                get_string('tenantcreate:success', 'local_webuntis', $item ),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            redirect(
                $PAGE->url,
                get_string('tenantcreate:failure', 'local_webuntis', $item ),
                null,
                \core\output\notification::NOTIFY_DANGER
            );
        }
    }
}


echo $OUTPUT->header();
$params = [
    'item' => [ $item ],
    'tenants' => array_values($DB->get_records('local_webuntis_tenant', [], 'school ASC')),
];
foreach ($missing as $field) {
    $params["missing_$field"] = 1;
}
echo $OUTPUT->render_from_template('local_webuntis/tenants', $params);
echo $OUTPUT->footer();
