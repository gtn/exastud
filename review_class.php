<?php

require "inc.php"
;

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$subjectid = required_param('subjectid', PARAM_INT);

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

$output = \block_exastud\get_renderer();

$url = '/blocks/exastud/review_class.php';
$PAGE->set_url($url);
$classheader = $class->title.($class->subject?' - '.$class->subject:'');
block_exastud_print_header(array('review', '='.$classheader));

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

foreach($categories as $category)
	$table->head[] = $category->title;

$table->align = array();
$table->align[] = 'center';
$table->align[] = 'left';

$table->align[] = 'center';

for($i=0;$i<=count($categories);$i++)
	$table->align[] = 'center';

$table->align[] = 'left';
$table->align[] = 'right';

$table->width = "90%";

$oddeven = true;
foreach($classusers as $classuser) {
	$user = $DB->get_record('user', array('id'=>$classuser->studentid));
	if (!$user)
		continue;
	
	$icons = '<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . \block_exastud\get_string('edit'). '" />';
	$userdesc = fullname($user, $user->id);
	
	$report = $DB->get_record('block_exastudreview', array('teacherid'=>$teacherid, 'subjectid'=>$subjectid, 'periodid'=>$actPeriod->id, 'studentid'=>$user->id));
	$row = new html_table_row();
	$row->cells[] = $OUTPUT->user_picture($user,array("courseid"=>$courseid));
	$row->cells[] = $userdesc;
	
	$row->cells[] = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_student.php?courseid=' . $courseid . '&classid=' . $classid . '&subjectid=' . $subjectid . '&studentid=' . $user->id . '">'.
		\block_exastud\trans('de:Bewerten').'</a>';
	
	if($report) {
		foreach($categories as $category) {
			$bewertung = $DB->get_field('block_exastudreviewpos', 'value', array("categoryid"=>$category->id,"reviewid"=>$report->id,"categorysource"=>$category->source));
			$row->cells[] = $bewertung && isset($evaluation_options[$bewertung]) ? $evaluation_options[$bewertung] : '';
		}
	}
	else {
		for($i=0;$i<count($categories);$i++)
			$row->cells[] = '';
	}
	
	$oddeven = !$oddeven;
	$row->oddeven = $oddeven;
	$table->data[] = $row;

	if ($report) {
		$cell = new html_table_cell();
		$cell->text = $report->review;
		$cell->colspan = count($categories);
		$cell->style = 'text-align: left;';
		$row = new html_table_row(array(
			'', '', '', $cell
		));
		$row->oddeven = $oddeven;
		$table->data[] = $row;
	}
}

echo $output->print_esr_table($table);
echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));

block_exastud_print_footer();
