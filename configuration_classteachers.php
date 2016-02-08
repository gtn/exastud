<?php

require "inc.php";

use block_exastud\globals as g;

define("MAX_USERS_PER_PAGE", 5000);

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$showall		= optional_param('showall', 0, PARAM_BOOL);
$searchtext	 = optional_param('searchtext', '', PARAM_TEXT); // search string
$add			= optional_param('add', 0, PARAM_BOOL);
$remove		 = optional_param('remove', 0, PARAM_BOOL);

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_MANAGE_CLASSES);
$curPeriod = block_exastud_check_active_period();

$class = block_exastud\get_teacher_class($classid);

$header = \block_exastud\get_string('configteacher', 'block_exastud', $class->title);
$url = '/blocks/exastud/configuration_classteachers.php';
$PAGE->set_url($url);
block_exastud_print_header(array('configuration_classes', '='.$header));

if ($frm = data_submitted()) {
	require_sesskey();

	if ($add and !empty($frm->addselect)) {
		foreach ($frm->addselect as $adduser) {
			if (!$adduser = clean_param($adduser, PARAM_INT)) {
				continue;
			}
			
			g::$DB->insert_or_update_record('block_exastudclassteachers',
				[ 'timemodified' => time() ],
				[
					'teacherid' => $adduser,
					'classid' => $class->id,
					'subjectid' => optional_param('classteacher_subjectid', 0, PARAM_INT),
				]);
		}
	} else if ($remove and !empty($frm->removeselect)) {
		foreach ($frm->removeselect as $removeuser) {
			if (!$removeuser = clean_param($removeuser, PARAM_INT)) {
				continue;
			}
			
			$DB->delete_records('block_exastudclassteachers', array('id'=>$removeuser, 'classid'=>$class->id));
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

$availableusers = $DB->get_records_sql('SELECT id, firstname, lastname, email
									FROM {user}
									WHERE '.$select.'
									-- disabled, allow teacher to be assign more than once (eg. 2 different subjects)
									-- AND id NOT IN (
									--		 SELECT teacherid
									--		 FROM {block_exastudclassteachers}
									--			   WHERE classid = '.$class->id.'
									--			   '.$selectsql.')
									 ORDER BY lastname ASC, firstname ASC');

echo '<div id="block_exastud">';

$classusers = block_exastud\get_class_teachers($class->id);

echo $OUTPUT->box_start();
$userlistType = 'teachers';
require __DIR__.'/lib/configuration_userlist.inc.php';
echo $OUTPUT->box_end();

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_class.php?courseid='.$courseid.'&classid='.$class->id,
					\block_exastud\get_string('back', 'block_exastud'));

block_exastud_print_footer();
