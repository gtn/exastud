<?php

require "inc.php";
require_once($CFG->dirroot . '/blocks/exastud/lib/edit_form.php');

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

if ($subjectid == block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN && $class->type == 'shared') {
	// for shared classes load common review data
	$teacherid = $class->userid;
} else {
	$teacherid = $USER->id;
}

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
	
	foreach ($categories as $category) {
		if (!isset($studentedit->{$category->id.'_'.$category->source})) continue;
		
		block_exastud\globals::$DB->insert_or_update_record('block_exastudreviewpos',
			["value"=>$studentedit->{$category->id.'_'.$category->source}],
			["reviewid"=>$newreview->id,"categoryid"=>$category->id,"categorysource"=>$category->source]);
	}

	redirect($returnurl);
}

$classheader = $class->title.($class->subject?' - '.$class->subject:'');

block_exastud_print_header(array('review',
	array('name' => $classheader, 'link' => $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid .
		'&classid=' . $classid.'&subjectid=' . $subjectid),
		), array('noheading'));

$student = $DB->get_record('user', array('id' => $studentid));

echo $OUTPUT->heading($classheader);

if ($subjectid == \block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN) {
	$user = $student;
	$userReport = block_exastud_get_report($user->id, $actPeriod->id);

	$table = new html_table();

	$table->head = array();
	$table->head[] = '';
	$table->head[] = \block_exastud\get_string('name');
	$table->head[] = \block_exastud\trans('de:Geburtsdatum');
	foreach($categories as $category)
		$table->head[] = $category->title;

	$table->align = array();
	$table->align[] = 'center';
	$table->align[] = 'left';
	$table->align[] = 'left';
	for($i=0;$i<count($categories);$i++)
		$table->align[] = 'center';

	$data = array();
	$data[] = $OUTPUT->user_picture($user,array("courseid"=>$courseid));
	$data[] = fullname($user, $user->id);
	$data[] = block_exastud\get_custom_profile_field_value($student->id, 'dateofbirth');

	foreach($categories as $category) {
		$data[] = @$userReport->category_averages[$category->source.'-'.$category->id];
	}

	$table->data[] = $data;

	echo $output->print_esr_table($table);
} else {
	$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)) . ' ' . fullname($student, $student->id);
	echo $OUTPUT->heading($studentdesc);
}

$studentform->set_data($formdata);
$studentform->display();

echo $OUTPUT->single_button($returnurl,
		\block_exastud\get_string('back', 'block_exastud'));

block_exastud_print_footer();
