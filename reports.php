<?php 
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

ob_start(); 

include("config.php");
include("class.session_handler.php");
include("check_session.php");
include_once("functions.php");
include_once("send_email.php");

/* This page is only accessible to runners. */
if (empty($_SESSION['is_runner']) && empty($_SESSION['is_payer'])) {
    header("location:worklist.php");
    return;
}

if(!isset($_SESSION['ufilter'])) {
  $_SESSION['ufilter'] = 'ALL';
}

$t_date = (isset($_POST['end_date'])) ? strtotime(trim($_POST['end_date'])) : time();
$f_date = (isset($_POST['start_date'])) ? strtotime(trim($_POST['start_date'])) : strtotime('-1 month', $t_date);

$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

if(isset($_POST['paid']) && !empty($_POST['paidList']) && !empty($_SESSION['is_payer'])) {
    foreach (explode(',', trim($_POST['paidList'], ',')) as $fee_id) {
        $fee_id = intval($fee_id);
        $query = "update `".FEES."` set `user_paid`={$_SESSION['userid']}, `paid`=1, paid_date = now() WHERE `id`={$fee_id}";
        $rt = mysql_query($query);
    }
}

//list of users for filtering
$userid = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";


/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
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

function fmtDate(d) {
    return '' + (d.getMonth()+1) + '/' + d.getDate() + '/' + d.getFullYear();
}

function fmtDate2(d) {
    return d.getFullYear() + '-' + String(d.getMonth() + 101).slice(-2) + '-' + String(d.getDate() + 101).slice(-2);
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


    function AppendPagination(page, cPages, table)
    {
        var pagination = '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table + '-pagination-row" ><td colspan="7" style="text-align:center;">Pages : &nbsp;';
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

        row = '<tr id="workitem-' + json[0] + '" class="row-worklist-live ';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += '">';
        row += '<td><input type="checkbox" name="fee_id[]" value="' + json[1] + '" data="' + json[5] + '" class="workitem-paid" /></td>';
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
     * Appends the Page total to the bottom of table
     *
    */
    function AppendPageTotal(pageTotal) {
        row =  '<tr class="row-worklist-live rowodd">'+
                '   <td colspan="6" align="center">Page Total </td>' +
                '   <td align="center">'+ '$' + pageTotal +'</td>' +
                '</tr>';
        $('.table-worklist tbody').append(row);
    }

    function GetReport(npage) {
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
            data: 'page='+npage+'&ufilter='+$("#user-filter").val()+'&from_date='+fromDate+'&to_date='+toDate+'&paid_status='+paidStatus,
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
                AppendPageTotal(json[0][3]|0.00);
                $('.table-worklist .workitem-paid').click(function(e){
                    $('#amtpaid').show();
                    if ($(this).attr('checked')) {
                        ttlPaid = parseFloat(ttlPaid) + parseFloat($(this).attr('data'));
                        paid_list[$(this).val()] = 1;
                    } else {
                        ttlPaid = parseFloat(ttlPaid) - parseFloat($(this).attr('data'));
                        paid_list[$(this).val()] = 0;
                    }
                    $('#amtpaid').text('($'+ttlPaid+' paid)');
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

    $(document).ready(function(){
        GetReport(<?php echo $page?>);    

        $("#owner").autocomplete('getusers.php', { cacheLength: 1, max: 8 } );
        $("#user-filter").change(function(){
            $.ajax({
                type: "POST",
                url: 'update_session.php',
                data: '&ufilter='+$("#user-filter").val()
            });
            page = 1;
            if (timeoutId) clearTimeout(timeoutId);
            GetReport(page);
        });
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
                $('#amtpaid').text('($'+ttlPaid+' paid)');
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
	    if (timeoutId) clearTimeout(timeoutId);
	    GetReport(page);
	});

    });
</script> 

<title>Worklist Reports | Lend a Hand</title>

</head>

<body>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->



<div>
    <div id="search-filter-wrap">
      <table id="search-filter-section">
	  <tr>
	    <td class="report-left-label">Payee</td>
	    <td ><?php DisplayFilter('ufilter'); ?></td>
	    <td class="report-left-label">Paid Status</td>
	    <td>
	      <select id="paid-status" >
		    <option value="ALL">ALL</option>
		    <option value="1">Paid</option>
		    <option value="0" selected>Unpaid</option>
	      </select>
	    </td>
	   </tr>
	  <tr>
	      <td class="report-left-label">Fee added between</td>
	      <td colspan="2">
	      <input type="text" class="text-field-sm" id="start-date" name="start_date" tabindex="1" value="" title="Start Date" size="20" />
	      <label for="end-date"> and </label><input type="text" class="text-field-sm" id="end-date" name="end_date" tabindex="2" value="" title="End Date" size="20" /> 
	      </td>
	      <td >
		<input type="submit" value="Go" id="refreshReport"></input>
	      </td>
	  </tr>
      </table>
    </div>
    <div style="clear:both"></div>
    <form id="reportForm" method="post" action="" />
        <input type="hidden" id="paid-list" name="paidList" value="" />
        <table width="100%" class="table-worklist">
            <thead>
            <tr class="table-hdng">
                <td width="3%"><input type="checkbox" id="report-check-all" value="1" /></td>
                <td width="7%">ID</td>
                <td width="35%">Summary</td>
                <td width="25%">Description</td>
                <td width="12%">Payee</td>
                <td width="13%">Paid Date</td>
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
<?php include("footer.php"); ?>
