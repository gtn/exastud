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

require_once __DIR__.'/lib/lib.php';

if ($ADMIN->fulltree) {
	if (!block_exastud_is_new_version()) {
		$settings->add(new admin_setting_configcheckbox('block_exastud_detailed_review', get_string('settings_detailed_review', 'block_exastud'),
						   get_string('settings_detailed_review_body', 'block_exastud'), 0, 1, 0));
		$settings->add(new admin_setting_configcheckbox('block_exastud_project_based_assessment', get_string('settings_project_based_assessment', 'block_exastud'),
				get_string('settings_project_based_assessment_body', 'block_exastud'), 0, 1, 0));
	}
	$settings->add(new admin_setting_configtext('exastud/school_name', \block_exastud\trans('de:Lernentwicklungsbericht: Schulname'), '', '', PARAM_TEXT));
	$settings->add(new admin_setting_configtext('exastud/school_location', \block_exastud\trans('de:Lernentwicklungsbericht: Ort'), '', '', PARAM_TEXT));
	$settings->add(new admin_setting_configtext('exastud/certificate_issue_date', \block_exastud\trans('de:Lernentwicklungsbericht: Zeugnisausgabedatum'), '', '', PARAM_TEXT));
}
