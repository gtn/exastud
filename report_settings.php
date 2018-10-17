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
require_once($CFG->dirroot . '/blocks/exastud/lib/edit_form.php');

if (!block_exastud_is_siteadmin()) {
    echo 'Only for site admins!';
    exit;
}

//$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
//$classid = optional_param('classid', 0, PARAM_INT);
$settingsid = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

require_login(1);

//block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);

//$actPeriod = block_exastud_get_active_or_next_period();
//$lastPeriod = block_exastud_get_last_period();
//$classes = block_exastud_get_head_teacher_classes_owner($actPeriod->id);
//$lastPeriodClasses = $lastPeriod ? block_exastud_get_head_teacher_classes_owner($lastPeriod->id) : [];
//$shownClasses = array(); // Which ckasses already shown on the page

$url = '/blocks/exastud/report_settings.php';
$PAGE->set_url($url);
$settingsform = new reportsettings_edit_form(null, [/*'classid' => $classid*/]);

if ($action && ($settingsid > 0 || $action == 'new')) {

    //Form processing and displaying is done here
    if ($settingsform->is_cancelled()) {
        redirect('report_settings.php');
    } else if ($settingsedit = $settingsform->get_data()) {
        require_sesskey();
        foreach ($settingsform->getAllSecondaryFields() as $field) {
            $element_data = array (
                'checked' => $settingsedit->{$field},
                'rows' => (in_array($field, $settingsform->getFieldsWithAdditionalParams()) ? $settingsedit->{$field.'_rows'} : 0),
                'count_in_row' => (in_array($field, $settingsform->getFieldsWithAdditionalParams()) ? $settingsedit->{$field.'_count_in_row'} : 0),
                'maxchars' => (in_array($field, $settingsform->getFieldsWithAdditionalParams()) ? $settingsedit->{$field.'_maxchars'} : 0)

            );
            $settingsedit->{$field} = serialize($element_data);
        }
        if (isset($settingsedit->id) && ($settingsedit->action == 'edit')) {
            $DB->update_record('block_exastudreportsettings', $settingsedit);
            // TODO: log event ?
        } else if ($settingsedit->action == 'new') {
            $newid = $DB->insert_record('block_exastudreportsettings', $settingsedit);
            // TODO: log event ?
        }
        redirect('report_settings.php');
    }

    $reportsetting = new stdClass();
    if ($action == 'edit') {
        require_sesskey();
        $reportsetting = $DB->get_record('block_exastudreportsettings', array('id' => $settingsid));
        $reportsetting->action = 'edit';
        $reportsetting = $settingsform->prepare_formdata($reportsetting);
        //$reportsetting->courseid = $courseid;
        //$reportsetting->classid = $classid;
    }
    /* else if($action == 'delete') {
        require_sesskey();
        $periodData = $DB->get_record('block_exastudperiod', array('id'=>$periodid));
        $DB->delete_records('block_exastudperiod', array('id'=>$periodid));
        \block_exastud\event\period_deleted::log(['objectid' => $periodid, 'other' => ['perioddata' => serialize($periodData)]]);
        redirect('periods.php?courseid=' . $courseid);
    }*/
    else {
        $reportsetting->action = 'new';
        $reportsetting->id = 0;
        //$reportsetting->courseid = $courseid;
        //$reportsetting->classid = $classid;
    }
    $output = block_exastud_get_renderer();
    echo $output->header('report_settings');

    echo $output->heading(block_exastud_get_string('report_settings'));

    echo "<br/>";
    $settingsform->set_data($reportsetting);
    $settingsform->display();

} else {

    $output = block_exastud_get_renderer();
    echo $output->header('report_settings');

    echo $output->heading(block_exastud_get_string('report_settings'));

    $newLink = html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', [
            //'courseid' => $courseid,
            //'classid' => $classid, // TODO: need?
            'action' => 'new',
            'sesskey' => sesskey()
    ]), block_exastud_get_string('report_settings_new'), ['class' => 'btn btn-default']);
    echo $newLink;

    // List of settings
    $reports = block_exastud_get_reportsettings_all(true);

    if ($reports) {
        $table = new html_table();

        $table->head = array(
                block_exastud_get_string('report_settings_setting_id'),
                block_exastud_get_string('report_settings_setting_title'),
                block_exastud_get_string('report_settings_setting_bp'),
                block_exastud_get_string('report_settings_setting_category'),
                block_exastud_get_string('report_settings_setting_template'),
                block_exastud_get_string('report_settings_setting_year'),
                block_exastud_get_string('report_settings_setting_reportdate'),
                block_exastud_get_string('report_settings_setting_studentname'),
                block_exastud_get_string('report_settings_setting_dateofbirth'),
                block_exastud_get_string('report_settings_setting_placeofbirth'),
                block_exastud_get_string('report_settings_setting_learninggroup'),
                block_exastud_get_string('report_settings_setting_class'),
                block_exastud_get_string('report_settings_setting_focus'),
                block_exastud_get_string('report_settings_setting_learnsocialbehavior'),
                block_exastud_get_string('report_settings_setting_subjects'),
                block_exastud_get_string('report_settings_setting_comments'),
                block_exastud_get_string('report_settings_setting_subjectelective'),
                block_exastud_get_string('report_settings_setting_subjectprofile'),
                block_exastud_get_string('report_settings_setting_assessmentproject'),
                block_exastud_get_string('report_settings_setting_ags'),
                '',
        );
        $table->align = array("left");
        // function for getting human value of field
        $setting_marker = function($field, $value) use ($settingsform) {
            $resArr = unserialize($value);
            if ($resArr['checked'] == 0) {
                return block_exastud_get_string('report_settings_no');
            } else {
                $result = block_exastud_get_string('report_settings_yes').'<br />';
                if (in_array($field, $settingsform->getFieldsWithAdditionalParams())) {
                    $result .= block_exastud_get_string('report_settings_countrows', null, $resArr['rows']);
                    $result .= '&nbsp;'.block_exastud_get_string('report_settings_countinrow', null, $resArr['count_in_row']);
                    if ($resArr['maxchars'] > 0) {
                        $result .= '<br />'.block_exastud_get_string('report_settings_maxchars', null, $resArr['maxchars']);
                    }
                    $result = '<small>'.$result.'</small>';
                }
                return $result;
            }
            return '';
        };
        //$classes = block_exastud_get_head_teacher_class('-all-');
        $templateList = block_exastud_get_report_templates('-all-');
        foreach ($reports as $report) {
            $bpData = $DB->get_record('block_exastudbp', ['id' => $report->bpid]);
            $editLink = html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', [
                    //'courseid' => $courseid,
                    //'classid' => $classid, // TODO: need?
                    'action' => 'edit',
                    'id' => $report->id,
                    'sesskey' => sesskey()
            ]), html_writer::tag("img", '', array('src' => 'pix/edit.png')));
            // function for call settings_marker only by name
            $call_setting_marker = function($name) use ($setting_marker, $report){
                return $setting_marker($name, $report->{$name});
            };
            $row = array(
                    $report->id,
                    '<strong>'.$report->title.'</strong>',
                    $bpData->title,
                    $report->category,
                    array_key_exists($report->template, $templateList) ? $templateList[$report->template] : $report->template,
                    $call_setting_marker('year'),
                    $call_setting_marker('report_date'),
                    $call_setting_marker('student_name'),
                    $call_setting_marker('date_of_birth'),
                    $call_setting_marker('place_of_birth'),
                    $call_setting_marker('learning_group'),
                    $call_setting_marker('class'),
                    $call_setting_marker('focus'),
                    $call_setting_marker('learn_social_behavior'),
                    $call_setting_marker('subjects'),
                    $call_setting_marker('comments'),
                    $call_setting_marker('subject_elective'),
                    $call_setting_marker('subject_profile'),
                    $call_setting_marker('assessment_project'),
                    $call_setting_marker('ags'),
                    $editLink,
            );
            $table->data[] = $row;
        }
    }

    echo $output->table($table);
}


echo $output->footer();