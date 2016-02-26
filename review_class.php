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

use block_exastud\globals as g;

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$subjectid = required_param('subjectid', PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_REVIEW);

$class = block_exastud\get_review_class($classid, $subjectid);

if(!$class) {
	print_error("badclass","block_exastud");
}

$teacherid = $USER->id;

if ($action == 'hide_student') {
	g::$DB->insert_or_update_record('block_exastudclassteastudvis', [
		'visible' => 0,
	], [
		'classteacherid' => $class->classteacherid,
		'studentid' => required_param('studentid', PARAM_INT),
	]);
} elseif ($action == 'show_student') {
	g::$DB->delete_records('block_exastudclassteastudvis', [
		'classteacherid' => $class->classteacherid,
		'studentid' => required_param('studentid', PARAM_INT),
	]);
}

$url = '/blocks/exastud/review_class.php';
$PAGE->set_url($url, [ 'courseid'=>$courseid, 'classid'=>$classid, 'subjectid'=>$subjectid ]);
$classheader = $class->title.($class->subject_title?' - '.$class->subject_title:'');

$output = \block_exastud\get_renderer();
echo $output->header(array('review', '='.$classheader));
echo $output->heading($classheader);

$actPeriod = block_exastud_check_active_period();


if (!$classstudents = \block_exastud\get_class_students($classid)) {
	echo $output->heading(\block_exastud\get_string('nostudentstoreview'));
	echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));
	echo $output->footer();
	exit;
}

$categories = block_exastud_get_class_categories($classid);
$evaluation_options = block_exastud_get_evaluation_options();

/* Print the Students */
$table = new html_table();

$table->head = array();
$table->head[] = ''; //userpic
$table->head[] = \block_exastud\get_string('name');
$table->head[] = ''; // bewerten button
$table->head[] = ''; // bewerten button

foreach($categories as $category)
	$table->head[] = $category->title;

$table->align = array();
$table->align[] = 'center';
$table->align[] = 'left';

$table->align[] = 'center';
$table->align[] = 'center';

for($i=0;$i<=count($categories);$i++)
	$table->align[] = 'center';

$table->align[] = 'left';
$table->align[] = 'right';

$hiddenclassstudents = [];
$oddeven = false;
foreach($classstudents as $classstudent) {
	$visible = $DB->get_field('block_exastudclassteastudvis', 'visible', [
		'classteacherid' => $class->classteacherid,
		'studentid' => $classstudent->id,
	]);
	if ($visible === false) {
		$visible = true;
	}

	if ($visible !== false && !$visible) {
		// hidden
		$hiddenclassstudents[] = $classstudent;
		continue;
	}

	$icons = '<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . \block_exastud\get_string('edit'). '" />';

	$report = $DB->get_record('block_exastudreview', array('teacherid'=>$teacherid, 'subjectid'=>$subjectid, 'periodid'=>$actPeriod->id, 'studentid'=>$classstudent->id));
	$row = new html_table_row();
	$row->cells[] = $output->user_picture($classstudent,array("courseid"=>$courseid));
	$row->cells[] = fullname($classstudent);

	if ($visible) {
		$show_hide_url = block_exastud\url::create($PAGE->url, [ 'action'=>'hide_student', 'studentid' => $classstudent->id]);
		$show_hide_icon = $OUTPUT->pix_icon('i/hide', block_exastud\get_string('hide'));
	} else {
		$show_hide_url = block_exastud\url::create($PAGE->url, [ 'action'=>'show_student', 'studentid' => $classstudent->id]);
		$show_hide_icon = $OUTPUT->pix_icon('i/show', block_exastud\get_string('show'));
	}
	$row->cells[] = '<a style="padding-right: 15px;" href="'.$show_hide_url.'">'.$show_hide_icon.'</a>';

	$row->cells[] = ($visible ? '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_student.php?courseid=' . $courseid . '&classid=' . $classid . '&subjectid=' . $subjectid . '&studentid=' . $classstudent->id . '">'.
		\block_exastud\trans('de:Bewerten').'</a>' : '');

	/* if (!$visible) {
		$cell = new html_table_cell();
		$cell->text = '';
		$cell->colspan = count($categories);
		$row->cells[] = $cell;
	} else */
	if($report) {
		foreach($categories as $category) {
			$bewertung = $DB->get_field('block_exastudreviewpos', 'value', array("categoryid"=>$category->id,"reviewid"=>$report->id,"categorysource"=>$category->source));
			$row->cells[] = $bewertung && isset($evaluation_options[$bewertung]) ? $evaluation_options[$bewertung] : '';
		}
	} else {
		for($i=0;$i<count($categories);$i++)
			$row->cells[] = '';
	}

	$row->attributes['class'] = 'oddeven'.(int)$oddeven;
	$oddeven = !$oddeven;
	$table->data[] = $row;

	if ($visible && $report) {
		$cell = new html_table_cell();
		$cell->text = $report->review;
		$cell->colspan = count($categories);
		$cell->style = 'text-align: left;';
		$row = new html_table_row(array(
			'', '', '', '', $cell
		));
		$row->attributes['class'] = 'oddeven'.(int)$oddeven;
		$table->data[] = $row;
	}
}

echo $output->table($table);

if ($hiddenclassstudents) {
	echo $output->heading(block_exastud\trans('de:Ausgeblendete Schüler'));

	$table = new html_table();

	$table->head = array();
	$table->head[] = ''; //userpic
	$table->head[] = \block_exastud\get_string('name');
	$table->head[] = ''; //buttons

	$table->align = array();
	$table->align[] = 'center';
	$table->align[] = 'left';

	foreach ($hiddenclassstudents as $classstudent) {
		$icons = '<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . \block_exastud\get_string('edit'). '" />';

		$row = new html_table_row();

		$row->cells[] = $output->user_picture($classstudent,array("courseid"=>$courseid));
		$row->cells[] = fullname($classstudent);

		$show_hide_url = block_exastud\url::create($PAGE->url, [ 'action'=>'show_student', 'studentid' => $classstudent->id]);
		$show_hide_icon = $output->pix_icon('i/show', block_exastud\get_string('show'));

		$row->cells[] =
			'<a style="padding-right: 15px;" href="'.$show_hide_url.'">'.$show_hide_icon.'</a>';

		$table->data[] = $row;
	}

	echo $output->table($table);
}

echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));

echo $output->footer();
