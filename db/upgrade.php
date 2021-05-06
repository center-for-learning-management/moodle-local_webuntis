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
 * @author    Robert Schrenk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_local_webuntis_upgrade($oldversion=0) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021050500) {
        $table = new xmldb_table('local_webuntis_tenant');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('tenant_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('school', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('consumerkey', XMLDB_TYPE_CHAR, '250', null, null, null, null);
        $table->add_field('consumersecret', XMLDB_TYPE_CHAR, '250', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('idx_tenant_id', XMLDB_INDEX_UNIQUE, ['tenant_id']);
        $table->add_index('idx_school', XMLDB_INDEX_UNIQUE, ['school']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2021050500, 'local', 'webuntis');
    }
    if ($oldversion < 2021050501) {
        $table = new xmldb_table('local_webuntis_tenant');
        $field = new xmldb_field('host', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null, 'school');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2021050501, 'local', 'webuntis');
    }
    if ($oldversion < 2021050604) {
        $table = new xmldb_table('local_webuntis_tenant');
        $field = new xmldb_field('client', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'host');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2021050604, 'local', 'webuntis');
    }

    return true;
}
