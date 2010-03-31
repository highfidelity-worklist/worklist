<?php
//  Copyright (c) 2010, LoveMachine Inc.
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
				Welcome, <?php echo $_SESSION['username']; ?><?php echo $feeinfo; ?> | <a href="logout.php">Logout</a>
			<?php }else{ ?>
				Welcome, <?php echo $_SESSION['nickname']; ?><?php echo $feeinfo; ?> | <a href="logout.php">Logout</a>
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

			<a href="worklist.php" class="iToolTip menuWorklist">Worklist</a> |
			<a href="<?php echo SERVER_BASE ?>/journal/" class="iToolTip menuJournal">Journal</a> |
			<a href="<?php echo SERVER_BASE ?>/sendlove/" class="iToolTip menuLove" target="_blank">Love</a> |
			<?php if (!empty($_SESSION['is_runner']) || !empty($_SESSION['is_payer'])) {?>
			<a href="reports.php" class="iToolTip menuReports">Reports</a> |
			<?php } ?>
			<a href="team.php">Team</a> |
			<a href="rewarder.php" class="iToolTip menuRewarder">Rewarder</a> |
			<a href="settings.php" class="iToolTip menuSettings">Settings</a>
			<?php } ?>
		</div>

		<script type="text/javascript">
		// Code for stats
        $(function() {
		$('#popup-user-info').dialog({ autoOpen: false});

		$.ajax({
			type: "POST",
			url: 'getstats.php',
			data: 'req=currentlink',
			dataType: 'html',
			success: function(html) {
				$('#stats-text').html(html);
				MapToolTip();
			}
		});
        });
		function ShowStats()    {
			// Clear the tables
			$('.row').remove();
			$('.runrow').remove();
			$('.mecrow').remove();
			$('.feerow').remove();
			$('.fee7row').remove();
			$('.fee30row').remove();
			$('.pastrow').remove();

			// Set loading text and image
			$('.table-fees-list').append("<tr class='fee30row'><td style='text-align:center; vertical-align:middle;' colspan='7'><img src='images/loader.gif'></img></td></tr>");
			$('.table-fees-list7').append("<tr class='fee7row'><td style='text-align:center; vertical-align:middle;' colspan='7'><img src='images/loader.gif'></img></td></tr>");
			$('.table-runners').append("<tr class='runrow'><td style='text-align:center; vertical-align:middle;' colspan='3'><img src='images/loader.gif'></td></tr>");
			$('.table-mechanics').append("<tr class='mecrow'><td style='text-align:center; vertical-align:middle;' colspan='3'><img src='images/loader.gif'></td></tr>");
			$('.table-fee-adders').append("<tr class='feerow'><td style='text-align:center; vertical-align:middle;' colspan='3'><img src='images/loader.gif'></td></tr>");
			$('.table-past-due').append("<tr class='pastrow'><td style='text-align:center;  vertical-align:middle;' colspan='2'><img src='images/loader.gif'></td></tr>");

			// From here on we load all the data

			// Load the bids and works labels
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=current',
				dataType: 'json',
				success: function(json) {
					$('#span-bids').html(json[0]);
					$('#span-work').html(json[1]);
					// Get average fees
					$.ajax({
						type: "POST",
						url: 'getstats.php',
						data: 'req=fees',
						dataType: 'json',
						success: function(json) {
							var data = json['AVG(amount)'];
							var shorted = Math.round(data*100)/100;
							$('#span-fees').html('$' + shorted);
						}
					});
				}
			});

			// Get last completed jobs in last 30 days
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=feeslist&interval=30',
				dataType: 'json',
				success: function(json) {
					$('.fee30row').remove();
					var fees = json;
					var rowCount = fees.length;
					for ( var i = 0; i < rowCount; i++ )	{
						var user = fees[i][0];
						var funct = "javascript:ShowUserInfo('" + user + "');";
						var row = '<tr class="row"><td onclick="' + funct + '">' + user + '</td>';
						row += '<td>$' + fees[i][1] + '</td>';
						row += '<td>' + fees[i][2] + '%</td></tr>';
						$('.table-fees-list').append(row);
					}
				}
			});

			// Get last completed jobs in last 7 days
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=feeslist&interval=7',
				dataType: 'json',
				success: function(json) {
					$('.fee7row').remove();
					var fees = json;
					var rowCount = fees.length;
					for ( var i = 0; i < rowCount; i++ )	{
						var user = fees[i][0];
						var funct = "javascript:ShowUserInfo('" + user + "');";
						var row = '<tr class="row"><td onclick="' + funct + '">' + user + '</td>';
						row += '<td>$' + fees[i][1] + '</td>';
						row += '<td>' + fees[i][2] + '%</td></tr>';
						$('.table-fees-list7').append(row);
					}
				}
			});

			// Get top 10 runners
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=runners',
				dataType: 'json',
				success: function(json) {
					$('.runrow').remove();
					var data = json;
					var total_tasks = 0;
					var total_working = 0;

					for ( var i = 0; i < data.length; i++ )	{
						var user = data[i][0];
						var funct = "javascript:ShowUserInfo('" + user + "');";
						total_tasks += parseInt( data[i][1] );
						total_working += parseInt( data[i][2] );

						var row = '<tr class="runrow"><td  onclick="' + funct + '" >'+ user + '</td><td style="text-align:right;">' + data[i][1] +
										'</td><td style="text-align:right;">' + data[i][2]  + '</td></tr>';

						$('.table-runners').append(row);
					}
					var totals_row = '<tr class="runrow"><td style="font-weight: bold;">Totals</td><td style="text-align: right; font-weight: bold;">'
											+ total_tasks + '</td><td style="text-align: right; font-weight: bold;">' + total_working + '</td></tr>';
					$('.table-runners').append(totals_row);
				}
			});

			// Get top 10 mechanics
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=mechanics',
				dataType: 'json',
				success: function(json) {
					$('.mecrow').remove();
					var data = json;
					var total_tasks = 0;
					var total_working = 0;

					for ( var i = 0; i < data.length; i++ )	{
						total_tasks += parseInt( data[i][1] );
						total_working += parseInt( data[i][2] );

						var user = data[i][0];
						var funct = "javascript:ShowUserInfo('" + user + "');";
						var row = '<tr class="mecrow"><td  onclick="' + funct + '" >'+ user + '</td><td style="text-align:right;">' + data[i][1] +
										'</td><td style="text-align:right;">' + data[i][2] + '</td></tr>';

						$('.table-mechanics').append(row);
					}
					var totals_row = '<tr class="mecrow"><td style="font-weight: bold;">Totals</td><td style="text-align: right; font-weight: bold;">'
											+ total_tasks + '</td><td style="text-align: right; font-weight: bold;">' + total_working + '</td></tr>';
					$('.table-mechanics').append(totals_row);
				}
			});

			// Get top 10 feed adders
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=feeadders',
				dataType: 'json',
				success: function(json) {
					$('.feerow').remove();
					var data = json;
					var total_tasks = 0;
					var total_fees = 0;

					for ( var i = 0; i < data.length; i++ )	{
						total_tasks += parseInt( data[i][1] );

						var user = data[i][0];
						var funct = "javascript:ShowUserInfo('" + user + "');";
						// Round average fee
						var avg_fee = Math.round(data[i][2]*10)/10;
						total_fees += avg_fee;
						var row = '<tr class="feerow"><td  onclick="' + funct + '" >'+ user + '</td><td style="text-align:right;">' + data[i][1] +
										'</td><td style="text-align:right;">$' + avg_fee  + '</td></tr>';

						$('.table-fee-adders').append(row);
					}
					var totals_row = '<tr class="feerow"><td style="font-weight: bold;">Totals</td><td style="text-align: right; font-weight: bold;">'
											+ total_tasks + '</td><td style="text-align: right; font-weight: bold;">$' + (Math.round( total_fees*10 )/10) + '</td></tr>';
					$('.table-fee-adders').append(totals_row);
				}
			});

			// Get top 10 mechanics with "Past Due"
			$.ajax({
				type: "POST",
				url: 'getstats.php',
				data: 'req=pastdue',
				dataType: 'json',
				success: function(json) {
					$('.pastrow').remove();
					var data = json;
					var total_tasks = 0;

					for ( var i = 0; i < data.length; i++ )	{
						total_tasks += parseInt( data[i][1] );

						var user = data[i][0];
						var funct = "javascript:ShowUserInfo('" + user + "');";
						var row = '<tr class="pastrow"><td  onclick="' + funct + '" >'+ user + '</td><td style="text-align:right;">' + data[i][1] + '</td></tr>';

						$('.table-past-due').append(row);
					}
					var totals_row = '<tr class="pastrow"><td style="font-weight: bold;">Totals</td><td style="text-align: right; font-weight: bold;">'
											+ total_tasks + '</td></tr>';
					$('.table-past-due').append(totals_row);
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
					} else {
						$('#popup-user-info #info-isrunner').attr('checked', '');
					}
					if( json[10] == "1" )	{
						$('#popup-user-info #info-ispayer').attr('checked', 'checked');
					} else {
						$('#popup-user-info #info-ispayer').attr('checked', '');
					}
					$('#popup-user-info #info-isrunner').attr('disabled', 'disabled');
					$('#popup-user-info #info-ispayer').attr('disabled', 'disabled');
				},
				error: function( xhdr, status, err )	{}
			});

			$('#popup-user-info').dialog('open');
		}
		
		function RelativeTime(x){
			var plural = '';
		 
			var mins = 60, hour = mins * 60; day = hour * 24,
				week = day * 7, month = day * 30, year = day * 365;

			if (x >= year) { x = (x / year)|0; dformat="yr"; }
			else if (x >= month) { x = (x / month)|0; dformat="mnth"; }
			else if (x >= day*4) { x = (x / day)|0; dformat="day"; }
			else if (x >= hour) { x = (x / hour)|0; dformat="hr"; }
			else if (x >= mins) { x = (x / mins)|0; dformat="min"; }
			else { x |= 0; dformat="sec"; }
			if (x > 1) plural = 's';
			if (x < 0) x = 0;
			return x + ' ' + dformat + plural;
		}
		// End of user info code
		</script>

		<!-- Popup for showing stats-->
		<?php require_once('popup-stats.inc') ?>

<!-- END Navigation placeholder -->

