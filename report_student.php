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

namespace block_exastud;

use \block_exastud\globals as g;

require __DIR__.'/inc.php';

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);

require_login($courseid);

block_exastud_require_global_cap(CAP_VIEW_REPORT);

if (!block_exastud_is_new_version()) die('not allowed');

// is my class?
$class = get_teacher_class($classid);

/*
if (!$DB->count_records('block_exastudclassteachers', array('teacherid' => $USER->id, 'classid' => $classid))) {
	print_error('badclass', 'block_exastud');
}
*/
if (!$DB->count_records('block_exastudclassstudents', array('studentid' => $studentid, 'classid' => $classid))) {
	print_error('badstudent', 'block_exastud');
}
if (!$student = $DB->get_record('user', array('id' => $studentid))) {
	print_error('badstudent', 'block_exastud');
}

$textReviews = get_text_reviews($class, $studentid);
$categories = get_class_categories_for_report($studentid, $class->id);

/*
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
	* /
	$templateProcessor->saveAs('result.docx');
	
	exit;
}
*/

$outputType = optional_param('output', '', PARAM_TEXT);
if (in_array($outputType, ['docx', 'docx_test'])) {
	$dateofbirth = get_custom_profile_field_value($student->id, 'dateofbirth');

	require_once __DIR__.'/classes/PhpWord/Autoloader.php';
	\PhpOffice\PhpWord\Autoloader::register();

	\PhpOffice\PhpWord\Settings::setTempDir($CFG->tempdir);

	$phpWord = new \PhpOffice\PhpWord\PhpWord();

	$pageWidthTwips = 9200;
	$tableWidthTwips = 9200 - 200;
	$tmpLogoFile = null;

	function block_exastud_report_standard_footer($section) {
		$footer = $section->createFooter();
		$footer->addPreserveText('Seite {PAGE} von {NUMPAGES}', null, ['align' => 'center']);
	}

	function block_exastud_report_standard_header($section) {
		global $student, $class;

		$header = $section->createHeader();
		$header->addTExt($student->lastname.', '.$student->firstname.', '.$class->title.', '.block_exastud_get_active_period()->description);
	}

	function block_exastud_report_wrapper_table() {
		global $pageWidthTwips, $section;

		// äußere tabelle, um cantSplit zu setzen (dadurch wird innere tabelle auf einer seite gehalten)
		$table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0]);
		$table->addRow(null, ['cantSplit' => true]);
		$cell = $table->addCell($pageWidthTwips + 100); // add some extra spacing, else borders don't work
		// $cell->getStyle()->setBgColor('99999');

		return $cell;
	}

	function block_exastud_report_header_body_table($header, $body = null) {
		global $tableWidthTwips;

		$cell = block_exastud_report_wrapper_table();

		// innere tabelle
		$table = $cell->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
		$table->addRow();
		$cell = $table->addCell($tableWidthTwips);
		$cell->getStyle()->setBgColor('D9D9D9');
		$cell->addText($header, ['bold' => true]);

		$table->addRow();
		\PhpOffice\PhpWord\Shared\Html::addHtml($table->addCell($tableWidthTwips), $body);

		return $table;
	}

	function block_exastud_report_subject_table($header, $body, $right) {
		global $tableWidthTwips;

		$cell = block_exastud_report_wrapper_table();

		// innere tabelle
		$table = $cell->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
		$table->addRow();
		$cell = $table->addCell($tableWidthTwips);
		$cell->getStyle()->setBgColor('D9D9D9');
		$cell->getStyle()->setGridSpan(2);
		$cell->addText($header, ['bold' => true]);

		$table->addRow();
		$cell = $table->addCell($tableWidthTwips / 4 * 3);
		\PhpOffice\PhpWord\Shared\Html::addHtml($cell, $body);

		$cell = $table->addCell($tableWidthTwips / 4);
		\PhpOffice\PhpWord\Shared\Html::addHtml($cell, $right);

		return $table;
	}

	;

	$section = $phpWord->addSection();
	block_exastud_report_standard_footer($section);
	// no header here

	// BW will kein logo
	if (false && $logo = block_exastud_get_main_logo()) {
		$tmpLogoFile = $logo->copy_content_to_temp();
		try {
			$section->addImage($tmpLogoFile, [
				'width' => round(\PhpOffice\PhpWord\Shared\Converter::cmToPixel(3.8)), // width: 3.8cm
				// 'width' => round(35 * 3.8), // width: 3.8cm
				'align' => 'center',
			]);
		} catch (\PhpOffice\PhpWord\Exception\InvalidImageException $e) {
			print_error(trans('en:The configured header image has a not supported format, please contat your administrator'));
		}
	}

	if (get_config('exastud', 'school_name')) $section->addText(get_config('exastud', 'school_name'),
		['size' => 26, 'bold' => false], ['align' => 'center', 'spaceBefore' => 250, 'spaceAfter' => 10]);
	$section->addText('Gemeinschaftsschule',
		['size' => 26, 'bold' => false], ['align' => 'center', 'spaceBefore' => 10, 'spaceAfter' => 10]);
	$section->addText('Lernentwicklungsbericht',
		['size' => 26, 'bold' => true], ['align' => 'center', 'spaceBefore' => 10, 'spaceAfter' => 200]);
	$section->addText('Information über die Lernentwicklung im '.block_exastud_get_active_period()->description,
		['size' => 14], ['align' => 'center', 'lineHeight' => 1, 'spaceAfter' => 100]);
	$section->addText('für',
		['size' => 14], ['align' => 'center', 'lineHeight' => 1, 'spaceAfter' => 300]);

	$table = block_exastud_report_wrapper_table()->addTable(array('borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80));
	$table->addRow();
	$table->addCell(2500)->addText('Vorname, Name');
	$table->addCell($tableWidthTwips - 2500)->addText($student->firstname.', '.$student->lastname);
	$table->addRow();
	$table->addCell()->addText(trans('de:Geburtsdatum'));
	$table->addCell()->addText($dateofbirth);
	$table->addRow();
	$table->addCell()->addText('Lerngruppe');
	$table->addCell()->addText($class->title);


	$studentdata = get_class_student_data($classid, $studentid);

	// title => required
	$bp2004subjects = array_map(function($a) {
		return explode("\t", $a);
	}, preg_split('!\s*\n\s*!', trim('
		Alevitische Religionslehre (RALE)	0
		Altkatholische Religionslehre (RAK)	0
		Ehtik (ETH)	0
		Evangelische Religionslehre (REV)	0
		Islamische Religionslehre sunnitischer Prägung (RISL)	0
		Jüdische Religionslehre (RJUED)	0
		Katholische Religionslehre (RRK)	0
		Orthodoxe Religionslehre (ROR)	0
		Syrisch-Orthodoxe Religionslehre (RSYR)	0
		Deutsch	1
		Mathematik	1
		Englisch	1
		EWG (Erdkunde, Wirtschaftskunde, Gemeinschaftskunde)	1
		NWA (Naturwissenschaftliches Arbeiten)	1
		Geschichte	1
		Bildende Kunst	1
		Musik	1
		Sport	1
		Französisch	0
		Technik	0
		Mensch und Umwelt (Mum)	0
		Bildende Kunst	0
		Musik	0
		NwT	0
		Sport	0
		Spanisch	0
	')));

	$textReviews = $DB->get_records_sql("
		SELECT DISTINCT s.title AS id, r.review, s.title AS title, r.subjectid AS subjectid
		FROM {block_exastudreview} r
		JOIN {block_exastudsubjects} s ON r.subjectid = s.id
		JOIN {block_exastudclass} c ON c.periodid = r.periodid
		JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid = r.teacherid AND ct.subjectid=r.subjectid

		WHERE r.studentid = ? AND r.periodid = ? AND TRIM(r.review) !=  ''
		GROUP BY s.title",
		array($studentid, $class->periodid));

	$subjects = [];
	foreach ($bp2004subjects as $bp2004subject) {
		$title = $bp2004subject[0];
		$required = $bp2004subject[1];

		if (isset($textReviews[$title])) {
			$textReview = $textReviews[$title];
			$subjects[] = (object)array_merge((array)$textReview, (array)\block_exastud\get_subject_student_data($class->id, $textReview->subjectid, $studentid));
		} elseif ($required) {
			$subjects[] = (object)[
				'title' => $title,
				'review' => '',
			];
		}
	}

	/*
	$table = $section->addTable(['borderSize'=>0, 'borderColor' => 'FFFFFF', 'cellMargin'=>0]);
	$table->addRow(null, ['cantSplit'=>true]);
	$table->getStyle()->setBorderSize(0);
	$table->getStyle()->setCellMargin(-80);
	$cell = $table->addCell($pageWidthTwips);
	$cell->addText("fsdfssdf");
	$cell->getStyle()->setBgColor('333333');

	$table = $cell->addTable(['borderSize'=>0, 'borderColor' => 'FFFFFF', 'cellMargin'=>0]);
	$table->addRow();
	$table->getStyle()->setBorderSize(0);
	$table->getStyle()->setCellMargin(0);
	$cell = $table->addCell($tableWidthTwips);
	$cell->addText("fsdfssdf");
	$cell->getStyle()->setBgColor('666666');
	// $table->getStyle()->set
	*/

	$section = $phpWord->addSection();
	block_exastud_report_standard_footer($section);
	block_exastud_report_standard_header($section);

	// $section->addPageBreak();
	// phpword bug: pagebreak needs some text
	// $section->addText('.', ['size' => 1, 'color'=>'ffffff']);

	$lern_und_sozialverhalten = g::$DB->get_record('block_exastudreview', array('teacherid' => $class->userid, 'subjectid' => SUBJECT_ID_LERN_UND_SOZIALVERHALTEN, 'periodid' => $class->periodid, 'studentid' => $studentid));
	$table = block_exastud_report_header_body_table(trans('de:Lern- und Sozialverhalten'), $lern_und_sozialverhalten);
	if (empty($lern_und_sozialverhalten)) {
		$cell = $table->getRows()[1]->getCells()[0];
		$cell->addText('');
		$cell->addText('');
		$cell->addText('');
		$cell->addText('');
	}

	foreach ($subjects as $textReview) {
		block_exastud_report_subject_table(
			$textReview->title,
			$textReview->review,
			'Niveau: '.(@$textReview->gme ?: '---').'<br />'.
			(trim(@$textReview->grade) ? 'Note: '.$textReview->grade.'<br />' : '')
		);
	}

	/*
	$cell = $header_body_cell('Ateliers');
	if (empty($studentdata['ateliers'])) {
		$cell->addText('');
	} else {
		\PhpOffice\PhpWord\Shared\Html::addHtml($cell, $studentdata['ateliers']);
	}

	$cell = $header_body_cell('Arbeitsgemeinschaften');
	if (empty($studentdata['arbeitsgemeinschaften'])) {
		$cell->addText('');
	} else {
		\PhpOffice\PhpWord\Shared\Html::addHtml($cell, $studentdata['arbeitsgemeinschaften']);
	}

	$cell = $header_body_cell('Besondere Stärken');
	if (empty($studentdata['besondere_staerken'])) {
		$cell->addText('');
		$cell->addText('');
		$cell->addText('');
		$cell->addText('');
	} else {
		\PhpOffice\PhpWord\Shared\Html::addHtml($cell, $studentdata['besondere_staerken']);
	}
	*/

	$section = $phpWord->addSection();
	block_exastud_report_standard_footer($section);
	block_exastud_report_standard_header($section);

	$table = block_exastud_report_header_body_table('Bemerkungen', @$studentdata['comments']);
	if (empty($studentdata['comments'])) {
		$cell = $table->getRows()[1]->getCells()[0];
		$cell->addText('');
		$cell->addText('');
		$cell->addText('');
		$cell->addText('');
	}

	block_exastud_report_header_body_table('Anlagen', 'Kompetenzprofile<br />Zielvereinbarungen');

	$section->addText('');
	$section->addText('');
	$section->addText("Lernentwicklungsgespräch(-e) Datum: _________________");
	$section->addText('');
	$location = get_config('exastud', 'school_location');
	$certificate_issue_date = trim(get_config('exastud', 'certificate_issue_date'));
	$section->addText(($location ?: "[Ort]").", den ".($certificate_issue_date ?: "______________"));
	$section->addText('');
	$section->addText('');
	$section->addText('');
	$section->addText("Unterschriften", ['bold' => true]);
	$section->addText('');

	$table = block_exastud_report_wrapper_table()->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
	$table->addRow();
	$cell = $table->addCell($tableWidthTwips / 4);
	$cell->addText('');
	$cell->addText('');
	$cell->addText('');
	$cell = $table->addCell($tableWidthTwips / 4);
	$cell->addText('');
	$cell->addText('');
	$cell->addText('');
	$cell = $table->addCell($tableWidthTwips / 4);
	$cell->addText('');
	$cell->addText('');
	$cell->addText('');
	$cell = $table->addCell($tableWidthTwips / 4);
	$cell->addText('');
	$cell->addText('');
	$cell->addText('');
	$table->addRow();
	$cell = $table->addCell($tableWidthTwips / 4);
	$cell->addText('Schüler /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	$cell->addText('Schülerin', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	$cell = $table->addCell($tableWidthTwips / 4);
	$cell->addText('Erziehungsberechtiger /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	$cell->addText('Erziehungsberechtige', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	$cell = $table->addCell($tableWidthTwips / 4);
	$cell->addText('Lernbegleiter /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	$cell->addText('Lernbegleiterin', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	$cell = $table->addCell($tableWidthTwips / 4);
	$cell->addText('Schulleiter /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	$cell->addText('Schulleiterin', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);

	$filename = ($certificate_issue_date ?: date('Y-m-d'))."-Lernentwicklungsbericht-{$class->title}-{$student->lastname}-{$student->firstname}.docx";

	if ($outputType == 'docx_test') {
		// testing:
		echo "<h1>testing, filename: $filename</h1>";
		echo \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML')->getContent();
		exit;
	}

	$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

	// // save as a random file in temp file
	$temp_file = tempnam($CFG->tempdir, 'PHPWord');
	$objWriter->save($temp_file);

	if ($tmpLogoFile) unlink($tmpLogoFile);

	require_once $CFG->dirroot.'/lib/filelib.php';

	// Your browser will name the file "myFile.docx"
	// regardless of what it's named on the server
	send_temp_file($temp_file, $filename);

	exit;
}


$url = '/blocks/exastud/report_student.php';
$PAGE->set_url($url);

$output = get_renderer();

$strstudentreview = get_string('reviewstudent');
$strclassreview = get_string('reviewclass');
echo $output->header(array('report',
	array('name' => $strclassreview, 'link' => $CFG->wwwroot.'/blocks/exastud/review_class.php?courseid='.$courseid.
		'&classid='.$classid),
	'='.$strstudentreview,
), array('noheading'));


$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)).' '.fullname($student);

echo $output->heading($studentdesc);

echo get_renderer()->print_student_report($categories, $textReviews);

echo $output->back_button(new url('report.php', ['courseid' => $courseid, 'classid' => $classid ]));

echo $output->footer();
