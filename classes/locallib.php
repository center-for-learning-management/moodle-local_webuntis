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
    private static $key = '';
    private static $preserved_caches = [];
    // Only session caches need to be preserved.
    private static $preserves = [
        array('type' => 'session', 'identifier' => 'code'),
        array('type' => 'session', 'identifier' => 'last_lesson_ids'),
        array('type' => 'session', 'identifier' => 'last_tenant_id'),
        array('type' => 'session', 'identifier' => 'last_tenant_sync'),
        array('type' => 'session', 'identifier' => 'synced_lesson_ids'),
        array('type' => 'session', 'identifier' => 'userinfos'),
        array('type' => 'session', 'identifier' => 'usermaps'),
        array('type' => 'session', 'identifier' => 'uses_webuntis'),
    ];

    /**
     * Retrieve preserved cache configuration from application cache
     * using a cache key. A key is valid only once, cache item is
     * removed once read.
     */
    public static function cache_fromkey() {
        $key = optional_param('cachekey', '', PARAM_TEXT);
        if (!empty($key)) {
            self::$preserved_caches = self::cache_get('application', "cachepreserve-$key");
            if (!empty(self::$preserved_caches)) {
                self::cache_set('application', "cachepreserve-$key", null, true);
                foreach (self::$preserved_caches as $type => $identifiers) {
                    foreach ($identifiers as $identifier => $value) {
                        self::cache_set($type, $identifier, $value);
                    }
                }
            }
        }
    }
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
        // All values are json_encoded by default in regard of
        // compatiblity prolems with certain cache types.
        return json_decode($cache->get($key),1);
    }
    /**
     * Store caches temporarily to preserve them when logging user in or out.
     * @param read if true store contents in local variable, if false restore cache.
     * @param url (optional) moodle_url to redirect after session cache was written to application cache.
     */
    public static function cache_preserve($read, $url = null) {
        global $TENANT;

        switch ($read) {
            case true:
                self::$preserved_caches = array();
                foreach (self::$preserves as $preserve) {
                    self::$preserved_caches[$preserve['type']][$preserve['identifier']] =
                        self::cache_get($preserve['type'], $preserve['identifier']);
                }
            break;
            case false:
                if (empty($key)) {
                    $key = uniqid();
                }
                self::cache_set('application', "cachepreserve-$key", self::$preserved_caches);
                if (empty($url)) $url = $TENANT->get_init_url(false, true);
                $url->param('cachekey', $key);
                redirect($url);
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
            // All values are json_encoded by default in regard of
            // compatiblity prolems with certain cache types.
            $cache->set($key, json_encode($value));
        }
    }

    /**
     * Set the MoodleSession cookie to SameSite=None
     * to allow embedding in an iframe.
     */
    public static function cookie_samesite() {
        $cookies = headers_list();
        header_remove('Set-Cookie');
        $setcookiesession = 'Set-Cookie: ' . session_name() . '=';

        foreach ($cookies as $cookie) {
            if (strpos($cookie, $setcookiesession) === 0) { // && strpos($cookie, 'SameSite=None') === false) {
                $cookie .= '; SameSite=None';
            }
            header($cookie, true);
        }
    }

    /**
     * Enable CORS for *.webuntis.com.
     * Code taken from https://stackoverflow.com/a/9866124 and adapted.
     */
    public static function cors() {
        if (isset($_SERVER['HTTP_ORIGIN']) && substr($_SERVER['HTTP_ORIGIN'], -13) == '.webuntis.com') {
            // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
            // you want to allow, and if so:
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }

        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                // may also be using PUT, PATCH, HEAD etc
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            exit(0);
        }
    }

    /**
     * Retrieve contents from an url.
     * @param url the url to open.
     * @param post variables to attach using post.
     * @param headers custom request headers.
     * @param basicauth username:password as String
     * @param debug boolean
     */
    public static function curl($url, $post = null, $headers = null, $basicauth = null, $debug = false) {
        $curldebugging = get_config('local_webuntis', 'curldebugging');
        if (!empty($curldebugging)) {
            $debug = $curldebugging;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $proxyhost = get_config('core', 'proxyhost');
        $proxyport = get_config('core', 'proxyport');
        $proxytype = get_config('core', 'proxytype');
        $proxyuser = get_config('core', 'proxyuser');
        $proxypassword = get_config('core', 'proxypassword');

        if (!empty($proxyhost)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxyhost);
        }
        if (!empty($proxyport)) {
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxyport);
        }
        if ($proxytype == "HTTP") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        }
        if ($proxytype == "SOCKS5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        if (!empty($proxyuser)) {
            if (!empty($proxypassword)) {
                $proxyuser .= ':' . $proxypassword;
            }
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyuser);
        }

        if ($debug) {
            ob_start();
            $out = fopen('php://output', 'w');
            curl_setopt($ch, CURLOPT_STDERR, $out);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            echo "<h3>URL: $url</h3>";
            echo "<p>POST: </p><pre>" . print_r($post, 1) . "</pre>";
            echo "<p>HEADERS: </p><pre>" . print_r($headers, 1) . "</pre>";
            echo "<p>BASICAUTH: </p><pre>" . print_r($basicauth, 1) . "</pre>";
        }

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
        if (!empty($basicauth)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $basicauth);
        }

        $result = curl_exec($ch);
        if ($debug) {
            fclose($out);
            $debug = ob_get_clean();
            echo "<p>DEBUG: </p><pre>$debug</pre>";
            echo "<p>RESULT: </p><pre>$result</pre>";
        }
        curl_close($ch);
        return $result;
    }

    public static function exception($message, $code) {
        $http_status_codes = array(
            100 => "Continue",
            101 => "Switching Protocols",
            102 => "Processing",
            200 => "OK",
            201 => "Created",
            202 => "Accepted",
            203 => "Non-Authoritative Information",
            204 => "No Content",
            205 => "Reset Content",
            206 => "Partial Content",
            207 => "Multi-Status",
            300 => "Multiple Choices",
            301 => "Moved Permanently",
            302 => "Found",
            303 => "See Other",
            304 => "Not Modified",
            305 => "Use Proxy",
            306 => "(Unused)",
            307 => "Temporary Redirect",
            308 => "Permanent Redirect",
            400 => "Bad Request",
            401 => "Unauthorized",
            402 => "Payment Required",
            403 => "Forbidden",
            404 => "Not Found",
            405 => "Method Not Allowed",
            406 => "Not Acceptable",
            407 => "Proxy Authentication Required",
            408 => "Request Timeout",
            409 => "Conflict",
            410 => "Gone",
            411 => "Length Required",
            412 => "Precondition Failed",
            413 => "Request Entity Too Large",
            414 => "Request-URI Too Long",
            415 => "Unsupported Media Type",
            416 => "Requested Range Not Satisfiable",
            417 => "Expectation Failed",
            418 => "I'm a teapot",
            419 => "Authentication Timeout",
            420 => "Enhance Your Calm",
            422 => "Unprocessable Entity",
            423 => "Locked",
            424 => "Failed Dependency",
            424 => "Method Failure",
            425 => "Unordered Collection",
            426 => "Upgrade Required",
            428 => "Precondition Required",
            429 => "Too Many Requests",
            431 => "Request Header Fields Too Large",
            444 => "No Response",
            449 => "Retry With",
            450 => "Blocked by Windows Parental Controls",
            451 => "Unavailable For Legal Reasons",
            494 => "Request Header Too Large",
            495 => "Cert Error",
            496 => "No Cert",
            497 => "HTTP to HTTPS",
            499 => "Client Closed Request",
            500 => "Internal Server Error",
            501 => "Not Implemented",
            502 => "Bad Gateway",
            503 => "Service Unavailable",
            504 => "Gateway Timeout",
            505 => "HTTP Version Not Supported",
            506 => "Variant Also Negotiates",
            507 => "Insufficient Storage",
            508 => "Loop Detected",
            509 => "Bandwidth Limit Exceeded",
            510 => "Not Extended",
            511 => "Network Authentication Required",
            598 => "Network read timeout error",
            599 => "Network connect timeout error"
        );

        if (!empty($http_status_codes[$code])) {
            header($_SERVER["SERVER_PROTOCOL"] . " " . $code . " " .  $http_status_codes[$code]);
        }
        die($message);
    }
    /**
     * Get actions for a particular purpose.
     * @param for specifies the purpose.
     * @param active specifies which item should be marked as active.
     * @param orgid orgid for eduvidual-based moodle instances.
     */
    public static function get_actions($for, $active = '') {
        $actions = [];
        switch($for) {
            case 'usermaps':
                // Administrator managing usermappings.
                $actions[] = (object) [
                        'active' => ($active == 'landingusermaps'),
                        'label' => get_string('admin:usermaps:pagetitle', 'local_webuntis'),
                        'relativepath' => '/local/webuntis/landingusermaps.php',
                    ];
                $actions[] = (object) [
                        'active' => ($active == 'landingusersync::create'),
                        'label' => get_string('admin:usersync:usercreate', 'local_webuntis'),
                        'relativepath' => '/local/webuntis/usersync.php?action=create',
                    ];
                $actions[] = (object) [
                        'active' => ($active == 'landingusersync::purge'),
                        'label' => get_string('admin:usersync:userpurge', 'local_webuntis'),
                        'relativepath' => '/local/webuntis/usersync.php?action=purge',
                    ];
            break;
        }
        return $actions;
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
     * Detects if the page is loaded within an iframe.
     * @return boolean
     */
    public static function in_iframe() {
        return (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe');
    }

    /**
     * Determines if eduvidual is installed on this system.
     * @return version-number
     */
    public static function uses_eduvidual() {
        return $version = get_config('local_eduvidual', 'version');
    }

    /**
     * Set for a particular user session whether or not we are using webuntis.
     */
    public static function uses_webuntis() {
        return !empty(\local_webuntis\tenant::last_tenant_id());
    }
}
