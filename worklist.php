<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
include("config.php");
include("class.session_handler.php");
include_once("functions.php");
include_once("send_email.php");
include_once("update_status.php");
include_once("workitem.class.php");
require_once('lib/Agency/Worklist/Filter.php');

$page=isset($_REQUEST["page"])?intval($_REQUEST["page"]):1; //Get the page number to show, set default to 1
$is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
$is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
$journal_message = '';
$nick = '';

$workitem = new WorkItem();

$userId = getSessionUserId();
if( $userId > 0 )	{
	$user = new User();
	$user->findUserById( $userId );
	$nick = $user->getNickname();
	$userbudget =$user->getBudget();
	$budget =number_format($userbudget);
}

$filter = new Agency_Worklist_Filter();
$filter->setName('.worklist')
       ->initFilter();

if ($userId > 0 && isset($_POST['save_item'])) {
    $args = array('itemid', 'summary', 'status', 'notes', 'bid_fee_desc', 'bid_fee_amount', 'bid_fee_mechanic_id', 'invite', 'is_expense', 'is_rewarder');
    foreach ($args as $arg) {
    		// Removed mysql_real_escape_string, because we should 
    		// use it in sql queries, not here. Otherwise it can be applied twice sometimes
        $$arg = !empty($_POST[$arg])?$_POST[$arg]:'';
    }

    $creator_id = $userId;

    if (!empty($_POST['itemid'])) {
        $workitem->loadById($_POST['itemid']);
        $journal_message .= $nick . " updated ";
    } else {
        $workitem->setCreatorId($creator_id);
        $journal_message .= $nick . " added ";
    }
    $workitem->setSummary($summary);

    // not every runner might want to be assigned to the item he created - only if he sets status to 'BIDDING'
    if($status == 'BIDDING' && ($user->getIs_runner() == 1 || $user->getBudget() > 0)){
        $runner_id = $userId;
    }else{
        $runner_id = 0;
    }

    $workitem->setRunnerId($runner_id);
    $workitem->setStatus($status);
    $workitem->setNotes($notes);
    $workitem->save();

    if(empty($_POST['itemid']))  {
        $bid_fee_itemid = $workitem->getId();
        $journal_message .= " item #$bid_fee_itemid: $summary. ";
        if (!empty($_POST['files'])) {
            $files = explode(',', $_POST['files']);
            foreach ($files as $file) {
                $sql = 'UPDATE `' . FILES . '` SET `workitem` = ' . $bid_fee_itemid . ' WHERE `id` = ' . (int)$file;
                mysql_query($sql);
            }
        }
    } else {
        $bid_fee_itemid = $itemid;
        $journal_message .=  "item #$itemid: $summary. ";
    }

    if (!empty($_POST['invite'])) {
        $people = explode(',', $_POST['invite']);
        invitePeople($people, $bid_fee_itemid, $summary, $notes);
    }

    if ($bid_fee_amount > 0) {
        $journal_message .= AddFee($bid_fee_itemid, $bid_fee_amount, 'Bid', $bid_fee_desc, $bid_fee_mechanic_id, $is_expense, $is_rewarder);
    }
} 

if (!empty($journal_message)) {
    //sending journal notification
    $data = array();
    $data['user'] = JOURNAL_API_USER;
    $data['pwd'] = sha1(JOURNAL_API_PWD);
    $data['message'] = stripslashes($journal_message);
    $prc = postRequest(JOURNAL_API_URL, $data);
}

/* Prevent reposts on refresh */
if (!empty($_POST)) {
    unset($_POST);
    header("Location:worklist.php");
    exit();
}

/*
 * Retrieve the latest status update from the user
 */
$current_status = get_status(true);

/*********************************** HTML layout begins here  *************************************/
$worklist_id = isset($_REQUEST['job_id']) ? intval($_REQUEST['job_id']) : 0;

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<link href="css/feedback.css" rel="stylesheet" type="text/css">
<link href="css/ui.toaster.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/jquery.tabSlideOut.v1.3.js"></script>
<script type="text/javascript" src="js/feedback.js"></script>
<script type="text/javascript" src="js/ui.toaster.js"></script>
<script type="text/javascript">
    // This variable needs to be in sync with the PHP filter name
    var filterName = '.worklist';
	var affectedHeader = false;
	var directions = {"ASC":"images/arrow-up.png","DESC":"images/arrow-down.png"};
    var refresh = <?php echo AJAX_REFRESH ?> * 1000;
    var lastId;
    var page = <?php echo $page ?>;
    var topIsOdd = true;
    var timeoutId;
    var addedRows = 0;
    var workitem = 0;
//    var cur_user = false;
    var workitems;
	var dirDiv;
	var dirImg;
    var user_id = <?php echo isset($_SESSION['userid']) ? $_SESSION['userid'] : '"nada"' ?>;
    var is_runner = <?php echo $is_runner ? 1 : 0 ?>;
    var runner_id = <?php echo !empty($runner_id) ? $runner_id : 0 ?>;
    var is_payer = <?php echo $is_payer ? 1 : 0 ?>;
	var dir = '<?php echo $filter->getDir(); ?>';
	var sort = '<?php echo $filter->getSort(); ?>';
	var resetOrder = false;

    function AppendPagination(page, cPages, table)    {
	// support for moving rows between pages
	if(table == 'worklist')	{
	    // preparing dialog
	    $('#pages-dialog select').remove();
	    var selector = $('<select>');
	    for (var i = 1; i <= cPages; i++) {
			selector.append('<option value = "' + i + '">' + i + '</option>');
	    }
	    $('#pages-dialog').prepend(selector);
	}
        var pagination = '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table + '-pagination-row nodrag nodrop " ><td colspan="6" style="text-align:center;">Pages : &nbsp;';
        if (page > 1) {
            pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + (page-1) + '">Prev</a> &nbsp;';
        }
        for (var i = 1; i <= cPages; i++) {
            if (i == page) {
                pagination += i + " &nbsp;";
            } else {
                pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + i + '" >' + i + '</a> &nbsp;';
            }
        }
        if (page < cPages) {
            pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + (page+1) + '">Next</a> &nbsp;';
        }
        pagination += '</td></tr>';
        $('.table-' + table).append(pagination);
    }
    function return2br(dataStr) {
        return dataStr.replace(/(\r\n|\r|\n)/g, "<br />");
    }
    // see getworklist.php for json column mapping
    function AppendRow(json, odd, prepend, moreJson, idx)    {
        var pre = '', post = '';
        var row;
			
        row = '<tr id="workitem-' + json[0] + '" class="row-worklist-live iToolTip hoverJobRow ';

		// disable dragging for all rows except with "BIDDING" status
		if (json[2] != 'BIDDING'){
			row += ' nodrag ';
		}

        if (odd) { row += ' rowodd' } else { row += 'roweven' }

		if(user_id == json[9]){ //is the same person who created the work item
		  row += ' rowown';
		}
        row += '">';
        if (prepend) {
			pre = '<div class="slideDown" style="display:none">';
			post = '</div>';
		}
		// Displays the ID of the task in the first row
		 // 26-APR-2010 <Yani>
		row+= '<td width="5%"><span class="taskID">#' + json[0] + '</span></td>';
		
        row += '<td width="45%"><span class="taskSummary">' + pre + json[1] + post + '</span></td>';
        if (json[2] == 'BIDDING' && json[10] > 0 && (user_id == json[9] || is_runner == 1)) {
            post = ' (' + json[10] + ')';
        }
        row += '<td width="10%">' + pre + json[2] + post + '</td>';
        pre = '';
        post = '';
/*
        if (json[3] != '') {
			var who = json[3];
			var tooltip = "Owner: "+json[4];
			if(json[9] != null && json[3] != json[9]) {
				who +=  ', ' + json[9];
				tooltip += '<br />Mechanic: '+json[10];
			}
			
            row += '<td width="15%" class="toolparent">' + pre + who + post + '<span class="tooltip">' + tooltip  + '</span>' + '</td>';
        } else {
            row += '<td width="15%">' + pre + json[3] + post + '</td>';
        }
*/

	var who = '',
		createTagWho = function(id,nickname,type) {
			return '<span class="'+type+'" title="' + id + '">'+nickname+'</span>';
		};
	if(json[3] == json[4]){
	
		// creator nickname can't be null, so not checking here
		who += createTagWho(json[9],json[3],"creator");
	}else{

		var runnerNickname = json[4] != null ? ', ' + createTagWho(json[13],json[4],"runner") : '';
		who += createTagWho(json[9],json[3],"creator") + runnerNickname;
	}
	if(json[5] != null){

		who += ', ' + createTagWho(json[14],json[5],"mechanic");
	}

	row += '<td width="15%" class="who">' + pre + who + post + '</td>';

        if (json[2] == 'WORKING' && json[11] != null) {
            row += '<td width="15%">' + pre + (RelativeTime(json[11]) + ' from now').replace(/0 sec from now/,'Past due') + post +'</td>';
        } else {
            row += '<td width="15%">' + pre + RelativeTime(json[6]) + ' ago' + post +'</td>';
        }

        // Comments
        row += '<td width="7.5%">' + json[12] + '</td>';

        if (is_runner == 1) {
			 var feebids = 0;
			if(json[7]){
				feebids = json[7];
			}
			var bid = 0;
			if(json[8]){
				bid = json[8];
			}
			if(json[2] == 'BIDDING'){
				bid = parseFloat(bid);
				if (bid == 0) {
					feebids = '';
				} else {
					feebids = '$' + parseFloat(bid);
				}
			} else {
				feebids = '$' + feebids;
			}
			row += '<td width="7.5%">' + pre + feebids + post + '</td>';
        }
		row += '</tr>';
        if (prepend) {
            $(row).prependTo('.table-worklist tbody')
				.find('td div.slideDown').fadeIn(500);
            setTimeout(function(){
                $(this).removeClass('slideDown');
                if (moreJson && idx-- > 1) {
                    topIsOdd = !topIsOdd;
                    AppendRow(moreJson[idx], topIsOdd, true, moreJson, idx);
                }
            }, 500);
        } else {
            $('.table-worklist tbody').append(row);
        }
    }

    function Change(obj, evt)    {
        if(evt.type=="focus")
            obj.style.borderColor="#6A637C";
        else if(evt.type=="blur")
           obj.style.borderColor="#d0d0d0";
    }

    function ClearSelection () {
        if (document.selection)
            document.selection.empty();
        else if (window.getSelection)
            window.getSelection().removeAllRanges();
    }
	
    function SetWorkItem(item) {
        var match = item.attr('id').match(/workitem-\d+/);
        if (match) {
            workitem = match[0].substr(9);
        } else {
            workitem = 0;
        }
		return workitem;
    }

	function orderBy(option) {
		if (option == sort) dir = ((dir == 'asc')? 'desc':'asc');
		else {
			sort = option;
			dir = 'asc';
		}
		GetWorklist(1,false);
	}
    function GetWorklist(npage, update, reload) {
		loaderImg.show("loadRunning","Loading, please wait ...");
		$.ajax({
			type: "POST",
			url: 'getworklist.php',
			cache: false,
			data: {
				page: npage,
				status: $('select[name=status]').val(),
				sort: sort,
				dir: dir,
				user: $('select[name=user]').val(),
				query: $("#query").val(),
				reload: ((reload == undefined) ? false : true)
			},
			dataType: 'json',
			success: function(json) {
				if (json[0] == "redirect") {
					$("#query").val('');
					window.location.href = buildHref(json[1], false);
					return false;
				}
				
				loaderImg.hide("loadRunning");
				if (affectedHeader) {
					affectedHeader.append(dirDiv);
					dirImg.attr('src',directions[dir.toUpperCase()]);
					dirDiv.css('display','block');
				} else {
					if (resetOrder) {
						dirDiv.css('display','none');
						resetOrder = false;
					}
				}
				affectedHeader = false;
				page = json[0][1]|0;
				var cPages = json[0][2]|0;

				$('.row-worklist-live').remove();
				workitems = json;
				if (!json[0][0]) return;

				/* When updating, find the last first element */
				for (var lastFirst = 1; update && page == 1 && lastId && lastFirst < json.length && lastId != json[lastFirst][0]; lastFirst++);
				lastFirst = Math.max(1, lastFirst - addedRows);
				addedRows = 0;

				/* Output the worklist rows. */
				var odd = topIsOdd;
				for (var i = lastFirst; i < json.length; i++) {
					AppendRow(json[i], odd);
					odd = !odd;
				}
				
				AppendPagination(page, cPages, 'worklist');

				if (update && lastFirst > 1) {
					/* Update the view by scrolling in the new entries from the top. */
					topIsOdd = !topIsOdd;
					AppendRow(json[lastFirst-1], topIsOdd, true, json, lastFirst-1);
				}
				lastId = json[1][0];
				
				/*commented for remove tooltip */
				//MapToolTips();
				var showUserInfo = function(userId) {
						$('#user-info').html('<iframe id="modalIframeId" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" />')
							.dialog('open');
						$('#modalIframeId').attr('src','userinfo.php?id=' + userId);
						return false;
 					};
				$('tr.row-worklist-live').hover(
					function() {
						var selfRow=$(this);
						$(".taskID,.taskSummary",this).wrap("<a href='" + 
							buildHref( SetWorkItem(selfRow), (selfRow.hasClass('rowown') || is_runner == 1) ) + 
							"'></a>");
						$(".creator,.runner,.mechanic",$(".who",this)).toggleClass("linkTaskWho").click(
							function() {
								showUserInfo($(this).attr("title"));
							}
						);
					},function() {
						$(".taskID,.taskSummary",this).unwrap();
						$(".creator,.runner,.mechanic",$(".who",this)).toggleClass("linkTaskWho").unbind("click");;
				});

				$('.worklist-pagination-row a').click(function(e){
					page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
					if (timeoutId) clearTimeout(timeoutId);
					GetWorklist(page, false);
					e.stopPropagation();
					return false;
				});

			},
			error: function(xhdr, status, err) {
				$('.row-worklist-live').remove();
				$('.table-worklist').append('<tr class="row-worklist-live rowodd"><td colspan="5" align="center">Oops! We couldn\'t find any work items.  <a id="again" href="#">Please try again.</a></td></tr>');
				$('#again').click(function(){
					loaderImg.hide("loadRunning");
					if (timeoutId) clearTimeout(timeoutId);
					GetWorklist(page, false);
					e.stopPropagation();
					return false;
				});
			}
		});

		timeoutId = setTimeout("GetWorklist("+page+", true, true)", refresh);
    }


	/*
	*    aneu: Added jquery.hovertip.min.js 
	*          Is this function below needed or used somewhere?
	*/
    function ToolTip() {
        xOffset = 10;
        yOffset = 20;
        var el_parent, el_child;
        $(".toolparent").hover(function(e){
            if (el_child) el_child.appendTo(el_parent).hide();
            el_parent = $(this);
            el_child = el_parent.children(".tooltip")
                .appendTo("body")
                .css("top",(e.pageY - xOffset) + "px")
                .css("left",(e.pageX + yOffset) + "px")
                .fadeIn("fast");
        },
        function(){
            if (el_child) el_child.appendTo(el_parent);
            $(".tooltip").hide();
            el_child = null;
        });
        $(".toolparent").mousemove(function(e){
            if (el_child) {
                el_child
                    .css("top",(e.pageY - xOffset) + "px")
                    .css("left",(e.pageX + yOffset) + "px");
            }
        });
    }

    function buildHref(item, edit) {
		var action = "view";
		if(edit) {
			action = "edit";
		}
		return "<?php echo SERVER_URL ; ?>workitem.php?job_id="+item+"&action="+action;
    }

    function ResetPopup() {
		$('#for_edit').show();
		$('#for_view').hide();
		$('.popup-body form input[type="text"]').val('');
		$('.popup-body form select option[value=\'BIDDING\']').attr('selected', 'selected');
		$('.popup-body form textarea').val('');
    }


//Most of the js code for implementing bidding capability starts here
    function GetBidlist(worklist_id, npage) {
		$("#bid").unbind( "click" );
		$.ajax({
			type: "POST",
			url: 'getbidlist.php',
			data: 'worklist_id=' + worklist_id + '&page=' + npage,
			dataType: 'json',
			success: function(json) {
				page = json[0][1]|0;
				var cPages = json[0][2]|0;

				$('.row-bidlist-live').remove();
				biditems = json;
				if (!json[1]){
					var row = '<tr bgcolor="#FFFFFF" class="row-bidlist-live bidlist-pagination-row" ><td colspan="4" style="text-align:center;">No bids yet.</td></tr>';
					$('.table-bidlist tbody').append(row);
					return;
				}

				var already_bid = false;

				/* Output the bidlist rows. */
				var odd = topIsOdd;
				for (var i = 1; i < json.length; i++) {
					if (json[i].bidder_id == "<?php echo (isset($_SESSION['userid'])) ? $_SESSION['userid'] : ''; ?>"){
						already_bid = true;
					}
					AppendBidRow(json[i], odd);
					odd = !odd;
				}

				AppendPagination(page, cPages, 'bidlist');

				$('.bidlist-pagination-row a').click(function(e){
					page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
					GetBidlist(worklist_id, page);
					e.stopPropagation();
					return false;
				});

				//show additional popup with bid info
				$('tr.row-bidlist-live').click(function(){
					var match = $(this).attr('class').match(/biditem-\d+/);
					var bid_id = match[0].substr(8);
					ResetBidInfoPopup();

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
								if (is_runner==1 || runner_id == "<?php echo (isset($_SESSION['userid'])) ? $_SESSION['userid'] : ''; ?>")
									$('#popup-bid-info form').append('<input type="submit" name="accept_bid" value="Accept">');
								if (is_runner==1 || (json.bidder_id == "<?php echo (isset($_SESSION['userid'])) ? $_SESSION['userid'] : ''; ?>"))
									$('#popup-bid-info form').append('<input type="submit" name="withdraw_bid" value="Withdraw" style="float:right;">');
							});

				$('#popup-bid-info').dialog('open');
			});

			if(already_bid)	{
				$('#bid').click(function(e){
					if (!confirm("You have already placed a bid, do you want to place a new one?"))	{
						$('#popup-bid').dialog('close');
						return false;
					}
				});
			}

			},
			error: function(xhdr, status, err) {
				$('.row-bidlist-live').remove();
				$('.table-bidlist').append('<tr class="row-bidlist-live rowodd"><td colspan="4" align="center">Oops! We couldn\'t find any bid items.  <a id="againbid" href="#">Please try again.</a></td></tr>');
				$('#againbid').click(function(e){
					if (timeoutId) clearTimeout(timeoutId);
					GetBidlist(worklist_id, page);
					e.stopPropagation();
					return false;
				});
			}
		});
    }

	//json row
	//id, bidder_id,email,nickname,worklist_id,bid_amount,bid_created,bid_done,notes,delta
    function AppendBidRow(json, odd)	{
        var pre = '', post = '';
        var row;
        row = '<tr class="row-bidlist-live ';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += ' biditem-' + json.id + '">'; //id
        row += '<td width="30%">' + pre + json.email + post + '</td>';//email
        row += '<td width="20%">' + pre + json.bid_amount + post + '</td>';
        row += '<td width="20%">' + pre + RelativeTime(json.future_delta) + post + '</td>';

        row += '<td width="20%">' + pre + RelativeTime(json.delta) + ' ago' + post + '</td></tr>';
       $('.table-bidlist tbody').append(row);
    }

    function ResetBidInfoPopup(){
      $('#popup-bid-info form input[type="submit"]').remove();
    }
//end of code for bidding table


//code for fees table
    // !!!
    // !!! This code is duplicated in workitem.inc
    // !!!
    function GetFeelist(worklist_id) {
		$.ajax({
			type: "POST",
			url: 'getfeelist.php',
			data: 'worklist_id=' + worklist_id,
			dataType: 'json',
			success: function(json) {
				$('.row-feelist-live').remove();
				feeitems = json;
				if (!json[1])	{
				  var row = '<tr bgcolor="#FFFFFF" class="row-feelist-live feelist-total-row" >'+
							'<td colspan="5" style="text-align:center;">No fees yet.</td></tr>';
				  $('.table-feelist tbody').append(row);
				  return;
				}

				/* Output the bidlist rows. */
				var odd = topIsOdd;
				for (var i = 1; i < json.length; i++) {
					AppendFeeRow(json[i], odd);
					odd = !odd;
				}

				//will row with total here
				var row = '<tr bgcolor="#FFFFFF" class="row-feelist-live feelist-total-row" >'+
							'<td colspan="5" style="text-align:center;">Total Fees $' + json[0][0] + '</td></tr>';
				$('.table-feelist tbody').append(row);

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
							type: 'POST',
							url: 'paycheck.php',
							dataType: 'json',
							data: {
								itemid: $('#' + this.id + ' input[name=itemid]').val(),
								paid_check: $('#' + this.id + ' input[name=paid_check]').val(),
								paid_notes: $('#' + this.id + ' textarea[name=paid_notes]').val()
							},
							success: function(data) {
								// We need to empty the notice field before we refill it
								$('.paidnotice').empty();
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
						GetFeelist(worklist_id);
					});

					return false;
				});

				$('.wd-link').click(function(e) {
					$(this).parent().submit();
				});
			},
			error: function(xhdr, status, err) {
				$('.row-feelist-live').remove();
				$('.table-feelist').append('<tr class="row-feelist-live rowodd"><td colspan="5" align="center">Oops! We couldn\'t find any fees.  <a id="againfee" href="#">Please try again.</a></td></tr>');
				$('#againfee').click(function(e){
					GetFeelist(worklist_id);
					e.stopPropagation();
					return false;
				});
			}
		});
    }

    function AppendFeeRow(json, odd)	{
        var pre = '', post = '';
        var row;
        row = '<tr class="row-feelist-live "';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += ' feeitem-' + json[0] + '">';
        row += '<td>' + pre + json[2] + post + '</td>';
        row += '<td>' + pre + json[1] + post + '</td>';
        row += '<td>' + pre + json[3] + post + '</td>';
        row += '<td>' + pre + json[4] + post + '</td>';

	    var paid = (json[5] == 0) ? 'No' : 'Yes';
	    if(is_payer) {
	        pre = '<a href="#" class = "paid-link" id = "feeitem-' + json[0] + '" >';
	        post = '</a>';
	    }

		var wd = '';
		if (is_runner) {
			var wd = ' - <form action="" method="post">' +
							'<input type="hidden" name="withdraw_bid" value="withdraw" />' +
							'<input type="hidden" name="fee_id" value="' + json[0] + '" />' +
							'<a href="#" class = "wd-link" title="Delete Entry">DEL</a>' +
						 '</form>';
		}

		row += '<td>' + pre + paid + post + wd + '</td></tr>';
		$('.table-feelist tbody').append(row);
    }

//end of code for fees table

	jQuery.fn.center = function () {
	  this.css("position","absolute");
	  this.css("top", (( $(window).height() - this.outerHeight() ) / 2 ) + "px");
	  this.css("left", (( $(window).width() - this.outerWidth() ) / 2 ) + "px");
	  return this;
	}
	/*
	show a message with a wait image
	several asynchronus calls can be made with different messages 
	*/
	var loaderImg = function($)
	{
		var aLoading = new Array(),
			_removeLoading = function(id) {
				for (var j=0; j < aLoading.length; j++) {
					if (aLoading[j].id == id) {
						if (aLoading[j].onHide) {
							aLoading[j].onHide();
						}
						aLoading.splice(j,1);
					}
				}
			},
			_show = function(id,title,callback) {
				aLoading.push({ id : id, title : title, onHide : callback});
				$("#loader_img_title").append("<div class='"+id+"'>"+title+"</div>");
				if (aLoading.length == 1) {
					$("#loader_img").css("display","block");
				}
				$("#loader_img_title").center();
			},
			_hide = function(id) {
				_removeLoading(id);
				if (aLoading.length == 0) {
					$("#loader_img").css("display","none");		
					$("#loader_img_title div").remove();
				} else {
					$("#loader_img_title ."+id).remove();
					$("#loader_img_title").center();
				}
			};
		
	return {
		show : _show,
		hide : _hide
	};

	}(jQuery); // end of function loaderImg


    $(document).ready(function() {
        // Fix the layout for the User selection box
        var box_h = $('select[name=user]').height() +1;
        $('#userbox').css('margin-top', '-'+box_h+'px');
    
		dirDiv = $("#direction");
		dirImg = $("#direction img");
		hdr = $(".table-hdng");
		if (sort != 'priority') {
			hdr.children().each(function() {
				if ($(this).text().toLowerCase() == unescape(sort.toLowerCase())) {
					affectedHeader = $(this);
				}
			});
		}
		hdr.children().click(function() {
			affectedHeader = $(this);
			orderBy($(this).text().toLowerCase());
		});

		$('#popup-edit').dialog({ 
			autoOpen: false,
            show: 'fade',
            hide: 'fade',
			maxWidth: 600, 
			width: 400,
			hasAutocompleter: false,
			open: function() {
				if (this.hasAutocompleter !== true) {
					$('.invite').autocomplete('getusers.php', {
						multiple: true,
						multipleSeparator: ', ',
						selectFirst: true,
						extraParams: { nnonly: 1 }
					});
					this.hasAutocompleter = true;
				}
			}
		});
		$('#popup-bid').dialog({ autoOpen: false, maxWidth: 600, width: 450, show: 'fade', hide: 'fade'});
		$('#popup-bid-info').dialog({ autoOpen: false, modal: true, show: 'fade', hide: 'fade'});
		$('#popup-addfee').dialog({ autoOpen: false, modal: true, width: 400, show: 'fade', hide: 'fade'});
		$('#popup-paid').dialog({ autoOpen: false, maxWidth: 600, width: 450, show: 'fade', hide: 'fade'});
		$('#pages-dialog').dialog({ autoOpen: false });

		$('#done_by').datepicker({
			duration: '',
			showTime: true,
			constrainInput: false,
			stepMinutes: 1,
			stepHours: 1,
			altTimeField: '',
			time24h: false
		});

		$('#popup-bid').bind('dialogclose', function(){
			$('#ui-datepicker-div').hide();
			$('#ui-timepicker-div').hide();
		});
		
		$('#user-info').dialog({
           autoOpen: false,
           modal: true,
           height: 500,
           width: 800
		});

		GetWorklist(<?php echo $page?>, false, true);
		
		$("#owner").autocomplete('getusers.php', { cacheLength: 1, max: 8 } );
		reattachAutoUpdate();

		$('#add').click(function(){
			$('#popup-edit').data('title.dialog', 'Add Worklist Item');
			$('#popup-edit form input[name="itemid"]').val('');
			ResetPopup();
			$('#save_item').click(function(event){
				if ($('#save_item').data("submitIsRunning") === true) {
					event.preventDefault();
					return false;
				}
				$('#save_item').data( "submitIsRunning",true );
				loaderImg.show( "saveRunning","Saving, please wait ...",function() {
					$('#save_item').data( "submitIsRunning",false );
				});
				if($('#popup-edit form input[name="bid_fee_amount"]').val() || $('#popup-edit form input[name="bid_fee_desc"]').val()) {
					// see http://regexlib.com/REDetails.aspx?regexp_id=318
					var regex = /^\$?(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
					var optionsLiveValidation = { onlyOnSubmit: true,
						onInvalid : function() {
							loaderImg.hide("saveRunning");
							this.insertMessage( this.createMessageSpan() ); 
							this.addFieldClass();
						}
					};
					var bid_fee_amount = new LiveValidation('bid_fee_amount',optionsLiveValidation);
					var bid_fee_desc = new LiveValidation('bid_fee_desc',optionsLiveValidation);

					bid_fee_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
					bid_fee_amount.add( Validate.Format, { pattern: regex, failureMessage: "Invalid Input!" });
					bid_fee_desc.add( Validate.Presence, { failureMessage: "Can't be empty!" });
				} else {
					if (bid_fee_amount) bid_fee_amount.destroy();
					if (bid_fee_desc) bid_fee_desc.destroy();
				}
				return true;
			});
			$('#fees_block').hide();
			$('#fees_single_block').show();
			$('#popup-edit').dialog('open');
		});

		$('.popup-body form input[type="submit"]').click(function(){
			var name = $(this).attr('name');
			$(".popup-page-value").val(page);

			switch(name)	{
				case "add_fee_dialog":
					SimplePopup('#popup-addfee',
								'Add Fee',
								workitem,
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

		$("#search").click(function(e){
			e.preventDefault();
			$("#searchForm").submit();
			return false;
		});
		
		$('#query').keypress(function(event) {
			if (event.keyCode == '13') {
				event.preventDefault();
				$("#search").click();
			}
		});

		$("#search_reset").click(function(e){
			e.preventDefault();
			$("#query").val('');
			affectedHeader = false;
			resetOrder = true;
			sort = 'null';
			dir = 'asc';
			GetWorklist(1,false);
			return false;
		});

		$("#searchForm").submit(function(){
			//$("#loader_img").css("display","block");
			GetWorklist(1,false);
			return false;
		});

		$('#page-go').click(function(){
			var npage = $('#pages-dialog select').val();
			if(npage != page){
				getIdFromPage(npage, $('#pages-dialog #worklist-id').val(), 0);
			}
			$('#pages-dialog').dialog('close');
			return false;
		});
	
		$('#page-go-highest').click(function()	{
			updatePriority($('#pages-dialog #worklist-id').val(), 0, 5);
			$('#pages-dialog').dialog('close');
			return false;
		});
		//-- gets every element who has .iToolTip and sets it's title to values from tooltip.php
		/* function commented for remove tooltip */
		//setTimeout(MapToolTips, 800);

		// to add a custom stuff we bind on events
		$('select[name=user]').bind({
			'beforeshow newlist': function(e, o) {
				// now we create a new li element with a checkbox in it
				var li = $('<li/>').css({
					left: 0,
					position: 'absolute',
					background: '#AAAAAA',
					width: '123px',
					top: '180px'
				});
				var label = $('<label/>').css('color', '#ffffff').attr('for', 'onlyActive');
				var checkbox = $('<input/>').attr({
					type: 'checkbox',
					id: 'onlyActive'
				}).css({
						margin: 0,
						position: 'relative',
						top: '1px'
				});

				// we need to update the global activeUsersFlag
				if (activeUsersFlag) {
					activeUsersFlag = 0;
					checkbox.attr('checked', true);
				} else {
					activeUsersFlag = 1;
					checkbox.attr('checked', false);
				}

				label.text(' Active only');
				label.prepend(checkbox);
				li.append(label);

				// now we add a function which gets called on click
				li.click(function(e) {
					// we hide the list and remove the active state
					o.list.hide();
					o.container.removeClass('ui-state-active');
					// we send an ajax request to get the updated list
					$.ajax({
						type: 'POST',
						url: 'refresh-filter.php',
						data: {
							name: filterName,
							active: activeUsersFlag
						},
						dataType: 'json',
						// on success we update the list
						success: $.proxy(o.setupNewList, o)
					});
					// just to be shure nothing else gets called we return false
					return false;
				});

				// the scroll handler so our new listelement will stay on the bottom
				o.list.scroll(function() {
					li.css('top', ($(this).scrollTop() + 180) + 'px');
				});

				// now we append the list element to the list
				o.list.append($('<li>&nbsp</li>'));
				o.list.append(li);
			}
		}).comboBox();
		$('#search-filter-wrap select[name=status]').comboBox();
	});
	
	function reattachAutoUpdate() {
		$("select[name=user], select[name=status]").change(function(){
			if ($("#search-filter").val() == 'UNPAID') {
				$(".worklist-fees").text('Unpaid');
			} else {
				$(".worklist-fees").text('Fees/Bids');
			}

			page = 1;
			if (timeoutId) clearTimeout(timeoutId);
			GetWorklist(page, false);
		});
	}

    function getIdFromPage(npage, worklist_id)	{
	    $.ajax({
			type: "POST",
			url: 'getworklist.php',
			cache: false,
			data: 'page='+npage+'&sfilter='+$("#search-filter").val()+'&ufilter='+$("#user-filter").val()+"&query="+$("#query").val(),
			dataType: 'json',
			success: function(json) {
				// if moving on the greater page - place item on top, if on page with smaller number - on the end of the list
				if(npage > page){
					prev_id = json[1][0];
				}else{
					prev_id = json[json.length-2][0];
				}
				updatePriority(worklist_id, prev_id, 5);
			}
		});
    }
	function updatePriority(worklist_id, prev_id, bump){
		$.ajax({
			type: "POST",
			url: 'updatepriority.php',
			data: 'id='+worklist_id+'&previd='+prev_id+'&bump='+bump,
			success: function(json) {
				GetWorklist(page, true);
			}
		});
	}
</script>
<script type="text/javascript" src="js/utils.js"></script>
<title>Worklist | Lend a Hand</title>
</head>
<body>
<div style="display: none; position: fixed; top: 0px; left: 0px; width: 100%; height: 100%; text-align: center; line-height: 100%; background: white; opacity: 0.7; filter: alpha(opacity =   70); z-index: 9998"
     id="loader_img"><div id="loader_img_title"><img src="images/loading_big.gif"
     style="z-index: 9999"></div></div>

<!-- Popup for editing/adding  a work item -->
<?php require_once('popup-edit.inc') ?>

<!-- Popup HTML for paying a fee -->
<?php require_once('popup-paid-html.inc') ?>

<!-- Popup for placing a bid -->
<?php require_once('popup-bid.inc') ?>

<!-- Popup for bid info-->
<?php require_once('popup-bid-info.inc') ?>

<!-- Popup for adding fee-->
<?php require_once('popup-addfee.inc') ?>

<!-- Popup for breakdown of fees-->
<?php require_once('popup-fees.inc') ?>

<!-- Div for moving items accross the pages -->
<div id="pages-dialog" title="Select page to move item" style="display: none;">
	<input type="submit" id="page-go" value="Go" /><br />
	<br />
	<input type="submit" id="page-go-highest" value="Highest" />
	<input type="hidden" id="worklist-id" />
</div>
<!-- Feedback tab html -->
<?php require_once('feedback.inc') ?>
<?php include("format.php"); ?>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

<?php if (isset($_SESSION['userid'])) { ?>
<?php if($is_runner){ ?>
<div style="float: right; margin-top: 3px; font-weight:bold; font-size:16px;">Remaining Budget: $<?php echo $budget; ?></div>
<?php }?>
<div id="buttons">
<p><input type="submit" id="add" name="add" value="Add" class="iToolTip addButton" /> 

</p>
</div>
<br style="clear: both;" />
<br/>
<div id="status-wrap" style="width:340px;">
	<form action="" id="status-update-form" style="width:340px;"><?php echo $nick?> is <span id="status-lbl"><b><?php echo $current_status?></b></span>
		<input style="display: none;" type="text" maxlength="45" id="status-update" name="status-update"
			value="<?php echo $current_status?>"></input>
		<div id="status-share" style="display: none; float:right; width:122px;">
			<input type="submit" value="Share" id="status-share-btn"></input>
		</div>
	</form>
</div>

<?php } ?>
<div id="search-filter-wrap">
    <div style="float: right">
        <form method="get" action="" id="searchForm" />
            <?php echo $filter->getUserSelectbox(1); ?>
	        <?php echo $filter->getStatusSelectbox(); ?>
	        <div class="input_box">
	            <input type="text" id="query" value="<?php echo (($filter->getQuery()) ? $filter->getQuery() : ''); ?>" name="query" alt="Search" size="20" />
	            <a id="search" href="" class="searchIcon"><img height="23" width="24" border="0" alt="zoom" src="images/spacer.gif"></a>
            	<a id="search_reset" href="" class="searchIcon"><img src="images/cross.png"></a>
            </div>
        </form>
    </div>
</div>

<div style="clear: both"></div>
<table width="100%" class="table-worklist">
    <thead>
        <tr class="table-hdng">
			 <td>ID</td>
            <td>Summary</td>
            <td>Status</td>
            <td>Who</td>
            <td>When</td>
            <td>Comments</td>
            <td class="worklist-fees"  <?php echo empty($_SESSION['is_runner']) ? 'style="display:none"' : ''; ?>>Fees/Bids</td>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
<span id="direction" style="display: none; float: right;"><img src="images/arrow-up.png" /></span>
<div id="user-info" title="User Info"></div>

<?php include("footer.php"); ?>
