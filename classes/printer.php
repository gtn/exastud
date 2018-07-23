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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
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

	static function toTemplateVarId($name) {
		return preg_replace('![^a-z]+!', '_', strtolower(trim($name)));
	}

    static function report_to_temp_file($class, $student, $templateid, $courseid)
    {
		global $CFG;

		$certificate_issue_date_text = block_exastud_get_certificate_issue_date_text($class);
		$certificate_issue_date_timestamp = block_exastud_get_certificate_issue_date_timestamp($class);
		$studentdata = block_exastud_get_class_student_data($class->id, $student->id);
		$period = block_exastud_get_period($class->periodid);

		if ($templateid == BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE) {
			$template = block_exastud_get_student_print_template($class, $student->id);
			$templateid = $template->get_template_id();
		} else {
			$template = \block_exastud\print_template::create($templateid);
		}

		$forminputs = $template->get_inputs();

		/*
		 * if ($templateid == 'leb_alter_bp_hj') {
		 * return static::leb($class, $student);
		 * }
		 */

		$templateFile = $template->get_file($templateid);

		// check if file does exist
		if (!file_exists($templateFile)) {
			throw new \Exception("template $templateid not found");
		}

		\PhpOffice\PhpWord\Settings::setTempDir($CFG->tempdir);
		$templateProcessor = new TemplateProcessor($templateFile);

		$data = [];
		$dataTextReplacer = [];
		$filters = [];

		$add_filter = function($id, $filter = null) use (&$filters) {
			if (is_callable($id)) {
				$filters[] = $id;
			} else {
				if (!isset($filters[join(',', $id)])) {
					$filters[join(',', $id)] = $filter;
				}
			}
		};

		$prepend_filter = function($filter = null) use (&$filters) {
			$filters = array_merge([
				$filter,
			], $filters);
		};

		// for all templates: filter schuljahr
		$add_filter(function($content) use ($period, $certificate_issue_date_timestamp) {
			$certificate_issue_date_timestamp = $certificate_issue_date_timestamp ?: null;

			// use current year or last year
			if (date('m', $certificate_issue_date_timestamp) >= 9) {
				$year1 = date('y', $certificate_issue_date_timestamp);
			} else {
				$year1 = date('y', $certificate_issue_date_timestamp) - 1;
			}
			$year2 = $year1 + 1;
			$year1 = str_pad($year1, 2, '0', STR_PAD_LEFT);
			$year2 = str_pad($year2, 2, '0', STR_PAD_LEFT);

			return preg_replace('!([^0-9])99([^0-9].{0,3000}[^0-9])99([^0-9])!U', '${1}'.$year1.'${2}'.$year2.'${3}', $content, 1, $count);
		});

		if ($templateid == 'Deckblatt und 1. Innenseite LEB') {
			$data = [
				'schule' => get_config('exastud', 'school_name'),
				'ort' => get_config('exastud', 'school_location'),
				'name' => $student->firstname.' '.$student->lastname,
				'geburtsdatum' => block_exastud_get_date_of_birth($student->id),
			];
		} elseif ($templateid == 'default_report') {
			$class_subjects = block_exastud_get_class_subjects($class);
			$lern_soz = block_exastud_get_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN);

			$data = [
				'periode' => $period->description,
				'lern_und_sozialverhalten' => static::spacerIfEmpty($lern_soz),
				'certda' => $certificate_issue_date_text,
				'schule' => get_config('exastud', 'school_name'),
				'ort' => get_config('exastud', 'school_location'),
				'name' => $student->firstname.' '.$student->lastname,
				'geburtsdatum' => block_exastud_get_date_of_birth($student->id),
				'klasse' => $class->title,
				'comments' => static::spacerIfEmpty(@$studentdata->comments),
			];

			$cloneValues = [];
			foreach ($class_subjects as $subject) {
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);

				if (!$subjectData || (!$subjectData->review && !$subjectData->grade && !$subjectData->niveau)) {
					continue;
				}

				$niveau = \block_exastud\global_config::get_niveau_option_title(@$subjectData->niveau) ?: @$subjectData->niveau;
				if (strlen($niveau) <= 1) {
					// G M E
					$niveau = 'Niveau: '.static::spacerIfEmpty($niveau);
				}

				$grade = (@$studentdata->print_grades ? 'Note: '.static::spacerIfEmpty(@$subjectData->grade) : '');

				$cloneValues[] = [
					"subject" => $subject->title,
					"subjecttext" => $subjectData->review,
					"subjectniveau" => $niveau,
					"subjectgrade" => $grade,
				];
			}

			$templateProcessor->cloneBlock('subjectif', count($cloneValues), true);
			foreach ($cloneValues as $cloneValue) {
				foreach ($cloneValue as $key => $value) {
					$templateProcessor->setValue($key, $value, 1);
				}
			}


			if ($logo = block_exastud_get_main_logo()) {
				$image = $logo->copy_content_to_temp();
				$size = @getimagesize($image);

				if ($size) {
					$templateProcessor->updateFile('word/media/image1.gif', $image);

					$templateProcessor->applyFiltersAllParts([function($content) use ($size) {
						return preg_replace_callback('!<wp:extent cx="(?<viewportcx>[0-9]*)" cy="(?<viewportcy>[0-9]*)".*name="Picture [12]".*cx="(?<cx>[0-9]*)" cy="(?<cy>[0-9]*)"!U', function($matches) use ($size) {
							if ($size[0] / $size[1] > $matches['cx'] / $matches['cy']) {
								$w = round($matches['cx']);
								$h = round($matches['cx'] / $size[0] * $size[1]);
							} else {
								$w = round($matches['cy'] / $size[1] * $size[0]);
								$h = round($matches['cy']);
							}

							return str_replace([$matches['cx'], $matches['cy'], $matches['viewportcx'], $matches['viewportcy']], [$w, $h, $w, $h], $matches[0]);
						}, $content);
					}]);
				}
			}

		} elseif (in_array($templateid, [
			'BP 2016/GMS Zeugnis 1.HJ',
			'BP 2016/GMS Zeugnis SJ',
			'BP 2004/GMS Zeugnis 1.HJ',
			'BP 2004/GMS Zeugnis SJ',
		])) {
			$bpsubjects = block_exastud_get_bildungsplan_subjects($class->bpid);
			$class_subjects = block_exastud_get_class_subjects($class);
			$lern_soz = block_exastud_get_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN);

			// use current year or last year
			if (date('m', $certificate_issue_date_timestamp) >= 9) {
				$year1 = date('Y', $certificate_issue_date_timestamp);
			} else {
				$year1 = date('Y', $certificate_issue_date_timestamp) - 1;
			}
			$year2 = $year1 + 1;
			$year1 = str_pad($year1, 2, '0', STR_PAD_LEFT);
			$year2 = str_pad($year2, 2, '0', STR_PAD_LEFT);

			$schuljahr = $year1.'/'.$year2;

			$data = [
				'schule' => get_config('exastud', 'school_name'),
				'ort' => get_config('exastud', 'school_location'),
				'name' => $student->firstname.' '.$student->lastname,
				'klasse' => $class->title,
				'certda' => $certificate_issue_date_text,
				'schuljahr' => $schuljahr,
				'lern_und_sozialverhalten' => static::spacerIfEmpty($lern_soz),
				'comments' => static::spacerIfEmpty(@$studentdata->comments),
				'religion' => '---',
				'profilfach' => '---',
				'wahlpflichtfach' => '---',
			];

			if ($dateofbirth = block_exastud_get_date_of_birth_as_timestamp($student->id)) {
				$dataTextReplacer['dd'] = date('d', $dateofbirth);
				$dataTextReplacer['mm'] = date('m', $dateofbirth);
				$dataTextReplacer['yyyy'] = date('Y', $dateofbirth);
			}

			// zuerst standardwerte
			foreach ($bpsubjects as $subject) {
				$data[static::toTemplateVarId($subject->title)] = '---';
			}

			$wahlpflichtfach = '---';
			$profilfach = '---';

			// danach mit richtigen werten überschreiben
			foreach ($class_subjects as $subject) {
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);

				if (!$subjectData) {
					continue;
				}

				$subject->title = preg_replace('!\s*\(.*$!', '', $subject->title);

				if (in_array($subject->shorttitle, [
					'RALE',
					'RAK',
					'ETH',
					'REV',
					'RISL',
					'RJUED',
					'RRK',
					'ROR',
					'RSYR',
				])) {
					$dataTextReplacer['Ethik (ETH)'] = $subject->title.' ('.$subject->shorttitle.')';
					$contentId = 'religion';
				} elseif (strpos($subject->title, 'Wahlpflichtfach') === 0) {
					$wahlpflichtfach = preg_replace('!^[^\s]+!', '', $subject->title);
					$contentId = 'wahlpflichtfach';
				} elseif (strpos($subject->title, 'Profilfach') === 0) {
					$profilfach = preg_replace('!^[^\s]+!', '', $subject->title);
					$contentId = 'profilfach';
				} else {
					$contentId = static::toTemplateVarId($subject->title);
				}

				$data[$contentId] = static::spacerIfEmpty(@$subjectData->review);

				$niveau = \block_exastud\global_config::get_niveau_option_title(@$subjectData->niveau) ?: @$subjectData->niveau;
				if (strlen($niveau) <= 1) {
					// G M E
					$niveau = 'Niveau '.static::spacerIfEmpty($niveau);
				}
				$filters[$contentId.'_niveau'] = function($content) use ($contentId, $niveau) {
					return preg_replace('!({'.$contentId.'}.*>)Bitte die Niveaustufe auswählen(<)!U', '${1}'.$niveau.'${2}', $content);
				};

				$grade = (@$studentdata->print_grades ? 'Note '.static::spacerIfEmpty(@$subjectData->grade) : '');
				$filters[$contentId.'_grade'] = function($content) use ($contentId, $grade) {
					return preg_replace('!({'.$contentId.'}.*>)ggf. Note(<)!U', '${1}'.$grade.'${2}', $content);
				};
			}

			// wahlpflichtfach + profilfach dropdowns
			$add_filter(function($content) use ($wahlpflichtfach) {
				return preg_replace('!(>)Technik(<.*{'.'wahlpflichtfach'.'})!U', '${1}'.$wahlpflichtfach.'${2}', $content);
			});
			$add_filter(function($content) use ($profilfach) {
				return preg_replace('!(>)Spanisch(<.*{'.'profilfach'.'})!U', '${1}'.$profilfach.'${2}', $content);
			});

			// nicht befüllte niveaus und noten befüllen
			$dataTextReplacer['Bitte die Niveaustufe auswählen'] = 'Niveau ---';
			$dataTextReplacer['ggf. Note'] = @$studentdata->print_grades ? 'Note ---' : '';
		} elseif (in_array($templateid, [
			'BP 2004/GMS Realschulabschluss 1.HJ',
			'BP 2004/GMS Realschulabschluss SJ',
			'BP 2004/GMS Klasse 10 E-Niveau 1.HJ',
			'BP 2004/GMS Klasse 10 E-Niveau SJ',
			'BP 2004/GMS Hauptschulabschluss 1.HJ',
			'BP 2004/GMS Hauptschulabschluss SJ',
			'BP 2004/GMS Abgangszeugnis',
			'BP 2004/GMS Abgangszeugnis HSA Kl.9 und 10',
			'BP 2004/Zertifikat fuer Profilfach',
			'BP 2004/Beiblatt zur Projektpruefung HSA',
			'BP 2004/GMS Abschlusszeugnis der Förderschule',
		    'BP 2004/GMS Halbjahreszeugniss der Förderschule',
		])) {
			$class_subjects = block_exastud_get_class_subjects($class);

			$wahlpflichtfach = static::spacerIfEmpty('');
			$profilfach = static::spacerIfEmpty('');
			$religion = static::spacerIfEmpty('');
			$religion_sub = '';

			$data = [
				'schule' => get_config('exastud', 'school_name'),
				'ort' => get_config('exastud', 'school_location'),
				'name' => $student->firstname.' '.$student->lastname,
				'kla' => $class->title,
				'geburt' => static::spacerIfEmpty(block_exastud_get_custom_profile_field_value($student->id, 'dateofbirth')),
				'certda' => $certificate_issue_date_text,
				'gebort' => static::spacerIfEmpty(block_exastud_get_custom_profile_field_value($student->id, 'placeofbirth')),
				'ags' => static::spacerIfEmpty(@$studentdata->ags),
				'projekt_thema' => static::spacerIfEmpty(@$studentdata->projekt_thema),
				'projekt_verbalbeurteilung' => static::spacerIfEmpty(@$studentdata->projekt_verbalbeurteilung),
				'datum' => date('d.m.Y'),
			];


			foreach ($template->get_inputs() as $inputid => $tmp) {
				if (!isset($data[$inputid])) {
					$data[$inputid] = static::spacerIfEmpty(@$studentdata->{$inputid});
				}
			}

			$placeholder = 'ph'.time();

			$grades = $template->get_grade_options();

			$add_filter(function($content) use ($placeholder) {
				// im template 'BP 2004/Halbjahresinformation Klasse 10Gemeinschaftsschule_E-Niveau_BP 2004' ist der Standardwert "2 plus"
				$ret = preg_replace('!>\s*(sgt|sehr gut|2 plus)\s*<!', '>'.$placeholder.'note<', $content, -1, $count);

				/*
				 * if (!$count) {
				 * throw new \Exception('sgt not found');
				 * }
				 */

				return $ret;
			});

			// noten
			foreach ($class_subjects as $subject) {
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);

				if (!$subjectData || !@$subjectData->grade) {
					continue;
				}

				$subject->title = preg_replace('!\s*\(.*$!', '', $subject->title);

				if (in_array($subject->shorttitle, [
					'RALE',
					'RAK',
					'ETH',
					'REV',
					'RISL',
					'RJUED',
					'RRK',
					'ROR',
					'RSYR',
				])) {
					if ($religion != static::spacerIfEmpty('')) {
						continue;
						// only if there is still no religion set
						// maybe there are 2 religion gradings? ignore the 2nd one
					}

					if ($subject->shorttitle == 'ETH') {
						$religion = 'Ethik';
						$religion_sub = '';
					} else {
						$religion = 'Religionslehre';
						if ($subject->shorttitle == 'RISL') {
							$religion_sub = 'islamisch sunnitischer Prägung';
						} else {
							// jüdische Relgigionslehre => jüdische
							$religion_sub = mb_strtolower(trim(str_replace('Religionslehre', '', $subject->title)));
							// jüdische => jüdisch
							$religion_sub = rtrim($religion_sub, 'e');
							// jüdisch => (jüdisch)
							$religion_sub = '('.$religion_sub.')';
						}
					}

					$gradeSearch = 'Ethik';
					$dropdownsBetween = 1; // 1, weil es selber auch ein dropdown ist
				} elseif (strpos($subject->title, 'Wahlpflichtfach') === 0) {
					$gradeSearch = 'Wahlpflicht';
					$wahlpflichtfach = preg_replace('!^[^\s]+!', '', $subject->title);
					// hier ist 1 dropdown dazwischen erlaubt (wahlpflichtfach name dropdown)
					$dropdownsBetween = 1;
				} elseif (strpos($subject->title, 'Profilfach') === 0) {
					$gradeSearch = 'Profilfach';
					$profilfach = preg_replace('!^[^\s]+!', '', $subject->title);
					// hier ist 1 dropdown dazwischen erlaubt (profilfach name dropdown)
					$dropdownsBetween = 1;
				} elseif (in_array($subject->shorttitle, [
					'EWG',
					'NWA',
				])) {
					// hier den shorttitle suchen
					$gradeSearch = $subject->shorttitle;
					$dropdownsBetween = 0;
				} else {
					$gradeSearch = '>'.$subject->title.'<';
					$dropdownsBetween = 0;
				}

				$grade = @$grades[@$subjectData->grade];
				if (!$grade) {
					// einfach die erste zahl nehmen und dann durch text ersetzen
					$grade = @$grades[substr(@$subjectData->grade, 0, 1)];
				}

				// TEST:
				// $grade = $subject->title.' '.$grade;

				$add_filter([
					'grade',
					$gradeSearch,
				], function($content) use ($gradeSearch, $grade, $placeholder, $dropdownsBetween) {
					if (!preg_match('!('.preg_quote($gradeSearch, '!').'.*)'.$placeholder.'note!U', $content, $matches)) {
						// var_dump(['fach nicht gefunden', $gradeSearch]);
						return $content;
					}

					if (substr_count($matches[0], '<w:dropDownList') > ($dropdownsBetween + 1)) {
						// da ist noch ein anderes dropdown dazwischen => fehler
						return $content;
					}

					$ret = preg_replace('!('.preg_quote($gradeSearch, '!').'.*)'.$placeholder.'note!U', '${1}'.$grade, $content, 1, $count);

					return $ret;
				});
			}

			if ($templateid == 'BP 2004/GMS Abgangszeugnis') {
				$value = static::spacerIfEmpty(@$forminputs['wann_verlassen']['values'][@$studentdata->wann_verlassen]);
				$add_filter(function($content) use ($placeholder, $value) {
					$ret = preg_replace('!>[^<]*am Ende[^<]*<!U', '>'.$value.'<', $content, -1, $count);
					if (!$count) {
						throw new \Exception('"am Ende" not found');
					}

					return $ret;
				});

				$values = [
					'G' => 'grundlegenden Niveau (G) beurteilt.',
					'M' => 'mittleren Niveau (M) beurteilt.',
					'E' => 'erweiteren Niveau (E) beurteilt.',
				];
				$value = static::spacerIfEmpty(@$values[@$studentdata->abgangszeugnis_niveau]);
				$add_filter(function($content) use ($value) {
					$ret = preg_replace('!>grundlegenden Niveau[^<]*<!U', '>'.$value.'<', $content, -1, $count);
					if (!$count) {
						throw new \Exception('"grundlegenden Niveau" not found');
					}

					return $ret;
				});
			} elseif ($templateid == 'BP 2004/GMS Abgangszeugnis HSA Kl.9 und 10') {
				$value = static::spacerIfEmpty(@$forminputs['wann_verlassen']['values'][@$studentdata->wann_verlassen]);
				$add_filter(function($content) use ($placeholder, $value) {
					$ret = preg_replace('!>[^<]*am Ende[^<]*<!U', '>'.$value.'<', $content, -1, $count);
					if (!$count) {
						throw new \Exception('"am Ende" not found');
					}

					return $ret;
				});
			} elseif ($templateid == 'BP 2004/GMS Klasse 10 E-Niveau SJ') {
				if (@$studentdata->verhalten) {
					$value = @$forminputs['verhalten']['values'][$studentdata->verhalten];
					$add_filter(function($content) use ($placeholder, $value) {
						return preg_replace('!(Verhalten.*)'.$placeholder.'note!U', '${1}'.$value, $content, -1, $count);
					});
				}
				if (@$studentdata->mitarbeit) {
					$value = @$forminputs['mitarbeit']['values'][$studentdata->mitarbeit];
					$add_filter(function($content) use ($placeholder, $value) {
						return preg_replace('!(Mitarbeit.*)'.$placeholder.'note!U', '${1}'.$value, $content, -1, $count);
					});
				}
			} elseif ($templateid == 'BP 2004/GMS Hauptschulabschluss SJ') {
				$data['gd'] = @$studentdata->gesamtnote_und_durchschnitt_der_gesamtleistungen;

				$values = [
					'9' => 'hat die Hauptschulabschlussprüfung nach Klasse 9 der Gemeinschaftsschule mit Erfolg abge-legt.',
					'10' => 'hat die Hauptschulabschlussprüfung nach Klasse 10 der Gemeinschaftsschule mit Erfolg abge-legt.',
				];
				$value = static::spacerIfEmpty(@$values[@$studentdata->bildungsstandard_erreicht]);
				$add_filter(function($content) use ($placeholder, $value) {
					$ret = preg_replace('!>[^<]*mit Erfolg[^<]*<!U', '>'.$value.'<', $content, -1, $count);
					if (!$count) {
						throw new \Exception('mit erfolg not found');
					}

					return $ret;
				});
			} elseif ($templateid == 'BP 2004/GMS Abschlusszeugnis der Förderschule') {
				$data['gd'] = static::spacerIfEmpty(@$studentdata->gesamtnote_und_durchschnitt_der_gesamtleistungen);
			}

			if ($value = @$grades[@$studentdata->projekt_grade]) {
				// im "Beiblatt zur Projektpruefung HSA" heisst das feld projet_text3lines
				$add_filter(function($content) use ($placeholder, $value) {
					return preg_replace('!(projekt_thema.*)'.$placeholder.'note!U', '${1}'.$value, $content, 1, $count);
				});
			}

			// religion + wahlpflichtfach + profilfach dropdowns
			$add_filter(function($content) use ($religion, $religion_sub, $wahlpflichtfach, $profilfach) {
				$content = preg_replace('!>\s*Ethik\s*<!U', '>'.$religion.'<', $content, 1, $count);

				/*
				 * if (!$count) {
				 * throw new \Exception('profilfach not found');
				 * }
				 */

				$content = preg_replace('!>\s*\(evangelisch\)\s*<!U', '>'.$religion_sub.'<', $content, 1, $count);

				$content = preg_replace('!(Wahlpflichtbereich.*>)Technik(<)!U', '${1}'.$wahlpflichtfach.'${2}', $content, 1, $count);

				/*
				 * if (!$count) {
				 * throw new \Exception('wahlpflichtfach not found');
				 * }
				 */

				$content = preg_replace('!(Profilfach.*>)Spanisch(<)!U', '${1}'.$profilfach.'${2}', $content, 1, $count);

				/*
				 * if (!$count) {
				 * throw new \Exception('profilfach not found');
				 * }
				 */

				return $content;
			});

			// alle restlichen noten dropdowns zurücksetzen
			$add_filter(function($content) use ($placeholder) {
				return str_replace($placeholder.'note', '--', $content);
			});
		} elseif ($templateid == 'Anlage zum Lernentwicklungsbericht') {
			$evalopts = g::$DB->get_records('block_exastudevalopt', null, 'sorting', 'id, title, sourceinfo');
			$categories = block_exastud_get_class_categories_for_report($student->id, $class->id);
			$subjects = static::get_exacomp_subjects($student->id);
            
			$data = [
				'periode' => $period->description,
				'schule' => get_config('exastud', 'school_name'),
				'ort' => get_config('exastud', 'school_location'),
				'name' => $student->firstname.' '.$student->lastname,
				'klasse' => $class->title,
				'geburtsdatum' => block_exastud_get_date_of_birth($student->id),
				'datum' => date('d.m.Y'),
			];

			$templateProcessor->duplicateCol('kheader', count($evalopts));
			foreach ($evalopts as $evalopt) {
				$templateProcessor->setValue('kheader', $evalopt->title, 1);
			}

			foreach ($categories as $category) {
				$templateProcessor->cloneRowToEnd('kriterium');
				$templateProcessor->setValue('kriterium', $category->title, 1);

				for ($i = 0; $i < count($evalopts); $i++) {
					$templateProcessor->setValue('kvalue', $category->average !== null && round($category->average) == $i ? 'X' : '', 1);
				}
			}
			$templateProcessor->deleteRow('kriterium');

			// subjects

			$templateProcessor->cloneBlock('subjectif', count($subjects), true);



			
			foreach ($subjects as $subject) {
				$templateProcessor->setValue("subject", $subject->title, 1);
				
				if(get_config('exacomp', 'assessment_topic_diffLevel') == 1 || get_config('exacomp', 'assessment_comp_diffLevel') == 1) {
				    $difflvl = get_config('exacomp', 'assessment_diffLevel_options');
				    $templateProcessor->duplicateCol('compheader', 2);
				    $templateProcessor->setValue("compheader", "Niveau", 1);
				    
				}
				$templateProcessor->setValue("compheader", "Note", 1);

				foreach ($subject->topics as $topic) {
			     	$templateProcessor->cloneRowToEnd("topic");			
					$templateProcessor->cloneRowToEnd("descriptor");
					$templateProcessor->setValue("topic", $topic->title, 1);
					$grading = @$studentdata->print_grades_anlage_leb ? $topic->teacher_eval_additional_grading : null;
					if(get_config('exacomp', 'assessment_topic_diffLevel') == 1){
					    $niveau = @$studentdata->print_grades_anlage_leb ? $topic->teacher_eval_niveau_text : null;
					$templateProcessor->setValue("tvalue", $niveau, 1);
					} else if(get_config('exacomp', 'assessment_comp_diffLevel') == 1){
					    $templateProcessor->setValue("tvalue", null, 1);
					}
					$templateProcessor->setValue("tvalue", $grading, 1);
					foreach ($topic->descriptors as $descriptor) {
						$templateProcessor->duplicateRow("descriptor");
						$templateProcessor->setValue("descriptor", ($descriptor->niveau_title ? $descriptor->niveau_title.': ' : '').$descriptor->title, 1);
						$grading = @$studentdata->print_grades_anlage_leb ? $descriptor->teacher_eval_additional_grading : null;
						if(get_config('exacomp', 'assessment_comp_diffLevel') == 1){
						    $niveau = @$studentdata->print_grades_anlage_leb ? $descriptor->teacher_eval_niveau_text : null;
						    $templateProcessor->setValue("dvalue", $niveau, 1);
						}else if(get_config('exacomp', 'assessment_topic_diffLevel') == 1){
						    $templateProcessor->setValue("dvalue", null, 1);
						}
						$templateProcessor->setValue("dvalue", $grading, 1);
					}

					$templateProcessor->deleteRow("descriptor");
				}

				$templateProcessor->deleteRow("topic");
				$templateProcessor->deleteRow("descriptor");
			}
		} elseif ($templateid == 'Anlage zum LernentwicklungsberichtAlt') {
		    $evalopts = g::$DB->get_records('block_exastudevalopt', null, 'sorting', 'id, title, sourceinfo');
		    $categories = block_exastud_get_class_categories_for_report($student->id, $class->id);
		    $subjects = static::get_exacomp_subjects($student->id);
		    
		    
		    $data = [
		        'periode' => $period->description,
		        'schule' => get_config('exastud', 'school_name'),
		        'ort' => get_config('exastud', 'school_location'),
		        'name' => $student->firstname.' '.$student->lastname,
		        'klasse' => $class->title,
		        'geburtsdatum' => block_exastud_get_date_of_birth($student->id),
		        'datum' => date('d.m.Y'),
		    ];
		    
		    $templateProcessor->duplicateCol('kheader', count($evalopts));
		    foreach ($evalopts as $evalopt) {
		        $templateProcessor->setValue('kheader', $evalopt->title, 1);
		    }
		    
		    foreach ($categories as $category) {
		        $templateProcessor->cloneRowToEnd('kriterium');
		        $templateProcessor->setValue('kriterium', $category->title, 1);
		        
		        for ($i = 0; $i < count($evalopts); $i++) {
		            $templateProcessor->setValue('kvalue', $category->average !== null && round($category->average) == $i ? 'X' : '', 1);
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
		            					$grading = @$studentdata->print_grades_anlage_leb ? $topic->teacher_eval_additional_grading : null;
		            					$templateProcessor->setValue("ne", $grading === 0 ? 'X' : '', 1);
		            					$templateProcessor->setValue("tw", $grading === 1 ? 'X' : '', 1);
		            					$templateProcessor->setValue("ue", $grading === 2 ? 'X' : '', 1);
		            					$templateProcessor->setValue("ve", $grading === 3 ? 'X' : '', 1);
		            
		            /*
		             * $gme = ['G', 'M', 'E'][$test % 3];
		             * $x = $test % 4;
		             * $test++;
		             * $templateProcessor->setValue("n", $gme.$test, 1);
		             * $templateProcessor->setValue("ne", $x === 0 ? 'X' : '', 1);
		             * $templateProcessor->setValue("tw", $x === 1 ? 'X' : '', 1);
		             * $templateProcessor->setValue("ue", $x === 2 ? 'X' : '', 1);
		             * $templateProcessor->setValue("ve", $x === 3 ? 'X' : '', 1);
		             */
		            
		            foreach ($topic->descriptors as $descriptor) {
		                $templateProcessor->duplicateRow("descriptor");
		                $templateProcessor->setValue("descriptor", ($descriptor->niveau_title ? $descriptor->niveau_title.': ' : '').$descriptor->title, 1);
		                
		                						$grading = @$studentdata->print_grades_anlage_leb ? $descriptor->teacher_eval_additional_grading : null;
		                						$templateProcessor->setValue("n", $descriptor->teacher_eval_niveau_text, 1);
		                						$templateProcessor->setValue("ne", $grading === 0 ? 'X' : '', 1);
		                						$templateProcessor->setValue("tw", $grading === 1 ? 'X' : '', 1);
		                						$templateProcessor->setValue("ue", $grading === 2 ? 'X' : '', 1);
		                						$templateProcessor->setValue("ve", $grading === 3 ? 'X' : '', 1);
		                
		                /*
		                 * $gme = ['G', 'M', 'E'][$test % 3];
		                 * $x = $test % 4;
		                 * $test++;
		                 * $templateProcessor->setValue("n", $gme.$test, 1);
		                 * $templateProcessor->setValue("ne", $x === 0 ? 'X' : '', 1);
		                 * $templateProcessor->setValue("tw", $x === 1 ? 'X' : '', 1);
		                 * $templateProcessor->setValue("ue", $x === 2 ? 'X' : '', 1);
		                 * $templateProcessor->setValue("ve", $x === 3 ? 'X' : '', 1);
		                 */
		            }
		            
		            $templateProcessor->deleteRow("descriptor");
		        }
		        
		        $templateProcessor->deleteRow("topic");
		        $templateProcessor->deleteRow("descriptor");
		    }
		} else {
			echo g::$OUTPUT->header();
			echo block_exastud_trans([
				'de:Leider wurde die Dokumentvorlage "{$a}" nicht gefunden.',
				'en:Template "{$a}" not found.',
			], $templateid);
			echo g::$OUTPUT->footer();
			exit();
		}

		// zuerst filters
		$templateProcessor->applyFilters($filters);
		$templateProcessor->setValues($data);
		$templateProcessor->replaceWords($dataTextReplacer);

		// $templateProcessor->check();

		if (optional_param('test', null, PARAM_INT)) {
			echo $templateProcessor->getDocumentMainPart();
			exit();
		}

		// save as a random file in temp file
		$temp_file = tempnam($CFG->tempdir, 'exastud');
		$templateProcessor->saveAs($temp_file);
		//change ending for dotx files
		if ($templateid == "BP 2004/GMS Abschlusszeugnis der Förderschule" || $templateid == "BP 2004/GMS Halbjahreszeugniss der Förderschule") {
			$filename = ($certificate_issue_date_text ?: date('Y-m-d'))."-".$template->get_name()."-{$class->title}-{$student->lastname}-{$student->firstname}.dotx";
		} else {
			$filename = ($certificate_issue_date_text ?: date('Y-m-d'))."-".$template->get_name()."-{$class->title}-{$student->lastname}-{$student->firstname}.docx";
		}

		return (object)[
			'temp_file' => $temp_file,
			'filename' => $filename,
		];
	}

	static function grades_report($class, $students) {
		global $CFG;

		$templateid = 'grades_report';

		$templateFile = __DIR__.'/../template/'.$templateid.'.docx';

		if (!file_exists($templateFile)) {
			throw new \Exception("template '$templateid' not found");
		}

		\PhpOffice\PhpWord\Settings::setTempDir($CFG->tempdir);
		$templateProcessor = new TemplateProcessor($templateFile);

		$period = block_exastud_get_period($class->periodid);

		$templateProcessor->setValue('schule', get_config('exastud', 'school_name'));
		$templateProcessor->setValue('periode', $period->description);
		$templateProcessor->setValue('klasse', $class->title);
		$templateProcessor->setValue('lehrer', fullname(g::$USER));
		$templateProcessor->setValue('datum', date('d.m.Y'));

		$class_subjects = block_exastud_get_bildungsplan_subjects($class->bpid);

		// split normal and grouped subjects (page 2)
		$normal_subjects = [];
		$grouped_subjects = [];
		foreach ($class_subjects as $subject) {
			if (preg_match('!religi|ethi!i', $subject->title)) {
				@$grouped_subjects['Religion / Ethik'][] = $subject;
				$subject->shorttitle_stripped = $subject->shorttitle;
			} elseif (preg_match('!^Wahlpflicht!i', $subject->title)) {
				$subject->shorttitle_stripped = preg_replace('!^WPF\s+!i', '', $subject->shorttitle);
				@$grouped_subjects['WPF'][] = $subject;
			} elseif (preg_match('!^Profilfach!i', $subject->title)) {
				$subject->shorttitle_stripped = preg_replace('!^Profil\s+!i', '', $subject->shorttitle);
				@$grouped_subjects['Profil'][] = $subject;
			} else {
				$normal_subjects[] = $subject;
			}
		}

		// page 1
		foreach ($normal_subjects as $subject) {
			$templateProcessor->setValue("s", $subject->shorttitle, 1);
		}
		$templateProcessor->setValue("s", '');

		$templateProcessor->cloneRow('student', count($students));
		$rowi = 0;
		foreach ($students as $student) {
			$rowi++;
			$templateProcessor->setValue("student#$rowi", $rowi.'. '.fullname($student));

			foreach ($normal_subjects as $subject) {
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);

				$value = $subjectData ? $subjectData->niveau.' '.$subjectData->grade : '';
				$templateProcessor->setValue("g#$rowi", $value, 1);
			}
			$templateProcessor->setValue("g#$rowi", '');
		}

		// page 2
		foreach ($grouped_subjects as $key => $subjects) {
			$templateProcessor->setValue("gs", $key, 1);
		}
		$templateProcessor->setValue("gs", '');

		$templateProcessor->cloneRow('gsstudent', count($students));
		$rowi = 0;
		foreach ($students as $student) {
			$rowi++;
			$templateProcessor->setValue("gsstudent#$rowi", $rowi.'. '.fullname($student));

			foreach ($grouped_subjects as $subjects) {
				$subjectData = null;

				foreach ($subjects as $subject) {
					$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);

					if ($subjectData && $subjectData->grade) {
						break;
					}
				}

				$value = $subjectData ? $subjectData->niveau.' '.$subjectData->grade : '';
				$templateProcessor->setValue("gsg#$rowi", $value, 1);
				$templateProcessor->setValue("gss#$rowi", $value ? $subject->shorttitle_stripped : '', 1);
			}

			$templateProcessor->setValue("gsg#$rowi", '');
			$templateProcessor->setValue("gss#$rowi", '');
		}

		// projekt
		$templateProcessor->cloneRow('prostudent', count($students));
		$rowi = 0;
		foreach ($students as $student) {
			$studentData = block_exastud_get_class_student_data($class->id, $student->id);
			$rowi++;

			$templateProcessor->setValue("prostudent#$rowi", $rowi.'. '.fullname($student));
			$templateProcessor->setValue("prog#$rowi", @$studentData->projekt_grade);
			$templateProcessor->setValue("prodescription#$rowi", @$studentData->projekt_thema);
		}

		// ags
		$templateProcessor->cloneRow('agstudent', count($students));
		$rowi = 0;
		foreach ($students as $student) {
			$studentData = block_exastud_get_class_student_data($class->id, $student->id);
			$rowi++;

			$templateProcessor->setValue("agstudent#$rowi", $rowi.'. '.fullname($student));
			$templateProcessor->setValue("agdescription#$rowi", @$studentData->ags);
		}

		// page 3
		$class_teachers = block_exastud_get_class_subject_teachers($class->id);
		$templateProcessor->cloneRow('sshort', count($class_teachers));
		$rowi = 0;
		foreach ($class_teachers as $class_teacher) {
			$rowi++;

			$subject = $class_subjects[$class_teacher->subjectid];
			$templateProcessor->setValue("sshort#$rowi", $subject->shorttitle);
			$templateProcessor->setValue("stitle#$rowi", $subject->title);
			$templateProcessor->setValue("steacher#$rowi", fullname($class_teacher));
		}

		// image
		// disable for now
		/*
		 * if ($logo = block_exastud_get_main_logo()) {
		 * $image = $logo->copy_content_to_temp();
		 * $size = @getimagesize($image);
		 *
		 * if ($size) {
		 * $templateProcessor->updateFile('word/media/image1.png', $image);
		 *
		 * $templateProcessor->applyFiltersAllParts([function($content) use ($size) {
		 * return preg_replace_callback('!<wp:extent cx="(?<viewportcx>[0-9]*)" cy="(?<viewportcy>[0-9]*)".*name="Picture [12]".*cx="(?<cx>[0-9]*)" cy="(?<cy>[0-9]*)"!U', function($matches) use ($size) {
		 * if ($size[0] / $size[1] > $matches['cx'] / $matches['cy']) {
		 * $w = round($matches['cx']);
		 * $h = round($matches['cx'] / $size[0] * $size[1]);
		 * } else {
		 * $w = round($matches['cy'] / $size[1] * $size[0]);
		 * $h = round($matches['cy']);
		 * }
		 *
		 * return str_replace([$matches['cx'], $matches['cy'], $matches['viewportcx'], $matches['viewportcy']], [$w, $h, $w, $h], $matches[0]);
		 * }, $content);
		 * }]);
		 * }
		 * }
		 */

		// save as a random file in temp file
		$temp_file = tempnam($CFG->tempdir, 'exastud');
		$templateProcessor->saveAs($temp_file);

		$filename = date('Y-m-d')."-".'Notenuebersicht'."-{$class->title}.docx";

		require_once $CFG->dirroot.'/lib/filelib.php';
		send_temp_file($temp_file, $filename);
	}

	static function grades_report_xlsx($class, $students) {
		global $CFG;

		\PhpOffice\PhpWord\Settings::setTempDir($CFG->tempdir);

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->setActiveSheetIndex(0);

		$class_subjects = block_exastud_get_bildungsplan_subjects($class->bpid);

		$cell = 0;
		$sheet->setCellValueByColumnAndRow($cell++, 1, 'Nr.');
		$sheet->setCellValueByColumnAndRow($cell++, 1, 'Name');
		foreach ($class_subjects as $subject) {
			$sheet->setCellValueByColumnAndRow($cell++, 1, $subject->shorttitle);
		}

		$sheet->setCellValueByColumnAndRow($cell++, 1, 'Projekt Note');
		$sheet->setCellValueByColumnAndRow($cell++, 1, 'Projekt Thema');
		$sheet->setCellValueByColumnAndRow($cell++, 1, 'AGs');

		$rowi = 1;
		foreach ($students as $student) {
			$rowi++;
			$cell = 0;
			$sheet->setCellValueByColumnAndRow($cell++, $rowi, $rowi - 1);
			$sheet->setCellValueByColumnAndRow($cell++, $rowi, fullname($student));

			foreach ($class_subjects as $subject) {
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);

				$value = $subjectData ? @$subjectData->niveau.' '.$subjectData->grade : '';
				$sheet->setCellValueByColumnAndRow($cell++, $rowi, $value);
			}

			$studentData = block_exastud_get_class_student_data($class->id, $student->id);
			$sheet->setCellValueByColumnAndRow($cell++, $rowi, @$studentData->projekt_grade);
			$sheet->setCellValueByColumnAndRow($cell++, $rowi, @$studentData->projekt_thema);
			$sheet->setCellValueByColumnAndRow($cell++, $rowi, @$studentData->ags);
		}

		/*
		 * $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'HTML');
		 *
		 * echo '<style>
		 * body {
		 * margin: 0 !important;
		 * padding: 5px !important;
		 * }
		 * table.gridlines td {
		 * border: 1px solid grey;
		 * padding: 3px;
		 * vertical-align: topf;
		 * }
		 * </style>';
		 * $writer->save('php://output');
		 * exit;
		 */

		$filename = date('Y-m-d')."-".'Notenuebersicht'."-{$class->title}.xlsx";

		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Excel2007');
		$temp_file = tempnam($CFG->tempdir, 'exastud');
		$writer->save($temp_file);

		require_once $CFG->dirroot.'/lib/filelib.php';
		send_temp_file($temp_file, $filename);
	}

	/*
	 * static function leb_standard_header($section) {
	 * global $student, $class;
	 *
	 * $header = $section->addHeader();
	 * $header->addTExt($student->lastname.', '.$student->firstname.', '.$class->title.', '.block_exastud_get_active_or_last_period()->description);
	 *
	 * return $header;
	 * }
	 *
	 * static function leb_standard_footer($section) {
	 * $footer = $section->addFooter();
	 * $footer->addPreserveText('Seite {PAGE} von {NUMPAGES}', null, ['align' => 'center']);
	 *
	 * return $footer;
	 * }
	 *
	 * static function leb_wrapper_table($section) {
	 * global $pageWidthTwips;
	 *
	 * // äußere tabelle, um cantSplit zu setzen (dadurch wird innere tabelle auf einer seite gehalten)
	 * $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0]);
	 * $table->addRow(null, ['cantSplit' => true]);
	 * $cell = $table->addCell($pageWidthTwips + 100); // add some extra spacing, else borders don't work
	 * // $cell->getStyle()->setBgColor('99999');
	 *
	 * return $cell;
	 * }
	 *
	 * static function leb_add_html($element, $html) {
	 *
	 * // delete span
	 * $html = preg_replace('!<span(\s[^>]+)?>!i', '', $html);
	 * $html = preg_replace('!</span>!i', '', $html);
	 *
	 * // delete styles
	 * $html = preg_replace('!\sstyle\s*=\s*"[^"]*"!i', '', $html);
	 *
	 * // delete empty paragraphs (moodle bug)
	 * $html = preg_replace('!<p>\s*</p>!i', '', $html);
	 *
	 * // delete double paraggraphs (moodle bug)
	 * $html = preg_replace('!<p>\s*<p>!i', '<p>', $html);
	 * $html = preg_replace('!</p>\s*</p>!i', '</p>', $html);
	 *
	 * $html = preg_replace('!&nbsp;!i', ' ', $html);
	 *
	 * // delete special ms office tags
	 * $html = preg_replace('!</?o:[^>]*>!i', '', $html);
	 *
	 * // phpoffice doesn't know <i> and <b>
	 * // it expects <strong> and <em>
	 * $html = preg_replace('!(</?)b(>)!i', '${1}strong${2}', $html);
	 * $html = preg_replace('!(</?)i(>)!i', '${1}em${2}', $html);
	 *
	 * \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
	 * \PhpOffice\PhpWord\Shared\Html::addHtml($element, $html);
	 * }
	 *
	 * static function leb_header_body_table($section, $header, $body = null) {
	 * global $tableWidthTwips;
	 *
	 * $cell = static::leb_wrapper_table($section);
	 *
	 * // innere tabelle
	 * $table = $cell->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
	 * $table->addRow();
	 * $cell = $table->addCell($tableWidthTwips);
	 * $cell->getStyle()->setBgColor('D9D9D9');
	 * $cell->addText($header, ['bold' => true]);
	 *
	 * if ($body !== null) {
	 * $table->addRow();
	 * static::leb_add_html($table->addCell($tableWidthTwips), $body);
	 * }
	 *
	 * return $table;
	 * }
	 *
	 * static function leb_subject_table($section, $header, $body, $right) {
	 * global $tableWidthTwips;
	 *
	 * $cell = static::leb_wrapper_table($section);
	 *
	 * // innere tabelle
	 * $table = $cell->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
	 * $table->addRow();
	 * $cell = $table->addCell($tableWidthTwips / 6 * 5);
	 * $cell->getStyle()->setBgColor('D9D9D9');
	 * // $cell->getStyle()->setGridSpan(2);
	 * $cell->addText($header, ['bold' => true]);
	 *
	 * $cell = $table->addCell($tableWidthTwips / 6);
	 * $cell->getStyle()->setBgColor('D9D9D9');
	 * $cell->addText('Niveaustufe', ['bold' => true]);
	 *
	 * $table->addRow();
	 * $cell = $table->addCell($tableWidthTwips / 6 * 5);
	 * static::leb_add_html($cell, $body);
	 *
	 * $cell = $table->addCell($tableWidthTwips / 6);
	 * static::leb_add_html($cell, $right);
	 *
	 * return $table;
	 * }
	 *
	 * static function leb($class, $student, $outputType = 'docx') {
	 * global $CFG;
	 *
	 * $dateofbirth = block_exastud_get_date_of_birth($student->id);
	 *
	 *
	 * \PhpOffice\PhpWord\Settings::setTempDir($CFG->tempdir);
	 *
	 * $phpWord = new \PhpOffice\PhpWord\PhpWord();
	 * $phpWord->setDefaultFontSize(10);
	 * $phpWord->setDefaultParagraphStyle(['spaceBefore' => 0, 'spaceAfter' => 0]);
	 *
	 * global $pageWidthTwips;
	 * $pageWidthTwips = 9200;
	 * global $tableWidthTwips;
	 * $tableWidthTwips = 9200 - 200;
	 * $tmpLogoFile = null;
	 *
	 * $section = $phpWord->addSection();
	 *
	 * // empty header on first page
	 * $header = $section->addHeader();
	 * $header->firstPage();
	 * // $footer = $section->addFooter();
	 * $footer = static::leb_standard_footer($section);
	 * $footer->firstPage();
	 *
	 * static::leb_standard_footer($section);
	 * static::leb_standard_header($section);
	 *
	 * // no header here
	 *
	 * // BW will kein logo
	 * if (false && $logo = block_exastud_get_main_logo()) {
	 * $tmpLogoFile = $logo->copy_content_to_temp();
	 * try {
	 * $section->addImage($tmpLogoFile, [
	 * 'width' => round(\PhpOffice\PhpWord\Shared\Converter::cmToPixel(3.8)), // width: 3.8cm
	 * // 'width' => round(35 * 3.8), // width: 3.8cm
	 * 'align' => 'center',
	 * ]);
	 * } catch (\PhpOffice\PhpWord\Exception\InvalidImageException $e) {
	 * print_error(block_exastud_trans('en:The configured header image has a not supported format, please contat your administrator'));
	 * }
	 * }
	 *
	 * if (get_config('exastud', 'school_name')) {
	 * $section->addText(get_config('exastud', 'school_name'),
	 * ['size' => 16, 'bold' => true], ['align' => 'center', 'spaceBefore' => 350]);
	 * }
	 * $section->addText('Lernentwicklungsbericht',
	 * ['size' => 16, 'bold' => true], ['align' => 'center', 'spaceBefore' => 350]);
	 * $section->addText(block_exastud_get_active_or_last_period()->description,
	 * ['size' => 12], ['align' => 'center', 'lineHeight' => 1, 'spaceBefore' => 350, 'spaceAfter' => 350]);
	 *
	 * $table = static::leb_wrapper_table($section)->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 80));
	 * $table->addRow();
	 * $table->addCell($tableWidthTwips / 6);
	 * $table->addCell($tableWidthTwips / 6 * 2)->addText(block_exastud_trans('de:Vor- und Zuname').':', ['bold' => true]);
	 * $table->addCell($tableWidthTwips / 6 * 3)->addText($student->firstname.' '.$student->lastname);
	 * $table->addRow();
	 * $table->addCell();
	 * $table->addCell()->addText(block_exastud_trans('de:Geburtsdatum').':', ['bold' => true]);
	 * $table->addCell()->addText($dateofbirth);
	 * $table->addRow();
	 * $table->addCell();
	 * $table->addCell()->addText(block_exastud_trans('de:Lerngruppe').':', ['bold' => true]);
	 * $table->addCell()->addText($class->title);
	 * $table->addRow();
	 * $table->addCell();
	 *
	 * $studentdata = block_exastud_get_class_student_data($class->id, $student->id);
	 *
	 * $availablesubjects = block_exastud_get_bildungsplan_subjects($class->bpid);
	 *
	 * $textReviews = g::$DB->get_records_sql("
	 * SELECT DISTINCT s.title AS id, r.review, s.title AS title, r.subjectid AS subjectid
	 * FROM {block_exastudreview} r
	 * JOIN {block_exastudsubjects} s ON r.subjectid = s.id
	 * JOIN {block_exastudclass} c ON c.periodid = r.periodid
	 * JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid = r.teacherid AND ct.subjectid=r.subjectid
	 * WHERE r.studentid = ? AND r.periodid = ? AND TRIM(r.review) != ''
	 * ", [$student->id, $class->periodid]);
	 *
	 * $subjects = [];
	 * foreach ($availablesubjects as $availablesubject) {
	 * if (isset($textReviews[$availablesubject->title])) {
	 * $textReview = $textReviews[$availablesubject->title];
	 * $subject = (object)array_merge((array)$textReview, (array)block_exastud_get_subject_student_data($class->id, $textReview->subjectid, $student->id));
	 * } elseif ($availablesubject->always_print) {
	 * $subject = (object)[
	 * 'title' => $availablesubject->title,
	 * 'review' => '---',
	 * ];
	 * } else {
	 * continue;
	 * }
	 *
	 * $subject->title = preg_replace('!\s*\(.*$!', '', $subject->title);
	 *
	 * $subjects[] = $subject;
	 * }
	 *
	 * /*
	 * $table = $section->addTable(['borderSize'=>0, 'borderColor' => 'FFFFFF', 'cellMargin'=>0]);
	 * $table->addRow(null, ['cantSplit'=>true]);
	 * $table->getStyle()->setBorderSize(0);
	 * $table->getStyle()->setCellMargin(-80);
	 * $cell = $table->addCell($pageWidthTwips);
	 * $cell->addText("fsdfssdf");
	 * $cell->getStyle()->setBgColor('333333');
	 *
	 * $table = $cell->addTable(['borderSize'=>0, 'borderColor' => 'FFFFFF', 'cellMargin'=>0]);
	 * $table->addRow();
	 * $table->getStyle()->setBorderSize(0);
	 * $table->getStyle()->setCellMargin(0);
	 * $cell = $table->addCell($tableWidthTwips);
	 * $cell->addText("fsdfssdf");
	 * $cell->getStyle()->setBgColor('666666');
	 * // $table->getStyle()->set
	 * /
	 *
	 * /*
	 * $section = $phpWord->addSection();
	 * static::leb_standard_footer($section);
	 * static::leb_standard_header($section);
	 * /
	 *
	 * /*
	 * $footer = $section->addFooter();
	 * $footer->firstPage();
	 * /
	 *
	 * // $section->addPageBreak();
	 * // phpword bug: pagebreak needs some text
	 * // $section->addText('.', ['size' => 1, 'color'=>'ffffff']);
	 *
	 * $lern_und_sozialverhalten = block_exastud_get_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN);
	 * $table = static::leb_header_body_table($section, block_exastud_trans('de:Lern- und Sozialverhalten'), block_exastud_text_to_html($lern_und_sozialverhalten) ?: '---');
	 * /*
	 * if (empty($lern_und_sozialverhalten)) {
	 * $cell = $table->getRows()[1]->getCells()[0];
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell->addText('');
	 * }
	 * /
	 *
	 * $table = static::leb_header_body_table($section, block_exastud_trans('de:Leistung in den einzelnen Fächern'), null);
	 * $cell = $table->getRows()[0]->getCells()[0];
	 * //$cell->addText('mit Angabe der Niveaustufe *, auf der die Leistungen überwiegend erbracht wurden. Auf Elternwunsch zusätzlich Note.',
	 * // ['size' => 9, 'bold' => true]);
	 *
	 * foreach ($subjects as $textReview) {
	 * static::leb_subject_table(
	 * $section,
	 * $textReview->title,
	 * block_exastud_text_to_html($textReview->review),
	 * 'Niveau: '.(@$textReview->niveau ?: '---').'<br />'.
	 * (@$studentdata->print_grades ? 'Note: '.(trim(@$textReview->grade) ?: '---').'<br />' : '')
	 * );
	 * }
	 *
	 * /*
	 * $cell = $header_body_cell('Ateliers');
	 * if (empty($studentdata['ateliers'])) {
	 * $cell->addText('');
	 * } else {
	 * static::leb_add_html($cell, $studentdata['ateliers']);
	 * }
	 *
	 * $cell = $header_body_cell('Arbeitsgemeinschaften');
	 * if (empty($studentdata['arbeitsgemeinschaften'])) {
	 * $cell->addText('');
	 * } else {
	 * static::leb_add_html($cell, $studentdata['arbeitsgemeinschaften']);
	 * }
	 *
	 * $cell = $header_body_cell('Besondere Stärken');
	 * if (empty($studentdata['besondere_staerken'])) {
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell->addText('');
	 * } else {
	 * static::leb_add_html($cell, $studentdata['besondere_staerken']);
	 * }
	 * /
	 *
	 * /*
	 * $section = $phpWord->addSection();
	 * static::leb_standard_footer($section);
	 * static::leb_standard_header($section);
	 * /
	 *
	 * $section->addText('');
	 *
	 * $table = static::leb_header_body_table($section, 'Bemerkungen', block_exastud_text_to_html(@$studentdata->comments));
	 * if (empty($studentdata->comments)) {
	 * $cell = $table->getRows()[1]->getCells()[0];
	 * $cell->addText('---');
	 * }
	 *
	 * $section->addText('');
	 * $section->addText('');
	 * $table = static::leb_wrapper_table($section)->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0));
	 * $table->addRow();
	 * $table->addCell(500)->addText('G =');
	 * $table->addCell($tableWidthTwips - 500)->addText('Grundlegendes Niveau, entspricht den Bildungsstandards der Hauptschule');
	 * $table->addRow();
	 * $table->addCell(500)->addText('M =');
	 * $table->addCell($tableWidthTwips - 500)->addText('Mittleres Niveau, entspricht den Bildungsstandards der Realschule');
	 * $table->addRow();
	 * $table->addCell(500)->addText('E =');
	 * $table->addCell($tableWidthTwips - 500)->addText('Erweitertes Niveau, entspricht den Bildungsstandards des Gymnasiums');
	 *
	 * $wrapper = static::leb_wrapper_table($section);
	 *
	 * $location = get_config('exastud', 'school_location');
	 * $certificate_issue_date_text = block_exastud_get_certificate_issue_date_text($class);
	 * $ort_datum = ($location ? $location.", " : '').$certificate_issue_date_text;
	 *
	 * $wrapper->addText('');
	 * $wrapper->addText('');
	 * $wrapper->addText('');
	 * $wrapper->addText('');
	 *
	 * $table = $wrapper->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40));
	 * $table->addRow();
	 * $table->addCell($tableWidthTwips / 7 * 3)->addText('', null, ['align' => 'center']);
	 * $table->addCell($tableWidthTwips / 7 * 1);
	 * $table->addCell($tableWidthTwips / 7 * 3, ['borderBottomSize' => 6, 'borderBottomColor' => 'black'])->addText($ort_datum, null, ['align' => 'center']);
	 * $table->addRow();
	 * $table->addCell()->addText('', ['size' => 8], ['align' => 'center']);
	 * $table->addCell();
	 * $table->addCell()->addText('Ort, Datum', ['size' => 8], ['align' => 'center']);
	 *
	 * $wrapper->addText('');
	 * $wrapper->addText('');
	 * $table = $wrapper->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0));
	 * $table->addRow();
	 * $table->addCell($tableWidthTwips)->addText('Dienstsiegel', null, ['align' => 'center']);
	 * $wrapper->addText('');
	 *
	 * $table = $wrapper->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40));
	 * $table->addRow();
	 * $table->addCell($tableWidthTwips / 7 * 3, ['borderBottomSize' => 6, 'borderBottomColor' => 'black'])->addText('', null, ['align' => 'center']);
	 * $table->addCell($tableWidthTwips / 7 * 1);
	 * $table->addCell($tableWidthTwips / 7 * 3, ['borderBottomSize' => 6, 'borderBottomColor' => 'black'])->addText('', null, ['align' => 'center']);
	 * $table->addRow();
	 * $table->addCell()->addText('Lerngruppenbegleiterin/Lerngruppenbegleiter', ['size' => 8], ['align' => 'center']);
	 * $table->addCell();
	 * $table->addCell()->addText('Schulleiterin/Schulleiter', ['size' => 8], ['align' => 'center']);
	 *
	 * $wrapper->addText('');
	 * $wrapper->addText('');
	 * $wrapper->addText('');
	 * $wrapper->addText('');
	 *
	 * $table = $wrapper->addTable(array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40));
	 * $table->addRow();
	 * $table->addCell($tableWidthTwips / 7 * 3, ['borderBottomSize' => 6, 'borderBottomColor' => 'black'])->addText('', null, ['align' => 'center']);
	 * $table->addCell($tableWidthTwips / 7 * 1);
	 * $table->addCell($tableWidthTwips / 7 * 3, ['borderBottomSize' => 6, 'borderBottomColor' => 'black'])->addText('', null, ['align' => 'center']);
	 * $table->addRow();
	 * $table->addCell()->addText('Schülerin/Schüler', ['size' => 8], ['align' => 'center']);
	 * $table->addCell();
	 * $table->addCell()->addText('Erziehungsberechtigte/Erziehungsberechtigter', ['size' => 8], ['align' => 'center']);
	 *
	 * /*
	 * static::leb_header_body_table($section, 'Anlagen', 'Kompetenzprofile<br />Zielvereinbarungen');
	 *
	 * $section->addText('');
	 * $section->addText('');
	 * $section->addText("Lernentwicklungsgespräch(-e) Datum: _________________");
	 * $section->addText('');
	 * $location = get_config('exastud', 'school_location');
	 * $certificate_issue_date_text = block_exastud_get_certificate_issue_date_text($class);
	 * $section->addText(($location ?: "[Ort]").", den ".($certificate_issue_date_text ?: "______________"));
	 * $section->addText('');
	 * $section->addText('');
	 * $section->addText('');
	 * $section->addText("Unterschriften", ['bold' => true]);
	 * $section->addText('');
	 *
	 * $table = static::leb_wrapper_table($section)->addTable(['borderSize' => 6, 'borderColor' => 'black', 'cellMargin' => 80]);
	 * $table->addRow();
	 * $cell = $table->addCell($tableWidthTwips / 4);
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell = $table->addCell($tableWidthTwips / 4);
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell = $table->addCell($tableWidthTwips / 4);
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell = $table->addCell($tableWidthTwips / 4);
	 * $cell->addText('');
	 * $cell->addText('');
	 * $cell->addText('');
	 * $table->addRow();
	 * $cell = $table->addCell($tableWidthTwips / 4);
	 * $cell->addText('Schüler /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	 * $cell->addText('Schülerin', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	 * $cell = $table->addCell($tableWidthTwips / 4);
	 * $cell->addText('Erziehungsberechtigter /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	 * $cell->addText('Erziehungsberechtigte', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	 * $cell = $table->addCell($tableWidthTwips / 4);
	 * $cell->addText('Lernbegleiter /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	 * $cell->addText('Lernbegleiterin', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	 * $cell = $table->addCell($tableWidthTwips / 4);
	 * $cell->addText('Schulleiter /', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	 * $cell->addText('Schulleiterin', null, ['align' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]);
	 * /
	 *
	 * $certificate_issue_date_text = block_exastud_get_certificate_issue_date_text($class);
	 * $filename = ($certificate_issue_date_text ?: date('Y-m-d'))."-Lernentwicklungsbericht-{$class->title}-{$student->lastname}-{$student->firstname}.docx";
	 *
	 * if ($outputType == 'docx_test' || optional_param('test', '', PARAM_TEXT)) {
	 * // testing:
	 * echo "<h1>testing, filename: $filename</h1>";
	 * echo \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML')->getContent();
	 * exit;
	 * }
	 *
	 * $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
	 *
	 * // // save as a random file in temp file
	 * $temp_file = tempnam($CFG->tempdir, 'PHPWord');
	 * $objWriter->save($temp_file);
	 *
	 * if ($tmpLogoFile) {
	 * unlink($tmpLogoFile);
	 * }
	 *
	 * return (object)[
	 * 'temp_file' => $temp_file,
	 * 'filename' => $filename,
	 * ];
	 * }
	 */
	static function get_exacomp_subjects($studentid) {
		if (!block_exastud_is_exacomp_installed()) {
			throw new \Exception('exacomp is not installed');
		}

		if (!method_exists('block_exacomp\api', 'get_comp_tree_for_exastud')) {
			throw new \Exception('please update exacomp version to match exastud version number');
		}

		return \block_exacomp\api::get_comp_tree_for_exastud($studentid);
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
		foreach ($data as $key => $value) {
			$this->setValue($key, $value);
			/*
			 * $value = ;
			 * $content = str_replace('{'.$key.'}', $value, $content);
			 * $content = str_replace('>'.$key.'<', '>'.$value.'<', $content);
			 */
		}
	}

	function setValue($search, $replace, $limit = self::MAXIMUM_REPLACEMENTS_DEFAULT) {
		$replace = $this->escape($replace);
		$replace = str_replace([
			"\r",
			"\n",
		], [
			'',
			'</w:t><w:br/><w:t>',
		], $replace);

		return $this->setValueRaw($search, $replace, $limit);
	}

	function setValueRaw($search, $replace, $limit = self::MAXIMUM_REPLACEMENTS_DEFAULT) {
		$oldEscaping = \PhpOffice\PhpWord\Settings::isOutputEscapingEnabled();

		// it's a raw value
		\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(false);

		$ret = parent::setValue($search, $replace, $limit);

		\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled($oldEscaping);

		return $ret;
	}

	function applyFilters($filters) {
		foreach ($filters as $filter) {
			$this->tempDocumentMainPart = $filter($this->tempDocumentMainPart);
		}
	}

	function applyFiltersAllParts($filters) {
		foreach ($filters as $filter) {
			$this->tempDocumentHeaders = $filter($this->tempDocumentHeaders);
			$this->tempDocumentMainPart = $filter($this->tempDocumentMainPart);
			$this->tempDocumentFooters = $filter($this->tempDocumentFooters);
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
	 * function strTagPos($string, $tag, $offset) {
	 * $tagStart = strpos($string, '<'.$tag.' ', $offset);
	 *
	 * if (!$tagStart) {
	 * $tagStart = strpos($string, '<'.$tag.'>', $string);
	 * }
	 * if (!$tagStart) {
	 * throw new Exception('Can not find the start position of tag '.$tag.'.');
	 * }
	 *
	 * return $tagStart;
	 * }
	 *
	 * function strrTagPos($string, $tag, $offset) {
	 * $tagStart = strrpos($this->tempDocumentMainPart, '<w:'.$tag.' ', ((strlen($this->tempDocumentMainPart) - $offset) * -1));
	 *
	 * if (!$tagStart) {
	 * $tagStart = strrpos($this->tempDocumentMainPart, '<w:'.$tag.'>', ((strlen($this->tempDocumentMainPart) - $offset) * -1));
	 * }
	 * if (!$tagStart) {
	 * throw new Exception('Can not find the start position of tag '.$tag.'.');
	 * }
	 *
	 * return $tagStart;
	 * }
	 *
	 * function findTagEnd($tag, $offset) {
	 * $search = '</w:'.$tag.'>';
	 *
	 * return strpos($this->tempDocumentMainPart, $search, $offset) + strlen($search);
	 * }
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
		 * if (!preg_match('!<w:'.$tag.'[\s>].*$!Uis', substr($this->tempDocumentMainPart, 0, $offset), $matches)) {
		 * throw new \Exception('tagStart $tag not found');
		 * }
		 *
		 * echo $offset - strlen($matches[0]);
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

		$table = $this->slice($this->tempDocumentMainPart, $this->rfindTagStart('tbl', $tagPos), $this->findTagEnd('tbl', $tagPos));

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
		for ($i = 1; $i < count($splits); $i+=3) {
			$splits[$i] = preg_replace('!(w:w=")[0-9]+!', '${1}'.$newWidth, $splits[$i]);
			$splits[$i+1] = str_repeat($splits[$i+1], $numberOfCols);
		}

		$table->set(join('', $splits));

		$this->tempDocumentMainPart = $table->join();
	}

	function escape($str) {
		static $xmlEscaper = null;
		if (!$xmlEscaper) {
			$xmlEscaper = new Xml();
		}

		return $xmlEscaper->escape($str);
	}

	function updateFile($filename, $path) {
		return $this->zipClass->addFromString($filename, file_get_contents($path));
	}
}
