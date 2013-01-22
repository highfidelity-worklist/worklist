<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

require_once ("functions.php");

if (!isset($is_runner)) {
    $is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
}
if (!isset($is_payer)) {
    $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
}

$userId = getSessionUserId();
$lovemachineLink = SENDLOVE_URL . '/';
$linkTarget = '';
$currentPage = basename($_SERVER['SCRIPT_NAME']); 
?>
<!-- Welcome, login/out -->
        <div id="welcome">
            <div id="welcomeInside">
                <div class="leftMenu">
                    <a href="worklist.php" <?php if ($currentPage == 'journal.php') {?>target="_blank"<?php } ?> ><div class="headerButton worklistBtn">Worklist</div></a>
                    <div class="headerButtonSeparator">&nbsp;</div>
                    <a href="journal.php" <?php if ($currentPage != 'journal.php') {?>target="_blank"<?php } ?> ><div class="headerButton chatBtn">Chat</div></a>
                    <div class="headerButtonSeparator">&nbsp;</div>
                    <a href="team.php"><div class="headerButton teamBtn">Team</div></a>
                    <div class="headerButtonSeparator">&nbsp;</div>
                    <a href="reports.php"><div class="headerButton reportsBtn">Reports</div></a>
                    <div class="headerButtonSeparator">&nbsp;</div>
                    <a href="help.php"><div class="headerButton helpBtn">Help</div></a>
                    <div class="headerButtonSeparator">&nbsp;</div>
                    <a href="projects.php"><div class="headerButton projectsBtn">Projects</div></a>
                    <div class="headerButtonSeparator">&nbsp;</div>
                    <a href="<?php echo WIKI_URL ?>"><div class="headerButton wikiBtn">Wiki</div></a>
                </div>
                <div class="rightMenu">
<?php if (isset($_SESSION['username'])) { ?>
                    <div class="loggedIn">
                        <a class="headerUserName" href="userinfo.php?id=<?php echo $userId; ?>" target="_blank">
                            <span id="user" class=''><?php echo $_SESSION['nickname']; ?></span>
                        </a>
                        <div class="headerButtonSeparator">&nbsp;</div>&nbsp;
                        <a href='javascript:;' class='following'><div>Following</div></a>
                        <div class="headerButtonSeparator">&nbsp;</div>
                        <a href='javascript:;' class='budget'><div class="headerButton earningsBtn"></div></a>
                        <div class="headerButtonSeparator">&nbsp;</div>
                        <a href='settings.php'><div class="headerButton settingsBtn"></div></a>
                        <div class="headerButtonSeparator">&nbsp;</div>
                        <a href='logout.php'><div class="headerButton logoutBtn"></div></a>
                    </div>
<?php } else { ?>
                    <div class="loggedOut">
                        <div class="headerButton loginBtn"><a href='login.php'>Login</a></div>
                        <div class="headerButtonSeparator">&nbsp;</div>
                        <div class="headerButton loginBtn bold"><a href='signup.php'>Sign Up</a>
                    </div>
                </div>
<?php
    }
    $return_from_getfeesums = true;
    include 'getfeesums.php';
?>
                <div class="clear"></div>
            </div>
        </div>
        </div>
        <div id="outside">
