    <div id="popup-confirmation" class="popup-body" title = "Please confirm">
            I agree that by adding either a bid or a fee, I accept that I will not 
            be paid for this work unless the Worklist and the owner of this job 
            approves payment. Also, I am contributing any code/work that I attach to 
            this job or upload to the Worklist servers whether or not I am paid. <br />

			Worklist is a community based on trust. Don't do work here unless you 
			are willing to trust and be trusted.
			<br />
			<input type="hidden" id="popup-confirmation-type" name="popup_confirmation_type" value="" />
			<input type="button" id="button_accept" name="button_accept" value="I accept" onClick="doConfirmForm($('#popup-confirmation-type').val())" style="position:relative; left:240px;" /> 
    </div>
