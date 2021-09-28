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

function local_webuntis_after_config() {
    global $CFG, $PAGE;
    if (empty(\local_webuntis\tenant::last_tenant_id())) {
        return;
    }
    $PAGE->add_body_class('webuntis-loading-check');

    // Capture oauth logins within iframes.
    if (strpos($_SERVER['SCRIPT_FILENAME'], '/auth/oauth2/login.php') > 0 && isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe') {
        $url = new moodle_url('/local/webuntis/loginexternal.php', [ 'url' => $CFG->wwwroot . $_SERVER['REQUEST_URI']]);
        //die("REdirect to $url");
        redirect($url);
    }
}

function local_webuntis_before_standard_html_head() {
    global $TENANT;
    // Only do something, if we came through webuntis.
    if (empty(\local_webuntis\tenant::last_tenant_id()) || defined('webuntis_no_action')) {
        return;
    }

    $TENANT = \local_webuntis\tenant::load();
    $USERMAP = new \local_webuntis\usermap();
}

/**
 * Extend Moodle Navigation.
 */
function local_webuntis_extend_navigation($navigation) {
    global $TENANT;
    // Only do something, if we came through webuntis.
    if (empty(\local_webuntis\tenant::last_tenant_id()) || defined('webuntis_no_action')) {
        return;
    }

    $TENANT = \local_webuntis\tenant::load();
    $USERMAP = new \local_webuntis\usermap();

    if ($USERMAP->get_userid() > 0 && $USERMAP->can_disconnect()) {
        global $USER;
        $nodehome = $navigation->get('actionmenu');
        if (empty($nodehome)) {
            $nodehome = $navigation;
        }
        $label = get_string('disconnect:user', 'local_webuntis');
        $url = new moodle_url('/local/webuntis/disconnect.php', array('userid' => $USER->id));
        $icon = new pix_icon('i/user', '', '');
        $nodemyorgs = $nodehome->add($label, $url, navigation_node::NODETYPE_LEAF, $label, 'disconnectuser', $icon);
        $nodemyorgs->showinflatnavigation = true;
    }
}

/**
 * Extend course settings
 */
function local_webuntis_extend_navigation_course($nav, $course, $context) {
    // Only do something, if we came through webuntis.
    if (empty(\local_webuntis\tenant::last_tenant_id()) || defined('webuntis_no_action')) {
        return;
    }
}
