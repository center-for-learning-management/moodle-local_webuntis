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

    if ($oldversion < 2021061400) {

        // Rename field autoenrol on table local_webuntis_orgmap to NEWNAMEGOESHERE.
        $table = new xmldb_table('local_webuntis_orgmap');
        $field = new xmldb_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'orgid');

        // Launch rename field autoenrol.
        $dbman->rename_field($table, $field, 'autoenrol');

        $field = new xmldb_field('autocreate', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'autoenrol');

        // Conditionally launch add field autocreate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('idx_orgid', XMLDB_INDEX_NOTUNIQUE, ['orgid']);

        // Conditionally launch add index idx_orgid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('idx_tenant_id', XMLDB_INDEX_UNIQUE, ['tenant_id']);

        // Conditionally launch add index idx_tenant_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }


        // Webuntis savepoint reached.
        upgrade_plugin_savepoint(true, 2021061400, 'local', 'webuntis');
    }

    return true;
}
