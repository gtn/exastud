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
$type = optional_param('type', '', PARAM_TEXT);

if ($type != 'categories' && $type != 'teachers') {
	$type = 'students';
}

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_MANAGE_CLASSES);

$classid = required_param('classid', PARAM_INT);
$class = block_exastud\get_teacher_class($classid);

$url = '/blocks/exastud/configuration_classes.php';
$PAGE->set_url($url);

if ($action == 'delete') {
	if (!optional_param('confirm', false, PARAM_BOOL)) {
		throw new moodle_exception('not confirmed');
	}

	$DB->delete_records('block_exastudclass', ['id' => $class->id]);

	redirect(new moodle_url('/blocks/exastud/configuration_classes.php?courseid='.$courseid));
}

$output = block_exastud\get_renderer();
echo $output->header([['id' => 'configuration_classes', 'classid' => $classid], $type]);

if (!\block_exastud\get_class_students($class->id)) {
	$deleteButton = $output->link_button('configuration_class.php?courseid='.$courseid.'&action=delete&classid='.$class->id.'&confirm=1',
		block_exastud\get_string('delete'),
		['exa-confirm' => block_exastud\trans('de:Wirklich löschen?')]);
} else {
	$deleteButton = html_writer::empty_tag('input', [
		'type' => 'button',
		'onclick' => "alert(".json_encode(block_exastud\trans('de:Es können nur Klassen ohne Schüler gelöscht werden')).")",
		'value' => block_exastud\trans('de:Klasse löschen'),
	]);
}

$subtitle = '';
$subtitle .= $class->title;
$editlink = $CFG->wwwroot.'/blocks/exastud/configuration_class_info.php?courseid='.$courseid.'&classid='.$class->id;
$subtitle .= $output->link_button($editlink, html_writer::tag("img", '', array('src' => 'pix/edit.png')).' '.block_exastud\trans('de:Klasse bearbeiten'));
$subtitle .= $deleteButton;

echo $output->print_subtitle($subtitle);

/* Print the Students */
if ($type == 'students') {
	$classstudents = \block_exastud\get_class_students($class->id);

	if ($action == 'save') {
		require_sesskey();

		$userdatas = \block_exastud\param::optional_array('userdatas', [PARAM_INT => (object)[
			'print_grades' => PARAM_BOOL,
			'bildungsstandard_erreicht' => PARAM_TEXT,
			'dropped_out' => PARAM_BOOL,
		]]);

		foreach ($classstudents as $student) {
			if (!isset($userdatas[$student->id])) {
				continue;
			}

			$current = \block_exastud\get_class_student_data($class->id, $student->id);
			$new = $userdatas[$student->id];

			\block_exastud\set_class_student_data($class->id, $student->id, 'print_grades', $new->print_grades);

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

	echo html_writer::tag("h2", \block_exastud\get_string('students'));
	$table = new html_table();

	$table->head = [
		\block_exastud\get_string('lastname'),
		\block_exastud\get_string('firstname'),
		\block_exastud\trans('de:Geburtsdatum'),
		\block_exastud\trans('de:Note im Lern&shy;entwicklungs&shy;bericht ausweisen'),
		\block_exastud\trans('de:Bildungsstandard erreicht'),
		\block_exastud\trans('de:Ausgeschieden'),
	];
	$table->align = array("left", "left", "left");
	// $table->attributes['style'] = "width: 75%;";
	$table->size = ['20%', '20%', '15%', '15%', '15%', '15%'];

	foreach ($classstudents as $classstudent) {
		$userdata = \block_exastud\get_class_student_data($class->id, $classstudent->id);

		$print_grades = '<input name="userdatas['.$classstudent->id.'][print_grades]" type="hidden" value="0"/>'.
			html_writer::checkbox('userdatas['.$classstudent->id.'][print_grades]', 1, @$userdata->print_grades);

		$bildungsstandard = html_writer::select(block_exastud\get_bildungsstandards(), 'userdatas['.$classstudent->id.'][bildungsstandard_erreicht]', @$userdata->bildungsstandard_erreicht, ['' => '']);
		$bildungsstandard = $bildungsstandard.
			(!empty($userdata->bildungsstandard_erreicht) ? ' '.userdate($userdata->bildungsstandard_erreicht_time, get_string('strftimedate', 'langconfig')) : '');

		$ausgeschieden = '<input name="userdatas['.$classstudent->id.'][dropped_out]" type="hidden" value="0"/>'.
			'<input name="userdatas['.$classstudent->id.'][dropped_out]" type="checkbox" value="1"'.
			(!empty($userdata->dropped_out) ? ' checked="checked"' : '').'/>'.
			(!empty($userdata->dropped_out) ? userdate($userdata->dropped_out_time, get_string('strftimedate', 'langconfig')) : '');

		$table->data[] = [
			$classstudent->lastname,
			$classstudent->firstname,
			block_exastud\get_custom_profile_field_value($classstudent->id, 'dateofbirth'),
			$print_grades,
			$bildungsstandard,
			$ausgeschieden,
		];
	}

//echo html_writer::table($table);
	echo '<form method="post">';
	echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
	echo '<input type="hidden" name="action" value="save" />';

	echo $output->table($table);

	echo '<div style="text-align: right;"><input type="submit" value="'.\block_exastud\get_string('savechanges').'"/></div>';

	echo '</form>';

	echo $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classmembers.php?courseid='.$courseid.'&classid='.$class->id,
		\block_exastud\get_string('editclassmemberlist'));

	echo $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classmembers_courses.php?courseid='.$courseid.'&classid='.$class->id,
		\block_exastud\trans(['de:Aus Kurs hinzufügen', 'en:Add from Course']));
}

/* Print the Classes */
if ($type == 'teachers') {
	echo html_writer::tag("h2", \block_exastud\get_string('teachers', 'block_exastud'));
	$table = new html_table();

	$table->head = array(
		\block_exastud\get_string('lastname'),
		\block_exastud\get_string('firstname'),
		\block_exastud\trans('de:Fachbezeichnung'),
	);
	$table->align = array("left", "left", "left", "left");
	$table->size = ['25%', '25%', '25%', '25%'];

	$classteachers = block_exastud\get_class_teachers($class->id);

	foreach ($classteachers as $classteacher) {
		$table->data[] = [
			$classteacher->lastname,
			$classteacher->firstname,
			$classteacher->subject_title ?: \block_exastud\trans('de:nicht zugeordnet'),
		];
	}

	//echo html_writer::table($table);
	echo $output->table($table);

	echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/exastud/configuration_classteachers.php?courseid='.$courseid.'&classid='.$class->id,
		\block_exastud\get_string('editclassteacherlist', 'block_exastud'), 'get');
}

/* Print the categories */
if ($type == 'categories' && \block_exastud\get_plugin_config('can_edit_bps_and_subjects')) {
	echo html_writer::tag("h2", \block_exastud\get_string('categories'));

	$table = new html_table();

	$table->align = array("left");
	$table->attributes['style'] = "width: 50%;";

	$categories = block_exastud_get_class_categories($class->id);

	foreach ($categories as $category) {
		$table->data[] = array($category->title);
	}

	echo $output->table($table);

	echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/exastud/configuration_categories.php?courseid='.$courseid.'&classid='.$class->id,
		\block_exastud\get_string('editclasscategories', 'block_exastud'), 'get');
}

echo $output->footer();
