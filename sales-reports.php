<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com


include("config.php");
include("class.session_handler.php");
include("check_session.php");
include_once("check_new_user.php"); 
include_once("functions.php");
#include_once("../worklist/send_email.php");
#include_once("../worklist/classes/Fee.class.php");
#require_once('../worklist/lib/Agency/Worklist/Filter.php');
include("class/Report.class.php");
include("helper.php");

if ( (!empty($_SESSION['is_payer']) || !empty($_SESSION['is_runner'])) === false  ) { 
    header("Location: ".SERVER_URL);
}


$report = new Report();
$page = 0;


$ordering='';
foreach($_GET as $key=>$value){
	$$key = mysql_real_escape_string($value);
}
foreach($_POST as $key=>$value){
	$$key = mysql_real_escape_string($value);
}
if(empty($sort)) $sort = 'desc';

if ($sort == 'desc') {
	$sortimg = '<span id="direction" style="display: block; float: right"><img src="images/arrow-down.png"></span>';
} else {
	$sortimg = '<span id="direction" style="display: block; float: right"><img src="images/arrow-up.png"></span>';
}
if(!empty($_POST)){
    $report->generateCSVReport($ordering, $sort, $customer, $start_date, $end_date, $status);
    exit();
}

$list = $report->getList($page, $ordering, $sort);

include("head.html");
?>
<title>Worklist Reports | Lend a Hand</title>
<style>
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
</style>
<link rel="stylesheet" href="css/datepicker.css" type="text/css" media="screen">
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<script type="text/javascript">
var updateFeeSumsTimes = '';
</script>
</head>
<body>
    <div id="outside">

    <!-- Welcome, login/out -->

    	<div id="welcome">
    		<?php if ( isset($_SESSION['username'])) {
    			$return_from_getfeesums = true;
    			include '../worklist/getfeesums.php';
    			$feeinfo = ' | Your fees: <a href="#feesToolTip" class="feesum" id="fees-week">$'.$sum['week'].'</a> this week, <a href="#feesToolTip" class="feesum" id="fees-month">$'.$sum['month'].'</a> this month';
    			if (empty($_SESSION['nickname'])){ ?>
    				Welcome, <span id="user"><?php echo $_SESSION['username']; ?></span><?php echo $feeinfo; ?> | <a href="logout.php">Logout</a>
    			<?php }else{ ?>
    				Welcome, <span id="user"><?php echo $_SESSION['nickname']; ?></span><?php echo $feeinfo; ?> | <a href="logout.php">Logout</a>
    			<?php } ?>
    			<?php }else{?>
    				<a href="login.php">Login</a> | <a href="signup.php">Sign Up</a>
    			<?php } ?>
    		<div id="tagline">Lend a hand.</div>
    	</div>

    	<div id="container">
    		<div id="left"></div>

    <!-- MAIN BODY -->
    		<div id="center">

    <!-- LOGO -->
    			<div id="stats">
    				<span id='stats-text'>
                        <a href='javascript:ShowStats()' class='iToolTip jobsBidding' ><span id='count_b'></span> jobs</a>
                        bidding, 
                        <a href='javascript:ShowStats()' class='iToolTip jobsBidding' ><span id='count_w'></span> jobs</a>
                        underway
                    </span>
    			</div>

    <!-- Navigation placeholder -->
    		<div id="nav">
    			<?php if (isset($_SESSION['username'])) { ?>

    			<a href="worklist.php" class="iToolTip menuWorklist">Worklist</a> |
    			<a href="<?php echo SERVER_BASE ?>/journal/" class="iToolTip menuJournal">Journal</a> |
    			<a href="<?php echo SERVER_BASE ?>/love/" class="iToolTip menuLove" target="_blank">Love</a> |
    			<a href="reports.php" class="iToolTip menuReports">Reports</a> |
    			<a href="team.php">Team</a> |
    			<a href="<?php echo SERVER_BASE ?>/love/tofor.php?tab=1" class="iToolTip menuRewarder" target="_blank">Review</a> |
    			<a href="settings.php" class="iToolTip menuSettings">Settings</a>
    			<?php } ?>
    		</div><!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- --> 
            <div>
                <div id="pp-reports-box" style="float:left;">
                </div>
                <div id="pp-filter-box">
                        <form action="" method="post" accept-charset="utf-8">
                        <div id="pp-filter-rrbox">
                            <input style="margin-top:12px;" type="submit" value="Export CSV" id="refreshReport"></input>
                        </div>
                        <div id="pp-filter-rbox">
                             <div>
                           Date period
                            From <input type="text" class="text-field-sm" id="start-date" name="start_date" tabindex="1" value="<?php echo empty($start_date) ? date("m/d/Y",strtotime('-2 weeks', time())) : $start_date; ?>" title="Start Date" size="20" />
                            </div>
                            <div style="margin-top:4px;">
                            To <input type="text" class="text-field-sm" id="end-date" name="end_date" tabindex="2" value="<?php echo  empty($end_date) ? date("m/d/Y",time()) : $end_date; ?>" title="End Date" size="20" />
                             </div>
                       </div>
                        <div id="pp-filter-lbox">
                            <div style=" margin-right:15px;">
                                <h3 style="margin-bottom:3px;">Filter results based on:</h3>

                            </div>
                            <div style="float:right;">
                            Customer <?php echo $report->getCustomerSelectbox($customer); ?>
                            </div>

                            <div style="clear:both;float:right;margin-top:10px;">
                                Status
                                <select id="filter_status" name="status">
                                    <option value="ALL">ALL</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Failed">Failed</option>
                                    <option value="Reversed">Reversed</option>
                                    <option value="Unclaimed">Unclaimed</option>
                                </select>
                            </div>
                        </div>
                        </form>
                    </div>
                
                <div style="clear:both"></div>
                <div id="tab-details">
                <table border="0" cellspacing="5" cellpadding="5">
                    <tr class="table-hdng">
                        <th>
                            <a href="sales-reports.php?<?php echo encode_array($_GET, array('ordering'=>'contact_first_name', 'sort'=>($sort == 'asc' ? 'desc' : 'asc') )) ?>">
                            Customer <?php echo $ordering == 'contact_first_name' ? $sortimg : ''; ?>
                        </th>
                        <th>
                            <a href="sales-reports.php?<?php echo encode_array($_GET, array('ordering'=>'domain', 'sort'=>($sort == 'asc' ? 'desc' : 'asc') )) ?>">
                            Domain <?php echo $ordering == 'domain' ? $sortimg : ''; ?>
                        </th>
                        <th>
                            <a href="sales-reports.php?<?php echo encode_array($_GET, array('ordering'=>'created', 'sort'=>($sort == 'asc' ? 'desc' : 'asc') )) ?>">
                            Created <?php echo $ordering == 'domain' ? $sortimg : ''; ?>
                        </th>
                        <th>
                            Status
                        </th><!-- trial (days left) or subscription or suspended -->
                        <th>
                            <a href="sales-reports.php?<?php echo encode_array($_GET, array('ordering'=>'employee_count', 'sort'=>($sort == 'asc' ? 'desc' : 'asc') )) ?>">
                            # Users <?php echo $ordering == 'employee_count' ? $sortimg : ''; ?>
                        </th>
                        <th>Last payment</th><!--popup-->
                        <th>Total Paid</th>
                        <th>
                            <a href="sales-reports.php?<?php echo encode_array($_GET, array('ordering'=>'source', 'sort'=>($sort == 'asc' ? 'desc' : 'asc') )) ?>">
                            Source <?php echo $ordering == 'employee_count' ? $sortimg : ''; ?>
						</th>
						<th>
                            <a href="sales-reports.php?<?php echo encode_array($_GET, array('ordering'=>'keywords', 'sort'=>($sort == 'asc' ? 'desc' : 'asc') )) ?>">
                            Keywords <?php echo $ordering == 'employee_count' ? $sortimg : ''; ?>
                        </th>
                    </tr>
                    <?php foreach($list->customers as $row): ?>
                    <tr>
                        <td><a href="javascript:void(0)" onClick="javascript:customerDetails(<?php print $row->cid ?>)"><?php print $row->contact_first_name ?></a></td>
                        <td><a href="http://<?php print $row->domain ?>" target="_blank"><?php print $row->domain ?></a></td>
                        <td><?php print $row->created ?></td>
                        <td><?php print $row->mode ?></td>
                        <td><?php print $row->employee_count ?></td>
                        <td>
                            <a href="javascript:void(0)" onClick="javascript:lastPayment(<?php print $row->cid ?>)">
                            <?php print $row->payment_amount; if($row->payment_amount) print '$'; ?></a>
                        </td>
                        <td>
                            <?php if(!empty($row->total_amount)): ?> 
                            <a href="javascript:void(0)" onClick="javascript:paymentHistory(<?php print $row->cid ?>)"><?php print $row->total_amount ?>$</a>
                            <?php endif; ?>
                        </td>
                        <td><?php print $row->source ?></td>
                        <td><?php print $row->keywords ?></td>
                    </tr>
                    <?php endforeach ?>

                </table>
                <br>
                Pages: <?php echo pagingList($list->totalpages, $_GET) ?>
                </div>
            </div>
            
<div id="detail-box" style="display:none"></div>
<script type="text/javascript">
    $(document).ready(function() {
    	$('thead td').click(function(event) {
    		link = $(this).find("a").attr("href");
    		if(link != undefined){
    			window.location = link;
    		}
    	});
    	
    	$('#start-date').datepicker({
        			changeMonth: true,
        			changeYear: true,
        			maxDate: 0,
        			showOn: 'button',
        			dateFormat: 'mm/dd/yy',
        			buttonImage: 'images/Calendar.gif',
        			buttonImageOnly: true
        		});
        $('#end-date').datepicker({
        			changeMonth: true,
        			changeYear: true,
        			maxDate: 0,
        			showOn: 'button',
        			dateFormat: 'mm/dd/yy',
        			buttonImage: 'images/Calendar.gif',
        			buttonImageOnly: true
        		});
	});
	
	function customerDetails(cid){
	    $('#detail-box').html('');
	    $.ajax({
	        type: "GET",
	        url: 'sales-customer-details.php?cid='+cid,
	        success: function(data){
	            $('#detail-box').html(data);
	        }
	    });
	    $('#detail-box').dialog({autoOpen: false, maxWidth: 1000, width: 400, maxHeight: 1000, height: 300, show: 'fade', hide: 'fade'});
	    $('#detail-box').data('title.dialog', 'Customer Details');
	    $('#detail-box').dialog('open');
	}
	function paymentHistory(cid){
	    $('#detail-box').html('');
	    $.ajax({
	        type: "GET",
	        url: 'sales-payment-history.php?cid='+cid,
	        success: function(data){
	            $('#detail-box').html(data);
	        }
	    });
	    $('#detail-box').dialog({autoOpen: false, maxWidth: 1000, width: 600, maxHeight: 1000, height: 600, show: 'fade', hide: 'fade'});
	    $('#detail-box').data('title.dialog', 'Payment History');
	    $('#detail-box').dialog('open');
	}
	
	function lastPayment(cid){
	    $('#detail-box').html('');
	    $.ajax({
	        type: "GET",
	        url: 'sales-lastpayment.php?cid='+cid,
	        success: function(data){
	            $('#detail-box').html(data);
	        }
	    });
	    $('#detail-box').dialog({autoOpen: false, maxWidth: 1000, width: 600, maxHeight: 1000, height: 300, show: 'fade', hide: 'fade'});
	    $('#detail-box').data('title.dialog', 'Last Payment');
	    $('#detail-box').dialog('open');
	}
</script>
<?php include("../worklist/footer.php"); ?>

