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

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$url = '/blocks/exastud/review.php';
$PAGE->set_url($url);
$output = block_exastud_get_renderer();
echo $output->header('review');

$actPeriod = block_exastud_check_active_period();

function block_exastud_print_period($courseid, $period, $type) {
    global $CFG;
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
        $table = new html_table();
        //$table->head = array(block_exastud_get_class_title($myclass->id));
        $table->align = array("left");
        //$table->attributes['class'] .= ' exastud-review-table';
		foreach ($reviewclasses as $myclass) {

            $classHeader = new html_table_row();
            $headerCell = new html_table_cell();
            $headerCell->text = '<span class="exastud-collapse-data" data-classid="'.$myclass->id.'">
                                    <img class="collapsed_icon" src="'.$CFG->wwwroot.'/blocks/exastud/pix/collapsed.png" width="16" height="16" title="'.block_exastud_get_string('collapse').'" />
                                    <img class="expanded_icon" src="'.$CFG->wwwroot.'/blocks/exastud/pix/expanded.png" width="16" height="16" title="'.block_exastud_get_string('collapse').'" />'.
                                    block_exastud_get_class_title($myclass->id).'
                                </span>';
            $headerCell->colspan = 2;
            $classHeader->cells[] = $headerCell;
            $classHeader->attributes['class'] = 'exastud-class-title';
            $table->data[] = $classHeader;

			$classstudents = block_exastud_get_class_students($myclass->id);
			if (!$classstudents) {
				$table->data[] = [
					block_exastud_get_string('nostudentstoreview'),
				];
			} else {
                $hRow = new \html_table_row();
                $htCell1 = new \html_table_cell(block_exastud_get_string('review_table_part_subjects'));
                $hRow->cells[] = $htCell1;
                $hRow->attributes['class'] = 'exastud-part-title exastud-data-row';
                $hRow->attributes['data-classid'] = $myclass->id;
                $subjectsData = array();
				foreach ($myclass->subjects as $subject) {
					$subjectsData[] =
						html_writer::link(new moodle_url('/blocks/exastud/review_class.php', [
							'courseid' => $courseid,
							'classid' => $myclass->id,
							'subjectid' => $subject->subjectid,
						]), $subject->subject_title ?: block_exastud_trans('de:nicht zugeordnet'));
				}
				$generaldata = array();
				if ($myclass->is_head_teacher) {
					/*if ($table->data) {
						// add spacer
						$table->data[] = ['<b>Weitere Formulardaten:'];
					}*/

                    $generaldata[] =
						html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
							'courseid' => $courseid,
							'classid' => $myclass->id,
							'type' => BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN,
						]), block_exastud_trans('de:Lern- und Sozialverhalten'));

                    $generaldata[] =
						html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
							'courseid' => $courseid,
							'classid' => $myclass->id,
							'type' => BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE,
						]), block_exastud_trans('de:Weitere Formularfelder'));

                    $generaldata[] =
						html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
							'courseid' => $courseid,
							'classid' => $myclass->id,
							'type' => BLOCK_EXASTUD_DATA_ID_ZERTIFIKAT_FUER_PROFILFACH,
						]), block_exastud_trans('de:Zertifikat für Profilfach'));

                    $generaldata[] =
						html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
							'courseid' => $courseid,
							'classid' => $myclass->id,
							'type' => BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO
						]), block_exastud_trans('de:Additional data'));

					/*$templates = \block_exastud\print_templates::get_class_other_print_templates_for_input($class);
					foreach ($templates as $key => $value) {
						$table->data[] = [
							html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
								'courseid' => $courseid,
								'classid' => $myclass->id,
								'type' => $key,
							]), $value),
						];
					}*/
					$hRow->cells[] = block_exastud_get_string('review_table_part_additional');
				} else {
                    $htCell1->colspan = 2;
                }
				$rowsCount = max(count($subjectsData), count($generaldata));
				$table->data[] = $hRow;
                for ($i = 0; $i < $rowsCount; $i++) {
                    $dRow = new \html_table_row();
                    $dRow->attributes['class'] = 'exastud-data-row';
                    $dRow->attributes['data-classid'] = $myclass->id;
                    $firstCell = new \html_table_cell();
                    $firstCell->text = (isset($subjectsData[$i]) ? $subjectsData[$i] : '');
                    $dRow->cells[] = $firstCell;
                    if ($myclass->is_head_teacher) {
                        $secondCell = new \html_table_cell();
                        $secondCell->text = (isset($generaldata[$i]) ? $generaldata[$i] : '');
                        $dRow->cells[] = $secondCell;
                    } else {
                        $firstCell->colspan = 2;
                    }
                    $table->data[] = $dRow;
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
		}
        echo $output->table($table);
	}
}

block_exastud_print_period($courseid, $actPeriod, 'active');

if ($lastPeriod = block_exastud_get_last_period()) {
	block_exastud_print_period($courseid, $lastPeriod, 'last');
}

echo $output->footer();
