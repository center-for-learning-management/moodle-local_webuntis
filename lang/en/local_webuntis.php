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

$string['admin:autocreate'] = 'Allow user creation';
$string['admin:autocreate:description'] = 'Allow creation of user accounts based on WebUntis user data.';
$string['admin:autocreate:syswarning'] = 'Attention: auto createn of user accounts is disabled by in the Moodle Website-Administration.';
$string['admin:pubkey:integration'] = 'Public Key / Integration';
$string['admin:pubkey:integration:description'] = 'The public key of Untis GmbH to transmit the oAuth credentials of tenants in the integration environment.';
$string['admin:pubkey:production'] = 'Public Key / Production';
$string['admin:pubkey:production:description'] = 'The public key of Untis GmbH to transmit the oAuth credentials of tenants in any production environment.';
$string['admin:usermaps:pagetitle'] = 'Usermapping';
$string['cachedef_application'] = 'Cache for local_webunits for whole application';
$string['cachedef_session'] = 'Cache for local_webunits for user session';
$string['disconnect:course'] = 'Remove WebUntis-Courselink';
$string['disconnect:description'] = 'Do you really want to unlink the user accounts of Webuntis and Moodle?';
$string['disconnect:user'] = 'Disconnect from webuntis';
$string['disconnected'] = 'Your accounts have been unlinked';
$string['eduvidual:autocreate'] = 'Allow creation of new Accounts';
$string['eduvidual:autoenrol'] = 'Automatically map webuntis user role to eduvidual';
$string['eduvidual:feature'] = 'Feature';
$string['eduvidual:features'] = 'eduvidual-Features';
$string['eduvidual:orgconfig'] = 'Enable eduvidual-specific features:';
$string['eduvidual:settings'] = 'Settings regarding organisations';
$string['endpointmissing'] = 'The oAuth-endpoint of the webuntis instance is unknown.';
$string['invalidinput'] = 'Invalid input.';
$string['invalidwebuntisinstance'] = 'The webuntis instance is invalid or has not been configured.';
$string['landing:pagetitle'] = 'Select course';
$string['landing:select_target'] = 'Please select from the following courses';
$string['landingmissing:description'] = 'Sorry, no courses have been linked yet.';
$string['landinguser:mapcurrent'] = 'Map <i>{$a->fullname}</i>';
$string['landinguser:mapother'] = 'Map existing user account';
$string['landinguser:mapnew'] = 'Create user account';
$string['landinguser:mapnew:notenoughdata:text'] = 'User account cannot be created due to insufficient profile data from WebUntis.';
$string['landinguser:mapnew:notenoughdata:showdetails'] = 'Show known profile data';
$string['landinguser:pagetitle'] = 'Map user accounts';
$string['landinguser:select_option'] = 'Please select one of the following options';
$string['pluginname'] = 'WebUntis Integration';
$string['privacy:metadata:local_webuntis_usermap'] = 'Stores user mappings between WebUntis and Moodle users';
$string['privacy:metadata:local_webuntis_usermap:school'] = 'The Name of the school in WebUntis';
$string['privacy:metadata:local_webuntis_usermap:candisconnect'] = 'Whether or not the user is allowed to remove this mapping';
$string['privacy:metadata:local_webuntis_usermap:email'] = 'The email of the user in WebUntis';
$string['privacy:metadata:local_webuntis_usermap:firstname'] = 'The firstname of the user in WebUntis';
$string['privacy:metadata:local_webuntis_usermap:lastaccess'] = 'The time when the user last accessed Moodle through WebUntis';
$string['privacy:metadata:local_webuntis_usermap:lastname'] = 'The lastname of the user in WebUntis';
$string['privacy:metadata:local_webuntis_usermap:remoteuserid'] = 'The UserID in WebUntis';
$string['privacy:metadata:local_webuntis_usermap:remoteuserrole'] = 'The UserRole in WebUntis';
$string['privacy:metadata:local_webuntis_usermap:tenant_id'] = 'The ID of the WebUntis Tenant';
$string['privacy:metadata:local_webuntis_usermap:timecreated'] = 'The time when the mapping was created';
$string['privacy:metadata:local_webuntis_usermap:userid'] = 'The UserID in Moodle';
$string['privacy:metadata:local_webuntis_usermap:userinfo'] = 'Additional user information, e.g. tokens';
$string['privacy:usermap'] = 'Usermap';
$string['proceed'] = 'Proceed';
$string['redirect_edit_landingpage'] = 'You have been automatically redirected to this course. If you want to change the target of this link, you can edit it <a href="{$a->editurl}">here</a>.';
$string['selectcourse_for_target'] = 'Select course for this target';
$string['tenant:settings'] = 'Settings regarding Webuntis Tenant';
$string['tenants'] = 'Tenants';
$string['usermap:failed'] = 'Moodle-User could not be linked to Webuntis-User.';
$string['usermap:success'] = 'Moodle-User successfully linked to Webuntis-User.';
