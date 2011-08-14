<?php
//  vim:ts=4:et
//
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

ob_start();
include("config.php");
include("class.session_handler.php");
include("check_session.php");
include("check_new_user.php");
include("functions.php");
require_once('classes/UserStats.class.php');
require_once('classes/User.class.php');

$con=mysql_connect( DB_SERVER,DB_USER,DB_PASSWORD );
mysql_select_db( DB_NAME,$con );

$cur_letter = isset( $_POST['letter'] ) ? $_POST['letter'] : "all";
$cur_page = isset( $_POST['page'] ) ? intval($_POST['page'] ) : 1;

$sfilter = !empty( $_POST['sfilter'] ) ? $_POST['sfilter'] : 'PAID';
$userId = getSessionUserId();
if( $userId > 0 )   {
    initUserById($userId);
    $user = new User();
    $user->findUserById( $userId );
    $nick = $user->getNickname();
    $userbudget =$user->getBudget();
    $budget = number_format($userbudget);
}

$newStats = UserStats::getNewUserStats();
/*********************************** HTML layout begins here  *************************************/

include("head.html");
?>

<title>Worklist | Team Members</title>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/teamnav.css" rel="stylesheet" type="text/css">
<link href="css/worklist.css" rel="stylesheet" type="text/css" >

<script type="text/javascript" src="js/jquery.timeago.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/utils.js"></script>

<script type="text/javascript">
var current_letter = '<?php echo $cur_letter; ?>';
var logged_id = <?php echo $_SESSION['userid']; ?>;
var runner =  <?php echo !empty($_SESSION['is_runner']) ? 1 : 0; ?>;
var current_page = <?php echo $cur_page; ?>;
var current_sortkey = 'earnings30';
var current_order = false;
var sfilter = '30'; // Default value for the filter
var show_actives = "FALSE";
  
$(document).ready(function() {

// Set the users with fees in X days label
    $('.select-days').html(sfilter + ' days');

    $('#outside').click(function() { //closing userbox on clicking outside of it
        $('#user-info').dialog('close');
    });

    fillUserlist(current_page);

    $('.ln-letters a').click(function(){
        var classes = $(this).attr('class').split(' ');
        current_letter = classes[0];
        fillUserlist(1);
        return false;
    });

    //table sorting thing
    $('.table-userlist thead tr th').hover(function(e){
        if(!$('div', this).hasClass('show-arrow')){
            if($(this).data('direction')){
                $('div', this).addClass('arrow-up');
            }else{
                $('div', this).addClass('arrow-down');
            }
        }
    }, function(e){
        if(!$('div', this).hasClass('show-arrow')){
            $('div', this).removeClass('arrow-up');
            $('div', this).removeClass('arrow-down');
        }
    });

    $('.table-userlist thead tr th').data('direction', false); //false == desc order
    $('.table-userlist thead tr th').click(function(e){
        $('.table-userlist thead tr th div').removeClass('show-arrow');
        $('.table-userlist thead tr th div').removeClass('arrow-up');
        $('.table-userlist thead tr th div').removeClass('arrow-down');
        $('div', this).addClass('show-arrow');
        var direction = $(this).data('direction');
        
        if(direction){
            $('div', this).addClass('arrow-up');
        }else{
            $('div', this).addClass('arrow-down');
        }
        
        var data = $(this).metadata();
        if (!data.sortkey) return false;
        current_sortkey = data.sortkey;
        current_order = $(this).data('direction');
        fillUserlist(current_page);
        
        $('.table-userlist thead tr th').data('direction', false); //reseting to default other rows
        $(this).data('direction',!direction); //switching on current
    }); //end of table sorting

    $('#user-info').dialog({
        autoOpen: false,
        modal: true,
        show: 'fade',
        hide: 'fade',
        height: 480,
        width: 840
    });
    if ($("#budgetPopup").length > 0) {
        $("#welcome .budget").html(' <a href="javascript:;" class="budget">Budget</a> ');
        $("#budgetPopup").dialog({
            title: "Budget",
            autoOpen: false,
            height: 'auto',
            width: '250px',
            position: ['center', 60],
            modal: true
        });
        $("#welcome .budget").click(function(){
            $("#budgetPopup").dialog("open");
        });
    };
        
    /**
     * Enable filter for users with fees in the last X days
     */
    $('#filter-by-fees').click(function() {
        if( show_actives == "FALSE") {
            show_actives = "TRUE";
            fillUserlist(current_page);
        } else {
            show_actives = "FALSE";
            fillUserlist(current_page);
        }
    });
    
    /**
     * Select users with fees in XX days
     */
    $('.days').change(function() {
        // Set the days filter
        sfilter = $('.days option:selected').val();
        
        // If the filter is active reload the list
        if (show_actives === "TRUE") {
            fillUserlist(current_page);
        }
    });
    
<?php 
    if( !empty($_REQUEST['showUser'])) {
        echo "showUserInfo(" . $_REQUEST['showUser'] . ");";
    }
?>
});

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
}

function fillUserlist(npage) {
    current_page = npage;
    var order = current_order ? 'ASC' : 'DESC';
    $.ajax({
        type: "POST",
        url: 'getuserlist.php',
        data: 'letter=' + current_letter + '&page=' + npage + '&order=' + current_sortkey + '&order_dir=' + order + '&sfilter=' + sfilter + '&active=' + show_actives,
        dataType: 'json',
        success: function(json) {
        
            $('.ln-letters a').removeClass('ln-selected');
            $('.ln-letters a.' + current_letter).addClass('ln-selected');
                    
            var page = json[0][1]|0;
            var cPages = json[0][2]|0;
            
            $('.row-userlist-live').remove();
            
            if (json.length > 1) {
                $('.table-hdng').show();
                $('#message').hide();
            }else{
                $('.table-hdng').hide();
                $('#message').show();
            }
            
            var odd = true;
            for (var i = 1; i < json.length; i++) {
                AppendUserRow(json[i], odd);
                odd = !odd;
            }
            
            $('tr.row-userlist-live').click(function(){
                var match = $(this).attr('class').match(/useritem-\d+/);
                var userid = match[0].substr(9);
                showUserInfo(userid);
                return false;
            });
            
            if(cPages > 1){ //showing pagination only if we have more than one page
            $('.ln-pages').html('<span>'+outputPagination(page,cPages)+'</span>');
                        
            $('.ln-pages a').click(function(){
                page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
                fillUserlist(page);
                return false;
            });
            
            }else{
                $('.ln-pages').html('');
            }
        },
        error: function(xhdr, status, err) {}
    });
}
  
function outputPagination(page, cPages) {
    var pagination = '';
    if (page > 1) { 
        pagination += '<a href="#?page=' + (page-1) + '">Prev</a>'; 
    } 
    for (var i = 1; i <= cPages; i++) {
        var sel = '';
        if (i == page) { 
            if (page == cPages) {
                sel = ' class="ln-selected ln-last"';
            } else {
                sel = ' class="ln-selected"';
            }
        }
        pagination += '<a href="#?page=' + i + '"' + sel + '>' + i + '</a>';  
    }
    if (page < cPages) { 
        pagination += '<a href="#?page=' + (page+1) + '" class = "ln-last">Next</a>'; 
    } 
    return pagination;
}

function AppendUserRow(json, odd) {
    var row;
    var pre = '';
    var post = '';
    row = '<tr class="row-userlist-live ';
    if (odd) {
        row += 'rowodd';
    } else {
        row += 'roweven';
    }
    row += ' useritem-' + json.id + '">';
    row += '<td class = "name-col">' + json.nickname + '</td>';
    row += '<td class="age">'+ json.joined + '</td>';
    row += '<td class="jobs">' + json.jobs_count + '</td>';
    row += '<td class="money">' + json.budget + '</td>';
    row += '<td class="money">$' +addCommas(Math.round(json.earnings)) + '</td>';
    row += '<td class="money">$' + addCommas(Math.round(json.earnings30)) + '</td>';
    row += '<td class="money">$' + addCommas(Math.round(json.rewarder)) + ' / ' + Math.round((parseFloat(json.rewarder) / (parseFloat(json.earnings) + 0.000001)) * 100*100)/100 + '%</td>';
    $('.table-userlist tbody').append(row);
}

function addCommas(nStr) {
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}

</script>
</head>

<body>
<!-- Popup for breakdown of fees-->
<?php require_once('dialogs/popup-fees.inc') ?>
<!-- Popup for add project info-->
<?php require_once('dialogs/popup-addproject.inc'); ?>
<?php include("format.php"); ?>
<!-- Popup for budget info -->
<?php require_once('dialogs/budget-expanded.inc'); ?>

<h1>Team Members</h1>
<table id="newUserStats">
    <caption>New user statistics - past 30 days</caption>
    <tr class="table-hdng">
        <th>New users</th>
        <th>Logged in</th>
        <th>With fees</th>
        <th>With bids</th>
    </tr>
    <tr>
        <td><?php echo $newStats['newUsers']; ?></td>
        <td><?php echo $newStats['newUsersLoggedIn']; ?></td>
        <td><?php echo $newStats['newUsersWithFees']; ?></td>
        <td><?php echo $newStats['newUsersWithBids']; ?></td>
    </tr>
</table>
<div class="clear"></div>
<div class="active-users">
    <input type="checkbox" id="filter-by-fees">Has fees in the last
       <select name="days" class="days">
           <option value="7">7 days</option>
           <option value="30" selected="selected">30 days</option>
           <option value="60">60 days</option>
           <option value="90">90 days</option>
           <option value="360">1 year</option>
       </select>
    </input>
</div>
<div class="clear"></div>
<div id="message">No results</div>
<table class="table-userlist" style="width:100%">
    <thead>
        <tr class="table-hdng">
            <th class="sort {sortkey: 'nickname'} clickable">Nickname<div class = "arrow"><div/></th>
            <th class="sort {sortkey: 'added'} clickable age">Age<div class = "arrow"><div/></th>
            <th class="sort {sortkey: 'jobs_count'} clickable jobs">Jobs<div class = "arrow"><div/></th>
            <th class="sort {sortkey: 'budget'} clickable money">Budget<div class = "arrow"><div/></th>
            <th class="sort {sortkey: 'earnings'} clickable money">Total Earnings<div class = "arrow"><div/></th>
            <th class="sort {sortkey: 'earnings30'} clickable money">30 Day Earnings<div class = "arrow"><div/></th>
            <th class="sort {sortkey: 'rewarder'} clickable money">Bonus $ / %<div class = "arrow"><div/></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
<div class="ln-letters"><a href="#" class="all ln-selected">ALL</a><a href="#" class="_">0-9</a><a href="#" class="a">A</a><a href="#" class="b">B</a><a href="#" class="c">C</a><a href="#" class="d">D</a><a href="#" class="e">E</a><a href="#" class="f">F</a><a href="#" class="g">G</a><a href="#" class="h">H</a><a href="#" class="i">I</a><a href="#" class="j">J</a><a href="#" class="k">K</a><a href="#" class="l">L</a><a href="#" class="m">M</a><a href="#" class="n">N</a><a href="#" class="o">O</a><a href="#" class="p">P</a><a href="#" class="q">Q</a><a href="#" class="r">R</a><a href="#" class="s">S</a><a href="#" class="t">T</a><a href="#" class="u">U</a><a href="#" class="v">V</a><a href="#" class="w">W</a><a href="#" class="x">X</a><a href="#" class="y">Y</a><a href="#" class="z ln-last">Z</a></div>
<div class="ln-pages"></div>
<div id="user-info" title="User Info"></div>
<div id="budgetPopup" style="display:none;">
    <div id="be-block" >
        <table id="be-table">
            <tr>
                <td class="be-table_cell1 iToolTip budgetRemaining"><strong>Remaining Funds:</strong></td>
                <td class="be-table_cell2 iToolTip budgetRemaining"><strong>
                <?php echo(money_format('$ %i', $user->getRemainingFunds())); ?></strong></td>
            </tr>
            <tr>
                <td onClick="budgetExpand(0)" class="be-table_cell1 iToolTip budgetAllocated">Allocated:</td>
                <td onClick="budgetExpand(0)" class="be-table_cell2 iToolTip budgetAllocated">
                <?php echo(money_format('$ %i', $user->getAllocated())); ?></td>
            </tr>
            <tr>
                <td onClick="budgetExpand(1)" class="be-table_cell1 iToolTip budgetSubmitted">Submitted:</td>
                <td onClick="budgetExpand(1)" class="be-table_cell2 iToolTip budgetSubmitted">
                <?php echo(money_format('$ %i', $user->getSubmitted())); ?></td>
            </tr>
            <tr>
                <td onClick="budgetExpand(2)" class="be-table_cell1 iToolTip budgetPaid">Paid:</td>
                <td onClick="budgetExpand(2)" class="be-table_cell2 iToolTip budgetPaid">
                <?php echo(money_format('$ %i', $user->getPaid())); ?></td>
            </tr>
        </table>
    </div>
</div>
<?php
  $user_id = isset($_SESSION['userid']) ? $_SESSION['userid'] : 0 ;
  if ( $user_id > 0) { ?>
<script type="text/javascript">
    var status_refresh = 5 * 1000;
    var statusTimeoutId = null;
    var lastStatus = 0;
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
                if(lastStatus < json[0]["timeplaced"]) {
                    lastStatus = json[0]["timeplaced"];
                    $('#status-update').val(json[0]["status"]);
                    $('#status-update').hide();
                    $('#status-lbl').show();
                    $("#status-share").hide();
                    $('#status-lbl').html( '<b>' + json[0]["status"] + '</b>' );
               }
            }
        });
        statusTimeoutId = setTimeout("GetStatus('journal')", status_refresh);
    }
    GetStatus('worklist');
</script>
<?php }
include("footer.php");?>
