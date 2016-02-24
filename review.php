<?php

require "inc.php";

$courseid = optional_param('courseid', 1, PARAM_INT);

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_REVIEW);

$url = '/blocks/exastud/review.php';
$PAGE->set_url($url);
$blockrenderer = $PAGE->get_renderer('block_exastud');
block_exastud_print_header('review');

$actPeriod = block_exastud_check_active_period();

$head_teacher_classes = \block_exastud\get_teacher_classes_all();
$reviewclasses = \block_exastud\get_review_classes_tree();

// $lern_und_sozialverhalten_classes = \block_exastud\get_head_teacher_lern_und_sozialverhalten_classes();

if(!$reviewclasses) {
	echo \block_exastud\get_string('noclassestoreview','block_exastud');
}
else {
	foreach ($reviewclasses as $myclass) {
		$table = new html_table();

		$table->head = array($myclass->title);

		$table->align = array("left");
		$table->width = "90%";

		$edit_link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid . '&amp;classid=' . $myclass->classid . '&amp;subjectid=' . $myclass->subjectid . '">';

		$table->data[] = array($edit_link.$myclass->title.($myclass->subject?' - '.$myclass->subject:'').'</a>');
		echo $blockrenderer->print_esr_table($table);
	}

}
block_exastud_print_footer();
