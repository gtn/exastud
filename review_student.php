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
require_once($CFG->dirroot.'/blocks/exastud/lib/edit_form.php');

use block_exastud\globals as g;

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);
$subjectid = required_param('subjectid', PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);

require_login($courseid);

if (!$returnurl) {
	$returnurl = new moodle_url('/blocks/exastud/review_class.php?courseid='.$courseid.'&classid='.$classid.'&subjectid='.$subjectid);
}

$output = block_exastud\get_renderer();

$url = '/blocks/exastud/review_student.php';
$PAGE->set_url($url);

block_exastud_require_global_cap(block_exastud\CAP_REVIEW);

$class = block_exastud\get_review_class($classid, $subjectid);

if (!$class) {
	print_error('badclass', 'block_exastud');
}

if ($DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid)) == 0) {
	print_error('badstudent', 'block_exastud');
}

$strstudentreview = \block_exastud\get_string('reviewstudent', 'block_exastud');
$strclassreview = \block_exastud\get_string('reviewclass', 'block_exastud');
$strreview = \block_exastud\get_string('review', 'block_exastud');

$actPeriod = block_exastud_check_active_period();
$categories = block_exastud_get_class_categories($classid);

$lastPeriod = block_exastud_get_last_period();
if ($lastPeriod) {
	$lastPeriodClass = $DB->get_record_sql("
		SELECT DISTINCT c.id
		FROM {block_exastudclass} c
		JOIN {block_exastudclassstudents} cs ON cs.classid=c.id 
		JOIN {block_exastudclassteachers} ct ON ct.classid=c.id
		WHERE c.periodid=? AND cs.studentid=? AND ct.teacherid=? AND ct.subjectid=?
	", [$lastPeriod->id, $studentid, g::$USER->id, $subjectid]);
} else {
	$lastPeriodClass = null;
}

$formdata = new stdClass();

$formdata->courseid = $courseid;
$formdata->studentid = $studentid;
$formdata->classid = $classid;
$formdata->subjectid = $subjectid;

$teacherid = $USER->id;


$exacomp_grades = [];
if (block_exastud\is_exacomp_installed()) {
	$title = 'Vorschläge aus Exacomp:';

	if (!method_exists('\block_exacomp\api', 'get_subjects_with_grade_for_teacher_and_student')) {
		$exacomp_grades[] = [$title, 'Please update exacomp to latest version'];
	} else {
		$subjects = \block_exacomp\api::get_subjects_with_grade_for_teacher_and_student($teacherid, $studentid);
		if (!$subjects) {
			$exacomp_grades[] = [$title, '---'];
		}
		foreach ($subjects as $subject) {
			$exacomp_grades[] = [
				$subject->title,
				'Note: '.($subject->additionalinfo ?: '---').
				' / Niveau: '.($subject->niveau ?: '---'),
			];
		}
	}
}


if (!$reviewdata = $DB->get_record('block_exastudreview', array('teacherid' => $teacherid, 'subjectid' => $subjectid, 'periodid' => $actPeriod->id, 'studentid' => $studentid))) {
	$formdata->review = '';
} else {
	foreach ($categories as $category) {
		$formdata->{$category->id.'_'.$category->source} = $DB->get_field('block_exastudreviewpos', 'value', array("categoryid" => $category->id, "reviewid" => $reviewdata->id, "categorysource" => $category->source));
	}
	$formdata->review = $reviewdata->review;
}
$studentform = new student_edit_form(null, [
	'categories' => $categories,
	'subjectid' => $subjectid,
	'exacomp_grades' => $exacomp_grades,
]);

if ($fromform = $studentform->get_data()) {
	$newreview = new stdClass();
	$newreview->timemodified = time();
	$newreview->review = $fromform->review;

	if (isset($reviewdata->id)) {
		$newreview->id = $reviewdata->id;
		$DB->update_record('block_exastudreview', $newreview);
	} else {
		$newreview->studentid = $studentid;
		$newreview->subjectid = $subjectid;
		$newreview->periodid = $actPeriod->id;
		$newreview->teacherid = $teacherid;
		$newreview->id = $DB->insert_record('block_exastudreview', $newreview);
	}

	g::$DB->insert_or_update_record('block_exastudreview', [
		'timemodified' => time(),
		'review' => $fromform->vorschlag,
	], [
		'studentid' => $studentid,
		'subjectid' => \block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
		'periodid' => $actPeriod->id,
		'teacherid' => $teacherid,
	]);

	\block_exastud\set_subject_student_data($classid, $subjectid, $studentid, 'grade', $fromform->grade);
	\block_exastud\set_subject_student_data($classid, $subjectid, $studentid, 'niveau', $fromform->niveau);

	foreach ($categories as $category) {
		if (!isset($fromform->{$category->id.'_'.$category->source})) {
			continue;
		}

		g::$DB->insert_or_update_record('block_exastudreviewpos',
			["value" => $fromform->{$category->id.'_'.$category->source}],
			["reviewid" => $newreview->id, "categoryid" => $category->id, "categorysource" => $category->source]);
	}

	redirect($returnurl);
}

$classheader = $class->title.($class->subject_title ? ' - '.$class->subject_title : '');

echo $output->header(array('review',
	array('name' => $classheader, 'link' => $CFG->wwwroot.'/blocks/exastud/review_class.php?courseid='.$courseid.
		'&classid='.$classid.'&subjectid='.$subjectid),
), array('noheading'));

$student = $DB->get_record('user', array('id' => $studentid));

echo $OUTPUT->heading($classheader);

$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)).' '.fullname($student);
echo $OUTPUT->heading($studentdesc);

// load lern&soz vorschlag
$formdata->vorschlag = $DB->get_field('block_exastudreview', 'review', [
	'studentid' => $studentid,
	'subjectid' => \block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
	'periodid' => $actPeriod->id,
	'teacherid' => $teacherid,
]);

$formdata = (object)array_merge((array)$formdata, (array)\block_exastud\get_subject_student_data($classid, $subjectid, $studentid));

if (empty($formdata->grade)) {
	$formdata->grade = '';
}

if ($lastPeriodClass && optional_param('action', null, PARAM_TEXT) == 'load_last_period_data') {
	$lastPeriodData = (object)\block_exastud\get_subject_student_data($lastPeriodClass->id, $subjectid, $studentid);

	$formdata->vorschlag = $DB->get_field('block_exastudreview', 'review', [
		'studentid' => $studentid,
		'subjectid' => \block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
		'periodid' => $lastPeriod->id,
		'teacherid' => $teacherid,
	]);

	if ($reviewdata = $DB->get_record('block_exastudreview', array('teacherid' => $teacherid, 'subjectid' => $subjectid, 'periodid' => $lastPeriod->id, 'studentid' => $studentid))) {
		foreach ($categories as $category) {
			$formdata->{$category->id.'_'.$category->source} = $DB->get_field('block_exastudreviewpos', 'value', array("categoryid" => $category->id, "reviewid" => $reviewdata->id, "categorysource" => $category->source));
		}
		$formdata->review = $reviewdata->review;
	}

	if ($lastPeriodData->niveau || $lastPeriodData->grade) {
		$formdata->review .= '<p><b>Bewertung '.$lastPeriod->description.':</b></p>';
		if ($lastPeriodData->niveau) {
			$formdata->review .= '<p>Niveau: '.$lastPeriodData->niveau.'</p>';
		}
		if ($lastPeriodData->grade) {
			$formdata->review .= '<p>Note: '.$lastPeriodData->grade.'</p>';
		}
	}
}

if ($lastPeriodClass) {
	if (optional_param('action', null, PARAM_TEXT) == 'load_last_period_data') {
		echo '<h2>'.block_exastud\trans('de:Daten wurden geladen').'</h2>';
	} else {
		$url = block_exastud\url::request_uri();
		$url->param('action', 'load_last_period_data');
		echo $output->link_button($url, block_exastud\trans('de:Eingaben von der letzten Periode/Halbjahr übernehmen'));
	}
}

$formdata->review = block_exastud_html_to_text($formdata->review);
$formdata->vorschlag = block_exastud_html_to_text($formdata->vorschlag);

$studentform->set_data($formdata);
$studentform->display();

echo $output->back_button($returnurl);

echo $output->footer();
