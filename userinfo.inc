<script type="text/javascript">
    parent.$('#user-info').dialog('option', 'title', 'User Info for <?php echo $user->getNickname(); ?>');
</script>
    <div class="user-info" title="">
        <span id="user-avatar"><img id="avatar" src="<?php echo($user->getAvatar(80, 80)); ?>" title="Profile Picture - <?php echo($user->getNickname()); ?>" /></span>
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

    /* Array comparising
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
?>
        <p class="info-label">
            <?php
            if($user->getCity() != ""){
                echo 'City, ';
            }
            if($countryCodeUrl != ""){
                echo 'Country - ';
            }
            ?>
            Local Time<br />
            <span id="info-currenttime" title="<?php echo $timezoneTable[$user -> getTimezone()]; ?>">
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
            <span id="btn-bonus"><input type="submit" <?php if ($budget <= 0) { echo 'disabled="disabled"'; } ?> id="pay_bonus" value="Pay Bonus" name="pay_bonus" /></span>
            <span id="btn-send-message" class="nickname-ping"><input type="submit" id="send-message" value="Send message" name="send_message" /></span>
        </div>
        <p class="info-label small-text"><br />
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
            <li><a href="chatHistory.php?action=getLatestForNickname&nickname=<?php echo urlencode($user->getNickname()); ?>&num=100&<?php echo time(); ?>">Chat History</a></li>
        </ul>
    <?php } else {?>
        <ul>
            <li><a href="#tabs-1">User Info</a></li>
            <li><a href="userNotes.php?action=getNote&userId=<?php echo $user->getId(); ?>">My Notes</a></li>
            <li><a href="chatHistory.php?action=getLatestForNickname&nickname=<?php echo urlencode($user->getNickname()); ?>&num=100&<?php echo time(); ?>">Chat History</a></li>
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
                <div class='reviewAddLink'>
                    <a href='javascript:;'>Add your review</a>
                </div>
                <div class='reviewEditLink'>
                    <a href='javascript:;'>Edit your review</a>
                </div>
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
                    <input type="checkbox" name="isrunner" value="isrunner" id="info-isrunner" <?php if($user->isRunner()) echo 'checked="checked" '; ?> /><label class="info-label">Runner</label>
                    <input type="checkbox" name="ispayer" value="ispayer" id="info-ispayer" <?php if($user->isPayer()) echo 'checked="checked" '; ?> /><label class="info-label">Payer</label>
                    <input class="admin-submit" type="submit" name="save_roles" value="Save Changes" />
                    <p class="info-label">Payment Information</p>
                    <input type="checkbox" id="approve" name = "w9" <?php if($user->isW9Approved()) echo 'checked="checked"'; ?> <?php if ($user->getHas_W2()) echo 'disabled="disabled"'; ?>/><label class="info-label">W9 Approved</label>
                    <input type="checkbox" id="paypal_verified" name="paypal_verified" <?php if ($user->getPaypal_verified()) echo 'checked="checked"'; ?> <?php if ($user->getHas_W2()) echo 'disabled="disabled"'; ?>/><label class="info-label">PayPal Verified</label>
                    <input type="checkbox" id="w2_employee" name="w2_employee" <?php if ($user->getHas_W2()) echo 'checked="checked"'; ?> /><label class="info-label">W2 Employee</label>
                    <p class = "info-label">Preferred Payment Method</p>
                    <span id="info-payway"><?php echo $user->getPayway() == '' ? $user->getPayway() : 'None selected'; ?></span>
                    <p class = "info-label">PayPal E-Mail</p>
                    <span id="info-paypal"><?php echo ($user->getPaypal_email() == '') ? 'No address on file' : $user->getPaypal_email(); ?></span>
                    <input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>" />
                </form>
            </div><!-- end of admin-left -->
            <div id="admin-right">
                <p class="info-label">Budget</p>
                <span id="info-budget">$<?php echo $user->getBudget(); ?></span>
                <input  class="admin-submit" type="submit" id="give" value="Give Budget..." />
<?php if ($is_payer): ?>
                <p class="info-label">Annual Salary</p>
                <form method="post" action="" name="salary" id="salary">
                    <input type="text" id="annual_salary" name="annual_salary" value="<?php echo $Annual_Salary;?>" />
                    <input class="admin-submit" type="submit" id="save_salary" value="Save salary"  name="save_salary"/>
                    <p class="info-label">Manager</p>
                    <?php echo $filter->getManagerUserSelectboxS("width:180px;"); ?>
                    <input class="admin-submit" type="submit" id="save_manager" value="Save manager" name="save_manager"/>
                    <input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>" />
                    <input type="hidden" name="salary_changed" id="salary_changed" value="0" />
                    <input type="hidden" name="manager_changed" id="manager_changed" value="0" />
                </form>
<?php else: ?>
                <form method="post" action="" name="manager" id="manager">
                    <p class="info-label">Manager</p>
                    <?php echo $filter->getManagerUserSelectboxS("width:180px;"); ?>
                    <input class="admin-submit" type="submit" id="save_manager" value="Save manager" name="save_manager"/>
                    <input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>" />
                </form>
<?php endif; ?>
                <p class="info-label">Referred By</p>
                <form method="post" action="" name="referred_by" id="referred_by">                   
                    <?php echo $filter->getReferrerUserSelectboxS("width:180px;"); ?>
                    <input class="admin-submit" type="submit" id="save_referrer" value="Save referred by" name="save_referrer"/>
                    <input type="hidden" name="userid" id="userid" value="<?php echo $user->getId(); ?>" />
                    <input type="hidden" name="referred_by_changed" id="referred_by_changed" value="0" />
                    <p class="info-label">Status</p>
                    <select id="changeUserStatus" style="width:auto;">
                    <option value="0" <?php echo(($user->getIs_active() == 0) ? 'selected="selected"' : ''); ?>>inactive</option>
                    <option value="1" <?php echo(($user->getIs_active() == 1) ? 'selected="selected"' : ''); ?>>active</option>
                    <option value="2" <?php echo(($user->getIs_active() == 2) ? 'selected="selected"' : ''); ?>>secured</option>
                    </select>
                    <br/>
                </form>
                <span>User last seen: <?php echo relativeTime($user->getTimeLastSeen(), false); ?></span>
            </div><!-- end of admin-right -->
        </div><!-- end of tab 2 -->
<?php
function getProjectList() {
    $query = "SELECT * FROM `".PROJECTS."` WHERE active=1";
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
} else {
    $has_sandbox = false;
}
?>
        <div id="tabs-3" style="clear: both;">
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
        </div><!-- end of tab 3 -->
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

    <div id="loading" style="display:none">
        <img src="images/loader.gif" alt = "" /><span>Please wait..</span>
    </div>
