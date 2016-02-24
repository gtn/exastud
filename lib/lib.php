<?php

namespace {

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot.'/cohort/lib.php';
require_once __DIR__.'/../block_exastud.php';
require_once __DIR__.'/common.php';

}

/*
 * overwrite common functions
 */
namespace block_exastud {
	use block_exastud\globals as g;

	const CAP_HEAD_TEACHER = 'head_teacher';
	// const CAP_SUBJECT_TEACHER = 'subject_teacher';

	const CAP_USE = 'use';
	const CAP_EDIT_PERIODS = 'editperiods';
	const CAP_UPLOAD_PICTURE = 'exastud:uploadpicture';
	const CAP_ADMIN = 'admin';
	const CAP_MANAGE_CLASSES = 'createclass';
	const CAP_VIEW_REPORT = 'viewreport';
	const CAP_REVIEW = 'review';

	const DATA_ID_LERN_UND_SOZIALVERHALTEN = 'learning_and_social_behavior';
	const SUBJECT_ID_LERN_UND_SOZIALVERHALTEN = -1;
	const SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG = -3;
	const SUBJECT_ID_OTHER_DATA = -1;
	const SUBJECT_ID_ADDITIONAL_CLASS_TEACHER = -2;

	/**
	 * Returns a localized string.
	 * This method is neccessary because a project based evaluation is available in the current exastud
	 * version, which requires a different naming.
	 */
	function get_string($identifier, $component = null, $a = null, $lazyload = false) {
		global $CFG;

		$manager = get_string_manager();

		if ($component == null) {
			$component = 'block_exastud';
		}

		// first try string with project_based_* prefix
		if (($component == 'block_exastud') && !empty($CFG->block_exastud_project_based_assessment) && $manager->string_exists('project_based_'.$identifier, $component)) {
			return $manager->get_string('project_based_'.$identifier, $component, $a);
		}

		if ($manager->string_exists($identifier, $component)) {
			return $manager->get_string($identifier, $component, $a);
		}

		return $manager->get_string($identifier, '', $a);
	}

	class permission_exception extends moodle_exception {
		function __construct($errorcode = 'Not allowed', $module='', $link='', $a=NULL, $debuginfo=null) {
			return parent::__construct($errorcode, $module, $link, $a, $debuginfo);
		}
	}

	function is_head_teacher($userid = null) {
		$cohort = get_head_teacher_cohort();
		return cohort_is_member($cohort->id, $userid ? $userid : g::$USER->id);
	}

	function get_head_teacher_cohort() {
		// get or create cohort if not exists
		$cohort = g::$DB->get_record('cohort', ['contextid' => \context_system::instance()->id, 'idnumber' => 'block_exastud_head_teachers']);

		$name = get_string('head_teachers');
		$description = trans('de:Können Klassen anlegen, Lehrkräfte und Schüler/innen zubuchen und den Lernentwicklungsbericht abrufen');

		if (!$cohort) {
			$cohort = (object)[
				'contextid' => \context_system::instance()->id,
				'idnumber' => 'block_exastud_head_teachers',
				'name' => $name,
				'description' => $description,
				'visible' => 1,
				'component' => '', // should be block_exastud, but then the admin can't change the group members anymore
			];
			$cohort->id = cohort_add_cohort($cohort);
		} else {
			// keep name or description up to date
			if ($name != $cohort->name || $description != $cohort->description) {
				g::$DB->update_record('cohort', [
					'id' => $cohort->id,
					'name' => $name,
					'description' => $description,
				]);
			}
		}

		return $cohort;
	}

	function get_head_teacher_classes_owner() {
		if (!block_exastud_has_global_cap(CAP_MANAGE_CLASSES)) {
			return [];
		}

		$curPeriod = block_exastud_check_active_period();
		return g::$DB->get_records_sql("
			SELECT c.*,
				'normal' AS type
			FROM {block_exastudclass} c
			WHERE c.userid=? AND c.periodid=?
			ORDER BY c.title", [g::$USER->id, $curPeriod->id]);
	}

	function get_head_teacher_classes_shared() {
		if (!block_exastud_has_global_cap(CAP_MANAGE_CLASSES)) {
			return [];
		}

		$curPeriod = block_exastud_check_active_period();
		$classes = g::$DB->get_records_sql("
			SELECT c.*,
				'shared' AS type,
				".\user_picture::fields('u', null, 'teacher_owner_id', 'teacher_owner_')."
			FROM {block_exastudclass} c
			JOIN {block_exastudclassteachers} ct ON ct.classid=c.id
			JOIN {user} u ON c.userid = u.id
			WHERE ct.subjectid=".SUBJECT_ID_ADDITIONAL_CLASS_TEACHER." AND ct.teacherid=? AND c.periodid=?
			ORDER BY c.title", [g::$USER->id, $curPeriod->id]);

		/*
		foreach ($classes as $class) {
			$class->title_full = fullname(filter_fields_by_prefix($class, 'teacher_owner_')).': '.$class->title;
		}
		*/

		return $classes;
	}

	function get_head_teacher_classes_all() {
		return get_head_teacher_classes_owner() + get_head_teacher_classes_shared();
	}

	function get_teacher_class($classid) {
		$classes = get_head_teacher_classes_all();

		if (!isset($classes[$classid])) {
			throw new moodle_exception('class not found');
		}

		return $classes[$classid];
	}

	function get_class_students($classid) {
		return g::$DB->get_records_sql("
			SELECT u.id, cs.id AS record_id, ".\user_picture::fields('u', null, 'userid')."
			FROM {user} u
			JOIN {block_exastudclassstudents} cs ON u.id=cs.studentid
			WHERE cs.classid=?
			ORDER BY u.lastname, u.firstname
		", [$classid]);
	}

	function get_class_teachers($classid) {
		$classteachers = iterator_to_array(g::$DB->get_recordset_sql("
			SELECT u.id, ct.id AS record_id, ".\user_picture::fields('u', null, 'userid').", ct.subjectid, s.title AS subject_title
			FROM {user} u
			JOIN {block_exastudclassteachers} ct ON ct.teacherid=u.id
			LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
			WHERE ct.classid=?
			ORDER BY s.sorting, u.lastname, u.firstname, s.id
		", [$classid]), false);

		foreach ($classteachers as $classteacher) {
			if ($classteacher->subjectid == SUBJECT_ID_ADDITIONAL_CLASS_TEACHER) {
				$classteacher->subject_title = get_string('head_teacher');
			}
		}

		return $classteachers;
	}

	function get_head_teacher_lern_und_sozialverhalten_classes() {
		$classes = get_head_teacher_classes_all();

		$ret = [];
		foreach ($classes as $class) {
			if (!block_exastud_has_global_cap(CAP_VIEW_REPORT, $class->userid)) {
				continue;
			}

			$ret[$class->id] = (object)[
				'classid' => $class->id,
				'subjectid' => SUBJECT_ID_LERN_UND_SOZIALVERHALTEN,
				'userid' => $class->userid,
				'title' => $class->title,
				'subject' => trans('de:Lern- und Sozialverhalten'),
				'type' => $class->type,
			];
		}

		return $ret;
	}

	/**
	 * this returns all review classes, can have multiple class entries if teacher has more than 1 subject
	 * @return array
	 */
	function get_review_classes() {
		$actPeriod = block_exastud_get_active_period();
		return g::$DB->get_records_sql("
			SELECT ct.id, ct.subjectid, ct.classid, c.title, s.title AS subject_title
			FROM {block_exastudclassteachers} ct
			JOIN {block_exastudclass} c ON ct.classid=c.id
			LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
			WHERE ct.teacherid=? AND c.periodid=? AND ct.subjectid >= 0
			ORDER BY c.title, s.sorting
		", array(g::$USER->id, $actPeriod->id));
	}

	function get_review_class($classid, $subjectid) {
		global $DB, $USER;

		if ($subjectid == SUBJECT_ID_LERN_UND_SOZIALVERHALTEN) {
			$classes = get_head_teacher_lern_und_sozialverhalten_classes();
			return isset($classes[$classid]) ? $classes[$classid] : null;
		} else {
			return $DB->get_record_sql("
			SELECT ct.id, ct.id AS classteacherid, c.title, s.title AS subject_title, c.userid
			FROM {block_exastudclassteachers} ct
			JOIN {block_exastudclass} c ON ct.classid=c.id
			LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
			WHERE ct.teacherid=? AND ct.classid=? AND ct.subjectid >= 0 AND ".($subjectid?'s.id=?':'s.id IS NULL')."
		", array($USER->id, $classid, $subjectid));
		}
	}

	/**
	 * wrote own function, so eclipse knows which type the output renderer is
	 * @return \block_exastud_renderer
	 */
	function get_renderer() {
		return g::$PAGE->get_renderer('block_exastud');
	}

	function filter_fields_by_prefix($object_or_array, $prefix) {
		$ret = [];
		foreach ($object_or_array as $key=>$value) {
			if (strpos($key, $prefix) === 0) {
				$ret[substr($key, strlen($prefix))] = $value;
			}
		}

		if (is_object($object_or_array)) {
			$ret = (object)$ret;
		}
		return $ret;
	}

	function get_text_reviews($class, $studentid) {
		$textReviews = iterator_to_array(g::$DB->get_recordset_sql("
			SELECT DISTINCT ".\user_picture::fields('u').", r.review, s.title AS subject_title, r.subjectid AS subjectid
			FROM {block_exastudreview} r
			JOIN {user} u ON r.teacherid = u.id
			JOIN {block_exastudsubjects} s ON r.subjectid = s.id
			JOIN {block_exastudclass} c ON c.periodid = r.periodid
			JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid = r.teacherid AND ct.subjectid=r.subjectid

			WHERE r.studentid = ? AND r.periodid = ? AND TRIM(r.review) !=  ''
			ORDER BY NOT(r.subjectid<0), s.title, u.lastname, u.firstname -- TODO: anpassen",
		array($studentid, $class->periodid)), false);

		foreach ($textReviews as $textReview) {
			if ($textReview->subject_title)
				$textReview->title = $textReview->subject_title; // .' ('.fullname($textReview).')';
			else
				$textReview->title = fullname($textReview);
		}

		$lern_und_sozialverhalten = g::$DB->get_record('block_exastudreview', array('teacherid' => $class->userid, 'subjectid'=>SUBJECT_ID_LERN_UND_SOZIALVERHALTEN, 'periodid' => $class->periodid, 'studentid' => $studentid));
		if ($lern_und_sozialverhalten) {
			$lern_und_sozialverhalten->title = trans('de:Lern- und Sozialverhalten');
			array_unshift($textReviews, $lern_und_sozialverhalten);
		}

		return $textReviews;
	}

	function get_reviewers_by_category_and_pos($periodid, $studentid, $categoryid, $categorysource, $pos_value) {
		return iterator_to_array(g::$DB->get_recordset_sql("
			SELECT u.*, s.title AS subject_title, pos.value
			FROM {block_exastudreview} r
			JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
			JOIN {user} u ON r.teacherid = u.id
			JOIN {block_exastudclass} c ON c.periodid = r.periodid
			JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid=r.teacherid AND ct.subjectid=r.subjectid
			LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
			WHERE c.periodid = ? AND r.studentid = ?
				AND pos.categoryid = ? AND pos.categorysource = ?
			".($pos_value !== null ? "AND pos.value = ?" : "AND pos.value > 0")."
			GROUP BY r.teacherid, s.id, pos.value
		", [$periodid, $studentid, $categoryid, $categorysource, $pos_value]), false);
	}

	function get_class_categories_for_report($studentid, $classid) {
		$evaluationOtions = block_exastud_get_evaluation_options();
		$categories = block_exastud_get_class_categories($classid);

		$current_parent = null;
		foreach ($categories as $category){

			$category->fulltitle = $category->title;
			if (preg_match('!^([^:]*):\s*([^\s].*)$!', $category->fulltitle, $matches)) {
				$category->parent = $matches[1];
				$category->title = $matches[2];
			} else {
				$category->parent = '';
				$category->title = $category->fulltitle;
			}

			$category->evaluationOtions = [];
			foreach ($evaluationOtions as $pos_value => $option) {
				$category->evaluationOtions[$pos_value] = (object)[
					'value' => $pos_value,
					'title' => $option,
					'reviewers' => get_reviewers_by_category_and_pos(block_exastud_get_active_period()->id, $studentid, $category->id, $category->source, $pos_value)
				];
			}
		}

		return $categories;
	}

	function get_custom_profile_field_value($userid, $fieldname) {
		return g::$DB->get_field_sql("SELECT uid.data
			FROM {user_info_data} uid
			JOIN {user_info_field} uif ON uif.id=uid.fieldid
			WHERE uif.shortname=? AND uid.userid=?
			", [$fieldname, $userid]);
	}

	function is_exacomp_installed() {
		return class_exists('\block_exacomp\api') && \block_exacomp\api::active();
	}

	function get_class_student_data($classid, $userid) {
		return g::$DB->get_records_menu('block_exastuddata', [
			'classid' => $classid,
			'studentid' => $userid
		], 'name', 'name, value');
	}

	function set_class_student_data($classid, $userid, $name, $value) {
		g::$DB->insert_or_update_record('block_exastuddata', [
			'value' => $value,
		], [
			'classid' => $classid,
			'studentid' => $userid,
			'name' => $name,
		]);
	}
}

namespace {

use block_exastud\globals as g;

define('DECIMALPOINTS', 1);

function block_exastud_is_new_version() {
	return true;
}

function block_exastud_has_global_cap($cap, $user = null) {
	try {
		block_exastud_require_global_cap($cap, $user);
		return true;
	} catch (block_exastud\permission_exception $e) {
		return false;
	} catch (\required_capability_exception $e) {
		return false;
	}
}

function block_exastud_require_global_cap($cap, $user = null) {
	// all capabilities require use
	require_capability('block/exastud:use', context_system::instance(), $user);

	switch ($cap) {
		case \block_exastud\CAP_EDIT_PERIODS:
		case \block_exastud\CAP_UPLOAD_PICTURE:
			require_capability('block/exastud:admin', context_system::instance(), $user);
			return;

		case \block_exastud\CAP_MANAGE_CLASSES:
		case \block_exastud\CAP_HEAD_TEACHER:
		case \block_exastud\CAP_VIEW_REPORT:
			if (!\block_exastud\is_head_teacher($user)) {
				throw new block_exastud\permission_exception('no headteacher');
			} else {
				return;
			}
		case \block_exastud\CAP_REVIEW:
			if (!\block_exastud\get_review_classes()) {
				throw new block_exastud\permission_exception('no classes');
			} else {
				return;
			}
	}

	require_capability('block/exastud:'.$cap, context_system::instance(), $user);
}

function block_exastud_check_periods($printBoxInsteadOfError = false) {
	block_exastud_has_wrong_periods($printBoxInsteadOfError);
	block_exastud_check_if_period_ovelap($printBoxInsteadOfError);
}
/*
function block_exastud_get_review_periods($studentid) {
	global $DB;
	return $DB->get_records_sql('SELECT periods_id FROM {block_exastudreview} r
			WHERE student_id = ? GROUP BY periods_id',array($studentid));
}
*/
function block_exastud_reviews_available() {
	global $DB, $USER, $CFG;
	
	if (block_exastud_is_new_version()) {
		// new version doesn't allow reviews for now
		return false;
	}
	
	$availablereviews = $DB->get_records_sql('SELECT id
			FROM {block_exastudreview}
			WHERE teacherid = '.$USER->id.' AND studentid IN (
			SELECT studentid
			FROM {block_exastudclassstudents} s, {block_exastudclass} c
			WHERE c.userid = '.$USER->id.' AND s.classid=c.id )');
	
	if(!empty($CFG->block_exastud_project_based_assessment)) {
		// lehrer classteacher und classstudents in period a review
		$availablereviews = $DB->get_records_sql('SELECT r.id FROM {block_exastudreview} r
			WHERE r.studentid IN
			(
			SELECT cs.studentid FROM {block_exastudclassteachers} ct, {block_exastudclassstudents} cs
			WHERE ct.teacherid = ? AND ct.classid = cs.classid
			)',array($USER->id));
	}
	return (bool)$availablereviews;
}
function block_exastud_has_wrong_periods($printBoxInsteadOfError = false) {
	global $DB;
	// check if any entry has a starttime after the endtime:
	$wrongs = $DB->get_records_sql('SELECT p.description, p.starttime, p.endtime FROM {block_exastudperiod} p WHERE starttime > endtime');

	if ($wrongs) {
		foreach($wrongs as $wrong) {
			if($printBoxInsteadOfError) {
				notify(get_string('errorstarttimebeforeendtime', 'block_exastud', $wrong));
			}
			else {
				error('errorstarttimebeforeendtime', 'block_exastud', '', $wrong);
			}
		}
	}

	return true;
}

function block_exastud_check_if_period_ovelap($printBoxInsteadOfError = false) {
	global $DB;
	$allPeriods = $DB->get_records('block_exastudperiod', null, 'id, description, starttime, endtime');

	$periodshistory = '';
	foreach ($allPeriods as $actPeriod) {
		if($periodshistory == '') {
			$periodshistory .= $actPeriod->id;
		}
		else {
			$periodshistory .= ', ' . $actPeriod->id;
		}
		$ovelapPeriods = $DB->get_records_sql('SELECT id, description, starttime, endtime FROM {block_exastudperiod}
				WHERE (id NOT IN (' . $periodshistory . ')) AND NOT ( (starttime < ' . $actPeriod->starttime . ' AND endtime < ' . $actPeriod->starttime . ')
				OR (starttime > ' . $actPeriod->endtime . ' AND endtime > ' . $actPeriod->endtime . ') )');

		if ($ovelapPeriods) {
			foreach ($ovelapPeriods as $overlapPeriod) {
				$a = new stdClass();
				$a->period1 = $actPeriod->description;
				$a->period2 = $overlapPeriod->description;

				if($printBoxInsteadOfError) {
					notify(get_string('periodoverlaps', 'block_exastud', $a));
				}
				else {
					print_error('periodoverlaps', 'block_exastud', '', $a);
				}
			}
		}
	}
}

function block_exastud_check_active_period() {
	global $CFG,$COURSE;

	if ($period = block_exastud_get_active_period()) {
		return $period;
	}
	
	if (block_exastud_has_global_cap(block_exastud\CAP_EDIT_PERIODS)) {
		redirect($CFG->wwwroot.'/blocks/exastud/configuration_periods.php?courseid='.$COURSE->id, \block_exastud\get_string('redirectingtoperiodsinput'));
	}
	
	throw new \moodle_exception('periodserror', 'block_exastud', $CFG->wwwroot.'/blocks/exastud/configuration_periods.php?courseid='.$COURSE->id);
}

function block_exastud_get_active_period() {
	global $DB;
	
	$periods = $DB->get_records_sql('SELECT * FROM {block_exastudperiod} WHERE (starttime <= ' . time() . ') AND (endtime >= ' . time() . ')');

	// genau 1e periode?
	if (count($periods) == 1) {
		return reset($periods);
	} else {
		return null;
	}
}

function block_exastud_get_period($periodid, $loadActive = true) {
	if ($periodid) {
		return g::$DB->get_record('block_exastudperiod', array('id'=>$periodid));
	} elseif ($loadActive) {
		// if period empty, load active one 
		return block_exastud_get_active_period();
	} else {
		return null;
	}
}

/*
function block_exastud_check_period($periodid, $loadActive = true) {
	$period = block_exastud_get_period($periodid, $loadActive);
	
	if ($period) {
		return $period;
	} else {
		print_error("invalidperiodid","block_exastud");
	}
}
*/

function block_exastud_get_period_categories($periodid) {
	global $DB;

	$reviewcategories = $DB->get_recordset_sql('SELECT rp.categoryid, rp.categorysource FROM {block_exastudreviewpos} rp, {block_exastudreview} r WHERE r.periodid=? AND rp.reviewid=r.id GROUP BY rp.categoryid, rp.categorysource',array($periodid));

	$categories=array();
	foreach($reviewcategories as $reviewcategory) {
		if ($tmp = block_exastud_get_category($reviewcategory->categoryid, $reviewcategory->categorysource))
			$categories[] = $tmp;
	}

	return $categories;
}
/*
function block_exastud_get_detailed_report($studentid, $periodid) {
	global $DB;

	$report = new stdClass();
	$review = $DB->get_records_sql('SELECT concat(pos.categoryid,"_",pos.categorysource) as uniqueuid, pos.value, u.lastname, u.firstname, pos.categoryid, pos.categorysource FROM 	{block_exastudreview} r
			JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
			JOIN {user} u ON r.teacher_id = u.id WHERE student_id = ? AND periods_id = ?',array($studentid,$periodid));

	$cats = $DB->get_records_sql('SELECT concat(categoryid,"_",categorysource) as uniqueuid,rp.categoryid, rp.categorysource FROM {block_exastudreview} r, {block_exastudreviewpos} rp where r.student_id = ? AND r.periods_id = ? AND rp.reviewid = r.id GROUP BY rp.categoryid, rp.categorysource',array($studentid,$periodid));
	foreach($cats as $cat) {

		if ($category = block_exastud_get_category($rcat->categoryid, $rcat->categorysource)) {


			$report->{$category->title} = is_null($rcat->avgvalue) ? '' : $rcat->avgvalue;

		}

	}

	return $report;
}
*/
function block_exastud_get_report($studentid, $periodid) {
	global $DB;

	$report = new stdClass();

	$totalvalue = $DB->get_record_sql('SELECT sum(rp.value) as total FROM {block_exastudreview} r, {block_exastudreviewpos} rp where r.studentid = ? AND r.periodid = ? AND rp.reviewid = r.id',array($studentid,$periodid));
	$report->totalvalue = $totalvalue->total;


	$reviewcategories = $DB->get_records_sql("
		SELECT DISTINCT rp.categoryid, rp.categorysource
		FROM {block_exastudreview} r
		JOIN {block_exastudreviewpos} rp ON rp.reviewid = r.id
		WHERE r.studentid = ? AND r.periodid = ?",
		array($studentid, $periodid));

	$report->category_averages = [];

	foreach ($reviewcategories as $rcat) {
		if ($category = block_exastud_get_category($rcat->categoryid, $rcat->categorysource)) {
			$catid = $rcat->categorysource.'-'.$rcat->categoryid;

			$reviewers = block_exastud\get_reviewers_by_category_and_pos($periodid, $studentid, $rcat->categoryid, $rcat->categorysource, null);
			$category_total = 0;
			$category_cnt = 0;

			foreach ($reviewers as $reviewer) {
				$category_total += $reviewer->value;
				$category_cnt++;
			}
			$average = $category_cnt > 0 ? round($category_total/$category_cnt, 2) : 0;
			$report->category_averages[$category->title] = $average; // wird das noch benötigt?
			$report->category_averages[$catid] = $average;
		}
	}

	$numrecords = $DB->get_record_sql('SELECT COUNT(id) AS count FROM {block_exastudreview} WHERE studentid=' . $studentid . ' AND periodid=' . $periodid);
	$report->numberOfEvaluations = $numrecords->count;

	$comments = $DB->get_recordset_sql("
				SELECT ".user_picture::fields('u').", r.review, s.title AS subject_title
				FROM {block_exastudreview} r
				JOIN {user} u ON r.teacherid = u.id
				LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
				WHERE r.studentid = ? AND r.periodid = ? AND TRIM(r.review) !=  ''
				ORDER BY s.title, u.lastname, u.firstname",
				array($studentid, $periodid));

	$report->comments = array();
	foreach($comments as $comment) {
		$newcomment = new stdClass();
		$newcomment->name = ($comment->subject_title?$comment->subject_title.' ('.fullname($comment).')':fullname($comment));
		$newcomment->review = format_text($comment->review);

		$report->comments[] = $newcomment;
	}

	return $report;
}

function block_exastud_read_template_file($filename) {
	global $CFG;
	$filecontent = '';

	if(is_file($CFG->dirroot . '/blocks/exastud/template/' . $filename)) {
		$filecontent = file_get_contents ($CFG->dirroot . '/blocks/exastud/template/' . $filename);
	}
	else if(is_file($CFG->dirroot. '/blocks/exastud/default_template/' . $filename)) {
		$filecontent = file_get_contents ($CFG->dirroot. '/blocks/exastud/default_template/' . $filename);
	}
	$filecontent = str_replace ( '###WWWROOT###', $CFG->wwwroot, $filecontent);
	return $filecontent;
}

function block_exastud_print_student_report_header() {
	echo block_exastud_read_template_file('header.html');
}
function block_exastud_print_student_report_footer() {
	echo block_exastud_read_template_file('footer.html');
}

function block_exastud_print_student_report($studentid, $periodid, $class, $pdf=false, $detail=false, $ranking = false)
{
	global $DB,$CFG,$OUTPUT;

	$detailedreview = !empty($CFG->block_exastud_detailed_review) && $detail;

	$period =$DB->get_record('block_exastudperiod', array('id'=>$periodid));

	if(!$studentReport = block_exastud_get_report($studentid, $periodid)) {
		print_error('studentnotfound','block_exastud');
	}

	
	$student = $DB->get_record('user', array('id'=>$studentid));
	$studentreport = block_exastud_read_template_file('student_new.html');
	$studentreport = str_replace ( '###STUDENTREVIEW###', \block_exastud\get_string('studentreview','block_exastud'), $studentreport);
	$studentreport = str_replace ( '###NAME###', get_string('name','block_exastud'), $studentreport);
	$studentreport = str_replace ( '###PERIODREVIEW###', get_string('periodreview','block_exastud'), $studentreport);
	$studentreport = str_replace ( '###REVIEWCOUNT###', get_string('reviewcount','block_exastud'), $studentreport);
	$studentreport = str_replace ( '###CLASSTRANSLATION###', \block_exastud\get_string('class','block_exastud'), $studentreport);
	$studentreport = str_replace ( '###FIRSTNAME###', $student->firstname, $studentreport);
	$studentreport = str_replace ( '###LASTNAME###', $student->lastname, $studentreport);
	if (!empty($CFG->block_exastud_project_based_assessment) && $ranking) {
		$studentreport = str_replace ( '###RANKING###', $ranking, $studentreport);
		$studentreport = str_replace ( '###RANKINGTRANSLATION###', 'Ranking', $studentreport);
	} else {
		$studentreport = str_replace ( '<tr>
						<td class="printpersonalinfo_heading">###RANKING###</td>
					</tr>
					<tr>
						<td class="printpersonalinfo_subheading">###RANKINGTRANSLATION###</td>
					</tr>', "", $studentreport);
	}
	if(!$pdf) $studentreport = str_replace ( '###USERPIC###', $OUTPUT->user_picture($DB->get_record('user', array("id"=>$studentid)),array("size"=>100)), $studentreport);
	else $studentreport = str_replace( '###USERPIC###', '', $studentreport);

	if ($logo = block_exastud_get_main_logo_url()) {
		$img = '<img id="logo" width="840" height="100" src="'.$logo.'"/>';
	} else {
		$img = '';
	}
	$studentreport = str_replace ( '###TITLE###',$img, $studentreport);
	$studentreport = str_replace ( '###CLASS###', $class->title, $studentreport);
	$studentreport = str_replace ( '###NUM###', $studentReport->numberOfEvaluations, $studentreport);
	$studentreport = str_replace ( '###PERIOD###', $period->description, $studentreport);
	$studentreport = str_replace ( '###LOGO###', $img, $studentreport);

	$categories = ($periodid==block_exastud_check_active_period()->id) ? block_exastud_get_class_categories($class->id) : block_exastud_get_period_categories($periodid);

	$html='';

	foreach($categories as $category) {
		$html.='<tr class="ratings"><td class="ratingfirst text">'.$category->title.'</td>
		<td class="rating legend">'.@$studentReport->{$category->title}.'</td></tr>';
			
		if($detailedreview) {
			$detaildata = $DB->get_recordset_sql("SELECT ".user_picture::fields('u').", pos.value, s.title AS subject_title
					FROM 	{block_exastudreview} r
					JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
					JOIN {user} u ON r.teacherid = u.id
					LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
					WHERE studentid = ? AND periodid = ? AND pos.categoryid = ? AND pos.categorysource = ?",array($studentid,$periodid,$category->id,$category->source));
			foreach($detaildata as $detailrow)
				$html.='<tr class="ratings"><td class="teacher">'.($detailrow->subject_title?$detailrow->subject_title.' ('.fullname($detailrow).')':fullname($detailrow)) . '</td>
				<td class="rating legend teacher">'.$detailrow->value.'</td></tr>';
		}
	}
	$studentreport = str_replace ( '###CATEGORIES###', $html, $studentreport);


	if (!$studentReport->comments) {
		$studentreport = str_replace ( '###COMMENTS###', '', $studentreport);
	}
	else {
		$comments='
		<table class="ratingtable"><tr class="ratingheading"><td><h3>'.get_string('detailedreview','block_exastud').'</h3></td></tr></table>';
		foreach($studentReport->comments as $comment) {
			$comments.='<table class="ratingtable">
			<tr class="ratinguser"><td class="ratingfirst">'.$comment->name.'</td></tr>
			<tr class="ratingtext"><td>'.$comment->review.'</td>
			</tr>
			</table>';
		}
		$studentreport = str_replace ( '###COMMENTS###', $comments, $studentreport);
	}

	if($pdf) {
		$imgdir = make_upload_directory("exastud/temp/userpic/{$studentid}");

		$fs = get_file_storage();
		$context = $DB->get_record("context",array("contextlevel"=>30,"instanceid"=>$studentid));
		$files = $fs->get_area_files($context->id, 'user', 'icon', 0, '', false);
		$file = reset($files);
		unset($files);
		//copy file
		if($file) {
			$newfile=$imgdir."/".$file->get_filename();
			$file->copy_content_to($newfile);
		}

		require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');
		try
		{
			// create new PDF document
			$pdf = new TCPDF("P", "pt", "A4", true, 'UTF-8', false);
			$pdf->SetTitle('Bericht');
			$pdf->AddPage();
			if($file) $pdf->Image($newfile,480,185, 75, 75);
			$pdf->writeHTML($studentreport, true, false, true, false, '');

			$pdf->Output('Student Review.pdf', 'I');
			unlink($newfile);
		}
		catch(tcpdf_exception $e) {
			echo $e;
			exit;
		}
	}
	else
		echo $studentreport;
}

function block_exastud_init_js_css(){
	global $PAGE, $CFG;

	// only allowed to be called once
	static $js_inited = false;
	if ($js_inited) return;
	$js_inited = true;

	// js/css for whole block
	$PAGE->requires->css('/blocks/exastud/css/styles.css');
	$PAGE->requires->jquery();
	$PAGE->requires->jquery_plugin('ui');
	$PAGE->requires->js('/blocks/exastud/javascript/exastud.js', true);

	// page specific js/css
	$scriptName = preg_replace('!\.[^\.]+$!', '', basename($_SERVER['PHP_SELF']));
	if (file_exists($CFG->dirroot.'/blocks/exastud/css/'.$scriptName.'.css'))
		$PAGE->requires->css('/blocks/exastud/css/'.$scriptName.'.css');
	if (file_exists($CFG->dirroot.'/blocks/exastud/javascript/'.$scriptName.'.js'))
		$PAGE->requires->js('/blocks/exastud/javascript/'.$scriptName.'.js', true);
}

function block_exastud_get_category($categoryid,$categorysource) {
	global $DB;
	switch ($categorysource) {
		case 'exastud':
			$category = $DB->get_record('block_exastudcate',array("id"=>$categoryid));
			if (!$category)
				return null;

			$category->source = 'exastud';

			return $category;
		case 'exacomp':
			if(\block_exastud\is_exacomp_installed()) {
				$category = $DB->get_record('block_exacomptopics',array("id"=>$categoryid));
				if (!$category)
					return null;

				$category->source = 'exacomp';

				return $category;
			} else {
				return null;
			}
	}
	return null;
}
function block_exastud_insert_default_entries() {
	global $DB;

	//if empty import
	if(!$DB->get_records('block_exastudcate')) {
		$DB->insert_record('block_exastudcate', array("sorting" => 1, "title"=>\block_exastud\get_string('teamplayer')));
		$DB->insert_record('block_exastudcate', array("sorting" => 2, "title"=>\block_exastud\get_string('responsibility')));
		$DB->insert_record('block_exastudcate', array("sorting" => 3, "title"=>\block_exastud\get_string('selfreliance', 'block_exastud')));
	}
	
	if(!$DB->get_records('block_exastudsubjects')) {
		$DB->insert_record('block_exastudsubjects', array("title"=>\block_exastud\trans('de:Deutsch')));
		$DB->insert_record('block_exastudsubjects', array("title"=>\block_exastud\trans('de:Englisch')));
		$DB->insert_record('block_exastudsubjects', array("title"=>\block_exastud\trans('de:Mathematik')));
	}
	
	if(!$DB->get_records('block_exastudevalopt')) {
		for ($i=1; $i<=10; $i++) {
			if (!get_string_manager()->string_exists('evaluation'.$i, 'block_exastud'))
				break;
			$DB->insert_record('block_exastudevalopt', array("sorting" => $i, "title"=>get_string('evaluation'.$i, 'block_exastud')));
		}
	}
}

function block_exastud_get_class_categories($classid) {
	global $DB;
	$classcategories = $DB->get_records('block_exastudclasscate', array("classid"=>$classid));
	
	if(!$classcategories) {
		//if empty insert default categories
		block_exastud_insert_default_entries();
		
		foreach ($DB->get_records('block_exastudcate', null, 'sorting, id') as $defaultCategory) {
			$DB->insert_record('block_exastudclasscate', array("classid"=>$classid,"categoryid"=>$defaultCategory->id,"categorysource"=>"exastud"));
		}
	}
	
	$classcategories = $DB->get_records_sql("
		SELECT classcate.*
		FROM {block_exastudclasscate} classcate
		LEFT JOIN {block_exastudcate} cate ON classcate.categorysource='exastud' AND classcate.categoryid=cate.id
		WHERE classcate.classid = ?
		ORDER BY cate.id IS NULL, cate.sorting, classcate.id
	", array($classid));
	
	
	$categories = array();
	foreach($classcategories as $category) {
		if ($tmp = block_exastud_get_category($category->categoryid, $category->categorysource))
			$categories[] = $tmp;
	}
	return $categories;
}

function block_exastud_get_evaluation_options($also_empty = false) {
	global $DB;
	
	$options = $also_empty ? array(
		0 => \block_exastud\trans('nicht gewählt') // empty option
	) : array();
	
	$options += $DB->get_records_menu('block_exastudevalopt');
	
	return $options;
}

/**
 * @return stored_file
 */
function block_exastud_get_main_logo() {
	$fs = get_file_storage();

	$areafiles = $fs->get_area_files(context_system::instance()->id, 'block_exastud', 'main_logo', 0, 'itemid', false);
	return empty($areafiles) ? null : reset($areafiles);
}
function block_exastud_get_main_logo_url() {
	if (!$file = block_exastud_get_main_logo()) {
		return null;
	}

	return moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), null, null, null);
}

}
