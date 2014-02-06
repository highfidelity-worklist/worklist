<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

require_once("config.php");

// @TODO: We extra the request but it seems we then don't use it?
extract($_REQUEST);

if(!empty($_POST['username'])) { 
    
    $token = md5(uniqid());
    $user = new User();
    if ($user->findUserByUsername($_POST['username'])) {
        $user->setForgot_hash($token);
        $user->save();
        $resetUrl = SECURE_SERVER_URL . 'resetpass.php?un=' . base64_encode($_POST['username']) . '&amp;token=' . $token;
        $resetUrl = '<a href="' . $resetUrl . '" title="Password Recovery">' . $resetUrl . '</a>';
        sendTemplateEmail($_POST['username'], 'recovery', array('url' => $resetUrl));
        $msg = '<p class="LV_valid">Login information will be sent if the email address ' . $_POST['username'] . ' is registered.</p>';
    } else {
        $msg = '<p class="LV_invalid">Sorry, unable to send password reset information. Try again or contact an administrator.</p>';
    }
}
/*********************************** HTML layout begins here  *************************************/
include("head.php");
?>
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<!-- Add page-specific scripts and styles here, see head.php for global scripts and styles  --> 
    <?php if (basename($_SERVER['PHP_SELF']) == 'worklist.php' && (array_key_exists('inlineHide',$_SESSION) && $_SESSION['inlineHide'] == 0) ) { ?>
    <script type="text/javascript">
    // html needed for welcome message
    var welcomeHTML = '<p><span class="inlineWelcome">Welcome to Worklist!</span></p>'+
    '<p>Browse the list of jobs below or click on <a href="journal.php" class="iToolTip menuJournal">Chat</a> to join our chat.</p>' +
    '<input type="submit" id="hideMessage" name="hideMessage" value="Hide this Message" />'+
    '<div id="inlineSource"><a href="http://svn.worklist.net/" target="_blank">Download our source code</a></div>'
    
    // call the addInlineMessage function to show the inline message for new users
    addInlineMessage(welcomeHTML);
    
    // code for button to hide inline message
    $('#hideMessage').click(function(){
        $('#inlineMessage').fadeOut(750);
    });
    
    </script>
    
    <?php } ?>
    <?php if (basename($_SERVER['PHP_SELF']) == 'worklist.php' && (array_key_exists('inlineHide',$_SESSION) && $_SESSION['inlineHide'] == 0) ) { ?>
    <script type="text/javascript">
    // html needed for welcome message
    var welcomeHTML = '<p><span class="inlineWelcome">Welcome to Worklist!</span></p>'+
    '<p>Browse the list of jobs below or click on <a href="journal.php" class="iToolTip menuJournal">Chat</a> to join our chat.</p>' +
    '<input type="submit" id="hideMessage" name="hideMessage" value="Hide this Message" />'+
    '<div id="inlineSource"><a href="http://svn.worklist.net/" target="_blank">Download our source code</a></div>'
    
    // call the addInlineMessage function to show the inline message for new users
    addInlineMessage(welcomeHTML);
    
    // code for button to hide inline message
    $('#hideMessage').click(function(){
        $('#inlineMessage').fadeOut(750);
    });
    
    </script>
    
    <?php } ?>
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<title>Worklist | Recover Password</title>
</head>
<body>
<?php
    require_once('header.php');
    require_once('format.php');
?>
<br/>
<br/>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->
    <h1>Recover Your Password</h1>
    <h3>So many passwords, so little time... :)</h3><br />
    <form action="#" method="post">
<?php
    if(!empty($msg)) {
        echo $msg;
    }
?>
        <div class="LVspace">
            <label>Email<br />
                <input type="text" id="username" name="username" class="text-field" size="30" />
            </label>
            <script type="text/javascript">
                var username = new LiveValidation('username', {
                    validMessage: "Valid email address.",
                    onlyOnBlur: false
                });
                username.add(Validate.Email);
                username.add(Validate.Length, {
                    minimum: 10,
                    maximum: 50
                });
            </script>
         </div>
         <br />
         <p><input type="submit" value="Send Mail" alt="Send Mail" name="Send Mail" /></p>
    </form>
<?php include("footer.php"); ?>
