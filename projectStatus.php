<?php
//  vim:ts=4:et
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
require_once ("config.php");
require_once 'head.html';

Session::check();

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

$projectObj = new Project();
$projectObj->loadByName($project);
$isGithubProject = $projectObj->getRepo_type() == 'git' ? true : false;
$isGitHubConnected = $user->isGithub_connected($projectObj->getGithubId());

if ($isGithubProject) {
    $templateEmail = "project-created-github";
}    

?>
<link type="text/css" href="css/worklist.css" rel="stylesheet" />
<link type="text/css" href="css/workitem.css" rel="stylesheet" />
<link type="text/css" href="css/review.css" rel="stylesheet" />
<link type="text/css" href="css/favorites.css" rel="stylesheet" />
<link type="text/css" href="css/userinfo.css" rel="stylesheet" />
<link type="text/css" href="css/jquery.combobox.css" rel="stylesheet" />
<link type="text/css" href="css/budget.css" rel="stylesheet" />
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
<script type="text/javascript" src="js/github.js"></script>
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
<?php if($isGithubProject) { ?>
    <div class="project-description">
        <p>Your project is being linked to the GitHub repository you specified. 
           Users will be able to bid on your projects once they have validated their GitHub credentials with Worklist. 
        </p>
    </div>
<?php } else { ?>
    <div class="project-description">
        <p>The setup of your project includes a MySQL database, Code Repository with a sample PHP page, and a Sandbox in which you may add to and update your codebase.</p>
    </div>
<?php } ?> 
    <div id="project-status">
<?php if($isGithubProject) { ?>
        <p>
            GitHub Repository linked
        </p>    
        <p>
            Emails sent
        </p>    
        <p>
            Project created!
        </p>    
<?php } else { ?>
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
<?php } ?>
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
        project_id = <?php echo $projectObj->getProjectId(); ?>,
        username = '<?php echo $username; ?>',
        nickname = '<?php echo $nickname; ?>',
        unixname = '<?php echo $unixname; ?>',
        newuser = '<?php echo $newUser; ?>',
        dbuser = '<?php echo $db_user; ?>',
        template = '<?php echo $templateEmail; ?>';
        github_repo_url = '<?php echo $projectObj->getRepository(); ?>';

    GitHub.isConnected = '<?php echo $isGitHubConnected; ?>';
    GitHub.applicationKey = '<?php echo $projectObj->getGithubId(); ?>';

    $(function() {
        WorklistProject.repo_type = '<?php echo $projectObj->getRepo_type(); ?>';
        WorklistProject.init();
        if (!GitHub.validate()) {
            GitHub.handleUserConnect();
        }
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
<?php
include("footer.php");
?>
