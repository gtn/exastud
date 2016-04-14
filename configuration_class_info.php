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
global $DB, $OUTPUT, $PAGE;

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = optional_param('classid', 0, PARAM_INT); // Course ID

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_MANAGE_CLASSES);
$curPeriod = block_exastud_check_active_period();

if (!$classid) {
	$class = new stdClass();
	$class->id = 0;
	$class->title = '';
} else {
	$class = block_exastud\get_teacher_class($classid);
}
$class->classid = $class->id;
$class->courseid = $courseid;

$classform = new class_edit_form();
if ($classform->is_cancelled()) {
	redirect('configuration_classes.php?courseid=' . $courseid);
} else if ($classedit = $classform->get_data()) {
	if(!confirm_sesskey()) {
		print_error("badsessionkey","block_exastud");
	}
	
	$newclass = new stdClass();
	$newclass->timemodified = time();
	$newclass->title = $classedit->title;

	if ($class->id) {
		$newclass->id = $class->id;
		$DB->update_record('block_exastudclass', $newclass);
	} else {
		$newclass->userid = $USER->id;
		$newclass->periodid = $curPeriod->id;
		$class->id = $DB->insert_record('block_exastudclass', $newclass);
	}

	$DB->delete_records('block_exastudclassteachers', ['teacherid' => $USER->id, 'classid' => $class->id]);
	if (!empty($classedit->mysubjectids)) {
		foreach ($classedit->mysubjectids as $subjectid) {
			$DB->insert_record('block_exastudclassteachers ', [
				'teacherid' => $USER->id,
				'classid' => $class->id,
				'timemodified' => time(),
				'subjectid' => $subjectid,
			]);
		}
	}

	redirect('configuration_class.php?courseid=' . $courseid.'&classid='.$class->id);
}

$url = "/blocks/exastud/configuration_class_info.php";
$PAGE->set_url($url);
$output = block_exastud\get_renderer();
echo $output->header(array('configuration_classes', 'editclassname'));

// TODO: two divs? -- daniel
echo '<div id="block_exastud">';
echo $OUTPUT->box( text_to_html(\block_exastud\get_string("explainclassname","block_exastud")));
echo $OUTPUT->heading($class->title);

$class->mysubjectids = $DB->get_records_menu('block_exastudclassteachers', ['teacherid' => $USER->id, 'classid' => $class->id], null, 'subjectid, subjectid AS tmp');
$classform->set_data($class);
$classform->display();

echo '</div>';
echo $output->footer();