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

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$subjectid = required_param('subjectid', PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$reviewclass = block_exastud_get_review_class($classid, $subjectid);
$class = block_exastud_get_class($classid);

if (!$reviewclass || !$class) {
	print_error("badclass","block_exastud");
}

$teacherid = $USER->id;

if ($action == 'update') {
    // Update grades
    $grades = block_exastud\param::optional_array('exastud_grade', [PARAM_TEXT => PARAM_TEXT]);
    foreach ($grades as $studentid => $grade) {
        block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'grade', $grade);
        block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'grade.modifiedby', $USER->id);
        block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'grade.timemodified', time());
    }
    // Update niveaus
    $niveaus_f = block_exastud\param::optional_array('exastud_niveau', [PARAM_TEXT => PARAM_TEXT]);
    foreach ($niveaus_f as $studentid => $n) {
        block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'niveau', $n);
    }
    
    redirect($_SERVER['REQUEST_URI']);
}

if ($action == 'hide_student' || $action == 'show_student') {
    $studentid = required_param('studentid', PARAM_INT);
    $student = $DB->get_record('user', array('id' => $studentid));
    $existing = $DB->get_record('block_exastudclassteastudvis', [
            'classteacherid' => $reviewclass->classteacherid,
            'studentid' => $studentid,
    ]);
    if ($action == 'hide_student') {
        g::$DB->insert_or_update_record('block_exastudclassteastudvis', [
                'visible' => 0,
        ], [
                'classteacherid' => $reviewclass->classteacherid,
                'studentid' => $studentid,
        ]);
        if (!$existing) { // the form used also for another actions and thay can go to the link with hide_student
            \block_exastud\event\student_hidden::log(['objectid' => $classid,
                    'relateduserid' => $studentid,
                    'other' => ['classtitle' => $reviewclass->title,
                            'subjectid' => $reviewclass->subject_id,
                            'subjecttitle' => $reviewclass->subject_title,
                            'studentname' => $student->firstname.' '.$student->lastname]]);
        }
    } else if ($action == 'show_student') {
        g::$DB->delete_records('block_exastudclassteastudvis', [
                'classteacherid' => $reviewclass->classteacherid,
                'studentid' => $studentid,
        ]);
        if ($existing) { // the form used also for another actions and thay can go to the link with show_student
            \block_exastud\event\student_shown::log(['objectid' => $classid,
                    'relateduserid' => $studentid,
                    'other' => ['classtitle' => $reviewclass->title,
                            'subjectid' => $reviewclass->subject_id,
                            'subjecttitle' => $reviewclass->subject_title,
                            'studentname' => $student->firstname.' '.$student->lastname]]);
        }
    }
}

$url = '/blocks/exastud/review_class.php';
$PAGE->set_url($url, [ 'courseid'=>$courseid, 'classid'=>$classid, 'subjectid'=>$subjectid ]);
$classheader = $reviewclass->title.($reviewclass->subject_title?' - '.$reviewclass->subject_title:'');

$output = block_exastud_get_renderer();
echo $output->header(array('review', '='.$classheader));
echo $output->heading($classheader);

$actPeriod = block_exastud_check_active_period();


if (!$classstudents = block_exastud_get_class_students($classid)) {
	echo $output->heading(block_exastud_get_string('nostudentstoreview'));
	echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));
	echo $output->footer();
	exit;
}

$categories = block_exastud_get_class_categories($classid);
$evaluation_options = block_exastud_get_evaluation_options();
 
echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
echo '<input type="hidden" name="action" value="update" />';
/* Print the Students */
$table = new html_table();

$table->head = array();
$userdatacolumn = new html_table_cell();
$table->head[] = $userdatacolumn; //userdata
//$table->head[] = block_exastud_get_string('name');
//$table->head[] = '';
//$table->head[] = block_exastud_get_string('gender'); // gender column
$table->head[] = block_exastud_get_string('report_learn_and_sociale'); // bewerten button
if (!block_exastud_get_only_learnsociale_reports()) {
    $table->head[] = block_exastud_trans('de:Notenerfassung / Niveau / Fach'); // bewerten button
    $table->head[] = block_exastud_get_string('Note');
    $table->head[] = block_exastud_get_string('Niveau');
}
$table->head[] = block_exastud_trans('de:Überfachliche Beurteilungen'); // bewerten button
// foreach ($categories as $category) {
//     $categorycolumn = new html_table_cell();
//     if (count($categories) > 5) {
//         $categorycolumn->attributes['class'] .= ' verticalCell ';
//         $categorycolumn->text = '<div class="verticalText"><div class="verticalTextInner">'.$category->title.'</div>';
//     } else {
//         $categorycolumn->text = $category->title;
//     }
//     $table->head[] = $categorycolumn;
// }

$table->align = array();
//$table->align[] = 'center';
$table->align[] = 'left';

//$table->align[] = 'center';
//$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';

// for($i = 0; $i <= count($categories); $i++) {
//     $table->align[] = 'center';
// }

$table->align[] = 'left';
$table->align[] = 'right';

$hiddenclassstudents = [];
$oddeven = false;
foreach ($classstudents as $classstudent) {
	$visible = $DB->get_field('block_exastudclassteastudvis', 'visible', [
		'classteacherid' => $reviewclass->classteacherid,
		'studentid' => $classstudent->id,
	]);
	if ($visible === false) {
		$visible = true;
	}

	if ($visible !== false && !$visible) {
		// hidden
		$hiddenclassstudents[] = $classstudent;
		continue;
	}

	$icons = '<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . block_exastud_get_string('edit'). '" />';

	/*
	$report = $DB->get_records('block_exastudreview', array('subjectid'=>$subjectid, 'periodid'=>$actPeriod->id, 'studentid'=>$classstudent->id), 'timemodified DESC');
	$report = reset($report);
	*/
	$report = $DB->get_record('block_exastudreview', array('teacherid'=>$teacherid, 'subjectid'=>$subjectid, 'periodid'=>$actPeriod->id, 'studentid'=>$classstudent->id));

	$subjectData = block_exastud_get_review($classid, $subjectid, $classstudent->id);

	$row = new html_table_row();
	$userdata = '<span class="exastud-userpicture">'.$output->user_picture($classstudent, array("courseid"=>$courseid)).'</span>';
	$userdata .= '<span class="exastud-username">'.fullname($classstudent).'</span>';

	if ($visible) {
		$show_hide_url = block_exastud\url::create($PAGE->url, [ 'action'=>'hide_student', 'studentid' => $classstudent->id]);
		$show_hide_icon = $OUTPUT->pix_icon('i/hide', block_exastud_get_string('hide'));
	} else {
		$show_hide_url = block_exastud\url::create($PAGE->url, [ 'action'=>'show_student', 'studentid' => $classstudent->id]);
		$show_hide_icon = $OUTPUT->pix_icon('i/show', block_exastud_get_string('show'));
	}
    $userdata .= '<span class="exastud-usergender">'.block_exastud_get_user_gender_string($classstudent->id).'</span>';
    $userdata .= '<span class="exastud-hidebutton"><a style="padding-right: 15px;" href="'.$show_hide_url.'">'.$show_hide_icon.'</a></span>';
    $userdatacell = new html_table_cell();
    $userdatacell->attributes['class'] .= 'exastud-userdata-cell';
    $userdatacell->text = '<div class="cell-content">'.$userdata.'</div>';
    $userdatacell->rowspan = 2;
    $row->cells[] = $userdatacell;

    $row->cells[] = ($visible ? $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student.php?courseid='.$courseid.'&classid='.$classid.'&subjectid='.$subjectid.'&studentid='.$classstudent->id.'&reporttype=social',
            block_exastud_get_string('review_button'), ['class' => 'btn btn-primary']) : '');
    if (!block_exastud_get_only_learnsociale_reports()) {
        $row->cells[] = ($visible ?
                $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student.php?courseid='.$courseid.'&classid='.$classid.
                        '&subjectid='.$subjectid.'&studentid='.$classstudent->id,
                        block_exastud_get_string('review_button'), ['class' => 'btn btn-primary']) : '');
    }

	// Grades column
    if (!block_exastud_get_only_learnsociale_reports()) {
        $formdata = new stdClass();
        $formdata = (object) array_merge((array) $formdata, (array) $subjectData);
        $template = block_exastud_get_student_print_template($class, $classstudent->id);
        $grade_options = array_filter($template->get_grade_options());
        if (empty($formdata->grade)) {
            $formdata->grade = '';
        }
        if (@$formdata->grade && !array_key_exists($formdata->grade, $grade_options) && count($grade_options) > 0) {
            $grade_options = [$formdata->grade => $formdata->grade] + $grade_options;
        }
        //$grade_form->addElement('static', 'exacomp_grades', block_exastud_trans('de:Vorschläge aus Exacomp'), $grade_options['exacomp_grades']);
        if ($grade_options && is_array($grade_options) && count($grade_options) > 0) {
            $grade_form = '<select name="exastud_grade['.$classstudent->id.']" class="custom-select">';
            $grade_form .= '<option value=""></option>';
            foreach ($grade_options as $k => $grade_option) {
                if ($formdata->grade == (string) $k) {
                    $grade_form .= '<option selected="selected" value="'.$k.'">'.$grade_option.'</option>';
                } else {
                    $grade_form .= '<option value="'.$k.'">'.$grade_option.'</option>';
                }
            }
            $grade_form .= '</select>';
        } else {
            $grade_form = '<input name="exastud_grade['.$classstudent->id.']" class="form-control " value="'.$formdata->grade.
                    '" size="5"/>';
        }
        $row->cells[] = $grade_form;
        // Niveau column
        $niveaus = ['' => ''] + block_exastud\global_config::get_niveau_options();
        if (empty($formdata->niveau)) {
            $formdata->niveau = '';
        }
        $niveau_form = '<select name="exastud_niveau['.$classstudent->id.']" class="custom-select">';
        foreach ($niveaus as $k => $niveau_option) {
            if ($formdata->niveau == (string) $k) {
                $niveau_form .= '<option selected="selected" value="'.$k.'">'.$niveau_option.'</option>';
            } else {
                $niveau_form .= '<option value="'.$k.'">'.$niveau_option.'</option>';
            }
        }
        $niveau_form .= '</select>';
        $row->cells[] = $niveau_form;
    }

    $row->cells[] = ($visible ?
            $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student.php?courseid='.$courseid.'&classid='.$classid.
                    '&subjectid='.$subjectid.'&studentid='.$classstudent->id.'&reporttype=inter',
                    block_exastud_get_string('review_button'), ['class' => 'btn btn-primary']) : '');

	/* if (!$visible) {
		$cell = new html_table_cell();
		$cell->text = '';
		$cell->colspan = count($categories);
		$row->cells[] = $cell;
	} else */

//     if ($report) {
//         foreach ($categories as $category) {
//             $bewertung = $DB->get_field('block_exastudreviewpos', 'value',
//                     array("categoryid" => $category->id, "reviewid" => $report->id, "categorysource" => $category->source));
//             switch (block_exastud_get_competence_eval_type()) {
//                 case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
//                 case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
//                     $row->cells[] = $bewertung && isset($evaluation_options[$bewertung]) ? $evaluation_options[$bewertung] : '';
//                     break;
//                 case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
//                     $row->cells[] = $bewertung && $bewertung > 0 ? $bewertung : '';
//                     break;
//             }
//         }
//     } else {
//         for ($i = 0; $i < count($categories); $i++) {
//             $row->cells[] = '';
//         }
//     }

	$row->attributes['class'] = 'oddeven'.(int)$oddeven;
	$table->data[] = $row;

	if ($visible) {
        $cell = new html_table_cell();
        if (!block_exastud_get_only_learnsociale_reports()) {
		    $cell->text = '<p>'.((trim(@$subjectData->review) ? block_exastud_text_to_html(trim($subjectData->review)) : '') ?: '---').'</p>';

            if (!empty($subjectData->niveau)) {
                $cell->text .= '<p><b>'.block_exastud_get_string('Niveau').':</b> '.
                        (block_exastud\global_config::get_niveau_option_title($subjectData->niveau) ?: $subjectData->niveau).'</p>';
            }
            if (!empty($subjectData->grade)) {
                $template = block_exastud_get_student_print_template($class, $classstudent->id);
                $value = @$template->get_grade_options()[$subjectData->grade] ?: $subjectData->grade;
                $cell->text .= '<p><b>'.block_exastud_get_string('Note').':</b> '.$value.'</p>';
            }
        } else {
            $learnReview = g::$DB->get_field('block_exastudreview', 'review', [
                    'studentid' => $classstudent->id,
                    'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
                    'periodid' => $actPeriod->id,
                    'teacherid' => $teacherid]
                );


            $cell->text = '<p>'.((trim(@$learnReview) ? block_exastud_text_to_html($learnReview) : '') ?: '---').'</p>';
        }
        $cell->colspan = count($categories) + 5;
        $cell->style = 'text-align: left;';

        if ($cell) {
            //$spacerCell = new html_table_cell();
            //$spacerCell->colspan = 4;
            $row = new html_table_row(array(
                    /*$spacerCell, */$cell
            ));
            $row->attributes['class'] = 'oddeven'.(int)$oddeven;
            $table->data[] = $row;
        }
	}

	$oddeven = !$oddeven;
}

echo $output->table($table);

if ($hiddenclassstudents) {
	echo $output->heading(block_exastud_trans('de:Ausgeblendete Schüler'));

	$table = new html_table();

	$table->head = array();
	$table->head[] = ''; //userpic
	$table->head[] = block_exastud_get_string('name');
	$table->head[] = ''; //buttons

	$table->align = array();
	$table->align[] = 'center';
	$table->align[] = 'left';

	foreach ($hiddenclassstudents as $classstudent) {
		$icons = '<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . block_exastud_get_string('edit'). '" />';

		$row = new html_table_row();

		$row->cells[] = $output->user_picture($classstudent,array("courseid"=>$courseid));
		$row->cells[] = fullname($classstudent);

		$show_hide_url = block_exastud\url::create($PAGE->url, [ 'action'=>'show_student', 'studentid' => $classstudent->id]);
		$show_hide_icon = $output->pix_icon('i/show', block_exastud_get_string('show'));

		$row->cells[] =
			'<a style="padding-right: 15px;" href="'.$show_hide_url.'">'.$show_hide_icon.'</a>';

		$table->data[] = $row;
	}

	echo $output->table($table);
}

echo '<input type="submit" value="'.block_exastud_get_string('savechanges').'" class="btn btn-default"/>';
echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));
echo '</form>';
echo $output->footer();
