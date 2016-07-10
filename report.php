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

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$periodid = optional_param('periodid', 0, PARAM_INT); // Period ID

if (!empty($CFG->block_exastud_project_based_assessment)) {
	redirect('report_project.php?courseid='.$courseid);
}

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_VIEW_REPORT);

$output = block_exastud\get_renderer();

$url = '/blocks/exastud/report.php';
$PAGE->set_url($url);


if ($classid = optional_param('classid', 0, PARAM_INT)) {
	$class = block_exastud\get_teacher_class($classid);
	$categories = ($periodid == 0 || $periodid == block_exastud_check_active_period()->id) ? block_exastud_get_class_categories($class->id) : block_exastud_get_period_categories($periodid);

	if (!$classstudents = block_exastud\get_class_students($class->id)) {
		echo $output->header('report');
		echo $output->heading(\block_exastud\trans(['de:Keine Schüler gefunden', 'en:No students found']));
		echo $output->back_button(new moodle_url('report.php', ['courseid' => $courseid]));
		echo $output->footer();
		exit;
	}

	/* Print the Students */
	$table = new html_table();

	$table->head = array();
	$table->head[] = '#'; //userpic
	$table->head[] = ''; //userpic
	$table->head[] = \block_exastud\get_string('name');
	$table->head[] = ''; //action
	foreach ($categories as $category) {
		$table->head[] = $category->title;
	}

	$table->align = array();
	$table->align[] = 'center';
	$table->align[] = 'center';
	$table->align[] = 'left';
	$table->align[] = 'center';
	for ($i = 0; $i < count($categories); $i++) {
		$table->align[] = 'center';
	}

	$i = 1;
	foreach ($classstudents as $classstudent) {
		$userReport = block_exastud_get_report($classstudent->id, $class->periodid);

		// $link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/report_student.php?courseid=' . $courseid . '&amp;studentid=' . $classstudent->id . '&periodid='.$periodid.'&classid='.$class->id.'">';
		// $icons = $link.'<img src="' . $CFG->wwwroot . '/blocks/exastud/pix/print.png" width="16" height="16" alt="' . \block_exastud\get_string('printversion', 'block_exastud'). '" /></a>';

		if (!empty($CFG->block_exastud_detailed_review)) {
			$link = '<a href="'.$CFG->wwwroot.'/blocks/exastud/report_student.php?courseid='.$courseid.'&amp;studentid='.$classstudent->id.'&periodid='.$periodid.'&classid='.$class->id.'">';
			$icons .= $link.'<img src="'.$CFG->wwwroot.'/blocks/exastud/pix/print_detail.png" width="16" height="16" alt="'.\block_exastud\get_string('printversion', 'block_exastud').'" /></a>';
		}
		//$link = '<a href="' . $CFG->wwwroot . '/blocks/exastud/printstudent.php?courseid=' . $courseid . '&amp;studentid=' . $classstudent->id . '&amp;sesskey=' . sesskey() . '&periodid='.$periodid.'&pdf=true">';
		//$icons .= $link.'<img src="' . $CFG->wwwroot . '/blocks/exastud/pix/pdf.png" width="23" height="16" alt="' . \block_exastud\get_string('printversion', 'block_exastud'). '" /></a>';

		$studentdesc = fullname($classstudent);
		//$studentdesc = print_user_picture($classstudent->id, $courseid, $classstudent->picture, 0, true, false) . ' ' . $link.fullname($classstudent, $classstudent->id).'</a>';

		//$table->data[] = array($studentdesc, $userReport->team, $userReport->resp, $userReport->inde, $icons);

		$data = array();
		$data[] = $i++;
		$data[] = $OUTPUT->user_picture($classstudent, array("courseid" => $courseid));
		$data[] = $studentdesc;

		$data[] = $output->link_button($CFG->wwwroot.'/blocks/exastud/report_student.php?courseid='.$courseid.'&classid='.$classid.'&studentid='.$classstudent->id,
				\block_exastud\trans('de:Alle Bewertungen zeigen')).
			$output->link_button($CFG->wwwroot.'/blocks/exastud/report_student.php?courseid='.$courseid.'&classid='.$classid.'&studentid='.$classstudent->id.'&output=docx',
				\block_exastud\trans('de:Lernentwicklungsbericht'));

		foreach ($categories as $category) {
			$data[] = @$userReport->category_averages[$category->source.'-'.$category->id];
		}

		$table->data[] = $data;
	}

	echo $output->header('report');

	echo $output->table($table);

	if (block_exastud_is_new_version()) {
		echo $output->link_button($CFG->wwwroot.'/blocks/exastud/report_student.php?courseid='.$courseid.'&classid='.$classid.'&all_students=1',
			\block_exastud\trans('de:Alle Lernentwicklungsberichte als Zip-Datei exportieren'));
	} else {
		echo '<a href="'.$CFG->wwwroot.'/blocks/exastud/printclass.php?courseid='.$courseid.'&amp;classid='.$class->id.'&periodid='.$periodid.'"><img src="'.$CFG->wwwroot.'/blocks/exastud/pix/print.png" width="16" height="16" alt="'.\block_exastud\get_string('printall', 'block_exastud').'" /></a>';
		echo '<a href="'.$CFG->wwwroot.'/blocks/exastud/printclass.php?courseid='.$courseid.'&amp;classid='.$class->id.'&periodid='.$periodid.'&detailedreport=true"><img src="'.$CFG->wwwroot.'/blocks/exastud/pix/print_detail.png" width="16" height="16" alt="'.\block_exastud\get_string('printall', 'block_exastud').'" /></a>';

		echo '<form name="periodselect" action="'.$CFG->wwwroot.$url.'?courseid='.$courseid.'" method="POST">
		<select name="periodid" onchange="this.form.submit();">';
		foreach ($DB->get_records('block_exastudperiod', null, 'endtime desc') as $period) {
			$select = ($period->id == $periodid) ? " selected " : "";
			echo '<option value="'.$period->id.'"'.$select.'>'.$period->description.'</option>';
		}
		echo '</select></form>';
	}

	echo $output->footer();
} else {
	echo $output->header('report');

	$periods = $DB->get_records_sql('SELECT * FROM {block_exastudperiod} WHERE (starttime <= '.time().') ORDER BY endtime DESC');

	foreach ($periods as $period) {
		$classes = block_exastud\get_head_teacher_classes_all($period->id);

		$table = new html_table();

		$table->head = [$period->description];
		$table->align = array("left");

		if (!$classes) {
			$table->data[] = [
				block_exastud\trans('de:Keine Klassen gefunden'),
			];
		} else {
			foreach ($classes as $class) {
				$table->data[] = [
					'<a href="report.php?courseid='.$courseid.'&classid='.$class->id.'">'.$class->title.'</a>',
				];
			}
		}

		echo $output->table($table);
	}

	echo $output->footer();
}
