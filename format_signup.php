<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

require_once('header.php');
?>
    <div id="container">
        <div id="left"></div>

<!-- MAIN BODY -->
        <div id="center">

        <!-- Popup for showing stats-->
        <?php
        $showStats=true;
        //These pages don't display stats so skip the hidden popup
        foreach(array('signup.php','login.php','settings.php') as $hideStats) {
          if (strpos($_SERVER['PHP_SELF'],$hideStats)) { $showStats=false; }
        }
        if ($showStats) { require_once('dialogs/popup-stats.inc'); }
        require_once('dialogs/popup-addproject.inc'); 
        
    
         ?>

<!-- END Navigation placeholder -->

