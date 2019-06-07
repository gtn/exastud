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
$openclass = optional_param('openclass', 0, PARAM_INT);

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$url = '/blocks/exastud/review.php';
$PAGE->set_url($url);
$output = block_exastud_get_renderer();
echo $output->header('review');

$actPeriod = block_exastud_check_active_period();

function block_exastud_print_period($courseid, $period, $type, $openclass) {
    global $CFG, $USER;
	$reviewclasses = block_exastud_get_head_teacher_classes_all($period->id);
	$output = block_exastud_get_renderer();

	// first headteacher classes
	foreach ($reviewclasses as $class) {
		$class->is_head_teacher = true;
		$class->subjects = [];
	}

	// then add the subjects to the classes
    //if (!block_exastud_get_only_learnsociale_reports()) {
        $reviewsubjects = block_exastud_get_review_subjects($period->id);
    //} else {
    //    $reviewsubjects = array();
    //}

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
			$unlocked_teachers = (array)json_decode(block_exastud_get_class_data($reviewsubject->classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS), true);

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
		if (!array_key_exists($reviewsubject->classid, $reviewclasses)) {
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
        $columnsCount = 3; // Subject, Additional, Subjects from other teachers
        $table = new html_table();
        //$table->head = array(block_exastud_get_class_title($myclass->id));
        $table->align = array("left");
        //$table->attributes['class'] .= ' exastud-review-table';
		foreach ($reviewclasses as $myclass) {
		    $columnsUsed = 0;
            $shownSubjects[] = '';
            $classHeader = new html_table_row();
            $headerCell = new html_table_cell();
            $headerCellText = '<span class="exastud-collapse-data" data-classid="'.$myclass->id.'" data-expanded="'.($openclass == $myclass->id ? 1 : 0).'">';
            $headerCellText .= '<img class="collapsed_icon"
                                    style="'.($openclass == $myclass->id ? 'display:none;' : '').'"                                     
                                    src="'.$CFG->wwwroot.'/blocks/exastud/pix/collapsed.png" 
                                    width="16" height="16" 
                                    title="'.block_exastud_get_string('collapse').'" />';
            $headerCellText .= '<img class="expanded_icon"
                                    style="'.($openclass == $myclass->id ? '' : 'display:none;').'"                                    
                                    src="'.$CFG->wwwroot.'/blocks/exastud/pix/expanded.png" 
                                    width="16" height="16" 
                                    title="'.block_exastud_get_string('collapse').'" />';
            $headerCellText .= block_exastud_get_class_title($myclass->id);
            $headerCellText .= '</span>';
            $headerCell->text = $headerCellText;
            $headerCell->colspan = $columnsCount;
            $classHeader->cells[] = $headerCell;
            $classHeader->attributes['class'] = 'exastud-class-title';
            $table->data[] = $classHeader;

			$classstudents = block_exastud_get_class_students($myclass->id);
			if (!$classstudents) {
                $dRow = new \html_table_row();
                $dRow->attributes['class'] = 'exastud-data-row';
                $dRow->attributes['data-classid'] = $myclass->id;
                $dRow->attributes['data-classopened'] = ($openclass == $myclass->id ? 1 : 0);
                $hCell = new html_table_cell();
                $hCell->colspan = $columnsCount ;
                $hCell->text = block_exastud_get_string('nostudentstoreview');
                $dRow->cells[] = $hCell;
				$table->data[] = $dRow;
			} else {

                $subjectsData = array();
                //if (!block_exastud_get_only_learnsociale_reports()) {
                    foreach ($myclass->subjects as $subject) {
                        $shownSubjects[] = $subject->subjectid;
                        $subjectsData[] =
                                html_writer::link(new moodle_url('/blocks/exastud/review_class.php', [
                                        'courseid' => $courseid,
                                        'classid' => $myclass->id,
                                        'subjectid' => $subject->subjectid,
                                ]), $subject->subject_title ?: block_exastud_get_string('not_assigned'));
                    }
                //}
                
                // add all subjects from Subject teachers (for readonly via class teacher)
                $subjectsFromOtherData = array();
                if (!empty($myclass->userid) && $USER->id == $myclass->userid) {
                    $allClassSubjects = block_exastud_get_class_subjects($myclass);
                    foreach ($allClassSubjects as $addSubj) {
                        if (!in_array($addSubj->id, $shownSubjects)) {
                            // teachers for subject
                            $subjectTeachers = block_exastud_get_class_subject_teachers($myclass->id);
                            $sTeachers = array_filter($subjectTeachers, function($subject) use ($addSubj) {
                                return ($subject->subjectid == $addSubj->id ? true : false);
                            });
                            $teacherNames = array_map(function($o) {return fullname($o);}, $sTeachers);
                            $teacherNames = implode(', ', $teacherNames);
                            //$subjectTeachers = block_exastud_get_su($myclass->id);
                            $subjectsFromOtherData[] = '<span class="exastud_muted_link">'.
                                    html_writer::link(new moodle_url('/blocks/exastud/review_class.php', [
                                        'courseid' => $courseid,
                                        'classid' => $myclass->id,
                                        'subjectid' => $addSubj->id,
                                    ]), $addSubj->title ?: block_exastud_get_string('not_assigned')).
                                    ($teacherNames ? ' ('.$teacherNames.')' : '').
                                    '</span>';
                        }
                    }
                }
                
				$generaldata = array();
				if ($myclass->is_head_teacher || block_exastud_is_profilesubject_teacher($myclass->id)) {
                    if ($myclass->is_head_teacher) {
                        $generaldata[] =
                                html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
                                        'courseid' => $courseid,
                                        'classid' => $myclass->id,
                                        'type' => BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN,
                                ]), block_exastud_get_string('report_learn_and_sociale'));
                    }
                    if (!block_exastud_get_only_learnsociale_reports() && $myclass->is_head_teacher) {
                        $generaldata[] =
                                html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
                                        'courseid' => $courseid,
                                        'classid' => $myclass->id,
                                        'type' => BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE,
                                ]), block_exastud_get_string('report_other_report_fields'));
                    }
                    if (!block_exastud_get_only_learnsociale_reports() &&
                            ($myclass->is_head_teacher || block_exastud_is_profilesubject_teacher($myclass->id))) {
                        // into Subject left column!!!!
                        // only if at least one subject:
                        if (block_exastud_is_profilesubject_teacher($myclass->id)) {
                            $subjectsData[] =
                                    html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
                                            'courseid' => $courseid,
                                            'classid' => $myclass->id,
                                            'type' => BLOCK_EXASTUD_DATA_ID_CERTIFICATE,
                                    ]), block_exastud_get_string('report_for_subjects'));
                        }
                    }
                    // bilingual review
                    if (!block_exastud_get_only_learnsociale_reports()) {
                        $bilingualtemplates = block_exastud_get_bilingual_reports();
                        foreach ($bilingualtemplates as $bilingualtemplateid => $bilingualtemplatename) {
                            // temporary disabled
                            if (11==22 && block_exastud_is_bilingual_teacher($myclass->id, null, null, $bilingualtemplateid)) {
                                // into Subject left column!!!!
                                $subjectsData[] =
                                        html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
                                                'courseid' => $courseid,
                                                'classid' => $myclass->id,
                                                'type' => BLOCK_EXASTUD_DATA_ID_BILINGUALES,
                                                'templateid' => $bilingualtemplateid,
                                        ]),
                                        $bilingualtemplatename
                                        //block_exastud_get_string('report_bilinguales')
                                        );
                            }
                        }
                    }
/*                    if (!block_exastud_get_only_learnsociale_reports() && $myclass->is_head_teacher) {
                        $generaldata[] =
                                html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
                                        'courseid' => $courseid,
                                        'classid' => $myclass->id,
                                        'type' => BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO
                                ]), block_exastud_get_string('report_for_additional'));
                    }*/

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
				}

                $hRow = new \html_table_row();
                $hRow->attributes['class'] = 'exastud-part-title exastud-data-row';
                $hRow->attributes['data-classid'] = $myclass->id;
                $hRow->attributes['data-classopened'] = ($openclass == $myclass->id ? 1 : 0);
                $htCellSubject = new \html_table_cell(block_exastud_get_string('review_table_part_subjects'));
                $hRow->cells[] = $htCellSubject;
                $columnsUsed++;
                if ($myclass->is_head_teacher || block_exastud_is_profilesubject_teacher($myclass->id)) {
                    $htCell = new \html_table_cell(block_exastud_get_string('review_table_part_additional'));
/*                    if (!empty($myclass->userid) && $USER->id == $myclass->userid) {
                        if (!count($subjectsFromOtherData)) {
                            $htCell->colspan = 2; // shown subjects from other teachers
                        }
                    }*/
                    $hRow->cells[] = $htCell;
                    $columnsUsed++;
                    //$hRow->cells[] = block_exastud_get_string('review_table_part_additional');
                    if (count($subjectsFromOtherData) > 0) {
                        $htCell = new \html_table_cell(block_exastud_get_string('review_table_part_subjectsfromother'));
                        $hRow->cells[] = $htCell;
                        $columnsUsed++;
                    }
                } /*else {
                    $htCellSubject->colspan = 3;
                }*/
                // last column -> colspan
                end($hRow->cells)->colspan = $columnsCount - $columnsUsed + 1;

				$rowsCount = max(count($subjectsData), count($generaldata), count($subjectsFromOtherData));
				$table->data[] = $hRow;
                for ($i = 0; $i < $rowsCount; $i++) {
                    $dRow = new \html_table_row();
                    $dRow->attributes['class'] = 'exastud-data-row';
                    $dRow->attributes['data-classid'] = $myclass->id;
                    $dRow->attributes['data-classopened'] = ($openclass == $myclass->id ? 1 : 0);
                    //if (!block_exastud_get_only_learnsociale_reports()) {
                    $subjectsCell = new \html_table_cell();
                    $subjectsCell->text = (isset($subjectsData[$i]) ? $subjectsData[$i] : '');
                    //$subjectsCell->colspan = ($myclass->is_head_teacher || block_exastud_is_profilesubject_teacher($myclass->id) ? 1 : 2);
/*                    if (!empty($myclass->userid) && $USER->id === $myclass->userid) { // TODO: is it enough?
                        if (!count($subjectsFromOtherData)) {
                            $subjectsCell->colspan += 1; // shown column for subjects from other teachers
                        }
                    }*/
                    $dRow->cells[] = $subjectsCell;
                    //}
                    if ($myclass->is_head_teacher || block_exastud_is_profilesubject_teacher($myclass->id)) {
                        $generalCell = new \html_table_cell();
                        $generalCell->text = (isset($generaldata[$i]) ? $generaldata[$i] : '');
/*                        if (!empty($myclass->userid) && $USER->id === $myclass->userid) {
                            if (!count($subjectsFromOtherData)) {
                                $generalCell->colspan = 2; // shown subjects from other teachers
                            }
                        }*/
                        $dRow->cells[] = $generalCell;
                        if (count($subjectsFromOtherData) > 0) {
                            $subjectFromOtherCell = new \html_table_cell();
                            $subjectFromOtherCell->text = (isset($subjectsFromOtherData[$i]) ? $subjectsFromOtherData[$i] : '');
                            $dRow->cells[] = $subjectFromOtherCell;
                        }
                    }
                    // last column -> colspan
                    end($dRow->cells)->colspan = $columnsCount - $columnsUsed + 1;
                    $table->data[] = $dRow;
                }

				if (block_exastud_is_project_teacher($myclass, g::$USER->id)) {
                    $dRow = new \html_table_row();
                    $dRow->attributes['class'] = 'exastud-data-row';
                    $dRow->attributes['data-classid'] = $myclass->id;
                    $dRow->attributes['data-classopened'] = ($openclass == $myclass->id ? 1 : 0);
                    $projectCell = new \html_table_cell();
                    $projectCell->text = html_writer::link(new moodle_url('/blocks/exastud/review_class_project_teacher.php', [
                                                                'courseid' => $courseid,
                                                                'classid' => $myclass->id]),
                                                            block_exastud_get_string('report_report_eval'));
                    $projectCell->colspan = 2;
                    $dRow->cells[] = $projectCell;
					$table->data[] = $dRow;
				}
			}
		}
        echo $output->table($table);
	}
}

block_exastud_print_period($courseid, $actPeriod, 'active', $openclass);

// last period
/*if ($lastPeriod = block_exastud_get_last_period()) {
	block_exastud_print_period($courseid, $lastPeriod, 'last', $openclass);
}*/

echo $output->footer();
