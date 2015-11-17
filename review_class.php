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

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$subjectid = required_param('subjectid', PARAM_INT);

require_login($courseid);

block_exastud_require_global_cap(block_exastud::CAP_USE);

$classdata = block_exastud\get_review_class($classid, $subjectid);

if(!$classdata) {
	print_error("badclass","block_exastud");
}

$url = '/blocks/exastud/review_class.php';
$PAGE->set_url($url);
$blockrenderer = $PAGE->get_renderer('block_exastud');
$classheader = $classdata->class.($classdata->subject?' - '.$classdata->subject:'');
block_exastud_print_header(array('review', '='.$classheader));

$actPeriod = block_exastud_check_active_period();

if(!$classusers = $DB->get_records('block_exastudclassstudents', array('classid'=>$classid))) {
	print_error('nostudentstoreview','block_exastud');
}

if ($subjectid == block_exastud::SUBJECT_ID_LERN_UND_SOZIALVERHALTEN) {
    $categories = [(object)[ 'title' => block_exastud::t('Lern- und Sozialverhalten'), 'id'=>0, 'source'=>'']];
} else {
    $categories = block_exastud_get_class_categories($classid);
}
$evaluation_options = block_exastud_get_evaluation_options();

/* Print the Students */
$table = new html_table();

$table->head = array();
$table->head[] = ''; //userpic
$table->head[] = block_exastud_get_string('name');
$table->head[] = ''; // bewerten button
if (is_new_version())
    $table->head[] = ''; // report button
foreach($categories as $category)
	$table->head[] = $category->title;

$table->align = array();
$table->align[] = 'center';
$table->align[] = 'left';

$table->align[] = 'center';
$table->align[] = 'center';

for($i=0;$i<=count($categories);$i++)
	$table->align[] = 'center';

$table->align[] = 'left';
$table->align[] = 'right';

$table->width = "90%";

$oddeven = true;
foreach($classusers as $classuser) {
	$user = $DB->get_record('user', array('id'=>$classuser->studentid));
	if (!$user)
		continue;
	
	$icons = '<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . block_exastud_get_string('edit'). '" />';
	$userdesc = fullname($user, $user->id);
	
	$report = $DB->get_record('block_exastudreview', array('teacherid'=>$USER->id, 'subjectid'=>$subjectid, 'periodid'=>$actPeriod->id, 'studentid'=>$user->id));
	$row = new html_table_row();
	$row->cells[] = $OUTPUT->user_picture($user,array("courseid"=>$courseid));
	$row->cells[] = $userdesc;
	
	$row->cells[] = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_student.php?courseid=' . $courseid . '&classid=' . $classid . '&subjectid=' . $subjectid . '&studentid=' . $user->id . '">'.
        block_exastud::t('de:Bewerten').'</a>';
	
	if (is_new_version()) {
        $row->cells[] = '<a href="' . $CFG->wwwroot . '/blocks/exastud/report_student.php?courseid=' . $courseid . '&classid=' . $classid . '&studentid=' . $user->id . '">'
            .block_exastud::t('de:Alle Bewertungen zeigen').'</a>'.
            '<br /><a href="' . $CFG->wwwroot . '/blocks/exastud/report_student.php?courseid=' . $courseid . '&classid=' . $classid . '&studentid=' . $user->id . '&output=docx">'
            .block_exastud::t('de:Als MS-Word exportieren').'</a>';
	}
	if($report) {
		foreach($categories as $category) {
			$bewertung = $DB->get_field('block_exastudreviewpos', 'value', array("categoryid"=>$category->id,"reviewid"=>$report->id,"categorysource"=>$category->source));
			$row->cells[] = $bewertung && isset($evaluation_options[$bewertung]) ? $evaluation_options[$bewertung] : '';
		}
	}
	else {
		for($i=0;$i<count($categories);$i++)
			$row->cells[] = '';
	}
    
    $oddeven = !$oddeven;
	$row->oddeven = $oddeven;
	$table->data[] = $row;

    if ($report) {
        /*
        $cell = new html_table_cell();
        $cell->text = block_exastud_get_string('evaluation', 'block_exastud');
        $cell->colspan = count($categories);
        $row = new html_table_row(array(
            'asdf', '', '', $cell
        ));
        $row->oddeven = $oddeven;
        $table->data[] = $row;
        */

        $cell = new html_table_cell();
        $cell->text = $report->review;
        $cell->colspan = count($categories);
        $cell->style = 'text-align: left;';
        $row = new html_table_row(array(
            '', '', '', '', $cell
        ));
        $row->oddeven = $oddeven;
        $table->data[] = $row;
    }
}

echo $blockrenderer->print_esr_table($table);

block_exastud_print_footer();
