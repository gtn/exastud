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
require_once($CFG->dirroot . '/blocks/exastud/lib/edit_form.php');

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$periodid = optional_param('periodid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_EDIT_PERIODS);

ob_start();
$periodform = new period_edit_form();
// bug in moodle forms lib, date_time_selector outputs utf8 bom characters
ob_clean();

//Form processing and displaying is done here
if ($periodform->is_cancelled()) {
	redirect('periods.php?courseid=' . $courseid);
} else if ($periodedit = $periodform->get_data()) {
	if(!confirm_sesskey()) {
		error("badsessionkey","block_exastud");
	}
	
	
	$newperiod = new stdClass();
	$newperiod->timemodified = time();
	$newperiod->userid=$USER->id;
	$newperiod->description = $periodedit->description;
	$newperiod->starttime = $periodedit->starttime;
	$newperiod->endtime = $periodedit->endtime;
	
	if(isset($periodedit->id) && ($periodedit->action == 'edit')) {
		$newperiod->id = $periodedit->id;
		
		if (!$DB->update_record('block_exastudperiod', $newperiod)) {
			error('errorupdateingperiod', 'block_exastud');
		}
	}
	else if($periodedit->action == 'new') {
		if (!($DB->insert_record('block_exastudperiod', $newperiod))) {
			error('errorinsertingperiod', 'block_exastud');
		}
		//add_to_log($courseid, 'block_exastud', 'new', 'configuration_periods.php?courseid=' . $courseid . '&action=new', '');
	}

	redirect('periods.php?courseid=' . $courseid);
}

$period = new stdClass();
$period->courseid = $courseid;
if($action == 'edit') {
	if(!confirm_sesskey()) {
		error("badsessionkey","block_exastud");
	}
	if (!$period = $DB->get_record('block_exastudperiod', array('id'=>$periodid))) {
		error("invalidperiodid","block_exastud");
	}
	$period->action = 'edit';
	$period->courseid = $courseid;
}
else if($action == 'delete') {
	if(!confirm_sesskey()) {
		error("badsessionkey","block_exastud");
	}
	$DB->delete_records('block_exastudperiod', array('id'=>$periodid));
	redirect('periods.php?courseid=' . $courseid);
}
else {
	$period->action = 'new';
	$period->description = '';
	$period->starttime = time();
	// make the deafult period one month long
	$period->endtime = mktime(0,0,0,date('m')+1, date('d'), date('Y'));
	$period->id = 0;
}



$url = '/blocks/exastud/configuration_periods.php';
$PAGE->set_url($url);
$output = block_exastud\get_renderer();
echo $output->header(array('settings', 'periods'));

echo "<br/>";
$periodform->set_data($period);
$periodform->display();

echo $output->footer();
