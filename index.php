<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

// Force HTTPS 
if(!array_key_exists('HTTPS', $_SERVER)) {
   header("HTTP/1.1 301 Moved Permanently");
   header('Location: https://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
   exit();
}

require_once("config.php");
require_once("class.session_handler.php");
include_once("check_new_user.php");
require_once("send_email.php");
require_once("functions.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Worklist | Build software fast, make money, great community.</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="copyright" content="Copyright (c) 2011 Coffee & Power, Inc.  All Rights Reserved. http://www.coffeeandpower.com" />
<link href="css/CMRstyles.css" rel="stylesheet" type="text/css">
<!--[if IE 6]>
  <link rel="stylesheet" href="css/ie.css" type="text/css" media="all" />
<![endif]-->
<link rel="shortcut icon" type="image/x-icon" href="images/worklist_favicon.png">
<link href="css/LVstyles.css" rel="stylesheet" type="text/css">
<link media="all" type="text/css" href="css/jquery-ui.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="css/smoothness/lm.ui.css"/>
<link rel="stylesheet" type="text/css" href="css/tooltip.css" />
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.12.min.js"></script>
<script type="text/javascript" src="js/jquery.watermark.min.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/lightbox-hc.js"></script>
<script type="text/javascript" src="js/class.js"></script>
<script type="text/javascript" src="js/add-proj-contact.js"></script>

<script src="https://www.google.com/jsapi" type="text/javascript"></script>
<script type="text/javascript">
$(document).ready(function(){
    $(".other-videos a").click(function(){
        var url = "https://www.youtube.com/embed/";
        $("iframe").attr("src", url + $(this).attr("v") + "?version=3");
    });
});
</script>
</head>
<body>
    <div id="outside" class="home">
        <div id="home-header">
            <h1 class="home-header-logo"><a href="<?php echo SERVER_URL; ?>" title="Worklist | Build software fast, make money, great community."><img src="<?php echo SERVER_URL; ?>images/worklist_logo.png" border="0" /></a></h1>
            <div class="home-header-nav">
<?php
if (getSessionUserId() > 0) {
    $name = empty($_SESSION['nickname']) ? getSubNickname($_SESSION['username']) : getSubNickname($_SESSION['nickname']);
    echo "Welcome, <span id='user'> $name </span>  |
        <a href='journal.php' title='Login to our Live Chat Journal'> Journal </a> |
        <a href='worklist.php' title='Worklist'> Worklist </a> |
        <a href='logout.php'>Logout</a>";
} else {
    echo '<a href="login.php">Login</a> | <a href="signup.php">Signup</a>';
}
?>
            </div>
            <div style="float:none; clear:both;"></div>
        </div>
        <div id="left"></div>
        <div id="center">
            <div id="home-slogan">
                <h2>Build software fast!</h2>
                <p class="slogan-text">A marketplace to rapidly build software and websites using a global<br />
                network of developers, designers and testers</p>
            </div>
            <div class="other-videos">
                <p><a href="#" v='DoFfdyIcVKw'>Intro for developers</a><a href="#" v='60-iFFBl4Z4'>Intro for entrepreneurs</a></p>
            </div>
            <div id="home-videos">
                <div class="main-video"><div id="videoDiv">
                    <iframe width="450" height="270" src="https://www.youtube.com/embed/DoFfdyIcVKw?version=3" frameborder="0" hd allowfullscreen></iframe>
                </div></div>
            </div>
            <div id="home-nav-btns">
                <a id="add-projects" href="#">Create New Project</a>
                <a id="browse-projects" href="<?php echo SERVER_URL; ?>projects.php">Browse Projects</a>
                <a href="<?php echo SERVER_URL; ?>worklist.php">See Current Jobs</a>
            </div>
            <?php include('dialogs/add-proj-contact.inc'); ?>
        </div>
        <div id="right"></div>
        <div style="float:none; clear:both;"></div>
        <div id="home-footer">
            <div class="candp-logo"><a href="http://www.coffeeandpower.com" title="Coffee and Power"><img src="<?php echo SERVER_URL; ?>images/mugcp.png" border="0" height="54" /></a><br />
            CoffeeandPower Inc., <?php echo date('Y'); ?>
            </div>
            <div class="home-footer-nav"><a href="http://blog.worklist.net">Worklist blog</a><a href="journal.php">Live chat journal</a></div>
            <div style="float:none; clear:both;"></div>
        </div>
    </div>
<?php include('dialogs/footer-analytics.inc'); ?>
</body>
</html>
