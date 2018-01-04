<?php
// This file is part of Exabis Student Review
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Student Review is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

require __DIR__.'/inc.php';
global $DB;
$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$periodid = optional_param('periodid', 0, PARAM_INT);
$classid = optional_param('classid',0,PARAM_INT);
$detail = optional_param('detailedreport', false, PARAM_BOOL);
block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_USE);

$actPeriod = ($periodid==0) ? block_exastud_check_active_period() : $DB->get_record('block_exastudperiod', array('id'=>$periodid));

$conditions = ($classid == 0) ? array('userid'=>$USER->id,'periodid'=>$actPeriod->id) : array('id'=>$classid);
if (!$class = $DB->get_record('block_exastudclass', $conditions)) {
	print_error('noclassfound', 'block_exastud');
} 
if(!$DB->record_exists("block_exastudclassteachers", array("classid"=>$class->id,"teacherid"=>$USER->id))) {
	print_error('noclassfound', 'block_exastud');
}

//if(!$mystudents = $DB->get_records_sql('SELECT s.id, s.studentid, r.review FROM {block_exastudclassstudents} s LEFT JOIN {block_exastudreview} r ON s.studentid=r.student_id WHERE s.classid=\'' . $class->id . '\' GROUP BY s.id')) {
if(!$mystudents = $DB->get_records_sql('
			SELECT s.id, s.studentid, sum(rp.value) as total, r.review FROM {block_exastudclassstudents} s, {block_exastudclass} c, {block_exastudreview} r, {block_exastudreviewpos} rp
			WHERE s.classid=?
			AND r.studentid = s.studentid AND r.periodid = c.periodid AND rp.reviewid = r.id AND s.classid = c.id GROUP BY s.studentid ORDER BY total DESC',array($class->id))) {
	print_error('studentsnotfound','block_exastud');
}

block_exastud_print_student_report_header();
echo '<div><a href="javascript:window.print()" title="'.block_exastud_get_string('print').'">'.block_exastud_get_string('print').'</a></div>';
$ranking = 1;
foreach($mystudents as $mystudent) {
	block_exastud_print_student_report($mystudent->studentid, $actPeriod->id, $class,false,$detail,$ranking++);
	echo '<p style=\'page-break-before: always;\'>&nbsp;</p>';
}

block_exastud_print_student_report_footer();
