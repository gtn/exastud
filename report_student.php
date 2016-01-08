<?php

namespace block_exastud;
use block_exastud;

require __DIR__.'/inc.php';

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);

require_login($courseid);

block_exastud_require_global_cap(block_exastud::CAP_USE);

if (!block_exastud_is_new_version()) die('not allowed');

// is my class?
if (!$class = $DB->get_record('block_exastudclass', array('id' => $classid, 'userid' => $USER->id))) {
	print_error('badclass', 'block_exastud');
}
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

$textReviews = get_text_reviews($studentid, $class->periodid);
$categories = get_class_categories_for_report($studentid, $class->id);

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

$outputType = optional_param('output', '', PARAM_TEXT);
if (in_array($outputType, ['docx', 'docx_test'])) {
	$birthday = $DB->get_field_sql("SELECT uid.data
		FROM {user_info_data} uid
		JOIN {user_info_field} uif ON uif.id=uid.fieldid AND uif.shortname='dateofbirth'
		WHERE uid.userid=?
		", [$student->id]);
	
	
	require_once __DIR__.'/classes/PhpWord/Autoloader.php';
	\PhpOffice\PhpWord\Autoloader::register();
	
	\PhpOffice\PhpWord\Settings::setTempDir($CFG->tempdir);
	
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$section = $phpWord->addSection();
	
	$pageWidthTwips = 9200;
	$tmpLogoFile = null;

	// note: image can't have spacing, so add some spacing text
	$section->addText(' ',
		null, ['align'=>'center', 'spaceBefore'=>1400]);

	if ($logo = block_exastud_get_main_logo()) {
		$tmpLogoFile = $logo->copy_content_to_temp();
		try {
			$section->addImage($tmpLogoFile, [/* 'width' => '500', */
				'align' => 'center']);
		} catch (\PhpOffice\PhpWord\Exception\InvalidImageException $e) {
			print_error(trans('en:The configured header image has a not supported format, please contat your administrator'));
		}
	}

	$section->addText(get_config('exastud', 'school_name'),
		['size' => 26, 'bold' => false], ['align'=>'center', 'spaceBefore'=>250, 'spaceAfter'=>10]);
	$section->addText('Gemeinschaftsschule',
		['size' => 26, 'bold' => false], ['align'=>'center', 'spaceBefore'=>10, 'spaceAfter'=>10]);
	$section->addText('Lernentwicklungsbericht',
		['size' => 26, 'bold' => true], ['align'=>'center', 'spaceBefore'=>10, 'spaceAfter'=>200]);
	$section->addText('Information über die Lernentwicklung im '.block_exastud_get_active_period()->description,
		['size' => 14], ['align'=>'center', 'lineHeight'=>1, 'spaceAfter'=>100]);
	$section->addText('für',
		['size' => 14], ['align'=>'center', 'lineHeight'=>1, 'spaceAfter'=>300]);
	
	$table = $section->addTable(array('borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80));
	$table->addRow();
	$table->addCell(2500)->addText('Vorname, Name');
	$table->addCell($pageWidthTwips-2500)->addText($student->firstname.', '.$student->lastname);
	$table->addRow();
	$table->addCell()->addText('Geburtsdatum');
	$table->addCell()->addText(!empty($birthday)?strftime('%d. %B %Y', $birthday):'');
	$table->addRow();
	$table->addCell()->addText('Lerngruppe');
	$table->addCell();
	
	$section->addPageBreak();

	$header_body_cell = function($header, $body=null) use (&$table, $pageWidthTwips) {
		$table->addRow(null, ['cantSplit'=>true]);
		$cell = $table->addCell($pageWidthTwips);
		$cell->addText($header, ['bold' => true], ['spaceAfter'=>200]);

		if ($body) {
			\PhpOffice\PhpWord\Shared\Html::addHtml($cell, $body);
		}

		return $cell;
	};

	$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);

	if ($textReviews && $textReviews[0]->subjectid == block_exastud::SUBJECT_ID_LERN_UND_SOZIALVERHALTEN) {
		$cell = $header_body_cell($textReviews[0]->title, $textReviews[0]->review);
		$cell->getStyle()->setGridSpan(2);
		unset ($textReviews[0]);
	}

	foreach($textReviews as $textReview) {
		$table->addRow(null, ['cantSplit'=>true]);
		$cell = $table->addCell($pageWidthTwips/4);
		$cell->addText($textReview->title, ['bold' => true]);

		\PhpOffice\PhpWord\Shared\Html::addHtml($table->addCell($pageWidthTwips/4*3), $textReview->review);
	}

	$section->addPageBreak();

	$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);

	$cell = $header_body_cell('Ateliers');
	$cell->addText('');

	$cell = $header_body_cell('Arbeitsgemeinschaften');
	$cell->addText('');

	$cell = $header_body_cell('Besondere Stärken');
	$cell->addText('');
	$cell->addText('');
	$cell->addText('');
	$cell->addText('');

	$cell = $header_body_cell('Anlagen');
	$cell->addText('Kompetenzprofile');
	$cell->addText('Zielvereinbarungen');

	$section->addText('');
	$section->addText('');
	$section->addText("Lernentwicklungsgespräch(-e) Datum: _________________");
	$section->addText('');
	$section->addText(get_config('exastud', 'school_location').", den ______________");
	$section->addText('');
	$section->addText('');
	$section->addText('');
	$section->addText("Unterschriften", ['bold' => true]);
	$section->addText('');

	$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
	$table->addRow();
	$cell = $table->addCell($pageWidthTwips/4);
	$cell->addText('');
	$cell->addText('');
	$cell->addText('');
	$cell = $table->addCell($pageWidthTwips/4);
	$cell->addText('');
	$cell->addText('');
	$cell->addText('');
	$cell = $table->addCell($pageWidthTwips/4);
	$cell->addText('');
	$cell->addText('');
	$cell->addText('');
	$cell = $table->addCell($pageWidthTwips/4);
	$cell->addText('');
	$cell->addText('');
	$cell->addText('');
	$table->addRow();
	$cell = $table->addCell($pageWidthTwips/4);
	$cell->addText('Schüler /', null, ['align'=>'center', 'spaceBefore'=>0, 'spaceAfter'=>0]);
	$cell->addText('Schülerin', null, ['align'=>'center', 'spaceBefore'=>0, 'spaceAfter'=>0]);
	$cell = $table->addCell($pageWidthTwips/4);
	$cell->addText('Erziehungsberechtiger /', null, ['align'=>'center', 'spaceBefore'=>0, 'spaceAfter'=>0]);
	$cell->addText('Erziehungsberechtige', null, ['align'=>'center', 'spaceBefore'=>0, 'spaceAfter'=>0]);
	$cell = $table->addCell($pageWidthTwips/4);
	$cell->addText('Lernbegleiter /', null, ['align'=>'center', 'spaceBefore'=>0, 'spaceAfter'=>0]);
	$cell->addText('Lernbegleiterin', null, ['align'=>'center', 'spaceBefore'=>0, 'spaceAfter'=>0]);
	$cell = $table->addCell($pageWidthTwips/4);
	$cell->addText('Schulleiter /', null, ['align'=>'center', 'spaceBefore'=>0, 'spaceAfter'=>0]);
	$cell->addText('Schulleiterin', null, ['align'=>'center', 'spaceBefore'=>0, 'spaceAfter'=>0]);

	if ($outputType == 'docx_test') {
		// testing:
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
	send_temp_file($temp_file, "Lernentwicklungsbericht ".fullname($student).".docx");
	unlink($temp_file);  // remove temp file
	
	exit;
}





$url = '/blocks/exastud/report_student.php';
$PAGE->set_url($url);

$strstudentreview = get_string('reviewstudent');
$strclassreview = get_string('reviewclass');
block_exastud_print_header(array('review',
	array('name' => $strclassreview, 'link' => $CFG->wwwroot . '/blocks/exastud/review_class.php?courseid=' . $courseid .
		'&classid=' . $classid),
	'=' . $strstudentreview
		), array('noheading'));


$studentdesc = $OUTPUT->user_picture($student, array("courseid" => $courseid)) . ' ' . fullname($student, $student->id);

echo $OUTPUT->heading($studentdesc);

echo get_renderer()->print_student_report($categories, $textReviews);

block_exastud_print_footer();
