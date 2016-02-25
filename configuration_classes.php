<?php

require __DIR__.'/inc.php';

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$action = optional_param('action', '', PARAM_TEXT);
require_login($courseid);

block_exastud_require_global_cap(block_exastud\CAP_MANAGE_CLASSES);

$url = '/blocks/exastud/configuration_classes.php';
$PAGE->set_url($url);

$classes = block_exastud\get_head_teacher_classes_owner();

if (!$classes && block_exastud_has_global_cap(block_exastud\CAP_HEAD_TEACHER)) {
	redirect('configuration_class_info.php?courseid=' . $courseid .'&action=add', \block_exastud\get_string('redirectingtoclassinput', 'block_exastud'));
}

$output = block_exastud\get_renderer();
echo $output->header('configuration_classes');

/* Print the Students */
echo html_writer::tag("h2", \block_exastud\trans('de:Meine Klassen'));

if ($classes) {
	$table = new html_table();

	$table->head = array (\block_exastud\trans('Klasse'), '');
	$table->align = array ("left", "left", "left");

	foreach ($classes as $class) {
		$table->data[] = [
			'<a href="configuration_class.php?courseid='.$courseid.'&action=edit&classid='.$class->id.'">'.$class->title.'</a>',
			'<a href="configuration_class.php?courseid='.$courseid.'&action=edit&classid='.$class->id.'">'.block_exastud\get_string('edit').'</a> '.
			'<a href="configuration_class.php?courseid='.$courseid.'&action=delete&classid='.$class->id.'&confirm=1" onclick="return confirm(\''.block_exastud\trans('de:Wirklich löschen?').'\');">'.block_exastud\get_string('delete').'</a>'
		];
	}

	echo $output->table($table);
}

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_class_info.php?courseid=' . $courseid .'&action=add',
		\block_exastud\trans('de:Klasse hinzufügen'), 'get');

if ($classes = block_exastud\get_head_teacher_classes_shared()) {
	echo html_writer::tag("h2", \block_exastud\trans('de:Mit mir geteilte Klassen'));

	$table = new html_table();

	$table->head = array (\block_exastud\trans('Klasse'), '');
	$table->align = array ("left", "left", "left");

	foreach ($classes as $class) {
		$table->data[] = [
			'<a href="configuration_class.php?courseid='.$courseid.'&action=edit&classid='.$class->id.'">'.$class->title.'</a>',
			'<a href="configuration_class.php?courseid='.$courseid.'&action=edit&classid='.$class->id.'">'.block_exastud\get_string('edit').'</a> '
		];
	}

	echo $output->table($table);
}



echo $output->footer();
