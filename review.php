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

block_exastud_require_global_cap(block_exastud\CAP_REVIEW);

$url = '/blocks/exastud/review.php';
$PAGE->set_url($url);
$blockrenderer = $PAGE->get_renderer('block_exastud');
block_exastud_print_header('review');
$actPeriod = block_exastud_check_active_period();

$myclasses = $DB->get_records_sql("
	SELECT ct.id, ct.subjectid, ct.classid, c.title, s.title AS subject
	FROM {block_exastudclassteachers} ct
	JOIN {block_exastudclass} c ON ct.classid=c.id
	LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
	WHERE ct.teacherid=? AND c.periodid=? AND ct.subjectid >= 0
	ORDER BY c.title, s.sorting
", array($USER->id, $actPeriod->id));

$lern_und_sozialverhalten_classes = \block_exastud\get_head_teacher_lern_und_sozialverhalten_classes();

if(!$lern_und_sozialverhalten_classes && !$myclasses) {
	echo \block_exastud\get_string('noclassestoreview','block_exastud');
}
else {
	if ($lern_und_sozialverhalten_classes) {
		/* Print the Students */
		$table = new html_table();

		$table->head = array(\block_exastud\trans('Lern- und Sozialverhalten'));

		$table->align = array("left");
		$table->width = "90%";

		foreach ($lern_und_sozialverhalten_classes as $myclass) {
			$edit_link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid . '&amp;classid=' . $myclass->classid . '&amp;subjectid=' . $myclass->subjectid . '">';

			$table->data[] = array($edit_link.$myclass->title.($myclass->subject?' - '.$myclass->subject:'').'</a>');
		}

		echo $blockrenderer->print_esr_table($table);
	}

	if ($myclasses) {
		/* Print the Students */
		$table = new html_table();

		$table->head = array(block_exastud\get_string('review'));

		$table->align = array("left");
		$table->width = "90%";

		foreach ($myclasses as $myclass) {
			$edit_link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid . '&amp;classid=' . $myclass->classid . '&amp;subjectid=' . $myclass->subjectid . '">';

			$table->data[] = array($edit_link.$myclass->title.($myclass->subject?' - '.$myclass->subject:'').'</a>');
		}

		echo $blockrenderer->print_esr_table($table);
	}
}
block_exastud_print_footer();
