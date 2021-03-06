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
require_once($CFG->dirroot.'/blocks/exastud/lib/edit_form.php');
global $DB, $OUTPUT, $PAGE;

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = optional_param('classid', 0, PARAM_INT); // Course ID

require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
$curPeriod = block_exastud_get_active_or_next_period();

if (!$classid) {
	$class = new stdClass();
	$class->id = 0;
	$class->title = '';
} else {
	$class = block_exastud_get_head_teacher_class($classid);
}
$class->classid = $class->id;
$class->courseid = $courseid;

$classform = new class_edit_form();

if ($classform->is_cancelled()) {
	redirect('configuration_classes.php?courseid='.$courseid);
} elseif ($classedit = $classform->get_data()) {
	if (!confirm_sesskey()) {
		print_error("badsessionkey", "block_exastud");
	}

	$newclass = new stdClass();
	$newclass->timemodified = time();
	$newclass->title = $classedit->title;
	$newclass->bpid = $classedit->bpid;

	if ($class->id) {
		$newclass->id = $class->id;
		$DB->update_record('block_exastudclass', $newclass);
	} else {
		$newclass->userid = $USER->id;
		$newclass->periodid = $curPeriod->id;
		$newclass->id = $DB->insert_record('block_exastudclass', $newclass);
	}

	block_exastud_set_class_data($newclass->id, BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID, $classedit->{BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID});

	if ($class->id) {
		// standard zeugnis zur??cksetzen (wegen alter version wo es kein standard zeugnis gab)
		$new_default_templateid = $classedit->{BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID};
		$old_default_templateid = block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID);

		foreach (block_exastud_get_class_students($class->id) as $student) {
			$templateid = block_exastud_get_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE);
			if ($templateid && $templateid == $new_default_templateid || $templateid == $new_default_templateid) {
				block_exastud_set_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE, '');
			}
		}

		block_exastud_normalize_projekt_pruefung($class);
	}

	if ($class->id) {
		redirect('configuration_class_info.php?courseid='.$courseid.'&classid='.$class->id);
	} else {
		redirect('configuration_class.php?courseid='.$courseid.'&classid='.$newclass->id);
	}
}

$classform->set_data((array)$class + (array)block_exastud_get_class_data($class->id));

$url = "/blocks/exastud/configuration_class_info.php";
$PAGE->set_url($url);
$output = block_exastud_get_renderer();
echo $output->header(['configuration_classes', 'class_info'], ['class' => ($class && $class->id) ? $class : null]);

if ($class && $class->id) {
	$classform->display();

	echo $output->heading2(block_exastud_trans('de:Klasse l??schen'));

	if (!block_exastud_get_class_students($class->id)) {
		$deleteButton = $output->link_button('configuration_class.php?courseid='.$COURSE->id.'&action=delete&classid='.$class->id.'&confirm=1',
			block_exastud_get_string('delete'),
			['exa-confirm' => block_exastud_get_string('delete_confirmation', null, $class->title)]);
	} else {
		$deleteButton = html_writer::empty_tag('input', [
			'type' => 'button',
			'onclick' => "alert(".json_encode(block_exastud_trans('de:Es k??nnen nur Klassen ohne Sch??ler gel??scht werden')).")",
			'value' => block_exastud_trans('de:Klasse l??schen'),
		]);
	}

	echo $deleteButton;
} else {
	echo $output->heading(block_exastud_trans(['de:Klasse hinzuf??gen', 'en:Add Class']));

	$classform->display();
}

if ($class->id) {
	$templates = \block_exastud\print_templates::get_class_available_print_templates($class);
} else {
	$templates = \block_exastud\print_templates::get_all_default_print_templates();
}

$bps = $DB->get_records('block_exastudbp', [], 'sorting');
$templates_by_bp = [];
foreach ($bps as $bp) {
	$templates_by_bp[$bp->id] = \block_exastud\print_templates::get_bp_available_print_templates($bp);
}

?>
	<script>
			var templates_by_bp = <?php echo json_encode($templates_by_bp); ?>;

			function populate_select(name) {
				var $input = $('input,select').filter('[name=' + name + ']');
				var val = $input.val();
				var $select = $('<select/>', {name: name});

				$.each(templates_by_bp[$('select[name=bpid]').val()], function (id, title) {
					$select.append($('<option/>', {
						value: id,
						text: title
					}));
				});

				$input.replaceWith($select);
				// don't use $select.val() because if the value does not exist (in chrome) the select is empty
				$select.find('option[value="'+val+'"]').prop('selected', true);

				$select.change(function () {
					if (!confirm('Soll das Zeugnisformular, das f??r die Sch??ler erzeugt wird, bei allen ge??ndert werden?')) {
						$(this).val(current);
					} else {
						current = $(this).val();
					}
				});
			}

			populate_select('default_templateid');

			$(document).on('change', '[name=bpid]', function () {
				populate_select('default_templateid');
			});
	</script>
<?php

echo $output->footer();
