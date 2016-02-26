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
require_once($CFG->dirroot . '/blocks/exastud/lib/edit_form.php');

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

if(!$class) {
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

$formdata = new stdClass();

$formdata->courseid = $courseid;
$formdata->studentid = $studentid;
$formdata->classid = $classid;
$formdata->subjectid = $subjectid;

$teacherid = $USER->id;

if (!$reviewdata = $DB->get_record('block_exastudreview', array('teacherid' => $teacherid, 'subjectid'=>$subjectid, 'periodid' => $actPeriod->id, 'studentid' => $studentid))) {
	$formdata->review = '';
} else {
	foreach($categories as $category) {
		$formdata->{$category->id.'_'.$category->source} = $DB->get_field('block_exastudreviewpos', 'value', array("categoryid"=>$category->id,"reviewid"=>$reviewdata->id,"categorysource"=>$category->source));
	}
	$formdata->review = $reviewdata->review;
}
$studentform = new student_edit_form(null,array('categories'=>$categories, 'subjectid'=>$subjectid));

if ($studentedit = $studentform->get_data()) {
	$newreview = new stdClass();
	$newreview->timemodified = time();
	$newreview->review = $studentedit->review;

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
		'review' => $studentedit->vorschlag,
	], [
		'studentid' => $studentid,
		'subjectid' => \block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
		'periodid' => $actPeriod->id,
		'teacherid' => $teacherid,
	]);

	foreach ($categories as $category) {
		if (!isset($studentedit->{$category->id.'_'.$category->source})) continue;

		g::$DB->insert_or_update_record('block_exastudreviewpos',
			["value"=>$studentedit->{$category->id.'_'.$category->source}],
			["reviewid"=>$newreview->id,"categoryid"=>$category->id,"categorysource"=>$category->source]);
	}

	redirect($returnurl);
}

$classheader = $class->title.($class->subject_title?' - '.$class->subject_title:'');

echo $output->header(array('review',
	array('name' => $classheader, 'link' => $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid .
		'&classid=' . $classid.'&subjectid=' . $subjectid),
		), array('noheading'));

$student = $DB->get_record('user', array('id' => $studentid));

echo $OUTPUT->heading($classheader);

$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)) . ' ' . fullname($student);
echo $OUTPUT->heading($studentdesc);

// load lern&soz vorschlag
$formdata->vorschlag = $DB->get_field('block_exastudreview', 'review', [
	'studentid' => $studentid,
	'subjectid' => \block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
	'periodid' => $actPeriod->id,
	'teacherid' => $teacherid,
]);

$studentform->set_data($formdata);
$studentform->display();

echo $output->back_button($returnurl);

echo $output->footer();
