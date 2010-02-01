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
$msg="";
$to=1;
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME);

$username = isset($_POST['username']) ? strtolower(trim($_POST['username'])) : '';

if(isset($_POST['sign_up'])){
  if(empty($username)||empty($_POST['password'])||empty($_POST['confirmpassword']))
  {
    $msg = "Please fill all required fields.<br />";
  }
  $about = isset($_POST['about']) ? $_POST['about'] : "";
  if(strlen($about) > 150){
    $msg .= "Text in field can't be more than 150 characters!";
  }

  if($msg == ""){
      $send_confirm_email = false;

      $res=mysql_query("select id,confirm,confirm_string from ".USERS." where username ='".mysql_real_escape_string($username)."'");
      if ($res) $user_row = mysql_fetch_assoc($res);

      //echo mysql_num_rows($res); exit;
      if(!$res || !mysql_num_rows($res)){
	  $confirm = (!empty($_POST['confirm']) && $_POST['confirm'] == base64_encode(sha1(SALT.$to))) ? 1 : 0;
	  $confirm_string = rand();
  //Array ( [nickname] => Proverko [username] => testo@testo.com [password] => proverko [confirmpassword] => proverko [about] => I'm a good guy :) [contactway] => PayPal [payway] => Cash :) [skills] => php, coldfusion, python [timezone] => +0100 [Sign_Up] => Sign Up ) 
	  $args = array('about', 'contactway', 'payway', 'skills', 'timezone');
	  foreach ($args as $arg){
	    $$arg = mysql_real_escape_string(htmlspecialchars($_POST[$arg]));
	  }
	  $res = mysql_query("INSERT INTO `".USERS."` ( `username`, `password`, `added`, `nickname`, `about`, `contactway`, `payway`, `skills`, `timezone`, `confirm`, `confirm_string` ) ".
	      "VALUES ('".mysql_real_escape_string($username)."', '".sha1(mysql_real_escape_string($_POST['password']))."', NOW(), '".
	      mysql_real_escape_string($_POST['nickname'])."', '".$about."', '".$contactway."', '".$payway."', '".$skills."', '".$timezone."',
	      '$confirm', '$confirm_string' )");
	  $user_id = mysql_insert_id();
	  
	  $to = $username;
	  $subject = "Registration Confirmation";
	  $link = SECURE_SERVER_URL."confirmation.php?cs=$confirm_string&str=".base64_encode($username);
	  $body = "<p>You are only one click away from completing your registration with the Worklist!</p>";
	  $body .= "<p><a href=\"$link\">Click here to verify your email address and activate your account.</a></p>";
	  $body .= "<p>Love,<br/>Philip and Ryan</p>";
	  $plain = "You are only one click away from completing your registration!\n\n";
	  $plain .= "Click the link below or copy into your browser's window to verify your email address and activate your account.\n";
	  $plain .= "    ".SECURE_SERVER_URL."confirmation.php?cs=$confirm_string&str=".base64_encode($username)."\n\n";
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
