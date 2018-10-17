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

if (!class_exists('block_exastud_link_to')) {
    class block_exastud_link_to extends admin_setting {

        private $linkparams = array();
        private $title = array();
        private $tagattributes = array();

        public function __construct($name, $visiblename, $description, $defaultsetting, $title = '', $linkparams = array(),
                $tagattributes = array()) {
            $this->linkparams = $linkparams;
            $this->tagattributes = $tagattributes;
            $this->title = $title;
            parent::__construct($name, $visiblename, $description, $defaultsetting);
        }

        public function get_setting() {
            return null;
        }

        public function write_setting($data) {
            return null;
        }

        public function output_html($data, $query = '') {
            $link = html_writer::link(new moodle_url('/blocks/exastud/report_settings.php', $this->linkparams),
                    $this->title, $this->tagattributes);
            //$output = parent::output_html($data, $query);
            $template = format_admin_setting($this, $this->visiblename, $link,
                    $this->description, true, '', '', $query);
            // Hide some html for better view of this settings.
            $doc = new DOMDocument();
            $doc->loadHTML(utf8_decode($template), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $selector = new DOMXPath($doc);
            // Clean div with classes.
            $deletedivs = array('form-label', 'form-defaultinfo');
            foreach ($deletedivs as $deletediv) {
                foreach ($selector->query('//div[contains(attribute::class, "'.$deletediv.'")]') as $e) {
                    $e->textContent = '';
                }
            }
            $template = $doc->saveHTML($doc->documentElement);
            return $template;
        }

    }
}


if ($ADMIN->fulltree) {
	$settings->add(new admin_setting_configtext('exastud/school_name', block_exastud_trans('de:Lernentwicklungsbericht: Schulname'), '', '', PARAM_TEXT));
	$settings->add(new admin_setting_configtext('exastud/school_location', block_exastud_trans('de:Lernentwicklungsbericht: Ort'), '', '', PARAM_TEXT));
	$settings->add(new admin_setting_configtext('exastud/bildungsstandards', block_exastud_trans('de:Bildungsstandards'),
		block_exastud_trans('de:Liste, mit Kommata getrennt'), '5,6,7,8,9,10', PARAM_TEXT));
	$settings->add(new admin_setting_configcheckbox('exastud/bw_active', block_exastud_trans('de:Baden-Württemberg Berichte'), '', 0));
	$settings->add(new admin_setting_configcheckbox('exastud/use_exacomp_grade_verbose', block_exastud_trans('de:Exabis Kompetenzraster Notenverbalisierung verwenden.'), '', 0));
    $settings->add(new admin_setting_configcheckbox('exastud/logging', block_exastud_get_string('logging'), '', 0));
    $settings->add(new block_exastud_link_to('link_to_report_templates_settings', block_exastud_get_string('report_settings_edit'), '', '', block_exastud_get_string('report_settings_edit'), [], ['class' => 'btn btn-default', 'target' => '_blank']));
}
