<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

require_once ("config.php");
if (! empty($_SERVER['PATH_INFO'])) {
    header( 'Location: https://' . SERVER_NAME . '/worklist/worklist.php');
    exit;
}

require_once ("class.session_handler.php");
require_once ("check_new_user.php");
require_once ("functions.php");
require_once ("send_email.php");
require_once ('lib/Agency/Worklist/Filter.php');

$page = isset($_REQUEST["page"]) ? (int) $_REQUEST['page'] : 1; // Get the page number to show, set default to 1

$journal_message = '';
// $nick is setup above.. and then overwritten here -- lithium
$nick = '';

$userId = getSessionUserId();

if ($userId > 0) {
    initUserById($userId);
    $user = new User();
    $user->findUserById($userId);
    // @TODO: this is overwritten below..  -- lithium
    $nick = $user->getNickname();
    $userbudget =$user->getBudget();
    $budget = number_format($userbudget);
}

$is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
$is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
$is_admin = !empty($_SESSION['is_admin']) ? 1 : 0;

$workitem = new WorkItem();

$filter = new Agency_Worklist_Filter();

// krumch 20110418 Set to open Worklist from Journal
$filter->initFilter();
$filter->setName('.worklist');
$project_id = 0;

if (! empty($_REQUEST['status'])) {
    $filter->setStatus($_REQUEST['status']);
} else {
    if (array_key_exists('status', $_REQUEST)) {
        $filter->setStatus('ALL');
    }
}

if (isset($_REQUEST['project'])) {
    $project = new Project();
    try {
        $project->loadByName($_REQUEST['project']);
        if ($project_id = $project->getProjectId()) {
            $filter->setProjectId($project_id);
        }
    } catch(Exception $e) {
        $filter->setProjectId(0);
    }
    unset($project);
}

if (! empty($_REQUEST['user'])) {
    $filter->setUser($_REQUEST['user']);
} else {
    if (array_key_exists('user', $_REQUEST)) {
        $filter->setUser(0);
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
        $$arg = (! empty($_POST[$arg])) ? $_POST[$arg] : '';
    }

    $creator_id = $userId;

    if (!empty($_POST['itemid']) && ($_POST['status']) != 'Draft') {
        $workitem->loadById($_POST['itemid']);
        $journal_message .= $nick . " updated ";
    } else {
        $workitem->setCreatorId($creator_id);
        $journal_message .= $nick . " added ";
    }
    $workitem->setSummary($summary);

    $workitem->setBugJobId($bug_job_id);
    // not every runner might want to be assigned to the item he created - only if he sets status to 'BIDDING'
    if ($status == 'Bidding' && $user->getIs_runner() == 1) {
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
    if (is_bug) {
        $bug_journal_message = " (bug of job #".$bug_job_id.")";
        notifyOriginalUsersBug($bug_job_id, $workitem);
    }

    if (empty($_POST['itemid']))  {
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

    if (! empty($_POST['invite'])) {
        $people = explode(',', $_POST['invite']);
        invitePeople($people, $workitem);
    }

    // send journal notification is there is one
    if (! empty($journal_message)) {
        sendJournalNotification(stripslashes($journal_message));
    }
}

/* Prevent reposts on refresh */
if (! empty($_POST)) {
    unset($_POST);
    header('Location: ' . APP_BASE . 'worklist.php');
    exit();
}

$worklist_id = isset($_REQUEST['job_id']) ? intval($_REQUEST['job_id']) : 0;

/*********************************** HTML layout begins here  *************************************/
require_once("head.html");
require_once('opengraphmeta.php');
?>
<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="js/paginator.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/paginator.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter_desc.js"></script>
<script type="text/javascript" src="js/skills.js"></script>
<script type="text/javascript">
    var lockGetWorklist = 0;
    var status_refresh = 5 * 1000;
    var statusTimeoutId = null;
    var lastStatus = 0;

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
    var addFromJournal = false;
    var dir = '<?php echo $filter->getDir(); ?>';
    var sort = '<?php echo $filter->getSort(); ?>';
    var resetOrder = false;
    stats.setUserId(user_id);
    var activeProjectsFlag = true;
    var skills = null;
    var only_needs_review_jobs = <?php echo $_GET['status'] == 'needs-review' ? 'true' : 'false' ?>;

    function AppendPagination(page, cPages, table)    {
        // support for moving rows between pages
        if (table == 'worklist') {
            // preparing dialog
            $('#pages-dialog select').remove();
            var selector = $('<select>');
            for (var i = 1; i <= cPages; i++) {
                selector.append('<option value = "' + i + '">' + i + '</option>');
            }
            $('#pages-dialog').prepend(selector);
        }
        
        var selfLink = '<?php echo $_SERVER['PHP_SELF'] ?>?page=';
        
        var previousLink = page > 1 
                ? '<a class="previous" href="' + selfLink + (page-1) + '">Previous</a> ' 
                : '<span class="previous inactive">Previous</span>',
            nextLink = page < cPages 
                ? '<a class="next" href="' + selfLink + (page+1) + '">Next</a> ' 
                : '<span class="next inactive">Next</span>';
        
        var pagination = 
            '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table + '-pagination-row nodrag nodrop">' +
            '<td class="not-workitem" colspan="6" style="text-align:center;"><span>' + previousLink;
            
        var fromPage = 1;
        if (cPages > 10 && page > 6) {
            if (page + 4 <= cPages) {
                fromPage = page - 6;
            } else {
                fromPage = cPages - 10;
            }
        }
        
        for (var i = fromPage; (i <= (fromPage +10) && i <= cPages); i++) {
            if (i == page) {
                pagination += '<span class="page current">' + i + "</span> ";
            } else {
                pagination += '<a class="page" href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + i + '" >' + i + '</a> ';
            }
        }
        pagination += nextLink + '</span></td></tr>';
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
        if (json[2] != 'Bidding') {
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

        var project_link = worklistUrl + '' + json[17];
        row +=
            '<td class="clickable not-workitem" onclick="location.href=\'' + project_link + '\'">' +
                '<div class="taskProject" id="' + json[16] + '">' +
                    '<span><a href="' + project_link + '">' + (json[17] == null ? '' : json[17]) + '</a></span>' +
                '</div>' +
            '</td>';
        //If job is a bug, add reference to original job
        if( json[18] > 0) {
            extraStringBug = '<small> (bug of '+json[18]+ ') </small>';
        } else {
            extraStringBug = '';
        }

        // Displays the ID of the task in the first row
        // 26-APR-2010 <Yani>
        var workitemId = 'workitem-' + json[0];
        row += '<td>' + 
                 '<div id="' + workitemId + '" class="taskSummary">' +
                   '<span>' +
                     '<span class="taskID">#' + json[0] + '</span> - ' +
                     json[1] + extraStringBug +
                   '</span>' +
                 '</div>' +
               '</td>';
        var bidCount = '';
        if ((json[2] == 'Bidding' || json[2] == 'SuggestedWithBid') && json[10] > 0) {
            bidCount = ' (' + json[10] + ')';
        }
        row += '<td><div class="taskStatus"><span>' + json[2] + bidCount + '</span></td>';

        var who = '',
            createTagWho = function (id, nickname, type) {
                return '<span class="' + type + '" title="' + id + '">' + nickname + '</span>';
            };
        if (json[3] == json[4]) {
            // creator nickname can't be null, so not checking here
            who += createTagWho(json[9],json[3],"creator");
        } else {
            var runnerNickname = json[4] != null ? ', ' + createTagWho(json[13], json[4], "runner") : '';
            who += createTagWho(json[9], json[3], "creator") + runnerNickname;
        }
        if (json[5] != null){
            who += ', ' + createTagWho(json[14], json[5], "mechanic");
        }
    
        row += '<td class="who not-workitem">' + 
                 '<div class="taskWho">' +
                   who + 
                 '</div>' +
               '</td>';
        
        if (json[2] == 'Working' && json[11] != null) {
            var pastDuePre = '', 
                pastDuePost = '',
                strAge = RelativeTime(json[11], true);
            if (strAge.substr(0, 1) == '-') {
                strAge = strAge.substr(1);
                strAge = "<span class='past-due'>Due: " + strAge + '</div>';
            } else {
                strAge = strAge + ' from now';
            }
            row += '<td>' +
                     '<div class="taskAge">' + 
                       '<span>' +
                         strAge
                       '</span>' +
                     '</div>' +
                   '</td>';
        } else if (json[2] == 'Done' ) {
    if (json[6] != null){
            row += '<td>' +
                     '<div class="taskAge">' + 
                       '<span>' +
                         RelativeTime(json[6], true) +
                       '</span>' +
                     '</div>' +
                    '</td>';
            } else {
            row += '<td>' +
                     '<div class="taskAge">' + 
                       '<span>' +
                         'unknown'
                       '</span>' +
                     '</div>' +
                   '</td>';
            }
        } else {
            row += '<td>' +
                     '<div class="taskAge">' + 
                       '<span>' +
                         RelativeTime(json[6], true) +
                       '</span>' +
                     '</div>' +
                   '</td>';
        }

        // Comments
        comments = (json[12] == 0) ? "" : json[12];
        row += '<td>' + 
                 '<div class="taskComments">' + 
                   '<span>' +
                     comments +
                   '<span>' +
                 '</div>' +
               '</td>';

        row += '</tr>';
        if (prepend) {
            // animate in each row
            $(row).hide().prependTo('.table-worklist tbody').fadeIn(300);
            $('#workitem-' + json[0]).removeAttr('style');
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
        loaderImg.show("loadRunning", "Loading, please wait ...");
        //if ($('#statusCombo').val().length == 1 && $('#statusCombo').val()[0] == 'review')
        var search_status = ($('#statusCombo').val() || []).join("/");
        if (search_status == 'Review' && only_needs_review_jobs) {
            search_status = 'Needs-Review';
        }
        $.ajax({
            type: "POST",
            url: 'getworklist.php',
            cache: false,
            data: {
                page: npage,
                project_id: $('.projectComboList .ui-combobox-list-selected').attr('val'),
                status: search_status,
                sort: sort,
                dir: dir,
                user: $('.userComboList .ui-combobox-list-selected').attr('val'),
                inComment: $('#search_comments').is(':checked') ? 1 : 0,
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
                    window.open('userinfo.php?id=' + $(this).attr("title"), '_blank');
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
        if (!$('#cr_anyone_field').is(':checked') && !$('#cr_3_favorites_field').is(':checked') && !$('#cr_project_admin_field').is(':checked') && !$('#cr_job_runner_field').is(':checked')) {
            $('#cr_anyone_field').prop('checked', true);
            $('#edit_cr_error').html("One selection must be checked");
            $('#edit_cr_error').fadeIn();
            $('#edit_cr_error').delay(2000).fadeOut();
            return false;
        };
        
    };

    $(document).ready(function() {
        // Fix the layout for the User selection box
        var box_h = $('select[name=user]').height() +1;
        $('#userbox').css('margin-top', '-'+box_h+'px');

        // Validate code review input
        $(':checkbox').change(function() {
            validateCodeReviews();
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

        GetWorklist(<?php echo $page?>, false, true);

        reattachAutoUpdate();

        $('#query').keypress(function(event) {
            if (event.keyCode == '13') {
                event.preventDefault();
                $("#searchForm").submit();
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

        $("#search_comments").change(function(e) {
            affectedHeader = false;
            resetOrder = true;
            sort = 'null';
            dir = 'asc';
            GetWorklist(1, false);
        });

        $("#searchForm").submit(function(){
            var query = $('#query').val();
            GetWorklist(1, false);
            return false;
        });

        //-- gets every element who has .iToolTip and sets it's title to values from tooltip.php
        /* function commented for remove tooltip */
        //setTimeout(MapToolTips, 800);

        // bind on creation of newList
        if ($('#projectCombo').length !== 0) {
            createActiveFilter('#projectCombo', 'projects', 1);
        }

        if(getQueryVariable('status') != null) {
            if (timeoutId) clearTimeout(timeoutId);
            GetWorklist(page, false);
        }
    });

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
<script type="text/javascript" src="js/uploadFiles.js"></script>
<title>Worklist: Develop software fast.</title>
<style>
#welcomeInside .worklistBtn {
    color: #ffffff;
    background-position: -356px -59px;
}
</style>
</head>
<body id="worklist">
<div style="display: none; position: fixed; top: 0px; left: 0px; width: 100%; height: 100%; text-align: center; line-height: 100%; background: white; opacity: 0.7; filter: alpha(opacity =   70); z-index: 9998"
     id="loader_img"><div id="loader_img_title"><img src="images/loading_big.gif"
     style="z-index: 9999"></div></div>

<!-- js template for file uploads -->
<?php 
    require_once('dialogs/file-templates.inc'); 
    //Popup for breakdown of fees
    require_once('dialogs/popup-fees.inc'); 
    //Popup for budget info
    require_once('dialogs/budget-expanded.inc'); 
    //Popups for tables with jobs from quick links 
    require_once('dialogs/popups-userstats.inc'); 

    require_once('dialogs/budget-transfer.inc');
 
    require_once('header.php');
    require_once('format.php');
?>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

<!-- Head with search filters, user status, runer budget stats and quick links for the jobs-->
<?php
   include("search-head.inc"); ?>
<!-- Popup for editing/adding  a work item -->
<?php require_once('dialogs/popup-edit.inc'); ?>
<table class="table-worklist">
    <thead>
        <tr class="table-hdng">
            <td class="clickable">Project</td>
            <td><span class="clickable">Job ID</span> &amp; <span class="clickable">Summary</span></td>
            <td class="clickable">Status</td>
            <td class="clickable">Who</td>
            <td class="clickable" id="defaultsort">Age</td>
            <td class="clickable"><div class="comments-icon" title="Comments"></div></td>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
<span id="direction" style="display: none; float: right;"><img src="images/arrow-up.png" /></span>
<?php include("footer.php"); ?>