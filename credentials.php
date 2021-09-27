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

error_log("credentials.php was called");
error_log(print_r($_REQUEST));

require_once('../../config.php');

$encrypted_data = file_get_contents('php://input');


error_log("Encrypted: " . $encrypted_data);
die("FERTICH");

$pubkey = get_config('local_webuntis', (strpos($_SERVER['HTTP_REFERER'], 'integration.webuntis.com') > 0) ? 'pubkey_integration' : 'pubkey_production');


//$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND);
//$decrypted_data mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $pubkey, hex2bin($encrypted_data), MCRYPT_MODE_ECB, $iv);

die($decrypted_data);
error_log("Decrypted: " . $decrypted_data);
