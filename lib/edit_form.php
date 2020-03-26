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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

use block_exastud\globals as g;

class class_edit_form extends moodleform {


	function definition() {
	    global $USER;
		$mform = &$this->_form;
        // if it is not a class owner, but siteadmin - use hidden form fields instead of usual

        $mform->addElement('hidden', 'classid');
        $mform->setType('classid', PARAM_INT);
        $mform->setDefault('classid', 0);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $titlelimit = 50;
        if (!$this->_customdata['for_siteadmin']) {
            $mform->addElement('text', 'title', block_exastud_get_string('class').':', array(
                    'size' => $titlelimit,
                    'class' => 'exastud-review-message',
                    'data-exastudmessage' => block_exastud_get_string('class_title_limit_message', null, $titlelimit)));
        } else {
            $mform->addElement('hidden', 'title');
        }
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', block_exastud_get_string('class_title_limit_message', null, $titlelimit), 'maxlength', $titlelimit, 'client');

        if (block_exastud_is_bw_active()) {
            $maxlength = 10;
        } else {
            $maxlength = 30; // for non-bw
        }
        if (!$this->_customdata['for_siteadmin']) {
            $mform->addElement('text',
                    'title_forreport',
                    block_exastud_get_string('class_title_for_report').':',
                    array('size' => $maxlength,
                            'class' => 'exastud-review-message',
                            'data-exastudmessage' =>  block_exastud_get_string('class_title_limit_message', null, $maxlength).' '.block_exastud_get_string('class_title_for_report_description')));
        } else {
            $mform->addElement('hidden', 'title_forreport');
        }
        $mform->setType('title_forreport', PARAM_TEXT);
        $mform->addRule('title_forreport', block_exastud_get_string('class_title_limit_message', null, $maxlength), 'maxlength', $maxlength, 'client');

        $bps = g::$DB->get_records_menu('block_exastudbp', null, 'sorting', 'id, title');
        $bps = ['' => ''] + $bps;
        if (!$this->_customdata['for_siteadmin']) {
            //if (block_exastud_is_bw_active()) {
                $mform->addElement('select',
                        'bpid',
                        block_exastud_get_string('class_educationplan').':',
                        $bps,
                        ['class' => 'exastud-review-message',
                                'data-exastudmessage' => block_exastud_get_string('attention_plan_will_change')]);
            //} else {
            //    $mform->addElement('hidden', 'bpid');
            //    $mform->setType('bpid', PARAM_INT);
            //    $mform->setDefault('bpid', 0);
            //}
        } else {
            $mform->addElement('hidden', 'bpid');
            $mform->setType('bpid', PARAM_INT);
        }
        /*		$mform->addElement('static', '', '&nbsp;',
                    g::$OUTPUT->notification(block_exastud_get_string('attention_plan_will_change'), 'notifymessage')
                );*/

        if (!$this->_customdata['for_siteadmin']) {
            $mform->addElement('text', BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID,
                    block_exastud_get_string('class_default_template').':', ['class' => 'exastud-review-message']);
        } else {
            $mform->addElement('hidden', BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID);
        }
        $mform->setType(BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID, PARAM_TEXT);

        /*		$mform->addElement('static', '', '&nbsp;',
                    g::$OUTPUT->notification(block_exastud_get_string('attention_template_will_change'), 'notifymessage')
                );*/

        /*
        $subjects = $DB->get_records_menu('block_exastudsubjects', null, 'title', 'id, title');
        $select = $mform->addElement('select', 'mysubjectids', block_exastud_get_string('subjects_taught_by_me'), $subjects);
        $select->setMultiple(true);
        */

		// change class owner  (only for siteadmin // deleted ==> or class owner)
        if ($this->_customdata['classid'] && (block_exastud_is_siteadmin()/* || $this->_customdata['is_classowner'] */)) {
            $headteachers = block_exastud_get_head_teachers_all();
            $options = array();
            foreach ($headteachers as $teacher) {
                $options[$teacher->id] = $teacher->lastname.' '.$teacher->firstname;
            }
            $selectboxoptions = array('class' => 'exastud-review-message');
            $link = new moodle_url('/message/index.php', ['id' => '0']);
            $a = new stdClass();
            $a->messagehref = $link->out();
            $selectboxoptions['data-exastudmessage'] = '';
            if (!block_exastud_is_siteadmin()) {
                $selectboxoptions['data-exastudmessage'] .= block_exastud_get_string('attention_owner_will_change');
            }
            $selectboxoptions['data-exastudmessage'] .= ($selectboxoptions['data-exastudmessage'] ? '<br>' : '').block_exastud_get_string('attention_send_message_to_classteacher', null, $a);
            $mform->addElement('select',
                    'userid',
                    block_exastud_get_string('class_owner'),
                    $options,
                    $selectboxoptions);
        } else {
            $mform->addElement('hidden', 'userid', null);
            $mform->setType('userid', PARAM_INT);
            if ($this->_customdata['classid'] && $this->_customdata['classid'] > 0) {
                $class = block_exastud_get_class($this->_customdata['classid']);
                $mform->setDefault('userid', $class->userid);
            } else {
                $mform->setDefault('userid', $USER->id);
            }
        }

/*        $mform->addElement('filemanager', 'class_logo', block_exastud_get_string('class_logo'), null,
                array(
                        'subdirs' => 0,
                        'maxfiles' => 1,
                        'accepted_types' => array('web_image'))
        );*/

        if (!block_exastud_is_bw_active()) {
            // Überfachliche Kompetenzen
            //$mform->addElement('checkbox', 'classteacher_grade_interdisciplinary_competences',  block_exastud_get_string('classteacher_grade_interdisciplinary_competences'));
            //$mform->addElement('checkbox', 'subjectteacher_grade_interdisciplinary_competences',  block_exastud_get_string('subjectteacher_grade_interdisciplinary_competences'));
            $group = array();
            $group[] = $mform->createElement('checkbox', 'classteacher_grade_interdisciplinary_competences', block_exastud_get_string('class_settings_class_teacher'));
            $group[] = $mform->createElement('checkbox', 'subjectteacher_grade_interdisciplinary_competences', block_exastud_get_string('class_settings_subject_teacher'));
            $mform->addGroup($group, 'edit_interdisciplinary_competences', block_exastud_get_string('class_settings_can_edit_crosscompetencies'), '&nbsp;&nbsp;&nbsp;', false);
            // Learning and social behavior
            //$mform->addElement('checkbox', 'classteacher_grade_learn_and_social_behaviour',  block_exastud_get_string('classteacher_grade_learn_and_social_behaviour'));
            //$mform->addElement('checkbox', 'subjectteacher_grade_learn_and_social_behaviour',  block_exastud_get_string('subjectteacher_grade_learn_and_social_behaviour'));
            $group = array();
            $group[] = $mform->createElement('checkbox', 'classteacher_grade_learn_and_social_behaviour', block_exastud_get_string('class_settings_class_teacher'));
            $group[] = $mform->createElement('checkbox', 'subjectteacher_grade_learn_and_social_behaviour', block_exastud_get_string('class_settings_subject_teacher'));
            $mform->addGroup($group, 'edit_learnsocial', block_exastud_get_string('class_settings_can_edit_learnsocial'), '&nbsp;&nbsp;&nbsp;', false);
        }

        $genders = array(
            'male' => block_exastud_get_string('man'),
            'female' => block_exastud_get_string('woman'),
        );
        $liederfields = ['schoollieder', 'groupleader', 'auditleader', 'classleader'];
        $mform->addElement('header', 'leaders', block_exastud_get_string('leaders'));
        foreach ($liederfields as $field) {
            $group = array();
            $group[] =& $mform->createElement('select', $field.'_gender', block_exastud_get_string('gender'), $genders);
            $mform->setType($field.'_gender', PARAM_TEXT);
            $group[] =& $mform->createElement('text', $field.'_name', block_exastud_get_string('name'), ['placeholder' => block_exastud_get_string('name')]);
            $mform->setType($field.'_name', PARAM_TEXT);
            $mform->addGroup($group, $field.'_group', block_exastud_get_string($field.'_fieldtitle'), array(' '), false);
        }
        $mform->closeHeaderBefore('leaders');


		$this->add_action_buttons();
	}

	function validation($data, $files) {
		return true;
	}

}

class period_edit_form extends moodleform {

	function definition() {
		$mform = $this->_form;

		$mform->addElement('text', 'description', block_exastud_get_string('perioddesc'), array('size' => 50));
		$mform->setType('description', PARAM_TEXT);
		$mform->addRule('description', block_exastud_get_string('error'), 'required', null, 'server', false, false);


		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);

		$mform->addElement('date_time_selector', 'starttime', block_exastud_get_string('starttime'));
		$mform->setType('starttime', PARAM_INT);
		$mform->addRule('starttime', null, 'required', null, 'server');

		$mform->addElement('date_time_selector', 'endtime', block_exastud_get_string('endtime'));
		$mform->setType('endtime', PARAM_INT);
		$mform->addRule('endtime', null, 'required', null, 'server');

		$mform->addElement('date_selector', 'certificate_issue_date', block_exastud_get_string('certificate_issue_date'), [
			'optional' => true,
		]);
		$mform->setType('certificate_issue_date', PARAM_INT);

		$mform->addElement('hidden', 'id');
		$mform->setType('id', PARAM_INT);
		$mform->setDefault('id', 0);

		$mform->addElement('hidden', 'action');
		$mform->setType('action', PARAM_TEXT);
		$mform->setDefault('action', 0);

		$this->add_action_buttons();
	}

}

class student_edit_form extends moodleform {

    function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, array $ajaxformdata = null) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/exastud/classes/exastud_competencetable.php');
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    function definition() {
	    global $DB;
		$mform = &$this->_form;

		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);
		$mform->setDefault('courseid', 0);

		$mform->addElement('hidden', 'classid');
		$mform->setType('classid', PARAM_INT);
		if ($this->_customdata['classid']) {
            $mform->setDefault('classid', $this->_customdata['classid']);
        } else {
            $mform->setDefault('classid', 0);
        }

		$mform->addElement('hidden', 'subjectid');
		$mform->setType('subjectid', PARAM_INT);
		$mform->setDefault('subjectid', 0);

		$mform->addElement('hidden', 'studentid');
		$mform->setType('studentid', PARAM_INT);
		$mform->setDefault('studentid', 0);

		if ($this->_customdata['reporttype']) {
            $mform->addElement('hidden', 'reporttype');
            $mform->setType('reporttype', PARAM_RAW);
            $mform->setDefault('reporttype', $this->_customdata['reporttype']);
        }

        $tagatributes = array();
        $tagattributestext = '';
        if (!@$this->_customdata['canReviewStudent']) {
            $tagatributes['disabled'] = 'disabled';
            $tagattributestext = ' readonly ';
        }

        if (array_key_exists('additionalHidden', $this->_customdata) && is_array($this->_customdata['additionalHidden'])) {
            foreach ($this->_customdata['additionalHidden'] as $paramname => $paramvalue) {
                $mform->addElement('hidden', $paramname);
                $mform->setType($paramname, PARAM_RAW);
                $mform->setDefault($paramname, $paramvalue);
            }
        }

		switch ($this->_customdata['reporttype']) {
            case 'inter':
                // interdisciplinary reviews
                $compeval_type = block_exastud_get_competence_eval_type();
                $selectoptions = block_exastud_get_evaluation_options(true);

                $mform->addElement('header', 'categories', block_exastud_get_string("interdisciplinary_competences"));
                $mform->setExpanded('categories');
                if ($this->_customdata['categories.modified']) {
                    $mform->addElement('static', '', '', $this->_customdata['categories.modified']);
                }
                $categories = $this->_customdata['categories'];

                if (block_exastud_is_bw_active() || $compeval_type == BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE) {

                    $curr_group = '!!--!!';
                    foreach ($categories as $category) {
                        if (isset($category->parent) && $category->parent) {
                            if ($category->parent != $curr_group) {
                                $curr_group = $category->parent;
                                $parent_title = $DB->get_field('block_exastudcate', 'title', ['id' => $curr_group]);
                                $mform->addElement('static', '', '<h3>'.$parent_title.'</h3>', '');
                            }
                        }
                        $id = $category->id.'_'.$category->source;
                        switch ($compeval_type) {
                            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                                $mform->addElement('text', $id, $category->title, $tagatributes);
                                $mform->setType($id, PARAM_FLOAT);
                                break;
                            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                            case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                                $mform->addElement('select', $id, $category->title, $selectoptions, $tagatributes);
                                $mform->setType($id, PARAM_INT);
                                $mform->setDefault($id, key($selectoptions));
                                break;
                        }
                    }
                } else {
                    $mform->addElement('exastud_competencetable', 'radio', $categories, $selectoptions, $this->_customdata['temp_formdata']);
                }
                break;
            case 'social':
                // learn and social
                $inputs = $this->_customdata['template']->get_inputs('all');
                if (!block_exastud_is_bw_active() && block_exastud_can_edit_learnsocial_classteacher($this->_customdata['classid'])) {
                    $template_inputparams = $inputs['learn_social_behavior'];
                } elseif (is_array($inputs) && array_key_exists('learn_social_behavior', $inputs)) {
                    $template_inputparams = $inputs['learn_social_behavior'];
                } else {
                    $template_inputparams = array();
                }
                $vorschlag_limits = array(
                        'cols' => (@$template_inputparams['cols'] && @$template_inputparams['cols'] <= 90) ? $template_inputparams['cols'] : 50,
                        'chars_per_row' => @$template_inputparams['cols'] ? $template_inputparams['cols'] : 80,
                        'rows' => @$template_inputparams['lines'] ? $template_inputparams['lines'] : 8
                );

                $mform->addElement('header', 'vorschlag_header',
                        block_exastud_get_string("learn_and_sociale"));
                $mform->setExpanded('vorschlag_header');
                $mform->addElement('textarea', 'vorschlag', '',
                        array_merge([   //'cols' => $vorschlag_limits['cols'],
                            'cols' => $vorschlag_limits['chars_per_row'] + 3,
                            'rows' => $vorschlag_limits['rows'],
                            'class' => 'limit-input-length',
                            //'wrap' => 'off',
                            'data-rowscharslimit-enable' => 1,
                            'data-rowslimit' => $vorschlag_limits['rows'],
                            'data-charsperrowlimit' => $vorschlag_limits['chars_per_row'],
                            'style' => "width: auto; height: 160px; resize: none; font-family: Arial !important; font-size: 11pt !important;",
                        ], $tagatributes));
                $mform->setType('vorschlag', PARAM_RAW);
                $mform->addElement('static', '', '',
                        block_exastud_get_string('textarea_max').
                            '<span id="max_vorschlag_rows">'.$vorschlag_limits['rows'].' '.block_exastud_get_string('textarea_rows').'</span>'.
                            ' / '.
                            '<span id="max_vorschlag_chars">'.(/*$vorschlag_limits['rows'] * */$vorschlag_limits['chars_per_row']).' '.block_exastud_get_string('textarea_chars').'</span>'.
                            '<span class="exastud-textarea-left-block">'.block_exastud_get_string('textarea_charsleft').': '.
                            '<span id="left_vorschlag_rows"><span class="exastud-value">-</span> <span class="exastud-wording">'.block_exastud_get_string('textarea_rows').'</span></span>'.
                            ' / '.
                            '<span id="left_vorschlag_chars"><span class="exastud-value">-</span> <span class="exastud-wording">'.block_exastud_get_string('textarea_chars').'</span></span>'.
                            '</span>');
                break;
            default:
                // subjectdata
                $subjectObjData = $DB->get_record('block_exastudsubjects', ['id' => $this->_customdata['subjectid']]);
                // subject review
                $inputs = $this->_customdata['template']->get_inputs('all');
                $template_inputparams = array();
                // for Wahlpflicht-bereich and for Profil-fach we can have different settings
                if (strpos($subjectObjData->title, 'Wahlpflichtfach') === 0 && array_key_exists('subject_elective', $inputs)) {
                    $template_inputparams = $inputs['subject_elective'];
                } elseif (strpos($subjectObjData->title, 'Profilfach') === 0 && array_key_exists('subject_profile', $inputs)) {
                    $template_inputparams = $inputs['subject_profile'];
                    // if this $template_inputparams has not limits - use limits from BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH
                    if ($template_inputparams['lines'] == 999 || $template_inputparams['cols'] == 999) {
                        $standardTemplateInputs = \block_exastud\print_templates::get_inputs_for_template(BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH, BLOCK_EXASTUD_TEMPLATE_DEFAULT_ID_BP2004_16_ZERTIFIKAT_FUER_PROFILFACH);
                        if (array_key_exists('besondere_kompetenzen', $standardTemplateInputs)) {
                            if ($template_inputparams['lines'] == 999) {
                                $template_inputparams['lines'] = $standardTemplateInputs['besondere_kompetenzen']['lines'];
                            }
                            if ($template_inputparams['cols'] == 999) {
                                $template_inputparams['cols'] = $standardTemplateInputs['besondere_kompetenzen']['cols'];
                            }
                        }
                    }
                }
                if (count($template_inputparams) == 0 && is_array($inputs) && array_key_exists('subjects', $inputs)) {
                    $template_inputparams = $inputs['subjects'];
                }
                $subject_limits = array(
                        'cols' => (@$template_inputparams['cols'] && @$template_inputparams['cols'] <= 90) ? $template_inputparams['cols'] : 50,
                        'chars_per_row' => @$template_inputparams['cols'] ? $template_inputparams['cols'] : 80,
                        'rows' => @$template_inputparams['lines'] ? $template_inputparams['lines'] : 8
                );
                $mform->addElement('header', 'review_header', block_exastud_trans("de:Fachkompetenzen"));
                $mform->setExpanded('review_header');
                if ($this->_customdata['review.modified']) {
                    $mform->addElement('static', '', '', $this->_customdata['review.modified']);
                }

                $mform->addElement('textarea', 'review', '',
                    array_merge([   //'cols' => $subject_limits['cols'],
                            'cols' => $subject_limits['chars_per_row'] + 3,
                            'rows' => $subject_limits['rows'],
                            //'wrap' => 'off',
                            'class' => 'limit-input-length',
                            'data-rowscharslimit-enable' => 1,
                            'data-rowslimit' => $subject_limits['rows'],
                            'data-charsperrowlimit' => $subject_limits['chars_per_row'],
                            'style' => "width: auto; height: 160px; resize: none; font-family: Arial !important; font-size: 11pt !important;",
                ], $tagatributes));
                $mform->setType('review', PARAM_RAW);
                $mform->addElement('static', 'hint', "",
                        block_exastud_get_string('textarea_max').
                                '<span id="max_review_rows">'.$subject_limits['rows'].' '.block_exastud_get_string('textarea_rows').'</span>'.
                                ' / '.
                                '<span id="max_review_chars">'.(/*$subject_limits['rows'] * */$subject_limits['chars_per_row']).' '.block_exastud_get_string('textarea_chars').'</span>'.
                                '<span class="exastud-textarea-left-block">'.block_exastud_get_string('textarea_charsleft').': '.
                                '<span id="left_review_rows"><span class="exastud-value">-</span> <span class="exastud-wording">'.block_exastud_get_string('textarea_rows').'</span></span>'.
                                ' / '.
                                '<span id="left_review_chars"><span class="exastud-value">-</span> <span class="exastud-wording">'.block_exastud_get_string('textarea_chars').'</span></span>'.
                                '</span>');

                // grades, niveaus
                $mform->addElement('header', 'grade_header', block_exastud_get_string("grade_and_difflevel"));
                $mform->setExpanded('grade_header');
                if ($this->_customdata['grade.modified']) {
                    $mform->addElement('static', '', '', $this->_customdata['grade.modified']);
                }
                $niveauarray=array();

                $niveauarray[] =& $mform->createElement('select', 'niveau', block_exastud_get_string('Niveau'), ['' => ''] + block_exastud\global_config::get_niveau_options($subjectObjData->no_niveau), $tagatributes);
                $niveauarray[] =& $mform->createElement('static', '', "", "");
                $niveauarray[] =& $mform->createElement('static', 'lastPeriodNiveau', "", block_exastud_trans('de:lastPeriodNiveau'));
                $niveauarray[] =& $mform->createElement('static', '', "", ")");
                $mform->addGroup($niveauarray, 'niveauarray',  block_exastud_get_string('Niveau'), array("( ", block_exastud_get_string('last_period'). ' ', ' '), false);

                $gradearray = array();
                if ($this->_customdata['grade_options'] && is_array($this->_customdata['grade_options'])) {
                    $gradearray[] =& $mform->createElement('select', 'grade', block_exastud_get_string('Note'),
                            ['' => ''] + $this->_customdata['grade_options'], $tagatributes);
                } else {
                    $grade = $mform->createElement('text', 'grade', block_exastud_get_string('Note'), $tagatributes);
                    $mform->setType('grade', PARAM_RAW);
                    $gradearray[] =& $grade;
                }
                $gradearray[] =& $mform->createElement('static', '', "", "");
                $gradearray[] =& $mform->createElement('static', 'lastPeriodGrade', "", block_exastud_trans('de:lastPeriodGrade'));
                $gradearray[] =& $mform->createElement('static', '', "", ")");
                $mform->addGroup($gradearray, 'gradearray', block_exastud_get_string('Note'), array('( ',  block_exastud_get_string('last_period').' ', " " ), false);

                $mform->addElement('static', 'exacomp_grades', block_exastud_get_string('suggestions_from_exacomp'), $this->_customdata['exacomp_grades']);
        }

        if (@$this->_customdata['canReviewStudent']) {
            $this->add_action_buttons(false);
        }
	}

    function validation($data, $files) {
        $custom_errors = array();
        // compare textareas: rows and cols must be good
        $fields = array_keys($data);
        $mform = $this->_form;
        foreach ($fields as $field) {
            if ($mform->elementExists($field)) {
                $element = $mform->getElement($field);
                if ($element->_type == 'textarea' && $data[$field] != '') {
                    $rowsfromstring = preg_split("/[\r\n]+/", $data[$field]);
                    $datawithoutlb = implode(' ', $rowsfromstring);
                    $charsperrowlimit = $element->_attributes['data-charsperrowlimit'];
                    $rows_limit = $element->_attributes['data-rowslimit'];
                    $rowsLeft = $rows_limit - count($rowsfromstring);
                    // real line can be without linebreaks, so - check only full text length
                    $fullLengthLimit = $rows_limit * $charsperrowlimit;
                    if (mb_strlen($datawithoutlb) > $fullLengthLimit) {
                        $custom_errors[$field] = block_exastud_get_string('template_textarea_limits_error');
                    } else {
                        foreach ($rowsfromstring as $rS) {
                            $addedLines = 0;
                            if (mb_strlen($rS) > $charsperrowlimit) {
                                $addedLines = floor((mb_strlen($rS) - 1) / $charsperrowlimit);
                            }
                            $rowsLeft = $rowsLeft - $addedLines;
                        }
                    }
                    /*if ($element->_attributes['cols'] > 0) {
                        $maxlength = max(array_map('strlen', $rowsfromstring));
                        if ($maxlength > $element->_attributes['cols']) {
                            $custom_errors[$field] = block_exastud_get_string('template_textarea_limits_error');
                        }
                    }*/
                    if ($rows_limit > 0 && (count($rowsfromstring) > $rows_limit) || $rowsLeft < 0) {
                        $custom_errors[$field] = block_exastud_get_string('template_textarea_limits_error');
                    }
                }
            }
        }
        $parent_result = parent::validation($data, $files);
        return $parent_result + $custom_errors;
    }

}

class student_other_data_form extends moodleform {
    
    private $list_matrix_checkboxes = array(); // for html changes

    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null,
            $editable = true, $ajaxformdata = null) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/exastud/classes/exastud_htmltag.php');
        MoodleQuickForm::registerElementType('exastud_htmltag', $CFG->dirroot.'/blocks/exastud/classes/exastud_htmltag.php', 'block_exastud_htmltag');
        require_once($CFG->dirroot.'/blocks/exastud/lib/reports_lib.php');
        require_once($CFG->dirroot.'/blocks/exastud/classes/exastud_reportmatrix.php');
        require_once($CFG->dirroot.'/blocks/exastud/classes/exastud_competencetable.php');
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

	function definition() {
        global $CFG, $DB;
		$mform = &$this->_form;
		if (array_key_exists('templateid', $this->_customdata)) {
            //$defaulttemplatesettings = block_exastud_get_default_templates($this->_customdata['templateid']);
            if (!$defaulttemplatesettings = block_exastud_get_default_templates($this->_customdata['templateid'])) {
                $defaulttemplatesettings = array();
            }
        } else {
            $defaulttemplatesettings = array();
        }
        $student = null;
        if (array_key_exists('student', $this->_customdata)) {
            $student = $this->_customdata['student'];
        }
        $courseid = 1;
        if (array_key_exists('courseid', $this->_customdata)) {
            $courseid = $this->_customdata['courseid'];
        }

        if (array_key_exists('classid', $this->_customdata)) {
            $classid = $this->_customdata['classid'];
        }

        $tagatributes = array();
        $tagattributestext = '';
        if (!@$this->_customdata['canReviewStudent']) {
            $tagatributes['disabled'] = 'disabled';
            $tagattributestext = ' readonly ';
        }

        // cross reviews if it is not for BW
        if (@$this->_customdata['cross_review'] && is_array(@$this->_customdata['cross_categories'])) {
            $cross_categories = $this->_customdata['cross_categories'];
            if (count($cross_categories)) {
                $compeval_type = block_exastud_get_competence_eval_type();
                $selectoptions = block_exastud_get_evaluation_options(true);
                $mform->addElement('header', 'categories', block_exastud_get_string("interdisciplinary_competences"));
                $mform->setExpanded('categories');
                if (array_key_exists('categories.modified', $this->_customdata) && $this->_customdata['categories.modified']) {
                    $mform->addElement('static', '', '', $this->_customdata['categories.modified']);
                }

                if ($compeval_type == BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE) {
                    $noteLimit = 6; // TODO: where is limit? Count of?: exastud/configuration_global.php?action=evalopts
                    $a = (object)['limit' => $noteLimit];
                    $mform->addElement('html', '<div class="alert alert-info">'.block_exastud_get_string('message_interdisciplinary_competences_notes_limit', 'block_exastud', $a).'</div>');
                    $curr_group = '!!--!!';
                    foreach ($cross_categories as $category) {
                        if (isset($category->parent) && $category->parent) {
                            if ($category->parent != $curr_group) {
                                $curr_group = $category->parent;
                                $parent_title = $DB->get_field('block_exastudcate', 'title', ['id' => $curr_group]);
                                $mform->addElement('static', '', '<h3>'.$parent_title.'</h3>', '');
                            }
                        }
                        $id = $category->id.'_'.$category->source;
                        $mform->addElement('text', $id, $category->title, $tagatributes);
                        $mform->setType($id, PARAM_FLOAT);
                    }
                } else {
                    if ($compeval_type == BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT) {
                        $noteLimit = block_exastud_get_competence_eval_typeevalpoints_limit();
                        $a = (object) ['limit' => $noteLimit];
                        $mform->addElement('html', '<div class="alert alert-info">'.block_exastud_get_string('message_interdisciplinary_competences_points_limit', 'block_exastud', $a).'</div>');
                    }
                    $mform->addElement('exastud_competencetable', 'radio', $cross_categories, $selectoptions, $this->_customdata['temp_formdata']);
                }

            }
        }

        $bilingualTemplates = array_keys(block_exastud_get_bilingual_reports());
        $tempCurrentElementGroup = '';
        $addFormElement = function($dataid, $input, $pObj) use ($mform, $defaulttemplatesettings, $bilingualTemplates, &$tempCurrentElementGroup, $student, $courseid, $CFG) {
            static $previousDataid;
            static $previousType;
            $activate_close_before_modifiedfield = false; // we need it because 'static' element does not have relation by element id (isn't ?)

            // close header after matrix
            if ($previousType == 'matrix' && $input['type'] != 'matrix') {
                $mform->closeHeaderBefore($dataid);
            }
            // close header before new element if before it was a language niveaus (spa)
            if ($previousDataid == 'spa_niveau') {
                if ($mform->elementExists('header_'.$previousDataid)) {
                    if (@$pObj->_customdata['modified'] && $input['type'] == 'textarea') {
                        // if 'modified' exists - next element will be closed autmatically.
                        // now - only for textareas !!!!!
                        $activate_close_before_modifiedfield = true;
                    } else {
                        $mform->closeHeaderBefore($dataid);
                    }
                }
            }
            switch ($input['type']) {
                case '':
                case 'textarea':
                    $elementSubTitle = '';
                    $elementTitle = $input['title'];
                    // bilingual form has some another behaviour. We need to group couples of inputs by field title
                    if (array_key_exists('id', $defaulttemplatesettings) && in_array($defaulttemplatesettings['id'], $bilingualTemplates)) {
                        preg_match('#\((.*?)\)#', $elementTitle, $match);
                        $groupName = $match[1];
                        if ($groupName != $tempCurrentElementGroup) {
                            $mform->addElement('header', 'header_'.$dataid, $groupName);
                            $tempCurrentElementGroup = $groupName;
                        }
                        $elementSubTitle =  preg_replace("/\([^)]+\)/", '', $elementTitle);
                    } else {
                        $mform->addElement('header', 'header_'.$dataid, $elementTitle);
                    }
                    $maxchars = '550';
                    if (@$pObj->_customdata['modified']) {
                        $mform->addElement('static', 'modified_'.$dataid, '', $pObj->_customdata['modified']);
                    }

                    if (empty($input['lines'])) {
                        $input['lines'] = 8;
                    }
                    if (empty($input['cols'])) {
                        $input['cols'] = 45;
                    }

                    $textarea_limits = array(
                            'cols' => (@$input['cols'] && @$input['cols'] <= 90) ? $input['cols'] : 50,
                            'chars_per_row' => @$input['cols'] ? $input['cols'] : 80,
                            'rows' => @$input['lines'] ? $input['lines'] : 8,
                            'maxchars' => @$input['maxchars'] ? $input['maxchars'] : 0
                    );

                    if ($textarea_limits['rows'] == 1) {
                        if ($mform->elementExists('header_'.$dataid)) {
                            $mform->setExpanded('header_'.$dataid);
                        }
                    }

                    $height = $input['lines'] * 22;
                    if ($input['lines'] == 1) {
                        $height = 35;
                    }
                    $mform->addElement('textarea', $dataid, $elementSubTitle, [
                        //'cols' => $input['cols'],
                            'cols' => $textarea_limits['chars_per_row'] + 3,
                            'rows' => $input['lines'],
                        //'wrap' => 'off',
                            'class' => 'limit-input-length',
                            'data-rowscharslimit-enable' => 1,
                            'data-rowslimit' => $textarea_limits['rows'],
                            'data-charsperrowlimit' => $textarea_limits['chars_per_row'],
                            'data-maxcharslimit' => $textarea_limits['maxchars'],
                            'style' => "width: auto; "./*($input['cols'] * 15).*/" height: ".$height."px; resize: none; font-family: Arial !important; font-size: 11pt !important;",
                    ]);
                    $mform->setType($dataid, PARAM_RAW);
                    $mform->addElement('static', '', '',
                            block_exastud_get_string('textarea_max').
                            '<span id="max_'.$dataid.'_rows">'.$textarea_limits['rows'].' '.block_exastud_get_string('textarea_rows').'</span>'.
                            ' / '.
                            '<span id="max_'.$dataid.'_chars">'.(/*$textarea_limits['rows'] * */$textarea_limits['chars_per_row']).' '.block_exastud_get_string('textarea_chars').'</span>'.
                            ((array_key_exists('maxchars', $textarea_limits) && $textarea_limits['maxchars'] > 0) ?
                                    ' / '.'<span id="max_'.$dataid.'_maxchars">'.(/*$textarea_limits['rows'] * */$textarea_limits['maxchars']).' '.block_exastud_get_string('textarea_maxchars').'</span>'
                                    : ''
                            ).
                            '<span class="exastud-textarea-left-block">'.block_exastud_get_string('textarea_charsleft').': '.
                            '<span id="left_'.$dataid.'_rows"><span class="exastud-value">-</span> <span class="exastud-wording">'.block_exastud_get_string('textarea_rows').'</span></span>'.
                            ' / '.
                            '<span id="left_'.$dataid.'_chars"><span class="exastud-value">-</span> <span class="exastud-wording">'.block_exastud_get_string('textarea_chars').'</span></span>'.
                            /*' / '.
                            '<span id="left_'.$dataid.'_maxchars"><span class="exastud-value">-</span> '.block_exastud_get_string('textarea_maxchars').'</span>'.*/
                            '</span>');
                    break;
                case 'text':
                    $mform->addElement('text', $dataid, $input['title']);
                    $mform->setType($dataid, PARAM_RAW);
                    break;
                case 'select':
                    switch ($dataid) {
                        case 'student_transfered':
                            $gender = block_exastud_get_user_gender($this->_customdata['student']->id);
                            switch ($gender) {
                                case 'male':
                                    $input['values'] = array_slice($input['values'], 2); // delete first TWO values from selectbox
                                    break;
                                case 'female':
                                    $input['values'] = array_slice($input['values'], 0, 2); // use only first TWO values from selectbox
                                    break;
                            }
                            break;
                    }
                    $mform->addElement('select', $dataid, $input['title'], ['' => ''] + $input['values']);
                    $mform->setType($dataid, PARAM_RAW);
                    break;
                case 'image':
                    $mform->addElement('filemanager', 'images['.$dataid.']', $input['title'], null,
                            array(
                                    'subdirs' => 0,
                                    'maxbytes' => intval($input['maxbytes']),
                                    'maxfiles' => 1,
                                    'accepted_types' => array('web_image'))
                    );
                    break;
                case 'userdata':
                    $tempObj = null;//new stdClass();
                    $realvalue = block_exastud_get_report_userdata_value($tempObj, '---', $student->id, $input['userdatakey']);
                    $url = block_exastud_global_useredit_link($student->id, $courseid);

                    if ($url) {
                        $edit_message = '<a href="'.$url.'" target="_blank" title="'.block_exastud_get_string('report_edit_userprofile').'"><img src="'.$CFG->wwwroot.'/blocks/exastud/pix/edit.png" /></a>&nbsp;';
                    } else {
                        $edit_message = '<img src="'.$CFG->wwwroot.'/blocks/exastud/pix/info.png" title="'.block_exastud_get_string('report_userprofile_field_info').' '.block_exastud_get_string('report_edit_userprofile_noaccess').'"/>&nbsp;';
                    }
                    $realvalue = $edit_message.$realvalue;
                    $mform->addElement('static', 'static_'.$dataid, $input['title'], $realvalue);
                    break;
                case 'matrix':
                    $mform->addElement('header', 'header_'.$dataid, $input['title']);
                    $mform->addElement('exastud_reportmatrix', $dataid, $input);
/*                    switch ($input['matrixtype']) {
                        case 'checkbox':
                            // table with checkboxes
                            $headercells = array();
                            foreach ($input['matrixcols'] as $colTitle) {
                                $headercells[] = $mform->createElement('text', $colTitle, '');
                                $mform->setType($dataid.'[matrixheader]['.$colTitle.']', PARAM_RAW);
                                $mform->setDefault($dataid.'[matrixheader]['.$colTitle.']', $colTitle);
                            }
                            $mform->addGroup($headercells, $dataid.'[matrixheader]', ' ');
                            $mform->freeze($dataid.'[matrixheader]'); // make them readonly
                            foreach ($input['matrixrows'] as $rowTitle) {
                                $cells = array();
                                foreach ($input['matrixcols'] as $colTitle) {
                                    $cells[] = $mform->createElement('checkbox', $colTitle);
                                    $mform->setType($dataid.'['.$rowTitle.']['.$colTitle.']', PARAM_RAW);
                                    $this->list_matrix_checkboxes[] = $dataid.'['.$rowTitle.']['.$colTitle.']';
                                }
                                $mform->addGroup($cells, $dataid.'['.$rowTitle.']', $rowTitle);
                            }
                            break;
                        case 'text':
                            // table with text inputs
                            $headercells = array();
                            foreach ($input['matrixcols'] as $colTitle) {
                                //$headercells[] = $mform->createElement('static', 'static_'.$dataid.'_'.$colTitle, $colTitle, $colTitle);
                                $headercells[] = $mform->createElement('text', $colTitle, '');
                                $mform->setType($dataid.'[matrixheader]['.$colTitle.']', PARAM_RAW);
                                $mform->setDefault($dataid.'[matrixheader]['.$colTitle.']', $colTitle);
                            }
                            $mform->addGroup($headercells, $dataid.'[matrixheader]', ' ');
                            $mform->freeze($dataid.'[matrixheader]'); // make them readonly
                            foreach ($input['matrixrows'] as $rowTitle) {
                                $cells = array();
                                //$cells[] = $mform->createElement('static', 'static_'.$dataid.'['.$rowTitle.']', $rowTitle, $rowTitle);
                                foreach ($input['matrixcols'] as $colTitle) {
                                    $cells[] = $mform->createElement('text', $colTitle, '');
                                    $mform->setType($dataid.'['.$rowTitle.']['.$colTitle.']', PARAM_RAW);
                                }
                                $mform->addGroup($cells, $dataid.'['.$rowTitle.']', $rowTitle);
                            }
                            break;
                        case 'radio':
                        default:
                            // every row has selectboxes from columns
                            $options = $input['matrixcols'];
                            foreach ($input['matrixrows'] as $row) {
                                $mform->addElement('select', $dataid.'['.$row.']', $row, $options);
                            }
                    }*/
                    break;
                default:
                    $mform->addElement('header', 'header_'.$dataid, $input['title']);
                    $mform->setExpanded('header_'.$dataid);
            }
            $previousDataid = $dataid;
            $previousType = $input['type'];
        };
        
        // grouping by header/body/footer
        // fixed list of inputs are in the header/footer. other list is in the body
        $pageParts = array (
                'header' => [
                        'title' => block_exastud_get_string('review_student_other_data_header'),
                        'inputs' => (@$defaulttemplatesettings['inputs_header'] ? $defaulttemplatesettings['inputs_header'] : array('class')),
                ],
                'body' => [
                        'title' => block_exastud_get_string('review_student_other_data_body'),
                        'inputs' => null, // all other
                ],
                'footer' => [
                        'title' => block_exastud_get_string('review_student_other_data_footer'),
                        'inputs' => (@$defaulttemplatesettings['inputs_footer'] ? $defaulttemplatesettings['inputs_footer'] : array('ags', 'comments', 'comments_short')),
                ],
        );

        foreach ($pageParts as $key => $pagePart) {
            $ff = $mform->addElement('exastud_htmltag',
                                '<h2 class="exastud-student-review-block-header">'.$pagePart['title'].'</h2>');
            $ff->setName('blockheader_'.$key);
            // get last inserted key and use it later for manage this element
            $clonetempt = $mform->_elements;
            end($clonetempt);
            $elementKey = key($clonetempt);
            $showBlock = false;
            foreach ($this->_customdata['categories'] as $dataid => $input) {
                if (    ($pagePart['inputs'] && in_array($dataid, $pagePart['inputs'])) // for header and footer
                        ||
                        (!$pagePart['inputs'] && !in_array($dataid, $pageParts['footer']['inputs'])) // for body
                ) {
                    if (array_key_exists('type', $input)) {
                        $addFormElement($dataid, $input, $this);
                        unset($this->_customdata['categories'][$dataid]);
                        $showBlock = true;
                    }
                }
            }
            // hide block header if it is empty
            if (!$showBlock) {
                //$mform->removeElement('blockheader_'.$key);
                unset($mform->_elements[$elementKey]);
            }
        }
        if (@$this->_customdata['type'] != BLOCK_EXASTUD_DATA_ID_CROSS_COMPETENCES
                //|| ($this->_customdata['type'] == BLOCK_EXASTUD_DATA_ID_CROSS_COMPETENCES && block_exastud_can_edit_crosscompetences_classteacher($classid))
                || (@$this->_customdata['type'] == BLOCK_EXASTUD_DATA_ID_CROSS_COMPETENCES && !block_exastud_is_bw_active() && @$classid && block_exastud_can_edit_crosscompetences_classteacher($classid))
                || (@$this->_customdata['class_review'])
        ) {
            $this->add_action_buttons(false);
        }
	}

    function validation($data, $files) {
        $custom_errors = array();
        // compare textareas: rows and cols must be good
        $fields = array_keys($data);
        $mform = $this->_form;
        foreach ($fields as $field) {
            if ($mform->elementExists($field)) {
                $element = $mform->getElement($field);
                if ($element->_type == 'textarea' && $data[$field] != '') {
                    $rowsfromstring = preg_split("/[\r\n]+/", $data[$field]);
                    $datawithoutlb = implode(' ', $rowsfromstring);
                    $charsperrowlimit = $element->_attributes['data-charsperrowlimit'];
                    $rows_limit = $element->_attributes['data-rowslimit'];
                    $rowsLeft = $rows_limit - count($rowsfromstring);
                    // real line can be without linebreaks, so - check only full text length
                    $fullLengthLimit = $rows_limit * $charsperrowlimit;
                    if (mb_strlen($datawithoutlb) > $fullLengthLimit) {
                        $custom_errors[$field] = block_exastud_get_string('template_textarea_limits_error');
                    } else {
                        foreach ($rowsfromstring as $rS) {
                            $addedLines = 0;
                            if (mb_strlen($rS) > $charsperrowlimit) {
                                $addedLines = floor((mb_strlen($rS) - 1) / $charsperrowlimit);
                            }
                            $rowsLeft = $rowsLeft - $addedLines;
                        }
                    }
                    /*if ($element->_attributes['cols'] > 0) {
                        $maxlength = max(array_map('strlen', $rowsfromstring));
                        if ($maxlength > $element->_attributes['cols']) {
                            $custom_errors[$field] = block_exastud_get_string('template_textarea_limits_error');
                        }
                    }*/
                    if ($rows_limit > 0 && (count($rowsfromstring) > $rows_limit) || $rowsLeft < 0) {
                        $custom_errors[$field] = block_exastud_get_string('template_textarea_limits_error');
                    }
                }
            }
        }
        $parent_result = parent::validation($data, $files);
        return $parent_result + $custom_errors;
    }

}

class reportsettings_edit_form extends moodleform {

    protected $allSecondaryFields = array(
            'year',
            'report_date',
            'student_name',
            'date_of_birth',
            'place_of_birth',
            'learning_group',
            'class',
            'focus',
            'learn_social_behavior',
            'subjects',
            'comments',
            'subject_elective',
            'subject_profile',
            'projekt_thema',
            'ags',
        );
    protected $fieldsWithAdditionalParams = array(
        'class',
        'focus',
        'learn_social_behavior',
        'subjects',
        'comments',
        'subject_elective',
        'subject_profile',
        'projekt_thema',
        'ags',
    );
    protected $notForNonBW = array(
        'ags',
        'subjects',
        'subject_elective',
        'subject_profile',
        'projekt_thema',
        'focus',
        'class',
        'learn_social_behavior',
    );
    protected $fieldGroups = array(
            'default' => array('year', 'report_date', 'student_name', 'date_of_birth', 'place_of_birth', 'learning_group', 'class'), // markers, which do not need to be checked. If this marker is exists in template - it will be changed
            'classTeacher' => array('comments'),
    );
    protected $errorsInForm = array();

    protected $input_types = array('textarea', 'text', 'select', 'header', 'image', 'userdata', 'matrix');
    protected $radioattributes = array(); // html tag attributes for radiobuttons

    protected $additionalData = null;

    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null,
            $editable = true, $ajaxformdata = null) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/exastud/classes/exastud_htmltag.php');
        MoodleQuickForm::registerElementType('exastud_htmltag', $CFG->dirroot.'/blocks/exastud/classes/exastud_htmltag.php', 'block_exastud_htmltag');
        //require_once($CFG->dirroot.'/blocks/exastud/classes/exastud_select.php');
        //MoodleQuickForm::registerElementType('exastud_select', $CFG->dirroot.'/blocks/exastud/classes/exastud_select.php', 'block_exastud_select');
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    public function getAllSecondaryFields() {
        $allSecondaryFields = $this->allSecondaryFields;
        if (!block_exastud_is_bw_active()) {
            $allSecondaryFields = array_diff($allSecondaryFields, $this->notForNonBW);
        }
        return $allSecondaryFields;
    }

    public function getFieldsWithAdditionalParams() {
        return $this->fieldsWithAdditionalParams;
    }

    public function getAdditionalData() {
        return $this->additionalData;
    }

    public function setAdditionalData($data) {
        $this->additionalData = $data;
    }

    function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        // additional html before all
        $mform->addElement('exastud_htmltag', '<style>
            h2.exastud-report-settings-group[data-grouptoggler]:before {
                content: url("'.$CFG->wwwroot.'/blocks/exastud/pix/expanded.png");
            }
            h2.exastud-report-settings-group[data-groupHidden=\'1\']:before {
                content: url("'.$CFG->wwwroot.'/blocks/exastud/pix/collapsed.png");
            }
        </style>');

        $mform->addElement('text', 'title', block_exastud_get_string('report_settings_setting_title'), array('size' => 50));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', block_exastud_get_string('error'), 'required', null, 'server', false, false);

        // BP
        if (block_exastud_is_bw_active()) {
            $bpList = g::$DB->get_records_menu('block_exastudbp', null, 'sorting', '*');
            $bpList = array(0 => '') + $bpList;
            $mform->addElement('select', 'bpid', block_exastud_get_string('report_settings_setting_bp'), $bpList);
            $mform->setType('bpid', PARAM_RAW);
        }

        // hidden
        $mform->addElement('advcheckbox', 'hidden', block_exastud_get_string('report_settings_setting_hidden'));
        $mform->setType('hidden', PARAM_INT);

        // RS or HS
        if (block_exastud_is_bw_active()) {
            $rs_hs = array('' => '', 'RS' => 'RS', 'HS' => 'HS');
            $mform->addElement('select', 'rs_hs', block_exastud_get_string('report_settings_setting_rs_hs'), $rs_hs);
            $mform->setType('rs_hs', PARAM_TEXT);
        }

        // category
        if (block_exastud_is_bw_active()) {
            //$mform->addElement('text', 'category', block_exastud_get_string('report_settings_setting_category'), array('size' => 50));
            // --- $mform->addRule('category', block_exastud_get_string('error'), 'required', null, 'server', false, false);
            $categoryGroup = array();
            $categoryGroup[] = $mform->createElement('text', 'category', block_exastud_trans('report_settings_setting_category'),
                    array('size' => 50));
            $mform->setType('category', PARAM_TEXT);
            $infoicon = '<img class=""
                        src="'.$CFG->wwwroot.'/blocks/exastud/pix/info.png"                          
                        title="'.$this->_customdata['category_infomessage'].'" />';
            $categoryGroup[] =& $mform->createElement('static', 'infocategory', '', $infoicon);
            $mform->addGroup($categoryGroup, 'category', block_exastud_trans('report_settings_setting_category'), ' ', false);
        }

        // template
        //$templateList = block_exastud_get_report_templates('-all-');
        $templateList = block_exastud_get_template_files();
        if ($this->_customdata['report_id'] > 0) {
            $reportsetting = $DB->get_record('block_exastudreportsettings', array('id' => $this->_customdata['report_id']));
            $currentTemplate = $reportsetting->template;
            if (!array_key_exists($currentTemplate, $templateList)) {
                $templateList[$currentTemplate] = $currentTemplate;
                $errA = (object)array('filename' => $currentTemplate);
                $this->errorsInForm['template'] = block_exastud_get_string('report_settings_no_template_file', null, $errA);
            }
        }
        $mform->addElement('select', 'template', block_exastud_get_string('report_settings_setting_template'), $templateList);
        $mform->setType('template', PARAM_RAW);
        // templates for JS
        // add link to "template" selectbox
        $filelinks = array();
        $pathTo = $CFG->wwwroot;
        if (count($templateList)) {
            $exts = array('dotx', 'docx');
            foreach ($templateList as $tmpl) {
                $realfilename = '';
                foreach ($exts as $ext) {
                    if (file_exists(BLOCK_EXASTUD_TEMPLATE_DIR.'/'.$tmpl.'.'.$ext)) {
                        $realfilename = $tmpl.'.'.$ext;
                        break;
                    }
                }
                if ($realfilename) {
                    $filelinks[$tmpl] = $pathTo.'/blocks/exastud/template/'.$realfilename;
                }
            }
        }
        $mform->addElement('exastud_htmltag', '<script>var templateLinks = \''.json_encode($filelinks).'\';</script>');
        // upload new file
        $mform->addElement('checkbox', 'overwritefile', block_exastud_get_string('report_settings_upload_new_filetemplate_overwrite'));
        $mform->addElement('filepicker', 'newfileupload', block_exastud_get_string('report_settings_upload_new_filetemplate'),
                null, array('accepted_types' => array('.docx', '.dotx')));

        // grades
        if (block_exastud_is_bw_active()) {
            $mform->addElement('textarea', 'grades', block_exastud_get_string('report_settings_setting_grades'),
                    array('rows' => 3, 'cols' => 50));
            $mform->setType('grades', PARAM_TEXT);
        }

        $fieldlist = $this->getAllSecondaryFields();

        // for non BW we need special ordering and grouping. Prepare them
        if (!block_exastud_is_bw_active()) {
            $orderedInputs = array();
            // at first - all elements from groupe
            foreach ($this->fieldGroups as $gkey => $gfields) {
                foreach ($gfields as $f) {
                    if (in_array($f, $fieldlist)) {
                        $orderedInputs[] = $f;
                    }
                }
            }
            // add other elements (not in grouped list)
            foreach ($fieldlist as $field) {
                if (!in_array($field, $orderedInputs)) {
                    $orderedInputs[] = $field;
                }
            }
            $fieldlist = $orderedInputs;
        }

        $currentGroup = '----';
        $otherStarted = false;

        foreach ($fieldlist as $field) {
            $newGroup = null;
            if (!block_exastud_is_bw_active()) {
                foreach ($this->fieldGroups as $gk => $gfields) {
                    if (in_array($field, $gfields)) {
                        $newGroup = $gk;
                    }
                }
                if ($newGroup && $currentGroup != $newGroup) {
                   $mform->addElement('static', '', '', '<h2 class="exastud-report-settings-group" data-groupToggler="'.$newGroup.'">'.
                            block_exastud_get_string('report_settings_group_title_'.$newGroup).'</h2>
                                                      <span class="exastud-report-settings-group-description">'.
                            block_exastud_get_string('report_settings_group_description_'.$newGroup).'</span>');
                    $currentGroup = $newGroup;
                }
                // if element is not in a group at all - after all kwnown groups
                if ($newGroup === null && !$otherStarted) {
                    $mform->addElement('static', '', '', '<h2 class="exastud-report-settings-group">'.
                            block_exastud_get_string('report_settings_group_title_other').'</h2>
                            <span class="exastud-report-settings-group-description">'.
                            block_exastud_get_string('report_settings_group_description_other').'</span>');
                    $otherStarted = true;
                }
            }
            $mform->addElement('exastud_htmltag', '<div id="exastud-additional-params-block-'.$field.'" class="exastud-setting-block" data-field="'.$field.'" '.($newGroup !== null ? ' data-fieldGroup = "'.$newGroup.'" ' : '').'>');
            if (in_array($field, $this->fieldsWithAdditionalParams)) {
                $mform->addElement('exastud_htmltag', '<hr />');
            }

            if ($newGroup == 'default') {
                $mform->addElement('static', '', '', '
                    <span class="exastud-report-settings-default-marker">${'.$field.'}:</span>
                    <span class="exastud-report-settings-default-marker">'.block_exastud_get_string('report_settings_setting_'.str_replace('_', '', $field)).'</span>
                    ');
                $mform->addElement('hidden', $field);
                $mform->setType($field, PARAM_INT);
                $mform->setDefault($field, 1);
            } else {
                $mform->addElement('advcheckbox', $field, block_exastud_get_string('report_settings_setting_'.str_replace('_', '', $field)), '', null, array(0, 1));
            }
            if (in_array($field, $this->fieldsWithAdditionalParams)) {
                // show with additional params
                $input_size = 5;
                $titleGroup = [];
                // wrapper
                //$mform->addElement('exastud_htmltag', '<div>');
                // key: used as marker in the docx
                $titleGroup[] = $mform->createElement('hidden', $field.'_key', $field);
                $mform->setType($field.'_key', PARAM_RAW);
                // title
                $titleGroup[] = $mform->createElement('text', $field.'_title', block_exastud_trans('de: Titel'), 'size = \'45\'');
                $mform->setType($field.'_title', PARAM_TEXT);
                $titleGroup[] = $mform->createElement('exastud_htmltag',
                        '<div class="exastud-template-settings-group group-'.$field.' main-params">
                            <span class="exastud-report-marker" data-for="'.$field.'">Marker: ${}</span>
                         </div>');
                $mform->addGroup($titleGroup, $field.'_mainparams', '', ' ', false);
                // type of parameter
                $radiotype = array();
                foreach ($this->input_types as $type) {
                    $radiotype[] = $mform->createElement('radio', $field.'_type', '', block_exastud_get_string('report_setting_type_'.$type), $type, $this->radioattributes);
                }
                $mform->addGroup($radiotype, $field.'_typeradiobuttons', '', array(' '), false);

                // paramaters for textarea
                $tempGroup = array();
                $tempGroup[] =& $mform->createElement('text', $field.'_rows', block_exastud_get_string('report_settings_countrows_fieldtitle'), array('size' => $input_size));
                $mform->setType($field.'_rows', PARAM_INT);
                $tempGroup[] =& $mform->createElement('text', $field.'_count_in_row', block_exastud_get_string('report_settings_countinrow_fieldtitle'), array('size' => $input_size));
                $mform->setType($field.'_count_in_row', PARAM_INT);
                $tempGroup[] =& $mform->createElement('text', $field.'_maxchars', block_exastud_get_string('report_settings_maxchars_fieldtitle'), array('size' => $input_size));
                $mform->setType($field.'_maxchars', PARAM_INT);
                $mform->addGroup($tempGroup, $field.'_textareaparams', '', ' ', false);

                // params for image
                $tempGroup = array();
                $tempGroup[] =& $mform->createElement('text', $field.'_maxbytes', block_exastud_get_string('report_setting_type_image_maxbytes'), array('size' => 20));
                $mform->setType($field.'_maxbytes', PARAM_INT);
                $tempGroup[] =& $mform->createElement('text', $field.'_width', block_exastud_get_string('report_setting_type_image_width'), array('size' => 5));
                $mform->setType($field.'_width', PARAM_INT);
                $tempGroup[] =& $mform->createElement('text', $field.'_height', block_exastud_get_string('report_setting_type_image_height'), array('size' => 5));
                $mform->setType($field.'_height', PARAM_INT);
                $mform->addGroup($tempGroup, $field.'_imageparams', '', ' ', false);

                // params for user's data
                $tempGroup = array();
                //$tempGroup[] =& $mform->createElement('exastud_htmltag', block_exastud_get_string('report_setting_type_userdata_datakey'));
                $tempGroup[] =& $mform->createElement('select', $field.'_userdatakey', block_exastud_get_string('report_setting_type_userdata_datakey'));
                $mform->setType($field.'_userdatakey', PARAM_RAW);
                $mform->addGroup($tempGroup, $field.'_userdataparams', '', ' ', false);

                // params for matrixes
                $mform->addGroup(array(), $field.'_matrixtype', '', ' ', false);
                $mform->addGroup(array(), $field.'_matrixparams', '', ' ', false);

                //$mform->addElement('exastud_htmltag', '</div>');
            } else {
                // only checkbox
                // TODO: add something?
            }
            $mform->addElement('exastud_htmltag', '</div>');
        }
        // additional dynamic fields
        //  ('additional_params')

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', 0);

        $mform->addElement('hidden', 'token');
        $mform->setType('token', PARAM_INT);
        $mform->setDefault('token', 0);

        $mform->addElement('hidden', 'params_sorting', '', ['id' => 'param_sorting']);
        $mform->setType('params_sorting', PARAM_TEXT);
        $mform->setDefault('params_sorting', '');

    }

    public function prepare_formdata($data) {
        $result = $data;
        if (is_array($data->grades)) {
            $result->grades = implode('; ', $data->grades);
        }
        foreach ($this->getAllSecondaryFields() as $field) {
            $fieldData = unserialize($data->{$field});
            $result->{$field} = $fieldData['checked'];
            $result->{$field.'_title'} = @$fieldData['title'] ? $fieldData['title'] : '';
            $result->{$field.'_key'} = @$fieldData['key'] ? $fieldData['key'] : $field;
            if (!empty($fieldData['type'])) {
                $result->{$field.'_type'} = $fieldData['type'];
            }
            // textarea
            if (!empty($fieldData['rows'])) {
                $result->{$field.'_rows'} = $fieldData['rows'];
            }
            if (!empty($fieldData['count_in_row'])) {
                $result->{$field.'_count_in_row'} = $fieldData['count_in_row'];
            }
            if (!empty($fieldData['maxchars'])) {
                $result->{$field.'_maxchars'} = $fieldData['maxchars'];
            }
            // selectbox
            if (!empty($fieldData['values'])) {
                $result->{$field.'_values'} = $fieldData['values'];
            }
            // image
            if (!empty($fieldData['maxbytes'])) {
                $result->{$field.'_maxbytes'} = $fieldData['maxbytes'];
            }
            if (!empty($fieldData['width'])) {
                $result->{$field.'_width'} = $fieldData['width'];
            }
            if (!empty($fieldData['height'])) {
                $result->{$field.'_height'} = $fieldData['height'];
            }
            // user's data
            if (!empty($fieldData['userdatakey'])) {
                $result->{$field.'_userdatakey'} = $fieldData['userdatakey'];
            }
            // matrix
            if (!empty($fieldData['matrixtype'])) {
                $result->{$field.'_matrixtype'} = $fieldData['matrixtype'];
            }
            if (!empty($fieldData['matrixrows'])) {
                $result->{$field.'_matrixrows'} = $fieldData['matrixrows'];
            }
            if (!empty($fieldData['matrixcols'])) {
                $result->{$field.'_matrixcols'} = $fieldData['matrixcols'];
            }
        }
        $additional_params_tmp = unserialize($data->additional_params);
        if (!block_exastud_is_bw_active() && $data->params_sorting) {
            $sorting = unserialize($data->params_sorting);
            if (count($sorting) > 0) {
                $additional_params_tmp = array_merge(array_flip($sorting), $additional_params_tmp);
            }
        }
        $result->additional_params = $additional_params_tmp;
        return $result;
    }

    public function definition_after_data() {
        global $CFG;
        $elements_with_wrongs = array();
        //parent::definition_after_data();
        //require_once($CFG->dirroot.'/blocks/exastud/classes/exastud_htmltag.php');
        //MoodleQuickForm::registerElementType('exastud_htmltag', $CFG->dirroot.'/blocks/exastud/classes/exastud_htmltag.php', 'block_exastud_htmltag');

        $mform =& $this->_form;

        foreach ($this->getAllSecondaryFields() as $field) {
            if (in_array($field, $this->fieldsWithAdditionalParams)) {
                // parameters for selectbox
                //$selectboxes[$i] = array();
                $j = 0;

                //echo '<pre>';print_r($mform->_defaultValues[$field.'_values']);echo '</pre>';
                if (empty($mform->_defaultValues[$field.'_values'])) {
                    $mform->_defaultValues[$field.'_values'] = array('' => ' ');  // for empty block (template for new first record)
                }
                foreach ($mform->_defaultValues[$field.'_values'] as $sKey => $sValue) {
                    //$selectboxes[$i][] = $j;
                    $selectboxParams = array();
                    $selectboxParams[] = $mform->createElement('text', $field.'_selectboxvalues_key['.$j.']', block_exastud_get_string('report_settings_selectboxkey_fieldtitle'), array('size' => 15));
                    $mform->setType($field.'_selectboxvalues_key['.$j.']', PARAM_RAW);
                    $mform->setDefault($field.'_selectboxvalues_key['.$j.']', $sKey);
                    $selectboxParams[] = $mform->createElement('text', $field.'_selectboxvalues_value['.$j.']', block_exastud_get_string('report_settings_selectboxvalue_fieldtitle'), array('size' => 45));
                    $mform->setType($field.'_selectboxvalues_value['.$j.']', PARAM_RAW);
                    $mform->setDefault($field.'_selectboxvalues_value['.$j.']', $sValue);
                    // moved to JS
                    //$selectboxParams[] = $mform->createElement('exastud_htmltag', '<div class="exastud-template-settings-group group-'.$field.' selectbox-settings">');
                    //$selectboxParams[] = $mform->createElement('exastud_htmltag', '<img class="add_selectbox_option" data-field="'.$field.'" data-optionid="'.$j.'" src="'.$CFG->wwwroot.'/blocks/exastud/pix/add.png" title="'.block_exastud_get_string('add').'"/>');
                    //$selectboxParams[] = $mform->createElement('exastud_htmltag', '<img class="delete_selectbox_option" data-field="'.$field.'" data-optionid="'.$j.'" src="'.$CFG->wwwroot.'/blocks/exastud/pix/del.png" title="'.block_exastud_get_string('delete').'"/>');
                    //$selectboxParams[] = $mform->createElement('exastud_htmltag', '</div');
                    $allOptions[] = $mform->addGroup($selectboxParams, $field.'_selectboxparams['.$j.']', '', ' ', false);
                    $mform->insertElementBefore($mform->removeElement($field.'_selectboxparams['.$j.']', false), $field.'_textareaparams');
                    $j++;
                }
                $mform->addElement('exastud_htmltag', '<script>'.$field.'_last_index_for_selectbox = '.($j - 1).';</script>');
            }
        }

        $selectboxes = array();

        $i = 0;
        $i_from_zero = true;

        $mform->addElement('exastud_htmltag', '<script>
                    var additional_params_last_index_for_selectbox = new Array();
                    var additional_params_last_index_for_matrixrows = new Array();
                    var additional_params_last_index_for_matrixcols = new Array();
        </script>');

        //echo '<pre>';print_r($mform->_defaultValues['additional_params']);exit;
        //array_unshift($mform->_defaultValues['additional_params'], array('-1' => array())); // for empty block (template for new first record)
        if (array_key_exists('additional_params', $mform->_defaultValues) && !$mform->_defaultValues['additional_params']) {
            $i = -1;
            $i_from_zero = false;
            $mform->_defaultValues['additional_params'] = array('-1' => array());  // for empty block (template for new first record)
        }

        $matrixRowsCount = array();
        if (!empty($mform->_defaultValues['additional_params']) && count($mform->_defaultValues['additional_params']) > 0) {
            // add additional_params to the form
            foreach ($mform->_defaultValues['additional_params'] as $param_key => $param_settings) {
                $main_block = array();
                // block delimeter
                $mform->addElement('exastud_htmltag', '<div id="exastud-additional-params-block-'.$i.'" class="exastud-setting-block exastud-additional-params-block '.($i < 0 ? 'hidden' : '').'" >');
                $mform->addElement('exastud_htmltag', '<hr />');
                // always 'checked'
                $mform->addElement('hidden', 'additional_params['.$i.']', '1');
                $mform->setDefault('additional_params['.$i.']', 1);
                // move (sorting) button
                if (!block_exastud_is_bw_active()) {
                    $mform->addElement('exastud_htmltag',
                            '<img class="sorting_param_button" data-paramid="'.$i.'" src="'.$CFG->wwwroot.
                            '/blocks/exastud/pix/move-vertical.png" title="'.block_exastud_get_string('sort_parameter').'"/>');
                }
                // delete button
                $mform->addElement('exastud_htmltag', '<img class="delete_param_button" data-paramid="'.$i.'" src="'.$CFG->wwwroot.'/blocks/exastud/pix/trash.png" title="'.block_exastud_get_string('delete_parameter').'"/>');
                // title
                $main_block[] = $mform->createElement('text', 'additional_params_title['.$i.']', block_exastud_trans('de: Titel'), 'size = \'45\'');
                if (!empty($param_settings['title'])) {
                    $mform->setDefault('additional_params_title['.$i.']', $param_settings['title']);
                }
                $mform->setType('additional_params_title', PARAM_TEXT);
                // key
                $main_block[] = $mform->createElement('text', 'additional_params_key['.$i.']', ''/*block_exastud_trans('de: Key')*/, 'size = \'45\'');
                if (!empty($param_settings['key'])) {
                    $mform->setDefault('additional_params_key['.$i.']', $param_settings['key']);
                }
                $mform->setType('additional_params_key', PARAM_RAW);
                $mform->addGroup($main_block, 'additional_params_mainparams['.$i.']', '', array(' '), false);
                // type
                $radiotype = array();
                foreach ($this->input_types as $type) {
                    $radiotype[] = $mform->createElement('radio', 'additional_params_type['.$i.']', '', block_exastud_get_string('report_setting_type_'.$type), $type, $this->radioattributes);
                    if (!empty($param_settings['type'])) {
                        $mform->setDefault('additional_params_type['.$i.']', $param_settings['type']);
                    }
                }
                $mform->addGroup($radiotype, 'additional_params_typeradiobuttons['.$i.']', '', array(' '), false);

                // paramaters for textarea
                $textareaParams = array();
                $textareaParams[] = $mform->createElement('text', 'additional_params_rows['.$i.']', block_exastud_get_string('report_settings_countrows_fieldtitle'), array('size' => 5));
                $mform->setType('additional_params_rows['.$i.']', PARAM_INT);
                if (!empty($param_settings['rows'])) {
                    $mform->setDefault('additional_params_rows['.$i.']', $param_settings['rows']);
                }
                $textareaParams[] = $mform->createElement('text', 'additional_params_count_in_row['.$i.']', block_exastud_get_string('report_settings_countinrow_fieldtitle'), array('size' => 5));
                $mform->setType('additional_params_count_in_row['.$i.']', PARAM_INT);
                if (!empty($param_settings['count_in_row'])) {
                    $mform->setDefault('additional_params_count_in_row['.$i.']', $param_settings['count_in_row']);
                }
                $textareaParams[] = $mform->createElement('text', 'additional_params_maxchars['.$i.']', block_exastud_get_string('report_settings_maxchars_fieldtitle'), array('size' => 5));
                $mform->setType('additional_params_maxchars['.$i.']', PARAM_INT);
                if (!empty($param_settings['maxchars'])) {
                    $mform->setDefault('additional_params_maxchars['.$i.']', $param_settings['maxchars']);
                }
                $mform->addGroup($textareaParams, 'additional_params_textareaparams['.$i.']', '', ' ', false);

                // parameters for selectbox
                $selectboxes[$i] = array();
                $j = 0;
                if (empty($param_settings['values'])) {
                    $param_settings['values'] = array('' => ' ');  // for empty block (template for new first record)
                }
                foreach ($param_settings['values'] as $sKey => $sValue) {
                    $selectboxes[$i][] = $j;
                    $selectboxParams = array();
                    $selectboxParams[] = $mform->createElement('text', 'additional_params_selectboxvalues_key['.$i.']['.$j.']',
                            block_exastud_get_string('report_settings_selectboxkey_fieldtitle'), array('size' => 15));
                    $mform->setType('additional_params_selectboxvalues_key['.$i.']['.$j.']', PARAM_RAW);
                    $mform->setDefault('additional_params_selectboxvalues_key['.$i.']['.$j.']', $sKey);
                    $selectboxParams[] = $mform->createElement('text', 'additional_params_selectboxvalues_value['.$i.']['.$j.']',
                            block_exastud_get_string('report_settings_selectboxvalue_fieldtitle'), array('size' => 45));
                    $mform->setType('additional_params_selectboxvalues_value['.$i.']['.$j.']', PARAM_RAW);
                    $mform->setDefault('additional_params_selectboxvalues_value['.$i.']['.$j.']', $sValue);
                    // moved to JS
                    //$selectboxParams[] = $mform->createElement('exastud_htmltag', '<div class="exastud-template-settings-group group-'.$sKey.' selectbox-settings">');
                    //$selectboxParams[] = $mform->createElement('exastud_htmltag', '<img class="add_selectbox_option" data-paramid="'.$i.'" data-optionid="'.$j.'" src="'.$CFG->wwwroot.'/blocks/exastud/pix/add.png" title="'.block_exastud_get_string('add').'"/>');
                    //$selectboxParams[] = $mform->createElement('exastud_htmltag', '<img class="delete_selectbox_option" data-paramid="'.$i.'" data-optionid="'.$j.'" src="'.$CFG->wwwroot.'/blocks/exastud/pix/del.png" title="'.block_exastud_get_string('delete').'"/>');
                    //$selectboxParams[] = $mform->createElement('exastud_htmltag', '</div>');
                    $mform->addGroup($selectboxParams, 'additional_params_selectboxparams['.$i.']['.$j.']', '', ' ', false);
                    $j++;
                }
                $mform->addElement('exastud_htmltag', '<script>additional_params_last_index_for_selectbox['.$i.'] = '.($j - 1).';</script>');
                //$mform->addGroup($additional_block, 'group_additionalparams', '', ' ', false);

                // paramaters for image
                $imageParams = array();
                $imageParams[] = $mform->createElement('text', 'additional_params_maxbytes['.$i.']', block_exastud_get_string('report_setting_type_image_maxbytes'), array('size' => 20));
                $mform->setType('additional_params_maxbytes['.$i.']', PARAM_INT);
                if (!empty($param_settings['maxbytes'])) {
                    $mform->setDefault('additional_params_maxbytes['.$i.']', $param_settings['maxbytes']);
                }
                $imageParams[] = $mform->createElement('text', 'additional_params_width['.$i.']', block_exastud_get_string('report_setting_type_image_width'), array('size' => 5));
                $mform->setType('additional_params_width['.$i.']', PARAM_INT);
                if (!empty($param_settings['width'])) {
                    $mform->setDefault('additional_params_width['.$i.']', $param_settings['width']);
                }
                $imageParams[] = $mform->createElement('text', 'additional_params_height['.$i.']', block_exastud_get_string('report_setting_type_image_height'), array('size' => 5));
                $mform->setType('additional_params_height['.$i.']', PARAM_INT);
                if (!empty($param_settings['height'])) {
                    $mform->setDefault('additional_params_height['.$i.']', $param_settings['height']);
                }
                $mform->addGroup($imageParams, 'additional_params_imageparams['.$i.']', '', ' ', false);

                // parameters for user's data
                require_once($CFG->dirroot . '/blocks/exastud/lib/reports_lib.php');
                $userDataParams = array();
                $selectboxparameters = block_exastud_get_report_user_fields();
                if (!empty($param_settings['userdatakey']) && !array_key_exists($param_settings['userdatakey'], $selectboxparameters)) {
                    $selectboxparameters[$param_settings['userdatakey']] = '-- ${'.$param_settings['userdatakey'].'} --';
                    //$elements_with_wrongs[] = 'additional_params_userdatakey['.$i.']';
                    $elements_with_wrongs[$param_settings['userdatakey']] = 'additional_params_userdataparams['.$i.']';
                }
                //$userDataParams[] = $mform->createElement('exastud_htmltag', block_exastud_get_string('report_setting_type_userdata_datakey'));
                $userDataParams[] = $mform->createElement('select', 'additional_params_userdatakey['.$i.']', block_exastud_get_string('report_setting_type_userdata_datakey'), $selectboxparameters);
                $mform->setType('additional_params_userdatakey['.$i.']', PARAM_RAW);
                if (!empty($param_settings['userdatakey'])) {
                    $mform->setDefault('additional_params_userdatakey['.$i.']', $param_settings['userdatakey']);
                }
                $mform->addGroup($userDataParams, 'additional_params_userdataparams['.$i.']', '', ' ', false);

                // parameters for matrix
                $matrixes[$i] = array();
                $matrixtypes = array('checkbox', 'radio', 'text');
                $matrixTypesElements = array();
                $matrixTypesElements[] = $mform->createElement('exastud_htmltag', block_exastud_get_string('report_setting_type_matrix_type').': ');
                foreach ($matrixtypes as $type) {
                    $matrixTypesElements[] = $mform->createElement('radio', 'additional_params_matrixtype['.$i.']', '', block_exastud_get_string('report_setting_type_matrix_type_'.$type), $type);
                    if (!empty($param_settings['matrixtype'])) {
                        $mform->setDefault('additional_params_matrixtype['.$i.']', $param_settings['matrixtype']);
                    }
                }
                $mform->addGroup($matrixTypesElements, 'additional_params_matrixtype['.$i.']', '', array(' '), false);
                $j = 0;
                if (empty($param_settings['matrixrows'])) {
                    $param_settings['matrixrows'] = array('' => '');  // for empty block (template for new first record)
                }
                $matrixRows = array();
                foreach ($param_settings['matrixrows'] as $sKey => $sValue) {
                    //$matrixes[$i][] = $j;
                    $matrixRows[] = $mform->createElement('text', 'additional_params_matrixrows['.$i.']['.$j.']',
                            '', array('size' => 15));
                    $mform->setType('additional_params_matrixrows['.$i.']['.$j.']', PARAM_RAW);
                    $mform->setDefault('additional_params_matrixrows['.$i.']['.$j.']', $sValue);
                    //$mform->addGroup($matrixRows, 'additional_params_matrixparams['.$i.']', '', ' ', false);
                    $j++;
                }
                $mform->addElement('exastud_htmltag', '<script>additional_params_last_index_for_matrixrows['.$i.'] = '.($j - 1).';</script>');
                $matrixRowsCount['additional_params'][$i] = $j-1; // need later for dividing rows and cols
                $j = 0;
                if (empty($param_settings['matrixcols'])) {
                    $param_settings['matrixcols'] = array('' => '');  // for empty block (template for new first record)
                }
                $matrixCols = array();
                foreach ($param_settings['matrixcols'] as $sKey => $sValue) {
                    //$matrixes[$i][] = $j;
                    $matrixCols[] = $mform->createElement('text', 'additional_params_matrixcols['.$i.']['.$j.']',
                            '', array('size' => 15));
                    $mform->setType('additional_params_matrixcols['.$i.']['.$j.']', PARAM_RAW);
                    $mform->setDefault('additional_params_matrixcols['.$i.']['.$j.']', $sValue);
                    $j++;
                }
                $mform->addElement('exastud_htmltag', '<script>additional_params_last_index_for_matrixcols['.$i.'] = '.($j - 1).';</script>');
                $resultGroupElements = array_merge($matrixRows, $matrixCols);
                $matrixGroups[] = $mform->addGroup($resultGroupElements, 'additional_params_matrixparams['.$i.']', '', ' ', false);
                // next template changing is in JS

                // end div
                $mform->addElement('exastud_htmltag', '</div>');
                $i++;
            }
            // options of elements
            //$additional_block_options = array();
            //$this->repeat_elements($additional_block, $count_additional, $additional_block_options, 'additional_params_repeat', 'additional_params', 0, null, null);
        }

        $mform->addElement('exastud_htmltag', '<script>var additional_params_last_index = '.($i - 1).';</script>');
        $mform->addElement('button', 'add_new_param', block_exastud_get_string('report_settings_button_add_additional_param'));

        $this->add_action_buttons();

        // additional changing in html of elements (needs for JS)
        $field_working = function ($field, $i = null) use ($mform, $selectboxes, $matrixRowsCount) {
            $arr = '';
            if ($i !== null) {
                $arr = '['.$i.']';
            }
            
            // main params (title, key)
            $main_settings = $mform->getElement($field.'_mainparams'.$arr);
            $main_settings->_attributes['class'] = 'exastud-template-settings-group group-'.$field.' main-params';
            $main_settings_elements = $main_settings->getElements();
            foreach ($main_settings_elements as $element) {
                $element->_attributes['class'] = 'exastud-template-settings-param';
            }
            // type radiobuttons settings
            $radio_settings = $mform->getElement($field.'_typeradiobuttons'.$arr);
            $radio_settings->_attributes['class'] = 'exastud-template-settings-group group-'.$field.' type-settings';
            $radio_settings_elements = $radio_settings->getElements();
            foreach ($radio_settings_elements as $element) {
                $element->_attributes['class'] = 'exastud-template-settings-param-type';
                $element->_attributes['data-field'] = $field.$arr;
            }
            // textarea params
            $textarea_settings = $mform->getElement($field.'_textareaparams'.$arr);
            $addclass2 = '';
            if ($i !== null) {
                $addclass2 .= ' textarea-settings-'.$i;
            }
            $textarea_settings->_attributes['class'] = 'exastud-template-settings-group group-'.$field.' textarea-settings '.$addclass2;
            $textarea_settings_elements = $textarea_settings->getElements();
            foreach ($textarea_settings_elements as $element) {
                $element->_attributes['class'] = 'exastud-template-settings-param';
            }
            // selectbox params
            if ($i !== null) {
                if (array_key_exists($i, $selectboxes) && count($selectboxes[$i]) > 0) {
                    foreach ($selectboxes[$i] as $j) {
                        $selectbox_settings = $mform->getElement($field.'_selectboxparams['.$i.']['.$j.']');
                        $addclass3 = '';
                        if ($i !== null) {
                            $addclass3 .= ' selectbox-settings-'.$i.'-'.$j;
                        }
                        $selectbox_settings->_attributes['class'] =
                                'exastud-template-settings-group group-'.$field.' selectbox-settings '.$addclass3;
                        $selectbox_settings_elements = $selectbox_settings->getElements();
                        foreach ($selectbox_settings_elements as $element) {
                            if ($element->_type == 'text') {
                                $element->_attributes['class'] = 'exastud-template-settings-param';
                            }
                        }
                    }
                }
            } else {
                for ($j = 0; $j <= 100; $j++) {
                    if ($mform->elementExists($field.'_selectboxparams['.$j.']')) {
                        $selectbox_settings = $mform->getElement($field.'_selectboxparams['.$j.']');
                        $addclass3 = '';
                        if ($i !== null) {
                            $addclass3 .= ' selectbox-settings-'.$field;
                        }
                        $selectbox_settings->_attributes['class'] =
                                'exastud-template-settings-group group-'.$field.' selectbox-settings '.$addclass3;
                        $selectbox_settings_elements = $selectbox_settings->getElements();
                        foreach ($selectbox_settings_elements as $element) {
                            if ($element->_type == 'text') {
                                $element->_attributes['class'] = 'exastud-template-settings-param';
                            }
                        }
                    } else {
                        break;
                    }
                }
            }
            // image params
            $image_settings = $mform->getElement($field.'_imageparams'.$arr);
            $addclass4 = '';
            if ($i !== null) {
                $addclass4 .= ' image-settings-'.$i;
            }
            $image_settings->_attributes['class'] = 'exastud-template-settings-group group-'.$field.' image-settings '.$addclass4;
            $image_settings_elements = $image_settings->getElements();
            foreach ($image_settings_elements as $element) {
                $element->_attributes['class'] = 'exastud-template-settings-param';
            }
            // users data params
            $userdata_settings = $mform->getElement($field.'_userdataparams'.$arr);
            $addclass5 = '';
            if ($i !== null) {
                $addclass5 .= ' userdata-settings-'.$i;
            }
            $userdata_settings->_attributes['class'] = 'exastud-template-settings-group group-'.$field.' userdata-settings '.$addclass5;
            $userdata_settings_elements = $userdata_settings->getElements();
            foreach ($userdata_settings_elements as $element) {
                $element->_attributes['class'] = 'exastud-template-settings-param';
            }
            // matrix params
            $addclass6 = '';
            if ($i !== null) {
                $addclass6 .= ' matrix-settings-'.$i;
            }
            $matrix_settingstype = $mform->getElement($field.'_matrixtype'.$arr);
            $matrix_settingstype->_attributes['class'] = 'exastud-template-settings-group group-'.$field.' matrix-settings '.$addclass6.' matrix-type';
            $matrix_settings = $mform->getElement($field.'_matrixparams'.$arr);
            $matrix_settings->_attributes['class'] = 'exastud-template-settings-group group-'.$field.' matrix-settings '.$addclass6;
            $matrix_settings_elements = $matrix_settings->getElements();
            foreach ($matrix_settings_elements as $k => $element) {
                $element->_attributes['class'] = 'exastud-template-settings-param';
                // add additional data to html tags
                if (array_key_exists($field, $matrixRowsCount)) {
                    if ($k <= @$matrixRowsCount[$field][$i]) { // it is row (1 element is a type of matrix!!!!)
                        $element->_attributes['class'] .= ' matrix-row ';
                    } else { // it is column
                        $element->_attributes['class'] .= ' matrix-col ';
                    }
                }
            }
        };

        foreach ($this->getAllSecondaryFields() as $field) {
            $formelement = $group = $mform->getElement($field);
            $formelement->_attributes['class'] = 'exastud-template-settings-param param-'.$field;
            if (in_array($field, $this->fieldsWithAdditionalParams)) {
                $field_working($field, null);
            }
            // if here is additional params
            if (array_key_exists('additional_params', $mform->_defaultValues) && $mform->_defaultValues['additional_params']) {
                for ($i = ($i_from_zero ? 0 : -1); $i < count($mform->_defaultValues['additional_params']) - ($i_from_zero ? 0 : 1); $i++) {
                    $field_working('additional_params', $i);
                }
            }

        }

        // errors in the specific parameters
        $urlToUserFieldsEdit = (new moodle_url($CFG->httpswwwroot . '/user/profile/index.php', []))->out(false);
        foreach ($elements_with_wrongs as $fieldname => $elementname) {
            $param = $mform->getElement($elementname);
            $userdata_settings_elements = $param->getElements();
            foreach ($userdata_settings_elements as $element) {
                $shortfieldname = str_replace('profile_field_', '', $fieldname);
                $a = (object)['fieldname' => $shortfieldname];
                $element->_attributes['data-exastud-report-marker-wrong'] = block_exastud_get_string('report_settings_userdata_wrong_user_parameter', 'block_exastud', $a);
                $element->_attributes['data-exastud-report-marker-addurl'] = $urlToUserFieldsEdit;
                $element->_attributes['data-exastud-report-marker-addurl_type'] = 'edit';
                $element->_attributes['data-exastud-report-marker-addurltitle'] = block_exastud_get_string('report_settings_userdata_wrong_user_parameter_editurl_title');
            }
            //}
        }

        // error in main fields
        foreach ($this->errorsInForm as $field => $errorText) {
            $element = $mform->getElement($field);
            $element->_attributes['data-exastud-report-field-wrong'] = $errorText;
        }

    }

/*    function validation($data, $files, $customData) {
        $this->prepare_formdata($customData);
        return parent::validation($data, $files);
    }*/


    function display($with_custom_definition = false) {
        global $CFG;
        if ($with_custom_definition) {
            $this->_definition_finalized = false; // needed for form after validation
        }
        parent::display();
    }

}

class report_settings_filter_form extends moodleform {

    function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'filter', block_exastud_get_string('filter_fieldset'));

        $mform->addElement('text', 'search', block_exastud_get_string('filter_search').':', array('size' => 50));
        $mform->setType('search', PARAM_TEXT);
        // BPs
        if (block_exastud_is_bw_active()) {
            $bps = g::$DB->get_records_menu('block_exastudbp', null, 'sorting', 'id, title');
            // add empty
            $bps = ['' => '', '0' => block_exastud_get_string('filter_empty')] + $bps;
            $mform->addElement('select',
                    'bpid',
                    block_exastud_get_string('filter_bp').':',
                    $bps);
        }
        // Categories
        if (block_exastud_is_bw_active()) {
            $categories = g::$DB->get_records_sql_menu(' SELECT DISTINCT category, category as value FROM {block_exastudreportsettings} WHERE category != \'\'');
            // add empty
            $categories = ['--notselected--' => '', '' => block_exastud_get_string('filter_empty')] + $categories;
            $mform->addElement('select',
                    'category',
                    block_exastud_get_string('filter_category').':',
                    $categories);
        }

        $mform->addElement('hidden', 'token');
        $mform->setType('token', PARAM_INT);
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);

        // checkbox: show full table
        if (!$this->_customdata['for_reseting']) {
            $mform->addElement('checkbox', 'showfulltable', block_exastud_get_string('filter_show_fulltable'));
        }

        $buttons = array();
        $buttons[] = $mform->createElement('submit', 'gofilter', block_exastud_get_string('filter_button'));
        $buttons[] = $mform->createElement('submit', 'clearfilter', block_exastud_get_string('clear_filter'));
        $mform->addGroup($buttons, 'buttons', '', array(' '), false);

        $mform->setExpanded('filter');
        $mform->closeHeaderBefore('filter');
    }

}

class change_subject_teacher_form extends moodleform {

    function definition() {
        global $DB;
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $currentteacher = $this->_customdata['currentteacher'];
        $subject = $this->_customdata['subject'];
        $classid = $this->_customdata['classid'];
        $a = (object)[
            'subjecttitle' => $subject->title,
            'currentteacher_name' => fullname($currentteacher),
        ];

        //$mform->addElement('static', 'exastud_description', block_exastud_get_string('form_subject_teacher_form_description', '', $a).':', array('size' => 50));
        $teachers = block_exastud_get_all_teachers($courseid);
        // teachersline in the teacher adding form
        $select  = " username <> 'guest' AND deleted = 0 AND confirmed = 1 ";
        $selectsql = "";

        $teachers = $DB->get_records_sql('SELECT id, firstname, lastname, email, '.get_all_user_name_fields(true).'
									FROM {user}
									WHERE '.$select.'
									    AND deleted = 0							
                                    ORDER BY lastname ASC, firstname ASC');

        $teachers = array_map(function($o) {return fullname($o).', '.$o->email;}, $teachers);
//        natcasesort($teachers);
        $mform->addElement('select',
                'newsubjectteacher',
                block_exastud_get_string('form_subject_teacher_form_select_new_teacher', '', $a).':*',
                $teachers);
        $mform->setType('newsubjectteacher', PARAM_INT);
        $mform->setDefault('newsubjectteacher', $currentteacher->id);

        // if current teacher is also "Additional class teacher"
        $headTeachers = block_exastud_get_class_diff_teachers($classid, 'head_teacher');
        if (array_key_exists($currentteacher->id, $headTeachers)) {
            $mform->addElement('checkbox', 'no_head_class_teacher', block_exastud_get_string('form_subject_teacher_form_no_head_class_teacher'), ' ');
        }

        $buttons = array();
        $buttons[] = $mform->createElement('submit', 'gochange', block_exastud_get_string('form_subject_teacher_form_save'));
        $buttons[] = $mform->createElement('cancel');
        $mform->addGroup($buttons, 'buttons', '', array(' '), false);
    }

}

class add_students_via_class_parameter_form extends moodleform {

    function definition() {
        global $CFG;
        $mform = $this->_form;
        require_once($CFG->dirroot.'/blocks/exastud/classes/exastud_htmltag.php');
        MoodleQuickForm::registerElementType('exastud_htmltag', $CFG->dirroot.'/blocks/exastud/classes/exastud_htmltag.php', 'block_exastud_htmltag');

        //$mform->addElement('header', 'class_toadd');
        $group=array();
        $group[] =& $mform->createElement('text', 'class_toadd', block_exastud_trans('de:Klasse/Lerngruppe').':');
        $group[] =& $mform->createElement('submit', 'add', block_exastud_trans('de:Schüler hinzufügen'));
        $mform->setType('class_toadd', PARAM_TEXT);
        $mform->addGroup($group, 'group', block_exastud_trans('de:Klasse/Lerngruppe').':', ' ', false);
        $mform->addElement('exastud_htmltag', block_exastud_trans('de: Schüler, die in ihrem Nutzerprofil im Bereich "weitere Profileinstellungen" im Feld Klasse/Lerngruppe den entsprechenden Eintrag haben zur Klasse hinzufügen.'));
    }


}


