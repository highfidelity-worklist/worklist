<?php 
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
?>

<div id="outside">

<!-- Welcome, login/out -->

	<div id="welcome">
		<?php if ( isset($_SESSION['username'])) {
			$return_from_getfeesums = true;
			include 'getfeesums.php';
			$feeinfo = ' | Your fees: <span class=feesum id=fees-week>$'.$sum['week'].'</span> this week, <span class=feesum id=fees-month>$'.$sum['month'].'</span> this month';
			if (empty($_SESSION['nickname'])){ ?>
				Welcome, <? $_SESSION['username']?><?=$feeinfo?> | <a href="logout.php">Logout</a>
			<?php }else{ ?>
				Welcome, <?php echo $_SESSION['nickname']; ?><?=$feeinfo?> | <a href="logout.php">Logout</a>
			<?php } ?>
			<?php }else{?>
				<a href="login.php">Login</a> | <a href="signup.php">Sign Up</a>
			<?php } ?>
		<div id="tagline">Lend a hand.</div>
	</div id="welcome">

	<div id="container">
		<div id="left"></div>
        
<!-- MAIN BODY -->
		<div id="center">
        
<!-- LOGO -->
			<div id="stats">
				<span id='stats-text'></span>
			</div>
        
<!-- Navigation placeholder -->
		<div id="nav">                    
			<?php if (isset($_SESSION['username'])) { ?>
			<a href="worklist.php">Worklist</a> | 
			<a href="<?php echo SERVER_BASE ?>/journal/">Journal</a> | 
			<?php if (!empty($_SESSION['is_runner'])) {?>
			<a href="reports.php">Reports</a> |
			<?php } ?>
			<a href="team.php">Team</a> |
			<a href="settings.php">Settings</a>
			<?php } ?>
		</div>
			
		<!-- Popup for user info-->
		<?php require_once('popup-user-info.inc') ?>
		<script type="text/javascript">
		// Code for stats
		$('#popup-user-info').dialog({ autoOpen: false});
		
		$.ajax({
			type: "POST",
			url: 'getstats.php',
			data: 'req=currentlink',
			dataType: 'html',
			success: function(html) {
				$('#stats-text').html(html);
			}
		});
	
		function ShowStats()    {
			$('.row').remove();
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=current',
				dataType: 'json',
				success: function(json) {
					$('#span-bids').html(json[0]);
					$('#span-work').html(json[1]);
				}
			});
		
			// Get average fees
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=fees',
				dataType: 'json',
				success: function(json) {
					var data = json['AVG(amount)'];
					var shorted = Math.round(data*10)/10;
					$('#span-fees').html('$' + shorted);
				}
			});
		
			// Get last completed jobs in last 7 days
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=table',
				dataType: 'json',
				success: function(json) {
					fees = json;
					for ( var i = 0; i < fees.length; i++ )	{
						var paid;
						if (fees[i][5] != 0)	{
							paid = 'Yes';
						}	else	{
							paid = 'No';
						}
						var user = fees[i][2];
						var funct = "javascript:ShowUserInfo('"+user+"');";
						var row = '<tr class="row"><td>' + fees[i][0] + '</td><td>' + fees[i][1] + '</td><td  onclick="'+funct+'" width="5%">' + user + '</td><td width="10%">$ '  + fees[i][3] + '</td><td width="20%">' + fees[i][4] + '</td><td>' + paid + '</td></tr>';
						$('.table-statslist').append(row);
					}
					var rowCount = fees.length;
					var endrow = '<tr class="row"><td style="text-align:center;" colspan ="7">' + rowCount + ' Jobs Completed</td></tr>';
					$('.table-statslist').append(endrow);
				}
			});

			// Get top 10 runners
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=runners',
				dataType: 'json',
				success: function(json) {
					var data = json;
					for ( var i = 0; i < data.length; i++ )	{
						var user = data[i][0];
						var funct = "javascript:ShowUserInfo('"+user+"');";
						var row = '<tr class="row"><td  onclick="'+funct+'" >'+ user + '</td><td>' + data[i][1] + '</td><td>' + data[i][2]  + '</td></tr>';
						$('.table-runners').append(row);
					}
				}
			});
				
			// Get top 10 mechanics
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=mechanics',
				dataType: 'json',
				success: function(json) {
					var data = json;
					for ( var i = 0; i < data.length; i++ )	{
						var user = data[i][0];
						var funct = "javascript:ShowUserInfo('"+user+"');";
						var row = '<tr class="row"><td  onclick="'+funct+'" >'+ user + '</td><td>' + data[i][1] + '</td></tr>';
						$('.table-mechanics').append(row);
					}
				}
			});
				
			// Get top 10 feed adders
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=feeadders',
				dataType: 'json',
				success: function(json) {
					var data = json;
					for ( var i = 0; i < data.length; i++ )	{
						var user = data[i][0];
						var funct = "javascript:ShowUserInfo('"+user+"');";
						// Round average fee
						var avg_fee = Math.round(data[i][2]*10)/10;
						var row = '<tr class="row"><td  onclick="'+funct+'" >'+ user + '</td><td>' + data[i][1] + '</td><td> $ ' + avg_fee  + '</td></tr>';
						$('.table-feed-adders').append(row);
					}
				}
			});
				
			// Get top 10 mechanics with "Past Due"
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=pastdue',
				dataType: 'json',
				success: function(json) {
					var data = json;
					for ( var i = 0; i < data.length; i++ )	{
						var user = data[i][0];
						var funct = "javascript:ShowUserInfo('"+user+"');";
						var row = '<tr class="row"><td  onclick="'+funct+'" >'+ user + '</td><td>' + data[i][1] + '</td></tr>';
						$('.table-past-due').append(row);
					}
				}
			});

			$('#popup-stats').dialog({ autoOpen: false, maxWidth: 1000, width: 800, maxHeight: 1000, height: 600 });
			$('#popup-stats').data('title.dialog', 'Task Statistics');
			$('#popup-stats').dialog('open');
		}
		// End code for stats
			
		// Code for showing user info
		function ShowUserInfo( userid )	{
			// Check if the user is real or a message
			if ( userid == 'SVN')	{
				return;
			}	else if ( userid == 'Work List' )	{
				return;
			}
			// If we got an author name, we look the Id on the database
			if( typeof( userid ) != 'number' )	{
				$.ajax({
					type: "POST",
					url: 'getuseritem.php',
					data: 'req=id&nickname='+userid,
					dataType: 'json',
					success: function(json)	{
						userid = json[0];
						_showInfo( userid );
					}
				});
			}	else	{
				_showInfo( userid );
			}
		}
			
		// Helper function needed because of the async nature of ajax
		// * Show the popup
		function _showInfo( userid )	{
			$('#popup-user-info  #popup-form input[type="submit"]').remove();
			$('#roles').show();
			$.ajax({
				type: "POST",
				url: 'getuseritem.php',
				data: 'req=item&item='+userid,
				dataType: 'json',
				success: function(json) {
					$('#popup-user-info #userid').val(json[0]);
					$('#popup-user-info #info-nickname').text(json[1]);
					$('#popup-user-info #info-email').text(json[2]);
					$('#popup-user-info #info-about').text(json[3]);
					$('#popup-user-info #info-contactway').text(json[4]);
					$('#popup-user-info #info-payway').text(json[5]);
					$('#popup-user-info #info-skills').text(json[6]);
					$('#popup-user-info #info-timezone').text(json[7]);
					$('#popup-user-info #info-joined').text(json[8]);
					if( json[9] == "1" )	{
						$('#popup-user-info #info-isrunner').attr('checked', 'checked');
					}	else	{
						$('#popup-user-info #info-isrunner').attr('checked', '');
					}
						$('#popup-user-info #info-isrunner').attr('disabled', 'disabled'); 
				},
				error: function( xhdr, status, err )	{}
			});
		
			$('#popup-user-info').dialog('open');
		}
		// End of user info code
		</script>
			
		<!-- Popup for showing stats-->
		<?php require_once('popup-stats.inc') ?>

<!-- END Navigation placeholder -->

