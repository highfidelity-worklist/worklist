<?php
/*
 * Copyright (c) 2011, LoveMachine Inc.
 * All Rights Reserved.
 *
 * http://www.lovemachineinc.com
 *
 * Development History:
 * 2011-07-30   #14907      Leo
 *
 */
error_reporting(E_ALL);
ob_start();
include("config.php");
include("class.session_handler.php");
include("check_new_user.php");
include("functions.php");

$userId = getSessionUserId();
$is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
$is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;

$selectedLetter = isset($_REQUEST['letter']) ? $_REQUEST['letter'] : "all";
$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;

/************************ HTML layout begins here  **************************/
include("head.html");
include("opengraphmeta.php");
?>
<title>Worklist Projects</title>
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<link href="css/welcome.css" rel="stylesheet" type="text/css" >
<link href='https://fonts.googleapis.com/css?family=Cabin:400,700' rel='stylesheet' type='text/css'>
<link rel="shortcut icon" type="image/x-icon" href="images/worklist_favicon.png">

<script type="text/javascript" src="js/jquery.timeago.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>

<script type="text/javascript">


    $(document).ready(function() {
     })
</script>
<style>

</style>
</head>

<body>
<?php
    require_once('header.php');
    require_once('format.php');
?>
<div id="welcomeContent">
    <div id="welcomeTop">
        <div id="welcomeLeftTop" class="welcomeRed">
            WELCOME TO 
            <span class="welcomeBold">
            THE NEW DEVELOPER NETWORK.
            </span>
        </div>
        <div id="welcomeRightTop">
            <div>
                <div id="welcomeRightTopArrow">
                </div>
                <div>
                I'm a<br/>
                <span class="welcomeRed">developer,</span><br/>
                <a href="signup.php">sign me up!</a>
                </div>
            </div>
        </div>
    </div>
    <div id="welcomeMiddle">
        <div id="welcomeLeftMiddle">
            <div id="welcomeLeftMiddleLine">
            </div>
        </div>
        <div id="welcomeRightMiddle"></div>
    </div>
    <div id="welcomeBottom">
        <div id="welcomeLeftBottom">
            Worklist is 
            <span class="welcomeRed welcomeBold">
                a marketplace to rapidly prototype software
            </span>
             and websites using 
            <span class="welcomeBold">
                a global network of developers, designers, and project managers.
            </span>
        </div>
        <div id="welcomeRightBottom">
            <div>
                <div id="welcomeRightTopArrow">
                </div>
                <div>
                    I'm an<br/>
                    <span class="welcomeRed">entrepreneur,</span><br/>
                    I want to <a href="signup.php">start<br/>
                     a project</a>
                </div>
            </div>
        </div>
    </div>
    <div id="developer" class="welcomeBold">
        <div id="developerTop" class="welcomeRed">
            DEVELOPERS:
        </div>
        <div id="developerMiddle">
            <div id="developerMiddle1">
            </div>
            <div id="developerMiddle2">
            </div>
            <div id="developerMiddle3">
            </div>
            <div id="developerMiddle4">
            </div>
        </div>
        <div id="developerBottom">
            <span class="welcomeRed">Write code,</span>
             wear pajamas,
            <span class="welcomeRed"> get paid.</span>
            <span class="welcomeItalic">It's that simple!</span>
        </div>
    </div>
    <div id="collaborate" >
        <div id="collaborateLeft" >
            <div id="collaborateLeft1" >
            </div>
            Collaborate remotely with other developers across the globe on a 
            variety of projects:<br/>
            apps, software, <br/>and websites.
        </div>
        <div id="collaborateMiddle">
            <div  id="collaborateMiddle1">
            </div>
            <div>
                Work whenever and wherever you like.
            </div>
            <div id="collaborateMiddle3">
            </div>
            <div id="collaborateMiddle4" class="welcomeBold">
                Set your own prices
            </div>
            <div id="collaborateMiddle5" >
                and bid on projects.
            </div>
        </div>
        <div id="collaborateRight">
            <div id="collaborateRight1" class="welcomeRed">
                <a href="signup.php" class="welcomeRed">Sign up now</a>
                <div>
                Get paid
                </div>
            </div>
            <div id="collaborateArrow">
            </div>
        </div>
    </div>
    <div id="fast">
        <div id="fastTop" class="welcomeRed welcomeBold">
            Fast payment:
        </div>
        <div id="fastBottom">
            Release code and
            <span class="welcomeBold">receive payments twice a week.</span>
        </div>
    </div>
    <div id="developerEnd">
        <div class="welcomeBold">DEVELOPERS:&nbsp;</div>
        <div id="collaborateArrowRight"></div>
        <div>
            &nbsp;<a href="signup.php" class="welcomeRed welcomeBold">SIGN UP NOW</a>
        </div>
    </div>
    <div id="entrepreneur" class="welcomeBold">
        <div id="entrepreneurTop" class="welcomeRed">
            ENTREPRENEURS:
        </div>
        <div id="entrepreneurMiddle">
            <div id="entrepreneurMiddle1">
            </div>
            <div id="entrepreneurMiddle2">
            </div>
            <div id="entrepreneurMiddle3">
            </div>
        </div>
        <div id="entrepreneurBottom">
            <span class="welcomeRed">Skilled developers.</span>
             The right budget.
            <span class="welcomeRed">The working prototype.</span>
        </div>
    </div>
    <div id="entrepreneur2">
        <div id="entrepreneur2Left">
            <div class="bullet">
            </div>
            <div class="bulletText">
                Go from concept to prototype
                <span class="welcomeBold">without hiring staff.</span>
            </div>
            <div class="bullet">
            </div>
            <div class="bulletText bulletText2">
                Crowdsource large projects for
                <span class="welcomeBold">agile develoment and fast turnaround.</span>
            </div>
            
			
        </div>
        <div id="entrepreneur2Right">
            <div class="bullet">
            </div>
            <div class="bulletTextRight">
                Pick the proper talent for your specific project.
            </div>
            <div class="bullet">
            </div>
            <div class="bulletTextRight">
                Gain creative insight and expertise from tech experts across the globe.
            </div>
			
			
	
        </div>
		
		
		<div class="getstartedContainer">
			<div class="getstarted">
                Get started quickly:
            </div>
			<div class="signupnowContainer">
				<div id="collaborateArrowRight"></div>
				<div class="signupnow">
					&nbsp;<a href="signup.php" class="welcomeRed welcomeBold">SIGN UP NOW</a>
				</div>
			</div>
			
		</div>
    
	
    </div>
    
<?php
//-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
include("footer.php");
?>
</div>
