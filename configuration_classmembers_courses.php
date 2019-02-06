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


require __DIR__.'/inc.php';

use block_exastud\globals as g;

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$classid = required_param('classid', PARAM_INT);
$selectedcourseid = optional_param('selectedcourseid', 0, PARAM_INT);

block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES);
$curPeriod = block_exastud_check_active_period();

$class = block_exastud_get_head_teacher_class($classid);

$courses = enrol_get_all_users_courses($USER->id, false, null, 'fullname');
if (!isset($courses[$selectedcourseid])) {
	$selectedcourseid = key($courses);
}

if (!$selectedcourseid) {
	$users = [];
} else {
	$users = get_enrolled_users(context_course::instance($selectedcourseid));

	// filter myself
	$users = array_filter($users, function($user) use ($USER) {
		return $user->id != $USER->id;
	});

	if (block_exastud_is_exacomp_installed()) {
		// nur schüler anzeigen
		$context = block_exacomp_get_context_from_courseid($selectedcourseid);
		$users = array_filter($users, function($user) use ($USER, $context) {
			return has_capability('block/exacomp:student', $context, $user);
		});
	}

	$classstudents = block_exastud_get_class_students($class->id);
}

if (optional_param('action', '', PARAM_TEXT) == 'add') {
	$userids = block_exastud\param::optional_array('userids', [PARAM_INT => PARAM_BOOL]);

	foreach ($userids as $userid => $add) {
        $userData = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
		if ($add) {
            $existing = $DB->get_record('block_exastudclassstudents', ['studentid' => $userid, 'classid' => $class->id]);
            g::$DB->insert_or_update_record('block_exastudclassstudents', [
				'timemodified' => time(),
			], [
				'studentid' => $userid,
				'classid' => $class->id,
			]);
			// add log only if record was added, but not updated
            if (!$existing) {
                \block_exastud\event\classmember_assigned::log(['objectid' => $class->id,
                        'courseid' => $courseid,
                        'relateduserid' => $userid,
                        'other' => ['classtitle' => $class->title,
                                    'relatedusername' => $userData->firstname.' '.$userData->lastname]]);
            }
		} else {
			$DB->delete_records('block_exastudclassstudents', [
				'studentid' => $userid,
				'classid' => $class->id,
			]);
            \block_exastud\event\classmember_unassigned::log(['objectid' => $class->id,
                    'courseid' => $courseid,
                    'relateduserid' => $userid,
                    'other' => ['classtitle' => $class->title,
                            'relatedusername' => $userData->firstname.' '.$userData->lastname]]);
		}
	}

	redirect($CFG->wwwroot.'/blocks/exastud/configuration_class.php?courseid='.$courseid.'&classid='.$class->id);
}


$url = new moodle_url('/blocks/exastud/configuration_classmembers.php');
$PAGE->set_url($url);
$output = block_exastud_get_renderer();
echo $output->header(['configuration_classes', 'students'], ['class' => $class]);

echo '<div>'.block_exastud_get_string('course').': ';
echo html_writer::select(array_map(function($c) {
	return $c->fullname;
}, $courses), "selectedcourseid", $selectedcourseid, false,
	array("onchange" => "exacommon.set_location_params({selectedcourseid: this.value});"));
echo '</div>';

if (!$users) {
	echo '<div>'.block_exastud_trans(['de:Keine anderen Benutzer gefunden', 'en:No other users found']).'</div>';
} else {

	echo '<form method="post">';
	echo '<input type="hidden" name="action" value="add" />';

	$table = new html_table();

	$table->head = [
		'<input type="checkbox" name="checkallornone" title="'.block_exastud_get_string('selectallornone', 'form').'" />',
		block_exastud_get_string('lastname'),
		block_exastud_get_string('firstname'),
	];
	// $table->align = array ("left", "center");
	$table->size = array("1%");

	foreach ($users as $user) {

		if (isset($classstudents[$user->id])) {
			// hidden input with value 0, so if teacher unchecks this user, value 0 is sent to delete that user
			$checkbox = '<input type="hidden" name="userids['.$user->id.']" value="0" />';
			$checkbox .= '<input type="checkbox" name="userids['.$user->id.']" value="1" checked="checked" />';
		} else {
			$checkbox = '<input type="checkbox" name="userids['.$user->id.']" value="1" />';
		}

		$table->data[] = [
			$checkbox,
			$user->lastname,
			$user->firstname,
		];
	}

	echo $output->table($table);
	echo '<input type="submit" value="'.block_exastud_get_string('savechanges').'" class="btn btn-default"/>';
	echo '</form>';
}

echo $output->back_button($CFG->wwwroot.'/blocks/exastud/configuration_class.php?courseid='.$courseid.'&classid='.$class->id);

echo $output->footer();
