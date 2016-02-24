<?php

namespace block_exastud;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/../lib/lib.php';

use block_exacomp\globals as g;

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

		return get_renderer()->print_student_report($categories, $textReviews);
	}

	static function delete_user_data($userid){
		global $DB;

		$DB->delete_records('block_exastudclass', array('userid'=>$userid));
		$DB->delete_records('block_exastudperiod', array('userid'=>$userid));

		$DB->delete_records('block_exastudclassteachers', array('teacherid'=>$userid));
		$DB->delete_records('block_exastudreview', array('teacherid'=>$userid));

		$DB->delete_records('block_exastudclassstudents', array('studentid'=>$userid));
		$DB->delete_records('block_exastudreview', array('studentid'=>$userid));

		return true;
	}
}
