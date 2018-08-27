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

require __DIR__.'/inc.php';

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$action = optional_param('action', '', PARAM_TEXT);
require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);

$actPeriod = block_exastud_get_active_or_next_period();
$lastPeriod = block_exastud_get_last_period();
$classes = block_exastud_get_head_teacher_classes_owner($actPeriod->id);
$lastPeriodClasses = $lastPeriod ? block_exastud_get_head_teacher_classes_owner($lastPeriod->id) : [];
$shownClasses = array(); // Which ckasses already shown on the page

$url = '/blocks/exastud/configuration_classes.php';
$PAGE->set_url($url);

$output = block_exastud_get_renderer();
echo $output->header('configuration_classes');

/* Print the Students */
echo $output->heading($actPeriod->description.': '.block_exastud_get_string('configuration_classes'));

if (!$classes) {
	echo '<div style="padding-bottom: 20px;">'.block_exastud_get_string('noclassfound').'</div>';
} else {
	$table = new html_table();

	$table->head = array(block_exastud_get_string('class'));
	$table->align = array("left");

	foreach ($classes as $class) {
		$table->data[] = [
			'<a href="configuration_class.php?courseid='.$courseid.'&classid='.$class->id.'">'.$class->title.'</a>',
		];
        $shownClasses[] = $class->id;
	}

	echo $output->table($table);
}

if ($lastPeriodClasses) {
	echo $output->heading($lastPeriod->description.': '.block_exastud_get_string('configuration_classes'));

	$table = new html_table();

	$table->head = array(block_exastud_get_string('class'));
	$table->align = array("left");

	foreach ($lastPeriodClasses as $class) {
		$table->data[] = [
			'<a href="configuration_class.php?courseid='.$courseid.'&classid='.$class->id.'">'.$class->title.'</a>',
		];
        $shownClasses[] = $class->id;
	}

	echo $output->table($table);
}

echo $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_class_info.php?courseid='.$courseid.'&action=add',
	block_exastud_trans(['de:Klasse hinzufügen', 'en:Add Class']));

if ($lastPeriodClasses) {
	echo $output->link_button($CFG->wwwroot.'/blocks/exastud/copy_classes.php?courseid='.$courseid,
		block_exastud_trans(['de:Klasse vom vorigen Eingabezeitraum kopieren', 'en:Copy Class from last Period']));
}

echo $output->link_button($CFG->wwwroot.'/blocks/exastud/import_class.php?courseid='.$courseid,
	block_exastud_trans(['de:Klasse von Sicherung wiederherstellen', 'en:Import Class from Backup']));


if (block_exastud_is_siteadmin()) {
    $allClasses = block_exastud_get_classes_all(true);
    echo '<br><br>'.$output->heading(block_exastud_get_string('configuration_classes_onlyadmin'));

    $table = new html_table();

    $table->head = array(block_exastud_get_string('class'));
    $table->align = array("left");
    $currentperiod = 0;
    foreach ($allClasses as $class) {
        if ($currentperiod != $class->periodid) {
            $periodData = $DB->get_record('block_exastudperiod', ['id' => $class->periodid]);
            $table->data[] = [
                    '<strong>'.date('d F Y, h:iA', $periodData->starttime).' - '.date('d F Y, h:iA', $periodData->endtime).'</strong>: '.$periodData->description.''
            ];
            $currentperiod = $class->periodid;
        }
        if (!in_array($class->id, $shownClasses)) {
            $ownerData = $DB->get_record('user', ['id' => $class->userid]);
            $table->data[] = [
                    '<a href="configuration_class.php?courseid='.$courseid.'&classid='.$class->id.'">'.$class->title.'</a><br/>'.
                    '<small>(id: '.$class->id.') '.$ownerData->firstname.' '.$ownerData->lastname.'</small>',
            ];
        }
    }

    echo $output->table($table);
}

/*
if ($classes = block_exastud_get_head_teacher_classes_shared($actPeriod->id)) {
	echo html_writer::tag("h2", block_exastud_trans('de:Mit mir geteilte Klassen'));

	$table = new html_table();

	$table->head = array(block_exastud_get_string('class'));
	$table->align = array("left");
	$table->size = array("50%");

	foreach ($classes as $class) {
		$table->data[] = [
			'<a href="configuration_class.php?courseid='.$courseid.'&classid='.$class->id.'">'.$class->title.'</a>'
		];
	}

	echo $output->table($table);
}
*/

echo $output->footer();
