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

defined('MOODLE_INTERNAL') || die;
require_once __DIR__.'/inc.php';

use block_exastud\globals as g;

class block_exastud_renderer extends plugin_renderer_base {

	public function header($items) {
		$items = (array)$items;
		$strheader = \block_exastud\get_string('blocktitle', 'block_exastud');

		$last_item_name = '';
		$tabs = array();

		if (block_exastud_get_active_period()) {
			if (block_exastud_has_global_cap(block_exastud\CAP_MANAGE_CLASSES)) {
				$tabs['configuration_classes'] = new tabobject('configuration_classes', new moodle_url('/blocks/exastud/configuration_classes.php', [ 'courseid' => g::$COURSE->id ]), \block_exastud\get_string("configuration_classes", "block_exastud"), '', true);
			}
			if (block_exastud_has_global_cap(block_exastud\CAP_REVIEW)) {
				$tabs['review'] = new tabobject('review', new moodle_url('/blocks/exastud/review.php', [ 'courseid' => g::$COURSE->id ]), \block_exastud\get_string("review", "block_exastud"), '', true);
			}
			if (block_exastud_has_global_cap(block_exastud\CAP_VIEW_REPORT)) {
				$tabs['report'] = new tabobject('report', new moodle_url('/blocks/exastud/report.php', [ 'courseid' => g::$COURSE->id ]), \block_exastud\get_string("reports", "block_exastud"), '', true);
			}
		}
		if (block_exastud_has_global_cap(block_exastud\CAP_ADMIN)) {
			$tabs['settings'] = new tabobject('settings', new moodle_url('/blocks/exastud/periods.php', [ 'courseid' => g::$COURSE->id ]), \block_exastud\get_string("settings"), '', true);

			$tabs['settings']->subtree[] = new tabobject('periods',	new moodle_url('/blocks/exastud/periods.php', [ 'courseid' => g::$COURSE->id ]), \block_exastud\get_string("periods"), '', true);
			$tabs['settings']->subtree[] = new tabobject('categories', new moodle_url('/blocks/exastud/configuration_global.php', [ 'courseid' => g::$COURSE->id ]).'&action=categories', \block_exastud\trans("de:Fächerübergreifende Kompetenzen"), '', true);
			$tabs['settings']->subtree[] = new tabobject('evalopts',   new moodle_url('/blocks/exastud/configuration_global.php', [ 'courseid' => g::$COURSE->id ]).'&action=evalopts', \block_exastud\trans("de:Bewertungsskala"), '', true);
			$tabs['settings']->subtree[] = new tabobject('subjects',   new moodle_url('/blocks/exastud/configuration_global.php', [ 'courseid' => g::$COURSE->id ]).'&action=subjects', \block_exastud\trans("de:Fachbezeichnungen"), '', true);

			if (block_exastud_has_global_cap(block_exastud\CAP_UPLOAD_PICTURE))
				$tabs['settings']->subtree[] = new tabobject('pictureupload', new moodle_url('/blocks/exastud/pictureupload.php', [ 'courseid' => g::$COURSE->id ]), \block_exastud\get_string("pictureupload", "block_exastud"), '', true);

			// syntax muss hier so sein: javascript:void ...!
			// moodle can't use json_encode in tabobjects
			// moodle can't use onclick in tabobjects
			if (is_siteadmin()) {
				$title = \block_exastud\get_string_if_exists('blocksettings') ?: \block_exastud\get_string("blocksettings", 'block');
				$tabs['blockconfig'] = new tabobject('blockconfig',	'javascript:void window.open(\''.\block_exastud\url::create('/admin/settings.php?section=blocksettingexastud')->out(false).'\');', $title, '', true);
			}
			$tabs['head_teachers'] = new tabobject('head_teachers', 'javascript:void window.open(\''.\block_exastud\url::create('/cohort/assign.php', [ 'id' => block_exastud\get_head_teacher_cohort()->id ])->out(false).'\');', \block_exastud\get_string('head_teachers'), '', true);
		}

		$tabtree = new tabtree($tabs);

		foreach ($items as $level => $item) {
			if (!is_array($item)) {
				if (!is_string($item)) {
					trigger_error('not supported');
				}

				if ($item[0] == '=')
					$item_name = substr($item, 1);
				else
					$item_name = \block_exastud\get_string($item, "block_exastud");

				$item = array('name' => $item_name, 'id'=>$item);
			}

			if (!empty($item['id']) && $tabobj = $tabtree->find($item['id'])) {
				// overwrite active and selected
				$tabobj->active = true;
				$tabobj->selected = true;
				if (empty($item['link']) && $tabobj->link) {
					$item['link'] = $tabobj->link;
				}
			}

			$last_item_name = $item['name'];
			g::$PAGE->navbar->add($item['name'], !empty($item['link'])? $item['link'] : null);
		}

		g::$PAGE->set_title($strheader.': '.$last_item_name);
		g::$PAGE->set_heading($strheader);
		g::$PAGE->set_cacheable(true);
		g::$PAGE->set_button('&nbsp;');

		block_exastud_init_js_css();

		$content  = '';
		$content .= parent::header();
		$content .= '<div id="block_exastud">';
		$content .= $this->render($tabtree);

		return $content;
	}

	public function footer() {
		$content  = '';
		$content .= '</div>';
		$content .= parent::footer();

		return $content;
	}

	public function table(html_table $table) {

		if (empty($table->attributes['class'])) {
			$table->attributes['class'] = 'exa_table';
		}

		return html_writer::table($table);
	}

	function print_subtitle($subtitle,$editlink = null) {
		return html_writer::tag("p", $subtitle .  (($editlink == null) ? "" : " " . html_writer::tag("a", html_writer::tag("img", '',array('src'=>'pix/edit.png')),array('href'=>$editlink,'class'=>'ers_inlineicon'))), array('class'=>'esr_subtitle'));
	}
	
	function print_edit_link($link) {
		return html_writer::tag("a", html_writer::tag("img", '',array('src'=>'pix/edit.png')),array('href'=>$link,'class'=>'ers_inlineicon'));
	}

	function print_student_report($categories, $textReviews) {
		$output = '<table id="review-table">';

		$current_parent = null;
		foreach ($categories as $category){

			if ($current_parent !== $category->parent) {
				$current_parent = $category->parent;
				$output .= '<tr><th class="category category-parent">'.($category->parent?$category->parent.':':'').'</th>';
				foreach ($category->evaluationOtions as $option) {
					$output .= '<th class="evaluation-header"><b>' . $option->title . '</th>';
				}
				$output .= '</tr>';
			}

			$output .= '<tr><td class="category">'.$category->title.'</td>';

			foreach ($category->evaluationOtions as $pos_value => $option) {
				$output .= '<td class="evaluation">';

				$output .= join(', ', array_map(function($reviewer){
					return $reviewer->subject_title?:fullname($reviewer);
				}, $option->reviewers));

				$output .= '</td>';
			}
			$output .= '</tr>';
		}

		$output .= '</table>';




		$output .= '<h3>'.\block_exastud\get_string('detailedreview').'</h3>';

		$output .= '<table id="ratingtable">';
		foreach($textReviews as $textReview) {
			$output .= '<tr><td class="ratinguser">'.$textReview->title.'</td>
				<td class="ratingtext">'.format_text($textReview->review).'</td>
				</tr>';
		}
		$output .= '</table>';

		return $output;
	}

	function back_button($url) {
		return $this->link_button(
			block_exastud\url::create($url),
			\block_exastud\get_string('back')
		);
	}
	
	function link_button($url, $label, $attributes = []) {
		return html_writer::empty_tag('input', $attributes + [
			'type' => 'button',
			'exa-type' => 'link',
			'href' => $url,
			'value' => $label,
		]);
	}
}
