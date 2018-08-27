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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

use block_exastud\globals as g;

class class_edit_form extends moodleform {

	function definition() {
		$mform = &$this->_form;

		$mform->addElement('hidden', 'classid');
		$mform->setType('classid', PARAM_INT);
		$mform->setDefault('classid', 0);

		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);

		$mform->addElement('text', 'title', block_exastud_get_string('class').':', array('size' => 50));
		$mform->setType('title', PARAM_TEXT);
		$mform->addRule('title', null, 'required', null, 'client');

		$bps = g::$DB->get_records_menu('block_exastudbp', null, 'sorting', 'id, title');
		$mform->addElement('select', 'bpid', block_exastud_trans('de:Bildungsplan').':', $bps);

		$mform->addElement('static', '', '&nbsp;',
			g::$OUTPUT->notification(block_exastud_trans(['de:Bitte beachten Sie: Bei einer Änderung des Bildungsplans müssen alle Bewertungen erneut eingegeben werden.', 'en:']), 'notifymessage')
		);

		$mform->addElement('text', BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID, block_exastud_trans('de:Standard Zeugnisformular').':');
		$mform->setType(BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID, PARAM_TEXT);

		$mform->addElement('static', '', '&nbsp;',
			g::$OUTPUT->notification(block_exastud_trans(['de:Bitte beachten Sie: Bei einer Änderung des Standard Zeugnisformulars wird für alle Schüler das Zeugnisformular geändert. Bestehende Formulareinstellungen wie z.B. Agangszeugnis werden beibehalten.', 'en:']), 'notifymessage')
		);

		/*
		$subjects = $DB->get_records_menu('block_exastudsubjects', null, 'title', 'id, title');
		$select = $mform->addElement('select', 'mysubjectids', block_exastud_get_string('subjects_taught_by_me'), $subjects);
		$select->setMultiple(true);
		*/

		// change class owner (only for siteadmin
        if (block_exastud_is_siteadmin()) {
            $headteachers = block_exastud_get_head_teachers_all();
            $options = array();
            foreach ($headteachers as $teacher) {
                $options[$teacher->id] = $teacher->lastname.' '.$teacher->firstname;
            }
            $mform->addElement('select', 'userid', get_string('class_owner', 'block_exastud'), $options);
        }

		$this->add_action_buttons();
	}

	function validation($data, $files) {
		return true;
	}

}

class period_edit_form extends moodleform {

	function definition() {
		$mform = $this->_form;

		$mform->addElement('text', 'description', block_exastud_get_string('perioddesc'), array('size' => 50));
		$mform->setType('description', PARAM_TEXT);
		$mform->addRule('description', block_exastud_get_string('error'), 'required', null, 'server', false, false);


		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);

		$mform->addElement('date_time_selector', 'starttime', block_exastud_get_string('starttime'));
		$mform->setType('starttime', PARAM_INT);
		$mform->addRule('starttime', null, 'required', null, 'server');

		$mform->addElement('date_time_selector', 'endtime', block_exastud_get_string('endtime'));
		$mform->setType('endtime', PARAM_INT);
		$mform->addRule('endtime', null, 'required', null, 'server');

		$mform->addElement('date_selector', 'certificate_issue_date', block_exastud_get_string('certificate_issue_date'), [
			'optional' => true,
		]);
		$mform->setType('certificate_issue_date', PARAM_INT);

		$mform->addElement('hidden', 'id');
		$mform->setType('id', PARAM_INT);
		$mform->setDefault('id', 0);

		$mform->addElement('hidden', 'action');
		$mform->setType('action', PARAM_TEXT);
		$mform->setDefault('action', 0);

		$this->add_action_buttons();
	}

}

class student_edit_form extends moodleform {

	function definition() {
		$mform = &$this->_form;

		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);
		$mform->setDefault('courseid', 0);

		$mform->addElement('hidden', 'classid');
		$mform->setType('classid', PARAM_INT);
		$mform->setDefault('classid', 0);

		$mform->addElement('hidden', 'subjectid');
		$mform->setType('subjectid', PARAM_INT);
		$mform->setDefault('subjectid', 0);

		$mform->addElement('hidden', 'studentid');
		$mform->setType('studentid', PARAM_INT);
		$mform->setDefault('studentid', 0);

		$selectoptions = block_exastud_get_evaluation_options(true);

		$mform->addElement('header', 'categories', block_exastud_trans("de:Fachübergreifende Kompetenzen"));
		$mform->setExpanded('categories');
		if ($this->_customdata['categories.modified']) {
			$mform->addElement('static', '', '', $this->_customdata['categories.modified']);
		}

		$categories = $this->_customdata['categories'];
		foreach ($categories as $category) {
			$id = $category->id.'_'.$category->source;

			$mform->addElement('select', $id, $category->title, $selectoptions);
			$mform->setType($id, PARAM_INT);
			$mform->setDefault($id, key($selectoptions));
		}

		$mform->addElement('header', 'vorschlag_header', block_exastud_trans("de:Lern- und Sozialverhalten: Formulierungsvorschlag für Klassenlehrkraft"));
		$mform->setExpanded('vorschlag_header');
		$mform->addElement('textarea', 'vorschlag', '', ['cols' => 50, 'rows' => 5,
			'class' => 'limit-input-length',
			'style' => "width: 750px; height: 160px; resize: none; font-family: Arial !important; font-size: 11pt !important;",
		]);
		$mform->setType('vorschlag', PARAM_RAW);
		$mform->addElement('static', '', '', block_exastud_trans('de:Max. 8 Zeilen / 680 Zeichen'));
		$mform->addElement('header', 'review_header', block_exastud_trans("de:Fachkompetenzen"));
		$mform->setExpanded('review_header');
		if ($this->_customdata['review.modified']) {
			$mform->addElement('static', '', '', $this->_customdata['review.modified']);
		}
		
		$mform->addElement('textarea', 'review', '', ['cols' => 50, 'rows' => 20,
			'class' => 'limit-input-length',
			'style' => "width: 556px; height: 160px; resize: none; font-family: Arial !important; font-size: 11pt !important;",
		]);
		$mform->setType('review', PARAM_RAW);
		$mform->addElement('static', 'hint', "",  block_exastud_trans('de:Max. 8 Zeilen / 550 Zeichen'));
		$mform->addElement('header', 'grade_header', block_exastud_trans("de:Note und Niveau"));
		$mform->setExpanded('grade_header');

		if ($this->_customdata['grade.modified']) {
			$mform->addElement('static', '', '', $this->_customdata['grade.modified']);
		}
 		$niveauarray=array();

		$niveauarray[] =& $mform->createElement('select', 'niveau', block_exastud_get_string('de:Niveau'), ['' => ''] + block_exastud\global_config::get_niveau_options());
		$niveauarray[] =& $mform->createElement('static', '', "", "");
		$niveauarray[] =& $mform->createElement('static', 'lastPeriodNiveau', "", block_exastud_trans('de:lastPeriodNiveau'));
		$niveauarray[] =& $mform->createElement('static', '', "", ")");
		$mform->addGroup($niveauarray, 'niveauarray',  block_exastud_trans('de:Niveau'), array("( ", block_exastud_trans('de:letztes Halbjahr: '), ' '), false);
		
		$gradearray=array();
		$gradearray[] =& $mform->createElement('select', 'grade', block_exastud_get_string('de:Note'), ['' => ''] + $this->_customdata['grade_options']);
		$gradearray[] =& $mform->createElement('static', '', "", "");
		$gradearray[] =& $mform->createElement('static', 'lastPeriodGrade', "", block_exastud_trans('de:lastPeriodGrade'));
		$gradearray[] =& $mform->createElement('static', '', "", ")");
		$mform->addGroup($gradearray, 'gradearray', block_exastud_trans('de:Note'), array('( ',  block_exastud_trans('de:letztes Halbjahr: '), " " ), false);

		$mform->addElement('static', 'exacomp_grades', block_exastud_trans('de:Vorschläge aus Exacomp'), $this->_customdata['exacomp_grades']);

		$this->add_action_buttons(false);
	}
}

class student_other_data_form extends moodleform {

	function definition() {
		$mform = &$this->_form;

		foreach ($this->_customdata['categories'] as $dataid => $input) {
			if (empty($input['type']) || $input['type'] == 'textarea') {
				$mform->addElement('header', 'header_'.$dataid, $input['title']);
				$mform->setExpanded('header_'.$dataid);
				$maxchars='550';
				if (@$this->_customdata['modified']) {
					$mform->addElement('static', '', '', $this->_customdata['modified']);
				}

				if (empty($input['lines'])) {
					$input['lines'] = 8;
				}
				if (empty($input['cols'])) {
					$input['cols'] = 45;
				}

				$mform->addElement('textarea', $dataid, '', ['cols' => $input['cols'], 'rows' => 10,
					'class' => 'limit-input-length',
					'style' => "width: ".($input['cols'] * 15)."px; height: ".($input['lines'] * 20)."px; resize: none; font-family: Arial !important; font-size: 11pt !important;",
				]);
				$mform->setType($dataid, PARAM_RAW);
				if($input['lines'] == 3){
				    $mform->addElement('static', '', '', block_exastud_trans('de:Max. 3 Zeilen / 250 Zeichen'));
				}elseif($input['lines'] == 5){
				    $mform->addElement('static', '', '', block_exastud_trans('de:Max. 5 Zeilen / 400 Zeichen'));
				}else {
					if($input['cols'] == 50){
				    $mform->addElement('static', '', '', block_exastud_trans('de:Max. 8 Zeilen / 680 Zeichen'));
				  }else{
				  	$mform->addElement('static', '', '', block_exastud_trans('de:Max. 8 Zeilen / 550 Zeichen'));
				  }
				}
				
			} elseif ($input['type'] == 'text') {
				$mform->addElement('text', $dataid, $input['title']);
				$mform->setType($dataid, PARAM_RAW);
			} elseif ($input['type'] == 'select') {
				$mform->addElement('select', $dataid, $input['title'], ['' => ''] + $input['values']);
				$mform->setType($dataid, PARAM_RAW);
			} else {
				$mform->addElement('header', 'header_'.$dataid, $input['title']);
				$mform->setExpanded('header_'.$dataid);
			}
		}

		$this->add_action_buttons(false);
	}

}
