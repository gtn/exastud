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

define("MAX_USERS_PER_PAGE", 5000);

use block_exastud\globals as g;

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$showall		= optional_param('showall', 0, PARAM_BOOL);
$searchtext	 = optional_param('searchtext', '', PARAM_TEXT); // search string
$add			= optional_param('add', 0, PARAM_BOOL);
$remove		 = optional_param('remove', 0, PARAM_BOOL);

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
$curPeriod = block_exastud_get_active_or_next_period();

$class = block_exastud_get_head_teacher_class($classid);

$url = '/blocks/exastud/configuration_classteachers.php';
$PAGE->set_url($url);
$output = block_exastud_get_renderer();
echo $output->header(['configuration_classes', 'teachers'], ['class' => $class]);

$addmessage = '';
$notDeletedUsers = array();

if ($frm = data_submitted()) {
	require_sesskey();

	if ($add and !empty($frm->addselect)) {
		$subjectid = required_param('classteacher_subjectid', PARAM_INT);

		foreach ($frm->addselect as $adduser) {
			if (!$adduser = clean_param($adduser, PARAM_INT)) {
				continue;
			}

			if ($subjectid == BLOCK_EXASTUD_SUBJECT_ID_ADDITIONAL_HEAD_TEACHER && $adduser == $class->userid) {
				// classteacher can't add himself
				continue;
			}

			g::$DB->insert_or_update_record('block_exastudclassteachers',
				[ 'timemodified' => time() ],
				[
					'teacherid' => $adduser,
					'classid' => $class->id,
					'subjectid' => $subjectid,
				]);

            $userData = $DB->get_record('user', ['id' => $adduser, 'deleted' => 0]);
            $subjectData = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
            \block_exastud\event\classteacher_assigned::log(['objectid' => $class->id,
                    'courseid' => $courseid,
                    'relateduserid' => $adduser,
                    'other' => ['subjectid' => $subjectid,
                                'subjecttitle' => (@$subjectData->title ? $subjectData->title : block_exastud_get_string('additional_head_teacher')),
                                'classtitle' => $class->title,
                                'relatedusername' => $userData->firstname.' '.$userData->lastname]]);
		}
	} else if ($remove and !empty($frm->removeselect)) {
		foreach ($frm->removeselect as $record_id) {
            if (!$record_id = clean_param($record_id, PARAM_INT)) {
                continue;
            }
            $existingrecord = $DB->get_record('block_exastudclassteachers', ['id' => $record_id]);

            if (!block_exastud_teacher_has_gradings_for_class($existingrecord->teacherid, $class->id, $existingrecord->subjectid)) {
                $DB->delete_records('block_exastudclassteachers', array('id' => $record_id, 'classid' => $class->id));
                $userData = $DB->get_record('user', ['id' => $existingrecord->teacherid, 'deleted' => 0]);
                $subjectData = $DB->get_record('block_exastudsubjects', ['id' => $existingrecord->subjectid]);
                if ($subjectData) {
                    \block_exastud\event\classteacher_unassigned::log(['objectid' => $class->id,
                            'courseid' => $courseid,
                            'relateduserid' => $existingrecord->teacherid,
                            'other' => ['subjectid' => $existingrecord->subjectid,
                                    'subjecttitle' => $subjectData->title,
                                    'classtitle' => $class->title,
                                    'relatedusername' => $userData->firstname.' '.$userData->lastname]]);
                }
            } else {
                $notDeletedUsers[$existingrecord->teacherid] = $existingrecord->subjectid;
            }

        }
	} else if ($showall) {
		$searchtext = '';
	}
}

$select  = "username <> 'guest' AND deleted = 0 AND confirmed = 1";
	
if ($searchtext !== '') {   // Search for a subset of remaining users
	//$LIKE	  = $DB->sql_ilike();
		$LIKE	  = "LIKE";
	$FULLNAME  = $DB->sql_fullname();

	$selectsql = " AND ($FULLNAME $LIKE '%$searchtext%' OR email $LIKE '%$searchtext%') ";
	$select  .= $selectsql;
} else { 
	$selectsql = ""; 
}

$availableusers = $DB->get_records_sql('SELECT id, firstname, lastname, email, '.get_all_user_name_fields(true).'
									FROM {user}
									WHERE '.$select.'
									AND deleted = 0
									-- disabled, allow teacher to be assign more than once (eg. 2 different subjects)
									-- AND id NOT IN (
									--		 SELECT teacherid
									--		 FROM {block_exastudclassteachers}
									--			   WHERE classid = '.$class->id.'
									--			   '.$selectsql.')
									 ORDER BY lastname ASC, firstname ASC');

$classstudents = block_exastud_get_class_teachers($class->id);

if (count($notDeletedUsers) > 0) {
    $message = block_exastud_get_string('can_not_delete_subject_teacher_because_has_grading');
    $message .= '<ul>';
    foreach ($notDeletedUsers as $teacherid => $subjectid) {
        $message .= '<li>';
        $subjObj = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
        $message .= fullname(block_exastud_get_user($teacherid)).' => '.$subjObj->title;
        $message .= '</li>';
    }
    $message .= '</ul>';
    echo $OUTPUT->notification($message, 'notifyproblem');
}


echo $OUTPUT->box_start();
$userlistType = 'teachers';
require __DIR__.'/lib/configuration_userlist.inc.php';
echo $OUTPUT->box_end();

echo $output->back_button($CFG->wwwroot . '/blocks/exastud/configuration_class.php?courseid='.$courseid.'&classid='.$class->id.'&type=teachers');

echo $output->footer();
