<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// All rights reserved
/**
 * @package moodlecore
 * @subpackage blocks
 * @copyright 2013 gtn gmbh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
*/

require("inc.php");

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);

require_login($courseid);

block_exastud_require_global_cap(block_exastud::CAP_USE);

if (!is_new_version()) die('not allowed');

if (!$class = $DB->get_record('block_exastudclass', array('id' => $classid))) {
    print_error('badclass', 'block_exastud');
}
if (!$DB->count_records('block_exastudclassteachers', array('teacherid' => $USER->id, 'classid' => $classid))) {
    print_error('badclass', 'block_exastud');
}
if (!$DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid))) {
    print_error('badstudent', 'block_exastud');
}
if (!$student = $DB->get_record('user', array('id' => $studentid))) {
    print_error('badstudent', 'block_exastud');
}



$textReviews = iterator_to_array($DB->get_recordset_sql("
    SELECT ".user_picture::fields('u').", r.review, s.title AS subject, r.subjectid AS subjectid
    FROM {block_exastudreview} r
    JOIN {user} u ON r.teacherid = u.id
    LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
    WHERE r.studentid = ? AND r.periodid = ? AND TRIM(r.review) !=  ''
    ORDER BY NOT(r.subjectid<0), s.title, u.lastname, u.firstname -- TODO: anpassen",
array($studentid, $class->periodid)), false);

foreach ($textReviews as $textReview) {
    if ($textReview->subjectid == block_exastud::SUBJECT_ID_LERN_UND_SOZIALVERHALTEN)
        $textReview->title = block_exastud::t('Lern- und Sozialverhalten');
    elseif ($textReview->subject)
        $textReview->title = $textReview->subject.' ('.fullname($textReview).')';
    else
        $textReview->title = fullname($textReview);
}

$evaluationOtions = block_exastud_get_evaluation_options();
$categories = block_exastud_get_class_categories($classid);
$current_parent = null;
foreach ($categories as $category){

    $category->fulltitle = $category->title;
    if (preg_match('!^([^:]*):\s*([^\s].*)$!', $category->fulltitle, $matches)) {
        $category->parent = $matches[1];
        $category->title = $matches[2];
    } else {
        $category->parent = '';
        $category->title = $category->fulltitle;
    }

    $category->evaluationOtions = [];
    foreach ($evaluationOtions as $pos_value => $option) {
        $category->evaluationOtions[$pos_value] = (object)[
            'value' => $pos_value,
            'title' => $option,
            'reviewers' => iterator_to_array($DB->get_recordset_sql("
                            SELECT u.*, s.title AS subject
                            FROM {block_exastudreview} r
                            JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
                            JOIN {user} u ON r.teacherid = u.id
                            JOIN {block_exastudclass} c ON c.periodid = r.periodid
                            LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
                            WHERE r.studentid = ? AND c.id = ?
                                AND pos.categoryid = ? AND pos.categorysource = ?
                                AND pos.value = ?
                            ", array($studentid, $classid, $category->id, $category->source, $pos_value)), true)
        ];
    }
}

if (optional_param('output', '', PARAM_TEXT) == 'template_test') {
    require_once __DIR__.'/classes/PhpWord/Autoloader.php';
    \PhpOffice\PhpWord\Autoloader::register();
    
    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('template.docx');
    
    // Variables on different parts of document
    $templateProcessor->setValue('name', htmlspecialchars($student->firstname.', '.$student->lastname));
    $templateProcessor->setValue('lern_und_sozialverhalten', htmlspecialchars($student->firstname.', '.$student->lastname));
    $templateProcessor->cloneRow('userId', 4);
/*
    $templateProcessor->setValue('serverName', htmlspecialchars(realpath(__DIR__), ENT_COMPAT, 'UTF-8')); // On header
    
    // Simple table
    $templateProcessor->cloneRow('rowValue', 10);
    
    $templateProcessor->setValue('rowValue#1', htmlspecialchars('Sun', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowValue#2', htmlspecialchars('Mercury', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowValue#3', htmlspecialchars('Venus', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowValue#4', htmlspecialchars('Earth', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowValue#5', htmlspecialchars('Mars', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowValue#6', htmlspecialchars('Jupiter', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowValue#7', htmlspecialchars('Saturn', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowValue#8', htmlspecialchars('Uranus', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowValue#9', htmlspecialchars('Neptun', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowValue#10', htmlspecialchars('Pluto', ENT_COMPAT, 'UTF-8'));
    
    $templateProcessor->setValue('rowNumber#1', htmlspecialchars('1', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowNumber#2', htmlspecialchars('2', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowNumber#3', htmlspecialchars('3', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowNumber#4', htmlspecialchars('4', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowNumber#5', htmlspecialchars('5', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowNumber#6', htmlspecialchars('6', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowNumber#7', htmlspecialchars('7', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowNumber#8', htmlspecialchars('8', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowNumber#9', htmlspecialchars('9', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('rowNumber#10', htmlspecialchars('10', ENT_COMPAT, 'UTF-8'));
    
    // Table with a spanned cell
    $templateProcessor->cloneRow('userId', 3);
    
    $templateProcessor->setValue('userId#1', htmlspecialchars('1', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('userFirstName#1', htmlspecialchars('James', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('userName#1', htmlspecialchars('Taylor', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('userPhone#1', htmlspecialchars('+1 428 889 773', ENT_COMPAT, 'UTF-8'));
    
    $templateProcessor->setValue('userId#2', htmlspecialchars('2', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('userFirstName#2', htmlspecialchars('Robert', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('userName#2', htmlspecialchars('Bell', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('userPhone#2', htmlspecialchars('+1 428 889 774', ENT_COMPAT, 'UTF-8'));
    
    $templateProcessor->setValue('userId#3', htmlspecialchars('3', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('userFirstName#3', htmlspecialchars('Michael', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('userName#3', htmlspecialchars('Ray', ENT_COMPAT, 'UTF-8'));
    $templateProcessor->setValue('userPhone#3', htmlspecialchars('+1 428 889 775', ENT_COMPAT, 'UTF-8'));
    
    echo date('H:i:s'), ' Saving the result document...', EOL;
    */
    $templateProcessor->saveAs('result.docx');
    
    exit;
}

if (optional_param('output', '', PARAM_TEXT) == 'docx') {
    require_once __DIR__.'/classes/PhpWord/Autoloader.php';
    \PhpOffice\PhpWord\Autoloader::register();
    
    \PhpOffice\PhpWord\Settings::setTempDir($CFG->tempdir);
    
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();
    
    $pageWidthTwips = 9200;

    $section->addText('Lernentwicklungsbericht',
        ['size' => 26, 'bold' => true], ['align'=>'center', 'spaceBefore'=>1400, 'spaceAfter'=>200]);
    $section->addText('Information über die Lernentwicklung im Wählen Sie ein Element aus. Schulhalbjahr 20XX/20XX',
        ['size' => 14], ['align'=>'center', 'lineHeight'=>1, 'spaceAfter'=>100]);
    $section->addText('für',
        ['size' => 14], ['align'=>'center', 'lineHeight'=>1, 'spaceAfter'=>300]);
    
    $table = $section->addTable(array('borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80));
    $table->addRow();
    $table->addCell(2500)->addText('Vorname, Name');
    $table->addCell($pageWidthTwips-2500)->addText($student->firstname.', '.$student->lastname);
    $table->addRow();
    $table->addCell()->addText('Geburtsdatum');
    $table->addCell();
    $table->addRow();
    $table->addCell()->addText('Lerngruppe');
    $table->addCell();
    
    $section->addPageBreak();
    $section->addText(' ');

    for ($i = 0; $i < 5; $i++) {
        if ($i > 0) $section->addText('Zum testen, werden die Reviews wiederholt ausgegeben');
        foreach($textReviews as $textReview) {
            // äußere tabelle, um cantSplit zu setzen (dadurch wird innere tabelle auf einer seite gehalten)
            $table = $section->addTable(['borderSize'=>0, 'borderColor' => 'FFFFFF', 'cellMargin'=>0]);
            $table->addRow(null, ['cantSplit'=>true]);
            $cell = $table->addCell($pageWidthTwips);
            
            // innere tabelle
            $table = $cell->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
            $table->addRow();
            $table->addCell($pageWidthTwips, ['bgColor' => 'F2F2F2'])->addText($textReview->title);
            $table->addRow();
            \PhpOffice\PhpWord\Shared\Html::addHtml($table->addCell($pageWidthTwips), $textReview->review);
        }
    }
    
    // echo \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML')->getContent();

    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

    // // save as a random file in temp file
    $temp_file = tempnam($CFG->tempdir, 'PHPWord');
    $objWriter->save($temp_file);
    
    // Your browser will name the file "myFile.docx"
    // regardless of what it's named on the server
    send_temp_file($temp_file, "Lernstandsbericht ".fullname($student).".docx");
    unlink($temp_file);  // remove temp file
    
    // $objWriter->save('helloWorld.docx');
    
    exit;
}





$url = '/blocks/exastud/report_student.php';
$PAGE->set_url($url);
$blockrenderer = $PAGE->get_renderer('block_exastud');

$strstudentreview = block_exastud_get_string('reviewstudent', 'block_exastud');
$strclassreview = block_exastud_get_string('reviewclass', 'block_exastud');
block_exastud_print_header(array('review',
    array('name' => $strclassreview, 'link' => $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid .
        '&classid=' . $classid),
    '=' . $strstudentreview
        ), array('noheading'));


$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)) . ' ' . fullname($student, $student->id);

echo $OUTPUT->heading($studentdesc);

echo '<table id="review-table">';

$current_parent = null;
foreach ($categories as $category){
    
    if ($current_parent !== $category->parent) {
        $current_parent = $category->parent;
        echo '<tr><th class="category category-parent">'.($category->parent?$category->parent.':':'').'</th>';
        foreach ($category->evaluationOtions as $option) {
            echo '<th class="evaluation-header"><b>' . $option->title . '</th>';
        }
        echo '</tr>';
    }
    
    echo '<tr><td class="category">'.$category->title.'</td>';

    foreach ($category->evaluationOtions as $pos_value => $option) {
        echo '<td class="evaluation">';

        echo join(', ', array_map(function($reviewer){
            return $reviewer->subject?$reviewer->subject.' ('.fullname($reviewer).')':fullname($reviewer);
        }, $option->reviewers));

        echo '</td>';
    }
    echo '</tr>';
}

echo '</table>';




echo '<h3>'.get_string('detailedreview','block_exastud').'</h3>';

echo '<table id="ratingtable">';
foreach($textReviews as $textReview) {
    echo '<tr><td class="ratinguser">'.$textReview->title.'</td>
        <td class="ratingtext">'.format_text($textReview->review).'</td>
        </tr>';
}
echo '</table>';

block_exastud_print_footer();
