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

	public function header($items, $options = [], $forSettings = false) {
	    global $PAGE;
		$items = (array)$items;
		$strheader = block_exastud_get_string('blocktitle');

		$last_item_name = '';
		$tabs = array();
        $activeItem = null;

		if (block_exastud_get_active_or_next_period() && block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
			$tabs['configuration_classes'] = new tabobject('configuration_classes', new moodle_url('/blocks/exastud/configuration_classes.php', ['courseid' => g::$COURSE->id]), block_exastud_get_string('configuration_classes'), '', true);
		}
		if (block_exastud_get_active_period() && block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_REVIEW)) {
			$tabs['review'] = new tabobject('review', new moodle_url('/blocks/exastud/review.php', ['courseid' => g::$COURSE->id]), block_exastud_get_string('review'), '', true);
		}

		if (block_exastud_get_active_or_last_period() && block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_VIEW_REPORT) && !block_exastud_is_siteadmin()) {
			$tabs['report'] = new tabobject('report', new moodle_url('/blocks/exastud/report.php', ['courseid' => g::$COURSE->id]), block_exastud_get_string('reports'), '', true);
		}
		/*
		if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
			$tabs[] = new tabobject('set_bildungsstandard', new moodle_url('/blocks/exastud/set_bildungsstandard.php', [ 'courseid' => g::$COURSE->id ]), block_exastud_trans("de:Bildungsstandard festlegen"), '', true);
		}
		*/

		if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_ADMIN)) {
			/*$tabs['settings'] = new tabobject('settings', new moodle_url('/blocks/exastud/periods.php', ['courseid' => g::$COURSE->id]), block_exastud_get_string("settings"), '', true);

			$tabs['settings']->subtree[] = new tabobject('periods', new moodle_url('/blocks/exastud/periods.php', ['courseid' => g::$COURSE->id]), block_exastud_get_string("periods"), '', true);
			$tabs['settings']->subtree[] = new tabobject('categories', new moodle_url('/blocks/exastud/configuration_global.php', ['courseid' => g::$COURSE->id]).'&action=categories', block_exastud_get_string("competencies"), '', true);
			$tabs['settings']->subtree[] = new tabobject('evalopts', new moodle_url('/blocks/exastud/configuration_global.php', ['courseid' => g::$COURSE->id]).'&action=evalopts', block_exastud_get_string("grading"), '', true);

			if (block_exastud_get_plugin_config('can_edit_bps_and_subjects')) {
				$tabs['settings']->subtree[] = new tabobject('bps', new moodle_url('/blocks/exastud/configuration_global.php', ['courseid' => g::$COURSE->id]).'&action=bps', block_exastud_get_string("education_plans"), '', true);
			}

			if (!block_exastud_is_bw_active()) {
				if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_UPLOAD_PICTURE)) {
					$tabs['settings']->subtree[] = new tabobject('pictureupload', new moodle_url('/blocks/exastud/pictureupload.php', ['courseid' => g::$COURSE->id]), block_exastud_get_string('pictureupload'), '', true);
				}
			}

			if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_ADMIN)) {
				$tabs['settings']->subtree[] = new tabobject('backup', new moodle_url('/blocks/exastud/backup.php', ['courseid' => g::$COURSE->id]), block_exastud_get_string("backup"), '', true);
			}*/

			// syntax muss hier so sein: javascript:void ...!
			// moodle can't use json_encode in tabobjects
			// moodle can't use onclick in tabobjects
			if (is_siteadmin()) {
				//$title = block_exastud_get_string_if_exists('blocksettings') ?: block_exastud_get_string("blocksettings", 'block');
				$title = block_exastud_get_string("block_settings");
				$tabs['blockconfig'] = new tabobject('blockconfig', 'javascript:void window.open(\''.\block_exastud\url::create('/admin/settings.php?section=blocksettingexastud')->out(false).'\');', $title, '', true);
			}
/*			$tabs['head_teachers'] = new tabobject('head_teachers', 'javascript:void window.open(\''.\block_exastud\url::create('/cohort/assign.php', ['id' => block_exastud_get_head_teacher_cohort()->id])->out(false).'\');', block_exastud_get_string('head_teachers'), '', true);*/
		}

        if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_ADMIN)
                || block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)
                || block_exastud_is_subject_teacher()) {
            $requests_tabtitle = null;
            if ($requests_count = block_exastud_get_admin_requests_count()) {
                //$requests_tabtitle = html_writer::tag("img", '', array('src' => 'pix/attention.png')).
                $requests_tabtitle = '<i class="fas fa-exclamation-triangle" title="'.block_exastud_get_string('requests_for_you').'"></i>'.'&nbsp;'.
                        block_exastud_get_string('requests').'&nbsp;('.$requests_count.')';
            } else {
                if (block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_ADMIN) || block_exastud_has_global_cap(BLOCK_EXASTUD_CAP_MANAGE_CLASSES)) {
                            $requests_tabtitle = block_exastud_get_string('requests');
                }
            }
            if ($requests_tabtitle) {
                $tabs['requests'] = new tabobject('requests', new moodle_url('/blocks/exastud/requests.php'),
                        $requests_tabtitle, block_exastud_get_string('requests_for_you'), true);
            }
        }

		$class = @$options['class'];

		if ($class) {
		    // All tabs are visible only for class owner (teacher). For site admin - only class_info with short edit class form
            if ($class->userid == g::$USER->id) { // only for owner
                $tabs['configuration_classes']->subtree[] = new tabobject('students',
                        new moodle_url('/blocks/exastud/configuration_class.php',
                                ['courseid' => g::$COURSE->id, 'action' => 'edit', 'classid' => $class->id, 'type' => 'students']),
                        block_exastud_get_string('students'),
                        '',
                        true);
                //if (!block_exastud_get_only_learnsociale_reports()) {
                    $tabs['configuration_classes']->subtree[] = new tabobject('studentgradereports',
                            new moodle_url('/blocks/exastud/configuration_class.php',
                                    ['courseid' => g::$COURSE->id, 'action' => 'edit', 'classid' => $class->id,
                                            'type' => 'studentgradereports']),
                            block_exastud_get_string('studentgradereports'),
                            '',
                            true);
                //}
                $tabs['configuration_classes']->subtree[] = new tabobject('teachers',
                        new moodle_url('/blocks/exastud/configuration_class.php',
                                ['courseid' => g::$COURSE->id, 'action' => 'edit', 'classid' => $class->id, 'type' => 'teachers']),
                        block_exastud_get_string('teachers'),
                        '',
                        true);
                //if (!block_exastud_get_only_learnsociale_reports()) {
                    $tabs['configuration_classes']->subtree[] = new tabobject('teachers_options',
                            new moodle_url('/blocks/exastud/configuration_class.php',
                                    ['courseid' => g::$COURSE->id, 'action' => 'edit', 'classid' => $class->id,
                                            'type' => 'teachers_options']),
                            block_exastud_get_string('teachers_options'),
                            '',
                            true);
                //}
                /*if (is_siteadmin()) {
                    $tabs['configuration_classes']->subtree[] = new tabobject('categories',
                            new moodle_url('/blocks/exastud/configuration_class.php',
                                    ['courseid' => g::$COURSE->id, 'action' => 'edit', 'classid' => $class->id,
                                            'type' => 'categories']),
                            block_exastud_get_string('categories'),
                            '',
                            true);
                }*/
                $tabs['configuration_classes']->subtree[] = new tabobject('class_info',
                        new moodle_url('/blocks/exastud/configuration_class_info.php',
                                ['courseid' => g::$COURSE->id, 'classid' => $class->id]),
                        block_exastud_get_string('class_info'),
                        '',
                        true);
                // $tabs['configuration_classes']->subtree[] = new tabobject('export_class', new moodle_url('/blocks/exastud/export_class.php', ['courseid' => g::$COURSE->id, 'classid' => $class->id]), block_exastud_get_string('export_class'), '', true);
            } else if (is_siteadmin()) {
                /*$tabs['configuration_classes']->subtree[] = new tabobject('categories',
                        new moodle_url('/blocks/exastud/configuration_class.php',
                                ['courseid' => g::$COURSE->id, 'action' => 'edit', 'classid' => $class->id,
                                        'type' => 'categories']),
                        block_exastud_get_string('categories'),
                        '',
                        true);*/
                $tabs['configuration_classes']->subtree[] = new tabobject('class_info',
                        new moodle_url('/blocks/exastud/configuration_class_info.php',
                                ['courseid' => g::$COURSE->id, 'classid' => $class->id]),
                        block_exastud_get_string('class_info'),
                        '',
                        true);
            }
		}

		if ($forSettings) {
            //$tabtree = new tabtree($tabs['settings']->subtree);
            $strheader = $PAGE->course->fullname;
            $tabtree = block_exastud_menu_for_settings();
        } else {
            $tabtree = new tabtree($tabs);
        }
        //$tabtree = new tabtree($tabs);

		foreach ($items as $level => $item) {
			if (!is_array($item)) {
				if (!is_string($item)) {
					trigger_error('not supported');
				}

				if ($item[0] == '=') {
					$item_name = substr($item, 1);
				} else {
					$item_name = @block_exastud_get_string($item);
				}

				$item = array('name' => $item_name, 'id' => $item);
			} else {
				if (!isset($item['name'])) {
					$item['name'] = @block_exastud_get_string($item['id']);
				}
			}
            $activeItem = @$item['id'];
			if (!empty($item['id']) && $tabobj = $tabtree->find($item['id'])) {
				// overwrite active and selected
				$tabobj->active = true;
				$tabobj->selected = true;

				if (empty($item['link']) && $tabobj->link) {
					$item['link'] = $tabobj->link;
				}
			}

			if ($item['name']) {
				$last_item_name = $item['name'];
				g::$PAGE->navbar->add($item['name'], !empty($item['link']) ? $item['link'] : null);
			}
		}

		g::$PAGE->set_title($strheader.': '.$last_item_name);
		g::$PAGE->set_heading($strheader);
		g::$PAGE->set_cacheable(true);
		g::$PAGE->set_button('&nbsp;');

		block_exastud_init_js_css();

		$content = '';
		$content .= parent::header();

		if (@$options['content_title']) {
            $content .= '<h2>'.$options['content_title'].'</h2>';
        }

        $content .= '<div id="block_exastud">';

		if (g::$PAGE->pagelayout != 'embedded') {
			if (block_exastud_is_a2fa_installed()) {
				$content .= \block_exa2fa\api::render_timeout_info('block_exastud');
			}

			if ($class && $tabtree->subtree['configuration_classes']->selected) {
				// if (@$tabtree->subtree[$items[0]['id']]->selected && !empty($options['betweenTabRowsCallback'])) {
				$subtree = $tabtree->subtree['configuration_classes']->subtree;
				unset($tabtree->subtree['configuration_classes']->subtree);

				$content .= $this->render($tabtree);

				$content .= $this->heading($class->title);

				$content .= $this->render(new tabtree($subtree));
			} else {
				$content .= $this->render($tabtree);
			}
		}
        // message to site admin
        if (is_siteadmin() && ($class || in_array($activeItem, ['configuration_classes', 'review', 'report']))) {
            $output = block_exastud_get_renderer();
            $content .= $output->notification(block_exastud_get_string('attention_admin_cannot_be_classteacher'), 'error');
        }

		return $content;
	}

	public function footer() {
		$content = '';
		$content .= '</div>';
		$content .= parent::footer();

		return $content;
	}

	public function table(html_table $table, $add_class = '') {

		if (empty($table->attributes['class'])) {
			$table->attributes['class'] = 'exa_table';
		}
		if ($add_class) {
            $table->attributes['class'] .= ' '.$add_class.' ';
        }

		return html_writer::table($table);
	}

	/*
	function print_subtitle($content) {
		return html_writer::tag("p", $content, array('class' => 'esr_subtitle'));
	}

	function print_edit_link($link) {
		return html_writer::tag("a", html_writer::tag("img", '', array('src' => 'pix/edit.png')), array('href' => $link, 'class' => 'ers_inlineicon'));
	}
	*/

	function student_report($class, $student) {
		$categories = block_exastud_get_class_categories_for_report($student->id, $class->id);
		$class_subjects = block_exastud_get_class_subjects($class);
		$lern_soz = block_exastud_get_class_student_data($class->id, $student->id, BLOCK_EXASTUD_DATA_ID_LERN_UND_SOZIALVERHALTEN);
        $student_review = block_exastud_get_report($student->id,  $class->periodid, $class->id);

		$template = block_exastud_get_student_print_template($class, $student->id);
        $output = '<style>
                    body {
                        color: #333333;                        
                    }
                    .heading1 {
                        font-size: 20px;
                        font-weight: bold;
                        font-family: Verdana, Arial, sans-serif;          
                        margin-top: 20px;          
                        margin-bottom: 5px;
                        line-height: 20px;
                    }
                    #review-table, #ratingtable {
                        color: #333333;
                        font-family: Verdana, Arial, sans-serif;
                        font-size: 14px;
                        border-collapse: collapse;
                        margin: 20px;
                        width: 99%;
                    }
                    #review-table, 
                    #review-table td,
                    #review-table th,
                    #ratingtable, 
                    #ratingtable td,
                    #ratingtable th {
                        border: 1px solid #aaaaaa;
                    }
                    #review-table td,
                    #review-table th,
                    #ratingtable td,
                    #ratingtable th {
                        padding: 7px;
                    }
                    #ratingtable td:nth-child(1) {
                        width: 30%;
                    }
                    </style>
        ';

		$output .= '<table id="review-table">';

		$current_parent = null;
		foreach ($categories as $category) {

			if ($current_parent !== $category->parent) {
				$current_parent = $category->parent;
				$output .= '<tr><th class="category category-parent" width="25%">'.($category->parent ? $category->parent.':' : '').'</th>';
				$output .= '<th class="average">'.block_exastud_get_string('average').'</th>';
                switch (block_exastud_get_competence_eval_type()) {
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                        foreach ($class_subjects as $subject) {
                            $output .= '<th>'.$subject->title.'</th>';
                        }
                        break;
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                    case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                        foreach ($category->evaluationOptions as $option) {
                            $output .= '<th class="evaluation-header" width="'.round((100 - 25) / count($category->evaluationOptions)).'%"><b>'.$option->title.'</th>';
                        }
                        break;

                }
				$output .= '</tr>';
			}

			$output .= '<tr style="border-bottom:1pt solid black;"><td class="category">'.$category->title.'</td>';

			// average column
            $globalAverage = (@$student_review->category_averages[$category->source.'-'.$category->id] ? $student_review->category_averages[$category->source.'-'.$category->id] : '');
            $output .= '<td class="average">'.$globalAverage.'</td>';

			switch (block_exastud_get_competence_eval_type()) {
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_GRADE:
                    foreach ($class_subjects as $subject) {
                        $output .= '<td align="center">';
                        $output .= $category->evaluationAverages[$subject->id]->value;
                        /*if ($category->evaluationAverages[$subject->id]->value > 0) {
                            $output .= ' <small>('.$category->evaluationAverages[$subject->id]->reviewers.' reviewers)<small>';
                        }*/
                        $output .= '</td>';
                    }
                    break;
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_POINT:
                case BLOCK_EXASTUD_COMPETENCE_EVALUATION_TYPE_TEXT:
                    foreach ($category->evaluationOptions as $pos_value => $option) {
                        $output .= '<td class="evaluation" align="center">';
                        $output .= join(', ', array_map(function($reviewer) {
                            return /*$reviewer->subject_title*/ $reviewer->subject_shorttitle ?: fullname($reviewer);
                        }, $option->reviewers));
                        $output .= '</td>';
                    }
                    break;

            }
			$output .= '</tr>';
		}

		$output .= '</table>';

		$output .= '<h3 class="detailed-for-student">'.block_exastud_get_string('detailedreview').'</h3>';

		$output .= '<table id="ratingtable" >';

		if ($lern_soz) {
			$output .= '<tr><td class="ratinguser">'.block_exastud_get_string('learn_and_sociale').'</td>
				<td class="ratingtext">'.format_text($lern_soz).'</td>
				</tr>';
		}

		foreach ($class_subjects as $subject) {
			$subjectData = block_exastud_get_review($class->id, $subject->id, $student->id);

			if (!$subjectData) {
				continue;
			}

			$output .= '<tr>';
            $output .= '<td class="ratinguser" valign="top">'.$subject->title.'</td>';
            $output .= '<td class="ratingtext">';
			$output .= format_text(@$subjectData->review);
			if (@$subjectData->niveau) {
				$output .= '<div><b>'.block_exastud_get_string('Niveau').':</b> ';
				$output .= (\block_exastud\global_config::get_niveau_option_title($subjectData->niveau) ?: $subjectData->niveau).'</div>';
			}
			if (@$subjectData->grade) {
				$value = @$template->get_grade_options()[$subjectData->grade] ?: $subjectData->grade;

				$output .= '<div><b>'.block_exastud_get_string('Note').':</b> '.$value.'</div>';
			}
			$output .= '</td></tr>';
		}

		$output .= '</table>';

		return $output;
	}

	function report_grades($class, $students) {
		$subjects = block_exastud_get_bildungsplan_subjects($class->bpid);

		ob_start();
		?>
		<style>
			#result td, th {
				text-align: center;
				width: 40px;
			}

			#result td:first-child, th:first-child {
				text-align: left;
				width: auto;
			}

		</style>
		<?php
		echo '<table border="1" id="result">';

		echo '<tr><th></th>';
		foreach ($subjects as $subject) {
			echo "<th>{$subject->shorttitle}</th>";
		}
		echo '</tr>';

		foreach ($students as $student) {
			echo "<tr><td>".fullname($student)."</td>";

			foreach ($subjects as $subject) {
				$subjectData = block_exastud_get_graded_review($class->id, $subject->id, $student->id);
				$value = @$subjectData->grade;

				echo "<td>{$value}</td>";
			}

			echo '</tr>';
		}

		echo '</table>';

		return ob_get_clean();
	}

	function back_button($url) {
		return $this->link_button(
			block_exastud\url::create($url),
			block_exastud_get_string('back'),
            array('class' => 'btn btn-default')
		);
	}

	function link_button($url, $label, $attributes = []) {
		return html_writer::tag('button', $label, $attributes + [
				'type' => 'button',
				'exa-type' => 'link',
				'href' => $url,
			]);
	}

	function last_modified($modifiedby, $timemodified) {
		if (is_scalar($modifiedby) && $modifiedby) {
			$modifiedby = g::$DB->get_record('user', array('id' => $modifiedby, 'deleted' => 0));
		}

		if (!$modifiedby) {
			return '';
		}

		return g::$OUTPUT->notification(block_exastud_get_string('last_edited_by', null, [
			'time' => userdate($timemodified), 'name' => fullname($modifiedby),
		]), g::$USER->id !== $modifiedby->id ? '' : 'notifymessage');
	}

	function heading($text) {
		$content = '<legend class="heading1">';
		$content .= $text;
		$content .= '</legend>';

		return $content;
	}

	function heading2($text) {
		$content = '<legend class="heading2">';
		$content .= $text;
		$content .= '</legend>';

		return $content;
	}

	/**
	 * in moodle33 pix_url was renamed to image_url
	 */
	public function image_url($imagename, $component = 'moodle') {
		if (method_exists(get_parent_class($this), 'image_url')) {
			return call_user_func_array(['parent', 'image_url'], func_get_args());
		} else {
			return call_user_func_array(['parent', 'pix_url'], func_get_args());
		}
	}
}
