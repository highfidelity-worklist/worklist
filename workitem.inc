<?php $user_id = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";
  $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
  $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;
  $flag = !$is_runner ? "disabled" : "";     //if user is a runner - allow to set funded
  $statusList = array("BIDDING","WORKING","DONE","SKIP");
  $allowEdit = false;
  if(($is_runner || $worklist['owner_id'] == $user_id)) {
      $allowEdit = true;
  }
/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<link type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<link type="text/css" href="css/workitem.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="js/datepicker.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>

<script>
  $(document).ready(function(){
	$('#done_by').datepicker({  
	  duration: '',  
	  showTime: true,  
	  constrainInput: false,  
	  stepMinutes: 1,  
	  stepHours: 1,  
	  altTimeField: '',  
	  time24h: false  
	});

	$('#popup-bid').dialog({ autoOpen: false, maxWidth: 600, width: 450 });
	$('#popup-bid-info').dialog({ autoOpen: false, modal: true});
	$('#popup-addfee').dialog({ autoOpen: false, modal: true, width: 400});
	$('#popup-paid').dialog({ autoOpen: false, maxWidth: 600, width: 450 });
	$('#message').dialog({ autoOpen: true});

	// JS Variables initialized from host PHP page
	var workitem_id = <?php echo $worklist['id'];?> ;
	var already_bid = <?php echo $currentUserHasBid ;?> ;
	var is_runner = <?php echo $is_runner;?> ;

	SimplePopup('#popup-bid',
		    'Place Bid',
		    workitem_id,
		    [['input', 'itemid', 'keyId', 'eval']]);


        $('.popup-body form input[type="submit"]').click(function(){
	  var name = $(this).attr('name');
//	  $(".popup-page-value").val(page);
	  switch(name){
	      case "add_fee_dialog": 
	      SimplePopup('#popup-addfee',
			      'Add Fee',
			      workitem_id,
			      [['input', 'itemid', 'keyId', 'eval']]);
	      $('#popup-addfee').dialog('open');
	      return false;
	      case "reset":
	      ResetPopup();
	      return false;
	      case "cancel":
	      $('#popup-delete').dialog('close');
	      $('#popup-edit').dialog('close');
	      $('#popup-paid').dialog('close');
	      return false;
	     }
	});
<?php if (isset($_SESSION['userid'])) {?>

	$('.paid-link').click(function(e){

	    var fee_id = $(this).attr('id').substr(8);

	    AjaxPopup('#popup-paid',
		      'Pay Fee',
		      'getfeeitem.php',
		      fee_id,
		      [ ['input', 'itemid', 'keyId', 'eval'],
			['textarea', 'paid_notes', 'json[2]', 'eval'],
			['checkbox', 'paid_check', 'json[1]', 'eval'] ]);
	    
		    $('.paidnotice').empty();
		    $('#popup-paid').dialog('open');
		    
		    // onSubmit event handler for the form
		    $('#popup-paid > form').submit(function() {
			    // now we save the payment via ajax		
			    $.ajax({
				    url: 'paycheck.php',
				    dataType: 'json',
				    data: {
					    itemid: $('#' + this.id + ' input[name=itemid]').val(),
					    paid_check: $('#' + this.id + ' input[name=paid_check]').val(),
					    paid_notes: $('#' + this.id + ' textarea[name=paid_notes]').val()
				    },
				    success: function(data) {
					    // We need to empty the notice field before we refill it
					    if (!data.success) {
						    // Failure message
						    var html = '<div style="padding: 0 0.7em; margin: 0.7em 0;" class="ui-state-error ui-corner-all">' +
										    '<p><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
										    '<strong>Alert:</strong> ' + data.message + '</p>' +
									    '</div>';
						    $('.paidnotice').append(html);
						    // Fire the failure event
						    $('#popup-paid > form').trigger('failure');
					    } else {
						    // Success message
						    var html = '<div style="padding: 0 0.7em; margin: 0.7em 0;" class="ui-state-highlight ui-corner-all">' +
										    '<p><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>' +
										    '<strong>Info:</strong> ' + data.message + '</p>' +
									    '</div>';
						    $('.paidnotice').append(html);
						    // Fire the success event
						    $('#popup-paid > form').trigger('success');
					    }
				    }
			    });
			    
			    return false;
		    });

		    // Here we need to capture the event and fire a new one to the upper container
		    $('#popup-paid > form').bind('success', function(e, d) {
			    $('.table-feelist tbody').empty();
			    //TODO Make this use a refresh when this page supports AJAX data refresh in future
			    location.reload();
		    });
	    
	    return false;
	});


	$('.wd-link').click(function(e) {
	    var fee_id = $(this).attr('id').substr(3);
	    $('#withdraw .fee_id').val(fee_id);
	    $('#withdraw').submit();
	});
	  $('tr.row-bidlist-live').click(function(){
	    var match = $(this).attr('class').match(/biditem-\d+/);
	    var bid_id = match[0].substr(8);
	    $('#popup-bid-info form input[type="submit"]').remove();

	    AjaxPopup('#popup-bid-info',
		      'Bid Info',
		      'getbiditem.php',
		      bid_id,
		      [ ['input', 'bid_id', 'keyId', 'eval'],
			['input', 'info-email2', 'json.email', 'eval'],
			['span', '#info-email', 'json.email', 'eval'],
			['span', '#info-bid-amount', 'json.bid_amount', 'eval'],
			['span', '#info-bid-done-by', 'json.done_by', 'eval'],
			['span', '#info-notes', 'json.notes', 'eval'] ],
		      function(json) {
			if( is_runner==1) 
			  $('#popup-bid-info form').append('<input type="submit" name="accept_bid" value="Accept">');
			if( is_runner==1 || (json.bidder_id == "<?php echo (isset($_SESSION['userid'])) ? $_SESSION['userid'] : ''; ?>"))
			      $('#popup-bid-info form').append('<input type="submit" name="withdraw_bid" value="Withdraw" style="float:right;">');				
		      });

	    $('#popup-bid-info').dialog('open');
	  });
<?php } ?>
      
	  if(already_bid){
	    $('#bid').click(function(e){  
		if (!confirm("You have already placed a bid, do you want to place a new one?"))
	      {
		  $('#popup-bid').dialog('close');
		  return false;
	      } 

	    });
	  }


  });

    function ResetPopup() {
	$('#for_edit').show();
	$('#for_view').hide();
        $('.popup-body form input[type="text"]').val('');
	$('.popup-body form input[name="owner"]').val('<?php echo (isset($_SESSION['nickname'])) ? $_SESSION['nickname'] : ''; ?>');
        $('.popup-body form select option[index=0]').attr('selected', 'selected');
        $('.popup-body form textarea').val('');
    }

  function showPlaceBidForm() {
      $('#popup-bid').dialog('open');
      return false;
  }

  function showFeeForm() {
	 $('#popup-addfee').dialog('open');
	 return false;
  }

  function saveWorkitem() {
      var summary = $('#summary').val();
      var funded = $('#funded').val();
      var status = $('#status').val();
      var notes = $('#edit-notes').val();
      $('#workitem-form').submit();

    return false;
  }


</script>
<title>Workitem | Lend a Hand</title>

</head>
<body>
<?php include("format.php"); ?>

    <div id="popup-buttons">
	<form method="get" action=""> 
	 <a class="workitem-back" title="Back to Worklist" href="worklist.php">Back to Worklist</a>
<?php if($user_id): ?>
      <?php if ($worklist['status'] == 'BIDDING'): ?>  
	<input type="submit" value="Add Bid" onclick="return showPlaceBidForm();"/>
      <?php endif; ?>
      <input type="submit" value="Add Fee" onclick="return showFeeForm();"/>
      <?php if(($allowEdit && $action!="edit")): ?>  
	  <input type="hidden" name="job_id" value="<?php echo $worklist_id;?>" />
	  <input type="hidden" name="action" value="edit" />
	  <input type="submit" value="Edit" id="edit_workitem"/>
      <?php endif;?>
<?php endif;?>
	</form> 
    <div style="clear:both; float:none;"> </div>
    </div>
<div id="page-content">
  <div id="left-panel">
    <div id="for_view">

      <?php if ($action =="edit"):  ?>
	<form id="workitem-form" method="post" action=""> 
	  <input type="hidden" name="save_workitem" value="save_workitem" />
	  <input type="hidden" name="action" value="save_workitem" />
	  <ul>
	    <li>
	      <span class="info-label">Summary</span>
	      <?php if($is_runner || $worklist['owner_id'] == $user_id): ?>
		<span>
		<input type="text" size="33" class="text-field" id="summary" name="summary" value="<?php echo $worklist['summary'];?>"/><span>
	      <?php else:?>
	      <span><?php echo $worklist['summary'];?></span>
	      <?php endif;?>
	    </li>
	    <li>
	    <span class="info-label">Status</span>
	      <?php if ($is_runner):  ?>
		<select id="status" name="status">
		<?php foreach($statusList as $status): ?>
		    <option value="<?php echo $status; ?>" <?=$status==$worklist['status']?' selected = "selected"' : '';?> ><?php echo $status; ?></option>
		<?php endforeach;?>
		</select>
		<?php else: ?>
		    <?php echo $worklist['status'];?>  
		    <input type="hidden" id="status" name="status" value = "<?php $worklist['status']; ?>" />    
		<?php endif;?>
	      </li>
		<li>
		<span class="info-label">Funded</span>
		  <input type="checkbox" name="funded" id="funded" <?=$worklist['funded']==1?" checked " : "";?>  <?php echo $flag; ?>/>
		</li>
	      </li>
		<li>
		<span class="info-label">Owner</span>
		<?php echo $worklist['nickname'];?>
		</li>
	    <li>
	    <span class="info-label">Notes</span>
	      <?php if($is_runner || $worklist['owner_id'] == $user_id): ?>
		<span><textarea size="33" name="notes" rows="25" cols="20" id="edit-notes"><?php echo $worklist['notes'];?></textarea><span>
	      <?php else:?>
	      <span id="info-notes"><?php echo nl2br($worklist['notes']);?></span>
	      <?php endif;?>
	    </li>
	  </ul>
      <?php if($allowEdit && $action=="edit"): ?>  
	  <input type="submit" value="Save" name="save_workitem" id="save_workitem" onclick="return saveWorkitem();"/>
      <?php endif;?>

	</form> 
	<?php else: ?>
	  <ul>
	    <li>
	      <span class="info-label">Summary</span>
	      <span><?php echo $worklist['summary'];?></span>
	    </li>
	    <li>
		  <span class="info-label">Status</span>
		  <?php echo $worklist['status'];?>  
		  <input type="hidden" id="status" name="status" value = "BIDDING" />    
	   </li>
		<li>
		<span class="info-label">Funded</span>
		  <?=$worklist['funded']==1?" Yes " : "No";?>
		</li>
	      </li>
		<li>
		<span class="info-label">Owner</span>
		<?php echo $worklist['nickname'];?>
		</li>
	    <li>
	    <span class="info-label">Notes</span>
	      <span id="info-notes"><?php echo nl2br($worklist['notes']);?></span>
	    </li>
	  </ul>
      <?php endif;?>
    </div>
  </div>
  <div id="right-panel">
	<table width="100%" class="table-bids"><caption class="table-caption" ><b>Bids</b></caption>
	  <thead>
		<tr class="table-hdng">
		  <td>Who</td>
		  <td>Bid Amount</td>
		  <td>Done In</td>
		</tr>
	  </thead>  
	  <tbody>  
	    <?php if(empty($bids)): ?>
	    <tr>
		<td style="text-align: center;" colspan="4">No bids yet.</td>
	    </tr>
	    <?php else:  ?>
		<?php 
		$row = 1;
		foreach($bids as $bid): ?>
		<tr class="<?php ($user_id) ? print 'row-bidlist-live' :'' ; ?> <?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?> biditem-<?php echo $bid['id'];?>">
		    <td><?php echo $bid['nickname'];?></td>
		    <td><?php echo $bid['bid_amount'];?></td>
		    <td><?php echo relativeTime($bid['future_delta']);?></td>
		</tr>
		<?php endforeach;?>
	    <?php endif;?>
	  </tbody>
	</table></br>
	<div id="bid-panel" >
    <table width="100%" class="table-bids"><caption class="table-caption" ><b>Fees</b></caption>
        <thead>
        <tr class="table-hdng">
            <td>Who</td>
            <td>Amount</td>
            <td>Description</td>
            <td>Date</td>
            <td>Paid</td>
        </tr>
	</thead>
	<tbody>  
	    <?php if(empty($fees)): ?>
	    <tr>
		<td style="text-align: center;" colspan="5">No fees yet.</td>
	    </tr>
	    <?php else: ?>
		<?php
		 $row = 1;
		 foreach($fees as $fee): ?>
		<tr class="<?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?> ">
		    <td><?php echo $fee['nickname'];?></td>
		    <td><?php echo $fee['amount'];?></td>
		    <td><?php echo $fee['desc'];?></td>
		    <td><?php echo $fee['date'];?></td>
		    <td>
		      <?php if($is_payer): ?>
			<a href="#" class = "paid-link" id = "feeitem-<?php echo $fee['id'];?>"><?php echo $fee['paid'] == 0 ? "No" : "Yes";?></a>
		      <?php else: ?>
			<?php echo $fee['paid'] == 0 ? "No" : "Yes";?>
		      <?php endif;?>
		      <?php if($user_id): ?>
		       - <a href="#" id="wd-<?php echo $fee['id'];?>" class="wd-link">WD</a>
		      <?php endif;?>
 		    </td>
		</tr>
		<?php endforeach;?>
		<tr><td colspan="5" style="text-align:center">Total Fees $<?php echo $total_fee;?></td></tr>
	    <?php endif;?>
	</tbody>
    </table>
	<form id="withdraw" method="post" action="" >
	    <input type="hidden" name="action" value="withdraw_bid" />
	    <input type="hidden" class="fee_id" name="fee_id" value="" />
	</form>
    </div><!--end of right-panel-->
</div>
<div style="clear:both"></div>
</div>
<!-- Popup HTML for Placing a bid -->
<?php if($message): ?>
<div id="message" style="display:none; padding: 0 0.7em; margin: 0.7em 0;" class="ui-state-highlight ui-corner-all">
    <p><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>
    <strong>Info:</strong><?php echo $message; ?></p>
</div>
<?php endif;?>
<?php include("popup-place-bid.inc"); ?>
<!-- Popup HTML for adding a fee -->
<?php include("popup-addfee.inc"); ?>
<!-- Popup HTML for paying a fee -->
<?php require_once('popup-paid-html.inc') ?>

<!-- Popup for bid info-->
<?php require_once('popup-bid-info.inc') ?>

<?php include("footer.php"); ?>
