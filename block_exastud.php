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
require_once __DIR__.'/lib/lib.php';
require_once __DIR__.'/../moodleblock.class.php';

class block_exastud extends block_list {
	
	function init() {
		$this->title = \block_exastud\get_string('pluginname');
	}

	function instance_allow_multiple() {
		return false;
	}

	function has_config() {
		return true;
	}

	function instance_allow_config() {
		return false;
	}

	/*
	function config_save($data) {
		print_r($data);
		die();
		// Default behavior: save all variables as $CFG properties
		foreach ($data as $name => $value) {
			set_config($name, $value);
		}
		return true;
	}
	*/
	function get_content() {
		global $CFG, $COURSE, $USER, $DB;

		if (!block_exastud_has_global_cap(block_exastud\CAP_USE)) {
			$this->content = '';
			return $this->content;
		}

		if ($this->content !== NULL) {
			return $this->content;
		}

		if (empty($this->instance)) {
			$this->content = '';
			return $this->content;
		}

		$this->content = new stdClass;
		$this->content->items = array();
		$this->content->icons = array();
		$this->content->footer = '';

		if (block_exastud_get_active_period()) {
			if (block_exastud_has_global_cap(block_exastud\CAP_MANAGE_CLASSES)) {
				$this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/exastud/pix/klassenzuteilung.png" height="16" width="23" alt="" />';
				$this->content->items[] = '<a title="' . \block_exastud\get_string('configuration_classes') . '" href="' . $CFG->wwwroot . '/blocks/exastud/configuration_classes.php?courseid=' . $COURSE->id . '">' . \block_exastud\get_string('configuration_classes') . '</a>';
			}
			if (block_exastud_has_global_cap(block_exastud\CAP_REVIEW)) {
				$this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/exastud/pix/beurteilung.png" height="16" width="23" alt="" />';
				$this->content->items[] = '<a title="' . \block_exastud\get_string('review') . '" href="' . $CFG->wwwroot . '/blocks/exastud/review.php?courseid=' . $COURSE->id . '">' . \block_exastud\get_string('review') . '</a>';
			}
			if (block_exastud_has_global_cap(block_exastud\CAP_VIEW_REPORT)) {
				$this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/exastud/pix/zeugnisse.png" height="16" width="23" alt="" />';
				$this->content->items[] = '<a title="' . \block_exastud\get_string('report') . '" href="' . $CFG->wwwroot . '/blocks/exastud/report.php?courseid=' . $COURSE->id . '">' . \block_exastud\get_string('report') . '</a>';
			}
		}

		if (block_exastud_has_global_cap(block_exastud\CAP_ADMIN)) {
			$this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/exastud/pix/eingabezeitraum.png" height="16" width="23" alt="" />';
			$this->content->items[] = '<a title="' . \block_exastud\get_string('settings') . '" href="' . $CFG->wwwroot . '/blocks/exastud/periods.php?courseid=' . $COURSE->id . '">' . \block_exastud\get_string('settings') . '</a>';
			$this->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/exastud/pix/head_teachers.png" height="16" width="23" alt="" />';
			$this->content->items[] = '<a title="' . \block_exastud\trans('head_teachers', 'de:Klassenlehrer') . '" href="' . $CFG->wwwroot . '/cohort/assign.php?id=' . block_exastud\get_head_teacher_cohort()->id . '">' . \block_exastud\trans('head_teachers', 'de:Klassenlehrer') . '</a>';
		}

		return $this->content;
	}
}
