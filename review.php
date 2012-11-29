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

require("inc.php");
global $DB;
$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID

require_login($courseid);

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/exastud:use', $context);

$url = '/blocks/exastud/review.php';
$PAGE->set_url($url);
block_exabis_student_review_print_header('review');
$actPeriod = block_exabis_student_review_get_active_period();

if(!$myclasses = $DB->get_records_sql('SELECT * FROM {block_exastudclassteachers} t JOIN {block_exastudclass} c ON t.classid=c.id AND t.teacherid=\'' . $USER->id . '\'')) {
	print_error('noclassestoreview','block_exastud');
}

/* Print the Students */
$table = new html_table();

$table->head = array(
	get_string('class', 'block_exastud'),
	get_string('action')
);

$table->align = array("left", "right");
$table->width = "90%";

foreach($myclasses as $myclass) {
	$edit_link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid . '&amp;classid=' . $myclass->classid . '&amp;sesskey=' . sesskey() . '&amp;action=edit">';

	$icons = $edit_link.'<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . get_string('edit'). '" /></a>';

	$table->data[] = array($edit_link.$myclass->class.'</a>', $icons);
}

echo html_writer::table($table);

block_exabis_student_review_print_footer();
