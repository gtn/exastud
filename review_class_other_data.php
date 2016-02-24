<?php

require __DIR__.'/inc.php';

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$type = required_param('type', PARAM_TEXT);

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_REVIEW);

$class = block_exastud\get_review_class($classid, \block_exastud\SUBJECT_ID_OTHER_DATA);

if(!$class) {
	print_error("badclass","block_exastud");
}

if ($type == \block_exastud\DATA_ID_LERN_UND_SOZIALVERHALTEN) {
	$categories = [
		\block_exastud\DATA_ID_LERN_UND_SOZIALVERHALTEN => \block_exastud\trans('de:Lern- und Sozialverhalten')
	];
	$classheader = $class->title.' - '.\block_exastud\trans('de:Lern- und Sozialverhalten');
} else {
	$categories = [
		'ateliers' => \block_exastud\trans('de:Ateliers'),
		'arbeitsgemeinschaften' => \block_exastud\trans('de:Arbeitsgemeinschaften'),
		'besondere_staerken' => \block_exastud\trans('de:Besondere Stärken'),
	];
	$classheader = $class->title.' - '.\block_exastud\trans('de:Weitere Daten');
}

$output = \block_exastud\get_renderer();

$url = '/blocks/exastud/review_class.php';
$PAGE->set_url($url, [ 'courseid'=>$courseid, 'classid'=>$classid, 'type'=>$type ]);
$output->header(array('review', '='.$classheader));
echo $output->heading($classheader);

$actPeriod = block_exastud_check_active_period();
$classstudents = \block_exastud\get_class_students($classid);
$evaluation_options = block_exastud_get_evaluation_options();

/* Print the Students */
$table = new html_table();

$table->head = array();
$table->head[] = ''; //userpic
$table->head[] = \block_exastud\get_string('name');
$table->head[] = ''; // bewerten button
foreach($categories as $category) {
	$table->head[] = $category;
}

$table->align = array();
$table->align[] = 'center';
$table->align[] = 'left';
$table->align[] = 'center';

foreach($classstudents as $classstudent) {
	$icons = '<img src="' . $CFG->wwwroot . '/pix/i/edit.gif" width="16" height="16" alt="' . \block_exastud\get_string('edit'). '" />';
	$userdesc = fullname($classstudent);

	$data = block_exastud\get_class_student_data($classid, $classstudent->id);

	$row = new html_table_row();
	$row->cells[] = $OUTPUT->user_picture($classstudent,array("courseid"=>$courseid));
	$row->cells[] = $userdesc;

	$row->cells[] = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_student_other_data.php?courseid=' . $courseid . '&classid=' . $classid . '&type=' . $type . '&studentid=' . $classstudent->id . '">'.
		\block_exastud\trans('de:Bearbeiten').'</a>';

	foreach($categories as $dataid=>$category) {
		$row->cells[] = !empty($data[$dataid]) ? $data[$dataid] : '';
	}

	$table->data[] = $row;
}

echo $output->table($table);

echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid]));

$output->footer();
