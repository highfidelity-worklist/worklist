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

if (isset($_POST['nickname']) && $errors == 0) { //only 150 characters check for now but who knows :)
    $nickname = mysql_real_escape_string(trim($_POST['nickname']));

    if ($nickname != $_SESSION['nickname']) {
        $_SESSION['nickname'] = $_POST['nickname'];
        $messages[] = "Your nickname is now '$nickname'.";

    } 
	//updating user info in database
	$args = array('nickname', 'about', 'contactway', 'payway', 'skills', 'timezone');
	foreach ($args as $arg){
	  $$arg = mysql_real_escape_string(htmlspecialchars($_POST[$arg]));
	}

        $sql = "UPDATE `".USERS."` SET `nickname` = '".$nickname."', `about` = '".$about."', `contactway` = '".$contactway."', `payway` = '".$payway."', `skills` = '".$skills."', `timezone` = '".$timezone."' WHERE id ='".$_SESSION['userid']."'";
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


<script type="text/javascript" src="js/skills.js"></script>
<script type="text/javascript" src="js/userinfo.js"></script>

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
                <input type="submit" value="Delete My Account" alt="Delete" name="Delete" onClick="javascript: return confirm('Do you really want to delete the account?');" />

                </form>


<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
