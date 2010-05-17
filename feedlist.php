<?php
/**
 * Copyright (c) 2010, LoveMachine Inc.
 * All Rights Reserved. 
 * http://www.lovemachineinc.com
 *
 */
// Hack to suppress links in footer.php when showing feeds
include("config.php");
include("class.session_handler.php");
require_once('functions.php');
include_once("send_email.php");
require_once('update_status.php');
$inFeedlist = true;
$userId = getSessionUserId();
if( $userId > 0 )	{
	$user = new User();
	$user->findUserById( $userId );
	$nick = $user->getNickname();
	$userbudget =$user->getBudget();
	$budget =number_format($userbudget);
 }


$current_status = get_status(true);

include('head.html');
?>
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<link href="css/feedback.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/jquery.tabSlideOut.v1.3.js"></script>
<script type="text/javascript" src="js/feedback.js"></script>
<title>Worklist RSS & Atom Feeds | Lend a Hand</title>
</head>
<!-- Feedback tab html -->
<?php require_once('feedback.inc') ?>
<?php include("format.php"); ?>
<body>
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
			<td><a href="feeds.php?name=priority&format=rss"><img src="images/rss.png"></img></a></td>
			<td><a href="feeds.php?name=priority&format=atom"><img src="images/atom.png"></img></a></td>
		</tr>
		<tr class="roweven">
			<td>Worklist most Recent completed jobs</td>
			<td><a href="feeds.php?name=completed&format=rss"><img src="images/rss.png"></img></a></td>
			<td><a href="feeds.php?name=completed&format=atom"><img src="images/atom.png"></img></a></td>
		</tr>				
	</table>
<?php 
include('footer.php');
?>



