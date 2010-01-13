<?php ob_start();
//
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//

include("config.php");
include("class.session_handler.php");
include_once("send_email.php");

$msg="";
$to=1;
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME);

$to = isset($_REQUEST['to']) ? strtolower(trim($_REQUEST['to'])) : '';
$username = isset($_POST['username']) ? strtolower(trim($_POST['username'])) : '';

if(empty($username)||empty($_POST['password'])||empty($_POST['confirmpassword']))
{
    $msg="Please fill all required fields.";
}
else
{
    $send_confirm_email = false;

    $res=mysql_query("select id,confirm,confirm_string from ".USERS." where username ='".mysql_real_escape_string($username)."'");
    if ($res) $user_row = mysql_fetch_assoc($res);

    //echo mysql_num_rows($res); exit;
    if(!$res || !mysql_num_rows($res))
    {
        $confirm = (!empty($_POST['confirm']) && $_POST['confirm'] == base64_encode(sha1(SALT.$to))) ? 1 : 0;
        $confirm_string = rand();
        $res = mysql_query("insert into ".USERS." ( username, password, added, nickname, confirm, confirm_string ) ".
            "values ('".mysql_real_escape_string($username)."', '".sha1(mysql_real_escape_string($_POST['password']))."', now(), '".
            mysql_real_escape_string($_POST['nickname'])."',
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


/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->

<link href="css/lightbox-hc.css" rel="stylesheet" type="text/css" >
<script language="javascript" src="js/lightbox-hc.js"></script>
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>

<title>Worklist | Sign Up to the Worklist</title>
</head>

<body <? if(!empty($username)) { if($to == $username) echo "onload=\"openbox('Signup Confirmation', 1)\"";} ?> >

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
            
		<? if(!empty($msg)) { ?>
            <p class="LV_invalid"><?=$msg?></p>
		<? } ?>

        <div id="in-rt">
            <h3>Lend a hand.</h3>
            <h3>Find out what doing at LoveMachine.</h3>
            <h3>Create an account and get to work now.</h3>      
        </div> 
            
        <form action="" name="signup" method="post">
            <div class="LVspace"> <p><label>Nickname<br />

            <input type="text" id="nickname" name="nickname" class="text-field" size="35" /></label><br />
            <script type="text/javascript">
                var nickname = new LiveValidation('nickname', {validMessage: "You have an OK Nickname."});                                    
                nickname.add(Validate.Format, {pattern: /[@]/, negate:true});
            </script></label>
            </p></div>
                            
            <div class="LVspace"><p>
            <label>Email *<br />
            <?php if (!empty($to)) { ?>
            <input type="hidden" id="username" name="username" value="<?php echo $to ?>" />
            <input type="text" id="username_show" name="username_show" class="text-field" size="35" value="<?php echo $to ?>" disabled />
            <?php } else { ?>
            <input type="text" id="username" name="username" class="text-field" size="35" />
            <?php } ?>
            </label>    
            <script type="text/javascript">
                    var username = new LiveValidation('username', {validMessage: "Valid email address."});
                    //username.add( Validate.Presence );
                    username.add( Validate.Email );
                    username.add(Validate.Length, { minimum: 10, maximum: 50 } );
            </script>
            </p></div>

            <div class="LVspace"><p>
            <label>Password *<br />
            <input name="password" type="password" id="password" class="text-field" size="35" />
            </label>
            
            <script type="text/javascript">
                 var password = new LiveValidation('password',{ validMessage: "You have an OK password.", onlyOnBlur: true });
                 password.add(Validate.Length, { minimum: 5, maximum: 12 } ); 
            </script>
            </p></div>
            
            <div class="LVspace"><p>
            <label>Confirm Password *<br />
            <input name="confirmpassword" id="confirmpassword" type="password" class="text-field" size="35" />
            </label>
            <script type="text/javascript">
                 var confirmpassword = new LiveValidation('confirmpassword', {validMessage: "Passwords Match."});
                  //confirmpassword.add(Validate.Length, { minimum: 5, maximum: 12 } ); 
                 confirmpassword.add(Validate.Confirmation, { match: 'password'} );
            </script>
            </p></div>

            <br />

            <p><input type="submit" value="Sign Up" alt="Sign Up" name="Sign Up" /></p>
            
        </form>
                
           
<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
