<?php

require __DIR__.'/inc.php';
require_once($CFG->dirroot . '/blocks/exastud/lib/edit_form.php');

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$type = required_param('type', PARAM_TEXT);
$studentid = required_param('studentid', PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);

require_login($courseid);

$parenturl = new moodle_url('/blocks/exastud/review_class_other_data.php?courseid='.$courseid.'&classid='.$classid.'&type='.$type);
if (!$returnurl) {
	$returnurl = $parenturl;
}

$output = block_exastud\get_renderer();

$PAGE->set_url('/blocks/exastud/review_student_other_data.php', [
	'courseid'=>$courseid,
	'classid'=>$classid,
	'type'=>$type,
	'studentid' => $studentid,
	'returnurl' => $returnurl,
]);

block_exastud_require_global_cap(block_exastud\CAP_REVIEW);

$class = block_exastud\get_review_class($classid, \block_exastud\SUBJECT_ID_OTHER_DATA);

if(!$class) {
	print_error('badclass', 'block_exastud');
}

if ($DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid)) == 0) {
	print_error('badstudent', 'block_exastud');
}
$student = $DB->get_record('user', array('id' => $studentid));

$strstudentreview = \block_exastud\get_string('reviewstudent', 'block_exastud');
$strclassreview = \block_exastud\get_string('reviewclass', 'block_exastud');
$strreview = \block_exastud\get_string('review', 'block_exastud');

$actPeriod = block_exastud_check_active_period();

if ($type == \block_exastud\DATA_ID_LERN_UND_SOZIALVERHALTEN) {
	$categories = [
		\block_exastud\DATA_ID_LERN_UND_SOZIALVERHALTEN => \block_exastud\trans('de:Lern- und Sozialverhalten')
	];
	$classheader = $class->title.' - '.\block_exastud\trans('de:Lern- und Sozialverhalten');
} else {
	$categories = [
		'ateliers' => \block_exastud\trans('de:Ateliers'),
		'arbeitsgemeinschaften' => \block_exastud\trans('de:Arbeitsgemeinschaften'),
		'besondere_staerken' => \block_exastud\trans('de:Besondere Stärken'),
	];
	$classheader = $class->title.' - '.\block_exastud\trans('de:Weitere Daten');
}

$studentform = new student_other_data_form($PAGE->url, array('categories'=>$categories, 'type'=>$type));

if ($fromform = $studentform->get_data()) {
	foreach ($categories as $dataid=>$category) {
		\block_exastud\set_class_student_data($classid, $studentid, $dataid, $fromform->{$dataid});
	}

	redirect($returnurl);
}

echo $output->header(array('review',
	array('name' => $classheader, 'link' => $parenturl),
		), array('noheading'));

echo $OUTPUT->heading($classheader);

if ($type == \block_exastud\DATA_ID_LERN_UND_SOZIALVERHALTEN) {
	$user = $student;
	$userReport = block_exastud_get_report($user->id, $actPeriod->id);

	$table = new html_table();

	$reviewcategories = block_exastud_get_class_categories($classid);

	$table->head = array();
	$table->head[] = '';
	$table->head[] = \block_exastud\get_string('name');
	$table->head[] = \block_exastud\trans('de:Geburtsdatum');
	foreach($reviewcategories as $category)
		$table->head[] = $category->title;

	$table->align = array();
	$table->align[] = 'center';
	$table->align[] = 'left';
	$table->align[] = 'left';
	for($i=0;$i<count($reviewcategories);$i++)
		$table->align[] = 'center';

	$row = array();
	$row[] = $OUTPUT->user_picture($user,array("courseid"=>$courseid));
	$row[] = fullname($user);
	$row[] = block_exastud\get_custom_profile_field_value($student->id, 'dateofbirth');

	foreach($reviewcategories as $category) {
		$row[] = @$userReport->category_averages[$category->source.'-'.$category->id];
	}

	$table->data[] = $row;

	echo $output->table($table);

	$vorschlaege = [];
	foreach (\block_exastud\get_class_teachers($classid) as $class_teacher) {
		if (isset($vorschlaege[$class_teacher->userid])) {
			continue;
		}

		$class_teacher->vorschlag = $DB->get_field('block_exastudreview', 'review', [
			'studentid' => $studentid,
			'subjectid' => \block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
			'periodid' => $actPeriod->id,
			'teacherid' => $class_teacher->userid,
		]);

		if ($class_teacher->vorschlag) {
			$vorschlaege[$class_teacher->userid] = $class_teacher;
		}
	}

	echo '<fieldset>';
	echo '<legend>'.\block_exastud\trans("de:Formulierungsvorschläge").'</legend>';
	echo '<div>';
	if ($vorschlaege) {
		foreach ($vorschlaege as $vorschlag) {
			echo '<b>'.$vorschlag->subject_title.':</b> '.$vorschlag->vorschlag;
			echo '<hr>';
		}
	} else {
		echo block_exastud\trans('de:Keine Vorschläge gefunden');
	}
	echo '</div></fieldset><br />';
} else {
	$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)) . ' ' . fullname($student);
	echo $OUTPUT->heading($studentdesc);
}

$formdata = block_exastud\get_class_student_data($classid, $studentid);

/*
$studentdata = block_exastud\get_class_student_data($classid, $studentid);
$formdata = new stdClass;
foreach ($categories as $dataid=>$category) {
	$formdata->{$dataid} = array('text'=>isset($studentdata[$dataid]) ? $studentdata[$dataid] : '', 'format'=>FORMAT_HTML);
}
*/

$studentform->set_data($formdata);
$studentform->display();

echo $output->back_button($returnurl);

echo $output->footer();
