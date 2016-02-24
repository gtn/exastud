<?php

defined('MOODLE_INTERNAL') || die;
require_once __DIR__.'/inc.php';

use block_exastud\globals as g;

class block_exastud_renderer extends plugin_renderer_base {

	public function header($items) {
		$items = (array)$items;
		$strheader = \block_exastud\get_string('pluginname', 'block_exastud');

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
			if (is_siteadmin()) {
				$tabs['settings']->subtree[] = new tabobject('blockconfig',	'javascript:window.open(\''.(new moodle_url('/admin/settings.php?section=blocksettingexastud'))->out(false).'\')', \block_exastud\get_string("blocksettings", 'block'), '', true);
			}
			$tabs['settings']->subtree[] = new tabobject('categories', new moodle_url('/blocks/exastud/configuration_global.php', [ 'courseid' => g::$COURSE->id ]).'&action=categories', \block_exastud\trans("de:Kompetenzen"), '', true);
			$tabs['settings']->subtree[] = new tabobject('subjects',   new moodle_url('/blocks/exastud/configuration_global.php', [ 'courseid' => g::$COURSE->id ]).'&action=subjects', \block_exastud\trans("de:Fachbezeichnungen"), '', true);
			$tabs['settings']->subtree[] = new tabobject('evalopts',   new moodle_url('/blocks/exastud/configuration_global.php', [ 'courseid' => g::$COURSE->id ]).'&action=evalopts', \block_exastud\trans("de:Bewertungsskala"), '', true);
			$tabs['settings']->subtree[] = new tabobject('head_teachers', 'javascript:window.open(\''.(new moodle_url('/cohort/assign.php', [ 'id' => block_exastud\get_head_teacher_cohort()->id ]))->out(false).'\')', \block_exastud\get_string('head_teachers'), '', true);

			if (block_exastud_has_global_cap(block_exastud\CAP_UPLOAD_PICTURE))
				$tabs['settings']->subtree[] = new tabobject('pictureupload', new moodle_url('/blocks/exastud/pictureupload.php', [ 'courseid' => g::$COURSE->id ]), \block_exastud\get_string("pictureupload", "block_exastud"), '', true);
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

		echo parent::header();

		echo '<div id="block_exastud">';

		echo $this->render($tabtree);

		// header
		/*
		if (!in_array('noheading', $options))
			echo $OUTPUT->heading($last_item_name);
		*/
	}

	public function footer() {
		echo '</div>';

		echo parent::footer();
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
		return html_writer::empty_tag('input', [
			'type' => 'button',
			'value' => \block_exastud\get_string('back'),
			'onclick' => 'document.location.href = '.json_encode(block_exastud\url::create($url)->out(false))
		]);
	}
}