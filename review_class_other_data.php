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

$reviewclass = block_exastud_get_review_class($classid, BLOCK_EXASTUD_SUBJECT_ID_OTHER_DATA);
$class = block_exastud_get_class($classid);

if (!$reviewclass || !$class) {
	print_error("badclass", "block_exastud");
}

if ($type == BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN) {
	$categories = [
		BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN => [
			'title' => block_exastud_trans('de:Lern- und Sozialverhalten'),
		],
	];
	$classheader = $reviewclass->title.' - '.block_exastud_trans('de:Lern- und Sozialverhalten');
} elseif ($type == BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE) {
	$categories = [
		BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE => [
			'title' => block_exastud_trans('de:Weitere Formularfelder'),
		],
	];
	$classheader = $reviewclass->title.' - '.block_exastud_trans('de:Weitere Formularfelder');
} else {
	$template = \block_exastud\print_template::create($type);
	$categories = $template->get_inputs();
	$classheader = $reviewclass->title.' - '.$template->get_name();
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
if (true) { // block_exastud_can_edit_class($reviewclass)) {
	$table->head[] = ''; // bewerten button
}
foreach ($categories as $category) {
	$table->head[] = $category['title'];
}

$table->align = array();
$table->align[] = 'center';
$table->align[] = 'left';
if (true) { // block_exastud_can_edit_class($reviewclass)) {
	$table->align[] = 'center';
}

foreach ($classstudents as $classstudent) {
	$icons = '<img src="'.$CFG->wwwroot.'/pix/i/edit.gif" width="16" height="16" alt="'.block_exastud_get_string('edit').'" />';
	$userdesc = fullname($classstudent);

	$data = (array)block_exastud_get_class_student_data($classid, $classstudent->id);

	$row = new html_table_row();
	$row->cells[] = $OUTPUT->user_picture($classstudent, array("courseid" => $courseid));
	$row->cells[] = $userdesc;

	// if (true) { // block_exastud_can_edit_class($reviewclass)) {
	$editUser = null;
	if (@$data['head_teacher']) {
		$editUser = $DB->get_record('user', array('id' => $data['head_teacher']));
	}
	if (!$editUser) {
		$editUser = $DB->get_record('user', array('id' => $reviewclass->userid));
	}

	if (@array_shift(array_keys($categories)) === BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE) {
		$hasInputs = !!block_exastud_get_student_print_template($class, $classstudent->id)->get_inputs();
	} else {
		$hasInputs = !!$categories;
	}

	if ($editUser->id !== $USER->id) {
		$row->cells[] = block_exastud_trans(['de:Zugeteilt zu {$a}'], fullname($editUser));
	} elseif (!$hasInputs) {
		// no categories, or it's a default printtemplate with no inputs
		$row->cells[] = block_exastud_trans(['de:Dieses Formular hat keine weiteren Eingabfelder'], fullname($editUser));
	} else {
		$row->cells[] = $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student_other_data.php?courseid='.$courseid.'&classid='.$classid.'&type='.$type.'&studentid='.$classstudent->id,
			block_exastud_get_string('edit'));
	}

	foreach ($categories as $dataid => $category) {
		if ($dataid === BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE) {
			$template = block_exastud_get_student_print_template($class, $classstudent->id);

			$content = '<div><b>Formular:</b> '.$template->get_name().'</div>';

			foreach ($template->get_inputs() as $dataid => $form_input) {
				if (@$form_input['type'] == 'select') {
					$value = @$form_input['values'][$data[$dataid]];
				} else {
					$value = !empty($data[$dataid]) ? block_exastud_text_to_html($data[$dataid]) : '';
				}

				$content .= '<div style="padding-top: 10px; font-weight: bold;">'.$form_input['title'].'</div>';
				$content .= '<div>'.$value.'</div>';
			}

			$row->cells[] = $content;
		} elseif (@$category['type'] == 'select') {
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
