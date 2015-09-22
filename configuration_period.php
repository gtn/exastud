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
require_once($CFG->dirroot . '/blocks/exastud/lib/edit_form.php');
global $DB;

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$periodid = optional_param('periodid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

require_login($courseid);

$context = context_system::instance();
//$context = get_context_instance(CONTEXT_COURSE,$courseid);
require_capability('block/exastud:use', $context);
require_capability('block/exastud:editperiods', $context);

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
		//add_to_log($courseid, 'block_exastud', 'new', 'configuration_period.php?courseid=' . $courseid . '&action=new', '');
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
	$period->endtime = time();
	$period->id = 0;
}



$url = '/blocks/exastud/configuration_period.php';
$PAGE->set_url($url);
block_exastud_print_header(array('periods', 'periodinput'));

echo "<br/>";
$periodform->set_data($period);
$periodform->display();

block_exastud_print_footer();
