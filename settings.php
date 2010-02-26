<?php ob_start(); 
//
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

include("config.php");
include("class.session_handler.php");
include("check_session.php");
include("functions.php");
include("timezones.php");
include("countrylist.php");
include("smslist.php");

$con=mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD);
mysql_select_db(DB_NAME,$con);
include_once("send_email.php");
$msg="";
$company="";

$messages = array();
$errors = 0;

  $about = isset($_POST['about']) ? $_POST['about'] : "";
  if(strlen($about) > 150){
    $errors = 1;
    $msg .= "Text in field can't be more than 150 characters!<br />";
  }


// check if phone was updated
$phone_sql = '';
if (isset($_POST['phone_edit']))
{
	// compute smsaddr from phone and provider
	$prov_address = $smslist[$_POST['country']][$_POST['provider']];
	$phone = preg_replace('/\D/', '', $_POST['phone']);
	$_POST['smsaddr'] = str_replace('{n}', $phone, $prov_address);

	$phone_sql_parts = array();
	$phone_keys = array('phone', 'country', 'smsaddr', 'provider');

	foreach ($phone_keys as $phone_key)
	{
		$phone_item = mysql_real_escape_string(htmlspecialchars($_POST[$phone_key]));
		$phone_sql_parts[] = "`${phone_key}` = '${phone_item}'";
	}

	$phone_sql = implode(',', $phone_sql_parts);
}

if (isset($_POST['nickname']) && $errors == 0) { //only 150 characters check for now but who knows :)
    $nickname = mysql_real_escape_string(trim($_POST['nickname']));

    if ($nickname != $_SESSION['nickname']) {
        $_SESSION['nickname'] = $_POST['nickname'];
        $messages[] = "Your nickname is now '$nickname'.";

    } 
	//updating user info in database
	$args = array('nickname', 'contactway', 'payway', 'skills', 'timezone');
	foreach ($args as $arg){
	  $$arg = mysql_real_escape_string(htmlspecialchars($_POST[$arg]));
	}
	// Strip out any html tags
	$about = mysql_real_escape_string(strip_tags($_POST['about']));

	if (isset($_POST['uscitizen']) && ($_POST['uscitizen'] == 'on')) {
		$uscitizen = 1;
	} else {
		$uscitizen = 0;
	}
	
        $sql = 'UPDATE `'.USERS."` SET `nickname` = '${nickname}', `about` = '${about}', `contactway` = '${contactway}', `payway` = '${payway}', `skills` = '${skills}', `is_uscitizen` = $uscitizen, `timezone` = '${timezone}' ". ($phone_sql?", ${phone_sql}":'') ;
	$sql .= "WHERE id = '${_SESSION['userid']}'";
	mysql_unbuffered_query($sql);

	
    $qry = "SELECT * FROM ".USERS." WHERE id='".$_SESSION['userid']."'";
    $rs = mysql_query($qry);
    if (!$rs || !($user_row = mysql_fetch_array($rs))) {
        session::init();
        header("Location:login.php");
    }


    if($_POST['oldpassword']!="")
    {
        $qry="select id from ".USERS." where username='".mysql_real_escape_string($_SESSION['username'])."' and password='".sha1(mysql_real_escape_string($_POST['oldpassword']))."'";
        $rs=mysql_query($qry);
        if(mysql_num_rows($rs) > 0)
        {
            if(($_POST['newpassword']!="")&&($_POST['newpassword']==$_POST['confirmpassword']))
            {
                $qry="update ".USERS." SET password='".sha1(mysql_real_escape_string($_POST['newpassword']))."' where id='".$_SESSION['userid']."'";
                mysql_query($qry);
                $messages[] = "Your password has been updated!";
            } else {
                $msg ="New passwords don't match!";
            }
        } else {
            $msg ="Old password doesn't match!";
        }
    }


    if (!empty($messages)) {
        $to = $_SESSION['username'];
        $subject = "Account Edit Successful.";
        $body  = "<p>Congratulations!</p>";
        $body .= "<p>You have successfully updated your settings with ".SERVER_NAME.": <br/>";
        foreach ($messages as $msg) {
            $body .= "&nbsp;&nbsp;$msg<br/>";
        }
        $body .= "</p><p>Love,<br/>Philip and Ryan</p>";
        sl_send_email($to, $subject, $body);

        $msg="Account updated successfully!";
    }
}

$sqlView = "SELECT * FROM ".USERS." WHERE username = '".mysql_real_escape_string($_SESSION['username'])."'";
$resView = mysql_query($sqlView);
$userInfo = mysql_fetch_array($resView);

if (!$resView || !$userInfo)
{
	session::init();
	header("Location:login.php");
} else {
	extract($userInfo, EXTR_SKIP | EXTR_REFS); // dump values into symbol tables as references
}

if( isset( $_POST['Delete']) ){
	$sql = "DELETE FROM `".USERS."` ".
                "WHERE `id` ='".$_SESSION['userid']."'";
    mysql_query($sql);
    header("Location: logout.php");
    exit;
}

/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->

<link href="css/uploadify.css" rel="stylesheet" type="text/css">
<link type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />

<script type="text/javascript" src="js/skills.js"></script>
<script type="text/javascript" src="js/userinfo.js"></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/sendlove.js"></script>
<script type="text/javascript" src="js/jquery.uploadify.v2.1.0.min.js"></script>
<script type="text/javascript" src="js/swfobject.js"></script>
<script type="text/javascript">
	$(document).ready(function () {
		var user = <?php echo('"' . $_SESSION['userid'] . '"'); ?>;
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
					$('input[name=uscitizen]').attr('checked', 'checked');
					$('#w9upload').show();
		        }
	        }
		});
	});
</script>

<title>Worklist | Account Settings</title>

</head>

<body>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->


            <h1>Edit Account Settings</h1>


                <form method="post" action="settings.php" name="frmsetting" onSubmit="return validate();">

                <p class="error"><?php echo isset($msg)?$msg:''?></p>
	   <!-- Column containing left part of the fields -->
	   <div class="left-col">
                <div class="LVspace">
		  <p><label for = "nickname">Nickname</label><br />
		    <input name="nickname" type="text" id="nickname"  value = "<?php echo $userInfo['nickname']; ?>" size="35"/>
		  </p>
		</div>
		<script type="text/javascript">
		  var nickname = new LiveValidation('nickname', {validMessage: "You have an OK Nickname."});                                    
		  nickname.add(Validate.Format, {pattern: /[@]/, negate:true});
		</script>
				
<?php include("sms-inc.php"); ?>

                <p><label>Current Password<br />
                <input type="password" name="oldpassword" id="oldpassword" size="35" />
                </label></p>
                <div class="LVspace">
                <p><label>New Password<br />
                <input type="password" name="newpassword" id="newpassword" size="35" />
                </label></p>
                <script type="text/javascript">
		  var newpassword = new LiveValidation('newpassword',{ validMessage: "You have an OK password.", onlyOnBlur: true });
		  newpassword.add(Validate.Length, { minimum: 5, maximum: 12 } );
                </script>
                </div>

                <div class="LVspacelg">
                <p><label>Re-enter New Password<br />
                <input type="password" name="confirmpassword" id="confirmpassword" size="35" />
                </label></p>
                <script type="text/javascript">
		  var confirmpassword = new LiveValidation('confirmpassword', {validMessage: "Passwords Match."});
		  confirmpassword.add(Validate.Confirmation, { match: 'newpassword'} ); 
                </script>
                 </div>
                
                <div class="LVspacelg">
                <p><label>US Citizen<br />
                <input type="checkbox" name="uscitizen" />
                </label></p>
                </div>
                
                <div class="LVspacelg" id="w9upload" style="display: none;">
                <p><label>W-9 Form Upload<br />
                <input id="formupload" name="formupload" type="file" />
                </label></p>
                <script type="text/javascript">// <![CDATA[
					$(document).ready(function() {
						var user = <?php echo('"' . $_SESSION['userid'] . '"'); ?>;
						$('#formupload').uploadify({
							uploader: 'images/uploadify.swf',
							script: 'jsonserver.php',
							scriptData: {
								action: 'w9Upload',
								userid: user
							},
							buttonImg: 'images/browse.jpg',
							cancelImg: 'images/cancel.png',
							width: 125,
							height: 24,
							auto: true,
							fileDesc: 'Only *.pdf Files',
							fileExt: '*.pdf',
							folder: '/uploads',
				            onComplete: function(a, b, c, d) {
					            var data = eval("(" + d + ')');
								$('.uploadnotice').empty();
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
				            	}
								$('.uploadnotice').append(html);
				            }
						});
					});
				// ]]></script>
                </div>
                <div class="uploadnotice"></div>
	   </div><!-- end of left-col div -->
	   <div class="right-col">
            <div class="LVspacehg">
	      <p>
		<label for = "about">What do we need to know about you?</label><br />
		<textarea id = "about" name = "about" cols = "35" rows = "4"><?php echo $userInfo['about'] ?></textarea>
	      </p>
	    </div>
            <script type="text/javascript">
	      var about = new LiveValidation('about');
	      about.add(Validate.Length, { minimum: 0, maximum: 150 } ); 
	    </script>

            <div class="LVspace">
	      <p>
	      <label for = "contactway">What is the preferred way to contact you?</label><br />
	      <input type="text" id="contactway" name="contactway" class="text-field" size="35" value = "<?php echo $userInfo['contactway']; ?>" />
	      </p>
	    </div>

            <div class="LVspace">
	      <p>
	      <label for = "payway">What is the best way to pay you for the work you will do?</label><br />
	      <input type="text" id="payway" name="payway" class="text-field" size="35" value = "<?php echo $userInfo['payway']; ?>" />
	      </p>
	    </div>

            <div class="LVspace">
	      <p>
	      <label for = "skills">Pick three skills you think are your strongest</label><br />
	      <input type="text" id="skills" name="skills" class="text-field" size="35" value = "<?php echo $userInfo['skills']; ?>" />
	      </p>
	    </div>

            <div class="LVspace">
	      <p>
	      <label for = "timezone">What timezone are you in?</label><br />
	      <select id="timezone" name="timezone">
<?php
  foreach($timezoneTable as $key => $value){
    $selected = '';
    if ($key == $userInfo['timezone']){
      $selected = 'selected = "selected"';
    }
    echo '
  <option value = "'.$key.'" '.$selected.'>'.$value.'</option>    
  ';
}
?>
	      </select>
	      </p>
	    </div>

	   </div><!-- end of right-col div -->
            <br style = "clear:both;" />
                <input type="submit" value="Update" alt="Update" name="submit" />
                <input type="submit" value="Delete My Account" alt="Delete" name="Delete" class="lgbutton" onClick="javascript: return confirm('Do you really want to delete the account?');" />

                </form>


<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
