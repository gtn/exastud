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

$url = '/blocks/exastud/configuration_classes.php';
$PAGE->set_url($url);

$actPeriod = block_exastud_get_active_or_next_period();
$classes = block_exastud\get_head_teacher_classes_owner($actPeriod->id);

if (!$classes && block_exastud_has_global_cap(block_exastud\CAP_HEAD_TEACHER)) {
	redirect('configuration_class_info.php?courseid='.$courseid.'&action=add', \block_exastud\get_string('redirectingtoclassinput', 'block_exastud'));
}

$output = block_exastud\get_renderer();
echo $output->header('configuration_classes');

/* Print the Students */
echo html_writer::tag("h2", $actPeriod->description.': '.\block_exastud\get_string('configuration_classes'));

if ($classes) {
	$table = new html_table();

	$table->head = array(\block_exastud\get_string('class'), '');
	$table->align = array("left", "left");
	$table->size = array("50%");

	foreach ($classes as $class) {
		$table->data[] = [
			'<a href="configuration_class.php?courseid='.$courseid.'&action=edit&classid='.$class->id.'">'.$class->title.'</a>',
			$output->link_button('configuration_class.php?courseid='.$courseid.'&action=edit&classid='.$class->id,
				block_exastud\get_string('edit')).
			$output->link_button('configuration_class.php?courseid='.$courseid.'&action=delete&classid='.$class->id.'&confirm=1',
				block_exastud\get_string('delete'),
				['exa-confirm' => block_exastud\trans('de:Wirklich löschen?')]).
			'<div style="margin-top: 10px;">'.
			$output->link_button('set_bildungsstandard.php?courseid='.$courseid.'&action=bildungsstandard&classid='.$class->id,
				block_exastud\trans('de:Bildungsstandard / Ausscheiden festlegen')).
			'</div>',
		];
	}

	echo $output->table($table);
}

echo $OUTPUT->single_button($CFG->wwwroot.'/blocks/exastud/configuration_class_info.php?courseid='.$courseid.'&action=add',
	\block_exastud\trans(['de:Klasse hinzufügen', 'en:Add Class']), 'get');

if ($classes = block_exastud\get_head_teacher_classes_shared()) {
	echo html_writer::tag("h2", \block_exastud\trans('de:Mit mir geteilte Klassen'));

	$table = new html_table();

	$table->head = array(\block_exastud\get_string('class'), '');
	$table->align = array("left", "left");
	$table->size = array("50%");

	foreach ($classes as $class) {
		$table->data[] = [
			'<a href="configuration_class.php?courseid='.$courseid.'&action=edit&classid='.$class->id.'">'.$class->title.'</a>',
			$output->link_button('configuration_class.php?courseid='.$courseid.'&action=edit&classid='.$class->id,
				block_exastud\get_string('edit')),
		];
	}

	echo $output->table($table);
}


echo $output->footer();
