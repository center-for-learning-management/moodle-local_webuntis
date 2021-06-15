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

    if ($oldversion < 2021061502) {

        // Define field autocreate to be added to local_webuntis_tenant.
        $table = new xmldb_table('local_webuntis_tenant');
        $field = new xmldb_field('autocreate', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'consumersecret');

        // Conditionally launch add field autocreate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('local_webuntis_orgmap');
        $field = new xmldb_field('autocreate');

        // Conditionally launch drop field autocreate.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Webuntis savepoint reached.
        upgrade_plugin_savepoint(true, 2021061502, 'local', 'webuntis');
    }


    return true;
}
