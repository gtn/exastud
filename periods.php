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

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_EDIT_PERIODS);

$strperiods = block_exastud_get_string('periods');

block_exastud_check_periods(true);

if (!$periods = $DB->get_records('block_exastudperiod', [], 'starttime DESC, endtime DESC')) {
	redirect('configuration_periods.php?courseid='.$courseid, block_exastud_get_string('redirectingtoperiodsinput'));
}
$url = '/blocks/exastud/periods.php';
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin'); // Needed for admin menu block

$output = block_exastud_get_renderer();
block_exastud_custom_breadcrumb($PAGE);
echo $output->header(['periods'], ['content_title' => block_exastud_get_string('pluginname')], true/*['settings', 'periods']*/);

/* Print the periods */
$table = new html_table();

$table->head = array(
	block_exastud_get_string('perioddescription'),
	block_exastud_get_string('starttime'),
	block_exastud_get_string('endtime'),
//	block_exastud_get_string('certificate_issue_date'),
	block_exastud_get_string('action'),
);

$table->align = array("left", "left", "left", /*"left",*/ "right");

$actPeriod = block_exastud_get_active_period();
if (!$actPeriod) { // if no any active period or more than one
    echo $output->notification(block_exastud_get_string('periods_incorrect'), 'notifyerror');
}

foreach ($periods as $period) {
	$editUrl = $CFG->wwwroot.'/blocks/exastud/configuration_periods.php?courseid='.$courseid.'&periodid='.$period->id.'&sesskey='.sesskey().'&action=edit';

	$icons = $output->link_button($editUrl,
		'<img src="pix/edit.png" alt="'.block_exastud_get_string('edit').'" />');
	$icons .= $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_periods.php?courseid='.$courseid.'&periodid='.$period->id.'&sesskey='.sesskey().'&action=delete',
		'<img src="pix/del.png" alt="'.block_exastud_get_string('delete').'" />',
		['exa-confirm' => block_exastud_get_string('delete_confirmation', null, $period->description)]
	);

	$starttime = userdate($period->starttime, block_exastud_get_string('strftimedatetime', 'langconfig'));
	$endtime = userdate($period->endtime, block_exastud_get_string('strftimedatetime', 'langconfig'));

	$table->data[] = [
		'<a href="'.$editUrl.'">'.($actPeriod && $period->id == $actPeriod->id ? '<b>' : '').$period->description.'</a>',
		$starttime,
		$endtime,
//		$period->certificate_issue_date ? block_exastud_format_certificate_issue_date($period->certificate_issue_date) : '',
		$icons,
	];
}

echo $output->table($table);

echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/exastud/configuration_periods.php?courseid='.$courseid.'&sesskey='.sesskey().'&action=new',
	block_exastud_get_string('newperiod'), 'get');

echo $output->footer();
