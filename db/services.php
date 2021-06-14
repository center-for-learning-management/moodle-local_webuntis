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

// We define the web service functions to install.
$functions = array(
    'local_webuntis_orgmap' => array(
        'classname'   => 'local_webuntis_external_eduvidual',
        'methodname'  => 'orgmap',
        'classpath'   => 'local/webuntis/externallib_eduvidual.php',
        'description' => 'Toggles mapping roles of an org.',
        'type'        => 'write',
        'ajax'        => 1,
    ),
    'local_webuntis_selecttarget' => array(
        'classname'   => 'local_webuntis_external',
        'methodname'  => 'selecttarget',
        'classpath'   => 'local/webuntis/externallib.php',
        'description' => 'Toggles selection of course as target.',
        'type'        => 'write',
        'ajax'        => 1,
    ),
);
