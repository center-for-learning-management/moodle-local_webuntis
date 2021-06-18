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

$confirmed = optional_param('confirmed', 0, PARAM_INT);

$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/webuntis/landinguser.php', array('confirmed' => $confirmed)));
$PAGE->set_title(get_string('landinguser:pagetitle', 'local_webuntis'));
$PAGE->set_heading(get_string('landinguser:pagetitle', 'local_webuntis'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('landinguser:pagetitle', 'local_webuntis'), $PAGE->url);
$PAGE->requires->css('/local/webuntis/style/main.css');

if (\local_webuntis\usermap::get_userid() > 0) {
    throw new moodle_error('already connected');
}

$enoughdata = \local_webuntis\usermap::check_data_prior_usercreate();
$canmapnew = get_config('local_webuntis', 'autocreate') &&
             \local_webuntis\tenant::get_autocreate();
$params = [
    'canmapnew' => $canmapnew,
    'canmapcurrent' => (isloggedin() && !isguestuser()) ? 1 : 0,
    'canmapother' => 1,
    'enoughdata' => $enoughdata,
    'userfullname' => \fullname($USER),
    'usermap' => \local_webuntis\usermap::get_usermap(),
    'wwwroot' => $CFG->wwwroot,
];

if (strlen($params['userfullname']) > 20) {
    $params['userfullname'] = substr($params['userfullname'], 0, 18) . '...';
}

$params['count'] = $params['canmapnew'] + $params['canmapcurrent'] + $params['canmapother'];

switch ($confirmed) {
    case 1: // Create new user
        if (empty($params['canmapnew'])) {
            throw new \moodle_exception('forbidden');
        }
        // Create new user and store id
        $u = (object) [
            'confirmed' => 1,
            'mnethostid' => 1,
            'username' => \local_webuntis\usermap::get_username(),
            'firstname' => \local_webuntis\usermap::get_firstname(),
            'lastname' => \local_webuntis\usermap::get_lastname(),
            'email' => \local_webuntis\usermap::get_email(),
            'auth' => 'manual',
        ];
        if (\local_webuntis\locallib::uses_eduvidual()) {
            if (empty($u->email)) {
                require_once("$CFG->dirroot/local/eduvidual/classes/lib_import.php");
                $compiler = new local_eduvidual_lib_import_compiler_user();
                $u = $compiler->compile($u);
            }
            $u->username = $u->email;
        }
        echo "Creating user: <pre>";
        print_r($u);
        echo "</pre>";
        die();

        $u->id = user_create_user($u, false, false);
        $u->idnumber = $u->id;
        $DB->set_field('user', 'idnumber', $u->idnumber, array('id' => $u->id));
        $user->secret = \local_eduvidual\locallib::get_user_secret($u->id);
        if (empty($user->password)) {
            $user->password = $user->secret;
        }
        update_internal_user_password($u, $user->password, false);
        set_user_preference('auth_forcepasswordchange', true, $u->id);

        $user->id = $u->id;

        \local_eduvidual\lib_enrol::choose_background($user->id);
        // Trigger event.
        \core\event\user_created::create_from_userid($user->id)->trigger();

    break;
    case 2: // Use current user
        if (empty($params['canmapcurrent'])) {
            throw new \moodle_exception('forbidden');
        }
        if (isloggedin() && !isguestuser()) {
            \local_webuntis\usermap::set_userid();
            if (\local_webuntis\usermap::get_userid() == $USER->id) {
                $url = \local_webuntis\tenant::get_init_url();
                redirect($url, get_string('usermap:success', 'local_webuntis'), 0, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                throw new \moodle_exception(get_string('usermap:failed', 'local_webuntis'));
            }
        } else {
            throw new \moodle_exception(get_string('usermap:failed', 'local_webuntis'));
        }
    break;
    case 3: // Use other users
        if (empty($params['canmapother'])) {
            throw new \moodle_exception('forbidden');
        }
        // Safely logout.
        $url = \local_webuntis\tenant::get_init_url();
        \local_webuntis\usermap::release();
        require_logout();
        redirect(new \moodle_url('/local/webuntis/landinguser.php', array('confirmed' => 4, 'initurl' => $url->out(false))));
    break;
    case 4:
        $initurl = required_param('initurl', PARAM_URL);
        $SESSION->wantsurl = $initurl;
        redirect(get_login_url());
    break;
}

// In case mapping other user is the only option, redirect automatically.
if ($params['canmapnew'] == 0 && $params['canmapcurrent'] == 0 && $params['canmapother'] == 1) {
    $url = new \moodle_url('/local/webuntis/landinguser.php', array('confirmed' => 3));
    redirect($url);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_webuntis/landinguser', $params);
echo $OUTPUT->footer();
