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

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);

$classid = required_param('classid', PARAM_INT);
$class = block_exastud_get_head_teacher_class($classid);

$period = block_exastud_get_period($class->periodid);
// agelaufene periode => unlock anbieten
$showUnlock = $period->endtime < time();

$url = '/blocks/exastud/configuration_classes.php';
$PAGE->set_url($url);

if ($action == 'delete') {
	if (!optional_param('confirm', false, PARAM_BOOL)) {
		throw new moodle_exception('not confirmed');
	}

	$DB->delete_records('block_exastudclass', ['id' => $class->id]);

	redirect(new moodle_url('/blocks/exastud/configuration_classes.php?courseid='.$courseid));
}

$output = block_exastud_get_renderer();
echo $output->header(['configuration_classes', $type], ['class' => $class]);

/* Print the Students */
if ($type == 'students') {
	$classstudents = block_exastud_get_class_students($class->id);
	$additional_head_teachers = block_exastud_get_class_additional_head_teachers($classid);

	if ($action == 'save') {
		require_sesskey();

		$userdatas = \block_exastud\param::optional_array('userdatas', [PARAM_INT => (object)[
			'print_grades' => PARAM_BOOL,
			'bildungsstandard_erreicht' => PARAM_TEXT,
			'dropped_out' => PARAM_BOOL,
			'head_teacher' => PARAM_INT,
			'print_template' => PARAM_RAW,
			'project_teacher' => PARAM_INT,
		]]);

		foreach ($classstudents as $student) {
			if (!isset($userdatas[$student->id])) {
				continue;
			}

			$current = block_exastud_get_class_student_data($class->id, $student->id);
			$new = $userdatas[$student->id];

			block_exastud_set_class_student_data($class->id, $student->id, 'print_grades', $new->print_grades);
			block_exastud_set_class_student_data($class->id, $student->id, 'head_teacher', $new->head_teacher);
			block_exastud_set_class_student_data($class->id, $student->id, 'print_template', $new->print_template);
			block_exastud_set_class_student_data($class->id, $student->id, 'project_teacher', $new->project_teacher);

			if (@$current->bildungsstandard_erreicht != @$new->bildungsstandard_erreicht) {
				// set it, if changed
				if (@$new->bildungsstandard_erreicht) {
					block_exastud_set_class_student_data($class->id, $student->id, 'bildungsstandard_erreicht', $new->bildungsstandard_erreicht);
					block_exastud_set_class_student_data($class->id, $student->id, 'bildungsstandard_erreicht_time', time());
				} else {
					block_exastud_set_class_student_data($class->id, $student->id, 'bildungsstandard_erreicht', null);
					block_exastud_set_class_student_data($class->id, $student->id, 'bildungsstandard_erreicht_time', null);
				}
			}
			if (@$current->dropped_out != @$new->dropped_out) {
				// set it, if changed
				if (@$new->dropped_out) {
					block_exastud_set_class_student_data($class->id, $student->id, 'dropped_out', 1);
					block_exastud_set_class_student_data($class->id, $student->id, 'dropped_out_time', time());
				} else {
					block_exastud_set_class_student_data($class->id, $student->id, 'dropped_out', null);
					block_exastud_set_class_student_data($class->id, $student->id, 'dropped_out_time', null);
				}
			}
		}
	}

	$buttons_left = '';
	$buttons_left .= $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classmembers.php?courseid='.$courseid.'&classid='.$class->id,
		block_exastud_get_string('editclassmemberlist'));

	$buttons_left .= $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classmembers_courses.php?courseid='.$courseid.'&classid='.$class->id,
		block_exastud_trans(['de:Aus Kurs hinzufügen', 'en:Add from Course']));

	if (!$classstudents) {
		echo $OUTPUT->notification(block_exastud_get_string('no_entries_found'), 'notifymessage');

		echo $buttons_left;
	} else {
		$table = new html_table();

		$table->head = [
			block_exastud_get_string('lastname'),
			block_exastud_get_string('firstname'),
			block_exastud_trans('de:Geschlecht'),
			block_exastud_trans('de:Geburtsdatum'),
			block_exastud_trans('de:Geburtsort'),
		];

		// $table->attributes['style'] = "width: 75%;";
		// $table->size = ['20%', '20%', '12%', '12%', '12%', '12%', '12%'];

		if ($additional_head_teachers) {
			$table->head[] = block_exastud_trans('de:Zuständiger Klassenlehrer');
			$additional_head_teachers_select = array_map(function($teacher) {
				return fullname($teacher);
			}, $additional_head_teachers);
		}

		$table->head = array_merge($table->head, [
			block_exastud_trans('de:Lehrkraft für Projektprüfung'),
			block_exastud_trans('de:Zeugnisformular'),
			block_exastud_trans('de:Note im Lern&shy;entwicklungs&shy;bericht ausweisen'),
			block_exastud_trans('de:Bildungsstandard erreicht'),
			block_exastud_trans('de:Ausgeschieden'),
		]);

		$available_templates = \block_exastud\print_template::get_class_available_print_templates($class);
		$default_templateid = block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID);
		$available_templates_tmp = $available_templates;
		$available_templates = [];
		foreach ($available_templates_tmp as $templateid => $title) {
			if ($templateid == $default_templateid) {
				$available_templates[''] = $title.' (Standard)';
			} else {
				$available_templates[$templateid] = $title;
			}
		}

		foreach ($classstudents as $classstudent) {
			$userdata = block_exastud_get_class_student_data($class->id, $classstudent->id);

			$print_grades = '<input name="userdatas['.$classstudent->id.'][print_grades]" type="hidden" value="0"/>'.
				html_writer::checkbox('userdatas['.$classstudent->id.'][print_grades]', 1, @$userdata->print_grades);

			$bildungsstandard = html_writer::select(block_exastud_get_bildungsstandards(), 'userdatas['.$classstudent->id.'][bildungsstandard_erreicht]', @$userdata->bildungsstandard_erreicht, ['' => '']);
			$bildungsstandard = $bildungsstandard.
				(!empty($userdata->bildungsstandard_erreicht) ? ' '.userdate($userdata->bildungsstandard_erreicht_time, block_exastud_get_string('strftimedate', 'langconfig')) : '');

			$ausgeschieden = '<input name="userdatas['.$classstudent->id.'][dropped_out]" type="hidden" value="0"/>'.
				'<input name="userdatas['.$classstudent->id.'][dropped_out]" type="checkbox" value="1"'.
				(!empty($userdata->dropped_out) ? ' checked="checked"' : '').'/>'.
				(!empty($userdata->dropped_out) ? userdate($userdata->dropped_out_time, block_exastud_get_string('strftimedate', 'langconfig')) : '');

			$gender = block_exastud_get_custom_profile_field_value($classstudent->id, 'gender');

			$row = [
				$classstudent->lastname,
				$classstudent->firstname,
				$gender,
				block_exastud_get_date_of_birth($classstudent->id),
				block_exastud_get_custom_profile_field_value($classstudent->id, 'placeofbirth'),
			];

			if ($additional_head_teachers) {
				$row[] = html_writer::select($additional_head_teachers_select, 'userdatas['.$classstudent->id.'][head_teacher]', @$userdata->head_teacher, fullname($USER));
			}

			$project_teachers = [];
			foreach (block_exastud_get_class_teachers($classid) as $teacher) {
				if ($teacher->id !== $class->userid) {
					$project_teachers[$teacher->id] = fullname($teacher);
				}
			}
			natsort($project_teachers);

			$templateid = block_exastud_get_student_print_templateid($class, $classstudent->id);
			if ($templateid == $default_templateid) {
				$templateid = '';
			}

			$row = array_merge($row, [
				html_writer::select($project_teachers, 'userdatas['.$classstudent->id.'][project_teacher]', @$userdata->{BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER}, fullname($USER)),
				html_writer::select($available_templates, 'userdatas['.$classstudent->id.'][print_template]', $templateid, false),
				$print_grades,
				$bildungsstandard,
				$ausgeschieden,
			]);

			$table->data[] = $row;
		}

		echo '<form method="post">';
		echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
		echo '<input type="hidden" name="action" value="save" />';

		echo $output->table($table);

		echo '<table style="width: 100%;"><tr><td>';
		echo $buttons_left;
		echo '</td><td style="text-align: right;">';
		echo '<input type="submit" value="'.block_exastud_get_string('savechanges').'"/>';
		echo '</td></tr></table>';

		echo '</form>';
	}
}

/* Print the Classes */
if ($type == 'teachers') {
	// echo html_writer::tag("h2", block_exastud_get_string('teachers'));
	$table = new html_table();

	$table->head = array(
		block_exastud_trans('de:Fachbezeichnung'),
		block_exastud_get_string('lastname'),
		block_exastud_get_string('firstname'),
	);
	$table->align = array("left", "left", "left");
	$table->size = ['33%', '33%', '33%'];

	$classteachers = block_exastud_get_class_subject_teachers($class->id);
	$additional_head_teachers = block_exastud_get_class_additional_head_teachers($class->id);
	// need to clone the table, else the output won't work twice
	$table_clone = clone($table);

	if ($additional_head_teachers) {
		foreach ($additional_head_teachers as $classteacher) {
			$table->data[] = [
				$classteacher->subject_title ?: block_exastud_trans('de:nicht zugeordnet'),
				$classteacher->lastname,
				$classteacher->firstname,
			];
		}

		echo $output->heading2(block_exastud_get_string('additional_head_teachers'));
		echo $output->table($table);
		echo $output->heading2(block_exastud_get_string('teachers'));
	}

	$table = $table_clone;
	foreach ($classteachers as $classteacher) {
		$table->data[] = [
			$classteacher->subject_title ?: block_exastud_trans('de:nicht zugeordnet'),
			$classteacher->lastname,
			$classteacher->firstname,
		];
	}

	if (!$classteachers) {
		echo $OUTPUT->notification(block_exastud_get_string('no_entries_found'), 'notifymessage');
	} else {
		echo $output->table($table);
	}

	echo $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classteachers.php?courseid='.$courseid.'&classid='.$class->id,
		block_exastud_get_string('editclassteacherlist'));

	if ($showUnlock) {
		echo $output->heading2(block_exastud_trans(['de:Bewertung erneuet freigeben', 'en:Allow reviewing this class']));

		$unlocked_teachers = (array)json_decode(block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS), true);

		if ($action == 'unlock') {
			require_sesskey();
			$teacherid = required_param('teacherid', PARAM_INT);

			echo $output->notification(block_exastud_trans(['de:freigegeben', 'en:unlocked']));

			$unlocked_teachers[$teacherid] = strtotime('+1day');
			block_exastud_set_class_data($class->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS, json_encode($unlocked_teachers));
		}

		$teachers = [ 0 => block_exastud_trans(['de:für alle', 'en:for all'])];
		foreach (array_merge($additional_head_teachers, $classteachers) as $classteacher) {
			$teachers[$classteacher->id] = fullname($classteacher);
		}

		foreach ($teachers as $teacherid => $teacherName) {
			if (isset($unlocked_teachers[$teacherid]) && $unlocked_teachers[$teacherid] > time()) {
				echo '<div>'.$teacherName.' '.
					block_exastud_trans(['de:bis', 'en:until']).' '.userdate($unlocked_teachers[$teacherid]).'</div>';
			}
		}

		echo '<form method="post">';
		echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
		echo '<input type="hidden" name="action" value="unlock" />';
		echo '<div>';
		echo html_writer::select($teachers, 'teacherid', '', false);
		echo $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classmembers_courses.php?courseid='.$courseid.'&classid='.$class->id,
			block_exastud_get_string('go'));
		echo '</div>';
		echo '</form>';
	}
}

/* Print the categories */
if ($type == 'categories' && block_exastud_get_plugin_config('can_edit_bps_and_subjects')) {
	// echo html_writer::tag("h2", block_exastud_get_string('categories'));

	$table = new html_table();

	$table->align = array("left");
	$table->attributes['style'] = "width: 50%;";

	$categories = block_exastud_get_class_categories($class->id);

	foreach ($categories as $category) {
		$table->data[] = array($category->title);
	}

	echo $output->table($table);

	echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/exastud/configuration_categories.php?courseid='.$courseid.'&classid='.$class->id,
		block_exastud_get_string('editclasscategories'), 'get');
}

echo $output->footer();
