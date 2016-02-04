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

$reviewclasses = \block_exastud\get_review_classes();

$lern_und_sozialverhalten_classes = \block_exastud\get_head_teacher_lern_und_sozialverhalten_classes();

if(!$lern_und_sozialverhalten_classes && !$reviewclasses) {
	echo \block_exastud\get_string('noclassestoreview','block_exastud');
}
else {
	if ($lern_und_sozialverhalten_classes) {
		/* Print the Students */
		$table = new html_table();

		$table->head = array(\block_exastud\trans('Lern- und Sozialverhalten'));

		$table->align = array("left");
		$table->width = "90%";

		foreach ($lern_und_sozialverhalten_classes as $myclass) {
			$edit_link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid . '&amp;classid=' . $myclass->classid . '&amp;subjectid=' . $myclass->subjectid . '">';

			$table->data[] = array($edit_link.$myclass->title.($myclass->subject?' - '.$myclass->subject:'').'</a>');
		}

		echo $blockrenderer->print_esr_table($table);
	}

	if ($reviewclasses) {
		/* Print the Students */
		$table = new html_table();

		$table->head = array(block_exastud\get_string('review'));

		$table->align = array("left");
		$table->width = "90%";

		foreach ($reviewclasses as $myclass) {
			$edit_link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid . '&amp;classid=' . $myclass->classid . '&amp;subjectid=' . $myclass->subjectid . '">';

			$table->data[] = array($edit_link.$myclass->title.($myclass->subject?' - '.$myclass->subject:'').'</a>');
		}

		echo $blockrenderer->print_esr_table($table);
	}
}
block_exastud_print_footer();
