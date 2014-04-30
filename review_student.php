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
global $DB;
$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);

require_login($courseid);

$url = '/blocks/exastud/review_student.php';
$PAGE->set_url($url);
//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();
require_capability('block/exastud:use', $context);

if (!confirm_sesskey()) {
    print_error('badsessionkey', 'block_exastud');
}

if ($DB->count_records('block_exastudclassteachers', array('teacherid' => $USER->id, 'classid' => $classid)) == 0) {
    print_error('badclass', 'block_exastud');
}

if ($DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid)) == 0) {
    print_error('badstudent', 'block_exastud');
}

$strstudentreview = get_string('reviewstudent', 'block_exastud');
$strclassreview = get_string('reviewclass', 'block_exastud');
$strreview = get_string('review', 'block_exastud');

$actPeriod = block_exabis_student_review_get_active_period();
$categories = block_exabis_student_review_get_class_categories($classid);

$formdata = new stdClass();

if (!$reviewdata = $DB->get_record('block_exastudreview', array('teacher_id' => $USER->id, 'periods_id' => $actPeriod->id, 'student_id' => $studentid))) {
	
    $formdata->courseid = $courseid;
    $formdata->studentid = $studentid;
    $formdata->classid = $classid;
    $formdata->review = '';
} else {
    $formdata->courseid = $courseid;
    $formdata->studentid = $studentid;
    $formdata->classid = $classid;
    foreach($categories as $category) {
    	$formdata->{$category->id.'_'.$category->source} = $DB->get_field('block_exastudreviewpos', 'value', array("categoryid"=>$category->id,"reviewid"=>$reviewdata->id,"categorysource"=>$category->source));
    }
    $formdata->review = $reviewdata->review;
}
$studentform = new student_edit_form(null,array("categories"=>$categories));

if ($studentedit = $studentform->get_data()) {
    $newreview = new stdClass();
    $newreview->timemodified = time();
    $newreview->student_id = $studentid;
    $newreview->periods_id = $actPeriod->id;
    $newreview->teacher_id = $USER->id;
    
    $newreview->review = $studentedit->review;

    if (isset($reviewdata->id)) {
        $newreview->id = $reviewdata->id;
        if ($DB->update_record('block_exastudreview', $newreview)) {
        	foreach($categories as $category) {
        		if($DB->record_exists('block_exastudreviewpos', array("categoryid"=>$category->id,"reviewid"=>$reviewdata->id,"categorysource"=>$category->source)))
        			$DB->set_field('block_exastudreviewpos', 'value', $studentedit->{$category->id.'_'.$category->source},array("categoryid"=>$category->id,"reviewid"=>$reviewdata->id,"categorysource"=>$category->source));
        		else
        			$DB->insert_record('block_exastudreviewpos', array("reviewid"=>$reviewdata->id,"categoryid"=>$category->id,"categorysource"=>$category->source,"value"=>$studentedit->{$category->id.'_'.$category->source}));
        	}
        }
        else
            print_error('errorupdatingstudent', 'block_exastud');
    } else {
        if (($newreview->id = $DB->insert_record('block_exastudreview', $newreview))) {
        	foreach($categories as $category) {
        		$data = new stdClass();
        		$data->reviewid = $newreview->id;
        		$data->categoryid = $category->id;
        		$data->categorysource = $category->source;
        		$data->value = $studentedit->{$category->id.'_'.$category->source};
        		$DB->insert_record('block_exastudreviewpos', $data);
        	}
        } else
            print_error('errorinsertingstudent', 'block_exastud');
    }
    redirect($CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid . '&amp;classid=' . $classid . '&amp;sesskey=' . sesskey());
}

block_exabis_student_review_print_header(array('review',
    array('name' => $strclassreview, 'link' => $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid .
        '&amp;classid=' . $classid . '&amp;sesskey=' . sesskey()),
    '=' . $strstudentreview
        ), array('noheading' => true));

$student = $DB->get_record('user', array('id' => $studentid));
$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)) . ' ' . fullname($student, $student->id);

echo $OUTPUT->heading($studentdesc);

$studentform->set_data($formdata);
$studentform->display();

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/review_class.php?courseid='.$courseid.'&classid='.$classid.'sesskey='.sesskey(),
        get_string('back', 'block_exastud'));

block_exabis_student_review_print_footer();
