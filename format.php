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
				<span id='stats-text'></span>
            </div>
			
			<script type="text/javascript">
			// Code for stats
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
						$('#lbl-bids').html('Current biddings ' + json[0]);
						$('#lbl-work').html('Current under work ' + json[1]);
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
						$('#lbl-fees').html('Average fees/job<br /><span id="info-status"></span>$ ' + data);
					}
				});
		
				// Get average hours
				$.ajax({
					type: "POST",
					url: 'getstats.php',
					data: 'req=hours',
					dataType: 'html',
					success: function(html) {
						//$('#lbl-hours').html('Average hours between job start and completion ' + html[1]);
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
						for ( var i = 1; i < fees.length; i++ )	{
							var paid;
							if (fees[i][5] != 0)	{
								paid = 'Yes';
							}	else	{
								paid = 'No';
							}
							var row = '<tr class="row"><td>' + fees[i][0] + '</td><td>' + fees[i][1] + '</td><td width="5%">' + fees[i][2] + '</td><td width="10%">$ '  + fees[i][3] + '</td><td width="20%">' + fees[i][4] + '</td><td>' + paid + '</td></tr>';
							$('.table-statslist').append(row);
						}
						var rowCount = fees.length;
						var endrow = '<tr class="row"><td style="text-align:center;" colspan ="7">' + rowCount + ' Jobs Completed</td></tr>';
						$('.table-statslist').append(endrow);
						}
				});

				$('#popup-stats').dialog({ autoOpen: false, maxWidth: 800, width: 700, maxHeight: 800, height: 450 });
				$('#popup-stats').data('title.dialog', 'Statistics for last 7 days');
				$('#popup-stats').dialog('open');
			}
			// End code for stats
			</script>
			
			<!-- Popup for showing stats-->
			<?php require_once('popup-stats.inc') ?>
<!-- END Navigation placeholder -->

