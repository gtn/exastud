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

require __DIR__.'/inc.php';
require_once __DIR__.'/lib/picture_upload_form.php';

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID

require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
$curPeriod = block_exastud_get_active_or_next_period();

$url = '/blocks/exastud/import_class.php';
$PAGE->set_url($url, [ 'courseid' => $courseid ]);
$output = block_exastud_get_renderer();

echo $output->header(['configuration_classes']);


require_once $CFG->libdir . '/formslib.php';

class block_exastud_import_class_form extends moodleform {

	function definition() {
		$mform = & $this->_form;

		$mform->addElement('header', 'comment', block_exastud_trans('de:Klasse Importieren'));
		$mform->addElement('filepicker', 'file', block_exastud_get_string("file"));
		$mform->addRule('file', block_exastud_get_string('commentshouldnotbeempty'), 'required', null, 'client');

		$this->add_action_buttons(false, block_exastud_trans('de:Importieren'));
	}
}


$mform = new block_exastud_import_class_form();
if ($mform->is_cancelled()) {
	redirect($returnurl);
} else if ($mform->is_submitted()) {
	$content = $mform->get_file_content('file');
	$data = null;
	if (!$content) {
		echo $output->notification(block_exastud_trans('de:Keine Datei ausgewählt'), 'notifyerror');
	} else {
		$json = gzdecode($content);
		if (!$json) {
			echo $output->notification(block_exastud_trans('de:Datei hat falsches Format'), 'notifyerror');
		} else {
			$data = json_decode($json);
			if (!$data) {
				echo $output->notification(block_exastud_trans('de:Datei hat falsches Format'), 'notifyerror');
			}
		}
	}

	if ($data) {
		// import it
		var_dump($data);

		$class = $data->class;
		$class->timemodified = time();
		$class->userid = $USER->id;
		$class->title .= ' ('.block_exastud_trans('de:Importiert am ').date('d.m.Y H:i').')';
		$class->id = $DB->insert_record('block_exastudclass', $data->class);

		// $data->bp: not needed
		// $data->period: not needed
		// $data->subjects: not needed
		// $data->evalopt: not needed

		foreach ($data->classteachers as $classteacher) {
			$classteacher->classid = $class->id;
			$DB->insert_record('block_exastudclassteachers', $classteacher);
		}

		foreach ($data->students as $student) {
			$DB->insert_record('block_exastudclassstudents', [
				"classid" => $class->id,
				'studentid' => $student->id,
				'timemodified' => @$student->timemodified ?: time(),
			]);
		}

		foreach ($data->categories as $category) {
			$DB->insert_record('block_exastudclasscate', [
				"classid" => $class->id,
				'categoryid' => $category->id,
				'categorysource' => $category->source,
			]);
		}

		foreach ($data->data as $data) {
			$data->classid = $class->id;
			$DB->insert_record('block_exastuddata', $data);
		}

		// $data->classteastudvis: TODO

		die('import');
	}
}

$mform->display();

echo $output->footer();
