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

$courseid = optional_param('courseid', 1, PARAM_INT);

require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_REVIEW);

$url = '/blocks/exastud/review.php';
$PAGE->set_url($url);
$output = block_exastud\get_renderer();
echo $output->header('review');

$actPeriod = block_exastud_check_active_period();


$reviewclasses = block_exastud\get_head_teacher_classes_all();

// first headteacher classes
foreach ($reviewclasses as $class) {
	$class->is_head_teacher = true;
	$class->subjects = [];
}

// then add the subjects to the classes
$reviewsubjects = block_exastud\get_review_classes();
foreach ($reviewsubjects as $reviewsubject) {
	if (!isset($reviewclasses[$reviewsubject->classid])) {
		$reviewclasses[$reviewsubject->classid] = $reviewsubject;
		$reviewclasses[$reviewsubject->classid]->id = $reviewsubject->classid;
		$reviewclasses[$reviewsubject->classid]->is_head_teacher = false;
		$reviewclasses[$reviewsubject->classid]->subjects = [];
	}

	$reviewclasses[$reviewsubject->classid]->subjects[] = $reviewsubject;
}

// $lern_und_sozialverhalten_classes = \block_exastud\get_head_teacher_lern_und_sozialverhalten_classes();

if(!$reviewclasses) {
	echo \block_exastud\get_string('noclassestoreview','block_exastud');
}
else {
	foreach ($reviewclasses as $myclass) {
		$table = new html_table();

		$table->head = array($myclass->title);

		$table->align = array("left");

		$classstudents = \block_exastud\get_class_students($myclass->id);
		if (!$classstudents) {
			$table->data[] = [
				\block_exastud\get_string('nostudentstoreview')
			];
		} else {
			foreach ($myclass->subjects as $subject) {
				$table->data[] = [
					html_writer::link(new moodle_url('/blocks/exastud/review_class.php', [
						'courseid' => $courseid,
						'classid' => $myclass->id,
						'subjectid' => $subject->subjectid
					]), $subject->subject_title ?: \block_exastud\trans('de:nicht zugeordnet'))
				];
			}

			if ($myclass->is_head_teacher) {
				if ($table->data) {
					// add spacer
					$table->data[] = [ '<b>Lernentwicklungsbericht:' ];
				}

				$table->data[] = [
					html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
						'courseid' => $courseid,
						'classid' => $myclass->id,
						'type' => \block_exastud\DATA_ID_LERN_UND_SOZIALVERHALTEN
					]), \block_exastud\trans('de:Lern- und Sozialverhalten'))
				];
				$table->data[] = [
					html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
						'courseid' => $courseid,
						'classid' => $myclass->id,
						'type' => 'others'
					]), \block_exastud\trans('de:Weitere Daten'))
				];
			}
		}

		echo $output->table($table);
	}

}
echo $output->footer();
