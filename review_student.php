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
require_once($CFG->dirroot.'/blocks/exastud/lib/edit_form.php');

use block_exastud\globals as g;

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);
$subjectid = required_param('subjectid', PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);

require_login($courseid);

if (!$returnurl) {
	$returnurl = new moodle_url('/blocks/exastud/review_class.php?courseid='.$courseid.'&classid='.$classid.'&subjectid='.$subjectid);
}

$output = block_exastud_get_renderer();

$url = '/blocks/exastud/review_student.php';
$PAGE->set_url($url);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$reviewclass = block_exastud_get_review_class($classid, $subjectid);
$class = block_exastud_get_class($classid);

if (!$reviewclass || !$class) {
	print_error('badclass', 'block_exastud');
}

if ($DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid)) == 0) {
	print_error('badstudent', 'block_exastud');
}

$student = $DB->get_record('user', array('id' => $studentid));
$template = block_exastud_get_student_print_template($class, $student->id);

$strstudentreview = block_exastud_get_string('reviewstudent');
$strclassreview = block_exastud_get_string('reviewclass');
$strreview = block_exastud_get_string('review');

$actPeriod = block_exastud_check_active_period();
$categories = block_exastud_get_class_categories($classid);

$lastPeriod = block_exastud_get_last_period();
if ($lastPeriod) {
	$lastPeriodClass = $DB->get_record_sql("
		SELECT DISTINCT c.id
		FROM {block_exastudclass} c
		JOIN {block_exastudclassstudents} cs ON cs.classid=c.id 
		JOIN {block_exastudclassteachers} ct ON ct.classid=c.id
		WHERE c.periodid=? AND cs.studentid=? AND ct.teacherid=? AND ct.subjectid=?
	", [$lastPeriod->id, $studentid, g::$USER->id, $subjectid], IGNORE_MULTIPLE);
} else {
	$lastPeriodClass = null;
}

$formdata = new stdClass();

$formdata->courseid = $courseid;
$formdata->studentid = $studentid;
$formdata->classid = $classid;
$formdata->subjectid = $subjectid;

$teacherid = $USER->id;


$exacomp_grades = '';
if (block_exastud_is_exacomp_installed()) {
	if (!method_exists('\block_exacomp\api', 'get_subjects_with_grade_for_teacher_and_student')) {
		$exacomp_grades = 'Please update exacomp to latest version';
	} else {
		$subjects = \block_exacomp\api::get_subjects_with_grade_for_teacher_and_student($teacherid, $studentid);
		if (!$subjects) {
			$exacomp_grades = '---';
		} else {
			foreach ($subjects as $subject) {
				$exacomp_grades .= '<b>'.$subject->title.'</b><br/>';
				$exacomp_grades .= 'Note: '.($subject->additionalinfo ?: '---').
					' / Niveau: '.($subject->niveau ?: '---').'<br/>';
			}
		}
	}
}

$reviewdata = $DB->get_record('block_exastudreview', array('teacherid' => $teacherid, 'subjectid' => $subjectid, 'periodid' => $actPeriod->id, 'studentid' => $studentid));

if ($reviewdata) {
	foreach ($categories as $category) {
		$formdata->{$category->id.'_'.$category->source} = $DB->get_field('block_exastudreviewpos', 'value', array("categoryid" => $category->id, "reviewid" => $reviewdata->id, "categorysource" => $category->source));
	}
}

$subjectData = block_exastud_get_review($classid, $subjectid, $studentid);
$formdata = (object)array_merge((array)$formdata, (array)$subjectData);

$grade_options = $template->get_grade_options();
if (@$formdata->grade && !isset($grade_options[$formdata->grade])) {
	$grade_options = [$formdata->grade => $formdata->grade] + $grade_options;

}
// create form and add customvariables
$studentform = new student_edit_form(null, [
	'categories' => $categories,
	'subjectid' => $subjectid,
	'exacomp_grades' => $exacomp_grades,
	'grade_options' => $grade_options,
	'categories.modified' =>
		$reviewdata
			? block_exastud_get_renderer()->last_modified($reviewdata->teacherid, $reviewdata->timemodified)
			: '',
	'review.modified' =>
		block_exastud_get_renderer()->last_modified(@$subjectData->{'review.modifiedby'}, @$subjectData->{'review.timemodified'}),
	'grade.modified' =>
		block_exastud_get_renderer()->last_modified(@$subjectData->{'grade.modifiedby'}, @$subjectData->{'grade.timemodified'}),
]);
// get all data form Form and save it
if ($fromform = $studentform->get_data()) {
	$newreview = new stdClass();
	$newreview->timemodified = time();
	$newreview->review = $fromform->review;

	$existingReview = $DB->get_record('block_exastudreview', ['studentid' => $studentid, 'subjectid' => $subjectid,
                                                                'periodid' => $actPeriod->id, 'teacherid' => $teacherid,]);
	$newreview = g::$DB->insert_or_update_record('block_exastudreview', $newreview, [
		'studentid' => $studentid,
		'subjectid' => $subjectid,
		'periodid' => $actPeriod->id,
		'teacherid' => $teacherid,
	]);
	if (!$existingReview || $existingReview->review != $fromform->review) {
	    $subjectData = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
        \block_exastud\event\studentreview_changed::log(['objectid' => $reviewclass->id,
                'relateduserid' => $studentid,
                'other' => ['classtitle' => $reviewclass->title,
                        'subjectid' => $subjectid,
                        'subjecttitle' => $subjectData->title,
                        'oldvalue' => ($existingReview ? $existingReview->review : null),
                        'value' => $fromform->review,
                        'studentname' =>  $student->firstname.' '.$student->lastname,
                        'target' => 'Fachkompetenzen']]);
    }

    $existingReview = $DB->get_record('block_exastudreview', ['studentid' => $studentid, 'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
            'periodid' => $actPeriod->id, 'teacherid' => $teacherid,]);
	g::$DB->insert_or_update_record('block_exastudreview', [
		'timemodified' => time(),
		'review' => $fromform->vorschlag,
	], [
		'studentid' => $studentid,
		'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
		'periodid' => $actPeriod->id,
		'teacherid' => $teacherid,
	]);
    if (!$existingReview || $existingReview->review != $fromform->vorschlag) {
        \block_exastud\event\studentreview_changed::log(['objectid' => $reviewclass->id,
                'relateduserid' => $studentid,
                'other' => ['classtitle' => $reviewclass->title,
                        'subjectid' => $subjectid,
                        'subjecttitle' => $subjectData->title,
                        'oldvalue' => ($existingReview ? $existingReview->review : null),
                        'value' => $fromform->vorschlag,
                        'target' => 'Lern- und Sozialverhalten',
                        'studentname' => $student->firstname.' '.$student->lastname]]);
    }


    block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'review', trim($fromform->review));
	block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'review.modifiedby', $USER->id);
	block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'review.timemodified', time());

	block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'grade', $fromform->grade);
	block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'niveau', $fromform->niveau);

	block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'grade.modifiedby', $USER->id);
	block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'grade.timemodified', time());

	if (!empty($formdata->lastPeriodFlag)) {
		block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'lastPeriodIsLoaded', 1);
	}

	foreach ($categories as $category) {
		if (!isset($fromform->{$category->id.'_'.$category->source})) {
			continue;
		}

		$newvalue = $fromform->{$category->id.'_'.$category->source};
		$existing = $DB->get_record('block_exastudreviewpos', ["reviewid" => $newreview->id,
                                                                "categoryid" => $category->id,
                                                                "categorysource" => $category->source] );
		g::$DB->insert_or_update_record('block_exastudreviewpos',
			["value" => $newvalue],
			["reviewid" => $newreview->id, "categoryid" => $category->id, "categorysource" => $category->source]);
		// only if changed
		if (!$existing || $newvalue != $existing->value) {
		    $subjectdata = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
		    $grades = block_exastud_get_evaluation_options(true);
            \block_exastud\event\studentreviewcategory_changed::log(['objectid' => $classid,
                    'relateduserid' => $studentid,
                    'other' => ['classtitle' => $reviewclass->title,
                            'subjectid' => $subjectid,
                            'subjecttitle' => $subjectdata->title,
                            'oldgrading' => (!$existing ? null : isset($grades[$existing->value]) ? $grades[$existing->value] : null),
                            'oldgradingid' => ($existing ? $existing->value : null),
                            'grading' => $grades[$newvalue],
                            'gradingid' => $newvalue,
                            'category' => $category->title,
                            'categoryid' => $category->id,
                            'studentname' => $student->firstname.' '.$student->lastname]]);
        }
	}

	redirect($returnurl);
}

$classheader = $reviewclass->title.($reviewclass->subject_title ? ' - '.$reviewclass->subject_title : '').' - '.$template->get_name();

echo $output->header(array('review',
	array('name' => $classheader, 'link' => $CFG->wwwroot.'/blocks/exastud/review_class.php?courseid='.$courseid.
		'&classid='.$classid.'&subjectid='.$subjectid),
), array('noheading'));

echo $OUTPUT->heading($classheader);

$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)).' '.fullname($student);
echo $output->heading($studentdesc);

// load lern&soz vorschlag
$formdata->vorschlag = $DB->get_field('block_exastudreview', 'review', [
	'studentid' => $studentid,
	'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
	'periodid' => $actPeriod->id,
	'teacherid' => $teacherid,
]);

if (empty($formdata->grade)) {
	$formdata->grade = '';
}

if (!(@$formdata->lastPeriodNiveau)) {
	$formdata->lastPeriodNiveau = "---";
}
if (!(@$formdata->lastPeriodGrade)) {
	$formdata->lastPeriodGrade = "---";
}


// load from last period
if ($lastPeriodClass) {
	$lastPeriodData = (object)block_exastud_get_review($lastPeriodClass->id, $subjectid, $studentid);


	if (optional_param('action', null, PARAM_TEXT) == 'load_last_period_data') {
		// set flag to show that last period is loaded
		$formdata->lastPeriodFlag = true;

		$formdata->vorschlag = $DB->get_field('block_exastudreview', 'review', [
			'studentid' => $studentid,
			'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
			'periodid' => $lastPeriod->id,
			'teacherid' => $teacherid,
		]);

		/*
		 * $reviewdata = $DB->get_records('block_exastudreview', array('subjectid' => $subjectid, 'periodid' => $lastPeriod->id, 'studentid' => $studentid), 'timemodified DESC');
		 * $reviewdata = reset($reviewdata);
		 */
		$reviewdata = $DB->get_record('block_exastudreview', array(
			'teacherid' => $teacherid,
			'subjectid' => $subjectid,
			'periodid' => $lastPeriod->id,
			'studentid' => $studentid,
		));

		if ($reviewdata) {
			foreach ($categories as $category) {
				$formdata->{$category->id.'_'.$category->source} = $DB->get_field('block_exastudreviewpos', 'value', array(
					"categoryid" => $category->id,
					"reviewid" => $reviewdata->id,
					"categorysource" => $category->source,
				));
			}
			$formdata->review = $reviewdata->review;
		}


		if (@$lastPeriodData->niveau || @$lastPeriodData->grade) {
			if (@$lastPeriodData->niveau) {

				$formdata->lastPeriodNiveau = $lastPeriodData->niveau;
				block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'lastPeriodNiveau', $lastPeriodData->niveau);
			}
			if (@$lastPeriodData->grade) {

				$formdata->lastPeriodGrade = $lastPeriodData->grade;
				block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'lastPeriodGrade', $lastPeriodData->grade);
			}
		}

	}
}


if ($lastPeriodClass) {
	if (optional_param('action', null, PARAM_TEXT) == 'load_last_period_data' || @$formdata->lastPeriodIsLoaded) {
		echo '<h2>'.block_exastud_trans('de:Daten der letzten Periode/Halbjahr wurden übernommen').'</h2>';
	} else {
		$url = block_exastud\url::request_uri();
		$url->param('action', 'load_last_period_data');
		echo $output->link_button($url, block_exastud_trans('de:Eingaben von der letzten Periode/Halbjahr übernehmen'));
	}
}

$formdata->review = block_exastud_html_to_text(@$formdata->review);
$formdata->vorschlag = block_exastud_html_to_text(@$formdata->vorschlag);

$studentform->set_data($formdata);
$studentform->display();

echo $output->back_button($returnurl);

echo $output->footer();
