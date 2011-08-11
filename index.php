<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

require_once("config.php");
require_once("class.session_handler.php");
include_once("check_new_user.php");
require_once("send_email.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Worklist | Fast pay for your work, open codebase, great community.</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="copyright" content="Copyright (c) 2009, LoveMachine Inc.  All Rights Reserved. http://www.lovemachineinc.com ">
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
<script type="text/javascript" src="js/jquery-1.5.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.12.min.js"></script>
<script type="text/javascript" src="js/jquery.watermark.min.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/lightbox-hc.js"></script>
<script type="text/javascript" src="js/class.js"></script>
<script type="text/javascript" src="js/add-proj-contact.js"></script>

<script type="text/javascript">

$(document).ready(function() {
    // uncomment this once the videos are ready
    // the main videoID needs to be loaded here so that you can load it on page load
    //google.setOnLoadCallback(_run("_lotxuUzI5Y"));
});

</script>

<!-- youtube js -->
<script src="http://www.google.com/jsapi" type="text/javascript"></script>
<script type="text/javascript">
  google.load("swfobject", "2.1");
</script>
<script type="text/javascript">
/*
* Change out the video that is playing
*/

// Update a particular HTML element with a new value
function updateHTML(elmId, value) {
    document.getElementById(elmId).innerHTML = value;
}

// Loads the selected video into the player.
function loadVideo(videoID) {
    if(ytplayer) {
      ytplayer.loadVideoById(videoID);
    }
}

// This function is called when an error is thrown by the player
function onPlayerError(errorCode) {
    alert("An error occured of type:" + errorCode);
}

// This function is automatically called by the player once it loads
function onYouTubePlayerReady(playerId) {
    ytplayer = document.getElementById("ytPlayer");
    ytplayer.addEventListener("onError", "onPlayerError");
}

// The "main method" of this sample. Called when someone clicks "Run".
function loadPlayer(videoID) {
    // The video to load
    //var videoID = "ylLzyHk54Z0"
    // Lets Flash from another domain call JavaScript
    var params = { allowScriptAccess: "always", wmode: "transparent" };
    // The element id of the Flash embed
    var atts = { id: "ytPlayer" };
    // All of the magic handled by SWFObject (http://code.google.com/p/swfobject/)
    swfobject.embedSWF("http://www.youtube.com/v/" + videoID +
                       "&enablejsapi=1",
                       "videoDiv", "450", "270", "8", null, null, params, atts);
}
function _run(videoID) {
    loadPlayer(videoID);
}

</script>
</head>
<body>
    <div id="outside" class="home">
        <div id="home-header">
            <h1 class="home-header-logo"><a href="<?php echo SERVER_URL; ?>" title="Worklist | Fast pay for your work, open codebase, great community."><img src="<?php echo SERVER_URL; ?>images/worklist_logo.png" border="0" /></a></h1>
            <div class="home-header-nav"><a href="<?php echo SERVER_URL; ?>login.php">Login</a> | <a href="<?php echo SERVER_URL; ?>signup.php">Signup</a></div>
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
                <p><a href="#" onclick="load();">What is Worklist?</a><a href="#" onclick="load();">How does it work?</a><a href="#" onclick="load();">Why is it better?</a></p>
            </div>
            <div id="home-videos">
                <div class="main-video"><div id="videoDiv"></div></div>
            </div>
            <div id="home-nav-btns">
                <a id="add-projects" href="#">Create New Project</a>
                <a id="browse-projects" href="<?php echo SERVER_URL; ?>projects.php">Browse Projects</a>
            </div>
            <?php include('dialogs/add-proj-contact.inc'); ?>
        </div>
        <div id="right"></div>
        <div style="float:none; clear:both;"></div>
        <div id="home-footer">
            <div class="candp-logo"><a href="http://www.coffeeandpower.com" title="Coffee and Power"><img src="<?php echo SERVER_URL; ?>images/mugcp.png" border="0" height="54" /></a><br />
            CoffeeandPower Inc., <?php echo date('Y'); ?>
            </div>
            <div class="home-footer-nav"><a href="http://blog.worklist.net">Worklist blog</a><a href="./journal">Live chat journal</a></div>
            <div style="float:none; clear:both;"></div>
        </div>
    </div>
<?php include('dialogs/footer-analytics.inc'); ?>
</body>
</html>
