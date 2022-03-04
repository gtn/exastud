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

namespace block_exastud;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/../inc.php';

use block_exastud\globals as g;

class api {
	static function active() {
		global $DB;
		
		// check if block is active
		if (!$DB->get_record('block',array('name'=>'exastud', 'visible'=>1))) {
			return false;
		}
		
		return true;
	}
	
	static function get_student_review_link_info_for_teacher($userid) {
		global $COURSE, $DB, $USER;
		
		$actPeriod = block_exastud_get_active_period();
		if (!$actPeriod) return;

		$classes = $DB->get_records_sql("
			SELECT ct.id, ct.subjectid, ct.classid, c.title, s.title AS subject_title
			FROM {block_exastudclassteachers} ct
			JOIN {block_exastudclass} c ON ct.classid=c.id
			LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
			JOIN {block_exastudclassstudents} cs ON cs.classid=c.id
			WHERE ct.teacherid=? AND c.periodid=? AND ct.subjectid >= 0 AND cs.studentid=?
			ORDER BY c.title, s.sorting
		", array($USER->id, $actPeriod->id, $userid));
		
		if (!$classes) {
			// keine bewertung für schüler
			return;
		}
		
		// theoretisch mehrere klassen moeglich
		// TODO: verlinkung wenn mehrere klassen?
		// vorerst nur die erste klasse verlinken
		$class = reset($classes);
		
		return (object)[
			'url' => new url('/blocks/exastud/review_student.php', [
						'courseid' => $COURSE->id,
						'classid' => $class->classid,
						'subjectid' => $class->subjectid,
						'studentid' => $userid,
						// 'returnurl' => $returnurl->as_local_url()
					])
		];
	}
	
	static function get_periods() {
		return g::$DB->get_records('block_exastudperiod', [], 'starttime DESC, endtime DESC');
	}

	static function get_student_periods_with_review($userid = 0) {
		if ($userid == 0)
			$userid = g::$USER->id;

		$sql = "
			SELECT DISTINCT p.id, p.description
			FROM {block_exastudreview} r
			JOIN {block_exastudperiod} p ON r.periodid = p.id
			WHERE r.studentid = ?
		";
		return g::$DB->get_records_sql($sql,array("studentid"=>$userid));
	}

	static function print_student_report($studentid, $periodid) {
		// get first class
		// TODO: what if student is in more than one class per period?
		$class = g::$DB->get_record_sql("
			SELECT c.*
			FROM {block_exastudclass} c
			JOIN {block_exastudclassstudents} cs ON cs.classid=c.id
			WHERE cs.studentid=? AND c.periodid=?
			ORDER BY c.title LIMIT 1
		", array($studentid, $periodid));

		$textReviews = get_text_reviews($class, $studentid);
		$categories = get_class_categories_for_report($studentid, $class->id);

		return block_exastud_get_renderer()->student_report($categories, $textReviews);
	}

	static function delete_user_data($userid){
		global $DB;

		$DB->delete_records('block_exastudclass', array('userid'=>$userid));
		// perioden sollte man hier nicht löschen
		// $DB->delete_records('block_exastudperiod', array('userid'=>$userid));

		$DB->delete_records('block_exastudclassteachers', array('teacherid'=>$userid));
		$DB->delete_records('block_exastudreview', array('teacherid'=>$userid));

		$DB->delete_records('block_exastudclassstudents', array('studentid'=>$userid));
		$DB->delete_records('block_exastudreview', array('studentid'=>$userid));

		return true;
	}

	static function get_bildungsstandard_erreicht($studentid) {
		global $DB;

		// klase mit letztem bildugnsstandard erreicht
		$class = g::$DB->get_record_sql("
			SELECT c.*
			FROM {block_exastudclass} c
			JOIN {block_exastudclassstudents} cs ON cs.classid=c.id
			JOIN {block_exastuddata} d ON d.classid=c.id AND cs.studentid=d.studentid
				AND name='bildungsstandard_erreicht_time' AND value>0
			WHERE cs.studentid=?
			ORDER BY d.value DESC LIMIT 1
		", [$studentid]);

		if (!$class) {
			return null;
		}

		$userdata = block_exastud_get_class_student_data($class->id, $studentid);
		return $userdata;
	}
}
