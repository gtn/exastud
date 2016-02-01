<?php

defined('MOODLE_INTERNAL') || die;

class block_exastud_renderer extends plugin_renderer_base {

	public function print_esr_table (html_table $table) {
		// prepare table data and populate missing properties with reasonable defaults
		if (!empty($table->align)) {
			foreach ($table->align as $key => $aa) {
				if ($aa) {
					$table->align[$key] = 'text-align:'. fix_align_rtl($aa) .';';  // Fix for RTL languages
				} else {
					$table->align[$key] = null;
				}
			}
		}
		if (!empty($table->size)) {
			foreach ($table->size as $key => $ss) {
				if ($ss) {
					$table->size[$key] = 'width:'. $ss .';';
				} else {
					$table->size[$key] = null;
				}
			}
		}
		if (!empty($table->wrap)) {
			foreach ($table->wrap as $key => $ww) {
				if ($ww) {
					$table->wrap[$key] = 'white-space:nowrap;';
				} else {
					$table->wrap[$key] = '';
				}
			}
		}
		if (!empty($table->head)) {
			foreach ($table->head as $key => $val) {
				if (!isset($table->align[$key])) {
					$table->align[$key] = null;
				}
				if (!isset($table->size[$key])) {
					$table->size[$key] = null;
				}
				if (!isset($table->wrap[$key])) {
					$table->wrap[$key] = null;
				}

			}
		}
		if (empty($table->attributes['class'])) {
			$table->attributes['class'] = 'esr_table';
		}
		if (!empty($table->tablealign)) {
			$table->attributes['class'] .= ' boxalign' . $table->tablealign;
		}

		// explicitly assigned properties override those defined via $table->attributes
		$table->attributes['class'] = trim($table->attributes['class']);
		$attributes = array_merge($table->attributes, array(
				'id'			=> $table->id,
				'width'		 => $table->width,
				'summary'	   => $table->summary,
				'cellpadding'   => $table->cellpadding,
				'cellspacing'   => $table->cellspacing,
			));
		$output = html_writer::start_tag('table', $attributes) . "\n";

		$countcols = 0;

		if (!empty($table->head)) {
			$countcols = count($table->head);

			$output .= html_writer::start_tag('thead', array()) . "\n";
			$output .= html_writer::start_tag('tr', array()) . "\n";
			$keys = array_keys($table->head);
			$lastkey = end($keys);

			foreach ($table->head as $key => $heading) {
				// Convert plain string headings into html_table_cell objects
				if (!($heading instanceof html_table_cell)) {
					$headingtext = $heading;
					$heading = new html_table_cell();
					$heading->text = $headingtext;
					$heading->header = true;
				}

				if ($heading->header !== false) {
					$heading->header = true;
				}

				if ($heading->header && empty($heading->scope)) {
					$heading->scope = 'col';
				}

				if (isset($table->headspan[$key]) && $table->headspan[$key] > 1) {
					$heading->colspan = $table->headspan[$key];
					$countcols += $table->headspan[$key] - 1;
				}

				if ($key == $lastkey) {
					$heading->attributes['class'] .= ' lastcol';
				}
				if (isset($table->colclasses[$key])) {
					$heading->attributes['class'] .= ' ' . $table->colclasses[$key];
				}
				$heading->attributes['class'] = trim($heading->attributes['class']);
				$attributes = array_merge($heading->attributes, array(
						'style'	 => $table->align[$key] . $table->size[$key] . $heading->style,
						'scope'	 => $heading->scope,
						'colspan'   => $heading->colspan,
					));

				$tagtype = 'td';
				if ($heading->header === true) {
					$tagtype = 'th';
				}
				$output .= html_writer::tag($tagtype, $heading->text, $attributes) . "\n";
			}
			$output .= html_writer::end_tag('tr') . "\n";
			$output .= html_writer::end_tag('thead') . "\n";

			if (empty($table->data)) {
				// For valid XHTML strict every table must contain either a valid tr
				// or a valid tbody... both of which must contain a valid td
				$output .= html_writer::start_tag('tbody', array('class' => 'empty'));
				$output .= html_writer::tag('tr', html_writer::tag('td', '', array('colspan'=>count($table->head))));
				$output .= html_writer::end_tag('tbody');
			}
		}

		if (!empty($table->data)) {
			$oddeven	= 1;
			$keys	   = array_keys($table->data);
			$lastrowkey = end($keys);
			$output .= html_writer::start_tag('tbody', array());

			foreach ($table->data as $key => $row) {
				if (($row === 'hr') && ($countcols)) {
					$output .= html_writer::tag('td', html_writer::tag('div', '', array('class' => 'tabledivider')), array('colspan' => $countcols));
					$oddeven = 1;
				} else {
					// Convert array rows to html_table_rows and cell strings to html_table_cell objects
					if (!($row instanceof html_table_row)) {
						$newrow = new html_table_row();

						foreach ($row as $cell) {
							if (!($cell instanceof html_table_cell)) {
								$cell = new html_table_cell($cell);
							}
							$newrow->cells[] = $cell;
						}
						$row = $newrow;
					}

					$oddeven = isset($row->oddeven) ? $row->oddeven : ($oddeven ? 0 : 1);
					if (isset($table->rowclasses[$key])) {
						$row->attributes['class'] .= ' ' . $table->rowclasses[$key];
					}

					$row->attributes['class'] .= ' r' . (int)$oddeven;
					if ($key == $lastrowkey) {
						$row->attributes['class'] .= ' lastrow';
					}

					$output .= html_writer::start_tag('tr', array('class' => trim($row->attributes['class']), 'style' => $row->style, 'id' => $row->id)) . "\n";
					$keys2 = array_keys($row->cells);
					$lastkey = end($keys2);

					$gotlastkey = false; //flag for sanity checking
					foreach ($row->cells as $key => $cell) {
						if ($gotlastkey) {
							//This should never happen. Why do we have a cell after the last cell?
							mtrace("A cell with key ($key) was found after the last key ($lastkey)");
						}

						if (!($cell instanceof html_table_cell)) {
							$mycell = new html_table_cell();
							$mycell->text = $cell;
							$cell = $mycell;
						}

						if (($cell->header === true) && empty($cell->scope)) {
							$cell->scope = 'row';
						}

						if (isset($table->colclasses[$key])) {
							$cell->attributes['class'] .= ' ' . $table->colclasses[$key];
						}

						$cell->attributes['class'] .= ' cell c' . $key;
						if ($key == $lastkey) {
							$cell->attributes['class'] .= ' lastcol';
							$gotlastkey = true;
						}
						$tdstyle = '';
						$tdstyle .= isset($table->align[$key]) ? $table->align[$key] : '';
						$tdstyle .= isset($table->size[$key]) ? $table->size[$key] : '';
						$tdstyle .= isset($table->wrap[$key]) ? $table->wrap[$key] : '';
						$cell->attributes['class'] = trim($cell->attributes['class']);
						$tdattributes = array_merge($cell->attributes, array(
								'style' => $tdstyle . $cell->style,
								'colspan' => $cell->colspan,
								'rowspan' => $cell->rowspan,
								'id' => $cell->id,
								'abbr' => $cell->abbr,
								'scope' => $cell->scope,
							));
						$tagtype = 'td';
						if ($cell->header === true) {
							$tagtype = 'th';
						}
						$output .= html_writer::tag($tagtype, $cell->text, $tdattributes) . "\n";
					}
				}
				$output .= html_writer::end_tag('tr') . "\n";
			}
			$output .= html_writer::end_tag('tbody') . "\n";
		}
		$output .= html_writer::end_tag('table') . "\n";

		return $output;
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
					return $reviewer->subject?$reviewer->subject.' ('.fullname($reviewer).')':fullname($reviewer);
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