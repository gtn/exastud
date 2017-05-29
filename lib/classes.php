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

class print_template {
	private $templateid;

	static function create($templateid) {
		return new static($templateid);
	}

	function __construct($templateid) {
		$this->templateid = $templateid;
	}

	function get_name() {
		return static::get_all_available_print_templates()[$this->templateid];
	}

	function get_template_id() {
		return $this->templateid;
	}

	static function get_all_available_print_templates() {
		return static::_get_class_available_print_templates(null);
	}

	static function get_class_available_print_templates($class) {
		return static::_get_class_available_print_templates($class);
	}

	/**
	 * if class is not set => return all available tempaltes
	 * @param null $class
	 * @return mixed
	 */
	protected static function _get_class_available_print_templates($class) {
		if ($class) {
			$bp = g::$DB->get_record('block_exastudbp', ['id' => $class->bpid]);
		} else {
			$bp = null;
		}

		if (!$bp || $bp->sourceinfo !== 'bw-bp2004') {
			$templates['BP 2016/Lernentwicklungsbericht neuer BP 1.HJ'] = 'LEB neuer BP 1.HJ';
			$templates['BP 2016/Lernentwicklungsbericht neuer BP SJ'] = 'LEB neuer BP SJ';
		}
		if (!$bp || $bp->sourceinfo !== 'bw-bp2016') {
			$templates['BP 2004/Lernentwicklungsbericht alter BP 1.HJ'] = 'LEB alter BP 1.HJ';
			$templates['BP 2004/Lernentwicklungsbericht alter BP SJ'] = 'LEB alter BP SJ';
			$templates['BP 2004/HalbjahreszeugnisRealschulabschluss an der Gemeinschaftsschule'] = 'Realschulabschluss GMS 1.HJ';
			$templates['BP 2004/Realschulabschlusszeugnis an der Gemeinschaftsschule BP 2004'] = 'Realschulabschluss GMS SJ';
			$templates['BP 2004/Halbjahresinformation Klasse 10Gemeinschaftsschule_E-Niveau_BP 2004'] = 'Klasse 10 GMS E-Niveau 1.HJ';
			$templates['BP 2004/Jahreszeugnis Klasse 10 der Gemeinschaftsschule E-Niveau'] = 'Klasse 10 GMS E-Niveau SJ';
			$templates['BP 2004/HalbjahreszeugnisHauptschulabschluss an der Gemeinschaftsschule _BP alt'] = 'Hauptschulabschluss GMS 1.HJ';
			$templates['BP 2004/Hauptschulabschluszeugnis GMS BP 2004'] = 'Hauptschulabschluss GMS SJ';
			$templates['BP 2004/Abgangszeugnis der Gemeinschaftsschule'] = 'Abgangszeugnis GMS';
			$templates['BP 2004/Abgangszeugnis der Gemeinschaftsschule HSA Kl.9 und 10'] = 'Abgangszeugnis GMS HSA Kl.9 und 10';
			// $templates['BP 2004/Zertifikat fuer Profilfach'] = 'Zertifikat für Profilfach';
			// $templates['BP 2004/Beiblatt zur Projektpruefung HSA'] = 'Beiblatt zur Projektprüfung HSA';
		}
		/*
		if (!$bp || $bp->sourceinfo !== 'bw-bp2004') {
			$templates['BP 2016/Lernentwicklungsbericht neuer BP 1.HJ'] = 'Lernentwicklungsbericht neuer BP 1.HJ';
			$templates['BP 2016/Lernentwicklungsbericht neuer BP SJ'] = 'Lernentwicklungsbericht neuer BP SJ';
		}
		if (!$bp || $bp->sourceinfo !== 'bw-bp2016') {
			$templates['BP 2004/Lernentwicklungsbericht alter BP 1.HJ'] = 'Lernentwicklungsbericht alter BP 1.HJ';
			$templates['BP 2004/Lernentwicklungsbericht alter BP SJ'] = 'Lernentwicklungsbericht alter BP SJ';
			$templates['BP 2004/HalbjahreszeugnisRealschulabschluss an der Gemeinschaftsschule'] = 'HalbjahreszeugnisRealschulabschluss an der Gemeinschaftsschule';
			$templates['BP 2004/Halbjahresinformation Klasse 10Gemeinschaftsschule_E-Niveau_BP 2004'] = 'Halbjahresinformation Klasse 10Gemeinschaftsschule_E-Niveau_BP 2004';
			$templates['BP 2004/HalbjahreszeugnisHauptschulabschluss an der Gemeinschaftsschule _BP alt'] = 'HalbjahreszeugnisHauptschulabschluss an der Gemeinschaftsschule _BP alt';
			$templates['BP 2004/Jahreszeugnis Klasse 10 der Gemeinschaftsschule E-Niveau'] = 'Jahreszeugnis Klasse 10 der Gemeinschaftsschule E-Niveau';
			$templates['BP 2004/Abgangszeugnis der Gemeinschaftsschule'] = 'Abgangszeugnis der Gemeinschaftsschule';
			$templates['BP 2004/Abgangszeugnis der Gemeinschaftsschule HSA Kl.9 und 10'] = 'Abgangszeugnis der Gemeinschaftsschule HSA Kl.9 und 10';
			$templates['BP 2004/Hauptschulabschluszeugnis GMS BP 2004'] = 'Hauptschulabschluszeugnis GMS BP 2004';
			$templates['BP 2004/Realschulabschlusszeugnis an der Gemeinschaftsschule BP 2004'] = 'Realschulabschlusszeugnis an der Gemeinschaftsschule BP 2004';
			// $templates['BP 2004/Zertifikat fuer Profilfach'] = 'Zertifikat für Profilfach';
			// $templates['BP 2004/Beiblatt zur Projektpruefung HSA'] = 'Beiblatt zur Projektprüfung HSA';
		}
	 	*/

		return $templates;
	}

	function get_grade_options() {
		if (in_array($this->templateid, [
			'BP 2004/Jahreszeugnis Klasse 10 der Gemeinschaftsschule E-Niveau',
			'BP 2004/HalbjahreszeugnisRealschulabschluss an der Gemeinschaftsschule',
			'BP 2004/HalbjahreszeugnisHauptschulabschluss an der Gemeinschaftsschule _BP alt',
		])) {
			return ['sgt', 'gut', 'bfr', 'ausr', 'mgh', 'ung'];
		} elseif ($this->templateid == 'BP 2004/Halbjahresinformation Klasse 10Gemeinschaftsschule_E-Niveau_BP 2004') {
			return [
				'1', '1-' => '1 minus', '1-2' => '1 - 2',
				'2+' => '2 plus', '2', '2-' => '2 minus', '2-3' => '2 - 3',
				'3+' => '3 plus', '3', '3-' => '3 minus', '3-4' => '3 - 4',
				'4+' => '4 plus', '4', '4-' => '4 minus', '4-5' => '4 - 5',
				'5+' => '5 plus', '5', '5-' => '5 minus', '5-6' => '5 - 6',
				'6+' => '6 plus', '6',
			];
		} else {
			return ['sehr gut', 'gut', 'befriedigend', 'ausreichend', 'mangelhaft', 'ungenügend'];
		}
	}

	function get_inputs() {
		$grades = $this->get_grade_options();

		if ($this->templateid == 'BP 2016/Lernentwicklungsbericht neuer BP' ||
			$this->templateid == 'BP 2004/Lernentwicklungsbericht alter BP'
		) {
			$inputs = [
				'comments' => [
					'title' => block_exastud_trans('de:Bemerkungen'),
					'type' => 'textarea',
				],
			];
		} elseif ($this->templateid == 'BP 2004/Jahreszeugnis Klasse 10 der Gemeinschaftsschule E-Niveau') {
			$inputs = [
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
			];
		} elseif ($this->templateid == 'BP 2004/Abgangszeugnis der Gemeinschaftsschule') {
			$inputs = [
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
					'type' => 'textarea3lines',
				],
				'comments_short' => [
					'title' => 'Bemerkungen',
					'type' => 'textarea3lines',
				],
				'abgangszeugnis_niveau' => [
					'title' => 'Die Leistung wurde in allen Fächern auf dem folgenden Niveau beurteilt',
					'type' => 'select',
					'values' => ['G' => 'G', 'M' => 'M', 'E' => 'E'],
				],
			];
		} elseif ($this->templateid == 'BP 2004/Abgangszeugnis der Gemeinschaftsschule HSA Kl.9 und 10') {
			$inputs = [
				'wann_verlassen' => [
					'title' => 'verlässt ...',
					'type' => 'select',
					'values' => [
						'ende9' => 'am Ende der Klasse 9 die Schule.',
						'ende10' => 'am Ende der Klasse 10 die Schule.',
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
				'ags' => [
					'title' => 'Teilnahme an Arbeitsgemeinschaften',
					'type' => 'textarea3lines',
				],
				'comments_short' => [
					'title' => 'Bemerkungen',
					'type' => 'textarea3lines',
				],
			];
		} elseif ($this->templateid == 'BP 2004/Hauptschulabschluszeugnis GMS BP 2004') {
			$inputs = [
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
			];
		} elseif ($this->templateid == 'BP 2004/Realschulabschlusszeugnis an der Gemeinschaftsschule BP 2004') {
			$inputs = [
				'projekt_thema' => [
					'title' => 'Projektprüfung: Thema',
					'type' => 'text',
				],
				'projekt_grade' => [
					'title' => 'Projektprüfung: Note',
					'type' => 'select',
					'values' => $grades,
				],
				'ags' => [
					'title' => 'Teilnahme an Arbeitsgemeinschaften',
					'type' => 'textarea3lines',
				],
				'comments_short' => [
					'title' => 'Bemerkungen',
					'type' => 'textarea3lines',
				],
			];
		} elseif ($this->templateid == 'BP 2004/Zertifikat fuer Profilfach') {
			$inputs = [
				'besondere_kompetenzen' => [
					'title' => 'Besondere Kompetenzen in folgenden Bereichen erworben',
					'type' => 'textarea',
				],
			];
		} elseif ($this->templateid == 'BP 2004/Beiblatt zur Projektpruefung HSA') {
			$inputs = [
				'projekt_text3lines' => [
					'title' => 'Projektthema',
					'type' => 'textarea3lines',
				],
				'projekt_verbalbeurteilung' => [
					'title' => 'Verbalbeurteilung',
					'type' => 'textarea',
				],
				'projekt_grade' => [
					'title' => 'Projektprüfung: Note',
					'type' => 'select',
					'values' => $grades,
				],
			];
		} elseif ($this->templateid == 'BP 2004/HalbjahreszeugnisRealschulabschluss an der Gemeinschaftsschule') {
			$inputs = [
				'ags' => [
					'title' => 'Teilnahme an Arbeitsgemeinschaften',
					'type' => 'textarea3lines',
				],
				'comments_short' => [
					'title' => 'Bemerkungen',
					'type' => 'textarea3lines',
				],
			];
		} elseif ($this->templateid == 'BP 2004/Halbjahresinformation Klasse 10Gemeinschaftsschule_E-Niveau_BP 2004') {
			$inputs = [
				'ags' => [
					'title' => 'Teilnahme an Arbeitsgemeinschaften',
					'type' => 'textarea3lines',
				],
				'comments_short' => [
					'title' => 'Bemerkungen',
					'type' => 'textarea3lines',
				],
			];
		} elseif ($this->templateid == 'BP 2004/HalbjahreszeugnisHauptschulabschluss an der Gemeinschaftsschule _BP alt') {
			$inputs = [
				'ags' => [
					'title' => 'Teilnahme an Arbeitsgemeinschaften',
					'type' => 'textarea3lines',
				],
				'comments_short' => [
					'title' => 'Bemerkungen',
					'type' => 'textarea3lines',
				],
			];
		} else {
			$inputs = [];
		}

		return $inputs;
	}
}
