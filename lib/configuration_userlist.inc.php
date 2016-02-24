<?php 

defined('MOODLE_INTERNAL') || die();

?><form id="assignform" action="<?php p($_SERVER['REQUEST_URI'])?>" method="post">
<div>
	<input type="hidden" name="sesskey" value="<?php p(sesskey()) ?>" />
	<table class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
		<tr>
			<td valign="top">
				<p><label for="removeselect"><?php print_string($userlistType, 'block_exastud'); ?></label></p>
			  <div class="userselector">
			  <select name="removeselect[]" size="20" id="removeselect" multiple="multiple"
					  onfocus="getElementById('assignform').add.disabled=true;
							   getElementById('assignform').remove.disabled=false;
							   getElementById('assignform').addselect.selectedIndex=-1;">
	
			  <?php
				$i = 0;
				foreach ($classstudents as $classstudent) {

				   $fullname = fullname($classstudent);
				   $hidden = "";
					echo "<option value=\"$classstudent->record_id\">".(!empty($classstudent->subject_title) ? $classstudent->subject_title.' ('.$fullname.", ".$classstudent->email.')' : $fullname.", ".$classstudent->email)."</option>\n";
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
				  <input name="add" id="add" type="submit" value="◄ <?php echo get_string('add'); ?>" title="<?php print_string('add'); ?>" />
				  <div class="enroloptions">
				  <?php
					if ($userlistType == 'teachers') {
						$subjects = $DB->get_records('block_exastudsubjects', null, 'title');
						echo '<p><label for="classteacher_subjectid">'.\block_exastud\trans('de:Fachbezeichnung / Rolle').'</label><br>';
						echo '<select id="classteacher_subjectid" name="classteacher_subjectid">';
						echo '<option></option>';

						$subjects = array_merge([
								(object)['id' => block_exastud\SUBJECT_ID_ADDITIONAL_CLASS_TEACHER, 'title' => \block_exastud\get_string('head_teacher')],
								(object)['id' => 0, 'title' => '-----------------'],
							], $subjects)	;

						foreach ($subjects as $subject) {
							echo '<option value="'.$subject->id.'"';
							if ($subject->id && $subject->id == optional_param('classteacher_subjectid', 0, PARAM_INT))
								echo ' selected="selected"';
							echo '>'.s($subject->title).'</option>';
						}
						echo '</select>';
						echo '</p>';
					} ?>
				  </div>
			  </div>
				<div id="removecontrols">
				  <input name="remove" id="remove" type="submit" value="<?php echo get_string('remove'); ?> ►" title="<?php print_string('remove'); ?>" />
			  </div>
			</td>
			<td valign="top">
				<p><label for="addselect"><?php print_string('availableusers', 'block_exastud'); ?></label></p>
				<div class="userselector" id="addselect_wrapper">
				<select name="addselect[]" size="20" id="addselect" multiple="multiple"
						onfocus="getElementById('assignform').add.disabled=false;
								 getElementById('assignform').remove.disabled=true;
								 getElementById('assignform').removeselect.selectedIndex=-1;">
				<?php
					$i = 0;
				  	if (!empty($searchtext)) {
						echo '<optgroup label="' . get_string('searchresults') . ' (' . count($availableusers) . ')">\n';
				  		foreach ($availableusers as $user) {
							$fullname = fullname($user);
						  	echo '<option value="' . $user->id . '">' . $fullname . ', ' . $user->email . '</option>\n';
							$i++;
						}
						echo "</optgroup>\n";
					} else {
						if (count($availableusers) > MAX_USERS_PER_PAGE) {
							echo '<optgroup label="'.get_string('toomanytoshow').'"><option></option></optgroup>'."\n"
								  .'<optgroup label="'.get_string('trysearching').'"><option></option></optgroup>'."\n";
						} else {
							foreach ($availableusers as $user) {
								$fullname = fullname($user);
								echo '<option value="' . $user->id . '">' . $fullname . ', ' . $user->email . '</option>\n';
								$i++;
							}
						}
					}

					if ($i==0) {
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
			   <input name="search" id="search" type="submit" value="<?php print_string('search') ?>" />
			   <?php
					if (!empty($searchtext)) {
						echo '<input name="showall" id="showall" type="submit" value="'.get_string('showall','block_exastud').'" />'."\n";
					}
			   ?>
			 </td>
		</tr>
	</table>
</div>
</form>
