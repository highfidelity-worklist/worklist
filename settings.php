<?php
//  vim:ts=4:et

//
//  Copyright (c) 2010, LoveMachine Inc.
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

$msg="";
$company="";

$saveArgs = array();
$messages = array();
$errors = 0;
$error = new Error();

if (isset($_POST['save_account'])) {

    $updateNickname = false;
    $updatePassword = false;

    // check if phone was updated
    if (isset($_POST['phone_edit']) || isset($_POST['int_code']) ||isset($_POST['timezone']))
    {
		$saveArgs = array('int_code'=>0, 'phone'=>1, 'country'=>1, 'smsaddr'=>1);

        foreach ($saveArgs as $arg=>$esc) {
            $$arg = ($esc ? $_POST[$arg] : intval($_POST[$arg]));
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


        // if user is new - create an entry for him
        // clear $saveArgs so it won't be updated for the second time
        if($_SESSION['new_user']){

            $user_id = intval($_SESSION['userid']);
            $username = $_SESSION['username'];
            $nickname = $_SESSION['nickname'];

            $sql = " INSERT INTO " . USERS . " 
                        (`id`, `username`, `nickname`, `timezone`, `country`, `is_uscitizen`, `provider` ,`phone`, `int_code`, `smsaddr`, `sms_flags`, `is_active`, `confirm`)
                        VALUES ('$user_id', '$username', '$nickname', '$timezone', '$country', '$is_uscitizen', '$provider', '$phone', '$int_code', '$smsaddr', '$sms_flags', '1', '1')";
            mysql_unbuffered_query($sql);
            $_SESSION['new_user'] = '';
            $saveArgs = array();
        }else{
            $messages[] = "Your country/phone settings have been updated.";
        }
    }

    // if nickname is different - update it through login call
    $nickname = trim($_POST['nickname']);
    if($nickname != $_SESSION['nickname']){
        $ret = Utils::updateLoginData(array('nickname' => $nickname), true, false);
        if($ret->error == 1){
            $error->setError($ret->message);
        }else{
            if(!$_SESSION['new_user']){
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
    $payway = '';
    if ($_POST['paytype'] == 'paypal') {
        $paypal = 1;
        $paypal_email = isset($_POST['paypal_email']) ? mysql_real_escape_string($_POST['paypal_email']) : "";
	} else if ($_POST['paytype'] == 'other') {
        $payway = isset($_POST['payway']) ? $_POST['payway'] : '';
    }

    $saveArgs = array('paypal'=>0, 'paypal_email'=>0, 'payway'=>1);
    $messages[] = "Your payment information has been updated.";
}

if (!empty($saveArgs)) {
    //updating user info in database
    foreach ($saveArgs as $arg=>$esc){
        if ($esc) $$arg = mysql_real_escape_string(htmlspecialchars($$arg));
    }

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
        $subject = "LoveMachine Account Edit Successful.";
        $body  = "<p>Congratulations!</p>";
        $body .= "<p>You have successfully updated your settings with ".SERVER_NAME.": <br/>";
        foreach ($messages as $msg) {
            $body .= "&nbsp;&nbsp;$msg<br/>";
        }
        $body .= "</p><p>Love,<br/><br/>Eliza @ the LoveMachine</p>";
        sl_send_email($to, $subject, $body);

        $msg="Account updated successfully!";
    }

    //Wired off, why is this here, causing settings page to exit
    //return 'ok';
}

// getting userInfo to prepopulate fields

    if(!$_SESSION['new_user']){
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

<script type="text/javascript" src="js/skills.js"></script>
<script type="text/javascript" src="js/userinfo.js"></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/sendlove.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript">
    var nclass;

    function validateLocalUpload(file, extension) {
        nclass = '.uploadnotice-local';
        return validateUpload(file, extension);
    }
    function validateW9Upload(file, extension) {
        nclass = '.uploadnotice-w9';
        return validateUpload(file, extension);
    }
    function validateUpload(file, extension) {
        if (! (extension && /^(pdf)$/i.test(extension))){
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
            values = { 
                int_code: $('#int_code').val(),
                phone: $('#phone').val(),
                phone_edit: $('#phone_edit').val(),
                country: $('#country').val(),
                smsaddr: $('#smsaddr').val(),
                provider: $('#provider').val(),
				timezone: $('#timezone').val(),
                journal_alerts: $('#journal_alerts').val(),
                bid_alerts: $('#bid_alerts').val(),
                nickname: $('#nickname').val(),
                save_account: 1,
                username: $('#username').val()
            };
        } else if (type == 'personal') {
            values = {
                about: $("#about").val(),
                skills: $("#skills").val(),
                contactway: $("#contactway").val(),
                save_personal: 1
            }
        } else if (type == 'payment') {
            values = {
                paytype: $("#paytype").val(),
                paypal_email: $("#paypal_email").val(),
                payway: $("#payway").val(),
                save_payment: 1
            }
        }

        $('.error').text('');

        $.ajax({
            type: "POST",
            url: 'settings.php',
            data: values,
            dataType: 'html',
            success: function(json) {
                $('#msg-'+type).text('Account settings saved!');
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
	function ChangePaymentMethod(){
		var paytype = $('#paytype').val();
		paypal.enable();
		payway.enable();
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
        $("#save_account").click(function(){
            saveSettings('account');
            return false;
        });
        $("#save_personal").click(function(){
            saveSettings('personal');
            return false;
        });
        $("#save_payment").click(function(){
            saveSettings('payment');
            return false;
        });
    });
</script>

<title>Worklist | Account Settings</title>

</head>

<body>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->


<h1 class="header">Edit Account Settings</h1>

<div id="account-info" class="settings">

    <table width="100%">
    <tr>
        <td align="left"><h2 class="subheader">Account Info<span class="heading-links"></h2></td>
        <td align="right"><a href="password.php">Change my password...</a></td>
    </tr>
    </table>

    <form method="post" action="settings.php" name="frmsetting">

        <span class="required-bullet">*</span> <span class="required">required fields</span>

        <p id="msg-account" class="error"></p>

        <p><label for = "nickname">Nickname</label><br />
            <span class="required-bullet">*</span> <input name="nickname" type="text" id="nickname"  value = "<?php echo $_SESSION['nickname']; ?>" size="35"/>
        </p>
        <script type="text/javascript">
            var nickname = new LiveValidation('nickname', {validMessage: "You have an OK Nickname."});
            nickname.add(Validate.Format, {pattern: /[@]/, negate:true});
        </script>

        <p><label for = "timezone">What timezone are you in?</label><br />
            <span class="required-bullet">*</span> <select id="timezone" name="timezone">
            <?php
            foreach($timezoneTable as $key => $value){
                $selected = '';
                if (!$_SESSION['new_user'] && $key == $userInfo['timezone']){
                    $selected = 'selected = "selected"';
                }
                echo '<option value = "'.$key.'" '.$selected.'>'.$value.'</option>';
            }
            ?>
            </select>
        </p>

    <?php
        $new_user = (bool) $_SESSION['new_user'];
        $country = !$new_user ? $userInfo['country'] : '';
        $int_code = !$new_user ? $userInfo['int_code'] : '';
        $phone = !$new_user ? $userInfo['phone'] : '';
        $provider = !$new_user ? $userInfo['provider'] : '';
        $sms_flags = !$new_user ? $userInfo['sms_flags'] : '';
        $settingsPage = true;
        include("sms-inc.php");
    ?>

        <input type="submit" id="save_account" value="Save Account Info" alt="Save Account Info" name="save_account" />

    </form>

</div>

<?php if(!$_SESSION['new_user']){ ?>
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
            <input type="text" id="skills" name="skills" class="text-field" value="<?php echo $userInfo['skills']; ?>" style="width:95%" />
        </p>

        <p><label for = "contactway">What is the preferred way to contact you?</label><br />
            <input type="text" id="contactway" name="contactway" class="text-field" value="<?php echo $userInfo['contactway']; ?>" style="width:95%" />
        </p>

        <input type="submit" id="save_personal" value="Save Personal Info" alt="Save Personal Info" name="save_personal" />

    </form>

</div>

<div id="payment-info" class="settings">

    <h2 class="subheader">Payment Info</h2>

    <p id="msg-payment" class="error"></p>

    <form method="post" action="settings.php" name="frmsetting">

        <p>
            <span class="required-bullet">*</span> <select id="paytype" name="paytype" onChange="ChangePaymentMethod();">
                <?php if (empty($userInfo['paypal_email']) && empty($userInfo['payway'])) { ?>
                <option value="how" selected>How shall we pay you?</option>
                <?php } ?>
                <option value="paypal" <?php if($userInfo['paypal']==1) echo 'selected'; else echo  ''?>>Please pay me via Paypal</option>
                <option value="other" <?php if($userInfo['paypal']==0) echo 'selected'; else echo  ''?>>I would prefer another method</option>
            </select>
        </p>

        <blockquote>

            <p id="paytype-paypal"><label>Paypal Email</label><br />
                <span class="required-bullet">*</span> <input type="text" id="paypal_email" name="paypal_email" class="text-field" value="<?php echo $userInfo['paypal_email']; ?>" style="width:95%" />
            </p>
			<script type="text/javascript">
				var paypal = new LiveValidation('paypal_email', {validMessage: "Valid email address."});
				paypal.add(Validate.Email);
				paypal.add(Validate.Presence, { failureMessage: "Can't be empty!" });
         	 </script> 
		   <p id="paytype-other"><label>If another payment method chosen, please enter preference info</label><br />
                <span class="required-bullet">*</span>
                    <input type="text" id="payway" name="payway" class="text-field" value="<?php echo !empty($userInfo['payway']) ? $userInfo['payway'] : ' '; ?>" style="width:95%" />
            </p>
		<script type="text/javascript">
			var payway = new LiveValidation('payway');
			payway.add(Validate.Presence, { failureMessage: "Can't be empty!" });
			payway.add(Validate.Format, { pattern: /^((?!Contract services, check, etc.).)*$/, failureMessage: "Can't be empty!" });
		</script>
        </blockquote>

        <p><label>All US Citizens must submit a W-9 to be paid by LoveMachine </label>
        <a href="http://www.irs.gov/pub/irs-pdf/fw9.pdf" link="#00008B" target="_blank"><FONT
COLOR="Blue">Download W-9 Here</FONT></a>
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
