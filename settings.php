<?php
//  vim:ts=4:et

//  Copyright (c) 2012, Coffee & Power, Inc.
//  All Rights Reserved.
//  http://www.coffeeandpower.com

ob_start();
include ("config.php");
include ("class.session_handler.php");
include ("check_session.php");
include ("functions.php");
require_once ("timezones.php");
include ("countrylist.php");
include ("smslist.php");
include_once ("send_email.php");
require_once ("lib/Sms.php");


$userId = getSessionUserId();
if ($userId) {
    $user = new User();
    $user->findUserById($userId);
}

$msg = "";
$company = "";

$saveArgs = array();
$messages = array();
$errors = 0;
$error = new Error();
$phone_changed = false;
$settings_link = SECURE_SERVER_URL . "settings.php";
$worklist_link = SERVER_URL . "worklist.php";

// process updates to user's settings
if (isset($_REQUEST['save_account'])) {

    $updateNickname = false;
    $updatePassword = false;

    // check if phone was updated
    if (isset($_REQUEST['phone_edit']) || isset($_REQUEST['int_code']) || isset($_REQUEST['timezone'])) {
        $saveArgs = array('int_code'=>0, 'phone'=>1, 'country'=>1, 'smsaddr'=>1);

        foreach ($saveArgs as $arg=>$esc) {
            $$arg = ($esc ? $_REQUEST[$arg] : intval($_REQUEST[$arg]));
        }

        if ($phone != $user->getPhone()) {
            $phone_verified = null;
            $saveArgs['phone_verified'] = 0;
            $phone_rejected = null;
            $saveArgs['phone_rejected'] = 0;
            if ($phone && Utils::validPhone($phone)) {
                $phone_confirm_string = substr(uniqid(), -4);
                $saveArgs['phone_confirm_string'] = 1;
                $phone_changed = true;
            }
        }

        if (isset($_REQUEST['city'])) {
            $city = $_REQUEST['city'];
            $saveArgs['city'] = 1;
        } else {
            // TODO: Actually return the error to user, rather than rely on javascript validation
            $error->setError('You are required to choose a city');
        }

        $sms_flags = 0;
        if (!empty($_REQUEST['journal_alerts'])) {
            $sms_flags |= SMS_FLAG_JOURNAL_ALERTS;
        }
        if (!empty($_REQUEST['bid_alerts'])) {
            $sms_flags |= SMS_FLAG_BID_ALERTS;
        }
        $saveArgs['sms_flags'] = 0;

        $timezone = mysql_real_escape_string(trim($_REQUEST['timezone']));
        $saveArgs['timezone'] = 0;

        $notifications = 0;
        $my_bids_notify = !empty($_REQUEST['my_bids_notify']) ? Notification::MY_BIDS_NOTIFICATIONS : 0;
        $ping_notify = !empty($_REQUEST['ping_notify']) ? Notification::PING_NOTIFICATIONS : 0;
        $review_notify = !empty($_REQUEST['review_notify']) ? Notification::REVIEW_NOTIFICATIONS : 0;
        $bidding_notify = !empty($_REQUEST['bidding_notify']) ? Notification::BIDDING_NOTIFICATIONS : 0;
        $my_review_notify = !empty($_REQUEST['my_review_notify']) ? Notification::MY_REVIEW_NOTIFICATIONS : 0;
        $my_completed_notify = !empty($_REQUEST['my_completed_notify']) ? Notification::MY_COMPLETED_NOTIFICATIONS : 0;
        $self_email_notify = !empty($_REQUEST['self_email_notify']) ? Notification::SELF_EMAIL_NOTIFICATIONS : 0;
        $bidding_email_notify = !empty($_REQUEST['bidding_email_notify']) ? Notification::BIDDING_EMAIL_NOTIFICATIONS : 0;
        $review_email_notify = !empty($_REQUEST['review_email_notify']) ? Notification::REVIEW_EMAIL_NOTIFICATIONS : 0;

        $notifications = Notification::setFlags(
            $review_notify,
            $bidding_notify,
            $my_review_notify,
            $my_completed_notify,
            $my_bids_notify,
            $ping_notify,
            $self_email_notify,
            $bidding_email_notify,
            $review_email_notify
        );

        $saveArgs['notifications'] = 0;

        // if user is new - create an entry for him
        // clear $saveArgs so it won't be updated for the second time
        // @TODO: Follow-up. Is this for the first creation of user in the worklist database (as opposed to
        // logon db?  -- lithium
        if (!empty($_SESSION['new_user'])) {

            $user_id = (int) $_SESSION['userid'];
            $username = $_SESSION['username'];
            $nickname = $_SESSION['nickname'];

            $sql = "
                INSERT INTO " . USERS . "
                (`id`, `username`, `nickname`, `timezone`, `country`, `phone`, `int_code`, `smsaddr`, `sms_flags`, `notifications`, `is_active`, `confirm`)
                VALUES ('$user_id', '$username', '$nickname', '$timezone', '$country', '$phone', '$int_code', '$smsaddr', '$sms_flags', '$notifications', '1', '1')";
            mysql_unbuffered_query($sql);
            $_SESSION['new_user'] = '';
            $saveArgs = array();
        } else {
              // we need to check if phone/country/timezone or city settings have changed
              // so as to send correct message in mail
        	if ($user->getCity() != $_REQUEST['city'] || $user->getCountry() != $_REQUEST['country']
        	    || $user->getPhone() != $_REQUEST['phone'] || $user->getTimezone() != $_REQUEST['timezone']){     		
                  $messages[] = "Your country/phone settings have been updated.";
              }        
        }
    }

    // has the nickname changed? update the database
    $nickname = trim($_REQUEST['nickname']);
    if($nickname != $_SESSION['nickname']) {
        $oldNickname = $_SESSION['nickname'];
        $user = new User();
        $user->findUserByNickname($nickname);

        if ($user->getId() != null && $user->getId() != intval($_SESSION['userid'])) {
            die(json_encode(array(
                'error' => 1,
                'message' => "Update failed, nickname already exists!"
            )));
        }


        if (!$_SESSION['new_user']) {
            $sql = "
                UPDATE " . USERS . "
                SET nickname = '" . mysql_real_escape_string($nickname) . "' WHERE id ='" . $_SESSION['userid'] . "'";

            if (mysql_query($sql)) {
                $_SESSION['nickname'] = $nickname;
                $messages[] = "Your nickname is now '$nickname'.";
            } else {
                $error->setError("Error updating nickname in Worklist");
            }
        }

        if ($error->getErrorFlag()) {
            $errormsg = implode(', ', $error->getErrorMessage());
            $body = 'Nickname update failed for user with id='. intval($_SESSION['userid']) . ". \n";
            $body .= "Old nickname: '" . $_SESSION['nickname'] . "'\n" ;
            $body .= "New nickname: '" . $nickname . "'\n" ;
            $body .= "Error message: '" . $errormsg;
            send_email(FEEDBACK_EMAIL, 'Update nickname for user failed!', nl2br($body), $body);
            die(json_encode(array(
                'error' => 1,
                'messsage' => $errormsg
            )));
        } else {
            sendJournalNotification("The new nickname for user '" . $oldNickname . "' is: '" . $nickname . "'");
        }
    }

    // has the email changed? send confirm.
    $username = trim($_REQUEST['username']);
    if ($username != $_SESSION['username']) {
    	//we need to check if the username exists
    	if ( $user->findUserByUsername($username)) {
    	    die(json_encode(array(
 	        'error' => 1,
               'message' => "This e-mail address is already linked to a Worklist account."
    	    )));
       }
    	
    	$user->findUserByUsername($_SESSION['username']);
    	//send out confirm email
    	$email_hash = md5(date('r', time()));;
    	
    	// generate email for confirm to new email address
    	$subject = "Your email has changed.";
    	
    	$link = SECURE_SERVER_URL . "confirmation.php?emstr=" . base64_encode($username);
    	
    	$body  = '<p>Dear ' . $user->getNickname() . ',</p>';
    	$body .= '<p>Please confirm your new email address in the <a href="' . $worklist_link . '">Worklist</a>.</p>';
    	$body .= '<p><a href=' . $link . '>Click here to confirm your email address</a></p>';
       $body .= '<p><br/>You can view your settings <a href=' . $settings_link . '>here</a></p>';
       $body .= '<p><a href=' . $worklist_link . '>www.worklist.net</a></p>';

    	
    	$plain  = 'Dear ' . $user->getNickname() . ',' . "\n\n";
    	$plain .= 'Please confirm your new email address in the Worklist.' . "\n\n";
    	$plain .= $link . "\n\n";
    	
    	$confirm_email = "An email containing a confirmation link was sent to your email address.<br/>";
    	$confirm_email .= "Please click on that link to verify your email address.";
    	
    	echo json_encode(array( 'confirm_email' => $confirm_email));
    	
    	if (! send_email($username, $subject, $body, $plain)) {
           error_log("settings.php: send_email failed");
    	    $confirm_txt = "There was an issue sending email. Please try again or notify support@worklist.net";
    	}
    	
    	// generate email to current email address
    	$subject = "Account email updated.";
    	$body  = '<p>Hello you!,</p>';
    	$body .= '<p>We received a request to update your email address for your Worklist.net account.</p>';
    	$body .= '<p>If you did not make this request, please contact support@worklist.net immediately.</p>';
    	$body .= '<p>See you at the <a href='.SERVER_URL.'>Worklist</a></p>';
    	
    	$plain  = 'Hello you! ,' . "\n\n";
    	$plain .= 'We received a request to update your email address for your Worklist.net account.' . "\n\n";
    	$plain .= 'If you did not make this request, please contact support@worklist.net immediately.' . "\n\n";
    	$plain .= 'See you in the Worklist' . "\n\n";
    	
    	if (! send_email($_SESSION['username'], $subject, $body, $plain)) {
    	    error_log("settings.php: send_email failed");
    	    $confirm_txt = "There was an issue sending email. Please try again or notify support@worklist.net";
    	}
    	$messages[] = "We receieved your request to modify your email.";
    }

} else if (isset($_REQUEST['save_personal'])) {
    $about = isset($_REQUEST['about']) ? strip_tags(substr($_REQUEST['about'], 0, 150)) : "";
    $skills = isset($_REQUEST['skills']) ? strip_tags($_REQUEST['skills']) : "";
    $contactway = isset($_REQUEST['contactway']) ? strip_tags($_REQUEST['contactway']) : "";

    $saveArgs = array('about'=>1, 'skills'=>1, 'contactway'=>1);
    $messages[] = "Your personal information has been updated.";
} else if (isset($_REQUEST['save_payment'])) {
    $paypal = 0;
    $paypal_email = '';
    // defaulting to paypal at this stage
    $payway = 'paypal';
    if ($_REQUEST['paytype'] == 'paypal') {
        $paypal = 1;
        $payway = "paypal";
        $paypal_email = isset($_REQUEST['paypal_email']) ? mysql_real_escape_string($_REQUEST['paypal_email']) : "";
    } else if ($_REQUEST['paytype'] == 'other') {
        $payway = '';
    }

    $saveArgs = array('paypal' => 0, 'paypal_email' => 0, 'payway' => 1);
    $messages[] = "Your payment information has been updated.";

    if (!$user->getW9_accepted() && $user->getCountry() == 'US') {
        $w9_accepted = 'NOW()';
        $saveArgs['w9_accepted'] = 0;
    }

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
        $subject = "Your payment details have changed";

        $link = SECURE_SERVER_URL . "confirmation.php?pp=" . $paypal_hash . "&ppstr=" . base64_encode($paypal_email);

        $body  = '<p>Dear ' . $user->getNickname() . ',</p>';
        $body .= '<p>Please confirm your payment email address to activate payments on your account and enable you to start placing bids in the <a href="' . $worklist_link . '">Worklist</a>.</p>';
        $body .= '<p><a href="' . $link . '">Click here to confirm your payment address</a></p>';

        $plain  = 'Dear ' . $user->getNickname() . ',' . "\n\n";
        $plain .= 'Please confirm your payment email address to activate payments on your accounts and enable you to start placing bids in the Worklist.' . "\n\n";
        $plain .= $link . "\n\n";

        $confirm_txt = "An email containing a confirmation link was sent to your payment email address. Please click on that link to verify your payment email address and activate your account.";
        if (! send_email($paypal_email, $subject, $body, $plain)) {
            error_log("signup.php: send_email failed");
            $confirm_txt = "There was an issue sending email. Please try again or notify support@worklist.net";
        }

        $user->setPaypal_verified(false);
        $user->setPaypal_hash($paypal_hash);
        $user->setPaypal_email($paypal_email);
        $user->save();
    }
} else if (isset($_REQUEST['save_w9Name'])) {
    $first_name = isset($_REQUEST['first_name']) ? mysql_real_escape_string($_REQUEST['first_name']) : "";
    $last_name = isset($_REQUEST['last_name']) ? mysql_real_escape_string($_REQUEST['last_name']) : "";
    $saveArgs = array('first_name'=>1, 'last_name'=>1);
}

// do we have data to update?
if (!empty($saveArgs)) {

    $sql = "UPDATE `" . USERS . "` SET ";
    foreach ($saveArgs as $arg => $esc) {

        if ($esc) {
            $$arg = mysql_real_escape_string(htmlspecialchars($$arg));
        }

        if (is_int($$arg) || ($arg == "w9_accepted" && $$arg == 'NOW()')) {
            $sql .= "`$arg` = " . $$arg . ",";
        } else {
            $sql .= "`$arg` = '" . $$arg ."',";
        }
    }

    $sql = rtrim($sql, ',');
    $sql .= " WHERE id = {$_SESSION['userid']}";
    $res = mysql_query($sql);

    if (!$res) {
        error_log("Error in saving settings: " . mysql_error() . ':' . $sql);
        die("Error in saving settings. " );
    }

// Email user
    if (!empty($messages)) {
        $to = $_SESSION['username'];
        $subject = "Settings";
        $body  = "<p>Congratulations!</p>";
        $body .= "<p>You have successfully updated your settings with Worklist <br/>";
        foreach ($messages as $msg) {
            $body .= "&nbsp;&nbsp;$msg<br/>";
        }
        $body .= '<p><br/>You can view your settings <a href=' . $settings_link . '>here</a></p>';
        $body .= '<p><a href=' . $worklist_link . '>www.worklist.net</a></p>';

        if(!send_email($to, $subject, $body)) { error_log("settings.php: send_email failed"); }

        $msg="Account updated successfully!";
    }

    if ($phone_changed) {
        $user->findUserById($_SESSION['userid']);
        $url = SERVER_URL . 'confirm_phone.php?user=' . $_SESSION['userid'] .
            '&phone=' . $phone . '&phoneconfirmstr=' . $phone_confirm_string;
        $msg = 'Confirm code: ' . $phone_confirm_string . ' (or follow URL)';
        Notification::sendShortSMS($user, 'Worklist phone validation', $msg, $url, true);
    }

    if (isset($_REQUEST['timezone'])) {
      $_SESSION['timezone'] = trim($_REQUEST['timezone']);
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
<link type="text/css" href="css/settings.css" rel="stylesheet" />

<script type="text/javascript" src="js/skills.js"></script>
<script type="text/javascript" src="js/userSkills.js"></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/sendlove.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/ajaxupload-3.6.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript" src="js/budget.js"></script>
<script type="text/javascript">
    var nclass;
    var user_id = <?php echo isset($_SESSION['userid']) ? $_SESSION['userid'] : 0; ?>;
    var worklistUrl = '<?php echo SERVER_URL; ?>';

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

    function validateNames(file, extension) {
        if (LiveValidation.massValidate( [ firstname, lastname ] )) {
            return validateW9Upload(file, extension);
        } else {
            return false;
        }
    }

    function validateW9Upload(file, extension) {
        nclass = '.uploadnotice-w9';
        return validateUpload(file, extension);
    }
    function validateUpload(file, extension) {
        if (! (extension && /^(pdf)$/i.test(extension))) {
            // extension is not allowed

            // Restore the styling of upload button
            $('#formupload').attr('value', 'upload W9');
            $('#formupload').removeClass('w9_upload_disabled');
            $('.w9_loader').css('visibility', 'hidden');

            $(nclass).empty();
            var html = '<div class="ui-state-error ui-corner-all">' +
                            '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                            '<strong>Error:</strong> This filetype is not allowed. Please upload a pdf file.</p>' +
                        '</div>';
            $(nclass).append(html);
            // cancel upload
            return false;
        }else{
            // Inform the user that the file is being uploaded...
            $(nclass).empty();
            $('#formupload').attr('value', 'uploading...');
            $('#formupload').addClass('w9_upload_disabled');
            $('.w9_loader').css('visibility', 'visible');
        }
    }

    function completeUpload(file, data) {
        $(nclass).empty();
        if (data.success) {
            // Restore the styling of upload button
            $('#formupload').attr('value', 'Success!');
            $('#formupload').removeClass('w9_upload_disabled');
            $('.w9_loader').css('visibility', 'hidden');

            var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-highlight ui-corner-all">' +
                            '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>' +
                            '<strong>Info:</strong> ' + data.message + '</p>' +
                        '</div>';
            saveSettings('w9Name');
        } else {
            // Restore the styling of upload button
            $('#formupload').attr('value', 'Fail');
            $('#formupload').removeClass('w9_upload_disabled');
            $('.w9_loader').css('visibility', 'hidden');

            var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-error ui-corner-all">' +
                            '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                            '<strong>Error:</strong> ' + data.message + '</p>' +
                        '</div>';
            this.enable();
        }
        $(nclass).append(html);
    }

    function validateW9Agree(value) {
        if (! $('#w9_accepted').is(':checked') && $('#country').val() == 'US') {
            return false;
        }
        return true;
    }

    function isJSON(json) {
        json = json.replace(/\\["\\\/bfnrtu]/g, '@');
        json = json.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
        json = json.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
        return (/^[\],:{}\s]*$/.test(json))
    }

    function saveSettings(type) {
        var values;
        if (type == 'account') {
            var massValidation = LiveValidation.massValidate( [ nickname, city, username ]);
            if (massValidation) {
                values = {
                    int_code: $('#int_code').val(),
                    phone: $('#int_code').val() + $('#phone').val(),
                    phone_edit: $('#phone_edit').val(),
                    country: $('#country').val(),
                    city: $('#city').val(),
                    smsaddr: ($('#smsaddr').val()),
                    timezone: $('#timezone').val(),
                    journal_alerts: $('#journal_alerts').prop('checked') ? 1 : 0,
                    bid_alerts: $('#bid_alerts').prop('checked') ? 1 : 0,
                    nickname: $('#nickname').val(),
                    save_account: 1,
                    username: $('#username').val(),
                    my_bids_notify: $('input[name="my_bids_notify"]').prop('checked') ? 1 : 0,
                    ping_notify: $('input[name="ping_notify"]').prop('checked') ? 1 : 0,
                    review_notify: $('input[name="review_notify"]').prop('checked') ? 1 : 0,
                    bidding_notify: $('input[name="bidding_notify"]').prop('checked') ? 1 : 0,
                    my_review_notify: $('input[name="my_review_notify"]').prop('checked') ? 1 : 0,
                    my_completed_notify: $('input[name="my_completed_notify"]').prop('checked') ? 1 : 0,
                    self_email_notify: $('input[name="self_email_notify"]').prop('checked') ? 1 : 0,
                    bidding_email_notify: $('input[name="bidding_email_notify"]').prop('checked') ? 1 : 0,
                    review_email_notify: $('input[name="review_email_notify"]').prop('checked') ? 1 : 0
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
            var massValidation = LiveValidation.massValidate( [ paypal, w9_accepted ]);
            if (massValidation) {
                values = {
                    paytype: $("#paytype").val(),
                    paypal_email: $("#paypal_email").val(),
                    payway: $("#payway").val(),
                    save_payment: 1,
                    w9_accepted: $('#w9_accepted').is(':checked')
                }
            } else {
                return false;
            }
        } else if (type == 'w9Name') {
            values = {
                first_name: $("#first_name").val(),
                last_name: $("#last_name").val(),
                save_w9Name: 1
            }
        }

        $('.error').text('');

        $.ajax({
            type: "POST",
            url: 'settings.php',
            data: values,
            success: function(json) {
               
                var message = 'Account settings saved!';
                var settings_json = isJSON(json) ? jQuery.parseJSON(json) : null;
                if (settings_json && settings_json.error) {
                    console.log(settings_json);
                    if (settings_json.error == 1) {
                        message = "There was an error updating your information.<br/>Please try again or contact a Runner for assistance.<br/>Reason for failure: " + settings_json.message;
                    } else {
                        message = json.message;
                    }
                } 
                if (type == 'payment' && json) {
                    $('#msg-'+type).html(message + '<br/>' + json);
                } else if (settings_json && settings_json.confirm_email) {
                    $('#msg-'+type).html(message + '<br/>' + settings_json.confirm_email) ;
                } else {
                    $('#msg-'+type).html(message);
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
            alert('Test SMS Sent to: ' + int_code + phone);
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
<?php if (isset($_REQUEST['ppconfirmed']) || isset($_REQUEST['emconfirmed'])) : ?>
        $('<div id="popup-confirmed"><div class="content"></div></div>').appendTo('body');

        <?php if (isset($_REQUEST['ppconfirmed'])){  ?>
            var $title = 'Your Paypal address was confirmed';
            var $content = 'Thank you for confirming your Paypal address.<br/><br/>You can now bid on items in the Worklist!<br/><br/><input style="" class="closeButton" type="button" value="Close" />';
        <?php } else { ?>
            var $title = 'Your email change is confirmed.';
            var $content = 'Thank you for confirming your changed email address.<br/><br/><input style="" class="closeButton" type="button" value="Close" />';
        <?php } ?>
            
        $('#popup-confirmed').dialog({
            dialogClass: "white-theme",
            modal: true,
            title: $title,
            autoOpen: true,
            width: 300,
            position: ['top'],
            open: function() {
                $('#popup-confirmed .content').html($content);
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
            onSubmit: validateNames,
            onComplete: completeUpload
        });

        $("#w9-dialog").dialog({
            dialogClass: 'white-theme',
            resizable: false,
            width: 220,
            title: 'W9 form upload',
            autoOpen: false,
            position: ['top'],
            open: function() {
                <?php if (empty($_SESSION['new_user'])) {
                    $firstName = $userInfo['first_name'];
                    $lastName = $userInfo['last_name'];
                } else {
                    $firstName = "";
                    $lastName = "";
                }
                ?>
                $("#last_name").val('<?php echo $lastName ?>');
                $("#first_name").val('<?php echo $firstName ?>');
                $(".uploadnotice-w9").html('');
                $(".LV_validation_message").html('');
            }
        });

        $("#uploadw9").click(function() {
            $("#w9-dialog").dialog("open");
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
<?php
    require_once('header.php');
    require_once('format.php');
?>
<!-- Popup for budget info -->
<?php require_once('dialogs/budget-expanded.inc'); ?>
<!-- Popup for budget info-->
<?php require_once('dialogs/popup-budget.inc'); ?>

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
            nickname.add(Validate.Length, { minimum: 0, maximum: 25 } );
            nickname.add(Validate.Format, {pattern: /[@]/, negate:true});
            nickname.add(Validate.Exclusion, { within: [ 'Nickname' ], failureMessage: "You must set your Nickname!" });
        </script>

        <p>
            <label for="username">Email </label>
            <br />
            <span class="required-bullet">*</span>
            <input type="text" id="username" name="username" class="text-field" size="35" value="<?php echo $_SESSION['username']; ?>" />
        </p>
        <script type="text/javascript">
            var username = new LiveValidation('username', {validMessage: "Valid email address."});
            username.add( Validate.Email );
            username.add(Validate.Length, { minimum: 4, maximum: 50 } );
            username.add(Validate.Exclusion, { within: [ 'username' ], failureMessage: "You must set your Email!" });
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

        $int_code = !$new_user ? trim($userInfo['int_code']) : '';
        $phone = !$new_user ? trim($userInfo['phone']) : '';
        if (strlen($int_code) && substr($phone, 0, strlen($int_code)) == $int_code) {
            $phone = substr($phone, strlen($int_code));
        }

        $sms_flags = !$new_user ? $userInfo['sms_flags'] : '';
        $picture = !$new_user ? $userInfo['picture'] : '';
        $notifications = !$new_user ? $userInfo['notifications'] : 0;
        $smsaddr = !$new_user ? $userInfo['smsaddr'] : '';
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
                ?>/>New Jobs set to bidding<br />
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
        <br/>
        <div >Send Email Messages</div>
        <div id="emailOptions">
            <div class="floatLeft">
                <input type="checkbox" name="bidding_email_notify" value="1" <?php
                echo Notification::isNotified($notifications, Notification::BIDDING_EMAIL_NOTIFICATIONS) ? 'checked="checked"' : '';
                ?>/>New Jobs set to bidding<br />
            <input type="checkbox" name="review_email_notify" value="1" <?php
                echo Notification::isNotified($notifications, Notification::REVIEW_EMAIL_NOTIFICATIONS) ? 'checked="checked"' : '';
                ?>/>Any job set to review
            </div>
            <div class="floatLeft">
                <input type="checkbox" name="self_email_notify" value="1" <?php
                echo Notification::isNotified($notifications, Notification::SELF_EMAIL_NOTIFICATIONS) ? 'checked="checked"' : '';
            ?>/>Receive email notifications from my actions<br />
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
    <img id="profilepicture"
    src="<?php echo (empty($picture) ? 'thumb.php?src=images/no_picture.png&w=100&h=100&zc=0' : APP_IMAGE_URL . $picture); ?>" />
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
            <input type="hidden" name="paytype" id="paytype" value="paypal" />
            <input type="hidden" name="payway" id="payway" value="paypal" />
        </blockquote>
        <h2 class="subheader">W-9 Form <small>(US Citizens Only)</small></h2>
        <p>
            All US Citizens must submit a W-9 to be paid by Worklist.
            <a href="http://www.irs.gov/pub/irs-pdf/fw9.pdf" link="#00008B" target="_blank"><span style="color: blue;">Download W-9 Here</span></a>.
            Remember, you need a valid US mailing address to receive your 1099 tax documents at the end of the year.
            If you move, it’s up to you to let us know your new address.
        </p>
        <p>
            <input type="checkbox" name="w9_accepted" id="w9_accepted" <?php if ($user->getW9_accepted()) { ?> checked="checked" disabled="disabled" <?php } ?> />
            <label id="w9_accepted_label" for="w9_accepted">Check this box to let us know you'll do your part!</label>
        </p>
        <blockquote>
            <input id="uploadw9" type="button" value="Upload W9" style="float:left;margin-left: 8px;" />
            <?php include("dialogs/popup-w9.inc")?>
        </blockquote>
        <script type="text/javascript">
            var paypal = new LiveValidation('paypal_email', {validMessage: "Valid email address."});
            paypal.add(Validate.Email);
            // TODO: Review requirements here. We let people signup without paypal, and we let them delete their paypal
            // email, which removes their paypal verification and prevents them from bidding
            // paypal.add(Validate.Presence, { failureMessage: "Can't be empty!" });

            var firstname = new LiveValidation('first_name', {validMessage: "First Name looks good", onlyOnBlur: true});
            firstname.add(Validate.Presence, { failureMessage: "Sorry, we need your first name before you can upload your W9. It’s only for administrative purposes and won’t be displayed in your profile"});
            firstname.add(Validate.Format, { pattern: /^[a-zA-Z]+$/, failureMessage: "Only characters through a-z and A-Z are allowed" });

            var lastname = new LiveValidation('last_name', {validMessage: "Last Name looks good", onlyOnBlur: true});
            lastname.add(Validate.Presence, { failureMessage: "Sorry, we need your last name before you can upload your W9. It’s only for administrative purposes and won’t be displayed in your profile"});
            lastname.add(Validate.Format, { pattern: /^[a-zA-Z]+$/, failureMessage: "Only characters through a-z and A-Z are allowed" });

            var w9_accepted = new LiveValidation('w9_accepted', {insertAfterWhatNode: 'w9_accepted_label'});
            w9_accepted.displayMessageWhenEmpty = true;
            w9_accepted.add(Validate.Custom, { against: validateW9Agree, failureMessage: "Oops! You forgot to agree that you'd keep us posted if you move. Please check the box, it's required, thanks!" });
        </script>


        <input type="submit" id="save_payment" value="Save Payment Info" alt="Save Payment Info" name="save_payment" />

    </form>

</div>

<script type="text/javascript">
setTimeout('ChangePaymentMethod()', 2000);
</script>
<?php } ?>

<?php
//-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
include("footer.php"); ?>
