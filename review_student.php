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
$reporttype = optional_param('reporttype', '', PARAM_RAW);
$type = optional_param('type', '', PARAM_TEXT);

setcookie('lastclass', $classid);

block_exastud_require_login($courseid);


if (!$returnurl) {
	$returnurl = new moodle_url('/blocks/exastud/review_class.php?courseid='.$courseid.'&classid='.$classid.'&subjectid='.$subjectid.'&openclass='.$classid);
}

$output = block_exastud_get_renderer();

$url = '/blocks/exastud/review_student.php?courseid='.$courseid.'&classid='.$classid.'&subjectid='.$subjectid.'&studentid='.$studentid;
$PAGE->set_url($url);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$class = block_exastud_get_class($classid);
$simulateSubjectId = $subjectid;
if ((block_exastud_is_profilesubject_teacher($classid) || $class->userid != $USER->id)
        && $type == BLOCK_EXASTUD_DATA_ID_CERTIFICATE) {
    //$simulateSubjectId = BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH;
    $simulateSubjectId = BLOCK_EXASTUD_DATA_ID_CERTIFICATE;
}
$reviewclass = block_exastud_get_review_class($classid, $simulateSubjectId);

if (!$reviewclass || !$class) {
	print_error('badclass', 'block_exastud');
}

if ($DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid)) == 0) {
	print_error('badstudent', 'block_exastud');
}

$student = $DB->get_record('user', array('id' => $studentid, 'deleted' => 0));
$template = block_exastud_get_student_print_template($class, $student->id);

$strstudentreview = block_exastud_get_string('reviewstudent');
$strclassreview = block_exastud_get_string('reviewclass');
$strreview = block_exastud_get_string('review');

$actPeriod = block_exastud_check_active_period();

// if class is from old period - check access for teacher and change $actPeriod to needed
if ($class->periodid != $actPeriod->id) {
    $unlocked = block_exastud_teacher_is_unlocked_for_old_class_review($classid, $USER->id, BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS);
    if ($unlocked) {
        $actPeriod = block_exastud_get_period($class->periodid);
    }
}

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

if ($reporttype == 'inter') { // no inter competences for such reports
    $template_category = $template->get_category();
    if (in_array($template_category, ['Abgang', 'Abschluss'])) {
        print_error('badtemplate', 'block_exastud');
    }
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

$reviewdata = $DB->get_record('block_exastudreview',
                                array('teacherid' => $teacherid,
                                        'subjectid' => $subjectid,
                                        'periodid' => $actPeriod->id,
                                        'studentid' => $studentid));

// if the student has a review from another teacher - probably this student was hidden and than again shown
// such student is not able to be review again
/*$reports_from_anotherteachers = $DB->get_record_sql('SELECT * FROM {block_exastudreview}
                                                    WHERE subjectid = ?
                                                        AND periodid = ?
                                                        AND studentid = ?
                                                        AND teacherid != ?',
    array($subjectid,
        $actPeriod->id,
        $studentid,
        $teacherid),
    IGNORE_MULTIPLE);*/
$canReviewStudent = true; // I can review by default
/*if ($reports_from_anotherteachers && count($reports_from_anotherteachers) > 0) {
    //$canReviewStudent = false;
    // change review data to data frm another teacher (first, it must be single?)
    $reviewdata = $reports_from_anotherteachers;
};*/

if ($reviewdata) {
	foreach ($categories as $category) {
		$formdata->{$category->id.'_'.$category->source} = $DB->get_field('block_exastudreviewpos',
                                                                            'value',
                                                                            array("categoryid" => $category->id,
                                                                                    "reviewid" => $reviewdata->id,
                                                                                    "categorysource" => $category->source));
	}
}

$subjectData = block_exastud_get_review($classid, $subjectid, $studentid);
$formdata = (object)array_merge((array)$formdata, (array)$subjectData);

$grade_options = array_filter($template->get_grade_options());
if (count($grade_options) > 0) {
    if (@$formdata->grade && count($grade_options) > 0 && !array_key_exists($formdata->grade, $grade_options)) {
        $grade_options = [$formdata->grade => $formdata->grade] + $grade_options;
    }
} else {
    $grade_options = null;
}
// create form and add customvariables
$studentform = new student_edit_form(null, [
    'template' => $template,
	'categories' => $categories,
	'classid' => $classid,
	'subjectid' => $subjectid,
	'exacomp_grades' => $exacomp_grades,
	'grade_options' => $grade_options,
	'canReviewStudent' => $canReviewStudent,
	'reporttype' => $reporttype, // inter - interdisciplinary; social - "learn and social". empty - Notenerfassung/Niveau/Fach
    'temp_formdata' => $formdata,
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

    switch ($reporttype) {
        case 'inter':
            $existingReview = $DB->get_record('block_exastudreview',
                    ['studentid' => $studentid, 'subjectid' => $subjectid,
                            'periodid' => $actPeriod->id, 'teacherid' => $teacherid,]);
            if ($existingReview && $existingReview->review) {
                $newreview->review = $existingReview->review;
            } else {
                $newreview->review = '';
            }
            $newreview = g::$DB->insert_or_update_record('block_exastudreview', $newreview, [
                    'studentid' => $studentid,
                    'subjectid' => $subjectid,
                    'periodid' => $actPeriod->id,
                    'teacherid' => $teacherid,
            ]);
            foreach ($categories as $category) {
                if (isset($fromform->{$category->id.'_'.$category->source})) {
                    $newvalue = $fromform->{$category->id.'_'.$category->source};
                } else {
                    $nv = optional_param($category->id.'_'.$category->source, null, PARAM_RAW); // for custom form element.
                    if ($nv) {
                        $newvalue = $nv;
                    } else {
                        continue;
                    }
                }

                $existing = $DB->get_record('block_exastudreviewpos', ["reviewid" => $newreview->id,
                        "categoryid" => $category->id,
                        "categorysource" => $category->source] );
                g::$DB->insert_or_update_record('block_exastudreviewpos',
                        ["value" => $newvalue],
                        ["reviewid" => $newreview->id, "categoryid" => $category->id, "categorysource" => $category->source]);
                // only if changed
                if (!$existing || $newvalue != $existing->value) {
                    $subjectObjData = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
                    $grades = block_exastud_get_evaluation_options(true);
                    $newToLog = (is_array($grades) && array_key_exists($newvalue, $grades) ? $grades[$newvalue] : $newvalue);
                    $oldToLog = (!$existing ? null : (is_array($grades) && array_key_exists($existing->value, $grades) ? $grades[$existing->value] : $existing->value));
                    \block_exastud\event\studentreviewcategory_changed::log(['objectid' => $classid,
                            'relateduserid' => $studentid,
                            'other' => ['classtitle' => $reviewclass->title,
                                    'subjectid' => $subjectid,
                                    'subjecttitle' => $subjectObjData->title,
                                    'oldgrading' => $oldToLog,
                                    'oldgradingid' => ($existing ? $existing->value : null),
                                    'grading' => $newToLog,
                                    'gradingid' => $newvalue,
                                    'category' => $category->title,
                                    'categoryid' => $category->id,
                                    'studentname' => $student->firstname.' '.$student->lastname]]);
                }
            }

            break;
        case 'social':
            $newreview->review = $fromform->vorschlag;
            $existingReview = $DB->get_record('block_exastudreview',
                    [       'studentid' => $studentid,
                            //'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
                            'subjectid' => $subjectid,
                            'periodid' => $actPeriod->id,
                            'teacherid' => $teacherid,]);
            g::$DB->insert_or_update_record('block_exastudreview', $newreview, [
                    'studentid' => $studentid,
                    //'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
                    'subjectid' => $subjectid,
                    'periodid' => $actPeriod->id,
                    'teacherid' => $teacherid,
            ]);
            if (!$existingReview || $existingReview->review != $fromform->vorschlag) {
                $subjectObjData = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
                \block_exastud\event\studentreview_changed::log(['objectid' => $reviewclass->id,
                        'relateduserid' => $studentid,
                        'other' => ['classtitle' => $reviewclass->title,
                                'subjectid' => $subjectid,
                                'subjecttitle' => $subjectObjData->title,
                                'oldvalue' => ($existingReview ? $existingReview->review : null),
                                'value' => $fromform->vorschlag,
                                'target' => block_exastud_get_string('learn_and_sociale'),
                                'studentname' => $student->firstname.' '.$student->lastname]]);
            }
            break;
        default:
            $newreview->review = $fromform->review;
            $existingReview = $DB->get_record('block_exastudreview', [
                    'studentid' => $studentid,
                    'subjectid' => $subjectid,
                    'periodid' => $actPeriod->id,
                    'teacherid' => $teacherid,]);
            /*$newreview = g::$DB->insert_or_update_record('block_exastudreview', $newreview, [
                    'studentid' => $studentid,
                    'subjectid' => $subjectid,
                    'periodid' => $actPeriod->id,
                    'teacherid' => $teacherid,
            ]);*/
            if (!$existingReview || $existingReview->review != $fromform->review) {
                $subjectObjData = $DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
                \block_exastud\event\studentreview_changed::log(['objectid' => $reviewclass->id,
                        'relateduserid' => $studentid,
                        'other' => ['classtitle' => $reviewclass->title,
                                'subjectid' => $subjectid,
                                'subjecttitle' => $subjectObjData->title,
                                'oldvalue' => ($existingReview ? $existingReview->review : null),
                                'value' => $fromform->review,
                                'studentname' =>  $student->firstname.' '.$student->lastname,
                                'target' => 'Fachkompetenzen']]);
            }

            block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'review', trim($fromform->review));
            block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'review.modifiedby', $USER->id);
            block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'review.timemodified', time());

            block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'grade', $fromform->grade);
            block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'grade.modifiedby', $USER->id);
            block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'grade.timemodified', time());

            block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'niveau', $fromform->niveau);

    }

	if (!empty($formdata->lastPeriodFlag)) {
		block_exastud_set_subject_student_data($classid, $subjectid, $studentid, 'lastPeriodIsLoaded', 1);
	}

	redirect($returnurl);
}

$classheader = $reviewclass->title.($reviewclass->subject_title ? ' - '.$reviewclass->subject_title : '').' - '.$template->get_name();

if (block_exastud_is_bw_active()) {
    echo '<script>var is_bw_activated = true;</script>'; // for activate some JS functions
}

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
	//'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
	'subjectid' => $subjectid,
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

	if (optional_param('action', null, PARAM_TEXT) == 'load_last_period_data') {
        $lastPeriodData = (object)block_exastud_get_review($lastPeriodClass->id, $subjectid, $studentid);

		// set flag to show that last period is loaded
		$formdata->lastPeriodFlag = true;
		$oldPeriodId = $DB->get_field('block_exastudclass', 'periodid', ['id' => $lastPeriodClass->id]);

        switch ($reporttype) {
            case 'inter':
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
                break;
            case 'social':
                $formdata->vorschlag = $DB->get_field('block_exastudreview', 'review', [
                        'studentid' => $studentid,
                        'subjectid' => $subjectid,
                        'periodid' => $oldPeriodId,
                        'teacherid' => $teacherid,
                ]);
                break;
            default:
                $formdata->review = $lastPeriodData->review;
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

        if (!$formdata->review) {
            $formdata->review = ''; // if no data from last period
        }
        if (!$formdata->vorschlag) {
            $formdata->vorschlag = ''; // if no data from last period
        }

	}
}


if ($lastPeriodClass) {
	if (optional_param('action', null, PARAM_TEXT) == 'load_last_period_data' || @$formdata->lastPeriodIsLoaded) {
		echo '<h2>'.block_exastud_trans('de:Daten der letzten Periode/Halbjahr wurden übernommen').'</h2>';
	} else {
		$url = block_exastud\url::request_uri();
		$url->param('action', 'load_last_period_data');
		echo $output->link_button($url, block_exastud_get_string('load_last_period'), ['class' => 'btn btn-default']);
	}
}
$formdata->review = block_exastud_html_to_text(@$formdata->review);
$formdata->vorschlag = block_exastud_html_to_text(@$formdata->vorschlag);

$studentform->set_data($formdata);
$studentform->display();

echo $output->back_button($returnurl);

echo $output->footer();
