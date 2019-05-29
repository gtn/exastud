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

defined('MOODLE_INTERNAL') || die();

?><form id="assignform" action="<?php p($_SERVER['REQUEST_URI'])?>" method="post" autocomplete="off">
<div>
	<input type="hidden" name="sesskey" value="<?php p(sesskey()) ?>" />
	<table class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
		<tr>
			<td valign="top">
				<p><label for="removeselect"><?php print_string('categories', 'block_exastud'); ?></label></p>
			  <div class="userselector">
			  <select name="removeselect[]" size="20" id="removeselect" multiple="multiple"
					  onfocus="getElementById('assignform').add.disabled=true;
							   getElementById('assignform').remove.disabled=false;
							   getElementById('assignform').addselect.selectedIndex=-1;">
	
			  <?php
				$i = 0;
				foreach ($classcategories as $classcategory) {
					echo "<option value='".$classcategory->id."_".$classcategory->source."'>".$classcategory->title."</option>\n";
					$i++;	
				}
				if ($i==0) {
					echo '<option/>'; // empty select breaks xhtml strict
				}
			  ?>
			  </select>
			  </div>
			</td>
			<td id="buttonscell">
			  <div id="addcontrols">
			  	  <input name="addbasic"
                         id="addbasic"
                         type="submit"
                         class = 'btn btn-default'
                         value="◄ <?php echo block_exastud_get_string('addallbasic'); ?>"
                         title="<?php block_exastud_get_string('addallbasic'); ?>" />
                  <label for="addbasicalways" style="margin-bottom: 3rem; display: inline-block;" ><input name="addbasicalways" id="addbasicalways" type="checkbox" <?php if ($addbasicalways) { echo ' checked="checked" ';} ?> value="1" style="display: inline; width: 30px;" /><?php echo block_exastud_get_string('addallbasicalways'); ?></label>
				  <input name="add"
                         id="add"
                         type="submit"
                         class = 'btn btn-default'
                         value="◄ <?php echo block_exastud_get_string('add'); ?>"
                         title="<?php print_string('add'); ?>" />
			  </div>
				<div id="removecontrols">
				  <input name="remove"
                         id="remove"
                         type="submit"
                         class = 'btn btn-default'
                         value="<?php echo block_exastud_get_string('remove'); ?> ►"
                         title="<?php print_string('remove'); ?>" />
			  	</div>
			</td>
			<td valign="top">
				<p><label for="addselect"><?php echo block_exastud_get_string('availablecategories'); ?></label></p>
			  <div class="userselector">
				<select name="addselect[]" size="20" id="addselect" multiple="multiple"
						onfocus="getElementById('assignform').add.disabled=false;
								 getElementById('assignform').remove.disabled=true;
								 getElementById('assignform').removeselect.selectedIndex=-1;">
				<?php
					$i = 0;
				  	if (!empty($searchtext)) {
						echo '<optgroup label="' . block_exastud_get_string('searchresults') . ' (' . count($availablecategories) . ')">\n';
				  		foreach ($availablecategories as $category) {
						  	echo '<option value="' . $category->id . '_'.$category->source.'">' . $category->title . '</option>\n';
							$i++;
						}
						echo "</optgroup>\n";
					} else {
						if (count($availablecategories) > MAX_USERS_PER_PAGE) {
							echo '<optgroup label="'.block_exastud_get_string('toomanytoshow').'"><option></option></optgroup>'."\n"
								  .'<optgroup label="'.block_exastud_get_string('trysearching').'"><option></option></optgroup>'."\n";
						} else {
							$subject = "";
							foreach ($availablecategories as $category) {
								if ($subject !== $category->subject_title) {
									$subject = $category->subject_title;
									echo '<optgroup label="'.$subject.'"></optgroup>';
								}
									
								echo '<option value="' . $category->id . '_'.$category->source.'">' . $category->title . '</option>\n';
								$i++;
							}
						}
					}

					if ($i == 0) {
						echo '<option/>'; // empty select breaks xhtml strict
					}
				?>
			   </select>
			   </div>
			   <label for="searchtext" class="accesshide"><?php p($strsearch) ?></label>
			   <input type="text" name="searchtext" id="searchtext" size="30" value="<?php p($searchtext, true) ?>"
						onfocus ="getElementById('assignform').add.disabled=true;
								  getElementById('assignform').remove.disabled=true;
								  getElementById('assignform').removeselect.selectedIndex=-1;
								  getElementById('assignform').addselect.selectedIndex=-1;"
						onkeydown = "var keyCode = event.which ? event.which : event.keyCode;
									 if (keyCode == 13) {
										  getElementById('assignform').previoussearch.value=1;
										  getElementById('assignform').submit();
									 } " />
			   <input name="search"
                      id="search"
                      type="submit"
                      class = 'btn btn-default'
                      value="<?php print_string('search') ?>" />
			   <?php
					if (!empty($searchtext)) {
						echo '<input name="showall" id="showall" type="submit" value="'.block_exastud_get_string('showall',null,block_exastud_get_string('categories')).'" />'."\n";
					}
			   ?>
			 </td>
		</tr>
	</table>
</div>
</form>
