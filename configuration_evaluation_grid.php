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

require("inc.php");
global $DB, $THEME;
define("MAX_USERS_PER_PAGE", 5000);


$courseid = optional_param('courseid', 1, PARAM_INT); // Course ID
$showall        = optional_param('showall', 0, PARAM_BOOL);
$searchtext     = optional_param('searchtext', '', PARAM_TEXT); // search string
$add            = optional_param('add', 0, PARAM_BOOL);
$remove         = optional_param('remove', 0, PARAM_BOOL);

require_login($courseid);

$context = context_course::instance($courseid);
//$context = get_context_instance(CONTEXT_COURSE,$courseid);
require_capability('block/exastud:use', $context);
require_capability('block/exastud:headteacher', $context);

$header = block_exastud_get_string('editclasscategories', 'block_exastud');
$url = '/blocks/exastud/configuration_categories.php';
$PAGE->set_url($url);

$availablecategories = $DB->get_records_sql('SELECT id, title
    FROM {block_exastudcate}
    ORDER BY sorting');

if (optional_param('action', '', PARAM_TEXT) == 'save') {
	if(!confirm_sesskey()) {
		die(get_string("badsessionkey","block_exastud"));
	}
	
	$items = block_exastud_param::required_array('items',
            array(PARAM_INT => (object)array(
                'id' => PARAM_INT,
                'title' => PARAM_TEXT
            )
        ));

	$deletecategories = $availablecategories;
	$sorting = 0;
	foreach ($items as $item) {
	    $sorting++;
	    $item->sorting = $sorting;
	    
	    if (isset($availablecategories[$item->id])) {
	        // update
	        $DB->update_record('block_exastudcate', $item);
	        
	        unset($deletecategories[$item->id]);
	    } else {
	        // insert
	        $DB->insert_record('block_exastudcate', $item);
	    }
	}
	
	foreach ($deletecategories as $item) {
	    $DB->delete_records('block_exastudcate', array('id'=>$item->id));
	}
	
	echo 'ok';
	
	exit;
}

block_exastud_print_header(array('configuration', '='.$header));

?>
<script>
	var exacomp_list_items = <?php echo json_encode(array_values($availablecategories) /* use array_values, because else the array gets sorted by key and not by sorting */); ?>
</script>
<div id="exacomp-list">
    <ul exacomp="items">
    	<li><input type="text" /> <span exacomp="delete-button">löschen</span></li>
    </ul>
    <input type="text" exacomp="new-text" />
    <input type="button" exacomp="new-button" value="Hinzufügen">
    <div><input type="button" exacomp="save-button" value="Speichern"></div>
</div>
<?php

block_exastud_print_footer();
