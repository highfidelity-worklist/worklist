<div id="popup-stats" title = "Statistics for last 7 days" class = "popup-body">
	<form name="popup-form" id="popup-form-edit" action="" method="post">
		<input type="hidden" name="itemid" value="0" />
		<input type="hidden" name="page" value="<?php echo isset($page) ? $page : 0 ?>" class="popup-page-value" />

		<div id = "for_view">
		<p class="info-label" id="lbl-bids">Current biddings: <span id="span-bids"></span></p>
		<p class="info-label" id="lbl-work">Current under work: <span id="span-work"></span></p>
		<p class = "info-label" id="lbl-fees">Average fees/job: <span id="span-fees"></span></p>
		</div>
		
		<p class="info-label"><br/>Jobs completed in the last 7 days<br/></p>
		<div id="jobstats_block" style="overflow: auto; height: 250px; border: medium 2px;">
			<table width="100%" class="table-statslist">
			<thead>
				<tr class="table-hdng" >
				<td>Id</td>
				<td>Summary</td>
				<td>Who</td>
				<td style="text-align: right;">Fees</td>
				<td style="text-align: right;">Finished</td>
				<td>Paid</td>
				</tr>
			</thead>
			<tbody>
			</tbody>
			</table>
		</div>

		<div style="float:left; width:49%;">
			<p class="info-label"><br/>Top 10 runners<br/></p>
			<table width="100%" class="table-runners">
			<thead>
				<tr class="table-hdng">
				<td>Runner</td>
				<td style="text-align: right;">Tasks</td>
				<td style="text-align: right;">Working on</td>
				</tr>
			</thead>
			<tbody>
			</tbody>
			</table>
		</div>

		<div style="float:right; width:49%;">
			<p class="info-label"><br/>Top 10 mechanics<br/></p>
			<table width="100%" class="table-mechanics">
			<thead>
				<tr class="table-hdng">
				<td>Mechanic</td>
				<td width="35%" style="text-align: right;">Number of Tasks</td>
				<td width="25%" style="text-align: right;">Working on</td>
				</tr>
			</thead>
			<tbody>
			</tbody>
			</table>
		</div>
		
		<div style="float:left; width:49%;">
			<p class="info-label"><br/>Top 10 fee adders<br/></p>
			<table width="100%" class="table-feed-adders">
			<thead>
				<tr class="table-hdng">
				<td>Mechanic</td>
				<td style="text-align: right;">Number Fees</td>
				<td style="text-align: right;">Average Fee Amount</td>
				</tr>
			</thead>
			<tbody>
			</tbody>
			</table>
		</div>	
		
		<div style="float:right; width:49%;">
			<p class="info-label"><br/>Top 10 mechanics with tasks marked "Past Due"<br/></p>
			<table width="100%" class="table-past-due">
			<thead>
				<tr class="table-hdng">
				<td>Mechanic</td>
				<td width="35%" style="text-align: right;">Number of Tasks</td>
				</tr>
			</thead>
			<tbody>
			</tbody>
			</table>
		</div>
	</form>
</div>
