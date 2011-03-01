<script>
  parent.$('#user-info').dialog('option','title','User Info for <?php echo $user->getNickname(); ?>');
</script>
    <div class="user-info" title="">
        <span id="user-avatar"><img id="avatar" src="<?php echo($user -> getAvatar(80,80)); ?>" title="Profile Picture - <?php echo($user -> getNickname()); ?>" /></span>
        <div class = "common-info">
        <?php include("userstats-table.inc"); ?>

        <p class = "info-label">City, Local Time<br />
        <span id="info-currenttime" title="<?php echo $timezoneTable[$user -> getTimezone()]; ?>"><?php echo $user ->getCity().", ";echo convertTimeZoneToLocalTime($user->getTimezone()); ?></span>
        </p>
        <div class="button-row">
            <span id="btn-bonus"><input type="submit" <?php if ($budget <= 0) { echo 'disabled="disabled"'; } ?> id="pay_bonus" value="Pay Bonus" name="pay_bonus" /></span>
            <span id="btn-send-message" class="nickname-ping"><input type="submit" id="send-message" value="Send message" name="send_message" /></span>
        </div>
        <p class = "info-label small-text"><br />
            <span id="info-timezone">&nbsp;</span>
        </p>
    </div><!-- end of common info div-->
    <?php if(! $is_runner){ ?><hr style="clear: both;" /><?php } ?>

    <div id="tabs" class="tabs-bottom">
    <?php if($is_runner){ ?>
        <ul>
            <li><a href="#tabs-1">User Info</a></li>
            <li><a href="#tabs-2">Admin</a></li>
            <li><a href="#tabs-3">Sandbox</a><li>
            <li><a href="userNotes.php?action=getNote&userId=<?php echo $user->getId(); ?>">My Notes</a></li>
        </ul>
    <?php } else {?>
        <ul>
            <li><a href="#tabs-1">User Info</a></li>
            <li><a href="userNotes.php?action=getNote&userId=<?php echo $user->getId(); ?>">My Notes</a></li>
        </ul>
		<input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>" />
    <?php } ?>
        <div id="tabs-1">
            <div class = "left-column">
                <p class = "info-label">Strongest Skills<br />
                    <span id="info-skills"><?php echo $user -> getSkills(); ?></span>
                </p>

                <p class = "info-label">Joined<br />
                    <span id="info-joined"><?php echo date('m/d/Y', strtotime($user -> getAdded())); ?></span>
                </p>
            </div><!-- end of left-column -->

            <div class = "right-column">
                <p class = "info-label">About<br />
                    <span id="info-about"><?php echo $user -> getAbout(); ?></span>
                </p>
                <p class = "info-label">Preferred Method of Contact<br />
                    <span id="info-contactway"><?php echo $user -> getContactway(); ?></span>
                </p>

                <p class = "info-label">Preferred Method of Payment<br />
                    <span id="info-payway"><?php echo $user -> getPayway(); ?></span>
                </p>
            </div><!-- end of right-column -->
        </div><!-- end of tabs-1 -->
        <div style = "clear: both;"></div>

    <?php if($is_runner){ ?>
        <div id="tabs-2" style = "clear: both;">
            <div id = "admin-left">
                <p class = "info-label">Email<br />
                    <span id="info-email"><a href="mailto:<?php echo $user -> getUsername(); ?>">
                    <?php echo $user -> getUsername(); ?></a></span>
                </p>
                <form id = "roles" method="post" action = "">
                <p class = "info-label">Roles<br />

                <input type="checkbox" name="isrunner" value="isrunner" id="info-isrunner" <?php if($user->isRunner()) echo 'checked = "checked" '; ?> /><span>Runner</span>
                <input type="checkbox" name="ispayer" value="ispayer" id="info-ispayer" <?php if($user->isPayer()) echo 'checked = "checked" '; ?> /><span>Payer</span>
                </p>

                <p class="info-label">W9 Approved<br />
                <input type="checkbox" id="approve" name = "w9" <?php if($user->isW9Approved()) echo 'checked = "checked"'; ?> />
                </p>
                <p class="info-label">Status<br />
                    <select id="changeUserStatus" style="width:auto; margin-bottom: 3em;">
                        <option value="0" <?php echo(($user->getIs_active() == 0) ? 'selected="selected"' : ''); ?>>inactive</option>
                        <option value="1" <?php echo(($user->getIs_active() == 1) ? 'selected="selected"' : ''); ?>>active</option>
                        <option value="2" <?php echo(($user->getIs_active() == 2) ? 'selected="selected"' : ''); ?>>secured</option>
                    </select>
                </p>

                <input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>" />
                <p class = "bottom">
                    <input type="submit" name="save_roles" value="Save Roles" />
                </p>
                </form>
            </div><!-- end of admin-left -->
            <div id = "admin-middle">
               <p class = "info-label">Budget<br />
                    <span id="info-budget"><?php echo $user->getBudget(); ?></span>
                    <input type="submit" id="give" value="Give Budget..." />
                </p><br />
				<?php if($is_payer){ ?>
				<p class="info-label">Annual Salary<br />
				<form method="post" action = "" name="salary" id="salary">
				<input type="text" id="annual_salary" name="annual_salary" value="<?php echo $Annual_Salary;?>"  style="width:120px;"/>
                </p>
                <p class="info-label">Manager<br/>
                <?php echo $filter->getManagerUserSelectboxS("width:180px;"); ?>
                </p>
                <p  class="bottom">
					<input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>" />
					<input type="hidden" name="salary_changed" id="salary_changed" value="0" />
					<input type="hidden" name="manager_changed" id="manager_changed" value="0" />
                    <input type="submit" id="save_salary" value="Save salary"  name="save_salary"/>
                </p>
				</form>
				<?php } else { ?>
				<form method="post" action="" name="manager" id="manager">
                    <p class="info-label">Manager<br/>
                    <?php echo $filter->getManagerUserSelectboxS("width:180px;"); ?>
                    </p>
                    <p  class="bottom">
                        <input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>" />
                        <input type="submit" id="save_manager" value="Save manager" name="save_manager"/>
                    </p>
                </form>
				<? } ?>
            </div><!-- end of admin-middle -->
            <div id = "admin-right">
 
            </div><!-- end of admin-right-->
        </div><!-- end of tab 2 -->
<?php
function getProjectList() {
    $query = "SELECT * FROM `projects` WHERE active=1";
    $query = mysql_query($query);
    $projects = array();
    $i = 0;
    while ($project = mysql_fetch_array($query)) {
        
        $projects[$i]['name'] = $project['name'];
        $projects[$i]['id']   = $project['project_id'];
        $projects[$i]['repo'] = $project['repository'];
        $i++;
    }
    return $projects;

}

$projects = getProjectList();
$user_projects = $user->getProjects_checkedout();
if (count($user_projects) > 0) {
    $has_sandbox = true;
}
?>        
        <div id="tabs-3" style="clear: both;">
            <form method="post" action = "" id="sandboxForm">
                <div style="clear: both; float: right;">
                    <input id="create_sandbox" type="submit" name="create_sandbox" value="Create SB" <?php if ($has_sandbox) { echo 'style="display: none;"'; } ?> />
                </div>
                <p id="unixusername-field" class = "info-label">UNIX Username: <br />
                    <input name="unixusername" id="unixusername" type="text" size="15" value = "<?php echo $user -> getUnixusername(); ?>" /><br />
                </p>
                <p id="projects-field" class="info-label">Projects to check-out :</p>
<?php foreach ($projects as $project) : ?>
                <div class="quarter-column"><input <?php if ($user->isProjectCheckedOut( $project['id'] )) { echo 'checked="checked"  disabled="disabled" '; } ?> type="checkbox" id="<?php echo $project['id']; ?>" /><input type="hidden" class="repo" value="<?php echo $project['repo']; ?>" /><?php echo $project['name']; ?></div>
<?php endforeach; ?>
            </form>
        </div><!-- end of tab 3 -->
        <div style = "clear: both;"></div>
    <?php } ?>
    </div><!--end of div "tabs" -->
</div> <!-- end of div user-info-->

<!-- popup div's -->
    <div id="give-budget" title="Give Budget" style="text-align: left;display: none">
        <form method="post" action = "">
            <label for="budget-amount" class="info-label">Amount: </label><br/>
                <input type="text" id="budget-amount" name="amount" /><br/>
            <label for="budget-reason" class="info-label">Reason: </label><br/>
                <input type="text" id="budget-reason" name="reason" style="width:95%" /><br/><br/>
                <input type = "hidden" id = "budget-receiver" name="receiver_id" value = "<?php echo $user-> getId(); ?>" />
            <input type="submit" value="Give" />
        </form>
    </div><!-- end of give budget div-->

    <div id="pay-bonus" title="Pay Bonus" style="text-align: left; display: none">
        <form method="post" action = "">
            <label for="bonus-amount" class="info-label">Amount: </label><br/>
                <input type="text" id="bonus-amount" name="amount" /><br/>
            <label for="bonus-reason" class="info-label">Reason: </label><br/>
                <input type="text" id="bonus-reason" name="reason" style="width:95%" /><br/><br/>
                <input type = "hidden" id = "bonus-receiver" name="receiver_id" value = "<?php echo $user-> getId(); ?>" />
            <input type="submit" value="Pay Bonus" />
        </form>
    </div><!-- end of pay bonus div -->

    <div id="loading" style="display:none">
        <img src="images/loader.gif" alt = "" /><span>Please wait..</span>
    </div>  
