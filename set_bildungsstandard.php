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
$action = optional_param('action', '', PARAM_TEXT);
$type = required_param('type', PARAM_TEXT);

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_MANAGE_CLASSES);

$classid = required_param('classid', PARAM_INT);
$class = block_exastud\get_teacher_class($classid);
$classstudents = \block_exastud\get_class_students($class->id);

$url = '/blocks/exastud/set_bildungsstandard.php';
$PAGE->set_url($url);

if ($action == 'save') {
	require_sesskey();

	$userdatas = \block_exastud\param::optional_array('userdatas', [PARAM_INT => (object)[
		'bildungsstandard_erreicht' => PARAM_TEXT,
		'dropped_out' => PARAM_BOOL,
	]]);

	foreach ($classstudents as $student) {
		if (!isset($userdatas[$student->id])) {
			continue;
		}

		$current = \block_exastud\get_class_student_data($class->id, $student->id);
		$new = $userdatas[$student->id];

		if ($type == 'bildungsstandard') {
			if (@$current->bildungsstandard_erreicht != @$new->bildungsstandard_erreicht) {
				// set it, if changed
				if (@$new->bildungsstandard_erreicht) {
					\block_exastud\set_class_student_data($class->id, $student->id, 'bildungsstandard_erreicht', $new->bildungsstandard_erreicht);
					\block_exastud\set_class_student_data($class->id, $student->id, 'bildungsstandard_erreicht_time', time());
				} else {
					\block_exastud\set_class_student_data($class->id, $student->id, 'bildungsstandard_erreicht', null);
					\block_exastud\set_class_student_data($class->id, $student->id, 'bildungsstandard_erreicht_time', null);
				}
			}
		} else {
			if (@$current->dropped_out != @$new->dropped_out) {
				// set it, if changed
				if (@$new->dropped_out) {
					\block_exastud\set_class_student_data($class->id, $student->id, 'dropped_out', 1);
					\block_exastud\set_class_student_data($class->id, $student->id, 'dropped_out_time', time());
				} else {
					\block_exastud\set_class_student_data($class->id, $student->id, 'dropped_out', null);
					\block_exastud\set_class_student_data($class->id, $student->id, 'dropped_out_time', null);
				}
			}
		}
	}
}

$output = block_exastud\get_renderer();
echo $output->header('configuration_classes');

echo $output->print_subtitle($class->title);

/* Print the Students */
echo html_writer::tag("h2", \block_exastud\get_string('members', 'block_exastud'));
$table = new html_table();

$table->head = [
	$type == 'bildungsstandard'
		? \block_exastud\trans('de:Bildungsstandard')
		: \block_exastud\trans('de:Ausgeschieden'),
	\block_exastud\get_string('firstname'),
	\block_exastud\get_string('lastname'),
	\block_exastud\get_string('email'),
];
$table->align = array("left", "left", "left");
// $table->attributes['style'] = "width: 75%;";
// $table->size = ['5%', '5%', '20%', '20%', '20%'];

foreach ($classstudents as $classstudent) {
	$userdata = \block_exastud\get_class_student_data($class->id, $classstudent->id);

	if ($type == 'bildungsstandard') {
		$bildungsstandard = html_writer::select(block_exastud\get_bildungsstandards(), 'userdatas['.$classstudent->id.'][bildungsstandard_erreicht]', @$userdata->bildungsstandard_erreicht, ['' => '']);

		$input = $bildungsstandard.
			(!empty($userdata->bildungsstandard_erreicht) ? ' '.userdate($userdata->bildungsstandard_erreicht_time) : '');
	} else {
		$input = '<input name="userdatas['.$classstudent->id.'][dropped_out]" type="hidden" value="0"/>'.
			'<input name="userdatas['.$classstudent->id.'][dropped_out]" type="checkbox" value="1"'.
			(!empty($userdata->dropped_out) ? ' checked="checked"' : '').'/>'.
			(!empty($userdata->dropped_out) ? userdate($userdata->dropped_out_time) : '');
	}


	$table->data[] = [
		$input,
		$classstudent->firstname,
		$classstudent->lastname,
		$classstudent->email,
	];
}

echo '<form method="post">';
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
echo '<input type="hidden" name="action" value="save" />';

echo $output->table($table);

echo '<input type="submit" value="'.\block_exastud\get_string('savechanges').'"/>';

echo $output->back_button($CFG->wwwroot.'/blocks/exastud/configuration_classes.php?courseid='.$courseid);

echo '</form>';

echo $output->footer();
