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

        $name = new lang_string('admin:autoenrol:force', 'local_webuntis');
        $desc = new lang_string('admin:autoenrol:force:description', 'local_webuntis');
        $options = [
            -1 => new lang_string('admin:autoenrol:force:disable', 'local_webuntis'),
            0 => new lang_string('admin:autoenrol:force:custom', 'local_webuntis'),
            1 => new lang_string('admin:autoenrol:force:enable', 'local_webuntis')
        ];
        $default = 0;

        $settings->add(
            new admin_setting_configselect(
                'local_webuntis/autoenrolforce',
                $name,
                $desc,
                $default,
                $options
            )
        );

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
        $default = "-----BEGIN PUBLIC KEY-----\n" . 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAy4SObQ2nfru24gRbrx7LqWbvbYyeMgWu6rWk5PdnZ5hFDoabRIdQPeL8EEp/vHz2AUjArYefoNuSY+0stSAdLYpH5OKLxao2fTpwpZxj70DNEPlFPsjQznX9OyXiNEEGKrXdXuuCHYjUsEwgbZijbJXWba/DqPqs9KIzRZBTjAOMKlPIm0cTtQ63GgD41AQoXY9PWnH8mDjrCrwXIgNiUw6imMUjsiR+kF9YP3+SizKDFoeiV7Xl6xdbi953OPVZ/KtSx2hn9RqH7jXv43TYXyRsRnDAH1mWt6ZAYJV+3JaCHGEwvN6yNQcnaBPWGXjw3s614iQgDR5EF0EpU4JtOwIDAQAB' . "\n-----END PUBLIC KEY-----";

        $setting = new admin_setting_configtextarea(
            'local_webuntis/pubkey_production',
            $name,
            $desc,
            $default
        );
        $settings->add($setting);

        $name = new lang_string('admin:curldebugging', 'local_webuntis');
        $desc = new lang_string('admin:curldebugging:description', 'local_webuntis');
        $default = 0;

        $setting = new admin_setting_configcheckbox(
            'local_webuntis/curldebugging',
            $name,
            $desc,
            $default
        );
        $settings->add($setting);
    }

    $ADMIN->add('local_webuntis', $settings);
}
