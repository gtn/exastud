<form id="assignform" action="<?php p($form_target)?>" method="post">
<div>

    <input type="hidden" name="id" value="<?php p($courseid) ?>" />
    <input type="hidden" name="sesskey" value="<?php p(sesskey()) ?>" />
    <table summary="" style="margin-left:auto;margin-right:auto" border="0" cellpadding="5" cellspacing="0">
        <tr>
            <td valign="top">
                <label for="removeselect"><?php print_string($userlistType, 'block_exastud'); ?></label>
	          <br />
	          <select name="removeselect[]" size="20" id="removeselect" multiple="multiple"
	                  onfocus="getElementById('assignform').add.disabled=true;
	                           getElementById('assignform').remove.disabled=false;
	                           getElementById('assignform').addselect.selectedIndex=-1;">
	
	          <?php
	            $i = 0;
                foreach ($classusers as $classuser) {

                   $fullname = fullname($classuser, true);
                    if ($classuser->hidden) {
                        $hidden=' ('.get_string('hiddenassign').') ';
                    } else {
                        $hidden="";
                    }
                    echo "<option value=\"$classuser->id\">".$fullname.", ".$classuser->email.$hidden."</option>\n";
                    $i++;    
                }
                if ($i==0) {
                    echo '<option/>'; // empty select breaks xhtml strict
                }
              ?>
	          </select>
            </td>
            <td valign="top">
              <br />
              <p class="arrow_button">
                  <input name="add" id="add" type="submit" value="<?php echo get_string('add'); ?>" title="<?php print_string('add'); ?>" />
                  <br />
                  <input name="remove" id="remove" type="submit" value="<?php echo get_string('remove'); ?>" title="<?php print_string('remove'); ?>" />
              </p>
            </td>
            <td valign="top">
                <label for="addselect"><?php print_string('availableusers', 'block_exastud'); ?></label>
                <br />
                <select name="addselect[]" size="20" id="addselect" multiple="multiple"
                        onfocus="getElementById('assignform').add.disabled=false;
                                 getElementById('assignform').remove.disabled=true;
                                 getElementById('assignform').removeselect.selectedIndex=-1;">
                <?php
                    $i = 0;
                  	if (!empty($searchtext)) {
						echo '<optgroup label="' . get_string('searchresults') . ' (' . count($availableusers) . ')">\n';
                  		foreach ($availableusers as $user) {
							$fullname = fullname($user, true);
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
                                $fullname = fullname($user, true);
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
               <br />
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
