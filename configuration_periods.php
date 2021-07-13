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

$url = '/blocks/exastud/configuration_periods.php';
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin'); // Needed for admin menu block

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_EDIT_PERIODS);

$periodform = new period_edit_form();

//Form processing and displaying is done here
if ($periodform->is_cancelled()) {
	redirect('periods.php?courseid=' . $courseid);
} else if ($periodedit = $periodform->get_data()) {
	require_sesskey();

	$newperiod = new stdClass();
	$newperiod->timemodified = time();
	$newperiod->userid=$USER->id;
	$newperiod->description = $periodedit->description;
	$newperiod->starttime = $periodedit->starttime;
	$newperiod->endtime = $periodedit->endtime;
	// TODO: delete this field
	$newperiod->certificate_issue_date = 0; //$periodedit->certificate_issue_date;
	
	if(isset($periodedit->id) && ($periodedit->action == 'edit')) {
		$newperiod->id = $periodedit->id;
        $oldPeriodData = $DB->get_record('block_exastudperiod', array('id'=>$newperiod->id));
		
		$DB->update_record('block_exastudperiod', $newperiod);
        \block_exastud\event\period_updated::log(['objectid' => $newperiod->id,
                                                    'other' => [
                                                            'perioddata' => serialize($newperiod),
                                                            'oldperioddata' => serialize($oldPeriodData)]]);
	}
	else if($periodedit->action == 'new') {
		$newid = $DB->insert_record('block_exastudperiod', $newperiod);
        \block_exastud\event\period_created::log(['objectid' => $newid, 'other' => ['perioddata' => serialize($newperiod)]]);
	}

	redirect('periods.php?courseid=' . $courseid);
}

$period = new stdClass();
$period->courseid = $courseid;
if($action == 'edit') {
	require_sesskey();

	if (!$period = $DB->get_record('block_exastudperiod', array('id'=>$periodid))) {
		error("invalidperiodid","block_exastud");
	}
	$period->action = 'edit';
	$period->courseid = $courseid;
}
else if($action == 'delete') {
	require_sesskey();
    $periodData = $DB->get_record('block_exastudperiod', array('id'=>$periodid));
	$DB->delete_records('block_exastudperiod', array('id'=>$periodid));
    \block_exastud\event\period_deleted::log(['objectid' => $periodid, 'other' => ['perioddata' => serialize($periodData)]]);
	redirect('periods.php?courseid=' . $courseid);
}
else {
	$period->action = 'new';
	$period->description = '';
	$period->starttime = time();
	// make the default period one month long
	$period->endtime = mktime(0,0,0,date('m')+1, date('d'), date('Y'));
	$period->id = 0;
}



$output = block_exastud_get_renderer();
block_exastud_custom_breadcrumb($PAGE);
echo $output->header(['periods'], ['content_title' => block_exastud_get_string('pluginname')], true);

echo "<br/>";
$periodform->set_data($period);
$periodform->display();

echo $output->footer();
