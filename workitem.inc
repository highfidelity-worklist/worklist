<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

    $user_id = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";
    $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
    $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;
    $creator_id = isset($worklist['creator_id']) ? $worklist['creator_id'] : 0;
    $mechanic_id = isset($worklist['mechanic_id']) ? $worklist['mechanic_id'] : 0;
    
    $has_budget = 0;
    if (! empty($user_id)) {
        $user = new User();
        $user->findUserById($user_id);
        if ($user->getBudget() > 0) {
            $has_budget = 1;
        }
    }

    $workitem = WorkItem::getById($worklist['id']);
    $projects = Project::getProjects();

    $allowEdit = false;
    $classEditable = "";
    if ($is_runner || ($creator_id == $user_id && $worklist['status'] == 'SUGGESTED' && is_null($worklist['runner_id'])) ||
    ($creator_id == $user_id && $worklist['status'] == 'SUGGESTEDwithBID' && is_null($worklist['runner_id']))) {
        $allowEdit = true;
        if ($action !="edit") {
            $classEditable = " editable";
        }
    }
    $hideFees = false;
    if ($worklist['status'] == 'BIDDING' || $worklist['status'] == 'SUGGESTED' || $worklist['status'] == 'SUGGESTEDwithBID') {
        $hideFees = true;
    }

    if ($worklist['status'] == 'DRAFT' && $creator_id != $user_id) { ?>
       <script type="text/javascript"> 
       alert("Draft jobs are only viewable by their creator.");
       location = "worklist.php";
       </script> 
   <?php  
    return;
    }

/*********************************** HTML layout begins here  *************************************/
include("head.html"); ?>
<link type="text/css" href="css/workitem.css" rel="stylesheet" />
<link type="text/css" href="css/review.css" rel="stylesheet" />
<link type="text/css" href="css/favorites.css" rel="stylesheet" />
<link type="text/css" href="css/userinfo.css" rel="stylesheet" />
<link type="text/css" href="css/jquery.combobox.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/datepicker.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/workitem.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="js/jquery.tallest.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/jquery.autogrow.js"></script>
<script type="text/javascript" src="js/jquery.blockUI.js"></script>
<script type="text/javascript" src="js/review.js"></script>
<script type="text/javascript" src="js/favorites.js"></script>
<script type="text/javascript">
<!-- Start User Info popup code -->
function resizeIframeDlg() {
    var bonus_h = $('#user-info').children().contents().find('#pay-bonus').is(':visible') ?
                  $('#user-info').children().contents().find('#pay-bonus').closest('.ui-dialog').height() : 0;

    var dlg_h = $('#user-info').children()
                               .contents()
                               .find('html body')
                               .height();

    var height = bonus_h > dlg_h ? bonus_h+35 : dlg_h+30;

    $('#user-info').animate({height: height});
}

$(document).ready(function() {
    applyPopupBehavior();
    $('#user-info').dialog({
        autoOpen: false,
        resizable: false,
        modal: false,
        show: 'fade',
        hide: 'fade',
        width: 840,
        height: 480
    });
    $('#sent-notify').dialog({
        modal: false,
        autoOpen: false,
        width: 250,
        height: 60,
        position: ['middle'],
        resizable: false,
        open: function() {
            $("#sent-notify").parent().children('.ui-dialog-titlebar').hide();
            setTimeout(function() {
                $("#sent-notify").dialog("close");
            }, 3000);
        }
    });

    <?php
    
    global $displayDialogAfterDone;

    if ($displayDialogAfterDone == true && $worklist['mechanic_id'] > 0) {
        $_SESSION['displayDialogAfterDone'] = false;
        $row = $workitem->getUserDetails($worklist['mechanic_id']);
        if (! empty($row)) {
            $nickname = $row['nickname'];
            echo "WReview.displayInPopup({user_id: " . $worklist['mechanic_id'] . ", nickname:'" . $row['nickname'] . "', withTrust: true});";
        }
    }
    ?>

    <?php if ($user_id > 0) { ?>
    $.get('getskills.php', function(data) {
        var skills = eval(data);
        $("#skills").autocomplete(skills, {
            width: 320,
            max: 10,
            highlight: false,
            multiple: true,
            multipleSeparator: ", ",
            scroll: true,
            scrollHeight: 300
        });
    });
    <?php } ?>
    makeWorkitemTooltip(".worklist-item");

    $('#workitem-form').submit(function() {
        return saveWorkitem();
    });
});

function showUserInfo(userId){
    $('#user-info').html('<iframe id="modalIframeId" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" />').dialog('open');
    $('#modalIframeId').attr('src','userinfo.php?id=' + userId);
    return false;
}
<!-- End User Info popup code -->
</script>
<script type="text/html" id="uploadedFiles">
<div id="accordion">
<?php require('dialogs/file-accordion.inc'); ?>
</div>
<?php if ($worklist['status'] != 'DONE' && (int) $user_id > 0) : ?>
<div class="fileUploadButton">
    Attach new files
</div>
<div class="uploadnotice"></div>
<?php endif; ?>
</script>
<!-- js template for file uploads -->
<?php require_once('dialogs/file-templates.inc'); ?>

<?php if ($user_id > 0) { // only logged in users can upload files ?>
<script type="text/javascript" src="js/uploadFiles.js"></script>
<?php } ?>

<script type="text/javascript">
var filterName = '.worklist';
var user_id = <?php echo !empty($user_id) ? $user_id : "''"; ?>;
var workitem_id = <?php echo $worklist['id'];?> ;
var imageArray = new Array();
var documentsArray = new Array();
var ping_who = '';
worklistUrl = '<?php echo SERVER_URL; ?>';
Workitem.sandbox_url = '<?php echo $worklist['sandbox']; ?>';

function reply(id) {
    var commentForm = $('.commentform');
    var clone = commentForm.clone();
    commentForm.remove();
    clone.insertAfter($('#comment-' + id));
    commentMargin = $('#comment-' + id).css('margin-left');
    leftMargin= (20 + parseInt(commentMargin)) + "px";
    clone.css({'margin-left':leftMargin});
    $('.commentform input[name=comment_id]').val(id);
    runDisableable();
}

    var getSliderValueFromText = function(val) {
        switch (val) {
            case '1 hour':
                return 0;
                break;
            case '2 hours':
                return 1;
                break;
            case '4 hours':
                return 2;
                break;
            case '8 hours':
                return 3;
                break;
            case '1 day':
                return 4;
                break;
            case '2 days':
                return 5;
                break;
            case '3 days':
                return 6;
                break;
            case '4 days':
                return 7;
                break;
            case '5 days':
                return 8;
                break;
            case '6 days':
                return 9;
                break;
            case '7 days':
                return 10;
                break;
            default:
                return 11;
        }
        return false;
    }

$(document).ready(function(){

    $('#bidDoneSlider').slider({
        value: 1,
        min: 0,
        max: 10,
        step: 1,
        slide: function(event, ui) {
            $('.sliderStepValue', $(this)).remove();
            $('.sliderStep', $(this)).eq(ui.value).html('<div class="sliderStepValue">' + getTextFromSliderValue(ui.value) + '</div>');
            $('#done_in').val(getTextFromSliderValue(ui.value));
        }
    });

    $('#bidExpireSlider').slider({
        value: 10,
        min: 0,
        max: 10,
        step: 1,
        slide: function(event, ui) {
            $('.sliderStepValue', $(this)).remove();
            $('.sliderStep', $(this)).eq(ui.value).html('<div class="sliderStepValue">' + getTextFromSliderValue(ui.value) + '</div>');
            $('#bid_expires').val(getTextFromSliderValue(ui.value));
        }
    });
    
    $('.sliderStepValue', '#bidDoneSlider').remove();
    $('.sliderStep', '#bidDoneSlider').eq(1).html('<div class="sliderStepValue">' + '2 hours' + '</div>');
    $('.sliderStepValue', '#bidExpireSlider').remove();
    $('.sliderStep', '#bidExpireSlider').eq(10).html('<div class="sliderStepValue">' + '7 days' + '</div>');

    $('#bidExpireEditSlider').slider({
        value: getSliderValueFromText($('#bid_expires_edit').val()),
        min: 0,
        max: 10,
        step: 1,
        slide: function(event, ui) {
            $('.sliderStepValue', $(this)).remove();
            $('.sliderStep', $(this)).eq(ui.value).html('<div class="sliderStepValue">' + getTextFromSliderValue(ui.value) + '</div>');
            $('#bid_expires_edit').val(getTextFromSliderValue(ui.value));
        }
    });

    $('#bidDoneEditSlider').slider({
        value: 1,
        min: 0,
        max: 10,
        step: 1,
        slide: function(event, ui) {
            $('.sliderStepValue', $(this)).remove();
            $('.sliderStep', $(this)).eq(ui.value).html('<div class="sliderStepValue">' + getTextFromSliderValue(ui.value) + '</div>');
            $('#done_in_edit').val(getTextFromSliderValue(ui.value));
        }
    });

    var getTextFromSliderValue = function(val) {
        var sRet="2 hours";
        switch (val) {
            case 0:
                sRet  = "1 hour";
                break;
            case 1:
                sRet = "2 hours";
                break;
            case 2:
                sRet = "4 hours";
                break;
            case 3:
                sRet = "8 hours";
                break;
            case 4:
                sRet = "1 day";
                break;
            case 5:
                sRet = "2 days";
                break;
            case 6:
                sRet = "3 days";
                break;
            case 7:
                sRet = "4 days";
                break;
            case 8:
                sRet = "5 days";
                break;
            case 9:
                sRet = "6 days";
                break;
            case 10:
                sRet = "7 days";
                break;
        }
        return sRet;
    };

    // default dialog options
    var dialog_options = { autoOpen: false, modal: true, maxWidth: 600, width: 450, show: 'fade', hide: 'fade' };
    $('#popup-bid').dialog(dialog_options);
    $('#popup-edit-bid-info').dialog(dialog_options);
    $('#popup-confirmation').dialog(dialog_options);
    $('#popup-ineligible').dialog({
        modal: true,
        title: "Your account is ineligible",
        autoOpen: false,
        width: 300,
        position: ['top'],
        open: function() {
            $('#button_settings').click(function() {
                document.location.href = 'settings.php#payment-info';
            });
        }
    });

    // If the bid info popup is set to modal clipboard won't work!
    $('#popup-bid-info').dialog({ autoOpen: false, modal: false, width: 400, height: 500, show: 'fade', hide: 'fade' });
    $('#popup-multiple-bid-info').dialog({ autoOpen: false, modal: true, width: 750, position: ['center', 160], show: 'fade', hide: 'fade' });
    $('#popup-addfee').dialog({ autoOpen: false, modal: true, width: 450, height: 230, show: 'fade', hide: 'fade' });
    $('#popup-startreview').dialog({ 
        closeOnEscape: false, 
        autoOpen: false, 
        modal: false, 
        width: 400, 
        show: 'fade', 
        hide: 'fade',
        open: function(event, ui) { 
            $(".ui-dialog-titlebar-close").hide(); 
        }
    });    
    $('#popup-paid').dialog({ autoOpen: false, maxWidth: 600, width: 450, show: 'fade', hide: 'fade' });
    $('#message').dialog({ autoOpen: true, show: 'fade', hide: 'fade' });
    $('#popup-pingtask').dialog({
        autoOpen: false,
        width: 400,
        position: [ 'top' ],
        show: 'fade',
        hide: 'fade',
        open: function() {
            $('#ping-msg').autogrow();
        },
        close: function() {
            $('#ping-msg').autogrow();
            $('#ping-msg').val('');
        }
    });
    $('#popup-reviewurl').dialog({ autoOpen: false, modal: true, width: 410, show: 'fade', hide: 'fade', resizable: false });
    $('#popup-withdraw-bid').dialog({ autoOpen: false, width: 420, show: 'fade', hide: 'fade' });
<?php if ($mechanic_id == $user_id): ?>
    $('#popup-addtip').dialog({ autoOpen: false, modal: true, width: 400, height: 250, show: 'fade', hide: 'fade' });
    $('.addTip').click(function() {
        $('#popup-addtip').dialog('open');
    });
<?php endif; ?>

    // JS Variables initialized from host PHP page
    var workitem_id = <?php echo $worklist['id'];?>;
    var already_bid = <?php echo $currentUserHasBid ;?>;
    var is_runner = <?php echo $is_runner;?>;
    var has_budget = <?php echo $has_budget;?>;
    var user_id = <?php echo !empty($user_id) ? $user_id : "''"; ?>;
    var isFollowing = <?php echo $workitem->isUserFollowing($user_id) ? 'true' : 'false';?>;

    $('#following').click(function() {
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                workitem: workitem_id,
                userid: user_id,
                action: 'ToggleFollowing'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    isFollowing = !isFollowing;
                    setFollowingText(isFollowing);
                }
            }
        });
    });

    (function($) {
        // journal info accordian
        // flag to say we've not loaded anything in there yet
        $.journalChat = false;
        $('.accordion').accordion({
            clearStyle: true,
            collapsible: true,
            active: true,
            change: function() {
                // we only need to get this once
                if(!$.journalChat) {
                    $.ajax({
                        // use journal's aj.php to query results on worklist id
                        type: 'post',
                        url: '<?php echo JOURNAL_QUERY_URL;?>',
                        data: {
                            action: 'getTaskPosts',
                            count: 50,
                            query: <?php echo $worklist['id'];?>,
                            filter: 'all',
                            order: 'ASC',
                            api_key:'<?php echo  JOURNAL_API_KEY; ?>'
                        },
                        dataType: 'json',
                        success: function(json)
                        {
                            if (json.html) {
                                $('#journalChatThrobber').fadeOut();
                                $.journalChat = true;
                                $('#journalChat').hide(0).html(json.html).fadeIn();
                            }
                        }
                    });
                }
            },
        });
        $('.accordion').accordion( "activate" , 0 );
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
                    $('#uploadPanel').append(files);
                    $('#accordion').fileUpload({images: imageArray, documents: documentsArray});
                }
            }
        });
    <?php 
            if (! empty($user_id)) {
    ?>
        setFollowingText(isFollowing);
    <?php   } else {
    ?>  $('#followingLogin').html('<a href="login.php">Login</a> to follow this task.');
    <?php   }
    ?>
    })(jQuery);

    SimplePopup('#popup-bid',
            'Place Bid',
            workitem_id,
            [['input', 'itemid', 'keyId', 'eval']]);


        $('.popup-body form input[type="submit"]').click(function(){
      var name = $(this).attr('name');
//    $(".popup-page-value").val(page);
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
        if ($('#feeitem-' + fee_id).html() == "Yes") {
            $('#paid_check').attr('checked', "1");
        } else if ($('#feeitem-' + fee_id).html() == "No" ) {
            $('#notpaid_check').attr('checked', "1");
        }
    
        $('#paid_check').click(function() { 
               var $checkbox = $('#notpaid_check');
               $checkbox.attr('checked', !$checkbox.attr('checked'));
        });
        $('#notpaid_check').click(function() {
               var $checkbox = $('#paid_check'); 
               $checkbox.attr('checked', !$checkbox.attr('checked'));
        });
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
                        paid_check: $('#' + this.id + ' input[name=paid_check]').prop('checked') ? 1 : 0,
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

    $('tr.row-bidlist-live').click(function() {

        $.metadata.setType("elem", "script")
        var bidData = $(this).metadata();

        // row has bid data attached so user is a bidder or a runner
        // - see table creation routine
        if(bidData.id){
            $('#popup-bid-info form input[type="submit"]').remove();
            $('#popup-bid-info form input[type="button"]').remove();

            $('#popup-bid-info input[name="bid_id"]').val(bidData.id);
            $('#popup-bid-info #info-email').html('<a href="#" onClick="javascript:showUserInfo(' + bidData.bidder_id +');">' + bidData.nickname + '</a>');
            $('#popup-bid-info #info-bid-created').text(bidData.bid_created);
            $('#popup-bid-info #info-bid-accepted').text(bidData.bid_accepted);
            $('#popup-bid-info #info-bid-expires').text(bidData.bid_expires);
            $('#popup-bid-info #info-bid-amount').text(bidData.amount);
            $('#popup-bid-info #info-bid-done-in').text(bidData.done_in);
            $('#popup-bid-info #info-notes').html(bidData.notes);

<?php if ((isset($is_runner) && $is_runner) || (isset($runner_id) && $user_id == $runner_id)) : ?>
            if($('#accept_bid').length == 0) {
                $('#popup-bid-info form').append('<input class="disableable" id="accept_bid" type="submit" name="accept_bid" value="Accept">');
                runDisableable();
            }
<?php endif; ?>

            if((bidData.bidder_id == user_id)){
<?php if (!$workitem->hasAcceptedBids()) : ?>
                $('#popup-bid-info form').append('<input type="button" name="edit" id="edit" value="Edit" onClick="return ConfirmEditBid()"  style="padding-left:20px;padding-right:20px;">');
<?php endif; ?>
            }

<?php if ($worklist['status'] == 'BIDDING' && ((isset($is_runner) && $is_runner) || (isset($runner_id) && $user_id == $runner_id))) {?>
 
    $('#popup-bid-info form').append('<input id="ping_bidder" type="button" name="ping_bidder" value="Reply"  onClick="return pingBidder(' + bidData.id + ')" >');
 
<?php } ?>

<?php if($worklist['status'] != 'DONE' && $worklist['status'] != 'WORKING' && $worklist['status'] != 'FUNCTIONAL' && $worklist['status'] != 'REVIEW' && $worklist['status'] != 'COMPLETED'){?>
            if( (is_runner==1 || (bidData.bidder_id == user_id)) && $('#withdraw_bid_accept').length == 0){
                $('#popup-bid-info form').append('<input id="withdraw_bid_accept" type="button" name="withdraw_bid_accept" value="Withdraw" onClick="return showWithdrawBidReason()" >');
            }
<?php } ?>
            // change user id to current bidder id
            stats.setUserId(bidData.bidder_id);

            // filling and appending user stats table
            $('.loader').show();
            $.getJSON('getuserstats.php', {id: bidData.bidder_id, statstype: 'counts'}, function(json) {

                // filling the table from json stats
                $('#total-jobs').html(json.total_jobs );
                $('#active-jobs').html(json.active_jobs);
                $('#total-earnings').html(json.total_earnings);
                $('#latest_earnings').html(json.latest_earnings);
                $('#total-bonus').html(json.bonus_total);
                $('#percent_bonus').html(json.bonus_percent);
                $('.loader').hide();
            });

            $('#popup-bid-info').dialog('open');

        }

    });


<?php } ?>
        $('#bid').click(function(e){
            if ( already_bid
                && $(this).parent().find('#mechanic_id').val() == '<?php echo $user_id ?>'
                && !confirm("You have already placed a bid, do you want to place a new one?")) {
                $('#popup-bid').dialog('close');
                return false;
            }
        });

    $('select[name="quick-status"]').change(function() {
        if ($(this).val() == 'FUNCTIONAL') {
            <?php if($mechanic_id == $user_id && $promptForReviewUrl) :?>
                $('#sandbox-url').val('<?php echo $worklist['sandbox']?>');
                $('#quick-status-review').val($(this).val());
                $('#popup-reviewurl').dialog('open');
            <?php else : ?>
                $('#quick-status form').submit();
            <?php endif; ?>
        } else {
            $('#quick-status form').submit();
        }
    });

<?php if(($is_runner) || ($mechanic_id == $user_id) && ($worklist['status'] != 'DONE')){?>
        $('#edit_review_url').click(function(e){
                $('#sandbox-url').val('<?php echo $worklist['sandbox']?>');
                $('#popup-reviewurl').dialog('open');
        });
<?php } ?>
  });


    function ResetPopup() {
        $('#for_edit').show();
        $('#for_view').hide();
        $('.popup-body form input[type="text"]').val('');
        $('.popup-body form select option[index=0]').prop('selected', true);
        $('.popup-body form textarea').val('');
    }
    function ConfirmEditBid(){
        var bid_id = $('#popup-bid-info input[name="bid_id"]').val();

        $('#popup-bid-info').dialog('close');
        AjaxPopup('#popup-edit-bid-info',
            'Edit Bid',
            'getbiditem.php',
            bid_id,
            [
                ['input', 'bid_id', 'keyId', 'eval'],
                ['input', 'bid_amount', 'json.bid_amount', 'eval'],
                ['input', 'bid_expires_edit', 'json.bid_expires', 'eval'],
                ['input', 'done_in_edit', 'json.bid_done_in', 'eval'],
                ['textarea', 'notes', 'json.notes', 'eval']
            ],

            function(json) {
                // figure out expires
                var expireSeconds = json.unix_expires - json.now;
                var expireHours = Math.round(expireSeconds / 3600);
                var expireText = '';
                if (expireHours > 24 || expireHours > 12) {
                    expireText = Math.round(expireHours / 24) + ' days';
                } else if (expireHours > 6) {
                    expireText = '8 hours';
                } else if (expireHours >= 4) {
                    expireText = '4 hours';
                } else if (expireHours > 1) {
                    expireText = expireHours + ' hours';
                } else {
                    expireText = '1 hour';
                }

                if(expireHours <= 0) {
                    $('#bid_expires_edit').val('1 hour');
                }

                var expireValue = getSliderValueFromText(expireText);
                var doneInValue = getSliderValueFromText(json.bid_done_in);

                $('.sliderStepValue', '#bidExpireEditSlider').remove();
                $('.sliderStep', '#bidExpireEditSlider').eq(expireValue).html('<div class="sliderStepValue">' + expireText + '</div>');
                $('.sliderStepValue', '#bidDoneEditSlider').remove();
                $('.sliderStep', '#bidDoneEditSlider').eq(doneInValue).html('<div class="sliderStepValue">' + json.bid_done_in + '</div>');

                $('#bidExpireEditSlider').slider('value', expireValue);
                $('#bidDoneEditSlider').slider('value', doneInValue);

            }
        );
        $('#popup-edit-bid-info').dialog('open');
    }
  function showConfirmForm(i) {
      $('#popup-confirmation-type').val(i);
      $('#popup-confirmation').dialog('open');
      return false;
  }
  function showIneligible(problem) {
      $('#popup-ineligible').dialog('open');
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

  function showWithdrawBidReason() {
      var bid_id = $('#popup-bid-info input[name="bid_id"]').val();
      $('#popup-bid-info').dialog('close');

      SimplePopup('#popup-withdraw-bid',
                'Withdraw Bid',
                 bid_id,
                 [['input', 'bid_id', 'keyId', 'eval']]);


        $('#popup-withdraw-bid').dialog('open');
        return false;
  }

    function pingBidder(id) {
        ping_who = 'bidder';
        $('#popup-pingtask form p #echo-journal').parent('p').remove();
        $('#popup-pingtask form h5').html('Ping about Bid');
        $('#popup-pingtask form input[name="bidder"]').val(id);
        $('#popup-pingtask').dialog('open');
        $('#popup-pingtask').dialog('option', 'title', 'Ping about Bid');
        return false;
    }

  function showFeeForm() {
     $('#popup-addfee').dialog('open');
     return false;
  }

    function showReviewForm() {
        var workitem_id = <?php echo $worklist['id'];?>;
        var user_id = <?php echo !empty($user_id) ? $user_id : "''"; ?>;
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                workitem: workitem_id,
                userid: user_id,
                action:'startCodeReview'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#popup-startreview').dialog('open');
                }
            }
        });
        return false;
    }
    
    function showEndReviewForm() {       
        $('#popup-endreview').dialog({
            closeOnEscape: false,
            autoOpen: false,
            modal: false,
            width: 400,
            open: function(event, ui) {
                $(".ui-dialog-titlebar-close").hide();
            }         
        });                                
        $('#popup-endreview').dialog('open');
    }

    function saveWorkitem() {
        var massValidation;
        if($('#is_bug').is(':checked')) {
            var bugJobId = new LiveValidation('bug_job_id', {
                onlyOnSubmit: true ,
                onInvalid : function() {
                    this.insertMessage( this.createMessageSpan() ); this.addFieldClass();
                }
            });
            bugJobId.add( Validate.Custom, {
                against: function(value,args){
                    id = $('#bugJobSummary').attr('title');
                    return (id!=0)
                },
                failureMessage: "Invalid item Id"
            });
            massValidation = LiveValidation.massValidate([ bugJobId ]);
            if (! massValidation) {
                return false;
            }
        }
        var summary = new LiveValidation('summary', {
            onlyOnSubmit: true ,
            onInvalid : function() {
                this.insertMessage( this.createMessageSpan() ); this.addFieldClass();
            }
        });
        summary.add( Validate.Presence, {
            failureMessage: "Can't be empty!"
        });
        massValidation = LiveValidation.massValidate( [ summary ]);
        if (!massValidation) {
          return false;
        }

        var editProject = new LiveValidation('project_id', {
            onInvalid : function() {
                this.insertMessage( this.createMessageSpan() );
                this.addFieldClass();
            }
        });
        editProject.add( Validate.Exclusion, {
            within: [ 'Select project' ],
            partialMatch: true,
            failureMessage: "You have to choose a project!"
        });
        massValidation = LiveValidation.massValidate( [ editProject ]);
        if (! massValidation) {
            return false;
        }
    }

    function AcceptMultipleBidOpen(){
        var job_id = workitem_id;
        $.ajax({
            type: 'POST',
            url: 'getmultiplebidlist.php',
            data: "job_id=" + job_id,
            success:function(response) {
                $('#popup-multiple-bid-info').html(response);
                $("#popup-multiple-bid-info .chkMechanic").change(function() {
                    if (this.checked) {
                        // remove ticks
                        $('#popup-multiple-bid-info .chkMechanic').removeAttr('checked');
                        // and add it back
                        $(this).prop('checked', true);
                        // and auto accept
                        $(this).parent().parent().find('.acceptMechanic').prop('checked', true);
                    }
                });

                $('#popup-multiple-bid-info .acceptMechanic').change(function() {
                    if (this.checked) {
                    } else {
                        if ($(this).parent().parent().find('.chkMechanic').is(':checked')) {
                            $(this).parent().parent().find('.chkMechanic').removeAttr('checked');
                        }
                    }
                });

                $('#popup-multiple-bid-form').submit(function() {
                    if ($(this).find('input.chkMechanic:checked').length > 0) {
                        return true;
                    } else {
                        $('<div id="popup-mechanic-required"><div class="content"></div></div>').appendTo('body');
                        $('#popup-mechanic-required').dialog({
                            modal: true,
                            title: 'Failed to specify mechanic',
                            autoOpen: true,
                            width: 300,
                            position: ['top'],
                            open: function() {
                                $('#popup-mechanic-required .content').html('<p>You must pick which user will be the main mechanic for this task.</p><input class="closeButton" type="button" value="Close" />');
                                $('#popup-mechanic-required .closeButton').click(function() {
                                    $('#popup-mechanic-required').dialog('close');
                                });
                            }
                        });

                        return false;
                    }
                });
            }
        });
        $('#popup-multiple-bid-info').dialog('open');
    }

    $(function() {
        $.loggedin = <?php echo isset($_SESSION['userid']) ? "true" : "false" ?>;

        $(".editable").attr("title","Switch to Edit Mode").click(function() {
            window.location.href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?job_id=<?php echo $worklist['id'];?>&action=edit&order=<?php echo $order_by; ?>";
        });

        $(".info-comments-order").attr("title", "Reverse Comments Order").click(function() {
            <?php $new_location = $_SERVER['SCRIPT_NAME'] . "?job_id=" . $worklist['id'] . "&action=" . $action . "&order=";
            if ($order_by != "DESC") {
                echo "window.location.href = \"" . $new_location . "DESC\";";
            } else {
                echo "window.location.href = \"" . $new_location . "ASC\";";
            } ?>
            return false;
        });

        $('#commentZone').css({'opacity':'0'});
        // Call via Ajax to ping the user in the journal
        // and in email.
        $('#send-ping-btn').click(function()    {
            var msg = $('#ping-msg').val();
            // if( $('#send-mail:checked').val() ) mail = 1;
            // always send email
            var mail = 1;
            var journal = $('#echo-journal').is(':checked') ? 1 : 0;
            var cc = $('#copy-me').is(':checked') ? 1 : 0;
            var data = {id : <?php echo $worklist_id; ?>, who : ping_who, msg : msg, mail : mail, journal : journal, cc : cc};
            $.ajax({
                type: "POST",
                url: 'pingtask.php',
                data: data,
                dataType: 'json',
                success: function() {
                    $("#sent-notify").html("<span>Your ping has been sent.</span>");
                    $("#sent-notify").dialog("open");
                }
            });
            $('#popup-pingtask').dialog('close');
            return false;

        });

        $('#pingMechanic').click(function() {
            if (!$.loggedin) {
                sendToLogin();
                return;
            }

            ping_who = 'mechanic';
            $('#popup-pingtask form h5').html('Ping the Mechanic about the task');
            $('#popup-pingtask').dialog('open');
            $('#popup-pingtask').dialog('option', 'title', 'Ping the Mechanic about the task');
            return false;
        });

        $('#pingRunner').click(function() {
            if (!$.loggedin) {
                sendToLogin();
                return;
            }

            ping_who = 'runner';
            $('#popup-pingtask form h5').html('Ping the Runner about the task');
            $('#popup-pingtask').dialog('open');
            $('#popup-pingtask').dialog('option', 'title', 'Ping the Runner about the task');
            return false;
        });

        $('#pingCreator').click(function() {
            if (!$.loggedin) {
                sendToLogin();
                return;
            }

            ping_who = 'creator';
            $('#popup-pingtask form h5').html('Ping the Creator about the task');
            $('#popup-pingtask').dialog('open');
            $('#popup-pingtask').dialog('option', 'title', 'Ping the Creator about the task');
            return false;
        });

        $('.CreatorPopup').click(function(event) {
            var bidderId=$(this).attr("bidderId");
            showUserInfo(bidderId);
            event.stopPropagation();
        });

        // Reassign runner
        (function($) {
            if ($('form span.runnerName') !== null) {
                $('form span.runnerName').css({
                    'cursor': 'pointer',
                    'text-decoration': 'underline'
                }).click(function() {
                    var shown = false;
                    $(this).parent().siblings().fadeOut(1000, function() {
                        if (shown != true) {
                            shown = true;
                            $('form span.runnerName').css('margin', '0px 7px 0px 0px');
                            $('form span#ping-r-btn').css('display', 'none');
                            $('form span.changeRunner').fadeIn(1000, function() {
                                $(this).children('input[name=changerunner]').click(function() {
                                    $(this).unbind('click');
                                    $.ajax({
                                        type: 'post',
                                        url: 'jsonserver.php',
                                        data: {
                                            action: 'changeRunner',
//  to avoid script loading error when not logged
//                                          userid: <?php echo($user_id); ?>,
                                            userid: <?php echo(!empty($user_id) ? $user_id : "''"); ?>,
                                            runner: $('form span.changeRunner select[name=runner]').val(),
                                            workitem: <?php echo($worklist_id); ?>
                                        },
                                        dataType: 'json',
                                        success: function(j) {
                                            if (j.success == true) {
                                                $('form span#ping-r-btn').text(j.nickname);
                                                $('form input[name=cancel]').click();
                                            }
                                        }
                                    });
                                });
                                $(this).children('input[name=cancel]').click(function() {
                                    $('form span.changeRunner').fadeOut(1000, function() {
                                        $('form span#ping-r-btn').css('display', 'inline');
                                        $('form span.runnerName').css('margin', '0px 7px 0px 15px');
                                        $('form span.runnerName').parent().siblings().fadeIn(1000);
                                    });
                                });
                            });
                        }
                    });
                });
            }
        })(jQuery);

        // applies the same size as the thinnest to all comments the show'em
        var thinnestComment = $('div.commentZone li').thinnestSize() - 14;
        $('div.commentZone li').width(thinnestComment);
        $('div.commentform textarea').width(thinnestComment);
        $('#commentZone').css({'opacity':'1'});

        //-- gets every element who has .iToolTip and sets it's title to values from tooltip.php
        setTimeout(MapToolTips, 800);

        return false;
    });
    <?php if ($user_id > 0 && $hideFees) { ?>
    $(function() {
        $('#fee-accordion').accordion({
            clearStyle: true,
            collapsible: true,
            active: false
        });
    });

    <?php } ?>
    
    function sendToLogin(){
        window.location = '<?php echo SERVER_URL; ?>login.php?redir=<?php echo urlencode(Utils::currentPageUrl()); ?>';
    }
    
    function setFollowingText(isFollowing){
        if(isFollowing == true) {
            $('#following').attr('title', 'You are currently following this job');
            $('#following').html('Un-Follow this job');
        } else {
            $('#following').attr('title', 'Click to receive updates for this job');
            $('#following').html('Follow this job');
        }
    }
</script>
<title>#<?php print $worklist['id']." - ".$worklist['summary']; ?></title>
</head>
<body>
    <?php include("format.php"); ?>
    <div id="info-top">
      <div id="info-top-left">
        <span class="info-summ-big-text<?php echo $classEditable;?>"><?php echo $worklist['summary'];?></span>
      </div>

      <div style="clear:both; float:none">&nbsp;</div>
    </div>
    <?php if ($erroneous) echo "<div style='color: red; text-align: center;'>{$the_errors}</div>"; ?>
        <div>
<?php if (isset($action_error) && !empty($action_error)) : ?>
        <div id="action-error" class="LV_invalid">Error performing requested action: <?php echo $action_error; ?></div>
<?php endif; ?>
        <div style="clear:both; float:none;"> </div>
        </div>
        <div id="page-content">
            <div id="analytics" data="<?php echo $worklist['id']; ?>"></div>
            <div class="info-summ-big">
                <div class="info-ID">
                    <span class="info-label">#</span><?php echo $worklist['id'];?>
                    <?php
                        //Show original item id if displayed item is a bug
                        if($workitem->getBugJobId() > 0) {
                            $originalItemLink = SERVER_URL."workitem.php?job_id=".
                                                $workitem->getBugJobId()."&action=view&order=" . $order_by;
                            echo "  [ bug of <a href='".$originalItemLink."' class='worklist-item' id='workitem-".$workitem->getBugJobId()."' >".$workitem->getBugJobId()."</a> ]";
                        }
                        ?>
                    &nbsp;-&nbsp;<span id="following"></span><span id="followingLogin"></span>
                </div>
                <?php if ($allowEdit && $action != "edit"): ?>
                    <span class="switchmode">-
                    <a title="Switch to Edit Mode" href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?job_id=<?php echo $worklist['id'];?>&action=edit&order=<?php echo $order_by; ?>">
                    Switch to Edit Mode</a>
                    </span>
                <?php endif;?>
                <?php if ($action == "edit"): ?>
                    <span class="switchmode">-
                    <a title="Switch to View Mode" href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?job_id=<?php echo $worklist['id'];?>&action=view&order=<?php echo $order_by; ?>">
                    Switch to View Mode</a>
                    </span>
                <?php endif;?>
            </div>
            <div style="clear:both; width:100%; float:none;"> </div>
        <?php if ($action != "edit"): ?>
            <div id="quick-status">
            <span class="info-label"><a href="help.php#ff5" target="_blank">Status</a></span>
            <span class="info-data" style="font-size:14px"><?php echo $worklist['status'];?></span>
            <?php
            if ((!$is_runner && ($mechanic_id == $user_id) && $worklist['status'] != 'DONE') || $is_runner 
            || ($worklist['creator_id']== $user_id && $worklist['status'] != 'DONE')) :
            ?>
            <form method="post" action="" style="display:inline">
            <input type="hidden" id="status-switch" name="status-switch" value="1" />
            <select name="quick-status">
            <option value="<?php echo $worklist['status'];?>" selected="selected"><?php echo $worklist['status'];?></option>
            <?php
            if (!$is_runner && ($mechanic_id == $user_id) && $worklist['status'] != 'DONE') { //mechanics
                foreach ($statusListMechanic as $status) {
                    if ($status != $worklist['status']) {?>
                        <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                <?php
                    }
                }
            }
            else if ($is_runner) { //runners and admins
                foreach ($statusListRunner as $status) {
                    if ($status != $worklist['status']) {
                ?>
                    <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                <?php
                    }
                }
            } else if ($worklist['creator_id']== $user_id && $worklist['status'] != 'DONE') { //creator
                foreach ($statusListCreator as $status) {
                    if ($status != $worklist['status']) {
                ?>
                    <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                <?php
                    }
                }
            }
            ?>
            </select>
            </form>
            <?php endif; ?>
          </div>
          <div style="clear:both; float:none;"> </div>
      <?php endif;?>
            <div id="left-panel">
                <div id="for_view">
<?php 
$runner_nickname = $worklist['runner_nickname'] != '' ? $worklist['runner_nickname'] : 'Not funded';
if ($action =="edit"):  ?>
<!-- *** Action is Edit *** -->
                <form id="workitem-form" method="post" action="">
                    <input type="hidden" name="save_workitem" value="save_workitem" />
                    <input type="hidden" name="action" value="save_workitem" />
                    <ul style="margin:0; padding:0;">
                <!-- Edit: Summary info -->
                        <li><span class="info-label">Summary</span><span class="info-data">
                            <?php // creator can edit it's own task if freshly added 23-MAR-2011 <godka>
                            if (($is_runner && $worklist['status']!='DONE') || ($creator_id==$user_id && $worklist['status']=='SUGGESTED' || $worklist['status']=='SUGGESTEDwithBID' && is_null($worklist['runner_id']))) { ?>
                            <input type="text" size="30" class="text-field" id="summary" name="summary" value="<?php echo html_entity_decode($worklist['summary'], ENT_QUOTES);?>"/>
                            <?php } else { ?>
                            <span class="info-summ"><?php echo $worklist['summary'];?>
                            <?php } ?></span>
                        </li>
                <!-- Edit: Status info -->
                        <li>
                            <span class="info-label"><a href="help.php#ff5" target="_blank">Status</a></span>
                            <span class="info-data">
                            <?php if ($is_runner || $user_id == $worklist['runner_id']){  ?><span class="info-data">
                            <select id="status" name="status">
                                <?php foreach ($statusListRunner as $status) { ?>
                                <option value="<?php echo $status; ?>"<?php echo $status == $worklist['status']?' selected="selected"' : ''; ?> ><?php echo $status; ?></option>
                                <?php }?>
                            </select>
                            <?php } else if ($creator_id == $user_id && $mechanic_id == $user_id){ ?><span class="info-data">
                            <select id="status" name="status">
                                <?php foreach ($statusListCreator as $status) { ?>
                                <option value="<?php echo $status; ?>" <?php echo $status == $worklist['status']?' selected="selected"' : ''; ?> ><?php echo $status; ?></option>
                                <?php } ?>
                            </select>
                            <?php } else if ($creator_id == $user_id) { ?>
                            <span class="info-data">
                            <select id="status" name="status">          
                                <?php foreach ($statusListCreator as $status) { ?>
                                <option value="<?php echo $status; ?>" <?php echo $status == $worklist['status']?' selected="selected"' : ''; ?> ><?php echo $status; ?></option>
                                <?php } ?>
                            </select>
                            <?php } else { ?><?php echo $worklist['status'];?>
                            <input type="hidden" id="status" name="status" value="<?php $worklist['status']; ?>" />
                            <?php } ?></span>
                        </li>
                <!-- Edit: Key People info box -->
                        <li>
                            <div id="creatorBox"><span id="pingCreator" class="creatorName" 
                            title="<?php echo isset($_SESSION['userid']) ? "Ping Creator" : "Log in to Ping Creator"; ?>">
                            <a href="#">Creator</a></span> <a href="#" onClick="javascript:showUserInfo(<?php echo $worklist['creator_id']; ?>);">
                            <?php echo $worklist['creator_nickname'];?></a></div>
                            <div id="runnerBox"><span class="runnerName">Runner</span>
                            <?php
                                if( $runner_nickname != '' ) {
                                    $tooltip = isset($_SESSION['userid']) ? "Ping Runner" : "Log in to Ping Runner";
                                    echo ' <span id="ping-r-btn" title="' . $tooltip . '">'. $runner_nickname . '</span>';
                                } else {
                                    echo "&nbsp" . $runner_nickname;
                                }

                                echo '<span class="changeRunner">';
                                $runnerslist = User::getRunnerlist();
                                echo '<select name="runner">';
                                foreach ($runnerslist as $r) {
                                    echo '<option value="' . $r->getId() . '"' . (($worklist['runner_id'] == $r->getId()) ? ' selected="selected"' : '') . '>' . $r->getNickname() . '</option>';
                                }
                                echo '</select>';
                                echo '&nbsp;<input type="button" name="changerunner" value="Change Runner" />&nbsp;<input type="button" name="cancel" value="Cancel" /></span>'
                            ?>
                            </div>
                                <?php
                                $mech = '';
                                if( count($fees) >0 ) {
                                    foreach( $fees as $fe )
                                        if( $fe['desc'] == 'Accepted Bid' ) $mech = $fe['nickname'];
                                }
                                if($mech=='') {
                                    $mech = 'Not assigned';
                                } else{
                                    $tooltip = isset($_SESSION['userid']) ? "Ping Mechanic" : "Log in to Ping Mechanic";
                                    $mech='<span id="ping-btn" title="' . $tooltip . '">' . $mech . '</span>';
                                }
                                echo '<div id="mechanicBox"><span class="mechanicName">Mechanic</span> ' . $mech . '</div>';
                            ?>
                        </li>
                        <li>
                        <?php if($worklist['status'] != 'DONE'):?>
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
                            <?php endif;?>
                        </li>
                <!-- Edit: Notes textarea -->
                        <li>
                            <span class="info-label">Notes</span>
                            <?php if (($is_runner || $creator_id == $user_id) && ($worklist['status'] != 'DONE')): ?>
                                <span id="info-notes">
                                    <textarea name="notes" id="edit-notes" <?php if(count($activeBids) > 0) {echo 'disabled="disabled"';} ?> ><?php echo $worklist['notes'];?></textarea>
                                </span>
                            <?php else:?>
                                <span id="info-notes"><?php echo replaceEncodedNewLinesWithBr($worklist['notes']);?></span>
                            <?php endif;?>
                        </li>
                        <li>
                            <span class="project-label">Project</span>
                            <?php if (($is_runner || $creator_id == $user_id) && ($worklist['status'] != 'DONE')): 

                                      $filter = new Agency_Worklist_Filter();
                                      $filter->setProjectId($worklist['project_id']);
                                      echo $filter->getProjectSelectbox('Select Project', 0, 'project_id', 'project_id');
                                  elseif( !empty($worklist['project_id'])) :?>
                                 <a href="<?php echo Project::getProjectUrl($worklist['project_id']);?>">
                                    <?php echo htmlspecialchars($worklist['project_name']);?>
                                 </a>
                            <?php else :?>
                             <span>Not assigned</span>
                            <?php endif;?>
                        </li>
                        <li>
                            <span class="info-label">Skills</span><br/>
<?php if (count($workitem->getSkills()) > 0) : ?>
                            <input type="text" size="60" class="text-field" name="skills" id="skills" value="<?php echo implode(', ', $workitem->getSkills()) . ', '; ?>" /></span>
<?php else: ?>
                            <input type="text" size="60" class="text-field" name="skills" id="skills" value="" /></span>
<?php endif; ?>
                        </li>
                        <li>
                            <span class="info-label">Bug</span><br/>
                            <div class="floatLeft">
                                <label id="bugLabel"><input type="checkbox" name="is_bug" id="is_bug" <?php if ($workitem->getBugJobId()>0 || $workitem->getIs_bug() == 1) echo 'checked="checked"';?>/> Bug</label>
                                (If known, linked to Job #)
                                <input type="text" id="bug_job_id" name="bug_job_id" class="text-field bug_job_id" size="48" value="<?php if($workitem->getBugJobId()>0) echo $workitem->getBugJobId();?>" />
                            </div>
                            <div class="bugJobSummary" id="bugJobSummary" title="0">
                            </div>
                            <div class="clear"></div>
                            <script type="text/javascript">
                                $(document).ready(function() {
                                    if($("#is_bug").is ( ":checked" )) {
                                        $("#bug_job_id").keyup();
                                    }
                                });
                            </script>
                        </li>
                    <!-- Edit: Sandbox textarea -->
                    <?php if ($mechanic_id > 0):?>
                    <li>
                        <span class="project-label" >Functional &amp; Review URL</span>
                        <?php if(($is_runner || $creator_id == $user_id) && ($worklist['status'] != 'DONE')): ?>
                        <input type="text" size="30" class="text-field" name="sandbox" id="sandbox" value="<?php echo htmlspecialchars($worklist['sandbox']);?>" />
                         <?php elseif ( !empty($worklist['sandbox']) ):?>
                            <a href="<?php echo $worklist['sandbox'];?>" target="_blank">
                                <?php echo htmlspecialchars($worklist['sandbox']);?>
                            </a>
                        <?php else :?>
                            <span>Not assigned</span>
                        <?php endif;?>
                    </li>
                    <?php endif;?>

                    </ul>
                    <?php if($allowEdit && $action=="edit"): ?>
                        <div class="buttonContainer-save">
                            <input type="submit" value="Save" name="save_workitem" id="save_workitem" />
                        </div>
                    <?php endif;?>
                </form>
<!-- *** End of Action is Edit *** -->
<?php else: ?>
<!-- *** Action is View *** -->
                <ul>
<?php if (! empty($status_error)) : ?>
                    <li><span class="LV_invalid status-error"><?php echo $status_error; ?></span></li>
<?php endif; ?>
                <!-- View: Key People info -->
                    <li>
                            <div id="creatorBox"><span id="pingCreator" class="creatorName" title="<?php echo isset($_SESSION['userid']) ? "Ping Creator" : "Log in to Ping Creator"; ?>"> <a href="#">Creator</a></span> <a href="#" onClick="javascript:showUserInfo(<?php echo $worklist['creator_id']; ?>);"><?php echo substr($worklist['creator_nickname'], 0, 9) . (strlen($worklist['creator_nickname']) > 9 ? '...' : '');?></a></div>
                            <div id="runnerBox">
                            <?php
                                if( $runner_nickname != 'Not funded' ) {
                            ?>
                                    <span id="pingRunner" class="runnerName" title="<?php echo isset($_SESSION['userid']) ? "Ping Runner" : "Log in to Ping Runner"; ?>"> <a href="#">Runner</a></span> <a href="#" onClick="javascript:showUserInfo(<?php echo $worklist['runner_id']; ?>);"><?php echo substr($worklist['runner_nickname'], 0, 9) . (strlen($worklist['runner_nickname']) > 9 ? '...' : '') ;?></a>
                            <?php
                                }
                                else {
                            ?>
                            <span class="runnerName" title="Ping Runner">Runner</span> Not funded
                            <?php
                                }
                            ?>
                            </div>
                            <?php
                                $mech = '';
                                if(count($fees)>0){foreach($fees as $fe){if($fe['desc']=='Accepted Bid'){$mech = $fe['nickname'];}}}
                                if($mech==''){
                                    echo'<div id="mechanicBox"><span class="mechanicName">Mechanic</span> Not Assigned</div>';
                                }
                                else{
                                    $tooltip = isset($_SESSION['userid']) ? "Ping Mechanic" : "Log in to Ping Mechanic";
                                    echo'<div id="mechanicBox"><span id="pingMechanic" class="mechanicName" title="' . $tooltip . '"><a href="#">Mechanic</a></span> <a href="#" onClick="javascript:showUserInfo('. $worklist['mechanic_id'].');"><span id="ping-btn" title="' . $tooltip . '">'.substr($mech, 0, 9) . (strlen($mech) > 9 ? '...' : '').'</span></a></div>';
                                }
                            ?>

                    </li>
                    <li>
                        <span class="project-label">Project</span>
                        <?php if ( !empty($worklist['project_id']) ):?>
                            <a href="<?php echo Project::getProjectUrl($worklist['project_id']);?>" target="_blank">
                                <?php echo htmlspecialchars($worklist['project_name']);?>
                            </a>
                        <?php else :?>
                            <span>Not assigned</span>
                        <?php endif;?>
                    </li>
                    <li>
                        <span class="info-label">Skills</span>
<?php if (count($workitem->getSkills()) > 0) : ?>
                        <span id="skills-list"><?php echo implode(', ', $workitem->getSkills()); ?></span>
<?php else: ?>
                        <span id="skills-list">None assigned</span>
<?php endif; ?>
                    </li>
                    <!-- View: Notes textarea -->
                    <li>
                        <span class="info-label">Notes</span>
                        <p class="notestext"><?php echo replaceEncodedNewLinesWithBr(linkify($worklist['notes'])); ?></p>
                        <!--<textarea size="33" style="-moz-border-radius: 6px; -webkit-border-radius: 6px; border-radius: 6px; width:418px;  height:164px; margin-top:8px;" name="notes" rows="25" cols="20" readonly="readonly" id="edit-notes"><?php //echo $worklist['notes'];?></textarea> -->
                    </li>
                    <!-- View: Sandbox textarea -->
                    <?php if ($mechanic_id > 0):?>
                    <li>
                        <span class="project-label" id="edit_review_url">Functional &amp; Review URL</span>
                        <?php if ( !empty($worklist['sandbox']) ):?>
                            <a href="<?php echo $worklist['sandbox'];?>" target="_blank">
                                  <?php 
                                  if (strlen(htmlspecialchars($worklist['sandbox'])) > 64) {
                                        echo substr(htmlspecialchars($worklist['sandbox']), 0, 64) . "...";
                                  } else {
                                      echo htmlspecialchars($worklist['sandbox']);
                                  }
                                  
                                  ?> 
                            </a><br />
                            <?php if (($worklist['status'] == 'FUNCTIONAL' || $worklist['status'] == 'REVIEW')
                                    && $worklist['sandbox'] != 'N/A') { ?> 
                                <input type="submit" id="view-sandbox" value="View Sandbox" />
                            <?php } ?>                         
                        <?php else :?>
                            <span>Not assigned</span>
                        <?php endif;?>
                    </li>
                    <?php endif;?>
                    <?php if ($user_id > 0):?>
                    <li>
                      <form action="" method="post" name="invite-form" onSubmit="return sendInviteForm();">
                        <span class="project-label" id="invite-label">Invite</span>
                        <input type="text" class="invite ac_input" name="invite" autocomplete="on" style="width: 210px" />
                        <script type="text/javascript">
                            $(document).ready(function() {
                              $('.invite').autocomplete('getusers.php', {
                                multiple: false,
                                extraParams: { nnonly: 1 }
                              });
                            });
                        </script>
                        <input type="submit" id="invite-people" name="invite-people" value="Invite" class="bottonAtRight" />
                      </form>
                    </li>
                    <?php endif;?>

                </ul>
<!-- *** End of Action IF *** -->
<?php endif;?>
            <div class="commentZone">
                <div class="info-comments">Comments</div>

                <?php if ($order_by != "DESC") : ?>
                    <a class="info-comments-order" href="#">&#9650;&#9660;</a>
                <?php else : ?>
                    <a class="info-comments-order" href="#">&#9660;&#9650;</a>
                <?php endif;

                if (!empty($user_id) && ($worklist['status'] != 'DONE')) : ?>
                <a class="info-comments-scroll-entry" href="#commentform">Scroll to bottom</a>
                <?php endif ?>
                <div style="clear:both;height:0px;">&nbsp;</div>              

                <?php if (!empty($user_id) && ($worklist['status'] != 'DONE') && $order_by == "DESC") : ?>
                <div class="commentform"> <a name="commentform"></a> <span class="info-label">Write a comment</span>
                    <form action="" method="post">
                        <input type="hidden" name="worklist_id" value="<?php echo $worklist['id']; ?>" />
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
                        <input type="hidden" name="order_by" value="DESC" />
                        <input type="hidden" name="comment_id" value="" />
                        <textarea name="comment" class="expand100-300" cols="20" rows="10" size="33"></textarea>
                        <br />
                        <input type="submit" name="newcomment" value="Post Comment" class="disableable" />
                    </form>
                </div> 
                <?php endif; ?>

                <ul>
                <?php
                    $comments = Comment::findCommentsForWorkitem($worklist['id']);
                    if (empty($comments)) :
                        ?>No comments!<?php
                    else :
                        $it=0;
                        $comments_list = array();

                        if ($order_by != "DESC") :
                            $comments_list = $comments;
                        else :
                            $comments_list = array_reverse($comments, true);
                        endif;

                        foreach ($comments_list as $comment) :
                            $comment[] = $comments[$it];
                            ?><li id="comment-<?php echo $comment['id']; ?>" class="comment depth-<?php echo $comment['depth']; ?> <?php ($it % 2) ? print 'imOdd' : print 'imEven'; $it++; ?> <?php if ($order_by == "DESC" && $comment['depth'] > 0) echo 'desc'; ?>">
                                <div class="comment-info">
                                    <span class="author profile-link" onClick="javascript:showUserInfo('<?php echo $comment['comment']->getUser()->getId(); ?>')"><strong><?php echo $comment['comment']->getUser()->getNickname(); ?></strong>&nbsp;
                                        <span class="date"><?php echo relativeTime(strtotime($comment['comment']->getDate())-time());?></span>
                                    </span>
                                </div>
                                <div class="comment">
                                    <image class="picture profile-link" src="<?php echo $comment['comment']->getUser()->getAvatar(); ?>" title="Profile Picture - <?php echo($comment['comment']->getUser()->getNickname()); ?>" onClick="javascript:showUserInfo('<?php echo $comment['comment']->getUser()->getId(); ?>')" />
                                    <?php echo replaceEncodedNewLinesWithBr(linkify($comment['comment']->getComment())); ?>
                                </div>
                                <?php if (!empty($user_id) && $comment['depth'] < 6 && ($worklist['status'] != 'DONE')) : ?>
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

                <?php if (!empty($user_id) && ($worklist['status'] != 'DONE') && $order_by == "ASC") : ?>
                <div class="commentform"> <a name="commentform"></a> <span class="info-label">Write a comment</span>
                    <form action="" method="post">
                        <input type="hidden" name="worklist_id" value="<?php echo $worklist['id']; ?>" />
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
                        <input type="hidden" name="order_by" value="ASC" />
                        <input type="hidden" name="comment_id" value="" />
                        <textarea name="comment" class="expand100-300" cols="20" rows="10" size="33"></textarea>
                        <br />
                        <input type="submit" name="newcomment" value="Post Comment" class="disableable" />
                    </form>
                </div> 
                <?php endif; ?>

        <!-- End of div commentZone -->
            </div>
            <span class="info-label">&larr;</span> <a class="workitem-back" title="Back to Worklist" href="worklist.php">Back to Worklist</a>
    <!-- End of div for_view -->
            </div>
<!-- End of div left-panel -->
        </div>
        <div id="right-panel">
            <div id="for_view">
                <div class="moneyZone">
                    <div id="bid-panel">
                        <table width="100%" class="table-bids">
                            <caption class="table-caption" >
                                <b>Bids</b> <span class="note">(click bid for detail)</span>
                            </caption>
                            <thead>
                                <tr class="table-hdng">
                                    <td>Who</td>
                                    <td>Amount</td>
                                    <td>Done By</td>
                                    <td>Expires</td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(empty($bids)) { ?>
                                <tr>
                                    <td style="text-align: center;" colspan="4">No bids yet.</td>
                                </tr>
                            <?php 
                                } else {

                                $row = 1;
                                foreach($bids as $bid) {
                                    if ($user->getId() == $bid['bidder_id'] && $bid['expires'] <= 0) {
                                        $biddings['0'] = $bid;
                                    } else if ($user->getId() != $bid['bidder_id'] && $bid['expires'] < 0) {
                                        $biddings = array();
                                    } else {
                                        $biddings['0'] = $bid;
                                    }

                                foreach($biddings as $key=>$bid) { 
                                    if ($user->getId() == $bid['bidder_id'] && $bid['expires'] < 0) {
                                        $expired_class = ' expired_warn';
                                    } else {
                                        $expired_class = '';
                                    }
                                    $canSeeBid = $user->isRunnerOfWorkitem($workitem) || $user->getId() == $bid['bidder_id'] || ($worklist['status'] == 'SUGGESTEDwithBID' && $is_runner);
                                    $row_class = "";
                                    $row_class .= ($user_id) ? 'row-bidlist-live ' : '' ;
                                    $row_class .= ($row % 2) ? 'rowodd ' : 'roweven ';
                                    $row_class .= 'biditem';
                                    $row_class .= ($canSeeBid)
                                                ? "-" . $bid['id'] . ' clickable' 
                                                : '';
                                    $row_class .= $expired_class;
                                    ?>
                                <tr class="<?php echo $row_class; ?>">
                                <?php
                                    // store bid info into jquery metadata so we won't have to fetch it again on user click
                                    // but only if user is runner or creator 15-MAR-2011 <godka>
                                    $notes = addcslashes(preg_replace("/\r?\n/", "<br />", $bid['notes']),"\\\'\"&\n\r<>");
                                    if ($canSeeBid) { ?>
                                        <script type="data"><?php echo
                                                "{id: {$bid['id']}, " .
                                                "nickname: '{$bid['nickname']}', " .
                                                "email: '{$bid['email']}', " .
                                                "amount: '{$bid['bid_amount']}', " .
                                                "bid_accepted: '{$bid['bid_accepted']}', " .
                                                "bid_created: '{$bid['bid_created']}', " .
                                                "bid_expires: '" . ($bid['expires'] ? relativeTime($bid['expires']) : "Never") . "', " .
                                                "time_to_complete: '{$bid['time_to_complete']}', " .
                                                "done_in: '{$bid['done_in']}', " .
                                                "bidder_id: {$bid['bidder_id']}, " .
                                                "notes:\"" .  replaceEncodedNewLinesWithBr($notes) . "\"}";
                                        ?></script>
                                    <?php } ?>
                                    <td>
                                    <?php if ($canSeeBid) { ?>
                                        <a href="#" bidderId="<?php echo $bid['bidder_id'];?>" class="CreatorPopup"><?php echo getSubNickname($bid['nickname']);?></a>
                                    <?php } else echo $bid['nickname']; ?>
                                    </td>
                                    <td >$ <?php echo $bid['bid_amount'];?></td>
                                    <td>
                                    <?php
                                        // new bids done by
                                        $past_due = '';
                                        $oTz = date_default_timezone_get();
                                        if (isset($_SESSION['timezone'])) {
                                            date_default_timezone_set(getTimeZoneDateTime($_SESSION['timezone']));
                                        }
                                        if ($bid['accepted'] == 1){
                                            $bid_done_by_stamp = strtotime($bid['unix_done_full']);
                                        } else {
                                            $bid_done_by_stamp = strtotime("+" . $bid['done_in'], time());
                                        }
                                        if ($bid_done_by_stamp < time() && ($workitem->getStatus() == 'WORKING' || $workitem->getStatus() == 'REVIEW' || $workitem->getStatus() == 'FUNCTIONAL')) {
                                            $past_due = ' class="past-due overdue"';
                                        }
                                        if (empty($past_due) && $workitem->getStatus() == 'DONE' && $workitem->getMechanicId() == $bid['bidder_id']) {
                                            $past_due = ' class="past-due done"';
                                        }

                                        echo "<em" . $past_due . ">" . date('m/d/Y h:i a', $bid_done_by_stamp) . "</em>";
                                        date_default_timezone_set($oTz);
                                    ?>
                                    
                                    
                                    </td>
<?php
    $expire_class = '';
    if (isset($bid['expires']) && $bid['expires'] != -1) {
        if ($bid['expires'] == -1) {
        } else if ($bid['expires'] <= BID_EXPIRE_WARNING) {
            $expire_class = 'class="warn"';
            if (isset($bid['expires']) && $bid['expires'] != 0) {
                $expire_class = '';
            }
        }
    }
?>
                                    <td <?php echo $expire_class; ?>><?php echo isset($bid['expires']) ? relativeTime($bid['expires']) : ''; ?></td>
                                </tr>
                                <?php } ?>
                            <?php } ?>
                        <?php } ?>
                            </tbody>
                        </table>
<?php if ($user_id) : ?>
<?php if ($worklist['status'] == 'BIDDING' || $worklist['status'] == 'SUGGESTEDwithBID' || ($worklist['status'] == 'SUGGESTED' && $worklist['creator_id']== $user_id)) : ?>
                        <div class="buttonContainer">
<?php if ($user->isEligible()) : ?>
                            <input type="submit" value="Add Bid" onClick="return showConfirmForm('bid');" />
<?php else: ?>
                            <input type="submit" value="Add Bid" onClick="return showIneligible('bid');" />
<?php endif; ?>
<?php if(! empty($bids) && ($is_runner || $user_id == $workitem->getRunnerId()) && count($bids) >1 && !$workitem->hasAcceptedBids() && (((strtoupper($workitem->getStatus()) == "BIDDING")) || $workitem->getStatus() == "SUGGESTEDwithBID")):?>
                            <input type="submit" value="Accept Multiple"  id="btnAcceptMultiple" onClick="javascript:AcceptMultipleBidOpen();"/>
<?php endif;?>
                        </div>
<?php endif; ?>
<?php endif;?>
                <!-- End of div bid-panel -->
                    </div>
                    <?php if ($user_id > 0) { ?>
                    <?php if ($hideFees) {?>
                    <div id="fee-accordion">
                        <h3><a href="#">More</a></h3>
                    <?php } ?>
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
                                <?php else: $row = 1;
                                    foreach($fees as $fee): ?>
                                    <tr class="<?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?> ">
                                        <td><a href="#" onClick="javascript:showUserInfo(<?php echo $fee['user_id'];?>);"><?php echo getSubNickname($fee['nickname']);?></a></td>
                                        <td>$ <?php echo $fee['amount'];?></td>
                                        <?php if (($worklist['status'] == 'REVIEW' || $worklist['status'] == 'COMPLETED' || $worklist['status'] == 'DONE')&& ($fee['desc'] == 'Accepted Bid')) { ?>
                                        <td>Bid Notes: <?php echo preg_replace("/\r?\n/", "<br />", $fee['bid_notes']);?></td>
                                        <?php } else { ?>
                                        <td><?php echo $fee['desc'];?></td>
                                        <?php }?>
                                        <td><?php echo $fee['date'];?></td>
                                        <td>
                                            <?php if($is_payer): ?><a href="#" class = "paid-link" id = "feeitem-<?php echo $fee['id'];?>"><?php echo $fee['paid'] == 0 ? "No" : "Yes";?></a>
                                            <?php else: echo $fee['paid'] == 0 ? "No" : "Yes";?>
                                            <?php endif;?>
                                            <?php if(($is_runner ||$user_id == $workitem->getRunnerId() || $user_id == $fee['user_id']) &&($user_id && empty($fee['paid']))):?>
                                            <br><a href="#" id="wd-<?php echo $fee['id'];?>" class="wd-link" title='Delete Entry'>DEL</a>
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
                            <?php if($user_id && ($worklist['status'] != 'DONE')): ?>
                            
                            <div class="buttonContainer">
                                <?php if ($mechanic_id == $user_id): ?>
                                    <input class="iToolTip addTip" type="submit" value="Tip User" />
                                <?php endif; ?>
                                
                                <?php if ($worklist['status'] == 'REVIEW') {
                                    if ( $workitem->getCRStarted() !=1) {
                                        if ($mechanic_id != $user_id) {
                                            if ($user->isEligible()) {
                                                if(hasRights($user_id, $workitem)) { ?>
                                                    <input class="iToolTip cR" type="submit" value="Start Code Review" onClick="return showReviewForm();"/>
                                        <?php   } else { ?>
                                                    <input class="iToolTip cRDisallowed" type="submit" value="Start Code Review" onClick="return false;"/>
                                        <?php   }
                                            }
                                        }
                                        
                                    } else if($workitem->getCRStarted() == 1 && $workitem->getCRCompleted() != 1) {
                                        if ($user->isEligible()) {
                                            if(hasRights($user_id, $workitem)) {
                                                if($user_id == $workitem->getCReviewerId() || $is_runner || $user_id == $workitem->getCreator() || $user_id == $mechanic_id) {?>
                                                    <input class="iToolTip endCr" type="submit" value="End Code Review" onClick="return showEndReviewForm();"/>
                                        <?php   }
                                            }
                                        } 
                                    } else if($workitem->getCRCompleted() == 1) { ?>
                                        <input class="iToolTip cRDisallowed" type="submit" value="Start Code Review" onClick="return false;"/>
                            <?php   }                                   
                                }?>
                                
                                <?php if ($user_id): ?>
                                    <?php if ($user->isEligible()) : ?>
                                        <input class="iToolTip addFee" type="submit" value="Add Fee" onClick="return showConfirmForm('fee');"/>
                                    <?php else: ?>
                                        <input class="iToolTip addFee" type="submit" value="Add Fee" onClick="return showIneligible('fee');"/>
                                    <?php endif; ?>
                                <?php endif; ?>
                                </div>
                            <?php endif;?>
                            <form id="withdraw" method="post" action="" >
                                <input type="hidden" name="action" value="withdraw_bid" />
                                <input type="hidden" class="fee_id" name="fee_id" value="" />
                            </form>
                    <!-- End of div fee-panel -->
                        </div>
                    <?php if ($hideFees) {?>
                    </div><!--  end accordian -->
                    <?php } ?>
                    <?php } else { ?>
                    <div class="buttonContainer">
                        <input style="cursor:pointer;" type="button" value="Login to Bid" onClick="sendToLogin(); return false;" />
                    </div>
                    <?php } ?>
            <!-- End of div moneyZone -->
                </div>
                <div id="uploadPanel"> </div>
                <?php if(JOURNAL_EXISTS) { // include the journal dropdown if the journal setting is true ?>
                <div id="journalPanel">
                    <div class="accordion">
                        <h3><a href="#" id="loadJournalInfo">Related Journal Discussion</a></h3>
                        <div id="journalChat">
                            <img src="images/loader.gif" id="journalChatThrobber"/>
                        </div>
                    </div>
                </div>
                <?php } ?>
        <!-- End of for_view-->
            </div>
<!-- End of div right-panel-->
        </div>
<!-- End of div pageContent -->
    </div><br>
            <div style="margin:0px 101px 6px 32px;line-height:50px; padding-top:30px;padding-right:10px; float:left;display:inline-block;">

                <span class="st_sharethis" st_title="Share this WorkItem" displayText="ShareThis"></span>
                <script type="text/javascript" src="https://ws.sharethis.com/button/buttons.js"></script>
                <script type="text/javascript">
                    stLight.options({
                        publisher: '4867cd3b-6b43-4c9b-b7a7-57966f818e05',
                        onhover: false
                    });
                </script>
            </div>
            <div style="clear:both"></div>

    <!-- Popup HTML for Placing a bid -->
    <?php if(isset($message)): ?>
    <div id="message" style="display:none; padding: 0 0.7em; margin: 0.7em 0;" class="ui-state-highlight ui-corner-all">
        <p><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><strong>Info:</strong><?php echo $message; ?></p>
    </div>
    <?php endif;?>
    <div id="sent-notify"></div>
    <!-- Popup for  edit bid info -->
    <?php include('dialogs/popup-edit-bid-info.inc') ?>
    <!-- Popup for place bid -->
    <?php include("dialogs/popup-place-bid.inc"); ?>
    <!-- Popup HTML for adding a fee -->
    <?php include("dialogs/popup-addfee.inc"); ?>
    <!-- Popup HTML for starting a code review -->
    <?php include("dialogs/popup-startreview.inc"); ?>
    <!-- Popup HTML for ending a code review -->
    <?php include("dialogs/popup-endreview.inc"); ?>    
    <!-- Popup for ping user on task-->
    <?php require_once('dialogs/popup-pingtask.inc') ?>
    <!-- Popup for bid info-->
    <?php require_once('dialogs/popup-bid-info.inc') ?>
    <!-- Popups for showing user statistics-->
    <?php require_once('dialogs/popups-userstats.inc') ?>
    <!-- Popup for multipleBid info-->
    <?php require_once('dialogs/popup-multiple-bid-info.inc') ?>
    <!-- Popup for confirmation info-->
    <?php require_once('dialogs/popup-confirmation.inc') ?>
    <!-- Popup for breakdown of fees-->
    <?php require_once('dialogs/popup-fees.inc') ?>
    <!-- Popups for entering bid withdraw reason-->
    <?php require_once('dialogs/popup-withdraw-bid.inc') ?>
    <!-- Popup for Add Review Url-->
    <?php require_once('dialogs/review-url-dialog.inc') ?>
    <!-- Include Add Project -->
    <?php require_once 'dialogs/popup-addproject.inc'; ?>
    <!-- Include Paid Popup -->
    <?php require_once 'dialogs/popup-paid.inc'; ?>
    <!-- Include Budget Popup -->
    <?php require_once('dialogs/popup-budget.inc'); ?>
<?php
    // if logged in user is the mechanic on the task, get the maximum amount
    // that they can tip from their Accepted Bid
    if ($mechanic_id == $user_id) {
        $max_tip = 0;
        foreach ($fees as $fee) {
            if ($fee['desc'] == 'Accepted Bid') {
                $max_tip = $fee['amount'];
                break;
            }
        }
        if ($max_tip > 0) {
            require_once('dialogs/popup-addtip.inc');
        }
    }
?>
<?php
    if (! $user->isEligible()) {
        require_once('dialogs/popup-ineligible.inc');
    }
?>
    <!-- Include User Info -->
        <div id="user-info" title="User Info"></div>
    <!-- Footer html-->
    <?php include("footer.php"); ?>
