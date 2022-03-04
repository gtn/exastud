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
require_once($CFG->dirroot . '/blocks/exastud/lib/reports_lib.php');

if (!block_exastud_is_siteadmin()) {
    echo 'Only for site admins!';
    exit;
}

// for development!!!
$tokenparam = optional_param('token', 0, PARAM_INT);
if (block_exastud_is_bw_active() && $tokenparam != 12345) {
    echo block_exastud_get_string('temporary_hidden');
    exit;
}

//$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
//$classid = optional_param('classid', 0, PARAM_INT);
$settingsid = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

block_exastud_require_login(1);

//block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);

//$actPeriod = block_exastud_get_active_or_next_period();
//$lastPeriod = block_exastud_get_last_period();
//$classes = block_exastud_get_head_teacher_classes_owner($actPeriod->id);
//$lastPeriodClasses = $lastPeriod ? block_exastud_get_head_teacher_classes_owner($lastPeriod->id) : [];
//$shownClasses = array(); // Which ckasses already shown on the page

$uploadFolder = BLOCK_EXASTUD_TEMPLATE_DIR.'/upload/';
if (!file_exists($uploadFolder) || !is_dir($uploadFolder)) {
    mkdir($uploadFolder, 0755);
}

$url = '/blocks/exastud/report_settings.php'.($tokenparam ? '?token='.$tokenparam : '');
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin'); // Needed for admin menu block
block_exastud_custom_breadcrumb($PAGE);

$showfulltable = false;

// filter form
$filterform = new report_settings_filter_form(null, ['for_reseting' => ($action == 'reset_default' ? 1 : 0)]);
$filterdata = new stdClass();
if ($filterform->is_cancelled()) {
    $SESSION->reports_filter = null;
    $params = array();
    if ($tokenparam) {
        $params['token'] = $tokenparam;
    }
    if ($action) {
        $params['action'] = $action;
    }
    redirect(new moodle_url('/blocks/exastud/report_settings.php', $params));
} else if ($newfilter = $filterform->get_data()) {
    if (!empty($newfilter->gofilter)) {
        $SESSION->reports_filter = serialize($newfilter);
    }
    if (!empty($newfilter->clearfilter)) {
        $SESSION->reports_filter = null;
    }
    $params = array();
    if ($tokenparam) {
        $params['token'] = $tokenparam;
    }
    if ($action) {
        $params['action'] = $action;
    }
    redirect(new moodle_url('/blocks/exastud/report_settings.php', $params));
}
if (!empty($SESSION->reports_filter)) {
    $filterdata = unserialize($SESSION->reports_filter);
    if (!empty($filterdata->showfulltable) && $filterdata->showfulltable) {
        $showfulltable = true;
    }
}
if ($tokenparam) {
    $filterdata->token = $tokenparam;
}
if ($action) {
    $filterdata->action = $action;
}
$filterform->set_data($filterdata);

$categories_withoutcross_competences = array('Abgang', 'Abschluss');
$attr = new stdClass();
$attr->categorytitles = '&#8221;'.implode('&#8220; or &#8221;', $categories_withoutcross_competences).'&#8220;';
$categoryinfo_text = block_exastud_get_string('info_category_without_cross_competences', null, $attr);

// report settings form
$reportsetting = new stdClass();
$settingsform = new reportsettings_edit_form(null, [
        //'classid' => $classid,
        'category_infomessage' => $categoryinfo_text,
        'report_id' => $settingsid
], 'post', '', ['class' => 'exastud-reports-form']);


if ($action && (($settingsid > 0 && $action == 'edit') || $action == 'new')) {

    //Form processing and displaying is done here
    if ($settingsform->is_cancelled()) {
        redirect('report_settings.php'.($tokenparam ? '?token='.$tokenparam : ''));
    } else if ($settingsedit = $settingsform->get_data()) {
        require_sesskey();
        $settingsedit = block_exastud_report_templates_prepare_serialized_data($settingsform, $settingsedit);
        if (isset($settingsedit->id) && ($settingsedit->action == 'edit')) {
            $DB->update_record('block_exastudreportsettings', $settingsedit);
            // TODO: log event ?
        } else if ($settingsedit->action == 'new') {
            // insert new reports with id > 1000. Because standard reports can be added manually to the code with static id
            if ($records = $DB->get_records('block_exastudreportsettings', null, 'id DESC', 'id', 0, 1)) {
                $lastid = key($records) + 1;
                if ($lastid < 1000) {
                    $lastid = 1000;
                }
            } else {
                $lastid = 1000;
            }
            //$newid = $DB->insert_record('block_exastudreportsettings', $settingsedit);
            $tablecolumns = $DB->get_columns('block_exastudreportsettings');
            $cleanedReport = array();
            $settingseditArray = (array)$settingsedit;
            foreach ($settingseditArray as $field => $value) {
                if (!isset($tablecolumns[$field])) {
                    continue;
                }
                $column = $tablecolumns[$field];
                // normalise value for DB
                if (is_bool($value)) {
                    $value = (int)$value;
                } else if ($value === '') {
                    if ($column->meta_type == 'I' or $column->meta_type == 'F' or $column->meta_type == 'N') {
                        $value = 0; // prevent '' problems in numeric fields
                    }
                } else if (is_float($value) and ($column->meta_type == 'C' or $column->meta_type == 'X')) {
                    $value = "$value";
                }
                $cleanedReport[$field] = $value;
            }
            $cleanedReport['id'] = $lastid;
            $newid = $DB->insert_record_raw('block_exastudreportsettings', $cleanedReport, true, false, true);
            // TODO: log event ?
        }
        redirect('report_settings.php'.($tokenparam ? '?token='.$tokenparam : ''));
    } else {
        // come not validated data
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
    echo $output->header(['report_settings'], ['content_title' => block_exastud_get_string('pluginname')], true);

    echo $output->heading(block_exastud_get_string('report_settings'));

    echo "<br/>";
    if ($tokenparam) {
        $reportsetting->token = $tokenparam;
    }
    
    $settingsform->set_data($reportsetting);
    $settingsform->display(true);

} else if ($action && $action == 'reset_default') {
    // reset default templates
    if (block_exastud_is_bw_active()) {
        $defaulttemplates = block_exastud_get_default_templates(null, false);
    } else {
        $defaulttemplates = block_exastud_get_default_templates(null, true);
    }
    $output = block_exastud_get_renderer();
    $doit = optional_param('doit', 0, PARAM_INT);
    if ($doit == '1') {
        if ($reinstall = optional_param('reinstall', null, PARAM_TEXT)) {
            // reset as during installation
            $allDefaultIds = array_map(function($o) {return $o['id'];}, $defaulttemplates);
            $allDefaultIds = array_values($allDefaultIds);
            $templateids = $allDefaultIds;
        } else {
            // reset selected
            $templateids = optional_param_array('templateid', null, PARAM_INT);
        }
        if ($templateids && is_array($templateids)) {
            foreach ($templateids as $tid) {
                block_exastud_fill_reportsettingstable($tid, true);
            }
        }
        redirect(new moodle_url('/blocks/exastud/report_settings.php', ['sesskey' => sesskey(), 'token' => $tokenparam]));
    }
    echo $output->header(['report_settings'], ['content_title' => block_exastud_get_string('pluginname')], true);
    $content = '';
    // add filter form
    $content .= $filterform->display();
    $filter = array();
    if (!empty($SESSION->reports_filter)) {
        $filter = (array)unserialize($SESSION->reports_filter);
    }

    $content .= $output->notification(block_exastud_get_string('reset_report_templates_description'), 'info');
    $formcontent = '';
    $formcontent .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'doit', 'value' => 1]);
    $formcontent .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'action', 'value' => 'reset_default']);
    if ($tokenparam) {
        $formcontent .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'token', 'value' => $tokenparam]);
    }
    $table = new html_table();
    $table->head = array(
            'id',
            '<span class="small"><a href="#" id="exastud-reset-template-selectall" data-curr="0">'.block_exastud_get_string('report_selectdeselect_all').'</a></span>',
            block_exastud_get_string('report_settings_setting_title'),
            block_exastud_get_string('report_settings_setting_template')
    );
    $rows = array();
    foreach ($defaulttemplates as $template) {
        $currenttemplate = $DB->get_record('block_exastudreportsettings', ['id' => $template['id']]);
        // filter template - filter by CURRENT parameters (not default)
        if (count($filter) > 0) {
            foreach ($filter as $f => $value) {
                switch ($f) {
                    case 'search':
                        if (trim($value) != '') {
                            if (strpos($currenttemplate->title, trim($value)) === false) {
                                continue 3;
                            }
                        }
                        break;
                    case 'bpid':
                        if (is_numeric($value) && $value >= 0) {
                            if ($currenttemplate->bpid != $value) {
                                continue 3;
                            }
                        }
                        break;
                    case 'category':
                        if ($value == '--notselected--') {
                            // no filter
                        } else if (trim($value) == '') {
                            if ($currenttemplate->category != '') {
                                continue 3;
                            }
                        } else {
                            if ($currenttemplate->category != trim($value)) {
                                continue 3;
                            }
                        }
                        break;
                }
            }
        }
        $row = new html_table_row();
        $row->cells[] = $template['id'];
        $addmessage = '';
        $row->cells[] = html_writer::checkbox('templateid[]', $template['id'], false, '', ['id' => 'template_'.$template['id'], 'class' => 'template-id']);
        if (!$currenttemplate) {
            $addmessage .= '<span class="exastud-additional-text">'.block_exastud_get_string('report_setting_willbe_added').'</span>';
        } else if ($currenttemplate && $currenttemplate->title != $template['name']) {
            $addmessage .= '<span class="exastud-additional-text">'.block_exastud_get_string('report_setting_current_title').': '.$currenttemplate->title.'</span>';
        }
        $row->cells[] = html_writer::tag('label', $template['name'].$addmessage, ['for' => 'template_'.$template['id']]);
        $addmessage = '';
        if ($currenttemplate && $currenttemplate->template != $template['file']) {
            $addmessage .= '<span class="exastud-additional-text">'.block_exastud_get_string('report_setting_current_file').': '.$currenttemplate->template.'</span>';
        }
        $row->cells[] = $template['file'].$addmessage;
        $rows[] = $row;
    }
    $table->data = $rows;
    $formcontent .= html_writer::table($table);
    $formcontent .= html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', ['sesskey' => sesskey(), 'token' => ($tokenparam ? $tokenparam : '')]),
            block_exastud_get_string('back'),
            ['class' => 'btn btn-default']);
    $formcontent .= '&nbsp;&nbsp;&nbsp;'.html_writer::tag('button', block_exastud_get_string('reset_report_selected_templates'), [
        'type' => 'submit',
        'class' => 'btn btn-danger',
    ]);
    $formcontent .= '&nbsp;&nbsp;&nbsp;'.html_writer::tag('button', block_exastud_get_string('reinstall_report_templates'), [
        'type' => 'submit',
        'class' => 'btn btn-danger',
        'name' => 'reinstall',
        'value' => 'reinstall'
    ]);
    $content .= html_writer::tag('form', $formcontent, [
        'id' => 'form-templatelist',
        'method' => 'post'
    ]);
    echo $content;

} elseif ($action && $action == 'delete') { // DELETE
    $reportid = required_param('id', PARAM_INT);
    if (optional_param('doit', 0, PARAM_INT)) {
        $DB->delete_records('block_exastudreportsettings', ['id' => $reportid]);
        redirect($PAGE->url);
    }
    $output = block_exastud_get_renderer();
    echo $output->header(['report_settings'], ['content_title' => block_exastud_get_string('pluginname')], true);
    echo $output->heading(block_exastud_get_string('report_settings'));
    $report = $DB->get_record('block_exastudreportsettings', ['id' => $reportid]);
    echo $OUTPUT->box(block_exastud_get_string('report_delete_confirm_message', null, $report->title), 'alert alert-warning');
    $formcontent = '';
    $formcontent .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'doit', 'value' => 1]);
    $formcontent .= html_writer::tag('button', block_exastud_get_string('back'), ['type' => 'button', 'class' => 'btn btn-default', 'onclick' => 'location.href="'.$PAGE->url.'";']);
    $formcontent .= '&nbsp;'.html_writer::tag('button', block_exastud_get_string('delete'), ['type' => 'submit', 'class' => 'btn btn-danger']);
    echo html_writer::tag('form', $formcontent, [
            'method' => 'post'
    ]);
} elseif ($action && $action == 'copy') { // COPY
    $reportid = required_param('id', PARAM_INT);
    $sourceReport = $DB->get_record('block_exastudreportsettings', ['id' => $reportid]);
    $newReport = clone($sourceReport);
    $newReport->hidden = 1;
    $newReport->source_id = 0;
    $newReport->source = '';
    $newReport->title = block_exastud_get_string('report_settings_copy_newtitle', null, $sourceReport);
    $newReport->id = $DB->insert_record('block_exastudreportsettings', $newReport);
    $aParam = (object) array(
        'newtitle' => $newReport->title,
        'newid' => $newReport->id,
        'sourcetitle' => $sourceReport->title,
        'sourceid' => $sourceReport->id
    );
    $message = block_exastud_get_string('report_settings_copy_done', null, $aParam);
    redirect($PAGE->url, $message, null, \core\output\notification::NOTIFY_INFO);
} else if ($action && $action == 'export') {
    // EXPORT
    if (optional_param('doit', 0, PARAM_INT)) {
        $templateids = optional_param_array('templateid', null, PARAM_INT);
        if ($templateids && is_array($templateids)) {
            $withFiles = optional_param('withfiles', 0, PARAM_INT);
            block_exastud_export_reports($templateids, $withFiles);
        }
        $params = array('sesskey' => sesskey());
        if ($tokenparam) {
            $params['token'] = $tokenparam;
        }
        redirect(new moodle_url('/blocks/exastud/report_settings.php', $params));
    }
    $output = block_exastud_get_renderer();
    echo $output->header(['report_settings'], ['content_title' => block_exastud_get_string('pluginname')], true);
    echo $output->heading(block_exastud_get_string('report_settings'));
    $content = '';
    $formcontent = '';
    $table = new html_table();
    $table->head = array(
            'id',
            '<span class="small"><a href="#" id="exastud-reset-template-selectall" data-curr="0">'.block_exastud_get_string('report_selectdeselect_all').'</a></span>',
            block_exastud_get_string('report_settings_setting_title'),
            block_exastud_get_string('report_settings_setting_template')
    );
    $mytemplates = block_exastud_templates_get_templates();
    $rows = array();
    foreach ($mytemplates as $template) {
        $row = new html_table_row();
        $row->cells[] = $template->id;
        //$addmessage = '';
        $row->cells[] = html_writer::checkbox('templateid[]', $template->id, false, '', ['id' => 'template_'.$template->id, 'class' => 'template-id']);
        $row->cells[] = html_writer::tag('label', $template->title, ['for' => 'template_'.$template->id]);
        //$addmessage = '';
        $row->cells[] = $template->template; // filename
        $rows[] = $row;
    }
    $table->data = $rows;
    $formcontent .= html_writer::table($table);
    $formcontent .= html_writer::checkbox('withfiles', 1, false, '&nbsp'.block_exastud_get_string('report_export_with_files')).'<br>';
    $formcontent .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'doit', 'value' => 1]);
    if ($tokenparam) {
        $formcontent .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'token', 'value' => $tokenparam]);
    }
    $formcontent .= html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', ['sesskey' => sesskey(), 'token' => ($tokenparam ? $tokenparam : '')]),
            block_exastud_get_string('back'),
            ['class' => 'btn btn-default']);
    $formcontent .= '&nbsp;&nbsp;&nbsp;'.html_writer::tag('button', block_exastud_get_string('report_export_selected_templates'), [
                    'type' => 'submit',
                    'class' => 'btn btn-info',
            ]);
    $content .= html_writer::tag('form', $formcontent, [
            'id' => 'form-templatelist',
            'method' => 'post'
    ]);
    echo $content;
} else if ($action && $action == 'import') {
    // IMPORT
    $returnurl = new moodle_url('/blocks/exastud/report_settings.php', ['sesskey' => sesskey(), 'token' => ($tokenparam ? $tokenparam : '')]);
    $content = '';

    $import_form = new block_exastud_import_report_form();

    if ($import_form->is_cancelled()) {
        redirect($returnurl);
    } else if ($import_data = $import_form->get_data()) {
        $updatereports = (@$import_data->updatereports ? true : false);
        $updatefiles = (@$import_data->updatefiles ? true : false);
        $tmpFile = $import_form->save_temp_file('datafile');
        $zip = new ZipArchive;
        $res = $zip->open($tmpFile);
        $message = '';
        $mesageFromImport = function ($resArr) {
            $message = '';
            foreach ($resArr as $resKey => $reports) {
                if (count($reports)) {
                    $message .= '<p>'.block_exastud_get_string('report_import_'.$resKey.'_list');
                    $message .= '<ul>';
                    foreach ($reports as $rid => $rtitle) {
                        $message .= '<li>'.$rtitle.'</li>';
                    }
                    $message .= '</ul>';
                }
            }
            return $message;
        };
        if ($res === TRUE) {
            // is this .zip
            $tmpdir = make_temp_directory('exastud_'.time());
            //$content .= 'ZIP';
            $zip->extractTo($tmpdir);
            // main xml file
            $mainXml = $tmpdir.'/reports.xml';
            if (file_exists($mainXml)) {
                $fileData = file_get_contents($mainXml);
                $importRes = block_exastud_import_report_xml($fileData, $updatereports, $updatefiles);
                // update files from zip
                if (array_key_exists('filelist', $importRes)) {
                    $filelist = $importRes['filelist'];
                    unset($importRes['filelist']);
                    foreach ($filelist as $file) {
                        $copyTo = '';
                        // docx or dotx
                        $fullPath = $tmpdir.'/sources/'.$file;
                        $exts = array('dotx', 'docx');
                        $exists = false;
                        foreach ($exts as $ext) {
                            if (file_exists($fullPath.'.'.$ext)) {
                                $fullPath = $fullPath.'.'.$ext;
                                $copyTo = BLOCK_EXASTUD_TEMPLATE_DIR.'/'.$file.'.'.$ext;
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists || !$copyTo) {
                            continue;
                        }
                        if ($updatefiles // overwrite
                            || (!$updatefiles && !file_exists($copyTo)) // only if does not exist
                        ) {
                            copy($fullPath, $copyTo);
                        }
                    }
                }
                $message .= $mesageFromImport($importRes);
            }
            $zip->close();
            remove_dir($tmpdir);
        } else {
            // it is not a zip
            $fileData = $import_form->get_file_content('datafile');
            $importRes = block_exastud_import_report_xml($fileData, $updatereports, $updatefiles);
            $message .= $mesageFromImport($importRes);
        }
        if ($message) {
            redirect($returnurl, $message, null, \core\output\notification::NOTIFY_INFO);
        } else {
            redirect($returnurl);
        }
    }
    $output = block_exastud_get_renderer();
    echo $output->header(['report_settings'], ['content_title' => block_exastud_get_string('pluginname')], true);
    echo $output->heading(block_exastud_get_string('report_settings'));

    $content .= $import_form->display();

    echo $content;
} else {
    // template list
    $output = block_exastud_get_renderer();
    echo $output->header(['report_settings'], ['content_title' => block_exastud_get_string('pluginname')], true);

    echo $output->heading(block_exastud_get_string('report_settings'));
    $content = '';

    if ($settingsid > 0) {
        if ($action == 'hide') {
            $DB->execute('UPDATE {block_exastudreportsettings} SET hidden = 1 WHERE id = ? ', [$settingsid]);
        }
        if ($action == 'unhide') {
            $DB->execute('UPDATE {block_exastudreportsettings} SET hidden = 0 WHERE id = ? ', [$settingsid]);
        }
    }

    // add filter form
    $content .= $filterform->display();

    $params = [
            'action' => 'new',
            'sesskey' => sesskey()
    ];
    if ($tokenparam) {
        $params['token'] = $tokenparam;
    }
    $newLink = html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', $params),
                                block_exastud_get_string('report_settings_new'), ['class' => 'btn btn-default']);
    $content .= $newLink.'<br>';

    // List of settings
    $filter = array();
    if (!empty($SESSION->reports_filter)) {
        $filter = (array)unserialize($SESSION->reports_filter);
    }
    $reports = block_exastud_get_reportsettings_all(true, $filter);

    if ($reports) {
        $table = new html_table();
        $table->attributes['class'] .= 'exa_table small';

        $infoicon = '<img class=""
                        src="'.$CFG->wwwroot.'/blocks/exastud/pix/info.png"                          
                        title="'.$categoryinfo_text.'" />';
        $commenticon = '<img class=""
                        src="'.$CFG->wwwroot.'/blocks/exastud/pix/comment.png"                          
                        title="'.$categoryinfo_text.'" />';

        if (block_exastud_is_bw_active()) {
            $table->head = array(
                    '',
                    '',
                    block_exastud_get_string('report_settings_setting_title'),
                    block_exastud_get_string('report_settings_setting_bp'),
                    block_exastud_get_string('report_settings_setting_category').html_writer::span($infoicon, 'info-icon'),
                    block_exastud_get_string('report_settings_setting_template')
            );
            if ($showfulltable) {
                $table->head = array_merge($table->head, array(
                        block_exastud_get_string('report_settings_setting_grades'),
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
                        block_exastud_get_string('report_settings_setting_projektthema'),
                        block_exastud_get_string('report_settings_setting_ags'),
                        block_exastud_get_string('report_settings_setting_additional_params'),
                ));
            }
        } else {
            $table->head = array(
                    '',
                    '',
                    block_exastud_get_string('report_settings_setting_title'),
                    block_exastud_get_string('report_settings_setting_template')
            );
            if ($showfulltable) {
                $table->head = array_merge($table->head, array(
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
                        block_exastud_get_string('report_settings_setting_projektthema'),
                        block_exastud_get_string('report_settings_setting_additional_params'),
                ));
            }
        }
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
                            $result .= '<br />'.block_exastud_get_string('report_settings_maxchars', null, $resArr['maxchars']);
                            $result = '<small>'.$result.'</small>';
                            break;
                        case 'text':
                            $result = '<small>'.$result.' (text)</small>';
                            break;
                        case 'image':
                            $result .= block_exastud_get_string('report_setting_type_image_maxbytes').': '.$resArr['maxbytes'];
                            $result .= '<br />'.block_exastud_get_string('report_setting_type_image_width').': '.$resArr['width'];
                            $result .= '&nbsp;'.block_exastud_get_string('report_setting_type_image_height').': '.$resArr['height'];
                            $result = '<small>'.$result.'</small>';
                            break;
                        case 'userdata':
                            $result .= block_exastud_get_string('report_setting_type_userdata').': '.$resArr['userdatakey'];
                            $result = '<small>'.$result.'</small>';
                            break;
                    }
                }
                return $result;
            }
            return '';
        };
        $templateList = block_exastud_get_template_files();
        $templateExcluded = block_exastud_get_template_files(true);
        foreach ($reports as $report) {
            // filtering reports by active BW or not: filter only by possible report filenames
            if (in_array($report->template, $templateExcluded)) {
                continue;
            }
            foreach ($templateExcluded as $excl) {
                if (strpos($report->template, $excl.'/') === 0
                    || $report->template == $excl) {
                    continue 2;
                }
            }
            $bpData = $DB->get_record('block_exastudbp', ['id' => $report->bpid]);
            $params = [
                    'action' => 'edit',
                    'id' => $report->id,
                    'sesskey' => sesskey()
            ];
            if ($tokenparam) {
                $params['token'] = $tokenparam;
            }
            // edit button
            $editLink = html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', $params),
                                            html_writer::tag("img", '', array('src' => 'pix/edit.png')), array('title' => 'id: '.$report->id));
            // hidden button
            if ($report->hidden) {
                $params['action'] = 'unhide';
                $hideLink = html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', $params),
                        html_writer::tag("img", '', array('src' => 'pix/show.png')), array('title' => ' unhide report '));
            } else {
                $params['action'] = 'hide';
                $hideLink = html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', $params),
                        html_writer::tag("img", '', array('src' => 'pix/hide.png')), array('title' => ' hide report '));
            }
            // copy button
            $params['action'] = 'copy';
            $copyLink = html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', $params),
                    html_writer::tag("img", '', array('src' => 'pix/copy.png')), array('title' => block_exastud_get_string('report_settings_copy')));

            // delete button
            $params['action'] = 'delete';
            $deleteLink = html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', $params),
                    html_writer::tag("img", '', array('src' => 'pix/del.png')), array('title' => block_exastud_get_string('report_delete', null, $report->title)));
            // is relevant subjects report?
            /*if ($report->relevant_subjects) {
                $relevantSubject = new html_table_cell();
                $relevantSubject->attributes['align'] = 'center';
                $relevantSubject->text = html_writer::tag("img", '', array('src' => 'pix/valid.png'));
            } else {
                $relevantSubject = '&nbsp;';
            }*/
            // function for call settings_marker only by name
            $call_setting_marker = function($name) use ($setting_marker, $report){
                return $setting_marker($name, $report->{$name});
            };
            $tableRow = new html_table_row();
            if ($report->hidden) {
                $tableRow->attributes['class'] .= ' exastud-hidden-report ';
            }
            if (block_exastud_is_bw_active()) {
                $row = array(
                        $editLink.' :'.$report->id,
                        $hideLink.'&nbsp;'.$copyLink.'&nbsp;'.$deleteLink,
                    //$relevantSubject,
                        '<strong>'.$report->title.'</strong>',
                        $bpData ? $bpData->title : '',
                        $report->category.(in_array(trim($report->category), $categories_withoutcross_competences) ?
                                html_writer::span($commenticon, 'info-icon') : ''),
                        array_key_exists($report->template, $templateList) ? $templateList[$report->template] : $report->template,);
                if ($showfulltable) {
                    $row = array_merge($row, array(
                            $report->grades,
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
                            $call_setting_marker('projekt_thema'),
                            $call_setting_marker('ags'),
                            block_exastud_get_reportsettings_additional_description($report)
                    ));
                }
            } else {
                // if template has userdata marker with non existing custom user field
                $userselectboxparameters = block_exastud_get_report_user_fields();
                $curr_template_inputs = \block_exastud\print_templates::get_template_inputs($report->id, 'all');
                $wrongUserdataMarker = '';
                if (is_array($curr_template_inputs)) {
                    foreach ($curr_template_inputs as $key => $param_settings) {
                        if ($param_settings['type'] == 'userdata') {
                            if (!empty($param_settings['userdatakey']) &&
                                    !array_key_exists($param_settings['userdatakey'], $userselectboxparameters)) {
                                /*$wrongUserdataMarker = '<img src="'.$CFG->wwwroot.'/blocks/exastud/pix/attention.png" title="'.
                                        block_exastud_get_string('report_settings_userdata_wrong_user_parameter_in_reports_list').
                                        '"/>&nbsp;';*/
                                $wrongUserdataMarker = '<i class="fas fa-exclamation-triangle" 
                                        title="'.block_exastud_get_string('report_settings_userdata_wrong_user_parameter_in_reports_list').'"></i>&nbsp;';
                                break;
                            }
                        }
                    }
                }
                // if no file template
                $a = (object)['filename' => $report->template];
                $fileNoExistsMessage = block_exastud_get_string('report_settings_no_template_file', 'block_exastud', $a);
                $row = array(
                        $editLink.' :'.$report->id,
                        $hideLink.'&nbsp;'.$copyLink.'&nbsp;'.$deleteLink,
                    //$relevantSubject,
                        $wrongUserdataMarker.'<strong>'.$report->title.'</strong>',
                        //array_key_exists($report->template, $templateList) ? $templateList[$report->template] : '<img src="'.$CFG->wwwroot.'/blocks/exastud/pix/attention.png" title="'.$fileNoExistsMessage.'"/>&nbsp;'.$report->template
                        array_key_exists($report->template, $templateList) ? $templateList[$report->template] : '<i class="fad fa-exclamation-triangle" title="'.$fileNoExistsMessage.'"></i>&nbsp;'.$report->template
                );
                if ($showfulltable) {
                    $row = array_merge($row, array(
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
                            $call_setting_marker('projekt_thema'),
                            block_exastud_get_reportsettings_additional_description($report)
                    ));
                }
            }
            $tableRow->cells = $row;

            $table->data[] = $tableRow;
        }
    }

    $buttons = '';
    if (!empty($table)) {
        $content .= $output->table($table);
        $buttons .= $newLink.'&nbsp;&nbsp;&nbsp;';
        // Reset templates to default state
        $params = [
                'action' => 'reset_default',
                'sesskey' => sesskey()
        ];
        if ($tokenparam) {
            $params['token'] = $tokenparam;
        }
        $buttons .= html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', $params),
                block_exastud_get_string('reset_report_templates'), ['class' => 'btn btn-danger']);
        $content .= html_writer::div($buttons, 'buttons');
    } else {
        $content .= block_exastud_get_string('not_found_report');
        $formcontent = '';
        $formcontent .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'doit', 'value' => 1]);
        $formcontent .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'action', 'value' => 'reset_default']);
        if ($tokenparam) {
            $formcontent .= html_writer::tag('input', '', ['type' => 'hidden', 'name' => 'token', 'value' => $tokenparam]);
        }
        $formcontent .= '&nbsp;&nbsp;&nbsp;'.html_writer::tag('button', block_exastud_get_string('reinstall_report_templates'), [
                        'type' => 'submit',
                        'class' => 'btn btn-danger',
                        'name' => 'reinstall',
                        'value' => 'reinstall'
                ]);
        $content .= html_writer::tag('form', $formcontent, [
                'id' => 'form-templatelist',
                'method' => 'post'
        ]);
    }
    // import / export buttons
    $ie_buttons = '<br /><br />';
    $params = [
            'action' => 'import',
            'sesskey' => sesskey()
    ];
    $ie_buttons .= html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', $params),
            block_exastud_get_string('report_button_import'), ['class' => 'btn btn-default']);
    $ie_buttons .= '&nbsp;&nbsp;&nbsp;';
    $params['action'] = 'export';
    $ie_buttons .= html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', $params),
            block_exastud_get_string('report_button_export'), ['class' => 'btn btn-default']);
    $content .= html_writer::div($ie_buttons , 'buttons');

    echo $content;
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
            $additional = '';
            switch ($reportData['type']) {
                case 'textarea':
                    $additional .= ':'.block_exastud_get_string('report_settings_countrows', null, $reportData['rows']);
                    $additional .= '&nbsp;/&nbsp;'.block_exastud_get_string('report_settings_countinrow_short', null, $reportData['count_in_row']);
                    if (@$reportData['maxchars'] > 0) {
                        $additional .= '&nbsp;/&nbsp;'.block_exastud_get_string('report_settings_maxchars_short', null, $reportData['maxchars']);
                    }
                    break;
            }
            $content .= '<li><strong>${'.$reportData['key'].'}:</strong> '.'<i>('.$reportData['type'].$additional.')</i> '.$reportData['title'].'</li>';
        }
        $content .= '</ul>';
    }
    return $content;
}

/**
 * @param reportsettings_edit_form $settingsform
 * @param $settingsedit
 * @return mixed
 * @throws coding_exception
 */
function block_exastud_report_templates_prepare_serialized_data($settingsform, $settingsedit) {
    // main properties
    $uploadFolder = BLOCK_EXASTUD_TEMPLATE_DIR.'/upload/';
    if ($params_sorting = optional_param('params_sorting', '', PARAM_TEXT)) {
        $settingsedit->params_sorting = serialize(json_decode($params_sorting));
    }
    // template file
    if ($tmpNewFile = $settingsform->save_temp_file('newfileupload')) {
        $newfilename = $settingsform->get_new_filename('newfileupload');
        $copyTo = $uploadFolder.$newfilename;
        if (!file_exists($copyTo)
                || (file_exists($copyTo) && @$settingsedit->overwritefile)
        ) {
            copy($tmpNewFile, $copyTo);
        } else {
            // if file exists - get new filename
            $newfilename = block_exastud_get_unique_filename($newfilename, $uploadFolder);
            $copyTo = $uploadFolder.$newfilename;
            copy($tmpNewFile, $copyTo);
        }
        unlink($tmpNewFile);
        // get new filename for report configurations
        $tmpInfo = pathinfo($copyTo);
        $newTemplateValue = str_replace(BLOCK_EXASTUD_TEMPLATE_DIR.'/', '', $copyTo);
        $rTrimLength = strlen($tmpInfo['extension']) + 1; // plus '.'
        $newTemplateValue = substr($newTemplateValue, 0, -$rTrimLength);
        $settingsedit->template = $newTemplateValue;
    }
    // template markers
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
                    $element_data['maxchars'] =
                            (isset($settingsedit->{$field.'_maxchars'}) && $settingsedit->{$field.'_maxchars'} > 0 ?
                                    $settingsedit->{$field.'_maxchars'} : 0);
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
                case 'image':
                    $element_data['maxbytes'] = (isset($settingsedit->{$field.'_maxbytes'}) && $settingsedit->{$field.'_maxbytes'} > 0 ?
                            $settingsedit->{$field.'_maxbytes'} : 50000);
                    $element_data['width'] = (isset($settingsedit->{$field.'_width'}) && $settingsedit->{$field.'_width'} > 0 ?
                            $settingsedit->{$field.'_width'} : 800);
                    $element_data['height'] = (isset($settingsedit->{$field.'_height'}) && $settingsedit->{$field.'_height'} > 0 ?
                            $settingsedit->{$field.'_height'} : 600);
                    break;
                case 'userdata':
                    $element_data['userdatakey'] = (isset($settingsedit->{$field.'_userdatakey'}) && $settingsedit->{$field.'_userdatakey'} != '' ?
                            $settingsedit->{$field.'_userdatakey'} : '');
                    break;
                case 'matrix':
                    $element_data['matrixtype'] = (isset($settingsedit->{$field.'_matrixtype'}) && $settingsedit->{$field.'_matrixtype'} != '' ?
                            $settingsedit->{$field.'_matrixtype'} : 'checkbox');
                    $matrix_rows = optional_param_array($field.'_matrixrows', '', PARAM_RAW);
                    if (!empty($matrix_rows) && count($matrix_rows) > 0) {
                        foreach ($matrix_rows as $iIndex => $iTitle) {
                            $element_data['matrixrows'][] = $iTitle;
                        };
                    }
                    $matrix_cols = optional_param_array($field.'_matrixcols', '', PARAM_RAW);
                    if (!empty($matrix_cols) && count($matrix_cols) > 0) {
                        foreach ($matrix_cols as $iIndex => $iTitle) {
                            $element_data['matrixcols'][] = $iTitle;
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
    $aparams_GP = optional_param_array('additional_params', '', PARAM_RAW);
    $additional_params = array();
    if ($aparams_GP && is_array($aparams_GP) && count($aparams_GP) > 0) {
        // main params
        $aparams_titles = optional_param_array('additional_params_title', '', PARAM_RAW);
        $aparams_keys = optional_param_array('additional_params_key', '', PARAM_RAW);
        $aparams_types = optional_param_array('additional_params_type', '', PARAM_RAW);
        // textarea
        $aparams_rows = optional_param_array('additional_params_rows', '', PARAM_INT);
        $aparams_count_in_rows = optional_param_array('additional_params_count_in_row', '', PARAM_INT);
        $aparams_maxchars = optional_param_array('additional_params_maxchars', '', PARAM_INT);
        // image
        $aparams_maxbytes = optional_param_array('additional_params_maxbytes', '', PARAM_INT);
        $aparams_width = optional_param_array('additional_params_width', '', PARAM_INT);
        $aparams_height = optional_param_array('additional_params_height', '', PARAM_INT);
        // selectbox
        $aparams_selectboxvalues_key = block_exastud_optional_param_array('additional_params_selectboxvalues_key', '', PARAM_RAW);
        $aparams_selectboxvalues_value = block_exastud_optional_param_array('additional_params_selectboxvalues_value', '', PARAM_RAW);
        // for userdata
        $aparams_userdatakey = optional_param_array('additional_params_userdatakey', '', PARAM_RAW);
        // matrix
        $aparams_matrixtype = optional_param_array('additional_params_matrixtype', '', PARAM_RAW);
        $aparams_matrixrows = block_exastud_optional_param_array('additional_params_matrixrows', '', PARAM_RAW);
        $aparams_matrixcols = block_exastud_optional_param_array('additional_params_matrixcols', '', PARAM_RAW);
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
                            $additional_params[$aparams_keys[$pIndex]]['maxchars'] =
                                    (isset($aparams_maxchars[$pIndex]) &&
                                    $aparams_maxchars[$pIndex] > 0 ?
                                            $aparams_maxchars[$pIndex] : 0);
                            break;
                        case 'select':
                            if (array_key_exists($pIndex, $aparams_selectboxvalues_key) && count($aparams_selectboxvalues_key[$pIndex]) > 0) {
                                foreach ($aparams_selectboxvalues_key[$pIndex] as $vIndex => $key) {
                                    $additional_params[$aparams_keys[$pIndex]]['values'][$key] =
                                            trim($aparams_selectboxvalues_value[$pIndex][$vIndex]);
                                };
                            }
                            break;
                        case 'image':
                            $additional_params[$aparams_keys[$pIndex]]['maxbytes'] =
                                    (isset($aparams_maxbytes[$pIndex]) && $aparams_maxbytes[$pIndex] > 0 ?
                                            $aparams_maxbytes[$pIndex] : 50000);
                            $additional_params[$aparams_keys[$pIndex]]['width'] =
                                    (isset($aparams_width[$pIndex]) && $aparams_width[$pIndex] > 0 ?
                                            $aparams_width[$pIndex] : 800);
                            $additional_params[$aparams_keys[$pIndex]]['height'] =
                                    (isset($aparams_height[$pIndex]) && $aparams_height[$pIndex] > 0 ?
                                            $aparams_height[$pIndex] : 600);
                            break;
                        case 'userdata':
                            $additional_params[$aparams_keys[$pIndex]]['userdatakey'] =
                                    (isset($aparams_userdatakey[$pIndex]) && $aparams_userdatakey[$pIndex] != '' ?
                                            $aparams_userdatakey[$pIndex] : '');
                            break;
                        case 'matrix':
                            $additional_params[$aparams_keys[$pIndex]]['matrixtype'] =
                                    (isset($aparams_matrixtype[$pIndex]) && $aparams_matrixtype[$pIndex] != '' ?
                                            $aparams_matrixtype[$pIndex] : 'checkbox');
                            if (array_key_exists($pIndex, $aparams_matrixrows) && count($aparams_matrixrows[$pIndex]) > 0) {
                                foreach ($aparams_matrixrows[$pIndex] as $rIndex => $rTitle) {
                                    $additional_params[$aparams_keys[$pIndex]]['matrixrows'][] = $rTitle;
                                };
                            }
                            if (array_key_exists($pIndex, $aparams_matrixcols) && count($aparams_matrixcols[$pIndex]) > 0) {
                                foreach ($aparams_matrixcols[$pIndex] as $rIndex => $rTitle) {
                                    $additional_params[$aparams_keys[$pIndex]]['matrixcols'][] = $rTitle;
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

// reset templates: for testing
//block_exastud_fill_reportsettingstable();