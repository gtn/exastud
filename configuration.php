<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// All rights reserved
/**
 * @package moodlecore
 * @subpackage blocks
 * @copyright 2013 gtn gmbh
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
*/

require("inc.php");

$courseid       = optional_param('courseid', 1, PARAM_INT); // Course ID
$action = optional_param('action', '', PARAM_TEXT);
require_login($courseid);

block_exastud_require_course_cap(block_exastud::CAP_HEADTEACHER, $courseid);
$curPeriod = block_exastud_check_active_period();

$url = '/blocks/exastud/configuration.php';
$PAGE->set_url($url);

if (!$class = $DB->get_record('block_exastudclass', array('userid'=>$USER->id,'periodid' => $curPeriod->id))) {
	redirect('configuration_class.php?courseid=' . $courseid, block_exastud_get_string('redirectingtoclassinput', 'block_exastud'));
}

if ($action == 'save-classteacher-subject') {
    if(!confirm_sesskey()) {
        die(get_string("badsessionkey","block_exastud"));
    }

    block_exastud\db::update_record('block_exastudclassteachers', array(
        'subjectid' => required_param('subjectid', PARAM_INT)
    ), array(
        'id' => required_param('classteacherid', PARAM_INT),
        'classid' => $class->id
    ));

    echo 'ok';

    exit;
}

block_exastud_print_header('configuration');
$blockrenderer = $PAGE->get_renderer('block_exastud');

echo $blockrenderer->print_subtitle($class->class, $CFG->wwwroot . '/blocks/exastud/configuration_class.php?courseid='.$courseid.'&sesskey='. sesskey());

/* Print the Students */
echo html_writer::tag("h2",block_exastud_get_string('members', 'block_exastud'));
$table = new html_table();

$table->head = array (block_exastud_get_string('firstname'), block_exastud_get_string('lastname'), block_exastud_get_string('email'));
$table->align = array ("left", "left", "left");
$table->width = "67.5%";
$table->size = ['33%', '33%', '33%'];

$usertoclasses = $DB->get_records('block_exastudclassstudents', array('classid'=>$class->id), 'studentid');

foreach($usertoclasses as $usertoclass) {
	$user = $DB->get_record('user', array('id'=>$usertoclass->studentid));
	$table->data[] = array ($user->firstname, $user->lastname, $user->email);
}

//echo html_writer::table($table);
echo $blockrenderer->print_esr_table($table);

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_classmembers.php?courseid='.$courseid.'&sesskey='. sesskey(),
		block_exastud_get_string('editclassmemberlist', 'block_exastud'));

/* Print the Classes */
echo html_writer::tag("h2",block_exastud_get_string('teachers', 'block_exastud'));
$table = new html_table();

$table->head = array (block_exastud_get_string('firstname'), block_exastud_get_string('lastname'), block_exastud_get_string('email'), block_exastud::t('de:Gegenstand'));
$table->align = array ("left", "left", "left", "left");
$table->width = "90%";
$table->size = ['25%', '25%', '25%', '25%'];

$classteachers = $DB->get_recordset_sql("
    SELECT ct.id, ".user_picture::fields('u', null, 'userid').", s.title AS subject
    FROM {user} u
    JOIN {block_exastudclassteachers} ct ON ct.teacherid=u.id
    LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
    WHERE ct.classid=?
    ORDER BY s.sorting, u.lastname, u.firstname
", array($class->id));


foreach($classteachers as $classteacher) {
    $table->data[] = array ($classteacher->firstname, $classteacher->lastname, $classteacher->email, $classteacher->subject);
}

//echo html_writer::table($table);
echo $blockrenderer->print_esr_table($table);

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_classteachers.php?courseid='.$courseid.'&sesskey='. sesskey(),
		block_exastud_get_string('editclassteacherlist', 'block_exastud'));

/* Print the categories */
echo html_writer::tag("h2",block_exastud_get_string('categories', 'block_exastud'));

$table = new html_table();

$table->align = array("left");
$table->width = "45%";

$categories = block_exastud_get_class_categories($class->id);

foreach($categories as $category) {
	$table->data[] = array($category->title);
}

echo $blockrenderer->print_esr_table($table);

echo $OUTPUT->single_button($CFG->wwwroot . '/blocks/exastud/configuration_categories.php?courseid='.$courseid.'&sesskey='.sesskey(),
		block_exastud_get_string('editclasscategories', 'block_exastud'));

block_exastud_print_footer();
