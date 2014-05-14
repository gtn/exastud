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

$context = context_system::instance();
//$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/exastud:use', $context);
require_capability('block/exastud:editperiods', $context);

$strperiods = block_exabis_student_review_get_string('periods', 'block_exastud');

block_exabis_student_review_check_periods(true);

if (!$periods = $DB->get_records('block_exastudperiod')) {
	redirect('configuration_period.php?courseid=' . $courseid, block_exabis_student_review_get_string('redirectingtoperiodsinput', 'block_exastud'));
}
$url = '/blocks/exastud/periods.php';
$PAGE->set_url($url);
$PAGE->requires->css('/blocks/exastud/styles.css');

block_exabis_student_review_print_header('periods');
$blockrenderer = $PAGE->get_renderer('block_exastud');

/* Print the periods */
$table = new html_table();

$table->head = array(
	block_exabis_student_review_get_string('perioddescription', 'block_exastud'),
	block_exabis_student_review_get_string('starttime', 'block_exastud'),
	block_exabis_student_review_get_string('endtime', 'block_exastud'),
	block_exabis_student_review_get_string('action')
);

$table->align = array("left", "left", "left", "right");
$table->width = "90%";

foreach($periods as $period) {

	$link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/configuration_period.php?courseid=' . $courseid . '&amp;periodid=' . $period->id . '&amp;sesskey=' . sesskey() . '&amp;action=edit">';

	$icons = $link.'<img src="pix/edit.png" alt="' . block_exabis_student_review_get_string('edit'). '" /></a>
			  <a href="' . $CFG->wwwroot . '/blocks/exastud/configuration_period.php?courseid=' . $courseid . '&amp;periodid=' . $period->id . '&amp;sesskey=' . sesskey() . '&amp;action=delete"><img src="pix/del.png" alt="' . block_exabis_student_review_get_string('delete'). '" /></a> ';

	$starttime = date('d. M. Y - H:i', $period->starttime);
	$endtime = date('d. M. Y - H:i', $period->endtime);
	
	$table->data[] = array ($link.$period->description.'</a>', $starttime, $endtime, $icons);
}

echo $blockrenderer->print_esr_table($table);

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_period.php?courseid='.$courseid.'&sesskey='.sesskey().'&action=new',
					block_exabis_student_review_get_string('newperiod', 'block_exastud'));

block_exabis_student_review_print_footer();
