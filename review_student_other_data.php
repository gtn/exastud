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

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$type = required_param('type', PARAM_TEXT);
$studentid = required_param('studentid', PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);

block_exastud_require_login($courseid);

$parenturl = new moodle_url('/blocks/exastud/review_class_other_data.php?courseid='.$courseid.'&classid='.$classid.'&type='.$type);
if (!$returnurl) {
	$returnurl = $parenturl;
}

$output = block_exastud_get_renderer();

$PAGE->set_url('/blocks/exastud/review_student_other_data.php', [
	'courseid' => $courseid,
	'classid' => $classid,
	'type' => $type,
	'studentid' => $studentid,
	'returnurl' => $returnurl,
    'openclass' => $classid,
]);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$class = block_exastud_get_class($classid);
$simulateSubjectId = BLOCK_EXASTUD_SUBJECT_ID_OTHER_DATA;
if ((block_exastud_is_profilesubject_teacher($classid) || $class->userid != $USER->id) 
        && $type == BLOCK_EXASTUD_DATA_ID_CERTIFICATE) {
    //$simulateSubjectId = BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH;
    $simulateSubjectId = BLOCK_EXASTUD_DATA_ID_CERTIFICATE;
}

$reviewclass = block_exastud_get_review_class($classid, $simulateSubjectId);

if (!$reviewclass
        || !$class
        || ($type == BLOCK_EXASTUD_DATA_ID_CERTIFICATE
                && !block_exastud_is_profilesubject_teacher($classid))) {
	print_error('badclass', 'block_exastud');
}

if ($DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid)) == 0) {
	print_error('badstudent', 'block_exastud');
}
$student = $DB->get_record('user', array('id' => $studentid, 'deleted' => 0));

$strstudentreview = block_exastud_get_string('reviewstudent');
$strclassreview = block_exastud_get_string('reviewclass');
$strreview = block_exastud_get_string('review');

$actPeriod = block_exastud_check_active_period();

switch ($type) {
    case BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN:
        $classstandarttemplate = block_exastud_get_class_data($class->id)->default_templateid;
        $template = block_exastud_get_student_print_template($class, $studentid);

        $inputs = \block_exastud\print_templates::get_template_inputs($classstandarttemplate);
        $categories = [
            BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN => [
                'title' => block_exastud_get_string('learn_and_sociale'),
                'cols' => (@$inputs['learn_social_behavior']['cols'] && @$inputs['learn_social_behavior']['cols'] <= 90) ? @$inputs['learn_social_behavior']['cols'] : 50,
                'lines' => @$inputs['learn_social_behavior']['lines'] ? @$inputs['learn_social_behavior']['lines'] : 8,
            ],
        ];
        $classheader = $reviewclass->title.' - '.block_exastud_get_string('learn_and_sociale');
        break;
    case BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE:
        $template = block_exastud_get_student_print_template($class, $student->id);
        $categories = $template->get_inputs($type);
        $classheader = $reviewclass->title.' - '.block_exastud_get_string('report_other_report_fields');
        break;
    case BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO:
        $template = block_exastud_get_student_print_template($class, $student->id);
        $categories = $template->get_inputs($type);
        $classheader = $reviewclass->title.' - '.block_exastud_get_string('report_other_report_fields');
        break;
    case BLOCK_EXASTUD_DATA_ID_CERTIFICATE:
        $template = \block_exastud\print_template::create(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH);
        $categories = $template->get_inputs(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH);
        $classheader = $reviewclass->title.' - '.$template->get_name();
        break;
    case BLOCK_EXASTUD_DATA_ID_BILINGUALES:
        $template = block_exastud_get_class_bilingual_template($class->id);
        $categories = $template->get_inputs(BLOCK_EXASTUD_DATA_ID_BILINGUALES);
        $classheader = $reviewclass->title.' - '.$template->get_name();
        break;
    default:
        $template = \block_exastud\print_template::create($type);
        $categories = $template->get_inputs($type);
        $classheader = $reviewclass->title.' - '.$template->get_name();
}
$olddata = (array)block_exastud_get_class_student_data($classid, $studentid);

$dataid = key($categories);
$studentform = new student_other_data_form($PAGE->url, [
	'categories' => $categories,
	'templateid' => $template->get_template_id(),
    'type' => $type,
	'modified' =>
		@$olddata[$dataid.'.modifiedby'] ?
			block_exastud_get_renderer()->last_modified(@$olddata[$dataid.'.modifiedby'], @$olddata[$dataid.'.timemodified'])
			: '',
]);

if ($fromform = $studentform->get_data()) {
    $context = context_system::instance(); // TODO: which context to use?
	foreach ($categories as $dataid => $category) {
	    if (array_key_exists('type', $category)) {
            switch ($category['type']) {
                case 'image':
                    file_save_draft_area_files($fromform->images[$dataid], $context->id, 'block_exastud', 'report_image_'.$dataid,
                            $student->id, array('subdirs' => 0, 'maxbytes' => $category['maxbytes'], 'maxfiles' => 1));
                    break;
                default:
                    block_exastud_set_class_student_data($classid, $studentid, $dataid, $fromform->{$dataid});
            }
        } else {
            block_exastud_set_class_student_data($classid, $studentid, $dataid, $fromform->{$dataid});
        }
        block_exastud_set_class_student_data($classid, $studentid, $dataid.'.modifiedby', $USER->id);
        block_exastud_set_class_student_data($classid, $studentid, $dataid.'.timemodified', time());
	}
	redirect($returnurl);
}

echo $output->header(array('review',
	array('name' => $classheader, 'link' => $parenturl),
), array('noheading'));

echo $output->heading($classheader);

if ($type == BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN) {
	$user = $student;
	$userReport = block_exastud_get_report($user->id, $actPeriod->id);
	$userCategoryReviews = block_exastud_get_reviewers_by_category($actPeriod->id, $user->id, false);
    $teachers = array();
	foreach ($userCategoryReviews as $rid => $r) {
        $tmpUser = block_exastud_get_user($rid);
        $teachers[$rid] = fullname($tmpUser);
    }
    asort($teachers);

	$table = new html_table();
	$headerrow = new html_table_row();
	$userData = new html_table_cell();
    $userData->rowspan = 2;
    $userData->header = true;
    $userData->text = $OUTPUT->user_picture($user, array("courseid" => $courseid)).fullname($user);
    $headerrow->cells[] = $userData;
    $average = new html_table_cell();
    $average->header = true;
    $average->rowspan = 2;
    $average->text = block_exastud_get_string('average');
    $headerrow->cells[] = $average;
    // teachers
    foreach ($teachers as $teacherId => $teacherName) {
        $hCell = new html_table_cell();
        $hCell->header = true;
        $hCell->text = $teacherName;
        $hCell->colspan = count($userCategoryReviews[$teacherId]);
        $headerrow->cells[] = $hCell;
    }
    $table->data[] = $headerrow;
    // subjects
    $subjectsRow = new html_table_row();
    foreach ($teachers as $teacherId => $teacherName) {
        foreach (array_keys($userCategoryReviews[$teacherId]) as $subjectId) {
            $subj = new html_table_cell();
            $subj->header = true;
            $subj->text = $DB->get_field('block_exastudsubjects', 'title', ['id' => $subjectId]);
            $subjectsRow->cells[] = $subj;
        }
    }
    $table->data[] = $subjectsRow;

    $reviewcategories = block_exastud_get_class_categories($classid);
    foreach ($reviewcategories as $category) {
        $row = array();
        $categoryCell = new html_table_cell();
        $categoryCell->text = $category->title;
        $row[] = $categoryCell;
        $row[] = @$userReport->category_averages[$category->source.'-'.$category->id];
        foreach ($teachers as $teacherId => $teacherName) {
            if (array_key_exists($teacherId, $userCategoryReviews)) {
                foreach (array_keys($userCategoryReviews[$teacherId]) as $subjectId) {
                    if (array_key_exists($subjectId, $userCategoryReviews[$teacherId])
                            && array_key_exists($category->source, $userCategoryReviews[$teacherId][$subjectId])
                            && array_key_exists($category->id, $userCategoryReviews[$teacherId][$subjectId][$category->source])) {
                        $row[] = $userCategoryReviews[$teacherId][$subjectId][$category->source][$category->id];
                    } else {
                        $row[] = '';
                    }
                }
            } else {
                $row[] = '';
            }
        }
        $table->data[] = $row;
    }
    echo $output->table($table);

	/*$table = new html_table();

	$reviewcategories = block_exastud_get_class_categories($classid);

	$table->head = array();
	$table->head[] = '';
	$table->head[] = block_exastud_get_string('name');
	$table->head[] = block_exastud_trans('de:Geburtsdatum');
	foreach ($reviewcategories as $category) {
		$table->head[] = $category->title;
	}

	$table->align = array();
	$table->align[] = 'center';
	$table->align[] = 'left';
	$table->align[] = 'left';
	for ($i = 0; $i < count($reviewcategories); $i++) {
		$table->align[] = 'center';
	}

	$row = array();
	$row[] = $OUTPUT->user_picture($user, array("courseid" => $courseid));
	$row[] = fullname($user);
	$row[] = block_exastud_get_date_of_birth($student->id);

	foreach ($reviewcategories as $category) {
		$row[] = @$userReport->category_averages[$category->source.'-'.$category->id];
	}

	$table->data[] = $row;

	echo $output->table($table);*/

	$vorschlaege = [];
	foreach (block_exastud_get_class_teachers($classid) as $class_teacher) {
		if ($class_teacher->subjectid == BLOCK_EXASTUD_SUBJECT_ID_ADDITIONAL_HEAD_TEACHER) {
			continue;
		}

		if (isset($vorschlaege[$class_teacher->userid])) {
			$vorschlaege[$class_teacher->userid]->subject_title .= ', '.$class_teacher->subject_title;
			continue;
		}

		$class_teacher->vorschlag = $DB->get_field('block_exastudreview', 'review', [
			'studentid' => $studentid,
			'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG,
			'periodid' => $actPeriod->id,
			'teacherid' => $class_teacher->userid,
		]);

		if ($class_teacher->vorschlag) {
			$vorschlaege[$class_teacher->userid] = $class_teacher;
		}
	}

	echo '<legend>'.block_exastud_get_string("textblock").'</legend>';

	if ($vorschlaege) {
		foreach ($vorschlaege as $vorschlag) {
			echo '<div style="font-weight: bold;">'.$vorschlag->subject_title.':</div>'.$vorschlag->vorschlag;
			echo '<hr>';
		}
	} else {
		echo block_exastud_trans('de:Keine Vorschläge gefunden');
	}

} else {
	$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)).' '.fullname($student);
	echo $OUTPUT->heading($studentdesc);
}

$formdata = $olddata;

if (count($categories) > 0) {
    $context = context_system::instance(); // TODO: which context to use?
    foreach ($categories as $dataid => $category) {
        if (array_key_exists('type', $category) && $category['type'] == 'image') {
            //if (!array_key_exists('images', $formdata)) {
            //    $formdata['images'] = array();
            //}
            $draftitemid = file_get_submitted_draft_itemid('report_image_'.$dataid);
            file_prepare_draft_area($draftitemid, $context->id, 'block_exastud', 'report_image_'.$dataid, $student->id,
                    array('subdirs' => 0, 'maxbytes' => $category['maxbytes'], 'maxfiles' => 1));
            $formdata['images['.$dataid.']'] = $draftitemid;
        } else {
            $formdata[$dataid] = block_exastud_html_to_text(@$formdata[$dataid]);
        }
    }
}
/*
$studentdata = block_exastud_get_class_student_data($classid, $studentid);
$formdata = new stdClass;
foreach ($categories as $dataid=>$category) {
	$formdata->{$dataid} = array('text'=>isset($studentdata[$dataid]) ? $studentdata[$dataid] : '', 'format'=>FORMAT_HTML);
}
*/

$studentform->set_data($formdata);
$studentform->display();

echo $output->back_button($returnurl);

echo $output->footer();
