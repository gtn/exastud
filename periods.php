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

require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_EDIT_PERIODS);

$strperiods = block_exastud_get_string('periods');

block_exastud_check_periods(true);

if (!$periods = $DB->get_records('block_exastudperiod')) {
	redirect('configuration_periods.php?courseid=' . $courseid, block_exastud_get_string('redirectingtoperiodsinput'));
}
$url = '/blocks/exastud/periods.php';
$PAGE->set_url($url);
$output = block_exastud_get_renderer();
echo $output->header(['settings', 'periods']);

/* Print the periods */
$table = new html_table();

$table->head = array(
	block_exastud_get_string('perioddescription'),
	block_exastud_get_string('starttime'),
	block_exastud_get_string('endtime'),
	block_exastud_get_string('action')
);

$table->align = array("left", "left", "left", "right");

foreach($periods as $period) {

	$link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/configuration_periods.php?courseid=' . $courseid . '&amp;periodid=' . $period->id . '&amp;sesskey=' . sesskey() . '&amp;action=edit">';

	$icons = $link.'<img src="pix/edit.png" alt="' . block_exastud_get_string('edit'). '" /></a>
			  <a href="' . $CFG->wwwroot . '/blocks/exastud/configuration_periods.php?courseid=' . $courseid . '&amp;periodid=' . $period->id . '&amp;sesskey=' . sesskey() . '&amp;action=delete"><img src="pix/del.png" alt="' . block_exastud_get_string('delete'). '" /></a> ';

	$starttime = date('d. M. Y - H:i', $period->starttime);
	$endtime = date('d. M. Y - H:i', $period->endtime);
	
	$table->data[] = array ($link.$period->description.'</a>', $starttime, $endtime, $icons);
}

echo $output->table($table);

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_periods.php?courseid='.$courseid.'&sesskey='.sesskey().'&action=new',
					block_exastud_get_string('newperiod'), 'get');

echo $output->footer();
