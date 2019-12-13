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
global $DB;

if (!block_exastud_is_subject_teacher()) {
    block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
}

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$action = optional_param('action', '', PARAM_RAW); // Course ID
$returnTo = optional_param('returnTo', '', PARAM_URL); // Course ID

block_exastud_require_login($courseid);

$url = '/blocks/exastud/requests.php';
$PAGE->set_url($url);

if ($action) {
    switch ($action) {
        case 'reviews_unlock_approve':
            block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
            // approve request
            $classid = required_param('classid', PARAM_INT);
            $teacherid = required_param('teacherid', PARAM_INT);
            // set to unlocked
            $unlocked_teachers =
                    (array) json_decode(block_exastud_get_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS), true);
            $unlocked_teachers[$teacherid] = strtotime('+1day');
            block_exastud_set_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS, json_encode($unlocked_teachers));
            // delete from to_approve
            $toapprove_teachers =
                    (array) json_decode(block_exastud_get_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE),
                            true);
            unset($toapprove_teachers[$teacherid]);
            block_exastud_set_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE, json_encode($toapprove_teachers));
            // send a message about "approve"
            $class = block_exastud_get_class($classid);
            if ($teacherid > 0) {
                $userto = $DB->get_record('user', array('id' => $teacherid));
            } else {
                // if for all teachers -> message to class owner
                $userto = $DB->get_record('user', array('id' => $class->userid));
            }
            $subject = block_exastud_get_string('notification_allow_review_old_class_approve_subject', null, array('site' => $SITE->fullname, 'classtitle' => $class->title));
            $message = block_exastud_get_string('notification_allow_review_old_class_approve_body', null, array('site' => $SITE->fullname, 'classtitle' => $class->title, 'datetime' => date('d.m.Y H:i', strtotime('+1day')), 'receiver' => fullname($userto)));
            $context = block_exastud_get_string('notification_allow_review_old_class_approve_context');
            $viewurl = new moodle_url('/blocks/exastud/requests.php');
            block_exastud_send_notification('approve_allow_review', $USER, $userto, $subject, $message, $context, $viewurl);
            break;
        case 'reviews_unlock_prolong':
            block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
            // prolong activated request
            $classid = required_param('classid', PARAM_INT);
            $teacherid = required_param('teacherid', PARAM_INT);
            // increase time to +1 day
            $unlocked_teachers =
                    (array) json_decode(block_exastud_get_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS), true);
            $unlocked_teachers[$teacherid] = strtotime('+1day');
            block_exastud_set_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS, json_encode($unlocked_teachers));
            // send a message about "prolong"
            $class = block_exastud_get_class($classid);
            if ($teacherid > 0) {
                $userto = $DB->get_record('user', array('id' => $teacherid));
            } else {
                // if for all teachers -> message to class owner
                $userto = $DB->get_record('user', array('id' => $class->userid));
            }
            $subject = block_exastud_get_string('notification_allow_review_old_class_prolong_subject', null, array('site' => $SITE->fullname, 'classtitle' => $class->title));
            $message = block_exastud_get_string('notification_allow_review_old_class_prolong_body', null, array('site' => $SITE->fullname, 'classtitle' => $class->title, 'datetime' => date('d.m.Y H:i', strtotime('+1day')), 'receiver' => fullname($userto)));
            $context = block_exastud_get_string('notification_allow_review_old_class_prolong_context');
            $viewurl = new moodle_url('/blocks/exastud/requests.php');
            block_exastud_send_notification('prolong_allow_review', $USER, $userto, $subject, $message, $context, $viewurl);
            break;
        case 'reviews_unlock_delete':
            block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
            // delete request (active and not)
            $classid = required_param('classid', PARAM_INT);
            $teacherid = required_param('teacherid', PARAM_INT);
            // increase time to +1 day
            $unlocked_teachers =
                    (array) json_decode(block_exastud_get_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS), true);
            unset($unlocked_teachers[$teacherid]);
            block_exastud_set_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS, json_encode($unlocked_teachers));
            $toapprove_teachers =
                    (array) json_decode(block_exastud_get_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE),
                            true);
            unset($toapprove_teachers[$teacherid]);
            block_exastud_set_class_data($classid, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE,
                    json_encode($toapprove_teachers));
            break;
        default:
    }
    if ($returnTo) {
        redirect($returnTo);
    } else {
        redirect($PAGE->url);
    }
}

$output = block_exastud_get_renderer();

echo $output->header('requests');
echo $output->heading(block_exastud_get_string('requests'));

$noAnyRequest = true;

// class delete requests
if (block_exastud_is_siteadmin()) {
    $classToDelete = $DB->get_records('block_exastudclass', ['to_delete' => 1]);
} else if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
    $classToDelete = $DB->get_records_select('block_exastudclass', ' to_delete IN (1, -1)  AND userid = ? ', [$USER->id]);
} else {
    $classToDelete = null;
}
if ($classToDelete) {
    echo $output->heading2(block_exastud_get_string('requests_class_delete_list'));
    $table = new html_table();
    $table->head = array(
        block_exastud_get_string('requests_class_title'),
        block_exastud_get_string('requests_class_teacher'),
        ''
    );
    foreach ($classToDelete as $class) {
        $row = new html_table_row();
        $classUrl = new \moodle_url('/blocks/exastud/configuration_class_info.php', ['classid' => $class->id]);
        $classLink = html_writer::link($classUrl, $class->title, ['target' => '_blank']);
        $classteacher = fullname(block_exastud_get_user($class->userid));
        $teacherUrl = new \moodle_url('/user/profile.php', array('id' => $class->userid));
        $teacherLink = html_writer::link($teacherUrl, $classteacher, ['target' => '_blank']);
        $buttons = array();
        // delete button
        if (block_exastud_is_siteadmin()) {
            // delete button
            $deleteUrl = new \moodle_url('/blocks/exastud/configuration_class.php',
                    array('courseid' => $courseid,
                            'action' => 'delete',
                            'classid' => $class->id,
                            'confirm' => 1,
                            'backTo' => 'requests'));
            $buttons[] = $output->link_button($deleteUrl,
                    block_exastud_get_string('requests_class_delete'),
                    ['exa-confirm' => block_exastud_get_string('delete_confirmation', null, $class->title),
                            'exa-type' => 'link',
                            'class' => 'btn btn-danger btn-sm',
                            'title' => block_exastud_get_string('delete')]);
            // refuse button
            $refuseUrl = new \moodle_url('/blocks/exastud/configuration_class.php',
                    array('courseid' => $courseid,
                            'action' => 'delete',
                            'classid' => $class->id,
                            'confirm' => 1,
                            'refuse' => 1,
                            'backTo' => 'requests'));
            $buttons[] = $output->link_button($refuseUrl,
                    block_exastud_get_string('delete_refuse_button'),
                    [       'class' => 'btn btn-danger btn-sm',
                            'title' => block_exastud_get_string('delete_refuse_button')]);
        }
        // refused message
        if ($class->to_delete == -1) {
            //$buttons[] = '<img src="'.$CFG->wwwroot.'/blocks/exastud/pix/attention.png" title="'.block_exastud_get_string('delete_class_refused').'"/>';
            $buttons[] = '<i class="fas fa-exclamation-triangle" title="'.block_exastud_get_string('delete_class_refused').'"></i>';
            $buttons[] = block_exastud_get_string('delete_class_refused');
        }
        // redo deleting button
        if ($class->userid == $USER->id) {
            $redoDeleteUrl = new \moodle_url('/blocks/exastud/configuration_class.php',
                    array('courseid' => $courseid,
                            'action' => 'to_delete',
                            'classid' => $class->id,
                            'confirm' => 1,
                            'unmark' => 1,
                            'backTo' => 'requests'));
            $buttons[] = $output->link_button($redoDeleteUrl,
                    block_exastud_get_string('unmark_to_delete_button'),
                    ['exa-confirm' => block_exastud_get_string('unmark_to_delete_go', null, $class->title),
                            'exa-type' => 'link',
                            'class' => 'btn btn-info btn-sm',
                            'title' => block_exastud_get_string('unmark_to_delete_button')]);
        }
        $row->cells = array(
            $classLink,
            $teacherLink,
            implode('&nbsp;', $buttons)
        );
        $table->data[] = $row;
    }
    echo html_writer::table($table);
    $noAnyRequest = false;
}

// unlock class reviews for old periods
block_exastud_update_allow_review_times(null, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE);
block_exastud_update_allow_review_times(null, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS);
$classesData = array();
$onlySubjectTeacher = true;
if (block_exastud_is_siteadmin()) {
    $sql = 'SELECT DISTINCT d.* 
              FROM {block_exastuddata} d 
              WHERE d.classid > 0 
                AND d.name = ? OR d.name = ?
                AND d.value != ? ';
    $classesData = $DB->get_records_sql($sql,
            [BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS, '']);
    $onlySubjectTeacher = false;
}
if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
    $sql = 'SELECT DISTINCT d.* 
              FROM {block_exastuddata} d 
              JOIN {block_exastudclass} c ON c.id = d.classid  
              WHERE d.classid > 0
                AND c.userid = ? 
                AND d.name = ? OR d.name = ?
                AND d.value != ? ';
    $classesData2 = $DB->get_records_sql($sql,
            [   $USER->id, 
                BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE, 
                BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS, 
                '']);
    $classesData = $classesData + $classesData2;
    $onlySubjectTeacher = false;
}
if (block_exastud_is_subject_teacher()) {
    $sql = 'SELECT DISTINCT d.* 
              FROM {block_exastuddata} d 
              JOIN {block_exastudclassteachers} ct ON ct.classid = d.classid  
              WHERE d.classid > 0
                AND ct.teacherid = ?                 
                AND d.name = ? OR d.name = ?
                AND d.value != ? ';
    $classesData2 = $DB->get_records_sql($sql,
            [   $USER->id,
                BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS_TO_APPROVE,
                BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS,
                '']);
    //echo "<pre>debug:<strong>requests.php:228</strong>\r\n"; print_r($classesData2); echo '</pre>'; exit; // !!!!!!!!!! delete it
    $classesData = $classesData + $classesData2;
}
$classes = array();
foreach ($classesData as $cData) {
    if (!array_key_exists($cData->classid, $classes)) {
        $classes[$cData->classid] = array();
    }
    $times = (array) json_decode($cData->value);
    foreach($times as $teacherId => $time) {
        if ($time >= time()) {
            if ($onlySubjectTeacher) {
                if ($teacherId == $USER->id) {
                    $classes[$cData->classid][$teacherId] = $time;
                }
            } else {
                $classes[$cData->classid][$teacherId] = $time;
            }
        }
    }
}
$classes = array_filter($classes, function($c) {return (count($c) > 0);});
if (count($classes) > 0) {
    echo $output->heading2(block_exastud_get_string('requests_unlock_review_list'));
    $table = new html_table();
    $table->head = array(
            block_exastud_get_string('requests_class_title'),
            block_exastud_get_string('requests_unlock_requested_teacher'),
            block_exastud_get_string('requests_unlock_request_until'),
            '',
            ''
    );
    foreach ($classes as $classid => $classRequests) {
        $class = block_exastud_get_class($classid);
        $period = block_exastud_get_period($class->periodid);
        $dateStart = date('d F Y', $period->starttime);
        $dateStart = preg_replace('/\s+/', '&nbsp;', $dateStart);
        $dateEnd = date('d F Y', $period->endtime);
        $dateEnd = preg_replace('/\s+/', '&nbsp;', $dateEnd);
        $row = new html_table_row();
        $cellClass = new html_table_cell();
        $cellClass->rowspan = count($classRequests);
        $cellClass->text = '<strong>'.$class->title.'</strong>
                <br><small>'.$period->description.' ('.$dateStart.'&nbsp;-&nbsp;'.$dateEnd.')</small>';
        $row->cells[] = $cellClass;
        $i = 0;
        foreach ($classRequests as $teacherId => $requestEndTime) {
            $approved = block_exastud_teacher_is_unlocked_for_old_class_review($classid, $teacherId, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS);
            if ($i !== 0) {
                $row = new html_table_row();
            }
            if ($teacherId > 0) {
                $classteacher = fullname(block_exastud_get_user($teacherId));
                $teacherUrl = new \moodle_url('/user/profile.php', array('id' => $teacherId));
                $teacherLink = html_writer::link($teacherUrl, $classteacher, ['target' => '_blank']);
            } else {
                // for all
                $teacherLink = block_exastud_get_string('allow_review_admin_approved_for_all');
            }
            $row->cells[] = $teacherLink;
            $row->cells[] = userdate($requestEndTime);
            // if it is admin or class teacher
            if (block_exastud_is_siteadmin() || $class->userid == $USER->id) {
                // link to approve/prolong
                if ($approved) {
                    $button1Link = new \moodle_url($PAGE->url,
                            ['action' => 'reviews_unlock_prolong', 'classid' => $classid, 'teacherid' => $teacherId]);
                    $button1 = $output->link_button($button1Link, block_exastud_get_string('requests_unlock_prolong_button'),
                            ['class' => 'btn btn-info btn-sm']);
                } else {
                    $button1Link = new \moodle_url($PAGE->url,
                            ['action' => 'reviews_unlock_approve', 'classid' => $classid, 'teacherid' => $teacherId]);
                    $button1 = $output->link_button($button1Link, block_exastud_get_string('requests_unlock_approve_button'),
                            ['class' => 'btn btn-success btn-sm']);
                }
                $row->cells[] = $button1;
                $button1Link = new \moodle_url($PAGE->url,
                        ['action' => 'reviews_unlock_delete', 'classid' => $classid, 'teacherid' => $teacherId]);
                $button2 = $output->link_button($button1Link, block_exastud_get_string('requests_unlock_delete_button'),
                        ['class' => 'btn btn-danger btn-sm']);
                $row->cells[] = $button2;
            } else if (block_exastud_is_subject_teacher()) {
                if ($approved) {
                    $button1 = '<img class="" src="'.$CFG->wwwroot.'/blocks/exastud/pix/valid.png" />&nbsp;'.block_exastud_get_string('allow_review_admin_approved');
                } else {
                    $button1 = block_exastud_get_string('allow_review_admin_notapproved_yet');
                }
                $row->cells[] = $button1;
            }
            $i++;
            $table->data[] = $row;
        }
    }
    echo html_writer::table($table);
    $noAnyRequest = false;
}

if ($noAnyRequest) {
    echo $output->notification(block_exastud_get_string('requests_no_any'), 'info');
}

echo $output->footer();

