<?php ob_start();
//
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//

include("config.php");
include("class.session_handler.php");
include_once("send_email.php");
include("timezones.php");
include("countrylist.php");
include("smslist.php");

$phone = $country = $provider = "";
$msg="";
$to=1;
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME);

$fields_to_htmlescape = array(
				'contactway' => '', 
				'payway' => '', 
				'skills' => '', 
				'timezone' => '', 
				'phone' => '', 
				'country' => '', 
				'provider' => '',
				'smsaddr' => '',
			);

$fields_to_not_escape = array(
				'nickname' => '', 
				'username' => '', 
				'password' => '', 
				'confirmpassword' => '', 
				'sign_up' => '', 
				'phone_edit' => '',
				'confirm' => '',
				'is_uscitizen' => '',
				'about' => '', 
			);
// Remove html tags from about box
if (isset($_POST['about'])) {
	$_POST['about'] = strip_tags($_POST['about']);
}

$minimal_POST = @array_intersect_key($_POST, $fields_to_not_escape + $fields_to_htmlescape);

// TODO: Code repeated from settings.php. Must be put in a library
// compute smsaddr from phone and provider
$prov_address = $smslist[$minimal_POST['country']][$minimal_POST['provider']];
$phone = preg_replace('/\D/', '', $minimal_POST['phone']);
$minimal_POST['smsaddr'] = str_replace('{n}', $phone, $prov_address);

$minimal_POST['username'] = $username = isset($_POST['username']) ? strtolower(trim($_POST['username'])) : '';
// make shure uscitizen is either 0 or 1
$minimal_POST['is_uscitizen'] = ($values_for_db['is_uscitizen'] == 'on') ? 1 : 0;

if(isset($minimal_POST['sign_up'])){
  if(empty($username)||empty($minimal_POST['password'])||empty($minimal_POST['confirmpassword']))
  {
    $msg = "Please fill all required fields.<br />";
  }
  $about = isset($minimal_POST['about']) ? $minimal_POST['about'] : "";
  if(strlen($about) > 150){
    $msg .= "Text in field can't be more than 150 characters!";
  }

  if($msg == ""){
      $send_confirm_email = false;

      $res=mysql_query("select id,confirm,confirm_string from ".USERS." where username ='".mysql_real_escape_string($username)."'");
      if ($res) $user_row = mysql_fetch_assoc($res);

      //echo mysql_num_rows($res); exit;
      if(!$res || !mysql_num_rows($res)){
  //Array ( [nickname] => Proverko [username] => testo@testo.com [password] => proverko [confirmpassword] => proverko [about] => I'm a good guy :) [contactway] => PayPal [payway] => Cash :) [skills] => php, coldfusion, python [timezone] => +0100 [Sign_Up] => Sign Up ) 
	  
	  $minimal_POST = array_merge($minimal_POST, array_map('htmlspecialchars', array_intersect_key($minimal_POST, $fields_to_htmlescape)));
	  unset($minimal_POST['confirmpassword']);
	  unset($minimal_POST['phone_edit']);
	  unset($minimal_POST['sign_up']);
	  $values_for_db = array_map('mysql_real_escape_string', $minimal_POST);
	  $values_for_db['password'] = sha1($values_for_db['password']);
	  $values_for_db['confirm'] = (!empty($minimal_POST['confirm']) && $minimal_POST['confirm'] == base64_encode(sha1(SALT.$to))) ? 1 : 0;
	  $values_for_db['confirm_string'] = rand();
	  //$values_for_db['added'] = 'NOW()'; <-- need this if we don't change the schema to use CURRENT_TIMESTAMP
	  /*
	  $res = mysql_query("INSERT INTO `".USERS."` ( `username`, `password`, `added`, `nickname`, `about`, `contactway`, `payway`, `skills`, `timezone`, `confirm`, `confirm_string`, `phone`, `country-iso`, `smsaddr`  ) ".
	      "VALUES ('".mysql_real_escape_string($username)."', '".sha1(mysql_real_escape_string($minimal_POST['password']))."', NOW(), '".
	      mysql_real_escape_string($minimal_POST['nickname'])."', '".$about."', '".$contactway."', '".$payway."', '".$skills."', '".$timezone."',
	      '$confirm', '$confirm_string' )");
	  */
	  $sql = 'INSERT INTO `'.USERS.'` (`'. implode('`,`', array_keys($values_for_db)) . '`,`added`) VALUES ("'. implode('","', array_values($values_for_db)). '",NOW() )';
	  error_log($sql);
	  $res = mysql_query($sql);
	  $user_id = mysql_insert_id();
	  $to = $username;
	  $subject = "Registration Confirmation";
	  $link = SECURE_SERVER_URL."confirmation.php?cs=${values_for_db['confirm_string']}&str=".base64_encode($username);
	  $body = "<p>You are only one click away from completing your registration with the Worklist!</p>";
	  $body .= "<p><a href=\"$link\">Click here to verify your email address and activate your account.</a></p>";
	  $body .= "<p>Love,<br/>Philip and Ryan</p>";
	  $plain = "You are only one click away from completing your registration!\n\n";
	  $plain .= "Click the link below or copy into your browser's window to verify your email address and activate your account.\n";
	  $plain .= "    ${link}\n\n";
	  $plain .= "Love,\nPhilip and Ryan</p>";
	  sl_send_email($to, $subject, $body, $plain);

	  $confirm_txt = "An email containing a confirmation link was sent to your email address. Please click on that link to verify your email address and activate your account.";
      } else {
	  $to = $username;
	  $confirm_txt = "An email containing a confirmation link was sent to your email address. Please click on that link to verify your email address and activate your account.";
      } 
  }
}


/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<script type="text/javascript" src="js/skills.js"></script>
<script type="text/javascript" src="js/userinfo.js"></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/sendlove.js"></script>
<script type="text/javascript">



</script>

<title>Worklist | Sign Up to the Worklist</title>
</head>

<body <?php if(!empty($username)) { if($to == $username) echo "onload=\"openbox('Signup Confirmation', 1)\"";} ?>>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

<!-- Light Box Code Start -->
<div id="filter" onClick="closebox()"></div>
<div id="box" >
<p align="center">Email Confirmation</p>
<p><font  style="color:#624100; font-size:12px; font-family:Verdana;"><?php echo isset($confirm_txt)?$confirm_txt:'' ?></font></p>
  <p>&nbsp;</p>
  <p align="center"><strong><a href="#" onClick="closebox()">Close</a></strong></p>
</div>
<!-- Light Box Code End -->

        <h1>Create a New Account</h1>
            
		<?php if(!empty($msg)) { ?>
            <p class="LV_invalid"><?php echo $msg; ?></p>
		<?php } ?>
            
        <form action="" name="signup" method="post">
	   <!-- Column containing left part of the fields -->
	   <div class="left-col">
            <div class="LVspace">
	      <p>
		<label for = "nickname">Nickname</label><br />
		<input type="text" id="nickname" name="nickname" class="text-field" size="35" value = "<?php echo isset($_POST['nickname']) ? $_POST['nickname'] : ""; ?>" />
	      </p>
	    </div>
            <script type="text/javascript">
	      var nickname = new LiveValidation('nickname', {validMessage: "You have an OK Nickname."});                                    
	      nickname.add(Validate.Format, {pattern: /[@]/, negate:true});
	    </script>

            <div class="LVspace">
	      <p>
		<label for = "username" >Email *</label><br />
		<input type="text" id="username" name="username" class="text-field" size="35" value = "<?php echo isset($_POST['username']) ? $_POST['username'] : ""; ?>" />   
	      </p>
	    </div>
            <script type="text/javascript">
	      var username = new LiveValidation('username', {validMessage: "Valid email address."});
	      username.add( Validate.Email );
	      username.add(Validate.Length, { minimum: 10, maximum: 50 } );
	    </script>

<?php include("sms-inc.php"); ?>

		<div class="LVspace">
			<p>
				<label for="uscitizen">Are you a US citizen?</label><br />
				<input type="checkbox" id="uscitizen" name="is_uscitizen" <?php echo((isset($_POST['uscitizen']) && ($_POST['uscitizen'] == 'on')) ? 'checked="checked" ' : ''); ?> />
			</p>
		</div>
            <div class="LVspace"><p>
            <label>Password *<br />
            <input type="password" id="password" name="password" class="text-field" size="35" />
            </label>
            </p></div>
            <script type="text/javascript">
                 var password = new LiveValidation('password',{ validMessage: "You have an OK password.", onlyOnBlur: true });
                 password.add(Validate.Length, { minimum: 5, maximum: 12 } ); 
            </script>
            
            <div class="LVspace"><p>
            <label>Confirm Password *<br />
            <input name="confirmpassword" id="confirmpassword" type="password" class="text-field" size="35" />
            </label>
            </p></div>
            <script type="text/javascript">
                 var confirmpassword = new LiveValidation('confirmpassword', {validMessage: "Passwords Match."});
                 confirmpassword.add(Validate.Confirmation, { match: 'password'} ); 
            </script>
	   </div><!-- end of left-col div -->
	   <div class="right-col">
            <div class="LVspacehg">
	      <p>
		<label for = "about">What do we need to know about you?</label><br />
		<textarea id = "about" name = "about" cols = "35" rows = "4"><?php echo isset($_POST['about']) ? $_POST['about'] : ""; ?></textarea>
	      </p>
	    </div>
            <script type="text/javascript">
	      var about = new LiveValidation('about');
	      about.add(Validate.Length, { minimum: 0, maximum: 150 } ); 
	    </script>
            <div class="LVspace">
	      <p>
	      <label for = "contactway">What is the preferred way to contact you?</label><br />
	      <input type="text" id="contactway" name="contactway" class="text-field" size="35" value = "<?php echo isset($_POST['contactway']) ? $_POST['contactway'] : ""; ?>" />
	      </p>
	    </div>

            <div class="LVspace">
	      <p>
	      <label for = "payway">What is the best way to pay you for the work you will do?</label><br />
	      <input type="text" id="payway" name="payway" class="text-field" size="35" value = "<?php echo isset($_POST['payway']) ? $_POST['payway'] : ""; ?>" />
	      </p>
	    </div>

            <div class="LVspace">
	      <p>
	      <label for = "skills">Pick three skills you think are your strongest</label><br />
	      <input type="text" id="skills" name="skills" class="text-field" size="35" value = "<?php echo isset($_POST['skills']) ? $_POST['skills'] : ""; ?>" />
	      </p>
	    </div>

            <div class="LVspace">
	      <p>
	      <label for = "timezone">What timezone are you in?</label><br />
	      <select id="timezone" name="timezone">
<?php
  foreach($timezoneTable as $key => $value){
    $selected = '';
    $zone = isset($_POST['timezone']) ? $_POST['timezone'] : date("O");

    if ($key == $zone){
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

            <p><input type="submit" value="Sign Up" alt="Sign Up" name="sign_up" /></p>
            
        </form>
                
           
<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
