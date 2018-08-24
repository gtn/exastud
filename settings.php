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

if ($ADMIN->fulltree) {
	$settings->add(new admin_setting_configtext('exastud/school_name', block_exastud_trans('de:Lernentwicklungsbericht: Schulname'), '', '', PARAM_TEXT));
	$settings->add(new admin_setting_configtext('exastud/school_location', block_exastud_trans('de:Lernentwicklungsbericht: Ort'), '', '', PARAM_TEXT));
	$settings->add(new admin_setting_configtext('exastud/bildungsstandards', block_exastud_trans('de:Bildungsstandards'),
		block_exastud_trans('de:Liste, mit Kommata getrennt'), '5,6,7,8,9,10', PARAM_TEXT));
	$settings->add(new admin_setting_configcheckbox('exastud/bw_active', block_exastud_trans('de:Baden-Württemberg Berichte'), '', 0));
	$settings->add(new admin_setting_configcheckbox('exastud/use_exacomp_grade_verbose', block_exastud_trans('de:Exabis Kompetenzraster Notenverbalisierung verwenden.'), '', 0));
    $settings->add(new admin_setting_configcheckbox('exastud/logging', block_exastud_get_string('logging'), '', 0));
}
