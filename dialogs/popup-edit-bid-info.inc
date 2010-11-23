    <div id="popup-edit-bid-info" class="popup-body" title = "Edit Bid">
            <form name = "popup-form" id = "popup-form" action="" method="post">
		    <input type="hidden" name="bid_id" value="" />
                <p class="info-label">Bid Amount<br />
                <input type="text" name="bid_amount" id="bid_amount" class="text-field money" size="48" value=""/>&nbsp;USD
                </p>
    
    			<div class="w9notice"></div>
    
                <p class="info-label">Done By<br />
                  <input type="text" class="text-field date" name="done_by_edit" id="done_by_edit" value="" size="20" />
                  <img src="images/Calendar.gif" class="dpButtonCal" onClick="displayDatePicker('done_by_edit', false, 'mdy', '/');" /> 
                  <img src="images/transparent.gif" width="30px" height="1" id="done_by_edit_button"/>
                </p>

                <script type="text/javascript">
                    // see http://regexlib.com/REDetails.aspx?regexp_id=318
                    // but without  dollar sign 22-NOV-2010 <krumch>
                    var regex_bid = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
                    var regex_date = /^\d{1,2}\/\d{1,2}\/\d{4}$|^\d{1,2}\/\d{1,2}\/\d{4} \d{1,2}:\d{2} (am|pm)$/;

                    var bid_amount = new LiveValidation('bid_amount',{ onlyOnSubmit: true });
                        bid_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                        bid_amount.add( Validate.Format, { pattern: regex_bid, failureMessage: "Invalid Input!" });

                    var done_by = new LiveValidation('done_by_edit',{ insertAfterWhatNode: 'done_by_edit_button', onlyOnSubmit: true });
                        done_by.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                        done_by.add( Validate.Format, { pattern: regex_date, failureMessage: "Invalid Input!" });
                </script>

                <p class="info-label">Notes<br />
                <textarea name="notes" size="48" /></textarea>
                </p>
                <br/><input type="submit" id="edit_bid" name="edit_bid" value="Edit Bid" />
            </form>
    </div>
