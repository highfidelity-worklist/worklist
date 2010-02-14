    <div id="popup-paid" class="popup-body" title = "Pay Fee">
            <form name="popup-form" action="" method="post">
                <input type="hidden" name="itemid" value="" />
		<?php if (isset($page)) { ?>
		    <input type="hidden" name="page" value="<?php echo $page ?>" class="popup-page-value" />
		<?php } ?>

                <p class="popup-paid-summary"></p>
                <p><label>Are you sure you want to pay this fee? <input type="checkbox" name="paid_check" id="paid_check" size="48">Yes</input>
                </label></p>

                <p><label>Notes<br />
                <textarea name="paid_notes" id="paid_notes" size="48" /></textarea>
                </label></p>

				<div class="paidnotice"></div>

                  <script type="text/javascript">
                    var summary = new LiveValidation('paid_notes',{ onlyOnSubmit: true });
                        summary.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                  </script>
    
                <input type="submit" name="paid" value="Save">
                <input type="submit" name="cancel" value="Cancel">
            </form>
    </div>
