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
$studentid = required_param('studentid', PARAM_INT);

require_login($courseid);

$returnurl = new moodle_url('/blocks/exastud/review_class_project_teacher.php?courseid='.$courseid.'&classid='.$classid);

$output = block_exastud_get_renderer();

$PAGE->set_url('/blocks/exastud/review_student_project_teacher.php', [
	'courseid' => $courseid,
	'classid' => $classid,
	'studentid' => $studentid,
]);

if (!$class = block_exastud_get_class($classid)) {
	throw new moodle_exception("badclass", "block_exastud");
}
if (!block_exastud_is_project_teacher($class, $USER->id)) {
	throw new moodle_exception("not a project teacher");
}
if (!$student = @block_exastud_get_project_teacher_students($class, $USER->id)[$studentid]) {
	throw new moodle_exception("project student not found");
}

$strstudentreview = block_exastud_get_string('reviewstudent');
$strclassreview = block_exastud_get_string('reviewclass');
$strreview = block_exastud_get_string('review');

$actPeriod = block_exastud_check_active_period();

$categories = [
	'projekt_thema' => [
		'title' => 'Thema',
		'type' => 'text',
	],
	'projekt_grade' => [
		'title' => 'Note',
		'type' => 'select',
		'values' => ['1' => 'sehr gut', '2' => 'gut', '3' => 'befriedigend', '4' => 'ausreichend', '5' => 'mangelhaft', '6' => 'ungenügend'],
		// block_exastud_get_student_print_template($class, $student->id)->get_grade_options(),
	],
	'projekt_verbalbeurteilung' => [
		'title' => 'Verbalbeurteilung',
		'type' => 'textarea',
		'lines' => 5,
	],
];

$classheader = $class->title.' - '.block_exastud_trans('de:Projektprüfung');

$studentform = new student_other_data_form($PAGE->url, [
	'categories' => $categories,
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
	array('name' => $classheader, 'link' => $returnurl),
), array('noheading'));

echo $output->heading($classheader);

$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)).' '.fullname($student);
echo $OUTPUT->heading($studentdesc);

$formdata = (array)block_exastud_get_class_student_data($classid, $studentid);

foreach ($categories as $dataid => $category) {
	$formdata[$dataid] = block_exastud_html_to_text(@$formdata[$dataid]);
}

$studentform->set_data($formdata);
$studentform->display();

echo $output->back_button($returnurl);

echo $output->footer();
