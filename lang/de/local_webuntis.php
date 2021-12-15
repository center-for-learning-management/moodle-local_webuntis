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

$string['admin:autocreate'] = 'Erlaube Kontenerstellung';
$string['admin:autocreate:description'] = 'Erlaube die Erstellung von Nutzerkonten auf Basis von WebUntis-Kontodaten.';
$string['admin:autocreate:syswarning'] = 'Achtung: Die Erstellung von Nutzerkonten wurde durch die Moodle Website-Administration deaktiviert.';
$string['admin:pubkey:integration'] = 'Public Key / Integration';
$string['admin:pubkey:integration:description'] = 'Der öffentliche Schlüssel von Untis GmbH, um oAuth-Zugangsdaten für Tenants der Integrationsumgebung zu übermitteln.';
$string['admin:pubkey:production'] = 'Public Key / Produktion';
$string['admin:pubkey:production:description'] = 'Der öffentliche Schlüssel von Untis GmbH, um oAuth-Zugangsdaten für Tenants einer Produktionsumgebung zu übermitteln.';
$string['admin:usermaps:pagetitle'] = 'Nutzer/innen';
$string['admin:usersync:createall'] = 'Erstellen';
$string['admin:usersync:created'] = 'erstellt';
$string['admin:usersync:createuser'] = 'Nutzerkonto erstellen';
$string['admin:usersync:existsbutnotmapped'] = 'Existiert, aber nicht zugeordnet';
$string['admin:usersync:missingdata'] = 'Daten unvollständig';
$string['admin:usersync:purgeall'] = 'Entfernen';
$string['admin:usersync:purged'] = 'entfernt';
$string['admin:usersync:purgeuser'] = 'Nutzerkonto entfernen';
$string['admin:usersync:pagetitle'] = 'Nutzersynchronisation';
$string['admin:usersync:rolesall'] = 'Setze Rollen';
$string['admin:usersync:rolesuser'] = 'Setze Rolle';
$string['admin:usersync:selectorg'] = 'Nutzerkonten mit folgender Schule abgleichen:';
$string['admin:usersync:usercreate'] = 'Nutzerkonten erstellen';
$string['admin:usersync:userpurge'] = 'Nutzerkonten entfernen';
$string['admin:usersync:userpurge:confirm:title'] = 'Wirklich entfernen?';
$string['admin:usersync:userpurge:confirm:text'] = 'Sie sind im Begriff eine große Anzahl an Nutzerkonten zu entfernen. Sind Sie sicher, dass Sie das tun möchten? Durch diese Aktion können Daten von Nutzer/innen verloren gehen.';
$string['admin:usersync:userroles'] = 'Nutzerrollen verwalten';
$string['admin:usersync:userroles:confirm:title'] = 'Wirklich durchführen?';
$string['admin:usersync:userroles:confirm:text'] = 'Sie sind im Begriff die Rolle einer großen Anzahl an Nutzerkonten zu ändern (möglicherweise auch Ihrer eigenen). Sind Sie sicher, dass Sie das tun möchten?';
$string['disconnect:course'] = 'WebUntis-Kursverknüpfung lösen';
$string['disconnect:description'] = 'Wollen Sie wirklich die Nutzerkonten von WebUntis und Moodle trennen?';
$string['disconnect:user'] = 'WebUntis trennen';
$string['disconnected'] = 'Die Verbindung zwischen den Konten wurde getrennt!';
$string['eduvidual:autocreate'] = 'Erlaube Erstellung neuer Konten';
$string['eduvidual:autoenrol'] = 'WebUntis-Nutzerrolle automatisch auf Schule übertragen';
$string['eduvidual:connect_org'] = 'Wählen Sie die Schulen zu dieser diese WebUntis Instanz gehört';
$string['eduvidual:connected'] = 'Zugehörig';
$string['eduvidual:feature'] = 'Funktion';
$string['eduvidual:features'] = 'eduvidual-Funktionen';
$string['eduvidual:management'] = 'WebUntis Verwaltung';
$string['eduvidual:orgs'] = 'Schulen';
$string['eduvidual:orgconfig'] = 'Verwaltung eduvidual-spezifischer Funktionen:';
$string['eduvidual:settings'] = 'Einstellungen zur Schulorganisation';
$string['endpointmissing'] = 'Der oAuth-Endpunkt der Webuntis-Instanz ist unbekannt.';
$string['exception:already_connected'] = 'Dieses Nutzerkonto aus WebUntis ist bereits mit einem Moodle-Konto verknüpft!';
$string['exception:already_exists'] = 'Nutzerkonto für diese Daten existiert bereits!';
$string['exception:invalid_data'] = 'Ungültige Daten angegeben!';
$string['exception:permission_denied'] = 'Zugriff nicht erlaubt!';
$string['invalidinput'] = 'Ungültige Eingabe.';
$string['invalidwebuntisinstance'] = 'Die Webuntis-Instanz scheint ungültig zu sein oder wurde noch nicht konfiguriert.';
$string['landing:pagetitle'] = 'Kurs auswählen';
$string['landing:select_target'] = 'Bitte wählen Sie aus den folgenden Kursen';
$string['landingmissing:description'] = 'Leider wurden an dieser Stelle noch keine Kurse verknüpft.';
$string['landinguser:mapcurrent'] = '<i>{$a->fullname}</i> verknüpfen';
$string['landinguser:mapother'] = 'Bestehendes Konto verknüpfen';
$string['landinguser:mapnew'] = 'Konto erstellen';
$string['landinguser:mapnew:notenoughdata:text'] = 'Ein Nutzerkonto kann aufgrund fehlender Profildaten von WebUntis nicht erstellt werden.';
$string['landinguser:mapnew:notenoughdata:showdetails'] = 'Zeige bekannte Profildaten';
$string['landinguser:pagetitle'] = 'Nutzerkonten verknüpfen';
$string['landinguser:select_option'] = 'Bitte wählen Sie eine der folgenden Optionen';
$string['loginexternal'] = 'Öffne in neuem Tab';
$string['loginexternal:description'] = 'Diese Loginmethode erfordert, dass der Login einmalig in einem neuen Tab ausgeführt wird. Klicken Sie den folgenden Button, um den Login zu starten!';
$string['pluginname'] = 'WebUntis Integration';
$string['proceed'] = 'Weiter';
$string['redirect_edit_landingpage'] = 'Sie wurden automatisch in diesen Kurs weitergeleitet. Falls Sie das Ziel dieses Links ändern möchten, klicken Sie bitte <a href="{$a->editurl}">hier</a>.';
$string['selectall'] = 'alle/keine';
$string['selectcourse_for_target'] = 'Wählen Sie einen Kurs für dieses Ziel';
$string['sync'] = 'Synchronisiere';
$string['sync_org'] = 'Synchronisiere mit {$a->name}';
$string['tenant:settings'] = 'Einstellungen zum Webuntis Tenant';
$string['tenants'] = 'Tenants';
$string['usermap:failed'] = 'Moodle-Konto konnte nicht mit WebUntis-Konto verknüpft werden.';
$string['usermap:success'] = 'Moodle-Konto wurde erfolgreich mit WebUntis-Konto verknüpft.';
