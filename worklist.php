<?php
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//  vim:ts=4:et

require_once("config.php");
if (!empty($_SERVER['PATH_INFO'])) {  header( 'Location: https://'.SERVER_NAME.'/worklist/worklist.php'); }
require_once("class.session_handler.php");
include_once("check_new_user.php");
require_once("functions.php");
require_once("send_email.php");
require_once("update_status.php");
require_once("workitem.class.php");
require_once('lib/Agency/Worklist/Filter.php');
require_once('classes/UserStats.class.php');
require_once('classes/Repository.class.php');
require_once('classes/Project.class.php');

$page = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1; //Get the page number to show, set default to 1

$userId = getSessionUserId();

if( $userId > 0 ) {
    initUserById($userId);
    $user = new User();
    $user->findUserById( $userId );
    $nick = $user->getNickname();
    $userbudget =$user->getBudget();
    $budget = number_format($userbudget);
}

$is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
$is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;

// are we on a project page? see .htaccess rewrite
$projectName = !empty($_REQUEST['project']) ? mysql_real_escape_string($_REQUEST['project']) : 0;
if ($projectName) {
    $inProject = new Project();
    try {
        $inProject->loadByName($projectName);
    } catch(Exception $e) {
        $error  = $e->getMessage();
        die($error);
    }
    // save changes to project
    if (isset($_REQUEST['save_project']) && ( $is_runner || $is_payer || $inProject->isOwner($userId))) {
        $inProject->setDescription($_REQUEST['description']);
        $inProject->setTestFlightTeamToken($_REQUEST['testflight_team_token']);
        $cr_anyone = ($_REQUEST['cr_anyone']) ? 1 : 0;
        $cr_3_favorites = ($_REQUEST['cr_3_favorites']) ? 1 : 0;
        $cr_project_admin = isset($_REQUEST['cr_project_admin']) ? 1 : 0;
        $cr_job_runner = isset($_REQUEST['cr_job_runner']) ? 1 : 0;
        $inProject->setCrAnyone($cr_anyone);
        $inProject->setCrFav($cr_3_favorites);
        $inProject->setCrAdmin($cr_project_admin);
        $inProject->setCrRunner($cr_job_runner);
        if ($_REQUEST['logoProject'] != "") {
            $inProject->setLogo($_REQUEST['logoProject']);
        }
        if (isset($_REQUEST['noLogo']) && $_REQUEST['noLogo'] == "1") {
            $inProject->setLogo("");
        }
        $inProject->save();
        // we clear post to prevent the page from redirecting
        $_POST = array();
    }
} else {
    $inProject = false;
}

$journal_message = '';
$nick = '';

$workitem = new WorkItem();
// get active projects
$projects = Project::getProjects(true);

// check if we are on a project page, and setup filter
if (is_object($inProject)) {
    $project_id = $inProject->getProjectId();
    $filter = new Agency_Worklist_Filter();
    $filter->setName('.worklist')
           ->setProjectId($project_id)
           ->initFilter();
    $hide_project_column = true;
} else {
    $hide_project_column = false;
    $filter = new Agency_Worklist_Filter();
// krumch 20110418 Set to open Worklist from Journal
    if(isset($_REQUEST['journal_query'])) {
        $filter->setName('.worklist')
               ->setStatus(strtoupper($_REQUEST['status']))
               ->initFilter();
    } else {
        $filter->setName('.worklist')
               ->initFilter();
    }
}
// save,edit,delete roles <mikewasmie 16-jun-2011>
if (is_object($inProject) && ( $is_runner || $is_payer || $inProject->isOwner($userId))) {
    if ( isset($_POST['save_role'])) {
        $args = array('role_title', 'percentage', 'min_amount');
        foreach ($args as $arg) {
            $$arg = mysql_real_escape_string($_POST[$arg]);
        }
        $role_id=$inProject->addRole($project_id,$role_title,$percentage,$min_amount);
    }

    if (isset($_POST['edit_role'])) {
        $args = array('role_id','role_title_edit', 'percentage_edit', 'min_amount_edit');
        foreach ($args as $arg) {
            $$arg = mysql_real_escape_string($_POST[$arg]);
        }
        $res=$inProject->editRole($role_id,$role_title_edit,$percentage_edit,$min_amount_edit);
    }

    if (isset($_POST['delete_role'])) {
        $role_id = mysql_real_escape_string($_POST['role_id']);
        $res=$inProject->deleteRole($role_id);
    }
}

if ($userId > 0 && isset($_POST['save_item'])) {
    $args = array( 'itemid', 'summary', 'project_id', 'status', 'notes',
                    'bid_fee_desc', 'bid_fee_amount','bid_fee_mechanic_id',
                     'invite', 'is_expense', 'is_rewarder', 'is_bug', 'bug_job_id');
    foreach ($args as $arg) {
            // Removed mysql_real_escape_string, because we should
            // use it in sql queries, not here. Otherwise it can be applied twice sometimes
        $$arg = !empty($_POST[$arg])?$_POST[$arg]:'';
    }

    $creator_id = $userId;

    if (!empty($_POST['itemid']) && ($_POST['status']) != 'DRAFT') {
        $workitem->loadById($_POST['itemid']);
        $journal_message .= $nick . " updated ";
    } else {
        $workitem->setCreatorId($creator_id);
        $journal_message .= $nick . " added ";
    }
    $workitem->setSummary($summary);

    $workitem->setBugJobId($bug_job_id);
    // not every runner might want to be assigned to the item he created - only if he sets status to 'BIDDING'
    if($status == 'BIDDING' && ($user->getIs_runner() == 1 || $user->getBudget() > 0)){
        $runner_id = $userId;
    }else{
        $runner_id = 0;
    }

    $workitem->setRunnerId($runner_id);
    $workitem->setProjectId($project_id);
    $workitem->setStatus($status);
    $workitem->setNotes($notes);
    $workitem->is_bug = isset($is_bug) ? true : false;
    $workitem->save();

    Notification::statusNotify($workitem);
    if(is_bug) {
        $bug_journal_message = " (bug of job #".$bug_job_id.")";
        notifyOriginalUsersBug($bug_job_id, $workitem);
    }
    
    if(empty($_POST['itemid']))  {
        $bid_fee_itemid = $workitem->getId();
        $journal_message .= " item #$bid_fee_itemid: $summary. ";
        if (!empty($_POST['files'])) {
            $files = explode(',', $_POST['files']);
            foreach ($files as $file) {
                $sql = 'UPDATE `' . FILES . '` SET `workitem` = ' . $bid_fee_itemid . ' WHERE `id` = ' . (int)$file;
                mysql_query($sql);
            }
        }
    } else {
        $bid_fee_itemid = $itemid;
        $journal_message .=  "item #$itemid$bug_journal_message: $summary. ";
    }
        
    if (!empty($_POST['invite'])) {
        $people = explode(',', $_POST['invite']);
        invitePeople($people, $workitem);
    }

    if ($bid_fee_amount > 0 && $status != 'DRAFT') {
        $journal_message .= AddFee($bid_fee_itemid, $bid_fee_amount, 'Bid', $bid_fee_desc, $bid_fee_mechanic_id, $is_expense, $is_rewarder);
    }
}

if (!empty($journal_message)) {
    //sending journal notification
    sendJournalNotification(stripslashes($journal_message));
}

// Load roles table id owner <mikewasmike 15-jun 2011>
if(is_object($inProject) && ( $is_runner || $is_payer || $inProject->isOwner($userId))){
    $roles = $inProject->getRoles($inProject->getProjectId());
}

/* Prevent reposts on refresh */
if (!is_object($inProject) && !empty($_POST)) {
    unset($_POST);
    header("Location:worklist.php");
    exit();
}

/*********************************** HTML layout begins here  *************************************/
$worklist_id = isset($_REQUEST['job_id']) ? intval($_REQUEST['job_id']) : 0;

include("head.html"); ?>
<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<?php if(isset($_REQUEST['addFromJournal'])) { ?>
<link href="css/addFromJournal.css" rel="stylesheet" type="text/css">
<?php } ?>
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<link href="css/ui.toaster.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="js/paginator.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/jquery.tabSlideOut.v1.3.js"></script>
<script type="text/javascript" src="js/ui.toaster.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>
<script type="text/javascript" src="js/paginator.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter_desc.js"></script>
<?php if(isset($_REQUEST['addFromJournal'])) { ?>
<script type="text/javascript" src="js/jquery.ba-resize.min.js"></script>
<?php } ?>
<script type="text/javascript">
    var lockGetWorklist = 0;
    var status_refresh = 5 * 1000;
    var statusTimeoutId = null;
    var lastStatus = 0;
    function validateUploadImage(file, extension) {
        if (!(extension && /^(jpg|jpeg|gif|png)$/i.test(extension))) {
            // extension is not allowed
            $('span.LV_validation_message.upload').css('display', 'none').empty();
            var html = 'This filetype is not allowed!';
            $('span.LV_validation_message.upload').css('display', 'inline').append(html);
            // cancel upload
            return false;
        }
    }
    
    function GetStatus(source) {
        var url = 'update_status.php';
        var action = 'get';
        if(source == 'journal') {
            url = '<?php echo JOURNAL_QUERY_URL; ?>';
            action = 'getUserStatus';
        }
        $.ajax({
            type: "POST",
            url: url,
            cache: false,
            data: {
                action: action
            },
            dataType: 'json',
            success: function(json) {
                if(json && json[0] && json[0]["timeplaced"]) {
                    if(lastStatus < json[0]["timeplaced"]) {
                        lastStatus = json[0]["timeplaced"];
                        $('#status-update').val(json[0]["status"]);
                        $('#status-update').hide();
                        $('#status-lbl').show();
                        $("#status-share").hide();
                        $('#status-lbl').html( '<b>' + json[0]["status"] + '</b>' );
                    }
               }
            }
        });
        statusTimeoutId = setTimeout("GetStatus('journal')", status_refresh);
    }
    // This variable needs to be in sync with the PHP filter name
    var filterName = '.worklist';
    var affectedHeader = false;
    var directions = {"ASC":"images/arrow-up.png","DESC":"images/arrow-down.png"};
    var refresh = <?php echo AJAX_REFRESH ?> * 1000;
    var lastId;
    var page = <?php echo $page ?>;
    var topIsOdd = true;
    var timeoutId;
    var addedRows = 0;
    var workitem = 0;
//    var cur_user = false;
    var workitems;
    var dirDiv;
    var dirImg;
// Ticket #11517, replace all the "isset($_SESSION['userid']) ..."  by a call to "getSessionUserId"
//   var user_id = <?php echo isset($_SESSION['userid']) ? $_SESSION['userid'] : '"nada"' ?>;
    var user_id = <?php echo getSessionUserId(); ?>;
    var is_runner = <?php echo $is_runner ? 1 : 0 ?>;
    var runner_id = <?php echo !empty($runner_id) ? $runner_id : 0 ?>;
    var is_payer = <?php echo $is_payer ? 1 : 0 ?>;
    var addFromJournal = '<?php echo isset($_REQUEST['addFromJournal']) ? $_REQUEST['addFromJournal'] : '' ?>';
    var dir = '<?php echo $filter->getDir(); ?>';
    var sort = '<?php echo $filter->getSort(); ?>';
    var inProject = '<?php echo is_object($inProject) ?  $project_id  : '';?>';
    var resetOrder = false;
    var worklistUrl = '<?php echo SERVER_URL; ?>';
    stats.setUserId(user_id);
    var activeProjectsFlag = true;
    var skills = null;

    function AppendPagination(page, cPages, table)    {
    // support for moving rows between pages
    if(table == 'worklist') {
        // preparing dialog
        $('#pages-dialog select').remove();
        var selector = $('<select>');
        for (var i = 1; i <= cPages; i++) {
            selector.append('<option value = "' + i + '">' + i + '</option>');
        }
        $('#pages-dialog').prepend(selector);
    }
        var pagination = '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table + '-pagination-row nodrag nodrop " ><td colspan="6" style="text-align:center;">Pages : &nbsp;';
        if (page > 1) {
            pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + (page-1) + '">Prev</a> &nbsp;';
        }
        for (var i = 1; i <= cPages; i++) {
            if (i == page) {
                pagination += i + " &nbsp;";
            } else {
                pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + i + '" >' + i + '</a> &nbsp;';
            }
        }
        if (page < cPages) {
            pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + (page+1) + '">Next</a> &nbsp;';
        }
        pagination += '</td></tr>';
        $('.table-' + table).append(pagination);
    }
    function return2br(dataStr) {
        return dataStr.replace(/(\r\n|\r|\n)/g, "<br />");
    }
    // see getworklist.php for json column mapping
    function AppendRow (json, odd, prepend, moreJson, idx) {
        var pre = '', post = '';
        var row;
        row = '<tr id="workitem-' + json[0] + '" class="row-worklist-live iToolTip hoverJobRow ';

        // disable dragging for all rows except with "BIDDING" status
        if (json[2] != 'BIDDING'){
            row += ' nodrag ';
        }

        if (odd) {
            row += ' rowodd';
        } else {
            row += 'roweven';
        }

        // Check if the user is either creator, runner, mechanic and assigns the rowown class
        // also highlight expired and tasks bidding on
        if (user_id == 0) { // Checks if a user is logged in, as of now it
                            // would show to non logged in users, since mechanic
                            // aren't checked for session
        } else if(user_id == json[13]) {// Runner
            row += ' rowrunner';
        } else if(user_id == json[14]) {// Mechanic
            row += ' rowmechanic';
        } else if(json[15] >0) { //user bid on this task
            row += ' rowbidon';
        } else if(json[19] == 'expired') { // bid expired
            row += ' rowbidexpired';
        } else if(user_id == json[9]) { // Creator
            row += ' rowown';
        }

        row += '">';
        if (prepend) {
            pre = '<div class="slideDown" style="display:none">';
            post = '</div>';
        }

<?php if (! $hide_project_column) : ?>
        row+= '<td width="9%"><span class="taskProject" id="' + json[16] + '"><a href="' + worklistUrl + '' + json[17] + '">' + (json[17] == null ? '' : json[17]) + '</a></span></td>';
<?php endif; ?>
        //If job is a bug, add reference to original job
        if( json[18] > 0) {
            extraStringBug = '<small> (bug of '+json[18]+ ') </small>';
        } else {
            extraStringBug = '';
        }
  
        // Displays the ID of the task in the first row
        // 26-APR-2010 <Yani>
        row += '<td width="41%"><span id="workitem-' + json[0] + '" class="taskSummary">' +
                '<span class="taskID">#' + json[0] + '</span> - ' +
                pre + json[1] + post + extraStringBug +
                '</span></td>';
<?php if (! $hide_project_column) : ?>
        if ((json[2] == 'BIDDING' || json[2] == 'SUGGESTEDwithBID') &&json[10] > 0) {
            post = ' (' + json[10] + ')';
        }
        row += '<td width="20%">' + pre + json[2] + post + '</td>';
<?php endif; ?>
        pre = '';
        post = '';
/*
        if (json[3] != '') {
            var who = json[3];
            var tooltip = "Owner: "+json[4];
            if(json[9] != null && json[3] != json[9]) {
                who +=  ', ' + json[9];
                tooltip += '<br />Mechanic: '+json[10];
            }
            
            row += '<td width="15%" class="toolparent">' + pre + who + post + '<span class="tooltip">' + tooltip  + '</span>' + '</td>';
        } else {
            row += '<td width="15%">' + pre + json[3] + post + '</td>';
        }
*/
    <?php if (! $hide_project_column) : ?>
    var who = '',
        createTagWho = function(id,nickname,type) {
            return '<span class="'+type+'" title="' + id + '">'+nickname+'</span>';
        };
    if(json[3] == json[4]){
    
        // creator nickname can't be null, so not checking here
        who += createTagWho(json[9],json[3],"creator");
    }else{

        var runnerNickname = json[4] != null ? ', ' + createTagWho(json[13],json[4],"runner") : '';
        who += createTagWho(json[9],json[3],"creator") + runnerNickname;
    }
    if(json[5] != null){

        who += ', ' + createTagWho(json[14],json[5],"mechanic");
    }

    row += '<td width="9.5%" class="who">' + pre + who + post + '</td>';

        if (json[2] == 'WORKING' && json[11] != null) {
            if ((RelativeTime(json[11]) + ' from now').replace(/0 sec from now/,'Past due') == 'Past due') {
                pre = "<span class='past-due'>";
                post = "</span>";
            }
            row += '<td width="15%">' + pre + (RelativeTime(json[11]) + ' from now').replace(/0 sec from now/,'Past due') + post +'</td>';
            pre = '';
            post = '';
        } else if (json[2] == 'DONE' && json[11] != null) {
            row += '<td width="15%">' + json[11] + '</td>';
        }else {
            row += '<td width="15%">' + pre + RelativeTime(json[6]) + ' ago' + post +'</td>';
        }

        // Comments
        row += '<td width="7.5%">' + json[12] + '</td>';

        if (is_runner == 1) {
             var feebids = 0;
            if(json[7]){
                feebids = json[7];
            }
            var bid = 0;
            if(json[8]){
                bid = json[8];
            }
            if(json[2] == 'BIDDING' || json[2] == 'SUGGESTEDwithBID'){
                bid = parseFloat(bid);
                if (bid == 0) {
                    feebids = '';
                } else {
                    feebids = '$' + parseFloat(bid);
                }
            } else {
                feebids = '$' + feebids;
            }
            row += '<td width="11%">' + pre + feebids + post + '</td>';
        }
        <?php endif; ?>
        row += '</tr>';
        if (prepend) {
            $(row).prependTo('.table-worklist tbody')
                .find('td div.slideDown').fadeIn(500);
            setTimeout(function(){
                $(this).removeClass('slideDown');
                if (moreJson && idx-- > 1) {
                    topIsOdd = !topIsOdd;
                    AppendRow(moreJson[idx], topIsOdd, true, moreJson, idx);
                }
            }, 500);
        } else {
            $('.table-worklist tbody').append(row);
        }
    }

    function Change(obj, evt)    {
        if(evt.type=="focus")
            obj.style.borderColor="#6A637C";
        else if(evt.type=="blur")
           obj.style.borderColor="#d0d0d0";
    }

    function ClearSelection () {
        if (document.selection)
            document.selection.empty();
        else if (window.getSelection)
            window.getSelection().removeAllRanges();
    }
    
    function SetWorkItem(item) {
        var match = item.attr('id').match(/workitem-\d+/);
        if (match) {
            workitem = match[0].substr(9);
        } else {
            workitem = 0;
        }
        return workitem;
    }

    function orderBy(option) {
        if (option == sort) dir = ((dir == 'asc')? 'desc':'asc');
        else {
            sort = option;
            dir = 'asc';
        }
        GetWorklist(1,false);
    }

    function resizeIframeDlg() {
        var bonus_h = $('#user-info').children().contents().find('#pay-bonus').is(':visible') ?
                      $('#user-info').children().contents().find('#pay-bonus').closest('.ui-dialog').height() : 0;
    
        var dlg_h = $('#user-info').children()
                                   .contents()
                                   .find('html body')
                                   .height();
    
        var height = bonus_h > dlg_h ? bonus_h+35 : dlg_h+30;
    
        $('#user-info').animate({height: height});
    }

    function showUserInfo(userId) {
        $('#user-info').html('<iframe id="modalIframeId" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" />').dialog('open');
        $('#modalIframeId').attr('src','userinfo.php?id=' + userId);
        return false;
    };

    function GetWorklist(npage, update, reload) {
        if(addFromJournal != '') {
            return true;
        }
        while(lockGetWorklist) {
// count atoms of the Universe untill old instance finished...
        }
        lockGetWorklist = 1;
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        loaderImg.show("loadRunning", "Loading, please wait ...");
        $.ajax({
            type: "POST",
            url: 'getworklist.php',
            cache: false,
            data: {
                page: npage,
                project_id: $('.projectComboList .ui-combobox-list-selected').attr('val') || inProject,
                status: ($('select[name=status]').val() || []).join("/"),
                sort: sort,
                dir: dir,
                user: $('.userComboList .ui-combobox-list-selected').attr('val'),
                query: $("#query").val(),
                reload: ((reload == undefined) ? false : true)
            },
            dataType: 'json',
            success: function(json) {
                if (json[0] == "redirect") {
                    lockGetWorklist = 0;
                    $("#query").val('');
                    window.location.href = buildHref( json[1] );
                    return false;
                }
                
                loaderImg.hide("loadRunning");
                if (affectedHeader) {
                    affectedHeader.append(dirDiv);
                    dirImg.attr('src',directions[dir.toUpperCase()]);
                    dirDiv.css('display','block');
                } else {
                    if (resetOrder) {
                        dirDiv.css('display','none');
                        resetOrder = false;
                    }
                }
                affectedHeader = false;
                page = json[0][1]|0;
                var cPages = json[0][2]|0;

                $('.row-worklist-live').remove();
                workitems = json;
                if (!json[0][0]) return;

                /* When updating, find the last first element */
                for (var lastFirst = 1; update && page == 1 && lastId && lastFirst < json.length && lastId != json[lastFirst][0]; lastFirst++);
                lastFirst = Math.max(1, lastFirst - addedRows);
                addedRows = 0;

                /* Output the worklist rows. */
                var odd = topIsOdd;
                for (var i = lastFirst; i < json.length; i++) {
                    AppendRow(json[i], odd);
                    odd = !odd;
                }
                
                AppendPagination(page, cPages, 'worklist');

                if (update && lastFirst > 1) {
                    /* Update the view by scrolling in the new entries from the top. */
                    topIsOdd = !topIsOdd;
                    AppendRow(json[lastFirst-1], topIsOdd, true, json, lastFirst-1);
                }
                lastId = json[1][0];
                makeWorkitemTooltip(".taskSummary");
                
                /*commented for remove tooltip */
                //MapToolTips();
                $('tr.row-worklist-live').hover(
                    function() {
                        var selfRow=$(this);
                        $(".taskSummary",this).wrap("<a href='" +
                            buildHref( SetWorkItem(selfRow) ) +
                            "'></a>");
                        $(".creator,.runner,.mechanic",$(".who",this)).toggleClass("linkTaskWho").click(
                            function() {
                                showUserInfo($(this).attr("title"));
                            }
                        );
                    },function() {
                        $(".taskSummary",this).unwrap();
                        $(".creator,.runner,.mechanic",$(".who",this)).toggleClass("linkTaskWho").unbind("click");;
                });

                $('.worklist-pagination-row a').click(function(e){
                    page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
                    if (timeoutId) clearTimeout(timeoutId);
                    GetWorklist(page, false);
                    e.stopPropagation();
                    lockGetWorklist = 0;
                    return false;
                });

            },
            error: function(xhdr, status, err) {
                $('.row-worklist-live').remove();
                $('.table-worklist').append('<tr class="row-worklist-live rowodd"><td colspan="5" align="center">Oops! We couldn\'t find any work items.  <a id="again" href="#">Please try again.</a></td></tr>');
//              Ticket #11560, hide the waiting message as soon as there is an error
                loaderImg.hide("loadRunning");
//              Ticket #11596, fix done with 11517
//              $('#again').click(function(){
                $('#again').click(function(e){
//                  loaderImg.hide("loadRunning");
                    if (timeoutId) clearTimeout(timeoutId);
                    GetWorklist(page, false);
                    e.stopPropagation();
                    lockGetWorklist = 0;
                    return false;
                });
            }
        });

        timeoutId = setTimeout("GetWorklist("+page+", true, true)", refresh);
        lockGetWorklist = 0;
    }


    /*
    *    aneu: Added jquery.hovertip.min.js
    *          Is this function below needed or used somewhere?
    */
    function ToolTip() {
        xOffset = 10;
        yOffset = 20;
        var el_parent, el_child;
        $(".toolparent").hover(function(e){
            if (el_child) el_child.appendTo(el_parent).hide();
            el_parent = $(this);
            el_child = el_parent.children(".tooltip")
                .appendTo("body")
                .css("top",(e.pageY - xOffset) + "px")
                .css("left",(e.pageX + yOffset) + "px")
                .fadeIn("fast");
        },
        function(){
            if (el_child) el_child.appendTo(el_parent);
            $(".tooltip").hide();
            el_child = null;
        });
        $(".toolparent").mousemove(function(e){
            if (el_child) {
                el_child
                    .css("top",(e.pageY - xOffset) + "px")
                    .css("left",(e.pageX + yOffset) + "px");
            }
        });
    }

    function buildHref(item ) {
        return "<?php echo SERVER_URL ; ?>workitem.php?job_id="+item+"&action=view";
    }

    function ResetPopup() {
        $('#for_edit').show();
        $('#for_view').hide();
        $('.popup-body form input[type="text"]').val('');
        $('.popup-body form select.resetToFirstOption option[index=0]').prop('selected', true);
        $('.popup-body form select option[value=\'BIDDING\']').prop('selected', true);
        $('.popup-body form textarea').val('');

        //Reset popup edit form
        $("#bug_job_id").prop ( "disabled" , true );
        $("#bug_job_id").val ("");
        $('#bugJobSummary').html('');
        $("#bugJobSummary").attr("title" , 0);
        $("#is_bug").prop('checked',false);
        $('input[name=files]').val('');
        $('#fileimagecontainer').text('');
        $('#imageCount').text('0');
       
    }



    jQuery.fn.center = function () {
      this.css("position","absolute");
      this.css("top", (( $(window).height() - this.outerHeight() ) / 2 ) + "px");
      this.css("left", (( $(window).width() - this.outerWidth() ) / 2 ) + "px");
      return this;
    }
    /*
    show a message with a wait image
    several asynchronus calls can be made with different messages
    */
    var loaderImg = function($)
    {
        var aLoading = new Array(),
            _removeLoading = function(id) {
                for (var j=0; j < aLoading.length; j++) {
                    if (aLoading[j].id == id) {
                        if (aLoading[j].onHide) {
                            aLoading[j].onHide();
                        }
                        aLoading.splice(j,1);
                    }
                }
            },
            _show = function(id,title,callback) {
                aLoading.push({ id : id, title : title, onHide : callback});
                $("#loader_img_title").append("<div class='"+id+"'>"+title+"</div>");
                if (aLoading.length == 1) {
                    $("#loader_img").css("display","block");
                }
                $("#loader_img_title").center();
            },
            _hide = function(id) {
                _removeLoading(id);
                if (aLoading.length == 0) {
                    $("#loader_img").css("display","none");
                    $("#loader_img_title div").remove();
                } else {
                    $("#loader_img_title ."+id).remove();
                    $("#loader_img_title").center();
                }
            };
        
    return {
        show : _show,
        hide : _hide
    };

    }(jQuery); // end of function loaderImg

    function validateCodeReviews() {
        if (!$('.cr_anyone_field').is(':checked') && !$('.cr_3_favorites_field').is(':checked') && !$('.cr_project_admin_field').is(':checked') && !$('.cr_job_runner_field').is(':checked')) {
            $('.cr_anyone_field').prop('checked', true);
            $('#edit_cr_error').html("One selection must be checked");
            $('#edit_cr_error').fadeIn();
            $('#edit_cr_error').delay(2000).fadeOut();
        };
        if (!$('.cr_anyone_field_ap').is(':checked') && !$('.cr_3_favorites_field_ap').is(':checked') && !$('.cr_project_admin_field_ap').is(':checked') && !$('.cr_job_runner_field_ap').is(':checked')) {
            $('.cr_anyone_field_ap').prop('checked', true);
            $('#edit_cr_error_ap').html("One selection must be checked");
            $('#edit_cr_error_ap').fadeIn();
            $('#edit_cr_error_ap').delay(2000).fadeOut();
        }
    };
 
    
    $(document).ready(function() {
        // Fix the layout for the User selection box
        var box_h = $('select[name=user]').height() +1;
        $('#userbox').css('margin-top', '-'+box_h+'px');
        $('.accordion').accordion({
            clearStyle: true,
            collapsible: true,
            active: true,
            create: function(event, ui) {
                if(inProject.length > 0) {
                    var intervalId = setInterval(function() {
                        if($("#workers tr").length) {
                            $('#workers').paginate(20, 500);
                            clearInterval(intervalId);
                        }
                    }, 2000);
                }
            }
        });

        // Validate code review input
        $(':checkbox').change(function() {
            validateCodeReviews();
        });


        if (inProject.length > 0) {
            if ($("#tablesorter tr").length) {
                $('#tablesorter').paginate(4, 100);
                $('#tablesorter').tablesorter();
            }
            makeWorkitemTooltip(".payment-worklist-item");
            if ( $("#projectLogoEdit").length > 0) {
                new AjaxUpload('projectLogoEdit', {
                    action: 'jsonserver.php',
                    name: 'logoFile',
                    data: {
                        action: 'logoUpload',
                        projectid: inProject,
                    },
                    autoSubmit: true,
                    responseType: 'json',
                    onSubmit: validateUploadImage,
                    onComplete: function(file, data) {
                        $('span.LV_validation_message.upload').css('display', 'none').empty();
                        if (!data.success) {
                            $('span.LV_validation_message.upload').css('display', 'inline').append(data.message);
                        } else if (data.success == true ) {
                            $("#projectLogoEdit").attr("src",data.url);
                            $('input[name=logoProject]').val(data.fileName);
                        }
                    }
                });
            }
        }

        new AjaxUpload('projectLogoAdd', {
            action: 'jsonserver.php',
            name: 'logoFile',
            data: {
                action: 'logoUpload',
                projectid: inProject,
            },
            autoSubmit: true,
            responseType: 'json',
            onSubmit: validateUploadImage,
            onComplete: function(file, data) {
                $('span.LV_validation_message.upload').css('display', 'none').empty();
                if (!data.success) {
                    $('span.LV_validation_message.upload').css('display', 'inline').append(data.message);
                } else if (data.success == true ) {
                    $("#projectLogoAdd").attr("src",data.url);
                    $('input[name=logoProject]').val(data.fileName);
                }
            }
        });

        
        $.get('getskills.php', function(data) {
            var skills = eval(data);
            $("#skills").autocomplete(skills, {
                width: 320,
                max: 10,
                highlight: false,
                multiple: true,
                multipleSeparator: ", ",
                scroll: true,
                scrollHeight: 300
            });
        });

        dirDiv = $("#direction");
        dirImg = $("#direction img");
        hdr = $(".table-hdng");
        if (sort != 'delta') {
            hdr.find(".clickable").each(function() {
                if ($(this).text().toLowerCase() == unescape(sort.toLowerCase())) {
                    affectedHeader = $(this);
                }
            });
        }
        else {
            affectedHeader = $('#defaultsort');
        }
        hdr.find(".clickable").click(function() {
            affectedHeader = $(this);
            orderBy($(this).text().toLowerCase());
        });
        if(addFromJournal != '') {
            var addJobPane = window.parent;
        }
        
        // new dialog for adding and editing roles <mikewasmike 16-jun-2011>
        $('#popup-addrole').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 450, show: 'fade', hide: 'fade' });
        $('#popup-role-info').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 450, show: 'fade', hide: 'fade' });
        $('#popup-edit-role').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 450, show: 'fade', hide: 'fade' });
        $('#popup-edit').dialog({
            autoOpen: false,
            show: 'fade',
            hide: 'fade',
            maxWidth: 600,
            width: 415,
            hasAutocompleter: false,
            hasCombobox: false,
            resizable:false,
            open: function() {
                if (this.hasAutocompleter !== true) {
                    $('.invite').autocomplete('getusers.php', {
                        multiple: true,
                        multipleSeparator: ', ',
                        selectFirst: true,
                        extraParams: { nnonly: 1 }
                    });
                    this.hasAutocompleter = true;
                }
                if (this.hasCombobox !== true) {
                    // to add a custom stuff we bind on events
                    $('#popup-edit select[name=itemProject]').bind({
                        'beforeshow newlist': function(e, o) {
                            // check if the div for the checkbox already exists
                            if ($('#projectPopupActiveBox').length == 0) {
                                var div = $('<div/>').attr('id', 'projectPopupActiveBox');

                                // now we add a function which gets called on click
                                div.click(function(e) {
                                    // we hide the list and remove the active state
                                    activeProjectsFlag = 1 - activeProjectsFlag;
                                    o.list.hide();
                                    o.container.removeClass('ui-state-active');
                                    // we send an ajax request to get the updated list
                                    $.ajax({
                                        type: 'POST',
                                        url: 'refresh-filter.php',
                                        data: {
                                            name: filterName,
                                            active: activeProjectsFlag,
                                            filter: 'projects'
                                        },
                                        dataType: 'json',
                                        // on success we update the list
                                        success: $.proxy(o.setupNewList, o)
                                    });
                                });
                                $('.itemProjectCombo').append(div);
                            }
                            // setup the label and checkbox to put in the div
                            var label = $('<label/>').css('color', '#ffffff').attr('for', 'onlyActive');
                            var checkbox = $('<input/>').attr({
                                type: 'checkbox',
                                id: 'onlyActive'
                            }).css({
                                    margin: 0,
                                    position: 'relative',
                                    top: '1px',
                            });

                            // we need to update the checkbox status
                            if (activeProjectsFlag) {
                                checkbox.prop('checked', true);
                            } else {
                                checkbox.prop('checked', false);
                            }

                            // put the label + checkbox in the div
                            label.text(' Active only');
                            label.prepend(checkbox);
                            $('#projectPopupActiveBox').html(label);
                        }
                    }).comboBox();
                    this.hasCombobox = true;
                } else {
                    $('#popup-edit select[name=itemProject]').next().hide();
                    setTimeout(function() {
                        var val1 = $($('#popup-edit select[name=itemProject] option').get(1)).attr("value");
                        $('#popup-edit select[name=itemProject]').comboBox({action:"val",param:[val1]});
                        setTimeout(function() {
                            $('#popup-edit select[name=itemProject]').next().show();
                            $('#popup-edit select[name=itemProject]').comboBox({action:"val",param:["select"]});
                        },50);
                    },20);
                    
                }
            },
            close: function() {
                if(addFromJournal != '') {
                    setTimeout(function() {
                        addJobPane.closeAddJobDialog()
                    }, 1000);
                }
            }
        });

        $('#budget-expanded').dialog({ autoOpen: false, width:920, show:'fade', hide:'drop' });
        $('#user-info').dialog({
           autoOpen: false,
           resizable: false,
           modal: false,
           show: 'fade',
           hide: 'fade',
           width: 840,
           height: 480
        });
        
        $('#popup-testflight').dialog({ autoOpen: false, maxWidth: 600, width: 410, show: 'fade', hide: 'fade' });

        GetWorklist(<?php echo $page?>, false, true);
        
        $("#owner").autocomplete('getusers.php', { cacheLength: 1, max: 8 } );
        reattachAutoUpdate();

        $('#add').click(function(){
            $('#popup-edit').data('title.dialog', 'Add Worklist Item');
            $('#popup-edit form input[name="itemid"]').val('');
            ResetPopup();
            $('#save_item').click(function(event){
                var massValidation;
                if ($('#save_item').data("submitIsRunning") === true) {
                    event.preventDefault();
                    return false;
                }
                $('#save_item').data( "submitIsRunning",true );
                loaderImg.show( "saveRunning","Saving, please wait ...",function() {
                    $('#save_item').data( "submitIsRunning",false );
                });

                if($('#popup-edit form input[name="is_bug"]').is(':checked')) {
                    var bugJobId = new LiveValidation('bug_job_id',{
                        onlyOnSubmit: true ,
                        onInvalid : function() {
                            loaderImg.hide("saveRunning");
                            this.insertMessage( this.createMessageSpan() );
                            this.addFieldClass();
                        }
                    });
                    bugJobId.add( Validate.Custom, {
                        against: function(value,args){
                            id=$('#bugJobSummary').attr('title');
                            return (id!=0)
                        },
                        failureMessage: "Invalid item Id"
                    });

                    massValidation = LiveValidation.massValidate([bugJobId]);
                    if (!massValidation) {
                        loaderImg.hide("saveRunning");
                        event.preventDefault();
                        return false;
                    }
                }
                if($('#popup-edit form input[name="bid_fee_amount"]').val() || $('#popup-edit form input[name="bid_fee_desc"]').val()) {
                    // see http://regexlib.com/REDetails.aspx?regexp_id=318
                    // but without  dollar sign 22-NOV-2010 <krumch>
                    var regex = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
                    var optionsLiveValidation = { onlyOnSubmit: true,
                        onInvalid : function() {
                            loaderImg.hide("saveRunning");
                            this.insertMessage( this.createMessageSpan() );
                            this.addFieldClass();
                        }
                    };
                    var bid_fee_amount = new LiveValidation('bid_fee_amount',optionsLiveValidation);
                    var bid_fee_desc = new LiveValidation('bid_fee_desc',optionsLiveValidation);

                    bid_fee_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                    bid_fee_amount.add( Validate.Format, { pattern: regex, failureMessage: "Invalid Input!" });
                    bid_fee_desc.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                    massValidation = LiveValidation.massValidate([bid_fee_amount, bid_fee_desc]);
                    if (!massValidation) {
                        loaderImg.hide("saveRunning");
                        event.preventDefault();
                        return false;
                     }
                } else {
                    if (bid_fee_amount) bid_fee_amount.destroy();
                    if (bid_fee_desc) bid_fee_desc.destroy();
                }
                var summary = new LiveValidation('summary',{ onlyOnSubmit: true ,
                    onInvalid : function() {
                        loaderImg.hide("saveRunning");
                        this.insertMessage( this.createMessageSpan() );
                        this.addFieldClass();
                    }});
                summary.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                massValidation = LiveValidation.massValidate( [ summary ]);
                if (!massValidation) {
                    loaderImg.hide("saveRunning");
                    event.preventDefault();
                    return false;
                }
                var itemProject = new LiveValidation('itemProjectCombo',{
                    onlyOnSubmit: true ,
                    onInvalid : function() {
                        loaderImg.hide("saveRunning");
                        this.insertMessage( this.createMessageSpan() );
                        this.addFieldClass();
                    }});
                itemProject.add( Validate.Exclusion, {
                    within: [ 'select' ], partialMatch: true,
                    failureMessage: "You have to choose a project!"
                });
                massValidation = LiveValidation.massValidate( [ itemProject ]);
                if (!massValidation) {
                    loaderImg.hide("saveRunning");
                    event.preventDefault();
                    return false;
                }
                addForm = $("#popup-edit");
                $.ajax({
                    url: 'addworkitem.php',
                    dataType: 'json',
                    data: {
                        bid_fee_amount:$(":input[name='bid_fee_amount']",addForm).val(),
                        bid_fee_mechanic_id:$(":input[name='bid_fee_mechanic_id']",addForm).val(),
                        bid_fee_desc:$(":input[name='bid_fee_desc']",addForm).val(),
                        itemid:$(":input[name='itemid']",addForm).val(),
                        summary:$(":input[name='summary']",addForm).val(),
                        files:$(":input[name='files']",addForm).val(),
                        invite:$(":input[name='invite']",addForm).val(),
                        notes:$(":input[name='notes']",addForm).val(),
                        page:$(":input[name='page']",addForm).val(),
                        project_id:$(":input[name='itemProject']",addForm).val(),
                        status:$(":input[name='status']",addForm).val(),
                        skills:$(":input[name='skills']",addForm).val(),
                        is_bug:$(":input[name='is_bug']",addForm).prop('checked'),
                        bug_job_id:$(":input[name='bug_job_id']",addForm).val()
                    },
                    type: 'POST',
                    success: function(json){
                        if ( !json || json === null ) {
                            alert("json null in addworkitem");
                            loaderImg.hide("saveRunning");
                            return;
                        }
                        if ( json.error ) {
                            alert(json.error);
                        } else {
                            $('#popup-edit').dialog('close');
                        }
                        loaderImg.hide("saveRunning");
                        if(addFromJournal != '') {
                            setTimeout(function() {
                                addJobPane.closeAddJobDialog()
                            }, 1000);
                        } else {
                            if (timeoutId) clearTimeout(timeoutId);
                            timeoutId = setTimeout("GetWorklist("+page+", true, true)", refresh);
                            GetWorklist("+page+", true, true);
                        }
                    }
                });
                return false;
            });
            $('#fees_block').hide();
            $('#fees_single_block').show();
            if(addFromJournal != '') {
                $('#popup-edit').dialog('option', 'dialogClass', 'addFromJournal-popup');
                $('#popup-edit').dialog('option', 'position', ['center', 'top']);
                $('#popup-edit').dialog('option', 'minHeight', 700);
                $('#popup-edit').dialog('option', 'autoResize', true);
                $('.addFromJournal-popup div.ui-dialog-titlebar').hide();
                $('#popup-edit').parent().attr('id', 'addFromJournal');
                $('#popup-edit').resize(function() {
                    addJobPane.resizeJobIframe($('#popup-edit').height()+50);
                });
                $('#popup-edit').dialog('open');
            } else {
                $('#popup-edit').dialog('open');
            }
        });
        $("#search").click(function(e){
            e.preventDefault();
            $("#searchForm").submit();
            return false;
        });
        
        $('#query').keypress(function(event) {
            if (event.keyCode == '13') {
                event.preventDefault();
                $("#search").click();
            }
        });

        $("#search_reset").click(function(e){
            e.preventDefault();
            $("#query").val('');
            affectedHeader = false;
            resetOrder = true;
            sort = 'null';
            dir = 'asc';
            GetWorklist(1,false);
            return false;
        });

        $("#searchForm").submit(function(){
            var query = $('#query').val();
            var last_query = $(this).data('query') || "";
            if (query != last_query) {

                //reset projects combo
                $('#projectCombo').data('comboBox').select(0, false);

                //reset users combo
                $('#userCombo').data('comboBox').select(0, false);

                //reset status combo
                $('.statusComboList li input[type=checkbox]').each( function() {
                    $(this).prop('checked', false);
                });
                $('#statusCombo').data('comboBox').select("ALL", false);

                $(this).data('query', query);
            }
            GetWorklist(1,false);
            return false;
        });

        <?php if ($inProject) { ?>
        $("#testFlightButton").click(function() {
            showTestFlightForm(<?php echo $inProject->getProjectId(); ?>);
        });
        <?php } ?>

        //derived from bids to show edit dialog when project owner clicks on a role <mikewasmike 16-jun-2011>
        $('tr.row-role-list-live ').click(function(){
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

        
        //-- gets every element who has .iToolTip and sets it's title to values from tooltip.php
        /* function commented for remove tooltip */
        //setTimeout(MapToolTips, 800);

<?php if (! $hide_project_column) : ?>
    // bind on creation of newList
            $('select[name=project]').bind({
                'beforeshow newlist': function(e, o) {
                                        
                    // check if the div for the checkbox already exists
                    if ($('#projectActiveBox').length == 0) {
                        var div = $('<div/>').attr('id', 'projectActiveBox');
                        
                        // now we add a function which gets called on click
                        div.click(function(e) {
                            e.stopPropagation();
                            // we hide the list and remove the active state
                            activeProjectsFlag = 1 - activeProjectsFlag;
                            o.list.hide();
                            $('#projectActiveBox').prop('checked', (activeUsersFlag ? true : false));
                            $('#projectActiveBox').hide();
                            o.container.removeClass('ui-state-active');
                            // we send an ajax request to get the updated list
                            $.ajax({
                                type: 'POST',
                                url: 'refresh-filter.php',
                                data: {
                                    name: filterName,
                                    active: activeProjectsFlag,
                                    filter: 'projects'
                                },
                                dataType: 'json',
                                // on success we update the list
                                success: $.proxy(o.setupNewList, o)
                            });
                        });
                        $('.projectCombo').append(div);
                    }
                    // setup the label and checkbox to put in the div
                    var label = $('<label/>').css('color', '#ffffff').attr('for', 'onlyActive');
                    var checkbox = $('<input/>').attr({
                        type: 'checkbox',
                        id: 'onlyActive'
                    }).css({
                            margin: 0,
                            position: 'relative',
                            top: '1px',
                    });

                    // we need to update the checkbox status
                    if (activeProjectsFlag) {
                        checkbox.prop('checked', true);
                    } else {
                        checkbox.prop('checked', false);
                    }
                    
                    // put the label + checkbox in the div
                    label.text(' Active only');
                    label.prepend(checkbox);
                    $('#projectActiveBox').html(label);
                }
            }).comboBox();
<?php endif; ?>

        if(getQueryVariable('status') != null) {
            if (timeoutId) clearTimeout(timeoutId);
            GetWorklist(page, false);
        }
    });
    
    function showAddRoleForm() {
        $('#popup-addrole').dialog('open');
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
    
    function reattachAutoUpdate() {
        $("select[name=user], select[name=status], select[name=project]").change(function(){
            if ($("#search-filter").val() == 'UNPAID') {
                $(".worklist-fees").text('Unpaid');
            } else {
                $(".worklist-fees").text('Fees/Bids');
            }

            page = 1;
            if (timeoutId) clearTimeout(timeoutId);
            GetWorklist(page, false);
        });
    }

    function getIdFromPage(npage, worklist_id)  {
        $.ajax({
            type: "POST",
            url: 'getworklist.php',
            cache: false,
            data: 'page='+npage+'&sfilter='+$("#search-filter").val()+'&ufilter='+$("#user-filter").val()+"&query="+$("#query").val(),
            dataType: 'json',
            success: function(json) {
                // if moving on the greater page - place item on top, if on page with smaller number - on the end of the list
                if(npage > page){
                    prev_id = json[1][0];
                }else{
                    prev_id = json[json.length-2][0];
                }
                updatePriority(worklist_id, prev_id, 5);
            }
        });
    }
    function updatePriority(worklist_id, prev_id, bump){
        $.ajax({
            type: "POST",
            url: 'updatepriority.php',
            data: 'id='+worklist_id+'&previd='+prev_id+'&bump='+bump,
            success: function(json) {
                GetWorklist(page, true);
            }
        });
    }

    /**
     * Show a dialog with expanded info on the selected @section
     * Sections:
     *  - 0: Allocated
     *  - 1: Submited
     *  - 2: Paid
     */
    function budgetExpand(section) {
        $('#be-search-field').val('');
        $('#be-search-field').keyup(function() {
            // Search current text in the table by hiding rows
            var search = $(this).val().toLowerCase();
            
            $('.data_row').each(function() {
                var html = $(this).text().toLowerCase();
                // If the Row doesn't contain the pattern hide it
                if (!html.match(search)) {
                    $(this).fadeOut('fast');
                } else { // If is hidden but matches the pattern, show it
                    if (!$(this).is(':visible')) {
                        $(this).fadeIn('fast');
                    }
                }
            });
        });
        // If clean search, fade in any hidden items
        $('#be-search-clean').click(function() {
            $('#be-search-field').val('');
            $('.data_row').each(function() {
                $(this).fadeIn('fast');
            });
        });
        switch (section) {
            case 0:
                // Fetch new data via ajax
                $('#budget-expanded').dialog('open');
                be_getData(section);
                break;
            case 1:
                // Fetch new data via ajax
                $('#budget-expanded').dialog('open');
                be_getData(section);
                break;
            case 2:
                // Fetch new data via ajax
                $('#budget-expanded').dialog('open');
                be_getData(section);
                break;
        }
    }
    
    function be_attachEvents(section) {
        $('#be-id').click(function() {
            be_handleSorting(section, $(this));
        });
        $('#be-summary').click(function() {
            be_handleSorting(section, $(this));
        });
        $('#be-who').click(function() {
            be_handleSorting(section, $(this));
        });
        $('#be-amount').click(function() {
            be_handleSorting(section, $(this));
        });
        $('#be-status').click(function() {
            be_handleSorting(section, $(this));
        });
        $('#be-created').click(function() {
            be_handleSorting(section, $(this));
        });
        $('#be-paid').click(function() {
            be_handleSorting(section, $(this));
        });
    }
    
    function be_getData(section, item, desc) {
        // Clear old data
        var header = $('#table-budget-expanded').children()[0];
        $('#table-budget-expanded').children().remove();
        $('#table-budget-expanded').append(header);
        be_attachEvents(section);
        
        var params = '?section='+section;
        var sortby = '';
        // If we've got an item sort by it
        if (item) {
            sortby = item.attr('id');
            params += '&sortby='+sortby+'&desc='+desc;
        }
        
        $.getJSON('get-budget-expanded.php'+params, function(data) {
            // Fill the table
            for (var i = 0; i < data.length; i++) {
                var link = '<a href="https://".SERVER_NAME."/worklist/workitem.php?job_id='+data[i].id+'&action=view" target="_blank">';
                // Separate "who" names into an array so we can add the userinfo for each one
                var who = data[i].who.split(", ");
                var who_link = '';
                for (var z = 0; z < who.length; z++) {
                    if (z < who.length-1) {
                        who[z] = '<a href="#" onclick="showUserInfo('+data[i].ids[z]+')">'+who[z]+'</a>, ';
                    } else {
                        who[z] = '<a href="#" onclick="showUserInfo('+data[i].ids[z]+')">'+who[z]+'</a>';
                    }
                    who_link += who[z];
                }
                
                var row = '<tr class="data_row"><td>'+link+'#'+data[i].id+'</a></td><td>'+link+data[i].summary+'</a></td><td>'+who_link+
                          '</td><td>$'+data[i].amount+'</td><td>'+data[i].status+'</td>'+
                          '<td>'+data[i].created+'</td>';
                if (data[i].paid != 'Not Paid') {
                    row += '<td>'+data[i].paid+'</td></tr>';
                } else {
                    row += '<td>'+data[i].paid+'</td></tr>';
                }
                $('#table-budget-expanded').append(row);
            }
        });
        $('#budget-report-export').click(function() {
            window.open('get-budget-expanded.php?section='+section+'&action=export', '_blank');
        });
    }

    function be_handleSorting(section, item) {
        var desc = true;
        if (item.hasClass('desc')) {
            desc = false;
        }
        
        // Cleanup sorting
        be_cleaupTableSorting();
        item.removeClass('asc');
        item.removeClass('desc');
        
        // Add arrow
        var arrow_up = '<div style="float:right;">'+
                       '<img src="images/arrow-up.png" height="15" width="15" alt="arrow"/>'+
                       '</div>';

        var arrow_down = '<div style="float:right;">'+
                         '<img src="images/arrow-down.png" height="15" width="15" alt="arrow"/>'+
                         '</div>';

        if (desc) {
            item.append(arrow_down);
            item.addClass('desc');
        } else {
            item.append(arrow_up);
            item.addClass('asc');
        }

        // Update Data
        be_getData(section, item, desc);
    }

    function be_cleaupTableSorting() {
        $('#be-id').children().remove();
        $('#be-summary').children().remove();
        $('#be-who').children().remove();
        $('#be-amount').children().remove();
        $('#be-status').children().remove();
        $('#be-created').children().remove();
        $('#be-paid').children().remove();
    }
    
    if(addFromJournal != '') {
        $(function() {
            $('#add').click();
        });
    }
</script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript">
var projectid = <?php echo !empty($project_id) ? $project_id : "''"; ?>;
var imageArray = new Array();
var documentsArray = new Array();
(function($) {
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
                var files = $('#projectuploadedFiles').parseTemplate(data.data);
                $('#uploadPanel').append(files);
                // sort the file upload accordion
                $('#accordion').fileUpload({images: imageArray, documents: documentsArray});
            }
        }
    });
})(jQuery);
</script>
<script type="text/javascript" src="js/uploadFiles.js"></script>
<title><?php
echo
  is_object($inProject)
  ?  'Project: ' . $inProject->getName()
  :  'Worklist | Fast pay for your work, open codebase, great community.'
;
?></title>
</head>
<body>
<div style="display: none; position: fixed; top: 0px; left: 0px; width: 100%; height: 100%; text-align: center; line-height: 100%; background: white; opacity: 0.7; filter: alpha(opacity =   70); z-index: 9998"
     id="loader_img"><div id="loader_img_title"><img src="images/loading_big.gif"
     style="z-index: 9999"></div></div>

<!-- js template for file uploads -->
<?php require_once('dialogs/file-templates.inc'); ?>
<!-- Popup for editing/adding  a work item -->
<?php require_once('dialogs/popup-edit.inc'); ?>
<!-- Popup for breakdown of fees-->
<?php require_once('dialogs/popup-fees.inc'); ?>
<!-- Popup for budget info -->
<?php require_once('dialogs/budget-expanded.inc') ?>
<!-- Popups for tables with jobs from quick links -->
<?php require_once('dialogs/popups-userstats.inc'); ?>
<!-- Popup for add project info-->
<?php require_once('dialogs/popup-addproject.inc'); ?>
<!-- Popup for add role -->
<?php include('dialogs/popup-addrole.inc') ?>
<!-- Popup for viewing role -->
<?php include('dialogs/popup-role-info.inc') ?>
<!-- Popup for edit role -->
<?php include('dialogs/popup-edit-role.inc') ?>
<!-- Popup for TestFlight -->
<?php include('dialogs/popup-testflight.inc') ?>
<?php
if(isset($_REQUEST['addFromJournal'])) {
?>
<div class="hidden">
<input type="submit" id="add" name="add" value="Add Job" />
</div>
<?php
} else {
    include("format.php");
?>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

<!-- Head with search filters, user status, runer budget stats and quick links for the jobs-->
<?php
// krumch 20110419 Set to open Worklist from Journal
if(isset($_REQUEST['journal_query'])) {
   $filter->setProjectId($_REQUEST['project']);
   $filter->setUser($_REQUEST['user']);
}
   include("search-head.inc"); ?>
<?php
// show project information header
if (is_object($inProject)) {
    $edit_mode = false;
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && ( $is_runner || $is_payer || $inProject->isOwner($userId))) {
        $edit_mode = true;
    }
?>
<?php if (($is_runner || $inProject->isOwner($userId)) && $inProject->getTestFlightTeamToken()) : ?>
        <input id="testFlightButton" type="submit" onClick="javascript:;" value="TestFlight" />
<?php endif; ?>
<?php if ( $is_runner || $is_payer || $inProject->isOwner($userId)) : ?>
<?php if ($edit_mode) : ?>
        <span style="width: 150px; float: right;"><a href="?action=view">Switch to View Mode</a></span>
<?php else: ?>
        <span style="width: 150px; float: right;"><a href="?action=edit">Switch to Edit Mode</a></span>
<?php endif; ?>
<?php endif; ?>
<?php if ($edit_mode) : ?>
    <form name="project-form" id="project-form" action="<?php echo SERVER_URL . $inProject->getName(); ?>" method="post">
    <p class="editProjectLogo">
    <img style="cursor: pointer; width:48px; height:48px; margin-right:5px; border: 2px solid rgb(209, 207, 207);"
    id="projectLogoEdit"
    src="<?php echo(!$inProject->getLogo() ? 'images/emptyLogo.png' : 'uploads/' . $inProject->getLogo());?>" />
    <input type="checkbox" name="noLogo" value="1" />
    <br/>Check box to<br/>remove logo
    <span style="display: none;" class="LV_validation_message LV_invalid upload"></span>
    </p>
    <h2 style="line-height:48px">Project: <?php echo $inProject->getName(); ?>[#<?php echo $inProject->getProjectId(); ?>]</h2>
        <fieldset id="editContainer">
            <p class="info-label">Edit Description:<br />
                <textarea name="description" id="description" size="48" /><?php echo $inProject->getDescription(); ?></textarea>
            </p>
            <p class="info-label">TestFlight Team Token:<br />
                <input name="testflight_team_token" id="testflight_team_token" type="text" value="<?php echo $inProject->getTestFlightTeamToken(); ?>" />
            </p>
            <div class="accordion">
                <h3><a href="#">Allow code reviews from:</a></h3>
                <div id="codeReviewRightsContainer">
                    <input class="cr_anyone_field" type="checkbox" name="cr_anyone" value="1" <?php echo ($inProject->getCrAnyone()>0) ? 'checked="checked"' : '' ; ?> />Anyone<br/>
                    <input class="cr_3_favorites_field" type="checkbox" name="cr_3_favorites" value="1" <?php echo ($inProject->getCrFav()>0) ? 'checked="checked"' : '' ; ?> />Anyone who is trusted by more than [3] people<br/>
                    <input class="cr_project_admin_field" type="checkbox" name="cr_project_admin" value="1" <?php echo ($inProject->getCrAdmin()>0) ? 'checked="checked"' : '' ; ?> />Anyone who is trusted by the project admin<br/>
                    <input class="cr_job_runner_field" type="checkbox" name="cr_job_runner" value="1" <?php echo ($inProject->getCrRunner()>0) ? 'checked="checked"' : '' ; ?> />Anyone who is trusted by the job manager<br/>
                </div>
            </div>
            <div id="edit_cr_error"></div>
            <br/>
            
            <div id="buttonHolder">
                <input class="left-button" type="submit" id="cancel" name="cancel" value="Cancel">
                <input class="right-button" type="submit" id="save_project" name="save_project" value="Save">
                <input type="hidden" value="" name="logoProject">
            </div>
            <input type="hidden" name="project" value="<?php echo $inProject->getName(); ?>" />
        </fieldset>
    </form>
<?php endif; ?>

<?php if (! $edit_mode) : ?>
    <p style="float:left">
        <img style="width:48px;height:48px;margin-right:5px;border: 2px solid rgb(209, 207, 207);"
        id="projectLogo"
        src="<?php echo(!$inProject->getLogo() ? 'images/emptyLogo.png' : 'uploads/' . $inProject->getLogo());?>" />
    </p>
    <h2 style="line-height:48px">Project: <?php echo $inProject->getName(); ?>[#<?php echo $inProject->getProjectId(); ?>] </h2>
    <ul class="descriptionHolder">
        <li><strong>Description:</strong> <?php echo nl2br(linkify(htmlspecialchars($inProject->getDescription()))); ?></li>
<?php endif; ?>
    </ul>
    <ul class="detailContainer">
        <li><strong>Budget:</strong> $<?php echo $inProject->getBudget(); ?></li>
        <li><strong>Contact Info:</strong> <?php echo $inProject->getContactInfo(); ?></li>
<?php if ($inProject->getRepository() != '') : ?>
        <li><strong>Repository:</strong> <a href="<?php echo $inProject->getRepoUrl(); ?>"><?php echo $inProject->getRepoUrl(); ?></a></li>
<?php else: ?>
        <li><strong>Repository:</strong> </li>
<?php endif; ?>
        <li><strong>Fund:</strong> <?php echo $inProject->getFundName(); ?></li>
<?php if ($inProject->getTestFlightTeamToken() != '' && ! $edit_mode) : ?>
        <li><strong>TestFlight Team Token:</strong> <?php echo $inProject->getTestFlightTeamToken(); ?></li>
<?php endif; ?>
    </ul>
    <div class="projectStatistics">
        <div id="stats-panel">
            <table width="100%" class="table-stats">
                <caption class="table-caption" >
                    <b>Stats</b>
                </caption>
                <thead>
                    <tr class="table-hdng">
                        <td>Total Jobs</td>
                        <td>Avg. Bid/Job</td>
                        <td>Avg. Job Time</td>
                    </tr>
                </thead>
                <tbody>
                    <tr class="row-role-list-live">
                        <td><?php echo $inProject->getTotalJobs()?></td>
                        <td title="Average amount of accepted Bid per Job">$<?php echo number_format($inProject->getAvgBidFee(), 2);?></td>
                        <td title="Average time from Bid Accept to being Paid"><?php echo $inProject->getAvgJobTime();?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <h3>Jobs:</h3>
<div><div class="projectLeft">
<?php } ?>
<table class="table-worklist">
    <thead>
        <tr class="table-hdng">
            <?php if (! $hide_project_column) echo '<td class="clickable">Project</td>'; ?>
            <td><span class="clickable">ID</span> - <span class="clickable">Summary</span></td>
            <?php if (! $hide_project_column) echo '<td class="clickable">Status</td>'; ?>
            <?php if (! $hide_project_column) echo '<td class="clickable">Who</td>'; ?>
            <?php if (! $hide_project_column) echo '<td class="clickable" id="defaultsort">When</td>'; ?>
            <?php if (! $hide_project_column) echo '<td class="clickable" style="min-width:80px">Comments</td>'; ?>
            <?php if (! $hide_project_column) {
                echo '<td class="worklist-fees clickable"';
                echo (empty($_SESSION['is_runner'])) ? ' style="display:none"' : '';
                echo '>Fees/Bids</td>';
            } ?>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
<?php if (is_object($inProject)) { ?>
</div>
<div class="projectRight">
<!-- table for roles <mikewasmike 15-ju-2011>  -->
<?php if ($inProject->isOwner($userId)) : ?>
            <div id="for_view">
               <div class="payments">
                    <div id="payment-panel">
                        <table width="100%" class="tablesorter" id="tablesorter">
                            <caption class="table-caption"
                                <b>Payment Summary</b>
                            </caption>
                            <thead>
                                <tr class="table-hdng">
                                    <th>Payee</th>
                                    <th>Job#</th>
                                    <th>Amount</th>
                                    <th>Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                        <?php if($payments = $inProject->getPaymentStats()) {
                                foreach ($payments as $payment) { ?>
                                    <tr class="row-payment-list-live">
                                        <td><a href="#" onclick="javascript:showUserInfo(<?php echo $payment['id']?>);"><?php echo $payment['nickname']?></a></td>
                                        <td><a class="payment-worklist-item" id="worklist-<?php echo $payment['worklist_id']?>" href="workitem.php?job_id=<?php echo $payment['worklist_id']?>" target="_blank">#<?php echo $payment['worklist_id']?></a></td>
                                        <td>$<?php echo $payment['amount']?></td>
                                        <td><?php echo (($payment['paid']==1) ? "PAID" : "UNPAID")?></td>
                                    </tr>
                        <?php   }
                            } else { ?>
                                <tr>
                                    <td style="text-align: center;" colspan="4">No records found.</td>
                                </tr>
                    <?php   }?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
<?php endif; ?>
<?php if ($is_runner || $is_payer || $inProject->isOwner($userId)) : ?>
            <div id="for_view">
                <div class="roles">
                    <div id="roles-panel">
                        <table width="100%" class="table-bids">
                            <caption class="table-caption" >
                                <b>Roles</b>
                            </caption>
                            <thead>
                                <tr class="table-hdng">
                                    <td>Title</td>
                                    <td>%</td>
                                    <td>Min. Amount</td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(empty($roles)) { ?>
                                <tr>
                                    <td style="text-align: center;" colspan="4">No roles added.</td>
                                </tr>
                            <?php } else { $row = 1;
                                foreach($roles as $role) { ?>
                                <tr class="row-role-list-live
                                    <?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?> roleitem<?php
                                         echo '-'.$role['id'];?>">
                                        <script type="data"><?php echo "{id: '{$role['id']}', role_title: '{$role['role_title']}', percentage: '{$role['percentage']}', min_amount: '{$role['min_amount']}'}" ?></script>
                                    <td ><?php echo $role['role_title'];?></td>
                                    <td ><?php echo $role['percentage'];?></td>
                                    <td ><?php echo $role['min_amount'];?></td>
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
            </div>
<?php endif; ?>
<!--End of roles table-->

<div id="uploadPanel">
    <script type="text/html" id="projectuploadedFiles">
        <div id="accordion">
            <h3><a href="#">Who has worked on Project</a><h3>
        <div class="projectWorkers" >
            <table width="100%" class="table-workers" id="workers">
                <caption class="table-caption" >
                    <br/>
                </caption>
                <thead>
                    <tr class="table-hdng">
                        <th>Who</th>
                        <th># of Jobs</th>
                        <th>Last Activity</th>
                        <th>Total Earned</th>
                    </tr>
                </thead>
                <tbody class="developerContent">
                    <?php if($developers = $inProject->getDevelopers()) { ?>
                        <?php foreach($developers as $developer) { ?>
                            <tr class="row-developer-list-live">
                                <td class="developer"><a href="#" onclick="javascript:showUserInfo(<?php echo $developer['id']?>);"><?php echo $developer['nickname']?></a></td>
                                <td class="jobCount"><?php echo $developer['totalJobCount']?></td>
                                <td><?php echo $inProject->getDevelopersLastActivity($developer['id'])?></td>
                                <td><?php echo (($developer['totalEarnings'] > 0) ? "$" . $developer['totalEarnings'] : "") ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if (!$edit_mode) { ?>
            <h3><a href="#">Allow code reviews from:</a></h3>
            <div id="codeReviewRightsContainer">
                <input disabled type="checkbox" value="1" <?php echo ($inProject->getCrAnyone() > 0) ? 'checked="checked"' : '' ; ?> />Anyone<br/>
                <input disabled type="checkbox" value="1" <?php echo ($inProject->getCrFav() > 0) ? 'checked="checked"' : '' ; ?> />Anyone who is trusted by more than [3] people<br/>
                <input disabled type="checkbox" value="1" <?php echo ($inProject->getCrAdmin() > 0) ? 'checked="checked"' : '' ; ?> />Anyone who is trusted by the project admin<br/>
                <input disabled type="checkbox" value="1" <?php echo ($inProject->getCrRunner() > 0) ? 'checked="checked"' : '' ; ?> />Anyone who is trusted by the job manager<br/>
            </div>
        <?php } ?>
        <?php require('dialogs/file-accordion.inc'); ?>
        </div>
        <div class="fileUploadButton">
            Attach new files
        </div>
    <div class="uploadnotice"></div>
    </script>
</div>
</div>
<div class="clear">&nbsp;</div>
</div>
<?php } ?>

<span id="direction" style="display: none; float: right;"><img src="images/arrow-up.png" /></span>
<div id="user-info" title="User Info"></div>
<script type="text/javascript">
GetStatus('worklist');
</script>

<?php
    include("footer.php");
}
?>
