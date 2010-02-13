<?php 
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

ob_start(); 

include("config.php");
include("class.session_handler.php");
include_once("functions.php");
include_once("send_email.php");

/* This page is only accessible to runners. */
if (empty($_SESSION['userid']) || empty($_SESSION['is_runner'])) {
    header("location:worklist.php");
    return;
}

if(!isset($_SESSION['ufilter'])) {
  $_SESSION['ufilter'] = 'ALL';
}

$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

if(isset($_POST['paid']) && !empty($_POST['paidList']) && !empty($_SESSION['is_payer'])) {
    foreach (explode(',', trim($_POST['paidList'], ',')) as $itemid) {
        $itemid = intval($itemid);
        $query = "update `".FEES."` set `user_paid`={$_SESSION['userid']}, `paid`=1 WHERE `worklist_id`={$itemid}";
        $rt = mysql_query($query);
    }
}

//list of users for filtering
$userid = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";


/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<link type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript">
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
        row += '<td><input type="checkbox" name="itemid[]" value="' + json[0] + '" data="' + json[5] + '" class="workitem-paid" /></td>';
        row += '<td>' + pre + json[0] + post + '</td>'; // Id
        row += '<td>' + pre + json[1] + post + '</td>'; // Summary
        row += '<td>' + pre + json[2] + post + '</td>'; // Description
        row += '<td>' + pre + json[6] + post + '</td>'; // Category
        row += '<td>' + pre + json[3] + post + '</td>'; // Status
        row += '<td>' + pre + json[4] + post + '</td>'; // Payee
        row += '<td>' + pre + '$' + json[5] + post + '</td>'; // Amount
        row += '</tr>';

        $('.table-worklist tbody').append(row);
    }

    function GetReport(npage) {
        $.ajax({
            type: "POST",
            url: 'getreport.php',
            data: 'page='+npage+'&ufilter='+$("#user-filter").val(),
            dataType: 'json',
            success: function(json) {
                $("#loader_img").css("display","none");
                page = json[0][1]|0;
                var cPages = json[0][2]|0;

                $('.row-worklist-live').remove();
                workitems = json;
                if (!json[0][0]) return;

                /* Output the worklist rows. */
                var odd = true;
                for (var i = 1; i < json.length; i++) {
                    AppendRow(json[i], odd);
                    odd = !odd;
                }
                AppendPagination(page, cPages, 'worklist');

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
                    '   <td colspan="5" align="center">Oops! We couldn\'t find any work items.  <a id="again" href="#">Please try again.</a></td>' +
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

    });
</script> 

<title>Worklist Reports | Lend a Hand</title>

</head>

<body>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

    <div id="search-filter-wrap">
         <div style="float:right" >
            <?php DisplayFilter('ufilter'); ?>
        </div>
    </div>    

    <div style="clear:both"></div>

    <form id="reportForm" method="post" action="" />
        <input type="hidden" id="paid-list" name="paidList" value="" />
        <table width="100%" class="table-worklist">
            <thead>
            <tr class="table-hdng">
                <td width="3%"><input type="checkbox" id="report-check-all" value="1" /></td>
                <td width="4%">ID</td>
                <td width="38%">Summary</td>
                <td width="25%">Description</td>
                <td width="10%">Category</td>
                <td width="7%">Status</td>
                <td width="8%">Payee</td>
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
      
<?php include("footer.php"); ?>
