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
    private static $preserved_caches = array();

    /**
     * Retrieve a key from cache.
     * @param cache cache object to use (application or session)
     * @param key the key.
     * @return whatever is in the cache.
     */
    public static function cache_get($cache, $key) {
        if (!in_array($cache, [ 'application', 'session'])){
            return;
        }
        $cache = \cache::make('local_webuntis', $cache);
        return $cache->get($key);
    }
    /**
     * Store caches temporarily to preserve them when logging user in or out.
     * @param read if true store contents in local variable, if false restore cache.
     */
    public static function cache_preserve($read) {
        global $TENANT;
        // Only session caches need to be preserved.
        $preserves = array(
            // lessonmap
            array('type' => 'session', 'identifier' => 'code'),
            array('type' => 'session', 'identifier' => 'last_lesson_ids'),
            array('type' => 'session', 'identifier' => 'last_tenant_id'),
            array('type' => 'session', 'identifier' => 'lessonmaps'),
            array('type' => 'session', 'identifier' => 'tenants'),
            array('type' => 'session', 'identifier' => 'userinfos'),
            array('type' => 'session', 'identifier' => 'usermaps'),
            array('type' => 'session', 'identifier' => 'uuid'),
            // usermap
            // array('type' => 'session', 'identifier' => 'token'),
        );
        switch ($read) {
            case true:
                self::$preserved_caches = array();
                foreach ($preserves as $preserve) {
                    self::$preserved_caches[$preserve['type']][$preserve['identifier']] =
                        self::cache_get($preserve['type'], $preserve['identifier']);
                }
            break;
            case false:
                foreach (self::$preserved_caches as $type => $identifiers) {
                    foreach ($identifiers as $identifier => $value) {
                        self::cache_set($type, $identifier, $value);
                    }
                }
            break;
        }
    }
    /**
     * Return cache or print it.
     * @param die if true will echo and die.
     */
    public static function cache_print($die = false) {
        self::cache_preserve(true);
        echo "Cache_print:<pre>";
        print_r(self::$preserved_caches);
        echo "</pre>";
        if ($die) {
            die();
        }
    }
    /**
     * Set a cache object.
     * @param cache cache object to use (application or session)
     * @param key the key.
     * @param value the value.
     * @param delete whether or not the key should be removed from cache.
     */
    public static function cache_set($cache, $key, $value, $delete = false) {
        if (!in_array($cache, [ 'application', 'session'])) {
            return;
        }
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
     * @param headers custom request headers.
     */
    public static function curl($url, $post = null, $headers = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        if (!empty($post) && count($post) > 0) {
            $fields = array();
            foreach ($post as $key => $value) {
                $fields[] = urlencode($key) . '=' . urlencode($value);
            }
            $fields = implode('&', $fields);

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        }
        if (!empty($headers) && count($headers) > 0) {
            $strheaders = array();
            foreach ($headers as $key => $value) {
                $strheaders[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $strheaders);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * Find the overview image of a course.
     * @param courseid
     */
    public static function get_courseimage($courseid) {
        global $CFG;
        $course = \get_course($courseid);
        $course = new \core_course_list_element($course);

        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $imagepath = '/' . $file->get_contextid() .
                        '/' . $file->get_component() .
                        '/' . $file->get_filearea() .
                        $file->get_filepath() .
                        $file->get_filename();
                $imageurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $imagepath, false);
                return $imageurl;
            }
        }
    }

    /**
     * Determines if eduvidual is installed on this system.
     */
    public static function uses_eduvidual() {
        global $CFG;
        return file_exists($CFG->dirroot . '/local/eduvidual/version.php');
    }
}
