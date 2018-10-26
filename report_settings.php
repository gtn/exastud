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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$reportsetting = new stdClass();
$settingsform = new reportsettings_edit_form(null, [/*'classid' => $classid*/]);

if ($action && ($settingsid > 0 || $action == 'new')) {

    //Form processing and displaying is done here
    if ($settingsform->is_cancelled()) {
        redirect('report_settings.php');
    } else if ($settingsedit = $settingsform->get_data()) {
        require_sesskey();
        $settingsedit = block_exastud_report_templates_prepare_serialized_data($settingsform, $settingsedit);
        if (isset($settingsedit->id) && ($settingsedit->action == 'edit')) {
            $DB->update_record('block_exastudreportsettings', $settingsedit);
            // TODO: log event ?
        } else if ($settingsedit->action == 'new') {
            $newid = $DB->insert_record('block_exastudreportsettings', $settingsedit);
            // TODO: log event ?
        }
        redirect('report_settings.php');
    } else {
        // do not validated data
        if ($settingsform->is_submitted()) {
            $settingsedit = $settingsform->get_submitted_data();
            $settingsedit = block_exastud_report_templates_prepare_serialized_data($settingsform, $settingsedit);
            $reportsetting = new stdClass();
            $reportsetting->action = 'edit';
            $reportsetting = $settingsform->prepare_formdata($settingsedit);
        }
    }

    if ($action == 'edit' && !$settingsform->is_submitted()) {
        require_sesskey();
        $reportsetting = $DB->get_record('block_exastudreportsettings', array('id' => $settingsid));
        $reportsetting->action = 'edit';
        $reportsetting = $settingsform->prepare_formdata($reportsetting);
        //if ($reportsetting->additional_params) {
        //    $settingsform->setAdditionalData(unserialize($reportsetting->additional_params));
        //}
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
    else if (!$settingsform->is_submitted()) {
        $reportsetting->action = 'new';
        $reportsetting->id = 0;
        //$reportsetting->courseid = $courseid;
        //$reportsetting->classid = $classid;
    }
    $output = block_exastud_get_renderer();
    echo $output->header('report_settings');

    echo $output->heading(block_exastud_get_string('report_settings'));

    echo "<br/>";
    //echo '<pre>';print_r($reportsetting);exit;
    $settingsform->set_data($reportsetting);
    $settingsform->display(true);

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
    echo $newLink.'<br>';

    // List of settings
    $reports = block_exastud_get_reportsettings_all(true);

    if ($reports) {
        $table = new html_table();
        $table->attributes['class'] .= 'exa_table small';

        $table->head = array(
                '',
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
                block_exastud_get_string('report_settings_setting_additional_params'),
        );
        $table->align = array("left");
        // function for getting human value of field
        $setting_marker = function($field, $value) use ($settingsform) {
            $resArr = unserialize($value);
            if (empty($resArr['checked']) || intval($resArr['checked']) == 0) {
                return block_exastud_get_string('report_settings_no');
            } else {
                $result = block_exastud_get_string('report_settings_yes').'<br />';
                if (!empty($resArr['type'])) {
                    switch ($resArr['type']) {
                        case 'textarea':
                            $result .= block_exastud_get_string('report_settings_countrows', null, $resArr['rows']);
                            $result .= '&nbsp;'.block_exastud_get_string('report_settings_countinrow', null, $resArr['count_in_row']);
                            $result .= '<br />'.block_exastud_get_string('report_settings_maxchars', null, $resArr['rows'] * $resArr['count_in_row']);
                            $result = '<small>'.$result.'</small>';
                            break;
                        case 'text':
                            $result = '<small>'.$result.' (text)</small>';
                            break;
                    }
                }
                return $result;
            }
            return '';
        };
        $templateList = block_exastud_get_template_files();
        foreach ($reports as $report) {
            $bpData = $DB->get_record('block_exastudbp', ['id' => $report->bpid]);
            $editLink = html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', [
                    //'courseid' => $courseid,
                    //'classid' => $classid, // TODO: need?
                    'action' => 'edit',
                    'id' => $report->id,
                    'sesskey' => sesskey()
            ]), html_writer::tag("img", '', array('src' => 'pix/edit.png')), array('title' => 'id: '.$report->id));
            // function for call settings_marker only by name
            $call_setting_marker = function($name) use ($setting_marker, $report){
                return $setting_marker($name, $report->{$name});
            };
            $row = array(
                    $editLink. ' :'.$report->id,
                    '<strong>'.$report->title.'</strong>',
                    $bpData ? $bpData->title : '',
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
                    block_exastud_get_reportsettings_additional_description($report)
            );
            $table->data[] = $row;
        }
    }

    if (!empty($table)) {
        echo $output->table($table);
    } else {
        echo 'No any template in DB';
    }
}

// testing import default templates
//block_exastud_fill_reportsettingstable();

echo $output->footer();

function block_exastud_get_reportsettings_additional_description($report) {
    $data = unserialize($report->additional_params);
    $content = '';
    if ($data && is_array($data) && count($data) > 0) {
        $content .= '<ul class="exastud-additional-params-shortlist">';
        foreach ($data as $key => $reportData) {
            $content .= '<li><strong>${'.$reportData['key'].'}:</strong> '.'<i>('.$reportData['type'].')</i> '.$reportData['title'].'</li>';
        }
        $content .= '</ul>';
    }
    return $content;
}

function block_exastud_report_templates_prepare_serialized_data($settingsform, $settingsedit) {
    foreach ($settingsform->getAllSecondaryFields() as $field) {
        if (in_array($field, $settingsform->getFieldsWithAdditionalParams())) {
            $element_data = array(
                    'key' => @$settingsedit->{$field.'_key'} ? $settingsedit->{$field.'_key'} : $field,
                    'title' => @$settingsedit->{$field.'_title'} ? $settingsedit->{$field.'_title'} :
                            block_exastud_get_string('report_settings_setting_'.str_replace('_', '', $field)),
                    'checked' => $settingsedit->{$field}
            );
            if (!empty($settingsedit->{$field.'_type'})) {
                $element_data['type'] = $settingsedit->{$field.'_type'};
            } else {
                $element_data['type'] = 'textarea';
            }
            switch ($element_data['type']) {
                case 'textarea':
                    $element_data['rows'] = (isset($settingsedit->{$field.'_rows'}) && $settingsedit->{$field.'_rows'} > 0 ?
                            $settingsedit->{$field.'_rows'} : 8);
                    $element_data['count_in_row'] =
                            (isset($settingsedit->{$field.'_count_in_row'}) && $settingsedit->{$field.'_count_in_row'} > 0 ?
                                    $settingsedit->{$field.'_count_in_row'} : 45);
                    break;
                case 'select':
                    // work with GP, because mform does not know about new options
                    $selectbox_optionskey = optional_param_array($field.'_selectboxvalues_key', '', PARAM_RAW);
                    if (!empty($selectbox_optionskey) && count($selectbox_optionskey) > 0) {
                        $selectbox_optionsvalue = optional_param_array($field.'_selectboxvalues_value', '', PARAM_RAW);
                        foreach ($selectbox_optionskey as $vIndex => $key) {
                            $element_data['values'][$key] = trim($selectbox_optionsvalue[$vIndex]);
                        };
                    }
                    break;
            }
        } else {
            $element_data = array(
                    'checked' => $settingsedit->{$field}
            );
        }
        $settingsedit->{$field} = serialize($element_data);
    }

    // additional params (dynamic)
    $aparams_GP = optional_param_array('additional_params', '', PARAM_INT);
    $additional_params = array();
    if (count($aparams_GP) > 0) {
        $aparams_titles = optional_param_array('additional_params_title', '', PARAM_RAW);
        $aparams_keys = optional_param_array('additional_params_key', '', PARAM_RAW);
        $aparams_types = optional_param_array('additional_params_type', '', PARAM_RAW);
        $aparams_rows = optional_param_array('additional_params_rows', '', PARAM_INT);
        $aparams_count_in_rows = optional_param_array('additional_params_count_in_row', '', PARAM_INT);
        $aparams_selectboxvalues_key = block_exastud_optional_param_array('additional_params_selectboxvalues_key', '', PARAM_RAW);
        $aparams_selectboxvalues_value = block_exastud_optional_param_array('additional_params_selectboxvalues_value', '', PARAM_RAW);
        foreach ($aparams_GP as $pIndex => $checked) {
            if ($pIndex > -1) {
                if ($aparams_keys[$pIndex] != '') {
                    $additional_params[$aparams_keys[$pIndex]] = array(
                            'key' => $aparams_keys[$pIndex],
                            'title' => $aparams_titles[$pIndex],
                            'checked' => '1',
                            'type' => $aparams_types[$pIndex]
                    );
                    switch ($additional_params[$aparams_keys[$pIndex]]['type']) {
                        case 'textarea':
                            $additional_params[$aparams_keys[$pIndex]]['rows'] =
                                    (isset($aparams_rows[$pIndex]) && $aparams_rows[$pIndex] > 0 ?
                                            $aparams_rows[$pIndex] : 8);
                            $additional_params[$aparams_keys[$pIndex]]['count_in_row'] =
                                    (isset($aparams_count_in_rows[$pIndex]) &&
                                    $aparams_count_in_rows[$pIndex] > 0 ?
                                            $aparams_count_in_rows[$pIndex] : 45);
                            break;
                        case 'select':
                            //echo $pIndex.'---<br>keys:<br><pre>'; print_r($aparams_selectboxvalues_key[$pIndex]); echo '</pre>'; // !!!!!!!!!! delete it
                            //echo $pIndex.'---<br>values:<br><pre>'; print_r($aparams_selectboxvalues_value[$pIndex]); echo '</pre>'; // !!!!!!!!!! delete it
                            if (array_key_exists($pIndex, $aparams_selectboxvalues_key) && count($aparams_selectboxvalues_key[$pIndex]) > 0) {
                                foreach ($aparams_selectboxvalues_key[$pIndex] as $vIndex => $key) {
                                    $additional_params[$aparams_keys[$pIndex]]['values'][$key] =
                                            trim($aparams_selectboxvalues_value[$pIndex][$vIndex]);
                                };
                            }
                            break;
                    }
                }
            }
        }
    }

    if (count($additional_params) > 0) {
        $settingsedit->additional_params = serialize($additional_params);
    } else {
        $settingsedit->additional_params = '';
    }

    return $settingsedit;
}