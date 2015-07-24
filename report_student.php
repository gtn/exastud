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
$periodid = optional_param('periodid', 0, PARAM_INT); // Period ID
$studentid = required_param('studentid', PARAM_INT); // Course ID

require_login($courseid);

$context = context_course::instance($courseid);
require_capability('block/exastud:use', $context);

$actPeriod = block_exastud_get_period($periodid, true);

$student = $DB->get_record_sql("
    SELECT s.studentid AS id, c.id AS classid
    FROM {block_exastudclassteachers} t
    JOIN {block_exastudclass} c ON t.classid=c.id
    JOIN {block_exastudclassstudents} s ON s.classid=c.id
    WHERE t.teacherid = ? AND c.periodid=? AND s.studentid = ?
", array($USER->id, $actPeriod->id, $studentid));

if (!$student) {
	print_error('nostudentstoreview','block_exastud');
}


$url = '/blocks/exastud/report_student.php';
$PAGE->set_url($url);
$blockrenderer = $PAGE->get_renderer('block_exastud');

block_exastud_print_header('report');

$evaluationOtions = block_exastud_get_evaluation_options();
$categories = block_exastud_get_class_categories($student->classid);

echo '<table id="review-table">';

$current_parent = null;
foreach ($categories as $category){
    
    $category_parent = preg_replace('/\s*:.*$/', '', $category->title);
    $category_name = preg_replace('/^[^:]*:\s*/', '', $category->title);
    
	if ($current_parent !== $category_parent) {
		$current_parent = $category_parent;
		echo '<tr><th class="category category-parent">'.$current_parent.':</th>';
		foreach ($evaluationOtions as $option) {
			echo '<th class="evaluation-header"><b>' . $option . '</th>';
		}
		echo '</tr>';
	}
	
	echo '<tr><td class="category">'.$category_name.'</td>';

	foreach ($evaluationOtions as $pos_value => $option) {
		echo '<td class="evaluation">';
		
        $reviewers = $DB->get_records_sql("
            SELECT u.* -- u.id, u.lastname, u.firstname
            FROM {block_exastudreview} r
            JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
            JOIN {user} u ON r.teacher_id = u.id
            WHERE r.student_id = ? AND r.periods_id = ? 
                AND pos.categoryid = ? AND pos.categorysource = ?
                AND pos.value = ?
        ", array($student->id, $actPeriod->id, $category->id, $category->source, $pos_value));
        $i = 0;
        foreach ($reviewers as $reviewer) {
            if ($i) echo ', ';
            $i++;
            echo fullname($reviewer);
        }
		echo '</td>';
	}
	echo '</tr>';
}

echo '</table>';

block_exastud_print_footer();
