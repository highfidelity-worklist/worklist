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
require_once('lib/Agency/Worklist/Filter.php');

/* This page is only accessible to runners. */
if (!empty($_SESSION['is_runner']) || !empty($_SESSION['is_payer'])) {
    $u_runner = true;
}

$filter = new Agency_Worklist_Filter();
$filter->setName('.reports')
       ->initFilter();

if (!$filter->getStart()) {
    $filter->setStart(date("m/d/Y",strtotime('-2 weeks', time())));
}

if (!$filter->getEnd()) {
    $filter->setEnd(date("m/d/Y",time()));
}

$page = $filter->getPage();

/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<link rel="stylesheet" href="css/datepicker.css" type="text/css" media="screen">

<title>PayPal Reports | Lend a Hand</title>

</head>

<body>
<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

    <div style="float:left;">
        <h1>PayPal Reports</h1>
    </div>
    <div id="pp-filter-box">
        <div id="pp-filter-rrbox">
			<div>
            Job ID # <input type="textbox" name="job_id" id="job_id"></input>
            </div>
			<div style="margin-top:5px;">
            Status
            <select id="filter_status">
                <option value="ALL">ALL</option>
                <option value="Completed">Completed</option>
                <option value="Failed">Failed</option>
                <option value="Reversed">Reversed</option>
                <option value="Unclaimed">Unclaimed</option>
            </select>
            </div>
            <input style="margin-top:12px;" type="submit" value="Refresh" id="refreshReport"></input>
        </div>
        <div id="pp-filter-rbox">
 			<div>
           Date period
	        From <input type="text" class="text-field-sm" id="start-date" name="start_date" tabindex="1" value="<?php echo date("m/d/Y",strtotime('-2 weeks', time())); ?>" title="Start Date" size="20" />
			</div>
			<div style="margin-top:4px;">
	        To <input type="text" class="text-field-sm" id="end-date" name="end_date" tabindex="2" value="<?php echo date("m/d/Y",time()); ?>" title="End Date" size="20" />
 			</div>
       </div>
        <div id="pp-filter-lbox">
            <div style=" margin-right:15px;">
                <h3 style="margin-bottom:3px;">Filter results based on:</h3>
	            
            </div>
			<div style="float:right;">
			User <?php echo $filter->getUserSelectbox(); ?>
            </div>

            <div style="clear:both;float:right;margin-top:10px;">
                Sort by <select style="" id="sort">
	                <option value="Alpha">Alphabetically</option>
	                <option value="Chrono">Chronologically</option>
	            </select>
            </div>
        </div>
    </div>
    
    <div style="clear:both;">
    <table width="100%" class="table-worklist">
        <thead>
        <tr class="table-hdng">
            <td width="35%">Summary</td>
            <td width="6%">ID</td>
            <td width="6%">Amount</td>
            <td width="12%">Date of Payment</td>
            <td width="13%">Payee</td>
            <td width="15%">Status</td>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    </div>
    
    <div style="float:left; margin-top:30px;">
        <input type="submit" value="Export report" id="exportReport"></input>
    </div>
    
<!-- --- End of content --- -->

<!-- Popup for showing stats-->
<?php require_once('dialogs/popup-pp-extended-info.inc') ?>

<script type="text/javascript">
    var filterName = ".reports";
	var _fromDate, _toDate;
	var fromDate = '';
	var toDate = '';
	var datePickerControl; // Month/Year date picker.
	var dateChangedUsingField = false; // True  if the date was changed using date field rather than picker.

	$('#popup-pp-extended-info').dialog({ autoOpen: false, modal: false, maxWidth: 770, width: 770 });
	
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
    var is_runner = <?php echo isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : '' ?>;
    var is_payer = <?php echo isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : '' ?>;

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

    // Shows the extended info dialog for the given id
    function showExtendedInfo(id) {
        // Get the info via ajax
        $.ajax({
			type: "POST",
			url: 'getpaypal.php',
			data: 'get_t_id='+id,
			dataType: 'json',
			success: function(json) {
                $('#id').text(json[0]);
                $('#fee-id').text(json[1]);
                $('#amount').text('$'+json[2]);
                $('#pay-fee').text('$'+json[3]);
                $('#pp-email').html('<a href="mailto:'+json[4]+'">'+json[4]+'</a>');
                $('#currency').text(json[5]);
                $('#mass-pay-desc').text(json[6]);
                if (json[7] != '') {
                    $('#mass-pay-run-stats-div').show();
                    $('#mass-pay-run-reason').text(json[7]);
                }
                if (json[8] != '') {
                	$('#mass-pay-run-reason-div').show();
                    $('#mass-pay-run-stats').text(json[8]);
                }
                $('#date').text(json[9]);
                $('#status').text(json[10]);
                if (json[11] != null) {
                	$('#deny-reason-div').show();
                    $('#deny-reason').text(json[11]);
                }
			},
			error: function( xhdr, status, err ) {}
		});
        
    	$('#popup-pp-extended-info').dialog('open');
    }

    function AppendPagination(page, cPages, table) {
        cspan = '6';
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

        row = '<tr id="workitem-' + json[0] + '" class="row-worklist-live ';
        if (odd) {
            row += 'rowodd';
        } else {
            row += 'roweven';
        }
        
        row += '">';
        if (is_runner != '' || is_payer != '') {
        	pre = '<a href="#">';
            post = '</a>';
            row += '<td onclick="javascript:showExtendedInfo('+parseInt(json[6])+')">' + pre + json[0] + post + '</td>'; // Summary
        } else {
        	row += '<td>' + pre + json[0] + post + '</td>'; // Summary
        }
        pre = '<a target="_blank" href="workitem.php?job_id='+json[1]+'">';
        post = '</a>';
        row += '<td>' + pre + json[1] + post + '</td>'; // Id
        pre = '', post = '';
        row += '<td>' + pre + '$' + json[2] + post + '</td>'; // Amount
        row += '<td>' + pre + formatValueForDisplay(json[3]) + post + '</td>'; // Paid Date
        row += '<td>' + pre + formatValueForDisplay(json[4]) + post + '</td>'; // Payee
        row += '<td>' + pre + json[5] + post + '</td>'; // Status
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
            cspan = '6'
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

    function GetReport(npage) {
        _fromDate = $("#start-date").datepicker('getDate');
        _toDate = $("#end-date").datepicker('getDate');
        if (_fromDate != null) {
            fromDate = fmtDate(_fromDate);
        }
        if (_toDate != null) {
            toDate = fmtDate(_toDate);
        }

        $.ajax({
            type: "POST",
            url: 'getpaypal.php',
            data: {
                page: npage,
                status: $('#filter_status').val(),
                user: $('select[name=user]').val(),
                order: $('#sort').val(),
                start: fromDate,
                end: toDate,
                job: $('#job_id').val(),
                reload: false
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
				    if (paid_list[$(this).val()]) {
					    $(this).attr('checked','checked');
				    }
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
					if (timeoutId) {
						clearTimeout(timeoutId);
					}
					
					GetReport(page);
					e.stopPropagation();
					return false;
				});
			}
		});
        timeoutId = setTimeout("GetReport("+page+", true)", refresh);
    }

    // Generate a CSV file with all the data in the report
    function exportReport() {
        _fromDate = $("#start-date").datepicker('getDate');
        _toDate = $("#end-date").datepicker('getDate');
        if (_fromDate != null) {
            fromDate = fmtDate(_fromDate);
        }
        if (_toDate != null) {
            toDate = fmtDate(_toDate);
        }
        
        var data = 'export=1&page='+<?php echo $page?>+'&status='+$('#filter_status').val()+'&user='+$('select[name=user]').val() +
                   '&order='+$('#sort').val()+'&start='+fromDate+'&end='+toDate+'&job='+$('#job_id').val()+'&reload=false';
        
        window.open('getpaypal.php?'+data, '_blank');
    }

	$(document).ready(function() {
        $('.text-field-sm').datepicker({
            changeMonth: true,
            changeYear: true,
            maxDate: 0,
            showOn: 'button',
            dateFormat: 'mm/dd/yy',
            buttonImage: 'images/Calendar.gif',
            buttonImageOnly: true
        });
        
		GetReport(<?php echo $page?>);

        $('.worklist-pagination-row a').live('click', function(e){
            page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
            if (timeoutId) clearTimeout(timeoutId);
            GetReport(page);
            e.stopPropagation();
            return false;

        });

        $('#refreshReport').click(function() {
            paid_list = [];
            if (timeoutId) clearTimeout(timeoutId);
            GetReport(page);
        });
		$('#sort, #filter_status').comboBox();

		});

	   $('#exportReport').click(function() {
	       exportReport();
	   });
</script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
<?php include("footer.php"); ?>


