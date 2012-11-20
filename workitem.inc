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
    if ($worklist['project_id']) {
        $workitem_project = new Project($worklist['project_id']);
    }
    $projects = Project::getProjects();

    $allowEdit = false;
    $classEditable = "";
    if ($is_runner || ($creator_id == $user_id && $worklist['status'] == 'Suggested' && is_null($worklist['runner_id'])) ||
    ($creator_id == $user_id && $worklist['status'] == 'SuggestedWithBid' && is_null($worklist['runner_id']))) {
        $allowEdit = true;
        if ($action !="edit") {
            $classEditable = " editable";
        }
    }
    $hideFees = false;
    if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'Suggested' || $worklist['status'] == 'SuggestedWithBid') {
        $hideFees = true;
    }

    if ($worklist['status'] == 'Draft' && $creator_id != $user_id) { ?>
       <script type="text/javascript">
       alert("Draft jobs are only viewable by their creator.");
       location = "worklist.php";
       </script>
   <?php
    return;
    }

/*********************************** HTML layout begins here  *************************************/
include("head.html"); ?>
<link type="text/css" href="css/worklist.css" rel="stylesheet" />
<link type="text/css" href="css/workitem.css" rel="stylesheet" />
<link type="text/css" href="css/review.css" rel="stylesheet" />
<link type="text/css" href="css/favorites.css" rel="stylesheet" />
<link type="text/css" href="css/userinfo.css" rel="stylesheet" />
<link type="text/css" href="css/jquery.combobox.css" rel="stylesheet" />
<link type="text/css" href="css/budget.css" rel="stylesheet" />
<link type="text/css" href="css/smoothness/white-theme.lm.ui.css" rel="stylesheet" />
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
<script type="text/javascript" src="js/class.js"></script>
<script type="text/javascript" src="js/jquery.scrollTo-min.js"></script>
<script type="text/javascript" src="js/jquery.combobox.js"></script>
<script type="text/javascript" src="js/review.js"></script>
<script type="text/javascript" src="js/favorites.js"></script>
<script type="text/javascript" src="js/budget.js"></script>
<script type="text/javascript" src="js/projects.js"></script>
<script type="text/javascript" src="js/github.js"></script>
<script type="text/javascript">
    var origStatus = '<?php echo $worklist['status']; ?>';

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

function openNotifyOverlay(html, autohide) {
    $("#sent-notify").html(html);
    $("#sent-notify").attr('autohide', autohide);
    $("#sent-notify").dialog('open');
}

$(document).ready(function() {
    applyPopupBehavior();

    $('#invite-people').dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        show: 'fade',
        hide: 'fade',
        width: 350,
        height: 140
    });
    $("#invite-link").click(function() {
        $('#invite-people').dialog('open');
    });

    $('#sent-notify').dialog({
        modal: false,
        autoOpen: false,
        width: 350,
        height: 70,
        position: ['middle'],
        resizable: false,
        open: function() {
            $("#sent-notify").parent().children('.ui-dialog-titlebar').hide();
            var autoHide = $("#sent-notify").attr('autohide') ? true : false;
            if (autoHide === false) {
                setTimeout(function() {
                    $("#sent-notify").dialog("close");
                }, 3000);
            }
        }
    });

    <?php

    global $displayDialogAfterDone;

    if ($displayDialogAfterDone == true && $worklist['mechanic_id'] > 0) {
        $_SESSION['displayDialogAfterDone'] = false;
        $row = $workitem->getUserDetails($worklist['mechanic_id']);
        if (! empty($row)) {
            $nickname = $row['nickname'];
            echo "WReview.displayInPopup({user_id: " . $worklist['mechanic_id'] . ", nickname:'" . $row['nickname'] . "', withTrust: true, notify_now: 0});";
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

    var userid_info_to_display = <?php echo (isset($_REQUEST['userinfotoshow']) && isset($_SESSION['userid'])) ? $_REQUEST['userinfotoshow'] : 0;?>; //holds the userid info to display
    //if the page was loaded with request to display userinfo automatically then do it.
    if (userid_info_to_display){
        showUserInfo(userid_info_to_display);
    }
});
<!-- End User Info popup code -->
</script>
<script type="text/html" id="uploadedFiles">
<div id="accordion">
<?php require('dialogs/file-accordion.inc'); ?>
</div>
<?php if ($worklist['status'] != 'Done' && (int) $user_id > 0) : ?>
<div id="uploadButtonDiv" class="buttonContainer uploadbutton fileUploadButton">
    <span class="smbutton">Attach files</span>
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
GitHub.isConnected = '<?php echo $isGitHubConnected; ?>';
WorklistProject.repo_type = '<?php echo $workitem_project->getRepo_type(); ?>';
GitHub.applicationKey = '<?php echo GITHUB_ID; ?>';
var filterName = '.worklist';
var user_id = <?php echo !empty($user_id) ? $user_id : "''"; ?>;
var workitem_id = <?php echo $worklist['id'];?> ;
var imageArray = new Array();
var documentsArray = new Array();
var ping_who = '';
var ping_bid_id = 0;
worklistUrl = '<?php echo SERVER_URL; ?>';
Workitem.sandbox_url = '<?php echo $worklist['sandbox']; ?>';

function reply(id) {
    var commentForm = $('.commentform');
    var clone = commentForm.clone();
    commentForm.remove();
    clone.insertAfter($('#comment-' + id));
    commentMargin = $('#comment-' + id).css('margin-left');
    leftMargin= (64 + parseInt(commentMargin)) + "px";
    clone.css({'margin-left':leftMargin});
    $('.commentform input[name=comment_id]').val(id);
    $('.commentform form input[name=newcomment]').click(function(event) {
        event.preventDefault();
        postComment();
    });
    runDisableable();
}

function postComment() {
    var id = $('.commentform input[name=comment_id]').val();
    var my_comment = $('.commentform form textarea[name=comment]').val();
    var orderby = '<?php echo $order_by; ?>';
    var color = 'imOdd';
    $('.commentform form textarea[name=comment]').val('');
    $('.commentform input[name=comment_id]').val('');
    $('.commentform').css({'margin-left':0});
    var commentForm = $('.commentform');
    var clone = commentForm.clone();
    commentForm.remove();

    $.ajax({
        type: 'post',
        url: 'workitem.php',
        data: {
            job_id: workitem_id,
            worklist_id: workitem_id,
            user_id: user_id,
            newcomment: '1',
            order_by: orderby,
            comment: my_comment,
            comment_id: id
        },
        dataType: 'json',
        success: function(data) {
            var depth;
            var elementClass;
            var order = '';
            if (data.success) {
                $('#no_comments').hide();
                if (id != '') {
                    elementClass = $('#comment-' + id).attr('class').split(" ");
                    depth = Number(elementClass[0].substring(elementClass[0].indexOf('-') + 1)) + 1;
                } else {
                    depth = 0;
                }
                if (orderby == 'DESC' && depth > 0) {
                    order = 'desc';
                }
                var replyLink = (depth < 6) ? '<div class="reply-lnk">' +
                        '<a href="#commentform" onClick="reply(' + data.id + '); return false;">Reply</a>' +
                    '</div>' : '';
                var newcomment =
                '<li id="comment-' + data.id + '" class="comment depth-' + depth + ' ' + color + ' ' + order + '"><div class="comment">' +
                    '<img class="picture profile-link" src="' + data.avatar + '" title="Profile Picture - ' + data.nickname + '" onClick="showUserInfo(' + data.userid + ')" />' +
                    '<div class="comment-container"><div class="comment-info"><span class="author profile-link" onClick="showUserInfo(' + data.userid +')"><strong>'+ data.nickname +'</strong>&nbsp;<span class="date">'+
                             data.date + '</span></span></div><div class="comment-text">' +
                             data.comment +
                         '</div><div class="reply-lnk">' +
                             replyLink +
                         '</div></div></div></li>';

                if (orderby == 'DESC') {
                    if (id == '') {
                        $('.commentZone ul').prepend(newcomment);
                    } else {
                        $(newcomment).insertBefore($('#comment-' + id).prevUntil('li[class*="' + (depth -1) + '"]').andSelf().filter(":first"));
                    }
                } else {
                    if (id == '') {
                        $('.commentZone ul').append(newcomment);
                    } else {
                        $(newcomment).insertAfter($('#comment-' + id).nextUntil('li[class*="' + (depth -1) + '"]').andSelf().filter(":last"));
                    }
                }
            }
            if (orderby == 'DESC') {
                $('.commentZone ul').prepend(clone);
            } else {
                $('.commentZone ul').append(clone);
            }
            $('.commentform form input[name=newcomment]').click(function(event) {
                event.preventDefault();
                postComment();
            });
            var thinnestComment = $('div.commentZone li').thinnestSize() - 14;
            $('div.commentZone li').width(thinnestComment);
            $('div.commentform textarea').width(thinnestComment);
            $('#commentZone').css({'opacity':'1'});
            $('.commentZone ul li').each(function (i) {
                if (color == 'imEven') {
                    $(this).removeClass('imOdd');
                    $(this).addClass('imEven');
                } else {
                    $(this).removeClass('imEven');
                    $(this).addClass('imOdd');
                }
                color = (color == 'imEven') ? 'imOdd' : 'imEven';
            });
        }
    });
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
    $('#popupSelectBudget').dialog({
        autoOpen: false,
        modal: true,
        width: 450,
        resizable: false,
        height: 230,
        open: function(event, ui) {

        }
    });
    Budget.initCombo();
    $("#popupSelectBudget #confirm_budget").click(function() {
        var budget = new LiveValidation('budget-source-combo', {
            onlyOnSubmit: true ,
            onInvalid : function() {
                this.insertMessage( this.createMessageSpan() ); this.addFieldClass();
            }
        });
        budget.add( Validate.Exclusion, { within: [ 0 ], failureMessage: "You must select a budget!" });
        massValidation = LiveValidation.massValidate( [ budget ]);
        if (!massValidation) {
          return false;
        }
        $("#budget_id, #budget_id_multiple_bid").val($('#budget-source-combo').val());
        $('#popupSelectBudget').dialog("close");
        openNotifyOverlay("Accepting bids", false);
        $('#' + $('#popupSelectBudget').data("clickon")).click();
    });
    $("#popupSelectBudget #cancel_budget").click(function() {
        $("#budget_id, #budget_id_multiple_bid").val("");
        var val1 = $($('#budget-source-combo option').get(0)).attr("value");
        $('#budget-source-combo').comboBox({action:"val", param: [val1]});
        $('#popupSelectBudget').dialog("close");
    });

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
    var dialog_options = { dialogClass: 'white-theme', autoOpen: false, modal: true, maxWidth: 600, width: 450, show: 'fade', hide: 'fade' };
    $('#popup-bid').dialog(dialog_options);
    $('#popup-edit-bid-info').dialog(dialog_options);
    $('#popup-confirmation').dialog(dialog_options);
    $('#popup-review-started').dialog(dialog_options);
    $('#popup-ineligible').dialog({
        dialogClass: 'white-theme',
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
    $('#popup-bid-info').dialog({
        dialogClass: 'white-theme',
        autoOpen: false,
        modal: false,
        resizable: false,
        width: 400,
        maxHeight: 600,
        show: 'fade',
        hide: 'fade',
        open: function() {
        }
    });
    $('#popup-fee-info').dialog({ autoOpen: false, modal: false, width: 400, show: 'fade', hide: 'fade', resizable: false });
    $('#popup-multiple-bid-info').dialog({ autoOpen: false, modal: true, width: 750, position: ['center', 160], show: 'fade', hide: 'fade' });
    $('#popup-addfee').dialog({ autoOpen: false, modal: true, width: 450, show: 'fade', hide: 'fade' });
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
        height: "auto",
        resizable: false,
        position: [ 'top' ],
        show: 'fade',
        hide: 'fade',
        close: function() {
            $('#ping-msg').val('').css("height", "50px");
        }
    });
    $('#ping-msg').autogrow();
    $('#popup-reviewurl').dialog({
        autoOpen: false,
        modal: true,
        width: 410,
        show: 'fade',
        hide: 'fade',
        resizable: false,
        close: function() {
            $('select[name=quick-status]').val(origStatus);
        }
    });
    $('#popup-withdraw-bid').dialog({ autoOpen: false, width: 420, show: 'fade', hide: 'fade' });
    $('#popup-decline-bid').dialog({ autoOpen: false, width: 420, show: 'fade', hide: 'fade' });
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

   $('.commentform form input[name=newcomment]').click(function(event) {
        event.preventDefault();
        postComment();
    });
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
            active: true
            }
        );
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
                    $('#accordion').bind( "accordionchangestart", function(event, ui) {
                        $('#uploadButtonDiv').appendTo(ui.newContent);
                        $('#uploadButtonDiv').css('display', 'block');
                    });
                }
            }
        });
        setTimeout(function(){
            $(".view_bid_id").click();
        }, 500);
    <?php
            if (!empty($user_id) || $user_id < 0) {
    ?>
        setFollowingText(isFollowing);
    <?php   } else {
    ?>  $('#followingLogin').html('<a href="login.php">Login to follow this task.</a>');
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
        e.stopPropagation();
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
        e.stopPropagation();
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
            if (bidData.bid_accepted.length > 0) {
                $('#popup-bid-info #info-bid-accepted').text(bidData.bid_accepted);
            } else {
                $('#bidAcceptedRow').hide();
            }
            $('#popup-bid-info #info-bid-expires').text(bidData.bid_expires);
            $('#popup-bid-info #info-bid-amount').text(bidData.amount);
            $('#popup-bid-info #info-bid-done-in').text(bidData.done_in);
            $('#popup-bid-info #info-notes').html(bidData.notes);

<?php if ((isset($is_runner) && $is_runner) || (isset($runner_id) && $user_id == $runner_id)) : ?>
            if($('#accept_bid').length == 0) {
                $('#popup-bid-info-buttons').append('<input type="button" class="disableable" id="accept_bid_select_budget" ' +
                            '  onClick="return selectBudget(\'accept_bid\');" name="accept_bid_select_budget" value="Accept">');
                $('#popup-bid-info-buttons').append('<input type="submit" class="disableable" style="display:none;" id="accept_bid" name="accept_bid" value="Confirm Accept">');
                runDisableable();
            }
<?php endif; ?>

            if((bidData.bidder_id == user_id)){
<?php   if (!$workitem->hasAcceptedBids()) : ?>
            $('#popup-bid-info-buttons').append('<input type="button" name="edit" id="edit" value="Edit" onClick="return ConfirmEditBid()" style="padding-left:20px;padding-right:20px;">');

<?php endif; ?>
            }

<?php if ($worklist['status'] == 'Bidding' && ((isset($is_runner) && $is_runner) || (isset($runner_id) && $user_id == $runner_id))) {?>

    $('#popup-bid-info-buttons').append('<input id="ping_bidder" type="button" name="ping_bidder" value="Reply"  onClick="return pingBidder(' + bidData.id + ')" >');

<?php } ?>

<?php if($worklist['status'] != 'Done' && $worklist['status'] != 'Working' && $worklist['status'] != 'Functional' && $worklist['status'] != 'Review' && $worklist['status'] != 'Completed') {?>
            if( (bidData.bidder_id == user_id) && $('#withdraw_bid_accept').length == 0){
                $('#popup-bid-info-buttons').append('<input id="withdraw_bid_accept" type="button" name="withdraw_bid_accept" value="Withdraw" onClick="return showWithdrawBidReason()" >');
            } else if (is_runner==1 && bidData.bidder_id != user_id) {
                $('#popup-bid-info-buttons').append('<input id="decline_bid_accept" type="button" name="decline_bid_accept" value="Decline" onClick="return showDeclineBidReason()" >');
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

    $('tr.row-feelist-live').click(function() {
        $.metadata.setType("elem", "script")
        var feeData = $(this).metadata();

        // row has bid data attached so user is a bidder or a runner
        // - see table creation routine
        if (feeData.id){
            $('#popup-fee-info #info-fee-email').html('<a href="#" onClick="javascript:showUserInfo(' + feeData.user_id + ');">' + feeData.nickname + '</a>');
            $('#popup-fee-info #info-fee-created').text(feeData.fee_created);
            $('#popup-fee-info #info-fee-amount').text(feeData.amount);
            $('#popup-fee-info #info-fee-notes').html(feeData.desc);
        }
        $('#popup-fee-info').dialog('open');
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

        if ($(this).val() != null && $(this).val() != '<?php echo $worklist['status'];?>') {
            var html = "<span>Changing status from <strong><?php echo $worklist['status'];?></strong> to <strong>"
                + $(this).val() +"</strong></span>";

            if ($(this).val() == 'Functional') {
                <?php if($mechanic_id == $user_id && $promptForReviewUrl) :?>
                $('#sandbox-url').val('<?php echo $worklist['sandbox']?>');
                    $('#quick-status-review').val($(this).val());
                    $('#popup-reviewurl').dialog('open');
                <?php else : ?>
                    openNotifyOverlay(html, false);
                    $('#quick-status form').submit();
                <?php endif; ?>
            } else {
                openNotifyOverlay(html, false);
                $('#quick-status form').submit();
            }
        }
    });

<?php if((($is_runner) || ($mechanic_id == $user_id)) &&
          (strcasecmp($worklist['status'], 'Done') != 0 &&
           strcasecmp($worklist['status'], 'Completed') != 0 )) {?>
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

    function selectBudget(id) {
        $("#budget_id, #budget_id_multiple_bid").val("");
        var val1 = $($('#budget-source-combo option').get(0)).attr("value");
        $('#budget-source-combo').comboBox({action:"val", param: [val1]});
        $('#popupSelectBudget').data("clickon", id).dialog('open');
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
        if (GitHub.validate()) {
            $('#popup-confirmation-type').val(i);
            $('#popup-confirmation').dialog('open');
        } else {
            GitHub.handleUserConnect();
        }
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
      $('#popup-bid').dialog("option", "width", 695);     
      $('#popup-bid').dialog('open');
      $('#popup-bid').dialog("option", "title", "New Title");
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

    function showDeclineBidReason() {
      var bid_id = $('#popup-bid-info input[name="bid_id"]').val();
      $('#popup-bid-info').dialog('close');

      SimplePopup('#popup-decline-bid',
                'Decline Bid',
                 bid_id,
                 [['input', 'bid_id', 'keyId', 'eval']]);


        $('#popup-decline-bid').dialog('open');
        return false;
    }

    function pingBidder(id) {
        ping_who = 'bidder';
        ping_bid_id = id;
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

   function CheckCodeReviewStatus() {
      var workitem_id = <?php echo $worklist['id'];?>;
      if (WorklistProject.repo_type == 'svn') {
        $.ajax({
            type: 'post',
            url: 'getcodereviewstatus.php',
            data: {
                workitemid: workitem_id
            },
            dataType: 'json',
            success: function(data) {

                    //now check the returned data. if code review has already been started show dialog
                    if(data[0].code_review_started == 1 ) {
                        $('#popup-review-started').dialog('open');
                    }
                    else {
                        showReviewForm();
                    }

            }
        });
      } else {
          showReviewForm();
      }
   }

   function showReviewForm() {
        var workitem_id = <?php echo $worklist['id'];?>;
        var user_id = <?php echo !empty($user_id) ? $user_id : "''"; ?>;
        if (WorklistProject.repo_type == 'svn') {
            openNotifyOverlay("Authorizing sandbox for code review ...", false);
        }
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
                $("#sent-notify").dialog("close");
                if (data.success) {
                    $('#popup-startreview').dialog('open');
                } else {
                    openNotifyOverlay(data.data);
                    $("#sent-notify").css({'min-height': '80px', 'text-align': 'left'});
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                }
            },
            error: function() {
                $("#sent-notify").dialog("close");
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

                $("#accept_bid_select_budget").click(function(){
                    selectBudget('accept_multiple_bid');
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
            $('#send-ping-btn').attr("disabled", "disabled");
            var msg = $('#ping-msg').val();
            // if( $('#send-mail:checked').val() ) mail = 1;
            // always send email
            var mail = 1;
            var journal = $('#echo-journal').is(':checked') ? 1 : 0;
            var cc = $('#copy-me').is(':checked') ? 1 : 0;
            var data = {id : <?php echo $worklist_id; ?>, who : ping_who, bid_id: ping_bid_id, msg : msg, mail : mail, journal : journal, cc : cc};
            $.ajax({
                type: "POST",
                url: 'pingtask.php',
                data: data,
                dataType: 'json',
                success: function(json) {
                    if (json && json.error) {
                        alert("Ping failed:" + json.error);
                    } else {
                        $("#sent-notify").html("<span>Your ping has been sent.</span>");
                        $("#sent-notify").dialog("open");
                    }
                    $('#send-ping-btn').removeAttr("disabled");
                    $('#popup-pingtask').dialog('close');
                },
                error: function() {
                    $('#send-ping-btn').removeAttr("disabled");
                }
            });
            return false;

        });

        $('#pingMechanic').click(function() {
            if (!$.loggedin) {
                sendToLogin();
                return;
            }

            ping_who = 'mechanic';
            ping_bid_id = 0;
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
            ping_bid_id = 0;
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
            ping_bid_id = 0;
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
        <?php 
        if ($action == "edit"): ?>
            (function($) {
                if ($('#runnerBox span.runnerName') !== null) {
                    $('#runnerBox span.runnerName').css({
                        'cursor': 'pointer',
                    }).click(function() {
                        var shown = false;
                        $(this).parent().siblings().fadeOut(1000, function() {
                            if (shown != true) {
                                shown = true;
                                $('#runnerBox').css('width', '400px');
                                $('#runnerBox a#ping-r-btn').css('display', 'none');
                                $('#runnerBox span.changeRunner').fadeIn(1000, function() {
                                    $('#runnerBox span.changeRunner div input[name=changerunner]').click(function() {
                                        $(this).unbind('click');
                                        $.ajax({
                                            type: 'post',
                                            url: 'jsonserver.php',
                                            data: {
                                                action: 'changeRunner',
                                                // to avoid script loading error when not logged
                                                // userid: <?php echo($user_id); ?>,
                                                userid: <?php echo(!empty($user_id) ? $user_id : "''"); ?>,
                                                runner: $('#runnerBox span.changeRunner select[name=runner]').val(),
                                                workitem: <?php echo($worklist_id); ?>
                                            },
                                            dataType: 'json',
                                            success: function(j) {
                                                if (j.success == true) {
                                                    $('#runnerBox span#ping-r-btn').text(j.nickname);
                                                    $('#runnerBox input[name=cancel]').click();
                                                }
                                            }
                                        });
                                    });
                                    $('#runnerBox span.changeRunner div input[name=cancel]').click(function() {
                                        $('#runnerBox span.changeRunner').fadeOut(1000, function() {
                                            $('#runnerBox span.changeRunner').css('display', 'none');
                                            $('#runnerBox').css('width', '130px');
                                            $('#runnerBox a#ping-r-btn').css('display', 'block');
                                            $('#runnerBox span.runnerName').parent().siblings().fadeIn(1000);
                                        });
                                    });
                                });
                            }
                        });
                    });
                }
            })(jQuery);
        <?php 
        endif; ?>

        // applies the same size as the thinnest to all comments the show'em
        var thinnestComment = $('div.commentZone li').thinnestSize() - 14;
        $('div.commentZone li').width(thinnestComment);
        $('div.commentform textarea').width(thinnestComment);
        $('#commentZone').css({'opacity':'1'});

        //-- gets every element who has .iToolTip and sets it's title to values from tooltip.php
        setTimeout(MapToolTips, 800);

        return false;
    });

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
<style>
#welcomeInside .worklistBtn {
    color: #ffffff;
}
</style>
</head>
<body>
<?php
    require_once('header.php');
    require_once('format.php');
?>

    <div class="first-line"></div>
    <div id="info-top">
        <div class="info-summ-big">
            <div class="info-ID">
                <span id="following"><span id="followingLogin"></span></span>
            </div>
            <span id="invite-link" title="Invite people" href="javascript">Invite someone</span>
            <?php if ($allowEdit && $action != "edit"): ?>
                <span class="switchmode"><a title="Switch to Edit Mode" href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?job_id=<?php echo $worklist['id'];?>&action=edit&order=<?php echo $order_by; ?>">Edit</a>
                </span>
            <?php endif;?>
            <?php if ($action == "edit"): ?>
                <span class="switchmode"><a title="Switch to View Mode" href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?job_id=<?php echo $worklist['id'];?>&action=view&order=<?php echo $order_by; ?>">View</a>
                </span>
            <?php endif;?>
        </div>
    </div>
    <div id="info-top" class="info-summ-big-text">
        <span class="cabin">
        #<?php echo $worklist['id'];?>
        <?php
            //Show original item id if displayed item is a bug
            if($workitem->getBugJobId() > 0) {
                $originalItemLink = SERVER_URL."workitem.php?job_id=".
                                    $workitem->getBugJobId()."&action=view&order=" . $order_by;
                echo "  [ bug of <a href='".$originalItemLink."' class='worklist-item' id='workitem-".$workitem->getBugJobId()."' >".$workitem->getBugJobId()."</a> ]";
            }
            ?>
        </span>
        <span class="divider">&nbsp;</span>
        <span class="title <?php echo $classEditable;?>"><?php echo $worklist['summary'];?></span>
    </div>
    <div id="info-top" class="top-bar">
    <div class="second-line"></div>
        <?php if ($action != "edit"): ?>
            <div id="quick-status">
                <span class="info-label"><a href="help.php#ff5" target="_blank">Job status:</a></span>&nbsp;
            <?php
            if ((!$is_runner && ($mechanic_id == $user_id) && $worklist['status'] != 'Done') || $is_runner
            || ($worklist['creator_id']== $user_id && $worklist['status'] != 'Done')) :
            ?>
                <form id="searchForm" method="post" action="">
                    <input type="hidden" id="status-switch" name="status-switch" value="1" />
                    <select id="statusCombo" name="quick-status" class="project-dropdown hidden" style="display: block;">
                        <option value="<?php echo $worklist['status'];?>" selected="selected"><?php echo $worklist['status'];?></option>
                        <?php
                        if (!$is_runner && ($mechanic_id == $user_id) && $worklist['status'] != 'Done') { //mechanics
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
                        } else if ($worklist['creator_id']== $user_id && $worklist['status'] != 'Done') { //creator
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
                    <script type="text/javascript">
                    $(document).ready(function() {
                    $('#statusCombo').comboBox();
                    });
                    </script>
                </form>
            <?php else : ?>
            
                    <form id="searchForm" method="post" action="" _lpchecked="1">
                        <div class="ui-state-default ui-corner-all ui-combobox statusCombo" style="display: inline-block; " id="container-statusCombo"><input type="text" class="ui-state-default ui-combobox-textbox" style="width: 198px; " readonly="readonly" value="<?php echo $worklist['status'];?>"></div>
                        </script>
                    </form>
                
            <?php endif; ?>
          </div>
        <?php endif;?>

            <?php
                if (($user->isRunnerOfWorkitem($workitem) ||
                    (array_key_exists('userid',$_SESSION) &&
                        ($_SESSION['userid'] == $worklist['budget_giver_id'] ||
                         strpos(BUDGET_AUTHORIZED_USERS, "," . $_SESSION['userid'] . ",") !== false) ) ) && !empty($worklist['budget_id'])) {
                    if ($action =="edit"): ?>
                    <?php else: ?>
                        <div class="job-budget">
                            <div class="project-label">Budget:</div>
                            <?php echo $worklist['budget_id'] . " - " . htmlspecialchars($worklist['budget_reason']);?>
                        </div>
                    <?php endif;?>
            <?php } ?>

<?php
if ($action =="edit"):  ?>
        <form id="workitem-form" method="post" action="">
            <input type="hidden" name="save_workitem" value="save_workitem" />
            <input type="hidden" name="action" value="save_workitem" />
            <div class="project <?php if ($workitem_project->getRepo_type() == 'git') { echo ' github';} ?>">
                <span class="info-label" id="edit-project-label">Project:&nbsp;</span>
                <?php if (($is_runner || $creator_id == $user_id) && ($worklist['status'] != 'Done')):
                          $filter = new Agency_Worklist_Filter();
                          $filter->setProjectId($worklist['project_id']);
                          echo $filter->getProjectSelectbox('Select Project', 0, 'project_id', 'project_id');
                        ?>
                            <script type="text/javascript">
                                $(document).ready(function() {
                                    $('#project_id').comboBox();
                                });
                            </script>
                        <?php
                      elseif( !empty($worklist['project_id'])) :?>
                     <a href="<?php echo Project::getProjectUrl($worklist['project_id']);?>" class="edit-project">
                        <?php echo htmlspecialchars($worklist['project_name']);?>
                     </a>
                <?php else :?>
                 <span>Not assigned</span>
                <?php endif;?>

<?php else: ?>

            <div class="project <?php if ($workitem_project->getRepo_type() == 'git') { echo ' github';} ?>">
                <div class="project-label">Project:</div>
                <?php if ( !empty($worklist['project_id']) ):?>
                    <a href="<?php echo Project::getProjectUrl($worklist['project_id']);?>" target="_blank">
                        <?php echo htmlspecialchars($worklist['project_name']);?>
                    </a>
                    <?php if ( !empty($worklist['p_website']) ) {
                        $project = new Project($worklist['project_id']);
                        echo "&nbsp;<a class='project_website' href='" . $project->getWebsiteUrl() . "' target='_blank'>website</a>";
                    }?>
                <?php else :?>
                    <span>Not assigned</span>
                <?php endif;?>
 <?php endif;?>

            <?php if ($workitem_project->getRepo_type() == 'git') { ?>
            <div class="project-github">
                <h1>Requires GitHub</h1>
            </div>
            <?php } ?>
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
            <div id="left-panel">

                <div class="people-info">
                    <div id="creatorBox"><span id="pingCreator" class="creatorName" 
                    title="<?php echo isset($_SESSION['userid']) ? "Ping Creator" : "Log in to Ping Creator"; ?>">
                    <a href="#">Job creator:</a></span> <a href="#"
                    onClick="javascript:showUserInfo(<?php echo $worklist['creator_id']; ?>);">
                    <?php echo $worklist['creator_nickname'];?></a></div>
                            
                        <div id="runnerBox">
                        <?php if ($action =="edit"):  ?>
                            <span class="runnerName">Runner:</span>
                            <?php
                                if( $worklist['runner_nickname'] != 'Not funded' ) {
                                    $tooltip = isset($_SESSION['userid']) ? "Ping Runner" : "Log in to Ping Runner";
                                    echo ' <a href="#" id="ping-r-btn" title="' . $tooltip . '"onClick="javascript:showUserInfo('.$worklist['runner_id'].'">'.substr($worklist['runner_nickname'], 0, 9) . (strlen($worklist['runner_nickname']) > 9 ? '...' : '').'</a>';
                                } else {
                                    echo "&nbsp" . $worklist['runner_nickname'];
                                } ?>
                                <span class="changeRunner">
                                    <?php $runnerslist = User::getRunnerlist();
                                    echo '<select name="runner">';
                                    foreach ($runnerslist as $r) {
                                        echo '<option value="' . $r->getId() . '"' . (($worklist['runner_id'] == $r->getId()) ? ' selected="selected"' : '') . '>' . $r->getNickname() . '</option>';
                                    }
                                    echo '</select>';?>
                                    <div class="buttonContainer"><input type="button" class="smbutton" name="changerunner" value="Change Runner" /></div>
                                    <div class="buttonContainer"><input type="button" class="smbutton" name="cancel" value="Cancel" /></div>
                                </span>
                        <?php else: ?>
                            <?php
                                if( $runner_nickname != 'Not funded' ) {
                            ?>
                            <span id="pingRunner" class="runnerName" title="<?php echo isset($_SESSION['userid']) ? "Ping Runner" : "Log in to Ping Runner"; ?>"> <a href="#">Runner:</a></span>
                            <a href="#" onClick="javascript:showUserInfo(<?php echo $worklist['runner_id']; ?>);"><?php echo substr($worklist['runner_nickname'], 0, 9) . (strlen($worklist['runner_nickname']) > 9 ? '...' : '') ;?></a>
                            <?php
                                }
                                else {
                            ?>
                            <span class="runnerName" title="Ping Runner">Runner:</span> Not funded
                            <?php
                                }
                            ?>
                        <?php endif;?>
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
                            $mech='<a id="ping-btn" title="' . $tooltip . '">' . $mech . '</a>';
                        }
                        echo '<div id="mechanicBox"><span class="mechanicName">Mechanic:</span> ' . $mech . '</div>';
                    ?>
                </div>
                <div id="for_view">
<?php
if ($action =="edit"):  ?>
<!-- *** Action is Edit *** -->
                    <ul style="margin:0; padding:0; border:none;">
                <!-- Edit: Summary info -->
                        <li><span class="info-label">Summary</span><span class="info-data">
                            <?php // creator can edit it's own task if freshly added 23-MAR-2011 <godka>
                            if (($is_runner && $worklist['status']!='Done') || ($creator_id==$user_id && $worklist['status']=='SUGGESTED' || $worklist['status']=='SUGGESTEDwithBID' && is_null($worklist['runner_id']))) { ?>
                            <input type="text" size="30" class="text-field" id="summary" name="summary" value="<?php echo $worklist['summary'];?>"/>
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
                <!-- Edit: Notes textarea -->
                        <li>
                            <span class="sections">Notes</span>
                            <?php if (($is_runner || $creator_id == $user_id) && ($worklist['status'] != 'Done')): ?>
                                <span id="info-notes">
                                    <textarea name="notes" id="edit-notes" class="expandable" <?php if(count($activeBids) > 0) {echo 'disabled="disabled"';} ?> ><?php echo $worklist['notes'];?></textarea>
                                </span>
                            <?php else:?>
                                <span id="info-notes"><?php echo replaceEncodedNewLinesWithBr($worklist['notes']);?></span>
                            <?php endif;?>
                        </li>
                        <?php
                            if ($user->isRunnerOfWorkitem($workitem) ||
                                $_SESSION['userid'] == $worklist['budget_giver_id'] ||
                                strpos(BUDGET_AUTHORIZED_USERS, "," . $_SESSION['userid'] . ",") !== false) {
                        ?>
                        <li>
                        <div class="budgetArea">
                            <div class="budget-label">Budget</div>
                            <?php
                                if ($user->isRunnerOfWorkitem($workitem)) {
                            ?>
                                    <span id="budget-source-combo-area">
                                        <select id="budget-source-combo" name="budget-source-combo" class="project-dropdown hidden">
                                            <?php
                                            if ( empty($worklist['budget_id']) || $worklist['budget_id'] == 0) {
                                            ?>
                                                <option value="0" selected="selected">Select a budget</option>
                                            <?php
                                            }
                                            echo $user->getBudgetCombo($worklist['budget_id']); ?>
                                        </select>
                                    </span>

                            <?php
                                } else {
                                    if (!empty($worklist['budget_id'])) {
                                        echo $worklist['budget_id'] . " - " . htmlspecialchars($worklist['budget_reason']);
                                    } else {
                                        echo "<span>Not assigned</span>";
                                    }
                                }
                            ?>
                            <div style="clear: both;"></div>
                        </div>
                        </li>
                        <?php } ?>
                    <!-- Edit: Sandbox textarea -->
                    <?php if ($mechanic_id > 0):?>
                    <li>
                        <div class="sandbox-container">
                            <span class="functional-label" >Sandbox:</span><br />
                            <?php if(($is_runner || $creator_id == $user_id) && ($worklist['status'] != 'Done')): ?>
                            <input type="text" size="30" class="text-field" name="sandbox" id="sandbox" value="<?php echo htmlspecialchars($worklist['sandbox']);?>" />
                             <?php elseif ( !empty($worklist['sandbox']) ):?>
                                <a href="<?php echo $worklist['sandbox'];?>" target="_blank">
                                    <?php echo htmlspecialchars($worklist['sandbox']);?>
                                </a>
                            <?php else :?>
                                <span>Not assigned</span>
                            <?php endif;?>
                        </div>
                    </li>
                    <?php endif;?>

                    </ul>
                    <?php if($allowEdit && $action=="edit"): ?>
                        <div class="buttonContainer">
                            <input type="submit" class="smbutton" value="Save" name="save_workitem" id="save_workitem" />
                        </div>
                    <?php endif;?>
                </form>
<!-- *** End of Action is Edit *** -->
<?php else: ?>
<!-- *** Action is View *** -->
<?php if (! empty($status_error)) : ?>
                    <span class="LV_invalid status-error"><?php echo $status_error; ?></span>
<?php endif; ?>
                <!-- View: Key People info -->
                    <!-- View: Notes textarea -->
                        <span class="sections">Notes</span>
                        <p class="notestext"><?php echo replaceEncodedNewLinesWithBr(linkify($worklist['notes'])); ?></p>
                    <!-- View: Sandbox textarea -->
                    <?php if ($mechanic_id > 0):?>
                        <div class="sandbox-container">
                            <?php if((strcasecmp($worklist['status'], 'Working') == 0 ||
                                strcasecmp($worklist['status'], 'Review') == 0 ||
                                strcasecmp($worklist['status'], 'Functional') == 0) && (($is_runner) || ($mechanic_id == $user_id))) {?>
                                <span class="iToolTip changeSBurl functional-label" id="edit_review_url">Sandbox:</span><br />
                            <?php } else {?>
                                <span class="functional-label">Sandbox:</span><br />
                            <?php }?>
    
                            <?php if ( !empty($worklist['sandbox']) ):?>
                                <a href="<?php echo $worklist['sandbox'];?>" target="_blank">
                                      <?php
                                      if (strlen(htmlspecialchars($worklist['sandbox'])) > 64) {
                                            echo substr(htmlspecialchars($worklist['sandbox']), 0, 64) . "...";
                                      } else {
                                          echo htmlspecialchars($worklist['sandbox']);
                                      }
    
                                      ?>
                                </a>
                                <?php if (($worklist['status'] == 'Functional' || $worklist['status'] == 'Review')
                                        && $worklist['sandbox'] != 'N/A' || ($worklist['status'] == 'Working' && ($user->isRunnerOfWorkitem($workitem) || $user->getId() == $mechanic_id))) { ?>
                                <div class="buttonContainer view-sandbox">
                                     <input type="submit" class="smbutton" id="view-sandbox" value="View diff" />
                                </div>
                                <?php } ?>
                            <?php else :?>
                                <span>Not assigned</span>
                            <?php endif;?>
                        </div>
                    <?php endif;?>
<!-- *** End of Action IF *** -->
<?php endif;?>
            <div class="commentZone">
                <div class="info-comments">Comments &amp; Activity</div>

                <?php
                if (!empty($user_id) && ($worklist['status'] != 'Done')) : ?>
                <a class="info-comments-scroll-entry" href="#commentform">See most recent</a>
                <?php endif ?>

                <?php if (!empty($user_id) && ($worklist['status'] != 'Done') && $order_by == "DESC") : ?>
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
                        ?><div id="no_comments">No comments!</div><?php
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
                            ?><li id="comment-<?php echo $comment['id']; ?>" class="depth-<?php echo $comment['depth']; ?> <?php ($it % 2) ? print 'imOdd' : print 'imEven'; $it++; ?> <?php if ($order_by == "DESC" && $comment['depth'] > 0) echo 'desc'; ?>">
                                <div class="comment">
                                    <image class="picture profile-link" src="<?php echo $comment['comment']->getUser()->getAvatar(); ?>" title="Profile Picture - <?php echo($comment['comment']->getUser()->getNickname()); ?>" onClick="javascript:showUserInfo('<?php echo $comment['comment']->getUser()->getId(); ?>')" />
                                    <div class="comment-container">
                                        <div class="comment-info">
                                            <span class="author profile-link" onClick="javascript:showUserInfo('<?php echo $comment['comment']->getUser()->getId(); ?>')"><strong><?php echo $comment['comment']->getUser()->getNickname(); ?></strong>&nbsp;
                                                <span class="date"><?php echo relativeTime(strtotime($comment['comment']->getDate())-time());?></span>
                                            </span>
                                        </div>
                                        <div class="comment-text">
                                        <?php echo replaceEncodedNewLinesWithBr(linkify($comment['comment']->getComment())); ?>
                                        <?php if (!empty($user_id) && $comment['depth'] < 6 && ($worklist['status'] != 'Done')) : ?>
                                        </div>
                                        <div class="reply-lnk">
                                            <a href="#commentform" onClick="reply(<?php echo $comment['id']; ?>);return false;">Reply</a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php
                        endforeach;
                    endif;
                ?>
                </ul>
                <?php if (!empty($user_id) && ($worklist['status'] != 'Done') && $order_by == "ASC") : ?>
                <div class="commentform"> <a name="commentform"></a>
                    <form action="" method="post">
                        <input type="hidden" name="worklist_id" value="<?php echo $worklist['id']; ?>" />
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
                        <input type="hidden" name="order_by" value="ASC" />
                        <input type="hidden" name="comment_id" value="" />
                        <textarea name="comment" class="expand100-300" cols="20" rows="4" size="33"></textarea>
                        <br />
                        <div class="button-container"><div class="buttonContainer"><input type="submit" class="smbutton" name="newcomment" value="Comment" class="disableable" /></div></div>
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

<?php
if ($action =="edit"):  ?>

            <div class="skills">
                <span class="skills-label">Skills this job requires:</span><br/>
                <?php if (count($workitem->getSkills()) > 0) : ?>
                <input type="text" size="60" class="text-field" name="skills" id="skills" value="<?php echo implode(', ', $workitem->getSkills()) . ', '; ?>" /></span>
                <?php else: ?>
                <input type="text" size="60" class="text-field" name="skills" id="skills" value="" /></span>
                <?php endif; ?>
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
            </div>

<?php else: ?>

                <div class="skills">
                    <div class="skills-label">Skills this job requires:</div>
                    <?php if (count($workitem->getSkills()) > 0) : ?>
                    <?php echo implode(', ', $workitem->getSkills()); ?>
                    <?php endif; ?>
                </div>

<?php endif;?>
                            <?php if ($user_id > 0 && $worklist['status'] == 'Review') { ?>
                            <div class="code-review">
                            <span>Time for a code review!</span>
                            The code written for this job is ready to be reviewed.<br />
                            <b><u>View the diff</u></b> to see the code, then start the review ...
                            <div class="buttonContainer feebutton">
                                    <?php if ($workitem->getCRStarted() !=1) {
                                        if ($mechanic_id != $user_id) {
                                            if ($user->isEligible()) {
                                                if(hasRights($user_id, $workitem)) { ?>
    
                                                    <input class="iToolTip cR smbutton" type="submit" value="Start Code Review" onClick="return CheckCodeReviewStatus();"/>
                                        <?php   } else { ?>
                                                    <input class="iToolTip cRDisallowed smbutton" type="submit" value="Start Code Review" onClick="return false;"/>
                                        <?php   }
                                            }
                                        }
    
                                    } else if($workitem->getCRStarted() == 1 && $workitem->getCRCompleted() != 1) {
                                        if ($user->isEligible()) {
                                            if(hasRights($user_id, $workitem)) {
                                                if($user_id == $workitem->getCReviewerId() || $is_runner || $user_id == $workitem->getCreator() || $user_id == $mechanic_id) {?>
                                                    <input class="iToolTip endCr smbutton" type="submit" value="End Code Review" onClick="return showEndReviewForm();"/>
                                        <?php   }
                                            }
                                        }
                                    } else if ($workitem->getCRCompleted()) { ?>
                                        <input class="iToolTip cRSetToFunctional smbutton" type="submit" value="Start Code Review" onClick="return false;"/>
                                <?php   } ?>
                                <div id="review-pointer"></div>
                                </div>
                            </div>
                            <?php }?>

            <div id="for_view">
                <div class="moneyZone">
                    <div id="bid-panel">
                        <table width="100%" class="table-bids">
                            <caption class="table-caption" >
                                <div class="noteWrapper"><span class="sections">Bids</span></div>
                                <?php if ($user_id) : ?>
                                    <?php if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'SuggestedWithBid' || ($worklist['status'] == 'Suggested' && $worklist['creator_id']== $user_id)) : ?>
                                        <div class="buttonContainer bidbutton">
                                            <?php if ($user->isEligible()) : ?>
                                            <input type="submit" class="smbutton" value="Add my bid" onClick="return showConfirmForm('bid');" />
                                            <?php else: ?>
                                            <input type="submit" class="smbutton" value="Add my bid" onClick="return showIneligible('bid');" />
                                            <?php endif; ?>
                                        </div>
                                        <?php if(! empty($bids) && ($is_runner || $user_id == $workitem->getRunnerId()) && count($bids) >1 && !$workitem->hasAcceptedBids()
                                              && ((($workitem->getStatus()) == "Bidding") || $workitem->getStatus() == "SuggestedWithBid")):?>
                                            <div class="buttonContainer bidbutton">
                                                <input type="submit" value="Accept Multiple"  id="btnAcceptMultiple" onClick="javascript:AcceptMultipleBidOpen();"/>
                                            </div>
                                        <?php endif;?>
                                    <?php endif;?>
                                <?php endif;?>
                                <?php if ($user_id > 0 && $hideFees) { ?>
                                <?php } else { ?>
                                    <?php if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'SuggestedWithBid') { ?>
                                    <div class="buttonContainer bidbutton ">
                                        <input style="cursor:pointer;" class="smbutton" type="button" value="Login to Bid" onClick="sendToLogin(); return false;" />
                                    </div>
                                    <?php } ?>
                                <?php } ?>
                            </caption>
                            <thead>
                                <tr class="table-hdng">
                                    <td><span class="table-back"><span>Who</span></span></td>
                                    <td><span class="table-back"><span>$</span></span></td>
                                    <td><span class="table-back"><span>Done in ...</span></span></td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(empty($bids)) { ?>
                                <tr>
                                    <td style="text-align: center;" colspan="4"><span class="table-back"><span>No bids yet.</span></span></td>
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
                                    $canSeeBid = $user->getIs_admin() == 1 || $user->isRunnerOfWorkitem($workitem) || $user->getId() == $bid['bidder_id'] || ($worklist['status'] == 'SUGGESTEDwithBID' && $is_runner);
                                    $row_class = "";
                                    $row_class .= ($user_id) ? 'row-bidlist-live ' : '' ;
                                    $row_class .= ($view_bid_id == $bid['id']) ? ' view_bid_id ' : '' ;
                                    $row_class .= ($row++ % 2) ? 'rowodd ' : 'roweven ';
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
                                    <span class="table-back">
                                        <span>
                                            <?php if ($canSeeBid) { ?>
                                                <a href="#" bidderId="<?php echo $bid['bidder_id'];?>" class="CreatorPopup"><?php echo getSubNickname($bid['nickname']);?></a>
                                            <?php } else echo $bid['nickname']; ?>
                                        </span>
                                    </span>
                                    </td>
                                    <td ><span class="table-back"><span>$ <?php echo $bid['bid_amount'];?></span></span></td>
                                    <td ><span class="table-back"><span><?php echo $bid['done_in'];?></span></span></td>
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
                                </tr>
                                <?php } ?>
                            <?php } ?>
                        <?php } ?>
                            </tbody>
                        </table>
                <!-- End of div bid-panel -->
                    </div>

                        <?php if ($user_id > 0) { ?>
                        <div id="fee-panel" >
                            <table width="100%" class="table-fees" cellpadding="0" cellspacing="0">
                                <caption class="table-caption" >
                                <div class="noteWrapper"><span class="sections">Fees</span></div>
                            <?php if($user_id && ($worklist['status'] != 'Done')): ?>

                                <?php if ($mechanic_id == $user_id) : ?>
                                    <div class="buttonContainer feebutton"><input class="iToolTip addTip smbutton" type="submit" value="Tip User" /></div>
                                <?php endif; ?>

                                <?php if ($user_id): ?>
                                    <div class="buttonContainer feebutton">
                                        <?php if ($user->isEligible()) : ?>
                                            <input class="iToolTip addFee smbutton" type="submit" value="Add a Fee" onClick="return showConfirmForm('fee');"/>
                                        <?php else: ?>
                                            <input class="iToolTip addFee smbutton" type="submit" value="Add a Fee" onClick="return showIneligible('fee');"/>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif;?>
                                </caption>
                                <thead>
                                <tr class="table-hdng">
                                    <td><span class="table-back"><span>Who</span></span></td>
                                    <td><span class="table-back"><span>$</span></span></td>
                                    <td><span class="table-back"><span>What</span></span></td>
                                    <td><span class="table-back"><span>When</span></span></td>
                                    <td><span class="table-back"><span>Paid</span></span></td>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if(empty($fees)): ?>
                                    <tr>
                                        <td style="text-align: center;" colspan="5">No fees yet.</td>
                                    </tr>
                                <?php else: $row = 1;
                                    foreach($fees as $fee): ?>
                                    <tr class="row-feelist-live <?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?> ">
                                        <script type="data"><?php echo
                                                "{id: {$fee['id']}, " .
                                                "nickname: '{$fee['nickname']}', " .
                                                "user_id: '{$fee['user_id']}', " .
                                                "amount: '{$fee['amount']}', " .
                                                "fee_created: '{$fee['date']}', " .
                                                "desc:\"" .  replaceEncodedNewLinesWithBr($fee['desc']) . "\"}";?>
                                        </script>
                                        <td class="nickname">
                                            <span class="table-back">
                                                <span>
                                                    <a href="#" onClick="javascript:showUserInfo(<?php echo $fee['user_id'];?>); event.stopPropagation();"
                                                        title="<?php echo $fee['nickname']; ?>">
                                                        <?php echo getSubNickname($fee['nickname'], 8);?>
                                                    </a>
                                                </span>
                                            </span>
                                        </td>
                                        <td><span class="table-back"><span>$<?php echo $fee['amount'];?></span></span></td>
                                        <td class="pre fee-description"><span class="table-back"><div class="arrow"></div></span></td>
                                        <td class="when"><span class="table-back"><span>
                                        <?php
                                            $date = explode("/", $fee['date']);
                                            echo date( "M j", mktime(0, 0, 0, $date[0], $date[1], $date[2]));
                                        ?></span></span></td>
                                        <td>
                                            <span class="table-back">
                                                <span>
                                                    <?php if($is_payer): ?>
                                                    <a href="#" class = "paid-link" id = "feeitem-<?php echo $fee['id'];?>"><?php echo $fee['paid'] == 0 ? "No" : "Yes";?></a>
                                                    <?php else: echo $fee['paid'] == 0 ? "No" : "Yes";?>
                                                    <?php endif;?>
                                                    <?php if(($is_runner ||$user_id == $workitem->getRunnerId() || $user_id == $fee['user_id']) &&($user_id && empty($fee['paid']))):?>
                                                    - <a href="#" id="wd-<?php echo $fee['id'];?>" class="wd-link" title='Delete Entry'>delete</a>
                                                    <?php endif;?>
                                                </span>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="bid-notes">
                                            <span><?php
                                                $feeDesc = replaceEncodedNewLinesWithBr($fee['desc']);
                                                echo '<b>'.truncateText($feeDesc).'</b><br /><br />';
                                            ?>
                                            <?php if (($worklist['status'] == 'Review' || $worklist['status'] == 'Completed' || $worklist['status'] == 'Done')&& ($fee['desc'] == 'Accepted Bid')) { ?>
                                            <b>Bid Notes:</b> <?php echo preg_replace("/\r?\n/", "<br />", $fee['bid_notes']);?>
                                            <?php }?></span>
                                            <div class="end-line"></div>
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                <?php endif;?>
                                </tbody>
                            </table>
                            <br />
                    <!-- End of div fee-panel -->
                            <form id="withdraw" method="post" action="" >
                                <input type="hidden" name="action" value="withdraw_bid" />
                                <input type="hidden" class="fee_id" name="fee_id" value="" />
                            </form>
                            <div style="clear: both"></div>
                        </div>
                    <?php } else if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'SuggestedWithBid') { ?>
                    <div class="buttonContainer">
                        <input style="cursor:pointer;" type="button" value="Login to Bid" onClick="sendToLogin(); return false;" />
                    </div>
                    <?php } ?>                        
                <!-- End of div fee-panel -->
            <!-- End of div moneyZone -->
                </div>
                <div id="uploadPanel"> </div>
                <?php if(JOURNAL_EXISTS) { // include the journal dropdown if the journal setting is true ?>
                <div id="journalPanel">
                        <h3><a href="#" id="loadJournalInfo">Related Chat Discussion</a></h3>
                        <div id="journalChat">
                            <?php
                                echo getTaskPosts($worklist['id']);
                             ?>
                        </div>
                </div>
                <?php } ?>
        <!-- End of for_view-->
            </div>
<!-- End of div right-panel-->
        </div>
<!-- End of div pageContent -->
    </div>

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
    <!-- Popup HTML for informing user that code review is already started review -->
    <?php include("dialogs/code-review-already-started.inc"); ?>
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
    <!-- Popup for fee info-->
    <?php require_once('dialogs/popup-fee-info.inc') ?>
    <!-- Popups for entering bid withdraw reason-->
    <?php require_once('dialogs/popup-withdraw-bid.inc') ?>
    <!-- Popups for entering bid decline reason-->
    <?php require_once('dialogs/popup-decline-bid.inc') ?>
    <!-- Popup for Add Review Url-->
    <?php require_once('dialogs/review-url-dialog.inc') ?>
    <!-- Include Paid Popup -->
    <?php require_once 'dialogs/popup-paid.inc'; ?>
    <!-- Include Budget Popup -->
    <?php require_once('dialogs/popup-budget.inc'); ?>
    <?php require_once('dialogs/budget-expanded.inc'); ?>
    <!-- Invite people popup -->
    <?php require_once 'dialogs/popup-invite-people.inc'; ?>

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
<!-- Footer html-->
<?php include("footer.php");


function  getTaskPosts($item_id) {

    require_once('chat.class.php');
    $query = $item_id;
    $response = new AjaxResponse($chat);
    try {
        $data = $response->latestFromTask($item_id);
    } catch(Exception $e) {
        $data['error'] = $e->getMessage();
    }
    return $data['html'];
}

?>
