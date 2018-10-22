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

use block_exastud\globals as g;

$courseid = optional_param('courseid', 1, PARAM_INT);

require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$url = '/blocks/exastud/review.php';
$PAGE->set_url($url);
$output = block_exastud_get_renderer();
echo $output->header('review');

$actPeriod = block_exastud_check_active_period();

function block_exastud_print_period($courseid, $period, $type) {
	$reviewclasses = block_exastud_get_head_teacher_classes_all($period->id);
	$output = block_exastud_get_renderer();

	// first headteacher classes
	foreach ($reviewclasses as $class) {
		$class->is_head_teacher = true;
		$class->subjects = [];
	}

	// then add the subjects to the classes
	$reviewsubjects = block_exastud_get_review_subjects($period->id);

	if ($type == 'last') {

		// filter unlocked
		foreach ($reviewclasses as $key => $class) {
			$unlocked_teachers = (array)json_decode(block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS), true);

			if ((isset($unlocked_teachers[g::$USER->id]) && $unlocked_teachers[g::$USER->id] > time())
				|| (isset($unlocked_teachers[0]) && $unlocked_teachers[0] > time())
			) {
				// unlocked
			} else {
				// locked
				unset($reviewclasses[$key]);
			}
		}

		foreach ($reviewsubjects as $key => $reviewsubject) {
			$unlocked_teachers = (array)json_decode(block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS), true);

			if ((isset($unlocked_teachers[g::$USER->id]) && $unlocked_teachers[g::$USER->id] > time())
				|| (isset($unlocked_teachers[0]) && $unlocked_teachers[0] > time())
			) {
				// unlocked
			} else {
				// locked
				unset($reviewsubjects[$key]);
			}
		}
	}

	foreach ($reviewsubjects as $reviewsubject) {
		if (!isset($reviewclasses[$reviewsubject->classid])) {
			$reviewclasses[$reviewsubject->classid] = $reviewsubject;
			$reviewclasses[$reviewsubject->classid]->id = $reviewsubject->classid;
			$reviewclasses[$reviewsubject->classid]->is_head_teacher = false;
			$reviewclasses[$reviewsubject->classid]->subjects = [];
		}

		$reviewclasses[$reviewsubject->classid]->subjects[] = $reviewsubject;
	}

	// $lern_und_sozialverhalten_classes = block_exastud_get_head_teacher_lern_und_sozialverhalten_classes();

	if ($type == 'last' && !$reviewclasses) {
		// all locked in last period, don't print anything
		return;
	}

	echo $output->heading($period->description);

	if (!$reviewclasses) {
		echo block_exastud_get_string('noclassestoreview');
	} else {
		foreach ($reviewclasses as $myclass) {
			$table = new html_table();

			$table->head = array(block_exastud_get_class_title($myclass->id));

			$table->align = array("left");

			$classstudents = block_exastud_get_class_students($myclass->id);
			if (!$classstudents) {
				$table->data[] = [
					block_exastud_get_string('nostudentstoreview'),
				];
			} else {
				foreach ($myclass->subjects as $subject) {
					$table->data[] = [
						html_writer::link(new moodle_url('/blocks/exastud/review_class.php', [
							'courseid' => $courseid,
							'classid' => $myclass->id,
							'subjectid' => $subject->subjectid,
						]), $subject->subject_title ?: block_exastud_trans('de:nicht zugeordnet')),
					];
				}

				if ($myclass->is_head_teacher) {
					if ($table->data) {
						// add spacer
						$table->data[] = ['<b>Weitere Formulardaten:'];
					}

					$table->data[] = [
						html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
							'courseid' => $courseid,
							'classid' => $myclass->id,
							'type' => BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN,
						]), block_exastud_trans('de:Lern- und Sozialverhalten')),
					];

					$table->data[] = [
						html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
							'courseid' => $courseid,
							'classid' => $myclass->id,
							'type' => BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE,
						]), block_exastud_trans('de:Weitere Formularfelder')),
					];

					$templates = \block_exastud\print_templates::get_class_other_print_templates_for_input($class);
					foreach ($templates as $key => $value) {
						$table->data[] = [
							html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
								'courseid' => $courseid,
								'classid' => $myclass->id,
								'type' => $key,
							]), $value),
						];
					}
				}

				if (block_exastud_is_project_teacher($myclass, g::$USER->id)) {
					$table->data[] = [
						html_writer::link(new moodle_url('/blocks/exastud/review_class_project_teacher.php', [
							'courseid' => $courseid,
							'classid' => $myclass->id,
						]), block_exastud_trans('de:Projektprüfung')),
					];
				}
			}

			echo $output->table($table);
		}
	}
}

block_exastud_print_period($courseid, $actPeriod, 'active');

if ($lastPeriod = block_exastud_get_last_period()) {
	block_exastud_print_period($courseid, $lastPeriod, 'last');
}

echo $output->footer();
