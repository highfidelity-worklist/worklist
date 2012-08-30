<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

require_once ("functions.php");

?>
<!-- break contained floats -->
            <div style="float:none; clear:both;"></div>

<!-- END MAIN BODY - Close DIV center -->
        </div>
        <div id="right"></div>

<!-- break 3-col float -->
        <div style="float:none; clear:both;"></div>

<!-- Close DIV container -->
    </div>
    <div id="footer">
<?php
    if(!isset($inWorkItem)){
        $inWorkItem = false;
    }

    // Suppress RSS feed links inside the RSS feed list
    if (!isset($inFeedlist) || ($inFeedlist === false)) {
        if ($inWorkItem === true) {
            $job_id = isset($_GET['job_id']) ? $_GET['job_id'] : false;

            echo '<div class="lefticon" style="padding-top:10px;">
                  <a href="' . SERVER_URL . 'feeds.php?name=comments&job_id=' . $job_id . '" title="Rss & Atom Feeds" style="text-decoration:none;">

                      <img alt="rss feed" title="rss feed" src="' . SERVER_URL .'images/rss20.png" />
                  </a>&nbsp;

                  <a href="' . SERVER_URL .'feeds.php?name=comments&job_id=' . $job_id . '" title="Rss & Atom Feeds" style="text-decoration:none;">
                      <img alt="atom feed" title="atom feed" src="' . SERVER_URL .'images/atom20.png" />
                  </a>
                  </div>';
        } else {
            echo '<div class="lefticon" style="padding-top:10px;">
                  <a href="' . SERVER_URL . 'feedlist.php" title="Rss & Atom Feeds" style="text-decoration:none;">
                      <img alt="rss feed" title="rss feed" src="' . SERVER_URL .'images/rss20.png" />
                      &nbsp;
                      <img alt="atom feed" title="atom feed" src="' . SERVER_URL .'images/atom20.png" />
                  </a>
                  </div>';
        }
    }
    $res = preg_split('%/%', $_SERVER['SCRIPT_NAME']);
    $filename = array_pop($res);
    $repname = array_pop($res);
    $viewSourceLink = "http://svn.worklist.net/";
    $version = Utils::getVersion();

?>
        <div class="copyText">&copy;&nbsp;<? echo date("Y"); ?>
            <a href="http://www.coffeeandpower.com" target="_blank">Coffee & Power, Inc.</a>&nbsp;&nbsp;|&nbsp;&nbsp;
            <a href="privacy.php" target="_blank">Privacy Policy</a>&nbsp;&nbsp;|&nbsp;&nbsp;
            <a href="mailto:contact@worklist.net" target="_blank">Contact Us</a>&nbsp;&nbsp;|&nbsp;&nbsp;
            <a href="<?php echo $viewSourceLink;?>" target="_blank">View the source code</a> | Version: <?php echo $version;?>
            <div id="stats-area">
                <span id='stats-text'>
                    <a href='./worklist.php?status=bidding' class='iToolTip jobsBidding actionBidding' ><span id='count_b'></span> jobs</a>
                    bidding, 
                    <a href='./worklist.php?status=underway' class='iToolTip jobsBidding actionUnderway' ><span id='count_w'></span> jobs</a>
                    underway
                </span>
            </div>
        </div>
    </div>

<!-- Close DIV outside -->
</div id="outside">

<?php require_once('dialogs/footer-analytics.inc'); ?>
</body>
</html>
