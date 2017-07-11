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
$classid = required_param('classid', PARAM_INT);

require_login($courseid);

if (!$class = block_exastud_get_class($classid)) {
	throw new moodle_exception("badclass", "block_exastud");
}
if (!block_exastud_is_project_teacher($class, $USER->id)) {
	throw new moodle_exception("not a project teacher");
}

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

$output = block_exastud_get_renderer();

$url = '/blocks/exastud/review_class_project_teacher.php';
$PAGE->set_url($url, ['courseid' => $courseid, 'classid' => $classid]);
echo $output->header(array('review', '='.$classheader));
echo $output->heading($classheader);

$project_teacher_students = block_exastud_get_project_teacher_students($class, $USER->id);

/* Print the Students */
$table = new html_table();

$table->head = array();
$table->head[] = ''; //userpic
$table->head[] = block_exastud_get_string('name');
$table->head[] = '';

foreach ($categories as $category) {
	$table->head[] = $category['title'];
}

$table->align = array();
$table->align[] = 'center';
$table->align[] = 'left';
$table->align[] = 'center';

foreach ($project_teacher_students as $classstudent) {
	$icons = '<img src="'.$CFG->wwwroot.'/pix/i/edit.gif" width="16" height="16" alt="'.block_exastud_get_string('edit').'" />';
	$userdesc = fullname($classstudent);

	$data = (array)block_exastud_get_class_student_data($classid, $classstudent->id);

	$row = new html_table_row();
	$row->cells[] = $OUTPUT->user_picture($classstudent, array("courseid" => $courseid));
	$row->cells[] = $userdesc;

	$row->cells[] = $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student_project_teacher.php?courseid='.$courseid.'&classid='.$classid.'&studentid='.$classstudent->id,
		block_exastud_get_string('edit'));

	foreach ($categories as $dataid => $category) {
		if (@$category['type'] == 'select') {
			$row->cells[] = @$category['values'][$data[$dataid]];
		} else {
			$row->cells[] = !empty($data[$dataid]) ? block_exastud_text_to_html($data[$dataid]) : '';
		}
	}

	$table->data[] = $row;
}

echo $output->table($table);

echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));

echo $output->footer();
