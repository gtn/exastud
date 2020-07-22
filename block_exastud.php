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

require_once __DIR__.'/inc.php';
require_once __DIR__.'/../moodleblock.class.php';


class block_exastud extends block_list {

	function init() {
		$this->title = block_exastud_get_string('blocktitle');
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

	function get_content() {
		global $CFG, $COURSE, $OUTPUT, $USER;



		if (!block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_USE)) {
			$this->content = '';

			return $this->content;
		}

		if ($this->content !== null) {
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

		$output = block_exastud_get_renderer();

		if (block_exastud_get_active_or_next_period() && block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
			$icon = '<img src="'.$output->image_url('klassenzuteilung', 'block_exastud').'" class="icon" alt="" />';
			$this->content->items[] = '<a title="'.block_exastud_get_string('configuration_classes').'" href="'.$CFG->wwwroot.'/blocks/exastud/configuration_classes.php?courseid='.$COURSE->id.'">'.$icon.block_exastud_get_string('configuration_classes').'</a>';
		}
		// show only if the user is a teacher of at least one class
        $myclasses = block_exastud_get_teacher_classes($USER->id);
        if (count($myclasses) > 0 && !block_exastud_is_siteadmin()) {
            if (block_exastud_get_active_period() && block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_REVIEW)) {
                $icon = '<img src="'.$output->image_url('beurteilung', 'block_exastud').'" class="icon" alt="" />';
                $this->content->items[] = '<a title="'.block_exastud_get_string('review').'" href="'.$CFG->wwwroot.
                        '/blocks/exastud/review.php'./*?courseid='.$COURSE->id.*/'">'.$icon.block_exastud_get_string('review').'</a>';
            }
            if (block_exastud_get_active_or_last_period() && block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_VIEW_REPORT)) {
                $icon = '<img src="'.$output->image_url('zeugnisse', 'block_exastud').'" class="icon" alt="" />';
                $this->content->items[] = '<a title="'.block_exastud_get_string('reports').'" href="'.$CFG->wwwroot.
                        '/blocks/exastud/report.php'./*?courseid='.$COURSE->id.*/'">'.$icon.block_exastud_get_string('reports').'</a>';
            }
        }

		if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_ADMIN)) {

			$icon = '<img src="'.$output->image_url('eingabezeitraum', 'block_exastud').'" class="icon" alt="" />';
			$this->content->items[] = '<a title="'.block_exastud_get_string('settings').'" href="'.$CFG->wwwroot.'/admin/settings.php?section=blocksettingexastud">'.$icon.block_exastud_get_string('settings').'</a>';
			$icon = '<img src="'.$output->image_url('head_teachers', 'block_exastud').'" class="icon" alt="" />';
			$this->content->items[] = '<a title="'.block_exastud_get_string('new_head_teacher').'" href="'.$CFG->wwwroot.'/cohort/assign.php?id='.block_exastud_get_head_teacher_cohort()->id.'">'.$icon.block_exastud_get_string('new_head_teacher').'</a>';
            $icon = '<img src="'.$output->image_url('calendar', 'block_exastud').'" class="icon" alt="" />';
            $this->content->items[] = '<a title="'.block_exastud_get_string('periods').'" href="'.$CFG->wwwroot.'/blocks/exastud/periods.php'./*.?courseid='.$COURSE->id.*/'">'.$icon.block_exastud_get_string('periods').'</a>';
		}
        if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_ADMIN)
                || block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)
                || block_exastud_is_subject_teacher()) {
            if ($request_count = block_exastud_get_admin_requests_count()) {
                $icon = '<img src="'.$output->image_url('attention', 'block_exastud').'" class="icon" alt="" />';
                $this->content->items[] =
                        '<a title="'.block_exastud_get_string('requests').'" href="'.$CFG->wwwroot.'/blocks/exastud/requests.php'.
                        '">'.$icon.block_exastud_get_string('requests').'</a> ('.$request_count.')';
            }
        }
		return $this->content;
	}
}
