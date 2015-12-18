<?php

namespace block_exastud;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/../lib/lib.php';

use \block_exacomp\globals as g;

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
		
		$actPeriod = block_exastud_check_active_period();
		
		$classes = $DB->get_records_sql("
			SELECT ct.id, ct.subjectid, ct.classid, c.class, s.title AS subject
			FROM {block_exastudclassteachers} ct
			JOIN {block_exastudclass} c ON ct.classid=c.id
			LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
			JOIN {block_exastudclassstudents} cs ON cs.classid=c.id
			WHERE ct.teacherid=? AND c.periodid=? AND cs.studentid=?
			ORDER BY c.class, s.sorting
		", array($USER->id, $actPeriod->id, $userid));
		
		if (!$classes) {
			// keine bewertung für schüler
			return null;
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

	static function get_student_reviews($userid) {
		global $DB;

		// TODO
		die('todo');
		$sql = "
			SELECT DISTINCT p.id, p.description
			FROM {block_exastudreview} r
			JOIN {block_exastudperiod} p ON r.periodid = p.id
			WHERE r.studentid = ?
		";
		return $DB->get_records_sql($sql,array("studentid"=>$userid));
	}

	static function delete_user_data($userid){
		global $DB;

		$result = $DB->delete_records('block_exastudclass', array('userid'=>$userid));
		$result = $DB->delete_records('block_exastudperiod', array('userid'=>$userid));

		$result = $DB->delete_records('block_exastudclassteachers', array('teacherid'=>$userid));
		$result = $DB->delete_records('block_exastudreview', array('teacherid'=>$userid));

		$result = $DB->delete_records('block_exastudclassstudents', array('studentid'=>$userid));
		$result = $DB->delete_records('block_exastudreview', array('studentid'=>$userid));
	}
	
}
