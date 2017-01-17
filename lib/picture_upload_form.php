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

require_once $CFG->libdir . '/formslib.php';

class block_exastud_picture_upload_form extends moodleform {

	function definition() {
		$mform = & $this->_form;

		$mform->addElement('header', 'comment', block_exastud_get_string('upload_picture'));
		$mform->addElement('html',block_exastud_get_string('logosize'));
		$mform->addElement('filepicker', 'file', block_exastud_get_string("file"),null,array('accepted_types'=>'image'));
		$mform->addRule('file', block_exastud_get_string('commentshouldnotbeempty'), 'required', null, 'client');

		$this->add_action_buttons(false, block_exastud_get_string('add'));
	}
}
