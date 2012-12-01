<?php
//  vim:ts=4:et

//  Copyright (c) 2012, Coffee & Power, Inc.
//  All Rights Reserved.
//  http://www.coffeeandpower.com

ob_start();
require_once ("config.php");
require_once ("class.session_handler.php");
require_once ("functions.php");

$msg = '';

// is the user logged in?
if (isset($_SESSION['userid'])) {

    // have they just logged in and been redirected back? eliza wants to know
    if (isset($_SESSION['redirectFromLogin'])) {
        $msgLogin = "Hello, it's good to see you! Let me know if I can help you with anything.".
                    "Type '@faq Eliza' or just click my icon in the lower left corner of the chat.".
                    "~Love, Eliza";
        unset($_SESSION['redirectFromLogin']);
    }

    initSessionDataByUserId($_SESSION["userid"]);
}

// generate random token if none is already saved in session
if(isset($_SESSION['csrf_token'])) {
    $csrf_token = $_SESSION['csrf_token'];
} else {
    $csrf_token = md5(uniqid(rand(), TRUE));
    $_SESSION['csrf_token'] = $csrf_token;
}

require_once("helper/checkJournal_session.php");
require_once("chat.class.php");
require_once("crypt.php");

$query = (isset($_REQUEST['query'])) ? (int) $_REQUEST['query'] : '';

if(isset($_POST['submitbutton']))
    $chat->sendEntry($_POST['author'], $_POST['message']);

$entries_result = $chat->loadEntries(0, array('count' => '50', 'query' => $query));
$entries = $entries_result['entries'];
$author = '';
$username = '';
$is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;

$version = Utils::getVersion();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="copyright" content="Copyright (c) 2010, LoveMachine Inc.  All Rights Reserved. http://www.lovemachineinc.com " />
<link rel="shortcut icon" type="image/x-icon" href="images/worklist_favicon.png" />
<link rel="stylesheet" type="text/css" href="css/CMRstyles.css" />
<link rel="stylesheet" type="text/css" href="css/menu.css" />
<link rel="stylesheet" type="text/css" href="css/common.css" />
<link rel="stylesheet" type="text/css" href="css/jquery.combobox.css" />
<link rel="stylesheet" type="text/css" href="css/jquery-ui.css" media="all" />
<link rel="stylesheet" type="text/css" href="css/jquery.autocomplete.css" />
<link rel="stylesheet" type="text/css" href="css/smoothness/lm.ui.css" />
<link rel="stylesheet" type="text/css" href="css/budget.css" />
<link rel="stylesheet" type="text/css" href="css/journal.css" />
<link rel="stylesheet" type="text/css" href="css/userinfo.css" />
<link rel="stylesheet" type="text/css" href="css/favorites.css" />
<link rel="stylesheet" type="text/css" href="css/LVstyles.css" />
<!--[if IE 6]>
    <link rel="stylesheet" href="css/ie.css" type="text/css" media="all" />
<![endif]-->
<title>Chat - Worklist</title>
<script type="text/javascript">
var refresh = 5 * 1000;
var statusTimeoutId = null;
var lastStatus = 0;

function StopStatus() {
    if(statusTimeoutId) clearTimeout(statusTimeoutId);
    lastStatus = 20;
}
</script>
<script type="text/javascript">
    var is_runner = <?php echo $is_runner ?>;
    var queryStr = '<?php echo $query ?>';
    var currentTime = <?php echo time() ?>;
    var earliestDate = <?php echo outputForJS($chat->getEarliestDate()) ?>;
    var firstDate = <?php echo outputForJS(strtotime($entries[0]['date'])) ?>, lastDate = <?php echo outputForJS(strtotime($entries[count($entries)-1]['date'])-365*24*60*60); ?>;
    var inThePresent = true;
    var lastId = <?php echo outputForJS($entries[count($entries)-1]['id']); ?>;
    <?php if (isset($_SESSION['userid'])): ?>
        var userId = <?php echo outputForJS($_SESSION['userid'], 0) ?>;
    <?php else: ?>
        var userId = 0;
    <?php endif; ?>
    <?php if (!empty($_SESSION['username'])): ?>
        var userName = '<?php echo outputForJS($_SESSION['username']) ?>';
    <?php else: ?>
        var userName = 'Guest';
    <?php endif; ?>
    var userIp = '<?php echo $_SERVER['REMOTE_ADDR']; ?>';
    var gotoDate = <?php echo  isset($_GET['goto']) ? strtotime($_GET['goto']) : (isset($_POST['goto']) ? strtotime($_POST['goto']) : '0'); ?>;
    var messagePruningOffsetPixels = 2000;
    var worklistUrl = '<?php echo WORKLIST_URL; ?>';
    var lastTouched = '<?php echo file_get_contents(JOURNAL_UPDATE_TOUCH_FILE); ?>';
    var latency_sample = '<?php echo LATENCY_SAMPLE; ?>';
    var csrf_token = '<?php echo $csrf_token; ?>';
    var addFromJournal = true;
</script>
<?php
// Force load individual files while we debug the issues using the minimized version - gj 2011-July-05
//        if ($_SERVER['HTTP_HOST'] == 'dev.sendlove.us' && strstr(substr($_SERVER['REQUEST_URI'],0,3),'~')) {
?>
<?php if (true): ?>
<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.12.min.js"></script>
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/class.js"></script>
<script type="text/javascript" src="js/jquery.combobox.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.autogrow.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/jquery_all.js"></script>
<script type="text/javascript" src="js/journal.js"></script>
<script type="text/javascript" src="js/common.js"></script>
<script type="text/javascript" src="js/budget.js"></script>
<?php else: ?>
<script type="text/javascript" src="js/jscode.min.js"></script>
<?php endif; ?>
<!-- js template for file uploads -->
<?php require_once('dialogs/file-templates.inc'); ?>
<?php require_once('dialogs/budget-expanded.inc') ?>
<script type="text/javascript">
<?php
if (isset($error) && $error->getErrorFlag() == 1) {
    $msg = "";
    foreach($error->getErrorMessage() as $m) {
        $msg .= $m ." ";
    }
?>
    retryMessage = "@me <?php echo $msg;?>"
    if (retryMessage) {
        sendEntryRetry();
    }
<?php } ?>

    <?php if ( isset($msgLogin)): ?>
        $.modal.showMessage("<?php echo $msgLogin;?>", 'login', 10000);
    <?php endif; ?>
    // 10000 = 10 seconds
    var checkUserLoggedInTime = 10000;

    $(window).ready(function(){
        if($('#guestUser').val() == "0") {
            setTimeout("checkUserLoggedIn()",checkUserLoggedInTime);
        }
    });

    var checkUserLoggedIn = function(){
        $.getJSON('helper/getAuthenticated.php',function(res) {
            if(res.reload == '1') {
                window.location.reload( false );
            } else {
                setTimeout("checkUserLoggedIn()",checkUserLoggedInTime);
            }
        });
    };
</script>
<!--  setup tooltip for setting and attachement links -->
<script type="text/javascript" src="js/plugins/jquery.tooltip.min.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#settingsButton').tooltip({fade: 250});
        $('input[name="attachment"]').tooltip({fade: 250});

        if ($("#budgetPopup").length > 0) {
            $("#budgetPopup").dialog({
                title: "Earning & Budget",
                autoOpen: false,
                height: 280,
                width: 370,
                position: ['center',60],
                modal: true
            });
            $("#welcome .budget").click(function(){
                $("#budgetPopup").dialog("open").centerDialog();
            });
        }

        stats.setUserId(userId);

        $("#welcome .following").click(function(){
            stats.stats_page = 1;
            $('#jobs-popup').dialog('option', 'title', 'Jobs I am Following').centerDialog();
            stats.showJobs('following');
            return false;
        });

        $('textarea#message-pane').bind('keydown keyup mousedown mouseup change', function(e) {
            if(e.keycode == 13) {
                setLocalTypingStatus(IDLE);
            } else {
                if ($(this).val() !== '') {
                    setLocalTypingStatus(TYPING);
                } else {
                    setLocalTypingStatus(IDLE);
                }
            }
        });
    });
</script>
<style>
#welcomeInside .chatBtn {
    color: #ffffff;
}
</style>
</head>
<body>
<?php require_once('dialogs/popup-budget.inc'); ?>
<!-- Popup for transfered info -->
<?php require_once('dialogs/budget-transfer.inc') ?>
<?php require_once('dialogs/popups-userstats.inc'); ?>
<?php
    if( isset($_SESSION['userid']) )  {
        require_once("helper/popup-penalty.inc");
        require_once("helper/popup-guest-selector.inc");
        require_once("helper/popup-useritems.inc");
    } else {
        require_once("helper/popup-guest-message.inc");
    }
?>
<audio id="chatSoundPlayer" preload="auto">
    <source src="mp3/bubblepop.mp3" />
    <source src="mp3/bubblepop.ogg" />
</audio>
<audio id="systemSoundPlayer" preload="auto">
    <source src="mp3/plazzle.mp3" />
    <source src="mp3/plazzle.ogg" />
</audio>
<audio id="pingSoundPlayer" preload="auto">
    <source src="mp3/warble.mp3" />
    <source src="mp3/warble.ogg" />
</audio>
<audio id="botSoundPlayer" preload="auto">
    <source src="mp3/sweosh.mp3" />
    <source src="mp3/sweosh.ogg" />
</audio>
<audio id="emergencySoundPlayer" preload="auto">
    <source src="mp3/red_alert.mp3" />
    <source src="mp3/red_alert.ogg" />
</audio>
<?php require_once('header.php'); ?>
        <input type="hidden" id="guestUser" value="<?php echo empty($_SESSION['username']) ? 0  : 1; ?>" />
        <div id="container">
            <div id="left"></div>
            <div id="content">
                <div id="debug"></div>
                <div class="clear"></div>
                <div id="guideline">
                    <div class="scroll-wrap">
                        <div class="scroll-pane">
                            <div class="scrollbar">
                                <div class="scrollbar-up"></div>
                                <div class="scrollbar-hold">
                                    <div class="scrollbar-box">
                                        <div class="scrollbar-thumb">
                                            <div class="scrollbar-thumb-left"></div>
                                            <span class="scrollbar-thumb-text"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="scrollbar-down"></div>
                            </div>
                            <div class="scroll-view">
                                <div id="entries">
<?php echo $chat->formatEntries($entries); ?>
                                </div>
                            </div>
                        </div>
                        <div id="bottom-panel">
                            <form method="POST" id="msgSubmit">
                                <div id="bottom_contain">
                                    <div id="bottom_left">
                                        <div id="buttons">
                                            <div id="settingsButton" title="Chat Settings">
                                                <img src="images/gif.gif" width="33" height="23" id="settingsSwitch" align="bottom" />
                                            </div>
                                            <div id="uploadButton" title="Upload to Chat">
                                                <?php
                                                if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], "iPhone")) {
                                                    if(! isset($_SESSION['userid'])) {
                                                        $alt = "alert('Error uploading file: You need to be logged in to upload a file')";
                                                        echo '<a href="javascript:void(0)" onclick="' . $alt . '">';
                                                    } else {
                                                        $enc_id = vEncrypt($_SESSION['userid']);
                                                        echo '<a href="mailto:' . JOURNAL_PICTURE_EMAIL_PREFIX . '+' . $enc_id . JOURNAL_PICTURE_EMAIL_DOMAIN . '?subject=new image">';
                                                    }
                                                }
                                                ?>
                                                <img id="camera_icon" src="images/gif.gif" />
                                                <?php
                                                if (strpos($_SERVER['HTTP_USER_AGENT'], "iPhone")) {
                                                    echo '</a>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="bottom_right">
                                        <textarea name="message-pane" id="message-pane"></textarea>
                                        <input type="hidden" value="<?php echo $author; ?>" name="author" id="author" />
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div id="online-users-container">
                        <div id="online-users"></div>
                    </div>
                    <div id="system-drawer-container">
                        <div id="system-drawer-inner">
                            <div id="search-filter-wrap" class="search">
                                <form id="searchForm" method="post">
                                    <div class="input_box">
                                        <div class="searchDiv">
                                            <input id="query" type="text" value="" size="20" alt="Chat history..." name="query">
                                            <div id="search_reset" class="crossSearch" title="Clear Search Parameters"></div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="system-currentJobs">
                                <div>
                                    <p>
                                        <span id="biddingjobs">no jobs</span> in 
                                        <a target="_blank" href="<?php echo WORKLIST_URL; ?>worklist.php?project=&user=0&status=bidding&journal_query=1">Bidding</a>
                                    </p>
                                    <p>
                                        <span id="reviewjobs">no jobs</span> in need of 
                                        <a target="_blank" href="<?php echo WORKLIST_URL; ?>worklist.php?project=&user=0&status=review&journal_query=1">Code Review</a>
                                    </p>
                                    <?php if (isset($_SESSION['userid'])): ?>
                                        <input type="button" value="Add Job" id="addJob" />
                                    <?php endif; ?>
                                </div>
                            </div>      
                            <div id="penalty-container" style="display:none">
                                <div id="penalty-message">
                                    <h2>You have been sent to Penalty Box</h2>
                                    Time until you can chat again:
                                </div>
                                <div id="penalty-countdown">
                                </div>
                                <div id="penalty-descriptions">
                                    <h3>Reasons given:</h3>
                                </div>
                            </div>
                            <div id="system-drawer-wrapper">
                                <div id="system-drawer-header">System Notifications</div>
                                <div id="system-drawer"></div>
                            </div>
                        </div>
                    </div>
                </div><!-- /#guideline -->
            </div><!-- /#content -->
            <div id="right"></div>
            <div style="clear: both;"></div>
            <img src="images/throbber_white_32.gif" class="scroll-pane-throbber" />
            <div id="attachment-popup"></div>
        </div><!-- /#container -->
        <div style="clear: both"></div>

        <div id="loginbox" style="display: none;"></div>
        <div id="SettingsWindow" style="display: none;">
            <a href="#" id="SettingsWindowClose">close x</a>
            <h1>Settings and Information</h1>
            <div id="botHelper">
                <h2>Chat commands</h2>
                <p>
                    <a href="#" class="botlink" title="Command: @me status [message]" data="@me help status">How do i set my status?</a>
                    <a href="#" class="botlink" title="Command: @me away [message]" data="@me away test">How do I set myself away?</a>
                    <a href="#" class="botlink" title="Command: @ping [user] [message]" data="@ping hello">How do I ping someone?</a>
                    <a href="#" class="botlink" title="Command: @love [to] [why]" data="@love help">How do I send love?</a>
                    <a href="#" class="botlink" title="Command: @alert add [keyword]" data="@alert help">How do I add an alert?</a>
<?php
    foreach(bot::getBotList() as $thebot) {
        $bot = $thebot->respondsTo();
        if (in_array($bot, array('ping', 'alert'))) {
            continue;
        }
        echo "<a href=\"#\" class=\"botlink notTop\" title=\"Command: @{$bot} help\" data=\"@faq {$bot}\">What can I use $bot for?</a> ";
    }
?>
                    <a href="#" class="morebotlinks">See more commands</a>
                </p>
            </div>
            <form id="audiosetter" action="" method="post">
                <h2>Audio Settings</h2>
                <ul>
                    <li>
                        <span style="cursor:pointer;text-decoration:underline;color:#9d9d9d;" onClick="chatSound.play();">Chat Sound</span>
                        <label class="on">ON <input id="chataudioon" name="chataudio" type="radio" onClick="ChatAudioOn();"></label>
                        <label class="off">OFF <input id="chataudiooff" name="chataudio" type="radio" onClick="ChatAudioOff();" /></label>
                    </li>
                    <li>
                        <span style="cursor:pointer;text-decoration:underline;color:#9d9d9d;" onClick="systemSound.play();">System Sound</span>
                        <label class="on">ON <input id="systemaudioon" name="systemaudio" type="radio" onClick="SystemAudioOn();"> </label>
                        <label class="off">OFF <input id="systemaudiooff" name="systemaudio" type="radio" onClick="SystemAudioOff();"> </label>
                    </li>
                    <li>
                        <span style="cursor:pointer;text-decoration:underline;color:#9d9d9d;" onClick="pingSound.play();">Ping Sound</span>
                        <label class="on">ON <input id="pingaudioon" name="pingaudio" type="radio" onClick="PingAudioOn();"> </label>
                        <label class="off">OFF <input id="pingaudiooff" name="pingaudio" type="radio" onClick="PingAudioOff();"> </label>
                    </li>
                    <li>
                        <span style="cursor:pointer;text-decoration:underline;color:#9d9d9d;" onClick="botSound.play();">Bot Sound</span>
                        <label class="on">ON <input id="botaudioon" name="botaudio" type="radio" onClick="BotAudioOn();"> </label>
                        <label class="off">OFF <input id="botaudiooff" name="botaudio" type="radio" onClick="BotAudioOff();"> </label>
                    </li>
<?php
    // @TODO - Discuss: Allow for emergency audio to be flipped on and off by choice?
?>
                    <li>
                        <span style="cursor:pointer;text-decoration:underline;color:#9d9d9d;" onClick="emergencySound.play();">Emergency Alert</span>
                        <label class="on">ON <input id="emergencyaudioon" name="emergencyaudio" type="radio" onClick="EmergencyAudioOn();"> </label>
                        <label class="off">OFF <input id="emergencyaudiooff" name="emergencyaudio" type="radio" onClick="EmergencyAudioOff();"> </label>
                    </li>
                </ul>
            </form>
        </div>

<?php 
$inJournal = true;
include("footer.php"); 
?>

