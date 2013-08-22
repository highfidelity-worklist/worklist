<?php
require_once('config.php');
require_once("class.session_handler.php");
require_once("check_session.php");
require_once("functions.php");

$is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
$projects = Project::getProjects(true);

include("head.html");
?>
<title>Add task / Report bug - Worklist: Develop software fast.</title>
<link href="css/addjob.css" rel="stylesheet" type="text/css" />
<link rel="shortcut icon" type="image/x-icon" href="images/worklist_favicon.png">

<script type="text/javascript" src="js/jquery.timeago.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/uploadFiles.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/skills.js"></script>
<script type="text/javascript" src="js/addjob.js"></script>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body>
<?php require_once('header.php'); ?>
<div id="addJob">
    <form action="javascript:;" method="post">
        <input type="hidden" name="itemid" value="0" />
        <input type="hidden" name="files" value="" />
        
        <fieldset id="project">
            <label for="itemProjectCombo">Job is for this project ...</label>
            <select id="itemProjectCombo" name="itemProject">
                <option value="select" <?php echo empty($_GET['project']) ? 'selected="selected"' : ''; ?>>Select project</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?php echo $p['project_id']; ?>" <?php echo $_GET['project'] == $p['name'] ? 'selected="selected"' : ''; ?>><?php echo $p['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </fieldset>
        
        <fieldset id="bug">
            <fieldset>
                <input type="checkbox" name="is_bug" id="is_bug" <?php echo isset($_GET['bugof']) && $_GET['bugof'] > 0 ? 'checked="checked"' : '' ?> />
                <label for="is_bug" class="small-label">This is a bug</label>
            </fieldset>
            <fieldset>
                <label for="bug_job_id" class="small-label">Bug of job # ... </label>
                <input type="text" id="bug_job_id" name="bug_job_id" value="<?php echo isset($_GET['bugof']) ? $_GET['bugof'] : '' ?>" />
            </fieldset>
            <p title="0"></p>
        </fieldset>
        
        <fieldset id="title">
            <label for="summary">Job title</label>
            <input type="text" name="summary" id="summary" class="text-field" value="<?php echo isset($_GET['bugof']) ? '[BUG] ' : '' ?>" />
        </fieldset>
        
        <fieldset id="content">
            <label for="notes">Full description<small> (Describe the work necessary to successfully complete this job.)</small></label>
            <textarea name="notes" id="notes"></textarea>
        </fieldset>
        
        <fieldset>
            <label for="skills">Skills necessary <small> (separate by commas ... ex: C, Linux, Software Architecture)</small></label>
            <input type="text" id="skills" name="skills" class="text-field" value="" />
        </fieldset>
        
        <fieldset>
            <label for="ivite" id="label-for-invite">
                Invite Worklist users <small>(separate by commas)</small>
            </label>
            <input id="ivite" type="text" name="invite" class="invite" />
        </fieldset>
        
        <div id="upload-section">
            <div id="addaccordion" class="clear"></div>
            <div id="uploadButtonDiv" class="uploadbutton fileUploadButton">
                <span class="smbutton">Attach files</span>
            </div>
            <div id="attachments"></div>
            <input type="hidden" name="files" value="" />
            <div class="uploadnotice"></div>
            <?php require_once('dialogs/file-templates.inc'); ?>
        </div>
        
        <fieldset id="buttons">
            <?php if ($is_runner): ?>
                <fieldset id="initial-status">
                    <label for="itemStatusCombo" class="small-label">Set the status of this job to:</label>
                    <select id="itemStatusCombo" name="status">
                        <option value="Draft">Draft</option>
                        <option value="Suggested">Suggested</option>
                        <option value="Bidding" selected="selected">Bidding</option>
                        <option value="Working">Working</option>
                        <option value="Done">Done</option>
                    </select>
                </fieldset>
            <?php else: ?>
                <input type="hidden" id="status" name="status" value="Suggested" />
            <?php endif; ?>
            
            <fieldset id="save">
                <input type="submit" name="save_item" value="Add job">
            </fieldset>
        </fieldset>
    </form>
</div>
<?php require_once('footer.php'); ?>