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

require __DIR__.'/inc.php';
global $DB;
$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_EDIT_PERIODS);

$strperiods = \block_exastud\get_string('periods', 'block_exastud');

block_exastud_check_periods(true);

if (!$periods = $DB->get_records('block_exastudperiod')) {
	redirect('configuration_periods.php?courseid=' . $courseid, \block_exastud\get_string('redirectingtoperiodsinput', 'block_exastud'));
}
$url = '/blocks/exastud/periods.php';
$PAGE->set_url($url);
$output = block_exastud\get_renderer();
$output->header(['settings', 'periods']);

/* Print the periods */
$table = new html_table();

$table->head = array(
	\block_exastud\get_string('perioddescription', 'block_exastud'),
	\block_exastud\get_string('starttime', 'block_exastud'),
	\block_exastud\get_string('endtime', 'block_exastud'),
	\block_exastud\get_string('action')
);

$table->align = array("left", "left", "left", "right");

foreach($periods as $period) {

	$link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/configuration_periods.php?courseid=' . $courseid . '&amp;periodid=' . $period->id . '&amp;sesskey=' . sesskey() . '&amp;action=edit">';

	$icons = $link.'<img src="pix/edit.png" alt="' . \block_exastud\get_string('edit'). '" /></a>
			  <a href="' . $CFG->wwwroot . '/blocks/exastud/configuration_periods.php?courseid=' . $courseid . '&amp;periodid=' . $period->id . '&amp;sesskey=' . sesskey() . '&amp;action=delete"><img src="pix/del.png" alt="' . \block_exastud\get_string('delete'). '" /></a> ';

	$starttime = date('d. M. Y - H:i', $period->starttime);
	$endtime = date('d. M. Y - H:i', $period->endtime);
	
	$table->data[] = array ($link.$period->description.'</a>', $starttime, $endtime, $icons);
}

echo $output->table($table);

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_periods.php?courseid='.$courseid.'&sesskey='.sesskey().'&action=new',
					\block_exastud\get_string('newperiod', 'block_exastud'), 'get');

$output->footer();
