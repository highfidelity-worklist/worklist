<script type="text/javascript">
    parent.$('#user-info').dialog('option', 'title', 'User Info for <?php echo $user->getNickname(); ?>');
</script>
    <div class="user-info" title="">
        <div class="common-info">
<?php
    include("userstats-table.inc");
    require_once 'countrylist.php'; // Get country and country url list
    // Google Maps search Base URL
    $gMapSearch = "http://maps.google.com/maps?q=";
    // CIA.gov Base URL
    $ciaGovBase = "https://www.cia.gov/library/publications/the-world-factbook/geos/";
    // CCF = Country Code Fetched from user
    $CCF = $user ->getCountry();
    
     /* Array comparing
     * If country is empty do nothing
     */
    if ($CCF == "") {
        // $countryName = "Country Name Unkown";
        // $countryCodeUrl = "Country URL Uknown";
        // $countryCode = "Country Code Unkown";
    } else {
    /* If country Code (key, array) is the same as
     * the country code stored in DB,
     * if it's do proceed and store variabels
     * with values
     */
        if (array_key_exists($CCF, $countrylist)) {
            $countryCode = $CCF;
            $countryName = $countrylist[$CCF];
            /* Proceed by checking (array) if the countries name
             * is identicel in the one of countriesurllist,
             * if so proceed and store the country code url
             * in another variabel
             */
            if (array_key_exists($countryName, $countryurllist)) {
                $countryCodeUrl = strtolower($countryurllist[$countryName]); // To prevent URL from not working, case sensitiv.
            }
        }
    }
    if (!isset($countryCodeUrl)) { $countryCodeUrl = ""; }
    if (!isset($countryName)) { $countryName = ""; }
?>
        <div id="user-avatar">
            <img id="avatar" src="<?php echo($user->getAvatar(80, 80)); ?>" title="Profile Picture - <?php echo($user->getNickname()); ?>" />
            <?php
                if ($reqUser->getId() != $user->getId()) {
                    if ( !$reqUser->isRunner() && !array_key_exists('admin',$_SESSION) && !$reqUser->isActive() ) {
                        $favoriteClass = "favorite_curr_user";
                        $title = "You must have been paid for a job in the last 90 days to Trust a person.";
                    } else {
                        $favoriteClass = "favorite_user";
                        $favoriteClass .= ($favorite_enabled == 1) ? " myfavorite" : " notmyfavorite" ;
                        $title = ($favorite_enabled == 1) ?
                            "Remove " . ucwords($user->getNickname()) . " as someone you trust. (don't worry it's anonymous)" :
                            "Add " . ucwords($user->getNickname()) . " as someone you trust." ;
                    }
                } else {
                    $favoriteClass = "favorite_curr_user";
                    $title = "";
                }
            ?>
            <div class="profileInfoFavorite" id="profileInfoFavorite">
                <div class="<?php echo $favoriteClass; ?>" title="<?php echo $title; ?>">&nbsp;</div>
                <span class="profileFavoriteText" data-favorite_count="<?php echo $favorite_count; ?>"></span>
            </div>
        </div>
        <p class="info-label" id="user-location">
            <?php
            if($user->getCity() != ""){
                echo 'City, ';
            }
            if($countryCodeUrl != ""){
                echo 'Country - ';
            }
            ?>
            Local Time<br />
            <span id="info-currenttime" title="<?php echo $timezoneTable[$user->getTimezone()]; ?>">
                <?php
                /* If city is not present, aka stored in DB
                 * do nothing and proceed to next step
                 */
                if($user->getCity() != ""){
                    echo '<a class="userInfoLink" href="'.$gMapSearch.''.$user->getCity().',+'.$countryName.'&z=6" target="_blank" title="See location on Google Maps">'.$user->getCity().'</a>, ';
                }
                /* If country code url exists is not empty
                 * do proceed with making the url, else nothing
                 */
                if($countryCodeUrl != ""){
                    echo '<a class="userInfoLink" href="'.$ciaGovBase.''.$countryCodeUrl.'.html" target="_blank" title="'.$countryName.'">'.$CCF.'</a> - ';
                }
        
                echo convertTimeZoneToLocalTime($user->getTimezone());
                ?>
            </span>
        </p>

        <div class="button-row">
            <?php if ($reqUserId > 0) { ?> 
            <span id="info-budget"><input type="submit" 
                <?php 
                if (!$reqUser->isRunner() || $reqUserId == $user->getId()) {
                    if (strpos(BUDGET_AUTHORIZED_USERS, "," . $reqUserId . ",") === false) {
                        echo 'disabled="disabled" class="button-disabled disableable" '; 
                    }
                }
                echo ' title="Remaining budget you can give: $' . $reqUser->getBudget() . '" '; 
                ?> id="give" value="Give Budget" />
            </span>
            <span id="btn-bonus"><input type="submit" 
                <?php if ($reqUserId == $user->getId() || ! $is_runner) { 
                    echo 'disabled="disabled" class="button-disabled disableable" '; 
                } 
                echo ' title="Remaining budget you can give: $' . $reqUser->getBudget() . '" '; 
                ?> 
                id="pay_bonus" value="Pay Bonus" name="pay_bonus" />
            </span>
                <?php if ($reqUserId != $user->getId()) { ?>
                <span id="btn-send-message" class="nickname-ping"><input type="submit" id="send-message" value="Send message" name="send_message" /></span>
            <?php 
                } 
            } ?>
        </div>
        <p class="info-label small-text"><br />
            <span id="info-timezone">&nbsp;</span>
        </p>
        
    </div><!-- end of common info div-->
    <div id="sent-notify"></div>
    <?php if(! $is_runner){ ?><hr style="clear: both;" /><?php } ?>

    <div id="tabs" class="tabs-bottom">
    <?php if($is_runner){ ?>
        <ul>
            <li><a href="#tabs-1">User Info</a></li>
            <li><a href="#tabs-2">Admin</a></li>
            <?php if ($user->getIs_runner()) { ?>
                <li><a href="#tabs-3">Runner</a></li>
            <?php } ?>
            <li><a href="#tabs-4">Sandbox</a><li>
            <li><a href="userNotes.php?action=getNote&userId=<?php echo $user->getId(); ?>">My Notes</a></li>
            <li><a href="chatHistory.php?action=getLatestForNickname&nickname=<?php echo urlencode($user->getNickname()); ?>&num=100&<?php echo time(); ?>">Chat History</a></li>
            <li><a href="budgetHistory.php?inDiv=tabs&id=<?php echo urlencode($user->getId()); ?>&num=100&<?php echo time(); ?>" id="tabBudgetHistory">Budget History</a></li>
        </ul>
    <?php } else {?>
        <ul>
            <li><a href="#tabs-1">User Info</a></li>
            <?php if ($user->getIs_runner()) { ?>
                <li><a href="#tabs-3">Runner</a></li>
            <?php } ?>            
            <?php if ($reqUserId > 0): ?>
            <li><a href="userNotes.php?action=getNote&userId=<?php echo $user->getId(); ?>">My Notes</a></li>
            <?php endif; ?>
            <li><a href="chatHistory.php?action=getLatestForNickname&nickname=<?php echo urlencode($user->getNickname()); ?>&num=100&<?php echo time(); ?>">Chat History</a></li>
            <li><a href="budgetHistory.php?inDiv=tabs&id=<?php echo urlencode($user->getId()); ?>&num=100&<?php echo time(); ?>">Budget History</a></li>
        </ul>
        <input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>" />
    <?php } ?>
        <div id="tabs-1">
            <div class = "left-column">
                <p class = "info-label">About<br />
                    <span id="info-about"><?php echo $user -> getAbout(); ?></span>
                </p>
                <p class = "info-label">Age<br />
                    <span id="info-joined">
                        <?php
                            $diffseconds = strtotime($user -> getAdded());
                            echo formatableRelativeTime($diffseconds,2);
                        ?>
                    </span>
                </p>
                <p class = "info-label">Strongest Skills<br />
                    <span id="info-skills"><?php echo $user -> getSkills(); ?></span>
                </p>

                <p class = "info-label">Preferred Method of Contact<br />
                    <span id="info-contactway"><?php echo $user -> getContactway(); ?></span>
                </p>


            </div><!-- end of left-column -->

            <div class = "right-column">
                <div class='reviewTitle' >
                    Reviews
                </div>
                <?php if ($reqUserId > 0): ?>
                <div class='reviewAddLink'>
                    <a href='javascript:;'>Add your review</a>
                </div>
                <div class='reviewEditLink'>
                    <a href='javascript:;'>Edit your review</a>
                </div>
                <?php endif; ?>
                <div class='reviewsList'>
<?php
    $res="<div class='noReview'>No Review available for this user.</div>";
    if (isset($reviewsList)) {
        $length = count($reviewsList);
        if ($length > 0) {
            $res = "";
            for ($i = 0; $i < $length; $i++) {
                $review = $reviewsList[$i];
                if ($review['me'] == 'y') {
                    $classColor = " myReview";
                    $title = "My review";
                    $feeRange = "Me";
                    $feeRangeTitle ='My review (' . $review['feeRange'] . ')';
                } else {
                    $classColor = " ";
                    $title = "";
                    $feeRange = $review['feeRange'];
                    $feeRangeTitle ='Number of jobs the reviewer has worked on';
                }
                $res .= '<div class="listReviewElement ' . $classColor . '" title="' . $title . '">' .
                        "<div class='feeRange' title='{$feeRangeTitle}'>" .$feeRange . "</div><div class='reviewText'>" . $review['review'] . "</div>" .
                        '</div>' ;
            }
        }
    }
    echo $res;
?>
                </div>
            </div><!-- end of right-column -->
        </div><!-- end of tabs-1 -->
        <div class="clear"></div>
<?php if($is_runner) { ?>
        <div id="tabs-2" class="clear">
            <div id="admin-left">
                <p class = "info-label">Email<br />
                    <span id="info-email"><a href="mailto:<?php echo $user -> getUsername(); ?>">
                    <?php echo $user -> getUsername(); ?></a></span>
                </p>
                <form id="roles" method="post" action="">
                    <p class="info-label">Roles</p>
                    <input type="checkbox" name="isrunner" value="isrunner" id="isrunner" <?php if($user->isRunner()) echo 'checked="checked" '; ?> /><label for="isrunner" class="info-label">Runner</label>
                    <input type="checkbox" name="ispayer" value="ispayer" id="ispayer" <?php if($user->isPayer()) echo 'checked="checked" '; ?> /><label for="ispayer" class="info-label">Payer</label>
                    <p class="info-label">Payment Information</p>
                    <?php $w9_status = $user->getW9_status(); ?>
                    <label for="isw9approved" class="info-label">W9</label>
                    <select id="w9status" name="w9status" <?php echo $user->isUsCitizen() ? '' : 'disabled="disabled"'; ?>>
                        <option value="awaiting-receipt" <?php echo $w9_status == 'awaiting-receipt' ? 'selected="selected"' : ''; ?>>Awaiting Receipt</option>
                        <option value="pending-approval" <?php echo $w9_status == 'pending-approval' ? 'selected="selected"' : ''; ?>>Pending Approval</option>
                        <option value="approved" <?php echo $w9_status == 'approved' ? 'selected="selected"' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $w9_status == 'rejected' ? 'selected="selected"' : ''; ?>>Rejected</option>
                        <option value="not-applicable" <?php echo $w9_status == 'not-applicable' ? 'selected="selected"' : ''; ?>>Not Applicable</option>
                    </select>
                    <div style="clear: both;"></div>
                    <input type="checkbox" id="isw2employee" name="isw2employee" <?php if ($user->getHas_W2()) echo 'checked="checked"'; ?> /><label for="isw2employee" class="info-label">W2 Employee</label>
                    <input type="checkbox" id="ispaypalverified" name="ispaypalverified" <?php if ($user->getPaypal_verified()) echo 'checked="checked"'; ?>/><label for="ispaypalverified" class="info-label">PayPal Verified</label>
                    <p class="info-label">Preferred Payment Method</p>
                    <span id="info-payway"><?php echo $user->getPayway() == '' ? $user->getPayway() : 'None selected'; ?></span>
                    <p class="info-label">PayPal E-Mail</p>
                    <span id="info-paypal"><?php echo ($user->getPaypal_email() == '') ? 'No address on file' : $user->getPaypal_email(); ?></span>
                    <input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>" />
                </form>
            </div><!-- end of admin-left -->
            <div id="admin-right">
                <form method="post" action="" name="userDropdownOptions" id="userDropdownOptions">
<?php if ($is_payer): ?>
                    <p class="info-label">Annual Salary</p>
                    <input type="text" id="annual_salary" name="annual_salary" value="<?php echo $Annual_Salary;?>" />
                    <input class="admin-submit" type="button" id="save_salary" value="Save salary"  name="save_salary"/>
<?php endif; ?>
                    <p class="info-label">Manager</p>
                    <?php echo $filter->getManagerUserSelectboxS("width:180px;"); ?>

                    <p class="info-label">Referred By</p>
                    <?php echo $filter->getReferrerUserSelectboxS("width:180px;"); ?>
                    <p class="info-label">Status</p>
                    <select id="isactive" name="isactive" style="width: auto;">
                        <option value="0" <?php echo(($user->getIs_active() == 0) ? 'selected="selected"' : ''); ?>>inactive</option>
                        <option value="1" <?php echo(($user->getIs_active() == 1) ? 'selected="selected"' : ''); ?>>active</option>
                        <option value="2" <?php echo(($user->getIs_active() == 2) ? 'selected="selected"' : ''); ?>>secured</option>
                    </select>
                    <p class = "info-label">Found Us On:
                    <span><?php echo $user -> getFindus(); ?></span>
                    </p>
                </form>
                    <p class = "info-label">User last seen: 
                    <span><?php echo relativeTime($user->getTimeLastSeen(), false); ?></span>
                    </p>
            </div><!-- end of admin-right -->
        </div><!-- end of tab 2 -->
        <div id="tabs-4" style="clear: both;">
            <form method="post" action="" id="sandboxForm">
                <div style="clear: both; float: right;">
                    <input id="create_sandbox" type="submit" name="create_sandbox" value="Create SB" <?php if ($has_sandbox) { echo 'style="display: none;"'; } ?> />
                </div>
                <p id="unixusername-field" class="info-label">UNIX Username: <br />
                    <input name="unixusername" id="unixusername" type="text" size="15" value = "<?php echo $user -> getUnixusername(); ?>" /><br />
                </p>
                <p id="projects-field" class="info-label">Projects to check-out :</p>
<?php foreach ($projects as $project) : ?>
                <div class="quarter-column"><input <?php if (($has_sandbox) && $user->isProjectCheckedOut( $project['id'] )) { echo 'checked="checked"  disabled="disabled" '; } ?> type="checkbox" id="<?php echo $project['id']; ?>" /><input type="hidden" class="repo" value="<?php echo $project['repo']; ?>" /><?php echo $project['name']; ?></div>
<?php endforeach; ?>
            </form>
        </div><!-- end of tab 4 -->
    <?php } ?>
    <?php if ($user->getIs_runner()) { ?>
        <div id="tabs-3" style="clear:both">
            <div id="admin-left">
                <div class="runnerStatistics">
                    <div id="stats-panel">
                        <table width="100%" class="table-runner-stats">
                            <caption class="table-caption" >
                                <b>Jobs as Runner</b>
                            </caption>
                            <thead>
                                <tr class="table-hdng">
                                    <td>Total/Active Jobs</td>
                                    <td>Avg. Job Run Time</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $userStats = new UserStats($user->getId())?>
                                <tr class="row-role-list-live">
                                    <td>
                                    <?php if ($userStats->getRunnerTotalJobsCount() > 0) {?>
                                        <a href = "#" id = "runner-total-jobs"><?php echo $userStats->getRunnerTotalJobsCount(); ?></a>
                                    <?php } else echo "0";?> / 
                                    <?php if ($userStats->getRunnerActiveJobsCount() > 0 ) {?>
                                        <a href = "#" id = "runner-active-jobs"><?php echo $userStats->getRunnerActiveJobsCount(); ?></a>
                                    <?php } else echo "0";?>
                                    </td>
                                    <td><?php echo $userStats->getAvgJobRunTime();?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>                
            </div>
            <div id="admin-right">
                <div id="runnersAccordion">
                    <h3><a href="#">Who has worked for Runner</a><h3>  
                    <div class="runnerWorkers" >
                        <table width="100%" class="table-runner-workers" id="runner-workers">
                            <caption class="table-caption" >
                                <br/>
                            </caption>
                            <thead>
                                <tr class="table-hdng">
                                    <th>Username</th>
                                    <th># of Jobs</th>
                                    <th>Total Earned</th>
                                </tr>
                            </thead>
                            <tbody class="runnerWorkerContent">
                                <?php if($runnerWorkers = $userStats->getDevelopersForRunner()) { ?>
                                    <?php foreach($runnerWorkers as $runnerWorker) { ?>
                                        <tr class="row-runner-developer-list-live">
                                            <td class="runnerWorker"><?php echo $runnerWorker['nickname']?></td>
                                            <td class="workerJobCount"><?php echo $runnerWorker['totalJobCount']?></td>
                                            <td><?php echo (($runnerWorker['totalEarnings'] > 0) ? "$" . $runnerWorker['totalEarnings'] : "") ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <h3><a href="#">Projects worked on by Runner</a><h3>  
                    <div class="runnerProjects" >
                        <table width="100%" class="table-runner-projects" id="runner-projects">
                            <caption class="table-caption" >
                                <br/>
                            </caption>
                            <thead>
                                <tr class="table-hdng">
                                    <th>Project Name</th>
                                    <th># of Jobs</th>
                                    <th>Project Total</th>
                                </tr>
                            </thead>
                            <tbody class="runnerProjectContent">
                                  <?php if($runnerProjects = $userStats->getProjectsForRunner()) { ?>
                                    <?php foreach($runnerProjects as $runnerProject) { ?>
                                        <tr class="row-runner-project-list-live">
                                            <td class="runnerProject"><?php echo $runnerProject['name']?></td>
                                            <td class="projectJobCount"><?php echo $runnerProject['totalJobCount']?></td>
                                            <td><?php echo (($runnerProject['totalEarnings'] > 0) ? "$" . $runnerProject['totalEarnings'] : "") ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>                                                          
                </div>
            </div>          
        </div> <!-- end of tabs-3-->
    <?php } ?>    
    </div><!--end of div "tabs" -->
</div> <!-- end of div user-info-->

<!-- popup div's -->
    <div id="budget-dialog" title="Budget details" class="hidden" data-userid = "<?php echo $user-> getId(); ?>">
    </div>
    <div id="budget-update-dialog" title="Budget update" class="hidden" data-userid = "<?php echo $user-> getId(); ?>">
        <div class="content"></div>
    </div>
    <div id="reject-w9" title="Reject W9" class="hidden">
        <form method="post" action = "">
            <label for="reject-reason" class="info-label">Reason: </label><br/>
            <textarea id="reject-reason" name="reason"></textarea><br/>
            <span style="float: right;"><span id="charCount">100</span> characters remaining</span>
            <input type = "hidden" id = "budget-receiver" name="receiver_id" value = "<?php echo $user-> getId(); ?>" />
        </form>
    </div><!-- end of reject w9 div -->
    
    <div id="pay-bonus" class="hidden" title="Pay Bonus" style="text-align: left;">
        <form method="post" action = "">
            <div class="leftBonusArea">
                <label for="bonus-amount" class="info-label">Amount: </label><br/>
                <input type="text" id="bonus-amount" name="amount" /><br/>
            </div>
            <div class="rightBonusArea">
                <label for="budget-source" class="info-label">Source: </label><br/>
                <select id="budget-source-combo-bonus" name="budget-source-combo-bonus" class="divComboBox">
                <option value="0" selected="selected">Select a budget</option>
                <?php echo $reqUser->getBudgetCombo(); ?>
                </select>
                <span id="validationMessage"></span>
            </div>
            <div class="clearBonusArea">
            <label for="bonus-reason" class="info-label">Reason: </label><br/>
                <input type="text" id="bonus-reason" name="reason" style="width:95%" /><br/>
                <input type = "hidden" id = "bonus-receiver" name="receiver_id" value = "<?php echo $user-> getId(); ?>" />
            <input type="submit" value="Pay Bonus" />
        </form>

        <!-- Add the bonus history table -->
        <?php if($is_runner){ ?>
        <hr/>
        <table class="bonus-history table-bonus-history">
        <thead>
            <tr style="cursor:arrow;" class="table-hdng">
                <th width="95px">Date</th>
                <th width="50px">Amount</th>
                <th width="80px">Receiver</th>
                <th width="300px">Description</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        </table>
        <?php } ?>

    </div><!-- end of pay bonus div -->
    <div class="hidden" id="loading">
        <img src="images/loader.gif" alt = "" /><span>Please wait..</span>
    </div>
