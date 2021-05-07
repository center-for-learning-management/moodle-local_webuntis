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

class locallib {
    /**
     * Retrieve a key from cache.
     * @param cache cache object to use (application or session)
     * @param key the key.
     * @return whatever is in the cache.
     */
    public static function cache_get($cache, $key) {
        if (!in_array($cache, [ 'application', 'session'])) return;
        $cache = \cache::make('local_webuntis', $cache);
        return $cache->get($key);
    }
    /**
     * Set a cache object.
     * @param cache cache object to use (application or session)
     * @param key the key.
     * @param value the value.
     * @param delete whether or not the key should be removed from cache.
     */
    public static function cache_set($cache, $key, $value, $delete = false) {
        if (!in_array($cache, [ 'application', 'session'])) return;
        $cache = \cache::make('local_webuntis', $cache);
        if ($delete) {
            $cache->delete($key);
        } else {
            $cache->set($key, $value);
        }
    }
    /**
     * Retrieve contents from an url.
     * @param url the url to open.
     * @param post variables to attach using post.
     */
    public static function curl($url, $post = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if(!empty($post)) {
            $fields_string = "";
            foreach($post as $key => $value) {
                $fields_string .= $key . '=' . $value . '&';
            }
            rtrim($fields_string, '&');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
