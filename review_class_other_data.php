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
require_once($CFG->dirroot . '/blocks/exastud/lib/reports_lib.php');

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$type = required_param('type', PARAM_TEXT);
$templateid = optional_param('templateid', -1, PARAM_INT);

setcookie('lastclass', $classid);

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_REVIEW);

$class = block_exastud_get_class($classid);
$simulateSubjectId = BLOCK_EXASTUD_SUBJECT_ID_OTHER_DATA;
if ((block_exastud_is_profilesubject_teacher($classid) || $class->userid != $USER->id)
        && $type == BLOCK_EXASTUD_DATA_ID_CERTIFICATE) {
    //$simulateSubjectId = BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH;
    $simulateSubjectId = BLOCK_EXASTUD_DATA_ID_CERTIFICATE;
}
$reviewclass = block_exastud_get_review_class($classid, $simulateSubjectId);

if (!$reviewclass || !$class) {
	print_error("badclass", "block_exastud");
}

switch ($type) {
    case BLOCK_EXASTUD_DATA_ID_CROSS_COMPETENCES:
            $categories = [
                    //BLOCK_EXASTUD_DATA_ID_CROSS_COMPETENCES => [
                    //'title' => block_exastud_get_string('cross_competences_for_head'),
                //],
            ];
            $classheader = $reviewclass->title.' - '.block_exastud_get_string('cross_competences_for_head');
            break;
    case BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN:
            $categories = [
                BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN => [
                    'title' => block_exastud_get_string('learn_and_sociale'),
                ],
            ];
            $classheader = $reviewclass->title.' - '.block_exastud_get_string('learn_and_sociale_for_head');
            break;
    /* case BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE:
            $categories = [
                BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE => [
                    'title' => block_exastud_get_string('report_other_report_fields'),
                ],
            ];
            $classheader = $reviewclass->title.' - '.block_exastud_get_string('report_other_report_fields');
            break; */
    case BLOCK_EXASTUD_DATA_ID_CERTIFICATE:
            $categories = [
                    BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH => [
                    'title' => block_exastud_get_string('report_for_subjects'),
                ],
            ];
            $classheader = $reviewclass->title.' - '.block_exastud_get_string('report_for_subjects');
            break;
    case BLOCK_EXASTUD_DATA_ID_BILINGUALES:
            $categories = [
                    BLOCK_EXASTUD_DATA_ID_BILINGUALES => [
                        'title' => block_exastud_get_string('report_bilinguales'),
                    ],
            ];
            //$classheader = $reviewclass->title.' - '.block_exastud_get_string('report_bilinguales');
            $classheader = $reviewclass->title.' - '.block_exastud\print_template::create($templateid)->get_name();
            break;
    default:
            // BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE
            $dataShown = false;
            if (isset($_COOKIE['student-fulldata-shown'])) {
                if ($_COOKIE['student-fulldata-shown'] == 1) {
                    $dataShown = true;
                }
            }
            $toogleButton = '<span class="toggle-all-students" data-shown="'.($dataShown ? '1' : '0').'">';
            $toogleButton .= '<a href="#" class="btn btn-xs exastud-collapse" '.($dataShown ? 'style="display: none;"' : '').' title="'.block_exastud_get_string('more_student_data_all').'">
                                    <img src="'.$CFG->wwwroot.'/blocks/exastud/pix/collapse_btn.png" width="16" height="16" />
                                </a>';
            $toogleButton .= '<a href="#" class="btn btn-xs exastud-uncollapse" '.($dataShown ? '' : 'style="display: none;"').' title="'.block_exastud_get_string('more_student_data_all_hide').'">
                                    <img src="'.$CFG->wwwroot.'/blocks/exastud/pix/uncollapse_btn.png" width="16" height="16" />
                                </a>';
            $toogleButton .= '</span>';

            $categories = [
                    BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE => [
                            'title' => block_exastud_get_string('report_other_report_fields').$toogleButton,
                    ],
            ];
            if (block_exastud_is_bw_active()) {
                $classheader = $reviewclass->title.' - '.block_exastud_get_string('report_other_report_fields');
            } else {
                $classheader = $reviewclass->title.' - '.block_exastud_get_string('report_report_fields');
            }
            /*// additional info - like BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE, but used another fields
            $template = \block_exastud\print_template::create($type);
            //$categories = $template->get_inputs($type);
            //$classheader = $reviewclass->title.' - '.$template->get_name();
            $categories = [
                    BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO => [
                            'title' => block_exastud_get_string('additional_info'),
                    ],
            ];
            $classheader = $reviewclass->title.' - '.block_exastud_get_string('additional_info');*/
}
$output = block_exastud_get_renderer();

$url = '/blocks/exastud/review_class_other_data.php';
$PAGE->set_url($url, ['courseid' => $courseid, 'classid' => $classid, 'type' => $type]);
echo $output->header(array('review', '='.$classheader));
echo $output->heading($classheader);

$actPeriod = block_exastud_check_active_period();
$classstudents = block_exastud_get_class_students($classid, true);
//$evaluation_options = block_exastud_get_evaluation_options();

/* Print the Students */
$table = new html_table();

$table->head = array();
$table->head[] = ''; //userpic
$table->head[] = block_exastud_get_string('name');
if (true) { // block_exastud_can_edit_class($reviewclass)) {
	$table->head[] = ''; // bewerten button
}
foreach ($categories as $category) {
	$table->head[] = $category['title'];
}

$table->align = array();
$table->align[] = 'center';
$table->align[] = 'left';
if (true) { // block_exastud_can_edit_class($reviewclass)) {
	$table->align[] = 'center';
}

$countItemsForHidding = 3; // from which count of items they must be hidden in the review table
$hasManyInputs = false; // flag

foreach ($classstudents as $classstudent) {
    $studenttemplateid =  block_exastud_get_student_print_template($class, $classstudent->id)->get_template_id();

    $hideReviewButton = false;

	$icons = '<img src="'.$CFG->wwwroot.'/pix/i/edit.gif" width="16" height="16" alt="'.block_exastud_get_string('edit').'" />';
	$userdesc = fullname($classstudent);

	$data = (array)block_exastud_get_class_student_data($classid, $classstudent->id);

	$row = new html_table_row();
	$row->cells[] = $OUTPUT->user_picture($classstudent, array("courseid" => $courseid));
	$row->cells[] = $userdesc;

	// if (true) { // block_exastud_can_edit_class($reviewclass)) {
	$editUser = null;
	if (@$data['head_teacher'] && $type != BLOCK_EXASTUD_DATA_ID_CERTIFICATE) {
		$editUser = $DB->get_record('user', array('id' => $data['head_teacher'], 'deleted' => 0));
	}
	if (!$editUser) {
		$editUser = $DB->get_record('user', array('id' => $reviewclass->userid, 'deleted' => 0));
	}

	$firstCat = @array_shift(array_keys($categories));
	switch ($firstCat) {
	    case BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE:
		    $hasInputs = !!block_exastud_get_student_print_template($class, $classstudent->id)->get_inputs(BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE);
		    break;
        case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH:
            // TODO: is it correct?
            $hasInputs = !!\block_exastud\print_templates::get_inputs_for_template(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH, BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH);
            //$hasInputs = !!block_exastud_get_student_print_template($class, $classstudent->id)->get_inputs(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH);
            break;
        case BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO:
            $hasInputs = !!block_exastud_get_student_print_template($class, $classstudent->id)->get_inputs(BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO); // TODO: some another?
            break;
        case BLOCK_EXASTUD_DATA_ID_BILINGUALES:
            $hasInputs = !!(block_exastud\print_template::create($templateid)->get_inputs(BLOCK_EXASTUD_DATA_ID_BILINGUALES));
            if (!block_exastud_is_bilingual_teacher($class->id, null, $classstudent->id, $templateid)) {
                $editBilingualUser = block_exastud_get_bilingual_teacher($classid, $classstudent->id);
                if ($editBilingualUser) {
                    $hideReviewButton = block_exastud_get_string('assigned_to', null, fullname($editBilingualUser));
                } else {
                    $hideReviewButton = ' ';
                }
            }
            //$hasInputs = !!block_exastud_get_class_bilingual_template($class->id)->get_inputs(BLOCK_EXASTUD_DATA_ID_BILINGUALES);
            break;
        case BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN:
            if (block_exastud_is_bw_active()) {
                // learn and social report must be only for 6,7,8,9 reports
                $learnSocReports = block_exastud_getlearnandsocialreports();
                if (!in_array($studenttemplateid, $learnSocReports)) {
                    $hideReviewButton = ' ';
                }
                $hasInputs = !!$categories;
                $personalHeadTeacher = block_exastud_get_personal_head_teacher($class->id, $classstudent->id, true);
                if ($personalHeadTeacher !== null && $personalHeadTeacher != $USER->id) {
                    $hideReviewButton = true;
                }
            } else {
                if (!block_exastud_can_edit_learnsocial_classteacher($class->id)) {
                    $hideReviewButton = ' ';
                    $hasInputs = true;
                }
            }
            break;
        default:
		    $hasInputs = !!$categories;
	}

	if ($type == BLOCK_EXASTUD_DATA_ID_CERTIFICATE && !block_exastud_is_profilesubject_teacher($classid)) {
        $row->cells[] = block_exastud_get_string('only_profilesubject_teacher');
    } else {
	    if (!$hideReviewButton) {
	        if ($type == BLOCK_EXASTUD_DATA_ID_CROSS_COMPETENCES) {
                $buttonTitle = block_exastud_get_string('show');
            } else {
                $buttonTitle = block_exastud_get_string('edit');
            }
            $row->cells[] =
                    $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student_other_data.php?courseid='.$courseid.
                            '&classid='.$classid.'&type='.$type.'&studentid='.$classstudent->id,
                            $buttonTitle,
                            array('class' => 'btn btn-default'));
        } else {
            if ($editUser->id != $USER->id) {
                $row->cells[] = block_exastud_get_string('assigned_to', null, fullname($editUser));
            } else if (!$hasInputs) {
                // no categories, or it's a default printtemplate with no inputs
                $row->cells[] = block_exastud_get_string('template_with_no_inputs');
            } /*else if ($type == BLOCK_EXASTUD_DATA_ID_BILINGUALES) {
                if (block_exastud_is_bilingual_teacher($class->id, null, $classstudent->id, $templateid)) {
                    $row->cells[] = $output->link_button($CFG->wwwroot.'/blocks/exastud/review_student_other_data.php?courseid='.$courseid.'&classid='.$classid.'&type='.$type.'&studentid='.$classstudent->id.'&templateid='.$templateid,
                            block_exastud_get_string('edit'),
                            array('class' => 'btn btn-default'));
                } else {
                    $editBilingualUser = block_exastud_get_bilingual_teacher($classid, $classstudent->id);
                    if ($editBilingualUser) {
                        $row->cells[] = block_exastud_trans(['de:Zugeteilt zu {$a}'], fullname($editBilingualUser));
                    } else {
                        $row->cells[] = '';
                    }
                }
            }*/
            else {
                $row->cells[] = $hideReviewButton;
            }
        }
    }
    $fs = get_file_storage();
    $context = context_system::instance();
	foreach ($categories as $dataid => $category) {

		if (in_array($dataid, [
		        BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE,
                BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO,
                BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH,
                BLOCK_EXASTUD_DATA_ID_BILINGUALES
                ])
        ) {
		    switch ($dataid) {
                case BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH:
                    $template = block_exastud\print_template::create(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH);
                    break;
                case BLOCK_EXASTUD_DATA_ID_BILINGUALES:
                    $template = block_exastud_get_class_bilingual_template($class->id, $classstudent->id);
                    //if (!block_exastud_is_bilingual_teacher($class->id, null, $classstudent->id, $templateid)) {
                    //    $template = null;
                    //}
                    if (!$template || $templateid != $template->get_template_id()) {
                        continue 3; // ignore this student because it has another template for bilingual
                    }
                    break;
                default:
                    $template = block_exastud_get_student_print_template($class, $classstudent->id);
            }
            if ($template) {
                if ($dataid == BLOCK_EXASTUD_DATA_ID_BILINGUALES && $hideReviewButton && block_exastud_is_bilingual_teacher($class->id, null, $classstudent->id)) {
                    $content = '<div><b>Formular:</b> '.
                            html_writer::link(new moodle_url('/blocks/exastud/review_class_other_data.php', [
                                    'courseid' => $courseid,
                                    'classid' => $class->id,
                                    'type' => BLOCK_EXASTUD_DATA_ID_BILINGUALES,
                                    'templateid' => $template->get_template_id()]),
                                    $template->get_name()
                            ).'</div>';
                } else {
                    $content = '<div><b>Formular:</b> '.$template->get_name().'</div>';
                }
                $student_profilfach = block_exastud_get_student_profilefach($class, $classstudent->id);
                if ($student_profilfach) {
                    $content .= '<span><strong>'.block_exastud_get_string('profilesubject').':</strong></span> '.$student_profilfach;
                }
                if (!$hideReviewButton) {
                    $inputs = $template->get_inputs($dataid);
                } else {
                    $inputs = null;
                }
                if ($inputs) {
                    // sorting
                    if (!block_exastud_is_bw_active()) {
                        $sorting = $template->get_params_sorting();
                        if ($sorting && count($sorting) > 0) {
                            $inputs = array_merge(array_flip($sorting), $inputs);
                        }
                    }
                    $i = 0;
                    $content .= '<div class="input-container">';
                    foreach ($inputs as $dataid => $form_input) {
                        $i++;
                        if ($i == $countItemsForHidding + 1) { // collapsible block only if count of inputs more than 3
                            $content .= '<span class="exastud-collapse-inputs" data-inputsBlock="'.$classstudent->id.'">
                                            <a class="btn btn-xs btn-default" href="#" title="'.block_exastud_get_string('more_student_data').'">...</a>
                                        </span>';
                            $content .= '<div class="input-collapsible" data-inputsBlock="'.$classstudent->id.'" style="'.($dataShown ? '' : 'display:none;').'">';
                        }
                        switch (@$form_input['type']) {
                            case 'select':
                                $value = @$form_input['values'][$data[$dataid]];
                                break;
                            case 'image':
                                $files = $fs->get_area_files($context->id, 'block_exastud', 'report_image_'.$dataid,
                                        $classstudent->id, 'itemid', false);
                                $filesOut = [];
                                foreach ($files as $file) {
                                    if ($file->get_userid() != $USER->id) {
                                        continue;
                                    }
                                    $filename = $file->get_filename();
                                    $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                                            $file->get_filearea(), $file->get_itemid(), $file->get_filepath(),
                                            $file->get_filename());
                                    $img = html_writer::img($url, $filename, ['width' => 150]);
                                    $filesOut[] = html_writer::link($url, $img, ['target' => '_blank']);
                                }
                                $br = ''; //html_writer::empty_tag('br');
                                $value = implode($br, $filesOut);
                                //$value = file_rewrite_pluginfile_urls('sss', 'pluginfile.php',
                                //        $context->id, 'block_exastud', 'report_image_'.$dataid, $classstudent->id);
                                break;
                            case 'userdata':
                                $tempNull = null;
                                $value = block_exastud_get_report_userdata_value($tempNull, $dataid, $classstudent->id, $form_input['userdatakey']);
                                break;
                            case 'matrix':
                                $value = '<table width="100%" border="1" style="border-color: #555555; font-size: 0.65em;">';
                                $value .= '<tr><td></td>';
                                foreach ($form_input['matrixcols'] as $coltitle) {
                                    $value .= '<td>'.$coltitle.'</td>';
                                }
                                $value .= '</tr>';
                                foreach ($form_input['matrixrows'] as $rowtitle) {
                                    $value .= '<tr>';
                                    $value .= '<td>'.$rowtitle.'</td>';
                                    foreach ($form_input['matrixcols'] as $coltitle) {
                                        $value .= '<td>';
                                        switch ($form_input['matrixtype']) {
                                            case 'text':
                                                $value .= @$data[$dataid][$rowtitle][$coltitle];
                                                break;
                                            case 'radio':
                                                if (@$data[$dataid][$rowtitle] == $coltitle) {
                                                    $value .= 'X';
                                                }
                                                break;
                                            case 'checkbox':
                                                if (@$data[$dataid][$rowtitle][$coltitle]) {
                                                    $value .= 'X';
                                                }
                                                break;
                                        }
                                        $value .= '</td>';
                                    }
                                    $value .= '</tr>';
                                }
                                $value .= '</table>';
                                break;
                            default:
                                $value = !empty($data[$dataid]) ? block_exastud_text_to_html($data[$dataid]) : '';
                        }
                        $content .= '<div class="student-input-data"><span class="input-title">'.(array_key_exists('title', $form_input) ? $form_input['title'] : '').':</span> <span>'.$value.'</span></div>';
                    }
                    if (count($inputs) > $countItemsForHidding) {
                        $hasManyInputs = true;
                        $content .= '</div>'; // collapsible inputs wrapper
                    }
                    $content .= '</div>'; // inputs wrapper
                } /*else {
			    $content .= '<small>'.block_exastud_trans('de:Dieses Formular hat keine weiteren Eingabfelder').'</small>';
            }*/
            } else {
                /*if ($dataid == BLOCK_EXASTUD_DATA_ID_BILINGUALES && !block_exastud_is_bilingual_teacher($class->id, null, $classstudent->id, $templateid)) {
                    $content = 'I am not an editor';
                } else {*/
                    $content = '';
                //}
            }

			$row->cells[] = $content;
		} /*elseif ($dataid == BLOCK_EXASTUD_DATA_ID_ADDITIONAL_INFO) {

        }*/ elseif (@$category['type'] == 'select') {
			$row->cells[] = @$category['values'][$data[$dataid]];
		} else {
			$row->cells[] = !empty($data[$dataid]) ? block_exastud_text_to_html($data[$dataid]) : '';
		}
	}

	$table->data[] = $row;
}

echo $output->table($table);

if (!$hasManyInputs) {
    echo '<script>var hideDetailInputsToggler = true;</script>';
}

echo $output->back_button(new moodle_url('review.php', ['courseid' => $courseid, 'openclass' => $classid]));

echo $output->footer();
