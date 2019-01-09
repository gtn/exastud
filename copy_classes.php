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

$courseid = optional_param('courseid', 1, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);

$actPeriod = block_exastud_get_active_or_next_period();
$lastPeriod = block_exastud_get_last_period();
$lastPeriodClasses = $lastPeriod ? block_exastud_get_head_teacher_classes_owner($lastPeriod->id) : [];

if (!$lastPeriodClasses) {
	throw new Exception('no classes found');
}

if ($action == 'copy') {
	$classid = required_param('classid', PARAM_INT);

	if (!isset($lastPeriodClasses[$classid])) {
		throw new Exception('class not found');
	}

	$class = $lastPeriodClasses[$classid];
	$oldId = $class->id;
	unset($class->id);
	$class->timemodified = time();
	$class->periodid = $actPeriod->id;
	$class->title = block_exastud_trans(['de:Kopie von {$a}', 'en:Copy of {$a}'], $class->title);
	$newId = $DB->insert_record('block_exastudclass', $class);

	$DB->execute("INSERT INTO {block_exastudclassstudents} (timemodified, classid, studentid)
		SELECT ?, ?, studentid
		FROM {block_exastudclassstudents}
		WHERE classid = ?", [time(), $newId, $oldId]);
	$DB->execute("INSERT INTO {block_exastudclassteachers} (timemodified, classid, teacherid, subjectid)
		SELECT ?, ?, teacherid, subjectid
		FROM {block_exastudclassteachers}
		WHERE classid = ?", [time(), $newId, $oldId]);

	redirect('configuration_class_info.php?courseid='.$courseid.'&classid='.$newId);
	exit;
}

$url = '/blocks/exastud/copy_classes.php';
$PAGE->set_url($url);

$output = block_exastud_get_renderer();
echo $output->header('configuration_classes');
echo $output->heading(block_exastud_trans(['de:Klasse vom vorigen Eingabezeitraum kopieren', 'en:Copy Class from last Period']));
$table = new html_table();

$table->head = [block_exastud_get_string('class'), ''];

foreach ($lastPeriodClasses as $class) {
	$table->data[] = [
		$class->title,
		$output->link_button($CFG->wwwroot.'/blocks/exastud/copy_classes.php?courseid='.$courseid.'&action=copy&classid='.$class->id,
			block_exastud_trans(['de:Klasse kopieren', 'en:Copy Class']),
            ['class' => 'btn btn-default']),
	];
}

echo $output->table($table);

echo $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classes.php?courseid='.$courseid,
	block_exastud_get_string('back'),
    ['class' => 'btn btn-default']);

echo $output->footer();
