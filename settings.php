<?php

defined('MOODLE_INTERNAL') || die;

require_once __DIR__.'/lib/lib.php';

if ($ADMIN->fulltree) {
	if (!block_exastud_is_new_version()) {
		$settings->add(new admin_setting_configcheckbox('block_exastud_detailed_review', get_string('settings_detailed_review', 'block_exastud'),
						   get_string('settings_detailed_review_body', 'block_exastud'), 0, 1, 0));
		$settings->add(new admin_setting_configcheckbox('block_exastud_project_based_assessment', get_string('settings_project_based_assessment', 'block_exastud'),
				get_string('settings_project_based_assessment_body', 'block_exastud'), 0, 1, 0));
	}
	$settings->add(new admin_setting_configtext('exastud/school_name', \block_exastud\trans('de:Schulname'), '', '', PARAM_TEXT));
}
