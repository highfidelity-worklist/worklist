<?php
//
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//

ob_start();
include("config.php");
include("class.session_handler.php");
include_once("send_email.php");
include_once("functions.php");
require 'class/CURLHandler.php';

$msg="";
$to=1;
$lightbox = "";

mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME);


if(!empty($_REQUEST['newPayPalEmail']) && !empty($_REQUEST['userId'])) {
    $paypal_hash = md5(date('r', time()));
    $paypal_email = $_REQUEST['newPayPalEmail'];
	
	$user = new User();
	if( !$user->findUserById( (int) $_REQUEST['userId']) ) {
		error_log("Failed to load user by ID on paypal email change");
	    exit(0);
	}
	
	if ($user->isPaypalVerified()) {
		error_log("Trying to change user ".(int) $_REQUEST['userId']." paypal address on confirmation.php");
	    exit(0);
	}
	
	if ($user->getCountry() == 'US') {
	    $user->setW9_accepted('NOW()');
	}
	
	$subject = "Your payment PayPal account has been set";

	$link = SECURE_SERVER_URL . "confirmation.php?pp=" . $paypal_hash . "&ppstr=" . base64_encode($paypal_email);
	$worklist_link = SERVER_URL . "worklist.php";

	$body  = '<p>Dear ' . $user->getNickname() . ',</p>';
	$body .= '<p>Please confirm your payment email address to activate payments on your account and enable you to start placing bids in the <a href="' . $worklist_link . '">Worklist</a>.</p>';
	$body .= '<p><a href="' . $link . '">Click here to confirm your payment address</a></p>';

	$plain  = 'Dear ' . $user->getNickname() . ',' . "\n\n";
	$plain .= 'Please confirm your payment email address to activate payments on your accounts and enable you to start placing bids in the Worklist.' . "\n\n";
	$plain .= $link . "\n\n";
			
	$confirm_txt = "An email containing a confirmation link was sent to your payment email address. Please click on that link to verify your payment email address and activate your account.";
	if (! send_email($paypal_email, $subject, $body, $plain)) { 
		error_log("signup.php: send_email failed");
		$confirm_txt = "There was an issue sending email. Please try again or notify admin@lovemachineinc.com";
	}

	$user->setPaypal_verified(false);
	$user->setPaypal_hash($paypal_hash);
	$user->setPaypal_email($paypal_email);
	$user->save();
	echo "email sent";
	exit(0);
}

if (isset($_REQUEST['str'])) {
    $user = new User();
    $email = mysql_real_escape_string(base64_decode($_REQUEST['str']));

    // verify the email belongs to a user
    if (! $user->findUserByUsername($email)) {
        header("Location:login.php");
        exit;
    } else {
        $data = array("username" => base64_decode($_REQUEST['str']), "token" => $_REQUEST['cs']);
        ob_start();
        echo CURLHandler::doRequest("POST", LOGIN_APP_URL . "confirm", $data);
        $result = ob_get_contents();
        ob_end_clean();
        $result = json_decode($result);
        if ($result->error == 1) {
            die($result->message);
        }
        $sql = "UPDATE ".USERS." SET confirm = 1, is_active = 1 WHERE username = '".mysql_real_escape_string(base64_decode($_REQUEST['str']))."'";
        mysql_query($sql);
        // send welcome email
        $data = array(
            'nickname' => $user->getNickname()
        );

        sendTemplateEmail($user->getUsername(), 'welcome', $data, 'Worklist <contact@worklist.net>');
        if (REQUIRELOGINAFTERCONFIRM) {
            session::init(); // User must log in AFTER confirming (they're not allowed to before)
        } else {
            initSessionData($row); //Optionally can login with confirm URL
        }
    }
} elseif (isset($_REQUEST['ppstr'])) {
    // paypal address confirmation
    $user = new User();
    $paypal_email = mysql_real_escape_string(base64_decode($_REQUEST['ppstr']));
    $hash = mysql_real_escape_string($_REQUEST['pp']);
    // echo $paypal_email;

    // verify the email belongs to a user
    if (! $user->findUserByPPUsername($paypal_email, $hash)) {
        // hacking attempt, or some other error
        header('Location: login.php');
    } else {
        $user->setPaypal_verified(true);
        $user->setPaypal_hash('');
        $user->save();
        header('Location: settings.php?ppconfirmed');
    }
    exit;
}

/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<script language="javascript" src="js/lightbox-hc.js"></script>

<!-- jquery file is for LiveValidation -->
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>


<title>Worklist | Confirmation</title>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/ajaxupload-3.6.js"></script>
<script type="text/javascript" language="javascript" >

var nclass;

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
var user = <?php echo $user->getId(); ?>;

function validateW9Agree(value) {
    if (! $('#w9_accepted').is(':checked') && '<?php echo $user->getCountry(); ?>' == 'US') {
        return false;
    }
    return true;
}

$(document).ready(function () {
	new AjaxUpload('formupload', {
		action: '<?php echo SERVER_URL; ?>jsonserver.php',
		name: 'Filedata',
		data: { action: 'w9Upload', userid: user },
		autoSubmit: true,
		responseType: 'json',
		onSubmit: validateW9Upload,
		onComplete: completeUpload
	});
	
	$('#save_payment').click( function () {
		massValidation = LiveValidation.massValidate( [ paypal, w9_accepted ]);   
		if (!massValidation) {
		  return false;
		}
		$.ajax({
			type: 'POST',
			url: '<?php echo SERVER_URL; ?>confirmation.php',
			data: { 
				newPayPalEmail: $('#paypal_email').val(),
				userId: user
			},
			success: function(json) {
			    $('#savePayPalEmailDialog').dialog('open');
			}
		});	
	});

	$('#enter_later').click( function () { 
		$('#saveLaterDialog').dialog('open');
	});
	
	$('#saveLaterDialog').dialog({
		modal: true,
		autoOpen: false,
		width: 350,
		height: 180,
		position: ['top'],
		resizable: false
	});

	$('#savePayPalEmailDialog').dialog({
		modal: true,
		autoOpen: false,
		width: 300,
		height: 130,
		position: ['top'],
		resizable: false
	});
	
	$('.okButton').click( function() {
		window.location = '<?php echo SERVER_URL; ?>login.php';
	});
});
</script>
</head>

<body <?php echo $lightbox ?> >

<?php include("format_signup.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

<!-- Light Box Code Start -->
<div id="filter" onClick="closebox()"></div>
<div id="box" >
<p align="center">Email Confirmation</p>
<p><font  style="color:#624100; font-size:12px; font-family:helvetica, arial, sans-serif;">Registration complete! Welcome to the Worklist. You can now start work.</font></p>
<p>&nbsp;</p>
<p align="center"><strong><a href="#" onClick="closebox()">Close</a></strong></p>
</div>
<!-- Light Box Code End -->
    <h1>You are almost ready!</h1>
          
    <p>In order to place bids and receive work from Worklist, you need to enter your PayPal enabled E-mail address, and if you are a US Citizen we also need a W-9 form, which you can download from <a href="http://www.irs.gov/pub/irs-pdf/fw9.pdf" target="_blank" >here</a>. Remember, you need a valid US mailing address to receive your 1099 tax documents at the end of the year. If you move, it’s up to you to let us know your new address.</p>
    <p>
        <input type="checkbox" name="w9_accepted" id="w9_accepted" />
        <label id="w9_accepted_label" for="w9_accepted">Check this box to let us know you’ll do your part!</label>
    </p>
<div id="payment-info" class="settings">
    <form method="post" action="settings.php" name="frmsetting">
        <h2 class="subheader">Paypal Payment Info</h2>
        <p id="msg-payment" class="error"></p>
        <blockquote>
            <p id="paytype-paypal"><label>Paypal Email</label><br />
                <input type="text" id="paypal_email" name="paypal_email" class="text-field" value=""  />
            </p>
            <script type="text/javascript">
                var paypal = new LiveValidation('paypal_email', {validMessage: "Valid email address."});
                paypal.add(Validate.Email);
                paypal.add(Validate.Presence);
                
                var w9_accepted = new LiveValidation('w9_accepted', {insertAfterWhatNode: 'w9_accepted_label'});
                w9_accepted.displayMessageWhenEmpty = true;
                w9_accepted.add(Validate.Custom, { against: validateW9Agree, failureMessage: "Oops! You forgot to agree that you'd keep us posted if you move. Please check the box, it's required, thanks!" });
            </script>
            <input type="hidden" name="paytype" id="paytype" value="paypal" />
            <input type="hidden" name="payway" id="payway" value="paypal" />
        </blockquote>
        <h2 class="subheader">W-9 Form <small>(US Citizens Only)</small></h2>
        <p>
        </p>
        <blockquote>
          <p><label style="float:left;line-height:26px">Upload W-9</label>
                <input id="formupload" type="button" value="Browse" style="float:left;margin-left: 8px;" />
          </p>
            <br style="clear:both" />
            <div class="uploadnotice-w9"></div>

        </blockquote>

        <input type="button" id="save_payment" value="Save Payment Info" alt="Save Payment Info" name="save_payment" />
        <input type="button" id="enter_later" value="Enter Info Later" alt="I will enter later" name="enter_later" />

    </form>

</div>
<div id='saveLaterDialog' title='Save Later'>
    <div class='content'>Remember, you need to submit your PayPal e-mail (and W-9 form for US Citizens) before you may bid on jobs. You may now log into the Worklist with OK button that will take user to Login page.
    </div>
     <input type="button" value="Ok" alt="Ok" class="okButton" />
</div>
<div id='savePayPalEmailDialog' title='Save PayPal Email'>
    <div class='content'>You may now log into the Worklist
    </div>
    <input type="button" value="Ok" alt="Ok" class="okButton" />
</div>                    
<?php include("footer.php"); ?>
