<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

    $user_id = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";
    $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
    $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;
    $statusList = array("SUGGESTED", "BIDDING","WORKING","REVIEW","DONE","SKIP");

    $creator_id = isset($worklist['creator_id']) ? $worklist['creator_id'] : 0;
    $mechanic_id = isset($worklist['mechanic_id']) ? $worklist['mechanic_id'] : 0;

    $has_budget = 0;
    if (!empty($user_id)) {
        $user = new User();
        $user->findUserById($user_id);
        if ($user->getBudget() > 0) {
            $has_budget = 1;
        }
    }

    $allowEdit = false;
    if ($is_runner || $creator_id == $user_id || $has_budget) {
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
<script type="text/javascript" src="js/jquery.tallest.js"></script>
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
	clone.css({'margin-left':(function(){return $('#comment-' + id).css('margin-left');})()});
	
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
	var workitem_id = <?php echo $worklist['id'];?>;
	var already_bid = <?php echo $currentUserHasBid ;?>;
	var is_runner = <?php echo $is_runner;?>;
    var has_budget = <?php echo $has_budget;?>;
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
					    paid_check: $('#' + this.id + ' input[name=paid_check]').attr('checked') ? 1 : 0,
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
<?php if ($is_runner || $user_id == $runner_id) { ?>
			  $('#popup-bid-info form').append('<input type="submit" name="accept_bid" value="Accept">');
<?php } ?>
			if( is_runner==1 || (json.bidder_id == "<?php echo $user_id ?>"))
			      $('#popup-bid-info form').append('<input type="submit" name="withdraw_bid" value="Withdraw" style="float:right;">');
		      });

	    $('#popup-bid-info').dialog('open');
	  });
<?php } ?>

        $('#bid').click(function(e){
            if (   already_bid
                && $(this).parent().find('#mechanic_id').val() == '<?php echo $user_id ?>'
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
			   
		$('#commentZone').css({'opacity':'0'});
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
		
		// applies the same size as the thinnest to all comments the show'em
		var thinnestComment = $('div.commentZone li').thinnestSize() - 14;
		$('div.commentZone li').width(thinnestComment);
		$('div.commentform textarea').width(thinnestComment);
		$('#commentZone').css({'opacity':'1'});
		
		
		return false;
	});
  
</script>
<title>Workitem | Lend a Hand</title>
</head>
<body>
	<?php include("format.php"); ?>
	<?php if ($erroneous) echo "<div style='color: red; text-align: center;'>{$the_errors}</div>"; ?>
		<div><a class="workitem-back" title="Back to Worklist" href="worklist.php">Back to Worklist</a>
		<?php if(($allowEdit && $action!="edit")): ?>
			&nbsp;|&nbsp;&nbsp;<a class="workitem-back" title="Switch to Edit Mode" href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?job_id=<?php echo $worklist['id'];?>&action=edit">Switch to Edit Mode</a>
		<?php endif;?>
		<?php if(($action=="edit")): ?>
			&nbsp;|&nbsp;&nbsp;<a class="workitem-back" title="Switch to View Mode" href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?job_id=<?php echo $worklist['id'];?>&action=view">Switch to View Mode</a>
		<?php endif;?>
		<div style="clear:both; float:none;"> </div>
		</div>
		<div id="page-content">
			<div style="margin:0px 101px 6px 0px;line-height:50px;float:right;display:inline-block;">
				<script type="text/javascript" src="http://w.sharethis.com/button/sharethis.js#publisher=4867cd3b-6b43-4c9b-b7a7-57966f818e05&amp;type=website&amp;style=horizontal"></script>
			</div>
			<div class="info-summ-big">
				<div class="info-ID">
					<span class="info-label">#</span> <?php echo $worklist['id'];?>
				</div>
				<span class="info-summ-big-text"><?php echo $worklist['summary'];?></span>
			</div>
			<div style="clear:both; width:100%; float:none;"> </div>
			<div id="left-panel">
				<div id="for_view">
<?php $runner_nickname = $worklist['runner_nickname'] != '' ? $worklist['runner_nickname'] : 'Not funded'; 

if ($action =="edit"):  ?>
<!-- *** Action is Edit *** -->
				<form id="workitem-form" method="post" action="">
					<input type="hidden" name="save_workitem" value="save_workitem" />
					<input type="hidden" name="action" value="save_workitem" />
					<ul style="margin:0; padding:0;">
				<!-- Edit: Summary info -->
						<li><span class="info-label">Summary</span>
							<?php if($is_runner || $creator_id == $user_id){ ?>
							<span class="info-data">
							<input type="text" size="30" class="text-field" id="summary" name="summary" value="<?php echo htmlentities($worklist['summary']);?>"/>
							<?php }else{?>
							<span class="info-summ"><?php echo $worklist['summary'];?>
							<?php } ?></span>
						</li>
				<!-- Edit: Status info -->
						<li><span class="info-label">Status</span><span class="info-data">
							<?php if ($is_runner || $user_id == $worklist['runner_id']){  ?><span class="info-data">
							<select id="status" name="status">
								<?php foreach($statusList as $status){ ?>
								<option value="<?php echo $status; ?>"<?php echo $status == $worklist['status']?' selected = "selected"' : ''; ?> ><?php echo $status; ?></option>
								<?php }?>
							</select>
                            <?php } else if ($has_budget && ($worklist['status'] == 'SUGGESTED' || $worklist['status'] == 'SKIP')) {?>
                            <select id="status" name="status">
                                <option value="<?php echo $worklist['status'] ?>" selected="selected"><?php echo $worklist['status'] ?></option>
                                <option value="BIDDING" <?php echo 'BIDDING' == $worklist['status']?' selected = "selected"' : ''; ?> >BIDDING</option>
                                <option value="WORKING" <?php echo 'WORKING' == $worklist['status']?' selected = "selected"' : ''; ?> >WORKING</option>
                            </select>
							<?php } elseif ($creator_id == $user_id && array_key_exists($worklist['status'], $statusMapMechanic)){ ?><span class="info-data">
							<select id="status" name="status">
								<option value="<?php echo $worklist['status']; ?>" selected="selected"><?php echo $worklist['status']; ?></option>
								<?php foreach($statusMapMechanic[$worklist['status']] as $status){ ?>
								<option value="<?php echo $status; ?>"><?php echo $status; ?></option>
								<?php } ?>
							</select>
							<?php }else{ ?><span class="info-data" style="font-size:18px"><?php echo $worklist['status'];?>
							<input type="hidden" id="status" name="status" value = "<?php $worklist['status']; ?>" />
							<?php } ?></span>
						</li>
				<!-- Edit: Key People info box -->
						<li>
							<div id="creatorBox"><span class="creatorName">Creator</span><?php echo $worklist['creator_nickname'];?></div>
							<div id="runnerBox"><span class="runnerName">Runner</span><?php echo $runner_nickname;?></div>
							<?php								
								$mech = '';							
								if(count($fees)>0){foreach($fees as $fe){if($fe['desc']=='Accepted Bid'){$mech = $fe['nickname'];}}}
								if($mech==''){$mech='Not assigned';}
								else{$mech='<span id="ping-btn" title="Ping Mechanic">'.$mech.'</span>';}							
								echo'<div id="mechanicBox"><span class="mechanicName">Mechanic</span>'.$mech.'</div>';
							?>
					<!-- Edit: Invite People field -->
							<div>
								<span style="font-weight:bold; margin-top:-5px;">Invite people to this task</span>
								<span class="info-invite" style="clear:none"><input type="text" name="invite" class="invite" /></span>
								<script type="text/javascript">
									$(document).ready(function() {
									$('.invite').autocomplete('getusers.php', {
										multiple: true,
										multipleSeparator: ', ',
										extraParams: { nnonly: 1 }
									});
								});
								</script>
							</div>
						</li>
				<!-- Edit: Notes textarea -->
						<li class="last">
							<span class="info-label">Notes</span>
							<?php if($is_runner || $creator_id == $user_id): ?>
								<span id="info-notes">
									<textarea size="33" style="-moz-border-radius: 6px; -webkit-border-radius: 6px; border-radius: 6px; width:418px; height:164px; margin-top:8px;" name="notes" rows="25" cols="20" id="edit-notes"><?php echo $worklist['notes'];?></textarea>
								</span>
							<?php else:?>
								<span id="info-notes"><?php echo nl2br($worklist['notes']);?></span>
							<?php endif;?>
						</li>
					</ul>
					<?php if($allowEdit && $action=="edit"): ?>
						<input style="margin-bottom:19px; margin-left:-8px;"type="submit" value="Save" name="save_workitem" id="save_workitem" onClick="return saveWorkitem();"/>
					<?php endif;?>
				</form>
<!-- *** End of Action is Edit *** -->
<?php else: ?>
<!-- *** Action is View *** -->
				<ul>
				<!-- View: Status info -->
					<li>
						<span class="info-label">Status</span><span class="info-data" style="margin-left:12px;font-size:16px"><?php echo $worklist['status'];?></span>
						<input type="hidden" id="status" name="status" value = "BIDDING" />
						<?php
							// quick status buttons for creators of items and mechanics working on them
							if(!$is_runner && ($creator_id == $user_id || $mechanic_id == $user_id) && array_key_exists($worklist['status'], $statusMapMechanic))
							{
								foreach($statusMapMechanic[$worklist['status']] as $status)
								{?>
									<form method="post" action="" style = "display:inline;">
										<input type="submit" id="status-switch" name="status-switch" value="Set to <?php echo $status; ?>" style="margin-top: -4px; float: right;" />
										<input type="hidden" name="quick-status" value="<?php echo $status; ?>" />
									</form>
								<?php 
								}
							}
							else if($is_runner && array_key_exists($worklist['status'], $statusMapRunner))
							{
								foreach($statusMapRunner[$worklist['status']] as $status)
								{?>
									<form method="post" action="" style = "display:inline;">
										<input type="submit" id="status-switch" name="status-switch" value="Set to <?php echo $status; ?>" style="margin-top: -4px; float: right;" />
										<input type="hidden" name="quick-status" value="<?php echo $status; ?>" />
									</form>
								<?php
								}
							}?>
					</li>
				<!-- View: Key People info -->
					<li>
							<div id="creatorBox"><span class="creatorName">Creator</span><?php echo $worklist['creator_nickname'];?></div>
							<div id="runnerBox"><span class="runnerName">Runner</span><?php echo $runner_nickname;?></div>
							<?php								
								$mech = '';							
								if(count($fees)>0){foreach($fees as $fe){if($fe['desc']=='Accepted Bid'){$mech = $fe['nickname'];}}}
								if($mech==''){$mech='Not assigned';}
								else{$mech='<span id="ping-btn" title="Ping Mechanic">'.$mech.'</span>';}							
								echo'<div id="mechanicBox"><span class="mechanicName">Mechanic</span>'.$mech.'</div>';
							?>
						
					</li>
				<!-- View: Notes textarea -->
					<li class="last">
						<span class="info-label">Notes</span>
						<textarea size="33" style="-moz-border-radius: 6px; -webkit-border-radius: 6px; border-radius: 6px; width:418px;  height:164px; margin-top:8px;" name="notes" rows="25" cols="20" readonly="readonly" id="edit-notes"><?php echo $worklist['notes'];?></textarea>
					</li>
				</ul>
<!-- *** End of Action IF *** -->
<?php endif;?>
			<div class="commentZone">
				<div class="info-comments">Comments</div>
				<ul>
				<?php 
					$comments = Comment::findCommentsForWorkitem($worklist['id']);
					if (empty($comments)) :
						?>No comments!<?php
					else :
						$it=0;
						foreach ($comments as $comment) :
							?><li id="comment-<?php echo $comment['id']; ?>" class="comment depth-<?php echo $comment['depth']; ?> <?php ($it % 2) ? print 'imOdd' : print 'imEven'; $it++; ?>">
								<div class="comment-info">
									<span class="author"><strong><?php echo $comment['comment']->getUser()->getNickname(); ?></strong>&nbsp;
										<span class="date"><?php echo relativeTime(strtotime($comment['comment']->getDate())-time());?></span>
									</span>
								</div>
								<div class="comment"><?php echo $comment['comment']->getComment(); ?></div>
								<?php if (!empty($user_id) && $comment['depth'] < 6) : ?>
								<div class="reply-lnk">
									<a href="#commentform" onClick="reply(<?php echo $comment['id']; ?>);return false;">Reply</a>
								</div>
								<?php endif; ?>
							</li>
						<?php
						endforeach;
					endif;
				?>
				</ul>
				<?php if (!empty($user_id)) : ?>
				<div class="commentform"> <a name="commentform"></a> <span class="info-label">Write a comment</span>
					<form action="" method="post">
						<input type="hidden" name="worklist_id" value="<?php echo $worklist['id']; ?>" />
						<input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
						<input type="hidden" name="comment_id" value="" />
						<textarea name="comment" cols="20" rows="10" size="33"></textarea>
						<br>
						<input type="submit" name="newcomment" value="Post Comment" />
					</form>
				</div>
				<?php endif; ?>
		<!-- End of div commentZone -->	
			</div>
	<!-- End of div for_view -->
			</div>
<!-- End of div left-panel -->
		</div>
		<div id="right-panel">
			<div class="moneyZone">
				<div id="bid-panel">
					<table width="100%" class="table-bids">
						<caption class="table-caption" >
							<b>Bids</b>
						</caption>
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
						<?php else: $row = 1;
							foreach($bids as $bid): ?>
							<tr class="<?php ($user_id) ? print 'row-bidlist-live' :'' ; ?> <?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?> biditem-<?php echo $bid['id'];?>">
								<td><?php echo $bid['nickname'];?></td>
								<td>$ <?php echo $bid['bid_amount'];?></td>
								<td<?php if ($bid['future_delta'] < 0) {echo ' class="warn"'; }?>><?php echo relativeTime($bid['future_delta']);?></td>
							</tr>
							<?php endforeach;?>
						<?php endif;?>
						</tbody>
					</table>
					<?php if($user_id): ?>
					<?php 	if ($worklist['status'] == 'BIDDING'): ?>
					<div class="buttonContainer">
						<input type="submit" value="Add Bid" onClick="return showConfirmForm('bid');"/>
					</div>
					<?php 	endif; ?>
					<?php endif;?>
			<!-- End of div bid-panel -->
				</div>
				<div id="fee-panel" >
					<table width="100%" class="table-fees">
						<caption class="table-caption" >
							<b>Fees</b>
						</caption>
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
						<?php else:	$row = 1; 
							foreach($fees as $fee): ?>
							<tr class="<?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?> ">
								<td><?php echo $fee['nickname'];?></td>
								<td>$ <?php echo $fee['amount'];?></td>
								<td><?php echo $fee['desc'];?></td>
								<td><?php echo $fee['date'];?></td>
								<td>
									<?php if($is_payer): ?><a href="#" class = "paid-link" id = "feeitem-<?php echo $fee['id'];?>"><?php echo $fee['paid'] == 0 ? "No" : "Yes";?></a>
									<?php else: echo $fee['paid'] == 0 ? "No" : "Yes";?>
									<?php endif;?>
									<?php if($user_id): ?><br><a href="#" id="wd-<?php echo $fee['id'];?>" class="wd-link">WD</a>
									<?php endif;?>
								</td>
							</tr>
							<?php endforeach;?>
							<tr>
								<td colspan="1" style="text-align:right; font-weight:bold;">Total</td>
								<td colspan="4" style="text-align:left"><span style="line-height:20px; font-weight:bold; font-size:16px">$ <?php echo $total_fee;?></span></td>
							</tr>
						<?php endif;?>
						</tbody>
					</table>
					<?php if($user_id): ?>
					<div class="buttonContainer">
						<input type="submit" value="Add Fee" onClick="return showConfirmForm('fee');"/>
					</div>
					<?php endif;?>
					<form id="withdraw" method="post" action="" >
						<input type="hidden" name="action" value="withdraw_bid" />
						<input type="hidden" class="fee_id" name="fee_id" value="" />
					</form>
			<!-- End of div fee-panel -->
				</div>
		<!-- End of div moneyZone -->
			</div>
			<div id="uploadPanel"> </div>
<!-- End of div right-panel-->
		</div>
<!-- End of div pageContent -->			
	</div>
	<div style="clear:both"></div>
			
	<!-- Popup HTML for Placing a bid -->
	<?php if(isset($message)): ?>
	<div id="message" style="display:none; padding: 0 0.7em; margin: 0.7em 0;" class="ui-state-highlight ui-corner-all">
		<p><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><strong>Info:</strong><?php echo $message; ?></p>
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
