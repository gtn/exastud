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
require_once __DIR__.'/lib/picture_upload_form.php';

use block_exastud\globals as g;

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID

require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
$curPeriod = block_exastud_get_active_or_next_period();

$url = '/blocks/exastud/import_class.php';
$PAGE->set_url($url, ['courseid' => $courseid]);
$output = block_exastud_get_renderer();

echo $output->header(['configuration_classes']);


require_once $CFG->libdir.'/formslib.php';

class block_exastud_import_class_form extends moodleform {
	function definition() {
		$mform = &$this->_form;

		$mform->addElement('header', 'comment', block_exastud_trans('de:Klasse Importieren'));

		$mform->addElement('checkbox', 'override_reviews', block_exastud_trans("de:Bewertung importieren"), ' ');
		// , block_exastud_trans("de:(Vorhandene Bewertungen der Lehrer für die Klassenfächer und die Schüler in der Klasse werden überschrieben)"));

		$mform->addElement('filepicker', 'file', block_exastud_get_string("file"));
		$mform->addRule('file', block_exastud_get_string('commentshouldnotbeempty'), 'required', null, 'client');

		$this->add_action_buttons(false, block_exastud_trans('de:Importieren'));
	}
}

class block_exastud_import_class_form2 extends moodleform {

	function definition() {
		$mform = &$this->_form;

		$mform->addElement('hidden', 'action');
		$mform->setType('action', PARAM_TEXT);

		$mform->addElement('header', 'comment', block_exastud_trans('de:Klasse Importieren'));

		$mform->addElement('hidden', 'override_reviews');
		$mform->setType('override_reviews', PARAM_INT);

		$mform->addElement('hidden', 'file');
		$mform->setType('file', PARAM_INT);

		$this->add_action_buttons(false, block_exastud_trans('de:Prüfen'));
	}
}

function block_exastud_import_class($doimport, $override_reviews, $draftitemid) {
	global $output, $DB, $USER;

	$fs = get_file_storage();
	$usercontext = context_user::instance($USER->id);
	$draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

	$file = reset($draftfiles);
	if (!$file) {
		echo $output->notification(block_exastud_trans('de:Keine Datei gefunden'), 'notifyerror');

		return;
	}

	// $content = $mform->get_file_content('file');
	$content = $file->get_content();

	if (!$content) {
		echo $output->notification(block_exastud_trans('de:Keine Datei ausgewählt'), 'notifyerror');

		return;
	}

	$json = @gzdecode($content);
	if (!$json) {
		echo $output->notification(block_exastud_trans('de:Datei hat falsches Format'), 'notifyerror');

		return;
	}
	$classData = json_decode($json);
	if (!$classData) {
		echo $output->notification(block_exastud_trans('de:Datei hat falsches Format'), 'notifyerror');

		return;
	}

	if (@$classData->datatype != 'block_exastud_class_export') {
		echo $output->notification(block_exastud_trans('de:Datei ist keine Sicherung einer Klasse'), 'notifyerror');

		return;
	} elseif (@$classData->dataversion != '0.1') {
		echo $output->notification(block_exastud_trans('de:Das Dateiformat ist leider nicht mit dieser Version des Lernentwicklungsberichts kompatibel'), 'notifyerror');

		return;
	}


	// import it
	/*
	var_dump($classData);
	var_dump($submitted_data->override_reviews);
	exit;
	/* */

	echo $output->notification(block_exastud_trans('de:Klassenname: {$a}', $classData->class->title), 'info');

	$class = clone $classData->class;
	$class->timemodified = time();
	$class->userid = $USER->id;
	$class->title .= ' ('.block_exastud_trans('de:Wiederhergestellt am ').date('d.m.Y H:i').')';
	if ($doimport) {
		$class->id = $DB->insert_record('block_exastudclass', $classData->class);
	}

	// $classData->bp: not needed
	// $classData->period: not needed
	// $classData->subjects: not needed
	// $classData->evalopt: not needed

	$classteacherMapping = [];
	$teacherids = [];
	foreach ($classData->classteachers as $classteacher) {
		$classteacher->classid = $class->id;
		$taecherids[$classteacher->teacherid] = $classteacher->teacherid;
		$subjectids[$classteacher->subjectid] = $classteacher->subjectid;

		if ($doimport) {
			$classteacherMapping[$classteacher->id] = $DB->insert_record('block_exastudclassteachers', $classteacher);
		}
	}

	$studentids = [];
	foreach ($classData->students as $student) {
		if ($doimport) {
			$DB->insert_record('block_exastudclassstudents', [
				"classid" => $class->id,
				'studentid' => $student->id,
				'timemodified' => @$student->timemodified ?: time(),
			]);
		}

		$studentids[$student->id] = $student->id;
	}


	if ($doimport) {
		foreach ($classData->categories as $category) {
			$DB->insert_record('block_exastudclasscate', [
				"classid" => $class->id,
				'categoryid' => $category->id,
				'categorysource' => $category->source,
			]);
		}

		foreach ($classData->data as $data) {
			$data->classid = $class->id;
			$DB->insert_record('block_exastuddata', $data);
		}

		foreach ($classData->classteastudvis as $classteastudvis) {
			if (!isset($classteacherMapping[$classteastudvis->classteacherid])) {
				continue;
			}

			$classteastudvis->classteacherid = $classteacherMapping[$classteastudvis->classteacherid];
			$DB->insert_record('block_exastudclassteastudvis', $classteastudvis);
		}
	}

	if ($override_reviews) {
		foreach ($classData->reviews as $review) {
			if (!empty($studentids[$review->studentid]) && $review->periodid == $class->periodid && !empty($taecherids[$review->teacherid]) && (!empty($subjectids[$review->subjectid]) || $review->subjectid < 0)) {
				// ok
			} else {
				continue;
			}

			$dbReview = $DB->get_record('block_exastudreview', array('teacherid' => $review->teacherid, 'subjectid' => $review->subjectid, 'periodid' => $class->periodid, 'studentid' => $review->studentid));

			if ($doimport) {
				$DB->delete_records('block_exastudreview', array('id' => $dbReview->id));
				$DB->delete_records('block_exastudreviewpos', array('reviewid' => $dbReview->id));
				$dbReview = null;
			}

			if ($dbReview) {
				if (!$doimport) {
					if ($review->review != $dbReview->review || ($review->timemodified != $dbReview->timemodified && $review->subjectid > 0)) {
						$teacher = $DB->get_record('user', ['id' => $review->teacherid]);

						$subject = '';
						if ($review->subjectid > 0) {
							$subject = $DB->get_record('block_exastudsubjects', ['id' => $review->subjectid]);
							if ($subject) {
								$subject = $subject->title;
							}
						} elseif ($review->subjectid == BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN) {
							$subject = block_exastud_trans("de:Lern- und Sozialverhalten");
						} elseif ($review->subjectid == BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG) {
							$subject = block_exastud_trans("de:Lern- und Sozialverhalten: Formulierungsvorschlag für Klassenlehrkraft");
						} else {
							$subject = '-';
						}

						$a = (object)[
							'type' => $subject ?: '-',
							'teacher' => $teacher ? fullname($teacher) : '-',
						];
						echo $output->notification(block_exastud_trans('de:Es wird eine Bewertung überschrieben (Typ: {$a->type}, Lehrer: {$a->teacher})', $a), 'notifyerror');
					}

					/*
					$dbReviewPoss = $DB->get_records('block_exastudreviewpos', array('reviewid' => $dbReview->id));
					foreach ($dbReviewPoss as $dbReviewPos) {
						var_dump($dbReviewPos);
					}

					echo 'check';
					*/
				}
			} else {
				$reviewid = g::$DB->insert_record('block_exastudreview', $review, array('teacherid' => $review->teacherid, 'subjectid' => $review->subjectid, 'periodid' => $class->periodid, 'studentid' => $review->studentid));

				foreach ($review->pos as $pos) {
					$pos->reviewid = $reviewid;
					$DB->insert_record('block_exastudreviewpos', $pos);
				}
			}
		}
	}

	if ($doimport) {
		echo $output->notification(block_exastud_trans('de:Klasse wurde wiederhergestellt, neuer Name: {$a}', $class->title), 'info');
	} else {
		echo $output->notification(block_exastud_trans('de:Klassendaten erfolgreich geprüft'), 'info');
	}

	return true;
}

if (optional_param('action', '', PARAM_TEXT) == 'import') {
	$mform = new block_exastud_import_class_form2();

	if ($submitted_data = $mform->get_data()) {
		block_exastud_import_class(true, @$submitted_data->override_reviews, $submitted_data->file);

		echo $output->footer();
		exit;
	}
}

$mform = new block_exastud_import_class_form();
if ($mform->is_cancelled()) {
	redirect($returnurl);
} else if ($submitted_data = $mform->get_data()) {
	if (!block_exastud_import_class(false, @$submitted_data->override_reviews, $submitted_data->file)) {
		echo $output->footer();
		exit;
	}

	$mform = new block_exastud_import_class_form2();
	$submitted_data->action = 'import';
	$mform->set_data($submitted_data);

	$mform->display();
} else {
	$mform->display();
}

echo $output->footer();
