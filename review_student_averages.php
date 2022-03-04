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
$studentid = required_param('studentid', PARAM_INT);

setcookie('lastclass', $classid);

block_exastud_require_login($courseid);

$returnurl = new moodle_url('/blocks/exastud/review_class_averages.php?courseid='.$courseid.'&classid='.$classid.'&openclass='.$classid);

$output = block_exastud_get_renderer();

$PAGE->set_url('/blocks/exastud/review_student_averages.php', [
	'courseid' => $courseid,
	'classid' => $classid,
	'studentid' => $studentid,
]);

if (!$class = block_exastud_get_class($classid)) {
	throw new moodle_exception("badclass", "block_exastud");
}
if (!block_exastud_is_class_teacher($classid, $USER->id)) {
    throw new moodle_exception("not a class teacher");
}
$student = $DB->get_record('user', array('id' => $studentid, 'deleted' => 0));

$actPeriod = block_exastud_check_active_period();

$classheader = $class->title.' - '.block_exastud_get_string('review_class_averages');

$studentform = new student_average_calculation_form($PAGE->url, [
    'studentid' => $studentid,
    'classid' => $classid,
	'courseid' => $courseid,
]);

$classSubjects = block_exastud_get_class_subjects($class);
block_exastud_add_projektarbait_to_subjectlist($class, $studentid, $classSubjects);
$subjectIds = array_map(function($s) {return $s->id;}, $classSubjects);
if ($fromform = $studentform->get_data()) {
    $factors = required_param_array('factors', PARAM_INT);
    foreach ($subjectIds as $sKey => $sId) {
        if (array_key_exists($sId, $factors)) {
            $factorValue = $factors[$sId];
            $factorValue = block_exastud_check_factors_limit($factorValue);
            if ($sId == BLOCK_EXASTUD_PROJECTARBAIT_FOR_AVERAGE_CALCULATING) {
                block_exastud_set_class_student_data($classid, $studentid, BLOCK_EXASTUD_PROJECTARBAIT_FOR_AVERAGE_CALCULATING_PARAMNAME, $factorValue);
            } else {
                block_exastud_set_subject_student_data($classid, $sId, $studentid, 'subject_average_factor', $factorValue);
            }
        }
    }
    $average = block_exastud_calculate_student_average($classid, $studentid);
    block_exastud_set_class_student_data($classid, $studentid, 'grade_average_calculated', $average);
    // if it is "EXPORT" button - create xls after saving
    $export_button = optional_param('export_xls', 0, PARAM_RAW);
    if ($export_button) {
        $xls = \block_exastud\printer::averages_to_xls($class, $studentid);
        ob_clean();
        if ($content = ob_get_clean()) {
            throw new \Exception('there was some other output: '.$content);
        }
        send_temp_file($xls->temp_file, basename($xls->filename));
    }
//	redirect($PAGE->url);
	redirect($returnurl);
}

$formdata = array();//(array)block_exastud_get_class_student_data($classid, $studentid);
$studentform->set_data($formdata);

echo $output->header(array('review',
	array('name' => $classheader, 'link' => $returnurl),
), array('noheading'));

echo $output->heading($classheader);

$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)).' '.fullname($student);
echo $OUTPUT->heading($studentdesc);

//$studentform->display();
$formHtml = $studentform->render();
$doc = new DomDocument;
$doc->validateOnParse = false;
@$doc->loadHTML(mb_convert_encoding($formHtml, 'HTML-ENTITIES', 'UTF-8'));
$allInputs = $doc->getElementsByTagName('input');
$factorElements = array();
foreach ($allInputs as $inp) {
    if (strpos($inp->getAttribute('name'), 'factors') !== false) {
        $factorElements[$inp->getAttribute('name')] = $inp;
    }
}
foreach ($classSubjects as $subject) {
    $inputName = 'factors['.$subject->id.']';
    if (array_key_exists($inputName, $factorElements)) {
        $inputEl = $factorElements[$inputName];
        $inputEl->setAttribute('autocomplete', 'off');
        // find parent with class 'form-group' to delete after moving
        // !!! Is there possible bad Moodle Theme for this process?
        $inputElWrapperTemp = $inputEl->parentNode;
        $inputElWrapper = null;
        $depth = 1;
        while ($depth <= 5) {
            if (strpos($inputElWrapperTemp->getAttribute('class'), 'form-group') !== false) {
                $inputElWrapper = $inputElWrapperTemp;
                break;
            }
            $inputElWrapperTemp = $inputElWrapperTemp->parentNode;
        }

        $insertMarker = $doc->getElementById('tempSubject_'.$subject->id);
        $insertWrapper = $insertMarker->parentNode;
        $insertWrapper->insertBefore($inputEl, $insertMarker);
        if ($inputElWrapper) {
            $inputElWrapper->parentNode->removeChild($inputElWrapper);
        }
    }
}
// display result HTML
echo $doc->saveHTML();

echo $output->back_button($returnurl);

echo $output->footer();
