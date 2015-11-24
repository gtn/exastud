<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class class_edit_form extends moodleform {

	function definition() {
		global $CFG, $USER;
		$mform = & $this->_form;

		$mform->addElement('text', 'class', \block_exastud\get_string('class', 'block_exastud').': ', array('size' => 50));
		$mform->setType('class', PARAM_TEXT);
		$mform->addRule('class', null, 'required', null, 'client');

		$mform->addElement('hidden', 'id');
		$mform->setType('id', PARAM_INT);
		$mform->setDefault('id', 0);
		
		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);

		$this->add_action_buttons();
	}

	function validation($data, $files) {
		return true;
	}

}

class period_edit_form extends moodleform {

	function definition() {
		global $CFG, $USER;
		$mform = $this->_form;

		$mform->addElement('text', 'description', get_string('perioddesc', 'block_exastud'), array('size' => 50));
		$mform->setType('description', PARAM_TEXT);
		$mform->addRule('description', get_string('error'), 'required', null, 'server', false, false);
		

		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);
		
		$mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'block_exastud'));
		$mform->setType('starttime', PARAM_INT);
		$mform->addRule('starttime', null, 'required', null, 'server');

		$mform->addElement('date_time_selector', 'endtime', get_string('endtime', 'block_exastud'));
		$mform->setType('endtime', PARAM_INT);
		$mform->addRule('endtime', null, 'required', null, 'server');

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
		global $CFG, $USER;
		$mform = & $this->_form;

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

		if ($this->_customdata['subjectid'] == block_exastud::SUBJECT_ID_LERN_UND_SOZIALVERHALTEN) {
			// für headteacher review ignorieren
		} else {
			$selectoptions = block_exastud_get_evaluation_options(true);
	
			$mform->addElement('header', 'categories', get_string('review', 'block_exastud'));
			
			$categories = $this->_customdata['categories'];
			foreach($categories as $category) {
				$id = $category->id.'_'.$category->source;
				
				$mform->addElement('select', $id, $category->title, $selectoptions);
				$mform->setType($id, PARAM_INT);
				$mform->setDefault($id, key($selectoptions));
			}
		}

		$mform->addElement('header', 'review_header', get_string('review', 'block_exastud'));
		$mform->addElement('htmleditor', 'review', get_string('review', 'block_exastud'), array('cols' => 50, 'rows' => 30));
		$mform->setType('review', PARAM_RAW);

		$this->add_action_buttons(false);
	}

}
