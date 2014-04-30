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
global $DB;
$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);

require_login($courseid);

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();

require_capability('block/exastud:use', $context);

if(!confirm_sesskey()) {
	print_error("badsessionkey","block_exastud");
}

if($DB->count_records('block_exastudclassteachers', array('teacherid'=>$USER->id, 'classid'=>$classid)) == 0) {
	print_error("badclass","block_exastud");
}

$url = '/blocks/exastud/review_class.php';
$PAGE->set_url($url);
block_exabis_student_review_print_header(array('review', 'reviewclass'));

$actPeriod = block_exabis_student_review_get_active_period();

if(!$classusers = $DB->get_records('block_exastudclassstudents', array('classid'=>$classid))) {
	print_error('nostudentstoreview','block_exastud');
}

$categories = block_exabis_student_review_get_class_categories($classid);

/* Print the Students */
$table = new html_table();

$table->head = array();
$table->head[] = get_string('name');
$table->head[] = get_string('action');
foreach($categories as $category)
	$table->head[] = $category->title;
$table->head[] = get_string('evaluation', 'block_exastud');

$table->align = array();
$table->align[] = 'left';

for($i=0;$i<count($categories);$i++)
	$table->align[] = 'center';

$table->align[] = 'left';
$table->align[] = 'right';

$table->width = "90%";

foreach($classusers as $classuser) {
	$user = $DB->get_record('user', array('id'=>$classuser->studentid));
	if (!$user)
		continue;
	
	$link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_student.php?courseid=' . $courseid . '&amp;classid=' . $classid . '&amp;sesskey=' . sesskey() . '&amp;studentid=' . $user->id . '">';

	$icons = $link.'<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . get_string('edit'). '" /></a>';
	$userdesc = $OUTPUT->user_picture($user,array("courseid"=>$courseid)) . ' ' . $link . fullname($user, $user->id).'</a>';
	
	$report = $DB->get_record('block_exastudreview', array('teacher_id'=>$USER->id, 'periods_id'=>$actPeriod->id, 'student_id'=>$user->id));
	$data = array();
	$data[] = $userdesc;
	$data[] = $icons;
	if($report) {
		foreach($categories as $category)
			$data[] = $DB->get_field('block_exastudreviewpos', 'value', array("categoryid"=>$category->id,"reviewid"=>$report->id,"categorysource"=>$category->source));
		$data[] = $report->review;
	}
	else
		for($i=0;$i<=count($categories);$i++)
			$data[] = '';
	
	$table->data[] = $data;
}

echo html_writer::table($table);

block_exabis_student_review_print_footer();
