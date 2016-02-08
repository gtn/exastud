<?php

require "inc.php";

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

if ($subjectid == block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN && $class->type == 'shared') {
	// for shared classes load common review data
	$teacherid = $class->userid;
} else {
	$teacherid = $USER->id;
}

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

$output = \block_exastud\get_renderer();

$url = '/blocks/exastud/review_class.php';
$PAGE->set_url($url, [ 'courseid'=>$courseid, 'classid'=>$classid, 'subjectid'=>$subjectid ]);
$classheader = $class->title.($class->subject?' - '.$class->subject:'');
block_exastud_print_header(array('review', '='.$classheader));
echo $output->heading($classheader);

$actPeriod = block_exastud_check_active_period();

if(!$classusers = $DB->get_records('block_exastudclassstudents', array('classid'=>$classid))) {
	echo $output->heading(\block_exastud\get_string('nostudentstoreview'));
	echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));
	block_exastud_print_footer();
	exit;
}

if ($subjectid == block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN) {
	$categories = [(object)[ 'title' => \block_exastud\trans('Lern- und Sozialverhalten'), 'id'=>0, 'source'=>'']];
} else {
	$categories = block_exastud_get_class_categories($classid);
}
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

$table->width = "90%";

$hiddenclassusers = [];
$oddeven = true;
foreach($classusers as $classuser) {
	$user = $DB->get_record('user', array('id'=>$classuser->studentid));
	if (!$user)
		continue;

	$can_toggle_visibility = ($subjectid != \block_exastud\SUBJECT_ID_LERN_UND_SOZIALVERHALTEN);
	if (!$can_toggle_visibility) {
		$visible = true;
	} else {
		$visible = $DB->get_field('block_exastudclassteastudvis', 'visible', [
			'classteacherid' => $class->classteacherid,
			'studentid' => $classuser->studentid,
		]);
		if ($visible === false) {
			$visible = true;
		}
	}

	if ($visible !== false && !$visible) {
		// hidden
		$classuser->user = $user;
		$hiddenclassusers[] = $classuser;
		continue;
	}

	$icons = '<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . \block_exastud\get_string('edit'). '" />';
	$userdesc = fullname($user, $user->id);

	$report = $DB->get_record('block_exastudreview', array('teacherid'=>$teacherid, 'subjectid'=>$subjectid, 'periodid'=>$actPeriod->id, 'studentid'=>$user->id));
	$row = new html_table_row();
	$row->cells[] = $OUTPUT->user_picture($user,array("courseid"=>$courseid));
	$row->cells[] = $userdesc;

	if ($can_toggle_visibility) {
		if ($visible) {
			$show_hide_url = block_exastud\url::create($PAGE->url, [ 'action'=>'hide_student', 'studentid' => $classuser->studentid]);
			$show_hide_icon = $OUTPUT->pix_icon('i/hide', block_exastud\get_string('hide'));
		} else {
			$show_hide_url = block_exastud\url::create($PAGE->url, [ 'action'=>'show_student', 'studentid' => $classuser->studentid]);
			$show_hide_icon = $OUTPUT->pix_icon('i/show', block_exastud\get_string('show'));
		}
		$row->cells[] = '<a style="padding-right: 15px;" href="'.$show_hide_url.'">'.$show_hide_icon.'</a>';
	} else {
		$row->cells[] = '';
	}

	$row->cells[] = ($visible ? '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_student.php?courseid=' . $courseid . '&classid=' . $classid . '&subjectid=' . $subjectid . '&studentid=' . $user->id . '">'.
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

	$oddeven = !$oddeven;
	$row->oddeven = $oddeven;
	$table->data[] = $row;

	if ($visible && $report) {
		$cell = new html_table_cell();
		$cell->text = $report->review;
		$cell->colspan = count($categories);
		$cell->style = 'text-align: left;';
		$row = new html_table_row(array(
			'', '', '', '', $cell
		));
		$row->oddeven = $oddeven;
		$table->data[] = $row;
	}
}

echo $output->print_esr_table($table);

if ($hiddenclassusers) {
	echo $output->heading(block_exastud\trans('de:Ausgeblendete Schüler'));

	$table = new html_table();

	$table->head = array();
	$table->head[] = ''; //userpic
	$table->head[] = \block_exastud\get_string('name');
	$table->head[] = ''; //buttons

	$table->align = array();
	$table->align[] = 'center';
	$table->align[] = 'left';

	$oddeven = true;

	foreach ($hiddenclassusers as $classuser) {
		$user = $classuser->user;
		$icons = '<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . \block_exastud\get_string('edit'). '" />';
		$userdesc = fullname($user, $user->id);

		$row = new html_table_row();

		$row->cells[] = $OUTPUT->user_picture($user,array("courseid"=>$courseid));
		$row->cells[] = $userdesc;

		$show_hide_url = block_exastud\url::create($PAGE->url, [ 'action'=>'show_student', 'studentid' => $classuser->studentid]);
		$show_hide_icon = $OUTPUT->pix_icon('i/show', block_exastud\get_string('show'));

		$row->cells[] =
			'<a style="padding-right: 15px;" href="'.$show_hide_url.'">'.$show_hide_icon.'</a>';

		$oddeven = !$oddeven;
		$row->oddeven = $oddeven;
		$table->data[] = $row;
	}

	echo $output->print_esr_table($table);
}

echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));

block_exastud_print_footer();
