<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 exabis internet solutions <info@exabis.at>
*  All rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This module is based on the Collaborative Moodle Modules from
*  NCSA Education Division (http://www.ncsa.uiuc.edu)
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require('inc.php');
global $DB;
$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$periodid = optional_param('periodid', 0, PARAM_INT);
require_login($courseid);

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/exastud:use', $context);
require_capability('block/exastud:head', $context);

if (!$class = $DB->get_record('block_exastudclass', array('userid'=>$USER->id))) {
	print_error('noclassfound', 'block_exastud');
}

$actPeriod = ($periodid==0) ? block_exabis_student_review_get_active_period() : $DB->get_record('block_exastudperiod', array('id'=>$periodid));

if(!$mystudents = $DB->get_records_sql('SELECT s.id, s.studentid, r.review FROM {block_exastudclassstudents} s LEFT JOIN {block_exastudreview} r ON s.studentid=r.student_id WHERE s.classid=\'' . $class->id . '\' GROUP BY s.id')) {
	print_error('studentsnotfound','block_exastud');
}
block_exabis_student_review_print_student_report_header();
echo '<a href="javascript:window.print()" title=”Drucken”>'.get_string('print','block_exastud').'</a>';
foreach($mystudents as $mystudent) {
	block_exabis_student_review_print_student_report($mystudent->studentid, $actPeriod->id, $class);
	echo '<p style=\'page-break-before: always;\'>&nbsp;</p>';
}

block_exabis_student_review_print_student_report_footer();
