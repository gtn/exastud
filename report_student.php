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
$studentid = required_param('studentid', PARAM_INT);

require_login($courseid);

block_exastud_require_global_cap(block_exastud::CAP_USE);

if (!is_new_version()) die('not allowed');

if (!$class = $DB->get_record('block_exastudclass', array('id' => $classid))) {
    print_error('badclass', 'block_exastud');
}
if (!$DB->count_records('block_exastudclassteachers', array('teacherid' => $USER->id, 'classid' => $classid))) {
    print_error('badclass', 'block_exastud');
}
if (!$DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid))) {
    print_error('badstudent', 'block_exastud');
}
if (!$student = $DB->get_record('user', array('id' => $studentid))) {
    print_error('badstudent', 'block_exastud');
}

$url = '/blocks/exastud/report_student.php';
$PAGE->set_url($url);
$blockrenderer = $PAGE->get_renderer('block_exastud');

$strstudentreview = block_exastud_get_string('reviewstudent', 'block_exastud');
$strclassreview = block_exastud_get_string('reviewclass', 'block_exastud');
block_exastud_print_header(array('review',
    array('name' => $strclassreview, 'link' => $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid .
        '&classid=' . $classid),
    '=' . $strstudentreview
        ), array('noheading'));


$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)) . ' ' . fullname($student, $student->id);

echo $OUTPUT->heading($studentdesc);



$evaluationOtions = block_exastud_get_evaluation_options();
$categories = block_exastud_get_class_categories($classid);

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
        
        $reviewers = $DB->get_recordset_sql("
            SELECT u.*, s.title AS subject
            FROM {block_exastudreview} r
            JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
            JOIN {user} u ON r.teacherid = u.id
            JOIN {block_exastudclass} c ON c.periodid = r.periodid
            LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
            WHERE r.studentid = ? AND c.id = ?
                AND pos.categoryid = ? AND pos.categorysource = ?
                AND pos.value = ?
        ", array($studentid, $classid, $category->id, $category->source, $pos_value));
        $i = 0;
        foreach ($reviewers as $reviewer) {
            if ($i) echo ', ';
            $i++;
            echo ($reviewer->subject?$reviewer->subject.' ('.fullname($reviewer).')':fullname($reviewer));
        }
        echo '</td>';
    }
    echo '</tr>';
}

echo '</table>';




$comments = $DB->get_recordset_sql("
                SELECT ".user_picture::fields('u').", r.review, s.title AS subject
                FROM {block_exastudreview} r
                JOIN {user} u ON r.teacherid = u.id
                LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
                WHERE r.studentid = ? AND r.periodid = ? AND TRIM(r.review) !=  ''
                ORDER BY s.title, u.lastname, u.firstname",
                array($studentid, $class->periodid));


echo '<h3>'.get_string('detailedreview','block_exastud').'</h3>';

echo '<table id="ratingtable">';
foreach($comments as $comment) {
    echo '<tr><td class="ratinguser">'.($comment->subject?$comment->subject.' ('.fullname($comment).')':fullname($comment)).'</td>
        <td class="ratingtext">'.format_text($comment->review).'</td>
        </tr>';
}
echo '</table>';

block_exastud_print_footer();
