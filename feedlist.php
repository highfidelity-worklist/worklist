<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

// Hack to suppress links in footer.php when showing feeds

include("config.php");
include("class.session_handler.php");
include("check_session.php");
require_once('functions.php');
include_once("send_email.php");
$inFeedlist = true;
$userId = getSessionUserId();
if( $userId > 0 )   {
    $user = new User();
    $user->findUserById( $userId );
    $nick = $user->getNickname();
    $userbudget =$user->getBudget();
    $budget =number_format($userbudget);
 }

include('head.html');
define('RSS_ICON_HTML', '<img alt="rss feed" src="' . SERVER_URL . 'images/rss.png" title="rss feed" />');
define('ATOM_ICON_HTML', '<img alt="rss feed" src="' . SERVER_URL . 'images/atom.png" title="rss feed" />');
?>
<link href="css/worklist.css" rel="stylesheet" type="text/css" />
<title>Worklist RSS & Atom Feeds | Lend a Hand</title>
</head>
<body>
<?php
    require_once('header.php');
    require_once('format.php');
?>
    <table class="table-worklist" width="100%">
        <thead>
            <tr class="table-hdng">
                <td>Name</td>
                <td>RSS</td>
                <td>Atom</td>
            </tr>
        </thead>
        <tr class="rowodd">
            <td >Worklist Top Priority Bidding Jobs</td>
            <td><a href="<?php echo SERVER_URL; ?>feeds.php?name=priority&format=rss"><?php echo RSS_ICON_HTML; ?></a></td>
            <td><a href="<?php echo SERVER_URL; ?>feeds.php?name=priority&format=atom"><?php echo ATOM_ICON_HTML; ?></a></td>
        </tr>
        <tr class="roweven">
            <td>Worklist most Recent completed jobs</td>
            <td><a href="<?php echo SERVER_URL; ?>feeds.php?name=completed&format=rss"><?php echo RSS_ICON_HTML; ?></a></td>
            <td><a href="<?php echo SERVER_URL; ?>feeds.php?name=completed&format=atom"><?php echo ATOM_ICON_HTML; ?></a></td>
        </tr>               
        <tr class="roweven">
            <td>Worklist most recent comments</td>
            <td><a href="<?php echo SERVER_URL; ?>feeds.php?name=comments&format=rss"><?php echo RSS_ICON_HTML; ?></a></td>
            <td><a href="<?php echo SERVER_URL; ?>feeds.php?name=comments&format=atom"><?php echo ATOM_ICON_HTML; ?></a></td>
        </tr>
    </table>
<?php require_once('footer.php'); ?>