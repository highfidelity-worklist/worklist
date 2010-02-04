    <div id="popup-bid" class="popup-body" title = "Place Bid">
            <table width="100%" class="table-bidlist">
                <thead>
                <tr class="table-hdng" >
                    <td>Email</td>
                    <td>Bid Amount</td>
                    <td>Done By</td>
                    <td>Age</td>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table><br />
            <form name="popup-form" action="" method="post">
                <input type="hidden" name="itemid" value="" />
    
                <p><label>Bid Amount<br />
                <input type="text" name="bid_amount" id="bid_amount" class="text-field money" size="48" />
                </label></p>
    
                <p><label>Done By<br />
                  <input type="text" class="text-field date" name="done_by" id="done_by" value="" size="20" />
                  <img src="images/Calendar.gif" class="dpButtonCal" onClick="displayDatePicker('done_by', false, 'mdy', '/');" /> 
                  <img src="images/transparent.gif" width="30px" height="1" id="done_by_button"/>
                </label></p>

                <script type="text/javascript">
                    // see http://regexlib.com/REDetails.aspx?regexp_id=318
                    var regex_bid = /^\$?(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
                    var regex_date = /^\d{1,2}\/\d{1,2}\/\d{4}$/;

                    var bid_email = new LiveValidation('bid_email',{ onlyOnSubmit: true });
                        bid_email.add( Validate.Presence, { failureMessage: "Can't be empty!" });

                    var bid_amount = new LiveValidation('bid_amount',{ onlyOnSubmit: true });
                        bid_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                        bid_amount.add( Validate.Format, { pattern: regex_bid, failureMessage: "Invalid Input!" });

                    var done_by = new LiveValidation('done_by',{ insertAfterWhatNode: 'done_by_button', onlyOnSubmit: true });
                        done_by.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                        done_by.add( Validate.Format, { pattern: regex_date, failureMessage: "Invalid Input!" });
                </script>

                <p><label>Notes<br />
                <textarea name="notes" size="48" /></textarea>
                </label></p>
    
                <input type="submit" id="bid" name="bid" value="Place Bid">
            </form>
    </div><!-- end of popup-bid -->
