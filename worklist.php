<?php
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//  vim:ts=4:et

require_once("config.php");
if (! empty($_SERVER['PATH_INFO'])) {
    header( 'Location: https://' . SERVER_NAME . '/worklist/worklist.php');
    exit;
}

require_once ("class.session_handler.php");
require_once ("check_new_user.php");
require_once ("functions.php");
require_once ("send_email.php");
require_once('lib/Agency/Worklist/Filter.php');

$page = isset($_REQUEST["page"]) ? (int) $_REQUEST['page'] : 1; // Get the page number to show, set default to 1

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
    if (isset($_REQUEST['save_internal']) && $userId > 0 && $user->getIs_admin()) {
        $inProject->setInternal($_REQUEST['internal']);
        if ($inProject->save()) {
            echo json_encode(array(
                'success' => true,
                'message' => ""
            ));
        } else {
            echo json_encode(array(
                'success' => false,
                'message' => "There was a problem setting this project to Internal"
            ));
        }
        exit();
    } else if (isset($_REQUEST['save_project']) && ( $is_runner || $is_payer || $inProject->isOwner($userId))) {
        $inProject->setDescription($_REQUEST['description']);
        $inProject->setWebsite($_REQUEST['website']);
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
// $nick is setup above.. and then overwritten here -- lithium
$nick = '';

$workitem = new WorkItem();

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
    $args = array(
        'itemid',
        'summary',
        'project_id',
        'status',
        'notes',
        // @TODO: I don't think bid_fee_* fields are relevant anymore -- lithium
        'bid_fee_desc',
        'bid_fee_amount',
        'bid_fee_mechanic_id',
        'invite',
        // @TODO: Same goes for is_expense and is_rewarder.. -- lithium
        'is_expense',
        'is_rewarder',
        'is_bug',
        'bug_job_id'
    );

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
    if ($status == 'BIDDING' && $user->getIs_runner() == 1) {
        $runner_id = $userId;
    } else {
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
}

// send journal notification is there is one
if (!empty($journal_message)) {
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

$worklist_id = isset($_REQUEST['job_id']) ? intval($_REQUEST['job_id']) : 0;

/*********************************** HTML layout begins here  *************************************/
require_once("head.html");
require_once('opengraphmeta.php');
?>
<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css" />
<link href="css/ui.toaster.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="js/paginator.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/common.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/jquery.tabSlideOut.v1.3.js"></script>
<script type="text/javascript" src="js/ui.toaster.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>
<script type="text/javascript" src="js/paginator.js"></script>
<script type="text/javascript" src="js/budget.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter_desc.js"></script>
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

    // This variable needs to be in sync with the PHP filter name
    var filterName = '.worklist';
    var affectedHeader = false;
    var directions = {"ASC":"images/arrow-up.png","DESC":"images/arrow-down.png"};
    var refresh = <?php echo AJAX_REFRESH ?> * 1000;
    var lastId;
    var page = <?php echo $page ?>;
    var topIsOdd = true;
    var timeoutId;
    var workitem = 0;
//    var cur_user = false;
    var workitems;
    var dirDiv;
    var dirImg;
// Ticket #11517, replace all the "isset($_SESSION['userid']) ..."  by a call to "getSessionUserId"
//   var user_id = <?php echo isset($_SESSION['userid']) ? $_SESSION['userid'] : '"nada"' ?>;
    var userId = user_id = <?php echo getSessionUserId(); ?>;
    var is_runner = <?php echo $is_runner ? 1 : 0 ?>;
    var runner_id = <?php echo !empty($runner_id) ? $runner_id : 0 ?>;
    var is_payer = <?php echo $is_payer ? 1 : 0 ?>;
    var is_admin = <?php echo $userId > 0 ? $user->getIs_admin() : 0 ?>;
    var isProjectInternal = <?php echo is_object($inProject) ? $inProject->getInternal() : 0 ?>;
    var project_name = "<?php echo is_object($inProject) ? addslashes($inProject->getName()) : '' ?>";
    var addFromJournal = false;
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
        var pagination = '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table +
        '-pagination-row nodrag nodrop " ><td class="not-workitem" colspan="6" style="text-align:center;">Pages : &nbsp;';
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

<?php if (! $hide_project_column) : ?>
        var project_link = worklistUrl + '' + json[17];
        row +=
            '<td width="9%" class="clickable not-workitem" onclick="location.href=\'' + project_link + '\'">' +
                '<span class="taskProject" id="' + json[16] + '">' +
                    '<a href="' + project_link + '">' + (json[17] == null ? '' : json[17]) + '</a>' +
                '</span>' +
            '</td>';
<?php endif; ?>
        //If job is a bug, add reference to original job
        if( json[18] > 0) {
            extraStringBug = '<small> (bug of '+json[18]+ ') </small>';
        } else {
            extraStringBug = '';
        }

        // Displays the ID of the task in the first row
        // 26-APR-2010 <Yani>
        var workitemId = 'workitem-' + json[0];
        row += '<td width="41%"><span id="' + workitemId + '" class="taskSummary">' +
                '<span class="taskID">#' + json[0] + '</span> - ' +
                json[1] + extraStringBug +
                '</span></td>';
<?php if (! $hide_project_column) : ?>
        var bidCount = '';
        if ((json[2] == 'BIDDING' || json[2] == 'SUGGESTEDwithBID') &&json[10] > 0) {
            bidCount = ' (' + json[10] + ')';
        }
        row += '<td width="20%">' + json[2] + bidCount + '</td>';
<?php endif; ?>
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

    row += '<td width="9.5%" class="who not-workitem">' + who + '</td>';

        if (json[2] == 'WORKING' && json[11] != null) {
            var pastDuePre = '', pastDuePost = '';
            if ((RelativeTime(json[11]) + ' from now').replace(/0 sec from now/,'Past due') == 'Past due') {
                pastDuePre = "<span class='past-due'>";
                pastDuePost = "</span>";
            }
            row += '<td width="15%">' + pastDuePre + (RelativeTime(json[11]) + ' from now').replace(/0 sec from now/,'Past due') + pastDuePost +'</td>';
        } else if (json[2] == 'DONE' && json[11] != null) {
            row += '<td width="15%">' + json[11] + '</td>';
        } else {
            row += '<td width="15%">' +  RelativeTime(json[6]) + ' ago' +'</td>';
        }

        // Comments
        comments = (json[12] == 0) ? "" : json[12];
        row += '<td width="7.5%">' + comments + '</td>';

        if (is_runner == 1) {
            if (user_id == json[13] || json[2] == 'SUGGESTEDwithBID') {
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
                row += '<td width="11%">' + feebids + '</td>';
            } else {
                row += '<td width="11%">&nbsp;</td>';
            }
        }
        <?php endif; ?>
        row += '</tr>';
        if (prepend) {
            // animate in each row
            $(row).hide().prependTo('.table-worklist tbody').fadeIn(300);
            setTimeout(function(){
                if (moreJson && idx-- > 1) {
                    topIsOdd = !topIsOdd;
                    AppendRow(moreJson[idx], topIsOdd, true, moreJson, idx);
                }
            }, 300);
        } else {
            $('.table-worklist tbody').append(row);
        }
        // Apply additional styling
        additionalRowUpdates(workitemId);
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
        var match;
        if (item.attr('id')) {
            match = item.attr('id').match(/workitem-\d+/);
        }
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
        GetWorklist(1, false);
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

    function GetWorklist(npage, update, reload) {
	 if (addFromJournal) {
            return true;
        }
        while(lockGetWorklist) {
        // count atoms of the Universe untill old instance finished...
        }
        lockGetWorklist = 1;
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        if (inProject.length > 0) {
            $('.table-worklist tbody').html('<tr class="row-worklist-live rowodd"><td colspan="5" align="center">Loading ...</td></tr>');
        } else {
            loaderImg.show("loadRunning", "Loading, please wait ...");
        }
        $.ajax({
            type: "POST",
            url: 'getworklist.php',
            cache: false,
            data: {
                page: npage,
                project_id: $('.projectComboList .ui-combobox-list-selected').attr('val') || inProject,
                status: ($('#statusCombo').val() || []).join("/"),
                sort: sort,
                dir: dir,
                user: $('.userComboList .ui-combobox-list-selected').attr('val'),
                inComment: $('#search_comment').hasClass("inComment") ? 1 : 0,
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
                for (var lastFirst = 1; update && lastFirst < json.length && lastId != json[lastFirst][0]; lastFirst++);

                if (update && lastId && lastFirst == json.length) {
                    // lastId has disappeared from the list during an update.. avoid copious animations by reverting lastFirst
                    lastFirst = 1;
                }
                
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


    function additionalRowUpdates(workitemId) {
        // Apply only once to new rows, either on an update or original load.
        var rowQuery = 'tr.row-worklist-live';
        var taskQuery = '.taskSummary';
        if (workitemId != undefined) {
            rowQuery += '#' + workitemId;
            taskQuery = '#' + workitemId + taskQuery;
        }
        // Buid the workitem anchors
        $(rowQuery).each(function() {
            var selfRow = $(this);
            $(".taskSummary", selfRow).parent().addClass("taskSummaryCell");
            $('.taskSummary', selfRow).wrap('<a href="' + buildHref(SetWorkItem(selfRow)) + '"></a>');
            $("td:not(.not-workitem)", selfRow).click(function(e) {
                if (! (e.ctrlKey || e.shiftKey || e.altKey)) {
                    window.location.href = buildHref(SetWorkItem(selfRow));
                }
            }).addClass("clickable");
    
            $(".creator, .runner, .mechanic", $(".who", selfRow)).addClass("linkTaskWho").click(
                function() {
                    showUserInfo($(this).attr("title"));
                }
            );
        });
        // Add the hover over tooltip
        makeWorkitemTooltip(taskQuery);
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

        if ($('#projectLogoAdd').length > 0) {
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
        }
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
        // new dialog for adding and editing roles <mikewasmike 16-jun-2011>
        $('#popup-addrole').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 450, show: 'fade', hide: 'fade' });
        $('#popup-role-info').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 450, show: 'fade', hide: 'fade' });
        $('#popup-edit-role').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 450, show: 'fade', hide: 'fade' });

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
            GetWorklist(1, false);
            return false;
        });

        $("#search_comment").click(function(e){
            e.preventDefault();
            affectedHeader = false;
            resetOrder = true;
            sort = 'null';
            dir = 'asc';
            if ($(this).hasClass("comment")) {
                $(this).removeClass("comment");
                $(this).addClass("comment_no");
                $(this).attr("title", "Do not include comments in search");
            } else {
                $(this).removeClass("comment_no");
                $(this).addClass("comment");
                $(this).attr("title", "Include Comments in search");
            }
            GetWorklist(1, false);
            return false;
        });

        $("#searchForm").submit(function(){
            var query = $('#query').val();
            GetWorklist(1, false);
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
        if ($('#projectCombo').length !== 0) {
            createActiveFilter('#projectCombo', 'projects', 1);
        }
<?php endif; ?>

        if(getQueryVariable('status') != null) {
            if (timeoutId) clearTimeout(timeoutId);
            GetWorklist(page, false);
        }

        if (is_admin) {
            $('#modeSwitch #internal').change(function() {
                var is_internal = $(this).is(':checked') ? 1 : 0;
                $.ajax({
                    type: 'POST',
                    url: 'worklist.php',
                    data: {
                        project: project_name,
                        save_internal: 1,
                        internal: is_internal
                    },
                    dataType: 'json',
                    complete: function(data) {
                        if (!data.success) {
                            alert(data.message);
                        }
                    }
                });
            });
            if (isProjectInternal) {
                $('#modeSwitch #internal').attr('checked', true);
            } else {
                $('#modeSwitch #internal').removeAttr('checked');
            }
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
            timeoutId = setTimeout('GetWorklist(' + page + ', false)', 50);
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

</script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript">
var projectid = <?php echo !empty($project_id) ? $project_id : "''"; ?>;
var imageArray = new Array();
var documentsArray = new Array();
(function($) {

    var workerUpdate = function() {
        $('#workers tbody').html("Loading ...");
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                userid: user_id,
                action: 'getDevelopersForProject'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    var files = $('#projectWorkersBody').parseTemplate(data.data);
                    $('#workers tbody').html(files);
                    // sort the file upload accordion

                }
            }
        });
    };
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
                $('#accordion').bind( "accordionchange", function(event, ui) {
                    if (ui.options.active == 0) {
                        workerUpdate();
                    }
                });

            }
        }
    });

    // get the project stats
    $.ajax({
        type: 'post',
        url: 'jsonserver.php',
        data: {
            projectid: projectid,
            userid: user_id,
            action: 'getStatsForProject'
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $("#stats-panel .total_jobs_stats").text(data.data.total_jobs_stats);
                $("#stats-panel .avg_bid_per_job_stats").text("$" + data.data.avg_bid_per_job_stats);
                $("#stats-panel .avg_job_time_stats").text(data.data.avg_job_time_stats);
            }
        }
    });
})(jQuery);
</script>
<script type="text/javascript" src="js/uploadFiles.js"></script>
<?php
$meta_title =
  is_object($inProject)
  ?  'Project: ' . $inProject->getName()
  :  'Worklist | Build software fast, make money, great community.'
;
?>
<title><?php echo $meta_title; ?></title>
<style>
#welcomeInside .worklistBtn {
    color: #ffffff;
}
</style>
</head>
<body>
<div style="display: none; position: fixed; top: 0px; left: 0px; width: 100%; height: 100%; text-align: center; line-height: 100%; background: white; opacity: 0.7; filter: alpha(opacity =   70); z-index: 9998"
     id="loader_img"><div id="loader_img_title"><img src="images/loading_big.gif"
     style="z-index: 9999"></div></div>

<!-- js template for file uploads -->
<?php require_once('dialogs/file-templates.inc'); ?>
<!-- Popup for breakdown of fees-->
<?php require_once('dialogs/popup-fees.inc'); ?>
<!-- Popup for budget info -->
<?php require_once('dialogs/budget-expanded.inc') ?>
<!-- Popups for tables with jobs from quick links -->
<?php require_once('dialogs/popups-userstats.inc'); ?>
<!-- Popup for add role -->
<?php include('dialogs/popup-addrole.inc') ?>
<!-- Popup for viewing role -->
<?php include('dialogs/popup-role-info.inc') ?>
<!-- Popup for edit role -->
<?php include('dialogs/popup-edit-role.inc') ?>
<!-- Popup for TestFlight -->
<?php include('dialogs/popup-testflight.inc') ?>
<?php
    require_once('header.php');
    require_once('format.php');
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
<!-- Popup for editing/adding  a work item -->
<?php require_once('dialogs/popup-edit.inc'); ?>
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
        <div id="modeSwitch">
<?php if ( $is_runner || $is_payer || $inProject->isOwner($userId)) : ?>
    <?php if ($edit_mode) : ?>
            <a href="?action=view">Switch to View Mode</a>
    <?php else: ?>
            <a href="?action=edit">Switch to Edit Mode</a>
    <?php endif; ?>
<?php endif; ?>
<?php if ($userId > 0 && $user->getIs_admin()) {?>
            <br/>
            <input type="checkbox" name="internal" id="internal" value="1" /> Internal
<?php } ?>
        </div>
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
            <p class="info-label">Website:
                <input name="website" id="website" type="text" value="<?php echo $inProject->getWebsite(); ?>" />
            </p>
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
        <li><strong>Total Funded:</strong> $<?php echo $inProject->getTotalFees($inProject->getProjectId()); ?></li>
        <li><strong>Contact Info:</strong> <?php echo $inProject->getContactInfo(); ?></li>
<?php if ($inProject->getRepository() != '') : ?>
        <li><strong>Repository:</strong> <a href="<?php echo $inProject->getRepoUrl(); ?>"><?php echo $inProject->getRepoUrl(); ?></a></li>
<?php else: ?>
        <li><strong>Repository:</strong> </li>
<?php endif; ?>
<?php if (! $edit_mode) : ?>
        <li><strong>Website:</strong>
    <?php if ($inProject->getWebsite() != '') : ?>
            <?php echo $inProject->getWebsiteLink(); ?>
    <?php endif; ?>
        </li>
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
                        <td class="total_jobs_stats">Loading ...</td>
                        <td class="avg_bid_per_job_stats" title="Average amount of accepted Bid per Job"></td>
                        <td class="avg_job_time_stats" title="Average time from Bid Accept to being Paid"></td>
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
                                        <td><a href="#" onClick="javascript:showUserInfo(<?php echo $payment['id']?>);"><?php echo $payment['nickname']?></a></td>
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
    <script type="text/html" id="projectWorkersBody">
                <# if (developers.length > 0) {
                    for(var i=0; i < developers.length; i++) {
                    var developer = developers[i];
                    #>
                    <tr class="row-developer-list-live">
                        <td class="developer"><a href="#" onclick="javascript:showUserInfo(<#= developer.id #>);"><#= developer.nickname #></a></td>
                        <td class="jobCount"><#= developer.totalJobCount #></td>
                        <td><#= developer.lastActivity #></td>
                        <td><#= developer.totalEarnings #></td>
                    </tr>
                    <# }
                } #>
    </script>
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

<?php
    include("footer.php");

