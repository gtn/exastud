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

require __DIR__.'/inc.php';

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$action = optional_param('action', '', PARAM_TEXT);
block_exastud_require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_ADMIN);

$header = block_exastud_get_string('settings');
$url = new moodle_url('/blocks/exastud/configuration_global.php', array('courseid' => $courseid, 'action' => $action));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin'); // Needed for admin menu block
$output = block_exastud_get_renderer();

block_exastud_insert_default_entries();

$availablecategories = $DB->get_records_sql('SELECT id, title, parent
	FROM {block_exastudcate}
	ORDER BY sorting');

$availablesubjects = $DB->get_records_sql('SELECT id, title, shorttitle
	FROM {block_exastudsubjects}
	ORDER BY sorting');

$availableevalopts = $DB->get_records_sql('SELECT id, title
	FROM {block_exastudevalopt}
	ORDER BY sorting');

$availablebps = $DB->get_records_sql('SELECT id, sourceinfo, title
	FROM {block_exastudbp}
	ORDER BY sorting');

foreach ($availablebps as $availablebp) {
	$availablebp->disabled = !block_exastud_can_edit_bp($availablebp);
}
$availablesubjects = block_exastud_get_bildungsplan_subjects(optional_param('bpid', 0, PARAM_INT));

if ($action == 'save-categories') {
	if (!confirm_sesskey()) {
		die(block_exastud_get_string('badsessionkey'));
	}

	$required_params = array(
            'id' => PARAM_INT,
            'title' => PARAM_TEXT,
    );
	if (!block_exastud_is_bw_active()) {
	    $required_params['parent'] = PARAM_INT;
    }
	$items = block_exastud\param::required_array('items',
		array(PARAM_INT => (object)$required_params,
    ));
	
	$todelete = $availablecategories;
	$sorting = 0;
	$newAddToClasses = array();
	foreach ($items as $item) {
		$sorting++;
		$item->sorting = $sorting;

		if (isset($availablecategories[$item->id])) {
			// update
			$DB->update_record('block_exastudcate', $item);
			// only if updated
			if ($availablecategories[$item->id]->title != $item->title) {
                \block_exastud\event\competence_updated::log(['objectid' => $item->id,
                        'other' => ['title' => $item->title, 'oldtitle' => $availablecategories[$item->id]->title]]);
            }
			unset($todelete[$item->id]);
		} else {
			// insert
			$newid = $DB->insert_record('block_exastudcate', $item);
            $newAddToClasses[] = $newid;
            \block_exastud\event\competence_created::log(['objectid' => $newid, 'other' => ['title' => $item->title]]);
		}
	}

	foreach ($todelete as $item) {
        $DB->delete_records('block_exastudclasscate', array('categoryid' => $item->id, 'categorysource' => 'exastud'));
        $DB->delete_records('block_exastudreviewpos', array('categoryid' => $item->id, 'categorysource' => 'exastud'));
		$DB->delete_records('block_exastudcate', array('id' => $item->id));
        \block_exastud\event\competence_deleted::log(['objectid' => $item->id, 'other' => ['title' => $item->title]]);
	}

	// update classes with checked 'always_basiccategories'
    if (count($newAddToClasses) > 0) {
        if (block_exastud_get_plugin_config('category_addbasictoclassalways')) {
            //$updateClasses = $DB->get_records('block_exastudclass', ['always_basiccategories' => 1]);
            $updateClasses = $DB->get_records('block_exastudclass');
            foreach ($updateClasses as $cId => $class) {
                foreach ($newAddToClasses as $catId) {
                    $newrelation = [
                            'classid' => $class->id,
                            'categoryid' => $catId,
                            'categorysource' => 'exastud'
                    ];
                    $newid = $DB->insert_record('block_exastudclasscate', $newrelation);
                }
            }
        }
    }

	echo 'ok';

	exit;
}

if ($action == 'save-subjects') {
	if (!confirm_sesskey()) {
		die(block_exastud_get_string('badsessionkey'));
	}

	$items = block_exastud\param::required_array('items',
		array(PARAM_INT => (object)array(
			'id' => PARAM_INT,
			'title' => PARAM_TEXT,
			'shorttitle' => PARAM_TEXT,
			'is_main' => PARAM_TEXT,
			'relevant' => PARAM_TEXT,
			'is_best' => PARAM_TEXT,
//			'relevant_rs' => PARAM_TEXT,
			// 'always_print' => PARAM_BOOL,
		))
	);

	$bpid = required_param('bpid', PARAM_INT);

	$todelete = $availablesubjects;
	$sorting = 0;
	foreach ($items as $item) {
		$sorting++;
		$item->sorting = $sorting;
        $item->not_relevant = ($item->relevant ? 0 : 1); // inverse from form to field (relevant -> not_relevant)
        $item->is_main = intval($item->is_main);
        $item->is_best = intval($item->is_best);
//        $item->not_relevant_rs = ($item->relevant_rs ? 0 : 1);
		if (isset($availablesubjects[$item->id])) {
            if (!block_exastud_can_edit_subject($availablesubjects[$item->id])) {
                unset($todelete[$item->id]);
                continue;
            }
            if (block_exastud_is_bw_subject($availablesubjects[$item->id])) {
                // bw can not change title and short title (use old titles)
                $item->title = $availablesubjects[$item->id]->title;
                $item->shorttitle = $availablesubjects[$item->id]->shorttitle;
            }
			// update
			$DB->update_record('block_exastudsubjects', $item);
            // only if updated
            if ($availablesubjects[$item->id]->title != $item->title) {
                \block_exastud\event\competence_updated::log(['objectid' => $item->id,
                        'other' => ['title' => $item->title, 'oldtitle' => $availablesubjects[$item->id]->title]]);
            }
			unset($todelete[$item->id]);
		} else {
			// insert
            // only for non bp (save button is hidden, but we need to check requests also)
            $bpData = $DB->get_record('block_exastudbp', ['id' => $bpid]);
            if (!block_exastud_is_bw_active() || !block_exastud_is_bw_bp($bpData)) {
                $item->bpid = $bpid;
                $newid = $DB->insert_record('block_exastudsubjects', $item);
                \block_exastud\event\subject_created::log(['objectid' => $newid, 'other' => ['title' => $item->title]]);
            }
		}
	}

	foreach ($todelete as $item) {
		$DB->delete_records('block_exastudsubjects', ['id' => $item->id]);
        \block_exastud\event\subject_deleted::log(['objectid' => $item->id, 'other' => ['title' => $item->title]]);
	}

	echo 'ok';

	exit;
}

if ($action == 'save-evalopts') {
	if (!confirm_sesskey()) {
		die(block_exastud_get_string('badsessionkey'));
	}

	$items = block_exastud\param::required_array('items',
		array(PARAM_INT => (object)array(
			'id' => PARAM_INT,
			'title' => PARAM_TEXT,
		),
		));

	$todelete = $availableevalopts;
	$sorting = 0;
	foreach ($items as $item) {
		$sorting++;
		$item->sorting = $sorting;

		if (isset($availableevalopts[$item->id])) {
			// update
			$DB->update_record('block_exastudevalopt', $item);
            // only if updated
            if ($availableevalopts[$item->id]->title != $item->title) {
                \block_exastud\event\gradingoption_updated::log(['objectid' => $item->id,
                        'other' => ['title' => $item->title, 'oldtitle' => $availableevalopts[$item->id]->title]]);
            }
			unset($todelete[$item->id]);
		} else {
			// insert
            $newid = $DB->insert_record('block_exastudevalopt', $item);
            \block_exastud\event\gradingoption_created::log(['objectid' => $newid, 'other' => ['title' => $item->title]]);
		}
	}

	foreach ($todelete as $item) {
		$DB->delete_records('block_exastudevalopt', array('id' => $item->id));
        \block_exastud\event\gradingoption_deleted::log(['objectid' => $item->id, 'other' => ['title' => $item->title]]);
	}

	echo 'ok';

	exit;
}

if ($action == 'save-bps') {
	if (!confirm_sesskey()) {
		die(block_exastud_get_string('badsessionkey'));
	}
	$items = block_exastud\param::required_array('items',
		array(PARAM_INT => (object)array(
			'id' => PARAM_INT,
			'title' => PARAM_TEXT,
		),
		));

	$todelete = $availablebps;
	$sorting = 0;
	foreach ($items as $item) {
		$sorting++;
		$item->sorting = $sorting;

		if (isset($availablebps[$item->id])) {
			// update
			$DB->update_record('block_exastudbp', $item);
            // only if updated
            if ($availablebps[$item->id]->title != $item->title) {
                \block_exastud\event\educationplan_updated::log(['objectid' => $item->id,
                        'other' => ['title' => $item->title, 'oldtitle' => $availablebps[$item->id]->title]]);
            }
			unset($todelete[$item->id]);
		} else {
			// insert
			$newid = $DB->insert_record('block_exastudbp', $item);
            \block_exastud\event\educationplan_created::log(['objectid' => $newid, 'other' => ['title' => $item->title]]);
		}
	}

	foreach ($todelete as $item) {
		$item = $DB->get_record('block_exastudbp', ['id' => $item->id]);
		if (!$item) {
			continue;
		}
		if (!block_exastud_can_edit_bp($item)) {
			continue;
		}

		$DB->delete_records('block_exastudbp', ['id' => $item->id]);
        \block_exastud\event\educationplan_deleted::log(['objectid' => $item->id, 'other' => ['title' => $item->title]]);
	}

	echo 'ok';

	exit;
}

block_exastud_custom_breadcrumb($PAGE);

if ($action == 'categories') {
	echo $output->header(['competencies'], ['content_title' => block_exastud_get_string('pluginname')], true/*['settings', ['id' => 'categories', 'name' => block_exastud_trans("de:Kompetenzen")]]*/);

	foreach ($availablecategories as $cat) {
        $cat->deleteButtonMessage = '';
        $relatedToClass = $DB->get_fieldset_sql('SELECT DISTINCT c.title 
                                                  FROM {block_exastudclasscate} cat
                                                    JOIN {block_exastudclass} c ON c.id = cat.classid
                                                  WHERE cat.categoryid = ? AND cat.categorysource = ?',
                array($cat->id, 'exastud'));
        if ($relatedToClass) {
            $cat->deleteButtonMessage .= block_exastud_get_string('this_category_related_to_classes').': '.implode(', ', $relatedToClass);
        }
        $reviewed = $DB->get_fieldset_sql('SELECT *
                                                  FROM {block_exastudreviewpos} rev                                                  
                                                  WHERE rev.categoryid = ? 
                                                      AND rev.categorysource = ?',
                array($cat->id, 'exastud'));
        if ($reviewed) {
            $cat->deleteButtonMessage .= ($cat->deleteButtonMessage ? "\r\n" : "").block_exastud_get_string('this_category_reviewed_for_student');
        }
        if (!$relatedToClass && !$reviewed) {
            $cat->canDelete = true;
        }
    }

	?>
	<script>
		var exa_list_items = <?php echo json_encode(array_values($availablecategories) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
	</script>
	<div id="exa-list">
		<ul exa="items" data-titles="<?php
                $column_titles = json_encode(array('111', '2222')/*, JSON_HEX_QUOT|JSON_HEX_APOS */);
                //$column_titles = str_replace("\u0022", "\\\"", $column_titles);
                //$column_titles = str_replace("\u0027", "\\'", $column_titles);
                echo htmlspecialchars($column_titles, ENT_QUOTES);
                ?>">
			<li>
				<input type="text" name="title"/>
                <?php
                if (!block_exastud_is_bw_active()) {
                    // main functionality in JS
                    echo '<select exa="parent-select" name="parent" title="'.block_exastud_get_string('parent').'">';
                    echo '<option value=""></option>';
                    $sql = 'SELECT * FROM {block_exastudcate} WHERE parent = 0 OR parent IS NULL';
                    $rootCategories = $DB->get_records_sql($sql);
                    foreach ($rootCategories as $rcat) {
                        echo '<option value="'.$rcat->id.'">'.$rcat->title.'</option>';
                    }
                    echo '</select>';
                }
                ?>
				<button exa="delete-button" class="btn btn-default"><?php echo block_exastud_get_string('delete'); ?></button>
			</li>
		</ul>
		<div exa="new-item">
			<input type="text" name="title"/>
			<input type="button" exa="new-button" class="btn btn-default" value="<?php echo block_exastud_get_string('add'); ?>">
		</div>
		<div exa="save">
			<input type="button" exa="save-button" class="btn btn-default" value="<?php echo block_exastud_get_string('savechanges'); ?>" class="btn btn-default">
            <?php
                echo $output->link_button($CFG->wwwroot.'/blocks/exastud/configuration_categories.php?courseid='.$courseid,
                    block_exastud_get_string('button_interdisciplinary_skills'), ['class' => 'btn btn-default']);
            ?>
		</div>
	</div>
	<?php

	echo $output->footer();
	exit;
}

if ($action == 'subjects') {
	echo $output->header(['education_plans'], ['content_title' => block_exastud_get_string('pluginname')], true/*['settings', ['id' => 'bps', 'name' => block_exastud_trans(['de:Fachbezeichnungen', 'de_at:Gegenstände'])]]*/);

	$bp = $DB->get_record('block_exastudbp', ['id' => required_param('bpid', PARAM_INT)]);

	//$canEdit = block_exastud_can_edit_bp($bp);
    $canEdit = true; // 24.12.2018, look later (28.04.2020)

	/*
	if (block_exastud_get_plugin_config('always_check_default_values')) {
		$defaultSubjects = (array)block_exastud_get_plugin_config('default_subjects');

		foreach ($availablesubjects as $subject) {
			$subject->disabled = in_array($subject->title, $defaultSubjects);
		}
	}
	*/

	$originalCanEdit = $canEdit;
    foreach ($availablesubjects as $subject) {
        if (block_exastud_can_edit_subject($subject)) {
            $canEdit = $originalCanEdit; // previous conditions
        } else {
            $canEdit = false;
        }
        $subject->relevant = ($subject->not_relevant ? 0 : 1); // inverse, because field is 'not_relevant', in the form - 'relevant' value
//        $subject->relevant_rs = ($subject->not_relevant_rs ? 0 : 1);
	    if (!$canEdit) {
			$subject->disabled = true;
		}
		$subject->canDelete = block_exastud_can_delete_subject($subject);
		$subject->titleReadonly = block_exastud_is_bw_subject($subject);
	}

	echo "<h2>".block_exastud_get_string('class_educationplan').": {$bp->title}</h2>";
	?>

	<script>
		var exa_list_items = <?php echo json_encode(array_values($availablesubjects) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
	</script>
	<div id="exa-list" <?php if (!$canEdit) { echo 'exa-sorting="false"'; } ?>>
		<div class="header">
			<div for-field="title"><?php echo block_exastud_get_string('bp_title'); ?></div>
			<div for-field="shorttitle"><?php echo block_exastud_get_string('bp_shorttitle'); ?></div>
            <?php if (block_exastud_is_bw_active()) { ?>
			<div for-field="relevant"><span for-field="K">(K)</span><span for-field="M">(M)</span><span for-field="B">(B)</span><sup>*</sup>
            </div>
            <?php } ?>
<!--			<div for-field="relevant_rs">--><?php //echo block_exastud_get_string('subject_category_m_rs'); ?><!--</div>-->
			<!-- div for-field="always_print"><?php echo block_exastud_get_string('bp_leb_always_print'); ?></div -->
		</div>
		<ul exa="items">
			<li>
                <input type="text" name="title"/>
                <input type="text" name="shorttitle"/>
                <?php if (block_exastud_is_bw_active()) { ?>
                    <input type="checkbox" name="is_main" value="1" />
                    <input type="checkbox" name="relevant" value="1" />
                    <input type="checkbox" name="is_best" value="1" />
				<?php } else { ?>
                    <input type="hidden" name="is_main" value="0" />
                    <input type="hidden" name="relevant" value="0" />
                    <input type="hidden" name="is_best" value="0" />
                <?php } ?>
				<!-- input type="checkbox" name="always_print" value="1"/ -->
				<button exa="delete-button" class="btn btn-default"><?php echo block_exastud_get_string('delete'); ?></button>
			</li>
		</ul>
		<?php if ($canEdit && !block_exastud_is_bw_active()) { ?>
		<div exa="new-item">
			<input type="text" name="title"/>
			<input type="text" name="shorttitle"/>
            <?php if (block_exastud_is_bw_active()) { ?>
                <input type="checkbox" name="is_main" value="1" />
                <input type="checkbox" name="relevant" value="1" />
                <input type="checkbox" name="is_best" value="1" />
            <?php } else { ?>
                <input type="hidden" name="is_main" value="0" />
                <input type="hidden" name="relevant" value="0" />
                <input type="hidden" name="is_best" value="0" />
            <?php } ?>
			<!-- input type="checkbox" name="always_print" value="1"/ -->
			<input type="button" exa="new-button" class="btn btn-default" value="<?php echo block_exastud_get_string('add'); ?>">
		</div>
        <div class="block-exastud-subject-legend">
            <sup>*</sup>&nbsp;K = Kernfach<br>
            M = maßgebliches Fach<br>
            B = bestes Fach aus ..<br>
        </div>
		<?php }
		?>
		<div exa="save">
			<?php if ($canEdit) { ?>
			<input type="button" exa="save-button" class="btn btn-default" value="<?php echo block_exastud_get_string('savechanges'); ?>">
			<?php } ?>
			<?php
			echo $output->back_button($CFG->wwwroot.'/blocks/exastud/configuration_global.php?courseid='.$courseid.'&action=bps');
			?>
		</div>
	</div>
	<?php

	echo $output->footer();
	exit;
}

if ($action == 'evalopts') {
    foreach ($availableevalopts as $opt) {
        $opt->canDelete = true;
    }
	echo $output->header(['grading'], ['content_title' => block_exastud_get_string('pluginname')], true/*['settings', ['id' => 'evalopts', 'name' => block_exastud_trans("de:Bewertungsskala")]]*/);
	?>
	<script>
		var exa_list_items = <?php echo json_encode(array_values($availableevalopts) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
	</script>
	<div id="exa-list">
		<ul exa="items">
			<li>
				<input type="text" name="title"/>
				<button exa="delete-button" class="btn btn-default"><?php echo block_exastud_get_string('delete'); ?></button>
			</li>
		</ul>
		<div exa="new-item">
			<input type="text" name="title"/>
			<input type="button" exa="new-button" class="btn btn-default" value="<?php echo block_exastud_get_string('add'); ?>">
		</div>
		<div exa="save">
			<input type="button" exa="save-button" class="btn btn-default" value="<?php echo block_exastud_get_string('savechanges'); ?>">
		</div>
	</div>
	<?php

	echo $output->footer();
	exit;
}

if ($action == 'bps') {
	echo $output->header(['education_plans'], ['content_title' => block_exastud_get_string('pluginname')], true/*['settings', ['id' => 'bps', 'name' => block_exastud_trans("de:Bildungspläne")]]*/);

	?>
	<script>
		var exa_list_items = <?php echo json_encode(array_values($availablebps) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
	</script>
	<div id="exa-list">
		<ul exa="items">
			<li>
				<input type="text" name="title"/>
				<button exa="delete-button" class="btn btn-default"><?php echo block_exastud_get_string('delete'); ?></button>
				<button exa="subjects-button" class="btn btn-default"><?php echo block_exastud_get_string('Subjects'); ?></button>
			</li>
		</ul>
		<div exa="new-item">
			<input type="text" name="title"/>
			<input type="button" exa="new-button" class="btn btn-default" value="<?php echo block_exastud_get_string('add'); ?>">
		</div>
		<div exa="save">
			<input type="button" exa="save-button" class="btn btn-default" value="<?php echo block_exastud_get_string('savechanges'); ?>">
		</div>
	</div>
	<?php

	echo $output->footer();
	exit;
}

die('wrong action '.$action);
