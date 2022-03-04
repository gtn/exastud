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

require_once($CFG->libdir.'/gdlib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/editadvanced_form.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/webservice/lib.php');

class exastud_user_formedit extends user_editadvanced_form {

    function get_possible_fields($getAll = false) {
        $fields = $this->_form->_elements;
        $resultFields = array();
        foreach ($fields as $field) {
            $classOfField = get_class($field);
            $fieldkey = $field->_attributes['name'];
            $label = $field->_label;
            if ($getAll) {
                $resultFields[$fieldkey] = $field; // for filtering in other functions
            } else {
                if (!in_array($classOfField, array(
                                'MoodleQuickForm_hidden',
                                'MoodleQuickForm_submit',
                            //'MoodleQuickForm_selectgroups' // need?
                        ))
                        && (strpos($fieldkey, 'passw') === false) // delete all password fields
                        && (trim($label) != '')
                ) {
                    $resultFields[$fieldkey] = $label;
                }
            }
        }
        return $resultFields;
    }
}

class block_exastud_import_report_form extends moodleform {
    function definition() {
        $mform = &$this->_form;

        $mform->addElement('filepicker', 'datafile', block_exastud_get_string("file"), null, array('accepted_types' => array('.xml', '.zip')));
        $mform->addRule('datafile', block_exastud_get_string('report_import_file_shouldnotbeempty'), 'required');

        $mform->addElement('checkbox', 'updatereports', block_exastud_get_string('report_export_update_reports'), ' ');
        $mform->addElement('checkbox', 'updatefiles', block_exastud_get_string('report_export_update_files'), ' ');

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);
        $mform->setDefault('action', 'import');

        $mform->addElement('hidden', 'doit');
        $mform->setType('doit', PARAM_INT);
        $mform->setDefault('doit', 1);

        $this->add_action_buttons(block_exastud_get_string('back'), block_exastud_get_string('report_import_templates'));
    }
}

function block_exastud_get_report_user_fields($getAll = false) {
    global $CFG;
    static $resultArr = null;
    if (!$resultArr) {
        $resultArr = array('' => '');
        // create fake editing form
        $user = new stdClass();
        $user->id = -1;
        $user->auth = 'manual';
        $user->confirmed = 1;
        $user->deleted = 0;
        $user->timezone = '99';
        $editoroptions = array(
                'maxfiles' => 0,
                'maxbytes' => 0,
                'trusttext' => false,
                'forcehttps' => false,
                'context' => context_system::instance()
        );
        //$filemanagercontext = $editoroptions['context'];
        $filemanageroptions = array('maxbytes'       => $CFG->maxbytes,
                'subdirs'        => 0,
                'maxfiles'       => 1,
                'accepted_types' => 'web_image');
        $userform = new exastud_user_formedit(new moodle_url($CFG->wwwroot), array(
                'editoroptions' => $editoroptions,
                'filemanageroptions' => $filemanageroptions,
                'user' => $user));
        $result = $userform->get_possible_fields($getAll);
        if ($getAll) {
            return $result;
        }
        // filter not needed fields
        $toDelete = array('deletepicture', 'auth', 'maildisplay', 'imagefile'/*, 'currentpicture'*/);
        foreach ($toDelete as $delKey) {
            if (array_key_exists($delKey, $result)) {
                unset($result[$delKey]);
            }
        }
        $resultArr = $result;
    }
    return $resultArr;
}

function block_exastud_get_report_userdata_value(&$templateProcessor = null, $datakey, $userid, $fieldname) {
    global $DB, $CFG, $OUTPUT;
    static $users = array();
    static $checkboxes = array();
    if (!count($checkboxes)) {
        $fields = block_exastud_get_report_user_fields(true);
        // get all checkboxes
        $fields = array_filter($fields, function($f) {if ($f->_attributes && array_key_exists('type', $f->_attributes) && $f->_attributes['type'] == 'checkbox') {return true;};});
        $checkboxes = array_keys($fields);
    }
    $value = '';
    // get all user data
    if (!array_key_exists($userid, $users)) {
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        if (!$user->deleted) {
            // Load user preferences.
            useredit_load_preferences($user);
            // Load custom profile fields data.
            profile_load_data($user);
            // User interests.
            $user->interests = implode(', ', core_tag_tag::get_item_tags_array('core', 'user', $userid));
            // TODO: user's image!
            $users[$userid] = $user;
        }
    }
    // get needed value
    if (array_key_exists($userid, $users) && $users[$userid]) {
        $user = $users[$userid];
        if (property_exists($user, $fieldname) || in_array($fieldname, ['currentpicture'])) {
            // is a checkbox
            if (in_array($fieldname, $checkboxes)) {
                return ($user->{$fieldname} ? 'V' : '-'); // char: '+' 'X' ????
            }
            // gender
            if ($fieldname == 'profile_field_gender') {
                return block_exastud_get_user_gender_string($userid);
            }
            // timezone
            if ($fieldname == 'timezone') {
                return core_date::get_localised_timezone($user->{$fieldname});
            }
            // email hide if hidding is setted up
            if ($fieldname == 'email') {
                $maildisplay = $user->maildisplay;
                // hide email
                if (!($maildisplay == 1 || $maildisplay == 2)) { // may be add "only for users from this course?" ($maildisplay == 2 && enrol_sharing_course($user, $USER))
                    return '';
                }
            }
            // country
            if ($fieldname == 'country') {
                return get_string($user->country, 'countries');
            }
            // user's picture
            if ($fieldname == 'currentpicture') {
                $contextuser = context_user::instance($userid, MUST_EXIST)->id;
                if ($templateProcessor) {
                    if (!$templateProcessor->addImageToReport($contextuser, $datakey, 'user', 'icon', false, 100, 100, false)) {
                        return ''; // empty image
                    }
                } else {
                    return $OUTPUT->user_picture($user);
                    //return '<img src="http://www.utzacademyonline.com/user/pix.php/'.$userid.'/f2.jpg">';
                }
                return '';
            }
            if (strpos($fieldname, 'profile_field_') !== false && empty($user->{$fieldname})) {
                return '';
            }
            return $user->{$fieldname};

        }
    }
    return $value;
}

/**
 * get all reports
 * @return array
 * @throws dml_exception
 */
function block_exastud_templates_get_templates() {
    global $DB;
    $result = $DB->get_records('block_exastudreportsettings');
    return $result;
}

function block_exastud_export_reports($templateids = array(), $withFiles = false) {
    global $CFG;
    $pluginmanager = core_plugin_manager::instance();
    $plugininfo = $pluginmanager->get_plugin_info('block_exastud');
    $exastudversion = @$plugininfo->versiondb;
    $resultXML = '<?xml version="1.0" encoding="UTF-8"?>';
    $resultXML .= "\r\n".'<reports exastud-version="'.$exastudversion.'">'."\r\n";
    $addFiles = array();
    foreach ($templateids as $tid) {
        $repfilename = '';
        $resultXML .= block_exastud_report_get_xmlSettings($tid, $repfilename);
        if ($repfilename) {
            $addFiles[] = $repfilename;
        }
    }
    $resultXML .= "\r\n".'</reports>';
    $resultFilename = 'exastud-reports-'.date('Y-m-d-H-i');
    if ($withFiles) {
        // add all files to ZIP
        $zipfilename = tempnam($CFG->tempdir, "zip");
        $zip = new \ZipArchive();
        $zip->open($zipfilename, \ZipArchive::OVERWRITE);
        // main xml file
        $temp_file = tempnam($CFG->tempdir, 'exastud');
        file_put_contents($temp_file, $resultXML);
        $zip->addFile($temp_file, 'reports.xml');
        // sources of reports
        $path_to_files = $CFG->dirroot.'/blocks/exastud/template/';
        foreach ($addFiles as $file) {
            // docx or dotx
            $fullPath = $path_to_files.$file;
            $exts = array('dotx', 'docx');
            $exists = false;
            foreach ($exts as $ext) {
                if (file_exists($fullPath.'.'.$ext)) {
                    $fullPath = $fullPath.'.'.$ext;
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                continue;
            }
            // new file - file in folder
            $filePathParts = explode($path_to_files, $fullPath);
            $basenameWithSubfolder = end($filePathParts);
            //$newFilename = 'sources/'.basename($fullPath);
            $newFilename = 'sources/'.$basenameWithSubfolder;
            $zip->addFile($fullPath, $newFilename);
        }
        $zip->close();
        $newZipFilename = $resultFilename.'.zip';
        send_temp_file($zipfilename, $newZipFilename);
        exit();
    } else {
        $temp_file = tempnam($CFG->tempdir, 'exastud');
        file_put_contents($temp_file, $resultXML);
        send_temp_file($temp_file, $resultFilename.'.xml');
        exit;
    }
}

function block_exastud_report_get_xmlSettings($templateid, &$repfilename) {
    global $DB;
    $template = $DB->get_record('block_exastudreportsettings', ['id' => $templateid]);
    if ($template) {
        $repfilename = $template->template;
        $formatXml = function ($simpleXMLElement) {
            $xmlDocument = new DOMDocument();
            $xmlDocument->preserveWhiteSpace = false;
            $xmlDocument->formatOutput = true;
            $xmlDocument->loadXML($simpleXMLElement->asXML());
            return $xmlDocument->saveXML();
        };
        $arrayToXml = function ($array, $rootElement = null, $xml = null, $rootattributes = array()) use ($formatXml) {
            $xml_clone = $xml;
            if ($xml_clone === null) {
                $xml_clone = new SimpleXMLElement($rootElement !== null ? '<'.$rootElement.'/>' : '<report/>');
            }
            if (count($rootattributes)) {
                foreach ($rootattributes as $attrname => $attrvalue) {
                    $xml_clone->addAttribute($attrname, $attrvalue);
                }
            }

            foreach ($array as $key => $val) {
                $xml_clone->addChild($key, $val);
            }
            $res = $formatXml($xml_clone);
            // delete first line with version of xml
            $res = substr($res, strpos($res, "\n")+1);
            return $res;
        };
        $templateArr = (array)$template;
        $source = block_exastud_get_my_source();
        // clean result array from redundant fields
        $redFields = array('id', 'source', 'source_id');
        $templateArr = array_diff_key($templateArr, array_flip($redFields));
        $resultXml = $arrayToXml($templateArr, 'report', null, array('id' => $templateid, 'source' => $source));
        return $resultXml;
    } else {
        return '';
    }
}

function block_exastud_report_exists_by_source($source, $source_id) {
    global $DB;
    $mysource = block_exastud_get_my_source();
    if ($source == $mysource) {
        $rec = $DB->get_record('block_exastudreportsettings', ['id' => $source_id]);
    } else {
        $rec = $DB->get_record('block_exastudreportsettings', ['source' => $source, 'source_id' => $source_id]);
    }
    if ($rec) {
        return $rec->id;
    }
    return false;
}

function block_exastud_import_report_xml($xmlcontent, $updatereports = false, $updatefiles = false) {
    global $DB;
    $inserted = array();
    $updated = array();
    $ignored = array();
    $filelist = array();
    $mysource = block_exastud_get_my_source();
    $xml = block_exastud_load_xml_data($xmlcontent, true, 'reports');
    $reports = $xml['reports']['#']['report'];
    $reportData = function($repXmlArr = array()) use ($mysource) {
        $resArr = array();
        if ($mysource == $repXmlArr['@']['source']) {
            $resArr['id'] = $repXmlArr['@']['id'];
        } else {
            $resArr['source_id'] = $repXmlArr['@']['id'];
            $resArr['source'] = $repXmlArr['@']['source'];
        }
        $props = $repXmlArr['#'];
        foreach ($props as $propName => $propVal) {
            $resArr[$propName] = $propVal[0]['#'];
        }
        return $resArr;
    };
    foreach ($reports as $i => $reportXml) {
        $attributes = $reportXml['@'];
        //$reportValues = $reportXml['#'];
        $existsid = block_exastud_report_exists_by_source($attributes['source'], $attributes['id']);
        $resData = (object)$reportData($reportXml);
        if ($existsid) {
            if ($updatereports) {
                // update reports is selected
                $resData->id = $existsid;
                $DB->update_record_raw('block_exastudreportsettings', $resData);
                //$DB->execute('UPDATE {block_exastudreportsettings} ', $resData);
                $updated[$existsid] = $resData->title.' (id: '.$existsid.')';
            } else {
                // ignore this report!
                $ignored[$existsid] = $resData->title.' (id: '.$existsid.')';
            }
        } else {
            // insert new report
            if ($mysource == $attributes['source']) {
                $resData->id = $attributes['id'];
                $newid = $DB->insert_record_raw('block_exastudreportsettings', $resData, true, false, true);
                $newid = $resData->id;
            } else {
                $newid = $DB->insert_record('block_exastudreportsettings', $resData);
            }

            $inserted[$newid] = $resData->title.' (id: '.$newid.')';
        }
        $filelist[] = $resData->template;
    }

    $result = array(
            'inserted' => $inserted,
            'updated' => $updated,
            'ignored' => $ignored
    );
    $result['filelist'] = $filelist;

    return $result;
}
