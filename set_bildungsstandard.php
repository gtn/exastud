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

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$action = optional_param('action', '', PARAM_TEXT);
require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_MANAGE_CLASSES);

$classid = required_param('classid', PARAM_INT);
$class = block_exastud\get_teacher_class($classid);

$url = '/blocks/exastud/set_bildungsstandard.php';
$PAGE->set_url($url);

if ($action == 'save') {
}

$output = block_exastud\get_renderer();
echo $output->header('configuration_classes');

echo $output->print_subtitle($class->title);

/* Print the Students */
echo html_writer::tag("h2", \block_exastud\get_string('members', 'block_exastud'));
$table = new html_table();

$table->head = [
	\block_exastud\trans('de:Bildungsstandard'),
	\block_exastud\trans('de:Ausgeschieden'),
	\block_exastud\get_string('firstname'),
	\block_exastud\get_string('lastname'),
	\block_exastud\get_string('email'),
];
$table->align = array("left", "left", "left");
$table->attributes['style'] = "width: 75%;";
$table->size = ['5%', '5%', '20%', '20%', '20%'];

$classstudents = \block_exastud\get_class_students($class->id);

foreach ($classstudents as $classstudent) {
	$table->data[] = [
		'<select><option></option><option>5-6</option><option>7-8</option></select>',
		'<input type="checkbox" />',
		$classstudent->firstname,
		$classstudent->lastname,
		$classstudent->email,
	];
}

//echo html_writer::table($table);
echo $output->table($table);

echo $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_classes.php?courseid='.$courseid,
	\block_exastud\get_string('savechanges'),
	['onclick' => "alert('Todo: Hier wird gespeichert')"]);

echo $output->footer();
