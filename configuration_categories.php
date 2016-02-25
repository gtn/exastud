<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// All rights reserved
/**
 * @package moodlecore
 * @subpackage blocks
 * @copyright 2013 gtn gmbh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
*/

require __DIR__.'/inc.php';

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

$header = \block_exastud\get_string('configcategories', 'block_exastud', $class->title);
$url = '/blocks/exastud/configuration_categories.php';
$PAGE->set_url($url);
$output = \block_exastud\get_renderer();
echo $output->header(array('configuration_classes', '='.$header));

if ($frm = data_submitted()) {
	if(!confirm_sesskey()) {
		print_error("badsessionkey","block_exastud");
	}
	if ($add and !empty($frm->addselect)) {
		foreach ($frm->addselect as $addcat) {

			$category = explode("_",$addcat);
			$entry = new stdClass();
			$entry->classid = $class->id;
			$entry->categoryid = $category[0];
			$entry->categorysource = $category[1];
				
			if (!$DB->insert_record('block_exastudclasscate', $entry)) {
				error('errorinsertingcategories', 'block_exastud');
			}
		}
	} else if ($remove and !empty($frm->removeselect)) {
		foreach ($frm->removeselect as $removecat) {
				
			$category = explode("_",$removecat);
			if (!$DB->delete_records('block_exastudclasscate', array('categoryid'=>$category[0], 'categorysource'=>$category[1], 'classid'=>$class->id))) {
				error('errorremovingcategories', 'block_exastud');
			}
		}
	} else if ($showall) {
		$searchtext = '';
	}
}

if ($searchtext !== '') {   // Search for a subset of remaining users
	//$LIKE	  = $DB->sql_ilike();
	$LIKE	  = "LIKE";
	$selectsql = " AND (title $LIKE '%$searchtext%') ";
	$selectsql_begin = " (title $LIKE '%$searchtext%') AND ";
} else {
	$selectsql = "";
	$selectsql_begin = "";
}

$availablecategories = $DB->get_records_sql('SELECT id, title
		FROM {block_exastudcate}
		WHERE '.$selectsql_begin.'id NOT IN (
		SELECT categoryid
		FROM {block_exastudclasscate}
		WHERE classid = '.$class->id.' AND categorysource="exastud"
		'.$selectsql.')');
foreach($availablecategories as $availablecategory) {
	$availablecategory->source = 'exastud';
	$availablecategory->subject_title = \block_exastud\get_string('basiccategories','block_exastud');
}

if(\block_exastud\is_exacomp_installed()) {
	$availablesubjects = $DB->get_records('block_exacompsubjects');
	foreach($availablesubjects as $subject) {
		$availabletopics = $DB->get_records_sql('SELECT id, title
				FROM {block_exacomptopics}
				WHERE '.$selectsql_begin.'id NOT IN (
				SELECT categoryid
				FROM {block_exastudclasscate}
				WHERE classid = '.$class->id.' AND categorysource="exacomp"
				'.$selectsql.') AND subjid = '.$subject->id.' AND source='.$subject->source);
		foreach($availabletopics as $topic) {
			$topic->source = 'exacomp';
			$topic->subject_title = $subject->title;
			$availablecategories[] = $topic;
		}
	}
}

$classcategories = block_exastud_get_class_categories($class->id);

echo $OUTPUT->box_start();
$userlistType = 'configurations';
require __DIR__.'/lib/configuration_categories.inc.php';
echo $OUTPUT->box_end();

echo $output->back_button($CFG->wwwroot . '/blocks/exastud/configuration_class.php?courseid='.$courseid.'&classid='.$class->id);

echo $output->footer();
