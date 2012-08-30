<?php
//  vim:ts=4:et
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
require_once ("config.php");
require_once ("functions.php");
require_once 'class.session_handler.php';
require_once 'send_email.php';
require_once 'head.html';

$project =  isset($_REQUEST['project']) ? $_REQUEST['project'] : null;
$errorOut = false;
$userId = getSessionUserId();
if (!$userId) {
    $errorOut = "You must be logged in to access this page";
}
$user = new User();
$user->findUserById($userId);
$nickname = $user->getNickname();
$username = $user->getUsername();
$unixname = $user->getUnixusername() ? $user->getUnixusername() : $nickname;
$db_user = (strlen($project) > 9) ? substr($project, 0, 9) . date('w') . date('s') : $project;
$userHasSandbox = $user->getHas_sandbox();
if ($userHasSandbox) {
    $newUser = false;
    $templateEmail = "project-created-existingsb";
} else {
    $newUser = true;
    $templateEmail = "project-created-newsb";
}
if (empty($project) || empty($username) || empty($nickname) || empty($unixname)) {
    $errorOut = "Not all information required to create the project is available.";
}
?>
<link type="text/css" href="css/worklist.css" rel="stylesheet" />
<link type="text/css" href="css/workitem.css" rel="stylesheet" />
<link type="text/css" href="css/review.css" rel="stylesheet" />
<link type="text/css" href="css/favorites.css" rel="stylesheet" />
<link type="text/css" href="css/userinfo.css" rel="stylesheet" />
<link type="text/css" href="css/jquery.combobox.css" rel="stylesheet" />
<link type="text/css" href="css/budget.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/datepicker.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/workitem.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="js/jquery.tallest.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/jquery.autogrow.js"></script>
<script type="text/javascript" src="js/jquery.blockUI.js"></script>
<script type="text/javascript" src="js/class.js"></script>
<script type="text/javascript" src="js/jquery.scrollTo-min.js"></script>
<script type="text/javascript" src="js/jquery.combobox.js"></script>
<script type="text/javascript" src="js/review.js"></script>
<script type="text/javascript" src="js/favorites.js"></script>
<script type="text/javascript" src="js/budget.js"></script>
<script type="text/javascript" src="js/projects.js"></script>
<script type="text/javascript">
function resizeIframeDlg() {
    var bonus_h = $('#user-info').children().contents().find('#pay-bonus').is(':visible') ?
    $('#user-info').children().contents().find('#pay-bonus').closest('.ui-dialog').height() : 0;

    var dlg_h = $('#user-info').children().contents().find('html body').height();

    var height = bonus_h > dlg_h ? bonus_h+35 : dlg_h+30;

    $('#user-info').animate({height: height});
}

$(document).ready(function() {
    $('#user-info').dialog({
        autoOpen: false,
        resizable: false,
        modal: false,
        show: 'fade',
        hide: 'fade',
        width: 840,
        height: 480
    });
});

</script>
<style>
#welcomeInside .worlistBtn {
    color: #ffffff;
}
</style>
<?php
require_once('header.php');
require_once('format.php');
?>

<h1 class="newProject">Project <?php echo $project; ?> setup underway...</h1>
<div class="project-description">
    <p>The setup of your project includes a MySQL database, Code Repository with a sample PHP page, and a Sandbox in which you may add to and update your codebase.</p>
</div>

<div id="project-status">
    <p id="db-status">
        Creating  MySQL Database with sample table ...
    </p>
    <p id="repo-status">
        Creating  Repository with sample php page ...
    </p>
    <p id="sandbox-status">
        Creating Sandbox ...
    </p>
    <p id="emails-status">
        E-Mailing credentials ...
    </p>
</div>
<div id="project-completed">
    <h1>Welcome to the Worklist!</h1>
    <p>Your project details can be viewed and updated here: <strong><a href="<?php echo SERVER_URL, $project; ?>"><?php echo $project; ?></a></strong></p>
</div>

<?php
if (!$errorOut) {
?>
<script type="text/javascript">
    var projectName = '<?php echo $project; ?>',
        username = '<?php echo $username; ?>',
        nickname = '<?php echo $nickname; ?>',
        unixname = '<?php echo $unixname; ?>',
        newuser = '<?php echo $newUser; ?>',
        dbuser = '<?php echo $db_user; ?>',
        template = '<?php echo $templateEmail; ?>';
        
    $(function(){
        WorklistProject.init();
    });
    
</script>
<?php
} else {
?>
<script type="text/javascript">
    $('#project-status').html('<h3>Something went wrong! <?php echo $errorOut; ?></h3>');
</script>
<?php
}
?>
<!-- Include User Info -->
<div id="user-info" title="User Info"></div>
<?php
include("footer.php");
?>
