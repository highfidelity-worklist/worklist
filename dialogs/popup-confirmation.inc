    <div id="popup-confirmation" class="popup-body" title = "Please confirm">
        I agree that by adding either a bid or a fee, I accept that I will not 
        be paid for this work unless <?php echo $workitem_project->getOwnerCompany(); ?>
        and the owner of this job approves payment.
        Also, by clicking the 'I accept' button, I am contributing all code and work
        that I attach to this job or upload to the Worklist servers, including any and
        all intellectual property rights related thereto, whether or not I am paid. All
        intellectual property and code I contribute is solely owned by
        <?php echo $workitem_project->getOwnerCompany(); ?>, and I hereby make all
        assignments necessary to accomplish the foregoing.
        <br />
        <input type="hidden" id="popup-confirmation-type" name="popup_confirmation_type" value="" />
        <input type="button" id="button_accept" name="button_accept" value="I accept" onClick="doConfirmForm($('#popup-confirmation-type').val())" style="position:relative; left:240px;" /> 
    </div>
