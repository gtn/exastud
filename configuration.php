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

$courseid       = optional_param('courseid', 1, PARAM_INT); // Course ID
$showall        = optional_param('showall', 0, PARAM_BOOL);
$searchtext     = optional_param('searchtext', '', PARAM_ALPHANUM); // search string
require_login($courseid);

$context = context_system::instance();
//$context = get_context_instance(CONTEXT_COURSE,$courseid);
require_capability('block/exastud:use', $context);
require_capability('block/exastud:headteacher', $context);

$url = '/blocks/exastud/configuration.php';
$PAGE->set_url($url);

if (!$class = $DB->get_record('block_exastudclass', array('userid'=>$USER->id))) {
	redirect('configuration_class.php?courseid=' . $courseid, get_string('redirectingtoclassinput', 'block_exastud'));
}

block_exabis_student_review_print_header('configuration');
echo $OUTPUT->heading($class->class);

//if no periods
//if (!$periods = $DB->get_records('block_exastudperiod')) {
if (block_exabis_student_review_get_active_period(false,false)==false){
echo $OUTPUT->box(get_string('noperiods', 'block_exastud'));
}

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_class.php?courseid='.$courseid.'&sesskey='. sesskey(),
		get_string('editclassname', 'block_exastud'));

/* Print the Students */
$table = new html_table();

$table->head = array (get_string('firstname'), get_string('lastname'), get_string('email'));
$table->align = array ("left", "left", "left");
$table->width = "90%";

$usertoclasses = $DB->get_records('block_exastudclassstudents', array('classid'=>$class->id), 'studentid');

foreach($usertoclasses as $usertoclass) {
	$user = $DB->get_record('user', array('id'=>$usertoclass->studentid));
	$table->data[] = array ($user->firstname, $user->lastname, $user->email);
}

echo html_writer::table($table);

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_classmembers.php?courseid='.$courseid.'&sesskey='. sesskey(),
		get_string('editclassmemberlist', 'block_exastud'));

/* Print the Classes */
$table = new html_table();

$table->head = array (get_string('firstname'), get_string('lastname'), get_string('email'));
$table->align = array ("left", "left", "left");
$table->width = "90%";

$usertoclasses = $DB->get_records('block_exastudclassteachers', array('classid'=>$class->id), 'teacherid');

foreach($usertoclasses as $usertoclass) {
	$user = $DB->get_record('user', array('id'=>$usertoclass->teacherid));
	$table->data[] = array ($user->firstname, $user->lastname, $user->email);
}

echo html_writer::table($table);

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_classteachers.php?courseid='.$courseid.'&sesskey='. sesskey(),
		get_string('editclassteacherlist', 'block_exastud'));

/* Print the categories */
$table = new html_table();

$table->head = array(get_string('categories','block_exastud'));
$table->align = array("left");
$table->width = "90%";

$categories = block_exabis_student_review_get_class_categories($class->id);

foreach($categories as $category) {
	$table->data[] = array($category->title);
}

echo html_writer::table($table);
echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_categories.php?courseid='.$courseid.'&sesskey='.sesskey(),
		get_string('editclasscategories', 'block_exastud'));

block_exabis_student_review_print_footer();
