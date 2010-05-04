<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
ob_start();

include("config.php");
include("class.session_handler.php");
#include("check_session.php");
include_once("functions.php");
include_once("send_email.php");
include_once("classes/Fee.class.php");
require_once('lib/Agency/Worklist/Filter.php');

/* This page is only accessible to runners. */
if (empty($_SESSION['is_runner']) && empty($_SESSION['is_payer']) && isset($_POST['paid'])) {
    header("location:worklist.php");
    return;
}

if (!empty($_REQUEST['payee'])) {
    $payee = new User();
    $payee->findUserByNickname($_REQUEST['payee']);
    $_REQUEST['user'] = $payee->getId();
}

$showTab = 0;
if (!empty($_REQUEST['view'])) {
    if ($_REQUEST['view'] == 'chart') {
        $showTab = 1;
    }
}

$_REQUEST['name'] = '.reports';
$filter = new Agency_Worklist_Filter($_REQUEST);

if (!$filter->getStart()) {
    $filter->setStart(date("m/d/Y",strtotime('-2 weeks', time())));
}

if (!$filter->getEnd()) {
    $filter->setEnd(date("m/d/Y",time()));
}

$page = $filter->getPage();

if(isset($_POST['paid']) && !empty($_POST['paidList']) && !empty($_SESSION['is_payer'])) {
    $summaryData = Fee::markPaidByList(explode(',', trim($_POST['paidList'], ',')), $user_paid=0, $paid_notes='', $paid=1);

    foreach ($summaryData as $user_id=>$data) {
        if ($data[0] > 0) {
            $mail = 'SELECT `username`,`rewarder_points` FROM '.USERS.' WHERE `id` = '.$user_id;
            $userData = mysql_fetch_array(mysql_query($mail));

            $subject = "New LoveMachine Rewarder Points";
            $body  = "LoveMachine paid you $".$data[0]." and you earned ".$data[1]." rewarder points.";
            $body .= "You currently have ".$userData['rewarder_points']." points available to reward other LoveMachiners with. ";
            $body .= "Reward them now on the Rewarder page:<br/>&nbsp;&nbsp;&nbsp;&nbsp;".SERVER_BASE."worklist/rewarder.php<br/><br/>";
            $body .= "Thank you!<br/><br/>Love,<br/>The LoveMachine<br/>";

            sl_send_email($userData['username'], $subject, $body);
        }
    }
}

/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<script
	src="js/raphael-min.js" type="text/javascript" charset="utf-8"></script>
<script
	src="js/timeline-chart.js" type="text/javascript" charset="utf-8"></script>


<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>

<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
<link rel="stylesheet" href="css/datepicker.css" type="text/css" media="screen">
<style type="text/css">

#date-fields {
float:top;
margin-left:10px;
margin-top:20px;
}
#date-fields label {
  float: left;
  width: 2em;
  margin-right: 1em;
}
.text-field-sm {
  width:80px;
}
.report-left-label {
width:8em;
}
.start-date-label {
width:8em;
}
#search-filter-section {
list-style:none;
margin-bottom:1em;
}
#search-filter-section table, #search-filter-section table td #search-filter-section table th{
border: none;
}
</style>
<script type="text/javascript">


var _fromDate, _toDate;
var fromDate = '';
var toDate = '';
var datePickerControl; // Month/Year date picker.
var dateChangedUsingField = false; // True  if the date was changed using date field rather than picker.
var currentTab = <?php echo $showTab; ?>; // 0 for details and 1 for chart

    /**
    * 
    */
    function withdraw_fee(fee_id) {
        var ajax_connection = $.get('wd_fee.php', { 'wd_fee_id': fee_id  }, 
            function(data) {
                if (data = 'Update Successful!') {
                    $('#workitem-'+fee_id).remove();
                }
                alert(data); 
            }
        );
    }

function fmtDate(d) {
    return '' + (d.getMonth()+1) + '/' + d.getDate() + '/' + d.getFullYear();
}

function fmtDate2(d) {
    return d.getFullYear() + '-' + String(d.getMonth() + 101).slice(-2) + '-' + String(d.getDate() + 100).slice(-2);
}

    var refresh = <?php echo AJAX_REFRESH ?> * 1000;
    var page = <?php echo $page ?>;
    var timeoutId;
    var ttlPaid = 0;
    var paid_list = [];
    var workitem = 0;
    var workitems;
    var user_id = <?php echo isset($_SESSION['userid']) ? $_SESSION['userid'] : '"nada"' ?>;
    var is_runner = <?php echo isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : '"nada"' ?>;

var getPaidItems = function() {
    var paidItems = 0;
    $(paid_list).each(function(idx, checked) {
       if (!checked) {
           return;
       }
       paidItems++;
    });
    return paidItems;
};

    function AppendPagination(page, cPages, table)
    {
        <?php if (!empty($_SESSION['is_payer'])) { ?>
            cspan = '8'
        <?php } else { ?> 
            cspan = '6'
        <?php } ?> 
        var pagination = '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table + '-pagination-row" ><td colspan="'+cspan+'" style="text-align:center;">Pages : &nbsp;';
        if (page > 1) {
            pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + (page-1) + '" title="'+(page-1)+'">Prev</a> &nbsp;';
        }
        for (var i = 1; i <= cPages; i++) {
            if (i == page) {
                pagination += i + " &nbsp;";
            } else {
                pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + i + '" title="'+i+'">' + i + '</a> &nbsp;';
            }
        }
        if (page < cPages) {
            pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + (page+1) + '" title="'+(page+1)+'">Next</a> &nbsp;';
        }
        pagination += '</td></tr>';
        $('.table-' + table).append(pagination);
    }
    

    // json row fields: id, summary, status, payee, fee
    function AppendRow(json, odd)
    {
        var pre = '', post = '';
        var row;

        row = '<tr id="workitem-' + json[1] + '" class="row-worklist-live ';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += '">';
        <?php  if (!empty($_SESSION['is_payer'])) { ?>
            row += '<td><input type="checkbox" name="fee_id[]" value="' + json[1] + '" data="' + json[5] + '" class="workitem-paid" /> </td>';
            row += '<td> <a href="javascript: void();" onclick="withdraw_fee(\'' + json[1] + '\')">Void</a> </td>';
        <?php } ?> 
        pre = '<a href="workitem.php?job_id='+json[0]+'">';
	    post = '</a>';
        row += '<td>' + pre + json[0] + post + '</td>'; // Id
	pre = '', post = '';
        row += '<td>' + pre + json[2] + post + '</td>'; // Summary
        row += '<td>' + pre + json[3] + post + '</td>'; // Description
        row += '<td>' + pre + formatValueForDisplay(json[4]) + post + '</td>'; // Payee
        row += '<td>' + pre + formatValueForDisplay(json[6]) + post + '</td>'; // Paid Date
        row += '<td>' + pre + '$' + json[5] + post + '</td>'; // Amount
        row += '</tr>';

        $('.table-worklist tbody').append(row);
    }

    /**
     *Formats the given value for display. For now null values are shown as --
     *
    */
    function formatValueForDisplay(valueToFormat) {
	var formattedValue = '--';
	if(valueToFormat != null) {
	    formattedValue = valueToFormat;
	}
	return formattedValue;
    }

    /**
     * Appends the Page , Grand totals to the bottom of table
     *
    */
    function AppendTotals(pageTotal, grandTotal) {
        <?php if (!empty($_SESSION['is_payer'])) { ?>
            cspan = '7'
        <?php } else { ?> 
            cspan = '5'
        <?php } ?> 
        row =  '<tr class="row-worklist-live rowodd">'+
                '   <td colspan="'+cspan+'" align="right">Page Total </td>' +
                '   <td align="center">'+ '$' + pageTotal +'</td>' +
                '</tr>';
        $('.table-worklist tbody').append(row);
        row =  '<tr class="row-worklist-live rowodd">'+
                '   <td colspan="'+cspan+'" align="right">Grand Total </td>' +
                '   <td align="center">'+ '$' + grandTotal +'</td>' +
                '</tr>';

        $('.table-worklist tbody').append(row);
    }

    function GetReport(npage, reload) {
	      _fromDate = $("#start-date").datepicker('getDate');
	     _toDate = $("#end-date").datepicker('getDate');
	      if(_fromDate != null) {
		fromDate = fmtDate(_fromDate);
	      }
	      if(_toDate != null) {
		toDate = fmtDate(_toDate);
	      }

	      var paidStatus = $('#paid-status').val();
        $.ajax({
            type: "POST",
            url: 'getreport.php',
            data: {
                page: npage,
                status: $('select[name=status]').val(),
                user: $('select[name=user]').val(),
                order: $('#sort-by').val(),
                // adding type field to the request
                // 28-APR-2010 <Yani>
                type: $('#type-status').val(),
                start: fromDate,
                end: toDate,
                paidstatus: paidStatus,
                reload: ((reload == true) ? true : false)
            },
            dataType: 'json',
            success: function(json) {
                $("#loader_img").css("display","none");
                page = json[0][1]|0;
                var cPages = json[0][2]|0;

                $('.row-worklist-live').remove();
                workitems = json;
                if (json[0][0] == 0 ) {
		  $('.table-worklist').append(
		      '<tr class="row-worklist-live rowodd">'+
		      '   <td colspan="8" align="center">Oops! We couldn\'t find any work items.</td>' +
		      '</tr>');

		  return;
		}

                /* Output the worklist rows. */
                var odd = true;
                for (var i = 1; i < json.length; i++) {
                    AppendRow(json[i], odd);
                    odd = !odd;
                }
                AppendPagination(page, cPages, 'worklist');
                AppendTotals(json[0][3]|0.00 ,json[0][4]|0.00);
                $('.table-worklist .workitem-paid').click(function(e){
                    $('#amtpaid').show();
                    if ($(this).attr('checked')) {
                        ttlPaid = parseFloat(ttlPaid) + parseFloat($(this).attr('data'));
                        paid_list[$(this).val()] = 1;
                    } else {
                        ttlPaid = parseFloat(ttlPaid) - parseFloat($(this).attr('data'));
                        paid_list[$(this).val()] = 0;
                    }
                    $('#amtpaid').text('($'+ttlPaid+' paid, ' + getPaidItems() + ' items)');
                });

                /* Reflect the paid list values as pages are reloaded. */
                $("#report-check-all").attr('checked', '');
                $('.table-worklist .workitem-paid').each(function(){
                    if (paid_list[$(this).val()]) $(this).attr('checked','checked');
                });
            },
            error: function(xhdr, status, err) {
                $('.row-worklist-live').remove();
                $('.table-worklist').append(
                    '<tr class="row-worklist-live rowodd">'+
                    '   <td colspan="8" align="center">Oops! We couldn\'t find any work items.  <a id="again" href="#">Please try again.</a></td>' +
                    '</tr>');
                $('#again').click(function(e){
                    $("#loader_img").css("display","none");
                    if (timeoutId) clearTimeout(timeoutId);
                    GetReport(page);
                    e.stopPropagation();
                    return false;
                });
            }
        });

        timeoutId = setTimeout("GetReport("+page+", true)", refresh);
    }

function initializeTabs()                                                                                                                                               
{                                                                                                                                                                       
        $("#tabs").tabs({selected: 0,
                select: function(event, ui) {
                    if(ui.index == 0)
                    {
                            currentTab = 0;
			    timeoutId = setTimeout("GetReport("+page+", true)", 50);
                    }
                    else
                    {
                            currentTab = 1;
                            timeoutId = setTimeout("setupTimelineChart(true)", 50);
                    }
                }                                                                                                                                                       
        });

}

function setupTimelineChart(reload)
{
	var chartPanelId = 'timeline-chart';
	$('#'+chartPanelId).empty();
	LoveChart.initialize(chartPanelId, 780, 300, 30);
	LoveChart.forceWeeklyLabels(false);
	LoveChart.fetchData = function (from, to, username, callback) {
	    if (from.getTime() > to.getTime()) {
	        var tmp = from;
	        from = to;
	        to = tmp;
	    }

	    var fromDate = fmtDate(from), toDate = fmtDate(to);
	    var paidStatus = $('#paid-status').val();
$.ajax({
            type: "POST",
            url: 'getreport.php',
            data: {
                qType: 'chart',
                status: $('select[name=status]').val(),
                user: $('select[name=user]').val(),
                order: $('#sort-by').val(),
                start: fromDate,
                end: toDate,
                paidstatus: paidStatus,
                // adding type filter content to the request
                // 30-APR-2010
                type: $('#type-status').val(),
                reload: ((reload == true) ? true : false)
            },
            dataType: 'json',
            success: function(data) {
	        callback(data.fees, data.uniquePeople, data.feeCount, data.labels);
	    } ,
            error: function(xhdr, status, err) {
                 $('#again').click(function(e){
                    $("#loader_img").css("display","none");
                    if (timeoutId) clearTimeout(timeoutId);
                    e.stopPropagation();
                    return false;
                });
            }
        });
	};
    loadTimelineChart();
}

function loadTimelineChart() {
	_fromDate = $("#start-date").datepicker('getDate');
	_toDate = $("#end-date").datepicker('getDate');
	if(_fromDate != null) {
	  fromDate = fmtDate(_fromDate);
	}
	if(_toDate != null) {
	  toDate = fmtDate(_toDate);
	}

	LoveChart.load(_fromDate, _toDate, "");
}
    $(document).ready(function(){
        GetReport(<?php echo $page; ?>, true);
        initializeTabs();
        $("#owner").autocomplete('getusers.php', { cacheLength: 1, max: 8 } );
        $("#report-check-all").live('change', function(){
            var isChecked = $("#report-check-all").attr('checked');

            $('.table-worklist .workitem-paid').each(function(){
                if (isChecked && !$(this).attr('checked')) {
                    $(this).attr('checked', 'checked');
                    ttlPaid = parseFloat(ttlPaid) + parseFloat($(this).attr('data'));
                    paid_list[$(this).val()] = 1;
                } else if (isChecked == '' && $(this).attr('checked')) {
                    $(this).attr('checked', '');
                    ttlPaid = parseFloat(ttlPaid) - parseFloat($(this).attr('data'));
                    paid_list[$(this).val()] = 0;
                }
                $('#amtpaid').text('($'+ttlPaid+' paid, ' + getPaidItems() + ' items)');
            });

            $('#amtpaid').show();
        });
        $('.worklist-pagination-row a').live('click', function(e){
            page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
            if (timeoutId) clearTimeout(timeoutId);
            GetReport(page);
            e.stopPropagation();
            return false;

        });
        $('#pay').click(function(){
            var paidLst = '';
            for (var i in paid_list) {
                if (paid_list[i]) paidLst += i + ',';
            }
            $('#paid-list').val(paidLst);
            return true;
        });

        // Show PayPal reports page
        $('#pp-reports-button').click(function() {
            var new_window = window.open('ppreports.php', '_blank');
            new_window.focus();
            return false;
        });
        
        // Show PayPal Payment Run page
        $('#pp-masspay-button').click(function() {
            var new_window = window.open('view-payments.php', '_blank');
            new_window.focus();
            return false;
        });

	$('.text-field-sm').datepicker({
		changeMonth: true,
		changeYear: true,
		maxDate: 0,
		showOn: 'button',
		dateFormat: 'mm/dd/yy',
		buttonImage: 'images/Calendar.gif',
		buttonImageOnly: true
	});

	$('#refreshReport').click(function() {
        paid_list = [];
	    if (timeoutId) clearTimeout(timeoutId);
	    _fromDate = $("#start-date").datepicker('getDate');
	    _toDate = $("#end-date").datepicker('getDate');
	    if(_fromDate != null) {
		    fromDate = fmtDate(_fromDate);
	    }
	    if(_toDate != null) {
		    toDate = fmtDate(_toDate);
	    }
	    if(currentTab == 0) {
	      location.href = 'reports.php?reload=false&view=details&user=' + $('select[name=user]').val() + '&status=' + $('select[name=status]').val() + '&type=' + $('#type-status').val() + '&order=' + $('#sort-by').val() + '&start=' + fromDate + '&end=' + toDate + '&paidstatus=' + $('#paid-status').val();
	    } else {
	      location.href = 'reports.php?reload=false&view=chart&user=' + $('select[name=user]').val() + '&status=' + $('select[name=status]').val() + '&type=' + $('#type-status').val() + '&order=' + $('#sort-by').val() + '&start=' + fromDate + '&end=' + toDate + '&paidstatus=' + $('#paid-status').val();
	    }
	});

    $('#tabs').tabs('select', currentTab);

    });
</script>

<title>Worklist Reports | Lend a Hand</title>

</head>

<body>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

<div>
    <div id="pp-reports-box" style="float:left;">
        <input type="submit" value="PayPal Reports" id="pp-reports-button"></input><br />
        <?php if (!empty($_SESSION['is_payer'])) { ?>
            <input type="submit" value="Run MassPay" id="pp-masspay-button" />
        <?php } ?>
    </div>
    <div id="search-filter-wrap-reports">
      <table id="search-filter-section">
	  <tr>
	    <td>Payee: <?php echo $filter->getUserSelectbox(); ?></td>
	    <td style="text-align: right;">Paid Status: 
	      <select id="paid-status" >
		    <option value="ALL"<?php echo(($filter->getPaidstatus() == 'ALL') ? ' selected="selected"' : ''); ?>>ALL</option>
		    <option value="1"<?php echo(($filter->getPaidstatus() == '1') ? ' selected="selected"' : ''); ?>>Paid</option>
		    <option value="0"<?php echo(($filter->getPaidstatus() == '0') ? ' selected="selected"' : ''); ?>>Unpaid</option>
	      </select>
	      <br />
        Type:
        <select id="type-status">
            <option value="ALL"<?php echo(($filter->getType() == 'ALL') ? ' selected="selected"' : ''); ?>>ALL</option>
            <option value="Fee"<?php echo(($filter->getType() == 'Fee') ? ' selected="selected"' : ''); ?>>Fee</option>
            <option value="Expense"<?php echo(($filter->getType() == 'Expense') ? ' selected="selected"' : ''); ?>>Expense</option>
            <option value="Rewarder"<?php echo(($filter->getType() == 'Rewarder') ? ' selected="selected"' : ''); ?>>Rewarder</option>
        </select>
	    </td>
	    <td style="text-align: right;">Item status: <?php echo $filter->getStatusSelectbox(); ?><br/>Order:
            <select id="sort-by">
                <option value="name"<?php echo(($filter->getOrder() == 'name') ? ' selected="selected"' : ''); ?>>Alphabetically</option>
                <option value="date"<?php echo(($filter->getOrder() == 'date') ? ' selected="selected"' : ''); ?>>Chronologically</option>
            </select>
        </td>
	   </tr>
	  <tr>
	      <td class="report-left-label">Fee added between</td>
	      <td >
	      <input type="text" class="text-field-sm" id="start-date" name="start_date" tabindex="1" value="<?php echo($filter->getStart()); ?>" title="Start Date" size="20" />
	      <label for="end-date"> and </label><input type="text" class="text-field-sm" id="end-date" name="end_date" tabindex="2" value="<?php echo($filter->getEnd()); ?>" title="End Date" size="20" />
	      </td>
	      <td style = "text-align: right;">
		<input type="submit" value="Go" id="refreshReport"></input>
	      </td>
	  </tr>
      </table>
    </div>
    <div style="clear:both"></div>
    <div id="tabs">
    <ul>
        <li><a href="#tab-details" >Details</a></li>
        <li><a href="#tab-chart" >Chart</a></li>
    </ul>
    <div id="tab-details">
            <form id="reportForm" method="post" action="" />
        <input type="hidden" id="paid-list" name="paidList" value="" />
        <table width="100%" class="table-worklist">
            <thead>
            <tr class="table-hdng">
                <?php if (!empty($_SESSION['is_payer'])) { ?>
                <td width="3%"><input type="checkbox" id="report-check-all" value="1" /></td>
                <td width="5%">Void Fee</td>
                <?php } ?>
                <td width="7%">ID</td>
                <td width="30%">Summary</td>
                <td width="25%">Description</td>
                <td width="12%">Payee</td>
                <td width="12%">Paid Date</td>
                <td width="5%">Fee</td>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <?php if (!empty($_SESSION['is_payer'])) { ?>
        <input type="submit" id="pay" name="paid" value="Mark Paid" /> <span id="amtpaid" style="display:none">($0 paid)</span>
        <?php } ?>
    </form>
    </div>
    <div id="tab-chart">
        <div id="timeline-chart">

        </div>
    </div>
    </div>
</div>
<?php include("footer.php"); ?>
