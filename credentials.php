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

require_once('../../config.php');

/**
 * As outputs of this programme can not be shown in any frontend,
 * you can route debug messages to the error_log. To do so, please
 * set debugging to true in the following line.
 */
$debugging = false;

if ($debugging) error_log("====================================");
if ($debugging) error_log("===== Getting the parameters");
$DATA = file_get_contents('php://input');

foreach (getallheaders() as $name => $value) {
    switch ($name) {
        case 'Algorithm':
            switch($value) {
                case 'SHA256': $ALGORITHM = OPENSSL_ALGO_SHA256; break;
                case 'SHA256withRSA': $ALGORITHM = 'sha256WithRSAEncryption'; break;
                default:
                    $ALGORITHM = $value;
            }
        break;
        case 'Authorization':
            $SIGNATURE = base64_decode($value);
        break;
    }
}

if ($debugging) error_log("Data $DATA");
if ($debugging) error_log("Signature $SIGNATURE");
if ($debugging) error_log("Algorithm $ALGORITHM");

if (empty($SIGNATURE) || empty($ALGORITHM)) {
    throw new moodle_exception('algorithm or signature missing');
}

// Verifying signature.
$pubkeys = [
    'production' => get_config('local_webuntis', 'pubkey_production'),
    'integration' => get_config('local_webuntis', 'pubkey_integration'),
];
$verified = false;

foreach ($pubkeys as $identifier => $pubkey) {
    if ($debugging) error_log("===== Testing pubkey of $identifier: $pubkey");

    $verified = openssl_verify($DATA, $SIGNATURE, $pubkey, $ALGORITHM);
    error_log("VERIFIED $verified with $identifier using algorithm $ALGORITHM");

    if (!$verified) {
        if ($debugging) error_log("Verification of signature failed using public key of $identifier");
    } else {
        if ($debugging) error_log("Signature verified using public key of $identifier");
        break;
    }
}

// Updating database.
if ($verified) {
    $tenant = json_decode($DATA);
    if (!empty($tenant->tenantId)) {
        if ($debugging) error_log("There was valid JSON-Data for tenant {$tenant->tenantId}");
        $obj = $DB->get_record('local_webuntis_tenant', [ 'tenant_id' => $tenant->tenantId ]);
        if (!empty($obj->id)) {
            $obj->school = $tenant->schoolName;
            $obj->client = $tenant->clientId;
            $obj->consumerkey = $tenant->secret;
            $obj->consumesecret = $tenant->password;
            $obj->timemodified = time();
            $DB->update_record('local_webuntis_tenant', $obj);
            if ($debugging) error_log("Tenant {$obj->tenant_id} updated");
        } else {
            $obj = (object) [
                'tenant_id' => $tenant->tenantId,
                'school' => $tenant->schoolName,
                'host' => '',
                'client' => $tenant->clientId,
                'consumerkey' => $tenant->secret,
                'consumersecret' => $tenant->password,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $obj->id = $DB->insert_record('local_webuntis_tenant', $obj);
            if ($debugging) error_log("Tenant {$obj->tenant_id} inserted");
        }
    }
}

if ($debugging) error_log("====================================");
