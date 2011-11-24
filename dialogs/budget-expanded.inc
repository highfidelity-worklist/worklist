<div id="budget-expanded" class="popup-body" title = "More info">
    <!-- Seach Field -->
    <div id="be-search">
        <div id="be-search-lbl">
            <span>Search in the report</span>
        </div>
        <input type="text" id="be-search-field"/>
        <div id="be-search-clean"></div>
    </div>

    <!-- Report Table -->
	<table width="100%" id="table-budget-expanded">
	    <thead>
	        <tr style="cursor:arrow;" class="table-hdng">
	            <td id="be-id" width="40px">ID</td>
	            <td id="be-summary">Summary</td>
	            <td id="be-who" width="140px">Who</td>
	            <td id="be-amount" width="60px">Amount</td>
	            <td id="be-status" width="65px">Status</td>
	            <td id="be-created" width="55px">Created</td>
	            <td id="be-paid" width="55px">Paid</td>
	        </tr>
	    </thead>
	    <tbody>
	    </tbody>
	</table><br/>
	<input type="submit" class="disableable" id="budget-report-export" value="Export to CSV"/>
</div>
