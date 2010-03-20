<?php
/**
 * Copyright (c) 2010, LoveMachine Inc.
 * All Rights Reserved. 
 * http://www.lovemachineinc.com
 *
 */
// Hack to suppress links in footer.php when showing feeds
$inFeedlist = true;
include('head.html');
?>
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<title>Worklist RSS & Atom Feeds | Lend a Hand</title>
</head>

<body>

<div id="outside">
	<div id="welcome">
	</div>
	<div id="container">
		<div id="left">
		</div>
		<div id="center">
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



