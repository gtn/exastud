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

class global_config {
	static function get_niveau_options() {
		return ['G' => 'G', 'M' => 'M', 'E' => 'E', 'Z' => 'zieldifferenter Unterricht'];
	}

	static function get_niveau_option_title($id) {
		return @static::get_niveau_options()[$id];
	}
}

class print_templates {
	static function get_template_name($templateid) {
		return static::get_template_config($templateid)['name'];
	}

	static function get_template_grades($templateid) {
		return static::get_template_config($templateid)['grades'];
	}

	static function get_template_inputs($templateid) {
		return static::get_template_config($templateid)['inputs'];
	}

	static function get_all_template_configs() {
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
				'file' => 'BP 2016/Lernentwicklungsbericht neuer BP 1.HJ',
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
				'file' => 'BP 2016/Lernentwicklungsbericht neuer BP SJ',
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
				'file' => 'BP 2004/Lernentwicklungsbericht alter BP 1.HJ',
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
				'file' => 'BP 2004/Lernentwicklungsbericht alter BP SJ',
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
				'file' => 'BP 2004/Halbjahresinformation Klasse 10Gemeinschaftsschule_E-Niveau_BP 2004',
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
				'file' => 'BP 2004/Jahreszeugnis Klasse 10 der Gemeinschaftsschule E-Niveau',
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
				'file' => 'BP 2004/Abgangszeugnis der Gemeinschaftsschule',
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
				'file' => 'BP 2004/Abgangszeugnis der Gemeinschaftsschule HSA Kl.9 und 10',
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
				'file' => 'BP 2004/HalbjahreszeugnisHauptschulabschluss an der Gemeinschaftsschule _BP alt',
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
				'file' => 'BP 2004/Hauptschulabschluszeugnis GMS BP 2004',
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
				'file' => 'BP 2004/HalbjahreszeugnisRealschulabschluss an der Gemeinschaftsschule',
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
				'file' => 'BP 2004/Realschulabschlusszeugnis an der Gemeinschaftsschule BP 2004',
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
				'file' => 'BP 2004/Zertifikat fuer Profilfach',
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
				'file' => 'BP 2004/Beiblatt zur Projektpruefung HSA',
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
		        'file' => 'BP 2004/Abschlusszeugnis der Foerderschule',
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
		        'file' => 'BP 2004/HJ zeugnis Foe',
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
				'file' => 'Deckblatt und 1. Innenseite LEB',
				'inputs' => [],
			],
		];

		return $templates;
	}

	static function get_template_config($templateid) {
		$templates = static::get_all_template_configs();

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

		if (block_exastud_is_bw_active()) {
			$templateids[] = 'Deckblatt und 1. Innenseite LEB';

			if (!$bp || $bp->sourceinfo !== 'bw-bp2016') {
				$templateids[] = 'BP 2004/Zertifikat fuer Profilfach';
			}

			$templateids[] = 'BP 2004/Beiblatt zur Projektpruefung HSA';
		}

		return static::get_template_name_array($templateids);
	}

	static private function get_template_name_array($templateids) {
		$templates = [];
		foreach ($templateids as $templateid) {
			$templates[$templateid] = static::get_template_name($templateid);
		}

		return $templates;
	}

	static function get_class_other_print_templates_for_input($class) {
		if ($class) {
			$bp = g::$DB->get_record('block_exastudbp', ['id' => $class->bpid]);
		} else {
			$bp = null;
		}

		$templateids = [];

		if (!$bp || $bp->sourceinfo !== 'bw-bp2016') {
			$templateids[] = 'BP 2004/Zertifikat fuer Profilfach';
		}

		// $templates['BP 2004/Beiblatt zur Projektpruefung HSA'] = 'Beiblatt zur Projektprüfung HSA';

		return static::get_template_name_array($templateids);
	}

	/**
	 * if class is not set => return all available templates
	 * @param null $class
	 * @return mixed
	 */
	protected static function _get_class_available_print_templates($class) {
		if ($class) {
			$bp = g::$DB->get_record('block_exastudbp', ['id' => $class->bpid]);
		} else {
			$bp = null;
		}

		return static::get_bp_available_print_templates($bp);
	}

	static function get_bp_available_print_templates($bp) {
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
		$ids = [
			'BP 2004/GMS Abgangszeugnis HSA Kl.9 und 10',
			'BP 2004/GMS Hauptschulabschluss SJ',
			'BP 2004/GMS Realschulabschluss SJ',
		];

		return array_combine($ids, $ids);
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

	function get_inputs() {
		return print_templates::get_template_inputs($this->templateid);
	}

	function get_config() {
		return print_templates::get_template_config($this->templateid);
	}

	function get_file($templateid) {
	    if($templateid == "BP 2004/GMS Abschlusszeugnis der Förderschule" || $templateid == "BP 2004/GMS Halbjahreszeugniss der Förderschule"){
	        return __DIR__.'/../template/'.$this->get_config()['file'].'.dotx';
	    }else{
		  return __DIR__.'/../template/'.$this->get_config()['file'].'.docx';
	    }
	}
}
