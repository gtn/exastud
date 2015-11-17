<?php

require "inc.php";

$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$action = optional_param('action', '', PARAM_TEXT);
require_login($courseid);

block_exastud_require_global_cap(block_exastud::CAP_ADMIN);

$header = block_exastud_get_string('settings');
$url = new moodle_url('/blocks/exastud/configuration_global.php', array('courseid'=>$courseid));
$PAGE->set_url($url);

block_exastud_insert_default_entries();

$availablecategories = $DB->get_records_sql('SELECT id, title
    FROM {block_exastudcate}
    ORDER BY sorting');

$availablesubjects = $DB->get_records_sql('SELECT id, title
    FROM {block_exastudsubjects}
    ORDER BY title');

$availableevalopts = $DB->get_records_sql('SELECT id, title
    FROM {block_exastudevalopt}
    ORDER BY sorting');

if ($action == 'save-categories') {
	if(!confirm_sesskey()) {
		die(get_string("badsessionkey","block_exastud"));
	}
	
	$items = block_exastud\param::required_array('items',
            array(PARAM_INT => (object)array(
                'id' => PARAM_INT,
                'title' => PARAM_TEXT
            )
        ));

	$todelete = $availablecategories;
	$sorting = 0;
	foreach ($items as $item) {
	    $sorting++;
	    $item->sorting = $sorting;
	    
	    if (isset($availablecategories[$item->id])) {
	        // update
	        $DB->update_record('block_exastudcate', $item);
	        
	        unset($todelete[$item->id]);
	    } else {
	        // insert
	        $DB->insert_record('block_exastudcate', $item);
	    }
	}
	
	foreach ($todelete as $item) {
	    $DB->delete_records('block_exastudcate', array('id'=>$item->id));
	}
	
	echo 'ok';
	
	exit;
}

if ($action == 'save-subjects') {
	if(!confirm_sesskey()) {
		die(get_string("badsessionkey","block_exastud"));
	}
	
	$items = block_exastud\param::required_array('items',
            array(PARAM_INT => (object)array(
                'id' => PARAM_INT,
                'title' => PARAM_TEXT
            )
        ));

	$todelete = $availablesubjects;
	$sorting = 0;
	foreach ($items as $item) {
	    $sorting++;
	    $item->sorting = $sorting;
	    
	    if (isset($availablesubjects[$item->id])) {
	        // update
	        $DB->update_record('block_exastudsubjects', $item);
	        
	        unset($todelete[$item->id]);
	    } else {
	        // insert
	        $DB->insert_record('block_exastudsubjects', $item);
	    }
	}
	
	foreach ($todelete as $item) {
	    $DB->delete_records('block_exastudsubjects', array('id'=>$item->id));
	}
	
	echo 'ok';
	
	exit;
}

if ($action == 'save-evalopts') {
	if(!confirm_sesskey()) {
		die(get_string("badsessionkey","block_exastud"));
	}
	
	$items = block_exastud\param::required_array('items',
            array(PARAM_INT => (object)array(
                'id' => PARAM_INT,
                'title' => PARAM_TEXT
            )
        ));

	$todelete = $availableevalopts;
	$sorting = 0;
	foreach ($items as $item) {
	    $sorting++;
	    $item->sorting = $sorting;
	    
	    if (isset($availableevalopts[$item->id])) {
	        // update
	        $DB->update_record('block_exastudevalopt', $item);
	        
	        unset($todelete[$item->id]);
	    } else {
	        // insert
	        $DB->insert_record('block_exastudevalopt', $item);
	    }
	}
	
	foreach ($todelete as $item) {
	    $DB->delete_records('block_exastudevalopt', array('id'=>$item->id));
	}
	
	echo 'ok';
	
	exit;
}

if ($action == 'categories') {
    block_exastud_print_header(['settings', ['id'=>'categories', 'name'=>block_exastud::t("de:Kompetenzen")]]);
    
    ?>
    <script>
    	var exa_list_items = <?php echo json_encode(array_values($availablecategories) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
    </script>
    <div id="exa-list">
        <ul exa="items">
        	<li><input type="text" /> <span exa="delete-button"><?php echo get_string('delete'); ?></span></li>
        </ul>
        <div exa="new-item">
        	<input type="text" exa="new-text" />
        	<input type="button" exa="new-button" value="<?php echo get_string('add'); ?>">
        </div>
        <div exa="save">
        	<input type="button" exa="save-button" value="<?php echo get_string('savechanges'); ?>">
    	</div>
    </div>
    <?php
    
    block_exastud_print_footer();
    exit;
}

if ($action == 'subjects') {
    block_exastud_print_header(['settings', ['id'=>'subjects', 'name'=>block_exastud::t(['de:Fachbezeichnungen', 'de_at:Gegenstände'])]]);
    
    ?>
    <script>
    	var exa_list_items = <?php echo json_encode(array_values($availablesubjects) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
    </script>
    <div id="exa-list" exa-sorting="false">
        <ul exa="items">
        	<li><input type="text" /> <span exa="delete-button"><?php echo get_string('delete'); ?></span></li>
        </ul>
        <div exa="new-item">
        	<input type="text" exa="new-text" />
        	<input type="button" exa="new-button" value="<?php echo get_string('add'); ?>">
        </div>
        <div exa="save">
        	<input type="button" exa="save-button" value="<?php echo get_string('savechanges'); ?>">
    	</div>
    </div>
    <?php
    
    block_exastud_print_footer();
    exit;
}

if ($action == 'evalopts') {
    block_exastud_print_header(['settings', ['id'=>'evalopts', 'name'=>block_exastud::t("de:Bewertungsskala")]]);
    
    ?>
    <script>
    	var exa_list_items = <?php echo json_encode(array_values($availableevalopts) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
    </script>
    <div id="exa-list">
        <ul exa="items">
        	<li><input type="text" /> <span exa="delete-button"><?php echo get_string('delete'); ?></span></li>
        </ul>
        <div exa="new-item">
        	<input type="text" exa="new-text" />
        	<input type="button" exa="new-button" value="<?php echo get_string('add'); ?>">
        </div>
        <div exa="save">
        	<input type="button" exa="save-button" value="<?php echo get_string('savechanges'); ?>">
    	</div>
    </div>
    <?php
    
    block_exastud_print_footer();
    exit;
}

die('wrong action '.$action);
