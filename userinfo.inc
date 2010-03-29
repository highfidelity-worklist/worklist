    <div class = "user-info" title = "User Info">
	<div class = "left-column">
            <p class = "info-label">Nickname<br />
            <span id="info-nickname"><?php echo $user -> getNickname(); ?> 
	<?php if($user -> getId() != $reqUser -> getId()){ ?>
	    <a href="#" id="reward-link">Reward</a></span>
	<?php } ?>
            </p>

            <p class = "info-label">Email<br />
            <span id="info-email"><?php echo $user -> getUsername(); ?></span>
            </p>

            <p class = "info-label">About<br />
            <span id="info-about"><?php echo $user -> getAbout(); ?></span>
            </p>

            <p class = "info-label">Strongest Skills<br />
            <span id="info-skills"><?php echo $user -> getSkills(); ?></span>
            </p>

            <p class = "info-label">Time zone<br />
            <span id="info-timezone"><?php echo $timezoneTable[$user -> getTimezone()]; ?></span>
            </p>
			
            <p class = "info-label">Current Local Time<br />
            <span id="info-currenttime"><?php echo convertTimeZoneToLocalTime($user -> getTimezone()); ?></span>
            </p>

            <p class = "info-label">Budget<br />
            <span id="info-budget"><?php echo $user->getBudget(); ?></span>
	    <?php if ($user->getId() == $_SESSION['userid'] && $user->getBudget() > 0) { ?>
            <input type="submit" id="give" value="Give Budget...">
            <?php } ?>
            </p>
            
            <form id = "roles" method="post" >
            <p class = "info-label">Roles<br />
	    <?php $disabled = !$is_runner ? 'disabled = "disabled"' : '';?>
            <input type="checkbox" name="isrunner" value="isrunner" id="info-isrunner" <?php if($user->isRunner()) echo 'checked = "checked" '; echo $disabled; ?> /><span>Runner</span>
            <input type="checkbox" name="ispayer" value="ispayer" id="info-ispayer" <?php if($user->isPayer()) echo 'checked = "checked" '; echo $disabled; ?> /><span>Payer</span>
            </p>
            
            <input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>">

	        <?php if ($is_runner) { ?>
            <input type="submit" name="save_roles" value="Save Roles">
            <?php } ?>
	        </form>
            
	</div><!-- end of left-column div -->
	<div class = "right-column">

            <p class = "info-label">Joined<br />
            <span id="info-joined"><?php echo date('m/d/Y', strtotime($user -> getAdded())); ?></span>
            </p>
            
            <p class = "info-label">Preferred Method of Contact<br />
            <span id="info-contactway"><?php echo $user -> getContactway(); ?></span>
            </p>

            <p class = "info-label">Preferred Method of Payment<br />
            <span id="info-payway"><?php echo $user -> getPayway(); ?></span>
            </p>

	    <?php if ($is_runner) { ?>
            <p class="info-label">W9 Approved<br />
            <input type="checkbox" id="approve" <?php if($user->isW9Approved()) echo 'checked = "checked"'; ?> />
            </p>
            <?php } ?>
    
		  
            <p class="info-label">Status<br />
            	<select id="changeUserStatus" style="width:auto;"<?php echo(($is_runner) ? '' : 'disabled="disabled"'); ?>>
					<option value="0"<?php echo(($user->getIs_active() == 0) ? 'selected="selected"' : ''); ?>>inactive</option>
					<option value="1"<?php echo(($user->getIs_active() == 1) ? 'selected="selected"' : ''); ?>>active</option>
					<option value="2"<?php echo(($user->getIs_active() == 2) ? 'selected="selected"' : ''); ?>>secured</option>
            	</select>
            </p>

		<?php if ($is_runner) { ?>
	    <form>
	    <p id="unixusername-field" class = "info-label">UNIX Username: <br />
		    <input name="unixusername" id="unixusername" type="text" size="15" value = "<?php echo $user -> getUnixusername(); ?>" /><br />
            </p>

	    <p id="projects-field" class = "info-label">Projects to check-out : <br />
		    <input id="projects" type="text" name="projects" size="20" value = "<?php echo $user -> getProjects_checkedout(); ?>"></span><br />
            </p>
	    <input id="create_sandbox" type="submit" name="create_sandbox" value="Create Sandbox" />
	    </form>
            <?php }elseif($user -> getHas_sandbox()){ ?>

            <p class = "info-label">Unix Username: <br />
            <span id="info-joined"><?php echo $user -> getUnixusername(); ?></span>
            </p>

	    <?php } ?>
	</div><!-- end of right-column div -->
    </div><!-- end of user-info div -->

    <div id="give-budget" title="Give Budget" style="text-align: left;display: none">
	<form>
	    <label for="amount" class="info-label">Amount: </label><br/>
            <input type="text" id="budget-amount" name="amount" /><br/>
	    <label for="reason" class="info-label">Reason: </label><br/>
            <input type="text" id="budget-reason" name="reason" style="width:95%" /><br/>
	    <label for="receiver_id" class="info-label">Receiver: </label><br/>
            <select id="budget-receiver" name='receiver_id'>
            <?php
            $user_array = GetUserList($_SESSION['userid'], $_SESSION['nickname']);
            foreach($user_array as $userid=>$nickname) {
                if ($userid == $_SESSION['userid']) continue;
                echo "<option value='{$userid}'>{$nickname}</option>\n";
            }
            ?>
            </select><br/>
	    <input type="submit" value="Give" />
	</form>
    </p>
    </div>

    <div id="quick-reward" title = "Reward User">
    <p>
	<span class = "labelo">Available points: </span><span id = "available" class = "points"></span>
    </p>
    <p>
	<span class = "labelo">Rewarded to this user: </span><span id = "already" class = "points"></span>
    </p>
    <p>
	<form>
	    <input type = "text" id = "toreward" />
	    <input type = "submit" value = "Reward">
	</form>
    </p>
    </div>

<div id="loading" style="display:none">
       <img src="images/loader.gif"></img> <span>Please wait..</span>
</div>  
