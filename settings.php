<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox('block_exastud_detailed_review', get_string('settings_detailed_review', 'block_exastud'),
                       get_string('settings_detailed_review_body', 'block_exastud'), 0, 1, 0));

}
