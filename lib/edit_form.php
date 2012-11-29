<?php

require_once($CFG->dirroot . '/lib/formslib.php');

class class_edit_form extends moodleform {

    function definition() {
        global $CFG, $USER;
        $mform = & $this->_form;

        $mform->addElement('text', 'class', 'Klasse:', array('size' => 50));
        $mform->setType('class', PARAM_TEXT);
        $mform->addRule('class', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);
        
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(false);
    }

    function validation($data, $files) {
        return true;
    }

}

class period_edit_form extends moodleform {

    function definition() {
        global $CFG, $USER;
        $mform = & $this->_form;

        $mform->addElement('text', 'description', 'Beschreibung der Periode:', array('size' => 50));
        $mform->setType('description', PARAM_TEXT);
        $mform->addRule('description', null, 'required', null, 'client');

        $timeoptions = array(
            'language' => 'en',
            'format' => 'd. M. Y - H:i',
            'minYear' => 2001,
            'maxYear' => 2010,
            'addEmptyOption' => false,
            'emptyOptionValue' => '',
            'emptyOptionText' => '&nbsp;',
            'optionIncrement' => array('i' => 1, 's' => 1),
            'optional' => false,
        );

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        
        $mform->addElement('date_time_selector', 'starttime', 'Startdatum:', $timeoptions);
        $mform->setType('starttime', PARAM_INT);
        $mform->addRule('starttime', null, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'endtime', 'Enddatum:', $timeoptions);
        $mform->setType('endtime', PARAM_INT);
        $mform->addRule('endtime', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', 0);

        $this->add_action_buttons(false);
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

        $mform->addElement('hidden', 'studentid');
        $mform->setType('studentid', PARAM_INT);
        $mform->setDefault('studentid', 0);

        $selectoptions = array(
            1 => get_string('evaluation1', 'block_exastud'),
            2 => get_string('evaluation2', 'block_exastud'),
            3 => get_string('evaluation3', 'block_exastud'),
            4 => get_string('evaluation4', 'block_exastud'),
            5 => get_string('evaluation5', 'block_exastud'),
            6 => get_string('evaluation6', 'block_exastud'),
            7 => get_string('evaluation7', 'block_exastud'),
            8 => get_string('evaluation8', 'block_exastud'),
            9 => get_string('evaluation9', 'block_exastud'),
            10 => get_string('evaluation10', 'block_exastud'),
        );

		$categories = $this->_customdata['categories'];
		foreach($categories as $category) {
			$id = $category->id.'_'.$category->source;
			$mform->addElement('select', $id, $category->title, $selectoptions);
			$mform->setType($id, PARAM_INT);
			$mform->setDefault($id, 1);
		}

        $mform->addElement('htmleditor', 'review', get_string('review', 'block_exastud'), array('cols' => 50, 'rows' => 30));
        $mform->setType('review', PARAM_RAW);

        $this->add_action_buttons(false);
    }

}

?>