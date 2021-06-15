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

if ($hassiteconfig) {
    $ADMIN->add(
        'localplugins',
        new admin_category(
            'local_webuntis',
            get_string('pluginname', 'local_webuntis')
        )
    );
    // We ommit the label, so that it does not show the heading.
    $settings = new admin_settingpage( 'local_webuntis_settings', '');

    if ($ADMIN->fulltree) {
        $name = new lang_string('admin:autocreate', 'local_webuntis');
        $desc = new lang_string('admin:autocreate:description', 'local_webuntis');
        $default = 1;

        $setting = new admin_setting_configcheckbox(
            'local_webuntis/autocreate',
            $name,
            $desc,
            $default
        );
        $settings->add($setting);
    }

    $ADMIN->add('local_webuntis', $settings);
}
