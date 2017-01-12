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

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/../inc.php';
require_once __DIR__.'/../../exacomp/inc.php';

use block_exastud\globals as g;
use PhpOffice\PhpWord\Escaper\RegExp;
use PhpOffice\PhpWord\Escaper\Xml;

class printer {
	static function spacerIfEmpty($value) {
		$value = trim($value);

		if (!trim(strip_tags($value))) {
			return '---';
		} else {
			return $value;
		}
	}

	static function report_to_temp_file($class, $student, $template) {
		global $CFG;

		$certificate_issue_date = trim(get_config('exastud', 'certificate_issue_date'));
		$studentdata = get_class_student_data($class->id, $student->id);

		if ($template == 'leb_alter_bp_hj') {
			return static::leb($class, $student);
		}

		$templateFile = __DIR__.'/../template/'.$template.'.docx';

		if (!file_exists($templateFile)) {
			throw new \Exception("template $template not found");
		}

		$templateProcessor = new TemplateProcessor($templateFile);

		$data = [];
		$dataTextReplacer = [];
		$filters = [];
		if ($template == 'Deckblatt und 1. Innenseite LEB') {
			$data = [
				'schule' => get_config('exastud', 'school_name'),
				'ort' => get_config('exastud', 'school_location'),
				'name' => $student->firstname.' '.$student->lastname,
				'geburtsdatum' => block_exastud_get_date_of_birth($student->id),
			];
		}
		if ($template == 'Lernentwicklungsbericht neuer BP 1.HJ' || $template == 'Lernentwicklungsbericht alter BP 1.HJ') {
			$data = [
				'schule' => get_config('exastud', 'school_name'),
				'ort' => get_config('exastud', 'school_location'),
				'name' => $student->firstname.' '.$student->lastname,
				'klasse' => $class->title,
				// 'geburtsdatum' => get_custom_profile_field_value($student->id, 'dateofbirth'),
				'certdate' => $certificate_issue_date,
				'lern_und_sozialverhalten' => static::spacerIfEmpty(get_class_student_data($class->id, $student->id, \block_exastud\DATA_ID_LERN_UND_SOZIALVERHALTEN)),
				'bemerkungen' => static::spacerIfEmpty(@$studentdata->comments),
				'religion' => '---',
				'profilfach' => '---',
				'wahlpflichtfach' => '---',
			];

			if ($dateofbirth = block_exastud_get_date_of_birth_as_timestamp($student->id)) {
				$dataTextReplacer['dd'] = date('d', $dateofbirth);
				$dataTextReplacer['mm'] = date('m', $dateofbirth);
				$dataTextReplacer['yyyy'] = date('Y', $dateofbirth);
			}

			$availablesubjects = block_exastud_get_bildungsplan_subjects($class->bpid);

			// zuerst standardwerte
			foreach ($availablesubjects as $subject) {
				$data[strtolower($subject->title)] = '---';
				$data[$subject->shorttitle] = '---';
			}

			$wahlpflichtfach = '---';
			$profilfach = '---';

			// danach mit richtigen werten überschreiben
			foreach ($availablesubjects as $subject) {
				$subjectData = \block_exastud\get_subject_student_data($class->id, $subject->id, $student->id);

				if (!@$subjectData->review) {
					continue;
				}

				$subject->title = preg_replace('!\s*\(.*$!', '', $subject->title);

				if (in_array($subject->shorttitle, ['RALE', 'RAK', 'ETH', 'REV', 'RISL', 'RJUED', 'RRK', 'ROR', 'RSYR'])) {
					$dataTextReplacer['Ethik (ETH)'] = $subject->title.' ('.$subject->shorttitle.')';
					$contentId = 'religion';
				} elseif (strpos($subject->title, 'Wahlpflichtfach') === 0) {
					$wahlpflichtfach = preg_replace('!^[^\s]+!', '', $subject->title);
					$contentId = 'wahlpflichtfach';
				} elseif (strpos($subject->title, 'Profilfach') === 0) {
					$profilfach = preg_replace('!^[^\s]+!', '', $subject->title);
					$contentId = 'profilfach';
				} else {
					$contentId = strtolower($subject->title);
				}

				$data[$contentId] = static::spacerIfEmpty($subjectData->review);

				$niveau = !empty($subjectData->niveau) ? 'Niveau '.$subjectData->niveau : '';
				$filters[] = function($content) use ($contentId, $niveau) {
					return preg_replace('!({'.$contentId.'}.*>)Bitte die Niveaustufe auswählen(<)!U', '$1'.$niveau.'$2', $content);
				};

				$grade = (@$studentdata->print_grades ? 'Note '.static::spacerIfEmpty(@$subjectData->grade) : '');

				$filters[] = function($content) use ($contentId, $grade) {
					return preg_replace('!({'.$contentId.'}.*>)ggf. Note(<)!U', '$1'.$grade.'$2', $content);
				};
			}

			// wahlpflichtfach + profilfach dropdowns
			$filters[] = function($content) use ($wahlpflichtfach) {
				return preg_replace('!(>)Technik(<.*{'.'wahlpflichtfach'.'})!U', '$1'.$wahlpflichtfach.'$2', $content);
			};
			$filters[] = function($content) use ($profilfach) {
				return preg_replace('!(>)Spanisch(<.*{'.'profilfach'.'})!U', '$1'.$profilfach.'$2', $content);
			};

			// nicht befüllte niveaus und noten befüllen
			$dataTextReplacer['Bitte die Niveaustufe auswählen'] = 'Niveau: ---';
			$dataTextReplacer['ggf. Note'] = @$studentdata->print_grades ? 'Note: ---' : '';
		}
		if ($template == 'Anlage zum Lernentwicklungsbericht') {
			$evalopts = g::$DB->get_records('block_exastudevalopt', null, 'sorting', 'id, title, sourceinfo');
			$categories = get_class_categories_for_report($student->id, $class->id);
			$subjects = static::get_exacomp_subjects($student->id);

			$data = [
				'schule' => get_config('exastud', 'school_name'),
				'ort' => get_config('exastud', 'school_location'),
				'name' => $student->firstname.' '.$student->lastname,
				'klasse' => $class->title,
				'geburtsdatum' => block_exastud_get_date_of_birth($student->id),
			];

			$templateProcessor->duplicateCol('kheader', count($evalopts));
			foreach ($evalopts as $evalopt) {
				$templateProcessor->setValue('kheader', $evalopt->title, 1);
			}

			foreach ($categories as $category) {
				$templateProcessor->cloneRowToEnd('kriterium');
				$templateProcessor->setValue('kriterium', $category->title, 1);

				for ($i = 0; $i < count($evalopts); $i++) {
					$templateProcessor->setValue('kvalue',
						$category->average !== null && round($category->average) == $i ? 'X' : '', 1);
				}
			}
			$templateProcessor->deleteRow('kriterium');


			// subjects

			$templateProcessor->cloneBlock('subjectif', count($subjects), true);

			$test = 0;

			foreach ($subjects as $subject) {
				$templateProcessor->setValue("subject", $subject->title, 1);

				foreach ($subject->topics as $topic) {
					$templateProcessor->cloneRowToEnd("topic");
					$templateProcessor->cloneRowToEnd("descriptor");

					$templateProcessor->setValue("topic", $topic->title, 1);

					$templateProcessor->setValue("n", $topic->teacher_eval_niveau_text, 1);
					$templateProcessor->setValue("ne", $topic->teacher_eval_additional_grading == 0 ? 'X' : '', 1);
					$templateProcessor->setValue("tw", $topic->teacher_eval_additional_grading == 1 ? 'X' : '', 1);
					$templateProcessor->setValue("ue", $topic->teacher_eval_additional_grading == 2 ? 'X' : '', 1);
					$templateProcessor->setValue("ve", $topic->teacher_eval_additional_grading == 3 ? 'X' : '', 1);

					/*
					$gme = ['G', 'M', 'E'][$test % 3];
					$x = $test % 4;
					$test++;
					$templateProcessor->setValue("n", $gme.$test, 1);
					$templateProcessor->setValue("ne", $x == 0 ? 'X' : '', 1);
					$templateProcessor->setValue("tw", $x == 1 ? 'X' : '', 1);
					$templateProcessor->setValue("ue", $x == 2 ? 'X' : '', 1);
					$templateProcessor->setValue("ve", $x == 3 ? 'X' : '', 1);
					*/

					foreach ($topic->descriptors as $descriptor) {
						$templateProcessor->duplicateRow("descriptor");
						$templateProcessor->setValue("descriptor", ($descriptor->niveau_title ? $descriptor->niveau_title.': ' : '').$descriptor->title, 1);

						$templateProcessor->setValue("n", $descriptor->teacher_eval_niveau_text, 1);
						$templateProcessor->setValue("ne", $descriptor->teacher_eval_additional_grading == 0 ? 'X' : '', 1);
						$templateProcessor->setValue("tw", $descriptor->teacher_eval_additional_grading == 1 ? 'X' : '', 1);
						$templateProcessor->setValue("ue", $descriptor->teacher_eval_additional_grading == 2 ? 'X' : '', 1);
						$templateProcessor->setValue("ve", $descriptor->teacher_eval_additional_grading == 3 ? 'X' : '', 1);

						/*
						$gme = ['G', 'M', 'E'][$test % 3];
						$x = $test % 4;
						$test++;
						$templateProcessor->setValue("n", $gme.$test, 1);
						$templateProcessor->setValue("ne", $x == 0 ? 'X' : '', 1);
						$templateProcessor->setValue("tw", $x == 1 ? 'X' : '', 1);
						$templateProcessor->setValue("ue", $x == 2 ? 'X' : '', 1);
						$templateProcessor->setValue("ve", $x == 3 ? 'X' : '', 1);
						*/
					}

					$templateProcessor->deleteRow("descriptor");
				}

				$templateProcessor->deleteRow("topic");
				$templateProcessor->deleteRow("descriptor");
			}
		}

		// zuerst filters
		$templateProcessor->applyFilters($filters);
		$templateProcessor->setValues($data);
		$templateProcessor->replaceWords($dataTextReplacer);

		// $templateProcessor->check();

		if (optional_param('test', null, PARAM_INT)) {
			echo $templateProcessor->getDocumentMainPart();
			exit;
		}

		// save as a random file in temp file
		$temp_file = tempnam($CFG->tempdir, 'exastud');
		$templateProcessor->saveAs($temp_file);

		$filename = ($certificate_issue_date ?: date('Y-m-d'))."-".ucfirst($template)."-{$class->title}-{$student->lastname}-{$student->firstname}.docx";

		return (object)[
			'temp_file' => $temp_file,
			'filename' => $filename,
		];
	}

	static function leb_standard_header($section) {
		global $student, $class;

		$header = $section->addHeader();
		$header->addTExt($student->lastname.', '.$student->firstname.', '.$class->title.', '.block_exastud_get_active_or_last_period()->description);

		return $header;
	}

	static function leb_standard_footer($section) {
		$footer = $section->addFooter();
		$footer->addPreserveText('Seite {PAGE} von {NUMPAGES}', null, ['align' => 'center']);

		return $footer;
	}

	static function leb_wrapper_table($section) {
		global $pageWidthTwips;

		// äußere tabelle, um cantSplit zu setzen (dadurch wird innere tabelle auf einer seite gehalten)
		$table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0]);
		$table->addRow(null, ['cantSplit' => true]);
		$cell = $table->addCell($pageWidthTwips + 100); // add some extra spacing, else borders don't work
		// $cell->getStyle()->setBgColor('99999');

		return $cell;
	}

	static function leb_add_html($element, $html) {

		// delete span
		$html = preg_replace('!<span(\s[^>]+)?>!i', '', $html);
		$html = preg_replace('!</span>!i', '', $html);

		// delete styles
		$html = preg_replace('!\sstyle\s*=\s*"[^"]*"!i', '', $html);

		// delete empty paragraphs (moodle bug)
		$html = preg_replace('!<p>\s*</p>!i', '', $html);

		// delete double paraggraphs (moodle bug)
		$html = preg_replace('!<p>\s*<p>!i', '<p>', $html);
		$html = preg_replace('!</p>\s*</p>!i', '</p>', $html);

		$html = preg_replace('!&nbsp;!i', ' ', $html);

		// delete special ms office tags
		$html = preg_replace('!</?o:[^>]*>!i', '', $html);

		// phpoffice doesn't know <i> and <b>
		// it expects <strong> and <em>
		$html = preg_replace('!(</?)b(>)!i', '$1strong$2', $html);
		$html = preg_replace('!(</?)i(>)!i', '$1em$2', $html);

		\PhpOffice\PhpWord\Shared\Html::addHtml($element, $html);
	}

	static function leb_header_body_table($section, $header, $body = null) {
		global $tableWidthTwips;

		$cell = static::leb_wrapper_table($section);

		// innere tabelle
		$table = $cell->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
		$table->addRow();
		$cell = $table->addCell($tableWidthTwips);
		$cell->getStyle()->setBgColor('D9D9D9');
		$cell->addText($header, ['bold' => true]);

		if ($body !== null) {
			$table->addRow();
			static::leb_add_html($table->addCell($tableWidthTwips), $body);
		}

		return $table;
	}

	static function leb_subject_table($section, $header, $body, $right) {
		global $tableWidthTwips;

		$cell = static::leb_wrapper_table($section);

		// innere tabelle
		$table = $cell->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
		$table->addRow();
		$cell = $table->addCell($tableWidthTwips / 6 * 5);
		$cell->getStyle()->setBgColor('D9D9D9');
		// $cell->getStyle()->setGridSpan(2);
		$cell->addText($header, ['bold' => true]);

		$cell = $table->addCell($tableWidthTwips / 6);
		$cell->getStyle()->setBgColor('D9D9D9');
		$cell->addText('Niveaustufe', ['bold' => true]);

		$table->addRow();
		$cell = $table->addCell($tableWidthTwips / 6 * 5);
		static::leb_add_html($cell, $body);

		$cell = $table->addCell($tableWidthTwips / 6);
		static::leb_add_html($cell, $right);

		return $table;
	}

	static function leb($class, $student, $outputType = 'docx') {
		global $CFG;

		$dateofbirth = block_exastud_get_date_of_birth($student->id);


		\PhpOffice\PhpWord\Settings::setTempDir($CFG->tempdir);

		$phpWord = new \PhpOffice\PhpWord\PhpWord();
		$phpWord->setDefaultFontSize(10);
		$phpWord->setDefaultParagraphStyle(['spaceBefore' => 0, 'spaceAfter' => 0]);

		global $pageWidthTwips;
		$pageWidthTwips = 9200;
		global $tableWidthTwips;
		$tableWidthTwips = 9200 - 200;
		$tmpLogoFile = null;

		$section = $phpWord->addSection();

		// empty header on first page
		$header = $section->addHeader();
		$header->firstPage();
		// $footer = $section->addFooter();
		$footer = static::leb_standard_footer($section);
		$footer->firstPage();

		static::leb_standard_footer($section);
		static::leb_standard_header($section);

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

		if (get_config('exastud', 'school_name')) {
			$section->addText(get_config('exastud', 'school_name'),
				['size' => 16, 'bold' => true], ['align' => 'center', 'spaceBefore' => 350]);
		}
		$section->addText('Lernentwicklungsbericht',
			['size' => 16, 'bold' => true], ['align' => 'center', 'spaceBefore' => 350]);
		$section->addText(block_exastud_get_active_or_last_period()->description,
			['size' => 12], ['align' => 'center', 'lineHeight' => 1, 'spaceBefore' => 350, 'spaceAfter' => 350]);

		$table = static::leb_wrapper_table($section)->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 80));
		$table->addRow();
		$table->addCell($tableWidthTwips / 6);
		$table->addCell($tableWidthTwips / 6 * 2)->addText(trans('de:Vor- und Zuname').':', ['bold' => true]);
		$table->addCell($tableWidthTwips / 6 * 3)->addText($student->firstname.' '.$student->lastname);
		$table->addRow();
		$table->addCell();
		$table->addCell()->addText(trans('de:Geburtsdatum').':', ['bold' => true]);
		$table->addCell()->addText($dateofbirth);
		$table->addRow();
		$table->addCell();
		$table->addCell()->addText(trans('de:Lerngruppe').':', ['bold' => true]);
		$table->addCell()->addText($class->title);
		$table->addRow();
		$table->addCell();

		$studentdata = get_class_student_data($class->id, $student->id);

		$availablesubjects = block_exastud_get_bildungsplan_subjects($class->bpid);

		$textReviews = g::$DB->get_records_sql("
		SELECT DISTINCT s.title AS id, r.review, s.title AS title, r.subjectid AS subjectid
		FROM {block_exastudreview} r
		JOIN {block_exastudsubjects} s ON r.subjectid = s.id
		JOIN {block_exastudclass} c ON c.periodid = r.periodid
		JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid = r.teacherid AND ct.subjectid=r.subjectid
		WHERE r.studentid = ? AND r.periodid = ? AND TRIM(r.review) !=  ''
	", [$student->id, $class->periodid]);

		$subjects = [];
		foreach ($availablesubjects as $availablesubject) {
			if (isset($textReviews[$availablesubject->title])) {
				$textReview = $textReviews[$availablesubject->title];
				$subject = (object)array_merge((array)$textReview, (array)\block_exastud\get_subject_student_data($class->id, $textReview->subjectid, $student->id));
			} elseif ($availablesubject->always_print) {
				$subject = (object)[
					'title' => $availablesubject->title,
					'review' => '---',
				];
			} else {
				continue;
			}

			$subject->title = preg_replace('!\s*\(.*$!', '', $subject->title);

			$subjects[] = $subject;
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

		/*
		$section = $phpWord->addSection();
		static::leb_standard_footer($section);
		static::leb_standard_header($section);
		*/

		/*
		$footer = $section->addFooter();
		$footer->firstPage();
		*/

		// $section->addPageBreak();
		// phpword bug: pagebreak needs some text
		// $section->addText('.', ['size' => 1, 'color'=>'ffffff']);

		$lern_und_sozialverhalten = get_class_student_data($class->id, $student->id, \block_exastud\DATA_ID_LERN_UND_SOZIALVERHALTEN);
		$table = static::leb_header_body_table($section, trans('de:Lern- und Sozialverhalten'), block_exastud_text_to_html($lern_und_sozialverhalten) ?: '---');
		/*
		if (empty($lern_und_sozialverhalten)) {
			$cell = $table->getRows()[1]->getCells()[0];
			$cell->addText('');
			$cell->addText('');
			$cell->addText('');
			$cell->addText('');
		}
		*/

		$table = static::leb_header_body_table($section, trans('de:Leistung in den einzelnen Fächern'), null);
		$cell = $table->getRows()[0]->getCells()[0];
		//$cell->addText('mit Angabe der Niveaustufe *, auf der die Leistungen überwiegend erbracht wurden. Auf Elternwunsch zusätzlich Note.',
		//	['size' => 9, 'bold' => true]);

		foreach ($subjects as $textReview) {
			static::leb_subject_table(
				$section,
				$textReview->title,
				block_exastud_text_to_html($textReview->review),
				'Niveau: '.(@$textReview->niveau ?: '---').'<br />'.
				(@$studentdata->print_grades ? 'Note: '.(trim(@$textReview->grade) ?: '---').'<br />' : '')
			);
		}

		/*
		$cell = $header_body_cell('Ateliers');
		if (empty($studentdata['ateliers'])) {
			$cell->addText('');
		} else {
			static::leb_add_html($cell, $studentdata['ateliers']);
		}

		$cell = $header_body_cell('Arbeitsgemeinschaften');
		if (empty($studentdata['arbeitsgemeinschaften'])) {
			$cell->addText('');
		} else {
			static::leb_add_html($cell, $studentdata['arbeitsgemeinschaften']);
		}

		$cell = $header_body_cell('Besondere Stärken');
		if (empty($studentdata['besondere_staerken'])) {
			$cell->addText('');
			$cell->addText('');
			$cell->addText('');
			$cell->addText('');
		} else {
			static::leb_add_html($cell, $studentdata['besondere_staerken']);
		}
		*/

		/*
		$section = $phpWord->addSection();
		static::leb_standard_footer($section);
		static::leb_standard_header($section);
		*/

		$section->addText('');

		$table = static::leb_header_body_table($section, 'Bemerkungen', block_exastud_text_to_html(@$studentdata->comments));
		if (empty($studentdata->comments)) {
			$cell = $table->getRows()[1]->getCells()[0];
			$cell->addText('---');
		}

		$section->addText('');
		$section->addText('');
		$table = static::leb_wrapper_table($section)->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0));
		$table->addRow();
		$table->addCell(500)->addText('G =');
		$table->addCell($tableWidthTwips - 500)->addText('Grundlegendes Niveau, entspricht den Bildungsstandards der Hauptschule');
		$table->addRow();
		$table->addCell(500)->addText('M =');
		$table->addCell($tableWidthTwips - 500)->addText('Mittleres Niveau, entspricht den Bildungsstandards der Realschule');
		$table->addRow();
		$table->addCell(500)->addText('E =');
		$table->addCell($tableWidthTwips - 500)->addText('Erweitertes Niveau, entspricht den Bildungsstandards des Gymnasiums');

		$wrapper = static::leb_wrapper_table($section);

		$location = get_config('exastud', 'school_location');
		$certificate_issue_date = trim(get_config('exastud', 'certificate_issue_date'));
		$ort_datum = ($location ? $location.", " : '').$certificate_issue_date;

		$wrapper->addText('');
		$wrapper->addText('');
		$wrapper->addText('');
		$wrapper->addText('');

		$table = $wrapper->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40));
		$table->addRow();
		$table->addCell($tableWidthTwips / 7 * 3)->addText('', null, ['align' => 'center']);
		$table->addCell($tableWidthTwips / 7 * 1);
		$table->addCell($tableWidthTwips / 7 * 3, ['borderBottomSize' => 6, 'borderBottomColor' => 'black'])->addText($ort_datum, null, ['align' => 'center']);
		$table->addRow();
		$table->addCell()->addText('', ['size' => 8], ['align' => 'center']);
		$table->addCell();
		$table->addCell()->addText('Ort, Datum', ['size' => 8], ['align' => 'center']);

		$wrapper->addText('');
		$wrapper->addText('');
		$table = $wrapper->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0));
		$table->addRow();
		$table->addCell($tableWidthTwips)->addText('Dienstsiegel', null, ['align' => 'center']);
		$wrapper->addText('');

		$table = $wrapper->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40));
		$table->addRow();
		$table->addCell($tableWidthTwips / 7 * 3, ['borderBottomSize' => 6, 'borderBottomColor' => 'black'])->addText('', null, ['align' => 'center']);
		$table->addCell($tableWidthTwips / 7 * 1);
		$table->addCell($tableWidthTwips / 7 * 3, ['borderBottomSize' => 6, 'borderBottomColor' => 'black'])->addText('', null, ['align' => 'center']);
		$table->addRow();
		$table->addCell()->addText('Lerngruppenbegleiterin/Lerngruppenbegleiter', ['size' => 8], ['align' => 'center']);
		$table->addCell();
		$table->addCell()->addText('Schulleiterin/Schulleiter', ['size' => 8], ['align' => 'center']);

		$wrapper->addText('');
		$wrapper->addText('');
		$wrapper->addText('');
		$wrapper->addText('');

		$table = $wrapper->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40));
		$table->addRow();
		$table->addCell($tableWidthTwips / 7 * 3, ['borderBottomSize' => 6, 'borderBottomColor' => 'black'])->addText('', null, ['align' => 'center']);
		$table->addCell($tableWidthTwips / 7 * 1);
		$table->addCell($tableWidthTwips / 7 * 3, ['borderBottomSize' => 6, 'borderBottomColor' => 'black'])->addText('', null, ['align' => 'center']);
		$table->addRow();
		$table->addCell()->addText('Schülerin/Schüler', ['size' => 8], ['align' => 'center']);
		$table->addCell();
		$table->addCell()->addText('Erziehungsberechtigte/Erziehungsberechtigter', ['size' => 8], ['align' => 'center']);

		/*
		static::leb_header_body_table($section, 'Anlagen', 'Kompetenzprofile<br />Zielvereinbarungen');

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

		$table = static::leb_wrapper_table($section)->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
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
		$cell->addText('Erziehungsberechtigter /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
		$cell->addText('Erziehungsberechtigte', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
		$cell = $table->addCell($tableWidthTwips / 4);
		$cell->addText('Lernbegleiter /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
		$cell->addText('Lernbegleiterin', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
		$cell = $table->addCell($tableWidthTwips / 4);
		$cell->addText('Schulleiter /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
		$cell->addText('Schulleiterin', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
		*/

		$certificate_issue_date = trim(get_config('exastud', 'certificate_issue_date'));
		$filename = ($certificate_issue_date ?: date('Y-m-d'))."-Lernentwicklungsbericht-{$class->title}-{$student->lastname}-{$student->firstname}.docx";

		if ($outputType == 'docx_test' || optional_param('test', '', PARAM_TEXT)) {
			// testing:
			echo "<h1>testing, filename: $filename</h1>";
			echo \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML')->getContent();
			exit;
		}

		$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

		// // save as a random file in temp file
		$temp_file = tempnam($CFG->tempdir, 'PHPWord');
		$objWriter->save($temp_file);

		if ($tmpLogoFile) {
			unlink($tmpLogoFile);
		}

		return (object)[
			'temp_file' => $temp_file,
			'filename' => $filename,
		];
	}

	static function get_exacomp_subjects($studentid) {
		$subjects = \block_exacomp\db_layer_all_user_courses::create($studentid)->get_subjects();

		$niveau_titles = g::$DB->get_records_menu(\block_exacomp\DB_EVALUATION_NIVEAU, [], '', 'id,title');

		// todo check timestamp for current semester
		$niveaus_topics = g::$DB->get_records_menu(\block_exacomp\DB_COMPETENCES, array("userid" => $studentid, "role" => \block_exacomp\ROLE_TEACHER, "comptype" => TYPE_TOPIC), '', 'compid as id, evalniveauid');
		$niveaus_competencies = g::$DB->get_records_menu(\block_exacomp\DB_COMPETENCES, array("userid" => $studentid, "role" => \block_exacomp\ROLE_TEACHER, "comptype" => TYPE_DESCRIPTOR), '', 'compid as id, evalniveauid');

		$teacher_additional_grading_topics = g::$DB->get_records_menu(\block_exacomp\DB_COMPETENCES,array("userid" => $studentid, "role" => \block_exacomp\ROLE_TEACHER, "comptype" => TYPE_TOPIC),'','compid as id, additionalinfo');
		$teacher_additional_grading_competencies = g::$DB->get_records_menu(\block_exacomp\DB_COMPETENCES,array("userid" => $studentid, "role" => \block_exacomp\ROLE_TEACHER, "comptype" => TYPE_DESCRIPTOR),'','compid as id, additionalinfo');

		foreach ($subjects as $subject) {
			// echo $subject->title."<br/>\n";
			foreach ($subject->topics as $topic) {
				// echo 'x '.$topic->title.' '.(@$niveaus_topics[$topic->id])."<br/>\n";
				foreach ($topic->descriptors as $descriptor) {
					// echo 'x x '.$descriptor->title.' '.(@$niveaus_competencies[$descriptor->id])."<br/>\n";
					$descriptor->teacher_eval_niveau_text = @$niveau_titles[$niveaus_competencies[$descriptor->id]];
					if (isset($teacher_additional_grading_competencies[$descriptor->id])) {
						// \block_exacomp\global_config::get_value_title_by_id
						$descriptor->teacher_eval_additional_grading = \block_exacomp\global_config::get_additionalinfo_value_mapping($teacher_additional_grading_competencies[$descriptor->id]);
					} else {
						$descriptor->teacher_eval_additional_grading = null;
					}

					if (!$descriptor->teacher_eval_niveau_text && !$descriptor->teacher_eval_additional_grading) {
						unset($topic->descriptors[$descriptor->id]);
					}
				}

				$topic->teacher_eval_niveau_text = @$niveau_titles[$niveaus_topics[$topic->id]];
				if (isset($teacher_additional_grading_topics[$topic->id])) {
					// \block_exacomp\global_config::get_value_title_by_id
					$topic->teacher_eval_additional_grading = \block_exacomp\global_config::get_additionalinfo_value_mapping($teacher_additional_grading_topics[$topic->id]);
				} else {
					$topic->teacher_eval_additional_grading = null;
				}

				if (!$topic->descriptors && !$topic->teacher_eval_niveau_text && ! $topic->teacher_eval_additional_grading) {
					unset($subject->topics[$topic->id]);
				}
			}

			if (!$subject->topics) {
				unset($subjects[$subject->id]);
			}
		}

		return $subjects;
	}
}

class Slice {
	function __construct($string, $start, $end) {
		$this->before = substr($string, 0, $start);
		$this->slice = substr($string, $start, $end - $start);
		$this->after = substr($string, $end);
	}

	function get() {
		return $this->slice;
	}

	function set($value) {
		$this->slice = $value;

		return $this;
	}

	function join() {
		return $this->before.$this->slice.$this->after;
	}
}

class TemplateProcessor extends \PhpOffice\PhpWord\TemplateProcessor {
	function getDocumentMainPart() {
		return $this->tempDocumentMainPart;
	}

	function setDocumentMainPart($part) {
		$this->tempDocumentMainPart = $part;
	}

	function setValues($data) {
		$xmlEscaper = new Xml();

		foreach ($data as $key => $value) {
			$value = $xmlEscaper->escape($value);
			$this->setValue($key, str_replace(["\r", "\n"], ['', '</w:t><w:br/><w:t>'], $value));
			/*
			$value = ;
			$content = str_replace('{'.$key.'}', $value, $content);
			$content = str_replace('>'.$key.'<', '>'.$value.'<', $content);
			*/
		}
	}

	function applyFilters($filters) {
		foreach ($filters as $filter) {
			$this->tempDocumentMainPart = $filter($this->tempDocumentMainPart);
		}
	}

	function replaceWords($data) {
		foreach ($data as $key => $value) {
			$this->tempDocumentMainPart = str_replace('>'.$key.'<', '>'.$value.'<', $this->tempDocumentMainPart);
		}
	}

	function check() {
		if (preg_match('!\\$(.*(>|{)(?<name>[a-z{}].*)<)!iU', $this->tempDocumentMainPart, $matches)) {
			throw new \Exception("fehler in variable ${matches['name']}");
		}
	}

	function tagPos($search) {
		if ('${' !== substr($search, 0, 2) && '}' !== substr($search, -1)) {
			$search = '${'.$search.'}';
		}

		$tagPos = strpos($this->tempDocumentMainPart, $search);
		if (!$tagPos) {
			throw new \Exception("Can't find '$search'");
		}

		return $tagPos;
	}

	public function cloneBlockAndSetNewVarNames($blockname, $clones, $replace, $varname) {
		$clone = $this->cloneBlock($blockname, $clones, $replace);

		for ($i = 0; $i < $clones; $i++) {
			$regExpEscaper = new RegExp();
			$this->tempDocumentMainPart = preg_replace($regExpEscaper->escape($clone), str_replace('${', '${'.$varname.$i.'-', $clone), $this->tempDocumentMainPart, 1);
		}
	}

	function cloneRowToEnd($search) {
		$tagPos = $this->tagPos($search);

		$rowStart = $this->findRowStart($tagPos);
		$rowEnd = $this->findRowEnd($tagPos);
		$xmlRow = $this->getSlice($rowStart, $rowEnd);

		$lastRowEnd = strpos($this->tempDocumentMainPart, '</w:tbl>', $tagPos);

		$result = $this->getSlice(0, $lastRowEnd);
		$result .= $xmlRow;
		$result .= $this->getSlice($lastRowEnd);

		$this->tempDocumentMainPart = $result;
	}

	function duplicateRow($search) {
		$tagPos = $this->tagPos($search);

		$rowStart = $this->findRowStart($tagPos);
		$rowEnd = $this->findRowEnd($tagPos);
		$xmlRow = $this->getSlice($rowStart, $rowEnd);

		$result = $this->getSlice(0, $rowEnd);
		$result .= $xmlRow;
		$result .= $this->getSlice($rowEnd);

		$this->tempDocumentMainPart = $result;
	}

	function deleteRow($search) {
		$this->cloneRow($search, 0);
	}

	/*
	function strTagPos($string, $tag, $offset) {
		$tagStart = strpos($string, '<'.$tag.' ', $offset);

		if (!$tagStart) {
			$tagStart = strpos($string, '<'.$tag.'>', $string);
		}
		if (!$tagStart) {
			throw new Exception('Can not find the start position of tag '.$tag.'.');
		}

		return $tagStart;
	}

	function strrTagPos($string, $tag, $offset) {
		$tagStart = strrpos($this->tempDocumentMainPart, '<w:'.$tag.' ', ((strlen($this->tempDocumentMainPart) - $offset) * -1));

		if (!$tagStart) {
			$tagStart = strrpos($this->tempDocumentMainPart, '<w:'.$tag.'>', ((strlen($this->tempDocumentMainPart) - $offset) * -1));
		}
		if (!$tagStart) {
			throw new Exception('Can not find the start position of tag '.$tag.'.');
		}

		return $tagStart;
	}

	function findTagEnd($tag, $offset) {
		$search = '</w:'.$tag.'>';

		return strpos($this->tempDocumentMainPart, $search, $offset) + strlen($search);
	}
	*/

	function splitByTag($string, $tag) {
		$rest = $string;
		$parts = [];

		while ($rest) {
			if (!preg_match('!^(?<before>.*)(?<tag><w:'.$tag.'[\s>].*</w:'.$tag.'>|<w:'.$tag.'(\s[^>]+)?/>)!Uis', $rest, $matches)) {
				$parts[] = $rest;
				break;
			}

			if ($matches['before']) {
				$parts[] = $matches['before'];
			}
			$parts[] = $matches['tag'];

			$rest = substr($rest, strlen($matches[0]));
		}

		return $parts;
	}

	function rfindTagStart($tag, $offset) {
		/*
		if (!preg_match('!<w:'.$tag.'[\s>].*$!Uis', substr($this->tempDocumentMainPart, 0, $offset), $matches)) {
			throw new \Exception('tagStart $tag not found');
		}

		echo $offset - strlen($matches[0]);
		*/

		$tagStart = strrpos($this->tempDocumentMainPart, '<w:'.$tag.' ', ((strlen($this->tempDocumentMainPart) - $offset) * -1));

		if (!$tagStart) {
			$tagStart = strrpos($this->tempDocumentMainPart, '<w:'.$tag.'>', ((strlen($this->tempDocumentMainPart) - $offset) * -1));
		}
		if (!$tagStart) {
			throw new Exception('Can not find the start position of tag '.$tag.'.');
		}

		return $tagStart;
	}

	function findTagEnd($tag, $offset) {
		$search = '</w:'.$tag.'>';

		return strpos($this->tempDocumentMainPart, $search, $offset) + strlen($search);
	}

	function slice($string, $start, $end) {
		return new Slice($string, $start, $end);
	}

	function duplicateCol($search, $numberOfCols = 1) {
		$tagPos = $this->tagPos($search);

		$table = $this->slice($this->tempDocumentMainPart,
			$this->rfindTagStart('tbl', $tagPos),
			$this->findTagEnd('tbl', $tagPos));

		$splits = static::splitByTag($table->get(), 'gridCol');

		preg_match('!(^.*w:w=")([0-9]+)(".*)$!', $splits[1], $firstCol);
		preg_match('!(^.*w:w=")([0-9]+)(".*)$!', $splits[2], $newCol);
		array_shift($firstCol);
		array_shift($newCol);

		$newWidth = $firstCol[1] - $newCol[1] * ($numberOfCols - 1);
		$firstCol[1] = $newWidth;

		$splits[1] = join('', $firstCol);
		$splits[2] = str_repeat($splits[2], $numberOfCols);

		$splits = static::splitByTag(join('', $splits), 'tc');

		$splits[1] = preg_replace('!(w:w=")[0-9]+!', '${1}'.$newWidth, $splits[1]);
		$splits[4] = preg_replace('!(w:w=")[0-9]+!', '${1}'.$newWidth, $splits[4]);

		$splits[2] = str_repeat($splits[2], $numberOfCols);
		$splits[5] = str_repeat($splits[5], $numberOfCols);

		$table->set(join('', $splits));

		$this->tempDocumentMainPart = $table->join();
	}

}
