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

$url = '/blocks/exastud/configuration_classmembers.php';
$PAGE->set_url($url);
$output = block_exastud_get_renderer();
echo $output->header(['configuration_classes', 'students'], ['class' => $class]);

if ($frm = data_submitted()) {
	require_sesskey();

	if ($add and !empty($frm->addselect)) {
		foreach ($frm->addselect as $adduser) {
			if (!$adduser = clean_param($adduser, PARAM_INT)) {
				continue;
			}
			
			$newuser = new stdClass();
			$newuser->studentid = $adduser;
			$newuser->classid = $class->id;
			$newuser->timemodified = time();
			
			$DB->insert_record('block_exastudclassstudents', $newuser);
            $userData = $DB->get_record('user', ['id' => $newuser->studentid, 'deleted' => 0]);
            \block_exastud\event\classmember_assigned::log(['objectid' => $newuser->classid,
                                                            'courseid' => $courseid,
                                                            'relateduserid' => $newuser->studentid,
                                                            'other' => ['classtitle' => $class->title,
                                                                    'relatedusername' => $userData->firstname.' '.$userData->lastname]
                                                            ]);
		}
	} else if ($remove and !empty($frm->removeselect)) {
		foreach ($frm->removeselect as $record_id) {
			if (!$record_id = clean_param($record_id, PARAM_INT)) {
				continue;
			}
			// need for getting student id
            $relation = $DB->get_record('block_exastudclassstudents', ['id' => $record_id]);
			$unassigneduserid = $relation->studentid;
			
			$DB->delete_records('block_exastudclassstudents', array('id'=>$record_id, 'classid'=>$class->id));

            $userData = $DB->get_record('user', ['id' => $unassigneduserid, 'deleted' => 0]);
            \block_exastud\event\classmember_unassigned::log(['objectid' => $class->id,
                                                            'courseid' => $courseid,
                                                            'relateduserid' => $unassigneduserid,
                                                            'other' => ['classtitle' => $class->title,
                                                                    'relatedusername' => $userData->firstname.' '.$userData->lastname]]);
		}
	} else if ($showall) {
		$searchtext = '';
	}
}

$select  = "username <> 'guest' AND deleted = 0 AND confirmed = 1";

$sqlparams = [];

if ($searchtext !== '') {   // Search for a subset of remaining users
	$FULLNAME  = $DB->sql_fullname();
	$selectsql = " AND ( ".$DB->sql_like($FULLNAME, ':fname_search', false)." OR ".$DB->sql_like('email', ':email_search', false).") ";
    $sqlparams['fname_search'] = '%'.$DB->sql_like_escape($searchtext).'%';
    $sqlparams['fname_search_begin'] = '%'.$DB->sql_like_escape($searchtext).'%';
    $sqlparams['email_search'] = '%'.$DB->sql_like_escape($searchtext).'%';
    $sqlparams['email_search_begin'] = '%'.$DB->sql_like_escape($searchtext).'%';
	$select .= str_replace(['fname_search', 'email_search'], ['fname_search_begin', 'email_search_begin'], $selectsql);
} else { 
	$selectsql = ""; 
}

$availableusers = $DB->get_records_sql('SELECT id, firstname, lastname, email, '.get_all_user_name_fields(true).'
									 FROM {user}
									 WHERE '.$select.'
									 AND deleted = 0
									 AND id NOT IN (
											 SELECT studentid
											 FROM {block_exastudclassstudents}
												   WHERE classid = '.$class->id.'
												   '.$selectsql.')
									 ORDER BY lastname ASC, firstname ASC',
                                    $sqlparams);

$classstudents = block_exastud_get_class_students($class->id);

echo $OUTPUT->box_start();
$userlistType = 'students';
require __DIR__.'/lib/configuration_userlist.inc.php';
echo $OUTPUT->box_end();
	
echo $output->back_button($CFG->wwwroot . '/blocks/exastud/configuration_class.php?courseid='.$courseid.'&classid='.$class->id.'&type=students');

echo $output->footer();
