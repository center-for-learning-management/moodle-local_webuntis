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

defined('MOODLE_INTERNAL') || die;

$unfakeips = \local_webuntis\locallib::cache_get('application', 'unfakeips');
if (!is_array($unfakeips)) $unfakeips = array();
$fakeip = optional_param('fakeip', 0, PARAM_INT);
if (!empty($fakeip)) {
    if ($fakeip == 1) {
        $unfakeips[$_SERVER['REMOTE_ADDR']] = true;
        echo "IP was stored, we will *not* fake for this ip " . $_SERVER['REMOTE_ADDR'] . "<br />";
        echo "To remove this IP again, click <a href=\"$CFG->wwwroot/local/webuntis/index.php?fakeip=-1\">here</a>";
    } else {
        unset($unfakeips[$_SERVER['REMOTE_ADDR']]);
        echo "IP was unset, we will fake for this ip " . $_SERVER['REMOTE_ADDR'] . "<br />";
        echo "To add this IP again, click <a href=\"$CFG->wwwroot/local/webuntis/index.php?fakeip=1\">here</a>";
    }
    \local_webuntis\locallib::cache_set('application', 'unfakeips', $unfakeips);
    die();
}

$fake = empty($unfakeips[$_SERVER['REMOTE_ADDR']]);
if ($fake) {
    // Fake the user and course id.
    $userid = 15;
    $courseid = 248;

    $user = \core_user::get_user($userid);

    \complete_user_login($user);

    if (\user_not_fully_set_up($user, true)) {
        redirect($CFG->wwwroot.'/user/edit.php?id='.$userid.'&course='.SITEID);
    } else {
        redirect($CFG->wwwroot.'/course/view.php?id='.$courseid);
    }
}
