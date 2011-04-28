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

if (isset($_REQUEST['str'])) {
    $res = mysql_query("select * from ".USERS." where username ='".mysql_real_escape_string(base64_decode($_REQUEST['str']))."'");
    if (mysql_num_rows($res) == 0) {
        header("Location:login.php");
        exit;
    } else {
        $data = array("username" => base64_decode($_REQUEST['str']), "token" => $_REQUEST['cs']);
      ob_start();
      echo CURLHandler::doRequest("POST", LOGIN_APP_URL . "confirm", $data);
      $result = ob_get_contents();
      ob_end_clean();
      $result = json_decode($result);
      if($result->error == 1){
          die($result->message);
      }
      $sql = "UPDATE ".USERS." SET confirm = 1, is_active = 1 WHERE username = '".mysql_real_escape_string(base64_decode($_REQUEST['str']))."'";
      mysql_query($sql);
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
    // echo $paypal_email;

    // verify the email belongs to a user
    if (! $user->findUserByPPUsername($paypal_email)) {
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
<h1>Email Confirmation</h1>
      
<p>Registration complete! Welcome to the Worklist. You can now start working.</p>
<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
                    
<?php include("footer.php"); ?>
