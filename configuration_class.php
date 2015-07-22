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
require_once($CFG->dirroot . '/blocks/exastud/lib/edit_form.php');
global $DB, $OUTPUT, $PAGE;

$courseid       = optional_param('courseid', 1, PARAM_INT); // Course ID
$showall        = optional_param('showall', 0, PARAM_BOOL);
$searchtext     = optional_param('searchtext', '', PARAM_ALPHANUM); // search string

require_login($courseid);

//$context = get_context_instance(CONTEXT_COURSE,$courseid);
$context = context_course::instance($courseid);
require_capability('block/exastud:use', $context);
require_capability('block/exastud:headteacher', $context);

$curPeriod = block_exastud_get_active_period(true);
if (!$class = $DB->get_record('block_exastudclass', array('userid'=>$USER->id,'periodid' => $curPeriod->id))) {
	$class = new stdClass();
	$class->courseid = $courseid;
	$class->class = '';
}
$class->courseid = $courseid;
$classform = new class_edit_form();
if ($classform->is_cancelled()) {
	redirect('configuration.php?courseid=' . $courseid);
} else if ($classedit = $classform->get_data()) {
	if(!confirm_sesskey()) {
		print_error("badsessionkey","block_exastud");
	}
	
	$newclass = new stdClass();
	$newclass->timemodified = time();
	$newclass->userid = $USER->id;
	$newclass->class = $classedit->class;
	$newclass->periodid = $curPeriod->id;
	
	if(isset($class->id)) {
		$newclass->id = $class->id;
		if (!$DB->update_record('block_exastudclass', $newclass)) {
			error('errorupdatingclass', 'block_exastud');
		}
	}
	else {
		if (!($class->id = $DB->insert_record('block_exastudclass', $newclass))) {
			error('errorinsertingclass', 'block_exastud');
		}
	}
	redirect('configuration.php?courseid=' . $courseid);
}

$url = "/blocks/exastud/configuration_class.php";
$PAGE->set_url($url);
block_exastud_print_header(array('configuration', 'editclassname'));

echo '<div id="block_student_review">';
echo "<br />";
echo $OUTPUT->box( text_to_html(block_exastud_get_string("explainclassname","block_exastud")));
echo '</div>';
echo '<div id="block_student_review">';
echo $OUTPUT->heading($class->class);

$classform->set_data($class);
$classform->display();

echo '</div>';
block_exastud_print_footer();
