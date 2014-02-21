<?php

class ProjectController extends Controller {
    public function run($project_name) {
        if (empty($project_name)) {
            $this->view = null;
            Utils::redirect('projects');
        }

        $projectName = mysql_real_escape_string($project_name);

        $project = new Project();
        try {
            $project->loadByName($projectName);
        } catch(Exception $e) {
            $error  = $e->getMessage();
            die($error);
        }

        $is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
        $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;

        //get the project owner
        $project_user = new User();
        $project_user->findUserById($project->getOwnerId());

        $userId = getSessionUserId();
        if ($userId > 0) {
            initUserById($userId);
            $user = new User();
            $user->findUserById($userId);
            // @TODO: this is overwritten below..  -- lithium
            $nick = $user->getNickname();
            $userbudget =$user->getBudget();
            $budget = number_format($userbudget);
            $is_owner = $project->isOwner($user->getId());
            $is_admin = $user->getIs_admin();
        } else {
            $is_owner = false;
            $is_admin = false;
        }

        $runners = $project->getRunners();

        if (isset($_REQUEST['save_project']) && ( $is_runner || $is_payer || $is_owner)) {
            $project->setDescription($_REQUEST['description']);
            $project->setWebsite($_REQUEST['website']);
            $project->setTestFlightEnabled(isset($_REQUEST['testflight_enabled']) ? 1 : 0);
            $project->setTestFlightTeamToken($_REQUEST['testflight_team_token']);
            $cr_anyone = ($_REQUEST['cr_anyone']) ? 1 : 0;
            $cr_3_favorites = ($_REQUEST['cr_3_favorites']) ? 1 : 0;
            $cr_project_admin = isset($_REQUEST['cr_project_admin']) ? 1 : 0;
            $cr_users_specified = isset($_REQUEST['cr_users_specified']) ? 1 : 0;
            $cr_job_runner = isset($_REQUEST['cr_job_runner']) ? 1 : 0;
            $internal = isset($_REQUEST['internal']) ? 1 : 0;
            $hipchat_enabled = isset($_REQUEST['hipchat_enabled']) ? 1 : 0;
            $project->setCrAnyone($cr_anyone);
            $project->setCrFav($cr_3_favorites);
            $project->setCrAdmin($cr_project_admin);
            $project->setCrRunner($cr_job_runner);
            $project->setCrUsersSpecified($cr_users_specified);
            $project->setHipchatEnabled($hipchat_enabled);
            $project->setHipchatNotificationToken($_REQUEST['hipchat_notification_token']);
            $project->setHipchatRoom($_REQUEST['hipchat_room']);
            $project->setHipchatColor($_REQUEST['hipchat_color']);
            
            if ($user->getIs_admin()) {
                $project->setInternal($internal);
            }
            
            if ($_REQUEST['logoProject'] != "") {
                $project->setLogo($_REQUEST['logoProject']);
            }
            if (isset($_REQUEST['noLogo']) && $_REQUEST['noLogo'] == "1") {
                $project->setLogo("");
            }
            $project->save();
            // we clear post to prevent the page from redirecting
            $_POST = array();
        }

        $project_id = $project->getProjectId();
        $hide_project_column = true;

        // save,edit,delete roles <mikewasmie 16-jun-2011>
        if ($is_runner || $is_payer || $project->isOwner($userId)) {
            if ( isset($_POST['save_role'])) {
                $args = array('role_title', 'percentage', 'min_amount');
                foreach ($args as $arg) {
                    $$arg = mysql_real_escape_string($_POST[$arg]);
                }
                $role_id = $project->addRole($project_id,$role_title,$percentage,$min_amount);
            }

            if (isset($_POST['edit_role'])) {
                $args = array('role_id','role_title_edit', 'percentage_edit', 'min_amount_edit');
                foreach ($args as $arg) {
                    $$arg = mysql_real_escape_string($_POST[$arg]);
                }
                $res = $project->editRole($role_id, $role_title_edit, $percentage_edit, $min_amount_edit);
            }

            if (isset($_POST['delete_role'])) {
                $role_id = mysql_real_escape_string($_POST['role_id']);
                $res = $project->deleteRole($role_id);
            }
            
        }

        /* Prevent reposts on refresh */
        if (! empty($_POST)) {
            unset($_POST);
            header('Location: ' . $projectName);
            exit();
        }

        $edit_mode = false;
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && ( $is_runner || $is_payer || $is_owner)) {
            $edit_mode = true;
        }

        $this->write('project', $project);
        $this->write('project_user', $project_user);
        $this->write('edit_mode', $edit_mode);
        $this->write('is_owner', $is_owner);

        parent::run();
    }
}

/*
$is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
$is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;

if (empty($_REQUEST['project'])) {
    header( 'Location: ' . SERVER_URL . 'projects.php');
    exit;
}


$projectName = mysql_real_escape_string($_REQUEST['project']);

$project = new Project();
try {
    $project->loadByName($projectName);
} catch(Exception $e) {
    $error  = $e->getMessage();
    die($error);
}
//get the project owner
$project_user = new User();
$project_user->findUserById($project->getOwnerId());

$userId = getSessionUserId();
if ($userId > 0) {
    initUserById($userId);
    $user = new User();
    $user->findUserById($userId);
    // @TODO: this is overwritten below..  -- lithium
    $nick = $user->getNickname();
    $userbudget =$user->getBudget();
    $budget = number_format($userbudget);
    $is_owner = $project->isOwner($user->getId());
    $is_admin = $user->getIs_admin();
} else {
    $is_owner = false;
    $is_admin = false;
}

$runners = $project->getRunners();

if (isset($_REQUEST['save_project']) && ( $is_runner || $is_payer || $is_owner)) {
    $project->setDescription($_REQUEST['description']);
    $project->setWebsite($_REQUEST['website']);
    $project->setTestFlightEnabled(isset($_REQUEST['testflight_enabled']) ? 1 : 0);
    $project->setTestFlightTeamToken($_REQUEST['testflight_team_token']);
    $cr_anyone = ($_REQUEST['cr_anyone']) ? 1 : 0;
    $cr_3_favorites = ($_REQUEST['cr_3_favorites']) ? 1 : 0;
    $cr_project_admin = isset($_REQUEST['cr_project_admin']) ? 1 : 0;
    $cr_users_specified = isset($_REQUEST['cr_users_specified']) ? 1 : 0;
    $cr_job_runner = isset($_REQUEST['cr_job_runner']) ? 1 : 0;
    $internal = isset($_REQUEST['internal']) ? 1 : 0;
    $hipchat_enabled = isset($_REQUEST['hipchat_enabled']) ? 1 : 0;
    $project->setCrAnyone($cr_anyone);
    $project->setCrFav($cr_3_favorites);
    $project->setCrAdmin($cr_project_admin);
    $project->setCrRunner($cr_job_runner);
    $project->setCrUsersSpecified($cr_users_specified);
    $project->setHipchatEnabled($hipchat_enabled);
    $project->setHipchatNotificationToken($_REQUEST['hipchat_notification_token']);
    $project->setHipchatRoom($_REQUEST['hipchat_room']);
    $project->setHipchatColor($_REQUEST['hipchat_color']);
    
    if ($user->getIs_admin()) {
        $project->setInternal($internal);
    }
    
    if ($_REQUEST['logoProject'] != "") {
        $project->setLogo($_REQUEST['logoProject']);
    }
    if (isset($_REQUEST['noLogo']) && $_REQUEST['noLogo'] == "1") {
        $project->setLogo("");
    }
    $project->save();
    // we clear post to prevent the page from redirecting
    $_POST = array();
}

$project_id = $project->getProjectId();
$hide_project_column = true;

// save,edit,delete roles <mikewasmie 16-jun-2011>
if ($is_runner || $is_payer || $project->isOwner($userId)) {
    if ( isset($_POST['save_role'])) {
        $args = array('role_title', 'percentage', 'min_amount');
        foreach ($args as $arg) {
            $$arg = mysql_real_escape_string($_POST[$arg]);
        }
        $role_id = $project->addRole($project_id,$role_title,$percentage,$min_amount);
    }

    if (isset($_POST['edit_role'])) {
        $args = array('role_id','role_title_edit', 'percentage_edit', 'min_amount_edit');
        foreach ($args as $arg) {
            $$arg = mysql_real_escape_string($_POST[$arg]);
        }
        $res = $project->editRole($role_id, $role_title_edit, $percentage_edit, $min_amount_edit);
    }

    if (isset($_POST['delete_role'])) {
        $role_id = mysql_real_escape_string($_POST['role_id']);
        $res = $project->deleteRole($role_id);
    }
    
    // Load roles table id owner <mikewasmike 15-jun 2011>
    $roles = $project->getRoles($project->getProjectId());
}

// Prevent reposts on refresh
if (! empty($_POST)) {
    unset($_POST);
    header('Location: ' . $projectName);
    exit();
}

$edit_mode = false;
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && ( $is_runner || $is_payer || $is_owner)) {
    $edit_mode = true;
}

require_once("head.php");

$meta_title = 'Worklist Project: ' . $project->getName();
$meta_desc = $project->getDescription();
$min_dimensions = 120; // Minimum image dimension some social networks must have before showing thumb
if ($project->getLogo()) {
    list($check_width, $check_height) = getimagesize(UPLOAD_PATH . $project->getLogo());
    if ($check_width >= $min_dimensions && $check_height >= $min_dimensions) {
        $meta_image = 'uploads/' . $project->getLogo();
    }
}
require_once('opengraphmeta.php');
?>
<!-- Add page-specific scripts and styles here, see head.php for global scripts and styles  -->
<link href="css/project.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/jquery/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery/jquery.metadata.js"></script>
<script type="text/javascript" src="js/jquery/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="js/jquery/jquery.tablesorter_desc.js"></script>
<script type="text/javascript" src="js/ajaxupload/ajaxupload.js"></script>
<script type="text/javascript" src="js/paginator.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/paginator.js"></script>
<script type="text/javascript" src="js/project.js"></script>
<script type="text/javascript">
    var projectid = <?php echo !empty($project_id) ? $project_id : "''"; ?>;
    var imageArray = new Array();
    var documentsArray = new Array();
    var edit_mode = <?php echo (int) $edit_mode ?>;
    var is_owner = <?php echo (int) $is_owner ?>;
</script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/html" id="uploadedFiles">
    <div id="accordion">
        <h3>Images (<span class="imageCount"><#= images.length #></span>)</h3>
        <div class="fileimagecontainer">
        <# if (images.length > 0) { #>
            <# for(var i=0; i < images.length; i++) {
            var image = images[i];
            #>
            <div class="filesIcon">
                <a class="attachment" href="<#= image.url #>" data-error_message="<#= image.error #>"><img width="75px" height="75px" src="<#= image.icon #>" /></a>
            </div>
            <div class="filesDescription">
                <# if (image.can_delete == true) { #>
                <a class="removeAttachment" id="fileRemoveAttachment_<#= image.fileid #>" href="javascript:;">Delete</a>
                <# } #>
                <h3 class="edittext" id="fileTitle_<#= image.fileid #>"><#= image.title #></h3>
                <p class="edittextarea" id="fileDesc_<#= image.fileid #>"><#= image.description #></p>
            </div>
            <div class="clear"></div>
            <# } #>
        <# } #>
        </div>
        <h3>Documents (<span class="documentCount"><#= documents.length #></span>)</h3>
        <div class="filedocumentcontainer">
        <# if (documents.length > 0) { #>
            <# for(var i=0; i < documents.length; i++) {
            var doc = documents[i];
            #>
            <div class="filesIcon">
                <a class="docs" href="<#= doc.url #>" data-error_message="<#= doc.error #>" data-fileid="<#= doc.fileid #>" target="_blank"><img width="32px" height="32px" src="<#= doc.icon #>" /></a>
            </div>
            <div class="documents filesDescription">
                <# if (doc.can_delete == true) { #>
                <a class="removeAttachment" id="fileRemoveAttachment_<#= doc.fileid #>" href="javascript:;">Delete</a>
                <# } #>
                <h3 class="edittext" id="fileTitle_<#= doc.fileid #>"><#= doc.title #></h3>
                <p class="edittextarea" id="fileDesc_<#= doc.fileid #>"><#= doc.description #></p>
            </div>
            <div class="clear"></div>
            <# } #>
        <# } #>
        </div>
    </div>
    {{#currentUser.id}}
    <div id="uploadButtonDiv" class="uploadbutton fileUploadButton">
        <span class="smbutton">Attach files</span>
    </div>
    <div class="uploadnotice"></div>
    {{/currentUser.id}}
</script>
<script type="text/html" id="uploadDocument">
    <div class="attachment">
        <span><#= title #></span>
        <span id="fileRemoveAttachment_<#= fileid #>" class="removeAttachment">
            <i class="icon-remove"></i>
        </span>
    </div>
</script>
<title>Project: <?php echo $project->getName(); ?></title>
</head>
<body id="project">
<?php 
    require_once('dialogs/file-templates.inc'); 
    //Popup for breakdown of fees
    require_once('dialogs/popup-fees.inc'); 
    //Popup for budget info
    require_once('dialogs/budget-expanded.inc'); 
    //Popups for tables with jobs from quick links 
    require_once('dialogs/popups-userstats.inc'); 
    //Popup for add role 
    include('dialogs/popup-addrole.inc'); 
    //Popup for viewing role 
    include('dialogs/popup-role-info.inc'); 
    //Popup for edit role 
    include('dialogs/popup-edit-role.inc'); 
    //Popup for TestFlight -->
    include('dialogs/popup-testflight.inc'); 
    //Popup for Adding Project Runner 
    include('dialogs/popup-addrunner.inc'); 
    //Popup for Adding Project Code Reviewer
    include('dialogs/popup-addcodereviewer.inc');
    
    require_once('header.php');
?>
    <?php if ($edit_mode): ?>
        <form name="project-form" id="project-form" action="<?php echo SERVER_URL . $project->getName(); ?>" method="post">
    <?php endif; ?>
    <div id="projectHeader">
        <div class="leftCol">
            <div id="projectLogo<?php echo $edit_mode ? 'Edit' : ''; ?>">
                <img src="<?php echo(!$project->getLogo() ? 'images/emptyLogo.png' : 'uploads/' . $project->getLogo());?>" />
                <?php if ($edit_mode): ?>
                    <input type="checkbox" name="noLogo" id="removeLogo" value="1" />
                    <label for="removeLogo">Remove logo</label>
                <?php endif; ?>
            </div>
            <h2><?php echo $project->getName(); ?></h2>
            <div id="projectUrl">
                <div>
                    <span id="projectUrlLabel">URL:</span>
                    <span id="projectUrlField">
                    <?php if ($edit_mode): ?>
                        <input name="website" id="website" type="text" value="<?php echo $project->getWebsite(); ?>" />                
                    <?php else: ?>
                        <?php if ($project->getWebsite()): ?>
                            <?php echo $project->getWebsiteLink(); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    </span>
                    <?php if ($is_admin): ?>
                        <div id="projectInternal">
                            <input type="checkbox" name="internal" id="internal" <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?> <?php echo $project->getInternal() ? 'checked="checked"' : ''; ?> />
                            <label for="internal">Internal project</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="rightCol">
            <div>
                <div id="contactInfo"><span>Contact Info:</span> 
                    <a href="mailto:<?php echo $project->getContactInfo(); ?>"><?php echo $project->getContactInfo(); ?></a>
                </div>
                <div id="projectRunner"><span>Project started by</span>
                    <a href='userinfo.php?id=<?php echo $project->getOwnerId(); ?>' target="_blank">
                        <span><?php echo $project_user->getNickname(); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div id="projectContent">
        <span class="LV_validation_message LV_invalid upload"></span>
        <div class="leftCol">
            <ul>
                <li id="projectDescription">
                    <h3>Description</h3>
                    <?php if ($edit_mode): ?>
                        <textarea name="description" id="description" /><?php echo $project->getDescription(); ?></textarea>
                    <?php else: ?>
                        <p>
                            <?php echo replaceEncodedNewLinesWithBr(linkify($project->getDescription())); ?>
                        </p>
                    <?php endif; ?>
                    <p id="projectTotalFees">
                        In total, <strong>$<?php echo $project->getTotalFees($project->getProjectId()); ?></strong>
                        has been funded for the development of <?php echo $project->getName(); ?> project.
                    </p>
                    <p id="projectFundingSource">
                        The funding source associated with this project is
                        <strong><?php echo $project->getFundName(); ?></strong>
                    </p>
                </li>
                <li>
                    <h3>Project contributors</h3>
                    <p id="projectContributors">
                        <?php
                            $contributors = $project->getContributors();
                            $count = 0; 
                            foreach($contributors as $contributor) {
                                if ($count) {
                                    echo ', ';
                                }
                                ?><a href="userinfo.php?id=<?php echo $contributor['id']; ?>" target="_blank"><?php echo $contributor['nickname']; ?></a><?php
                                $count++;                                
                            }
                        ?>
                    </p>
                </li>
                <?php if ($is_runner || $is_payer || $is_owner) : ?>
                    <li id="projectRoles">
                        <h3>Team member roles</h3>
                        <div id="for_view">
                            <div id="roles-panel">
                                <table>
                                    <thead>
                                        <tr>
                                            <td>Role</td>
                                            <td>% of fees</td>
                                            <td>Min amount</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($roles)) { ?>
                                        <tr>
                                            <td class="norecords" colspan="3">No roles added.</td>
                                        </tr>
                                    <?php } else { $row = 1;
                                        foreach ($roles as $role) { ?>
                                        <tr class="role 
                                            <?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?> roleitem<?php
                                                 echo '-'.$role['id'];?>">
                                                <script type="data">
                                                    {
                                                        id: '<?php echo $role['id']; ?>', 
                                                        role_title: '<?php echo $role['role_title']; ?>', 
                                                        percentage: '<?php echo $role['percentage']; ?>', 
                                                        min_amount: '<?php echo $role['min_amount']; ?>'
                                                    }
                                                </script>
                                            <td class="roleTitle"><div><span><?php echo $role['role_title'];?></span></div></td>
                                            <td class="rolePercentage"><?php echo $role['percentage'];?></td>
                                            <td class="roleAmmount"><?php echo $role['min_amount'];?></td>
                                        </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                                <div class="buttonContainer">
                                    <input type="submit" value="Add Role" onClick="return showAddRoleForm('bid');" />
                                </div>
        
                            </div>
                        </div>
                    </li>
                <?php endif; ?>
                <li id="projectRunners">
                    <h3>Project runners</h3>
                    <table id="project_runners_table">
                        <thead>
                            <tr>
                                <th <?php echo (($is_admin == 1) || $is_owner) ? 'colspan="2"' : ''; ?>>Who</th>
                                <th># of Jobs</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <?php if (($is_admin == 1) || $is_owner): ?>
                        <div class="buttonContainer">
                            <input type="submit" id="addrunner" value="Add" />
                            <input type="submit" id="removerunner" value="Remove" />
                        </div>
                    <?php endif; ?>
                </li>
                <li id="projectCodeReviewRights">
                    <h3>Code reviews <span>are allowed from</span></h3>
                    <div>
                        <input id="cr_anyone_field" name="cr_anyone" type="checkbox" class="code_review_chks"
                            <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?> value="1" 
                            <?php echo ($project->getCrAnyone() > 0) ? 'checked="checked"' : '' ; ?> />
                        <label for="cr_anyone_field">Anyone</label>
                    </div>
                    <div>
                        <input id="cr_3_favorites_field" name="cr_3_favorites" type="checkbox" class="code_review_chks"
                        <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?> value="1" 
                        <?php echo ($project->getCrFav() > 0) ? 'checked="checked"' : '' ; ?> />
                        <label for="cr_3_favorites_field">Anyone who is trusted by more than [3] people</label>
                    </div>
                    <div>
                        <input id="cr_project_admin_field" name="cr_project_admin" type="checkbox" class="code_review_chks"
                        <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?> value="1" 
                        <?php echo ($project->getCrAdmin() > 0) ? 'checked="checked"' : '' ; ?> />
                        <label for="cr_project_admin_field">Anyone who is trusted by the project admin</label>
                    </div>
                    <div>
                        <input id="cr_job_runner_field" name="cr_job_runner" type="checkbox" class="code_review_chks"
                        <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?> value="1" 
                        <?php echo ($project->getCrRunner() > 0) ? 'checked="checked"' : '' ; ?> />
                        <label for="cr_job_runner_field">Anyone who is trusted by the job manager</label>
                    </div>
                    <div>
                        <input id="cr_users_specified_field" name="cr_users_specified" type="checkbox"
                        <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?> value="1" 
                        <?php echo ($project->getCrUsersSpecified() > 0) ? 'checked="checked"' : '' ; ?> />
                        <label for="cr_users_specified_field">Use only code reviewers listed below</label>
                    </div>
                    <table id="codeReviewers">
                        <thead>
                            <tr>
                                <th <?php echo (($is_admin == 1) || $is_owner) ? 'colspan="2"' : ''; ?>>Who</th>
                                <th># of Jobs</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <?php if (($is_admin == 1) || $is_owner): ?>
                        <div class="buttonContainer">
                            <input type="submit" id="addcodereviewer" value="Add" />
                            <input type="submit" id="removecodereviewer" value="Remove" />
                        </div>
                    <?php endif; ?>
                </li>
                <li id="hipchat">
                    <h3>HipChat</h3>
                    <div>
                        <div>
                            <input type="checkbox" name="hipchat_enabled" id="hipchat_enabled"
                                <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?>
                                <?php echo ($project->getHipchatEnabled()) ? 'checked="checked"' : '' ; ?> />
                            <label for="hipchat_enabled">Enabled</label>
                        </div>
                        <?php if($edit_mode == true) { ?>
                            <div>
                                <label for="hipchat_notification_token">Notification Token</label>
                                <input type="text" name="hipchat_notification_token" id="hipchat_notification_token" value="<?php echo $project->getHipchatNotificationToken(); ?>" />
                            </div>
                            <div>
                                <label for="hipchat_room">Room</label>
                                <input type="text" id="hipchat_room" name="hipchat_room" value="<?php echo $project->getHipchatRoom(); ?>" />                        
                            </div>
                            <div>
                                <label for="hipchat_color">Message Color</label>
                                <?php
                                    foreach ($project->getHipchatColorsArray() as $color) {
                                        $selected = '';
                                        if ($project->getHipchatColor() == $color) {
                                            $selected = 'checked="checked"';
                                        }
                                        echo "<div><label><input name=\"hipchat_color\" type=\"radio\"\
                                            $selected value=\"$color\" />$color</label></div>";
                                    }
                                ?>
                            </div>
                        <?php } ?>
                    </div>
                </li>  
                <li id="projectTestFlight">
                    <h3>TestFlight</h3>
                    <div>
                        <input type="checkbox" name="testflight_enabled" id="testflight_enabled"
                            <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?>
                            <?php echo ($project->getTestFlightEnabled()) ? 'checked="checked"' : '' ; ?> />
                        <label for="testflight_enabled">Enabled</label>
                    </div>
                    <?php if ($edit_mode): ?>
                        <label for="testflight_team_token">Team token</label>
                        <input name="testflight_team_token" id="testflight_team_token" type="text" value="<?php echo $project->getTestFlightTeamToken(); ?>" />
                    <?php else: ?> 
                        <?php if (($is_runner || $is_owner) && $project->getTestFlightEnabled() && $project->getTestFlightTeamToken()) : ?>
                            <div class="buttonContainer">
                                <input id="testFlightButton" type="submit" onClick="javascript:;" value="TestFlight" />
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
        <div class="rightCol">
            <ul>
                <li>
                    <h3>Repository</h3>
                    <p id="projectRepository">
                        We use <a href="http://en.wikipedia.org/wiki/Apache_Subversion" target="_blank">svn</a>
                        or <a href="http://en.wikipedia.org/wiki/Git_(software)" target="_blank">git</a>
                        for <a href="http://en.wikipedia.org/wiki/Distributed_development" target="_blank">distributed development</a>.
                        Each project has its own repository. The repository for this project is:
                        <a id="projectRepositoryUrl" target="_blank" " href="<?php echo $project->getRepoUrl(); ?>"><?php echo $project->getRepoUrl(); ?></a>                        
                    </p>
                </li>
                <li id="projectTotalJobs">
                    <h3>Total jobs:</h3>
                    <span id="total_jobs_stats"><?php echo $project->getTotalJobs(); ?></span>
                </li>
                <li id="projectAverageBid">
                    <h3>Average bid:</h3>
                    <span id="avg_bid_per_job_stats" title="Average amount of accepted Bid per Job">$<?php echo number_format($project->getAvgBidFee(), 2); ?></span>
                </li>
                <li id="projectAverageJobTime">
                    <h3>Average job time:</h3>
                    <span id="avg_job_time_stats" title="Average time from Bid Accept to being Paid"><?php echo $project->getAvgJobTime(); ?></span>
                </li>
                <li id="projectActiveJobs">
                    <?php 
                    $jobs = $project->getActiveJobs(); 
                    if($jobs) { ?>
                        <h3><?php echo (count($jobs)); ?> active job<?php echo (count($jobs) == 1 ? '' : 's'); ?></h3>
                    <?php } else { ?>
                        <h3>no active jobs</h3>
                    <?php } ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Job ID & Summary</th>
                                <th>Status</th>
                                <th>Sandbox</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if($jobs) {
                            $row = 1;
                            foreach ($jobs as $job) { ?>
                                <tr class="job <?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?>">
                                    <td class="projectJobLink">
                                        <a id="worklist-<?php echo $job['id']; ?>" target="_blank"
                                          href="<?php echo SERVER_URL . 'job/' . $job['id'] ?>">
                                            <?php echo $job['id'] . ' <span>' . $job['summary'] . '</span>'; ?>
                                        </a>
                                        
                                    </td>
                                    <td class="projectJobStatus"><?php echo $job['status'] ?></td>
                                    <td class="projectJobSandbox"><?php echo empty($job['sandbox']) || $job['sandbox'] == 'N/A' ? '' : '<a href="' . $job['sandbox'] . '" target="_blank">View</a>' ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="norecords" colspan="3">No records found.</td>
                            </tr>
                        <?php }?>
                        </tbody>
                    </table>
                    <p id="viewHistory">
                        <a href="<?php echo SERVER_URL; ?>jobs?project=<?php echo $project->getName(); ?>&user=&status=0" target="_blank">View history of jobs</a>
                        for the <?php echo $project->getName(); ?> project 
                    </p>
                </li>
                <?php if ($is_owner) : ?>
                    <li id="projectPayments">
                        <h3>Payment summary</h3>
                        <div id="for_view">
                            <div id="payment-panel">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Payee</th>
                                            <th>Job#</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if($payments = $project->getPaymentStats()) {
                                        $row = 1;
                                        foreach ($payments as $payment) { ?>
                                            <tr class="payment <?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?>">
                                                <td><a href="userinfo.php?id=<?php echo $payment['id']?>" target="_blank"><?php echo $payment['nickname']?></a></td>
                                                <td>
                                                    <a id="worklist-<?php echo $payment['worklist_id']?>" class="payment-joblink" target="_blank"
                                                          href="job/<?php echo $payment['worklist_id']; ?>">
                                                        #<?php echo $payment['worklist_id']?>
                                                    </a>
                                                </td>
                                                <td>$<?php echo $payment['amount']?></td>
                                                <td><?php echo (($payment['paid']==1) ? "PAID" : "UNPAID")?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td class="norecords" colspan="4">No records found.</td>
                                        </tr>
                                    <?php }?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>
                <li>
                    <div id="uploadPanel"></div>
                </li>
            </ul>
        </div>
    </div>
    <div id="modeSwitch" class="buttonContainer">
    <?php if ( $is_runner || $is_payer || $is_owner) : ?>
        <?php if ($edit_mode) : ?>
            <div id="buttonHolder">
                <input class="left-button" type="submit" id="cancel_project_edit" name="cancel" value="Cancel">
                <input class="right-button" type="submit" id="save_project" name="save_project" value="Save project details">
                <input type="hidden" value="" name="logoProject">
            </div>
        <?php else: ?>
            <div id="buttonHolder">
                <input class="left-button" type="submit" id="edit_project" name="edit" value="Edit project details">
            </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>
    <?php if ($edit_mode): ?>
        </form>
    <?php endif; ?>
<?php include("footer.php"); ?>
*/
