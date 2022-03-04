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

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
$curPeriod = block_exastud_get_active_or_next_period();

if (!$classid) {
	$class = new stdClass();
	$class->id = 0;
	$class->title = '';
	$class->userid = $USER->id; // Temporary.
} else {
	$class = block_exastud_get_head_teacher_class($classid);
}
$class->classid = $class->id;
$class->courseid = $courseid;

// TODO: does it need?
// get block_context
//if ($courseid > 0) {
//    $blockinstance = $DB->insert_record('block_instances', $blockinstance);
//    $cm = get_coursemodule_from_instance('wiki', $wiki->id);
//    $context = context_module::instance($cm->id);
//}

$customdata = array('for_siteadmin' => false,
                    'is_classowner' => false);
if ($class->userid && $class->userid != $USER->id && block_exastud_is_siteadmin()) {
    $customdata['for_siteadmin'] = true;
}
if ($class->userid && $class->userid == $USER->id) {
    $customdata['is_classowner'] = true;
}
$customdata['classid'] = $classid;
$classform = new class_edit_form(null, $customdata);

if ($classform->is_cancelled()) {
	redirect('configuration_classes.php?courseid='.$courseid);
} elseif ($classedit = $classform->get_data()) {
	if (!confirm_sesskey()) {
		print_error("badsessionkey", "block_exastud");
	}

	$newclass = new stdClass();
	$newclass->timemodified = time();
	$newclass->title = $classedit->title;
	$newclass->title_forreport = $classedit->title_forreport;
	$newclass->certificate_issue_date = $classedit->certificate_issue_date;
	if(!$classedit->bpid){
	    if (block_exastud_is_bw_active()) {
            $classedit->bpid = 1;
        } else {
            $classedit->bpid = 0;
        }
	}
	$newclass->bpid = $classedit->bpid;

	if ($class->id) {
		$newclass->id = $class->id;
		// admin and class owner can change owner of class
		if ((block_exastud_is_siteadmin() || $class->userid == $USER->id )
		        && $classedit->userid != $class->userid) {
            $newclass->userid = $classedit->userid;
        } else {
            $newclass->userid = $class->userid;
        }
		$DB->update_record('block_exastudclass', $newclass);
        \block_exastud\event\class_updated::log(['objectid' => $newclass->id,
                                                'courseid' => $courseid,
                                                'other' => ['classtitle' => $classedit->title,
                                                            'oldclasstitle' => $class->title]]);
	} else {
		$newclass->userid = $USER->id;
        if (block_exastud_is_siteadmin() && $classedit->userid != $USER->id) {
            $newclass->userid = $classedit->userid;
        }
		$newclass->periodid = $curPeriod->id;
		$newclass->id = $DB->insert_record('block_exastudclass', $newclass);
        // relate categories:
        block_exastud_relate_categories_to_class($newclass->id);

		\block_exastud\event\class_created::log(['objectid' => $newclass->id,
                                                'courseid' => $courseid,
                                                'other' => ['classtitle' => $classedit->title]]);
	}
    // event for changing of owner. It can be for updated class and also for the new class
    if (block_exastud_is_siteadmin() &&
            (($class->id && $classedit->userid != $class->userid) ||
             (!$class->id && $newclass->userid != $USER->id)) ) {
        $newowner = $DB->get_record('user', array('id' => $classedit->userid, 'deleted' => 0));
        $oldowner = $DB->get_record('user', array('id' => ($class->id ? $class->userid : $USER->id), 'deleted' => 0));

        \block_exastud\event\classowner_updated::log(['objectid' => $newclass->id,
                'courseid' => $courseid,
                'relateduserid' => $newclass->userid,
                'other' => ['classtitle' => $classedit->title,
                        'oldownername' => $oldowner->firstname.' '.$oldowner->lastname,
                        'oldownerid' => ($class->id ? $class->userid : $USER->id),
                        'ownername' => $newowner->firstname.' '.$newowner->lastname]]);
    }

    /*file_save_draft_area_files($classedit->class_logo, context_system::instance()->id, 'block_exastud', 'class_logo',
            $class->id, array('subdirs' => 0, 'maxfiles' => 1));*/

	block_exastud_set_class_data($newclass->id, BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID, $classedit->{BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID});
    $liederfields = ['schoollieder', 'groupleader', 'auditleader', 'classleader'];
    foreach ($liederfields as $field) {
        if (isset($classedit->{$field.'_gender'})) {
            block_exastud_set_class_data($newclass->id, $field.'_gender', $classedit->{$field.'_gender'});
        }
        if (isset($classedit->{$field.'_name'})) {
            block_exastud_set_class_data($newclass->id, $field.'_name', $classedit->{$field.'_name'});
        }
    }
    // additional checkboxes
    $checkboxes = ['classteacher_grade_interdisciplinary_competences', 'subjectteacher_grade_interdisciplinary_competences',
                    'classteacher_grade_learn_and_social_behaviour', 'subjectteacher_grade_learn_and_social_behaviour'];
    foreach ($checkboxes as $chname) {
        $v = optional_param($chname, 0, PARAM_INT);
        block_exastud_set_class_data($newclass->id, $chname, $v);
    }

	if ($class->id) {
		// standard zeugnis zurücksetzen (wegen alter version wo es kein standard zeugnis gab)
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

	if ($newclass->userid != $class->userid) {
	    // owner of class was changed
        if (block_exastud_is_siteadmin()) {
            // it did site admin
            redirect('configuration_class.php?courseid='.$courseid.'&classid='.$newclass->id);
        } else if ($class->userid == $USER->id) {
            // it did class owner. he has not access to this class from now
            $a = new stdClass();
            $a->classtitle = $newclass->title;
            $a->owner = fullname(block_exastud_get_user($newclass->userid));
            $message = block_exastud_get_string('classowner_changed_message', null, $a);
            $link = new moodle_url('/message/index.php', ['id' => '0']);
            $a->messagehref = $link->out();
            $message .= ($message ? '<br />' : '').block_exastud_get_string('attention_send_message_to_classteacher', null, $a);
            redirect('configuration_classes.php?courseid='.$courseid,
                    $message,
                    null,
                    \core\output\notification::NOTIFY_INFO);
        }
    } else if ($class->id) {
		redirect('configuration_class_info.php?courseid='.$courseid.'&classid='.$class->id);
	} else /*if (block_exastud_is_siteadmin() && $newclass->userid != $USER->id) {
	    // If the siteamin addes a new class for another user - we have another behaviour. So use this code.

    } else*/ {
		redirect('configuration_class.php?courseid='.$courseid.'&classid='.$newclass->id);
	}
} else { // edit form opened
    // default sertificate issue date - from period issue date
    if (!@$class->certificate_issue_date) {
        $class->certificate_issue_date = $curPeriod->certificate_issue_date;
    }
    if ($class->id) {
        /*$draftitemid = file_get_submitted_draft_itemid('class_logo');
        file_prepare_draft_area($draftitemid, context_system::instance()->id, 'block_exastud', 'class_logo', $class->id,
                array('subdirs' => 0, 'maxfiles' => 1));
        $class->class_logo = $draftitemid;*/
    }
}



$classform->set_data((array)$class + (array)block_exastud_get_class_data($class->id));

$url = "/blocks/exastud/configuration_class_info.php?courseid=".$courseid.'&classid='.$classid;
$PAGE->set_url($url);
$output = block_exastud_get_renderer();
echo $output->header(['configuration_classes', 'class_info'], ['class' => ($class && $class->id) ? $class : null]);

if ($class && $class->id) {
	$classform->display();

	$buttons = '';

    /*if ($class->userid == $USER->id) {
        $img = '<img src="'.$CFG->wwwroot.'/blocks/exastud/pix/backup.png" title="'.block_exastud_get_string('export_class').'"/>';
        $buttons .= $output->link_button('export_class.php?courseid='.$COURSE->id.'&classid='.$class->id,
                $img.'&nbsp;&nbsp;&nbsp;'.block_exastud_get_string('export_class'), ['class' => 'btn btn-default']);
    }*/

    // disabled now. button is in the ist view
	/*if (!block_exastud_get_class_students($class->id) || block_exastud_is_siteadmin()) {
        $buttons .= $output->link_button('configuration_class.php?courseid='.$COURSE->id.'&action=delete&classid='.$class->id.'&confirm=1',
			block_exastud_get_string('delete'),
			['exa-confirm' => block_exastud_get_string('delete_confirmation', null, $class->title),
             'class' => 'btn btn-danger btn-toRight']);
	} else {
        $buttons .= $output->link_button('configuration_class.php?courseid='.$courseid.'&action=to_delete&classid='.$class->id.'&confirm=0',
                block_exastud_get_string('class_delete'),
                ['title' => block_exastud_get_string('class_delete'),
                 'class' => 'btn btn-danger btn-toRight']
        );
		//$deleteButton = html_writer::empty_tag('input', [
		//	'type' => 'button',
		//	'onclick' => "alert(".json_encode(block_exastud_get_string('delete_class_only_without_users')).")",
		//	'value' => block_exastud_get_string('class_delete'),
        //    'class' => 'btn btn-danger'
		//]);
	}*/

    echo html_writer::div($buttons, 'additional_buttons');

} else if (!block_exastud_is_siteadmin()) {
	echo $output->heading(block_exastud_get_string('add_class'));

	$classform->display();
} else {
    echo block_exastud_get_string('attention_admin_cannot_be_classteacher');
}

if ($class->id) {
	$templates = \block_exastud\print_templates::get_class_available_print_templates($class);
} else {
	$templates = \block_exastud\print_templates::get_all_default_print_templates();
}

if (block_exastud_is_bw_active()) {
    $bps = $DB->get_records('block_exastudbp', [], 'sorting');
} else {
    $bps = array();
}
$templates_by_bp = [];
$tempbp = (object)['id' => 0];
$templates_by_bp[''] = \block_exastud\print_templates::get_bp_available_print_templates($tempbp);
if (block_exastud_is_bw_active()) {
    foreach ($bps as $bp) {
	    $templates_by_bp[$bp->id] = \block_exastud\print_templates::get_bp_available_print_templates($bp);
    }
} else {
    // all templates if no BW
    $templates_by_bp[''] = \block_exastud\print_templates::get_bp_available_print_templates(null);
}
$templates_by_bp = block_exastud_clean_templatelist_for_classconfiguration($templates_by_bp, 'class');
?>
	<script>
			var templates_by_bp = <?php echo json_encode($templates_by_bp); ?>;
			var isBW = <?php echo (block_exastud_is_bw_active() ? 'true;' : 'false;')?>

			function populate_select(name) {
				var $input = $('input,select').filter('[name=' + name + ']');
                if ($input.attr('type') == 'hidden') {
                    return false;
                }
				var val = $input.val();
				var $select = $('<select/>', {name: name, class: 'custom-select'});
				$select.attr('data-exastudmessage', '<?php echo block_exastud_get_string('attention_template_will_change'); ?>');

				if ($('select[name="bpid"]').length) {
                    var bpselected = $('select[name="bpid"]').val();
                } else {
                    var bpselected = $('input[name="bpid"]').val();
                }
				if (bpselected == 0) {
                    bpselected = '';
                }
                if (!isBW) {
                    bpselected = ''; // if no BW = always all reports
                }
				$.each(templates_by_bp[bpselected], function (id, title) {
					$select.append($('<option/>', {
						value: id,
						text: title
					}));
				});

				$input.replaceWith($select);
				// don't use $select.val() because if the value does not exist (in chrome) the select is empty
				$select.find('option[value="'+val+'"]').prop('selected', true);

				$select.change(function () {
					if (!confirm('Soll das Zeugnisformular, das für die Schüler erzeugt wird, bei allen geändert werden?')) {
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
