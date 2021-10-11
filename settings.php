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
    $ADMIN->add(
        'local_webuntis',
        new admin_externalpage(
            'local_webuntis/tenants',
            get_string('tenants', 'local_webuntis'),
            new \moodle_url('/local/webuntis/tenants.php', [])
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

        $name = new lang_string('admin:pubkey:integration', 'local_webuntis');
        $desc = new lang_string('admin:pubkey:integration:description', 'local_webuntis');
        $default = "-----BEGIN PUBLIC KEY-----\n" . 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxmHgTa7Qf4buurWraH9MqcEipr4YrMpIg1NVbV7sx2p1yhZ5HQ5hPfsuRRqk9ss7UYJS4dnTsjLCwJ1j91PmxZBnceSkgjHunZ53AxsQP7h/A8g3igbi+tRw6+9agyM8zRLeAaufQFvm6/81obezB54vjv1qPGXgX07cmgj2w2EMC39Q4S0eKVU8svjw3QTE0ZD7Gc92T+rMIhVrX5sAKviczs8VSA8CZnM7PDASZ/kjZF9umMfEzmxGm5BVCqMqpCTFh3CMljMmoH3lCro3r9Ve2Unl5Cc8wRJekSOIbpKJ54eVL6zwEExfPlTKQZslLKBhaNtquLJJkgV057ANDwIDAQAB' . "\n-----END PUBLIC KEY-----";

        $setting = new admin_setting_configtextarea(
            'local_webuntis/pubkey_integration',
            $name,
            $desc,
            $default
        );
        $settings->add($setting);

        $name = new lang_string('admin:pubkey:production', 'local_webuntis');
        $desc = new lang_string('admin:pubkey:production:description', 'local_webuntis');
        $default = "-----BEGIN PUBLIC KEY-----\n" . 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArqx5iTI7Vm5V8K865H79svRanqQOB/WR32nd+Fw08vxfcesiO0Frg816dPk6MgRvurQ7fxv4SZaa5T+TNppgdjJBtrLX2uJlaTW8pHMTgZ/TAuoW/IZdvOKGhwsJh6unQwHDEmD7IE35agcyeuglNoHO4z3/Dnd/m9ufraf9HStQNUs820+Y5ENOBd76qfgKeIl0bV/PLnuVXUwMb1K3UQtP3N2xcNurpQB7AyQyrPWoKHUjPka/nROWJouUSficatL8XOay5GKm9SfK7hfNiDxB/vYUV/mdS5JSH9Lq2k/+zbZHDh47ea6CrKRxLM69di0BbnIuQTA1/YUDnm/cnQIDAQAB' . "\n-----END PUBLIC KEY-----";

        $setting = new admin_setting_configtextarea(
            'local_webuntis/pubkey_production',
            $name,
            $desc,
            $default
        );
        $settings->add($setting);
    }

    $ADMIN->add('local_webuntis', $settings);
}
