<?php
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//  vim:ts=4:et

require_once ("config.php");
require_once ("class.session_handler.php");
require_once ("functions.php");
require_once ("send_email.php");

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
    $isGitHubConnected = $user->getGithub_connected();
    $GitHubToken = $isGitHubConnected ? $user->getGithub_token() : false;
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
    $project->setTestFlightTeamToken($_REQUEST['testflight_team_token']);
    $cr_anyone = ($_REQUEST['cr_anyone']) ? 1 : 0;
    $cr_3_favorites = ($_REQUEST['cr_3_favorites']) ? 1 : 0;
    $cr_project_admin = isset($_REQUEST['cr_project_admin']) ? 1 : 0;
    $cr_job_runner = isset($_REQUEST['cr_job_runner']) ? 1 : 0;
    $internal = isset($_REQUEST['internal']) ? 1 : 0;
    $project->setCrAnyone($cr_anyone);
    $project->setCrFav($cr_3_favorites);
    $project->setCrAdmin($cr_project_admin);
    $project->setCrRunner($cr_job_runner);
    
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

/* Prevent reposts on refresh */
if (! empty($_POST)) {
    unset($_POST);
    header('Location: ' . APP_BASE . '/' . $projectName);
    exit();
}

$edit_mode = false;
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && ( $is_runner || $is_payer || $is_owner)) {
    $edit_mode = true;
}

/*********************************** HTML layout begins here  *************************************/
require_once("head.html");


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
<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/project.css" rel="stylesheet" type="text/css" />
<link href="css/ui.toaster.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="js/paginator.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/jquery.tabSlideOut.v1.3.js"></script>
<script type="text/javascript" src="js/ui.toaster.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>
<script type="text/javascript" src="js/paginator.js"></script>
<script type="text/javascript" src="js/budget.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter_desc.js"></script>
<script type="text/javascript">
    var userId = user_id = <?php echo getSessionUserId(); ?>;
    var projectid = <?php echo !empty($project_id) ? $project_id : "''"; ?>;
    var imageArray = new Array();
    var documentsArray = new Array();

    $(document).ready(function() {
        // get the project files
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                userid: user_id,
                action: 'getFilesForProject'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    var images = data.data.images;
                    var documents = data.data.documents;
                    for (var i=0; i < images.length; i++) {
                        imageArray.push(images[i].fileid);
                    }
                    for (var i=0; i < documents.length; i++) {
                        documentsArray.push(documents[i].fileid);
                    }
                    var files = $('#uploadedFiles').parseTemplate(data.data);
                    $('#uploadPanel').append(files);
                    $('#accordion').fileUpload({images: imageArray, documents: documentsArray});
                    $('#accordion').bind( "accordionchangestart", function(event, ui) {
                        $('#uploadButtonDiv').appendTo(ui.newContent);
                        $('#uploadButtonDiv').css('display', 'block');
                    });
                }
            }
        });
    
        // Get the project runners
        getProjectRunners = function() {
            $('#projectRunners tbody').html('Loading ...');
            $.ajax({
                type: 'post',
                url: 'jsonserver.php',
                data: {
                    projectid: projectid,
                    action: 'getRunnersForProject'
                },
                dataType: 'json',
                success: function(data) {
                    $('#projectRunners tbody').html('');
                    $('#remove-runner tbody').html('');
                    if (data.success) {
                        runners = data.data.runners;
                        var html = '';
                        if (runners.length > 0) {
                            for(var i=0; i < runners.length; i++) {
                                var runner = runners[i];
                                html =
                                    '<tr class="runner row' + ((i+1) % 2 ? 'odd' : 'even') + '">' +
                                        <?php if ($is_admin || $is_owner): ?>
                                        '<td class="runnerRemove">' + (runner.owner ? '' : '<input type="checkbox" name="runner' + runner.id + '" />') + '</td>' +
                                        <?php endif; ?>
                                        '<td class="runnerName"><a href="userinfo.php?id=' + runner.id + '" target="_blank">' + runner.nickname + '</a></td>' +
                                        '<td class="runnerJobCount">' + runner.totalJobCount + '</td>' +
                                        '<td class="runnerLastActivity">' + (runner.lastActivity ? runner.lastActivity : '') + '</td>' +
                                    '</tr>'
                                $('#projectRunners tbody').append(html);
                            }
                        }
                    }
                }
            });
        }
        getProjectRunners();
        
        $('.add-runner').autocomplete('getusers.php', {
            multiple: false,
            extraParams: { nnonly: 1 }
        });
        
        $('#addRunner-form').submit(function(event) {
            openNotifyOverlay('<span>Adding runner to your project...</span>', false);
            $.ajax({
                type: 'post',
                url: 'jsonserver.php',
                data: {
                    projectid: projectid,
                    nickname: $('.add-runner').val(),
                    action: 'addRunnerToProject'
                },
                dataType: 'json',
                success: function(data) {
                    $('.add-runner').val('');
                    closeNotifyOverlay();
                    openNotifyOverlay('<span>' + data.data + '<span>', true);
                    if (data.success) {
                        getProjectRunners();
                        closeAddRunnerForm();
                    }
                },
                error: function() {
                    closeNotifyOverlay();
                }
            });
            
            return false;
        });
    
        $('#removerunner').click(function(event) {
            openNotifyOverlay(
                '<span>Removing selected user(s) as Runner(s) for this project. ' +
                'If this user has active jobs for which they are the Runner, you will need to ' +
                'change the Runner status to an eligible Runner.</span>', true);
            
            var runners = '';
            $('#projectRunners input[name^=runner]:checked').each(function() {
                var runner = parseInt($(this).attr('name').substring(6));
                if (runners.length) runners += ';';{
                runners += '' + runner;
                }
            });
            $.ajax({
                type: 'post',
                url: 'jsonserver.php',
                data: {
                    projectid: projectid,
                    runners: runners,
                    action: 'removeRunnersFromProject'
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        getProjectRunners();
                    }
                }
            });
            return false;
        });
        
        $("#testFlightButton").click(function() {
            showTestFlightForm(<?php echo $project->getProjectId(); ?>);
        });

        makeWorkitemTooltip(".payment-joblink, .joblink");
        
        if ($("#projectLogoEdit").length > 0) {
            new AjaxUpload('projectLogoEdit', {
                action: 'jsonserver.php',
                name: 'logoFile',
                data: {
                    action: 'logoUpload',
                    projectid: projectid,
                },
                autoSubmit: true,
                responseType: 'json',
                onSubmit: validateUploadImage,
                onComplete: function(file, data) {
                    $('span.LV_validation_message.upload').css('display', 'none').empty();
                    if (!data.success) {
                        $('span.LV_validation_message.upload').css('display', 'inline').append(data.message);
                    } else if (data.success == true ) {
                        $("#projectLogoEdit img").attr("src",data.url);
                        $('input[name=logoProject]').val(data.fileName);
                    }
                }
            });
        }
        
        //derived from bids to show edit dialog when project owner clicks on a role <mikewasmike 16-jun-2011>
        $('tr.role').click(function(){
            $.metadata.setType("elem", "script")
            var roleData = $(this).metadata();

            // row has role data attached
            if(roleData.id){
                $('#popup-role-info input[name="role_id"]').val(roleData.id);
                $('#popup-role-info #info-title').text(roleData.role_title);
                $('#popup-role-info #info-percentage').text(roleData.percentage);
                $('#popup-role-info #info-min-amount').text(roleData.min_amount);
                //future functions to display more information as well as enable disable removal edition
                $('#popup-role-info').dialog('open');
            }
        });

        $('#editRole').click(function(){
            // row has role data attached
            $('#popup-role-info').dialog('close');
                $('#popup-edit-role input[name="role_id"]').val($('#popup-role-info input[name="role_id"]').val());
                $('#popup-edit-role #role_title_edit').val($('#popup-role-info #info-title').text());
                $('#popup-edit-role #percentage_edit').val($('#popup-role-info #info-percentage').text());
                $('#popup-edit-role #min_amount_edit').val($('#popup-role-info #info-min-amount').text());
                $('#popup-edit-role').dialog('open');
        });

        //popup for adding Project Runner
        $('#add-runner').dialog({
            autoOpen: false,
            resizable: false,
            modal: true,
            show: 'fade',
            hide: 'fade',
            width: 350,
            height: 180
        });
        
        $('#addrunner').click(function() {
            $('#add-runner').dialog('open');
        });
        
        //popup for removing Project Runner
        $('#remove-runner').dialog({
            autoOpen: false,
            resizable: false,
            modal: true,
            show: 'fade',
            hide: 'fade',
            width: 350,
            height: 450,
        });
        
        // new dialog for adding and editing roles <mikewasmike 16-jun-2011>
        $('#popup-addrole').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 250, show: 'fade', hide: 'fade' });
        $('#popup-role-info').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 250, show: 'fade', hide: 'fade' });
        $('#popup-edit-role').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 250, show: 'fade', hide: 'fade' });

        $('#popup-testflight').dialog({ autoOpen: false, maxWidth: 600, width: 410, show: 'fade', hide: 'fade' });
        
        $("#owner").autocomplete('getusers.php', { cacheLength: 1, max: 8 } );
        
        <?php if ($edit_mode): ?>
            $('#cancel_project_edit').click(function() {
                location.href = '?action=view';
                return false;
            });
        <?php else: ?>
            $('#edit_project').click(function() {
                location.href = '?action=edit';
                return false;
            });
        <?php endif; ?>
    });
    
    function showAddRoleForm() {
        $('#popup-addrole').dialog('open');
        return false;
    }

    function showAddRunnerForm() {
        $('#add-runner').dialog('open');
        return false;
    }

    function closeAddRunnerForm() {
        $('#add-runner').dialog('close');
        return false;
    }

    function showTestFlightForm(project_id) {
        $('#popup-testflight').dialog('open');
        $('#popup-testflight .error').hide();
        $('#popup-testflight form').hide();
        $('#popup-testflight form #ipa-select input').remove();

        $.getJSON('testflight.php?project_id=' + project_id, function(data) {
            $('#popup-testflight .loading').hide()
            if (data['error']) {
                $('#popup-testflight .error')
                    .text(data['error'])
                    .show();
            } else {
                $('#popup-testflight form #message').val(data['message']);
                $.each(data['ipaFiles'], function(index, value) {
                    $('#popup-testflight form #ipa-select').append('<input type="radio" name="ipa" value="' + value + '" /> ' + value + '<br />');
                });
                $('#popup-testflight form #ipa-select input:first').prop('checked', true);
                $('#popup-testflight form').show();
                $('#popup-testflight .right-note').show();

                $('#popup-testflight form #submit_testflight').click(function() {
                    var params = 'project_id=' + project_id + '&message=' + $('#popup-testflight form #message').val();
                    params += "&ipa_file=" + $('#popup-testflight form #ipa-select input').val();
                    params += "&notify="
                    params += $('#popup-testflight form input[type=checkbox]').is(':checked');
                    $.getJSON('testflight.php?' + params, function(data) {
                        if (data == null) {
                            alert("There was an error with publishing to TestFlight. Please try again.");
                        } else if (data['error']) {
                            alert(data['error']);
                        }
                    });
                    $('#popup-testflight').dialog('close');
                });

            }
        });
        return false;
    }
</script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/html" id="uploadedFiles">
<div id="accordion">
<?php require('dialogs/file-accordion.inc'); ?>
</div>
<?php if ($userId > 0) : ?>
<div id="uploadButtonDiv" class="uploadbutton fileUploadButton">
    <span class="smbutton">Attach files</span>
</div>
<div class="uploadnotice"></div>
<?php endif; ?>
</script>
<!-- js template for file uploads -->
<?php require_once('dialogs/file-templates.inc'); ?>

<?php if ($userId > 0): ?>
<script type="text/javascript" src="js/uploadFiles.js"></script>
<?php endif; ?>

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

    require_once('header.php');
    require_once('format.php');
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
                                                        id: '{<?php echo $role['id']; ?>}', 
                                                        role_title: '{<?php echo $role['role_title']; ?>}', 
                                                        percentage: {'<?php echo $role['percentage']; ?>}', 
                                                        min_amount: {'<?php echo $role['min_amount']; ?>}'
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
                <li id="projectCodeReviewRights">
                    <h3>Code reviews <span>are allowed from</span></h3>
                    <div>
                        <input id="cr_anyone_field" name="cr_anyone" type="checkbox" 
                            <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?> value="1" 
                            <?php echo ($project->getCrAnyone() > 0) ? 'checked="checked"' : '' ; ?> />
                        <label for="cr_anyone_field">Anyone</label>
                    </div>
                    <div>
                        <input id="cr_3_favorites_field" name="cr_3_favorites" type="checkbox" 
                        <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?> value="1" 
                        <?php echo ($project->getCrFav() > 0) ? 'checked="checked"' : '' ; ?> />
                        <label for="cr_3_favorites_field">Anyone who is trusted by more than [3] people</label>
                    </div>
                    <div>
                        <input id="cr_project_admin_field" name="cr_project_admin" type="checkbox" 
                        <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?> value="1" 
                        <?php echo ($project->getCrAdmin() > 0) ? 'checked="checked"' : '' ; ?> />
                        <label for="cr_project_admin_field">Anyone who is trusted by the project admin</label>
                    </div>
                    <div>
                        <input id="cr_job_runner_field" name="cr_job_runner" type="checkbox" 
                        <?php echo $edit_mode ? '' : 'disabled="disabled"'; ?> value="1" 
                        <?php echo ($project->getCrRunner() > 0) ? 'checked="checked"' : '' ; ?> />
                        <label for="cr_job_runner_field">Anyone who is trusted by the job manager</label>
                    </div>
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
                    <?php $jobs = $project->getActiveJobs(); ?>
                    <h3><?php echo (count($jobs) ? count($jobs) : 'No'); ?> active job<?php echo (count($jobs) == 1 ? '' : 's'); ?></h3>
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
                                          href="<?php echo SERVER_URL . 'workitem.php?job_id=' . $job['id'] . '&action=view'; ?>">
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
                        <a href="<?php echo SERVER_URL; ?>worklist.php?project=<?php echo $project->getName(); ?>&user=&status=0" target="_blank">View history of jobs</a>
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
                                                          href="workitem.php?job_id=<?php echo $payment['worklist_id']; ?>&action=view">
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
                <li id="projectRunners">
                    <h3>Project runners</h3>
                    <table>
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
                <?php if ($project->getTestFlightTeamToken() != '' || $edit_mode) : ?>
                    <li id="projectTestFlight">
                        <h3>TestFlight</h3>
                        <?php if ($edit_mode): ?>
                            <label for="testflight_team_token">Team token</label>
                            <input name="testflight_team_token" id="testflight_team_token" type="text" value="<?php echo $project->getTestFlightTeamToken(); ?>" />
                        <?php else: ?> 
                            <div class="buttonContainer">
                                <?php if (($is_runner || $is_owner) && $project->getTestFlightTeamToken()) : ?>
                                    <input id="testFlightButton" type="submit" onClick="javascript:;" value="TestFlight" />
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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