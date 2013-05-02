<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 *
 * Development History:
 * 2011-07-30   #14907      Leo
 *
 */

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

$user = new User();
$user->findUserById($userId);

?>
<title>Projects - Worklist: Develop software fast.</title>
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<link href="css/projects.css" rel="stylesheet" type="text/css" >
<link href="css/responsive.css" rel="stylesheet" type="text/css" >
<link rel="shortcut icon" type="image/x-icon" href="images/worklist_favicon.png">

<script type="text/javascript" src="js/jquery.timeago.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/project.js"></script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>
<script type="text/javascript" src="js/budget.js"></script>
<script type="text/javascript" src="js/plugins/jquery.infinitescroll.min.js"></script>
<script type="text/javascript" src="js/github.js"></script>
<style>
#welcomeInside .projectsBtn {
    color: #ffffff;
}
</style>
</head>
<body>
<?php
    require_once('header.php');
    require_once('format.php');
?>
    <div class="project-wrapper">
        <a href="javascript:" id="add-project" title="Add my project"><span>+</span> New project</a>
        <h1><strong>PROJECTS:</strong> Active in the past 90 days</h1>

        <section id="projects"></section>
        <a id="all-projects" href="javascript:">All other projects</a>
        <nav id="page-nav">
            <!-- this link is here because infinitescroll requires it -->
            <a href="getprojects.php?page=2"></a>
        </nav>
        
    </div>
<?php
    /**
     * Popups
     */
    // breakdown of fees
    require_once('dialogs/popup-fees.inc');
    // add project dialog
    // budget info
    require_once('dialogs/budget-expanded.inc');
    // transferred budget
    require_once('dialogs/budget-transfer.inc');
    require_once('footer.php');
