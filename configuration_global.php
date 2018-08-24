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
require_login($courseid);

block_exastud_require_global_cap(BLOCK_EXASTUD_CAP_ADMIN);

$header = block_exastud_get_string('settings');
$url = new moodle_url('/blocks/exastud/configuration_global.php', array('courseid' => $courseid, 'action' => $action));
$PAGE->set_url($url);
$output = block_exastud_get_renderer();

block_exastud_insert_default_entries();

$availablecategories = $DB->get_records_sql('SELECT id, title
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

	$items = block_exastud\param::required_array('items',
		array(PARAM_INT => (object)array(
			'id' => PARAM_INT,
			'title' => PARAM_TEXT,
		),
		));

	$todelete = $availablecategories;
	$sorting = 0;
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
            \block_exastud\event\competence_created::log(['objectid' => $newid, 'other' => ['title' => $item->title]]);
		}
	}

	foreach ($todelete as $item) {
		$DB->delete_records('block_exastudcate', array('id' => $item->id));
        \block_exastud\event\competence_deleted::log(['objectid' => $item->id, 'other' => ['title' => $item->title]]);
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
			// 'always_print' => PARAM_BOOL,
		))
	);

	$bpid = required_param('bpid', PARAM_INT);

	$todelete = $availablesubjects;
	$sorting = 0;
	foreach ($items as $item) {
		$sorting++;
		$item->sorting = $sorting;

		if (isset($availablesubjects[$item->id])) {
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
			$item->bpid = $bpid;
			$newid = $DB->insert_record('block_exastudsubjects', $item);
            \block_exastud\event\subject_created::log(['objectid' => $newid, 'other' => ['title' => $item->title]]);
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

if ($action == 'categories') {
	echo $output->header(['settings', ['id' => 'categories', 'name' => block_exastud_trans("de:Kompetenzen")]]);

	?>
	<script>
		var exa_list_items = <?php echo json_encode(array_values($availablecategories) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
	</script>
	<div id="exa-list">
		<ul exa="items">
			<li>
				<input type="text" name="title"/>
				<button exa="delete-button"><?php echo block_exastud_get_string('delete'); ?></button>
			</li>
		</ul>
		<div exa="new-item">
			<input type="text" name="title"/>
			<input type="button" exa="new-button" value="<?php echo block_exastud_get_string('add'); ?>">
		</div>
		<div exa="save">
			<input type="button" exa="save-button" value="<?php echo block_exastud_get_string('savechanges'); ?>">
		</div>
	</div>
	<?php

	echo $output->footer();
	exit;
}

if ($action == 'subjects') {
	echo $output->header(['settings', ['id' => 'bps', 'name' => block_exastud_trans(['de:Fachbezeichnungen', 'de_at:Gegenstände'])]]);

	$bp = $DB->get_record('block_exastudbp', ['id' => required_param('bpid', PARAM_INT)]);

	$canEdit = block_exastud_can_edit_bp($bp);

	/*
	if (block_exastud_get_plugin_config('always_check_default_values')) {
		$defaultSubjects = (array)block_exastud_get_plugin_config('default_subjects');

		foreach ($availablesubjects as $subject) {
			$subject->disabled = in_array($subject->title, $defaultSubjects);
		}
	}
	*/

	if (!$canEdit) {
		foreach ($availablesubjects as $subject) {
			$subject->disabled = true;
		}
	}

	echo "<h2>".block_exastud_trans('de:Bildungsplan').": {$bp->title}</h2>";
	?>

	<script>
		var exa_list_items = <?php echo json_encode(array_values($availablesubjects) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
	</script>
	<div id="exa-list" <?php if (!$canEdit) { echo 'exa-sorting="false"'; } ?>>
		<div class="header">
			<div for-field="title"><?php echo block_exastud_trans(['de:Bezeichnung', 'en:Name']); ?></div>
			<div for-field="shorttitle"><?php echo block_exastud_trans(['de:Kurzbezeichnung', 'en:Shortname']); ?></div>
			<!-- div for-field="always_print"><?php echo block_exastud_trans(['de:Immer im LEB drucken', 'en:Always print']); ?></div -->
		</div>
		<ul exa="items">
			<li>
				<input type="text" name="title"/>
				<input type="text" name="shorttitle"/>
				<!-- input type="checkbox" name="always_print" value="1"/ -->
				<button exa="delete-button"><?php echo block_exastud_get_string('delete'); ?></button>
			</li>
		</ul>
		<?php if ($canEdit) { ?>
		<div exa="new-item">
			<input type="text" name="title"/>
			<input type="text" name="shorttitle"/>
			<!-- input type="checkbox" name="always_print" value="1"/ -->
			<input type="button" exa="new-button" value="<?php echo block_exastud_get_string('add'); ?>">
		</div>
		<?php } ?>
		<div exa="save">
			<?php if ($canEdit) { ?>
			<input type="button" exa="save-button" value="<?php echo block_exastud_get_string('savechanges'); ?>">
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
	echo $output->header(['settings', ['id' => 'evalopts', 'name' => block_exastud_trans("de:Bewertungsskala")]]);

	?>
	<script>
		var exa_list_items = <?php echo json_encode(array_values($availableevalopts) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
	</script>
	<div id="exa-list">
		<ul exa="items">
			<li>
				<input type="text" name="title"/>
				<button exa="delete-button"><?php echo block_exastud_get_string('delete'); ?></button>
			</li>
		</ul>
		<div exa="new-item">
			<input type="text" name="title"/>
			<input type="button" exa="new-button" value="<?php echo block_exastud_get_string('add'); ?>">
		</div>
		<div exa="save">
			<input type="button" exa="save-button" value="<?php echo block_exastud_get_string('savechanges'); ?>">
		</div>
	</div>
	<?php

	echo $output->footer();
	exit;
}

if ($action == 'bps') {
	echo $output->header(['settings', ['id' => 'bps', 'name' => block_exastud_trans("de:Bildungspläne")]]);

	?>
	<script>
		var exa_list_items = <?php echo json_encode(array_values($availablebps) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
	</script>
	<div id="exa-list">
		<ul exa="items">
			<li>
				<input type="text" name="title"/>
				<button exa="delete-button"><?php echo block_exastud_get_string('delete'); ?></button>
				<button exa="subjects-button"><?php echo block_exastud_trans('de:Fachbezeichnungen'); ?></button>
			</li>
		</ul>
		<div exa="new-item">
			<input type="text" name="title"/>
			<input type="button" exa="new-button" value="<?php echo block_exastud_get_string('add'); ?>">
		</div>
		<div exa="save">
			<input type="button" exa="save-button" value="<?php echo block_exastud_get_string('savechanges'); ?>">
		</div>
	</div>
	<?php

	echo $output->footer();
	exit;
}

die('wrong action '.$action);
