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
global $DB;
$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$periodid = optional_param('periodid', 0, PARAM_INT); // Course ID
$pdf = optional_param('pdf', false, PARAM_BOOL); // Course ID
$studentid = required_param('studentid', PARAM_INT); // Course ID
require_login($courseid);

$context = get_context_instance(CONTEXT_COURSE,$courseid);
require_capability('block/exastud:use', $context);
require_capability('block/exastud:headteacher', $context);

$actPeriod = ($periodid==0) ? block_exabis_student_review_get_active_period() : $DB->get_record('block_exastudperiod', array('id'=>$periodid));

if (!$class = $DB->get_record('block_exastudclass', array('userid'=>$USER->id))) {
	print_error('noclassfound', 'block_exastud');
}

if(!$pdf) block_exabis_student_review_print_student_report_header();
block_exabis_student_review_print_student_report($studentid, $actPeriod->id, $class, $pdf);
if(!$pdf) block_exabis_student_review_print_student_report_footer();