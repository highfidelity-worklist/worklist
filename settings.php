<?php
//  vim:ts=4:et

//
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

ob_start();
include("config.php");
include("class.session_handler.php");
include("check_session.php");
include("functions.php");
include("timezones.php");
include("countrylist.php");
include("smslist.php");
include_once("send_email.php");
require_once("lib/Sms.php");

require_once ("class/Utils.class.php");
require_once ("class/Error.class.php");

$userId = getSessionUserId();
if ($userId) {
    $user = new User();
    $user->findUserById($userId);
}

$msg="";
$company="";

$saveArgs = array();
$messages = array();
$errors = 0;
$error = new Error();

// process updates to user's settings
if (isset($_POST['save_account'])) {

    $updateNickname = false;
    $updatePassword = false;

    // check if phone was updated
    if (isset($_POST['phone_edit']) || isset($_POST['int_code']) ||isset($_POST['timezone'])) {
        $saveArgs = array('int_code'=>0, 'phone'=>1, 'country'=>1, 'smsaddr'=>1);

        foreach ($saveArgs as $arg=>$esc) {
            $$arg = ($esc ? $_POST[$arg] : intval($_POST[$arg]));
        }

        if (isset($_POST['city'])) {
            $city = $_POST['city'];
            $saveArgs['city'] = 1;
        } else {
            // TODO: Actually return the error to user, rather than rely on javascript validation
            $error->setError('You are required to choose a city');
        }

        $provider = mysql_real_escape_string($_POST['provider']);
        $saveArgs['provider'] = 0;
        $is_uscitizen = ($_POST['country'] == 'US' ? 1 : 0);
        $saveArgs['is_uscitizen'] = 0;

        $sms_flags = 0;
        if (!empty($_POST['journal_alerts'])) $sms_flags |= SMS_FLAG_JOURNAL_ALERTS;
        if (!empty($_POST['bid_alerts'])) $sms_flags |= SMS_FLAG_BID_ALERTS;
        $saveArgs['sms_flags'] = 0;

        $timezone = mysql_real_escape_string(trim($_POST['timezone']));
        $saveArgs['timezone'] = 0;

        $notifications = 0;
        $my_bids_notify = !empty($_POST['my_bids_notify']) ? Notification::MY_BIDS_NOTIFICATIONS : 0;
        $ping_notify = !empty($_POST['ping_notify']) ? Notification::PING_NOTIFICATIONS : 0;
        $review_notify = !empty($_POST['review_notify']) ? Notification::REVIEW_NOTIFICATIONS : 0;
        $bidding_notify = !empty($_POST['bidding_notify']) ? Notification::BIDDING_NOTIFICATIONS : 0;
        $my_review_notify = !empty($_POST['my_review_notify']) ? Notification::MY_REVIEW_NOTIFICATIONS : 0;
        $my_completed_notify = !empty($_POST['my_completed_notify']) ? Notification::MY_COMPLETED_NOTIFICATIONS : 0;
        $notifications = Notification::setFlags($review_notify, $bidding_notify, $my_review_notify, $my_completed_notify, $my_bids_notify, $ping_notify);
        $saveArgs['notifications'] = 0;

        // if user is new - create an entry for him
        // clear $saveArgs so it won't be updated for the second time
        if ($_SESSION['new_user']) {

            $user_id = intval($_SESSION['userid']);
            $username = $_SESSION['username'];
            $nickname = $_SESSION['nickname'];

            $sql = " INSERT INTO " . USERS . " 
                        (`id`, `username`, `nickname`, `timezone`, `country`, `is_uscitizen`, `provider` ,`phone`, `int_code`, `smsaddr`, `sms_flags`, `notifications`, `is_active`, `confirm`)
                        VALUES ('$user_id', '$username', '$nickname', '$timezone', '$country', '$is_uscitizen', '$provider', '$phone', '$int_code', '$smsaddr', '$sms_flags', '$notifications', '1', '1')";
            mysql_unbuffered_query($sql);
            $_SESSION['new_user'] = '';
            $saveArgs = array();
        } else {
            $messages[] = "Your country/phone settings have been updated.";
        }
    }

    // if nickname is different - update it through login call
    $nickname = trim($_POST['nickname']);
    if($nickname != $_SESSION['nickname']) {
        $ret = Utils::updateLoginData(array('nickname' => $nickname), true, false);
        if ($ret->error == 1) {
            // TODO: Actually send error back to browser, if necessary
            $error->setError($ret->message);
        } else {
            if(!$_SESSION['new_user']) {
                $sql = "UPDATE " . USERS . " SET nickname='" . mysql_real_escape_string($nickname) . "' WHERE id ='" . $_SESSION['userid'] . "'";
                mysql_query($sql);
                $_SESSION['nickname'] = $nickname;
                $messages[] = "Your nickname is now '$nickname'.";
            }
        }
    }

} else if (isset($_POST['save_personal'])) {
    $about = isset($_POST['about']) ? strip_tags(substr($_POST['about'], 0, 150)) : "";
    $skills = isset($_POST['skills']) ? strip_tags($_POST['skills']) : "";
    $contactway = isset($_POST['contactway']) ? strip_tags($_POST['contactway']) : "";

    $saveArgs = array('about'=>1, 'skills'=>1, 'contactway'=>1);
    $messages[] = "Your personal information has been updated.";
} else if (isset($_POST['save_payment'])) {
    $paypal = 0;
    $paypal_email = '';
    // defaulting to paypal at this stage
    $payway = 'paypal';
    if ($_POST['paytype'] == 'paypal') {
        $paypal = 1;
        $payway = "paypal";
        $paypal_email = isset($_POST['paypal_email']) ? mysql_real_escape_string($_POST['paypal_email']) : "";
    } else if ($_POST['paytype'] == 'other') {
        $payway = '';
    }

    $saveArgs = array('paypal' => 0, 'paypal_email' => 0, 'payway' => 1);
    $messages[] = "Your payment information has been updated.";

    $paypalPrevious = $user->getPaypal_email();

    // user deleted paypal email, deactivate
    if (empty($paypal_email)) {
        $user->setPaypal_verified(false);
        $user->setPaypal_email('');
        $user->save();
    // user changed paypal address
    } else if ($paypalPrevious != $paypal_email) {    
        $paypal_hash = md5(date('r', time()));;

        // generate email
        $subject = "Your Paypal address has changed";

        $link = SECURE_SERVER_URL . "confirmation.php?pp=" . $paypal_hash . "&ppstr=" . base64_encode($paypal_email);
        $worklist_link = SERVER_URL . "worklist.php";

        $body  = '<p>Dear ' . $user->getNickname() . ',</p>';
        $body .= '<p>Please confirm your Paypal address to activate payments on your account and enable you to start placing bids in the <a href="' . $worklist_link . '">Worklist</a>.</p>';
        $body .= '<p><a href="' . $link . '">Click here to confirm your Paypal address</a></p>';

        $plain  = 'Dear ' . $user->getNickname() . ',' . "\n\n";
        $plain .= 'Please confirm your Paypal address to activate payments on your accounts and enable you to start placing bids in the Worklist.' . "\n\n";
        $plain .= $link . "\n\n";
                
        $confirm_txt = "An email containing a confirmation link was sent to your Paypal address. Please click on that link to verify your Paypal address and activate your account.";
        if (! sl_send_email($paypal_email, $subject, $body, $plain)) { 
            error_log("signup.php: sl_send_email failed");
            $confirm_txt = "There was an issue sending email. Please try again or notify admin@lovemachineinc.com";
        }

        $user->setPaypal_verified(false);
        $user->setPaypal_hash($paypal_hash);
        $user->setPaypal_email($paypal_email);
        $user->save();
    }
}

if (!empty($saveArgs)) {

    $sql = "UPDATE `".USERS."` SET ";
    foreach ($saveArgs as $arg=>$esc) {

        if ($esc) $$arg = mysql_real_escape_string(htmlspecialchars($$arg));

        if (is_int($$arg)) {
            $sql .= "`$arg`=".$$arg.",";
        } else {
            $sql .= "`$arg`='".$$arg."',";
        }
    }
    $sql = rtrim($sql, ',');
    $sql .= " WHERE id = '${_SESSION['userid']}'";
    mysql_query($sql);

// Email user
    if (!empty($messages)) {
        $to = $_SESSION['username'];
        $subject = "Settings";
        $body  = "<p>Congratulations!</p>";
        $body .= "<p>You have successfully updated your settings with Worklist <br/>";
        foreach ($messages as $msg) {
            $body .= "&nbsp;&nbsp;$msg<br/>";
        }
        if(!sl_send_email($to, $subject, $body)) { error_log("settings.php: sl_send_email failed"); }

        $msg="Account updated successfully!";
    }


    if (isset($confirm_txt) && ! empty($confirm_txt)) {
        echo $confirm_txt;
    } else {
        exit;
    }

    // exit on ajax post - if we experience issues with a blank settings page, need to look at the ajax submit functions
    exit;

}

// getting userInfo to prepopulate fields

    if(empty($_SESSION['new_user'])) {
        $qry = "SELECT * FROM ".USERS." WHERE id='".$_SESSION['userid']."'";
        $rs = mysql_query($qry);
        if ($rs) {
            $userInfo = mysql_fetch_array($rs);
        }
    }
/*********************************** HTML layout begins here  *************************************/

include("head.html");
?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->

<!--Added worklist.css to solve stylesheet issues for settings.php-->
<link type="text/css" href="css/worklist.css" rel="stylesheet" />
<link type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<link type="text/css" href="css/settings.css" rel="stylesheet" />

<script type="text/javascript" src="js/skills.js"></script>
<script type="text/javascript" src="js/userSkills.js"></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/sendlove.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/ajaxupload-3.6.js"></script>
<script type="text/javascript">
    var nclass;

    function validateUploadImage(file, extension) {
        if (!(extension && /^(jpg|jpeg|gif|png)$/i.test(extension))) {
            // extension is not allowed
            $('span.LV_validation_message.upload').css('display', 'none').empty();
            var html = 'This filetype is not allowed!';
            $('span.LV_validation_message.upload').css('display', 'inline').append(html);
            // cancel upload
            return false;
        }
    }
    
    function completeUploadImage(file, data) {
        $('span.LV_validation_message.upload').css('display', 'none').empty();
        if (!data.success) {
            $('span.LV_validation_message.upload').css('display', 'inline').append(data.message);
        } else {
            window.location.reload();
        }
    }
    
    function validateLocalUpload(file, extension) {
        nclass = '.uploadnotice-local';
        return validateUpload(file, extension);
    }
    function validateW9Upload(file, extension) {
        nclass = '.uploadnotice-w9';
        return validateUpload(file, extension);
    }
    function validateUpload(file, extension) {
        if (! (extension && /^(pdf)$/i.test(extension))) {
            // extension is not allowed
            $(nclass).empty();
            var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-error ui-corner-all">' +
                            '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                            '<strong>Error:</strong> This filetype is not allowed. Please upload a pdf file.</p>' +
                        '</div>';
            $(nclass).append(html);
            // cancel upload
            return false;
        }
    }

    function completeUpload(file, data) {
        $(nclass).empty();
        if (data.success) {
            var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-highlight ui-corner-all">' +
                            '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>' +
                            '<strong>Info:</strong> ' + data.message + '</p>' +
                        '</div>';
        } else {
            var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-error ui-corner-all">' +
                            '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                            '<strong>Error:</strong> ' + data.message + '</p>' +
                        '</div>';
            this.enable();
        }
        $(nclass).append(html);
    }

    function saveSettings(type) {
        var values;
        if (type == 'account') {
            var massValidation = LiveValidation.massValidate( [ nickname, city ]);
            if (massValidation) {
                values = { 
                    int_code: $('#int_code').val(),
                    phone: $('#phone').val(),
                    phone_edit: $('#phone_edit').val(),
                    country: $('#country').val(),
                    city: $('#city').val(),
                    smsaddr: $('#smsaddr').val(),
                    provider: $('#provider').val(),
                    timezone: $('#timezone').val(),
                    journal_alerts: $('#journal_alerts').attr('checked') ? 1 : 0,
                    bid_alerts: $('#bid_alerts').attr('checked') ? 1 : 0,
                    nickname: $('#nickname').val(),
                    save_account: 1,
                    username: $('#username').val(),
                    my_bids_notify: $('input[name="my_bids_notify"]').attr('checked') ? 1 : 0,
                    ping_notify: $('input[name="ping_notify"]').attr('checked') ? 1 : 0,
                    review_notify: $('input[name="review_notify"]').attr('checked') ? 1 : 0,
                    bidding_notify: $('input[name="bidding_notify"]').attr('checked') ? 1 : 0,
                    my_review_notify: $('input[name="my_review_notify"]').attr('checked') ? 1 : 0,
                    my_completed_notify: $('input[name="my_completed_notify"]').attr('checked') ? 1 : 0
                };
            } else {
                return false;
            }
        } else if (type == 'personal') {
            values = {
                about: $("#about").val(),
                skills: $("#skills").val(),
                contactway: $("#contactway").val(),
                save_personal: 1
            }
        } else if (type == 'payment') {
            var massValidation = LiveValidation.massValidate( [ paypal ]);
            if (massValidation) {
                values = {
                    paytype: $("#paytype").val(),
                    paypal_email: $("#paypal_email").val(),
                    payway: $("#payway").val(),
                    save_payment: 1
                }
            } else {
                return false;
            }
        }

        $('.error').text('');

        $.ajax({
            type: "POST",
            url: 'settings.php',
            data: values,
            dataType: 'html',
            success: function(json) {
                if (type == 'payment') {
                    $('#msg-'+type).html('Account settings saved' + '<br/>' + json);
                } else {
                    $('#msg-'+type).text('Account settings saved!' );
                }
            },
            error: function(xhdr, status, err) {
                $('#msg-'+type).text('We were unable to save your settings. Please try again.');
            }
        });
    }

    function smsSendTestMessage() {
        var int_code = $('#int_code').val();
        var phone = $('#phone').val();
        if (int_code != '' && phone != '') {
            $.ajax({
                type: "POST",
                url: 'jsonserver.php',
                data: {
                    action: 'sendTestSMS',
                    phone: int_code + phone
                },
                dataType: 'json'
            });
            alert('Test SMS Sent');
        } else {
            alert('Please enter a valid telephone number.');
        }
        return false;
    }
    function ChangePaymentMethod() {
        var paytype = $('#paytype').val();
        paypal.enable();
        // validation disabled: payway.enable();
        if (paytype == 'paypal') {
            $('#paytype-paypal').show();
            $('#paytype-other').hide();
        } else if (paytype == 'other') {
            $('#paytype-paypal').hide();
            $('#paytype-other').show();
        } else {
            $('#paytype-paypal').hide();
            $('#paytype-other').hide();
        }
    }
    $(document).ready(function () {
<?php if (isset($_REQUEST['ppconfirmed'])) : ?>
        $('<div id="popup-confirmed"><div class="content"></div></div>').appendTo('body');
        $('#popup-confirmed').dialog({
            modal: true,
            title: 'Your Paypal address was confirmed',
            autoOpen: true,
            width: 300,
            position: ['top'],
            open: function() {
                $('#popup-confirmed .content').html('Thank you for confirming your Paypal address.<br/><br/>You can now bid on items in the Worklist!<br/><br/><input style="" class="closeButton" type="button" value="Close" />');
                $('#popup-confirmed .closeButton').click(function() {
                    $('#popup-confirmed').dialog('close');
                });
            }
        });
<?php endif; ?>

        var pictureUpload = new AjaxUpload('profilepicture', {
            action: 'api.php',
            name: 'profile',
            data: { action: 'uploadProfilePicture', api_key: '<?php echo API_KEY; ?>', userid: '<?php echo $_SESSION['userid']; ?>' },
            autoSubmit: true,
            hoverClass: 'imageHover',
            responseType: 'json',
            onSubmit: validateUploadImage,
            onComplete: completeUploadImage
        });
        var user = <?php echo('"' . $_SESSION['userid'] . '"'); ?>;

        new AjaxUpload('formupload', {
            action: 'jsonserver.php',
            name: 'Filedata',
            data: { action: 'w9Upload', userid: user },
            autoSubmit: true,
            responseType: 'json',
            onSubmit: validateW9Upload,
            onComplete: completeUpload
        });

        new AjaxUpload('formupload-local', {
            action: 'jsonserver.php',
            name: 'Filedata',
            data: { action: 'localUpload', userid: user },
            autoSubmit: true,
            responseType: 'json',
            onSubmit: validateLocalUpload,
            onComplete: completeUpload
        });

        $.ajax({
            type: "POST",
            url: 'jsonserver.php',
            data: {
                action: 'isUSCitizen',
                userid: user
            },
            dataType: 'json',
            success: function(data) {
                if ((data.success === true) && (data.isuscitizen === true)) {
                $('#w9upload').show();
                }
            }
        });
        $("#send-test").click(smsSendTestMessage);
        $("#save_account").click(function() {
            saveSettings('account');
            return false;
        });
        $("#save_personal").click(function() {
            saveSettings('personal');
            return false;
        });
        $("#save_payment").click(function() {
            saveSettings('payment');
            return false;
        });
    });
</script>

<title>Worklist | Account Settings</title>

</head>

<body>

<?php include("format.php"); ?>
<!-- Popup for add project info-->
<?php require_once('dialogs/popup-addproject.inc'); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->


<h1 class="header">Edit Account Settings</h1>

<div id="account-info" class="settings">

    <table width="100%">
    <tr>
        <td align="left"><h2 class="subheader">Account Info<span class="heading-links" /></h2></td>
        <td align="right"><a href="password.php">Change my password...</a></td>
    </tr>
    </table>
    <div id="formHolder">
        <div id="formLeft">
    
    <form method="post" action="settings.php" name="frmsetting">

        <span class="required-bullet">*</span> <span class="required">required fields</span>

        <p id="msg-account" class="error"></p>

        <p><label for = "nickname">Nickname</label><br />
            <span class="required-bullet">*</span> <input name="nickname" type="text" id="nickname"  value = "<?php echo $_SESSION['nickname']; ?>" size="35"/>
        </p>
        <script type="text/javascript">
            var nickname = new LiveValidation('nickname', {validMessage: "You have an OK Nickname." });
            nickname.add(Validate.Format, {pattern: /[@]/, negate:true});
            nickname.add(Validate.Exclusion, { within: [ 'Nickname' ], failureMessage: "You must set your Nickname!" });
        </script>

        <p><label for = "timezone">What timezone are you in?</label><br />
            <span class="required-bullet">*</span> <select id="timezone" name="timezone">
            <?php
            foreach($timezoneTable as $key => $value) {
                $selected = '';
                if (empty($_SESSION['new_user']) && $key == $userInfo['timezone']) {
                    $selected = 'selected = "selected"';
                }
                echo '<option value = "'.$key.'" '.$selected.'>'.$value.'</option>';
            }
            ?>
            </select>
        </p>

    <?php
        $new_user = (bool) ! empty($_SESSION['new_user']);
        $country = !$new_user ? $userInfo['country'] : '';
        $city = !$new_user ? $userInfo['city'] : '';
        $int_code = !$new_user ? $userInfo['int_code'] : '';
        $phone = !$new_user ? $userInfo['phone'] : '';
        $provider = !$new_user ? $userInfo['provider'] : '';
        $sms_flags = !$new_user ? $userInfo['sms_flags'] : '';
        $picture = !$new_user ? $userInfo['picture'] : '';
        $notifications = !$new_user ? $userInfo['notifications'] : 0;
        $settingsPage = true;
        include("sms-inc.php");
    ?>
           <br />
        <div >Send SMS Messages</div>                
        <div id="smsOptions">    
            <div class="floatLeft">

            <input type="checkbox" name="my_bids_notify" value="1" <?php 
                echo Notification::isNotified($notifications, Notification::MY_BIDS_NOTIFICATIONS) ? 'checked="checked"' : '';
                ?>/>Bids on my jobs<br />
            <input type="checkbox" name="ping_notify" value="1" <?php 
                echo Notification::isNotified($notifications, Notification::PING_NOTIFICATIONS) ? 'checked="checked"' : '';
                ?>/>Pings
            </div>
            <div class="floatLeft">
                <input type="checkbox" name="bidding_notify" value="1" <?php
                echo Notification::isNotified($notifications, Notification::BIDDING_NOTIFICATIONS) ? 'checked="checked"' : '';
                ?>/>New Jobs<br />
            <input type="checkbox" name="review_notify" value="1" <?php 
                echo Notification::isNotified($notifications, Notification::REVIEW_NOTIFICATIONS) ? 'checked="checked"' : '';
                ?>/>Any job set to review
            </div>
            <div class="floatLeft">
                <input type="checkbox" name="my_review_notify" value="1" <?php
                    echo Notification::isNotified($notifications, Notification::MY_REVIEW_NOTIFICATIONS) ? 'checked="checked"' : '';
                    ?>/>My jobs set to review<br />
                <input type="checkbox" name="my_completed_notify" value="1" <?php 
                    echo Notification::isNotified($notifications, Notification::MY_COMPLETED_NOTIFICATIONS) ? 'checked="checked"' : '';
                    ?>/>My jobs set to completed
            </div>
        </div>
        <div style="clear:both">
            <input type="submit" id="save_account" value="Save Account Info" alt="Save Account Info" name="save_account" />
        </div>
    </form>
    </div>
    <div id="formRight">
    <p style="text-align: center; cursor: pointer;">
    <label style="text-align: left; display: block;">Photo<br></label> 
    <img style="border: 2px solid rgb(209, 207, 207); padding: 10px;"
    id="profilepicture"
    src="thumb.php?src=<?php echo((empty($picture) ? '/images/no_picture.png' : '/uploads/' . $picture));?>&w=100&h=90&zc=0" />
    <span class="picture_info">Click here to change it</span>
    <span style="display: none;"
    class="LV_validation_message LV_invalid upload"></span></p>
    </div>
    </div>
    <div style="clear: both;"></div>
</div>

<?php if(empty($_SESSION['new_user'])) { ?>
<div id="personal-info" class="settings">

    <h2 class="subheader">Personal Info</h2>

    <p id="msg-personal" class="error"></p>

    <form method="post" action="settings.php" name="frmsetting">

        <p><label for = "about">What do we need to know about you?</label><br />
            <textarea id="about" name="about" rows="5" style="width:95%"><?php echo $userInfo['about'] ?></textarea>
        </p>
        <script type="text/javascript">
            var about = new LiveValidation('about');
            about.add(Validate.Length, { minimum: 0, maximum: 150 } );
        </script>

        <p><label for = "skills">Pick three skills you think are your strongest</label><br />
            <input type="text" id="skills" name="skills" class="text-field skills-watermark" value="<?php echo $userInfo['skills']; ?>" style="width:95%" />
        </p>

        <p><label for = "contactway">What is the preferred way to contact you?</label><br />
            <input type="text" id="contactway" name="contactway" class="text-field" value="<?php echo $userInfo['contactway']; ?>" style="width:95%" />
        </p>

        <input type="submit" id="save_personal" value="Save Personal Info" alt="Save Personal Info" name="save_personal" />

    </form>

</div>

<div id="payment-info" class="settings">
    <form method="post" action="settings.php" name="frmsetting">
        <h2 class="subheader">Paypal Payment Info</h2>
        <p id="msg-payment" class="error"></p>
        <blockquote>
            <p id="paytype-paypal"><label>Paypal Email</label><br />
                <span class="required-bullet">*</span> <input type="text" id="paypal_email" name="paypal_email" class="text-field" value="<?php echo $userInfo['paypal_email']; ?>" style="width:95%" />
            </p>
            <script type="text/javascript">
                var paypal = new LiveValidation('paypal_email', {validMessage: "Valid email address."});
                paypal.add(Validate.Email);
                // TODO: Review requirements here. We let people signup without paypal, and we let them delete their paypal 
                // email, which removes their paypal verification and prevents them from bidding
                // paypal.add(Validate.Presence, { failureMessage: "Can't be empty!" });
            </script> 
            <input type="hidden" name="paytype" id="paytype" value="paypal" />
            <input type="hidden" name="payway" id="payway" value="paypal" />
        </blockquote>
        <h2 class="subheader">W-9 Form <small>(US Citizens Only)</small></h2>
        <p>
            <label>All US Citizens must submit a W-9 to be paid by LoveMachine </label>
            <a href="http://www.irs.gov/pub/irs-pdf/fw9.pdf" link="#00008B" target="_blank"><span style="color: blue;">Download W-9 Here</span></a>
        </p>
        <blockquote>
          <p><label style="float:left;line-height:26px">Upload W-9</label>
                <input id="formupload" type="button" value="Browse" style="float:left;margin-left: 8px;" />
          </p>
            <br style="clear:both" />
            <div class="uploadnotice-w9"></div>

            <p><label style="float:left">Upload local<br/>tax doc</label>
                <input id="formupload-local" type="button" value="Browse" style="float:left;margin-left: 8px;" />
            </p>
            <br style="clear:both" />
            <div class="uploadnotice-local"></div>
    </blockquote>

        <input type="submit" id="save_payment" value="Save Payment Info" alt="Save Payment Info" name="save_payment" />

    </form>

</div>

<script type="text/javascript">
setTimeout('ChangePaymentMethod()', 2000);
</script>
<?php } ?>
<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
