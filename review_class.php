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
$type = optional_param('type', '', PARAM_TEXT);

setcookie('lastclass', $classid);

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$class = block_exastud_get_class($classid);
$simulateSubjectId = $subjectid;
if ((block_exastud_is_profilesubject_teacher($classid) || $class->userid != $USER->id)
        && $type == BLOCK_EXASTUD_DATA_ID_CERTIFICATE) {
    $simulateSubjectId = BLOCK_EXASTUD_DATA_ID_CERTIFICATE;
}
$reviewclass = block_exastud_get_review_class($classid, $simulateSubjectId);

if (!$class || (!$reviewclass && $USER->id != $class->userid)) { // class teacher can preview of reviews
	print_error("badclass", "block_exastud");
}

$isSubjectTeacher = true;
$teacherid = $USER->id;

if (!$reviewclass && $USER->id == $class->userid) {
    $isSubjectTeacher = false;
    $subjectTeachers = block_exastud_get_class_subject_teachers($class->id);
    $sTeachers = array_filter($subjectTeachers, function($subject) use ($subjectid) {
        return ($subject->subjectid == $subjectid ? true : false);
    });
    $teacherid = array_shift($sTeachers)->id;
    $reviewclass = $DB->get_record_sql("
			SELECT ct.id, ct.id AS classteacherid, c.title, s.title AS subject_title, s.id as subject_id, c.userid
			FROM {block_exastudclassteachers} ct
			JOIN {block_exastudclass} c ON ct.classid=c.id
			LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
			WHERE ct.teacherid=? AND ct.classid=? AND ct.subjectid >= 0 AND ".($subjectid ? 's.id=?' : 's.id IS NULL')."
		", array($teacherid, $classid, $subjectid), IGNORE_MULTIPLE);
}


if ($action == 'update' && $isSubjectTeacher) {
    // Update genders
    $genders = block_exastud\param::optional_array('exastud_gender', [PARAM_TEXT => PARAM_TEXT]);
    foreach ($genders as $studentid => $gender) {
        block_exastud_set_custom_profile_field_value($studentid, 'gender', $gender);
    }
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

    redirect('review.php?courseid='.$courseid.'&openclass='.$classid);
    //redirect($_SERVER['REQUEST_URI']);
}

if (($action == 'hide_student' || $action == 'show_student') && $isSubjectTeacher) {
    $studentid = required_param('studentid', PARAM_ALPHANUM);
    if ($studentid == 'all') {
        // all students for class
        $allstudents = block_exastud_get_class_students($classid);
        $allstudents = array_keys($allstudents);
        $alreadyhiddenStudents = g::$DB->get_records_menu('block_exastudclassteastudvis',
                [   'classteacherid' => $reviewclass->classteacherid,
                    'visible' => 0,
                ],
                '',
                'studentid, studentid as temp');
        $alreadyhiddenStudents = array_keys($alreadyhiddenStudents);
        if ($action == 'hide_student') {
            $studentsTo = array_diff($allstudents, $alreadyhiddenStudents);
        } else {
            $studentsTo = $alreadyhiddenStudents;
        }
        $students = $studentsTo;
    } else {
        $students = array(intval($studentid));
    }

    $doHideShow = function($action, $classid, $student, $reviewclass) use ($DB) {
        $existing = $DB->get_record('block_exastudclassteastudvis', [
                'classteacherid' => $reviewclass->classteacherid,
                'studentid' => $student->id,
        ]);
        if ($action == 'hide_student') {
            g::$DB->insert_or_update_record('block_exastudclassteastudvis', [
                    'visible' => 0,
            ], [
                    'classteacherid' => $reviewclass->classteacherid,
                    'studentid' => $student->id,
            ]);
            if (!$existing) { // the form used also for another actions and thay can go to the link with hide_student
                \block_exastud\event\student_hidden::log(['objectid' => $classid,
                        'relateduserid' => $student->id,
                        'other' => ['classtitle' => $reviewclass->title,
                                'subjectid' => $reviewclass->subject_id,
                                'subjecttitle' => $reviewclass->subject_title,
                                'studentname' => $student->firstname.' '.$student->lastname]]);
            }
        } else if ($action == 'show_student') {
            g::$DB->delete_records('block_exastudclassteastudvis', [
                    'classteacherid' => $reviewclass->classteacherid,
                    'studentid' => $student->id,
            ]);
            if ($existing) { // the form used also for another actions and thay can go to the link with show_student
                \block_exastud\event\student_shown::log(['objectid' => $classid,
                        'relateduserid' => $student->id,
                        'other' => ['classtitle' => $reviewclass->title,
                                'subjectid' => $reviewclass->subject_id,
                                'subjecttitle' => $reviewclass->subject_title,
                                'studentname' => $student->firstname.' '.$student->lastname]]);
            }
        }
    };

    foreach ($students as $studentid) {
        $student = $DB->get_record('user', array('id' => $studentid, 'deleted' => 0));
        $doHideShow($action, $classid, $student, $reviewclass);
    }


}

$url = '/blocks/exastud/review_class.php';
$PAGE->set_url($url, [ 'courseid'=>$courseid, 'classid'=>$classid, 'subjectid'=>$subjectid ]);
$classheader = $reviewclass->title.($reviewclass->subject_title?' - '.$reviewclass->subject_title:'');

$output = block_exastud_get_renderer();
echo $output->header(array('review', '='.$classheader));
echo $output->heading($classheader);

$actPeriod = block_exastud_check_active_period();


if (!$classstudents = block_exastud_get_class_students($classid, true)) {
	echo $output->heading(block_exastud_get_string('nostudentstoreview'));
	echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));
	echo $output->footer();
	exit;
}

$categories = block_exastud_get_class_categories($classid);
$evaluation_options = block_exastud_get_evaluation_options();

// hide cross category editing for these categories:
$hideCrossCategoryFor = array('Abgang', 'Abschluss');
 
echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="post" class="exastud-review-form">';
echo '<input type="hidden" name="action" value="update" />';

$tableheadernote = block_exastud_get_string('Note');
$tableheaderniveau = block_exastud_get_string('Niveau');
$tableheadersubjects = block_exastud_trans('de:Fachkompetenzen '); // bewerten button
$tableheaderlearnsocial = block_exastud_get_string('learn_and_sociale'); // bewerten button
$tableheadercategories = block_exastud_trans('de:Überfachliche Kompetenzen'); // bewerten button

/* Print the Students */
$table = new html_table();
$table->head = array();
$userdatacolumn = new html_table_cell();
$table->head[] = $userdatacolumn; //userdata
$hide_allstudents_url = block_exastud\url::create($PAGE->url, ['action' => 'hide_student', 'studentid' => 'all']);
$hideAllButton = '<span class="exastud-hidebutton">
                    <a style="padding-right: 15px;" href="'.$hide_allstudents_url.'">'.
                        $OUTPUT->pix_icon('i/hide', block_exastud_get_string('hide')).
                        '&nbsp;'.block_exastud_get_string('hide_all').
                    '</a></span>';
if ($isSubjectTeacher) {
    $table->head[] = $hideAllButton; // hide button
}
$table->head[] = block_exastud_get_string('gender'); // gender
if (block_exastud_is_bw_active()) {
    $table->head[] = $tableheadernote; // note
    $table->head[] = $tableheaderniveau; // niveau
    $table->head[] = $tableheadersubjects; // fachcompetenzen
}
if ($isSubjectTeacher) {
    // if at least one student has tamplate with a category not in $hideCrossCategoryFor
    $editCrossCategories = false;
    if (block_exastud_can_edit_crosscompetences_subjectteacher($classid)) {
        foreach ($classstudents as $classstudent) {
            $template = block_exastud_get_student_print_template($class, $classstudent->id);
            $template_category = $template->get_category();
            if (!in_array($template_category, $hideCrossCategoryFor)) {
                $editCrossCategories = true;
                break;
            }
        }
    }
    if ($editCrossCategories) {
        $table->head[] = $tableheadercategories; // Interdisciplinary competences
    }
}
if (block_exastud_can_edit_learnsocial_subjectteacher($classid)) {
    $table->head[] = $tableheaderlearnsocial; // learn and social
}

$table->align = array();
$table->align[] = 'left';

$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';

$table->align[] = 'left';
$table->align[] = 'right';

$hiddenclassstudents = [];
$oddeven = false;

$tabledeletecolumns = array('niveau', 'subjects', 'learnsocial'/*, 'categories'*/);
$tabledeletecolumns = array_combine($tabledeletecolumns, $tabledeletecolumns);
$gender_options = block_exastud_get_custom_profile_field_valuelist('gender', 'param1', true);

if ($isSubjectTeacher) {

    foreach ($classstudents as $classstudent) {
        $visible = $DB->get_field('block_exastudclassteastudvis', 'visible', [
                'classteacherid' => $reviewclass->classteacherid,
                'studentid' => $classstudent->id,
        ]);
        if ($visible === false) { // if no table record
            $visible = true;
        }

        if ($visible !== false && !$visible) {
            // hidden
            $hiddenclassstudents[] = $classstudent;
            continue;
        }

        $icons = '<img src="'.$CFG->wwwroot.'/pix/i/edit.gif" width="16" height="16" alt="'.block_exastud_get_string('edit').'" />';

        /*
        $report = $DB->get_records('block_exastudreview', array('subjectid'=>$subjectid, 'periodid'=>$actPeriod->id, 'studentid'=>$classstudent->id), 'timemodified DESC');
        $report = reset($report);
        */
        $report = $DB->get_record('block_exastudreview',
                array('teacherid' => $teacherid,
                    'subjectid' => $subjectid,
                    'periodid' => $actPeriod->id,
                    'studentid' => $classstudent->id), '*', IGNORE_MULTIPLE);
        // if the student has a review from another teacher - probably this student was hidden and than again shown
        // such student is not able to be review again
        $reports_from_anotherteachers = $DB->get_records_sql('SELECT * FROM {block_exastudreview}
                                                    WHERE subjectid = ?
                                                        AND periodid = ?
                                                        AND studentid = ?
                                                        AND teacherid != ?',
                                                array($subjectid,
                                                    $actPeriod->id,
                                                    $classstudent->id,
                                                    $teacherid));
        $canReviewStudent = true;

//var_dump($canReviewStudent).'<br>';
        $subjectData = block_exastud_get_review($classid, $subjectid, $classstudent->id);

        $template = block_exastud_get_student_print_template($class, $classstudent->id);
        
        $template_category = $template->get_category();
        $editCrossCategories = false;
        if (block_exastud_can_edit_crosscompetences_subjectteacher($classid)) {
            if (!in_array($template_category, $hideCrossCategoryFor)) {
                $editCrossCategories = true;
            }
        }

        // some columns can be empty because template has not such fields:
        $editSubjectNiveau = false;
        $editSubjectGrade = true;
        $editSubjectReview = false;
        $editLearnSocialBehavior = false;
        $allinputs = $template->get_inputs('all');
		$personalHeadTeacher = block_exastud_get_personal_head_teacher($class->id, $classstudent->id, true);
        if ($allinputs) {
            if (array_key_exists('subjects', $allinputs)) {
                $editSubjectReview = true;
                $editSubjectNiveau = true;
                unset($tabledeletecolumns['niveau']);
                unset($tabledeletecolumns['subjects']);
            }
			// show learn and social if the report has input for this and if I am a main teacher for the student
            if (!block_exastud_is_bw_active() && block_exastud_can_edit_learnsocial_classteacher($class->id)) {
                $editLearnSocialBehavior = true;
                unset($tabledeletecolumns['learnsocial']);
            } elseif (array_key_exists('learn_social_behavior', $allinputs)) {
                $editLearnSocialBehavior = true;
                unset($tabledeletecolumns['learnsocial']);
            }
        }
        if (!$canReviewStudent) {
            $editSubjectNiveau = false;
            $editSubjectGrade = false;
            $editSubjectReview = false;
            $editLearnSocialBehavior = false;
            $editCrossCategories = false;
        }

        if (!block_exastud_is_bw_active()) {
            $editSubjectGrade = false;
            $editSubjectReview = false;
            $editSubjectNiveau = false;
            // return to delete :-)
            $tabledeletecolumns['niveau'] = 'niveau';
            $tabledeletecolumns['subjects'] = 'subjects';
        }

        $row = new html_table_row();
        $row->attributes['data-studentid'] = $classstudent->id;
        // user data
        $userdata = '<span class="exastud-userpicture">'.$output->user_picture($classstudent, array("courseid" => $courseid)).'</span>';
        $userdata .= '<span class="exastud-username">'.fullname($classstudent).'</span>';

        $userdatacell = new html_table_cell();
        $userdatacell->attributes['class'] .= 'exastud-userdata-cell';
        $userdatacell->text = '<div class="cell-content">'.$userdata.'</div>';
        $userdatacell->text .= '<span class="exastud-template-title">'.block_exastud_trans('de:Zeugnisformular').': '.$template->get_name().'</span>';
        if (block_exastud_is_bw_active()) {
            $userdatacell->rowspan = 2;
        }
        $row->cells[] = $userdatacell;

        // hide button
        if ($visible) {
            $show_hide_url = block_exastud\url::create($PAGE->url, ['action' => 'hide_student', 'studentid' => $classstudent->id]);
            $show_hide_icon = $OUTPUT->pix_icon('i/hide', block_exastud_get_string('hide'));
        } else {
            $show_hide_url = block_exastud\url::create($PAGE->url, ['action' => 'show_student', 'studentid' => $classstudent->id]);
            $show_hide_icon = $OUTPUT->pix_icon('i/show', block_exastud_get_string('show'));
        }
        $hidecolumn = new html_table_cell();
        if (block_exastud_is_bw_active()) {
            $hidecolumn->rowspan = 2;
        }
        $hidecolumn->style .= ' vertical-align: top; ';
        $hidecolumn->text = '<span class="exastud-hidebutton"><a style="padding-right: 15px;" href="'.$show_hide_url.'">'.$show_hide_icon.'</a></span>';
        $row->cells[] = $hidecolumn;

        // gender
        $gendercolumn = new html_table_cell();
        if (block_exastud_is_bw_active()) {
            $gendercolumn->rowspan = 2;
        }
        $gendercolumn->style .= ' vertical-align: top; ';
        $gender = block_exastud_get_user_gender_string($classstudent->id);
        if ($gender) {
            $gendercolumn->text = '<span class="exastud-usergender">'.$gender.'</span>';
        } else {
            // show gender selectbox only if the user does not have own gender
            $gender_form = '<select name="exastud_gender['.$classstudent->id.']" class="custom-select">';
            foreach ($gender_options as $k => $gender_option) {
                $gender_form .= '<option value="'.$gender_option.'">'.$gender_option.'</option>';
            }
            $gender_form .= '</select>';
            $gendercolumn->text = $gender_form;
        }
        $row->cells[] = $gendercolumn;

        if (block_exastud_is_bw_active()) {
            // Grades column
            $formdata = new stdClass();
            $formdata = (object) array_merge((array) $formdata, (array) $subjectData);
            $grade_options = array_filter($template->get_grade_options());
            if (empty($formdata->grade)) {
                $formdata->grade = '';
            }
            if (@$formdata->grade && !array_key_exists($formdata->grade, $grade_options) && count($grade_options) > 0) {
                $grade_options = [$formdata->grade => $formdata->grade] + $grade_options;
            }
            //$grade_form->addElement('static', 'exacomp_grades', block_exastud_trans('de:Vorschläge aus Exacomp'), $grade_options['exacomp_grades']);
            if ($editSubjectGrade) {
                if ($grade_options && is_array($grade_options) && count($grade_options) > 0) {
                    $grade_form = '<select name="exastud_grade[' . $classstudent->id . ']" class="custom-select">';
                    $grade_form .= '<option value=""></option>';
                    foreach ($grade_options as $k => $grade_option) {
                        if ($formdata->grade == (string)$k) {
                            $grade_form .= '<option selected="selected" value="' . $k . '">' . $grade_option . '</option>';
                        } else {
                            $grade_form .= '<option value="' . $k . '">' . $grade_option . '</option>';
                        }
                    }
                    $grade_form .= '</select>';
                } else {
                    $grade_form = '<input name="exastud_grade[' . $classstudent->id . ']" class="form-control " value="' . $formdata->grade .
                        '" size="5"/>';
                }
            } else {
                $grade_form = '';
            }
            $row->cells[] = $grade_form;
            // Niveau column
            if ($editSubjectNiveau) {
                $no_niveau = $DB->get_field('block_exastudsubjects', 'no_niveau', ['id' => $subjectid]);
                $niveaus = ['' => ''] + block_exastud\global_config::get_niveau_options($no_niveau);
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
            } else {
                $row->cells[] = '';
            }
            // Fachkompetenzen column
            if ($editSubjectReview) {
                $row->cells[] = ($visible ?
                        $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student.php?courseid='.$courseid.'&classid='.
                                $classid.'&subjectid='.$subjectid.'&studentid='.$classstudent->id,
                                block_exastud_get_string('review_button'), ['class' => 'btn btn-primary']) : '');
            } else {
                $row->cells[] = '';
            }
        }

        // Überfachliche Beurteilungen
        if (block_exastud_can_edit_crosscompetences_subjectteacher($classid)) {
            if ($editCrossCategories) {
                $row->cells[] = ($visible ?
                        $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student.php?courseid='.$courseid.'&classid='.
                                $classid.'&subjectid='.$subjectid.'&studentid='.$classstudent->id.'&reporttype=inter',
                                block_exastud_get_string('review_button'), ['class' => 'btn btn-primary']) : '');
            } else {
                $row->cells[] = '';
            }
        }
        // Learning and social behavior column
        if (block_exastud_can_edit_learnsocial_subjectteacher($classid)) {
            if ($editLearnSocialBehavior) {
                $row->cells[] = ($visible ?
                        $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student.php?courseid='.$courseid.'&classid='.
                                $classid.'&subjectid='.$subjectid.'&studentid='.$classstudent->id.'&reporttype=social',
                                block_exastud_get_string('review_button'), ['class' => 'btn btn-primary']) : '');
            } else {
                $row->cells[] = '';
            }
        }

        $row->attributes['class'] = 'oddeven'.(int) $oddeven;
        $table->data[] = $row;

        // preview of reviews
        if ($visible && block_exastud_is_bw_active()) {
            $cell = new html_table_cell();
            //if (!block_exastud_get_only_learnsociale_reports()) {
                if ($editSubjectReview) {
                    $cell->text = '<p>'.
                            ((trim(@$subjectData->review) ? block_exastud_text_to_html(trim($subjectData->review)) : '') ?: '---').
                            '</p>';
                }
                if ($editSubjectNiveau) {
                    if (!empty($subjectData->niveau)) {
                        $cell->text .= '<p><b>'.block_exastud_get_string('Niveau').':</b> '.
                                (block_exastud\global_config::get_niveau_option_title($subjectData->niveau) ?: $subjectData->niveau).
                                '</p>';
                    }
                }
                if (!empty($subjectData->grade)) {
                    $template = block_exastud_get_student_print_template($class, $classstudent->id);
                    $value = @$template->get_grade_options()[$subjectData->grade] ?: $subjectData->grade;
                    $cell->text .= '<p><b>'.block_exastud_get_string('Note').':</b> '.$value.'</p>';
                }
            /*} else {
                $learnReview = g::$DB->get_field('block_exastudreview', 'review', [
                                'studentid' => $classstudent->id,
                                //'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
                                'subjectid' => $subjectid,
                                'periodid' => $actPeriod->id,
                                'teacherid' => $teacherid]
                );
                $cell->text = '<p>'.((trim(@$learnReview) ? block_exastud_text_to_html($learnReview) : '') ?: '---').'</p>';
            }*/
            $cell->colspan = count($categories) + 5;
            $cell->style = 'text-align: left;';

            if ($cell) {
                //$spacerCell = new html_table_cell();
                //$spacerCell->colspan = 4;
                $row = new html_table_row(array(
                    /*$spacerCell, */
                        $cell
                ));
                $row->attributes['class'] = 'oddeven'.(int) $oddeven;
                $table->data[] = $row;
            }
        }

        $oddeven = !$oddeven;
    }

} else { // for non subject teacher = class owner - review of class
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

        $report = $DB->get_record('block_exastudreview',
                array('teacherid' => $teacherid, 'subjectid' => $subjectid, 'periodid' => $actPeriod->id,
                        'studentid' => $classstudent->id));

        $subjectData = block_exastud_get_review($classid, $subjectid, $classstudent->id);

        $row = new html_table_row();
        $userdata =
                '<span class="exastud-userpicture">'.$output->user_picture($classstudent, array("courseid" => $courseid)).'</span>';
        $userdata .= '<span class="exastud-username">'.fullname($classstudent).'</span>';

        $userdata .= '<span class="exastud-usergender">'.block_exastud_get_user_gender_string($classstudent->id).'</span>';
        $userdatacell = new html_table_cell();
        $userdatacell->attributes['class'] .= 'exastud-userdata-cell';
        $userdatacell->text = '<div class="cell-content">'.$userdata.'</div>';
        $row->cells[] = $userdatacell;

        // gender
        $row->cells[] = block_exastud_get_user_gender_string($classstudent->id);

        if (block_exastud_is_bw_active()) {
            // Grades column
            if (!empty($subjectData->grade)) {
                $template = block_exastud_get_student_print_template($class, $classstudent->id);
                $value = @$template->get_grade_options()[$subjectData->grade] ?: $subjectData->grade;
                $row->cells[] .= $value;
            } else {
                $row->cells[] = '';
            }
            // Niveau column
            if (!empty($subjectData->niveau)) {
                $row->cells[] = block_exastud\global_config::get_niveau_option_title($subjectData->niveau);
            } else {
                $row->cells[] = '';
            }
            // subject review
            $row->cells[] = '<p>'.
                    ((trim(@$subjectData->review) ? block_exastud_text_to_html(trim($subjectData->review)) : '') ?: '---').
                    '</p>';
        }
        $learnReview = g::$DB->get_field('block_exastudreview', 'review', [
                        'studentid' => $classstudent->id,
                        //'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
                        'subjectid' => $subjectid,
                        'periodid' => $actPeriod->id,
                        'teacherid' => $teacherid]
        );
        if ($learnReview) {
            $row->cells[] = ($visible ? $learnReview : '');
            unset($tabledeletecolumns['learnsocial']);
        } else {
            $row->cells[] = '';
        }

        $row->attributes['class'] = 'oddeven'.(int) $oddeven;
        $table->data[] = $row;
        $oddeven = !$oddeven;
    }

}

// clean empty columns
// get column indexes. They can be different via exastud configuration or via logged in user
$tablecolumns = array();
foreach ($table->head as $k => $headitem) {
    switch ($headitem) {
        case $tableheadernote:
            $tablecolumns['note'] = $k;
            break;
        case $tableheaderniveau:
            $tablecolumns['niveau'] = $k;
            break;
        case $tableheadersubjects:
            $tablecolumns['subjects'] = $k;
            break;
        case $tableheaderlearnsocial:
            $tablecolumns['learnsocial'] = $k;
            break;
        case $tableheadercategories:
            $tablecolumns['categories'] = $k;
            break;
    }
}
// delete needed columns
foreach ($tabledeletecolumns as $todelete) {
    unset($table->head[@$tablecolumns[$todelete]]);
    if (count($table->data)) {
        foreach ($table->data as $row) {
            unset($row->cells[@$tablecolumns[$todelete]]);
        }
    }
}

echo $output->table($table);

if ($hiddenclassstudents) {
	echo $output->heading(block_exastud_trans('de:Ausgeblendete Schüler'));

	$table = new html_table();

	$table->head = array();
	$table->head[] = ''; //userpic
	$table->head[] = block_exastud_get_string('name');
    $hide_allstudents_url = block_exastud\url::create($PAGE->url, ['action' => 'show_student', 'studentid' => 'all']);
    $showAllButton = '<span class="exastud-hidebutton">
                    <a style="padding-right: 15px;" href="'.$hide_allstudents_url.'">'.
            $OUTPUT->pix_icon('i/show', block_exastud_get_string('show')).
            '&nbsp;'.block_exastud_get_string('show_all').
            '</a></span>';
	$table->head[] = $showAllButton; //buttons

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

        if ($isSubjectTeacher) {
            $row->cells[] =
                    '<a style="padding-right: 15px;" href="'.$show_hide_url.'">'.$show_hide_icon.'</a>';

        }
		$table->data[] = $row;
	}

	echo $output->table($table);
}

if ($isSubjectTeacher) {
    echo '<input type="submit" value="'.block_exastud_get_string('savechanges').'" class="btn btn-default exastud-submit-button"/>&nbsp;';
}
//echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid, 'openclass' => $classid]));
echo '<input type="button" value="'.block_exastud_get_string('back').'" class="btn btn-default" exa-type="link" href="'.new moodle_url('review.php', ['courseid' => $courseid, 'openclass' => $classid]).'"/>';

echo '</form>';
echo $output->footer();
