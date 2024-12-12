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

namespace local_webuntis;

defined('MOODLE_INTERNAL') || die;

class hook_callbacks {
    public static function after_config() {
        global $CFG, $PAGE;
        if (empty(\local_webuntis\locallib::uses_webuntis())) {
            return;
        }
        \local_webuntis\locallib::cookie_samesite();

        $PAGE->add_body_class('webuntis-loading-check');

        // Capture oauth logins within iframes.
        if (strpos($_SERVER['SCRIPT_FILENAME'], '/auth/oauth2/login.php') > 0 && \local_webuntis\locallib::in_iframe()) {
            $url = new moodle_url('/local/webuntis/loginexternal.php', [ 'url' => $CFG->wwwroot . $_SERVER['REQUEST_URI']]);
            redirect($url);
        }
    }

    public static function before_standard_head_html_generation(\core\hook\output\before_standard_head_html_generation $hook): void {
        // Only do something, if we came through webuntis.
        if (empty(\local_webuntis\locallib::uses_webuntis()) || defined('webuntis_no_action')) {
            return;
        }

        \local_webuntis\tenant::load();
    }
}
