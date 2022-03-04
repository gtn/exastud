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

block_exastud_require_login($courseid);

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

		// $mform->addElement('header', 'comment', block_exastud_trans('de:Klasse Importieren'));

		$mform->addElement('checkbox', 'override_reviews', block_exastud_get_string('import_class_reviewsimport'), ' ');
		// , block_exastud_trans("de:(Vorhandene Bewertungen der Lehrer für die Klassenfächer und die Schüler in der Klasse werden überschrieben)"));

		$mform->addElement('filepicker', 'file', block_exastud_get_string("file"));
		$mform->addRule('file', block_exastud_get_string('commentshouldnotbeempty'), 'required', null, 'client');

		$this->add_action_buttons(false, block_exastud_get_string('class_import_button'));
	}
}

class block_exastud_import_class_form_do_import extends moodleform {

	function definition() {
		$mform = &$this->_form;

		$mform->addElement('hidden', 'action');
		$mform->setType('action', PARAM_TEXT);

		// $mform->addElement('header', 'comment', block_exastud_trans('de:Klasse Importieren'));

		$mform->addElement('hidden', 'override_reviews');
		$mform->setType('override_reviews', PARAM_INT);

		$mform->addElement('hidden', 'file');
		$mform->setType('file', PARAM_INT);

		$this->add_action_buttons(false, block_exastud_get_string('class_import_button_confirm'));
	}
}

class block_exastud_import_class_form_password extends moodleform {

	function definition() {
		$mform = &$this->_form;

		$mform->addElement('hidden', 'action');
		$mform->setType('action', PARAM_TEXT);

		$mform->addElement('hidden', 'override_reviews');
		$mform->setType('override_reviews', PARAM_INT);

		$mform->addElement('hidden', 'file');
		$mform->setType('file', PARAM_INT);

		// $mform->addElement('header', 'comment', block_exastud_trans('de:Klasse Importieren'));

		$mform->addElement('passwordunmask', 'password', block_exastud_get_string("password"));
		$mform->setType('password', PARAM_TEXT);
		$mform->addRule('password', block_exastud_get_string('required'), 'required', null, 'client');

		$this->add_action_buttons(false, block_exastud_get_string('class_import_button_confirm'));
	}
}

function block_exastud_import_class($doimport, $override_reviews, $draftitemid, $password='') {
	global $output, $DB, $USER;

	$fs = get_file_storage();
	$usercontext = context_user::instance($USER->id);
	$draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

	$file = reset($draftfiles);
	if (!$file) {
		echo $output->notification(block_exastud_get_string('file_not_found'), 'notifyerror');

		return;
	}

	// $content = $mform->get_file_content('file');
	$content = $file->get_content();

	if (!$content) {
		echo $output->notification(block_exastud_get_string('file_not_selected'), 'notifyerror');

		return;
	}

	$content = @gzdecode($content);
	if (!$content) {
		echo $output->notification(block_exastud_get_string('file_is_wrong_format'), 'notifyerror');

		return;
	}
	$classData = json_decode($content);
	if (!$classData) {
		echo $output->notification(block_exastud_get_string('file_is_wrong_format'), 'notifyerror');

		return;
	}

	if (@$classData->datatype != 'block_exastud_class_export') {
		echo $output->notification(block_exastud_get_string('file_is_not_class_backup'), 'notifyerror');

		return;
	} elseif (@$classData->dataversion != '0.1' && @$classData->dataversion != '0.2') {
		echo $output->notification(block_exastud_get_string('file_version_wrong'), 'notifyerror');

		return;
	}

	$ret = (object)[ 'ok' => true ];

	$ret->needs_password = !!@$classData->encrypted;

	if ($ret->needs_password && $password) {
		$iv = base64_decode($classData->iv);
		$encrypted = base64_decode($classData->encrypted);

		$privateData = openssl_decrypt($encrypted, 'aes-256-cbc', $password, true, $iv);
		if (!$privateData) {
			echo $output->notification(block_exastud_get_string('wrong_password'), 'notifyerror');

			return;
		}
		$privateData = json_decode($privateData);
		if (!$privateData) {
			echo $output->notification(block_exastud_get_string('file_is_wrong_format'), 'notifyerror');

			return;
		}

		$classData = (object)array_merge((array)$classData, (array)$privateData);
	}

	// import it

	$class = clone $classData->class;
	$class->timemodified = time();
	$class->userid = $USER->id;

	$existingClass = $DB->get_records_sql('SELECT * FROM {block_exastudclass} WHERE userid=? AND title=?', [$USER->id, $class->title]);
	$existingClass = reset($existingClass);

	if (!$doimport) {
		if ($existingClass) {
			echo $output->notification(block_exastud_get_string('import_class_already_exist', null, $class->title), 'error');
		} else {
			echo $output->notification(block_exastud_get_string('classname').': '.$class->title, 'info');
		}
	}

	// $class->title .= ' ('.block_exastud_trans('de:Wiederhergestellt am ').date('d.m.Y H:i').')';
	if ($doimport) {
		if ($existingClass) {
			$class->id = $existingClass->id;
			$DB->update_record('block_exastudclass', $class);
		} else {
			$class->id = $DB->insert_record('block_exastudclass', $class);
		}
	}

	// $classData->bp: not needed
	// $classData->period: not needed
	// $classData->subjects: not needed
	// $classData->evalopt: not needed

	if ($doimport) {
		$DB->delete_records('block_exastudclassstudents', ['classid' => $class->id]);
		// teachers
		$DB->delete_records('block_exastudclassteachers', ['classid' => $class->id]);
		// data
		$DB->delete_records('block_exastuddata', ['classid' => $class->id]);
		// classcate
		$DB->delete_records('block_exastudclasscate', ['classid' => $class->id]);
		// classcate
		$DB->delete_records('block_exastudclasscate', ['classid' => $class->id]);

		// TODO: block_exastudclassteastudvis
	}

	$classteacherMapping = [];
	$teacherids = [];
	if (@$classData->classteachers) {
		foreach ($classData->classteachers as $classteacher) {
			$classteacher->classid = $class->id;
			$teacherids[$classteacher->teacherid] = $classteacher->teacherid;
			$subjectids[$classteacher->subjectid] = $classteacher->subjectid;

			if ($doimport) {
				$classteacherMapping[$classteacher->id] = $DB->insert_record('block_exastudclassteachers', $classteacher);
			}
		}
	}

	$studentids = [];
	if (@$classData->students) {
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

	if ($override_reviews && @$classData->reviews) {
		foreach ($classData->reviews as $review) {
			if (!empty($studentids[$review->studentid]) && $review->periodid == $class->periodid && !empty($teacherids[$review->teacherid]) && (!empty($subjectids[$review->subjectid]) || $review->subjectid < 0)) {
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
						$teacher = $DB->get_record('user', ['id' => $review->teacherid, 'deleted' => 0]);

						$subject = '';
						if ($review->subjectid > 0) {
							$subject = $DB->get_record('block_exastudsubjects', ['id' => $review->subjectid]);
							if ($subject) {
								$subject = $subject->title;
							}
						} elseif ($review->subjectid == BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN) {
							$subject = block_exastud_get_string("learn_and_sociale");
						} /*elseif ($review->subjectid == BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG) {
							$subject = block_exastud_get_string("learn_and_sociale_for_head");
						}*/ else {
							$subject = '-';
						}

						$a = (object)[
							'type' => $subject ?: '-',
							'teacher' => $teacher ? fullname($teacher) : '-',
						];
						echo $output->notification(block_exastud_get_string('import_evaluation_will_overwrite', null, $a), 'notifyerror');
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
		echo $output->notification(block_exastud_get_string('import_class_restored', null, $class->title), 'info');
	} else {
		echo $output->notification(block_exastud_get_string('import_class_checked_success'), 'info');
	}

	return $ret;
}

if (optional_param('action', '', PARAM_TEXT) == 'password') {
	$mform = new block_exastud_import_class_form_password();

	if ($submitted_data = $mform->get_data()) {
		if (!block_exastud_import_class(true, @$submitted_data->override_reviews, $submitted_data->file, $submitted_data->password)) {
			$mform->display();
		}

		echo $output->footer();
		exit;
	}
}

if (optional_param('action', '', PARAM_TEXT) == 'import') {
	$mform = new block_exastud_import_class_form_do_import();

	if ($submitted_data = $mform->get_data()) {
		block_exastud_import_class(true, @$submitted_data->override_reviews, $submitted_data->file);

		echo $output->footer();
		exit;
	}
}

echo $output->heading(block_exastud_get_string('import_class'));

$mform = new block_exastud_import_class_form();
if ($mform->is_cancelled()) {
	redirect($returnurl);
} else if ($submitted_data = $mform->get_data()) {
	if (!$ret = block_exastud_import_class(false, @$submitted_data->override_reviews, $submitted_data->file)) {
		echo $output->footer();
		exit;
	}

	// remove the submit button text
	unset($submitted_data->submitbutton);

	if ($ret->needs_password) {
		$mform = new block_exastud_import_class_form_password();
		$submitted_data->action = 'password';
		$mform->set_data($submitted_data);
	} else {
		$mform = new block_exastud_import_class_form_do_import();
		$submitted_data->action = 'import';
		$mform->set_data($submitted_data);
	}

	$mform->display();
} else {
	$mform->display();
}

echo $output->footer();
