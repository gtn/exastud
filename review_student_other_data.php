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

$output = block_exastud_get_renderer();

$PAGE->set_url('/blocks/exastud/review_student_other_data.php', [
	'courseid' => $courseid,
	'classid' => $classid,
	'type' => $type,
	'studentid' => $studentid,
	'returnurl' => $returnurl,
]);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$reviewclass = block_exastud_get_review_class($classid, BLOCK_EXASTUD_SUBJECT_ID_OTHER_DATA);
$class = block_exastud_get_class($classid);

if (!$reviewclass || !$class) {
	print_error('badclass', 'block_exastud');
}

if ($DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid)) == 0) {
	print_error('badstudent', 'block_exastud');
}
$student = $DB->get_record('user', array('id' => $studentid));

$strstudentreview = block_exastud_get_string('reviewstudent');
$strclassreview = block_exastud_get_string('reviewclass');
$strreview = block_exastud_get_string('review');

$actPeriod = block_exastud_check_active_period();

if ($type == BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN) {
	$categories = [
		BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN => [
			'title' => block_exastud_trans('de:Lern- und Sozialverhalten'),
			'cols' => 50,
		],
	];
	$classheader = $reviewclass->title.' - '.block_exastud_trans('de:Lern- und Sozialverhalten');
} elseif ($type == BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE) {
	$categories = block_exastud_get_student_print_template($class, $student->id)->get_inputs();
	$classheader = $reviewclass->title.' - '.block_exastud_trans('de:Weitere Formularfelder');
} else {
	$template = \block_exastud\print_template::create($type);
	$categories = $template->get_inputs();
	$classheader = $reviewclass->title.' - '.$template->get_name();
}

$olddata = (array)block_exastud_get_class_student_data($classid, $studentid);

$dataid = key($categories);
$studentform = new student_other_data_form($PAGE->url, [
	'categories' => $categories, 'type' => $type,
	'modified' =>
		@$olddata[$dataid.'.modifiedby'] ?
			block_exastud_get_renderer()->last_modified(@$olddata[$dataid.'.modifiedby'], @$olddata[$dataid.'.timemodified'])
			: '',
]);

if ($fromform = $studentform->get_data()) {
	foreach ($categories as $dataid => $category) {
		block_exastud_set_class_student_data($classid, $studentid, $dataid, $fromform->{$dataid});
		block_exastud_set_class_student_data($classid, $studentid, $dataid.'.modifiedby', $USER->id);
		block_exastud_set_class_student_data($classid, $studentid, $dataid.'.timemodified', time());
	}

	redirect($returnurl);
}

echo $output->header(array('review',
	array('name' => $classheader, 'link' => $parenturl),
), array('noheading'));

echo $output->heading($classheader);

if ($type == BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN) {
	$user = $student;
	$userReport = block_exastud_get_report($user->id, $actPeriod->id);

	$table = new html_table();

	$reviewcategories = block_exastud_get_class_categories($classid);

	$table->head = array();
	$table->head[] = '';
	$table->head[] = block_exastud_get_string('name');
	$table->head[] = block_exastud_trans('de:Geburtsdatum');
	foreach ($reviewcategories as $category) {
		$table->head[] = $category->title;
	}

	$table->align = array();
	$table->align[] = 'center';
	$table->align[] = 'left';
	$table->align[] = 'left';
	for ($i = 0; $i < count($reviewcategories); $i++) {
		$table->align[] = 'center';
	}

	$row = array();
	$row[] = $OUTPUT->user_picture($user, array("courseid" => $courseid));
	$row[] = fullname($user);
	$row[] = block_exastud_get_date_of_birth($student->id);

	foreach ($reviewcategories as $category) {
		$row[] = @$userReport->category_averages[$category->source.'-'.$category->id];
	}

	$table->data[] = $row;

	echo $output->table($table);

	$vorschlaege = [];
	foreach (block_exastud_get_class_teachers($classid) as $class_teacher) {
		if ($class_teacher->subjectid == BLOCK_EXASTUD_SUBJECT_ID_ADDITIONAL_HEAD_TEACHER) {
			continue;
		}

		if (isset($vorschlaege[$class_teacher->userid])) {
			$vorschlaege[$class_teacher->userid]->subject_title .= ', '.$class_teacher->subject_title;
			continue;
		}

		$class_teacher->vorschlag = $DB->get_field('block_exastudreview', 'review', [
			'studentid' => $studentid,
			'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
			'periodid' => $actPeriod->id,
			'teacherid' => $class_teacher->userid,
		]);

		if ($class_teacher->vorschlag) {
			$vorschlaege[$class_teacher->userid] = $class_teacher;
		}
	}

	echo '<legend>'.block_exastud_trans("de:Formulierungsvorschläge").'</legend>';

	if ($vorschlaege) {
		foreach ($vorschlaege as $vorschlag) {
			echo '<div style="font-weight: bold;">'.$vorschlag->subject_title.':</div>'.$vorschlag->vorschlag;
			echo '<hr>';
		}
	} else {
		echo block_exastud_trans('de:Keine Vorschläge gefunden');
	}

} else {
	$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)).' '.fullname($student);
	echo $OUTPUT->heading($studentdesc);
}

$formdata = $olddata;

foreach ($categories as $dataid => $category) {
	$formdata[$dataid] = block_exastud_html_to_text(@$formdata[$dataid]);
}
/*
$studentdata = block_exastud_get_class_student_data($classid, $studentid);
$formdata = new stdClass;
foreach ($categories as $dataid=>$category) {
	$formdata->{$dataid} = array('text'=>isset($studentdata[$dataid]) ? $studentdata[$dataid] : '', 'format'=>FORMAT_HTML);
}
*/

$studentform->set_data($formdata);
$studentform->display();

echo $output->back_button($returnurl);

echo $output->footer();
