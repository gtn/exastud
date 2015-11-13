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

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);
$subjectid = required_param('subjectid', PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);

require_login($courseid);

if (!$returnurl) {
    $returnurl = new moodle_url('/blocks/exastud/review_class.php?courseid='.$courseid.'&classid='.$classid.'&subjectid='.$subjectid);
}

$url = '/blocks/exastud/review_student.php';
$PAGE->set_url($url);

block_exastud_require_global_cap(block_exastud::CAP_USE);

$classdata = $DB->get_record_sql("
    SELECT ct.id, c.class, s.title AS subject
    FROM {block_exastudclassteachers} ct
    JOIN {block_exastudclass} c ON ct.classid=c.id
    LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
    WHERE ct.teacherid=? AND ct.classid=? AND ".($subjectid?'s.id=?':'s.id IS NULL')."
", array($USER->id, $classid, $subjectid));

if(!$classdata) {
    print_error('badclass', 'block_exastud');
}

if ($DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid)) == 0) {
    print_error('badstudent', 'block_exastud');
}

$strstudentreview = block_exastud_get_string('reviewstudent', 'block_exastud');
$strclassreview = block_exastud_get_string('reviewclass', 'block_exastud');
$strreview = block_exastud_get_string('review', 'block_exastud');

$actPeriod = block_exastud_check_active_period();
$categories = block_exastud_get_class_categories($classid);

$formdata = new stdClass();

$formdata->courseid = $courseid;
$formdata->studentid = $studentid;
$formdata->classid = $classid;
$formdata->subjectid = $subjectid;

if (!$reviewdata = $DB->get_record('block_exastudreview', array('teacherid' => $USER->id, 'subjectid'=>$subjectid, 'periodid' => $actPeriod->id, 'studentid' => $studentid))) {
    $formdata->review = '';
} else {
    foreach($categories as $category) {
    	$formdata->{$category->id.'_'.$category->source} = $DB->get_field('block_exastudreviewpos', 'value', array("categoryid"=>$category->id,"reviewid"=>$reviewdata->id,"categorysource"=>$category->source));
    }
    $formdata->review = $reviewdata->review;
}
$studentform = new student_edit_form(null,array("categories"=>$categories));

if ($studentedit = $studentform->get_data()) {
    $newreview = new stdClass();
    $newreview->timemodified = time();
    $newreview->studentid = $studentid;
    $newreview->subjectid = $subjectid;
    $newreview->periodid = $actPeriod->id;
    $newreview->teacherid = $USER->id;
    
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
    redirect($returnurl);
}

$classheader = block_exastud_get_string('reviewclass').': '.$classdata->class.($classdata->subject?' - '.$classdata->subject:'');

block_exastud_print_header(array('review',
    array('name' => $strclassreview, 'link' => $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid .
        '&classid=' . $classid),
        '='.$classheader,
    '=' . $strstudentreview
        ), array('noheading'));

$student = $DB->get_record('user', array('id' => $studentid));
$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)) . ' ' . fullname($student, $student->id);

echo $OUTPUT->heading($classheader);
echo $OUTPUT->heading($studentdesc);

$studentform->set_data($formdata);
$studentform->display();

echo $OUTPUT->single_button($returnurl,
        block_exastud_get_string('back', 'block_exastud'));

block_exastud_print_footer();
