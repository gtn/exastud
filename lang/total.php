<?php
// This file is part of Exabis Student Review
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Student Review is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

return [
	// shown in admin plugin list
	'pluginname' => [
		'Exabis Lernentwicklungsbericht',
		'Exabis Student Review',
	],
	// shown in block title and all headers
	'blocktitle' => [
		'Lernentwicklungsbericht',
		'Student Review',
	],


	// === grp1 ===
	'exastud:use' => [
		'Student Review benutzen',
		'Use of Exabis Student Review',
	],
	'exastud:editperiods' => [
		'Einstellungen bearbeiten',
		'Edit periods',
	],
	'exastud:admin' => [
		'Administrator',
		'Administrator',
	],
	'exastud:uploadpicture' => [
		'Logo uploaden',
		'Upload logo',
	],
	'exastud:addinstance' => [
		'Student Review auf Kursseite anlegen',
		'Add Exabis Student Review to the course',
	],
	'exastud:myaddinstance' => [
		'Student Review auf Startseite anlegen',
		'Add Exabis Student Review on My home',
	],


	// === grp2 ===
	'head_teacher' => [
		'Klassenlehrkraft',
		'Class teacher',
	],
	'head_teachers' => [
		'Klassenlehrkräfte',
		'Class teachers',
	],
	'head_teachers_description' => [
		'Können Klassen anlegen, Lehrkräfte und Schüler/innen zubuchen und den Lernentwicklungsbericht abrufen',
		'Can create classes, assign teachers and students and view the learning development report',
	],
	'new_head_teacher' => [
		'Neue Klassenlehrkraft zuweisen',
		'New class teachers',
	],
	'additional_head_teacher' => [
		'Zusätzliche Klassenlehrkraft',
		'Additional class teacher',
	],
	'additional_head_teachers' => [
		'Zusätzliche Klassenlehrkräfte',
		'Additional class teachers',
	],
	'configuration_classes' => [
		'Klassen',
		'Classes',
	],
	'configuration_classes_onlyadmin' => [
		'Admin access',
		'Admin access',
	],
	'project_based_configuration' => [
		'Projekt konfigurieren',
		'Edit project',
	],
	'report' => [
		'Bericht',
		'Report',
	],
	'reports' => [
		'Berichte exportieren',
		'Reports',
	],
	'reports_server_notification' => [
		'Falls der Bericht nicht ordnungsgemäß generiert wird, könnte dies auf eine zu große Datenmenge (timeout) hinweisen. Bitte drucken Sie in diesem Fall nicht die ganze Klasse, sondern jeweils nur eine Teilauswahl.',
		'If the report is not generated correctly, it could indicate an excessive amount of data or a server setting that is too low. Please contact the administrator of the server in this case.',
	],
	'periods' => [
		'Eingabezeiträume',
		'Periods',
	],
	'review' => [
		'Berichte befüllen',
		'Review',
	],
    'review_button' => [
        'Bewerten',
        'Review',
    ],
	'pictureupload' => [
		'Logo Upload',
		'Logo Upload',
	],
	'upload_picture' => [
		'Laden Sie das große Landeswappen für das Abschlusszeugnis hoch.',
		'You can upload a logo for a school-specific report',
	],
	'upload_success' => [
		'Das neue Logo wurde erfolgreich hochgeladen!',
		'The new logo was uploaded successfully!',
	],
	'availableusers' => [
		'Verfügbare Benutzer',
		'Available Users',
	],
	'teachers' => [
		'Lehrkräfte',
		'Teachers',
	],
	'teachers_options' => [
		'Zuständige Lehrkraft',
		'Teacher Options',
	],
	'project_based_teachers' => [
		'Projektbeurteiler',
		'Project-Teachers',
	],
    'attention_plan_will_change' => [
        'Bitte beachten Sie: Bei einer Änderung des Bildungsplans müssen alle Bewertungen erneut eingegeben werden.',
        'Please note: if the education plan is changed, all ratings must be re-entered',
    ],
    'attention_template_will_change' => [
        'Bitte beachten Sie: Bei einer Änderung des Standard Zeugnisformulars wird für alle Schüler das Zeugnisformular geändert. Bestehende Formulareinstellungen wie z.B. Abgangszeugnis werden beibehalten.',
        'Please note: changing the standard certificate form will change the certificate form for all students.',
    ],
	'class_info' => [
		'Klasseneinstellungen',
		'Edit Class',
	],
	'export_class' => [
		'Sicherung der Klasse erstellen',
		'Backup assessment data of class',
	],
	'export_class_password_message' => [
		'Bitte notieren Sie sich das Passwort "<strong>{$a}</strong>", bevor Sie fortfahren',
		'Please remember the password "<strong>{$a}</strong>" before proceeding',
	],
	'students' => [
		'Schülerinnen und Schüler',
		'Students',
	],
	'studentgradereports' => [
		'Schülerzeugnisse',
		'Grade Reports',
	],
	'no_entries_found' => [
		'Keine Einträge gefunden',
		'No Entries found',
	],
	'project_based_students' => [
		'Projekte',
		'Projects',
	],
	'errorinsertingclass' => [
		'Fehler bei der Erstellung einer Klasse',
		'Error when inserting class',
	],
	'redirectingtoclassinput' => [
		'Keine Klasse definiert, Weiterleitung zur Eingabe',
		'No class information found, redirecting to class input.',
	],
	'project_based_redirectingtoclassinput' => [
		'Kein Projekt definiert, Weiterleitung zur Eingabe',
		'No project information found, redirecting to project input.',
	],
	'errorupdatingclass' => [
		'Fehler bei der Aktualisierung der Klasse',
		'Error when updating class',
	],
	'project_based_errorupdatingclass' => [
		'Fehler bei der Aktualisierung des Projekts',
		'Error when updating project',
	],
	'editclassmemberlist' => [
		'Schüler/innen bearbeiten',
		'Edit student list',
	],
	'add_class_from_course' => [
		'Aus Kurs hinzufügen',
		'Add from Course',
	],
	'project_based_editclassmemberlist' => [
		'Projekte bearbeiten',
		'Edit project list',
	],
	'editclassteacherlist' => [
		'Lehrkräfte bearbeiten',
		'Edit teacher list',
	],
	'project_based_editclassteacherlist' => [
		'Beurteiler bearbeiten',
		'Edit teacher list',
	],
	'editclassname' => [
		'Klassenname',
		'Edit class',
	],
	'project_based_editclassname' => [
		'Projektname',
		'Edit project',
	],
	'editclasscategories' => [
		'Beurteilungskategorien bearbeiten',
		'Edit assessment categories',
	],
	'noclassfound' => [
		'Keine Klasse gefunden',
		'No class found!',
	],
	'project_based_noclassfound' => [
		'Kein Projekt gefunden',
		'No project found!',
	],
	'configteacher' => [
		'Lehrkräfte in {$a}',
		'Teachers in {$a}',
	],
	'project_based_configteacher' => [
		'Beurteiler in {$a}',
		'Teachers in {$a}',
	],
	'configmember' => [
		'Teilnehmer in {$a}',
		'Students of {$a}',
	],
	'project_based_configmember' => [
		'Projekte in {$a}',
		'Students of {$a}',
	],
	'configcategories' => [
		'Beurteilungskategorien in {$a}',
		'Assessment categories in {$a}',
	],
	'errorinsertingstudents' => [
		'Fehler beim Hinzufügen eines Schülers im Kurs',
		'Error occured when adding student to a course',
	],
	'errorinsertingcategories' => [
		'Fehler beim Hinzufügen einer Kategorie im Kurs',
		'Error occured when adding categorie to a course',
	],
	'errorremovingstudents' => [
		'Fehler beim Löschen eines Schülers im Kurs',
		'Error occured when removing student from a course',
	],
	'errorremovingcategories' => [
		'Fehler beim Löschen von Kategorien aus einem Kurs',
		'Error occured when removing categories from a course',
	],
	'back' => [
		'zurück',
		'Back',
	],
	'periodinput' => [
		'Zeitraumeingabe',
		'Periods input',
	],
	'redirectingtoperiodsinput' => [
		'Keine Eingabezeiträume gefunden, Weiterleitung zur Eingabe eines Eingabezeitraums',
		'No period information found, redirecting to periods input',
	],
	'perioddescription' => [
		'Beschreibung',
		'Description',
	],
	'starttime' => [
		'Startdatum',
		'Start time',
	],
	'endtime' => [
		'Enddatum',
		'End time',
	],
	'newperiod' => [
		'Neuer Beurteilungszeitraum',
		'New period',
	],
	'invalidperiodid' => [
		'Falsche Beurteilungszeitraums-ID',
		'Invalid period-ID',
	],
	'certificate_issue_date' => [
		'Zeugnisausgabedatum',
		'Certificate Issue Date',
	],
	'certificate_issue_date_class' => [
		'Zeugnisdatum',
		'Certificate Issue Date',
	],
	'noclassestoreview' => [
		'Keine Klasse zur Beurteilung',
		'No classes to review',
	],
	'project_based_noclassestoreview' => [
		'Kein Projekt zur Beurteilung',
		'No projects to review',
	],
	'class' => [
		'Klasse',
		'Class',
	],
	'class_title_limit_message' => [
		'Max. {$a} Zeichen.',
		'Max. {$a} chars.',
	],
    'class_title_for_report' => [
        'Klassenname im Zeugnis',
        'Class name in Report'
    ],
    'class_title_for_report_description' => [
        'Dieser Klassenname wird im Zeugnis angezeigt. Wenn hier kein Wert eingefügt wird, wird obige Klassenbezeichnung verwendet.',
        'This class title is used in reports. If this field is empty, in reports the title of class above is used'
    ],
    'class_educationplan' => [
        'Bildungsplan',
        'Educational plan'
    ],
    'class_default_template' => [
        'Standard Zeugnisformular',
        'Default template'
    ],
	'class_owner' => [
		'Neue Klassenlehrkraft',
		'Current class teacher',
	],
	'class_logo' => [
		'Class logo',
		'Class logo',
	],
	'school_logo' => [
		'Schullogo',
		'School logo',
	],
	'school_logo_description' => [
		'maximale Bildgröße: {$a->width}x{$a->height} Pixel',
		'Maximum size of image: {$a->width}x{$a->height} pixels',
	],
	'class_delete' => [
		'Klasse löschen',
		'Delete class',
	],
	'project_based_class' => [
		'Projekt',
		'Project',
	],
	'reviewclass' => [
		'Klassenbeurteilung',
		'Class review',
	],
	'project_based_reviewclass' => [
		'Projektbeurteilung',
		'Project review',
	],
	'badclass' => [
		'Sie können diese Klasse nicht beurteilen',
		'You cannot edit this class',
	],
	'project_based_badclass' => [
		'Sie können dieses Projekt nicht beurteilen',
		'You cannot edit this project',
	],
	'nostudentstoreview' => [
		'Keine Schüler zu beurteilen',
		'No students to review',
	],
	'reviewstudent' => [
		'Student review',
		'Student review',
	],
	'categories' => [
		'Beurteilungskategorien',
		'Assessment categories',
	],
    'addallbasic' => [
        'Standardkategorien hinzufügen',
        'Add basic categories',
    ],
    'addallbasicalways' => [
        'Alle neuen Standardkategorien automatisch zuordnen',
        'Add all new basic categories automatically',
    ],
	'basiccategories' => [
		'Standardkategorien',
		'Basic categories',
	],
	'availablecategories' => [
		'Verfügbare Beurteilungskategorien',
		'Available assessment categories',
	],
	'teamplayer' => [
		'Teamfähigkeit',
		'Team player',
	],
	'responsibility' => [
		'Verantwortlichkeit',
		'Responsibility',
	],
	'selfreliance' => [
		'Selbstständigkeit',
		'Self-reliance',
	],
	'evaluation' => [
		'Evaluation',
		'Evaluation',
	],
	'badstudent' => [
		'Der Schüler ist nicht Mitglied dieser Klasse',
		'The student is not member of this class',
	],
	'project_based_badstudent' => [
		'Der Schüler nimmt nicht an diesem Projekt teil.',
		'The student is not member of this project',
	],
	'errorupdatingstudent' => [
		'Fehler beim Aktualisierung des Schülers',
		'Error occured when updating student',
	],
	'errorinsertingstudent' => [
		'Fehler beim Einfügen des Schülers',
		'Error occured when inserting student',
	],
	'nostudentstoreport' => [
		'Kein Schüler zu beurteilen',
		'No students to report',
	],
	'nostudentsfound' => [
		'Keine Schüler gefunden.',
		'No students found',
	],
	'errorstarttimebeforeendtime' => [
		'Eingabezeitraum {$a->description} hat ein Enddatum vor dem Startdatum!',
		'Period {$a->description} has a start time before an end time!',
	],
	'printversion' => [
		'Druckversion',
		'Print version',
	],
	'printall' => [
		'Alle drucken',
		'Print all',
	],
	'periodoverlaps' => [
		'Eingabezeitraum {$a->period1} überschneidet sich mit {$a->period2}',
		'Period {$a->period1} overlaps with {$a->period2}',
	],
	'periodserror' => [
		'Fehler bei der Konfiguration der Eingabezeiträume',
		'Error with the configuration of periods',
	],
	'evaluation1' => [
		'1 - unzureichend',
		'1 - insufficient',
	],
	'evaluation2' => [
		'2',
		'2',
	],
	'evaluation3' => [
		'3',
		'3',
	],
	'evaluation4' => [
		'4',
		'4',
	],
	'evaluation5' => [
		'5',
		'5',
	],
	'evaluation6' => [
		'6',
		'6',
	],
	'evaluation7' => [
		'7',
		'7',
	],
	'evaluation8' => [
		'8',
		'8',
	],
	'evaluation9' => [
		'9',
		'9',
	],
	'evaluation10' => [
		'10 - sehr gut',
		'10 - very good',
	],
	'explainclassname' => [
		'Hier können Sie den Klassennamen editieren und löschen',
		'Edit class name here',
	],
	'project_based_explainclassname' => [
		'Hier können Sie den Projektnamen editieren',
		'Click here to edit the project name',
	],
	'showall' => [
		'Alle anzeigen',
		'Show all',
	],
	'logosize' => [
		'',
		'For efficient use the logo banner must be 840x100px. Please avoid using transparency in PNG files as they may cause an error while creating a PDF report.',
	],
	'detailedreview' => [
		'Ausführliche Beurteilung',
		'Detailed review',
	],
	'studentreview' => [
		'SCHÜLERBEWERTUNG',
		'STUDENT REVIEW',
	],
	'project_based_studentreview' => [
		'PROJEKTBEWERTUNG',
		'PROJECT REVIEW',
	],
	'name' => [
		'Name',
		'Name',
	],
	'periodreview' => [
		'Bewertung für den Eingabezeitraum',
		'Review for period',
	],
	'reviewcount' => [
		'Bewertungen abgegeben',
		' review(s) yet',
	],
	'print' => [
		'Drucken',
		'Print',
	],
	'perioddesc' => [
		'Beschreibung des Eingabezeitraums',
		'Description of the period: ',
	],
	'noperiods' => [
		'Es gibt noch keinen Eingabezeitraum. Bitte wenden Sie sich an den Administrator.',
		'There is no assessment-period defined yet. Please contact the administrator.',
	],
	'commentshouldnotbeempty' => [
		'Der Kommentar darf nicht leer sein.',
		'You have to enter a comment.',
	],
    'report_template' => [
            'Vorlage',
            'Template',
    ],
    'report_preview' => [
            'Bildschirmausgabe',
            'Preview'
    ],
    'report_select_all' => [
            'Alles markieren',
            'Select all'
    ],
    'select_all' => [
            'Alles markieren',
            'Select all'
    ],
    'hide_all' => [
            'alle ausblenden',
            'Hide all'
    ],
    'show_all' => [
            'alle einblenden',
            'Show all'
    ],
    'report_delete' => [
        'Delete report: {$a}',
        'Delete report: {$a}',
    ],
    'report_delete_confirm_message' => [
        'Are you sure that you want to delete report "{$a}"',
        'Are you sure that you want to delete report "{$a}"',
    ],
    'report_selectdeselect_all' => [
            'Alles aus-/abwählen',
            'Select/Deselect all',
    ],
    'report_settings' => [
            'Berichtskonfiguration',
            'Report settings',
    ],
    'report_settings_edit' => [
            'Berichts-Konfigurationen',
            'Report configurations',
    ],
    'report_settings_new' => [
            'Neue Berichts-Konfiguration hinzufügen',
            'New report configuration',
    ],
    'report_settings_setting_id' => [
            '',
            '',
    ],
    'report_settings_setting_title' => [
            'Titel',
            'Title',
    ],
    'report_settings_setting_bp' => [
            'BP',
            'Curriculum',
    ],
    'report_settings_setting_hidden' => [
            'Versteckt',
            'Hidden',
    ],
    'report_settings_setting_rs_hs' => [
            'Schnittberechnung maßgebliches Fach',
            'RS/HS category',
    ],
    'report_settings_setting_category' => [
            'Kategorie',
            'Category',
    ],
    'report_settings_setting_template' => [
            'Formular',
            'Template',
    ],
    'report_settings_setting_grades' => [
            'Notenskala',
            'Grades',
    ],
    'report_settings_setting_year' => [
            'Schuljahr',
            'School year',
    ],
    'report_settings_setting_reportdate' => [
            'Zeugnisdatum',
            'Date for report card',
    ],
    'report_settings_setting_studentname' => [
            'Vor- und Zuname',
            'First and second name',
    ],
    'report_settings_setting_dateofbirth' => [
            'Geburtsdatum',
            'Date of birth',
    ],
    'report_settings_setting_placeofbirth' => [
            'Geburtsort',
            'Place of birth',
    ],
    'report_settings_setting_learninggroup' => [
            'Lerngruppe',
            'Learning group',
    ],
    'report_settings_setting_class' => [
            'Klasse',
            'Class',
    ],
    'report_settings_setting_focus' => [
            'Schwerpunkt',
            'Focus',
    ],
    'report_settings_setting_learnsocialbehavior' => [
            'Lern- u. Sozialverhalten',
            'Learning and social behavior',
    ],
    'report_settings_setting_subjects' => [
            'Fächer',
            'Subjects',
    ],
    'report_settings_setting_comments' => [
            'Bemerkungen',
            'Comments',
    ],
    'report_settings_setting_subjectelective' => [
            'Wahlpflichtfach',
            'Elective subject',
    ],
    'report_settings_setting_subjectprofile' => [
            'Profilfach',
            'Profile subject',
    ],
    'report_settings_setting_projektthema' => [
            'Projektprüfung',
            'Project assessment',
    ],
    'report_settings_setting_ags' => [
            'AGs',
            'Team',
    ],
    'report_settings_setting_additional_params' => [
            'weitere Parameter',
            'Additional parameters',
    ],
    'report_settings_no' => [
            'nein',
            'no',
    ],
    'report_settings_yes' => [
            'ja',
            'yes',
    ],
    'report_settings_countrows' => [
            '{$a} Zeilen',
            '{$a} rows',
    ],
    'report_settings_countinrow' => [
            'zu je {$a} Zeichen',
            'with {$a} characters per row',
    ],
    'report_settings_countinrow_short' => [
            'zu je {$a} Zeichen',
            '{$a} chars ',
    ],
    'report_settings_maxchars_short' => [
            '{$a} z.',
            'max {$a} chars',
    ],
    'report_settings_maxchars' => [
            '{$a} Zeichen',
            'with a maximum of {$a} characters',
    ],
    'report_settings_countrows_fieldtitle' => [
                'Zeilen',
                'Count of rows',
    ],
    'report_settings_countinrow_fieldtitle' => [
            'zu je Zeichen',
            'Characters per row',
    ],
    'report_settings_maxchars_fieldtitle' => [
            'Zeichen',
            'Maximum of characters',
    ],
    'report_settings_button_add_additional_param' => [
            'Neuen Parameter hinzufügen',
            'Add a new parameter',
    ],
    'report_settings_selectboxkey_fieldtitle' => [
            'Schlüssel',
            'Key',
    ],
    'report_settings_selectboxvalue_fieldtitle' => [
            'Wert',
            'Value',
    ],
    'delete_parameter' => [
            'Parameter löschen',
            'Delete parameter',
    ],
    'sort_parameter' => [
            'Sort parameters (move)',
            'Sort parameters (move)',
    ],
    'move_here' => [
        'Move here',
        'Move here',
    ],
    'delete' => [
            'Löschen',
            'Delete',
    ],
    'add' => [
            'Hinzufügen',
            'Add',
    ],
    'report_setting_type_textarea' => [
            'Textbereich',
            'Textarea',
    ],
    'report_setting_type_text' => [
            'Textfeld',
            'Text',
    ],
    'report_setting_type_select' => [
            'Auswahl',
            'Select',
    ],
    'report_setting_type_header' => [
            'Kopfzeile',
            'Header',
    ],
    'report_setting_type_image' => [
            'Bild',
            'Picture',
    ],
    'report_setting_type_image_maxbytes' => [
            'Max size (bytes)',
            'Max size (bytes)',
    ],
    'report_setting_type_image_width' => [
            'Breite',
            'Width',
    ],
    'report_setting_type_image_height' => [
            'Höhe',
            'Height',
    ],
    'report_setting_type_userdata' => [
            'Profilfeld',
            'Profile field',
    ],
    'report_setting_type_userdata_datakey' => [
            'Voranngelegtes Profilfeld auswählen',
            'Choose user\'s field',
    ],
    'report_setting_type_matrix' => [
            'Matrix',
            'Matrix',
    ],
    'report_setting_type_matrix_type' => [
        'Matrix-Art',
        'Matrix type',
    ],
    'report_setting_type_matrix_type_checkbox' => [
        'Checkbox',
        'Checkbox',
    ],
    'report_setting_type_matrix_type_radio' => [
        'Radio-Button',
        'Radio button',
    ],
    'report_setting_type_matrix_type_text' => [
        'Text',
        'Text',
    ],
    'report_setting_type_matrix_row_titles' => [
        'Zeilen-Inhalte',
        'Row titles',
    ],
    'report_setting_type_matrix_column_titles' => [
        'Spalten-Inhalte',
        'Column titles',
    ],
    'reset_report_templates' => [
            'Standardvorlagen auf Originalwerte zurücksetzen',
            'Reset default templates to default state',
    ],
    'reinstall_report_templates' => [
            'Standardvorlagen neu installieren',
            'Reinstall all default templates',
    ],
    'reset_report_selected_templates' => [
            'Ausgewählte Vorlagen auf Originalwerte zurücksetzen',
            'Reset selected templates to default state',
    ],
    'reset_report_templates_description' => [
            'Sind Sie sicher? Standardvorlagen werden auf die Originalwerte zurückgesetzt. Benutzerdefinierte Vorlagen bleiben unverändert.',
            'Are you sure? Default templates will be reset to default state. Custom templates will not be changed',
    ],
    'report_setting_current_title' => [
            'Bisheriger Name',
            'Current title',
    ],
    'report_setting_current_file' => [
            'Current template',
            'Current template',
    ],
    'report_setting_willbe_added' => [
            'Vorlage existiert nicht und wird hinzugefügt. Falls die Vorlage gelöscht wurde kann der Administrator diese erneut hinzufügen.',
            'Does not exist now. Will be added.',
    ],
    'report_button_import' => [
        'Vorlagen importieren',
        'Import reports',
    ],
    'report_button_export' => [
        'Vorlagen exportieren',
        'Export reports',
    ],
    'report_export_selected_templates' => [
        'Ausgewählte Vorlagen exportieren',
        'Export selected',
    ],
    'report_export_with_files' => [
        'Add sources (files) of templates',
        'Add sources (files) of templates',
    ],
    'report_export_update_reports' => [
        'vorhandene Vorlagen aktualisieren',
        'Update reports if existant',
    ],
    'report_export_update_files' => [
        'vorhandene Vorlage-Dateien aktualisieren',
        'Update files if existant',
    ],
    'report_import_templates' => [
        'Import',
        'Import',
    ],
    'report_import_file_shouldnotbeempty' => [
            'Dateiname fehlt.',
            'File should not be empty.',
    ],
    'report_import_inserted_list' => [
            'Folgende Vorlagen wurden hinzugefügt',
            'These reports were inserted',
    ],
    'report_import_updated_list' => [
            'Folgende Vorlagen wurden aktualisiert',
            'These reports were updated',
    ],
    'report_import_ignored_list' => [
            'Folgende Vorlagen wurden nicht berücksichtigt',
            'These reports were ignored',
    ],
    'report_settings_upload_new_filetemplate' => [
        'Neue Vorlage hochladen',
        'Upload new template file',
    ],
    'report_settings_upload_new_filetemplate_overwrite' => [
        'bestehende Datei überschreiben',
        'Overwrite file if existant',
    ],
    'upload_new_templatefile' => [
        'Neue Vorlage hochladen',
        'Upload new template',
    ],
    'hide_uploadform' => [
        'Schließen',
        'Hide upload form',
    ],
    'report_settings_copy' => [
        'Copy report',
        'Copy report',
    ],
    'report_settings_copy_newtitle' => [
        '{$a->title} - COPY !!!',
        '{$a->title} - COPY !!!',
    ],
    'report_settings_copy_done' => [
        'Created a new report "{$a->newtitle}" (id: {$a->newid}) from "{$a->sourcetitle}" (id: {$a->sourceid})',
        'Created a new report "{$a->newtitle}" (id: {$a->newid}) from "{$a->sourcetitle}" (id: {$a->sourceid})',
    ],
    'select_student' => [
        'Bitte wählen Sie zumindest einen Schüler/eine Schülerin aus',
        'Please select at least one student',
    ],
    'not_enough_data_for_report' => [
        'Für den Schüler/die Schülerin wurden noch keine Bewertungen für das Zeugnis erfasst!',
        'Not enough data for generating reports for selected students',
    ],
    'review_table_part_subjects' => [
            'Eingaben als Fachlehrkraft',
            'Subjects',
    ],
    'review_table_part_additional' => [
            'Eingaben als Klassenlehrkraft',
            'General',
    ],
    'review_table_part_subjectsfromother' => [
            'Weitere Fachlehrkräfte',
            'From other subject teachers',
    ],
    'additional_info' => [
            'Zusatzinformationen',
            'Additional info'
    ],

    'settings_only_learnsoziale' => [
            'nur überfachliche Kompetenzen erfassen',
            'Assessment of learning and social behavior only',
    ],
    'settings_shoolname' => [
                'Lernentwicklungsbericht: Schulname',
                'Learning Development Report: School Name',
    ],
    'settings_shooltype' => [
                'Lernentwicklungsbericht: Schulart',
                'Learning Development Report: School Type',
    ],
    'settings_city' => [
                'Lernentwicklungsbericht: Ort',
                'Learning Development Report: City',
    ],
    'settings_edustandarts' => [
                'Bildungsstandards',
                'Fulfillable educational standards',
    ],
    'settings_edustandarts_description' => [
                'Liste, mit Kommata getrennt',
                'Comma seperated list',
    ],
    'settings_bw_reports' => [
                'Gemeinschaftsschulen Berichte',
                'Use interdenominational schools reports',
    ],
    'settings_exacomp_verbeval' => [
                'Exabis Kompetenzraster Notenverbalisierung verwenden',
                'Verbalized assessment from exabis competences',
    ],
    'settings_exacomp_assessment_categories' => [
                'Kompetenzraster für Beurteilungskategorien verwenden',
                'Use competence grids for assessment categories',
    ],
    'settings_sourceId' => [
            'Source ID',
            'Source ID',
    ],
    'settings_sourceId_description' => [
            'Automatisch generierte ID dieser Exastud Installation. Diese kann nicht geändert werden',
            'Automatically generated ID of this Exastud installation. This ID can not be changed',
    ],
    'settings_grade_interdisciplinary_competences' => [
        'Überfachliche Kompetenzen für Klassenlehrkraft freischalten',
        'Show interdisciplinary competences to class teachers',
    ],
    'report_learn_and_sociale' => [
            'Lern- und Sozialverhalten',
            'Learning and social behavior',
    ],
    'report_cross_competences' => [
            'Überfachliche Kompetenzen',
            'Interdisciplinary competences',
    ],
    'report_other_report_fields' => [
            'Weitere Daten/Formularfelder',
            'Other report fields',
    ],
    'report_report_fields' => [
            'Daten/Formularfelder',
            'Report fields',
    ],
    'report_bilinguales' => [
            'Bilingualer Unterricht',
            'Bilingual instruction',
    ],
    'report_for_subjects' => [
            'Zertifikat für Profilfach',
            'Certificate for profile subject',
    ],
    'report_for_additional' => [
            'weitere Daten',
            'Additional fields',
    ],
    'report_report_eval' => [
            'Projektprüfung',
            'Project evaluations',
    ],
    'review_project_evalueations' => [
            'Projektprüfung / Projektarbeit',
            'Project evaluations',
    ],
    'report_student_template' => [
            'Zeugnisformular',
            'Template',
    ],

    'not_assigned' => [
        'nicht zugeordnet',
        'Not assigned',
    ],

	'competencies' => [
		'Überfachliche Kompetenzen',
		'Interdisciplinary competences',
	],
	'Note' => [
		'Note',
		'Grade',
	],
	'Niveau' => [
		'Niveau',
		'Level',
	],
    'last_period' => [
        'letztes Halbjahr:',
        'Last period:',
    ],
    'periods_incorrect' => [
        'Für das aktuelle Datum gibt es keinen oder mehrere Eingabezeiträume. Bitte überprüfen sie ihre Eingabezeiträume!',
        'Period defined is not correct',
    ],
    'suggestions_from_exacomp' => [
        'Vorschläge aus Exacomp',
        'Grade suggestions from competence grid (exacomp)',
    ],
    'grade_and_difflevel' => [
        'Note und Niveau',
        'Grade and difficulty level'
    ],
    'load_last_period' => [
        'Eingaben von der letzten Periode/Halbjahr übernehmen',
        'Load last period',
    ],
    'load_last_period_done' => [
        'Daten der letzten Periode/Halbjahr wurden übernommen',
        'Last period is adapted',
    ],
	'grading' => [
		'Bewertungsskala',
		'Grading',
	],
	'Subjects' => [
		'Fachbezeichnungen',
		'Subjects',
	],
	'education_plans' => [
		'Bildungspläne',
		'Educational Plans',
	],
    'de:Lernentwicklungsbericht: Schulname' => [
        null,
        'Report: name of organization',
    ],
    'de:Lernentwicklungsbericht: Ort' => [
        null,
        'Report: city',
    ],
    'de:Bildungsstandards' => [
        null,
        'Fulfillable educational standards',
    ],
    'de:Liste, mit Kommata getrennt' => [
        null,
        'Comma seperated list',
    ],
    'de:Lernentwicklungsbericht: Zeugnisausgabedatum' => [
        null,
        'Report: date',
    ],
    'delete_class_only_without_users' => [
        'Es können nur Klassen ohne Schüler gelöscht werden',
        'Only classes without students can be deleted'
    ],
    'force_class_to_delete' => [
        'Es können nur Klassen ohne Schüler gelöscht werden. Klassen, die durch ein Häkchen zur Löschung freigegeben sind, können vom Administrator gelöscht werden.',
        'Only classes without students can be deleted. But you can mark this class to deleting by site admin'
    ],
    'already_marked' => [
        'diese Klasse ist bereits zur Löschung vorgemerkt',
        'This class is already marked'
    ],
    'mark_to_delete_go' => [
        'Markieren zur Löschung durch den Administrator.',
        'Mark to delete by admin'
    ],
    'unmark_to_delete_go' => [
        'Markierung für die Löschung aufheben',
        'Unmark to delete by admin'
    ],
    'unmark_to_delete_button' => [
        'Markierung für die Löschung aufheben',
        'Unmark to delete'
    ],
    'class_marked_as_todelete' => [
        'Diese Klasse ist zum Löschen durch den Administrator vorgemerkt.',
        'This class marked to delete by site-admin',
    ],
    'class_marked_as_todelete_hover' => [
            'Diese Klasse ist zum Löschen durch den Administrator vorgemerkt. Um die Vormerkung zu löschen hier klicken.',
            'This class marked to delete by site admin. Click to redo deleting request.',
    ],
    'interdisciplinary_competences' => [
        'Überfachliche Kompetenzen',
        'Interdisciplinary competences',
    ],
    'average' => [
        'Durchschnitt',
        'Average',
    ],


	// === settings ===
	'settings_detailed_review' => [
		'Einzelpunktevergabe anzeigen',
		'Detailed review',
	],
	'settings_detailed_review_body' => [
		'Diese Einstellung erlaubt es die Punktevergabe der Beurteilenden einzeln einzusehen',
		'This setting allows you to see all the assessments from teachers in detail',
	],
	'settings_project_based_assessment' => [
		'Beurteilung auf Projekt-Basis',
		'Project based assessment',
	],
	'settings_project_based_assessment_body' => [
		'Diese Einstellung erlaubt es statt Klassen Projekte zu verwalten und zu beurteilen',
		'This setting allows you to use project based assessment instead of class assessment',
	],
	'blocksettings' => [
		'Deckblattdaten'
	],
	'delete_confirmation' => [
		'Soll "{$a}" wirklich gelöscht werden?',
		'Do you really want to delete "{$a}"?',
	],
	'delete_subjectteacher_confirmation' => [
		'Do you really want to delete this subject teacher?',
		'Do you really want to delete this subject teacher?',
	],
    'delete_refuse_button' => [
            'Löschanfrage ablehnen.',
            'Refuse deletion',
    ],
    'delete_class_refused' => [
            'Anfrage wurde abgelehnt',
            'Refused',
    ],
	'logging' => [
		'Logging aktivieren',
		'Activate logging',
	],
	'settings_competence_evaltype' => [
		'Bewertungsschema',
		'Assessment of competences is based on',
	],
	'settings_competence_evaltype_text' => [
		'Text-Eintrag',
		'Text entry',
	],
	'settings_competence_evaltype_grade' => [
		'Note',
		'Grades',
	],
	'settings_competence_evaltype_point' => [
		'Punkte',
		'Points',
	],
	'settings_competence_evalpoints_limit' => [
		'Höchste Punkteanzahl',
		'Maximum points for assessment type "points"',
	],
	'settings_competence_evalpoints_limit_description' => [
		'Wenn als Bewertungsschema "Punkte" gewählt ist.',
        'If value of exastud/competence_evaltype is based upon points',
	],
	'settings_eval_setup' => [
		'Bewertungsschema Texteintrag',
        'Assessment grading values',
	],
	'settings_eval_setup_link' => [
		'Bewertungskategorien bearbeiten',
        'Edit',
	],


	// === grp3 ===
	'total' => [
		'Gesamtpunkte',
		'Total score',
	],
	'project_based_total' => [
		'Gesamtpunkte',
		'Total score',
	],
	/*
	'subjects_taught_by_me' => [
		'Von mir in dieser Klasse unterrichtete Fächer',
		'Subjects in this class taught by me',
	],
	*/
	'project_based_upload_picture' => [
		null,
		'You can upload a logo for a project specific report',
	],
	'project_based_errorinsertingclass' => [
		null,
		'Error occured while inserting project',
	],

    'html_report' => [
        'Gesamtübersicht',
        'Overview',
    ],
    'download' => [
        'Download',
        'Download',
    ],

    // === events ===
    'event_classcreated_name' => [
        'Class created',
        'Class created'
    ],
    'event_classdeleted_name' => [
        'Class deleted',
        'Class deleted'
    ],
    'event_classupdated_name' => [
        'Class updated',
        'Class updated'
    ],
    'event_classmemberassigned_name' => [
        'User assigned to class',
        'User assigned to class'
    ],
    'event_classmemberunassigned_name' => [
        'User unassigned from class',
        'User unassigned from class'
    ],
    'event_classteacherassigned_name' => [
        'Teacher assigned to class',
        'Teacher assigned to class'
    ],
    'event_classteacherunassigned_name' => [
        'Teacher unassigned from class',
        'Teacher unassigned from class'
    ],
    'event_classteacherchanged_name' => [
        'Teacher changed',
        'Teacher changed'
    ],
    'event_classdatachanged_name' => [
        'Data of the class was changed',
        'Data of the class was changed'
    ],
    'event_studentdatachanged_name' => [
        'Data of the student was changed',
        'Data of the student was changed'
    ],
    'event_subjectstudentdatachanged_name' => [
        'Data of the student was changed for subject',
        'Data of the student was changed for subject'
    ],
    'event_classassessmentcategory_added_name' => [
        'Assessment category was added to class',
        'Assessment category was added to class'
    ],
    'event_classassessmentcategory_deleted_name' => [
        'Assessment category was deleted from class',
        'Assessment category was deleted from class'
    ],
    'event_periodcreated_name' => [
            'Period created',
            'Period created'
    ],
    'event_perioddeleted_name' => [
            'Period deleted',
            'Period deleted'
    ],
    'event_periodupdated_name' => [
            'Period updated',
            'Period updated'
    ],
    'event_competencecreated_name' => [
            'Competence created',
            'Competence created'
    ],
    'event_competenceupdated_name' => [
            'Competence updated',
            'Competence updated'
    ],
    'event_competencedeleted_name' => [
            'Competence deleted',
            'Competence deleted'
    ],
    'event_gradingoptioncreated_name' => [
            'Grading option created',
            'Grading option created'
    ],
    'event_gradingoptionupdated_name' => [
            'Grading option updated',
            'Grading option updated'
    ],
    'event_gradingoptiondeleted_name' => [
            'Grading option deleted',
            'Grading option deleted'
    ],
    'event_educationplancreated_name' => [
            'Education plan created',
            'Educational plan created'
    ],
    'event_educationplanupdated_name' => [
            'Education plan updated',
            'Educational plan updated'
    ],
    'event_educationplandeleted_name' => [
            'Education plan deleted',
            'Educational plan deleted'
    ],
    'event_subjectcreated_name' => [
            'Subject created',
            'Subject created'
    ],
    'event_subjectupdated_name' => [
            'Subject updated',
            'Subject updated'
    ],
    'event_subjectdeleted_name' => [
            'Subject deleted',
            'Subject deleted'
    ],
    'event_studenthidden_name' => [
            'Student hidden',
            'Student hidden'
    ],
    'event_studentshown_name' => [
            'Student shown',
            'Student shown'
    ],
    'event_studentreviewcategorychanged_name' => [
            'Student category review changed',
            'Student category assessment changed'
    ],
    'event_studentreviewchanged_name' => [
            'Student review changed',
            'Student assessment changed'
    ],
    'event_classownerupdated_name' => [
            'Class teacher updated',
            'Class teacher updated',
    ],
    'template_textarea_limits_error' => [
        'Please use defined limits for textarea fields',
        'Please use defined limits for textarea fields'
    ],

    'gender' => [
        'Geschlecht',
        'Gender',
    ],
    'it_is_my_class' => [
        'Meine Klasse',
        'My class',
    ],
    'classowner_changed_message' => [
        'Sie haben die Klassenlehrkraft für die Klasse "{$a->classtitle}" erfolgreich geänder auf {$a->owner}. ',
        'You changed the class teacher "{$a->classtitle}" to {$a->owner}. So, you do not have access to edit this class from now!',
    ],
    'attention_owner_will_change' => [
        'Achtung, wenn sie eine neue Klassenlehrkraft zuteilen, haben sie keine Rechte mehr diese Klasse zu bearbeiten.',
        'Please note: if you will change the teacher of own class - you will not have access to edit this class!',
    ],
    'classteacher_grade_interdisciplinary_competences' => [
        'Klassenlehrkraft kann überfachliche Kompetenzen erfassen',
        'Class teacher can edit interdisciplinary competences'
    ],
    'subjectteacher_grade_interdisciplinary_competences' => [
        'Fachlehrer kann überfachliche Kompetenzen erfassen',
        'Subject teacher can edit interdisciplinary competences'
    ],
    'classteacher_grade_learn_and_social_behaviour' => [
        'Klassenlehrkraft kann Lern- und Sozialverhalten erfassen',
        'Class teacher can edit learning and social behaviour'
    ],
    'subjectteacher_grade_learn_and_social_behaviour' => [
        'Fachlehrer kann Lern- und Sozialverhalten erfassen',
        'Subject teacher can edit learning and social behavior '
    ],
    'class_settings_can_edit_crosscompetencies' => [
        'kann überfachliche Kompetenzen erfassen',
        'can edit interdisciplinary competences'
    ],
    'class_settings_can_edit_learnsocial' => [
        'kann Lern- und Sozialverhalten erfassen:',
        'can edit learning and social competences'
    ],
    'class_settings_class_teacher' => [
        'Klassenlehrkraft',
        'Class teacher'
    ],
    'class_settings_subject_teacher' => [
        'Fachlehrer',
        'Subject teacher'
    ],

	'settings_heading_security' => [
		'Sicherheit',
		'Security'
	],
	'settings_heading_security_description' => [
		'',
		''
	],
	'export_password_message' => [
		'Bitte notieren Sie sich das Passwort "<strong>{$a}</strong>", bevor Sie fortfahren.<br/><br/>
		Hinweis: Passwortgeschützte zip-Dateien können unter Windows zwar geöffnet werden, aber die Dateien innerhalb der Zip-Datei können nur mit einem externen Programm (z.B. 7-Zip) extrahiert werden.
		',
		'Please remember the password "<strong>{$a}</strong>" before proceeding',
	],
    'settings_export_class_password' => [
        'Sicherung von Klassen mit Passwort schützen (AES-256 Verschlüsselung)',
        'Passwort protection (AES-256 encryption) for class backups'
    ],
    'settings_export_class_report_password' => [
        'Export von Klassenberichten mit Passwort schützen (AES-256 Verschlüsselung)',
        'Passwort protection (AES-256 encryption) for class reports'
    ],
    'settings_export_class_report_password_description' => [
		'(Nur ab php Version 7.2 verfügbar)',
		'(only available from php version 7.2 on)'
    ],
    'backup_description' => [
        'Hier können Sie alle Tabellen des Lernentwicklungsberichts im sql-Format sichern. Das Einspielen der Sicherung führen Sie bitte mit einem Datenbank-Tool wie z.B. phpMyAdmin durch.',
        'Create a backup of assessment data as an sql file. As an admin you can import this backup using a database tool like phpMyAdmin. Backups on teacher level are possible for their individual data.'
    ],
    'backup_go' => [
        'Datenbank jetzt sichern',
        'Backup Database now',
    ],
    'block_settings' => [
        'Blockeinstellungen',
        'Settings',
    ],
    'teacher_subject_role' => [
        'Fachbezeichnung / Rolle',
        'Description of subject/role of teacher',
    ],
    'head_teacher' => [
        'Zuständiger Klassenlehrer',
        'Head teacher',
    ],
    'teacher_for_project' => [
        'Lehrkraft für Projektprüfung',
        'Teacher for project assessment',
    ],
    'teacher_for_bilingual' => [
        'Lehrkraft für Bilingualer',
        'Teacher for bilingual assessment',
    ],
    'report_for_bilingual' => [
        'Zeugnisformular für Bilingualer',
        'Report for bilingual assessment',
    ],
    'textblock' => [
        'Formulierungsvorschläge',
        'Text block',
    ],
    'learn_and_sociale' => [
            'Lern- und Sozialverhalten',
            'Learning and social behavior',
    ],
    'cross_competences_for_head' => [
            'Überfachliche Kompetenzen',
            'Interdisciplinary competences',
    ],
    'learn_and_sociale_for_head' => [
            'Lern- und Sozialverhalten',
            'Learning and social behavior',
    ],
    'learn_and_sociale_for_head2' => [
            'Überfachliche Kompetenzen',
            'Learning and social behavior',
    ],
    'textarea_max' => [
            'Max.',
            'Maximum',
    ],
    'textarea_rows' => [
            'Zeilen',
            'Rows',
    ],
    'textarea_chars' => [
            'Zeichen',
            'Characters',
    ],
    'textarea_charsleft' => [
            'Zeichen verfügbar',
            'Characters left',
    ],
    'textarea_linestomuch' => [
            'Zeilen zuviel',
            'Lines too much',
    ],
    'textarea_charstomuch' => [
            'Zeichen zuviel',
            'Characters too much',
    ],
    'textarea_maxchars' => [
            'Gesamtzeichen',
            'All characters',
    ],
    'textarea_limit_notation' => [
        'Damit der Text vollständig ausgegeben werden kann, können in der letzten Zeile nur {$a->chars_per_row} Zeichen erfasst werden.',
        'To show full text in the Report, please do not type in more then {$a->chars_per_row} signs in the last row.',
    ],
    'attention_send_message_to_classteacher' => [
        'Bitte <a id="exastud_link_to_class_teacher" href="{$a->messagehref}" target="_blank">benachrichtigen</a> Sie die neue Klassenlehrkraft über die Neuzuteilung.',
        'Do not forget to <a id="exastud_link_to_class_teacher" href="{$a->messagehref}" target="_blank">send a message</a> to the new class teacher about his new class',
    ],
    'attention_admin_cannot_be_classteacher' => [
        'Klassen können nur als Klassenlehrkraft erstellt werden. Bitte loggen Sie sich als Klassenlehrkraft ein, um eine Klasse zu erstellen.',
        'Classes can only be defined in a class teacher role. Please log in as a class teacher to define a class.',
    ],
    'subject_category' => [
        'Kategorie',
        'Category'
    ],
    'subject_category_m' => [
        'maßgebliches Fach',
        'Relevant Subject'
    ],
    //'subject_category_m_rs' => [
    //    'maßgebliches Fach',
    //    'relevant Subject'
    //],
    'subject_category_b' => [
        'beste Note of "Fächer"',
        'Best grade'
    ],
    'filter_fieldset' => [
        'Filter',
        'Filter',
    ],
    'filter_search' => [
        'Suche',
        'Title search',
    ],
    'filter_bp' => [
        'BP',
        'Curriculum',
    ],
    'filter_empty' => [
        'keine',
        'Empty',
    ],
    'filter_category' => [
        'Kategorie',
        'Category',
    ],
    'filter_button' => [
        'Filter',
        'Filter',
    ],
    'clear_filter' => [
        'Filter zurücksetzen',
        'Clear filters',
    ],
    'not_found_report' => [
        'Keine Berichtsvorlagen vorhanden',
        'No any report found',
    ],
    'filter_show_fulltable' => [
        'Gesamt-Tabelle anzeigen',
        'Show full table',
    ],
    'temporary_hidden' => [
        'vorübergehend deaktiviert',
        'Temporarily unavailable',
    ],
    'add_class' => [
        'Klasse hinzufügen',
        'Add Class',
    ],
    'copy_class_from_last_period' => [
        'Klasse vom vorigen Eingabezeitraum kopieren',
        'Copy class from last period',
    ],
    'import_class_from_backup' => [
        'Klasse von Sicherung wiederherstellen',
        'Import class from backup',
    ],
    'only_profilesubject_teacher' => [
        'Nur Profilfach-Lehrkräfte können diesen Eintrag bearbeiten.',
        'Only profile subject\'s teacher can edit',
    ],
    'man' => [
        'männlich',
        'male',
    ],
    'woman' => [
        'weiblich',
        'female',
    ],
    'UMan' => [
        'Männlich',
        'Male',
    ],
    'UWoman' => [
        'Weiblich',
        'Female',
    ],
    'legend' => [
        'Legende',
        'Key',
    ],
    'ags' => [
        'Arbeitsgemeinschaften',
        'Working groups',
    ],
    'acronym' => [
        'Fachkürzel',
        'Acronym',
    ],
    'subject' => [
        'Fach',
        'Subject',
    ],
    'teacher' => [
        'Lehrer',
        'Teacher',
    ],

    'review_student_other_data_header' => [
        'Kopfteil',
        'Header',
    ],
    'review_student_other_data_body' => [
        'Fächer',
        'Body',
    ],
    'review_student_other_data_footer' => [
        'Fussteil',
        'Footer',
    ],

    'leaders' => [
        'Unterzeichnende',
        'Signatures'
    ],
    'schoollieder_fieldtitle' => [
        'Schulleiterin',
        'School principal',
    ],
    'groupleader_fieldtitle' => [
        'Lerngruppenbegleiter/-In',
        'Learning group tutor',
    ],
    'auditleader_fieldtitle' => [
        'Vorsitzende(r) des Prüfungsausschusses',
        'Chairman of the Audit Committee',
    ],
    'classleader_fieldtitle' => [
        'Klassenlehrer/-In',
        'Head of class',
    ],
    'classteacher' => [
        'Klassenlehrer',
        'Class teacher',
    ],
    'subjectteacher_change_button' => [
        'neue Lehrkraft zuordnen und Bewertungen übernehmen',
        'Match a new teacher and transmit his grading'
    ],
    'subjectteacher_delete_button' => [
        'Die Zuordnung dieser Lehrkraft zu diesem Fach aufheben.',
        'Delete relation of this teacher and subject'
    ],
    'form_subject_teacher_form_header' => [
        'Fach an andere Lehrkraft übertragen',
        'Transfer subject to other teacher'
    ],
    'form_subject_teacher_form_description' => [
        'Sie können das Fach {$a->subjecttitle} einer anderen Lehrkraft übertragen. Die bisherige Lehrkraft ist {$a->currentteacher_name}. Alle Eingaben und Bewertungen von {$a->currentteacher_name} werden der neuen Lehrkraft übertragen.',
        'You can transfer the subject {$a->subjecttitle} to another teacher. The current teacher is {$a->currentteacher_name}. All gradings from {$a->currentteacher_name} are transferred to new teacher.'
    ],
    'form_subject_teacher_form_select_new_teacher' => [
        'Neue Lehrkraft für {$a->subjecttitle}',
        'New teacher for {$a->subjecttitle}',
    ],
    'form_subject_teacher_form_select_new_teacher_docu' => [
        'In der Auswahl sind Benutzer aufgelistet, welche in diversen Zusammenhängen die Lehrerrolle innehaben (Klassenlehrer, Kurslehrer, Klassenersteller, Fachlehrer, zusätzlicher Klassenlehrer, Projekt- oder bilungualer Lehrer)',
        'In this Dropdown Users are listed, who have a teacher role in some context (classteacher, teacher in this course, classowner, teacher in a subject, additional class teacher, teacher for project, bilingual teacher)',
    ],
    'form_subject_teacher_form_no_head_class_teacher' => [
            'Diese Lehrkraft auch als Klassenlehrkraft ersetzen.',
            'Also remove from head class teacher',
    ],
    'form_subject_teacher_form_save' => [
        'Lehrkraft jetzt übertragen.',
        'Transfer subject now.',
    ],

    'button_interdisciplinary_skills' => [
        'Überfachliche Kompetenzen bearbeiten',
        'Interdisciplinary skills'
    ],

    'profilesubject' => [
        'Profilfach',
        'Profile subject',
    ],

    'select_another_class' => [
            'Andere Klasse auswählen',
            'Select another class',
    ],

    'this_category_related_to_classes' => [
            'Diese Kategorie wird in folgenden Klassen verwendet',
            'This category is related to class',
    ],
    'this_category_reviewed_for_student' => [
            'Zu dieser Kategorie gibt es Schülerbeurteilungen',
            'This category has been reviewed for some students',
    ],
    'info_category_without_cross_competences' => [
            'Bei der Kategorie {$a->categorytitles} - gibt es keine &#8221;Überfachlichen Kompetenzen&#8220; ',
            'if category is {$a->categorytitles} - this report does not have &#8221;interdisciplinary competences&#8220; ',
    ],
    'donotleave_page_message' => [
            'Sie haben noch nicht gespeichert. Wollen sie diese Seite verlassen ohne die Änderungen zu speichern?',
            'You have unsaved changes on this page. Do you want to leave this page and discard your changes or stay on this page?'
    ],
    'please_enter_group_name' => [
        'Bitte geben Sie die Klassen/Lerngruppenbezeichnung ein!',
        'Please enter the class/group name!'
    ],
    'mixed_bw_nonbw_class_error_2' => [
        'Template für die Klasse wurde nicht gefunden. Möglicherweise wollen sie die Klasse verwenden ohne "exastud | bw_active" aktiviert zu haben',
        'Template for class not found. Please check the option of interdenominational schools - "exastud | bw_active"',
    ],
    'report_settings_userdata_wrong_user_parameter' => [
        'Profilfeld \'{$a->fieldname}\' existiert nicht. Bitte erstellen sie das Profilfeld oder ändern sie die Zuordnung.',
        'User profile field \'{$a->fieldname}\' does not exist. Create it or change value to existing field.',
    ],
    'report_settings_userdata_wrong_user_parameter_editurl_title' => [
        'Benutzerprofil ändern',
        'Edit user profile fields',
    ],
    'report_settings_no_template_file' => [
        'Keine Berichtsdatei: {$a->filename}',
        'No template file: {$a->filename}',
    ],
    'report_settings_userdata_wrong_user_parameter_in_reports_list' => [
        'Der Bericht hat falsche Marker defíniert. Bitte ändern sie den Bericht entsprechend.',
        'This report has wrong userdata markers. Please edit this report to resolve this error.',
    ],
    'report_edit_userprofile' => [
        'Edit user profile',
        'Edit user profile',
    ],
    'report_userprofile_field_info' => [
        'User\'s property.',
        'User\'s property.',
    ],
    'report_edit_userprofile_noaccess' => [
        'You have not privilege to edit user\'s profile',
        'You do not have the rights to edit user\'s profile',
    ],
    'report_settings_group_title_default' => [
        'Standardeinstellung',
        'Default'
    ],
    'report_settings_group_title_classTeacher' => [
        'Klassenlehrer Eingabefelder',
        'For class teacher'
    ],
    'report_settings_group_title_other' => [
        'Weitere Felder',
        'Additional fields',
    ],
    'report_settings_group_description_default' => [
        'Diese Markierungen sind bereits im Modul enthalten und werden mit den Werten der entsprechenden Eingabefelder befüllt, falls sie im Bericht enthalten sind.',
        'These markers are fixed and will be filled with own values if they are in the report',
    ],
    'report_settings_group_description_classTeacher' => [
        'Felder, die von der Klassenlehrkraft ausgefüllt werden können',
        'Fields to be filled in by the class teacher'
    ],
    'report_settings_group_description_other' => [
        '',
        ''
    ],
    'parent' => [
        'Parent',
        'Parent',
    ],
    'allow_review_until' => [
        'Bearbeitung Freigeben bis',
        'until',
    ],
    'allow_review_admin_approved' => [
        'bestätigt',
        'approved',
    ],
    'allow_review_admin_notapproved_yet' => [
        'noch nicht bestätigt',
        'not approved yet',
    ],
    'allow_review_admin_approved_for_all' => [
        'für alle',
        'for all',
    ],
    'allow_review_make_request' => [
        'Anfrage an die Klassenlehrkraft stellen um das Bearbeiten dieser Klasse für 1 Tag freizuschalten.',
        'make a request to admin for unlock editing',
    ],
    'allow_review' => [
        'Bearbeiten freischalten',
        'unlock editing',
    ],
    'allow_review_make_request_already' => [
        'Die Freischaltung dieser Klasse zur Bearbeitung wurde beim Klassenlehrer angefragt. Es ist noch keine Zustimmung durch die Klassenlehrkraft erfolgt.',
        'already requested for unlock editing',
    ],
    'requests' => [
        'Benutzeranfragen',
        'System requests',
    ],
    'requests_class_delete_list' => [
        'Klasse löschen',
        'Classes delete',
    ],
    'requests_class_title' => [
        'Klasse',
        'Class',
    ],
    'requests_class_teacher' => [
        'Klassenlehrkraft',
        'Class teacher',
    ],
    'requests_class_delete' => [
        'Löschen',
        'Delete',
    ],
    'requests_unlock_review_list' => [
        'Aktivieren der Beurteilungsmöglichkeit von Klassen aus vergangenen Eingabezeiträumen',
        'Unlock to review',
    ],
    'requests_unlock_requested_teacher' => [
        'Anfrage von Lehrkraft',
        'request for teacher',
    ],
    'requests_unlock_request_until' => [
        'bis',
        'until',
    ],
    'requests_unlock_approve_button' => [
        'Zustimmen',
        'Approve',
    ],
    'requests_unlock_prolong_button' => [
        'Freigabe verlängern (auf jetzt + 24h)',
        'Prolong',
    ],
    'requests_unlock_delete_button' => [
        'Ablehnen',
        'Refuse',
    ],
    'requests_unlock_request_created' => [
        'die Anfrage wurde erstellt',
        'Request created',
    ],
    'requests_no_any' => [
        'Keine Benutzeranfragen vorhanden',
        'No any requests',
    ],
    'requests_for_you' => [
        'Es sind Systemnachrichten für sie vorhanden.',
        'There are System Requests for you',
    ],
    'not_selected' => [
        'keine',
        'Empty',
    ],
    'project_teacher_also_was_changed' => [
        '{$a->newteachername} ist auch neuer Projektprüfer anstatt {$a->oldteachername}',
        '{$a->newteachername} also got Project Teacher instead of {$a->oldteachername}'
    ],
    'can_not_delete_subject_teacher_because_has_grading' => [
        'Dieser Fachlehrer hat bereits Beurteilungen in diesem Fach abgegeben. Er kann nicht als Fachlehrer gelöscht werden, bitte nehmen sie stattdessen einen Fachlehrerwechsel vor.',
        'This subjectteachers have done grading in this class in this subject. It is not possible to delete him as subject teacher. Please change subject teacher in this case.'
    ],
    'more_student_data' => [
        'Detaildaten auf/zuklappen',
        'Show more data',
    ],
    'more_student_data_all' => [
    		'Gesamtdaten in der Liste anzeigen.',
        'Show more data for all students',
    ],
    'more_student_data_all_hide' => [
        'Anzeige gekürzter Daten für alle Schüler',
        'Hide detail data for all students',
    ],
    'messageprovider:approve_allow_review' => [
        'Freigabe der Klasse aus vorigem Eingabezeitraum verlängern.',
        'Approve of review of class from old period',
    ],
    'messageprovider:prolong_allow_review' => [
        'Freigabe der Klasse aus vorigem Eingabezeitraum verlängern.',
        'Prolong of review of class from old period',
    ],
    'notification_allow_review_old_class_approve_subject' => [
            '{$a->site}: Die Freigabe der Klasse "{$a->classtitle}" wurde bestätigt',
            '{$a->site}: Review of class "{$a->classtitle}" was approved',
    ],
    'notification_allow_review_old_class_approve_body' => [
            'Sehr geehrte/r {$a->receiver}, </br></br>Die Klasse "{$a->classtitle}" wurde freigegeben bis {$a->datetime}.</br></br> Diese Nachricht wurde automatisch generiert von {$a->site}.',
            'Dear {$a->receiver}, </br></br>Review of class "{$a->classtitle}" was approved until {$a->datetime}.</br></br> This message has been generated automatically from moodle site {$a->site}.',
    ],
    'notification_allow_review_old_class_approve_context' => [
        'Klasse aus vorigem Eingabezeitraum freigeben.',
        'Allow review class from old period',
    ],
    'notification_allow_review_old_class_prolong_subject' => [
            '{$a->site}: Die Freigabe der Klasse "{$a->classtitle}" wurde verlängert.',
            '{$a->site}: Review of class "{$a->classtitle}" was prolonged',
    ],
    'notification_allow_review_old_class_prolong_body' => [
            'Sehr geehrte/r {$a->receiver}, </br></br>Die Freigabe der Klasse "{$a->classtitle}" wurde verlängert {$a->datetime}.</br></br> Diese Nachricht wurde automatisch generiert. {$a->site}.',
            'Dear {$a->receiver}, </br></br>Review of class "{$a->classtitle}" was prolonged to {$a->datetime}.</br></br> This message has been generated automatically from moodle site {$a->site}.',
    ],
    'notification_allow_review_old_class_prolong_context' => [
        'Klasse aus vorigem Eingabezeitraum freigeben',
        'Allow review class from old period',
    ],
    'no_possible_inputs_in_report' => [
            'Im Berichtskonfigurator sind keine "Klassenlehrer Eingabefelder" definiert. Deshalb ist hier keine Dateneingabe möglich',
            'No fields defined in Report Configurator. No data input possible',
    ],
    'message_interdisciplinary_competences_notes_limit' => [
        'Bitte tragen sie Noten von 1-{$a->limit} ein.',
        'Please enter grades 1-{$a->limit}.'
    ],
    'message_interdisciplinary_competences_points_limit' => [
        'Bitte tragen sie Punkte von 0-{$a->limit} ein.',
        'Please enter Point 0-{$a->limit}.'
    ],
    'cross_competences_maintable_title_for_grade' => [
        'Bewertungsschema: Note',
        'Gradingscheme: Grade',
    ],
    'cross_competences_maintable_title_for_points' => [
        'Bewertungsschema: Punkte',
        'Gradingscheme: Points',
    ],
    'cross_competences_maintable_title_for_texts' => [
        'Bewertungsschema: Text-Eintrag',
        'Gradingscheme: Text entry',
    ],
    'review_class_averages' => [
        'Schnittberechnung',
        'Averages calculation',
    ],
    'review_class_average_value' => [
        'Notenschnitt',
        'Average value',
    ],
    'review_class_average_not_calculated' => [
        'Es wurde noch keine Schnittberechnung durchgeführt.',
        'no average grading calculated',
    ],
    'average_calculate_table_student' => [
        'Student',
        'Student',
    ],
    'average_calculate_table_subjecttype' => [
        'Typ',
        'type',
    ],
    'average_calculate_table_factor' => [
        'Faktor',
        'Factor',
    ],
    'average_calculate_table_grading' => [
        'Beurteilung',
        'Grading',
    ],
    'average_calculate_table_summ' => [
        'Summe',
        'Summe',
    ],
    'average_calculate_table_average' => [
        'Schnitt',
        'Average',
    ],
    'average_calculate_table_average_project_title' => [
        'Projektarbeit / Projektprüfung',
        'Projektarbeit / Projektprüfung',
    ],
    'average_calculate_button' => [
        'Berechnen',
        'Calculate',
    ],
    'average_export_button' => [
        'Export',
        'Export',
    ],
    'average_needs_calculate' => [
        'Bitte berechnen Sie die durchschnittliche Bewertung für den Schüler.',
        'Please make calculation of average grading for student'
    ],
    'average_needs_calculate_for_student' => [
        'Bitte führen Sie zuerst beim Schüler / bei der Schülerin {$a->studentname} eine Berechnung des Notenschnittes durch.',
        'Please make calculation of average grading for student {$a->studentname}.'
    ],
    'report_averages_title' => [
        'Durchschnitt',
        'Averages'
    ],
    'report_averages_header_subjects' => [
        'Fächer',
        'Subjects'
    ],
    'report_averages_header_student' => [
        'Schüler/in',
        'Student'
    ],
    'report_averages_header_type' => [
        'Type',
        'Type',
    ],
    'report_averages_header_factor' => [
        'Faktor',
        'Factor'
    ],
    'report_averages_header_grading' => [
        'Beurteilung',
        'Grading'
    ],
    'report_averages_header_sum' => [
        'Summe',
        'Sum'
    ],
    'report_averages_header_average' => [
        'Durchschnitt',
        'Average'
    ],
    'certificate_issue_date_missed_message' => [
        'Es wurde noch kein Zeugnisausgabedatum angegeben. Der Administrator sollte zur korrekten Ausgabe des Zeugnisses das Zeugnisausgabedatum angeben.',
        'No certificate issue date has yet been specified. The administrator should input the Certificate Issue Date for generating the certificate correctly.'
    ],
    'reports_overviews' => [
        'Übersichten',
        'Overviews'
    ],
    'reports_certs_and_attachments' => [
        'Zeugnisse und Anlagen',
        'Certificates and attachments'
    ],
    'report_screen' => [
        'Bildschirm',
        'Screen'
    ],
    'report_file' => [
        'Datei',
        'File'
    ],
    'report_overview_xlsx' => [
        'Notenübersicht (xlsx)',
        'Overview of grades (xlsx)'
    ],
    'report_overview_docx' => [
        'Notenübersicht (docx)',
        'Overview of grades (docx)'
    ],
    'report_column_template' => [
        'Zeugnisformular',
        'Certificate form',
    ],
    'Template' => [
        'Zeugnis',
        'Certificate',
    ],
    'Template_and_departure' => [
        'Zeugnis / Abgangszeugnis',
        'Certificate / Certificate of Departure',
    ],
    'report_column_enddate' => [
        'Ausgeschieden',
        'Dropped out',
    ],
    'not_defined' => [
        'nicht gewählt',
        'not defined'
    ],
    'No_suggestions' => [
        'Keine Vorschläge gefunden',
        'No suggestions found'
    ],
    'no_project_examination_for_project' => [
        'Projektprüfung für Formular \'{$a}\' nicht verfügbar',
        'Project examination for form \'{$a}\' not available'
    ],
    'Subjects' => [
        'Fachbezeichnung',
        'Subjects'
    ],
    'allow_reviewing' => [
        'Bewertung erneut freigeben',
        'Allow reviewing this class'
    ],
    'prof_skills' => [
        'Fachkompetenzen',
        'Professional skills',
    ],
    'hidden_students' => [
        'Ausgeblendete Schüler',
        'Hidden students'
    ],
    'assigned_to' => [
        'Zugeteilt zu {$a}',
        'Assigned to {$a}'
    ],
    'template_with_no_inputs' => [
        'Dieses Formular hat keine weiteren Eingabfelder',
        'This template has no further input fields'
    ],
    'old_subjects' => [
        'Alte Fächer',
        'Old subjects',
    ],
    'class_group' => [
        'Klasse/Lerngruppe',
        'Class group',
    ],
    'clas_group_add_students' => [
        'Schüler hinzufügen',
        'Add students',
    ],
    'class_import_button' => [
        'Prüfen',
        'Check',
    ],
    'class_import_button_confirm' => [
        'Jetzt Importieren',
        'Import now',
    ],
    'file_not_found' => [
        'Keine Datei gefunden',
        'File not found'
    ],
    'file_not_selected' => [
        'Keine Datei ausgewählt',
        'File not selected'
    ],
    'file_is_wrong_format' => [
        'Datei hat falsches Format',
        'The file is in the wrong format'
    ],
    'file_is_not_class_backup' => [
        'Datei ist keine Sicherung einer Klasse',
        'The file is not a backup of a Class'
    ],
    'file_version_wrong' => [
        'Das Dateiformat ist leider nicht mit dieser Version des Lernentwicklungsberichts kompatibel',
        'Unfortunately, the file format is not compatible with this version of assessment data'
    ],
    'wrong_password' => [
        'Falsches Passwort',
        'Wrong password'
    ],
    'classname' => [
        'Klassenname',
        'Class name',
    ],
    'import_class_already_exist' => [
        'Klasse "{$a}" existiert bereits und wird überschrieben',
        'Class "{$a}" already exists and will be overwritten',
    ],
    'import_evaluation_will_overwrite' => [
        'Es wird eine Bewertung überschrieben (Typ: {$a->type}, Lehrer: {$a->teacher})',
        'An evaluation will be overwritten (Type: {$a->type}, Teacher: {$a->teacher})',
    ],
    'import_class_restored' => [
        'Klasse "{$a}" wurde wiederhergestellt',
        'Class "{$a}" has been restored',
    ],
    'import_class_checked_success' => [
        'Klassendaten erfolgreich geprüft',
        'Class data checked successfully',
    ],
    'import_class' => [
        'Klasse Importieren',
        'Import class',
    ],
    'import_class_reviewsimport' => [
        'Bewertung importieren',
        'Import reviews',
    ],
    'bp_title' => [
        'Bezeichnung',
        'Name',
    ],
    'bp_shorttitle' => [
        'Kurzbezeichnung',
        'Shortname',
    ],
    'bp_leb_always_print' => [
        'Immer im LEB drucken',
        'Always print',
    ],
    'last_edited_by' => [
        'Letzte Änderung von {$a->name} am {$a->time}',
        'Last Change by {$a->name} on {$a->time}'
    ],
    'no_other_users_found' => [
          'Keine anderen Benutzer gefunden',
          'No other users found'
    ],
    'copy_class' => [
        'Klasse kopieren',
        'Copy Class',
    ],
    'copy_class_from_last_period' => [
        'Klasse vom vorigen Eingabezeitraum kopieren',
        'Copy Class from last Period',
    ],
    'copy_class_new_title' => [
        'Kopie von {$a}',
        'Copy of {$a}',
    ],
    'class_add_students_from_group_description' => [
        'Schüler, die in ihrem Nutzerprofil im Bereich "weitere Profileinstellungen" im Feld Klasse/Lerngruppe den entsprechenden Eintrag haben zur Klasse hinzufügen.',
        'Students from this class group (see student user profile) will be added to the class'
    ],
];