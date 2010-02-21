<div id="popup-stats" title = "Statistics for last 7 days" class = "popup-body">
	<form name="popup-form" id="popup-form-edit" action="" method="post">
		<input type="hidden" name="itemid" value="0" />
		<input type="hidden" name="page" value="<?php echo $page ?>" class="popup-page-value" />

		<div id = "for_view">
			<p class="info-label" id="lbl-bids">Current biddings: <span id="span-bids"></span></p>
			<p class="info-label" id="lbl-work">Current under work: <span id="span-work"></span></p>
			<p class = "info-label" id="lbl-fees">Average fees/job: <span id="span-fees"></span></p>
		</div>
		
		<p class = "info-label"><br/>Jobs completed in the last 7 days<br/></p>
		<div id = "jobstats_block" style="overflow: auto; height: 250px; border: medium 2px;">
			<table width="100%" class="table-statslist">
				<thead>
					<tr class="table-hdng" >
						<td>Id</td>
						<td>Summary</td>
						<td>Who</td>
						<td>Fee</td>
						<td>Finished</td>
						<td>Paid</td>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>

		<div style="margin-right: 5px; float:left; width:45%;">
			<p class = "info-label"><br/>Top 10 runners<br/></p>
			<div id = "runners_block" style="overflow: auto; height 300px;">
				<table width="100%" class="table-runners">
					<thead>
						<tr class="table-hdng">
							<td>Runner</td>
							<td>Tasks</td>
							<td>Working</td>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>

		<div style="position:relative;">
			<p class = "info-label"><br/>Top 10 mechanics<br/></p>
			<div id = "mechanics_block" style="overflow: auto; height 300px;">
				<table width="100%" class="table-mechanics">
					<thead>
						<tr class="table-hdng">
							<td>Mechanic</td>
							<td width="30%">Number of Tasks</td>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		
		<div style="margin-right:5px; float:left; width:45%;">
			<p class = "info-label"><br/>Top 10 fee adders<br/></p>
			<div id = "fee-adders_block" style="overflow: auto; height 300px;">
				<table width="100%" class="table-feed-adders">
					<thead>
						<tr class="table-hdng">
							<td>Mechanic</td>
							<td>Number Fees</td>
							<td>Average Fee Amount</td>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		
		<div style="position:relative;">
			<p class = "info-label"><br/>Top 10 mechanics with tasks marked "Past Due"<br/></p>
			<div id = "pastdue_block" style="overflow: auto; height 300px;">
				<table width="100%" class="table-past-due">
					<thead>
						<tr class="table-hdng">
							<td>Mechanic</td>
							<td width="30%">Number of Tasks</td>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</form>
</div>
