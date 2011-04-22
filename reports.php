<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
ob_start();

include("config.php");
include("class.session_handler.php");
include("check_session.php");
include_once("check_new_user.php"); 
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
$filter = new Agency_Worklist_Filter($_REQUEST, true);
if (!$filter->getStart()) {
    $filter->setStart(date("m/d/Y",strtotime('-90 days', time())));
}

if (!$filter->getEnd()) {
    $filter->setEnd(date("m/d/Y",time()));
}

$page = $filter->getPage();

if(isset($_POST['paid']) && !empty($_POST['paidList']) && !empty($_SESSION['is_payer'])) {
    Fee::markPaidByList(explode(',', trim($_POST['paidList'], ',')), $user_paid=0, $paid_notes='', $paid=1);
}

/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/teamnav.css" rel="stylesheet" type="text/css">
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<script src="js/raphael-min.js" type="text/javascript" charset="utf-8"></script>
<script src="js/timeline-chart.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/utils.js"></script>
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
    text-align: right;
    width:8em;
}
.start-date-label {
width:8em;
}
#search-filter-section {
list-style:none;
margin-bottom:1em;
background-color: #FFFFDD;
border: 2px solid #DCD998;
-moz-box-sizing:none;
}
#search-filter-section table, #search-filter-section table td #search-filter-section table th{
border: none;
}

#search-filter-section td, #search-filter-section th {
    border: none;
}

td.redtext {
color: red;
}
</style>
<script type="text/javascript">
var filterName = ".reports";
var _fromDate, _toDate;
var fromDate = '';
var toDate = '';
var datePickerControl; // Month/Year date picker.
var dateChangedUsingField = false; // True  if the date was changed using date field rather than picker.
var currentTab = <?php echo $showTab; ?>; // 0 for details and 1 for chart
var current_order = <?php echo $filter->getDir() == 'ASC' ? 'true' : 'false'; ?>;
var current_sortkey = '<?php echo $filter->getOrder(); ?>';
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

    function AppendPagination(page, cPages, table) {
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
    function AppendRow(json, odd) {
        var pre = '', post = '';
        var row;

        row = '<tr id="workitem-' + json[1] + '" class="row-worklist-live ';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += '">';
        <?php  if (!empty($_SESSION['is_payer'])) { ?>
            row += '<td><input type="checkbox" name="fee_id[]" value="' + json[1] + '" data="' + json[5] + '" class="workitem-paid" /> </td>';
            row += '<td> <a href="javascript: void();" onclick="withdraw_fee(\'' + json[1] + '\')">Void</a> </td>';
        <?php } ?> 
        if (json[0] == 0) {
        row += '<td>' + pre + 'Bonus' + post + '</td>'; // Id
        }
        if (json[0] != 0) {    
        pre = '<a href="workitem.php?job_id='+json[0]+'">';
        post = '</a>';
        row += '<td>' + pre + json[0] + post + '</td>'; // Id
        }
        pre = '', post = '';
        if (json[0] == 0) {
            row += '<td>' + pre + 'Bonus Payment' + post + '</td>'; // Summary
            }
        if (json[0] != 0) {
        row += '<td>' + pre + json[2] + post + '</td>'; // Summary
        }
        row += '<td>' + pre + json[3] + post + '</td>'; // Description
        row += '<td';
        if (json[7] == 0) {
            row += ' class="redtext"';
        }
        row += '>' + pre + formatValueForDisplay(json[4]) + post + '</td>'; // Payee
        row += '<td>' + pre + formatValueForDisplay(json[6]) + post; // Paid Date
        if (json[9] == 1) {
            row += ' (r)' + '</td>';
        }
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
        if (valueToFormat != null) {
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

    function GetReport(npage, reload, sort) {
        _fromDate = $("#start-date").datepicker('getDate');
        _toDate = $("#end-date").datepicker('getDate');
        if (_fromDate != null) {
            fromDate = fmtDate(_fromDate);
        }
        if(_toDate != null) {
        toDate = fmtDate(_toDate);
        }
        var order = '';
        sort_key= current_sortkey;
        var order = current_order ? 'ASC' : 'DESC';
        var paidStatus = $('#paid-status').val();

        $.ajax({
            type: "POST",
            url: 'getreport.php',
            data: {
                page: npage,
                status: $('select[name=status]').val(),
                user: $('select[name=user]').val(),
                project_id: $('select[name=project]').val(),
                order: sort_key,
                dir: order,
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

    function initializeTabs() {
        $("#tabs").tabs({selected: 0,
            select: function(event, ui) {
                if(ui.index == 0) {
                    currentTab = 0;
                    timeoutId = setTimeout("GetReport("+page+", true)", 50);
                } else {
                    currentTab = 1;
                    timeoutId = setTimeout("setupTimelineChart(false)", 50);
                }
            }
        });
        $( "#tabs" ).tabs( "option", "selected", 1 );
    }

function setupTimelineChart(reload) {
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
                project_id: $('select[name=project]').val(),
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
                if (data && data.fees && data.fees !== null  ) {
                    callback(data.fees, data.uniquePeople, data.feeCount, data.labels);
                }
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
    if (_fromDate != null) {
        fromDate = fmtDate(_fromDate);
    }
    if (_toDate != null) {
        toDate = fmtDate(_toDate);
    }

    LoveChart.load(_fromDate, _toDate, "");
}

    $(document).ready(function(){
        GetReport(<?php echo $page; ?>, true);

        // table sorting thing
        $('.table-worklist thead tr th').hover(function(e){
            if(! $('div', this).hasClass('show-arrow')){
                if ($(this).data('direction')) {
                    $('div', this).addClass('arrow-up');
                } else {
                    $('div', this).addClass('arrow-down');
                }
            }
        }, function(e){
            if(!$('div', this).hasClass('show-arrow')){
                $('div', this).removeClass('arrow-up');
                $('div', this).removeClass('arrow-down');
            }
        });

        $('.table-worklist thead tr th').data('direction', false); //false == desc order
        $('.table-worklist thead tr th').click(function(e){
            $('.table-worklist thead tr th div').removeClass('show-arrow');
            $('.table-worklist thead tr th div').removeClass('arrow-up');
            $('.table-worklist thead tr th div').removeClass('arrow-down');
            $('div', this).addClass('show-arrow');
            var direction = $(this).data('direction');
            
            if (direction){
                $('div', this).addClass('arrow-up');
            } else {
                $('div', this).addClass('arrow-down');
            }
            
            var data = $(this).metadata();
            if (!data.sortkey) {
                alert("no sortkey");
                return false;
            }
            
            reload = false;
            current_sortkey = data.sortkey;
            current_order = $(this).data('direction');
            $('#sort-by').val(current_sortkey);
            GetReport(page, false, current_sortkey);
            $('.table-worklist thead tr th').data('direction', false); //reseting to default other rows
            $(this).data('direction',!direction); //switching on current
        }); //end of table sorting
        
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

        $('#sales-reports-button').click(function() {
            var new_window = window.open('sales-reports.php', '_blank');
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
              location.href = 'reports.php?reload=false&view=details&user=' + $('select[name=user]').val() + '&status=' + $('select[name=status]').val() + '&project_id=' + $('select[name=project]').val() + '&type=' + $('#type-status').val() + '&order=' + $('#sort-by').val() + '&start=' + fromDate + '&end=' + toDate + '&paidstatus=' + $('#paid-status').val();
            } else {
              location.href = 'reports.php?reload=false&view=chart&user=' + $('select[name=user]').val() + '&status=' + $('select[name=status]').val() + '&project_id=' + $('select[name=project]').val() + '&type=' + $('#type-status').val() + '&order=' + $('#sort-by').val() + '&start=' + fromDate + '&end=' + toDate + '&paidstatus=' + $('#paid-status').val();
            }
        });

        $('#tabs').tabs('select', currentTab);

        $('#type-status,#paid-status,#sort-by,select[name=status],select[name=project]').bind({
            'beforeshow newlist': function(e, o) {
                o.list.css("z-index","100")
            }}).comboBox();
   });
</script>
<script type="text/javascript" src="js/utils.js"></script>

<title>Worklist Reports | Lend a Hand</title>

</head>

<body>
<!-- Popup for breakdown of fees-->
<?php require_once('dialogs/popup-fees.inc') ?>
<!-- Popup for add project info-->
<?php require_once('dialogs/popup-addproject.inc'); ?>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

<div>
    <div id="pp-reports-box" style="float:left;">
        <input type="submit" value="PayPal Reports" id="pp-reports-button"></input><br />
        <?php if (!empty($_SESSION['is_payer'])) { ?>
            <input type="submit" value="Run MassPay" id="pp-masspay-button" /><br />
        <?php } ?>
        <?php if (!empty($_SESSION['is_runner'])) { ?>
            <input type="submit" value="Sales Reports" id="sales-reports-button"></input>
        <?php } ?>
    </div>
    <div id="search-filter-wrap-reports">
      <table id="search-filter-section">
      <tr>
        <td class="textAlignReport">
            <div >Payee: <?php echo $filter->getUserSelectbox(1,true); ?></div>
            <div class="second-line">
                Project:  <?php echo $filter->getProjectSelectbox(true); ?>
            </div>
            </td>
        <td class="textAlignReport">
            <div>Paid Status: 
          <select id="paid-status" >
            <option value="ALL"<?php echo(($filter->getPaidstatus() == 'ALL') ? ' selected="selected"' : ''); ?>>ALL</option>
            <option value="1"<?php echo(($filter->getPaidstatus() == '1') ? ' selected="selected"' : ''); ?>>Paid</option>
            <option value="0"<?php echo(($filter->getPaidstatus() == '0') ? ' selected="selected"' : ''); ?>>Unpaid</option>
          </select>
          </div>
          <div class="second-line">
            Type:
            <select id="type-status">
                <option value="ALL"<?php echo(($filter->getType() == 'ALL') ? ' selected="selected"' : ''); ?>>ALL</option>
                <option value="Fee"<?php echo(($filter->getType() == 'Fee') ? ' selected="selected"' : ''); ?>>Fee</option>
                <option value="Bonus"<?php echo(($filter->getType() == 'Bonus') ? ' selected="selected"' : ''); ?>>Bonus</option>
                <option value="Expense"<?php echo(($filter->getType() == 'Expense') ? ' selected="selected"' : ''); ?>>Expense</option>
                </select>
            </div>
        </td>
        <td class="textAlignReport">
            <div>Item status: <?php echo $filter->getStatusSelectbox(true); ?>
            </div>
            <div class="second-line">Order:
            <select id="sort-by">
                <option value="name"<?php echo(($filter->getOrder() == 'name') ? ' selected="selected"' : ''); ?>>Alphabetically</option>
                <option value="date"<?php echo(($filter->getOrder() == 'date') ? ' selected="selected"' : ''); ?>>Chronologically</option>
            </select>
            </div>
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
                <th width="7%" class="sort {sortkey: 'id'} clickable">ID<div class = "arrow"><div/></th>
                <th width="30%" class="sort {sortkey: 'summary'} clickable">Summary<div class = "arrow"><div/></th>
                <th width="25%" class="sort {sortkey: 'desc'} clickable">Description<div class = "arrow"><div/></th>
                <th width="12%" class="sort {sortkey: 'payee'} clickable">Payee<div class = "arrow"><div/></th>
                <th width="15%" class="sort {sortkey: 'paid_date'} clickable">Paid Date<div class = "arrow"><div/></th>
                <th width="5%" class="sort {sortkey: 'fee'} clickable">Fee<div class = "arrow"><div/></th>
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
