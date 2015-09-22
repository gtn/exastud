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
$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID

require_login($courseid);

//$context = get_context_instance(CONTEXT_SYSTEM);
$context = context_system::instance();

require_capability('block/exastud:use', $context);

$url = '/blocks/exastud/review.php';
$PAGE->set_url($url);
$blockrenderer = $PAGE->get_renderer('block_exastud');
block_exastud_print_header('review');
$actPeriod = block_exastud_check_active_period();

$myclasses = $DB->get_records_sql("
    SELECT ct.id, ct.subjectid, ct.classid, c.class, s.title AS subject
    FROM {block_exastudclassteachers} ct
    JOIN {block_exastudclass} c ON ct.classid=c.id
    LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
    WHERE ct.teacherid=? AND c.periodid=?
    ORDER BY c.class, s.sorting
", array($USER->id, $actPeriod->id));

if(!$myclasses) {
	echo block_exastud_get_string('noclassestoreview','block_exastud');
}
else {
	/* Print the Students */
	$table = new html_table();

	$table->head = array(
			block_exastud_get_string('class', 'block_exastud')
	);

	$table->align = array("left");
	$table->width = "90%";

	foreach($myclasses as $myclass) {
		$edit_link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid . '&amp;classid=' . $myclass->classid . '&amp;subjectid=' . $myclass->subjectid . '">';

		$table->data[] = array($edit_link.$myclass->class.($myclass->subject?' - '.$myclass->subject:'').'</a>');
	}

	echo $blockrenderer->print_esr_table($table);
}
block_exastud_print_footer();
