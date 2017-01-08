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

	if (!$classstudents = block_exastud\get_class_students($class->id)) {
		echo $output->header('report');
		echo $output->heading(\block_exastud\trans(['de:Keine Schüler gefunden', 'en:No students found']));
		echo $output->back_button(new moodle_url('report.php', ['courseid' => $courseid]));
		echo $output->footer();
		exit;
	}

	if ($template = optional_param('template', '', PARAM_TEXT)) {
		$studentids = \block_exastud\param::optional_array('studentids', PARAM_INT);

		$printStudents = [];
		foreach ($studentids as $studentid) {
			if (isset($classstudents[$studentid])) {
				$printStudents[] = $classstudents[$studentid];
			}
		}

		if ($printStudents && $template == 'html_report') {
			echo $output->header('report');

			foreach ($printStudents as $student) {
				$textReviews = \block_exastud\get_text_reviews($class, $student->id);
				$categories = \block_exastud\get_class_categories_for_report($student->id, $class->id);

				$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)).' '.fullname($student);
				echo $output->heading($studentdesc);

				echo $output->print_student_report($categories, $textReviews);

				echo '<hr>';
			}

			echo $output->back_button(new moodle_url('report.php', ['courseid' => $courseid, 'classid' => $classid]));
			echo $output->footer();
			exit;
		}

		if (!$printStudents) {
			// do nothing
		} elseif (count($printStudents) == 1) {
			// print one student
			$student = reset($printStudents);
			$file = \block_exastud\printer::report_to_temp_file($class, $student, $template);

			if ($content = ob_get_clean()) {
				throw new \Exception('there was some other output: '.$content);
			}

			require_once $CFG->dirroot.'/lib/filelib.php';
			send_temp_file($file->temp_file, $file->filename);

			exit;
		} else {
			$zipfilename = tempnam($CFG->tempdir, "zip");
			$zip = new \ZipArchive();
			$zip->open($zipfilename, \ZipArchive::OVERWRITE);

			$temp_files = [];

			foreach ($printStudents as $student) {
				$file = \block_exastud\printer::report_to_temp_file($class, $student, $template);
				$zip->addFile($file->temp_file, $file->filename);
				$temp_files[] = $file->temp_file;
			}

			$zip->close();

			// bug in zip?!? first close the zip and then we can delete the temp files
			foreach ($temp_files as $temp_file) {
				unlink($temp_file);
			}

			$certificate_issue_date = trim(get_config('exastud', 'certificate_issue_date'));
			$filename = ($certificate_issue_date ?: date('Y-m-d'))."-Lernentwicklungsbericht-{$class->title}.zip";

			require_once $CFG->dirroot.'/lib/filelib.php';
			send_temp_file($zipfilename, $filename);
			exit;
		}
	}

	/* Print the Students */
	$table = new html_table();

	$table->head = array();
	$table->head[] = '<input type="checkbox" name="checkallornone"/>';
	$table->head[] = '';
	$table->head[] = '';
	$table->head[] = \block_exastud\get_string('name');

	$table->size = ['5%', '5%', '5%'];

	$table->align = array();
	$table->align[] = 'center';
	$table->align[] = 'center';
	$table->align[] = 'center';
	$table->align[] = 'left';

	$i = 1;
	foreach ($classstudents as $classstudent) {
		$studentdesc = fullname($classstudent);

		$data = array();
		$data[] = '<input type="checkbox" name="studentids[]" value="'.$classstudent->id.'"/>';
		$data[] = $i++;
		$data[] = $OUTPUT->user_picture($classstudent, array("courseid" => $courseid));
		$data[] = $studentdesc;

		$table->data[] = $data;
	}

	echo $output->header('report');

	echo '<form method="post">';

	$bp = $DB->get_record('block_exastudbp', ['id' => $class->bpid]);

	echo '<select name="template">';
	echo '<option value="">--- Vorlage wählen ---</option>';
	echo '<option value="html_report">Ausgabe am Bildschirm</option>';
	echo '<option value="Deckblatt und 1. Innenseite LEB">Deckblatt und 1. Innenseite LEB</option>';

	if ($bp->sourceinfo !== 'bw-bp2016') {
		echo '<option value="Lernentwicklungsbericht alter BP 1.HJ">Lernentwicklungsbericht alter BP 1.HJ</option>';
	}
	if ($bp->sourceinfo !== 'bw-bp2004') {
		echo '<option value="Lernentwicklungsbericht neuer BP 1.HJ">Lernentwicklungsbericht neuer BP 1.HJ</option>';
	}
	echo '<option value="Anlage zum Lernentwicklungsbericht">Anlage zum Lernentwicklungsbericht</option>';
	echo '</select>';

	echo $output->table($table);

	echo '<input type="submit" value="'.\block_exastud\trans(['de:Weiter', 'en:Next']).'"/>';

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
