<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

    $user_id = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";
    $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
    $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;
    $statusList = array("SUGGESTED", "BIDDING","WORKING","REVIEW","DONE","SKIP");

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
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.min.js"></script>
<script type="text/html" id="uploadedFiles">
<div id="accordion">
	<h3><a href="#">Images (<span id="imageCount"><#= images.length #></span>)</a></h3>
	<div id="fileimagecontainer">
	<# if (images.length > 0) { #>
		<# for(var i=0; i < images.length; i++) {
		var image = images[i];
		#>
		<div class="filesIcon">
			<a href="<#= image.url #>"><img width="75px" height="75px" src="<#= image.icon #>" /></a>
		</div>
		<div class="filesDescription">
			<h3 class="edittext" id="fileTitle_<#= image.fileid #>"><#= image.title #></h3>
			<p class="edittextarea" id="fileDesc_<#= image.fileid #>"><#= image.description #></p>
		</div>
		<div class="clear"></div>
		<# } #>
	<# } #>
	</div>
	<h3><a href="#">Documents (<span id="documentCount"><#= documents.length #></span>)</a></h3>
	<div id="filedocumentcontainer">
	<# if (documents.length > 0) { #>
		<# for(var i=0; i < documents.length; i++) {
		var doc = documents[i];
		#>
		<div class="filesIcon">
			<a href="<#= doc.url #>" target="_blank"><img width="32px" height="32px" src="<#= doc.icon #>" /></a>
		</div>
		<div class="documents filesDescription">
			<h3 class="edittext" id="fileTitle_<#= doc.fileid #>"><#= doc.title #></h3>
			<p class="edittextarea" id="fileDesc_<#= doc.fileid #>"><#= doc.description #></p>
		</div>
		<div class="clear"></div>
		<# } #>
	<# } #>
	</div>
</div>
<div id="fileUploadButton">
	Attach new files
</div>
</script>
<script type="text/html" id="uploadImage">
	<div class="filesIcon">
		<a href="<#= url #>"><img width="75px" height="75px" src="<#= icon #>" /></a>
	</div>
	<div class="filesDescription">
		<h3 class="edittext" id="fileTitle_<#= fileid #>"><#= title #></h3>
		<p class="edittextarea" id="fileDesc_<#= fileid #>"><#= description #></p>
	</div>
	<div class="clear"></div>
</script>
<script type="text/html" id="uploadDocument">
	<div class="filesIcon">
		<a href="<#= url #>" target="_blank"><img width="32px" height="32px" src="<#= icon #>" /></a>
	</div>
	<div class="documents filesDescription">
		<h3 class="edittext" id="fileTitle_<#= fileid #>"><#= title #></h3>
		<p class="edittextarea" id="fileDesc_<#= fileid #>"><#= description #></p>
	</div>
	<div class="clear"></div>
</script>

<script type="text/javascript">
var user_id = <?php echo($user_id); ?>;
var workitem_id = <?php echo $worklist['id'];?> ;
var imageArray = new Array();
var documentsArray = new Array();

function reply(id) {
	var commentForm = $('.commentform');
	var clone = commentForm.clone();
	commentForm.remove();
	clone.insertAfter($('#comment-' + id));
	$('.commentform input[name=comment_id]').val(id);
}

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

	$('#popup-bid').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 450 });
	$('#popup-confirmation').dialog({ autoOpen: false, modal: true, maxWidth: 600, width: 450 });
	
	// If the bid info popup is set to modal clipboard won't work!
	$('#popup-bid-info').dialog({ autoOpen: false, modal: false});
	$('#popup-addfee').dialog({ autoOpen: false, modal: true, width: 400});
	$('#popup-paid').dialog({ autoOpen: false, maxWidth: 600, width: 450 });
	$('#message').dialog({ autoOpen: true});
	$('#popup-pingtask').dialog({ autoOpen: false, width: 400});


	// JS Variables initialized from host PHP page
	var workitem_id = <?php echo $worklist['id'];?> ;
	var already_bid = <?php echo $currentUserHasBid ;?> ;
	var is_runner = <?php echo $is_runner;?> ;
	var user_id = <?php echo($user_id); ?>;

	(function($) {
		$.ajax({
			type: 'post',
			url: 'jsonserver.php',
			data: {
				workitem: workitem_id,
				userid: user_id,
				action: 'getFilesForWorkitem'
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
					var files = $('#uploadedFiles').parseTemplate(data.data);
					files = files + '<script type="text/javascript" src="js/uploadFiles.js"><\/script>';
					$('#uploadPanel').append(files);
					$('#accordion').accordion({
						clearStyle: true,
						collapsible: true
					});
				}
			}
		});
	})(jQuery);

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
<?php if ($is_runner || $worklist['owner_id'] == $_SESSION['userid']):?>
			  $('#popup-bid-info form').append('<input type="submit" name="accept_bid" value="Accept">');
<?php endif;?>
			if( is_runner==1 || (json.bidder_id == "<?php echo (isset($_SESSION['userid'])) ? $_SESSION['userid'] : ''; ?>"))
			      $('#popup-bid-info form').append('<input type="submit" name="withdraw_bid" value="Withdraw" style="float:right;">');
		      });

	    $('#popup-bid-info').dialog('open');
	  });
<?php } ?>

        $('#bid').click(function(e){
            if (   already_bid
                && $(this).parent().find('#mechanic_id').val() == '<?php echo $_SESSION['userid'] ?>'
                && !confirm("You have already placed a bid, do you want to place a new one?")) {
                $('#popup-bid').dialog('close');
                return false;
            }
        });

  });

    function ResetPopup() {
		$('#for_edit').show();
		$('#for_view').hide();
		$('.popup-body form input[type="text"]').val('');
		$('.popup-body form input[name="owner"]').val('<?php echo (isset($_SESSION['nickname'])) ? $_SESSION['nickname'] : ''; ?>');
		$('.popup-body form select option[index=0]').attr('selected', 'selected');
		$('.popup-body form textarea').val('');
    }

  function showConfirmForm(i) {
      $('#popup-confirmation-type').val(i);
      $('#popup-confirmation').dialog('open');
      return false;
  }
  function doConfirmForm(i) {
      if (i == 'bid') {
      	  $('#popup-confirmation').dialog('close');
      	  showPlaceBidForm();
      } else if (i == 'fee') {
      	  $('#popup-confirmation').dialog('close');
	  	  showFeeForm();
	  }
      return false;
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
      var status = $('#status').val();
      var notes = $('#edit-notes').val();
      $('#workitem-form').submit();

    return false;
  }

	$(function()	{
		// Call via Ajax to ping the user in the journal
		// and in email.
		$('#send-ping-btn').click(function()	{
			var msg = $('#ping-msg').val();
			var mail = 0;
			if( $('#send-mail:checked').val() )
				mail = 1;
			
			$.ajax({
				type: "POST",
				url: 'pingtask.php',
				data: 'id=' + <?php echo $worklist_id; ?> + '&msg=' + msg + '&mail=' + mail,
				dataType: 'json',
				success: function() {}
			});
			$('#popup-pingtask').dialog('close');
			return false;
			
		});

		$('#ping-btn').click(function()	{
			$('#popup-pingtask').dialog('open');
			return false;
		});
		return false;
	});
  
</script>
<title>Workitem | Lend a Hand</title>

</head>
<body>
<?php include("format.php"); ?>

    <div id="popup-buttons">
	<?php
		if ($erroneous) {
			echo "	<div style='color: red; text-align: center;'>{$the_errors}</div>";
		}
	?>
	<form method="get" action="">

	 <div><a class="workitem-back" title="Back to Worklist" href="worklist.php">Back to Worklist</a></div>
<?php if($user_id): ?>
      <?php if ($worklist['status'] == 'BIDDING'): ?>
	<input type="submit" value="Add Bid" onclick="return showConfirmForm('bid');"/>
      <?php endif; ?>
      <input type="submit" value="Add Fee" onclick="return showConfirmForm('fee');"/>
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
          <div style="float:left;width:50%">
	        <span class="info-label">ID</span>
	        <span><?php echo $worklist['id'];?></span>
          </div>
          <div style="float:right;margin-top:4px;width:50%">
            <script type="text/javascript" src="http://w.sharethis.com/button/sharethis.js#publisher=4867cd3b-6b43-4c9b-b7a7-57966f818e05&amp;type=website&amp;style=horizontal"></script>
          </div>
          <div style="clear:both"></div>
	    </li>
	    <li>
	      <span class="info-label">Summary</span>
	      <?php if($is_runner || $worklist['owner_id'] == $user_id){ ?>
		<span>
		<input type="text" size="33" class="text-field" id="summary" name="summary" value="<?php echo htmlentities($worklist['summary']);?>"/><span>
	      <?php }else{?>
	      <span><?php echo $worklist['summary'];?></span>
	      <?php } ?>
	    </li>
	    <li>
	    <span class="info-label">Status</span>
	      <?php if ($is_runner){  ?>
		<select id="status" name="status">
		<?php foreach($statusList as $status){ ?>
		    <option value="<?php echo $status; ?>" <?php echo $status == $worklist['status']?' selected = "selected"' : ''; ?> ><?php echo $status; ?></option>
		<?php }?>
		</select>
		<?php }elseif ($worklist['owner_id'] == $user_id && array_key_exists($worklist['status'], $statusMapMechanic)){ ?>
                  <select id="status" name="status">
		    <option value="<?php echo $worklist['status']; ?>" selected="selected"><?php echo $worklist['status']; ?></option>
		    <?php foreach($statusMapMechanic[$worklist['status']] as $status){ ?>
		    <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
		    <?php } ?>
                  </select>

		<?php }else{ ?>
		    <?php echo $worklist['status'];?>
		    <input type="hidden" id="status" name="status" value = "<?php $worklist['status']; ?>" />
		<?php } ?>
	      </li>
              <li>
                <span class="info-label">Owner</span>
                <?php echo $worklist['nickname'];?>
				<form method="post" action="" style="display:inline;">
					<input type="submit" id="ping-btn" style="margin-top:4px; float:right;" value="Ping mechanic"></input>
				</form>
              </li>
          <li>
              <span class="info-label">Invite</span>
              <input type="text" name="invite" class="invite" />
              <script type="text/javascript">
              $(document).ready(function() {
                $('.invite').autocomplete('getusers.php', {
                    multiple: true,
                    multipleSeparator: ', ',
                    extraParams: { nnonly: 1 }
                });
              });
              </script>
              <small>(Comma-separated list of developers.)</small>
	    </li>
	    <li class="last">
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
          <div style="float:left;width:50%">
	        <span class="info-label">ID</span>
	        <span><?php echo $worklist['id'];?></span>
          </div>
          <div style="float:right;margin-top:4px;width:50%">
            <script type="text/javascript" src="http://w.sharethis.com/button/sharethis.js#publisher=4867cd3b-6b43-4c9b-b7a7-57966f818e05&amp;type=website&amp;style=horizontal"></script>
          </div>
          <div style="clear:both"></div>
	    </li>
	    <li>
	      <span class="info-label">Summary</span>
	      <span><?php echo $worklist['summary'];?></span>
	    </li>
	    <li>
              <span class="info-label">Status</span>
              <?php echo $worklist['status'];?>
              <input type="hidden" id="status" name="status" value = "BIDDING" />
	      <?php 
		    // quick status buttons for creators of items and mechanics working on them
		    if(!$is_runner && ($worklist['owner_id'] == $user_id || $worklist['mechanic_id'] == $user_id) && array_key_exists($worklist['status'], $statusMapMechanic)){

			      foreach($statusMapMechanic[$worklist['status']] as $status){ ?>

					  <form method="post" action="" style = "display:inline;">
						<input type="submit" id="status-switch" name="status-switch" value="Set to <?php echo $status; ?>" style="margin-bottom: 5px; float: right;" />
						<input type="hidden" name="quick-status" value="<?php echo $status; ?>" />
					  </form>

			<?php } //2 buttons for runners to quickly adjust status
		    } else if($is_runner && array_key_exists($worklist['status'], $statusMapRunner))	{

			      foreach($statusMapRunner[$worklist['status']] as $status){?>

				<form method="post" action="" style = "display:inline;">
					<input type="submit" id="status-switch" name="status-switch" value="Set to <?php echo $status; ?>" style="margin-bottom: 5px; float: right;" />
					<input type="hidden" name="quick-status" value="<?php echo $status; ?>" />
				</form>

			<?php }
		    } ?>
	    </li>
            <li>
				<span class="info-label">Owner</span>
				<?php echo $worklist['nickname'];?>
				<form method="post" action="" style="display:inline;">
					<input type="submit" id="ping-btn" style="margin-top:3px; float:right;" value="Ping mechanic"></input>
				</form>
            </li>
            <li class="last">
              <span class="info-label">Notes</span>
              <span><textarea size="33" name="notes" rows="25" cols="20" readonly="readonly" id="edit-notes"><?php echo $worklist['notes'];?></textarea><span>
            </li>
	  </ul>
      <?php endif;?>
      <ul>
      	<li class="comments">
      		<span class="info-label">Comments</span>
	      	<ul>
	      	<?php 
	      		$comments = Comment::findCommentsForWorkitem($worklist['id']);
	      		if (empty($comments)) :
	      	?>
	      		<li>No comments!</li>
	      	<?php
	      		else :
	      			foreach ($comments as $comment) :
	      	?>
	      		<li id="comment-<?php echo $comment['id']; ?>" class="comment depth-<?php echo $comment['depth']; ?>">
	      			<div class="comment-info">
	      				<span class="author"><?php echo $comment['comment']->getUser()->getNickname(); ?> says:</span>
						<span class="date"><?php echo date('F j, Y, g:i a', strtotime($comment['comment']->getDate()))?> <?php if (!empty($user_id)) : ?>(<a href="#commentform" onclick="reply(<?php echo $comment['id']; ?>);return false;">reply</a>)<?php endif; ?></span>
	      			</div>
	      			<div class="comment">
						<?php echo $comment['comment']->getComment(); ?>
					</div>
	      		</li>
	        <?php 
	        		endforeach;
	        	endif;
	        ?>
	      	</ul>
	  	</li>
	  	<?php if (!empty($user_id)) : ?>
      	<li class="commentform">
      		<a name="commentform"></a>
      		<span class="info-label">Write a comment</span>
      		<form action="" method="post">
      			<input type="hidden" name="worklist_id" value="<?php echo $worklist['id']; ?>" />
      			<input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
      			<input type="hidden" name="comment_id" value="" />
      			<textarea name="comment" cols="20" rows="10" size="33"></textarea>
      			<input type="submit" name="newcomment" value="Post Comment" />
      		</form>
      	</li>
      	<?php endif; ?>
      </ul>
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
		    <td<?php if ($bid['future_delta'] < 0) {echo ' class="warn"'; }?>><?php echo relativeTime($bid['future_delta']);?></td>
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
    <div id="uploadPanel">

    </div>
</div>
<div style="clear:both"></div>
</div>
<!-- Popup HTML for Placing a bid -->
<?php if(isset($message)): ?>
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
<!-- Popup for ping user on task-->
<?php require_once('popup-pingtask.inc') ?>

<!-- Popup for bid info-->
<?php require_once('popup-bid-info.inc') ?>

<!-- Popup for confirmation info-->
<?php require_once('popup-confirmation.inc') ?>

<?php include("footer.php"); ?>
