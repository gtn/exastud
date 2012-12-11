<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 exabis internet solutions <info@exabis.at>
*  All rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This module is based on the Collaborative Moodle Modules from
*  NCSA Education Division (http://www.ncsa.uiuc.edu)
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

// 
$string['exastud:use'] = 'Student Review benutzen';
$string['exastud:head'] = 'Klassenvorstand';
$string['exastud:editperiods'] = 'Perioden bearbeiten';
$string['exastud:uploadpicture'] = 'Logo uploaden';
//
$string['pluginname'] = 'Exabis Student Review';
$string['blocktitle'] = 'Exabis Student Review';
$string['modulename'] = 'Exabis Student Review';
$string['blockname'] = 'Exabis Student Review';
$string['configuration'] = 'Klasse konfigurieren';
$string['report'] = 'Bericht';
$string['periods'] = 'Perioden';
$string['review'] = 'Bewertung';
$string['pictureupload'] = 'Logo Upload';
$string['upload_picture'] = 'Lade ein schuleigenes Logo f&uuml;r den Bericht hoch';
$string['upload_success'] = 'Das neue Logo wurde erfolgreich hochgeladen!';
$string['availableusers'] = 'Verf&uuml;gbare Benutzer';
$string['teachers'] = 'Lehrer';
$string['members'] = 'Teilnehmer';
$string['errorinsertingclass'] = 'Fehler bei der Erstellung einer Klasse';
$string['redirectingtoclassinput'] = 'Keine Klasse definiert, Weiterleitung zur Eingabe';
$string['errorupdatingclass'] = 'Fehler bei der Aktualisierung der Klasse';
$string['editclassmemberlist'] = 'Klassenteilnehmer bearbeiten';
$string['editclassteacherlist'] = 'Lehrer bearbeiten';
$string['editclassname'] = 'Klassenname';
$string['editclasscategories'] = 'Beurteilungskategorien bearbeiten';
$string['noclassfound'] = NULL;
$string['configteacher'] = 'Lehrer in {$a}';
$string['configmember'] = 'Teilnehmer in {$a}';
$string['configcategories'] = 'Beurteilungskategorien in {$a}';
$string['errorinsertingstudents'] = 'Fehler beim Hinzuf&uuml;gen eines Sch&uuml;lers im Kurs';
$string['errorinsertingcategories'] = 'Fehler beim Hinzuf&uuml;gen einer Kategorie im Kurs';
$string['errorremovingstudents'] = 'Fehler beim L&ouml;schen eines Sch&uuml;lers im Kurs';
$string['errorinsertingteachers'] = 'Fehler beim Hinzuf&uuml;gen eines Lehrers im Kurs';
$string['errorremovingteachers'] = 'Fehler beim L&öuml;schen eines Lehrers im Kurs';
$string['errorremovingcategories'] = 'Fehler beim L&ouml;schen von Kategorien aus einem Kurs';
$string['back'] = 'zur&uuml;ck';
$string['periodinput'] = 'Periodeneingabe';
$string['redirectingtoperiodsinput'] = 'Keine Beurteilungsperiode gefunden, Weiterleitung zur Eingabe einer Beurteilungsperiode';
$string['errorinsertingperiod'] = 'Fehler beim Einf&uuml;gen einer Beurteilungsperiode';
$string['errorupdateingperiod'] = 'Fehler bei der Aktualisierung einer Beurteilungsperiode';
$string['perioddescription'] = 'Beschreibung';
$string['starttime'] = 'Beginn der Beurteilung';
$string['endtime'] = 'Ende der Beurteilung';
$string['newperiod'] = 'Neue Beurteilungsperiode';
$string['invalidperiodid'] = 'Falsche Beurteilungsperioden-ID';
$string['noclassestoreview'] = 'Keine Klasse zur Beurteilung';
$string['class'] = 'Klasse';
$string['reviewclass'] = 'Klassenbeurteilung';
$string['badclass'] = 'Sie k&ouml;nnen diese Klasse nicht beurteilen';
$string['nostudentstoreview'] = 'Keine Sch&uuml;ler zu beurteilen';
$string['reviewstudent'] = 'Student review';
$string['categories'] = 'Beurteilungskategorien';
$string['basiccategories'] = 'Standardkategorien';
$string['availablecategories'] = 'Verf&uuml;gbare Beurteilungskategorien';
$string['teamplayer'] = 'Teamf&auml;higkeit';
$string['responsibility'] = 'Verantwortlichkeit';
$string['selfreliance'] = 'Selbstst&auml;ndigkeit';
$string['evaluation'] = 'Evaluation';
$string['badstudent'] = 'Der Sch&uuml;ler ist nicht Mitglied dieser Klasse';
$string['errorupdatingstudent'] = 'Fehler beim Aktualisierung des Sch&uuml;lers';
$string['errorinsertingstudent'] = 'Fehler beim Einf&uuml;gen des Sch&uuml;lers';
$string['nostudentstoreport'] = 'Kein Sch&uuml;ler zu beurteilen';
$string['errorstarttimebeforeendtime'] = 'Beurteilungsperiode {$a->description} hat ein Enddatum vor dem Startdatum!';
$string['printversion'] = 'Druckversion';
$string['printall'] = 'alle drucken';
$string['periodoverlaps'] = 'Beurteilungsperiod {$a->period1} &uuml;berschneidet sich mit {$a->period2}';
$string['periodserror'] = 'Fehler bei der Konfiguration der Beurteilungsperioden';
$string['evaluation1'] = '1 - unzureichend';
$string['evaluation2'] = '2';
$string['evaluation3'] = '3';
$string['evaluation4'] = '4';
$string['evaluation5'] = '5';
$string['evaluation6'] = '6';
$string['evaluation7'] = '7';
$string['evaluation8'] = '8';
$string['evaluation9'] = '9';
$string['evaluation10'] = '10 - sehr gut';
$string['explainclassname'] = 'Hier k&ouml;nnen Sie den Klassennamen editieren';
$string['showall'] = 'Alle anzeigen';
$string['logosize'] = 'Der Logo-Banner sollte die Größe 840x100px haben. Außerdem ist Transparenz in PNG Bildern zu vermeiden, da es sonst beim Erstellen eines PDF Berichtes zu Fehlern kommen kann.';
$string['detailedreview'] = 'Ausführliche Beurteilung';