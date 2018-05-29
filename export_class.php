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
$classid = optional_param('classid', 0, PARAM_INT); // Course ID

require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
$curPeriod = block_exastud_get_active_or_next_period();

$class = block_exastud_get_head_teacher_class($classid);

$students = block_exastud_get_class_students($class->id);

$students = $DB->get_records_sql('
	SELECT u.id, u.firstname, u.lastname, u.idnumber, u.email, cs.timemodified
	FROM {user} u
	JOIN {block_exastudclassstudents} cs ON cs.classid=? AND u.id=cs.studentid
	', [$class->id]);

$subjects = $DB->get_records('block_exastudsubjects', ['bpid' => $class->bpid]);
$classteachers = $DB->get_records('block_exastudclassteachers', ['classid' => $class->id]);

$plugininfo = core_plugin_manager::instance()->get_plugin_info('block_exastud');;

$data = (object)[];
$data->datatype = 'block_exastud_class_export';
$data->dataversion = '0.1';
$data->exporttime = time();
$data->pluginversion = $plugininfo->versiondisk;
$data->pluginrelease = $plugininfo->release;

$data->class = $class;
$data->bp = $DB->get_record('block_exastudbp', ['id' => $class->bpid]);
$data->period = $DB->get_record('block_exastudperiod', ['id' => $class->periodid]);
$data->subjects = array_values($subjects);
$data->evalopt = array_values($DB->get_records('block_exastudevalopt'));
$data->classteachers = array_values($classteachers);
$data->students = array_values($students);
$data->categories = block_exastud_get_class_categories($class->id);

$data->classteastudvis = [];
foreach ($data->classteachers as $classteacher) {
	$data->classteastudvis = array_merge($data->classteastudvis, $DB->get_records('block_exastudclassteastudvis', ['classteacherid' => $classteacher->id]));
}


$teacherids = $DB->get_records_menu('block_exastudclassteachers', ['classid' => $class->id], null, 'distinct teacherid AS tmp, teacherid');
$data->reviews = [];
if ($students && $teacherids) { /* && $subjects */
	$data->reviews = array_values($DB->get_records_sql('
	SELECT r.*
	FROM {block_exastudreview} r
	WHERE r.studentid IN ('.join(',', array_keys($students)).')
		AND periodid=?
		AND teacherid IN ('.join(',', $teacherids).')
		AND (subjectid IN ('.join(',', array_merge([-999 /* dummy */], array_keys($subjects))).') or subjectid <= 0)
		ORDER BY timemodified DESC
	', [$class->periodid]));

	foreach ($data->reviews as $review) {
		// transfer reviews also to classdata
		if ($review->subjectid > 0) {
			$studentData = block_exastud_get_subject_student_data($class->id, $review->subjectid, $review->studentid);
			if (!isset($studentData->review) && !isset($data->{'review.timemodified'})) {
				block_exastud_set_subject_student_data($class->id, $review->subjectid, $review->studentid, 'review', trim($review->review));
				block_exastud_set_subject_student_data($class->id, $review->subjectid, $review->studentid, 'review.modifiedby', $review->teacherid);
				block_exastud_set_subject_student_data($class->id, $review->subjectid, $review->studentid, 'review.timemodified', $review->timemodified);
			}
		}

		$review->pos = array_values($DB->get_records('block_exastudreviewpos', ['reviewid' => $review->id]));
	}
}

$data->data = array_values($DB->get_records('block_exastuddata', ['classid' => $class->id]));

// TESTING:
// echo json_encode($data, JSON_PRETTY_PRINT); exit;

$file = tempnam($CFG->tempdir, "zip");

require_once($CFG->libdir.'/filelib.php');
file_put_contents($file, gzencode(json_encode($data, JSON_PRETTY_PRINT)));
send_temp_file($file, 'backup_exastud_class_'.clean_filename($class->title).'_'.date('Y-m-d').'.gz');
