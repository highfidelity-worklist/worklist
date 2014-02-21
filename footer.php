<div id="footer">
<?php

$inWorkItem = (isset($inWorkItem) ? $inWorkItem : false);

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
              </a>&nbsp;

              <a title="Twitter Feed" href="https://twitter.com/worklistnet" target="_blank">
                  <img alt="follow us on twitter" title="follow us on twitter" src="' . SERVER_URL . 'images/twitter.png" />
              </a>&nbsp;
              </div>';
    } else {
        echo '<div class="lefticon" style="padding-top:10px;">
              <a href="' . SERVER_URL . 'feedlist.php" title="Rss & Atom Feeds" style="text-decoration:none;">
                  <img alt="rss feed" title="rss feed" src="' . SERVER_URL .'images/rss20.png" />
                  &nbsp;
                  <img alt="atom feed" title="atom feed" src="' . SERVER_URL .'images/atom20.png" />
                  &nbsp;
              </a>
              <a title="Twitter Feed" href="https://twitter.com/worklistnet" target="_blank"><img alt="follow us on twitter" title="follow us on twitter" src="' . SERVER_URL . 'images/twitter.png" /></a>
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
        <a href="http://blog.coffeeandpower.com" target="_blank">Coffee & Power, Inc.</a>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="privacy.php" target="_blank">Privacy Policy</a>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="mailto:contact@worklist.net" target="_blank">Contact Us</a>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="<?php echo $viewSourceLink;?>" target="_blank">View the source code</a> | Version: <?php echo $version;?>
        <div id="stats-area">
            <span id='stats-text'>
                <a href="./jobs?status=bidding&project=0&user=0" class="iToolTip jobsBidding actionBidding">
                    <span id="count_b"></span> jobs</a> bidding, 
                <a href="./jobs?status=underway&project=0&user=0" class="iToolTip jobsBidding actionUnderway">
                    <span id="count_w"></span> jobs</a> underway
            </span>
        </div>
    </div>

</div>

<?php
// Popup for openNotifyOverlay()
echo ((!isset($inFeedlist) || ($inFeedlist === false)) ? '<div id="sent-notify"></div>' : '');

require_once('dialogs/popups-userstats.inc');
require_once('dialogs/popup-addproject.inc');
require_once('dialogs/popup-budget.inc');
require_once('dialogs/footer-analytics.inc');    
?>
</body>
</html>
