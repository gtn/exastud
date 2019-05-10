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

defined('MOODLE_INTERNAL') || die;

require_once __DIR__.'/inc.php';

if (!class_exists('block_exastud_admin_setting_bwactivecheckbox')) {
    class block_exastud_admin_setting_bwactivecheckbox extends admin_setting_configcheckbox {

        public function write_setting($data) {
            global $DB;
            // if this param does NOT exist - it is first installation
            //$existing = get_config('exastud', 'bw_active');
            // use SQL-request instead moodle api function! (possible cache, history....)
            $existing = $DB->record_exists_sql('SELECT * FROM {config_plugins} WHERE plugin = \'exastud\' AND name=\'bw_active\'');
            parent::write_setting($data);
            if ($existing === false) {
                block_exastud_insert_default_entries();
                block_exastud_fill_reportsettingstable();
            }
            return '';
        }
    }
}

if (!class_exists('block_exastud_settings_extraconfigstoredfile')) {
    class block_exastud_settings_extraconfigstoredfile extends admin_setting_configstoredfile {

        static public $logowidth = 150;
        static public $logoheight = 300;

        public function write_setting($data) {
            global $CFG;
            //require_once($CFG->libdir.'/gdlib.php');
            $parentresult = parent::write_setting($data);
            // change image size
            //$size = array_shift($args); // The path hides the size.
            $itemid = clean_param($this->itemid, PARAM_INT);
            //$filename = clean_param(array_shift($args), PARAM_FILE);
            // Extract the requested width and height.
            $maxwidth = self::$logowidth;
            $maxheight = self::$logoheight;
            // Find the original file.
            $fs = get_file_storage();
            if ($files = $fs->get_area_files(1, 'exastud', 'block_exastud_schoollogo', $itemid, '', false)) {
                foreach ($files as $logofile) {
                    if ($logofile->is_valid_image()) {
                        /** @var stored_file $logo */
                        $logo = (array)$logofile;
                        $filedata = $logofile->resize_image($maxwidth, $maxheight);
                        $logo = array_merge($logo, array(
                                'id' => $logofile->get_id(),
                                'contextid' => $logofile->get_contextid(),
                                'component' => 'exastud',
                                'filearea' => 'block_exastud_schoollogo',
                                'itemid' => $itemid,
                                'filepath' => $logofile->get_filepath(),
                                'filename' => $logofile->get_filename().'--temp',
                        ));
                        $newlogo = $fs->create_file_from_string($logo, $filedata);
                        $logofile->replace_file_with($newlogo);
                        $newlogo->delete();
                    }
                }
            }
            return $parentresult;
        }

        public function output_html($data, $query = '') {
            $output = parent::output_html($data, $query);
            $attr = new stdClass();
            $attr->width = self::$logowidth;
            $attr->height = self::$logoheight;
            // Add needed element attributes for work with preconfiguration.
            $doc = new DOMDocument();
            $message = new DOMElement('span', block_exastud_get_string('school_logo_description', null, $attr));
            $doc->loadHTML(utf8_decode($output), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $selector = new DOMXPath($doc);
            foreach ($selector->query("//*[contains(@class, 'fp-restrictions')]") as $e) {
                $mres = $e->insertBefore($message, $e->firstChild);
                $mres->setAttribute('class', 'block-exastud-schoollogo-size');
                $mres->setAttribute('style', 'color:red; clear:both; display: block;');
                //$e->appendChild($message);
            }
            $output = $doc->saveHTML($doc->documentElement);
            return $output;
        }
    }
}

if (!class_exists('block_exastud_settings_menu')) {
    class block_exastud_settings_menu extends admin_setting {

        public function __construct($name, $heading, $information) {
            $this->nosave = true;
            parent::__construct($name, $heading, $information, '');
        }

        public function get_setting() {
            return true;
        }

        public function write_setting($data) {
            return '';
        }
        /**
         * Returns an HTML string
         * @return string Returns an HTML string
         */
        public function output_html($data, $query='') {
            global $OUTPUT;
            $tabtree = block_exastud_menu_for_settings();
            $tabobj = $tabtree->find('blockconfig');
            $tabobj->active = true;
            $tabobj->selected = true;
            $menu = $OUTPUT->render($tabtree);
            return $menu;
        }
    }
}

if (!class_exists('block_exastud_link_to')) {
    class block_exastud_link_to extends admin_setting {

        private $link = '';
        private $linkparams = array();
        private $title = array();
        private $tagattributes = array();
        private $keptLabel = false;

        public function __construct($name, $visiblename, $description, $defaultsetting, $link = '', $title = '', $linkparams = array(), $tagattributes = array(), $keptLabel = false) {
            $this->nosave = true;
            $this->link = $link;
            $this->linkparams = $linkparams;
            $this->tagattributes = $tagattributes;
            $this->title = $title;
            $this->keptLabel = $keptLabel;
            parent::__construct($name, $visiblename, $description, $defaultsetting);
        }

        public function get_setting() {
            return true;
        }

        public function write_setting($data) {
            return '';
        }

        public function output_html($data, $query = '') {
            if ($this->link) {
                $link = html_writer::link(new moodle_url($this->link, $this->linkparams),
                        $this->title, $this->tagattributes);
            } else {
                return '';
            }
            //$output = parent::output_html($data, $query);
            $template = format_admin_setting($this, $this->visiblename, $link,
                    $this->description, true, '', '', $query);
            // Hide some html for better view of this settings.
            $doc = new DOMDocument();
            $doc->loadHTML(utf8_decode($template), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $selector = new DOMXPath($doc);
            // Clean div with classes.
            $elementsToDelete = array();
            // label div
            $labeldivs = array('form-label');
            if (!$this->keptLabel) {
                foreach ($labeldivs as $deletediv) {
                    foreach ($selector->query('//div[contains(attribute::class, "'.$deletediv.'")]') as $e) {
                        $e->textContent = '';
                    }
                }
            } else {
                // show label, but delete short variable name
                $elementsToDelete[] = '//span[contains(attribute::class, "form-shortname")]';
            }
            // another divs
            $infodivs = array('form-defaultinfo');
            foreach ($infodivs as $deletediv) {
                foreach ($selector->query('//div[contains(attribute::class, "'.$deletediv.'")]') as $e) {
                    $e->textContent = '';
                }
            }
            // delete additional elements if it is added in previous code
            if (count($elementsToDelete) > 0) {
                foreach ($elementsToDelete as $toDel) {
                    foreach ($selector->query($toDel) as $e) {
                        $e->textContent = '';
                    }
                }
            }
            $template = $doc->saveHTML($doc->documentElement);
            return $template;
        }

    }
}


if ($ADMIN->fulltree) {
    $settings->add(new block_exastud_settings_menu('exastud/menu', '', ''));

	$settings->add(new admin_setting_configtext('exastud/school_name', block_exastud_get_string('settings_shoolname'), '', '', PARAM_TEXT));
	$settings->add(new admin_setting_configtext('exastud/school_location', block_exastud_get_string('settings_city'), '', '', PARAM_TEXT));
	$settings->add(new admin_setting_configtext('exastud/bildungsstandards', block_exastud_get_string('settings_edustandarts'),
		block_exastud_get_string('settings_edustandarts_description'), '5,6,7,8,9,10', PARAM_TEXT));
	//$settings->add(new admin_setting_configcheckbox('exastud/bw_active', block_exastud_get_string('settings_bw_reports'), '', 0));
	$settings->add(new block_exastud_admin_setting_bwactivecheckbox('exastud/bw_active', block_exastud_get_string('settings_bw_reports'), '', 0));
	$settings->add(new admin_setting_configcheckbox('exastud/use_exacomp_grade_verbose', block_exastud_get_string('settings_exacomp_verbeval'), '', 0));
	$settings->add(new admin_setting_configcheckbox('exastud/use_exacomp_assessment_categories', block_exastud_get_string('settings_exacomp_assessment_categories'), '', 0));
    $settings->add(new admin_setting_configcheckbox('exastud/logging', block_exastud_get_string('logging'), '', 0));
    $evalTypes = [
        BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT => block_exastud_get_string('settings_competence_evaltype_text'),
        BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE => block_exastud_get_string('settings_competence_evaltype_grade'),
        BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT => block_exastud_get_string('settings_competence_evaltype_point'),
    ];
    $settings->add(new admin_setting_configselect('exastud/competence_evaltype', block_exastud_get_string('settings_competence_evaltype'), '', 0, $evalTypes));
    $settings->add(new block_exastud_link_to('link_to_settings_evals',
            block_exastud_get_string("settings_eval_setup"),
            '',
            '',
            '/blocks/exastud/configuration_global.php',
            block_exastud_get_string('settings_eval_setup_link'),
            ['action' => 'evalopts'],
            ['target' => '_blank'],
            true));
    $settings->add(new admin_setting_configtext('exastud/competence_evalpoints_limit', block_exastud_get_string('settings_competence_evalpoints_limit'), block_exastud_get_string('settings_competence_evalpoints_limit_description'), 10, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('exastud/only_learnsociale_reports', block_exastud_get_string('settings_only_learnsoziale'), '', 0));
    /*// periods
    $settings->add(new block_exastud_link_to('link_to_settings_periods', block_exastud_get_string("periods"), '', '', '/blocks/exastud/periods.php', block_exastud_get_string('periods'), [], ['class' => 'btn btn-default']));
    // competencies
    $settings->add(new block_exastud_link_to('link_to_settings_competencies', block_exastud_get_string("competencies"), '', '', '//blocks/exastud/configuration_global.php', block_exastud_get_string('competencies'), ['action' => 'categories'], ['class' => 'btn btn-default']));
    // Grading
    $settings->add(new block_exastud_link_to('link_to_settings_grading', block_exastud_get_string("grading"), '', '', '//blocks/exastud/configuration_global.php', block_exastud_get_string('grading'), ['action' => 'evalopts'], ['class' => 'btn btn-default']));
    // Education plans
    $settings->add(new block_exastud_link_to('link_to_settings_bps', block_exastud_get_string("education_plans"), '', '', '//blocks/exastud/configuration_global.php', block_exastud_get_string('education_plans'), ['action' => 'bps'], ['class' => 'btn btn-default']));
    // Logo upload
    $settings->add(new block_exastud_link_to('link_to_settings_pictureupload', block_exastud_get_string("pictureupload"), '', '', '/blocks/exastud/pictureupload.php', block_exastud_get_string('pictureupload'), [], ['class' => 'btn btn-default']));
    // Backup
    $settings->add(new block_exastud_link_to('link_to_settings_backup', block_exastud_get_string("backup"), '', '', '/blocks/exastud/backup.php', block_exastud_get_string('backup'), [], ['class' => 'btn btn-default']));
    // Head teachers
    $settings->add(new block_exastud_link_to('link_to_settings_headteachers', block_exastud_get_string("head_teachers"), '', '', '/cohort/assign.php', block_exastud_get_string('head_teachers'), ['id' => block_exastud_get_head_teacher_cohort()->id], ['class' => 'btn btn-default']));
    */
    // template configurations
    //$settings->add(new block_exastud_link_to('link_to_settings_report_templates', block_exastud_get_string('report_settings_edit'), '', '', '/blocks/exastud/report_settings.php', block_exastud_get_string('report_settings_edit'), [], ['class' => 'btn btn-default', 'target' => '_blank']));

	if (block_exastud_is_a2fa_installed()) {
		$description = '';
	} else {
		$description = '<span style="color: red">'.block_exastud_get_string('settings_a2fa_requirement_description').'</span>';
	}
	$a2fa_requirement = [
		'' => block_exastud_get_string('settings_a2fa_requirement_def'),
		'user_a2fa' => block_exastud_get_string('settings_a2fa_requirement_user_a2fa'),
		'a2fa_timeout' => block_exastud_get_string('settings_a2fa_requirement_a2fa_timeout'),
	];
	$settings->add(new admin_setting_configselect('exastud/a2fa_requirement', block_exastud_get_string('settings_a2fa_requirement'), $description, '', $a2fa_requirement));

	//$settings->add(new admin_setting_configstoredfile('exastud/school_logo',
	$settings->add(new block_exastud_settings_extraconfigstoredfile('exastud/school_logo',
                            block_exastud_get_string('school_logo'),
                            '',
                            'block_exastud_schoollogo',
                            0,
                            array(
                                    'subdirs' => 0,
                                    'maxfiles' => 1,
                                    'accepted_types' => array('web_image'))));

	$settings->add(new admin_setting_configcheckbox('exastud/export_class_password', block_exastud_get_string('backup_class_protection'), '', 0));
}
