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

require("inc.php");
global $DB, $THEME;
define("MAX_USERS_PER_PAGE", 5000);


$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$showall        = optional_param('showall', 0, PARAM_BOOL);
$searchtext     = optional_param('searchtext', '', PARAM_TEXT); // search string
$add            = optional_param('add', 0, PARAM_BOOL);
$remove         = optional_param('remove', 0, PARAM_BOOL);

require_login($courseid);

$context = get_context_instance(CONTEXT_COURSE,$courseid);
require_capability('block/exastud:use', $context);
require_capability('block/exastud:headteacher', $context);
$curPeriod = block_exabis_student_review_get_active_period(true);

if (!$class = $DB->get_record('block_exastudclass', array('userid'=>$USER->id,'periodid' => $curPeriod->id))) {
	print_error('noclassfound', 'block_exastud');
}

$header = get_string('configcategories', 'block_exastud', $class->class);
$url = '/blocks/exastud/configuration_categories.php';
$PAGE->set_url($url);
block_exabis_student_review_print_header(array('configuration', '='.$header));

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
				error('errorremovingcategories', 'block_exabis_student_review');
			}
		}
	} else if ($showall) {
		$searchtext = '';
	}
}

if ($searchtext !== '') {   // Search for a subset of remaining users
	//$LIKE      = $DB->sql_ilike();
	$LIKE      = "LIKE";
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
	$availablecategory->subject = get_string('basiccategories','block_exastud');
}

if(block_exabis_student_review_check_competence_block()) {
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
			$topic->subject = $subject->title;
			$availablecategories[] = $topic;
		}
	}
}

$classcategories = block_exabis_student_review_get_class_categories($class->id);

echo $OUTPUT->box_start();
$form_target = 'configuration_categories.php?courseid='.$courseid;
$userlistType = 'configurations';
require dirname(__FILE__).'/lib/configuration_categories.inc.php';
echo $OUTPUT->box_end();

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration.php?courseid='.$courseid,
		get_string('back', 'block_exastud'));

block_exabis_student_review_print_footer();
