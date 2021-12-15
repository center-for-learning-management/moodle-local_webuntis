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

$action = required_param('action', PARAM_ALPHANUM);
$orgid  = optional_param('orgid', 0, PARAM_INT); // for eduvidual.

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/webuntis/usersync.php', [ 'action' => $action, 'orgid' => $orgid ]));
$PAGE->set_title(get_string('admin:usersync:pagetitle', 'local_webuntis'));
$PAGE->set_heading(get_string('admin:usersync:pagetitle', 'local_webuntis'));
$PAGE->set_pagelayout('standard');

$url = new \moodle_url('/local/webuntis/landingedit.php', array());
$PAGE->navbar->add(get_string('landing:pagetitle', 'local_webuntis'), $url);
$PAGE->navbar->add(get_string('admin:usersync:pagetitle', 'local_webuntis'), $PAGE->url);
$PAGE->requires->css('/local/webuntis/style/main.css');

if (!empty(\local_webuntis\locallib::uses_eduvidual())) {
    if (!\local_eduvidual\locallib::is_manager()) {
        throw new \moodle_exception('exception:permission_denied', 'local_webuntis');
    }
} elseif (!is_siteadmin()) {
    throw new \moodle_exception('exception:permission_denied', 'local_webuntis');
}

\local_webuntis\tenant::load();
$USERMAP->sync();

$params = (object)[
    'action' => $action,
    'orgid' => $orgid,
    'uses_eduvidual' => \local_webuntis\locallib::uses_eduvidual(),
    'tenant_id' => $TENANT->get_tenant_id(),
    'wwwroot' => $CFG->wwwroot,
];

if ($action == 'roles' && empty($params->uses_eduvidual)) {
    redirect('/local/webuntis/landingusermaps.php');
}

echo $OUTPUT->header();
$actions = \local_webuntis\locallib::get_actions('usermaps', "landingusersync::$action");
echo $OUTPUT->render_from_template('local_webuntis/navbar', [ 'actions' => $actions ]);

switch ($action) {
    case 'create':
        $sql = "SELECT *
                    FROM {local_webuntis_usermap}
                    WHERE tenant_id = ?
                        AND (userid = 0 OR userid IS NULL)
                    ORDER BY lastname ASC, firstname ASC";
        $params->notmappedusers = array_values($DB->get_records_sql($sql, [ $TENANT->get_tenant_id() ]));
        foreach ($params->notmappedusers as $nmu) {
            $nmu->missingdata = (empty($nmu->email) || empty($nmu->firstname) || empty($nmu->lastname));
            if (!empty($nmu->missingdata)) {
                $chkusers = array_values($DB->get_records('user', [ 'email' => strtolower($nmu->email)]));
                $nmu->exists = (count($chkusers) > 0);
            }
        }
        echo $OUTPUT->render_from_template('local_webuntis/usersync_create', $params);
    break;
    case 'purge':
        if (!empty($params->uses_eduvidual)) {
            $orgs = \local_eduvidual\locallib::get_organisations('Manager', false);
            if (empty($orgid) && count($orgs) == 1) {
                $params->orgid = $orgs[0]->orgid;
            }
            if (empty($params->orgid)) {
                $params = (object) [
                    'action' => $action,
                    'orgs' => array_values(\local_eduvidual\locallib::get_organisations('Manager', false)),
                    'wwwroot' => $CFG->wwwroot,
                ];
                echo $OUTPUT->render_from_template('local_webuntis/usersync_selectorg', $params);
            } else {
                $sql = "SELECT *
                            FROM {user}
                            WHERE id IN (
                                SELECT userid
                                    FROM {local_eduvidual_orgid_userid}
                                    WHERE orgid = ?
                            ) AND id IN (
                                SELECT userid
                                    FROM {local_webuntis_usermap}
                                    WHERE tenant_id = ?
                                        AND userid > 0
                                        AND userid IS NOT NULL
                            )
                            AND id <> ?
                            AND id > 1
                            AND id NOT IN ($CFG->siteadmins)
                            AND deleted = 0
                            ORDER BY lastname ASC, firstname ASC";
                $params->purgecandidates = array_values($DB->get_records_sql($sql, [ $params->orgid, $TENANT->get_tenant_id(), $USER->id ]));

                foreach ($params->purgecandidates as $pc) {
                    $u = \core_user::get_user($pc->id);
                    $pc->profileimage = $OUTPUT->user_picture($u, array('size' => 30));
                }
                echo $OUTPUT->render_from_template('local_webuntis/usersync_purge', $params);
            }
        } else {
            $sql = "SELECT *
                        FROM {user}
                        WHERE id NOT IN (
                            SELECT userid
                                FROM {local_webuntis_usermap}
                                WHERE tenant_id = ?
                                    AND userid > 0
                                    AND userid IS NOT NULL
                            )
                            AND id > 1
                            AND id NOT IN ($CFG->siteadmins)
                            AND deleted = 0
                        ORDER BY lastname ASC, firstname ASC";
            $params->purgecandidates = array_values($DB->get_records_sql($sql, [ $TENANT->get_tenant_id() ]));
            foreach ($params->purgecandidates as $pc) {
                $u = \core_user::get_user($pc->id);
                $pc->profileimage = $OUTPUT->user_picture($u, array('size' => 30));
            }
            echo $OUTPUT->render_from_template('local_webuntis/usersync_purge', $params);
        }
    break;
    case 'roles':
        $orgs = \local_eduvidual\locallib::get_organisations('Manager', false);
        if (empty($params->orgid) && count($orgs) == 1) {
            $params->orgid = $orgs[0]->orgid;
        }
        if (empty($params->orgid)) {
            $params->orgs = array_values(\local_eduvidual\locallib::get_organisations('Manager', false));
            echo $OUTPUT->render_from_template('local_webuntis/usersync_selectorg', $params);
        } else {
            $params->mappedusers = [];
            $fields = implode(',', [
                "webuntis.remoteuserid ruid",
                "webuntis.email w_email",
                "webuntis.firstname w_firstname",
                "webuntis.lastname w_lastname",
                "webuntis.username w_username",
                "webuntis.remoteuserrole w_role",
                "moodle.id m_id",
                "moodle.email m_email",
                "moodle.firstname m_firstname",
                "moodle.lastname m_lastname",
                "moodle.username m_username",
            ]);
            $sql = "SELECT $fields
                        FROM {local_webuntis_usermap} webuntis, {user} moodle
                        WHERE webuntis.tenant_id = ?
                            AND webuntis.userid > 0
                            AND webuntis.userid = moodle.id
                        ORDER BY webuntis.lastname ASC, webuntis.firstname ASC";
            $mappedusers = array_values($DB->get_records_sql($sql, [ $TENANT->get_tenant_id() ]));

            foreach ($mappedusers as $mu) {
                $mu->w_role = ucfirst($mu->w_role);
                $role = [];
                if (!empty($mu->m_id)) {
                    $u = \core_user::get_user($mu->m_id);
                    $mu->m_profileimage = $OUTPUT->user_picture($u, array('size' => 30));
                    $mu->m_role = \local_eduvidual\locallib::get_orgrole($params->orgid, $mu->m_id);
                    $mu->role_differ = (str_replace('Administrator', 'Manager', $mu->w_role) != $mu->m_role);
                }
                $params->mappedusers[] = $mu;
            }

            echo $OUTPUT->render_from_template('local_webuntis/usersync_roles', $params);
        }

    break;
}



echo $OUTPUT->footer();
