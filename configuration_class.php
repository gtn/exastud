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
$startPeriod = optional_param('startPeriod', 0, PARAM_INT);

if (!in_array($type, ['categories', 'teachers', 'teachers_options', 'studentgradereports'])) {
	$type = 'students';
}

$notification = '';

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);

$classid = required_param('classid', PARAM_INT);
setcookie('lastclass', $classid);
$class = block_exastud_get_head_teacher_class($classid);

$period = block_exastud_get_period($class->periodid);
// agelaufene periode => unlock anbieten
$showUnlock = $period->endtime < time();

$url = '/blocks/exastud/configuration_class.php?courseid='.$courseid.'&classid='.$classid.($action ? '&action='.$action : '').($type ? '&type='.$type : '');
$PAGE->set_url($url);

block_exastud_init_js_css(['select2']);

if ($action == 'delete') {
	if (!optional_param('confirm', false, PARAM_BOOL)) {
		throw new moodle_exception('not confirmed');
	}
	if (optional_param('refuse', false, PARAM_BOOL)) {
	    // mark as 'refused' to deleting
        $DB->execute('UPDATE {block_exastudclass} 
                        SET to_delete = ? 
                      WHERE id = ? ',
                    [-1, $classid]);
    } else {
	    // real deleting
        $classData = block_exastud_get_class($class->id);
        $DB->delete_records('block_exastudclass', ['id' => $class->id]);
        // delete related data
        // students
        $DB->delete_records('block_exastudclassstudents', ['classid' => $class->id]);
        // teachers
        $DB->delete_records('block_exastudclassteachers', ['classid' => $class->id]);
        // data
        $DB->delete_records('block_exastuddata', ['classid' => $class->id]);
        // classcate
        $DB->delete_records('block_exastudclasscate', ['classid' => $class->id]);

        // TODO: block_exastudclassteastudvis

        \block_exastud\event\class_deleted::log(['objectid' => $class->id, 'other' => ['classtitle' => $classData->title]]);
    }

    // redirects
    $backTo = optional_param('backTo', '', PARAM_RAW);
    switch ($backTo) {
        case 'requests':
            redirect(new moodle_url('/blocks/exastud/requests.php'));
            break;
        default:
            redirect(new moodle_url('/blocks/exastud/configuration_classes.php?courseid='.$courseid));
    }
}

$output = block_exastud_get_renderer();

if ($showUnlock && $action == 'unlock') {
    require_sesskey();
    $teacherid = required_param('teacherid', PARAM_INT);

    $notification = $output->notification(block_exastud_get_string('requests_unlock_request_created'));

    $clTeachers = block_exastud_get_class_teachers($class->id);
    if ($USER->id == $class->userid) {
        // class owner can manage unlocking of own class
        $unlocked_teachers = (array) json_decode(block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS), true);
        $unlocked_teachers[$teacherid] = strtotime('+1day');
        block_exastud_set_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS, json_encode($unlocked_teachers));
    } else {
        // request to admin
         $toapprove_teachers = (array) json_decode(block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE), true);
         $toapprove_teachers[$teacherid] = strtotime('+2 weeks');
         block_exastud_set_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE, json_encode($toapprove_teachers));
    }
}

// redirect to edit class form if it is not a class owner, but is site admin
if (block_exastud_is_siteadmin() && $class->userid != $USER->id && !in_array($type, ['categories'])) {
    $redirecturl = new moodle_url('/blocks/exastud/configuration_class_info.php',
            ['courseid' => $courseid, 'classid' => $class->id]);
    redirect($redirecturl);
}

// the teacher can not delete this class, because the class has related data
// so the teacher can mark this class to deleting by admin
if ($action == 'to_delete') {
    $classData = block_exastud_get_class($class->id);
	$confirm = optional_param('confirm', false, PARAM_BOOL);
	if ($confirm) {
	    $unmark = optional_param('unmark', false, PARAM_BOOL);
	    if ($unmark) {
            $classData->to_delete = 0;
        } else {
            $classData->to_delete = 1;
        }
	    $DB->update_record('block_exastudclass', $classData);
        redirect(new moodle_url('/blocks/exastud/configuration_classes.php?courseid='.$courseid.'&startPeriod='.$startPeriod));
    } else {
	    // confirmation message
        echo $output->header(['configuration_classes']);
        echo $OUTPUT->notification(block_exastud_get_string('force_class_to_delete'), 'notifymessage');
        if ($classData->to_delete) {
            //echo $OUTPUT->notification(block_exastud_get_string('already_marked'), 'warning');
            echo html_writer::link($CFG->wwwroot.'/blocks/exastud/configuration_class.php?courseid='.$courseid.
                    '&action=to_delete&classid='.$classData->id.'&confirm=1&unmark=1startPeriod='.$startPeriod,
                    block_exastud_get_string('unmark_to_delete_go'),
                    ['class' => 'btn btn-info', 'title' => block_exastud_get_string('unmark_to_delete_go')]);
        } else {
            echo html_writer::link($CFG->wwwroot.'/blocks/exastud/configuration_class.php?courseid='.$courseid.
                    '&action=to_delete&classid='.$classData->id.'&confirm=1&startPeriod='.$startPeriod,
                    block_exastud_get_string('mark_to_delete_go'),
                    ['class' => 'btn btn-danger', 'title' => block_exastud_get_string('mark_to_delete_go')]);
        }
        echo '&nbsp;&nbsp;&nbsp;';
        echo html_writer::link($CFG->wwwroot.'/blocks/exastud/configuration_classes.php?courseid='.$courseid.'&startPeriod='.$startPeriod,
                block_exastud_get_string('back'),
                ['class' => 'btn btn-default', 'title' => block_exastud_get_string('back')]);

        echo $output->footer();
        exit;
    }
}

if ($action == 'delsubjectteacher') {
    $subjectid = required_param('subjectid', PARAM_INT);
    $teacherid = required_param('teacherid', PARAM_INT);
    if (!block_exastud_teacher_has_gradings_for_class($teacherid, $class->id, $subjectid)) {
        $DB->delete_records('block_exastudclassteachers', array('teacherid' => $teacherid, 'classid' => $class->id, 'subjectid' => $subjectid));
    }
}

if ($action == 'changesubjectteacher') {
    require_once($CFG->dirroot . '/blocks/exastud/lib/edit_form.php');
    // confirmation message

    $subjectid = required_param('subjectid', PARAM_INT);
    $subject = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
    $currentTeacher = block_exastud_get_class_teacher_by_subject($classid, $subjectid);
    //$currentTeacher = block_exastud_get_user($currentTeacherId);
    $subject = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
    $teacherform = new change_subject_teacher_form('configuration_class.php?courseid='.$courseid.'&classid='.$class->id.'&action=changesubjectteacher&type=teachers&subjectid='.$subjectid,
            ['courseid' => $courseid,
            'currentteacher' => $currentTeacher,
            'subject' => $subject,
            'classid' => $class->id
    ]);
    $redirectparams = array(
            'courseid' => $courseid,
            'classid' => $classid,
            'action' => 'edit',
            'type' => 'teachers'
    );
    if ($teacherform->is_cancelled()) {
        redirect(new moodle_url('/blocks/exastud/configuration_class.php', $redirectparams));
    } else if ($newteacher = $teacherform->get_data()) {
        $oldTeacherid = $currentTeacher->id;
        $message = '';
        if ($newteacher->newsubjectteacher > 0) {
            // subject realation
            $DB->execute('UPDATE {block_exastudclassteachers}
                            SET teacherid = ? 
                            WHERE teacherid = ? 
                              AND classid = ?
                              AND subjectid = ?
                          ', [$newteacher->newsubjectteacher, $oldTeacherid, $classid, $subjectid]);
            // subject reviews
            $DB->execute('UPDATE {block_exastudreview} 
                            SET teacherid = ? 
                            WHERE teacherid = ? 
                              AND periodid = ?                            
                              AND subjectid = ?
                          ', [$newteacher->newsubjectteacher, $oldTeacherid, $class->periodid, $subjectid]);
            // change project teacher
            $projectteahcerscount = $DB->count_records_select('block_exastuddata', $DB->sql_compare_text('value').' = :value 
                                                                    AND classid = :classid
                                                                    AND subjectid = 0
                                                                    AND name = :name ',
                                                    array(  'value' => $oldTeacherid,
                                                            'classid' => $classid,
                                                            'name' => 'project_teacher')); // Count is needed for showing/not message

            if ($projectteahcerscount > 0) {
                $DB->execute('UPDATE {block_exastuddata} 
                            SET value = ? 
                            WHERE value = ? 
                              AND classid = ?
                              AND subjectid = 0
                              AND name = \'project_teacher\'
                          ', [$newteacher->newsubjectteacher, $oldTeacherid, $classid]);
                $a = (object)array(
                        'newteachername' => fullname(block_exastud_get_user($newteacher->newsubjectteacher)),
                        'oldteachername' => fullname(block_exastud_get_user($oldTeacherid)),
                );
                $message .= block_exastud_get_string('project_teacher_also_was_changed', null, $a);
            }
            // change head teachers
            if (@$newteacher->no_head_class_teacher) {
                //$classOwnerId = block_exastud_get_class($classid)->userid;
                $classOwnerId = 0; // zero - becaus it is default for any students
                // change to class teacher (owner)
                $DB->execute('UPDATE {block_exastuddata} 
                            SET value = ? 
                            WHERE value = ? 
                              AND classid = ?
                              AND subjectid = 0
                              AND name = \'head_teacher\'
                          ', [$classOwnerId, $oldTeacherid, $classid]);
            }
            $class = block_exastud_get_class($classid);
            $subjectData = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
            \block_exastud\event\classteacher_changed::log(['objectid' => $classid,
                    'courseid' => $courseid,
                    'relateduserid' => $oldTeacherid,
                    'other' => ['subjectid' => $subjectid,
                            'subjecttitle' => (@$subjectData->title ? $subjectData->title : block_exastud_get_string('additional_head_teacher')),
                            'classtitle' => $class->title,
                            'newrelateduserid' => $newteacher->newsubjectteacher,
                            'newrelatedusername' => fullname(block_exastud_get_user($newteacher->newsubjectteacher)),
                            'oldrelatedusername' => fullname(block_exastud_get_user($oldTeacherid))]]);
            redirect(new moodle_url('/blocks/exastud/configuration_class.php', $redirectparams), $message);
        }
    }
    echo $output->header(['configuration_classes', $type], ['class' => $class]);
    $a = (object)[
            'subjecttitle' => $subject->title,
            'currentteacher_name' => fullname($currentTeacher),
    ];
    echo html_writer::tag('h1', block_exastud_get_string('form_subject_teacher_form_header'));
    echo html_writer::div(block_exastud_get_string('form_subject_teacher_form_description', '', $a));
    $teacherform->display();
    echo html_writer::tag('small', block_exastud_get_string('form_subject_teacher_form_select_new_teacher_docu'));
    echo $output->footer();
    exit;
}

echo $output->header(['configuration_classes', $type], ['class' => $class]);
switch ($type) {
    /* Print the Students */
    case 'students':
        $showaddtoclassform = false;
        $classstudents = block_exastud_get_class_students($class->id);
        if (!$classstudents) {
            $showaddtoclassform = true;
            require_once($CFG->dirroot . '/blocks/exastud/lib/edit_form.php');
            $addFromClassForm = new add_students_via_class_parameter_form(
                'configuration_class.php?courseid=' . $courseid . '&classid=' . $class->id . '&type=students'
            );
            if ($addFromClassForm->is_cancelled()) {
                redirect(new moodle_url('/blocks/exastud/configuration_class.php?courseid=' . $courseid . '&classid=' . $class->id . '&type=students'));
            } else if ($classToAdd = $addFromClassForm->get_data()) {
                $fieldId = $DB->get_field('user_info_field', 'id', ['shortname' => 'class'], IGNORE_MULTIPLE);
                if (!$fieldId) {
                    throw new moodle_exception('no Klasse/Lerngruppe field for user!');
                }
                $searchValue = trim(@$classToAdd->class_toadd);
                if ($searchValue) {
                    $users = $DB->get_fieldset_select('user_info_data', 'userid', 'fieldid = ? AND data = ?', [$fieldId, $searchValue]);
                    if ($users && count($users)) {
                        $existingusers = block_exastud_get_class_students($class->id);
                        foreach ($users as $user) {
                            if (!array_key_exists($user, $existingusers)) {
                                $newuser = new stdClass();
                                $newuser->studentid = $user;
                                $newuser->classid = $class->id;
                                $newuser->timemodified = time();
                                $DB->insert_record('block_exastudclassstudents', $newuser);
                            }
                        }
                    }
                } else {
                    echo $output->notification(block_exastud_get_string('please_enter_group_name'), 'warning');
                }
            }
        }

        $classstudents = block_exastud_get_class_students($class->id);

        $buttons_left = '';
        $buttons_left .= $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classmembers.php?courseid='.$courseid.
                '&classid='.$class->id,
                block_exastud_get_string('editclassmemberlist'), ['class' => 'btn btn-default']);
        $buttons_left .= $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classmembers_courses.php?courseid='.
                $courseid.'&classid='.$class->id,
                block_exastud_get_string('add_class_from_course'), ['class' => 'btn btn-default']);

        if (!$classstudents) {
            echo $OUTPUT->notification(block_exastud_get_string('no_entries_found'), 'notifymessage');

            echo $buttons_left;
        } else {
            $table = new html_table();

            $table->size = ['1%', '15%', '15%'];

            $table->head = [
                    '#',
                    block_exastud_get_string('lastname'),
                    block_exastud_get_string('firstname'),
                    block_exastud_get_string('gender'),
                    block_exastud_get_string('report_settings_setting_dateofbirth'),
                    block_exastud_get_string('report_settings_setting_placeofbirth'),
            ];

            $i = 0;
            foreach ($classstudents as $classstudent) {
                $i++;

                $gender = block_exastud_get_user_gender_string($classstudent->id);

                $row = [
                        $i,
                        $classstudent->lastname,
                        $classstudent->firstname,
                        $gender,
                        block_exastud_get_date_of_birth($classstudent->id),
                        block_exastud_get_custom_profile_field_value($classstudent->id, 'placeofbirth'),
                ];

                $table->data[] = $row;
            }

            echo $output->table($table);

            echo $buttons_left;

        }
        // add students via profile field 'class'
        if ($showaddtoclassform) {
            echo $addFromClassForm->display();
        }
        break;

    case 'studentgradereports':
        $classstudents = block_exastud_get_class_students($class->id);
        $additional_head_teachers = block_exastud_get_class_additional_head_teachers($classid);

        if ($action == 'save') {
            require_sesskey();

            $userdatas = \block_exastud\param::optional_array('userdatas', [PARAM_INT => (object) [
                    'print_template' => PARAM_RAW,
                    'print_grades' => PARAM_BOOL,
                    'print_grades_anlage_leb' => PARAM_BOOL,
                    'bildungsstandard_erreicht' => PARAM_TEXT,
                    'dropped_out' => PARAM_BOOL,
            ]]);

            foreach ($classstudents as $student) {
                if (!isset($userdatas[$student->id])) {
                    continue;
                }

                $current = block_exastud_get_class_student_data($class->id, $student->id);
                $new = $userdatas[$student->id];

                block_exastud_set_class_student_data($class->id, $student->id, 'print_template', $new->print_template);
                block_exastud_set_class_student_data($class->id, $student->id, 'print_grades', $new->print_grades);
                block_exastud_set_class_student_data($class->id, $student->id, 'print_grades_anlage_leb',
                        $new->print_grades_anlage_leb);

                if (@$current->bildungsstandard_erreicht != @$new->bildungsstandard_erreicht) {
                    // set it, if changed
                    if (@$new->bildungsstandard_erreicht) {
                        block_exastud_set_class_student_data($class->id, $student->id, 'bildungsstandard_erreicht',
                                $new->bildungsstandard_erreicht);
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

            block_exastud_normalize_projekt_pruefung($class);
        }

        if (!$classstudents) {
            echo $OUTPUT->notification(block_exastud_get_string('no_entries_found'), 'notifymessage');
        } else {
            $table = new html_table();

            $table->size = ['1%', '15%', '15%'];
            $selectall =
                    '<br><label style="font-weight: normal;"><input type="checkbox" class="exastud-select-column-checkboxes">&nbsp;'.
                    block_exastud_get_string('select_all').'</label>';

            $table->head = [
                    '#',
                    block_exastud_get_string('lastname'),
                    block_exastud_get_string('firstname'),
                    block_exastud_get_string('report_column_template'),
            ];
            if (block_exastud_is_bw_active()) {
                $table->head = array_merge($table->head, [
                    block_exastud_trans('de:LEB: Note ausweisen').$selectall,
                    block_exastud_trans('de:LEB-Anlage: Note ausweisen').$selectall,
                    block_exastud_trans('de:Bildungsstandard erreicht')
                ]);
            }
            $table->head = array_merge($table->head, [
                block_exastud_get_string('report_column_enddate').$selectall,
                    ]);

            $available_templates = \block_exastud\print_templates::get_class_available_print_templates($class);
            $default_templateid = block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID);
            $available_templates_tmp = $available_templates;
            $available_templates = [];
            foreach ($available_templates_tmp as $templateid => $title) {
                if ($templateid == $default_templateid) {
                    $available_templates[$templateid] = $title.' (Standard)';
                } else {
                    $available_templates[$templateid] = $title;
                }
            }

            $available_templates = block_exastud_clean_templatelist_for_classconfiguration($available_templates, 'student');

            $i = 0;
            foreach ($classstudents as $classstudent) {
                $i++;
                $userdata = block_exastud_get_class_student_data($class->id, $classstudent->id);

                if (block_exastud_is_bw_active()) {
                    $print_grades = '<input name="userdatas['.$classstudent->id.'][print_grades]" type="hidden" value="0"/>'.
                            html_writer::checkbox('userdatas['.$classstudent->id.'][print_grades]', 1, @$userdata->print_grades);

                    $print_grades_anlage_leb =
                            '<input name="userdatas['.$classstudent->id.'][print_grades_anlage_leb]" type="hidden" value="0"/>'.
                            html_writer::checkbox('userdatas['.$classstudent->id.'][print_grades_anlage_leb]', 1,
                                    @$userdata->print_grades_anlage_leb);
                    $bildungsstandard = html_writer::select(block_exastud_get_bildungsstandards(),
                            'userdatas['.$classstudent->id.'][bildungsstandard_erreicht]', @$userdata->bildungsstandard_erreicht,
                            ['' => '']);
                    $bildungsstandard = $bildungsstandard.
                            (!empty($userdata->bildungsstandard_erreicht) ? ' '.userdate($userdata->bildungsstandard_erreicht_time,
                                            block_exastud_get_string('strftimedate', 'langconfig')) : '');
                }

                $ausgeschieden = '<input name="userdatas['.$classstudent->id.'][dropped_out]" type="hidden" value="0"/>'.
                        '<input name="userdatas['.$classstudent->id.'][dropped_out]" type="checkbox" value="1"'.
                        (!empty($userdata->dropped_out) ? ' checked="checked"' : '').'/>'.
                        (!empty($userdata->dropped_out) ?
                                userdate($userdata->dropped_out_time, block_exastud_get_string('strftimedate', 'langconfig')) : '');

                $templateid = block_exastud_get_student_print_templateid($class, $classstudent->id);
                //if ($templateid == $default_templateid) {
                //	$templateid = '';
                //}

                $row = [
                        $i,
                        $classstudent->lastname,
                        $classstudent->firstname,
                        html_writer::select($available_templates, 'userdatas['.$classstudent->id.'][print_template]', $templateid,
                                false),
                    ];
                if (block_exastud_is_bw_active()) {
                    $row = array_merge($row, [
                            $print_grades,
                            $print_grades_anlage_leb,
                            $bildungsstandard,
                    ]);
                }
                $row = array_merge($row, [
                        $ausgeschieden,
                ]);

                $table->data[] = $row;
            }

            echo '<form method="post">';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<input type="hidden" name="action" value="save" />';

            echo $output->table($table);

            echo '<table style="width: 100%;"><tr><td>';
            echo '</td><td style="text-align: right;">';
            echo '<input type="submit" value="'.block_exastud_get_string('savechanges').'" class="btn btn-default"/>';
            echo '</td></tr></table>';

            echo '</form>';
        }
        break;

    /* Print the Classes */
    case 'teachers':
        $classteachers = block_exastud_get_class_subject_teachers($class->id);
        $additional_head_teachers = block_exastud_get_class_additional_head_teachers($class->id);

        $table = new html_table();

        $table->head = array(
                block_exastud_get_string('Subjects'),
                block_exastud_get_string('lastname'),
                block_exastud_get_string('firstname'),
        );
        $table->align = array("left", "left", "left");
        $table->size = ['33%', '33%', '33%'];

        // need to clone the table, else the output won't work twice
        $table_clone = clone($table);

        if ($additional_head_teachers) {
            foreach ($additional_head_teachers as $classteacher) {
                $table->data[] = [
                        $classteacher->subject_title ?: block_exastud_get_string('not_assigned'),
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
            $params = [
                    'courseid' => $courseid,
                    'classid' => $class->id,
                    'action' => 'delsubjectteacher',
                    'type' => 'teachers',
                    'subjectid' => $classteacher->subjectid,
            ];
            if (block_exastud_teacher_has_gradings_for_class($classteacher->userid, $class->id, $classteacher->subjectid)) {
                $deleteTeacherButton = html_writer::span(
                        html_writer::link('#',
                                html_writer::tag("img", '', array('src' => 'pix/del.png')),
                                array(  'style' => 'opacity: 0.25;',
                                        'title' => block_exastud_get_string('can_not_delete_subject_teacher_because_has_grading'))),
                        'exastud-buttons');
            } else {
                $deleteTeacherButton = html_writer::span(
                        html_writer::link(new moodle_url('/blocks/exastud/configuration_class.php', $params + ['teacherid' => $classteacher->userid]),
                                html_writer::tag("img", '', array('src' => 'pix/del.png')),
                                array(  'exa-confirm' => block_exastud_get_string('delete_subjectteacher_confirmation'),
                                        'exa-type' => 'link',
                                        'title' => block_exastud_get_string('subjectteacher_delete_button'))),
                        'exastud-buttons');
            }
            $params['action'] = 'changesubjectteacher';
            $changeTeacherButton = html_writer::span(
                    html_writer::link(new moodle_url('/blocks/exastud/configuration_class.php', $params),
                            html_writer::tag("img", '', array('src' => 'pix/refresh.png')),
                            array('title' => block_exastud_get_string('subjectteacher_change_button'))),
                    'exastud-buttons');
            $subjectCell = new html_table_cell();
            $subjectCell->text = $classteacher->subject_title ?: block_exastud_get_string('not_assigned');
            $subjectCell->attributes['data-subjectid'] = $classteacher->subjectid;
            $table->data[] = [
                    $subjectCell,
                    $classteacher->lastname,
                    $classteacher->firstname.$changeTeacherButton.$deleteTeacherButton,
            ];
        }

        if (!$classteachers) {
            echo $OUTPUT->notification(block_exastud_get_string('no_entries_found'), 'notifymessage');
        } else {
            echo $output->table($table);
        }

        echo $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classteachers.php?courseid='.$courseid.'&classid='.
                $class->id,
                block_exastud_get_string('editclassteacherlist'), ['class' => 'btn btn-default']);
        break;

    case 'teachers_options':
        $classstudents = block_exastud_get_class_students($class->id);
        $classteachers = block_exastud_get_class_subject_teachers($class->id);
        $additional_head_teachers = block_exastud_get_class_additional_head_teachers($class->id);

        if ($action == 'save') {
            require_sesskey();

            $userdatas = \block_exastud\param::optional_array('userdatas', [PARAM_INT => (object) [
                    'head_teacher' => PARAM_INT,
                    'project_teacher' => PARAM_INT,
                    'bilingual_teacher' => PARAM_INT,
                    'bilingual_templateid' => PARAM_INT,
            ]]);

            foreach ($classstudents as $student) {
                if (!isset($userdatas[$student->id])) {
                    continue;
                }

                $new = $userdatas[$student->id];

                block_exastud_set_class_student_data($class->id, $student->id, 'head_teacher', $new->head_teacher);
                block_exastud_set_class_student_data($class->id, $student->id, 'project_teacher', $new->project_teacher);
                //block_exastud_set_class_student_data($class->id, $student->id, 'bilingual_teacher', $new->bilingual_teacher);
                //block_exastud_set_class_student_data($class->id, $student->id, 'bilingual_templateid', $new->bilingual_templateid);
            }
        }

        $table = new html_table();

        $table->size = ['1%', '15%', '15%'];

        $table->head = [
                '#',
                block_exastud_get_string('lastname'),
                block_exastud_get_string('firstname'),
        ];

        if ($additional_head_teachers) {
            $table->head[] = block_exastud_get_string('head_teacher');
            $additional_head_teachers_select = array_map(function($teacher) {
                return fullname($teacher);
            }, $additional_head_teachers);
        }
        $table->head = array_merge($table->head, [
                block_exastud_get_string('teacher_for_project'),
        ]);

        // bilingual properties
        //$table->head[] = block_exastud_get_string('teacher_for_bilingual');
        //$table->head[] = block_exastud_get_string('report_for_bilingual');

        // bilingual teachers
        $bilingual_teachers = [$class->userid => fullname($DB->get_record('user', ['id' => $class->userid, 'deleted' => 0]))];
        foreach (block_exastud_get_class_teachers($classid) as $teacher) {
            if ($teacher->id !== $class->userid) {
                $bilingual_teachers[$teacher->id] = fullname($teacher);
            }
        }
        natsort($bilingual_teachers);

        $bilingual_templates = block_exastud_get_bilingual_reports(true);

        // project teachers
        $project_teachers = [$class->userid => fullname($DB->get_record('user', ['id' => $class->userid, 'deleted' => 0]))];
//        $allUsers = $DB->get_records('user', ['deleted' => 0]);
        $allTeachers = block_exastud_get_all_teachers();
        foreach ($allTeachers as $tUser) {
            if ($tUser->id !== $class->userid) {
                $project_teachers[$tUser->id] = fullname($tUser);
            }
        }
        natsort($project_teachers);

        $i = 0;
        foreach ($classstudents as $classstudent) {
            $i++;
            $userdata = block_exastud_get_class_student_data($class->id, $classstudent->id);
            // main student data
            $row = [
                    $i,
                    $classstudent->lastname,
                    $classstudent->firstname,
            ];
            // head teacher
            if ($additional_head_teachers) {
                $row[] = html_writer::select($additional_head_teachers_select, 'userdatas['.$classstudent->id.'][head_teacher]',
                        @$userdata->head_teacher, fullname($USER));
            }
            // project teacher
            if (block_exastud_student_has_projekt_pruefung($class, $classstudent->id)) {
                $row[] = html_writer::select($project_teachers, 'userdatas['.$classstudent->id.'][project_teacher]',
                        @$userdata->{BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER}, block_exastud_get_string('not_selected'), ['class' => 'projectteacherslist']);
            } else {
                $template = block_exastud_get_student_print_template($class, $classstudent->id);
                $row[] = block_exastud_get_string('no_project_examination_for_project', null, $template->get_name());
            }

            // bilinguales
/*            $row[] = html_writer::select($bilingual_teachers, 'userdatas['.$classstudent->id.'][bilingual_teacher]',
                    @$userdata->{BLOCK_EXASTUD_DATA_ID_BILINGUAL_TEACHER}, block_exastud_trans('de:keine'));
            $row[] = html_writer::select($bilingual_templates, 'userdatas['.$classstudent->id.'][bilingual_templateid]',
                    @$userdata->{BLOCK_EXASTUD_DATA_ID_BILINGUAL_TEMPLATE}, block_exastud_trans('de:keine'));*/

            $table->data[] = $row;
        }

        echo '<form method="post">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<input type="hidden" name="action" value="save" />';

        echo $output->table($table);

        echo '<table style="width: 100%;"><tr><td style="text-align: right;">';
        echo '<input type="submit" value="'.block_exastud_get_string('savechanges').'" class="btn btn-default"/>';
        echo '</td></tr></table>';

        echo '</form>';

        if ($showUnlock) {
            echo $output->heading2(block_exastud_get_string('allow_reviewing'));

            echo $notification;

            $toapprove_teachers = (array) json_decode(block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE), true);
            $unlocked_teachers = (array) json_decode(block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS), true);
            $all_teachers_list = $toapprove_teachers + $unlocked_teachers;

            $teachers = [0 => block_exastud_get_string('allow_review_admin_approved_for_all')];
            foreach (array_merge($additional_head_teachers, $classteachers) as $classteacher) {
                $teachers[$classteacher->id] = fullname($classteacher);
            }
            if (count($teachers)) {
                $teachersTable = new html_table();
                $teachersTable->head = array(
                        block_exastud_get_string('teacher'),
                        block_exastud_get_string('allow_review_until'),
                        block_exastud_get_string('allow_review_admin_approved'),
                        ''
                );
                foreach ($teachers as $teacherid => $teacherName) {
                    if (isset($all_teachers_list[$teacherid]) && $all_teachers_list[$teacherid] > time()) {
                        $row = new html_table_row();
                        $row->cells = array(
                            $teacherName,
                            userdate($all_teachers_list[$teacherid]),
                            array_key_exists($teacherid, $unlocked_teachers) ? '<img class="" src="'.$CFG->wwwroot.'/blocks/exastud/pix/valid.png" title="'.block_exastud_get_string('allow_review_make_request_already').'" />' : ''
                        );
                        $cellButtons = new html_table_cell();
                        $cellButtonsContent = array();
                        // button to approve
                        if (array_key_exists($teacherid, $toapprove_teachers)) {
                            $buttonLink = new \moodle_url('requests.php', ['action' => 'reviews_unlock_approve', 'classid' => $class->id, 'teacherid' => $teacherid, 'returnTo' => $PAGE->url]);
                            $cellButtonsContent[] = $output->link_button($buttonLink, block_exastud_get_string('requests_unlock_approve_button'), ['class' => 'btn btn-success btn-sm']);
                        }
                        // button to prolong
                        if (array_key_exists($teacherid, $unlocked_teachers)) {
                            $buttonLink = new \moodle_url('requests.php', ['action' => 'reviews_unlock_prolong', 'classid' => $class->id, 'teacherid' => $teacherid, 'returnTo' => $PAGE->url]);
                            $cellButtonsContent[] = $output->link_button($buttonLink, block_exastud_get_string('requests_unlock_prolong_button'), ['class' => 'btn btn-info btn-sm']);
                        }
                        // button to delete request
                        if (array_key_exists($teacherid, $unlocked_teachers)) {
                            $buttonLink = new \moodle_url('requests.php', ['action' => 'reviews_unlock_delete', 'classid' => $class->id, 'teacherid' => $teacherid, 'returnTo' => $PAGE->url]);
                            $cellButtonsContent[] = $output->link_button($buttonLink, block_exastud_get_string('requests_unlock_delete_button'), ['class' => 'btn btn-danger btn-sm']);
                        }
                        $cellButtons->text = implode('&nbsp;', $cellButtonsContent);
                        $row->cells[] = $cellButtons;
                        $teachersTable->data[] = $row;
                    }
                }
                echo html_writer::table($teachersTable);
            }

            // buttons to unlock requests
            echo '<form method="post">';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<input type="hidden" name="action" value="unlock" />';
            echo '<input type="hidden" name="type" value="teachers_options" />';
            echo '<input type="hidden" name="classid" value="'.$class->id.'" />';
            echo '<input type="hidden" name="courseid" value="'.$courseid.'" />';
            echo '<div>';
            echo html_writer::select($teachers, 'teacherid', '', false);
            if ($USER->id == $class->userid) {
                echo '&nbsp;<input type="submit" name="submit" value="'.block_exastud_get_string('allow_review').'" class="btn btn-default">';
            } else {
                echo '&nbsp;<input type="submit" name="submit" value="'.block_exastud_get_string('allow_review_make_request').'" class="btn btn-default">';
            }
            echo '</div>';
            echo '</form>';
        }
        break;

    /* Print the categories */
    case 'categories':
        if (block_exastud_get_plugin_config('can_edit_bps_and_subjects')) {
            // echo html_writer::tag("h2", block_exastud_get_string('categories'));

            //echo $output->notification('This function is disabled!', 'notifyerror');

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
        break;
}

echo $output->footer();
