<div id="popup-edit-bid-info" class="popup-body" title="Edit Bid">
    <form name="popup-form" id="popup-form" action="" method="post">
        <input type="hidden" name="bid_id" value="" />
        <p class="floatLeft">
            <label id="lblAmountEdit">Amount<br />
                <input type="text" name="bid_amount" id="bid_amount_edit" class="text-field money" size="48" />&nbsp;USD
            </label>
        </p>
        <?php if ($worklist['status'] == 'SUGGESTEDwithBID' || $worklist['status'] == 'SUGGESTED') { ?>
        <div class="clear"></div>
        <div id="bidDoneEdit" class="">
            <input type="hidden" name="done_in_edit" id="done_in_edit" value="24 hours" />
            </div>
        <div id="bidExpiresEdit" class="">
            <input type="hidden" name="bid_expires_edit" id="bid_expires_edit" value="1 years" />
        </div>
        
        <?php } else { ?>
        <div class="clear"></div>
        <div id="bidDoneEdit">
            <label>Done In</label>
            <div class="timeSlider" id="bidDoneEditSlider">
                <div class="sliderStepsArea">
                    <div class="sliderStep sliderStepFirst">
                        <div class="sliderStepValue" >2 hours</div>
                    </div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep sliderStepLast"></div>
                    <div class="sliderStep sliderStepLast sliderStepLastEmpty"></div>
                </div>
            </div>
            <input type="hidden" name="done_in_edit" id="done_in_edit" value="" />
        </div>
        <div class="clear"></div>
        <div id="bidExpiresEdit">
            <label>Bid Expires</label>
            <div class="timeSlider" id="bidExpireEditSlider">
                <div class="sliderStepsArea">
                    <div class="sliderStep sliderStepFirst">
                        <div class="sliderStepValue" >7 days</div>
                    </div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep sliderStepLast"></div>
                    <div class="sliderStep sliderStepLast sliderStepLastEmpty"></div>
                </div>
            </div>
            <input type="hidden" name="bid_expires_edit" id="bid_expires_edit" value="" />
        </div>
        <?php } ?>
        <div class="clear"></div>

        <p><label>Notes <small>(mandatory)</small><br />
        <textarea class="notes expand100-200" name="notes" id="notes-edit"></textarea>
        </label></p>
        <script type="text/javascript">
            // see http://regexlib.com/REDetails.aspx?regexp_id=318
            // but without  dollar sign 22-NOV-2010 <krumch>
            var regex_bid = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
            var regex_date = /^\d{1,2}\/\d{1,2}\/\d{4}$|^\d{1,2}\/\d{1,2}\/\d{4} \d{1,2}:\d{2} (am|pm)$/;

            var bid_amount = new LiveValidation('bid_amount_edit', { insertAfterWhatNode: 'lblAmountEdit', onlyOnSubmit: true });
                bid_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                bid_amount.add( Validate.Format, { pattern: regex_bid, failureMessage: "Invalid Input!" });
        </script>

        <br/><input type="submit" id="edit_bid" name="edit_bid" value="Edit Bid" />
    </form>
</div>
