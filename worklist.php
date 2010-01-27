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

$page=isset($_REQUEST["page"])?intval($_REQUEST["page"]):1; //Get the page number to show, set default to 1

if (isset($_SESSION['userid']) && isset($_POST['save'])) {
    $args = array('id', 'summary', 'status', 'notes');
    foreach ($args as $arg) {
        $$arg = mysql_real_escape_string($_POST[$arg]);
    }

    $creator_id = $_SESSION['userid'];

    $owner_id = 0;
    $owner = mysql_real_escape_string($_POST['owner']);
    $res = mysql_query("select id from ".USERS." where username='$owner' || nickname='$owner'");
    if ($res && ($row = mysql_fetch_assoc($res))) {
        $owner_id = $row['id'];
    }

    if (!empty($_POST['id'])) {
        $query = "update ".WORKLIST." set summary='$summary', owner_id='$owner_id',
	status='$status', notes='$notes' where id='$id'";
    } else {
        $query = "insert into ".WORKLIST." ( summary, creator_id, owner_id, status, notes, created ) ".
            "values ( '$summary', '$creator_id', '$owner_id', '$status', '$notes', now() )";
    }

    $rt = mysql_query($query);
} else if (isset($_SESSION['userid']) && isset($_POST['delete']) && !empty($_POST['id'])) {
    mysql_query("delete from ".WORKLIST." where id='".intval($_POST['id'])."'");
}
//placing a bid
if (isset($_POST['bid'])){
    $args = array('id', 'bidder_id', 'email', 'bid_amount','done_by', 'notes');
    foreach ($args as $arg) {
        $$arg = mysql_real_escape_string($_POST[$arg]);
    }
    mysql_unbuffered_query("INSERT INTO `".BIDS."` (`id`, `bidder_id`, `email`, `worklist_id`, `bid_amount`, `bid_created`, `bid_done`, `notes`) 
                            VALUES (NULL, '$bidder_id', '$email', '$id', '$bid_amount', NOW(), '".date("Y-m-d", strtotime($done_by))."', '$notes')");

    //sending email to the owner of worklist item
    $rt = mysql_query("SELECT `username`, `summary` FROM `users`, `worklist` WHERE `worklist`.`creator_id` = `users`.`id` AND `worklist`.`id` = ".$id);
    $row = mysql_fetch_assoc($rt);
    $subject = "new bid: ".$row['summary'];
    $body =  "<p>New bid was placed for worklist item \"".$row['summary']."\"<br/>";
    $body .= "Details of the bid:<br/>";
    $body .= "Bidder Email: ".$email."<br/>";
    $body .= "Done By: ".$done_by."<br/>";
    $body .= "Bid Amount: ".$bid_amount."<br/>";
    $body .= "Notes: ".$notes."</p>";
    $body .= "<p>Love,<br/>Worklist</p>";
    sl_send_email($row['username'], $subject, $body);
}

//accepting a bid
if (isset($_POST['accept_bid'])){
    $new_user = false;
    $bid_id = intval($_POST['bid_id']);
    $res = mysql_query('SELECT * FROM `'.BIDS.'` WHERE `id`='.$bid_id);
    $bid_info = mysql_fetch_assoc($res);
    //it's not a guest  
    if($bid_info['bidder_id'] != 0){
      $bidder_id = $bid_info['bidder_id']; 
    }else{
      //first check if the user exists under different id (maybe he just bidded as a guest but already has an account) or created account after bidding
      $res = mysql_query("SELECT `id` FROM `".USERS."` WHERE `username`='".$bid_info['email']."'");
      if($user_info = mysql_fetch_assoc($res)){
      $bidder_id = $user_info['id'];
      }else{
        //user does not exist so we have to create him with random password
        $nickname = explode("@", $bid_info['email']);
        $usernew = simpleCreateUser($bid_info['email'], $nickname[0]);
        $password = $usernew['password'];
        $bidder_id = $usernew['user_id'];
        $new_user = true;
      }
    }

//changing owner of the job
    mysql_unbuffered_query("UPDATE `worklist` SET `owner_id` =  '$bidder_id', `status` = 'WORKING' WHERE `worklist`.`id` = ".$bid_info['worklist_id']);

//adding bid amount to list of fees
    mysql_unbuffered_query("INSERT INTO `".FEES."` (`id`, `worklist_id`, `amount`, `user_id`, `desc`, `date`, `paid`) VALUES (NULL, ".$bid_info['worklist_id'].", '".$bid_info['bid_amount']."', '$bidder_id', 'Accepted Bid', NOW(), '0')");

    //sending email to the bidder 
    $rt = mysql_query("SELECT `nickname`, `summary` FROM `users`, `worklist` WHERE `worklist`.`creator_id` = `users`.`id` AND `worklist`.`id` = ".$bid_info['worklist_id']);
    $row = mysql_fetch_assoc($rt);
    $subject = "bid accepted: ".$row['summary'];
    $body = "Promised by: ".$row['nickname']."</p>";

    if($new_user){
    $body .= "<p>New account was created for you. You can login using details below:</p>";
    $body .= "<p>Username: ".$bid_info['email']."<br/>Password: ".$password."</p>";    
    }

    $body .= "<p>Love,<br/>Worklist</p>";
    sl_send_email($bid_info['email'], $subject, $body);
}

//adding fee to fees table
if (isset($_POST['add_fee']) && isset($_SESSION['userid'])){
    $args = array('id', 'fee_amount', 'fee_desc');
    foreach ($args as $arg) {
        $$arg = mysql_real_escape_string($_POST[$arg]);
    }
  mysql_unbuffered_query("INSERT INTO `".FEES."` (`id`, `worklist_id`, `amount`, `user_id`, `desc`, `date`, `paid`) VALUES (NULL, '$id', '$fee_amount', ".$_SESSION['userid'].", '$fee_desc', NOW(), '0')");
}

$userid = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";
$rt = mysql_query("SELECT `id`, `nickname` FROM `users` WHERE `id`!='$userid' and `confirm`='1'");
$users = array();
while ($row = mysql_fetch_assoc($rt)) {
    if (!empty($row['nickname'])) {
        $users[$row['id']] = $row['nickname'];
    }
}


/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<link href="css/datepicker.css" rel="stylesheet" type="text/css" >
<link type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/datepicker.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript">
    var refresh = <?php echo AJAX_REFRESH ?> * 1000;
    var lastId;
    var page = <?php echo $page ?>;
    var topIsOdd = true;
    var timeoutId;
    var addedRows = 0;
    var workitem = 0;
    var workitems;
    var user_id = <?php echo isset($_SESSION['userid']) ? $_SESSION['userid'] : '"nada"' ?>;

    function AppendPagination(page, cPages, table)
    {
        var pagination = '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table + '-pagination-row" ><td colspan="5" style="text-align:center;">Pages : &nbsp;';
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

    // json row fields: id, summary, status, owner nickname, owner username, delta
    function AppendRow(json, odd, prepend, moreJson, idx)
    {
        var pre = '', post = '';
        var row;

        row = '<tr id="workitem-' + json[0] + '" class="row-worklist-live ';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += '">';
        if (prepend) { pre = '<div class="slideDown" style="display:none">'; post = '</div>'; }
        row += '<td width="50%">' + pre + json[1] + post + '</td>';
        //if the status is BIDDING - add link to show bidding popup
        if (json[2] == 'BIDDING'){
            pre = '<a href="#" class = "bidding-link" id = "workitem-' + json[0] + '" >';
            post = '</a>';
        }
        row += '<td width="10%">' + pre + json[2] + post + '</td>';
        pre = '';
        post = '';
        if (json[3] != '') {
            row += '<td width="15%" class="toolparent">' + pre + json[3] + post + '<span class="tooltip">' + json[4] + '</span>' + '</td>';
        } else {
            row += '<td width="15%">' + pre + json[3] + post + '</td>';
        }
        row += '<td width="15%">' + pre + RelativeTime(json[5]) + post + '</td>';
	var feebids = 0;
	if(json[6]){
	  feebids = json[6];
	}
	var bid = 0;
	if(json[7]){
	  bid = json[7];
	}
	if(json[2] == 'BIDDING'){
	  feebids = parseFloat(feebids) + parseFloat(bid);  
	}

	row += '<td width="10%">' + pre + '$' + feebids + post + '</td></tr>';

        if (prepend) {
            $(row).prependTo('.table-worklist tbody').find('td div.slideDown').fadeIn(500);
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

    function Change(obj, evt)
    {
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

    function SelectWorkItem(item) {
        if (workitem > 0) $('#workitem-'+workitem).removeClass('workitem-selected');
        var match = item.attr('id').match(/workitem-\d+/);
        if (match) {
            workitem = match[0].substr(9);
            $('#workitem-'+workitem).addClass('workitem-selected');
        } else {
            workitem = 0;
        }
        if (workitem != 0) {
            $("#edit,#delete").attr('disabled', '');
        } else {
            $("#edit,#delete").attr('disabled', 'disabled');
        }
    }

    function GetWorklist(npage, update) {
    $.ajax({
        type: "POST",
        url: 'getworklist.php',
        data: 'page='+npage+'&sfilter='+$("#search-filter").val()+'&ufilter='+$("#user-filter").val(),
        dataType: 'json',
        success: function(json) {
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

            /* Disabled: ToolTip(); */

            $('.bidding-link').click(function(e){
                var worklist_id = $(this).attr('id').substr(9);
                ResetPopup();
                GetBidlist(worklist_id, 1);
                $('#popup-bid form input[name="id"]').val(worklist_id);
                $('#popup-bid form input[name="email"]').val('<?php echo (isset($_SESSION['username'])) ? $_SESSION['username'] : ''; ?>');
                $('#popup-bid form input[name="nickname"]').val('<?php echo (isset($_SESSION['nickname'])) ? $_SESSION['nickname'] : ''; ?>');
		$('#popup-bid').dialog('open');
            });

            $('.worklist-pagination-row a').click(function(e){
                page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
                if (timeoutId) clearTimeout(timeoutId);
                GetWorklist(page, false);
                e.stopPropagation();
                return false;
            });

            $('tr.row-worklist-live').click(function(){
                SelectWorkItem($(this));
                return false;
            });

            $('tr.row-worklist-live').dblclick(function(){
                $('#popup-edit form input[name="id"]').val(workitem);
                ClearSelection();
                <?php if (isset($_SESSION['userid'])) { ?>
                $('.popup-title').text('Edit Worklist Item');
                <?php } else { ?>
                $('.popup-title').text('View Worklist Item');
                <?php } ?>
                PopulatePopup(workitem);
		$('#popup-edit').data('title.dialog', 'Edit Worklist Item');
		$('#popup-edit').dialog('open');
            });

            var startIdx;
            $('.table-worklist').tableDnD({
                onDragStart: function(table, row) {
                    row = $(row);
                    startIdx = row.parent().children().index(row);
                    SelectWorkItem(row);
                },
                <?php if (isset($_SESSION['userid'])) {?>
                onDrop: function(table, row) {
                    row = $(row);
                    var worklist_id = row.attr('id').substr(9);
                    var prev_id = 0;
                    if (row.prev().attr('id')) prev_id = row.prev().attr('id').substr(9);
                    var bump = (startIdx - row.parent().children().index(row));
                    if (bump != 0) {
                        $.ajax({
                            type: "POST",
                            url: 'updatepriority.php',
                            data: 'id='+worklist_id+'&previd='+prev_id+'&bump='+bump,
                            success: function(json) {
                           }
                        });
                    }
                }
                <?php } ?>
            });

            if (workitem > 0) {
                var el = $('#workitem-'+workitem);
                if (el.length > 0) {
                    el.addClass('workitem-selected');
                } else {
                    workitem = 0;
                }
            }
        },
        error: function(xhdr, status, err) {
            $('.row-worklist-live').remove();
            $('.table-worklist').append('<tr class="row-worklist-live rowodd"><td colspan="5" align="center">Oops! We couldn\'t find any work items.  <a id="again" href="#">Please try again.</a></td></tr>');
            $('#again').click(function(){
                if (timeoutId) clearTimeout(timeoutId);
                GetWorklist(page, false);
                e.stopPropagation();
                return false;
            });
        }
    });

    timeoutId = setTimeout("GetWorklist("+page+", true)", refresh);
    }

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

    function PopulatePopup(item) {
        $.ajax({
            type: "POST",
            url: 'getworkitem.php',
            data: 'item='+item,
            dataType: 'json',
            success: function(json) {
                $('.popup-body form input[name="id"]').val(item);
                $('.popup-body form input[name="summary"]').val(json[0]);
                $('.popup-body form input[name="owner"]').val(json[1]);
                $('.popup-body form select[name="status"] option[value="'+json[2]+'"]').attr('selected','selected');
                $('.popup-body form textarea[name="notes"]').val(json[3]);
		$('#fees_block').show();
		GetFeelist(item);
            },
            error: function(xhdr, status, err) {
            }
        });
    }

    function ResetPopup() {
        $('.popup-body form input[type="text"]').val('');
	$('.popup-body form input[name="owner"]').val('<?php echo (isset($_SESSION['nickname'])) ? $_SESSION['nickname'] : ''; ?>');
        $('.popup-body form select option[index=0]').attr('selected', 'selected');
        $('.popup-body form textarea').val('');
    }
    

//Most of the js code for implementing bidding capability starts here
    function GetBidlist(worklist_id, npage) {
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
              var row = '<tr bgcolor="#FFFFFF" class="row-bidlist-live bidlist-pagination-row" >\
                          <td colspan="4" style="text-align:center;">No bids yet.</td></tr>';
              $('.table-bidlist tbody').append(row);
              return;
            } 

            /* Output the bidlist rows. */
            var odd = topIsOdd;
            for (var i = 1; i < json.length; i++) {
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
              PopulateBidInfoPopup(bid_id);
              $('#popup-bid-info form input[name="bid_id"]').val(bid_id);
	      $('#popup-bid-info').dialog('open');
            });

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
    function AppendBidRow(json, odd)
    {
        var pre = '', post = '';
        var row;
        row = '<tr class="row-bidlist-live ';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += ' biditem-' + json[0] + '">';
        row += '<td width="30%">' + pre + json[2] + post + '</td>';
        row += '<td width="20%">' + pre + json[4] + post + '</td>';
        row += '<td width="20%">' + pre + json[9] + post + '</td>';
        row += '<td width="20%">' + pre + RelativeTime(json[8]) + post + '</td></tr>';
       $('.table-bidlist tbody').append(row);
    }

    function PopulateBidInfoPopup(item) {
        $.ajax({
            type: "POST",
            url: 'getbiditem.php',
            data: 'item='+item,
            dataType: 'json',
            success: function(json) {
                $('#popup-bid-info form input[name="id"]').val(item);
                $('#popup-bid-info #info-email').text(json[2]);
                $('#popup-bid-info #info-bid-amount').text(json[4]);
                $('#popup-bid-info #info-bid-done-by').text(json[9]);
                $('#popup-bid-info #info-notes').text(json[7]);
		
                if(json[8] == user_id){
                  //adding "Accept" button
                  $('#popup-bid-info form').append('<input type="submit" name="accept_bid" value="Accept">');
                }
            },
            error: function(xhdr, status, err) {
            }
        });
    }
    
    function ResetBidInfoPopup(){
      $('#popup-bid-info form input[type="submit"]').remove();
    }
//end of code for bidding table

//code for fees table
    function GetFeelist(worklist_id) {
    $.ajax({
        type: "POST",
        url: 'getfeelist.php',
        data: 'worklist_id=' + worklist_id,
        dataType: 'json',
        success: function(json) {

            $('.row-feelist-live').remove();
            feeitems = json;
            if (!json[1]){
              var row = '<tr bgcolor="#FFFFFF" class="row-feelist-live feelist-total-row" >\
                          <td colspan="5" style="text-align:center;">No fees yet.</td></tr>';
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
              var row = '<tr bgcolor="#FFFFFF" class="row-feelist-live feelist-total-row" >\
                          <td colspan="5" style="text-align:center;">Total Fees $' + json[0][0] + '</td></tr>';
              $('.table-feelist tbody').append(row);


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

    function AppendFeeRow(json, odd)
    {
        var pre = '', post = '';
        var row;
        row = '<tr class="row-feelist-live ';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += ' feeitem-' + json[0] + '">';
        row += '<td>' + pre + json[2] + post + '</td>';
        row += '<td>' + pre + json[1] + post + '</td>';
        row += '<td>' + pre + json[3] + post + '</td>';
        row += '<td>' + pre + json[4] + post + '</td>';
	if(json[5] == 0){
	  var paid = 'No'
	}else{
	  var paid = 'Yes';
	}
        row += '<td>' + pre + paid + post + '</td></tr>';
       $('.table-feelist tbody').append(row);
    }

//end of code for fees table

    $(document).ready(function(){
	$('#popup-edit').dialog({ autoOpen: false, maxWidth: 600, width: 400 });
	$('#popup-delete').dialog({ autoOpen: false});
	$('#popup-bid').dialog({ autoOpen: false, maxWidth: 600, width: 450 });
	$('#popup-bid-info').dialog({ autoOpen: false, modal: true});
	$('#popup-addfee').dialog({ autoOpen: false, modal: true, width: 400});
        GetWorklist(<?php echo $page?>, false);    

        $("#owner").autocomplete('getusers.php', { cacheLength: 1, max: 8 } );
        $("#search-filter, #user-filter").change(function(){
            page = 1;
            if (timeoutId) clearTimeout(timeoutId);
            GetWorklist(page, false);
        });

        $('#add').click(function(){
	    $('#popup-edit').data('title.dialog', 'Add Worklist Item');
            ResetPopup();
	    $('#fees_block').hide();
	    $('#popup-edit').dialog('open');
        });
        $('#edit').click(function(){
            $('#popup-edit form input[name="id"]').val(workitem);
            PopulatePopup(workitem);
	    $('#popup-edit').data('title.dialog', 'Edit Worklist Item');
	    $('#popup-edit').dialog('open');
        });
        $('#delete').click(function(){
            var summary = '(No summary)';
            for (i = 1; i <= workitems[0][0]; i++) {
                if (workitems[i][0] == workitem) {
                    summary = workitems[i][1];
                    break;
                }
            }
            $('#popup-delete form input[name="id"]').val(workitem);
            $('.popup-delete-summary').text('"'+summary+'"');
	    $('#popup-delete').dialog('open');
        });

        $('.popup-body form input[type="submit"]').click(function(){
            var name = $(this).attr('name');

            $(".popup-page-value").val(page);
	    
	    switch(name){
	      case "add_fee_dialog": 
		$('#popup-addfee').dialog('open');
		return false;
		break;
	      case "reset":
                ResetPopup();
                return false;
		break;
	      case "cancel":
		$('#popup-delete').dialog('close');
		$('#popup-edit').dialog('close');
                return false;
		break;
	    
	    }
        });
    });
</script> 

<title>Worklist | Lend a Hand</title>

</head>

<body>
    <div id="popup-edit" title = "Add Worklist Item" class = "popup-body">
            <form name="popup-form" action="" method="post">
                <input type="hidden" name="id" value="0" />
                <input type="hidden" name="page" value="<?php echo $page ?>" class="popup-page-value" />

                <p><label>Summary<br />
                <input type="text" name="summary" class="text-field" size="48" />
                </label></p>

                <p><label>Runner<br />
                <input type="text" id="owner" name="owner" class="text-field" size="48" />
                </label></p>
      
                <p><label>Status<br />
                <select name="status">
                    <option value="BIDDING" selected = "selected" >BIDDING</option>
                    <option value="WORKING">WORKING</option>
                    <option value="SKIP">SKIP</option>
                    <option value="DONE">DONE</option>
                </select>
                </label></p>
    
                <p><label>Notes<br />
                <textarea name="notes" size="48" /></textarea>
                </label></p>
		<div id = "fees_block">
		  Fees
		  <table width="100%" class="table-feelist">
		      <thead>
		      <tr class="table-hdng" >
			  <td>Who</td>
			  <td>Amount</td>
			  <td>Description</td>
			  <td>Date</td>
			  <td>Paid</td>
		      </tr>
		      </thead>
		      <tbody>
		      </tbody>
		  </table><br />    
                <?php if (isset($_SESSION['userid'])) { ?>
		  <p>
		    <input type="submit" name="add_fee_dialog" value="Add Fee">
		  </p>
		</div><!-- end of fees_block -->
                <input type="submit" name="save" value="Save">
                <input type="submit" name="reset" value="Reset">
                <input type="submit" name="cancel" value="Cancel">
                <?php } else { ?>
		</div><!-- end of fees_block -->
                <input type="submit" name="cancel" value="Close">
                <?php } ?>
            </form>
        </div>

    <div id="popup-delete" class="popup-body" title = "Delete Worklist Item">
            <form name="popup-form" action="" method="post">
                <input type="hidden" name="id" value="" />
                <input type="hidden" name="page" value="<?php echo $page ?>" class="popup-page-value" />

                <p class="popup-delete-summary"></p>
                <p>Are you sure you want to delete this work item?</p>
    
                <input type="submit" name="delete" value="Yes">
                <input type="submit" name="cancel" value="No">
            </form>
    </div>
    <!-- Popup for placing a bid -->
    <div id="popup-bid" class="popup-body" title = "Place Bid">
            <table width="100%" class="table-bidlist">
                <thead>
                <tr class="table-hdng" >
                    <td>Email</td>
                    <td>Bid Amount</td>
                    <td>Done By</td>
                    <td>Age</td>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table><br />
            <form name="popup-form" action="" method="post">
                <input type="hidden" name="id" value="" />
                <input type="hidden" name="bidder_id" value="<?php echo (isset($_SESSION['userid'])) ? $_SESSION['userid'] : 0; ?>" />

                <p><label>Your Email<br />
                <input type="text" name="email" class="text-field" size="48" />
                </label></p>
    
                <p><label>Bid Amount<br />
                <input type="text" name="bid_amount" class="text-field money" size="48" />
                </label></p>
    
                <p><label>Done By<br />
                  <input type="text" class="text-field date" name="done_by" value="" size="20" />
                  <img src="images/Calendar.gif" class="dpButtonCal" onClick="displayDatePicker('done_by', false, 'mdy', '/');" />
                  <img src="images/transparent.gif" width="30px" height="1" />
                </label></p>

                <p><label>Notes<br />
                <textarea name="notes" size="48" /></textarea>
                </label></p>
    
                <input type="submit" name="bid" value="Place Bid">
            </form>
    </div><!-- end of popup-bid -->

    <!-- Popup for bid info-->
    <div id="popup-bid-info" class="popup-body" title = "Bid Info">
            <p class = "info-label">Email<br />
            <span id="info-email"></span>
            </p>

            <p class = "info-label">Bid Amount<br />
            <span id="info-bid-amount"></span>
            </p>

            <p class = "info-label">Done By<br />
            <span id="info-bid-done-by"></span>
            </p>

            <p class = "info-label">Notes<br />
            <span id="info-notes"></span>
            </p>

            <form name = "popup-form" id = "popup-form" action="" method="post">
                <input type="hidden" name="bid_id" value="" />
            </form>
    </div><!-- end of popup-bid-info -->

    <!-- Popup for adding fee-->
    <div id="popup-addfee" class="popup-body" title = "Add Fee">
            <form name="popup-form" action="" method="post">
                <input type="hidden" name="id" value="" />

                <p><label>Amount<br />
		  <input type="text" name="fee_amount" class="text-field money" size="48" />
                </label></p>

                <p><label>Description<br />
		  <input type="text" name="fee_desc" class="text-field" size="48" />
                </label></p>

		<input type="submit" name="add_fee" value="Add Fee">
            </form>
    </div><!-- end of popup-addfee -->

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

    <?php if (isset($_SESSION['userid'])) { ?>
    <div id="buttons">
        <p>
            <input type="submit" id="add" name="add" value="Add">
            <input type="submit" id="edit" name="edit" value="Edit" disabled>
            <input type="submit" id="delete" name="delete" value="Delete" disabled>
        </p>
    </div>
    <?php } ?>
            
    <div id="search-filter-wrap">
        <p>
             <select name="ufilter" id="user-filter">
                <option value="ALL">ALL USERS</option>
<?php 
    if(isset($_SESSION['userid'])){
	echo '
  <option value="'.$_SESSION['userid'].'">'.$_SESSION['nickname'].'</option>

';
    } 
?>
                <?php foreach ($users as $user_id=>$nickname) { ?>
                <option value="<?php echo $user_id ?>"><?php echo $nickname ?></option>
                <?php } ?>
            </select>
            <select name="sfilter" id="search-filter">
                <option value="WORKING/BIDDING">WORKING/BIDDING</option>
                <option value="ALL">ALL</option>
                <option value="SKIP">SKIP</option>
                <option value="DONE">DONE</option>
            </select>
        </p>
    </div>

    <div style="clear:both"></div>

    <table width="100%" class="table-worklist">
        <thead>
        <tr class="table-hdng">
            <td>Summary</td>
            <td>Status</td>
            <td>Who</td>
            <td>Age</td>
            <td>Fees/Bids</td>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
      
<?php include("footer.php"); ?>
