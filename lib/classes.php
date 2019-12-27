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

use block_exastud\globals as g;
use function PHPSTORM_META\type;

class global_config {
	static function get_niveau_options($no_niveau = false) {
	    $no_niveau_options = ['Niveau G/M/E' => 'Niveau G/M/E',
                              'Z' => 'zieldifferenter Unterricht'];
	    if ($no_niveau) {
            return $no_niveau_options;
        }
        $niveau_options = ['G' => 'G',
                            'M' => 'M',
                            'E' => 'E',
                            'Z' => 'zieldifferenter Unterricht'];
	    if ($no_niveau == '_all_') { // TODO: check this condition. looks impossible
	        return array_merge($no_niveau_options, $niveau_options);
        }
        return $niveau_options;
	}

	static function get_niveau_option_title($id) {
		return @static::get_niveau_options('_all_')[$id];
	}
}

class print_templates {
	static function get_template_name($templateid) {
		return static::get_template_config($templateid)['name'];
	}

	static function get_template_file($templateid) {
	    global $CFG;
	    $filename = static::get_template_config($templateid)['file'];
	    $path = $CFG->dirroot.'/blocks/exastud/template/';
        if (is_file($path.$filename.'.docx')) {
            return $path.$filename.'.docx';
        } else if (is_file($path.$filename.'.dotx')) {
            return $path.$filename.'.dotx';
        } else {
            throw new \Exception("file for template '$templateid' not found");
        }
	}

    static function get_template_rs_hs_category($templateid) {
        return static::get_template_config($templateid)['rs_hs'];
    }

    static function get_template_category($templateid) {
        return static::get_template_config($templateid)['category'];
    }

	static function get_template_grades($templateid) {
		return static::get_template_config($templateid)['grades'];
	}

	static function get_template_params_sorting($templateid) {
		return static::get_template_config($templateid)['params_sorting'];
	}

	static function get_template_inputs($templateid, $type = BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN) {
		return static::get_template_config($templateid, $type)['inputs'];
	}

    static function get_all_template_configs($type = BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN) {
	    $templates = array();
	    $templates_temp = g::$DB->get_records('block_exastudreportsettings');
	    foreach ($templates_temp as $tmpl) {
	        $grades = array('' => '') + array_map('trim', explode(';', $tmpl->grades));
            $grades = array_combine($grades, $grades);
	        $templates[$tmpl->id] = array(
	                'name' => $tmpl->title,
                    'file' => $tmpl->template,
                    //'grades' => ['1'=>'1'], // for testing
                    //'grades' => block_exastud_get_evaluation_options(true),
                    'grades' => $grades,
                    'rs_hs' => (@$tmpl->rs_hs ? $tmpl->rs_hs : ''),
                    'category' => $tmpl->category,
                    'params_sorting' => (@$tmpl->params_sorting ? unserialize($tmpl->params_sorting) : ''),
                    'inputs' => self::get_inputs_for_template($tmpl->id, $type)
            );
        }
	    return $templates;
    }

    static function get_inputs_for_template($templateid, $type = BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN) {
        $template = g::$DB->get_record('block_exastudreportsettings', ['id' => $templateid]);
        switch ($type) {
            case BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN:
                $fields = array('learn_social_behavior');
                break;
            case BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE:
                $fields = array('comments', /*'subjects', 'subject_elective', 'subject_profile',*/ 'ags', 'class', 'focus' );
                $fieldsAdditional = unserialize($template->additional_params);
                if (is_array($fieldsAdditional)) {
                    $fields = array_merge($fields, $fieldsAdditional);
                }
                // some sorting:
                if (array_key_exists('lessons_target', $fields)) {
                    $cloneElem = $fields['lessons_target'];
                    unset($fields['lessons_target']);
                    $focusPosition = array_search('focus', $fields);
                    array_splice($fields, $focusPosition, 0, array($cloneElem) );
                }
                if (array_key_exists('beiblatt', $fields)) {
                    $cloneElem = $fields['beiblatt'];
                    unset($fields['beiblatt']);
                    $focusPosition = array_search('focus', $fields);
                    array_splice($fields, $focusPosition + 1, 0, array($cloneElem) );
                }
                unset($fields['besondere_kompetenzen']);
                unset($fields['projekt_thema']);
                unset($fields['projekt_grade']);
                unset($fields['projekt_verbalbeurteilung']);
                unset($fields['projekt_text3lines']);
                if (!$fields) {
                    $fields = array();
                }
                break;
            case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH:
                $fields_temp = unserialize($template->additional_params);
                if (!$fields_temp) {
                    $fields = array();
                } else {
                    $fields = array_intersect_key($fields_temp, array_flip(array('besondere_kompetenzen'/*, 'profilfach_fixed'*/)));
                }
                break;
            case BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO:
                $fields = unserialize($template->additional_params);
                unset($fields['besondere_kompetenzen']);
                unset($fields['projekt_thema']);
                unset($fields['projekt_grade']);
                unset($fields['projekt_verbalbeurteilung']);
                unset($fields['projekt_text3lines']);
                if (!$fields) {
                    $fields = array();
                }
                break;
            case BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER:
                $fieldsstatic = array('projekt_thema');
                $keptaditional = array('projekt_grade', 'projekt_verbalbeurteilung', 'projekt_text3lines');
                $customfields = unserialize($template->additional_params);
                if ($customfields) {
                    $customfields = array_intersect_key($customfields, array_flip($keptaditional));
                    $fields = array_merge($fieldsstatic, $customfields);
                } else {
                    $fields = $fieldsstatic;
                }
                break;
            case BLOCK_EXASTUD_DATA_ID_BILINGUALES:
                $fields = array();
                $keptaditional = array();
                for ($i = 5; $i <= 10; $i++) {
                    $keptaditional[] = 'eng_subjects_'.$i;
                    $keptaditional[] = 'eng_lessons_'.$i;
                }
                $customfields = unserialize($template->additional_params);
                if ($customfields) {
                    $fields = array_intersect_key($customfields, array_flip($keptaditional));
                }
                break;
            case 'all':
                $fieldsstatic = array('learn_social_behavior', 'subjects', 'comments', 'subject_elective', 'subject_profile', 'projekt_thema', 'ags', 'focus', 'class');
                $customfields = unserialize($template->additional_params);
                if ($customfields) {
                    $fields = array_merge($fieldsstatic, $customfields);
                } else {
                    $fields = $fieldsstatic;
                }
                break;
            default:
                $fields = array();
                break;
        }
        $inputs = array();
        foreach ($fields as $field) {
            if (is_array($field)) {
                $fieldData = $field;
                $field = $field['key'];
            } else if(isset($template->{$field})) {
                $fieldData = unserialize($template->{$field});
            } else {
                continue;
            }
            if ($fieldData['checked'] == 1) {
                $inputs[$field] = array(
                        'title' => $fieldData['title'],
                        'type' => $fieldData['type'],
                );
                switch ($fieldData['type']) {
                    case 'text':
                        break;
                    case 'textarea':
                        $inputs[$field]['lines'] = ($fieldData['rows'] > 0 ? $fieldData['rows'] : 8);
                        $inputs[$field]['cols'] = ($fieldData['count_in_row'] > 0 ? $fieldData['count_in_row'] : 45);
                        if (array_key_exists('maxchars', $fieldData)) {
                            $inputs[$field]['maxchars'] = ($fieldData['maxchars'] > 0 ? $fieldData['maxchars'] : 0);
                        }
                        break;
                    case 'select':
                        $inputs[$field]['values'] = (count($fieldData['values']) > 0 ? $fieldData['values'] : array());
                        break;
                    case 'header':
                        break;
                    case 'image':
                        $inputs[$field]['maxbytes'] = ($fieldData['maxbytes'] > 0 ? $fieldData['maxbytes'] : 50000);
                        $inputs[$field]['width'] = ($fieldData['width'] > 0 ? $fieldData['width'] : 800);
                        $inputs[$field]['height'] = ($fieldData['height'] > 0 ? $fieldData['height'] : 600);
                        break;
                    case 'userdata':
                        $inputs[$field]['userdatakey'] = ($fieldData['userdatakey'] != '' ? $fieldData['userdatakey'] : '');
                        break;
                    case 'matrix':
                        $inputs[$field]['matrixtype'] = ($fieldData['matrixtype'] != '' ? $fieldData['matrixtype'] : 'radio');
                        $inputs[$field]['matrixrows'] = (count($fieldData['matrixrows']) > 0 ? $fieldData['matrixrows'] : array());
                        $inputs[$field]['matrixcols'] = (count($fieldData['matrixcols']) > 0 ? $fieldData['matrixcols'] : array());
                        break;
                }
            }
        }

        // sort inputs by fixed sorting parameter
        $defaulttemplatesettings = block_exastud_get_default_templates($templateid);
        if (is_array($defaulttemplatesettings) && array_key_exists('inputs_order', $defaulttemplatesettings)) {
            $inputsorting = $defaulttemplatesettings['inputs_order'];
            $inputsSorted = array_merge(array_flip($inputsorting), $inputs);
            $inputsSorted = array_intersect_key($inputsSorted, $inputs);
            $inputs = $inputsSorted;
        }
        if (count($inputs) > 0) {
            return $inputs;
        } else {
            return null;
        }
    }

    /**
     * @return array
     * @deprecated
     */
	static function _old_get_all_template_configs() {
		$grades_1_bis_6 = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'];
		$grades_short = ['1' => 'sgt', '2' => 'gut', '3' => 'bfr', '4' => 'ausr', '5' => 'mgh', '6' => 'ung'];
		$grades_mit_plus_minus_bis = [
			'1' => '1', '1-' => '1-', '1-2' => '1-2',
			'2+' => '2+', '2' => '2', '2-' => '2-', '2-3' => '2-3',
			'3+' => '3+', '3' => '3', '3-' => '3-', '3-4' => '3-4',
			'4+' => '4+', '4' => '4', '4-' => '4-', '4-5' => '4-5',
			'5+' => '5+', '5' => '5', '5-' => '5-', '5-6' => '5-6',
			'6+' => '6+', '6' => '6',
		];
		$grades_mit_plus_minus_bis_ausgeschrieben = [
			'1' => '1', '1-' => '1 minus', '1-2' => '1 - 2',
			'2+' => '2 plus', '2' => '2', '2-' => '2 minus', '2-3' => '2 - 3',
			'3+' => '3 plus', '3' => '3', '3-' => '3 minus', '3-4' => '3 - 4',
			'4+' => '4 plus', '4' => '4', '4-' => '4 minus', '4-5' => '4 - 5',
			'5+' => '5 plus', '5' => '5', '5-' => '5 minus', '5-6' => '5 - 6',
			'6+' => '6 plus', '6' => '6',
		];
		$grades_lang = ['1' => 'sehr gut', '2' => 'gut', '3' => 'befriedigend', '4' => 'ausreichend', '5' => 'mangelhaft', '6' => 'ungenügend'];

		$templates = [
			'default_report' => [
				'name' => 'Standard Zeugnis',
				'file' => 'default_report',
				'grades' => $grades_1_bis_6,
				'inputs' => [
					'comments' => [
						'title' => block_exastud_trans('de:Bemerkungen'),
						'type' => 'textarea',
					],
				],
			],
			'BP 2016/GMS Zeugnis 1.HJ' => [
				'name' => 'BP 2016 GMS Zeugnis 1.HJ',
				'file' => 'BP 2016/BP2016_GMS_Halbjahr_Lernentwicklungsbericht',
				'grades' => $grades_mit_plus_minus_bis,
				'inputs' => [
					'comments' => [
						'title' => block_exastud_trans('de:Bemerkungen'),
						'type' => 'textarea',
					],
				],
			],
			'BP 2016/GMS Zeugnis SJ' => [
				'name' => 'BP 2016 GMS Zeugnis SJ',
				'file' => 'BP 2016/BP2016_Jahreszeugnis_Lernentwicklungsbericht',
				'grades' => $grades_1_bis_6,
				'inputs' => [
					'comments' => [
						'title' => block_exastud_trans('de:Bemerkungen'),
						'type' => 'textarea',
					],
				],
			],
			'BP 2004/GMS Zeugnis 1.HJ' => [
				'name' => 'BP 2004 GMS Zeugnis 1.HJ',
				'file' => 'BP 2004/BP2004_GMS_Halbjahr_Lernentwicklungsbericht',
				'grades' => $grades_mit_plus_minus_bis,
				'inputs' => [
					'comments' => [
						'title' => block_exastud_trans('de:Bemerkungen'),
						'type' => 'textarea',
					],
				],
			],
			'BP 2004/GMS Zeugnis SJ' => [
				'name' => 'BP 2004 GMS Zeugnis SJ',
				'file' => 'BP 2004/BP2004_Jahreszeugnis_Lernentwicklungsbericht',
				'grades' => $grades_1_bis_6,
				'inputs' => [
					'comments' => [
						'title' => block_exastud_trans('de:Bemerkungen'),
						'type' => 'textarea',
					],
				],
			],
			'BP 2004/GMS Klasse 10 E-Niveau 1.HJ' => [
				'name' => 'BP 2004 GMS Klasse 10 E-Niveau 1.HJ',
				'file' => 'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_E_Niveau',
				'grades' => $grades_mit_plus_minus_bis_ausgeschrieben,
				'inputs' => [
					'ags' => [
						'title' => 'Teilnahme an Arbeitsgemeinschaften',
						'type' => 'textarea',
						'lines' => 3,
					],
					'comments_short' => [
						'title' => 'Bemerkungen',
						'type' => 'textarea',
						'lines' => 3,
					],
				],
			],
			'BP 2004/GMS Klasse 10 E-Niveau SJ' => [
				'name' => 'BP 2004 GMS Klasse 10 E-Niveau SJ',
				'file' => 'BP 2004/BP2004_Jahreszeugnis_E_Niveau',
				'grades' => $grades_short,
				'inputs' => [
					'verhalten' => [
						'title' => 'Verhalten',
						'type' => 'select',
						'values' => [1 => 'sgt', 2 => 'gut', 3 => 'bfr', 6 => 'unbfr'],
					],
					'mitarbeit' => [
						'title' => 'Mitarbeit',
						'type' => 'select',
						'values' => [1 => 'sgt', 2 => 'gut', 3 => 'bfr', 6 => 'unbfr'],
					],
					'ags' => [
						'title' => 'Teilnahme an Arbeitsgemeinschaften',
					],
					'comments_short' => [
						'title' => 'Bemerkungen',
					],
				],
			],
			'BP 2004/GMS Abgangszeugnis' => [
				'name' => 'BP 2004 GMS Abgangszeugnis',
				'file' => 'BP 2004/BP2004_GMS_Abgangszeugnis_GMS',
				'grades' => $grades_lang,
				'inputs' => [
					'wann_verlassen' => [
						'title' => 'verlässt ...',
						'type' => 'select',
						'values' => [
							'heute8' => 'heute die Klasse 8 der Schule.',
							'heute9' => 'heute die Klasse 9 der Schule.',
							'heute10' => 'heute die Klasse 10 der Schule.',
							'during8' => 'während der Klasse 8 die Schule.',
							'during9' => 'während der Klasse 9 die Schule.',
							'during10' => 'während der Klasse 10 die Schule.',
							'ende8' => 'am Ende der Klasse 8 die Schule.',
							'ende10' => 'am Ende der Klasse 10 die Schule.',
						],
					],
					'ags' => [
						'title' => 'Teilnahme an Arbeitsgemeinschaften',
						'type' => 'textarea',
						'lines' => 3,
					],
					'comments_short' => [
						'title' => 'Bemerkungen',
						'type' => 'textarea',
						'lines' => 3,
					],
					'abgangszeugnis_niveau' => [
						'title' => 'Die Leistung wurde in allen Fächern auf dem folgenden Niveau beurteilt',
						'type' => 'select',
						'values' => ['G' => 'G', 'M' => 'M', 'E' => 'E'],
					],
				],
			],
			'BP 2004/GMS Abgangszeugnis HSA Kl.9 und 10' => [
				'name' => 'BP 2004 GMS Abgangszeugnis HSA Kl.9 und 10',
				'file' => 'BP 2004/BP2004_GMS_Abgangszeugnis_HS_9_10',
				'grades' => $grades_lang,
				'inputs' => [
					'wann_verlassen' => [
						'title' => 'verlässt ...',
						'type' => 'select',
						'values' => [
							'ende9' => 'am Ende der Klasse 9 die Schule.',
							'ende10' => 'am Ende der Klasse 10 die Schule.',
						],
					],
					/*
					'projekt_thema' => [
						'title' => 'Projektprüfung: Thema',
						'type' => 'text',
					],
					'projekt_grade' => [
						'title' => 'Projektprüfung: Note',
						'type' => 'select',
						'values' => $grades,
					],
					*/
					'ags' => [
						'title' => 'Teilnahme an Arbeitsgemeinschaften',
						'type' => 'textarea',
						'lines' => 3,
					],
					'comments_short' => [
						'title' => 'Bemerkungen',
						'type' => 'textarea',
						'lines' => 3,
					],
				],
			],
			'BP 2004/GMS Hauptschulabschluss 1.HJ' => [
				'name' => 'BP 2004 GMS Hauptschulabschluss 1.HJ',
				'file' => 'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_HS',
				'grades' => $grades_short,
				'inputs' => [
					'ags' => [
						'title' => 'Teilnahme an Arbeitsgemeinschaften',
						'type' => 'textarea',
						'lines' => 3,
					],
					'comments_short' => [
						'title' => 'Bemerkungen',
						'type' => 'textarea',
						'lines' => 3,
					],
				],
			],
			'BP 2004/GMS Hauptschulabschluss SJ' => [
				'name' => 'BP 2004 GMS Hauptschulabschluss SJ',
				'file' => 'BP 2004/BP2004_GMS_Abschlusszeugnis_HS',
				'grades' => $grades_lang,
				'inputs' => [
					/*
					'abgelegt' => [
						'title' => 'Hat die Hauptschulabschlussprüfung nach',
						'type' => 'select',
						'values' => [
							'nach9' => 'Klasse 9 der Gemeinschaftsschule mit Erfolg abgelegt.',
							'nach10' => 'Klasse 10 der Gemeinschaftsschule mit Erfolg abgelegt.',
						],
					],
					'projekt_thema' => [
						'title' => 'Projektprüfung: Thema',
						'type' => 'text',
					],
					'projekt_grade' => [
						'title' => 'Projektprüfung: Note',
						'type' => 'select',
						'values' => $grades,
					],
					*/
					'gesamtnote_und_durchschnitt_der_gesamtleistungen' => [
						'title' => 'Gesamtnote und Durchschnitt der Gesamtleistungen',
						'type' => 'text',
					],
					'ags' => [
						'title' => 'Teilnahme an Arbeitsgemeinschaften',
					],
					'comments_short' => [
						'title' => 'Bemerkungen',
					],
				],
			],
			'BP 2004/GMS Realschulabschluss 1.HJ' => [
				'name' => 'BP 2004 GMS Realschulabschluss 1.HJ',
				'file' => 'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_RS',
				'grades' => $grades_short,
				'inputs' => [
					'ags' => [
						'title' => 'Teilnahme an Arbeitsgemeinschaften',
						'type' => 'textarea',
						'lines' => 3,
					],
					'comments_short' => [
						'title' => 'Bemerkungen',
						'type' => 'textarea',
						'lines' => 3,
					],
				],
			],
			'BP 2004/GMS Realschulabschluss SJ' => [
				'name' => 'BP 2004 GMS Realschulabschluss SJ',
				'file' => 'BP 2004/BP2004_GMS_Abschlusszeugnis_RS',
				'grades' => $grades_lang,
				'inputs' => [
					/*
					'projekt_thema' => [
						'title' => 'Projektprüfung: Thema',
						'type' => 'text',
					],
					'projekt_grade' => [
						'title' => 'Projektprüfung: Note',
						'type' => 'select',
						'values' => $grades,
					],
					*/
					'ags' => [
						'title' => 'Teilnahme an Arbeitsgemeinschaften',
						'type' => 'textarea',
						'lines' => 3,
					],
					'comments_short' => [
						'title' => 'Bemerkungen',
						'type' => 'textarea',
						'lines' => 3,
					],
				],
			],
			'BP 2004/Zertifikat fuer Profilfach' => [
				'name' => 'Zertifikat für Profilfach',
				'file' => 'BP 2004/BP2004_16_Zertifikat_fuer_Profilfach',
				'grades' => [],
				'inputs' => [
					'besondere_kompetenzen' => [
						'title' => 'Besondere Kompetenzen in folgenden Bereichen erworben',
						'type' => 'textarea',
						'lines' => 13,
					],
				],
			],
			'BP 2004/Beiblatt zur Projektpruefung HSA' => [
				'name' => 'Beiblatt zur Projektprüfung HSA',
				'file' => 'BP 2004/BP2004_GMS_Anlage_Projektpruefung_HS',
				'grades' => $grades_lang,
				'inputs' => [
					/*
					'projekt_text3lines' => [
						'title' => 'Projektthema',
						'type' => 'textarea',
						'lines' => 3,
					],
					'projekt_verbalbeurteilung' => [
						'title' => 'Verbalbeurteilung',
						'type' => 'textarea',
						'lines' => 5,
					],
					'projekt_grade' => [
						'title' => 'Projektprüfung: Note',
						'type' => 'select',
						'values' => $grades,
					],
					*/
				],
			],
			'Anlage zum Lernentwicklungsbericht' => [
				'name' => 'Anlage zum Lernentwicklungsbericht',
				'file' => 'Anlage zum Lernentwicklungsbericht',
				'inputs' => [],
			],
		    'Anlage zum LernentwicklungsberichtAlt' => [
			    'name' => 'Anlage zum LernentwicklungsberichtAlt',
			    'file' => 'Anlage zum LernentwicklungsberichtAlt',
			    'inputs' => [],
			],
		    'BP 2004/GMS Abschlusszeugnis der Förderschule' => [
		        'name' => 'BP 2004 GMS Abschlusszeugnis der Förderschule',
		        'file' => 'BP 2004/BP2004_GMS_Abgangszeugnis_Foe',
		        'grades' => $grades_lang,
		        'inputs' => [
		            'gesamtnote_und_durchschnitt_der_gesamtleistungen' => [
		                'title' => 'Gesamtnote und Durchschnitt der Gesamtleistungen',
		                'type' => 'text',
		            ],
		            'ags' => [
		                'title' => 'Teilnahme an Arbeitsgemeinschaften',
		            ],
		            'comments_short' => [
		                'title' => 'Bemerkungen',
		            ],
		        ],
		    ],
		    'BP 2004/GMS Halbjahreszeugniss der Förderschule' => [
		        'name' => 'BP 2004 GMS Halbjahreszeugniss der Förderschule',
		        'file' => 'BP 2004/BP2004_GMS_Halbjahr_Zeugnis_Foe',
		        'grades' => $grades_short,
		        'inputs' => [
		            'ags' => [
		                'title' => 'Teilnahme an Arbeitsgemeinschaften',
		                'type' => 'textarea',
		                'lines' => 3,
		            ],
		        ],
		    ],
			'Deckblatt und 1. Innenseite LEB' => [
				'name' => 'Deckblatt und 1. Innenseite LEB',
				'file' => 'Lernentwicklungsbericht_Deckblatt_und_1._Innenseite',
				'inputs' => [],
			],
		];

		return $templates;
	}

	static function get_template_config($templateid, $type = BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN) {
		$templates = static::get_all_template_configs($type);

		if (empty($templates[$templateid])) {
			throw new moodle_exception("template '$templateid' not found");
		} else {
			return $templates[$templateid];
		}
	}

	static function get_all_default_print_templates() {
		return static::_get_class_available_print_templates(null);
	}

	static function get_class_available_print_templates($class) {
		return static::_get_class_available_print_templates($class);
	}

	static function get_class_other_print_templates($class) {
		if ($class) {
			$bp = g::$DB->get_record('block_exastudbp', ['id' => $class->bpid]);
		} else {
			$bp = null;
		}
		$templateids = [];

		// check on bw_active
        if (block_exastud_is_bw_active()) {
            $notpossibledefaulttemplates = block_exastud_get_default_templates(null, true);
        } else {
            $notpossibledefaulttemplates = block_exastud_get_default_templates(null, false);
        }
        $notpossibledefaulttemplatesids = array_map(function($r) {return $r['id'];}, $notpossibledefaulttemplates);

		if (block_exastud_is_bw_active() /*&& !block_exastud_get_only_learnsociale_reports()*/) {
		    // templates for "Reports" page

			//if (!$bp || $bp->sourceinfo !== 'bw-bp2016') {
            $templateids[] = BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH; // 'BP 2004/Zertifikat fuer Profilfach';
			//}

            if ($bp && $bp->sourceinfo == 'bw-bp2016') {
                // 2016 Beiblatt zur Projektprüfung
                $templateids[] = BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT; // 'BP 2016/Beiblatt zur Projektpruefung HSA';
                //$templateids[] = BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2016_GMS_BEIBLATT_PROJEKTARBEIT_HSA; // 'BP 2016/Beiblatt zur Projektpruefung HSA';
            } else {
                // 2004 Beiblatt zur Projektprüfung
                $templateids[] = BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_GMS_BEIBLATT_PROJEKTPRUEFUNG_HSA; // 'BP 2004/Beiblatt zur Projektpruefung HSA';
            }
		} else {
		    $templates = g::$DB->get_records('block_exastudreportsettings', ['bpid' => 0, 'hidden' => 0]);
		    foreach ($templates as $tmpl) {
		        if (!in_array($tmpl->id, $notpossibledefaulttemplatesids)) {
                    $templateids[] = $tmpl->id;
                }
            }
        }
		return static::get_template_name_array($templateids);
	}

	static private function get_template_name_array($templateids) {
		$templates = [];
		foreach ($templateids as $templateid) {
            //$templates[$templateid] = static::get_template_name($templateid);
			$templates[$templateid] = g::$DB->get_field('block_exastudreportsettings', 'title', ['id' => $templateid]);
		}
		return $templates;
	}

	static function get_class_other_print_templates_for_input($class) {
		/*if ($class) {
			$bp = g::$DB->get_record('block_exastudbp', ['id' => $class->bpid]);
		} else {
			$bp = null;
		}*/

		$templateids = [];

        // TODO: may be add some categorization?
        if ($class) {
            $tmpls = g::$DB->get_records('block_exastudreportsettings', ['bpid' => $class->bpid]);
        } else {
            $tmpls = g::$DB->get_records('block_exastudreportsettings');
        }
        foreach ($tmpls as $tmpl) {
            if (!in_array($tmpl->id, $templateids)) {
                $templateids[] = $tmpl->id;
            }
        }

		return static::get_template_name_array($templateids);
	}

	/**
	 * if class is not set => return all available templates
	 * @param null $class
	 * @return mixed
	 */
	protected static function _get_class_available_print_templates($class) {
		if ($class) {
		    if (block_exastud_is_bw_active()) {
                $bp = g::$DB->get_record('block_exastudbp', ['id' => $class->bpid]);
            } else {
		        $bp = null; // TODO: is this ok?
            }
		} else {
			$bp = null;
		}

		return static::get_bp_available_print_templates($bp);
	}

    static function get_bp_available_print_templates($bp) {
        $templateids = [];
        // check on bw_active
        if (block_exastud_is_bw_active()) {
            $notpossibledefaulttemplates = block_exastud_get_default_templates(null, true);
        } else {
            $notpossibledefaulttemplates = block_exastud_get_default_templates(null, false);
        }
        $notpossibledefaulttemplatesids = array_map(function($r) {return $r['id'];}, $notpossibledefaulttemplates);
        if ($bp) {
            $templates = g::$DB->get_records('block_exastudreportsettings', ['bpid' => $bp->id]);
        } else {
            $templates = g::$DB->get_records('block_exastudreportsettings');
        }
        foreach ($templates as $templ) {
            if (!in_array($templ->id, $templateids) // not a double
                && !$templ->hidden // not hidden
                && !in_array($templ->id, $notpossibledefaulttemplatesids) // not in another bw_active
                ) {
                $templateids[] = $templ->id;
            }
        }
        return static::get_template_name_array($templateids);
    }

    /**
     * @param $bp
     * @return array
     * @deprecated
     */
	static function _old_get_bp_available_print_templates($bp) {
		$templateids = [];

		if (block_exastud_is_bw_active()) {
			if (!$bp || $bp->sourceinfo !== 'bw-bp2004') {
				$templateids[] = 'BP 2016/GMS Zeugnis 1.HJ';
				$templateids[] = 'BP 2016/GMS Zeugnis SJ';
			}
			if (!$bp || $bp->sourceinfo !== 'bw-bp2016') {
				$templateids[] = 'BP 2004/GMS Zeugnis 1.HJ';
				$templateids[] = 'BP 2004/GMS Zeugnis SJ';

				$templateids[] = 'BP 2004/GMS Realschulabschluss 1.HJ';
				$templateids[] = 'BP 2004/GMS Realschulabschluss SJ';
				$templateids[] = 'BP 2004/GMS Klasse 10 E-Niveau 1.HJ';
				$templateids[] = 'BP 2004/GMS Klasse 10 E-Niveau SJ';
				$templateids[] = 'BP 2004/GMS Hauptschulabschluss 1.HJ';
				$templateids[] = 'BP 2004/GMS Hauptschulabschluss SJ';
				$templateids[] = 'BP 2004/GMS Abgangszeugnis';
				$templateids[] = 'BP 2004/GMS Abgangszeugnis HSA Kl.9 und 10';
				$templateids[] = 'BP 2004/GMS Abschlusszeugnis der Förderschule';
				$templateids[] = 'BP 2004/GMS Halbjahreszeugniss der Förderschule';
			}
		} else {
			$templateids[] = 'default_report';
		}

		return static::get_template_name_array($templateids);
	}

	static function get_templateids_with_projekt_pruefung() {
	    // templates with enabled 'projekt_thema'
        $available_templates = \block_exastud\print_templates::get_all_template_configs('all');
        $ids = array();
        foreach ($available_templates as $tmplid => $template) {
            if (array_key_exists('inputs', $template) && is_array($template['inputs']) && count($template['inputs']) > 0) {
                if (array_key_exists('projekt_thema', $template['inputs'])
                        && count($template['inputs']['projekt_thema']) > 0) {
                    $ids[] = $tmplid;
                }
            }
        }
		/*$ids = [
			'BP 2004/GMS Abgangszeugnis HSA Kl.9 und 10',
			'BP 2004/GMS Hauptschulabschluss SJ',
			'BP 2004/GMS Realschulabschluss SJ',
		];*/
		return array_combine($ids, $ids);
	}
	
	static function get_marker_configurations($templateid, $type, $class, $student) {
	    $tableData = g::$DB->get_record('block_exastudreportsettings', array('id' => $templateid));
	    $markers = array();
	    // checkboxes (fields of block_exastudreportsettings)
        // fieldname => array of possible markernames (for old templates supporting)
        $checkboxes = array(
            'year' => array('schuljahr'),
            'report_date' => array('datum'),
            'student_name' => array('name'), // do we need to have checkboxes for 'name', 'class', 'dates'....?
            'date_of_birth' => array('geburtsdatum', 'geburt'),
            'place_of_birth' => array('gebort'),
            'learning_group' => array(),
            //'class' => array('klasse', 'kla'),
            'class' => array(),
            'focus' => array()
        );
        $valueInsert = function($field, $value) use (&$markers, $checkboxes) {
            $markers[$field] = $value;
            if (is_array($checkboxes[$field])) {
                foreach ($checkboxes[$field] as $k => $oldmarker) {
                    $markers[$oldmarker] = $value;
                }
            }
        };
        foreach (array_keys($checkboxes) as $fieldname) {
            $fieldValue = '';
            $fieldData = unserialize($tableData->{$fieldname});
            // without checkboxes?
            switch ($fieldname) {
                case 'report_date':
                    $fieldValue = date('d.m.Y');
                    break;
                case 'student_name':
                    $fieldValue = $student->firstname.' '.$student->lastname;
                    break;
            }
            // with checkboxes
            if ($fieldData['checked'] && !$fieldValue) {
                $fieldValue = ' ---VAL--- '; // temporary
                switch ($fieldname) {
                    case 'year':
                        $fieldValue = block_exastud_get_year_for_report($class);
                        break;
                    //case 'report_date':
                    //    $fieldValue = date('d.m.Y');
                    //    break;
                    //case 'student_name':
                    //    $fieldValue = $student->firstname.' '.$student->lastname;
                    //    break;
                    case 'date_of_birth':
                        $fieldValue = block_exastud_get_date_of_birth($student->id);
                        break;
                    case 'place_of_birth':
                        $fieldValue = block_exastud_get_custom_profile_field_value($student->id, 'placeofbirth');
                        break;
                    case 'learning_group':
                        //$fieldValue = ' -- learning group -- ';
                        $fieldValue = $class->title;
                        break;
                    case 'class':
                        //$fieldValue = $class->title;
                        $fieldValue = ' -- class -- ';
                        break;
                    case 'focus':
                        $fieldValue = ' -- focus -- ';
                        break;
                }
                $valueInsert($fieldname, $fieldValue);
            } else {
                if (!$fieldValue) {
                    $valueInsert($fieldname, '');
                } else {
                    $valueInsert($fieldname, $fieldValue);
                }
            }
        }
        // inputs
        $inputs = static::get_template_inputs($templateid, $type);
        // for support old markers: new => array of old
        $oldsupport = array(
                'learn_social_behavior' => array('lern_und_sozialverhalten'),
                'projekt_thema' => array('assessment_project'),
                //'ags' => array('gesamtnote_und_durchschnitt_der_gesamtleistungen'),
        );
        $studentdata = (array)block_exastud_get_class_student_data($class->id, $student->id);
        if (is_array($inputs)) {
            foreach ($inputs as $key => $input) {
                switch ($input['type']) {
                    case 'header':
                        $markers[$key] = $input['title'];
                        break;
                    case 'image':
                        break;
                    default:
                        if ($key == 'learn_social_behavior' && empty($studentdata['learn_social_behavior'])) {
                            $studentdata['learn_social_behavior'] = (!empty($studentdata['learning_and_social_behavior']) ? $studentdata['learning_and_social_behavior'] : '') ;
                        }
                        if (array_key_exists($key, $studentdata)) {
                            $val = trim($studentdata[$key]);
                            if (!trim(strip_tags($val))) {
                                $inputValue = '---'; // spacer if empty
                            } else {
                                $inputValue = $val;
                                // crop for input limits
                                if ($input['type'] == 'textarea') {
                                    $inputValue = block_exastud_cropStringByInputLimitsFromTemplate($inputValue, $templateid, $key);
                                }
                            }
                        } else {
                            $inputValue = ' --- ';
                        }
                        $markers[$key] = $inputValue;
                        if (array_key_exists($key, $oldsupport)) {
                            foreach ($oldsupport[$key] as $oldkey) {
                                $markers[$oldkey] = $inputValue;
                            }
                        }
                }
            }
        }
        return $markers;
    }
}

class print_template {
	private $templateid;

	static function create($templateid) {
		return new static($templateid);
	}

	function __construct($templateid) {
		$this->templateid = $templateid;
	}

	function get_name() {
		return print_templates::get_template_name($this->templateid);
	}

	function get_template_id() {
		return $this->templateid;
	}

	function get_grade_options() {
		return print_templates::get_template_grades($this->templateid);
	}

	function get_inputs($type = BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN) {
		return print_templates::get_template_inputs($this->templateid, $type);
	}

	function get_config() {
		return print_templates::get_template_config($this->templateid);
	}

	function get_marker_configurations($type = BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN, $class = null, $student = null) {
		return print_templates::get_marker_configurations($this->templateid, $type, $class, $student);
	}

	function get_file() {
        return print_templates::get_template_file($this->templateid);
	    /*if($templateid == "BP 2004/GMS Abschlusszeugnis der Förderschule" || $templateid == "BP 2004/GMS Halbjahreszeugniss der Förderschule"){
	        return __DIR__.'/../template/'.$this->get_config()['file'].'.dotx';
	    } else {
		  return __DIR__.'/../template/'.$this->get_config()['file'].'.docx';
	    }*/
	}

	function get_rs_hs_category() {
        return print_templates::get_template_rs_hs_category($this->templateid);
    }

    function get_category() {
        return trim(print_templates::get_template_category($this->templateid));
    }

    function get_params_sorting() {
        return print_templates::get_template_params_sorting($this->templateid);
    }



}
