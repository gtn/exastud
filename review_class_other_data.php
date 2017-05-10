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
$type = required_param('type', PARAM_TEXT);

require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$class = block_exastud_get_review_class($classid, BLOCK_EXASTUD_SUBJECT_ID_OTHER_DATA);

if (!$class) {
	print_error("badclass", "block_exastud");
}

if ($type == BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN) {
	$categories = [
		BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN => [
			'title' => block_exastud_trans('de:Lern- und Sozialverhalten'),
		],
	];
	$classheader = $class->title.' - '.block_exastud_trans('de:Lern- und Sozialverhalten');
} else {
	$categories = block_exastud_get_class_other_data_form_inputs($class, $type);
	$classheader = $class->title.' - '.$type;
}

$output = block_exastud_get_renderer();

$url = '/blocks/exastud/review_class.php';
$PAGE->set_url($url, ['courseid' => $courseid, 'classid' => $classid, 'type' => $type]);
echo $output->header(array('review', '='.$classheader));
echo $output->heading($classheader);

$actPeriod = block_exastud_check_active_period();
$classstudents = block_exastud_get_class_students($classid);
$evaluation_options = block_exastud_get_evaluation_options();

/* Print the Students */
$table = new html_table();

$table->head = array();
$table->head[] = ''; //userpic
$table->head[] = block_exastud_get_string('name');
if (true) { // block_exastud_can_edit_class($class)) {
	$table->head[] = ''; // bewerten button
}
foreach ($categories as $category) {
	$table->head[] = $category['title'];
}

$table->align = array();
$table->align[] = 'center';
$table->align[] = 'left';
if (true) { // block_exastud_can_edit_class($class)) {
	$table->align[] = 'center';
}

foreach ($classstudents as $classstudent) {
	$icons = '<img src="'.$CFG->wwwroot.'/pix/i/edit.gif" width="16" height="16" alt="'.block_exastud_get_string('edit').'" />';
	$userdesc = fullname($classstudent);

	$data = (array)block_exastud_get_class_student_data($classid, $classstudent->id);

	$row = new html_table_row();
	$row->cells[] = $OUTPUT->user_picture($classstudent, array("courseid" => $courseid));
	$row->cells[] = $userdesc;

	// if (true) { // block_exastud_can_edit_class($class)) {
	$editUser = null;
	if (@$data['head_teacher']) {
		$editUser = $DB->get_record('user', array('id' => $data['head_teacher']));
	}
	if (!$editUser) {
		$editUser = $DB->get_record('user', array('id' => $class->userid));
	}

	if ($editUser->id !== $USER->id) {
		$row->cells[] = block_exastud_trans(['de:Zugeteilt zu {$a}'], fullname($editUser));
	} else {
		$row->cells[] = $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student_other_data.php?courseid='.$courseid.'&classid='.$classid.'&type='.$type.'&studentid='.$classstudent->id,
			block_exastud_get_string('edit'));
	}

	foreach ($categories as $dataid => $category) {
		$row->cells[] = !empty($data[$dataid]) ? block_exastud_text_to_html($data[$dataid]) : '';
	}

	$table->data[] = $row;
}

echo $output->table($table);

echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));

echo $output->footer();
