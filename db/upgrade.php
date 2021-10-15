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

function xmldb_local_webuntis_upgrade($oldversion=0) {
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2021092400) {
        $table = new xmldb_table('local_webuntis_tenant');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'autocreate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2021092400, 'local', 'webuntis');
    }
    if ($oldversion < 2021101501) {
        $table = new xmldb_table('local_webuntis_tenant');
        $field = new xmldb_field('host', XMLDB_TYPE_CHAR, '250', null, XMLDB_NOTNULL, null, null, 'school');
        $dbman->change_field_precision($table, $field);
        upgrade_plugin_savepoint(true, 2021101501, 'local', 'webuntis');
    }
    return true;
}
