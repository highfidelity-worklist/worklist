<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

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
            echo '<div class="lefticon" style="padding-top:10px;"><a href="' . SERVER_URL . 'feeds.php?name=comments&job_id=' . $job_id . '" title="Rss & Atom Feeds" style="text-decoration:none;"><img alt="rss feed" title="rss feed" src="' . SERVER_URL .'images/rss20.png" /></a>&nbsp;<a href="' . SERVER_URL .'feeds.php?name=comments&job_id=' . $job_id . '" title="Rss & Atom Feeds" style="text-decoration:none;"><img alt="atom feed" title="atom feed" src="' . SERVER_URL .'images/atom20.png" /></a></div>';
        } else {
            echo '<div class="lefticon" style="padding-top:10px;"><a href="' . SERVER_URL . 'feedlist.php" title="Rss & Atom Feeds" style="text-decoration:none;"><img alt="rss feed" title="rss feed" src="' . SERVER_URL .'images/rss20.png" />&nbsp;<img alt="atom feed" title="atom feed" src="' . SERVER_URL .'images/atom20.png" /></a></div>';
        }
    }
    $res = preg_split('%/%', $_SERVER['SCRIPT_NAME']);
    $filename = array_pop($res);
    $repname = array_pop($res);
    $viewSourceLink = "http://svn.sendlove.us/";
?>
        <div class="copyText">&copy;&nbsp;<? echo date("Y"); ?> <a href="http://www.lovemachineinc.com" target="_blank">LoveMachine, Inc.</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="privacy.php" target="_blank">Privacy Policy</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?php echo $viewSourceLink;?>" target="_blank">View the source code</a> Version: <?php echo APP_VERSION; ?></div>
        <div class="loves"><a href="http://www.lovemachineinc.com" target="_blank"><img src="images/LMLogo3.png" border="0"/></a></div>
    </div>

<!-- Close DIV outside -->
</div id="outside">

<!-- Google Analytics -->
<script type="text/javascript">
    var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
    document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
    try {
        var pageTracker = _gat._getTracker("UA-11529958-4");
        pageTracker._trackPageview();
    } catch(err) {}
</script>

</body>
</html>
