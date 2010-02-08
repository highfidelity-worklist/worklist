<?php 
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
?>

<div id="outside">

<!-- Welcome, login/out -->
       
            <div id="welcome">
            <?php if ( isset($_SESSION['username'])) {

                    if (empty($_SESSION['nickname'])){ ?>
                        Welcome, <? $_SESSION['username']?> | <a href="logout.php">Logout</a>
                    <?php }else{ ?>
                        Welcome, <?php echo $_SESSION['nickname']; ?> | <a href="logout.php">Logout</a>
                    <?php }
                }else{?>
                        <a href="login.php">Login</a> | <a href="signup.php">Sign Up</a>
                <?php } ?>

                <div id="tagline">Lend a hand.</div>
            </div id="welcome">

    
    <div id="container">
    
        <div id="left"></div>
        
<!-- MAIN BODY -->
        
        <div id="center">
        
<!-- LOGO -->
        
<!-- Navigation placeholder -->
            <div id="nav">                    
            <?php if (isset($_SESSION['username'])) { ?>
                <a href="worklist.php">Worklist</a> | 
		<?php if (!empty($_SESSION['is_runner'])) {?>
                <a href="reports.php">Reports</a> |
            	<?php } ?>
                <a href="team.php">Team</a> |
                <a href="settings.php">Settings</a>
            <?php } ?>
            </div>
<!-- END Navigation placeholder -->

