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
$classid = required_param('classid', PARAM_INT);

setcookie('lastclass', $classid);

block_exastud_require_login($courseid);

if (!$class = block_exastud_get_class($classid)) {
	throw new moodle_exception("badclass", "block_exastud");
}
if (!block_exastud_is_project_teacher($class, $USER->id)) {
	throw new moodle_exception("not a project teacher");
}

/*$categories = [
	'projekt_thema' => [
		'title' => 'Thema',
		'type' => 'text',
	],
	'projekt_grade' => [
		'title' => 'Note',
		'type' => 'select',
		'values' => ['1' => 'sehr gut', '2' => 'gut', '3' => 'befriedigend', '4' => 'ausreichend', '5' => 'mangelhaft', '6' => 'ungenügend'],
		// block_exastud_get_student_print_template($class, $student->id)->get_grade_options(),
	],
	'projekt_verbalbeurteilung' => [
		'title' => 'Verbalbeurteilung',
		'type' => 'textarea',
		'lines' => 5,
	],
];*/

$categories = [
        BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER => [
                'title' => block_exastud_get_string('review_project_evalueations'),
        ]
];
//'projekt_thema', 'projekt_grade', 'projekt_verbalbeurteilung'];
$classheader = $class->title.' - '.block_exastud_get_string('review_project_evalueations');

$output = block_exastud_get_renderer();

$url = '/blocks/exastud/review_class_project_teacher.php';
$PAGE->set_url($url, ['courseid' => $courseid, 'classid' => $classid]);
echo $output->header(array('review', '='.$classheader));
echo $output->heading($classheader);

$project_teacher_students = block_exastud_get_project_teacher_students($class, $USER->id, true);

/* Print the Students */
$table = new html_table();

$table->head = array();
$table->head[] = ''; //userpic
$table->head[] = block_exastud_get_string('name');
$table->head[] = '';

foreach ($categories as $category) {
	$table->head[] = $category['title'];
}

$table->align = array();
$table->align[] = 'center';
$table->align[] = 'left';
$table->align[] = 'center';

foreach ($project_teacher_students as $classstudent) {
    $template = block_exastud_get_student_print_template($class, $classstudent->id);
	$icons = '<img src="'.$CFG->wwwroot.'/pix/i/edit.gif" width="16" height="16" alt="'.block_exastud_get_string('edit').'" />';
	$userdesc = fullname($classstudent);

	$data = (array)block_exastud_get_class_student_data($classid, $classstudent->id);

	$row = new html_table_row();
	$row->cells[] = $OUTPUT->user_picture($classstudent, array("courseid" => $courseid));
	$row->cells[] = $userdesc;

	$row->cells[] = $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student_project_teacher.php?courseid='.$courseid.'&classid='.$classid.'&studentid='.$classstudent->id,
		block_exastud_get_string('edit'), ['class' => 'btn btn-default']);

	foreach ($categories as $dataid => $category) {
        $content = '<div><b>Formular:</b> '.$template->get_name().'</div>';
        $inputs = $template->get_inputs($dataid);
        if ($inputs) {
            foreach ($inputs as $dataid => $form_input) {
                switch (@$form_input['type']) {
                    case 'select':
                        $value = @$form_input['values'][$data[$dataid]];
                        break;
                    case 'image':
                        $files = $fs->get_area_files($context->id, 'block_exastud', 'report_image_'.$dataid, $classstudent->id,
                                'itemid', false);
                        $filesOut = [];
                        foreach ($files as $file) {
                            if ($file->get_userid() != $USER->id) {
                                continue;
                            }
                            $filename = $file->get_filename();
                            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                                    $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                            $img = html_writer::img($url, $filename, ['width' => 150]);
                            $filesOut[] = html_writer::link($url, $img, ['target' => '_blank']);
                        }
                        $br = '';
                        $value = implode($br, $filesOut);
                        break;
                    default:
                        $value = !empty($data[$dataid]) ? block_exastud_text_to_html($data[$dataid]) : '';
                }

                $content .= '<div style="padding-top: 10px; font-weight: bold;">'.$form_input['title'].'</div>';
                $content .= '<div>'.$value.'</div>';
            }
        }
        $row->cells[] = $content;
        /*if (@$category['type'] == 'select') {
			$row->cells[] = @$category['values'][$data[$dataid]];
		} else {
			$row->cells[] = !empty($data[$dataid]) ? block_exastud_text_to_html($data[$dataid]) : '';
		}*/
	}

	$table->data[] = $row;
}

echo $output->table($table);

echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid, 'openclass' => $classid]));

echo $output->footer();
