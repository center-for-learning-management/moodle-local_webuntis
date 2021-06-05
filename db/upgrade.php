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

    if ($oldversion < 2021060500) {
        $table = new xmldb_table('local_webuntis_usermap');
        $field = new xmldb_field('remoteuserrole', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'remoteuserid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2021060500, 'local', 'webuntis');
    }
    if ($oldversion < 2021060501) {

        // Define table local_webuntis_coursemap to be created.
        $table = new xmldb_table('local_webuntis_coursemap');

        // Adding fields to table local_webuntis_coursemap.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, null, XMLDB_SEQUENCE, null);
        $table->add_field('tenant_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('lessonid', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_webuntis_coursemap.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_webuntis_coursemap.
        $table->add_index('idx_tenant_id_lessonid', XMLDB_INDEX_UNIQUE, ['tenant_id', 'lessonid']);

        // Conditionally launch create table for local_webuntis_coursemap.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Webuntis savepoint reached.
        upgrade_plugin_savepoint(true, 2021060501, 'local', 'webuntis');
    }


    return true;
}
