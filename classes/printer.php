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

use block_exacomp\cross_subject;
use block_exastud\globals as g;
use core\plugininfo\search;
use PhpOffice\PhpWord\Escaper\RegExp;
use PhpOffice\PhpWord\Escaper\Xml;

class printer {

	static function spacerIfEmpty($value) {
		$value = trim($value);

		if ($value == '/--empty--/') {
		    return '';
        }

		if (!trim(strip_tags($value))) {
			return '---';
		} else {
			return $value;
		}
	}

	static function toTemplateVarId($name) {
		return preg_replace('![^a-z]+!', '_', strtolower(trim($name)));
	}

    static function report_to_temp_file($class, $student, $templateid, $courseid) {
		global $CFG, $USER;
        require_once($CFG->dirroot . '/blocks/exastud/lib/reports_lib.php');

        $certificate_issue_date_text = block_exastud_get_certificate_issue_date_text($class);
		$certificate_issue_date_timestamp = block_exastud_get_certificate_issue_date_timestamp($class);
		$studentdata = block_exastud_get_class_student_data($class->id, $student->id);
		$period = block_exastud_get_period($class->periodid);

        $class_subjects = block_exastud_get_class_subjects($class);

        // change template for bilingual. it must be always only selected in 'teacher options page'
        if (in_array($templateid, [
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_TESTAT_BILINGUALES_PROFIL_KL_8,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_ZERTIFIKAT_BILINGUALES_PROFIL_KL_10,
        ])) {
            $templateid = block_exastud_get_class_bilingual_template($class->id, $student->id)->get_template_id();
        }
		if ($templateid == BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE) {
			$template = block_exastud_get_student_print_template($class, $student->id);
            $forminputs = $template->get_inputs(BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE);
            $template_type = $templateid; // for using later
			$templateid = $template->get_template_id();
		} else /*if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT_SIMPLE) {
            // temporary. TODO: delete this when the DB will be updated
            $templatesAll = block_exastud_get_default_templates();
            $tmplArr = $templatesAll['Anlage simple'];
            $tempTemplateName = $tmplArr['name'];
            $templateFile = __DIR__.'/../template/'.$tmplArr['file'].'.docx';
            $template = null;
            $forminputs = $tmplArr['inputs'];
        } else*/ {
			$template = \block_exastud\print_template::create($templateid);
            $forminputs = $template->get_inputs($templateid);
            $template_type = $templateid; // for using later
		}

		/*
		 * if ($templateid == 'leb_alter_bp_hj') {
		 * return static::leb($class, $student);
		 * }
		 */

        $allinputs = $template->get_inputs('all');

		if (!isset($templateFile)) {
            $templateFile = $template->get_file();
        }

		// check if file does exist
		if (!file_exists($templateFile)) {
			throw new \Exception("template $templateid not found");
		}

		\PhpOffice\PhpWord\Settings::setTempDir($CFG->tempdir);
		$templateProcessor = new TemplateProcessor($templateFile);

		$data = [];
		$dataTextReplacer = [];
		$filters = [];

		$add_filter = function($id, $filter = null, $replace = false) use (&$filters) {
			if (is_callable($id)) {
				$filters[] = $id;
			} else {
			    if (!$replace) { // do not replace if it is existing already
                    if (!isset($filters[join(',', $id)])) {
                        $filters[join(',', $id)] = $filter;
                    }
                } else {
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
            if ($certificate_issue_date_timestamp) {
                if (date('m', $certificate_issue_date_timestamp) >= 9) {
                    $year1 = date('y', $certificate_issue_date_timestamp);
                } else {
                    $year1 = date('y', $certificate_issue_date_timestamp) - 1;
                }
            } else {
                if (date('m') >= 9) {
                    $year1 = date('y');
                } else {
                    $year1 = date('y') - 1;
                }
            }
			$year2 = $year1 + 1;
			$year1 = str_pad($year1, 2, '0', STR_PAD_LEFT);
			$year2 = str_pad($year2, 2, '0', STR_PAD_LEFT);
			return preg_replace('!([^0-9])99([^0-9].{0,3000}[^0-9])99([^0-9])!U', '${1}'.$year1.'${2}'.$year2.'${3}', $content, 1, $count);
			//return preg_replace('!([^0-9])99([^0-9].{0,3000}[^0-9])99([^0-9])!U', '${1}'.$year1.'${2}'.$year2.'${3}', $content, 1, $count);
		});
        $lern_soz = block_exastud_get_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN);
        $fs = get_file_storage();
        // markers for using in dropdowns. Used later as constant list
        $data_dropdowns = ['profilfach_titel', 'wahlfach_titel', 'gender_select'];
		// default markers
        $data = [
                'periode' => $period->description,
                'schule' => get_config('exastud', 'school_name'),
                'schule_type' => get_config('exastud', 'school_type'),
                'schule_nametype' => get_config('exastud', 'school_name').' '.get_config('exastud', 'school_type'),
                'ort' => get_config('exastud', 'school_location'),
                'name' => $student->firstname.' '.$student->lastname,
                'student_name' => $student->firstname.' '.$student->lastname,
                'first_name' => $student->firstname,
                'firstname' => $student->firstname,
                'last_name' => $student->lastname,
                'lastname' => $student->lastname,
                'geburtsdatum' => block_exastud_get_date_of_birth($student->id),
                'klasse' => (trim($class->title_forreport) ? $class->title_forreport : $class->title),
                'kla' => (trim($class->title_forreport) ? $class->title_forreport : $class->title),
                'certda' => $certificate_issue_date_text,
                'religion' => '---',
                'profilfach' => '---',
                'wahlpflichtfach' => '---',
                'projekt_thema' => static::spacerIfEmpty(@$studentdata->projekt_thema),
                'projekt_verbalbeurteilung' => static::spacerIfEmpty(block_exastud_crop_value_by_template_input_setting(@$studentdata->projekt_verbalbeurteilung, $templateid, 'projekt_verbalbeurteilung')),
                'comments' => static::spacerIfEmpty(block_exastud_crop_value_by_template_input_setting(@$studentdata->comments, $templateid, 'comments')),
                'comments_short' => static::spacerIfEmpty(block_exastud_crop_value_by_template_input_setting(@$studentdata->comments_short, $templateid, 'comments_short')),
                'ags' => static::spacerIfEmpty(block_exastud_crop_value_by_template_input_setting(@$studentdata->ags, $templateid, 'ags')),
                'lern_und_sozialverhalten' => static::spacerIfEmpty($lern_soz),
                'learn_social_behavior' => static::spacerIfEmpty($lern_soz),
        ];
        // gender
        $gender = block_exastud_get_user_gender($student->id);
        switch ($gender) {
            case 'male':
                $data['gender_select'] = 'Der Schüler';
                break;
            case 'female':
                $data['gender_select'] = 'Die Schülerin';
                break;
            default:
                $data['gender_select'] = '---';
        }

        // school logo: ${school_logo}  : mantis 3450 - only for grades_report
        //if (!$templateProcessor->addImageToReport('school_logo', 'exastud', 'block_exastud_schoollogo', 0, 1024, 768)) {
            $dataKey['school_logo'] = ''; // no logo files
        //};
        // class logo: ${class_logo}
        //if (!$templateProcessor->addImageToReport('class_logo', 'block_exastud', 'class_logo', $class->id, 1024, 768)) {
        //    $dataKey['class_logo'] = ''; // no logo files
        //};
		// preparation data from template settings
        if ($template) {
            $marker_configurations = $template->get_marker_configurations('all', $class, $student);
            $data = array_merge($data, $marker_configurations);
        }
        // preparation data from images
        if ($template) {
            $inputs = print_templates::get_template_inputs($templateid, 'all');
        } else {
            $inputs = null;
        }
        if ($inputs && count($inputs) > 0) {
            $context = \context_system::instance();
            foreach ($inputs as $dataKey => $input) {
                if ($input['type'] == 'image') {
                    if (!$templateProcessor->addImageToReport(null, $dataKey, 'block_exastud', 'report_image_'.$dataKey, $student->id, $input['width'], $input['height'], true)) {
                        $data[$dataKey] = ''; // empty image
                    }
                    /*$files = $fs->get_area_files($context->id, 'block_exastud', 'report_image_'.$dataKey, $student->id, 'itemid', false);
                    if ($files) {
                        foreach ($files as $file) {
                            if ($file->get_userid() != $USER->id) {
                                continue;
                            }
                            $file_content = $file->get_content();
                            $file_info = $file->get_imageinfo();
                            $fileExt = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
                            $templateProcessor->setMarkerImages($dataKey, $file_content, $fileExt, $input['width'], $input['height'], $file_info);
                        }
                    } else {
                        $data[$dataKey] = ''; // empty image
                    }*/
                }
            }
        }

		if (in_array($templateid, array(
		        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_DEFAULT_REPORT,
                //BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_DEFAULT_REPORT_COMMON
        ))) { // default_report
			//$class_subjects = block_exastud_get_class_subjects($class);
			//$lern_soz = block_exastud_get_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN);

			//$data = [
				//'periode' => $period->description,
				//'lern_und_sozialverhalten' => static::spacerIfEmpty($lern_soz),
				//'certda' => $certificate_issue_date_text,
				//'schule' => get_config('exastud', 'school_name'),
				//'ort' => get_config('exastud', 'school_location'),
				//'name' => $student->firstname.' '.$student->lastname,
				//'geburtsdatum' => block_exastud_get_date_of_birth($student->id),
				//'klasse' => $class->title,
				//'comments' => static::spacerIfEmpty(@$studentdata->comments),
			//];

			$cloneValues = [];
			foreach ($class_subjects as $subject) {
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);

                if (!$subjectData || (!$subjectData->grade
                                && !(array_key_exists('subjects', $allinputs)
                                        && $subjectData->niveau
                                        && trim($subjectData->review)
                                ))
                ) {
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

			// if ($templateid != BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_DEFAULT_REPORT_COMMON && $logo = block_exastud_get_main_logo()) {
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
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
                ])) {
		    $showNiveauZComments = false; // show niveau Z comments if at least one subject has Z niveau
			$bpsubjects = block_exastud_get_bildungsplan_subjects($class->bpid);
            //$class_subjects = block_exastud_get_class_subjects($class);
            //$lern_soz = block_exastud_get_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN);

			/*// use current year or last year
			if (date('m', $certificate_issue_date_timestamp) >= 9) {
				$year1 = date('Y', $certificate_issue_date_timestamp);
			} else {
				$year1 = date('Y', $certificate_issue_date_timestamp) - 1;
			}
			$year2 = $year1 + 1;
			$year1 = str_pad($year1, 2, '0', STR_PAD_LEFT);
			$year2 = str_pad($year2, 2, '0', STR_PAD_LEFT);

			$schuljahr = $year1.'/'.$year2;*/

			//$data = [
				//'schule' => get_config('exastud', 'school_name'),
				//'ort' => get_config('exastud', 'school_location'),
				//'name' => $student->firstname.' '.$student->lastname,
				//'klasse' => $class->title,
				//'certda' => $certificate_issue_date_text,
				//'schuljahr' => $schuljahr,
				//'lern_und_sozialverhalten' => static::spacerIfEmpty($lern_soz),
				//'comments' => static::spacerIfEmpty(@$studentdata->comments),
				//'religion' => '---',
				//'profilfach' => '---',
				//'wahlpflichtfach' => '---',
			//];

			if ($dateofbirth = block_exastud_get_date_of_birth_as_timestamp($student->id)) {
				$dataTextReplacer['dd'] = date('d', $dateofbirth);
				$dataTextReplacer['mm'] = date('m', $dateofbirth);
				$dataTextReplacer['yyyy'] = date('Y', $dateofbirth);
			}

			// zuerst standardwerte
			foreach ($bpsubjects as $subject) {
				$data[static::toTemplateVarId($subject->title)] = '---';
			}

			$wahlpflichtfach = static::spacerIfEmpty('');
			$profilfach = static::spacerIfEmpty('');
            $religion = static::spacerIfEmpty('');

			// danach mit richtigen werten überschreiben
			foreach ($class_subjects as $subject) {
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);

				if (!$subjectData) {
					continue;
				}

				$subject->title = preg_replace('!\s*\(.*$!', '', $subject->title);

				if (in_array($subject->shorttitle, [
					'alev',
					'ak',
					'eth',
					'ev',
					'isl',
					'jd',
					'rk',
					'orth',
					'syr',
				])) {
                    if ($religion != static::spacerIfEmpty('') && $religion != 'Ethik') {
                        continue;
                        // only if there is still no religion set
                        // maybe there are 2 religion gradings? ignore the 2nd one
                    }
                    if (!$subjectData ||
                            // at least one of grade/niveau/review
                            !(  $subjectData->grade
                                || $subjectData->niveau
                                || trim($subjectData->review)
                            )
                        /*(
                            (@$studentdata->print_grades && ( // if 'print_grades' - use grade and niveau and review
                                !$subjectData->grade
                                && !(array_key_exists('subjects', $allinputs)
                                    && $subjectData->niveau
                                    && trim($subjectData->review)
                                    )
                                )
                            )
                            ||
                            (!@$studentdata->print_grades && !( // if no 'print_grades' - use niveau OR review
                                            //!$subjectData->grade
                                            $subjectData->niveau
                                            || trim($subjectData->review)
                                )
                            )
                        )*/
                    ) {
                        continue; // we need to select first graded religion
                    }
                    if ($subject->shorttitle == 'eth') {
                        $religion = 'Ethik';
                    } else {
                        $religion = 'Religionslehre ('.$subject->shorttitle.')';
                    }
				    /*if ($subject->shorttitle != 'eth'){
				        $dataTextReplacer['Ethik'] = 'Religionslehre ('.$subject->shorttitle.')';
				    }*/
					$contentId = 'religion';
				} elseif (strpos($subject->title, 'Wahlpflichtfach') === 0) {
                    if ($wahlpflichtfach != static::spacerIfEmpty('')) {
                        continue;
                        // only if there is still no $wahlpflichtfach set
                        // maybe there are 2 $wahlpflichtfach gradings? ignore the 2nd one
                    }
                    /*if (!$subjectData || (!$subjectData->grade
                                    && !(array_key_exists('subjects', $allinputs)
                                            && $subjectData->niveau
                                            && trim($subjectData->review)
                                    ))
                    ) {*/
                    if (!$subjectData ||
                            // at least one of grade/niveau/review
                            !(  $subjectData->grade
                                    || $subjectData->niveau
                                    || trim($subjectData->review)
                            )
                        /*(
                                (@$studentdata->print_grades && ( // if 'print_grades' - use grade and niveau and review
                                                !$subjectData->grade
                                                && !(array_key_exists('subjects', $allinputs)
                                                        && $subjectData->niveau
                                                        && trim($subjectData->review)
                                                )
                                        )
                                )
                                ||
                                (!@$studentdata->print_grades && !( // if no 'print_grades' - use niveau OR review
                                            //!$subjectData->grade
                                                $subjectData->niveau
                                                || trim($subjectData->review)
                                        )
                                )
                        )*/
                    ) {
                        continue; // we need to select first graded $wahlpflichtfach
                    }
					$wahlpflichtfach = trim(preg_replace('!^[^\s]+!', '', $subject->title));
					$contentId = 'wahlpflichtfach';
				} elseif (strpos($subject->title, 'Profilfach') === 0) {
                    if ($profilfach != static::spacerIfEmpty('')) {
                        continue;
                        // only if there is still no profilfach set
                        // maybe there are 2 profilfach gradings? ignore the 2nd one
                    }
                    /*if (!$subjectData || (!$subjectData->grade
                                    && !(array_key_exists('subjects', $allinputs)
                                            && $subjectData->niveau
                                            && trim($subjectData->review)
                                    ))
                    ) {*/
                    if (!$subjectData ||
                            // at least one of grade/niveau/review
                            !(  $subjectData->grade
                                    || $subjectData->niveau
                                    || trim($subjectData->review)
                            )
                        /*(
                                (@$studentdata->print_grades && ( // if 'print_grades' - use grade and niveau and review
                                                !$subjectData->grade
                                                && !(array_key_exists('subjects', $allinputs)
                                                        && $subjectData->niveau
                                                        && trim($subjectData->review)
                                                )
                                        )
                                )
                                ||
                                (!@$studentdata->print_grades && !( // if no 'print_grades' - use niveau OR review
                                            //!$subjectData->grade
                                                $subjectData->niveau
                                                || trim($subjectData->review)
                                        )
                                )
                        )*/
                    ) {
                        continue; // we need to select first graded profile subject
                    }
                    $profilfachT = preg_replace('!^[^\s]+!', '', $subject->title);
					$profilfach = $profilfachT; 
                    
					$contentId = 'profilfach';
				} else {
					$contentId = static::toTemplateVarId($subject->title);
				}

				$data[$contentId] = block_exastud_cropStringByInputLimitsFromTemplate(static::spacerIfEmpty(@$subjectData->review), $templateid, 'subjects');
				if ($subject->no_niveau == 1 && !empty($subjectData->niveau) && $subjectData->niveau != 'Z' && $subjectData->niveau != 'zieldifferenter Unterricht') {
				    $niveau = 'Niveau G / M / E';
				} else {
				    $niveau = \block_exastud\global_config::get_niveau_option_title(@$subjectData->niveau) ?: @$subjectData->niveau;
				    if (strlen($niveau) <= 1) {
					   // G M E
					   $niveau = 'Niveau '.static::spacerIfEmpty($niveau);
				    }
				}

				$filters[$contentId.'_niveau'] = function($content) use ($contentId, $niveau) {
					return preg_replace('!({'.$contentId.'}.*>)Bitte die Niveaustufe auswählen(<)!U', '${1}'.$niveau.'${2}', $content);
				};

                if (!empty($subjectData->niveau) && ($subjectData->niveau == 'Z' || $subjectData->niveau == 'zieldifferenter Unterricht')) {
                    $showNiveauZComments = true;
                }

				$grade = (@$studentdata->print_grades ? 'Note '.static::spacerIfEmpty(@$subjectData->grade) : '');
				$filters[$contentId.'_grade'] = function($content) use ($contentId, $grade) {
					return preg_replace('!({'.$contentId.'}.*>)ggf. Note(<)!U', '${1}'.$grade.'${2}', $content);
				};
			}

			if ($religion != self::spacerIfEmpty('')) {
                $dataTextReplacer['Ethik'] = $religion;
            } else {
                $dataTextReplacer['Ethik'] = 'Religionslehre';
            }

			// wahlpflichtfach + profilfach dropdowns
			$add_filter(function($content) use ($wahlpflichtfach) {
                $tempWahlpflichtFach = $wahlpflichtfach;
                if ($wahlpflichtfach == self::spacerIfEmpty('')) {
                    $tempWahlpflichtFach = '';
                }
				return preg_replace('!(>)Technik(<.*{'.'wahlpflichtfach'.'})!U', '${1}'.$tempWahlpflichtFach.'${2}', $content);
			});
			$add_filter(function($content) use ($profilfach) {
			    $tempProfileFach = $profilfach;
			    if ($profilfach == self::spacerIfEmpty('')) {
			        $tempProfileFach = '';
                }
				return preg_replace('!(>)Spanisch(<.*{'.'profilfach'.'})!U', '${1}'.$tempProfileFach.'${2}', $content);
			});

			// nicht befüllte niveaus und noten befüllen
			$dataTextReplacer['Bitte die Niveaustufe auswählen'] = 'Niveau ---';
			$dataTextReplacer['ggf. Note'] = @$studentdata->print_grades ? 'Note ---' : '';

			// beiblatt
            if (in_array($templateid, [
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT])) {
                if (!empty($studentdata->lessons_target)) {
                    //$dataTextReplacer['zieldifferenter Unterricht'] = $studentdata->lessons_target ? $studentdata->lessons_target : '';
                    $dataTextReplacer['Wählen Sie ein Element aus.'] = $studentdata->lessons_target ? $studentdata->lessons_target : '';
                    $dataTextReplacer['Beiblatt'] = $studentdata->beiblatt ? $studentdata->beiblatt : '';
                } else {
                    // if not 'zieldifferenter Unterricht' - empty all Bemerkungen field
                    //$dataTextReplacer['zieldifferenter Unterricht'] = '';
                    $dataTextReplacer['Wählen Sie ein Element aus.'] = '';
                    $dataTextReplacer['Beiblatt'] = '';
                    $studentdata->focus = '/--set-empty--/';
                    $data['focus'] = '/--set-empty--/';
                    $dataTextReplacer['${lessons_target}'] = '';
                    $data['lessons_target'] = '/--set-empty--/';
                    $studentdata->lessons_target = '';
                    $data['first_name'] = '';
                    //$data['comments'] = '';
					$showNiveauZComments = false;
                }
            }
            // clean comments block if at least one subject has Z niveau
            if (!$showNiveauZComments
                && in_array($templateid, [
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
            ])) {
                $dataTextReplacer['Wählen Sie ein Element aus.'] = '';
                $dataTextReplacer['Beiblatt'] = '';
                $studentdata->focus = '/--set-empty--/';
                $data['focus'] = '/--set-empty--/';
                $dataTextReplacer['${lessons_target}'] = '';
                $data['lessons_target'] = '/--set-empty--/';
                $studentdata->lessons_target = '';
                $data['student_name'] = '';
                $data['first_name'] = '';
                //$data['comments'] = '';
                // comment instead student name - for better view
                $data['first_name'] = $data['comments'];
				$data['student_name'] = $data['comments'];
                $data['comments'] = '';
            }

            // clean bottom notification about grading
            $data_dropdowns = array_merge($data_dropdowns, array('bottom_note_title_general', 'bottom_note_title', 'bottom_note1', 'bottom_note2'));
            if (in_array($templateid, [
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_LERNENTWICKLUNGSBERICHT,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
            ])) {
                if (@$studentdata->print_grades) {
                    $data['bottom_note_title_general'] = 'Notenstufen:';
                    $data['bottom_note_title'] = 'Leistungen in den einzelnen Fächern:';
                    $data['bottom_note1'] = 'sehr gut (1) = sgt, gut (2) = gut, befriedigend (3) = bfr,';
                    $data['bottom_note2'] = 'ausreichend (4) = ausr, mangelhaft (5) = mgh, ungenügend (6) = ung';
                } else {
                    $data['bottom_note_title_general'] = '/--set-empty--/';
                    $data['bottom_note_title'] = '/--set-empty--/';
                    $data['bottom_note1'] = '/--set-empty--/';
                    $data['bottom_note2'] = '/--set-empty--/';
                }
            }

		} elseif (in_array($templateid, [
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_RS,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_SCHULPFLICHT,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_FOE,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_SCHULPFLICHT,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_FOE,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT_HSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_FOE,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRZEUGNIS_RS,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_TESTAT_BILINGUALES_PROFIL_KL_8,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_GMS_ZERTIFIKAT_BILINGUALES_PROFIL_KL_10
		])) {
			//$class_subjects = block_exastud_get_class_subjects($class);

			$wahlpflichtfach = static::spacerIfEmpty('');
			$profilfach = static::spacerIfEmpty('');
			$religion = static::spacerIfEmpty('');
			$religion_sub = '';
            $profileFachPhysikOption = '* Physik wurde anstelle des Profilfachs dreistündig belegt.';
            $subjectsToDelete = array('WBS' => 'Wirtschaft / Berufs- und Studienorientierung'); // with title in doc template
            $data['exam_english'] = '/--set-empty--/';
            if (@$studentdata->exam_english) {
                $data['exam_english'] = $studentdata->exam_english;
            }


			/*foreach ($template->get_inputs() as $inputid => $tmp) {
				if (!isset($data[$inputid])) {
					$data[$inputid] = static::spacerIfEmpty(@$studentdata->{$inputid});
				}
			}*/
			


			$placeholder = 'ph'.time();

			$grades = $template->get_grade_options();

			$add_filter(function($content) use ($placeholder) {
				// im template 'BP 2004/Halbjahresinformation Klasse 10Gemeinschaftsschule_E-Niveau_BP 2004' ist der Standardwert "2 plus"
                // this bad if the text of document contains text like 'sgt', 'sehr gut'...:
				//$ret = preg_replace('!>\s*(sgt|sehr gut|2 plus)\s*<!', '>'.$placeholder.'note<', $content, -1, $count);
                // try this regexp - change only in selectboxes. Need to check!
                $ret = preg_replace('!<w:sdtContent>(.*)(<w:t>[^\/]*)(\s*)(sgt|sehr gut|2 plus)<\/w:t>(.*)<\/w:sdtContent[^\/]*>!Us',
                                        '<w:sdtContent>${1}${2}${3}'.$placeholder.'note</w:t>${5}</w:sdtContent>', $content, -1);

				/*
				 * if (!$count) {
				 * throw new \Exception('sgt not found');
				 * }
				 */

				return $ret;
			});
            $sum = 0.0;
            $rsum = 0.0;
            $scnt = 0;
            $rcnt = 0;
            $min = 9999;
			$minForRelevantSubjects = 0;
            $useRelevantKoef = false;
			// noten
			foreach ($class_subjects as $subject) {
			    $isReligionSubject = false;
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);
				//if (!$subjectData || !@$subjectData->grade) {
				//	continue;
				//}

				$subject->title = preg_replace('!\s*\(.*$!', '', $subject->title);

				if (in_array($subject->shorttitle, [
				    'alev',
				    'ak',
				    'eth',
				    'ev',
				    'isl',
				    'jd',
				    'rk',
				    'orth',
				    'syr',
				])) {
                    $isReligionSubject = true;
					if ($religion != static::spacerIfEmpty('') && $religion != 'Ethik') {
						continue;
						// only if there is still no religion set
						// maybe there are 2 religion gradings? ignore the 2nd one
					}
                    if (!$subjectData || (!$subjectData->grade
                                    && !(array_key_exists('subjects', $allinputs)
                                            && $subjectData->niveau
                                            && trim($subjectData->review)
                                    ))
                    ) {
                        continue; // we need to select first graded religion
                    }
					if ($subject->shorttitle == 'eth') {
						$religion = 'Ethik';
						$religion_sub = '';
					} else {
						$religion = 'Religionslehre';
						$religion_sub = '('.$subject->shorttitle.')';
					}
					if ($subject->shorttitle != 'eth' &&
                        in_array($templateid, [
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE,
                            //BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_LERNENTWICKLUNGSBERICHT,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11, // here are two selectboxes
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11, // here are two selectboxes
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_FOE,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRZEUGNIS_RS,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11,
                            //BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA,
                            //BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2,
					])) {
					    $religion = 'Religionslehre ('.$subject->shorttitle.')';
					}
					$gradeSearch = 'Ethik';
					$dropdownsBetween = 1; // 1, weil es selber auch ein dropdown ist
				} elseif (strpos($subject->title, 'Wahlpflichtfach') === 0) {
                    if ($wahlpflichtfach != static::spacerIfEmpty('')) {
                        continue;
                        // only if there is still no $wahlpflichtfach set
                        // maybe there are 2 $wahlpflichtfach gradings? ignore the 2nd one
                    }
                    if (!$subjectData || (!$subjectData->grade
                                    && !(array_key_exists('subjects', $allinputs)
                                            && $subjectData->niveau
                                            && trim($subjectData->review)
                                    ))
                    ) {
                        continue; // we need to select first graded $wahlpflichtfach
                    }
                    $wahlpflichtfach = trim(preg_replace('!^[^\s]+!', '', $subject->title));
                    switch ($templateid) {
                        // may be for all?
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_RS:
                            $gradeSearch = $wahlpflichtfach;
                            $dropdownsBetween = 0;
                            break;
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_FOE:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE:
                            $gradeSearch = 'Wahlpflichtbereich'; // because 'Wahlpflicht' is using in another place
                            $dropdownsBetween = 1;
                            break;
                        default:
                            $gradeSearch = 'Wahlpflicht';
                            $dropdownsBetween = 0;
                    }
					// hier ist 1 dropdown dazwischen erlaubt (wahlpflichtfach name dropdown)
					//$dropdownsBetween = 0; // 1?
				} elseif (strpos($subject->title, 'Profilfach') === 0) {
                    if ($profilfach != static::spacerIfEmpty('')) {
                        continue;
                        // only if there is still no profilfach set
                        // maybe there are 2 profilfach gradings? ignore the 2nd one
                    }
                    if (!$subjectData || (!$subjectData->grade
                                    && !(array_key_exists('subjects', $allinputs)
                                         && $subjectData->niveau
                                         && trim($subjectData->review)
                                        ))
                                        ) {
                        continue; // we need to select first graded profile subject
                    }
                    $profilfachT = trim(preg_replace('!^[^\s]+!', '', $subject->title));
                    switch ($templateid) {
                        // may be for all?
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA:
                            $gradeSearch = 'Profilfach '.$profilfachT;
                            break;
                        default:
                            $gradeSearch = 'Profilfach';
                    }
                    /*if (@$studentdata->profilfach_fixed && @$studentdata->profilfach_fixed != $profilfachT) {
                        continue; // if the student has fixed profilfach (in review page) - we need to get values for this fixed subject
                    }*/
                    $profilfach = $profilfachT;
					// hier ist 1 dropdown dazwischen erlaubt (profilfach name dropdown)
					$dropdownsBetween = 1;
					// if at least one profilfach is graded - clear PhysikOption ('*' and description)
                    if ($subjectData) {
                        $profileFachPhysikOption = '';
                    }
				}
				// 13.06.2019 hidden for many reports, except these:
                elseif (in_array($subject->shorttitle, [
					'EWG',
					'NWA',
                ]) && in_array($templateid, [
                            //BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA,
                            //BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA,
                        ])) {
					// hier den shorttitle suchen
					//$gradeSearch = $subject->shorttitle;
					$gradeSearch = $subject->title.' ('.$subject->shorttitle.')';
					$dropdownsBetween = 0;
				} elseif (array_key_exists($subject->shorttitle, $subjectsToDelete)) {
					$gradeSearch = $subjectsToDelete[$subject->shorttitle];
					$dropdownsBetween = 0;
				} else {
					$gradeSearch = '>'.$subject->title.'<';
					$dropdownsBetween = 0;
				}
                // for deleting needed subjects
                if (array_key_exists($subject->shorttitle, $subjectsToDelete)) {
                    unset($subjectsToDelete[$subject->shorttitle]);
                }

				$grade = @$grades[@$subjectData->grade];
				if (!$grade && !empty($subjectData->grade)) { // get grade for cross grade between templates
                    $indexOfGrade = block_exastud_get_grade_index_by_value($subjectData->grade);
                    $gradeByCurrentTemplate = block_exastud_get_grade_by_index($indexOfGrade, $grades);
                    $grade = $gradeByCurrentTemplate;
                }
				if (!$grade) {
				//	// einfach die erste zahl nehmen und dann durch text ersetzen
					$grade = @$grades[substr(@$subjectData->grade, 0, 1)];
				}

                if ($isReligionSubject) {
                    $replacefilter = true;
                } else {
                    $replacefilter = false;
                }

                $add_filter([
                        'grade',
                        $gradeSearch,
                ], function($content) use ($gradeSearch, $grade, $placeholder, $dropdownsBetween, $templateid) {
                    if (!preg_match('!('.preg_quote($gradeSearch, '!').'.*)'.$placeholder.'note!U', $content, $matches)) {
                         //var_dump(['fach nicht gefunden', $gradeSearch]);
                        return $content;
                    }

                    if (substr_count($matches[0], '<w:dropDownList') > ($dropdownsBetween + 1)) {
                        // da ist noch ein anderes dropdown dazwischen => fehler
                        return $content;
                    }

                    if (!trim($grade)) {
                        $grade = '---';
                    }
                    $ret = preg_replace('!('.preg_quote($gradeSearch, '!').'.*)'.$placeholder.'note!U', '${1}'.$grade, $content,
                            1, $count);

                    return $ret;
                }, $replacefilter);

                switch ($templateid) {
                    case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA:
                    case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2:
                    case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA:
                        $avgCalcSubjects = array('D', 'M', 'E', 'G', 'Geo', 'Gk', 'WBS', 'Ph', 'Ch', 'Bio', 'Mu', 'BK', 'Sp');
                        $avgCalcSubjectsWPF = array('WPF F', 'WPF AES', 'WPF Te');
                        $avgCalcSubjectsProfil = array('Profil BK', 'Profil Mu', 'Profil Nwt', 'Profil IMP', 'Profile S', 'Profil Sp');
                        break;
                    default:
                        $avgCalcSubjects = array('D', 'M', 'E', 'G', 'BK', 'Mu', 'Sp', 'EWG', 'NWA');
                        $avgCalcSubjectsWPF = array('WPF F', 'WPF MuM', 'WPF Te');
                        $avgCalcSubjectsProfil = array('Profil BK', 'Profil Mu', 'Profil Nut', 'Profil S', 'Profil Sp');
                }
                $avgCalcSubjectsRel = array('eth', 'alev', 'ak', 'ev', 'isl', 'jd', 'rk', 'orth', 'syr');
                $avgCalcAll = array_merge($avgCalcSubjects, $avgCalcSubjectsRel, $avgCalcSubjectsWPF, $avgCalcSubjectsProfil);
                if (!isset($religionGrade)) {
                    $religionGrade = 0;
                }
				$gradeForCalc = (float)block_exastud_get_grade_index_by_value($grade);
                // to calculate the average grade
                if (in_array($subject->shorttitle, $avgCalcAll)) {
                    // look on religion (only one or Ethik).
                    // Cause 'Ethik' we need to look not only for first value. So add this value later. now - ignore that
                    switch ($templateid) {
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2:
                        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA:
						case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS:
                            // all subjects has the same weight (25.06.2019)
                            if (in_array($subject->shorttitle, $avgCalcSubjectsRel)) {
                                $religionGrade = $gradeForCalc;
                            } elseif (!in_array($subject->shorttitle, $avgCalcSubjectsProfil)) { // no calculate for Prifolefach
//                                $sum += $gradeForCalc;
//                                $scnt++;
								
								$useRelevantKoef = true;
                                if (($subject->not_relevant == 1 && $template->get_rs_hs_category() == 'HS')
                                        || ($subject->not_relevant_rs == 1 && $template->get_rs_hs_category() == 'RS')
                                ) {
                                    if ($gradeForCalc < $min) {
                                        $min = $gradeForCalc;
                                    }
                                    $rsum += $gradeForCalc;
                                    $rcnt++;
                                }
//                                echo 'added '.$subject->title.': '.$gradeForCalc.'<br>';
	                              if (!empty ($gradeForCalc)){
	                                $sum += $gradeForCalc;
	                                $scnt++;
	                              }
                            }
                            break;
                        default:
                            if (in_array($subject->shorttitle, $avgCalcSubjectsRel)) {
                                $religionGrade = $gradeForCalc;
                            } elseif (!in_array($subject->shorttitle, $avgCalcSubjectsProfil)) { // no calculate for Prifolefach
                                $useRelevantKoef = true;
                                if (($subject->not_relevant == 1 && $template->get_rs_hs_category() == 'HS')
                                        || ($subject->not_relevant_rs == 1 && $template->get_rs_hs_category() == 'RS')
                                ) {
                                    if ($gradeForCalc < $min) {
                                        $min = $gradeForCalc;
                                    }
                                    if (!empty ($gradeForCalc)){
	                                    $rsum += $gradeForCalc;
	                                    $rcnt++;
	                                  }
                                }
	                        			if (!empty ($gradeForCalc)){
	                                $sum += $gradeForCalc;
	                                $scnt++;
	                              }
                            }
                    }
                }
			}
			//exit; // delete it!

			if (isset($religionGrade) && $religionGrade > 0) {
			    $sum += $religionGrade;
//                echo 'religion: '.$religionGrade.'<br>';
                $scnt++;
            }
            $projekt_grade = (float)block_exastud_get_grade_index_by_value(@$grades[@$studentdata->projekt_grade]);
            if ($projekt_grade && $projekt_grade > 0) {
                $sum += $projekt_grade;
//                echo 'proj: '.$projekt_grade.'<br>';
                $scnt++;
            }
			if ($scnt > 0) {
                $avg = $sum / $scnt;
            } else {
			    $avg = 0;
            }
//			echo '::'.$projekt_grade;exit;
//            echo $sum.'/'.$scnt.'<br>'; exit;
//			echo 'avg: '.$avg;
//			echo '2: '.($sum - $rsum + $min).'<br>';
//			echo '3: '.(($scnt - $rcnt) + 1).'<br>';
			if ($avg > 4.4 && $useRelevantKoef) {
			    $avg = (($sum - $rsum) + $min) / (($scnt - $rcnt) + 1);
			    $avg = 0; //customer request 11.7.2019, additional conditions will be necessary
      }
//			echo 'new'.$avg;
//			exit;
			if (in_array($templateid, [
                            //BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_FOE, // is this need?
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_FOE,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA
                    ])) {
                //$avg = round($avg, 1, PHP_ROUND_HALF_DOWN); // not always correct. ???
                $fig = (int) str_pad('1', 2, '0'); // 2 (second parameter) - precision
                $avg  = (floor($avg * $fig) / $fig); // - ALWAYS round down!
                $data['gd'] = number_format($avg, 1, ',', '');
                $avgForVerbal = '1';
                $avgVerbal = 'sehr gut';
                if ($avg >= 1.5 && $avg <= 2.4) {
                    $avgVerbal = 'gut';
                    $avgForVerbal = '2';
                } else if ($avg >= 2.5 && $avg <= 3.4) {
                    $avgForVerbal = '3';
                    $avgVerbal = 'befriedigend';
                } else if ($avg >= 3.5 && $avg <= 4.4) {
                    $avgForVerbal = '4';
                    $avgVerbal = 'ausreichend';
                } else if ($avg >= 4.5) {
                    $avgForVerbal = '5';
                    $avgVerbal = 'mangelhaft';
                }else if ($avg == 0) {
                    $avgForVerbal = '0';
                    $avgVerbal = '';
                }
                // other selectboxes
//                if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA) {
//                    $avgVerbal = block_exastud_get_grades_set('short')[$avgForVerbal];
//                }

                if (in_array($templateid, [
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA])
                ) {
                    $add_filter(function($content) use ($placeholder, $avgVerbal) {
                        $ret = preg_replace('!(Gesamtleistungen.*)'.$placeholder.'note!sU', '${1}'.$avgVerbal, $content, -1, $count);
                        if (!$count) {
                            throw new \Exception('"Gesamtleistungen" not found');
                        }
                        return $ret;
                    });
                } else if (in_array($templateid, [BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA])
                ) {
                    $add_filter(function($content) use ($placeholder, $avgVerbal) {
                        $ret = preg_replace('!(Gesamtnote.*)'.$placeholder.'note!sU', '${1}'.$avgVerbal, $content, -1, $count);
                        if (!$count) {
                            throw new \Exception('"Gesamtnote" not found');
                        }
                        return $ret;
                    });
                }

			}

			// delete subjects (now it is using only for BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11)
            if (count($subjectsToDelete) > 0) {
                foreach ($subjectsToDelete as $sTitle) {
                    // at first - delete related grading
                    $add_filter(function($content) use ($placeholder, $sTitle) {
                        $ret = preg_replace('!('.preg_quote($sTitle, '!').'.*)'.$placeholder.'note!U', '${1}', $content, 1, $count);
                        return $ret;
                    });
                    // then - hide subject name
                    $add_filter(function($content) use ($placeholder, $sTitle) {
                        $ret = preg_replace('!>[^<]*'.preg_quote($sTitle, '!').'[^<]*<!U', '><', $content, 1, $count);
                        return $ret;
                    });
                }
            }

			if (in_array($templateid, [
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA
                    ])
            ) {
                $values = [
                        'G' => 'grundlegenden Niveau',
                        'M' => 'mittleren Niveau',
                        'E' => 'erweiteren Niveau',
                ];
                // for working already existing values
                if (array_key_exists(@$studentdata->abgangszeugnis_niveau, $values)) {
                    $data['abgangszeugnis_niveau'] =
                            static::spacerIfEmpty($values[$studentdata->abgangszeugnis_niveau]);
                }
            }
            // wann_verlassen
            if (in_array($templateid, [
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_SCHULPFLICHT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_SCHULPFLICHT
                    ])
            ) {
			    // for working already existing values
                $data['wann_verlassen'] = static::spacerIfEmpty((int)filter_var(@$studentdata->wann_verlassen, FILTER_SANITIZE_NUMBER_INT));
			    /* // template was changed
				$value = static::spacerIfEmpty(@$forminputs['wann_verlassen']['values'][@$studentdata->wann_verlassen]);
				$add_filter(function($content) use ($placeholder, $value) {
					$ret = preg_replace('!>[^<]*am Ende[^<]*<!U', '>'.$value.'<', $content, -1, $count);
					if (!$count) {
						throw new \Exception('"am Ende" not found');
					}

					return $ret;
				});
			    */

				$values = [
					'G' => 'grundlegenden Niveau',
					'M' => 'mittleren Niveau',
					'E' => 'erweiteren Niveau',
				];
                // for working already existing values
                if (array_key_exists(@$studentdata->abgangszeugnis_niveau, $values)) {
                    $data['abgangszeugnis_niveau'] =
                            static::spacerIfEmpty($values[$studentdata->abgangszeugnis_niveau]);
                }
				/*
				$value = static::spacerIfEmpty(@$values[@$studentdata->abgangszeugnis_niveau]);
				$add_filter(function($content) use ($value) {
					$ret = preg_replace('!>grundlegenden Niveau[^<]*<!U', '>'.$value.'<', $content, -1, $count);
					if (!$count) {
						throw new \Exception('"grundlegenden Niveau" not found');
					}

					return $ret;
				});*/
			}
			// am Ende...
			if (in_array($templateid, array(
			        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA)))
            {
				$value = static::spacerIfEmpty(@$forminputs['wann_verlassen']['values'][@$studentdata->wann_verlassen]);
				$add_filter(function($content) use ($placeholder, $value) {
					$ret = preg_replace('!>[^<]*am Ende[^<]*<!U', '>'.$value.'<', $content, -1, $count);
					if (!$count) {
						throw new \Exception('"am Ende" not found');
					}

					return $ret;
				});
			}
			// Verhalten, mitarbeit
			if (in_array($templateid, array(
			        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11))
            ) {
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
			}
			if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS) {
				//$data['gd'] = @$studentdata->gesamtnote_und_durchschnitt_der_gesamtleistungen;
                // moved to marker ${abgelegt}
				/*$values = [
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
				});*/
			}
			// * Physik wurde ...
			if (in_array($templateid, [
			        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11,])
            ) {
                $add_filter(function($content) use ($profileFachPhysikOption) {
                    $ret = preg_replace('!>[^<]*\* Physik wurde[^<]*<!U', '>'.$profileFachPhysikOption.'<', $content, -1, $count);
                    return $ret;
                });
                if (!$profileFachPhysikOption) {
                    $star = '';
                } else {
                    $star = '*';
                }
                $add_filter(function($content) use ($star) {
                    $ret = preg_replace('!>\*<!U', '>'.$star.'<', $content, -1, $count);
                    return $ret;
                });
            }
			/*elseif (in_array($templateid, [
			                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_FOE,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE,
                            ])) {*/

			// project grades
			if ($value = @$grades[@$studentdata->projekt_grade]) {
				// im "Beiblatt zur Projektpruefung HSA" heisst das feld projet_text3lines
				$add_filter(function($content) use ($placeholder, $value) {
					return preg_replace('!(projekt_thema.*)'.$placeholder.'note!U', '${1}'.$value, $content, 1, $count);
				});
			}

            $tempProfilfach = $profilfach;
            if ($profilfach == self::spacerIfEmpty('')
                    && !in_array($templateid, [
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11,
                            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11,
                                    ]
            )) {
                $tempProfilfach = '';
            }
            /*if (in_array($templateid, [
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH
                    ])
                && @$studentdata->profilfach_fixed
                && @$studentdata->profilfach_fixed != $profilfach) {
                $tempProfilfach = $studentdata->profilfach_fixed;
            }*/
            $data['profilfach_titel'] = $tempProfilfach;
            $data['wahlfach_titel'] = $wahlpflichtfach;

			// religion + wahlpflichtfach + profilfach dropdowns
			$add_filter(function($content) use ($templateid, $religion, $religion_sub, $wahlpflichtfach, $profilfach, $tempProfilfach) {
			    if ($religion == self::spacerIfEmpty('')) {
			        if (in_array($templateid, [
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_FOE,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRZEUGNIS_RS,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11,
                    ])) {
                        $religion = 'Religionslehre/Ethik';
                    } else {
                        $religion = 'Religionslehre';
                    }
                }
                $content = preg_replace('!>\s*Ethik\s*<!U', '>'.$religion.'<', $content, 1, $count);

				$content = preg_replace('!>\s*\(ev\)\s*<!U', '>'.$religion_sub.'<', $content, 1, $count);

				$content = preg_replace('!(Wahlpflichtbereich.*>)Technik(<)!U', '${1}'.$wahlpflichtfach.'${2}', $content, 1, $count);

				if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11) {
				    if ($profilfach == self::spacerIfEmpty('')) {
                        // no profile subject!
                        $content = preg_replace('!(>)Spanisch.*Profil(<)!U', '${1}Profilfach${2}', $content, 1, $count);
                    } else {
                        $content = preg_replace('!(>)Spanisch(.*Profil<)!U', '${1}'.$profilfach.'${2}', $content, 1, $count);
                    }
                } else {
                    $content = preg_replace('!(Profilfach.*>)Spanisch(<)!U', '${1}'.$tempProfilfach.'${2}', $content, 1, $count);
                }

				return $content;
			});

			// alle restlichen noten dropdowns zurücksetzen
			$add_filter(function($content) use ($placeholder, $templateid) {
                $replaceTo = '---';
                
				return str_replace($placeholder.'note', $replaceTo, $content);
			});
		} else if (in_array($templateid, [
		        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_TEMP
                 ])) {
            // very old exacomp?
            $oldExacomp = false;
            if (!function_exists('block_exacomp_get_assessment_diffLevel')) { // this function - for example
                $oldExacomp = true;
            }
			$evalopts = g::$DB->get_records('block_exastudevalopt', null, 'sorting', 'id, title, sourceinfo');
			$categories = block_exastud_get_class_categories_for_report($student->id, $class->id);
			$subjects = static::get_exacomp_subjects($student->id);
			// <!------------- from here!!!!!!!!!!!!!!!
			/*if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN) {
                $subjects = array();
            } else {*/
               /* $subjects = static::get_exacomp_subjects($student->id);
                if (!$subjects || count($subjects) == 0) {
                    // no any competences in dakora/exacomp for this student. So - no report
                    return null;
                }*/
            //}

            if (!$templateProcessor->addImageToReport(null, 'school_logo', 'exastud', 'block_exastud_schoollogo', 0, 1024, 768)) {
                $templateProcessor->setValue("school_logo", ''); // no logo files
            };

            if ($templateid != BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_TEMP) {
                // get max columns count
                $maxColumns = 0;
                if (in_array($templateid, [
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHT,
                ])) {
                    switch (block_exastud_get_competence_eval_type()) {
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                            $maxColumns = max($maxColumns, count($class_subjects));
                            break;
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                            $maxColumns = max($maxColumns, block_exastud_get_competence_eval_typeevalpoints_limit());
                            break;
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                            $maxColumns = max($maxColumns, count($evalopts));
                            break;
                    }
                    //$maxColumns++; // average value
                } else {
                    $maxColumns = count($evalopts);
                }

                $templateProcessor->duplicateCol('kheader', $maxColumns);
                /*foreach ($evalopts as $evalopt) {
                    $templateProcessor->setValue('kheader', $evalopt->title, 1);
                }*/
                switch (block_exastud_get_competence_eval_type()) {
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                        foreach ($class_subjects as $subject) {
                            $templateProcessor->setValue('kheader', $subject->title, 1);
                        }
                        break;
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                        $limit = block_exastud_get_competence_eval_typeevalpoints_limit();
                        for ($i = 1; $i <= $limit; $i++) {
                            $templateProcessor->setValue('kheader', $i, 1);
                        }
                        break;
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                        foreach ($evalopts as $evalopt) {
                            $templateProcessor->setValue('kheader', $evalopt->title, 1);
                        }
                        break;

                }

                $classteachers = array();
                $subjectsOfTeacher = array();
                $teachers = array_filter(block_exastud_get_class_subject_teachers($class->id),
                        function($o) use (&$classteachers, &$subjectsOfTeacher) {
                            if (!in_array($o->id, $classteachers)) {
                                $classteachers[] = $o->id;
                            }
                            if ($o->subjectid > 0) {
                                $subjectsOfTeacher[$o->id][] = $o->subjectid;
                            }
                            return null;
                        });
                $classteachers = array_map(function($o) {
                    return block_exastud_get_user($o);
                }, $classteachers);
                foreach ($categories as $category) {
                    $category_cnt = 0;
                    $category_total = 0;
                    foreach ($classteachers as $teacher) {
                        foreach ($subjectsOfTeacher[$teacher->id] as $subjectId) {
                            $cateReview = block_exastud_get_category_review_by_subject_and_teacher($class->periodid, $student->id,
                                    $category->id, $category->source, $teacher->id, $subjectId);
                            if (@$cateReview->catreview_value) {
                                $category_total += (@$cateReview->catreview_value ? $cateReview->catreview_value : 0);
                                $category_cnt++;
                            }
                        }
                    }
                    $average = $category_cnt > 0 ? round($category_total / $category_cnt, 2) : 0;
                    $category->average = $average;
                }

                foreach ($categories as $category) {
                    $templateProcessor->cloneRowToEnd('kriterium');
                    $templateProcessor->setValue('kriterium', $category->title, 1);
                    //echo "<pre>debug:<strong>printer.php:1443</strong>\r\n"; print_r($category); echo '</pre>'; // !!!!!!!!!! delete it
                    /*for ($i = 0; $i < count($evalopts); $i++) {
                        $templateProcessor->setValue('kvalue', $category->average !== null && round($category->average) == ($i + 1) ? 'X' : '', 1);
                    }*/
                    switch (block_exastud_get_competence_eval_type()) {
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                            foreach ($class_subjects as $subject) {
                                $v = $category->evaluationAverages[$subject->id]->value ? $category->evaluationAverages[$subject->id]->value : '';
                                $templateProcessor->setValue('kvalue', $v, 1);
                            }
                            break;
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                            for ($i = 0; $i < $maxColumns; $i++) {
                                $templateProcessor->setValue('kvalue',
                                        $category->average !== null && round($category->average) == ($i + 1) ? 'X' : '', 1);
                            }
                            break;
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                            for ($i = 0; $i < count($evalopts); $i++) {
                                $templateProcessor->setValue('kvalue',
                                        $category->average !== null && round($category->average) == ($i + 1) ? 'X' : '', 1);
                            }
                            break;

                    }
                }
                //exit;
                $templateProcessor->deleteRow('kriterium');
            }
			// subjects
			$templateProcessor->cloneBlock('subjectif', count($subjects), true);
			foreach ($subjects as $subject) {
				$templateProcessor->setValue("subject", $subject->title, 1);

				if (get_config('exacomp', 'assessment_topic_diffLevel') == 1 || get_config('exacomp', 'assessment_comp_diffLevel') == 1) {
				    $difflvl = get_config('exacomp', 'assessment_diffLevel_options');
				    $templateProcessor->duplicateCol('compheader', 2);
				    $templateProcessor->setValue("compheader", "Niveau", 1);

				}
				$templateProcessor->setValue("compheader", "Note", 1);
                $verbalsForOldExacomp = array(
                        'nicht erreicht',
                        'teilweise erreicht',
                        'überwiegend erreicht',
                        'vollständig erreicht');

				foreach ($subject->topics as $topic) {
			     	$templateProcessor->cloneRowToEnd("topic");
					$templateProcessor->cloneRowToEnd("descriptor");
					$templateProcessor->setValue("topic", $topic->title, 1);
					$grading = @$studentdata->print_grades_anlage_leb ? $topic->teacher_eval_additional_grading : null;
					if (get_config('exacomp', 'assessment_topic_diffLevel') == 1){
					    $niveau = @$studentdata->print_grades_anlage_leb ? $topic->teacher_eval_niveau_text : null;
					    $templateProcessor->setValue("tvalue", $niveau, 1);
					} else if (get_config('exacomp', 'assessment_comp_diffLevel') == 1){
					    $templateProcessor->setValue("tvalue", null, 1);
					}
					if ($oldExacomp) {
					    $grading = $verbalsForOldExacomp[$grading];
                    }
                    $templateProcessor->setValue("tvalue", $grading, 1);
					foreach ($topic->descriptors as $descriptor) {
						$templateProcessor->duplicateRow("descriptor");
						$templateProcessor->setValue("descriptor", ($descriptor->niveau_title ? $descriptor->niveau_title.': ' : '').$descriptor->title, 1);
						$grading = @$studentdata->print_grades_anlage_leb ? $descriptor->teacher_eval_additional_grading : null;
						if (get_config('exacomp', 'assessment_comp_diffLevel') == 1){
						    $niveau = @$studentdata->print_grades_anlage_leb ? $descriptor->teacher_eval_niveau_text : null;
						    $templateProcessor->setValue("dvalue", $niveau, 1);
						} elseif (get_config('exacomp', 'assessment_topic_diffLevel') == 1){
						    $templateProcessor->setValue("dvalue", null, 1);
						}
                        if ($oldExacomp) {
                            $grading = $verbalsForOldExacomp[$grading];
                        }
						$templateProcessor->setValue("dvalue", $grading, 1);
					}

					$templateProcessor->deleteRow("descriptor");
				}

				$templateProcessor->deleteRow("topic");
				$templateProcessor->deleteRow("descriptor");
			}
		} elseif (in_array($templateid, array(
		        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON
        ))) {

            // very old exacomp?
            $oldExacomp = false;
            if (!function_exists('block_exacomp_get_assessment_diffLevel')) { // this function - for example
                $oldExacomp = true;
            }

            //$templateProcessor->duplicateCol('kheader', $maxColumns + 1); // +1 = column for average
            //$templateProcessor->setValue('kheader', block_exastud_get_string('average'), 1);

		    $evalopts = g::$DB->get_records('block_exastudevalopt', null, 'sorting', 'id, title, sourceinfo');
		    $categories = block_exastud_get_class_categories_for_report($student->id, $class->id);
		    //echo "<pre>debug:<strong>printer.php:1483</strong>\r\n"; print_r($categories); echo '</pre>'; exit; // !!!!!!!!!! delete it
		    $subjects = static::get_exacomp_subjects($student->id);
            if (!$subjects || count($subjects) == 0) {
                // no any competences in dakora/exacomp for this student. So - no report
                return null;
            }

            // get max columns count
            $maxColumns = 0;
            if (in_array($templateid, array(
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON
            ))) {
                switch (block_exastud_get_competence_eval_type()) {
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                        $maxColumns = max($maxColumns, count($class_subjects));
                        break;
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                        $maxColumns = max($maxColumns, block_exastud_get_competence_eval_typeevalpoints_limit());
                        break;
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                        $maxColumns = max($maxColumns, count($evalopts));
                        break;
                }
                if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON) {
                    $maxColumns++; // average value
                }
            } else {
                $maxColumns = count($evalopts);
            }

		    $templateProcessor->duplicateCol('kheader', $maxColumns);

            if (in_array($templateid, array(
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT,
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON
            ))) {
                if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON) {
                    $templateProcessor->setValue('kheader', block_exastud_get_string('average'), 1);
                }
                switch (block_exastud_get_competence_eval_type()) {
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                        foreach ($class_subjects as $subject) {
                            $templateProcessor->setValue('kheader', $subject->title, 1);
                        }
                        break;
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                        $limit = block_exastud_get_competence_eval_typeevalpoints_limit();
                        for ($i = 1; $i <= $limit; $i++) {
                            $templateProcessor->setValue('kheader', $i, 1);
                        }
                        break;
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                        foreach ($evalopts as $evalopt) {
                            $templateProcessor->setValue('kheader', $evalopt->title, 1);
                        }
                        break;

                }
            } else {
                foreach ($evalopts as $evalopt) {
                    $templateProcessor->setValue('kheader', $evalopt->title, 1);
                }
            }

            $classteachers = array();
            $subjectsOfTeacher = array();
            $teachers = array_filter(block_exastud_get_class_subject_teachers($class->id), function($o) use (&$classteachers, &$subjectsOfTeacher) {
                if (!in_array($o->id, $classteachers)) {
                    $classteachers[] = $o->id;
                }
                if ($o->subjectid > 0) {
                    $subjectsOfTeacher[$o->id][] = $o->subjectid;
                }
                return null;
            });
            $classteachers = array_map(function($o) {return block_exastud_get_user($o);}, $classteachers);

            foreach ($categories as $category) {
                $category_cnt = 0;
                $category_total = 0;
                foreach ($classteachers as $teacher) {
                    foreach ($subjectsOfTeacher[$teacher->id] as $subjectId) {
                        $cateReview = block_exastud_get_category_review_by_subject_and_teacher($class->periodid, $student->id, $category->id, $category->source, $teacher->id, $subjectId);
                        if (@$cateReview->catreview_value) {
                            $category_total += (@$cateReview->catreview_value ? $cateReview->catreview_value : 0);
                            $category_cnt++;
                        }
                    }
                }
                $average = $category_cnt > 0 ? round($category_total / $category_cnt, 2) : 0;
                $category->average = $average;
            }

            if (in_array($templateid, array(
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON
            ))) {
                $student_review = block_exastud_get_report($student->id,  $class->periodid, $class->id);
                foreach ($categories as $category) {
                    $templateProcessor->cloneRowToEnd('kriterium');
                    $templateProcessor->setValue('kriterium', $category->title, 1);
                    $globalAverage = (@$student_review->category_averages[$category->source.'-'.$category->id] ?
                            $student_review->category_averages[$category->source.'-'.$category->id] : 0);
                    //$globalAverage = $category->average;
                    //echo "<pre>debug:<strong>printer.php:1573</strong>\r\n"; print_r($globalAverage); echo '</pre>'; // !!!!!!!!!! delete it
                    $templateProcessor->setValue('kvalue', round($globalAverage, 2), 1);
                    switch (block_exastud_get_competence_eval_type()) {
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                            foreach ($class_subjects as $subject) {
                                $v = $category->evaluationAverages[$subject->id]->value ? $category->evaluationAverages[$subject->id]->value : '';
                                $templateProcessor->setValue('kvalue', $v, 1);
                            }
                            break;
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                            for ($i = 0; $i < $maxColumns; $i++) {
                                foreach ($category->evaluationOptions as $pos_value => $option) {
                                    //$average = $category->average;
                                    //$templateProcessor->setValue('kvalue',
                                    //        $average !== null && round($average) == ($i + 1) ? 'X' : '', 1);

                                    $cellOutput = '';
                                    $subjectsList = array_map(function($reviewer) {
                                        return $reviewer->subject_shorttitle ?: fullname($reviewer);
                                    }, $option->reviewers);
                                    if (count($subjectsList)) {
                                        $cellOutput = join(', ', $subjectsList);
                                    }
                                    $templateProcessor->setValue('kvalue', $cellOutput, 1);
                                }
                            }
                            break;
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                            foreach ($category->evaluationOptions as $pos_value => $option) {
                                $cellOutput = '';
                                $subjectsList = array_map(function($reviewer) {
                                    return $reviewer->subject_shorttitle ?: fullname($reviewer);
                                }, $option->reviewers);
                                if (count($subjectsList)) {
                                    $cellOutput = join(', ', $subjectsList);
                                }
                                $templateProcessor->setValue('kvalue', $cellOutput, 1);
                            }
                            break;

                    }
                }
                //exit;
            } else {
                foreach ($categories as $category) {
                    $templateProcessor->cloneRowToEnd('kriterium');
                    $templateProcessor->setValue('kriterium', $category->title, 1);

                    switch (block_exastud_get_competence_eval_type()) {
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                            foreach ($class_subjects as $subject) {
                                $v = $category->evaluationAverages[$subject->id]->value ? $category->evaluationAverages[$subject->id]->value : '';
                                $templateProcessor->setValue('kvalue', $v, 1);
                            }
                            break;
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                            $average = $category->average;
                            for ($i = 0; $i < $maxColumns; $i++) {
                                if ($average !== null && round($average) == ($i + 1)) {
                                    $templateProcessor->setValue('kvalue', 'X', 1);
                                } else {
                                    $templateProcessor->setValue('kvalue', '', 1);
                                }
                            }
                            break;
                        case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                            for ($i = 0; $i < count($evalopts); $i++) {
                                $average = $category->average;
                                $templateProcessor->setValue('kvalue', $average !== null && round($average) == ($i + 1) ? 'X' : '', 1);
                            }
                            break;

                    }
                    /*for ($i = 0; $i < count($evalopts); $i++) {
                        $average = $category->average;
                        $templateProcessor->setValue('kvalue', $average !== null && round($average) == ($i + 1) ? 'X' : '', 1);
                    }*/
                }
            }
		    $templateProcessor->deleteRow('kriterium');
		    
		    // subjects
            if (!count($subjects)) {
                $templateProcessor->replaceBlock('headerExacompSubjects', '');
            } else {
                $templateProcessor->cloneBlock('headerExacompSubjects', 1, true);
                //$templateProcessor->replaceBlock('headerExacompSubjects', 'Erreichte Kompetenzen in den Fächern mit Kompetenzrastern');
            }

            $templateProcessor->cloneBlock('subjectif', count($subjects), true);

            // uncomment this if you need to use grading options from exacomp ..
            /*$gradesColCount = block_exacomp_get_report_columns_count_by_assessment();
            $gradesStartColumn = 0;
            $gradeopts = array();
            $gradeVerbal = array(
                    block_exacomp_get_string('grade_Verygood'),
                    block_exacomp_get_string('grade_good'),
                    block_exacomp_get_string('grade_Satisfactory'),
                    block_exacomp_get_string('grade_Sufficient'),
                    block_exacomp_get_string('grade_Deficient'),
                    block_exacomp_get_string('grade_Insufficient')
            );
            for ($i = $gradesStartColumn; $i < $gradesColCount; $i++) {
                switch (block_exacomp_get_assessment_comp_scheme()) {
                    case BLOCK_EXACOMP_ASSESSMENT_TYPE_GRADE:
                        if(get_config('exacomp', 'use_grade_verbose_competenceprofile')){
                            $gradeopts[] = $gradeVerbal[$i];
                        } else {
                            $gradeopts[] = $i;
                        }
                        break;
                    case BLOCK_EXACOMP_ASSESSMENT_TYPE_VERBOSE:
                        $titles = preg_split("/(\/|,) /", block_exacomp_get_assessment_verbose_options());
                        $gradeopts[] = $titles[$i];
                        break;
                    case BLOCK_EXACOMP_ASSESSMENT_TYPE_POINTS:
                        $gradeopts[] = $i;
                        break;
                    case BLOCK_EXACOMP_ASSESSMENT_TYPE_YESNO:
                        $gradeopts[] = $i == 1 ? block_exacomp_get_string('yes_no_Yes') : block_exacomp_get_string('yes_no_No');
                        break;
                }
            }
            $gradeopts = array_filter($gradeopts);
            $templateProcessor->duplicateCol('gheader', count($gradeopts), 2, 3);
            foreach ($gradeopts as $gradeopt) {
                $templateProcessor->setValue('gheader', $gradeopt, 1);
            }*/
            // to this (for grading)
		    $test = 0;
		    foreach ($subjects as $subject) {
		        $templateProcessor->setValue("subject", $subject->title, 1);

		        if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON) {
                    if (get_config('exacomp', 'assessment_topic_diffLevel') == 1 || get_config('exacomp', 'assessment_comp_diffLevel') == 1) {
                        $templateProcessor->duplicateCol('compheader', 2);
                        $templateProcessor->setValue("compheader", "Niveau", 1);

                    }
                    $templateProcessor->setValue("compheader", "Note", 1);
                }

		        foreach ($subject->topics as $topic) {
		            $templateProcessor->cloneRowToEnd("topic");
		            $templateProcessor->cloneRowToEnd("descriptor");
		            $templateProcessor->setValue("topic", html_entity_decode($topic->title), 1);
		            
                    $templateProcessor->setValue("n", $topic->teacher_eval_niveau_text, 1);
                    if (@$studentdata->print_grades_anlage_leb || $templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON) {
                        if (isset($topic->teacher_eval_additional_grading_real)) {
                            $grading = $topic->teacher_eval_additional_grading_real;
                        } else {
                            $grading = $topic->teacher_eval_additional_grading;
                        }
                        if ($oldExacomp) {
                            // old exacomp returns correct column number. look function get_additionalinfo_value_mapping()
                            $crossGrading = $grading;
                        } else {
                            $crossGrading = self::get_exacomp_crossgrade($grading, 'topic', 4);
                        }
                    } else {
                        $grading = -1;
                        $crossGrading = -1; // do not show at all
                    }


                    // for grading options from exacomp
                    /*for ($i = $gradesStartColumn; $i < $gradesColCount; $i++) {
                        if (array_search($grading, $gradeopts) == $i) {
                            $templateProcessor->setValue("tv", 'X', 1);
                        } else {
                            $templateProcessor->setValue("tv", '', 1);
                        }
                    }*/

                    if ($crossGrading == -1) {
                        $templateProcessor->setValue("ne", '---', 1);
                    } else {
                        $templateProcessor->setValue("ne", $crossGrading == 0 ? 'X' : '', 1);
                    }
                    $templateProcessor->setValue("tw", $crossGrading == 1 ? 'X' : '', 1);
                    $templateProcessor->setValue("ue", $crossGrading == 2 ? 'X' : '', 1);
                    $templateProcessor->setValue("ve", $crossGrading == 3 ? 'X' : '', 1);
		            
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
		            if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON) {
                        //$grading = @$studentdata->print_grades_anlage_leb ? $topic->teacher_eval_additional_grading : null;
                        if (get_config('exacomp', 'assessment_topic_diffLevel') == 1) {
                            $niveau = @$topic->teacher_eval_niveau_text ? $topic->teacher_eval_niveau_text : null;
                            $templateProcessor->setValue("tvalue", $niveau, 1);
                        } else if (get_config('exacomp', 'assessment_comp_diffLevel') == 1) {
                            $templateProcessor->setValue("tvalue", null, 1);
                        }
                        if ($crossGrading != -1) {
                            $templateProcessor->setValue("tvalue", $grading, 1);
                        } else {
                            $templateProcessor->setValue("tvalue", '', 1);
                        }
                    }
		            
		            foreach ($topic->descriptors as $descriptor) {
		                $grading = null;
		                $templateProcessor->duplicateRow("descriptor");
		                $templateProcessor->setValue("descriptor", ($descriptor->niveau_title ? html_entity_decode($descriptor->niveau_title).': ' : '').html_entity_decode($descriptor->title), 1);

                        $templateProcessor->setValue("n", $descriptor->teacher_eval_niveau_text, 1);
		                if (@$studentdata->print_grades_anlage_leb || $templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON) {
                            if (isset($descriptor->teacher_eval_additional_grading_real)) {
                                $grading = $descriptor->teacher_eval_additional_grading_real;
                            } else {
                                $grading = $descriptor->teacher_eval_additional_grading;
                            }
                            // from bug of exacomp - trying to get value from direct query. (for possibility work without upgrading of exacomp)
                            if ($grading === null) {
                                $gradeRecordExacomp = g::$DB->get_record_sql("
                                                    SELECT * FROM {".BLOCK_EXACOMP_DB_COMPETENCES."}
                                                    WHERE userid=? AND role=? AND comptype = ? AND compid = ?
                                                    ORDER BY timestamp DESC", [$student->id, BLOCK_EXACOMP_ROLE_TEACHER, BLOCK_EXACOMP_TYPE_DESCRIPTOR, $descriptor->id],
                                                IGNORE_MULTIPLE);
                                if ($gradeRecordExacomp) {
                                    if (function_exists('block_exacomp_get_assessment_comp_scheme')) {
                                        $descriptor_scheme = block_exacomp_get_assessment_comp_scheme();
                                    } else {
                                        $descriptor_scheme = 1;
                                        if (!defined('BLOCK_EXACOMP_ASSESSMENT_TYPE_GRADE')) {
                                            @define('BLOCK_EXACOMP_ASSESSMENT_TYPE_GRADE', 1);
                                        }
                                    }
                                    switch ($descriptor_scheme) {
                                        case 1:
                                        case BLOCK_EXACOMP_ASSESSMENT_TYPE_GRADE:
                                            $grading = $gradeRecordExacomp->additionalinfo;
                                            break;
                                        case 2:
                                        case BLOCK_EXACOMP_ASSESSMENT_TYPE_VERBOSE:
                                            $grading = $gradeRecordExacomp->value;
                                            break;
                                        case 3:
                                        case BLOCK_EXACOMP_ASSESSMENT_TYPE_POINTS:
                                            $grading = $gradeRecordExacomp->value;
                                            break;
                                        case 4:
                                        case BLOCK_EXACOMP_ASSESSMENT_TYPE_GRADE:
                                            $grading = $gradeRecordExacomp->value;
                                            break;
                                    }
                                }
                            }
                            if ($oldExacomp) {
                                $crossGrading = $grading;
                            } else {
                                $crossGrading = self::get_exacomp_crossgrade($grading, 'comp', 4);
                            }
                        } else {
                            $grading = -1;
                            $crossGrading = -1; // do not show at all
                        }

                        /*for ($i = $gradesStartColumn; $i < $gradesColCount; $i++) {
                            if (array_search($grading, $gradeopts) == $i) {
                                $templateProcessor->setValue("dv", 'X', 1);
                            } else {
                                $templateProcessor->setValue("dv", '', 1);
                            }
                        }*/

                        if ($crossGrading == -1) {
                            $templateProcessor->setValue("ne", '---', 1);
                        } else {
                            $templateProcessor->setValue("ne", $crossGrading == 0 ? 'X' : '', 1);
                        }
                        $templateProcessor->setValue("tw", $crossGrading == 1 ? 'X' : '', 1);
                        $templateProcessor->setValue("ue", $crossGrading == 2 ? 'X' : '', 1);
                        $templateProcessor->setValue("ve", $crossGrading == 3 ? 'X' : '', 1);

                        if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT_COMMON) {
                            if (get_config('exacomp', 'assessment_topic_diffLevel') == 1) {
                                $niveau = @$descriptor->teacher_eval_niveau_text ? $descriptor->teacher_eval_niveau_text : null;
                                $templateProcessor->setValue("dvalue", $niveau, 1);
                            } else if (get_config('exacomp', 'assessment_comp_diffLevel') == 1) {
                                $templateProcessor->setValue("dvalue", null, 1);
                            }
                            if ($crossGrading != -1) {
                                $templateProcessor->setValue("dvalue", $grading, 1);
                            } else {
                                $templateProcessor->setValue("dvalue", '', 1);
                            }
                        }

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
		    //exit; // delete it
		} else if (in_array($templateid, [
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_UEBERFACHLICHE_KOMPETENZEN_COMMON
        ])) {
            self::cross_competences_for_report($templateProcessor, $templateid, $class, $student, $class_subjects);
            /*$categories = block_exastud_get_class_categories_for_report($student->id, $class->id);

            $student_review = block_exastud_get_report($student->id, $class->periodid, $class->id);

            // get max columns count
            $maxColumns = 0;
            switch (block_exastud_get_competence_eval_type()) {
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                    $maxColumns = max($maxColumns, count($class_subjects));
                    break;
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                    foreach ($categories as $category) {
                        $maxColumns = max($maxColumns, count($category->evaluationOptions));
                    }
                    break;
            }

            // header of table
            $templateProcessor->duplicateCol('kheader', $maxColumns + 1); // +1 = column for average
            $templateProcessor->setValue('kheader', block_exastud_get_string('average'), 1);
            switch (block_exastud_get_competence_eval_type()) {
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                    foreach ($class_subjects as $subject) {
                        $templateProcessor->setValue('kheader', $subject->title, 1);
                    }
                    break;
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                    $category = reset($categories);
                    foreach ($category->evaluationOptions as $option) {
                        $templateProcessor->setValue('kheader', $option->title, 1);
                    }
                    break;

            }

            foreach ($categories as $category) {
                $templateProcessor->cloneRowToEnd('kriterium');
                $templateProcessor->setValue('kriterium', $category->title, 1);

                $globalAverage = (@$student_review->category_averages[$category->source.'-'.$category->id] ? $student_review->category_averages[$category->source.'-'.$category->id] : 0);
                $templateProcessor->setValue('kvalue', round($globalAverage, 2), 1);
                switch (block_exastud_get_competence_eval_type()) {
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                        foreach ($class_subjects as $subject) {
                            $templateProcessor->setValue('kvalue', $category->evaluationAverages[$subject->id]->value, 1);
                        }
                        break;
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                        foreach ($category->evaluationOptions as $pos_value => $option) {
                            $cellOutput = join(', ', array_map(function($reviewer) {
                                return $reviewer->subject_shorttitle ?: fullname($reviewer);
                            }, $option->reviewers));
                            $templateProcessor->setValue('kvalue', $cellOutput, 1);
                        }
                    break;

                }
            }
            $templateProcessor->deleteRow('kriterium');*/

            // code for generating table with Teachers in the headers
            /*
            // get max count of columns
            $maxReviewers = 0;
            $teacherReviews = array();
            $teachersForColumns = block_exastud_get_class_teachers($class->id);
            $tempArr = array();
            $subjectsOfTeacher = array();
            $teachersForColumns = array_filter($teachersForColumns, function($o) use (&$tempArr, &$subjectsOfTeacher) {
                        if ($o->subjectid > 0) {
                            $subjectsOfTeacher[$o->id][] = $o->subjectid;
                        }
                        if (!in_array($o->id, $tempArr) && $o->subjectid > 0) {
                            $tempArr[] = $o->id;
                            return block_exastud_get_user($o->id);
                        }
                        return null;
                    });
            //$teachersForColumns = array();
            $maxReviewers = count($teachersForColumns);
            foreach ($categories as $category) {
                $categoryKey = $category->id.'_'.$category->source;
                $teacherReviews[$categoryKey] = (object)array(
                    'title' => $category->title,
                    'average' => $category->average,
                    'reviewers' => array()
                );
                if ($category->evaluationOptions && count($category->evaluationOptions)) {
                    foreach ($category->evaluationOptions as $evalOption) {
                        if ($evalOption->reviewers && count($evalOption->reviewers)) {
                            //$maxReviewers = max($maxReviewers, count($evalOption->reviewers));
                            foreach ($evalOption->reviewers as $reviewer) {
                                $teacherReviews[$categoryKey]->reviewers[$reviewer->id] = $reviewer;
                            }
                        }
                    }
                }
            }

            $templateProcessor->duplicateCol('kheader', $maxReviewers + 1); // +1 = column for average
            $templateProcessor->setValue('kheader', block_exastud_get_string('average'), 1);
            foreach ($teachersForColumns as $columnTeacher) {
                $templateProcessor->setValue('kheader', fullname($columnTeacher), 1);
            }

            foreach ($teacherReviews as $key => $review) {
                list($categoryId, $categorySource) = explode('_', $key);
                $templateProcessor->cloneRowToEnd('kriterium');
                $templateProcessor->setValue('kriterium', $review->title, 1);

                $globalAverage = (@$student_review->category_averages[$categorySource.'-'.$categoryId] ? $student_review->category_averages[$categorySource.'-'.$categoryId] : 0);
                $templateProcessor->setValue('kvalue', round($globalAverage, 2), 1);
                foreach ($teachersForColumns as $columnTeacher) {
                    $teacher_total = 0;
                    $teacher_cnt = 0;
                    foreach ($subjectsOfTeacher[$columnTeacher->id] as $subjectid) {
                        $cateReview = block_exastud_get_category_review_by_subject_and_teacher($class->periodid, $student->id, $categoryId, $categorySource, $columnTeacher->id, $subjectid);
                        if (@$cateReview->catreview_value) { // only reviewed subject/categories
                            $teacher_total += (@$cateReview->catreview_value ? $cateReview->catreview_value : 0);
                            $teacher_cnt++;
                        }
                    }

                    $teacher_average = $teacher_cnt > 0 ? round($teacher_total / $teacher_cnt, 2) : 0;
                    if ($teacher_average) {
                        $templateProcessor->setValue('kvalue', $teacher_average, 1);
                    } else {
                        $templateProcessor->setValue('kvalue', '', 1);
                    }
                }
            }
            $templateProcessor->deleteRow('kriterium');*/
            
            if (!$templateProcessor->addImageToReport(null, 'school_logo', 'exastud', 'block_exastud_schoollogo', 0, 1024, 768)) {
                $templateProcessor->setValue("school_logo", ''); // no logo files
            };
        } else if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_DEFAULT_REPORT_COMMON) {
            // "überfachliche Kompetenzen" part
            self::cross_competences_for_report($templateProcessor, $templateid, $class, $student, $class_subjects);
            $students = [$student];
            self::learn_sozial_for_report($templateProcessor, $templateid, $class, $students);
        }

		// projekt_ingroup property
		if (in_array($templateid, [
		        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT_HSA,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT
                ]
        )) {
		    // the teacher selects only one selectbox 'projekt_ingroup'. In the report we need a few:
            $projekt_ingroup = static::spacerIfEmpty(@$studentdata->projekt_ingroup);
            $data_dropdowns = array_merge($data_dropdowns, array('projekt_individ', 'projekt_reviewedfor', 'projekt_individended'));
            $data['projekt_individ'] = 'Wählen Sie ein Element aus.';
            $data['projekt_individended'] = 'Wählen Sie ein Element aus.';
            $data['projekt_reviewedfor'] = 'Wählen Sie ein Element aus.';
            switch ($projekt_ingroup) {
                case 'in der Gruppe':
                    $data['projekt_individ'] = 'in der Gruppe';
                    $data['projekt_individended'] = 'in eine Präsentation durch eine Schülergruppe.';
                    if ($templateid == BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT) {
                        $data['projekt_individended'] = 'in eine Präsentation durch die Schülergruppe.';
                    }
                    $data['projekt_reviewedfor'] = 'gemeinsamen';
                    break;
                case 'individuell':
                    $data['projekt_individ'] = 'individuell';
                    $data['projekt_individended'] = 'in eine Präsentation.';
                    $data['projekt_reviewedfor'] = 'individuellen';
                    break;
            }
        }

        // annotation property
        if (in_array($templateid, [
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA,
                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT_HSA,
                ]
        )) {
            // annotation: ${annotation_marker} only if ${annotation} is not empty
            $data_dropdowns = array_merge($data_dropdowns, array('annotation_marker', 'annotation'));
            $data['annotation_marker'] = ' ';
            $data['annotation'] = ' ';
            $annotation = trim(static::spacerIfEmpty(@$studentdata->annotation));
            if ($annotation != '' && $annotation != '---') {
                $data['annotation_marker'] = 'Anmerkung:';
                $data['annotation'] = trim($annotation);
            }
        }

        // leiter (director) and Vorsitzende (chair)
        // almost all templates have this selectbox
        $data_dropdowns = array_merge($data_dropdowns, array('leiter', 'chair', 'gruppen_leiter', 'klass_leiter'));
        $data['leiter'] = block_exastud_leiter_titles_by_gender('school', @block_exastud_get_class_data($class->id)->schoollieder_gender);
        $data['leiter_name'] = (@block_exastud_get_class_data($class->id)->schoollieder_name ? block_exastud_get_class_data($class->id)->schoollieder_name : ' ');
        $data['chair'] = block_exastud_leiter_titles_by_gender('chair', @block_exastud_get_class_data($class->id)->auditleader_gender, 'femail', $templateid);
        if (in_array($templateid, [
            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS,
            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2,
            BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA,
        ])){
            $data['chair'] = block_exastud_leiter_titles_by_gender('audit', @block_exastud_get_class_data($class->id)->auditleader_gender);
        }
        $data['chair_name'] = (@block_exastud_get_class_data($class->id)->auditleader_name ? block_exastud_get_class_data($class->id)->auditleader_name : ' ');
        $data['gruppen_leiter'] = block_exastud_leiter_titles_by_gender('group', @block_exastud_get_class_data($class->id)->groupleader_gender);
        $data['gruppen_leiter_name'] = (@block_exastud_get_class_data($class->id)->groupleader_name ? block_exastud_get_class_data($class->id)->groupleader_name : ' ');
        $data['klass_leiter'] = block_exastud_leiter_titles_by_gender('class', @block_exastud_get_class_data($class->id)->classleader_gender);
        $data['klass_leiter_name'] = (@block_exastud_get_class_data($class->id)->classleader_name ? block_exastud_get_class_data($class->id)->classleader_name : ' ');
        // aotu class leader data
        /*$classteacher = block_exastud_get_user($class->userid);
        $classteachergender = block_exastud_get_user_gender($classteacher->id);
        $data['klass_leiter'] = block_exastud_leiter_titles_by_gender('class', $classteachergender);
        $data['klass_leiter_name'] = fullname($classteacher);*/

        // some templates has selectbox/niveau for languages. Make it works:
        // only lang subjects
        $langSubjects = [
                'D' => 'deu',
                'E' => 'eng',
                'F' => 'fra',
                'S' => 'spa'];
        // default are empty:
        foreach ($langSubjects as $lkey => $lindex) {
            $data_dropdowns[] = $lindex.'_graded';
            $data[$lindex.'_graded'] = '/--set-empty--/';
            $data[$lindex.'_niveau'] = '';
        }
        // get from language reviews
        /*foreach ($class_subjects as $subject) {
            if (in_array($subject->shorttitle, array_keys($langSubjects)) && !$subject->no_niveau) {
                $subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);
                if (!$subjectData || (!$subjectData->review && !$subjectData->grade && !$subjectData->niveau)) {
                    continue;
                }
                $data[$langSubjects[$subject->shorttitle].'_graded'] = $subject->title.':';
                $niveau = \block_exastud\global_config::get_niveau_option_title(@$subjectData->niveau) ?: @$subjectData->niveau;
                if (strlen($niveau) <= 1) {
                    $data[$langSubjects[$subject->shorttitle].'_niveau'] = 'Niveau '.static::spacerIfEmpty($niveau);
                }
            }
        }*/
        // get directly from review in "Other report fields"
        foreach ($langSubjects as $lkey => $lindex) {
            if (@$studentdata->{$lindex.'_niveau'}) {
                $subject = block_exastud_get_subject_by_shorttitle($lkey, $class->bpid);
                $data[$lindex.'_niveau'] = @$studentdata->{$lindex.'_niveau'};
                $data[$langSubjects[$subject->shorttitle].'_graded'] = $subject->title.':';
            }
        }

		// TODO: how we can check template generation?
		/*else {
			echo g::$OUTPUT->header();
			echo block_exastud_trans([
				'de:Leider wurde die Dokumentvorlage "{$a}" nicht gefunden.',
				'en:Something wrong with Template "{$a}" processing.',
			], $templateid);
			echo g::$OUTPUT->footer();
			exit();
		}*/

		// compare $data with values in selectboxes. If it is empty (---) - delete <w:sdtContent>, if not - delete option
        if ($template) {
            $inputs = print_templates::get_template_inputs($templateid, 'all');
        }

        foreach ($data as $dKey => $dItem) {
            $select_text = '';
            // it is selectbox
            if (in_array($dKey, $data_dropdowns) ||
                    (is_array($inputs) && array_key_exists($dKey, $inputs) && $inputs[$dKey]['type'] == 'select')) {
                // replace default marker
                if (in_array($dItem, ['---', '/--empty--/', '/--set-empty--/'])) {
                    if (in_array($dKey, ['profilfach_titel', 'wahlfach_titel'])) {
                        if (in_array($templateid, [
                                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA,
                                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS,
                                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_RS,
                                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU,
                                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU,
                            ])) {
                            continue; // bug with small font
                        }
                        $select_text = '---';
                    } else {
                        $select_text = 'Wählen Sie ein Element aus.';
                    }
                    if ($dItem == '/--set-empty--/') {
                        $select_text = '';
                    }

                    // delete stdContent
                    $add_filter(function($content) use ($dKey, $templateid, $select_text) {
//                        $ret = '';
                        //try {
                            //$ret = preg_replace('!(<w:sdtContent>.*)\${'.$dKey.'}(.*<\/w:sdtContent>)!Us', '${1}Wählen Sie ein Element aus.${2}', $content, -1, $count);
                            // add custom style to Placeholder
                            $ret = preg_replace('~(<w:sdtContent>[^\/]*)(<w:rPr>)((?:(?!<w:sdtContent>).)*)(<\/w:rPr>[^\/]*)\${'.$dKey.'}(.*<\/w:sdtContent>)~Us',
                                    '${1}${2}
                                                <w:color w:val="555555" />
										        <w:sz w:val="12" />
										        ${4}'.$select_text.'${5}', $content, -1, $count);
                            if (!$count) { // another dropdown
                                /*$ret = preg_replace('~(<w:sdtContent>[^\/]*)(<w:pPr>.*<\/w:pPr>)(.*)(<w:rPr>)((?:(?!<w:sdtContent>).)*)(<\/w:rPr>[^\/]*)\${'.$dKey.'}(.*<\/w:sdtContent>)~Us',
                                                '${1}${2}${3}${4}
                                                <w:color w:val="555555" />
										        <w:sz w:val="14" />
										        ${6}'.$select_text.'${7}', $content, -1, $count);*/
                                // something wrong with next reports. use anoher sttyle for them
                                $styleFor = '<w:color w:val="555555" />
										        <w:sz w:val="14" />';
                                if ($dKey == 'profilfach_titel' /*&& in_array($templateid, [
                                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11,
                                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11,
                                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11,
                                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11,
                                ])*/)  {
                                    $styleFor = '<w:rStyle w:val="Formatvorlage108"/>';
                                } /*elseif ($dKey = 'wahlfach_titel' && in_array($templateid, [
                                        BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA
                                    ])) {
                                    $styleFor = '<w:color w:val="555555" />
										        <w:sz w:val="20" />';
                                }*/
                                // next trying: the sdtContent always after dropdown items?
                                $ret = preg_replace('~(\${'.$dKey.'}.*)(<w:sdtContent>.*)(<w:pPr>.*<\/w:pPr>)(.*)(<w:rPr>)(.*)(<\/w:rPr>[^\/]*)\${'.$dKey.'}(.*<\/w:sdtContent>)~Us',
                                                '${1}${2}${3}${4}${5}'.
                                                $styleFor.
                                                '${7}'.$select_text.'${8}', $content, -1, $count);
                            }
                            if (!$count) { // another dropdown
                                $ret = preg_replace('~(<w:sdtContent>[^\/]*)\${'.$dKey.'}(.*<\/w:sdtContent>)~Us',
                                                '${1}<w:rPr>
                                                <w:color w:val="555555" />
										        <w:sz w:val="14" /></w:rPr>${2}
										        '.$select_text.'${3}', $content, -1, $count);
                            }
                            /*$ret = preg_replace('!(<w:sdtContent>[^\/]*)(<w:rPr>)(.*)(<\/w:rPr>[^\/]*)\${class}(.*<\/w:sdtContent>)!Us',
                                                '${1}${2}
                                                    <w:color w:val="555555" />
                                                    <w:sz w:val="12" />
                                                    ${4}Wählen Sie ein Element aus.${5}', $content, -1, $count);*/
                            //$ret = preg_replace('!<w:sdtContent>.*\${'.$dKey.'}.*<\/w:sdtContent>!Us', '', $content, -1, $count);
                        //} catch (\Exception $e) {
                        //    throw new \Exception('"asdasdasd" not found');
                        //}
                        if (!$count) {
                            return $content;
                            throw new \Exception('"'.$dKey.'" in template "'.$templateid.'" not found');
                        }
                        return $ret;
                    });
                }
                // delete option with key marker
                $add_filter(function($content) use ($dKey) {
                    $ret = preg_replace('!<w:listItem[^>]*w:value="\${'.$dKey.'}".*\/>!Us', '', $content, -1, $count);
                    return $ret;
                });
            }
        }

        // some reports has '*' or words in the dropdownlists. We need to find all of them
        switch ($templateid) {
            //case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_HS:
            //case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_RS:
                //if (mb_stripos($data['wahlfach_titel'], 'französisch') !== false) {
                //    $data['wahlfach_titel'] .= '*';
                //}
                //if (mb_stripos($data['profilfach_titel'], 'spanisch') !== false) {
                //    $data['profilfach_titel'] .= '*';
                //}
                break;
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL10_RSA:
                //if (mb_stripos($data['wahlfach_titel'], 'technik') !== false
                //        || mb_stripos($data['wahlfach_titel'], 'Alltagskultur, Ernährung, Soziales') !== false
                //) {
                //    $data['wahlfach_titel'] .= '**';
                //}
                //if (mb_stripos($data['wahlfach_titel'], 'französisch') !== false) {
                //    $data['wahlfach_titel'] .= '*/**';
                //}
                //if (mb_stripos($data['profilfach_titel'], 'spanisch') !== false) {
                //    $data['profilfach_titel'] .= '*';
                //}
                if (trim($data['profilfach_titel']) != '') {
                    $data['profilfach_titel'] = 'Profilfach '.trim($data['profilfach_titel']);
                    $data['profilfach_titel'] .= '*';
                }
                break;
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA:
                if (trim($data['profilfach_titel']) != '') {
                    $data['profilfach_titel'] = 'Profilfach '.trim($data['profilfach_titel']);
                    $data['profilfach_titel'] .= '*';
                }
                break;
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_KL9_10_HSA_2:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11:
                //if (mb_stripos($data['wahlfach_titel'], 'technik') !== false
                //        || mb_stripos($data['wahlfach_titel'], 'Alltagskultur, Ernährung, Soziales') !== false
                //) {
                //    $data['wahlfach_titel'] .= '**';
                //}
                //if (mb_stripos($data['wahlfach_titel'], 'französisch') !== false) {
                //    $data['wahlfach_titel'] .= '*/**';
                //}
                //if (mb_stripos($data['profilfach_titel'], 'spanisch') !== false) {
                //    $data['profilfach_titel'] .= '*';
                //}
                if (trim($data['profilfach_titel']) != '') {
                    $data['profilfach_titel'] = 'Profilfach '.trim($data['profilfach_titel']);
                }
                break;
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_SCHULPFLICHT:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_SCHULPFLICHT:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABGANGSZEUGNIS_NICHT_BEST_HSA:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_ABSCHLUSSZEUGNIS_FOE:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL10_E_NIVEAU:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHR_ZEUGNIS_FOE:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRZEUGNIS_RS:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHRESINFORMATION_KL11:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_HALBJAHRESINFORMATION_KL11:
                if (trim($data['profilfach_titel']) != '') {
                    $data['profilfach_titel'] = 'Profilfach '.trim($data['profilfach_titel']);
                }
                break;
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_RSA:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_GLEICHWERTIGER_BILDUNGSABSCHLUSS_HSA:
                if (trim($data['profilfach_titel']) != '') {
                    $data['profilfach_titel'] = 'Profilfach '.trim($data['profilfach_titel']);
                } else {
                    $data['profilfach_titel'] = '---';
                }
                break;
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_KL9_10_HSA:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_RS:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABSCHLUSSZEUGNIS_RS:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU:
                // for bug with small '---' in result document
                if (trim($data['wahlfach_titel']) == '') {
                    $data['wahlfach_titel'] = '---';
                }
                if (trim($data['profilfach_titel']) == '') {
                    $data['profilfach_titel'] = '---';
                }
                break;
        }

        // fill selectboxes by needed values
        switch ($templateid) {
            // student_transfered
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL10_E_NIVEAU:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_JAHRESZEUGNIS_KL11:
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_JAHRESZEUGNIS_KL11:
                if (array_key_exists('student_transfered', $inputs)) {
                    $newItems = [
                            '' => 'Wählen Sie ein Element aus.',
                    ];
                    $gender = block_exastud_get_user_gender($student->id);
                    $values = $inputs['student_transfered']['values'];
                    switch ($gender) {
                        case 'male':
                            $values = array_slice($values, 2); // delete first TWO values from selectbox
                            break;
                        case 'female':
                            $values = array_slice($values, 0, 2); // use only first TWO values from selectbox
                            break;
                    }
                    $newItems = array_merge($newItems, $values);
                    $templateProcessor->fillSelectbox('student_transfered', $newItems);
                }
                break;
        }
        
        // go throw inputs 
        foreach ($inputs as $key => $input) {
            switch ($input['type']) {
                case 'userdata':
                    // user's data markers
                    if ($input['userdatakey']) {
                        $data[$key] = block_exastud_get_report_userdata_value($templateProcessor, $key, $student->id, $input['userdatakey']);
                    }
                    break;
                case 'matrix':
                    // matrix type
                    foreach ($input['matrixrows'] as $rindex => $row) {
                        foreach ($input['matrixcols'] as $cindex => $col) {
                            $key_index = $key.'_'.($rindex + 1).'_'.($cindex + 1);
                            $stdataarr = $studentdata->$key;
                            switch ($input['matrixtype']) {
                                case 'radio':
                                    if (@$stdataarr[$row] == $col) {
                                        $data[$key_index] = 'X';
                                    } else {
                                        $data[$key_index] = '';
                                    }
                                    break;
                                case 'checkbox':
                                    if (@$stdataarr[$row][$col]) {
                                        $data[$key_index] = 'X';
                                    } else {
                                        $data[$key_index] = '';
                                    }
                                    break;
                                case 'text':
                                    if (@$stdataarr[$row][$col]) {
                                        $data[$key_index] = $stdataarr[$row][$col];
                                    } else {
                                        $data[$key_index] = '';
                                    }
                                    break;
                            }
                        }                         
                    }
                    break;
            }
        }
        
        // add school logo
        $data['school_logo'] = '';
        if (!$templateProcessor->addImageToReport(null, 'school_logo', 'exastud', 'block_exastud_schoollogo', 0, 100, 100)) {
            $data['school_logo'] = ''; // no logo files
        };

        // crop by input limits: TODO: check!!!!
        foreach ($data as $d => $value) {
            $data[$d] = block_exastud_crop_value_by_template_input_setting($value, $templateid, $d);
        }

        // grouped competences (categories)
        if ($templateProcessor->blockExists('grouped_competences')) {
            $competenceTree = block_exastud_competence_tree($class->id);
            // generate correct template table (columns)
            $evalopts = g::$DB->get_records('block_exastudevalopt', null, 'sorting', 'id, title, sourceinfo');
            //$categories = block_exastud_get_class_categories_for_report($student->id, $class->id);
            //$subjects = static::get_exacomp_subjects($student->id);
            $templateProcessor->duplicateCol('kheader', count($evalopts));
            foreach ($evalopts as $evalopt) {
                $templateProcessor->setValue('kheader', $evalopt->title, 1);
            }

            $classteachers = array();
            $subjectsOfTeacher = array();
            $teachers = array_filter(block_exastud_get_class_subject_teachers($class->id), function($o) use (&$classteachers, &$subjectsOfTeacher) {
                if (!in_array($o->id, $classteachers)) {
                    $classteachers[] = $o->id;
                }
                if ($o->subjectid > 0) {
                    $subjectsOfTeacher[$o->id][] = $o->subjectid;
                }
                return null;
            });
            $classteachers = array_map(function($o) {return block_exastud_get_user($o);}, $classteachers);
            // clone tables for every group
            $templateProcessor->exastud_cloneBlock('grouped_competences', count($competenceTree), true);
            foreach ($competenceTree as $parentId => $group) {
                if (count($group['children'])) {
                    $templateProcessor->setValue('group_title', $group['title'], 1);
                    foreach ($group['children'] as $category) {
                        // get average value
                        //$category_cnt = 0;
                        //$category_total = 0;
                        //foreach ($classteachers as $teacher) {
                        //    foreach ($subjectsOfTeacher[$teacher->id] as $subjectId) {
                                //$cateReview = block_exastud_get_category_review_by_subject_and_teacher($class->periodid, $student->id, $category->id, $category->source, $teacher->id, $subjectId);
                                //$cateReview = block_exastud_get_category_review_by_subject_and_teacher($class->periodid, $student->id, $category->id, $category->source, $teacher->id, 0);
                                $cateReview = block_exastud_get_average_evaluation_by_category($class->id, $class->periodid, $student->id, $category->id, $category->source, true);
                                //if (@$cateReview->catreview_value) {
                                //    $category_total += (@$cateReview->catreview_value ? $cateReview->catreview_value : 0);
                                //    $category_cnt++;
                                //}
                            //}
                        //}
                        //$catAverage = $category_cnt > 0 ? round($category_total / $category_cnt, 2) : 0;
                        if (array_key_exists('average', (array)$cateReview)) {
                            $catAverage = $cateReview->average;
                        } else {
                            $catAverage = null;
                        }

                        $templateProcessor->cloneRowToEnd('ktitle');
                        $templateProcessor->setValue('ktitle', $category->title, 1);
                        for ($i = 0; $i < count($evalopts); $i++) {
                            $templateProcessor->setValue('kvalue', $catAverage !== null && round($catAverage) == ($i + 1) ? 'X' : '', 1);
                        }
                    }
                    $templateProcessor->deleteRow('ktitle');
                }
            }
        }
        
        // learn and social behaviour in reports can have different settings for BW and non-BW.  
        // non-BW has class-setting, BW has report-settings
        //if (!block_exastud_is_bw_active() && block_exastud_can_edit_learnsocial_classteacher($class->id)) {
        //    $data['learn_social_behavior'] = $studentData->learn_social_behavior;
        //}

//exit;
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
		/*if (in_array($templateid, [
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_ABGANGSZEUGNIS_FOE,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_HALBJAHR_ZEUGNIS_FOE,
        ])) {
			$filename = ($certificate_issue_date_text ? preg_replace('/[\\/]/', '-', $certificate_issue_date_text) : date('Y-m-d'))."-".$template->get_name()."-{$class->title}-{$student->lastname}-{$student->firstname}.dotx";
		} else {*/
			$filename = ($certificate_issue_date_text ? preg_replace('/[\\/]/', '-', $certificate_issue_date_text) : date('Y-m-d'))."-".(@$tempTemplateName ? $tempTemplateName : $template->get_name())/*."-{$class->title}"*/."-{$student->lastname}-{$student->firstname}.docx";
		//}

        $filename = block_exastud_normalize_filename($filename);

		return (object)[
			'temp_file' => $temp_file,
			'filename' => $filename,
		];
	}

	static function cross_competences_for_report(&$templateProcessor, $templateid, $class, $student, $class_subjects) {
        $categories = block_exastud_get_class_categories_for_report($student->id, $class->id);
        $student_review = block_exastud_get_report($student->id, $class->periodid, $class->id);
        // get max columns count
        $maxColumns = 0;
        switch (block_exastud_get_competence_eval_type()) {
            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                $maxColumns = max($maxColumns, count($class_subjects));
                break;
            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                foreach ($categories as $category) {
                    $maxColumns = max($maxColumns, count($category->evaluationOptions));
                }
                break;
        }

        // header of table
        $templateProcessor->duplicateCol('kheader', $maxColumns + 1); // +1 = column for average
        $templateProcessor->setValue('kheader', block_exastud_get_string('average'), 1);
        switch (block_exastud_get_competence_eval_type()) {
            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                foreach ($class_subjects as $subject) {
                    $templateProcessor->setValue('kheader', $subject->title, 1);
                }
                break;
            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                $category = reset($categories);
                foreach ($category->evaluationOptions as $option) {
                    $templateProcessor->setValue('kheader', $option->title, 1);
                }
                break;

        }

        foreach ($categories as $category) {
            $templateProcessor->cloneRowToEnd('kriterium');
            $templateProcessor->setValue('kriterium', $category->title, 1);

            $globalAverage = (@$student_review->category_averages[$category->source.'-'.$category->id] ? $student_review->category_averages[$category->source.'-'.$category->id] : 0);
            $templateProcessor->setValue('kvalue', round($globalAverage, 2), 1);
            switch (block_exastud_get_competence_eval_type()) {
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                    foreach ($class_subjects as $subject) {
                        $templateProcessor->setValue('kvalue', $category->evaluationAverages[$subject->id]->value, 1);
                    }
                    break;
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                    foreach ($category->evaluationOptions as $pos_value => $option) {
                        $cellOutput = join(', ', array_map(function($reviewer) {
                            return $reviewer->subject_shorttitle ?: fullname($reviewer);
                        }, $option->reviewers));
                        $templateProcessor->setValue('kvalue', $cellOutput, 1);
                    }
                    break;

            }
        }
        $templateProcessor->deleteRow('kriterium');
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
		$templateProcessor->setValue('klasse', (trim($class->title_forreport) ? $class->title_forreport : $class->title));
		$templateProcessor->setValue('lehrer', fullname(g::$USER));
		$templateProcessor->setValue('datum', date('d.m.Y'));

		//$class_subjects = block_exastud_get_bildungsplan_subjects($class->bpid);
		$class_subjects = block_exastud_get_class_subjects($class);


		// split normal and grouped subjects (page 2)
		$normal_subjects = [];
        // add Verhalten and Mitarbeit to $normal_subjects if selected report has them
        // at least one selected student
        $verhaltenExists = false;
        $verhaltens = array();
        $mitarbeitExists = false;
        $mitarbeits = array();
        foreach ($students as $student) {
            $inputs = array_keys(block_exastud_get_student_print_template($class, $student->id)->get_inputs(BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO));
            $studentData = block_exastud_get_class_student_data($class->id, $student->id);
            if (in_array('verhalten', $inputs)) {
                $verhaltenExists = true;
                $verhaltens[$student->id] = @$studentData->verhalten;
            }
            if (in_array('mitarbeit', $inputs)) {
                $mitarbeitExists = true;
                $mitarbeits[$student->id] = @$studentData->mitarbeit;
            }
        }

		$grouped_subjects = [];
		foreach ($class_subjects as $subject) {
			if (preg_match('!religi|ethi!i', $subject->title)) {
                $subject->shorttitle_stripped = $subject->shorttitle;
				@$grouped_subjects['Religion / Ethik'][] = $subject;
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

        // school logo: ${school_logo}
        if (!$templateProcessor->addImageToReport(null, 'school_logo', 'exastud', 'block_exastud_schoollogo', 0, 1024, 768)) {
            $templateProcessor->setValue("school_logo", ''); // no logo files
        };
        //class logo: ${class_logo}
        //if (!$templateProcessor->addImageToReport('class_logo', 'block_exastud', 'class_logo', $class->id, 1024, 768)) {
        //    $templateProcessor->setValue("class_logo", ''); // no logo files
        //};


        // page 1
        $columnsCount = count($normal_subjects);
        if ($verhaltenExists) {
            $columnsCount++;
        }
        if ($mitarbeitExists) {
            $columnsCount++;
        }
        $columnStart = 0;
        $templateProcessor->duplicateCell('s', $columnsCount - 1);
        $templateProcessor->duplicateCell('g', $columnsCount - 1);
        // some incorrect result. Why?
        //$templateProcessor->duplicateCol('s', $columnsCount - 1);

        $columnsCount = count($grouped_subjects);
        $columnStart = 0;
        $templateProcessor->duplicateCell('gs', $columnsCount - 1);
        $templateProcessor->duplicateCell('gst', ($columnsCount - 1) * 2);

        // for average column
        //$templateProcessor->duplicateCell('gs', 1);
        //$templateProcessor->duplicateCell('gst', 2);
        /*for ($k = 1; $k <= $columnsCount -1; $k++) {
            $templateProcessor->duplicateCell('gsg', 1);
            $templateProcessor->duplicateCell('gss', 1);
        }*/
        foreach ($grouped_subjects as $key => $subjects) {
            $shorttitle = trim($key);
            $shorttitle = preg_replace('/(^\s+|\s+$|\s+)/', mb_convert_encoding('&#160;', 'UTF-8', 'HTML-ENTITIES'), $shorttitle); // insert &nbsp to table header
            $templateProcessor->setValue("gs", $shorttitle, 1);
        }
        $templateProcessor->setValue("avg", 'Notendurchschnitt');
        $templateProcessor->setValue("gs", '');

        // Change orientation if count of columns > 10
        /*if (count($normal_subjects) > 10) {
            $templateProcessor->changeOrientation('L');
        }*/ // now is always L
        if ($verhaltenExists) {
            $templateProcessor->setValue("s", 'Verhalten', 1);
        }
        if ($mitarbeitExists) {
            $templateProcessor->setValue("s", 'Mitarbeit', 1);
        }

		foreach ($normal_subjects as $subject) {
            $shorttitle = trim($subject->shorttitle);
            $shorttitle = preg_replace('/(^\s+|\s+$|\s+)/', mb_convert_encoding('&#160;', 'UTF-8', 'HTML-ENTITIES'), $shorttitle); // insert &nbsp to table header
			$templateProcessor->setValue("s", $shorttitle, 1);
		}
		$templateProcessor->setValue("s", '');

		$templateProcessor->cloneRow('student', count($students));
		$rowi = 0;
		foreach ($students as $student) {
			$rowi++;
			$templateProcessor->setValue("student#$rowi", $rowi.'. '.fullname($student));
            $subjectsToAverage = array();
            // verhalten und mitarbeit
            if ($verhaltenExists) {
                if (array_key_exists($student->id, $verhaltens)) {
                    $templateProcessor->setValue("g#$rowi", $verhaltens[$student->id], 1);
                } else {
                    $templateProcessor->setValue("g#$rowi", '', 1);
                }
            }
            if ($mitarbeitExists) {
                if (array_key_exists($student->id, $mitarbeits)) {
                    $templateProcessor->setValue("g#$rowi", $mitarbeits[$student->id], 1);
                } else {
                    $templateProcessor->setValue("g#$rowi", '', 1);
                }
            }
			// normal subjects
			foreach ($normal_subjects as $subject) {
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);
                $value = '';
				if ($subjectData) {
				    if (isset($subjectData->niveau)) {
				        $value .= $subjectData->niveau.' ';
                    }
                    if (isset($subjectData->grade)) {
                        $subjectsToAverage[$subject->id] = $subjectData->grade;
                        $value .= $subjectData->grade;
                    }
                }
				$templateProcessor->setValue("g#$rowi", $value, 1);
			}
			$templateProcessor->setValue("g#$rowi", '');

			// grouped subjects
            $toDoc = array();
            foreach ($grouped_subjects as $groupkey => $subjects) {
                if (!array_key_exists($groupkey, $toDoc)) {
                    $toDoc[$groupkey] = array();
                }
                foreach ($subjects as $subject) {
                    $subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);

                    if (!$subjectData || (!$subjectData->review && !$subjectData->grade && !$subjectData->niveau)) {
                        continue;
                    }
                    $value = '';
                    if (isset($subjectData->niveau)) {
                        $value .= $subjectData->niveau.' ';
                    }
                    if (isset($subjectData->grade)) {
                        $subjectsToAverage[$subject->id] = $subjectData->grade;
                        $value .= $subjectData->grade;
                    }

                    switch ($groupkey) {
                        case 'Religion / Ethik': // for religin some another rule
                            if (!empty($toDoc[$groupkey]['shorttitle']) && $toDoc[$groupkey]['shorttitle'] != 'eth') {
                                continue;
                            } else {
                                $toDoc[$groupkey]['shorttitle'] = ($value ? $subject->shorttitle_stripped : '');
                                $toDoc[$groupkey]['value'] = ($value ? $value : '');
                            }
                            break;
                        default:
                            if (empty($toDoc[$groupkey]['shorttitle'])) {
                                $toDoc[$groupkey]['shorttitle'] = ($value ? $subject->shorttitle_stripped : '');
                                $toDoc[$groupkey]['value'] = ($value ? $value : '');
                            }
                    }

                }
            }

            foreach ($grouped_subjects as $groupkey => $subjects) {
                $subjectShorttitle = (@$toDoc[$groupkey]['shorttitle'] ? $toDoc[$groupkey]['shorttitle'] : '');
                $value = (@$toDoc[$groupkey]['value'] ? $toDoc[$groupkey]['value'] : '');
                $templateProcessor->setValue("gst#$rowi", $value, 1);
                $templateProcessor->setValue("gst#$rowi", $value ? $subjectShorttitle : '', 1);
            }
            // add average values
            $templateid = block_exastud_get_student_print_template($class, $student->id)->get_template_id();
            $avg = block_exastud_get_grade_average_value($subjectsToAverage, false, $templateid, $class->id, $student->id);
            //$avgVerbal = block_exastud_get_grade_average_value($subjectsToAverage, true);
            $templateProcessor->setValue("gavg#$rowi", $avg, 1);
            //$templateProcessor->setValue("gst#$rowi", $avgVerbal ? $avgVerbal : '', 1);

            $templateProcessor->setValue("gsg#$rowi", '');
            $templateProcessor->setValue("gss#$rowi", '');

		}

		// page 2

/*		$templateProcessor->cloneRow('gsstudent', count($students));
		$rowi = 0;
		foreach ($students as $student) {
			$rowi++;
			$templateProcessor->setValue("gsstudent#$rowi", $rowi.'. '.fullname($student));

			foreach ($grouped_subjects as $subjects) {
				$subjectData = null;

				foreach ($subjects as $subject) {
					$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);

					if ($subjectData && isset($subjectData->grade) && $subjectData->grade) {
						break;
					}
				}
                $value = '';
                if ($subjectData) {
                    if (isset($subjectData->niveau)) {
                        $value .= $subjectData->niveau.' ';
                    }
                    if (isset($subjectData->grade)) {
                        $value .= $subjectData->grade;
                    }
                }
				$templateProcessor->setValue("gsg#$rowi", $value, 1);
				$templateProcessor->setValue("gss#$rowi", $value ? $subject->shorttitle_stripped : '', 1); // TODO: ???
			}

			$templateProcessor->setValue("gsg#$rowi", '');
			$templateProcessor->setValue("gss#$rowi", '');
		}*/

		// projekt
		$templateProcessor->cloneRow('prostudent', count($students));
		$rowi = 0;
        $nodata = true;
		foreach ($students as $student) {
			$studentData = block_exastud_get_class_student_data($class->id, $student->id);
			if(@$studentData->projekt_grade || @$studentData->projekt_thema) {
                $rowi++;
                $nodata = false;
                $templateProcessor->setValue("prostudent#$rowi", $rowi . '. ' . fullname($student));
                $templateProcessor->setValue("prog#$rowi", @$studentData->projekt_grade);
                $templateProcessor->setValue("prodescription#$rowi", @$studentData->projekt_thema);
            }
		}
		if ($nodata) {
            $templateProcessor->replaceBlock('projects', '');
        } else {
            $templateProcessor->cloneBlock('projects', 1, true);
        }

		// ags
		$templateProcessor->cloneRow('agstudent', count($students));
		$rowi = 0;
        $nodata = true;
		foreach ($students as $student) {
			$studentData = block_exastud_get_class_student_data($class->id, $student->id);
			if (@$studentData->ags) {
                $rowi++;
                $nodata = false;
                $templateProcessor->setValue("agstudent#$rowi", $rowi . '. ' . fullname($student));
                // crop ags by limits. Limits are got from class template
                $template = block_exastud_get_student_print_template($class, $student->id);
                $templateid = $template->get_template_id();
                $ags = block_exastud_cropStringByInputLimitsFromTemplate(@$studentData->ags, $templateid, 'ags');
                $templateProcessor->setValue("agdescription#$rowi", $ags);
            }
		}
        if ($nodata) {
            $templateProcessor->replaceBlock('agss', '');
        } else {
            $templateProcessor->cloneBlock('agss', 1, true);
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

		$filename = date('Y-m-d')."-".'Notenuebersicht'."-".(trim($class->title_forreport) ? $class->title_forreport : $class->title).".docx";
        $filename = block_exastud_normalize_filename($filename);

        return (object)[
                'temp_file' => $temp_file,
                'filename' => $filename,
        ];

		//require_once $CFG->dirroot.'/lib/filelib.php';
		//send_temp_file($temp_file, $filename);
	}

	static function lern_und_social_report($templateid, $class, $students) {
		global $CFG;

		//$templateid = BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_LERN_UND_SOZIALVERHALTEN;
        $templateFile = \block_exastud\print_templates::get_template_file($templateid);

		if (!file_exists($templateFile)) {
			throw new \Exception("template '$templateid' not found");
		}


		\PhpOffice\PhpWord\Settings::setTempDir($CFG->tempdir);
		$templateProcessor = new TemplateProcessor($templateFile);

		$period = block_exastud_get_period($class->periodid);

		$templateProcessor->setValue('schule', get_config('exastud', 'school_name'));
		$templateProcessor->setValue('schule_nametype', get_config('exastud', 'school_name').' '.get_config('exastud', 'school_type'));
		$templateProcessor->setValue('ort', get_config('exastud', 'school_location'));
		$templateProcessor->setValue('periode', $period->description);
        $templateProcessor->setValue('year', block_exastud_get_year_for_report($class));
		$templateProcessor->setValue('klasse', (trim($class->title_forreport) ? $class->title_forreport : $class->title));
		$classteacher = block_exastud_get_user($class->userid);
		$templateProcessor->setValue('lehrer', fullname($classteacher));
		$templateProcessor->setValue('datum', date('d.m.Y'));

		// school logo: ${school_logo}
        if (!$templateProcessor->addImageToReport(null, 'school_logo', 'exastud', 'block_exastud_schoollogo', 0, 1024, 768)) {
            $templateProcessor->setValue("school_logo", ''); // no logo files
        };

        //$class_subjects = block_exastud_get_class_subjects($class);

        if (!self::learn_sozial_for_report($templateProcessor, $templateid, $class, $students)) {
            return null;
        };

        /*// prepare reviews
		$class_reviews = array();
		foreach ($students as $student) {
            $studentData = block_exastud_get_class_student_data($class->id, $student->id);
            // class teacher review
			if ($studentData->head_teacher) {
                $headTeacher = $studentData->head_teacher;
            } else {
			    $headTeacher = $class->userid; // class owner as head teacher
            }
            $headTeacherObject = block_exastud_get_user($headTeacher);
			$headLernReview = $studentData->learning_and_social_behavior;
			// get reviews from teachers
            if (block_exastud_is_bw_active()) {
                // get from main userdata
                if (@$studentData->learning_and_social_behavior) {
                    $reviews = ['head' => (object)[
                            'userid' => $headTeacher,
                            'teacher' => $headTeacherObject,
                            'subject_title' => block_exastud_trans('de:Zuständiger Klassenlehrer'),
                            'title' => block_exastud_trans('de:Zuständiger Klassenlehrer'), // subject title
                            'review' => $headLernReview
                    ]];
                } else {
                    $reviews = array();
                }
            } else {
                // get from report configurable
                if (@$studentData->learn_social_behavior || @($studentData->learning_and_social_behavior)) {
                    $reviews = ['head' => (object)[
                            'userid' => $headTeacher,
                            'teacher' => $headTeacherObject,
                            'subject_title' => block_exastud_trans('de:Klassenlehrer'),
                            'title' => block_exastud_trans('de:Klassenlehrer'),
                            'review' => $studentData->learn_social_behavior ? $studentData->learn_social_behavior : $studentData->learning_and_social_behavior
                    ]];
                } else {
                    $reviews = array();
                }
            }

            // 2. subject by subject
             $zu="
                ";//linebreak in word
            foreach (block_exastud_get_class_subjects($class) as $class_subject) {
                $steachers = block_exastud_get_class_teachers_by_subject($class->id, $class_subject->id);
             		$tempreview="";
                foreach ($steachers as  $steacher){
	                $class_subject->review = g::$DB->get_field('block_exastudreview', 'review', [
	                        'studentid' => $student->id,
	                        'subjectid' => $class_subject->id,
	                        'periodid' => $class->periodid,
	                        'teacherid' => $steacher->id,
	                ]);
	                if ($class_subject->review) {
	                    $tempreview .= "***".fullname($steacher)."***:<br>".$class_subject->review."<br><br>";	                  
	                }
	              }
	             
	                if ($tempreview!="") {
	                	$tempreview=preg_replace('/^<br><br>/','',$tempreview);
	                	$class_subject->review=str_replace ("<br>",$zu,$tempreview);
	                  $reviews[$class_subject->id] = $class_subject;
	                }
	                
	              
            }

            if (count($reviews)) {
                $class_reviews[$student->id] = $reviews;
            }
		}
        if (!count($class_reviews)) {
            // no any review = no any report
            return null;
        }

        $templateProcessor->cloneBlock('studentblock', count($class_reviews), true);

        $s = 0;
        foreach ($class_reviews as $review_studentid => $student_reviews) {
            $s++;
            $student = block_exastud_get_user($review_studentid);
            $templateProcessor->setValue("student_number", $s, 1);
            $templateProcessor->setValue("student_name", fullname($student), 1);
            $templateProcessor->setValue("birthday", block_exastud_get_date_of_birth($review_studentid), 1);

            $templateProcessor->cloneRow('teacher', count($student_reviews));
            $rowi = 0;
            foreach ($student_reviews as $student_review) {
                $rowi++;
                //$teacher = block_exastud_get_user($student_review->userid);
                $templateProcessor->setValue("teacher#$rowi", fullname($student_review->teacher ), 1);
                $templateProcessor->setValue("subject#$rowi", $student_review->title, 1);
                $templateProcessor->setValue("learn_and_sociale#$rowi", $student_review->review, 1);
            }
        }*/

		// save as a random file in temp file
		$temp_file = tempnam($CFG->tempdir, 'exastud');
		$templateProcessor->saveAs($temp_file);

		$filename = date('d-m-y')."-".'Lern_und_Sozialverhalten'."-".(trim($class->title_forreport) ? $class->title_forreport : $class->title).".docx";
        $filename = block_exastud_normalize_filename($filename);

        return (object)[
                'temp_file' => $temp_file,
                'filename' => $filename,
        ];

	}

	static function grades_report_html($class, $students) {
		global $CFG;

		//$class_subjects = block_exastud_get_bildungsplan_subjects($class->bpid);
		$class_subjects = block_exastud_get_class_subjects($class);

		// split normal and grouped subjects
		$normal_subjects = [];
		// add Verhalten and Mitarbeit to $normal_subjects if selected report has them
        // at least one selected student
        $verhaltenExists = false;
        $verhaltens = array();
        $mitarbeitExists = false;
        $mitarbeits = array();
        foreach ($students as $student) {
            $inputs = array_keys(block_exastud_get_student_print_template($class, $student->id)->get_inputs(BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO));
            $studentData = block_exastud_get_class_student_data($class->id, $student->id);
            if (in_array('verhalten', $inputs)) {
                $verhaltenExists = true;
                $verhaltens[$student->id] = @$studentData->verhalten;
            }
            if (in_array('mitarbeit', $inputs)) {
                $mitarbeitExists = true;
                $mitarbeits[$student->id] = @$studentData->mitarbeit;
            }
        }

		$grouped_subjects = [];
		foreach ($class_subjects as $subject) {
			if (preg_match('!religi|ethi!i', $subject->title)) {
                $subject->shorttitle_stripped = $subject->shorttitle;
				@$grouped_subjects['Religion / Ethik'][] = $subject;
			} elseif (preg_match('!^Wahlpflicht!i', $subject->title)) {
				$subject->shorttitle_stripped = preg_replace('!^WPF\s+!i', '', $subject->shorttitle);
				@$grouped_subjects['WPF'][] = $subject;
			} elseif (preg_match('!^Profilfach!i', $subject->title)) {
				$subject->shorttitle_stripped = preg_replace('!^Profil\s+!i', '', $subject->shorttitle);
				@$grouped_subjects['Profil'][] = $subject;
			} else {
                $subject->shorttitle_stripped = $subject->shorttitle;
				$normal_subjects[] = $subject;
			}
		}

        // school logo
        $logo_src = '';
        $fs = get_file_storage();
        $files = $fs->get_area_files(\context_system::instance()->id, 'exastud', 'block_exastud_schoollogo', 0, 'itemid', false);
        if ($files && count($files) > 0) {
            foreach ($files as $file) {
                $file_name = $file->get_filename();
                $url = \moodle_url::make_file_url('/pluginfile.php',
                        '/'.$file->get_contextid().'/block_exastud/block_exastud_schoollogo/'.$file->get_itemid().'/'.$file_name);
                $logo_info = $file->get_imageinfo();
                $logo_src = $url;
                // only first!
                break;
            }
        }

        $all_subjects = $normal_subjects;
        foreach (['Religion / Ethik', 'WPF', 'Profil'] as $sgroup) {
            if (array_key_exists($sgroup, $grouped_subjects)) {
                $all_subjects = array_merge($all_subjects, $grouped_subjects[$sgroup]);
            }
        }

        // table 1: subjects
        $isGroupedSubjects = array();
        $groupedSubjectTitles = array();
        // at first - generate data for subjects. So we will can hide empty columns
        $studentsData = array();
        $subjectsForAvg = array();
        foreach ($students as $student) {
            // normal subjects
            foreach ($normal_subjects as $subject) {
                $subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);
                $value = '';
                if ($subjectData) {
                    if (isset($subjectData->niveau)) {
                        $value .= $subjectData->niveau.' ';
                    }
                    if (isset($subjectData->grade)) {
                        $subjectsForAvg[$student->id][$subject->id] = $subjectData->grade;
                        $value .= $subjectData->grade;
                    }
                }
                if ($value) {
                    @$studentsData[$subject->id][$student->id] = $value;
                }
            }
            // grouped subjects
            foreach (['Religion / Ethik', 'WPF', 'Profil'] as $sgroup) {
                if (array_key_exists($sgroup, $grouped_subjects)) {
                    foreach (@$grouped_subjects[$sgroup] as $skey => $subject) {
                        $isGroupedSubjects[$subject->id] = $subject->id;
                        $groupedSubjectTitles[$subject->id] = $sgroup;
                        $subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);
                        $value = '';
                        if ($subjectData) {
                            if (isset($subjectData->niveau)) {
                                $value .= $subjectData->niveau.' ';
                            }
                            if (isset($subjectData->grade)) {
                                $subjectsForAvg[$student->id][$subject->id] = $subjectData->grade;
                                $value .= $subjectData->grade;
                            }
                        }
                        if (trim($value)) {
                            @$studentsData[$subject->id][$student->id] = $value;
                        }
                    }
                }
            }
        }

        $subjectsTable = new \html_table();
        $subjectsTable->head = array();
        $subjectsTable->align = array();
        $subjectsTable->attributes['border'] = '1';
        $subjectsTable->attributes['class'] = ' ';
        $subjectsTable->id = 'subjectsTable';
        $subjectsTable->head[] = ''; // student name
        $subjectsTable->align[] = '';
        if ($verhaltenExists) {
            $subjectsTable->head[] = 'Verhalten'; // Verhalten
        }
        if ($mitarbeitExists) {
            $subjectsTable->head[] = 'Mitarbeit'; // mitarbeit
        }

        foreach ($all_subjects as $subject) {
            if (count(@$studentsData[$subject->id]) > 0) {
                $hCell = new \html_table_cell();
                if (in_array($subject->id, $isGroupedSubjects)) {
                    $hCell->colspan = 2;
                    $hCell->text = $groupedSubjectTitles[$subject->id];
                } else {
                    $shorttitle = trim($subject->shorttitle_stripped);
                    $shorttitle = preg_replace('/(^\s+|\s+$|\s+)/', mb_convert_encoding('&#160;', 'UTF-8', 'HTML-ENTITIES'), $shorttitle); // insert &nbsp to table header
                    $hCell->text = $shorttitle;
                }
                $subjectsTable->head[] = $hCell;
                $subjectsTable->align[] = 'center';
            }
        }
        // average column
        $hCell = new \html_table_cell();
        //$hCell->colspan = 2;
        $hCell->text = 'Notendurchschnitt';
        $subjectsTable->head[] = $hCell;
        $subjectsTable->align[] = 'center';


		foreach ($students as $student) {
			$row = new \html_table_row();
			$cells = array();
			$cells[] = fullname($student);
			if ($verhaltenExists) {
                $cells[] = array_key_exists($student->id, $verhaltens) ? $verhaltens[$student->id] : '';
            }
			if ($mitarbeitExists) {
                $cells[] = array_key_exists($student->id, $mitarbeits) ? $mitarbeits[$student->id] : '';
            }
            foreach ($all_subjects as $subject) {
                if (count(@$studentsData[$subject->id]) > 0) {
                    if (in_array($subject->id, $isGroupedSubjects)) {
                        $cells[] = @$studentsData[$subject->id][$student->id];
                        $cells[] = $subject->shorttitle_stripped;
                    } else {
                        $cells[] = @$studentsData[$subject->id][$student->id];
                    }
                }
            }
            $templateid = block_exastud_get_student_print_template($class, $student->id)->get_template_id();
            if (array_key_exists($student->id, $subjectsForAvg)) {
                $avg = block_exastud_get_grade_average_value($subjectsForAvg[$student->id], false, $templateid, $class->id, $student->id);
            } else {
                $avg = block_exastud_get_grade_average_value(array(), false, $templateid, $class->id, $student->id);
            }
            //$avgVerbal = block_exastud_get_grade_average_value($subjectsForAvg, true);
            $cells[] = $avg;
            //$cells[] = $avgVerbal;

            $row->cells = $cells;
            $subjectsTable->data[] = $row;
		}

		// table 2: projekt
        $projectTable = new \html_table();
        $projectTable->head = array();
        $projectTable->align = array();
        $projectTable->attributes['border'] = '1';
        $projectTable->attributes['class'] = ' ';
        $projectTable->id = 'projectTable';
        $projectTable->head[] = ''; // student name
        $hCell = new \html_table_cell();
        $hCell->colspan = 2;
        $hCell->text = block_exastud_get_string('report_report_eval');
        $projectTable->head[] = $hCell;

		foreach ($students as $student) {
            $studentData = block_exastud_get_class_student_data($class->id, $student->id);
            if (@$studentData->projekt_grade || @$studentData->projekt_thema) {
                $row = new \html_table_row();
                $cells = array();
                $cells[] = fullname($student);
                $cells[] = @$studentData->projekt_grade;
                $cells[] = @$studentData->projekt_thema;
                $row->cells = $cells;
                $projectTable->data[] = $row;
            }
		}

		// table 3: ags
        $agsTable = new \html_table();
        $agsTable->head = array();
        $agsTable->align = array();
        $agsTable->attributes['border'] = '1';
        $agsTable->attributes['class'] = ' ';
        $agsTable->id = 'agsTable';
        $agsTable->head[] = ''; // student name
        $agsTable->head[] = block_exastud_get_string('ags');
		foreach ($students as $student) {
			$studentData = block_exastud_get_class_student_data($class->id, $student->id);
			if (@$studentData->ags) {
                $row = new \html_table_row();
                $cells = array();
                $cells[] = fullname($student);
                // crop ags by limits. Limits are got from class template
                $template = block_exastud_get_student_print_template($class, $student->id);
                $templateid = $template->get_template_id();
                $ags = block_exastud_cropStringByInputLimitsFromTemplate(@$studentData->ags, $templateid, 'ags');
                $cells[] = block_exastud_text_to_html($ags);
                $row->cells = $cells;
                $agsTable->data[] = $row;
            }
		}

		// table 4: subjects with teachers
		$class_teachers = block_exastud_get_class_subject_teachers($class->id);
        $teachersTable = new \html_table();
        $teachersTable->head = array();
        $teachersTable->align = array();
        $teachersTable->attributes['border'] = '1';
        $teachersTable->attributes['class'] = ' ';
        $teachersTable->id = 'teachersTable';
        $teachersTable->head[] = block_exastud_get_string('acronym');
        $teachersTable->head[] = block_exastud_get_string('subject');
        $teachersTable->head[] = block_exastud_get_string('teacher');
		foreach ($class_teachers as $class_teacher) {
            $subject = $class_subjects[$class_teacher->subjectid];
            $row = new \html_table_row();
            $cells = array();
            $cells[] = $subject->shorttitle;
            $cells[] = $subject->title;
            $cells[] = fullname($class_teacher);
            $row->cells = $cells;
            $teachersTable->data[] = $row;
		}

		// result content
		$result_content = '';
		if ($logo_src) {
            $wh = '';
            // simple resizing
            if (key_exists('width', $logo_info) && $logo_info['width'] > 1024) {
                $wh = ' width="1024" height="auto" ';
            } else if (key_exists('height', $logo_info) && $logo_info['height'] > 768) {
                $wh = ' height="768" width="auto" ';
            }
		    $logoTable = new \html_table();
            $logoTable->attributes['class'] = ' ';
            $logoTable->id = 'logoTable';
		    $logoTable->align = array('', 'right');
		    $logoTable->data = array(
		            array(
                        '',
                        '<img src="'.$logo_src.'" '.$wh.'/>',
                    ),
            );
            $result_content .= \html_writer::table($logoTable);
        }
        if (count($subjectsTable->data) > 0) {
            $result_content .= '<br /><br />'.\html_writer::table($subjectsTable);
        }
        if (count($projectTable->data) > 0) {
            $result_content .= '<br /><br />'.\html_writer::table($projectTable);
        }
        if (count($agsTable->data) > 0) {
            $result_content .= '<br /><br />'.\html_writer::table($agsTable);
        }
        if (count($teachersTable->data) > 0) {
            $result_content .= '<br /><br />'.\html_writer::table($teachersTable);
        }

        return $result_content;

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

        // add Verhalten and Mitarbeit to $normal_subjects if selected report has them
        // at least one selected student
        $verhaltenExists = false;
        $verhaltens = array();
        $mitarbeitExists = false;
        $mitarbeits = array();
        foreach ($students as $student) {
            $inputs = array_keys(block_exastud_get_student_print_template($class, $student->id)->get_inputs(BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO));
            $studentData = block_exastud_get_class_student_data($class->id, $student->id);
            if (in_array('verhalten', $inputs)) {
                $verhaltenExists = true;
                $verhaltens[$student->id] = @$studentData->verhalten;
            }
            if (in_array('mitarbeit', $inputs)) {
                $mitarbeitExists = true;
                $mitarbeits[$student->id] = @$studentData->mitarbeit;
            }
        }
        if ($verhaltenExists) {
            $sheet->setCellValueByColumnAndRow($cell++, 1, 'Verhalten');
        }
        if ($mitarbeitExists) {
            $sheet->setCellValueByColumnAndRow($cell++, 1, 'Mitarbeit');
        }

		foreach ($class_subjects as $subject) {
			$sheet->setCellValueByColumnAndRow($cell++, 1, $subject->shorttitle);
		}

		$sheet->setCellValueByColumnAndRow($cell++, 1, 'Notendurchschnitt');
		$sheet->setCellValueByColumnAndRow($cell++, 1, 'Projekt Note');
		$sheet->setCellValueByColumnAndRow($cell++, 1, 'Projekt Thema');
		$sheet->setCellValueByColumnAndRow($cell++, 1, 'AGs');

		$rowi = 1;
		foreach ($students as $student) {
			$rowi++;
			$cell = 0;
			$sheet->setCellValueByColumnAndRow($cell++, $rowi, $rowi - 1);
			$sheet->setCellValueByColumnAndRow($cell++, $rowi, fullname($student));
			$subjectsToAverage = array();
			// Verhalten and Mitarbeit
            if ($verhaltenExists) {
                if (array_key_exists($student->id, $verhaltens)) {
                    $sheet->setCellValueByColumnAndRow($cell++, $rowi, $verhaltens[$student->id]);
                } else {
                    $sheet->setCellValueByColumnAndRow($cell++, $rowi, '');
                }
            }
            if ($mitarbeitExists) {
                if (array_key_exists($student->id, $mitarbeits)) {
                    $sheet->setCellValueByColumnAndRow($cell++, $rowi, $mitarbeits[$student->id]);
                } else {
                    $sheet->setCellValueByColumnAndRow($cell++, $rowi, '');
                }
            }

			foreach ($class_subjects as $subject) {
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);
				$value = '';
                if ($subjectData) {
                    if (isset($subjectData->niveau)) {
                        if ($subject->no_niveau == 1) {
                            $value .= trim(str_ireplace('niveau', '', $subjectData->niveau));
                        } else {
                            $value .= $subjectData->niveau;
                        }
                    }
                    if (isset($subjectData->grade)) {
                        $subjectsToAverage[$subject->id] = $subjectData->grade;
                        $value .= ' '.$subjectData->grade;
                    }
                }

				$sheet->setCellValueByColumnAndRow($cell++, $rowi, $value);
			}
            $templateid = block_exastud_get_student_print_template($class, $student->id)->get_template_id();
            $avg = block_exastud_get_grade_average_value($subjectsToAverage, false, $templateid, $class->id, $student->id);
            $sheet->setCellValueByColumnAndRow($cell++, $rowi, ($avg ? $avg : ''));

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

		$filename = date('Y-m-d')."-".'Notenuebersicht'."-".(trim($class->title_forreport) ? $class->title_forreport : $class->title).".xlsx";

		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Excel2007');
		$temp_file = tempnam($CFG->tempdir, 'exastud');
		$writer->save($temp_file);

        $filename = block_exastud_normalize_filename($filename);

        return (object)[
                'temp_file' => $temp_file,
                'filename' => $filename,
        ];
		//require_once $CFG->dirroot.'/lib/filelib.php';
		//send_temp_file($temp_file, $filename);
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

	// BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_ANLAGE_ZUM_LERNENTWICKLUNGSBERICHTALT has 4 columns
    // the exacomp can have different grade values
	static function get_exacomp_crossgrade($origValue, $level = 'topic', $columnsCount = 4) {
	    if (!$origValue) {
            return $origValue;
        }
		if (!block_exastud_is_exacomp_installed()) {
			throw new \Exception('exacomp is not installed');
		}

		if (!method_exists('block_exacomp\api', 'get_comp_tree_for_exastud')) {
			throw new \Exception('please update exacomp version to match exastud version number');
		}

        $oldExacomp = false;
		//if (!!defined('BLOCK_EXACOMP_ASSESSMENT_TYPE_GRADE') || !defined('BLOCK_EXACOMP_ASSESSMENT_TYPE_VERBOSE')) {
        if (!function_exists('block_exacomp_get_assessment_diffLevel')) {
            @define('BLOCK_EXACOMP_ASSESSMENT_TYPE_GRADE', 1);
            @define('BLOCK_EXACOMP_ASSESSMENT_TYPE_VERBOSE', 2);
            @define('BLOCK_EXACOMP_ASSESSMENT_TYPE_POINTS', 3);
            @define('BLOCK_EXACOMP_ASSESSMENT_TYPE_YESNO', 4);
            $oldExacomp = true;
        }
		if (function_exists('block_exacomp_get_assessment_comp_scheme')) {
            if ($level == 'comp') {
                $scheme = block_exacomp_get_assessment_comp_scheme();
            } else {
                $scheme = block_exacomp_get_assessment_topic_scheme();
            }
        } else {
		    $scheme = 1;
        }
        $val = 0;
        switch ($scheme) {
            case BLOCK_EXACOMP_ASSESSMENT_TYPE_GRADE:
                // now we are thinking only about 6
                if (!$oldExacomp && get_config('exacomp', 'use_grade_verbose_competenceprofile')) {
                    // these values from exacomp API: get_comp_tree_for_exastud
                    if ($origValue == block_exacomp_get_string('grade_Verygood')) {
                        $val = 1.4;
                    } else if ($origValue == block_exacomp_get_string('grade_good')) {
                        $val = 2.4;
                    } else if ($origValue == block_exacomp_get_string('grade_Satisfactory')) {
                        $val = 3.4;
                    } else if ($origValue == block_exacomp_get_string('grade_Sufficient')) {
                        $val = 4.4;
                    } else if ($origValue == block_exacomp_get_string('grade_Deficient')) {
                        $val = 5.4;
                    } else {
                        // block_exacomp_get_string('grade_Insufficient')
                        $val = 5.5;
                    }
                    // if 4 columns
                    //return round($val * (-0.6) + 3.6); // max value 6 to 4 columns. TODO: is it ok?
                } else {
                    $val = $origValue;
                    //return round($origValue * (-0.6) + 3.6); // TODO: intval?
                }
                //$val = $origValue;
                break;
            case BLOCK_EXACOMP_ASSESSMENT_TYPE_VERBOSE:
                $options = array_map('trim', explode(',', block_exacomp_get_assessment_verbose_options()));
                //$crossPoints = array_combine(range(1, count($options)), array_values($options)); // start from 1
                $numberValue = array_search($origValue, $options);
                //return $numberValue;
                $val = $numberValue;
                break;
            case BLOCK_EXACOMP_ASSESSMENT_TYPE_POINTS:
                $maxColumns = 3; // 0, 1, 2, 3
                $maxPoints = block_exacomp_get_assessment_points_limit(); // 0, 1, ... max
                $koef = $maxColumns / $maxPoints;
                $val = $origValue * $koef;
                //return round($origValue * $koef); // TODO: intval?
                break;
            case BLOCK_EXACOMP_ASSESSMENT_TYPE_YESNO:
                if (!isset($teacher_additional_grading_topics[$record->compid])) {
                    if ($origValue == block_exacomp_get_string('yes_no_Yes') || $origValue > 0) {
                        //return $columnsCount;
                        $val = 1;
                    } else {
                        //return 1; // first column is BAD
                        $val = 6;
                    }
                }
                break;
        }
        $fig = (int) str_pad('1', 2, '0'); // 2 (second parameter) - precision
        $val  = (floor($val * $fig) / $fig); // - ALWAYS round down!
        // result column
        if ($val <= 2.2) {
            $result = 3; // vollständig erreicht
        } else if ($val <= 3.5) {
            $result = 2; // überwiegend erreicht
        } else if ($val <= 4.8) {
            $result = 1; // teilweise erreicht
        } else {
            $result = 0; // nicht erreicht
        }
        return $result;
	}

	static function learn_sozial_for_report(&$templateProcessor, $tamplateid, $class, $students) {
        $class_reviews = array();
        foreach ($students as $student) {
            $studentData = block_exastud_get_class_student_data($class->id, $student->id);
            // class teacher review
            if ($studentData->head_teacher) {
                $headTeacher = $studentData->head_teacher;
            } else {
                $headTeacher = $class->userid; // class owner as head teacher
            }
            $headTeacherObject = block_exastud_get_user($headTeacher);
            $headLernReview = $studentData->learning_and_social_behavior;
            // get reviews from teachers
            if (block_exastud_is_bw_active()) {
                // get from main userdata
                if (@$studentData->learning_and_social_behavior) {
                    $reviews = ['head' => (object)[
                            'userid' => $headTeacher,
                            'teacher' => $headTeacherObject,
                            'subject_title' => block_exastud_trans('de:Zuständiger Klassenlehrer'),
                            'title' => block_exastud_trans('de:Zuständiger Klassenlehrer'), // subject title
                            'review' => $headLernReview
                    ]];
                } else {
                    $reviews = array();
                }
            } else {
                // get from report configurable
                if (@$studentData->learn_social_behavior || @($studentData->learning_and_social_behavior)) {
                    $reviews = ['head' => (object)[
                            'userid' => $headTeacher,
                            'teacher' => $headTeacherObject,
                            'subject_title' => block_exastud_trans('de:Klassenlehrer'),
                            'title' => block_exastud_trans('de:Klassenlehrer'),
                            'review' => $studentData->learn_social_behavior ? $studentData->learn_social_behavior : $studentData->learning_and_social_behavior
                    ]];
                } else {
                    $reviews = array();
                }
            }

            // 2. subject by subject
            $zu="
                ";//linebreak in word
            foreach (block_exastud_get_class_subjects($class) as $class_subject) {
                $steachers = block_exastud_get_class_teachers_by_subject($class->id, $class_subject->id);
                $tempreview="";
                foreach ($steachers as  $steacher){
                    $class_subject->review = g::$DB->get_field('block_exastudreview', 'review', [
                            'studentid' => $student->id,
                            'subjectid' => $class_subject->id,
                            'periodid' => $class->periodid,
                            'teacherid' => $steacher->id,
                    ]);
                    if ($class_subject->review) {
                        $tempreview .= "***".fullname($steacher)."***:<br>".$class_subject->review."<br><br>";
                    }
                }

                if ($tempreview!="") {
                    $tempreview=preg_replace('/^<br><br>/','',$tempreview);
                    $class_subject->review=str_replace ("<br>",$zu,$tempreview);
                    $reviews[$class_subject->id] = $class_subject;
                }


            }

            if (count($reviews)) {
                $class_reviews[$student->id] = $reviews;
            }
        }
        if (!count($class_reviews)) {
            // no any review = no any report
            return null;
        }

        $templateProcessor->cloneBlock('studentblock', count($class_reviews), true);

        $s = 0;
        foreach ($class_reviews as $review_studentid => $student_reviews) {
            $s++;
            $student = block_exastud_get_user($review_studentid);
            $templateProcessor->setValue("student_number", $s, 1);
            $templateProcessor->setValue("student_name", fullname($student), 1);
            $templateProcessor->setValue("birthday", block_exastud_get_date_of_birth($review_studentid), 1);

            $templateProcessor->cloneRow('teacher', count($student_reviews));
            $rowi = 0;
            foreach ($student_reviews as $student_review) {
                $rowi++;
                //$teacher = block_exastud_get_user($student_review->userid);
                $templateProcessor->setValue("teacher#$rowi", fullname($student_review->teacher ), 1);
                $templateProcessor->setValue("subject#$rowi", $student_review->title, 1);
                $templateProcessor->setValue("learn_and_sociale#$rowi", $student_review->review, 1);
            }
        }
        return true;
    }

}

class Slice {

	function __construct($string, $start, $end) {
		$this->before = mb_substr($string, 0, $start);
		$this->slice = mb_substr($string, $start, $end - $start);
		$this->after = mb_substr($string, $end);
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
    protected $_rels;
    protected $_headerrels = array();
    protected $_footerrels = array();
    protected $_types;
    protected $_countRels = 0;

    public function __construct($documentTemplate){
        parent::__construct($documentTemplate);
        $this->_countRels = 100;
    }

    public function save()
    {
        //add this snippet to this function after $this->zipClass->addFromString('word/document.xml', $this->tempDocumentMainPart);
        // for main content
        if ($this->_rels != "") {
            $this->zipClass->addFromString('word/_rels/document.xml.rels', $this->_rels);
        }
        // for header
        if (count($this->_headerrels)) {
            foreach ($this->_headerrels as $fname => $fcontent) {
                $this->zipClass->addFromString($fname, $fcontent);
            }
        }
        // for footer
        if (count($this->_footerrels)) {
            foreach ($this->_footerrels as $fname => $fcontent) {
                $this->zipClass->addFromString($fname, $fcontent);
            }
        }
        // Content_Types
        if ($this->_types != "") {
            $this->zipClass->addFromString('[Content_Types].xml', $this->_types);
        }
        return parent::save();
    }

    function limpiarString($str) {
        return str_replace(
                array('&', '<', '>', "\n"),
                array('&amp;', '&lt;', '&gt;', "\n" . '<w:br/>'),
                $str
        );
    }

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

    function sanitizeXML($string)
    {
        if (!empty($string))
        {
            // remove EOT+NOREP+EOX|EOT+<char> sequence (FatturaPA)
            $string = preg_replace('/(\x{0004}(?:\x{201A}|\x{FFFD})(?:\x{0003}|\x{0004}).)/u', '', $string);
            $regex = '/(
            [\xC0-\xC1] # Invalid UTF-8 Bytes
            | [\xF5-\xFF] # Invalid UTF-8 Bytes
            | \xE0[\x80-\x9F] # Overlong encoding of prior code point
            | \xF0[\x80-\x8F] # Overlong encoding of prior code point
            | [\xC2-\xDF](?![\x80-\xBF]) # Invalid UTF-8 Sequence Start
            | [\xE0-\xEF](?![\x80-\xBF]{2}) # Invalid UTF-8 Sequence Start
            | [\xF0-\xF4](?![\x80-\xBF]{3}) # Invalid UTF-8 Sequence Start
            | (?<=[\x0-\x7F\xF5-\xFF])[\x80-\xBF] # Invalid UTF-8 Sequence Middle
            | (?<![\xC2-\xDF]|[\xE0-\xEF]|[\xE0-\xEF][\x80-\xBF]|[\xF0-\xF4]|[\xF0-\xF4][\x80-\xBF]|[\xF0-\xF4][\x80-\xBF]{2})[\x80-\xBF] # Overlong Sequence
            | (?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF]) # Short 3 byte sequence
            | (?<=[\xF0-\xF4])[\x80-\xBF](?![\x80-\xBF]{2}) # Short 4 byte sequence
            | (?<=[\xF0-\xF4][\x80-\xBF])[\x80-\xBF](?![\x80-\xBF]) # Short 4 byte sequence (2)
        )/x';
            $string = preg_replace($regex, '', $string);

            $result = "";
            $length = strlen($string);
            for ($i=0; $i < $length; $i++)
            {
                $current = ord($string{$i});
                if (($current == 0x9) ||
                        ($current == 0xA) ||
                        ($current == 0xD) ||
                        (($current >= 0x20) && ($current <= 0xD7FF)) ||
                        (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                        (($current >= 0x10000) && ($current <= 0x10FFFF)))
                {
                    $result .= chr($current);
                }
                else
                {
                    // $ret;    // use this to strip invalid character(s)
                    // $ret .= " ";    // use this to replace them with spaces
                }
            }
            $string = $result;
        }
        return $string;
    }

	function setValue($search, $replace, $limit = self::MAXIMUM_REPLACEMENTS_DEFAULT) {
        $replace = $this->sanitizeXML($replace);
		$replace = $this->escape($replace);
		// if the marker ${marker} is in the some element (form, textblock,...) he is inserting in something like w:val="${marker}"
        // and we does not need to replace linebreaks.
        // check at least one marker in the val=""
        $tempDocumentMainPart = $this->getDocumentMainPart();
        $tempDocumentMainPart = preg_replace('/(.*)<w:textInput>(.*)w:val="\${'.$search.'}"(.*)<\/w:textInput>(.*)/ms',
                '${1}<w:textInput>${2}w:val="${--'.$search.'--}"${3}</w:textInput>${4}', $tempDocumentMainPart); // TODO: check this!!
         //temporary hidden - much better use this! TODO: replace if perfomance issue!!!
        //$tempDocumentMainPart = preg_replace('/(<w:textInput>[^\/]*?)w:val="\${'.$search.'}"(.*<\/w:textInput>)/Ums',
        //        '${1}w:val="${--'.$search.'--}"${2}', $tempDocumentMainPart); // TODO: check this!!
        $this->setDocumentMainPart($tempDocumentMainPart);
        //} else {
        //    $replaceNL = true;
        //}
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

	function directReplace($search, $replace) {
		$oldEscaping = \PhpOffice\PhpWord\Settings::isOutputEscapingEnabled();
		// it's a raw value
		\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(false);
        $this->tempDocumentHeaders = str_replace($search, $replace, $this->tempDocumentHeaders);
        $this->tempDocumentMainPart = str_replace($search, $replace, $this->tempDocumentMainPart);
        $this->tempDocumentFooters = str_replace($search, $replace, $this->tempDocumentFooters);
        //foreach ($this->tempDocumentFooters as &$footerXML) {
        //    $footerXML = str_replace($search, $replace, $footerXML);
        //}
		\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled($oldEscaping);
		return true;
	}

	function changeOrientation($to = null) {
        $parts = $this->splitByTag($this->tempDocumentMainPart, 'pgSz');
	    $origOrient = $parts[1];
	    if ($to) {
            $pattern = '/(w:.?)=[\'"]([^\'"]*)/';
            preg_match_all($pattern, $origOrient, $matches, PREG_SET_ORDER);
            $attrs = [];
            foreach($matches as $match){
                $attrs[$match[1]] = $match[2];
                //Array (
                //      [w:w] => 12240
                //      [w:h] => 15840
                //)
            };
            if ($to == 'L' || $to == 'landscape') {
                $width = max($attrs);
                $height = min($attrs);
            } else {
                $width = min($attrs);
                $height = max($attrs);
            }
            $newOrient = '<w:pgSz w:w="'.$width.'" w:h="'.$height.'"/>';
        } else {
	        // toggle of orientation
            $newOrient = str_replace('w:w', 'aa:aa', $origOrient);
            $newOrient = str_replace('w:h', 'w:w', $newOrient);
            $newOrient = str_replace('aa:aa', 'w:h', $newOrient);
        }
        $this->directReplace($origOrient, $newOrient);
        return true;
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
		if ('${' !== mb_substr($search, 0, 2) && '}' !== mb_substr($search, -1)) {
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

			$rest = mb_substr($rest, strlen($matches[0]));
		}

		return $parts;
	}

    function rfindTagStart($tag, $offset, $fromContent = '')
    {
        /*
         * if (!preg_match('!<w:'.$tag.'[\s>].*$!Uis', substr($this->tempDocumentMainPart, 0, $offset), $matches)) {
         * throw new \Exception('tagStart $tag not found');
         * }
         *
         * echo $offset - strlen($matches[0]);
         */
        if ($fromContent) {
            $searchIn = $fromContent;
        } else {
            $searchIn = $this->tempDocumentMainPart;
        }

        $tagStart = strrpos($searchIn, '<w:' . $tag . ' ', ((strlen($searchIn) - $offset) * - 1));

        if (! $tagStart) {
            $tagStart = strrpos($searchIn, '<w:' . $tag . '>', ((strlen($searchIn) - $offset) * - 1));
        }
        if (! $tagStart) {
            throw new Exception('Can not find the start position of tag ' . $tag . '.');
        }

        return $tagStart;
    }

    function findTagEnd($tag, $offset, $fromContent = '')
    {
        $search = '</w:' . $tag . '>';
        if ($fromContent) {
            $searchIn = $fromContent;
        } else {
            $searchIn = $this->tempDocumentMainPart;
        }
        return strpos($searchIn, $search, $offset) + strlen($search);
    }


	function slice($string, $start, $end) {
		return new Slice($string, $start, $end);
	}

    /**
     * You need to do it with every row
     * @param string $search
     * @param int $numberOfCells
     */
    function duplicateCell($search, $numberOfCells = 1)
    {
        if ('${' !== mb_substr($search, 0, 2) && '}' !== mb_substr($search, - 1)) {
            $search = '${' . $search . '}';
        }

        $tagPos = $this->tagPos($search);

        $table = $this->slice($this->tempDocumentMainPart, $this->rfindTagStart('tbl', $tagPos), $this->findTagEnd('tbl', $tagPos));
        $newTagPos = strpos($table->get(), $search);

        $cellStartPos = $this->rfindTagStart('tc', $newTagPos, $table->get());
        $cellEndPos = $this->findTagEnd('tc', $newTagPos, $table->get());
        $cellToCopy = $this->slice($table->get(), $cellStartPos, $cellEndPos);

        // add new cell after source cell
        $resultCells = '';
        for($i = 0; $i < $numberOfCells; $i++) {
            $resultCells .= $cellToCopy->get();
        }
        $tableContent = substr_replace($table->get(), $resultCells, $cellEndPos, 0);

        $table->set($tableContent);
        $this->tempDocumentMainPart = $table->join();
    }

    // $columnIndex: 1 - clone second column, 2 - third.... (sometimes it is needed)
    // $columnCount: count of original columns (before cloning)
    function duplicateCol($search, $numberOfCols = 1, $columnIndex = 1, $columnsCount = 2) {
		$tagPos = $this->tagPos($search);

		$table = $this->slice($this->tempDocumentMainPart, $this->rfindTagStart('tbl', $tagPos), $this->findTagEnd('tbl', $tagPos));

		$splits = static::splitByTag($table->get(), 'gridCol');

		preg_match('!(^.*w:w=")([0-9]+)(".*)$!', $splits[1], $firstCol);
		preg_match('!(^.*w:w=")([0-9]+)(".*)$!', $splits[$columnIndex + 1], $newCol);
		array_shift($firstCol);
		array_shift($newCol);

		$newWidth = $firstCol[1] - $newCol[1] * ($numberOfCols - 1);
		$firstCol[1] = $newWidth;

		$splits[1] = join('', $firstCol);
		$splits[$columnIndex + 1] = str_repeat($splits[$columnIndex + 1], $numberOfCols);

		$splits = static::splitByTag(join('', $splits), 'tc');
        for ($i = 1; $i < count($splits); $i += $columnsCount + 1) {
			$splits[$i] = preg_replace('!(w:w=")[0-9]+!', '${1}'.$newWidth, $splits[$i]);
            $ind = $i + $columnIndex;
			if (array_key_exists($ind, $splits) && $ind != (count($splits) - 1)) {
                $splits[$ind] = str_repeat($splits[$ind], $numberOfCols);
            }
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

	function updateFileFromContent($filename, $content) {
		return $this->zipClass->addFromString($filename, $content);
	}

    /**
     * @param string $strKey
     * @param string $imgContent
     * @param string $imgExt
     * @param int $w
     * @param int $h
     * @param array $imageinfo
     * @return mixed
     */
    public function setMarkerImages($strKey, $imgContent = '', $imgExt = 'jpg', $w = 200, $h = 200, $imageinfo = array()) {
        if (!$strKey || !$imgContent || !$imgExt) {
            return false;
        }

        $strKey = '${'.$strKey.'}';
        $relationTmpl = '<Relationship Id="RID" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/IMG"/>';

        $imgTmpl = '<w:pict><v:shape type="#_x0000_t75" style="width:WIDpx;height:HEIpx"><v:imagedata r:id="RID" o:title=""/></v:shape></w:pict>';

        $toAdd = $toAddImg = $toAddType = '';
        $aSearch = array('RID', 'IMG');
        $aSearchType = array('IMG', 'EXT');
        $countrels = $this->_countRels++;
        $imgName = 'img' . $countrels . '.' . $imgExt;

        $this->zipClass->deleteName('word/media/'.$imgName);
        $this->zipClass->addFromString('word/media/'.$imgName, $imgContent);

        $newW = $w;
        $newH = $h;
        if (count($imageinfo) > 0 && (array_key_exists('width', $imageinfo) || array_key_exists('height', $imageinfo))) {
            $fileWidth = $imageinfo['width'];
            $fileHeight = $imageinfo['height'];
            $resized = false;
            // size of image will changed only if file size greater than needed.
            // if file size is less - will be used file size
            if ($fileWidth > $w) {
                $koef = $fileWidth / $w;
                $newW = $w;
                $newH = round((int)$fileHeight / $koef);
                $fileWidth = $newW;
                $fileHeight = $newH;
                $resized = true;
            }
            if ($fileHeight > $h) {
                $koef = $fileHeight / $h;
                $newW = round((int)$fileWidth / $koef);
                $newH = $h;
                $resized = true;
            }
            if (!$resized) {
                $newW = $fileWidth;
                $newH = $fileHeight;
            }
        }

        $typeTmpl = '<Override PartName="/word/media/'.$imgName.'" ContentType="image/EXT"/>';

        $rid = 'rId' . $countrels;
        $toAddImg .= str_replace(array('RID', 'WID', 'HEI'), array($rid, $newW, $newH), $imgTmpl) ;

        $aReplace = array($imgName, $imgExt);
        $toAddType .= str_replace($aSearchType, $aReplace, $typeTmpl) ;

        $aReplace = array($rid, $imgName);
        $toAdd .= str_replace($aSearch, $aReplace, $relationTmpl);

        if ($this->_rels == "") {
            $this->_rels = $this->zipClass->getFromName('word/_rels/document.xml.rels');
            $this->_types = $this->zipClass->getFromName('[Content_Types].xml');
        }

        $this->_types = str_replace('</Types>', $toAddType, $this->_types) . '</Types>';
        $this->_rels = str_replace('</Relationships>', $toAdd, $this->_rels) . '</Relationships>';

        $header_footer_count = 3; // TODO: 3 or more???
        $emptyRelFileContent = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
        // if the word/_rels/headerX.xml.rels file exists - use this
        // if does not exist - look on word/headerX.xml file. if it is existing - create new word/_rels/headerX.xml.rels
        // if both do not exist - no any doings
        // headers
        if (!count($this->_headerrels)) {
            for ($i = 1; $i <= $header_footer_count; $i++) {
                if ($headerContent = $this->zipClass->getFromName('word/_rels/header'.$i.'.xml.rels')) {
                    $this->_headerrels['word/_rels/header'.$i.'.xml.rels'] = $headerContent;
                } else if ($tempContent = $this->zipClass->getFromName('word/header'.$i.'.xml')) {
                    $this->_headerrels['word/_rels/header'.$i.'.xml.rels'] = $emptyRelFileContent; // new rel file
                }
            }
        }
        // footers
        if (!count($this->_footerrels)) {
            for ($i = 1; $i <= $header_footer_count; $i++) {
                if ($footerContent = $this->zipClass->getFromName('word/_rels/footer'.$i.'.xml.rels')) {
                    $this->_footerrels['word/_rels/footer'.$i.'.xml.rels'] = $footerContent;
                } else if ($tempContent = $this->zipClass->getFromName('word/footer'.$i.'.xml')) {
                    $this->_headerrels['word/_rels/footer'.$i.'.xml.rels'] = $emptyRelFileContent; // new rel file
                }
            }
        }
        // add relations
        foreach ($this->_headerrels as $hkey => $hcontent) {
            $this->_headerrels[$hkey] = str_replace('</Relationships>', $toAdd, $hcontent) . '</Relationships>';
        }
        foreach ($this->_footerrels as $hkey => $fcontent) {
            $this->_footerrels[$hkey] = str_replace('</Relationships>', $toAdd, $fcontent) . '</Relationships>';
        }

        $this->directReplace($strKey, $toAddImg);
        //return $toAddImg;
    }

    // Main function to add images to template
    public function addImageToReport($context, $stringKey, $component="block_exastud", $filearea, $modelid = false, $w = 1024, $h = 768, $fileFromLoggedInUser = false) {
        global $USER;
        $fs = get_file_storage();
        if (!$context) {
            $context = \context_system::instance()->id;
        }
        $files = $fs->get_area_files($context, $component, $filearea, $modelid, 'itemid', false);
        if ($files && count($files) > 0) {
            foreach ($files as $file) {
                if ($fileFromLoggedInUser && $file->get_userid() != $USER->id) {
                    continue;
                }
                $file_content = $file->get_content();
                $file_info = $file->get_imageinfo();
                if (!$w && !$h) {
                    $w = (array_key_exists('width', $file_info) ? $file_info['width'] : 1024);
                    $h = (array_key_exists('height', $file_info) ? $file_info['height'] : 768);
                }
                $fileExt = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
                $this->setMarkerImages($stringKey, $file_content, $fileExt, $w, $h, $file_info);
            }
            return true;
        }
        return false; // empty marker
    }

    public function fillSelectbox($stringKey, $newItems = array()) {
        $tagPos = $this->tagPos($stringKey);
        $sboxStart = strrpos($this->tempDocumentMainPart, '<w:comboBox ', ((strlen($this->tempDocumentMainPart) - $tagPos) * -1));
        if (!$sboxStart) {
            $sboxStart = strrpos($this->tempDocumentMainPart, '<w:comboBox>', ((strlen($this->tempDocumentMainPart) - $tagPos) * -1));
        }
        if (!$sboxStart) {
            return true;
        }
        $sboxEnd = strpos($this->tempDocumentMainPart, '</w:comboBox>', $tagPos) + 13;

        //$sboxXml = $this->getSlice($sboxStart, $sboxEnd);
        $newSboxXml = '<w:comboBox>';
        foreach ($newItems as $text => $value) {
            $newSboxXml .= '<w:listItem '.($text ? 'w:displayText="'.$text.'"' : '').' w:value="'.$value.'"/>';
        }
        $newSboxXml .= '</w:comboBox>';

        $result = $this->getSlice(0, $sboxStart);
        $result .= $newSboxXml;
        $result .= $this->getSlice($sboxEnd);

        $this->tempDocumentMainPart = $result;
    }

    public function blockExists($search, $searchEnd = '') {
        $searchStart = $search;
        if (!$searchEnd) {
            $searchEnd = $search;
        }
        if ('${' !== mb_substr($search, 0, 2) && '}' !== mb_substr($search, -1)) {
            $searchStart = '${'.$search.'}';
        }
        if ('${/' !== mb_substr($searchEnd, 0, 2) && '}' !== mb_substr($searchEnd, -1)) {
            $searchEnd = '${/'.$searchEnd.'}';
        }
        $startPos = strpos($this->tempDocumentMainPart, $searchStart);
        $endPos = strpos($this->tempDocumentMainPart, $searchEnd);
        if ($startPos && $endPos && $endPos > $startPos) {
            // block is here!
            return true;
        }
        return false;
    }

    public function exastud_cloneBlock($blockname, $clones = 1, $replace = true) {
        $xmlBlock = null;
        $reg = '/(<\?xml.*)(<w:p.*>\${' . $blockname . '}<\/w:.*?p>)(.*)(<w:p.*\${\/' . $blockname . '}<\/w:.*?p>)/is';
        $reg = '/(<\?xml.*)(<w:p[^a-zA-Z].*>\${' . $blockname . '}<\/w:.*?p>)(.*)(<w:p[^a-zA-Z].*\${\/' . $blockname . '}<\/w:.*?p>)/is';
        preg_match(
                $reg,
                $this->tempDocumentMainPart,
                $matches
        );

        if (isset($matches[3])) {
            $xmlBlock = $matches[3];
            $cloned = array();
            for ($i = 1; $i <= $clones; $i++) {
                $cloned[] = $xmlBlock;
            }

            if ($replace) {
                $this->tempDocumentMainPart = str_replace(
                        $matches[2] . $matches[3] . $matches[4],
                        implode('', $cloned),
                        $this->tempDocumentMainPart
                );
            }
        }

        return $xmlBlock;
    }

}
