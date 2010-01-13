<?php ob_start();
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//
include("config.php");
include("class.session_handler.php");
if (!empty($_SESSION['username'])) {
     header("Location:worklist.php");
     exit;
}
include_once("functions.php");

// Database Connection Establishment String
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
// Database Selection String
mysql_select_db(DB_NAME);

$username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';
if (!empty($_REQUEST['reauth'])) { $msg = 'This transaction is not authorized!'; }
$expired = !empty($_REQUEST['expired']) ? 1 : 0;

if($_POST) {
    if($username != "") {
        $res=mysql_query("select * from ".USERS." where username = '".mysql_real_escape_string($_POST['username'])."' and password = '".sha1(mysql_real_escape_string($_POST['password']))."'");
        if($res && mysql_num_rows($res) > 0) {
            $row=mysql_fetch_array($res);
            if($row['confirm']==1) {
                initSessionData($row);
    
                if ($_POST['redir']) {
                    header("Location:".urldecode($_POST['redir']));
                } else { 
                      if (!empty($_POST['reauth'])) {
                          header("Location:".urldecode($_POST['reauth']));
                      } else {
                          header("Location:worklist.php");
                      }
                }
                exit;
            } else {
                $msg ="Your email is not verified yet. Check your inbox for an email to verify your account first.<br />OR<br /><a href=\"resend.php\">Re-Send Email Confirmation</a>";
            }
        } else {
            $msg ="Invalid username or password";
            $msg = "Enter username or password";
        }
    }
}

if(isset($_SESSION['username']) and isset($_SESSION['confirm_string']) and $_SESSION['username']!="") {
    $res = mysql_query("select id from ".USERS.
                       " where username = '".mysql_real_escape_string($_SESSION['username'])."' and confirm_string = '".mysql_real_escape_string($_SESSION['confirm_string'])."'");
    if($res && mysql_num_rows($res) > 0) {
        $row=mysql_fetch_array($res);
        if (!empty($_POST['redir'])) {
             header("Location:".urldecode($_POST['redir']));
        } else {
               if (!empty($_POST['reauth'])) {
                   header("Location:".urldecode($_POST['reauth']));
               } else {
                   header("Location:worklist.php");
               }
    }
        exit;
    }
}


/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->

<!-- jquery file is for LiveValidation -->
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>


<title>Welcome to the Worklist</title>

</head>

<body>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->
           
    <h1>Login to the Worklist</h1>
                       
    <?php if ($expired) { ?>
    <p class="error">Your session has expired.  Please log in again.</p>
    <?php } ?>

    <div id="in-rt">
        <h3 style="margin-bottom:0;">New to the Worklist?</h3>
        <p><a href="signup.php">Sign up now!</a></p>
                        
        <h3 style="margin-bottom:0;">Forget your password?</h3>
        <p><a href="forgot.php">Recover it here</a></p>
    </div> 

    <div id="in-lt">
        <? if(!empty($msg)) { ?>
            <p class="LV_invalid"><?=$msg?></p>
        <? } ?>
        
        <form id="login" action="" method="post" >
                <input type="hidden" name="redir" value="<?php echo addslashes(!empty($_REQUEST['redir'])?$_REQUEST['redir']:(!empty($_REQUEST['reauth'])?$_REQUEST['reauth']:''))?>" />

                <div class="LVspace">
                <label>Username<br />
                  <input type="text" id="username" name="username" class="text-field" size="40" />
                </label>
                <script type="text/javascript">
                    var username = new LiveValidation('username',{ validMessage: "Valid email address.", onlyOnBlur: false });
                        username.add(SLEmail);
                        username.add(Validate.Length, { minimum: 10, maximum: 50 } );
                </script>
                 </div>
                
                  <p><label>Password<br />
                  <input type="password" id="password" name="password" class="text-field" size="40"  />
                  </label></p>
             
                <p><input type="submit" id="Login" value="Login" name="Login" alt="Login"></p>
                
      </form>

</div>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
