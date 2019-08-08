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
        $toDelete = array('deletepicture', 'auth', 'imagefile'/*, 'currentpicture'*/); // imagefile,currentpicture -> TODO: add user's photo!
        foreach ($toDelete as $delKey) {
            if (array_key_exists($delKey, $result)) {
                unset($result[$delKey]);
            }
        }
        $resultArr = $result;
        //echo "<pre>debug:<strong>reports_lib.php:88</strong>\r\n"; print_r($resultArr); echo '</pre>'; // !!!!!!!!!! delete it
    }
    return $resultArr;
}

function block_exastud_get_report_userdata_value(&$templateProcessor, $datakey, $userid, $fieldname) {
    global $DB, $CFG;
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
    //echo "<pre>debug:<strong>reports_lib.php:126</strong>\r\n"; print_r($users); echo '</pre>'; exit; // !!!!!!!!!! delete it
    // get needed value
    if (array_key_exists($userid, $users) && $users[$userid]) {
        $user = $users[$userid];
        if (property_exists($user, $fieldname) || in_array($fieldname, ['currentpicture'])) {
            // is a checkbox
            if (in_array($fieldname, $checkboxes)) {
                return ($user->{$fieldname} ? 'V': '-'); // char: '+' 'X' ????
            }
            // gender
            if ($fieldname == 'profile_field_gender') {
                return block_exastud_get_user_gender_string($userid);
            }
            // timezone
            if ($fieldname == 'timezone') {
                return core_date::get_localised_timezone($user->{$fieldname});
            }
            // user's picture
            if ($fieldname == 'currentpicture') {
                $contextuser = context_user::instance($userid, MUST_EXIST)->id;
                if (!$templateProcessor->addImageToReport($contextuser, $datakey, 'user', 'icon', false, 100, 100, false)) {
                    return ''; // empty image
                }
                return '';
            }
            return $user->{$fieldname};

        }
    }
    return $value;
}

