<?php
require_once $CFG->libdir . '/formslib.php';

class block_exastud_picture_upload_form extends moodleform {

	function definition() {
		global $CFG, $USER, $DB;
		$mform = & $this->_form;

		$this->_form->_attributes['action'] = $_SERVER['REQUEST_URI'];
		$mform->addElement('header', 'comment', get_string("upload_picture", "block_exastud"));
		$mform->addElement('html',get_string('logosize','block_exastud'));
		$mform->addElement('filepicker', 'file', get_string("file"),null,array('accepted_types'=>'image'));
		$mform->addRule('file', get_string("commentshouldnotbeempty", "block_exaport"), 'required', null, 'client');

		$this->add_action_buttons(false, get_string('add'));

	}

}