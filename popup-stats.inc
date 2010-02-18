<div id="popup-stats" title = "Statistics for last 7 days" class = "popup-body">
	<form name="popup-form" id="popup-form-edit" action="" method="post">
		<input type="hidden" name="itemid" value="0" />
		<input type="hidden" name="page" value="<?php echo $page ?>" class="popup-page-value" />

		<div id = "for_view">

			<p class="info-label" id="lbl-bids">
			<p class="info-label" id="lbl-work">

			<p class = "info-label" id="lbl-fees">

			<!-- <p class="info-label" id="lbl-hours"><br />
			<span id="info-funded"></span></p>-->
						
		</div>
		
		<div id = "fees_block">
			<p class = "info-label"><br/>Jobs completed in the last 7 days<br/></p>
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
			</table><br />
		</div>
	</form>
</div>
