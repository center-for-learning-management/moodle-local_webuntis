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

/**
 * Extend Moodle Navigation.
 */
function local_webuntis_extend_navigation($navigation) {
    // Only do something, if we came through webuntis.
    if (empty(\local_webuntis\usermap::get_id())) return;

    global $USER;
    $nodehome = $navigation->get('home');
    if (empty($nodehome)){
        $nodehome = $navigation;
    }
    $label = get_string('disconnect:user', 'local_webuntis');
    $url = new moodle_url('/local/webuntis/disconnect.php', array('userid' => $USER->id));
    $icon = new pix_icon('i/user', '', '');
    $nodemyorgs = $nodehome->add($label, $url, navigation_node::NODETYPE_LEAF, $label, 'disconnectuser', $icon);
    $nodemyorgs->showinflatnavigation = true;
}

/**
 * Extend course settings
 */
function local_webuntis_extend_navigation_course($nav, $course, $context) {
    // Only do something, if we came through webuntis.
    if (empty(\local_webuntis\usermap::get_id())) return;

    $coursecontext = \context_course::instance($course->id);
    if (has_capability('moodle/course:delete', $coursecontext)) {
        $url = new \moodle_url('/local/webuntis/disconnect.php', array('courseid' => $course->id));
        $nav->add(get_string('disconnect:course', 'local_webuntis'), $url);
    }
}