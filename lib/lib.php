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

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot.'/cohort/lib.php';
require_once __DIR__.'/../block_exastud.php';
require_once __DIR__.'/common.php';
require_once __DIR__.'/classes.php';

use block_exastud\globals as g;

const BLOCK_EXASTUD_CAP_HEAD_TEACHER = 'head_teacher';
// const BLOCK_EXASTUD_CAP_SUBJECT_TEACHER = 'subject_teacher';

const BLOCK_EXASTUD_CAP_USE = 'use';
const BLOCK_EXASTUD_CAP_EDIT_PERIODS = 'editperiods';
const BLOCK_EXASTUD_CAP_UPLOAD_PICTURE = 'exastud:uploadpicture';
const BLOCK_EXASTUD_CAP_ADMIN = 'admin';
const BLOCK_EXASTUD_CAP_MANAGE_CLASSES = 'createclass';
const BLOCK_EXASTUD_CAP_VIEW_REPORT = 'viewreport';
const BLOCK_EXASTUD_CAP_REVIEW = 'review';

const BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN = 'learning_and_social_behavior';
const BLOCK_EXASTUD_DATA_ID_UNLOCKED_TEACHERS = 'unlocked_teachers';
const BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE = 'print_template';
const BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID = 'default_templateid';
const BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER = 'project_teacher';

const BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN = -1;
const BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN_VORSCHLAG = -3;
const BLOCK_EXASTUD_SUBJECT_ID_OTHER_DATA = -1;
const BLOCK_EXASTUD_SUBJECT_ID_ADDITIONAL_HEAD_TEACHER = -2;

class block_exastud_permission_exception extends moodle_exception {
	function __construct($errorcode = 'Not allowed', $module = '', $link = '', $a = null, $debuginfo = null) {
		return parent::__construct($errorcode, $module, $link, $a, $debuginfo);
	}
}

/**
 * Returns a localized string.
 * This method is neccessary because a project based evaluation is available in the current exastud
 * version, which requires a different naming.
 */
function block_exastud_get_string($identifier, $component = null, $a = null, $lazyload = false) {
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

function block_exastud_get_string_if_exists($identifier, $component = null, $a = null) {
	$manager = get_string_manager();

	if ($component === null) {
		$component = 'block_exastud';
	}

	if ($manager->string_exists($identifier, $component)) {
		return $manager->get_string($identifier, $component, $a);
	}

	return null;
}

function block_exastud_is_head_teacher($userid = null) {
	$cohort = block_exastud_get_head_teacher_cohort();

	return cohort_is_member($cohort->id, $userid ? $userid : g::$USER->id);
}

function block_exastud_get_head_teacher_cohort() {
	// get or create cohort if not exists
	$cohort = g::$DB->get_record('cohort', ['contextid' => \context_system::instance()->id, 'idnumber' => 'block_exastud_head_teachers']);

	$name = block_exastud_get_string('head_teachers');
	$description = block_exastud_trans('de:Können Klassen anlegen, Lehrkräfte und Schüler/innen zubuchen und den Lernentwicklungsbericht abrufen');

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

function block_exastud_get_head_teacher_classes_owner($periodid) {
	if (!block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
		return [];
	}

	return g::$DB->get_records_sql("
			SELECT c.*,
				'normal' AS type
			FROM {block_exastudclass} c
			WHERE c.userid=? AND c.periodid=?
			ORDER BY c.title", [g::$USER->id, $periodid]);
}

function block_exastud_get_head_teacher_classes_shared($periodid) {
	/*
	if (!block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
		return [];
	}
	*/

	$classes = g::$DB->get_records_sql("
			SELECT c.*,
				'shared' AS type,
				".\user_picture::fields('u', null, 'teacher_owner_id', 'teacher_owner_')."
			FROM {block_exastudclass} c
			JOIN {block_exastudclassteachers} ct ON ct.classid=c.id
			JOIN {user} u ON c.userid = u.id
			WHERE ct.subjectid=".BLOCK_EXASTUD_SUBJECT_ID_ADDITIONAL_HEAD_TEACHER." AND ct.teacherid=? AND c.periodid=?
			ORDER BY c.title", [g::$USER->id, $periodid]);

	/*
	foreach ($classes as $class) {
		$class->title_full = fullname(filter_fields_by_prefix($class, 'teacher_owner_')).': '.$class->title;
	}
	*/

	return $classes;
}

function block_exastud_get_head_teacher_classes_all($periodid) {
	return block_exastud_get_head_teacher_classes_owner($periodid) + block_exastud_get_head_teacher_classes_shared($periodid);
}

function block_exastud_get_head_teacher_class($classid) {
	$periods = g::$DB->get_records_sql('SELECT * FROM {block_exastudperiod}');

	foreach ($periods as $period) {
		$classes = block_exastud_get_head_teacher_classes_all($period->id);

		if (isset($classes[$classid])) {
			return $classes[$classid];
		}
	}
	// only for Admin access.
    if (block_exastud_is_siteadmin()) {
        $classes = block_exastud_get_classes_all();
        if (isset($classes[$classid])) {
            return $classes[$classid];
        }
    }

	throw new moodle_exception('class ('.$classid.') not found');
}

// get ALL classes
function block_exastud_get_classes_all($sortByPeriod = false) {
    return g::$DB->get_records_sql("
			SELECT c.*,
				'normal' AS type
			FROM {block_exastudclass} c
            LEFT JOIN {block_exastudperiod} p ON p.id = c.periodid
			ORDER BY ".($sortByPeriod ? "p.description, p.id, " : "")." c.title");
}

function block_exastud_get_class_students($classid) {
	return g::$DB->get_records_sql("
			SELECT u.id, cs.id AS record_id, ".\user_picture::fields('u', null, 'userid')."
			FROM {user} u
			JOIN {block_exastudclassstudents} cs ON u.id=cs.studentid
			WHERE cs.classid=?
			ORDER BY u.lastname, u.firstname
		", [$classid]);
}

function block_exastud_get_class_teachers($classid) {
	return array_merge(block_exastud_get_class_additional_head_teachers($classid), block_exastud_get_class_subject_teachers($classid));
}

function block_exastud_get_class_subjects($class) {
	$subjects = block_exastud_get_bildungsplan_subjects($class->bpid);
	$teachers = block_exastud_get_class_subject_teachers($class->id);

	return array_filter($subjects, function($subject) use ($teachers) {
		return block_exastud_find_object_in_array_by_property($teachers, 'subjectid', $subject->id);
	});
}

function block_exastud_get_class_subject_teachers($classid) {
	return iterator_to_array(g::$DB->get_recordset_sql("
			SELECT u.id, ct.id AS record_id, ".\user_picture::fields('u', null, 'userid').", ct.subjectid, s.title AS subject_title
			FROM {user} u
			JOIN {block_exastudclassteachers} ct ON ct.teacherid=u.id
			JOIN {block_exastudclass} c ON c.id=ct.classid
			JOIN {block_exastudsubjects} s ON ct.subjectid = s.id AND s.bpid=c.bpid
			WHERE c.id=?
			ORDER BY s.sorting, u.lastname, u.firstname, s.id
		", [$classid]), false);
}

function block_exastud_get_class_additional_head_teachers($classid) {
	$classteachers = g::$DB->get_records_sql("
			SELECT u.*, ct.id AS record_id, ct.subjectid
			FROM {user} u
			JOIN {block_exastudclassteachers} ct ON ct.teacherid=u.id
			JOIN {block_exastudclass} c ON c.id=ct.classid
			WHERE c.id=? AND ct.subjectid=?
			AND c.userid<>u.id
			ORDER BY u.lastname, u.firstname
		", [$classid, BLOCK_EXASTUD_SUBJECT_ID_ADDITIONAL_HEAD_TEACHER]);

	foreach ($classteachers as $classteacher) {
		$classteacher->subject_title = block_exastud_get_string('additional_head_teacher');
	}

	return $classteachers;
}

function block_exastud_get_head_teacher_lern_und_sozialverhalten_classes() {
	$classes = block_exastud_get_head_teacher_classes_all(block_exastud_get_active_or_next_period()->id);

	$ret = [];
	foreach ($classes as $class) {
		if (!block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_VIEW_REPORT, $class->userid)) {
			continue;
		}

		$ret[$class->id] = (object)[
			'classid' => $class->id,
			'subjectid' => BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN,
			'userid' => $class->userid,
			'title' => $class->title,
			'subject' => block_exastud_trans('de:Lern- und Sozialverhalten'),
			'type' => $class->type,
		];
	}

	return $ret;
}

/**
 * this returns all review classes, can have multiple class entries if teacher has more than 1 subject
 * @return array
 */
function block_exastud_get_review_subjects($periodid) {
	return g::$DB->get_records_sql("
			SELECT ct.id, ct.subjectid, ct.classid, c.title, s.title AS subject_title
			FROM {block_exastudclassteachers} ct
			JOIN {block_exastudclass} c ON ct.classid=c.id
			JOIN {block_exastudsubjects} s ON ct.subjectid = s.id AND s.bpid=c.bpid 
			WHERE ct.teacherid=? AND c.periodid=? AND ct.subjectid >= 0
			ORDER BY c.title, s.sorting
		", array(g::$USER->id, $periodid));
}

function block_exastud_get_review_class($classid, $subjectid) {
	global $DB, $USER;

	if ($subjectid == BLOCK_EXASTUD_SUBJECT_ID_LERN_UND_SOZIALVERHALTEN) {
		$classes = block_exastud_get_head_teacher_lern_und_sozialverhalten_classes();

		return isset($classes[$classid]) ? $classes[$classid] : null;
	} else {
		return $DB->get_record_sql("
			SELECT ct.id, ct.id AS classteacherid, c.title, s.title AS subject_title, s.id as subject_id, c.userid
			FROM {block_exastudclassteachers} ct
			JOIN {block_exastudclass} c ON ct.classid=c.id
			LEFT JOIN {block_exastudsubjects} s ON ct.subjectid = s.id
			WHERE ct.teacherid=? AND ct.classid=? AND ct.subjectid >= 0 AND ".($subjectid ? 's.id=?' : 's.id IS NULL')."
		", array($USER->id, $classid, $subjectid), IGNORE_MULTIPLE);
	}
}

/**
 * wrote own function, so eclipse knows which type the output renderer is
 * @return \block_exastud_renderer
 */
function block_exastud_get_renderer() {
	return g::$PAGE->get_renderer('block_exastud');
}

/*
function block_exastud_filter_fields_by_prefix($object_or_array, $prefix) {
	$ret = [];
	foreach ($object_or_array as $key => $value) {
		if (strpos($key, $prefix) === 0) {
			$ret[substr($key, strlen($prefix))] = $value;
		}
	}

	if (is_object($object_or_array)) {
		$ret = (object)$ret;
	}

	return $ret;
}
*/

function block_exastud_get_reviewers_by_category_and_pos($periodid, $studentid, $categoryid, $categorysource, $pos_value) {
	return iterator_to_array(g::$DB->get_recordset_sql("
			SELECT DISTINCT u.*, s.title AS subject_title, pos.value
			FROM {block_exastudreview} r
			JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
			JOIN {user} u ON r.teacherid = u.id
			JOIN {block_exastudclass} c ON c.periodid = r.periodid
			JOIN {block_exastudclassteachers} ct ON ct.classid=c.id AND ct.teacherid=r.teacherid AND ct.subjectid=r.subjectid
			LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
			WHERE c.periodid = ? AND r.studentid = ?
				AND pos.categoryid = ? AND pos.categorysource = ?
			".($pos_value !== null ? "AND pos.value = ?" : "AND pos.value > 0")."
			-- GROUP BY r.teacherid, s.id, pos.value
		", [$periodid, $studentid, $categoryid, $categorysource, $pos_value]), false);
}

function block_exastud_get_class_categories_for_report($studentid, $classid) {
	$evaluationOtions = block_exastud_get_evaluation_options();
	$categories = block_exastud_get_class_categories($classid);

	$current_parent = null;
	foreach ($categories as $category) {

		$category->fulltitle = $category->title;
		if (preg_match('!^([^:]*):\s*([^\s].*)$!', $category->fulltitle, $matches)) {
			$category->parent = $matches[1];
			$category->title = $matches[2];
		} else {
			$category->parent = '';
			$category->title = $category->fulltitle;
		}

		$category->evaluationOtions = [];
		$reviewPoints = 0;
		$reviewCnt = 0;
		$i = 0;

		foreach ($evaluationOtions as $pos_value => $option) {

			$category->evaluationOtions[$pos_value] = (object)[
				'value' => $pos_value,
				'title' => $option,
				'reviewers' => $reviewers = block_exastud_get_reviewers_by_category_and_pos(block_exastud_get_active_or_last_period()->id, $studentid, $category->id, $category->source, $pos_value),
			];
			$reviewPoints += count($reviewers) * $i;
			$reviewCnt += count($reviewers);
			$i++;
		}

		if ($reviewCnt) {
			$category->average = $reviewPoints / $reviewCnt;
		} else {
			$category->average = null;
		}
	}


	return $categories;
}

function block_exastud_get_custom_profile_field_value($userid, $fieldname) {
	return g::$DB->get_field_sql("SELECT uid.data
			FROM {user_info_data} uid
			JOIN {user_info_field} uif ON uif.id=uid.fieldid
			WHERE uif.shortname=? AND uid.userid=?
			", [$fieldname, $userid]);
}

function block_exastud_is_exacomp_installed() {
	return class_exists('\block_exacomp\api') && \block_exacomp\api::active();
}

function block_exastud_get_class_data($classid, $name = null) {
	return block_exastud_get_subject_student_data($classid, 0, 0, $name);
}

function block_exastud_get_class_student_data($classid, $userid, $name = null) {
	return block_exastud_get_subject_student_data($classid, 0, $userid, $name);
}

function block_exastud_get_subject_student_data($classid, $subjectid, $userid, $name = null) {
	$data = (object)g::$DB->get_records_menu('block_exastuddata', [
		'classid' => $classid,
		'studentid' => $userid,
		'subjectid' => $subjectid,
	], 'name', 'name, value');

	if ($name) {
		return @$data->$name;
	} else {
		return $data;
	}
}

function block_exastud_set_class_data($classid, $name, $value) {
	return block_exastud_set_subject_student_data($classid, 0, 0, $name, $value);
}

function block_exastud_set_class_student_data($classid, $userid, $name, $value) {
    return block_exastud_set_subject_student_data($classid, 0, $userid, $name, $value);
}

function block_exastud_set_subject_student_data($classid, $subjectid, $userid, $name, $value) {
	$conditions = [
		'classid' => $classid,
		'studentid' => $userid,
		'subjectid' => $subjectid,
		'name' => $name,
	];
    $olddata = block_exastud_get_subject_student_data($classid, $subjectid, $userid, $name);
	if ($value === null) {
		g::$DB->delete_records('block_exastuddata', $conditions);
	} else {
		g::$DB->insert_or_update_record('block_exastuddata', [
			'value' => $value,
		], $conditions);
	}
	// logging
    // add to log only if data was changed
    if ($olddata != $value) {
	    // not for time data
        $strpos_arr = function ($haystack, $needle) {
            if(!is_array($needle)) $needle = array($needle);
            foreach($needle as $what) {
                if(($pos = strpos($haystack, $what))!==false) return $pos;
            }
            return false;
        };
        if ($strpos_arr($name, ['.timemodified', '.modifiedby', '_time']) === false && !in_array($name, ['review'])) { // 'review' works in studentreview_changed event
            $classData = block_exastud_get_class($classid);
            $userData = g::$DB->get_record('user', ['id' => $userid]);
            $subjectData = g::$DB->get_record('block_exastudsubjects', ['id' => $subjectid]);
            if ($subjectid === 0 && $userid === 0) { // classes data
                \block_exastud\event\classdata_changed::log(['objectid' => $classid,
                        'other' => ['name' => $name,
                                    'value' => $value,
                                    'classtitle' => $classData->title,
                                    'oldvalue' => $olddata]]);
            } else if ($subjectid === 0) { // student's data
                \block_exastud\event\studentdata_changed::log(['objectid' => $classid, 'relateduserid' => $userid,
                        'other' => ['name' => $name,
                                    'value' => $value,
                                    'classtitle' => $classData->title,
                                    'relatedusername' => $userData->firstname.' '.$userData->lastname]]);
            } else {
                \block_exastud\event\subjectstudentdata_changed::log(['objectid' => $classid, 'relateduserid' => $userid,
                        'other' => ['name' => $name,
                                    'value' => $value,
                                    'subjectid' => $subjectid,
                                    'subjecttitle' => $subjectData->title,
                                    'classtitle' => $classData->title,
                                    'relatedusername' => $userData->firstname.' '.$userData->lastname]]);
            }
        }
    }
}

function block_exastud_check_profile_fields() {

	$categoryid = g::$DB->get_field_sql("SELECT id FROM {user_info_category} ORDER BY sortorder LIMIT 1");
	if (!$categoryid) {
		$categoryid = g::$DB->insert_record('user_info_category', [
			'name' => block_exastud_get_string('profiledefaultcategory', 'admin'),
			'sortorder' => 1,
		]);
	}

	$sortorder = g::$DB->get_field_sql('SELECT MAX(sortorder) FROM {user_info_field} WHERE categoryid=?', [$categoryid]);

	$fields = [
		[
			'shortname' => 'dateofbirth',
			'name' => block_exastud_trans('de:Geburtsdatum'),
			'description' => '',
			'datatype' => 'text',
			'categoryid' => $categoryid,
			'locked' => 1,
			'required' => 0,
			'visible' => 0,
			'param1' => 30,
			'param2' => 2048,
			'param3' => 0,
		], [
			'shortname' => 'placeofbirth',
			'name' => block_exastud_trans('de:Geburtsort'),
			'description' => '',
			'datatype' => 'text',
			'categoryid' => $categoryid,
			'locked' => 1,
			'required' => 0,
			'visible' => 0,
			'param1' => 30,
			'param2' => 2048,
			'param3' => 0,
		], [
			'shortname' => 'gender',
			'name' => block_exastud_trans('de:Geschlecht'),
			'description' => '',
			'datatype' => 'menu',
			'categoryid' => $categoryid,
			'locked' => 1,
			'required' => 0,
			'visible' => 0,
			// TODO: english male / famle auch berücksichtigen.
			// => die moodle default sprach einstellung hernehmen.
			'param1' => "\nmännlich\nweiblich",
		],
	];

	foreach ($fields as $field) {
		$id = g::$DB->get_field('user_info_field', 'id', ['shortname' => $field['shortname']]);
		if ($id) {
			// don't update those:
			unset($field['name']);
			unset($field['description']);

			g::$DB->update_record('user_info_field', $field, ['id' => $id]);
		} else {
			$sortorder++;
			$field['sortorder'] = $sortorder;
			g::$DB->insert_record('user_info_field', $field);
		}
	}
}

function block_exastud_find_object_in_array_by_property($array, $key, $value) {
	if (!$array) {
		return null;
	}

	foreach ($array as $item) {
		if ($item->$key == $value) {
			return $item;
		}
	}

	return null;
}

function block_exastud_insert_default_entries($dorecheck = false) {

	//if empty import
	$categories = g::$DB->get_records('block_exastudcate', null, 'sorting', 'id, title, sourceinfo');
	$defaultItems = (array)block_exastud_get_plugin_config('default_categories');

	if (!$categories || $dorecheck || block_exastud_get_plugin_config('always_check_default_values')) {
		$sorting = 1;
		foreach ($defaultItems as $defaultItem) {
			$defaultItem = (object)$defaultItem;

			if ($dbItem = block_exastud_find_object_in_array_by_property($categories, 'sourceinfo', $defaultItem->sourceinfo)) {
				g::$DB->update_record('block_exastudcate', [
					'id' => $dbItem->id,
					'sorting' => $sorting,
				]);
				unset($categories[$dbItem->id]);
			} elseif ($dbItem = block_exastud_find_object_in_array_by_property($categories, 'title', $defaultItem->title)) {
				g::$DB->update_record('block_exastudcate', [
					'id' => $dbItem->id,
					'sourceinfo' => $defaultItem->sourceinfo,
					'sorting' => $sorting,
				]);
				unset($categories[$dbItem->id]);
			} else {
				g::$DB->insert_record('block_exastudcate', [
					'sourceinfo' => $defaultItem->sourceinfo,
					'title' => $defaultItem->title,
					'sorting' => $sorting,
				]);
			}

			$sorting++;
		}

		foreach ($categories as $id => $title) {
			g::$DB->update_record('block_exastudcate', ['id' => $id, 'sorting' => $sorting]);
			$sorting++;
		}
	}

	$evalopts = g::$DB->get_records('block_exastudevalopt', null, 'sorting', 'id, title, sourceinfo');
	$defaultItems = (array)block_exastud_get_plugin_config('default_evalopt');

	if ($defaultItems && (!$evalopts || $dorecheck || block_exastud_get_plugin_config('always_check_default_values'))) {
		$sorting = 1;
		foreach ($defaultItems as $defaultItem) {
			$defaultItem = (object)$defaultItem;

			if ($dbItem = block_exastud_find_object_in_array_by_property($evalopts, 'sourceinfo', $defaultItem->sourceinfo)) {
				g::$DB->update_record('block_exastudevalopt', [
					'id' => $dbItem->id,
					'sorting' => $sorting,
				]);
				unset($evalopts[$dbItem->id]);
			} elseif ($dbItem = block_exastud_find_object_in_array_by_property($evalopts, 'title', $defaultItem->title)) {
				g::$DB->update_record('block_exastudevalopt', [
					'id' => $dbItem->id,
					'sourceinfo' => $defaultItem->sourceinfo,
					'sorting' => $sorting,
				]);
				unset($evalopts[$dbItem->id]);
			} else {
				g::$DB->insert_record('block_exastudevalopt', [
					'sourceinfo' => $defaultItem->sourceinfo,
					'title' => $defaultItem->title,
					'sorting' => $sorting,
				]);
			}

			$sorting++;
		}

		foreach ($evalopts as $id => $title) {
			g::$DB->update_record('block_exastudevalopt', ['id' => $id, 'sorting' => $sorting]);
			$sorting++;
		}
	} elseif (!$evalopts) {
		for ($i = 1; $i <= 10; $i++) {
			if (!get_string_manager()->string_exists('evaluation'.$i)) {
				break;
			}
			g::$DB->insert_record('block_exastudevalopt', [
				'sourceinfo' => 'default-from-lang-'.$i,
				"sorting" => $i,
				"title" => block_exastud_get_string('evaluation'.$i),
			]);
		}
	}

	$bps = g::$DB->get_records('block_exastudbp', null, 'sorting', 'id, title, sourceinfo');
	$defaultBps = (array)block_exastud_get_plugin_config('default_bps');

	if (!$bps || $dorecheck || block_exastud_get_plugin_config('always_check_default_values')) {
		$sorting = 1;
		foreach ($defaultBps as $defaultBp) {
			$defaultBp = (object)$defaultBp;

			if ($dbBp = block_exastud_find_object_in_array_by_property($bps, 'sourceinfo', $defaultBp->sourceinfo)) {
				g::$DB->update_record('block_exastudbp', [
					'id' => $dbBp->id,
					'sorting' => $sorting,
				]);
				unset($bps[$dbBp->id]);
			} elseif ($dbBp = block_exastud_find_object_in_array_by_property($bps, 'title', $defaultBp->title)) {
				g::$DB->update_record('block_exastudbp', [
					'id' => $dbBp->id,
					'sourceinfo' => $defaultBp->sourceinfo,
					'sorting' => $sorting,
				]);
				unset($bps[$dbBp->id]);
			} else {
				$dbBp = $defaultBp;
				$dbBp->sorting = $sorting;
				$dbBp->id = g::$DB->insert_record('block_exastudbp', $dbBp);
			}
			$sorting++;

			$subjects = block_exastud_get_bildungsplan_subjects($dbBp->id);
			$subjectSorting = 1;

			foreach ($defaultBp->subjects as $subject) {
				$subject = (object)$subject;
				$subject->sorting = $subjectSorting;
				$subjectSorting++;

				if ($dbSubject = block_exastud_find_object_in_array_by_property($subjects, 'sourceinfo', $subject->sourceinfo)) {
					g::$DB->update_record('block_exastudsubjects', [
						'id' => $dbSubject->id,
						'sorting' => $subjectSorting,
					]);
					unset($subjects[$dbSubject->id]);
				} elseif ($dbSubject = block_exastud_find_object_in_array_by_property($subjects, 'title', $subject->title)) {
					g::$DB->update_record('block_exastudsubjects', [
						'id' => $dbSubject->id,
						'sourceinfo' => $subject->sourceinfo,
						'sorting' => $subjectSorting,
					]);
					unset($subjects[$dbSubject->id]);
				} else {
					$subject->bpid = $dbBp->id;
					g::$DB->insert_record('block_exastudsubjects', $subject);
				}
			}

			foreach ($subjects as $id => $title) {
				g::$DB->update_record('block_exastudsubjects', ['id' => $id, 'sorting' => $subjectSorting]);
				$subjectSorting++;
			}
		}

		foreach ($bps as $id => $tmp) {
			g::$DB->update_record('block_exastudbp', ['id' => $id, 'sorting' => $sorting]);
			$sorting++;
		}
	}
}

function block_exastud_get_plugin_config($name = null) {
	static $config = null;

	if ($config === null) {
		$config = [];

		if (file_exists(__DIR__.'/../local.config/config.php')) {
			$config += require __DIR__.'/../local.config/config.php';
		}

		$config += (array)get_config('exastud');

		$config = (object)$config;
	}

	if (!empty($name)) {
		if (array_key_exists($name, $config)) {
			return $config->$name;
		} else {
			return null;
		}
	} else {
		return $config;
	}
}

function block_exastud_is_new_version() {
	return true;
}

function block_exastud_has_global_cap($cap, $user = null) {
	try {
		block_exastud_require_global_cap($cap, $user);

		return true;
	} catch (block_exastud_permission_exception $e) {
		return false;
	} catch (required_capability_exception $e) {
		return false;
	}
}

function block_exastud_require_global_cap($cap, $user = null) {
	// all capabilities require use
	require_capability('block/exastud:use', context_system::instance(), $user);

	switch ($cap) {
		case BLOCK_EXASTUD_CAP_EDIT_PERIODS:
		case BLOCK_EXASTUD_CAP_UPLOAD_PICTURE:
			require_capability('block/exastud:admin', context_system::instance(), $user);

			return;

		case BLOCK_EXASTUD_CAP_MANAGE_CLASSES:
		case BLOCK_EXASTUD_CAP_HEAD_TEACHER:
		case BLOCK_EXASTUD_CAP_VIEW_REPORT:
			if (!block_exastud_is_head_teacher($user)) {
				throw new block_exastud_permission_exception('no headteacher');
			} else {
				return;
			}
		case BLOCK_EXASTUD_CAP_REVIEW:
			$actPeriod = block_exastud_get_active_period();
			$lastPeriod = block_exastud_get_last_period();
			if (($actPeriod && (block_exastud_get_review_subjects($actPeriod->id) || block_exastud_get_head_teacher_classes_all($actPeriod->id)))
				|| ($lastPeriod && (block_exastud_get_review_subjects($lastPeriod->id) || block_exastud_get_head_teacher_classes_all($lastPeriod->id)))
			) {
				// has reviews in this or last period (=a class is unlocked for late review)
				return;
			} else {
				throw new block_exastud_permission_exception('no classes');
			}
	}

	require_capability('block/exastud:'.$cap, context_system::instance(), $user);
}

function block_exastud_check_periods($printBoxInsteadOfError = false) {
	block_exastud_has_wrong_periods($printBoxInsteadOfError);
	block_exastud_check_if_periods_overlap($printBoxInsteadOfError);
}

/*
function block_exastud_get_review_periods($studentid) {
	global $DB;
	return $DB->get_records_sql('SELECT periods_id FROM {block_exastudreview} r
			WHERE student_id = ? GROUP BY periods_id',array($studentid));
}
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

	if (!empty($CFG->block_exastud_project_based_assessment)) {
		// lehrer classteacher und classstudents in period a review
		$availablereviews = $DB->get_records_sql('SELECT r.id FROM {block_exastudreview} r
		WHERE r.studentid IN
		(
		SELECT cs.studentid FROM {block_exastudclassteachers} ct, {block_exastudclassstudents} cs
		WHERE ct.teacherid = ? AND ct.classid = cs.classid
		)', array($USER->id));
	}

	return (bool)$availablereviews;
}
*/

function block_exastud_has_wrong_periods($printBoxInsteadOfError = false) {
	global $DB;
	// check if any entry has a starttime after the endtime:
	$wrongs = $DB->get_records_sql('SELECT p.description, p.starttime, p.endtime FROM {block_exastudperiod} p WHERE starttime > endtime');

	if ($wrongs) {
		foreach ($wrongs as $wrong) {
			if ($printBoxInsteadOfError) {
			    g::$OUTPUT->notification(block_exastud_get_string('errorstarttimebeforeendtime', null, $wrong));
			} else {
				error('errorstarttimebeforeendtime', 'block_exastud', '', $wrong);
			}
		}
	}

	return true;
}

function block_exastud_check_if_periods_overlap($printBoxInsteadOfError = false) {
	global $DB;
	$allPeriods = $DB->get_records('block_exastudperiod', null, 'id, description, starttime, endtime');

	$periodshistory = '';
	foreach ($allPeriods as $actPeriod) {
		if ($periodshistory == '') {
			$periodshistory .= $actPeriod->id;
		} else {
			$periodshistory .= ', '.$actPeriod->id;
		}
		$ovelapPeriods = $DB->get_records_sql('SELECT id, description, starttime, endtime FROM {block_exastudperiod}
				WHERE (id NOT IN ('.$periodshistory.')) AND NOT ( (starttime < '.$actPeriod->starttime.' AND endtime < '.$actPeriod->starttime.')
				OR (starttime > '.$actPeriod->endtime.' AND endtime > '.$actPeriod->endtime.') )');

		if ($ovelapPeriods) {
			foreach ($ovelapPeriods as $overlapPeriod) {
				$a = new stdClass();
				$a->period1 = $actPeriod->description;
				$a->period2 = $overlapPeriod->description;

				if ($printBoxInsteadOfError) {
				    g::$OUTPUT->notification(block_exastud_get_string('periodoverlaps', null, $a));
				} else {
					print_error('periodoverlaps', 'block_exastud', '', $a);
				}
			}
		}
	}
}

function block_exastud_check_active_period() {
	global $CFG, $COURSE;

	if ($period = block_exastud_get_active_period()) {
		return $period;
	}

	if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_EDIT_PERIODS)) {
		redirect($CFG->wwwroot.'/blocks/exastud/configuration_periods.php?courseid='.$COURSE->id, block_exastud_get_string('redirectingtoperiodsinput'));
	}

	throw new \moodle_exception('periodserror', 'block_exastud', $CFG->wwwroot.'/blocks/exastud/configuration_periods.php?courseid='.$COURSE->id);
}

function block_exastud_get_active_period() {
	$periods = g::$DB->get_records_sql('SELECT * FROM {block_exastudperiod} WHERE (starttime <= '.time().') AND (endtime >= '.time().')');

	// genau 1e periode?
	if (count($periods) == 1) {
		return reset($periods);
	} else {
		return null;
	}
}

function block_exastud_get_active_or_next_period() {
	return g::$DB->get_record_sql('SELECT * FROM {block_exastudperiod} WHERE (endtime >= '.time().') ORDER BY starttime ASC LIMIT 1');
}

function block_exastud_get_active_or_last_period() {
	return g::$DB->get_record_sql('SELECT * FROM {block_exastudperiod} WHERE (starttime <= '.time().') ORDER BY endtime DESC LIMIT 1');
}

function block_exastud_get_last_period() {
	return g::$DB->get_record_sql('SELECT * FROM {block_exastudperiod} WHERE (endtime <= '.time().') ORDER BY starttime DESC LIMIT 1');
}

function block_exastud_get_period($periodid, $loadActive = true) {
	if ($periodid) {
		return g::$DB->get_record('block_exastudperiod', array('id' => $periodid));
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

	$reviewcategories = $DB->get_recordset_sql('SELECT rp.categoryid, rp.categorysource FROM {block_exastudreviewpos} rp, {block_exastudreview} r WHERE r.periodid=? AND rp.reviewid=r.id GROUP BY rp.categoryid, rp.categorysource', array($periodid));

	$categories = array();
	foreach ($reviewcategories as $reviewcategory) {
		if ($tmp = block_exastud_get_category($reviewcategory->categoryid, $reviewcategory->categorysource)) {
			$categories[] = $tmp;
		}
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

	$totalvalue = $DB->get_record_sql('SELECT sum(rp.value) as total FROM {block_exastudreview} r, {block_exastudreviewpos} rp where r.studentid = ? AND r.periodid = ? AND rp.reviewid = r.id', array($studentid, $periodid));
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

			$reviewers = block_exastud_get_reviewers_by_category_and_pos($periodid, $studentid, $rcat->categoryid, $rcat->categorysource, null);
			$category_total = 0;
			$category_cnt = 0;

			foreach ($reviewers as $reviewer) {
				$category_total += $reviewer->value;
				$category_cnt++;
			}
			$average = $category_cnt > 0 ? round($category_total / $category_cnt, 2) : 0;
			$report->category_averages[$category->title] = $average; // wird das noch benötigt?
			$report->category_averages[$catid] = $average;
		}
	}

	$numrecords = $DB->get_record_sql('SELECT COUNT(id) AS count FROM {block_exastudreview} WHERE studentid='.$studentid.' AND periodid='.$periodid);
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
	foreach ($comments as $comment) {
		$newcomment = new stdClass();
		$newcomment->name = ($comment->subject_title ? $comment->subject_title.' ('.fullname($comment).')' : fullname($comment));
		$newcomment->review = format_text($comment->review);

		$report->comments[] = $newcomment;
	}

	return $report;
}

function block_exastud_read_template_file($filename) {
	global $CFG;
	$filecontent = '';

	if (is_file($CFG->dirroot.'/blocks/exastud/template/'.$filename)) {
		$filecontent = file_get_contents($CFG->dirroot.'/blocks/exastud/template/'.$filename);
	} else {
		if (is_file($CFG->dirroot.'/blocks/exastud/default_template/'.$filename)) {
			$filecontent = file_get_contents($CFG->dirroot.'/blocks/exastud/default_template/'.$filename);
		}
	}
	$filecontent = str_replace('###WWWROOT###', $CFG->wwwroot, $filecontent);

	return $filecontent;
}

function block_exastud_print_student_report_header() {
	echo block_exastud_read_template_file('header.html');
}

function block_exastud_print_student_report_footer() {
	echo block_exastud_read_template_file('footer.html');
}

function block_exastud_print_student_report($studentid, $periodid, $class, $pdf = false, $detail = false, $ranking = false) {
	global $DB, $CFG, $OUTPUT;

	$detailedreview = !empty($CFG->block_exastud_detailed_review) && $detail;

	$period = $DB->get_record('block_exastudperiod', array('id' => $periodid));

	if (!$studentReport = block_exastud_get_report($studentid, $periodid)) {
		print_error('studentnotfound', 'block_exastud');
	}


	$student = $DB->get_record('user', array('id' => $studentid));
	$studentreport = block_exastud_read_template_file('student_new.html');
	$studentreport = str_replace('###STUDENTREVIEW###', block_exastud_get_string('studentreview'), $studentreport);
	$studentreport = str_replace('###NAME###', block_exastud_get_string('name'), $studentreport);
	$studentreport = str_replace('###PERIODREVIEW###', block_exastud_get_string('periodreview'), $studentreport);
	$studentreport = str_replace('###REVIEWCOUNT###', block_exastud_get_string('reviewcount'), $studentreport);
	$studentreport = str_replace('###CLASSTRANSLATION###', block_exastud_get_string('class'), $studentreport);
	$studentreport = str_replace('###FIRSTNAME###', $student->firstname, $studentreport);
	$studentreport = str_replace('###LASTNAME###', $student->lastname, $studentreport);
	if (!empty($CFG->block_exastud_project_based_assessment) && $ranking) {
		$studentreport = str_replace('###RANKING###', $ranking, $studentreport);
		$studentreport = str_replace('###RANKINGTRANSLATION###', 'Ranking', $studentreport);
	} else {
		$studentreport = str_replace('<tr>
						<td class="printpersonalinfo_heading">###RANKING###</td>
					</tr>
					<tr>
						<td class="printpersonalinfo_subheading">###RANKINGTRANSLATION###</td>
					</tr>', "", $studentreport);
	}
	if (!$pdf) {
		$studentreport = str_replace('###USERPIC###', $OUTPUT->user_picture($DB->get_record('user', array("id" => $studentid)), array("size" => 100)), $studentreport);
	} else {
		$studentreport = str_replace('###USERPIC###', '', $studentreport);
	}

	if ($logo = block_exastud_get_main_logo_url()) {
		$img = '<img id="logo" width="840" height="100" src="'.$logo.'"/>';
	} else {
		$img = '';
	}
	$studentreport = str_replace('###TITLE###', $img, $studentreport);
	$studentreport = str_replace('###CLASS###', $class->title, $studentreport);
	$studentreport = str_replace('###NUM###', $studentReport->numberOfEvaluations, $studentreport);
	$studentreport = str_replace('###PERIOD###', $period->description, $studentreport);
	$studentreport = str_replace('###LOGO###', $img, $studentreport);

	$categories = ($periodid == block_exastud_check_active_period()->id) ? block_exastud_get_class_categories($class->id) : block_exastud_get_period_categories($periodid);

	$html = '';

	foreach ($categories as $category) {
		$html .= '<tr class="ratings"><td class="ratingfirst text">'.$category->title.'</td>
		<td class="rating legend">'.@$studentReport->{$category->title}.'</td></tr>';

		if ($detailedreview) {
			$detaildata = $DB->get_recordset_sql("SELECT ".user_picture::fields('u').", pos.value, s.title AS subject_title
					FROM 	{block_exastudreview} r
					JOIN {block_exastudreviewpos} pos ON pos.reviewid = r.id
					JOIN {user} u ON r.teacherid = u.id
					LEFT JOIN {block_exastudsubjects} s ON r.subjectid = s.id
					WHERE studentid = ? AND periodid = ? AND pos.categoryid = ? AND pos.categorysource = ?", array($studentid, $periodid, $category->id, $category->source));
			foreach ($detaildata as $detailrow) {
				$html .= '<tr class="ratings"><td class="teacher">'.($detailrow->subject_title ? $detailrow->subject_title.' ('.fullname($detailrow).')' : fullname($detailrow)).'</td>
				<td class="rating legend teacher">'.$detailrow->value.'</td></tr>';
			}
		}
	}
	$studentreport = str_replace('###CATEGORIES###', $html, $studentreport);


	if (!$studentReport->comments) {
		$studentreport = str_replace('###COMMENTS###', '', $studentreport);
	} else {
		$comments = '
		<table class="ratingtable"><tr class="ratingheading"><td><h3>'.block_exastud_get_string('detailedreview').'</h3></td></tr></table>';
		foreach ($studentReport->comments as $comment) {
			$comments .= '<table class="ratingtable">
			<tr class="ratinguser"><td class="ratingfirst">'.$comment->name.'</td></tr>
			<tr class="ratingtext"><td>'.$comment->review.'</td>
			</tr>
			</table>';
		}
		$studentreport = str_replace('###COMMENTS###', $comments, $studentreport);
	}

	if ($pdf) {
		$imgdir = make_upload_directory("exastud/temp/userpic/{$studentid}");

		$fs = get_file_storage();
		$context = $DB->get_record("context", array("contextlevel" => 30, "instanceid" => $studentid));
		$files = $fs->get_area_files($context->id, 'user', 'icon', 0, '', false);
		$file = reset($files);
		unset($files);
		//copy file
		if ($file) {
			$newfile = $imgdir."/".$file->get_filename();
			$file->copy_content_to($newfile);
		}

		require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');
		try {
			// create new PDF document
			$pdf = new TCPDF("P", "pt", "A4", true, 'UTF-8', false);
			$pdf->SetTitle('Bericht');
			$pdf->AddPage();
			if ($file) {
				$pdf->Image($newfile, 480, 185, 75, 75);
			}
			$pdf->writeHTML($studentreport, true, false, true, false, '');

			$pdf->Output('Student Review.pdf', 'I');
			unlink($newfile);
		} catch (tcpdf_exception $e) {
			echo $e;
			exit;
		}
	} else {
		echo $studentreport;
	}
}

function block_exastud_init_js_css() {
	global $PAGE, $CFG;

	// only allowed to be called once
	static $js_inited = false;
	if ($js_inited) {
		return;
	}
	$js_inited = true;

	// js/css for whole block
	$PAGE->requires->css('/blocks/exastud/css/styles.css');
	$PAGE->requires->jquery();
	$PAGE->requires->jquery_plugin('ui');
	$PAGE->requires->js('/blocks/exastud/javascript/common.js', true);
	$PAGE->requires->js('/blocks/exastud/javascript/exastud.js', true);

	// page specific js/css
	$scriptName = preg_replace('!\.[^\.]+$!', '', basename($_SERVER['PHP_SELF']));
	if (file_exists($CFG->dirroot.'/blocks/exastud/css/'.$scriptName.'.css')) {
		$PAGE->requires->css('/blocks/exastud/css/'.$scriptName.'.css');
	}
	if (file_exists($CFG->dirroot.'/blocks/exastud/javascript/'.$scriptName.'.js')) {
		$PAGE->requires->js('/blocks/exastud/javascript/'.$scriptName.'.js', true);
	}
}

function block_exastud_get_category($categoryid, $categorysource) {
	global $DB;
	switch ($categorysource) {
		case 'exastud':
			$category = $DB->get_record('block_exastudcate', array("id" => $categoryid));
			if (!$category) {
				return null;
			}

			$category->source = 'exastud';

			return $category;
		case 'exacomp':
			if (block_exastud_is_exacomp_installed()) {
				$category = $DB->get_record('block_exacomptopics', array("id" => $categoryid));
				if (!$category) {
					return null;
				}

				$category->source = 'exacomp';

				return $category;
			} else {
				return null;
			}
	}

	return null;
}

function block_exastud_get_class_categories($classid) {
	global $DB;
	$classcategories = $DB->get_records('block_exastudclasscate', array("classid" => $classid));

	if (!$classcategories) {
		//if empty insert default categories
		block_exastud_insert_default_entries();

		foreach ($DB->get_records('block_exastudcate', null, 'sorting, id') as $defaultCategory) {
			$DB->insert_record('block_exastudclasscate', array("classid" => $classid, "categoryid" => $defaultCategory->id, "categorysource" => "exastud"));
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
	foreach ($classcategories as $category) {
		if ($tmp = block_exastud_get_category($category->categoryid, $category->categorysource)) {
			$categories[] = $tmp;
		}
	}

	return $categories;
}

function block_exastud_get_evaluation_options($also_empty = false) {
	global $DB;

	$options = $also_empty ? array(
		0 => block_exastud_trans('de:nicht gewählt') // empty option
	) : array();

	$options += $DB->get_records_menu('block_exastudevalopt', [], 'sorting', 'id, title');

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

function block_exastud_str_to_csv($string, $delimiter, $has_header) {
	$string = trim($string, "\r\n");
	$string = rtrim($string);
	$csv = preg_split("!\r?\n!", $string);

	foreach ($csv as &$item) {
		$item = str_getcsv($item, $delimiter);
	}
	unset($item);

	if ($has_header) {
		$header = array_shift($csv);

		foreach ($csv as &$item) {
			$newItem = [];
			foreach ($item as $i => $part) {
				$newItem[$header[$i]] = $part;
			}
			$item = $newItem;
		}
		unset($item);
	}

	return $csv;
}

function block_exastud_html_to_text($html) {
	if (preg_match('!</p>|<br\s*/?>!', $html)) {
		// is html
		$html = html_to_text($html, 0);
	}

	return $html;
}

function block_exastud_text_to_html($text) {
	// make sure it's text
	$text = block_exastud_html_to_text($text);

	return text_to_html($text, null, false);
}

function block_exastud_can_edit_bp($bp) {
	return !preg_match('!^bw\-*!', $bp->sourceinfo);
}

function block_exastud_can_edit_class($class) {
	return $class->userid == g::$USER->id;
}

function block_exastud_get_date_of_birth_as_timestamp($userid) {
	$str = trim(block_exastud_get_custom_profile_field_value($userid, 'dateofbirth'));
	if (!$str) {
		return null;
	}
	$parts = preg_split('![^0-9]+!', $str);
	if (count($parts) != 3) {
		// wrong format
		return null;
	}

	return mktime(0, 0, 0, $parts[1], $parts[0], $parts[2]);
}

function block_exastud_get_date_of_birth($userid) {
	$timestamp = block_exastud_get_date_of_birth_as_timestamp($userid);
	if (!$timestamp) {
		return null;
	}

	return date('d.m.Y', $timestamp);
}

function block_exastud_get_review($classid, $subjectid, $studentid) {
	$data = block_exastud_get_subject_student_data($classid, $subjectid, $studentid);

	if (!isset($data->review)) {
		// always fill review property
		$data->review = null;

		// fallback for old style with own table
		$class = block_exastud_get_class($classid);

		$reviewdata = g::$DB->get_records('block_exastudreview', array('subjectid' => $subjectid, 'periodid' => $class->periodid, 'studentid' => $studentid), 'timemodified DESC');
		$reviewdata = reset($reviewdata);
		if ($reviewdata) {
			$data->review = $reviewdata->review;
			$data->{'review.modifiedby'} = $reviewdata->teacherid;
			$data->{'review.timemodified'} = $reviewdata->timemodified;
		}
	}

	return $data;
}

/**
 * liefert einen review zurück, falls dieser (ganz oder teilweise) ausgfüllt wurde
 * @param $classid
 * @param $subjectid
 * @param $studentid
 */
function block_exastud_get_graded_review($classid, $subjectid, $studentid) {
	$subjectData = block_exastud_get_review($classid, $subjectid, $studentid);
	if (!$subjectData) {
		// no review
		return;
	}
	if (!@$subjectData->review && !@$subjectData->grade && !@$subjectData->niveau) {
		// empty review data
		return;
	}

	// has a review
	return $subjectData;
}

function block_exastud_get_bildungsplan_subjects($bpid) {
	return g::$DB->get_records('block_exastudsubjects', ['bpid' => $bpid], 'sorting');
}

function block_exastud_get_class($classid) {
	return g::$DB->get_record('block_exastudclass', ['id' => $classid]);
}


function block_exastud_get_bildungsstandards() {
	$bildungsstandards = array_map('trim', explode(',', block_exastud_get_plugin_config('bildungsstandards')));
	$bildungsstandards = array_combine($bildungsstandards, $bildungsstandards);

	return $bildungsstandards;
}

function block_exastud_get_class_title($classid) {
	$class = block_exastud_get_class($classid);

	$classTitle = $class->title;
	if ($head_teacher = g::$DB->get_record('user', array('id' => $class->userid))) {
		$classTitle .= ' ('.fullname($head_teacher).')';
	}

	return $classTitle;
}

function block_exastud_get_student_print_templateid($class, $userid) {
	$templateid = block_exastud_get_class_student_data($class->id, $userid, BLOCK_EXASTUD_DATA_ID_PRINT_TEMPLATE);
	$available_templates = \block_exastud\print_templates::get_class_available_print_templates($class);
	if (isset($available_templates[$templateid])) {
		return $templateid;
	}

	$default_templateid = block_exastud_get_class_data($class->id, BLOCK_EXASTUD_DATA_ID_CLASS_DEFAULT_TEMPLATEID);
	if (isset($available_templates[$default_templateid])) {
		return $default_templateid;
	}

	return key($available_templates);
}

/**
 * @param $class
 * @param $userid
 * @return block_exastud\print_template
 */
function block_exastud_get_student_print_template($class, $userid) {
	$templateid = block_exastud_get_student_print_templateid($class, $userid);

	return block_exastud\print_template::create($templateid);
}

function block_exastud_is_project_teacher($class, $userid) {
	return !!block_exastud_get_project_teacher_students($class, $userid);
}

function block_exastud_get_project_teacher_students($class, $userid) {
	$classstudents = block_exastud_get_class_students($class->id);
	$project_teacher_students = [];

	foreach ($classstudents as $classstudent) {
		$project_teacher_id = block_exastud_get_class_student_data($class->id, $classstudent->id, BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER);
		if ($project_teacher_id == $userid) {
			$project_teacher_students[$classstudent->id] = $classstudent;
		}
	}

	return $project_teacher_students;
}

function block_exastud_get_user_gender($userid) {
	$value = block_exastud_get_custom_profile_field_value($userid, 'gender');
	if (!$value) {
		return null;
	} elseif ($value[0] == 'm') {
		return 'male';
	} else {
		return 'female';
	}
}

function block_exastud_student_has_projekt_pruefung($class, $userid) {
	$templateids_with_projekt_pruefung = \block_exastud\print_templates::get_templateids_with_projekt_pruefung();
	$templateid = block_exastud_get_student_print_templateid($class, $userid);

	return in_array($templateid, $templateids_with_projekt_pruefung);
}

function block_exastud_normalize_projekt_pruefung($class) {
	$classstudents = block_exastud_get_class_students($class->id);

	foreach ($classstudents as $student) {
		if (!block_exastud_student_has_projekt_pruefung($class, $student->id)) {
			block_exastud_set_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_PROJECT_TEACHER, null);
		}
	}
}

function block_exastud_format_certificate_issue_date($time) {
	if (substr(current_language(), 0, 2) == 'de') {
		return date('d.m.Y', $time);
	} else {
		return userdate($time, block_exastud_get_string('strftimedatefullshort', 'langconfig'));
	}
}

function block_exastud_get_certificate_issue_date_timestamp($class) {
	$period = block_exastud_get_period($class->periodid);

	return @$period->certificate_issue_date ?: null;
}

function block_exastud_get_certificate_issue_date_text($class) {
	if ($certificate_issue_date_timestamp = block_exastud_get_certificate_issue_date_timestamp($class)) {
		return block_exastud_format_certificate_issue_date($certificate_issue_date_timestamp);
	} else {
		return null;
	}
}

function block_exastud_is_bw_active() {
	return !!block_exastud_get_plugin_config('bw_active');
}

function block_exastud_is_siteadmin($userid = null) {
    global $CFG, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if (!$userid) {
        return false;
    }

    $admins = explode(',', $CFG->siteadmins);
    $result = in_array($userid, $admins);
    return $result;
}

function block_exastud_get_head_teachers_all() {
    global $DB, $CFG;
    $cohort = block_exastud_get_head_teacher_cohort();
    $where[] = 'u.id <> :guestid';
    $params['guestid'] = $CFG->siteguest;
    $where[] = 'u.deleted = 0';
    $where[] = 'u.confirmed = 1';
    //$where[] = 'cm.cohortid = :cohortid';
    $params['cohortid'] = $cohort->id;
    $where = implode(' AND ', $where);
    $sql = " SELECT u.* 
              FROM {user} u
              JOIN {cohort_members} cm ON (cm.userid = u.id AND cm.cohortid = :cohortid)
              WHERE $where";
    $headTeachers = $DB->get_records_sql($sql, $params);
    return $headTeachers;
}