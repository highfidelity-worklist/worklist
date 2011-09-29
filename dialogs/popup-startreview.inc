<div id="popup-startreview" class="popup-body" title = "End Code Review">
    <p>You have started the Code Review. Do not click the Finish Code Review Button until you have
completed the review. Leave this popup open while doing the code review.</p>
    <form name="popup-form" id="popup-form-startreview" action="" method="post">
            <input type="hidden" name="itemid" value="" />
            <p class="info-label" style="display:block">Reviewer: <em>
            <?php echo isset($_SESSION['nickname']) ? $_SESSION['nickname'] : ""; ?>
            </em> </p>
            <p class="info-label iToolTip enterCrAmount" style="display:block">Fee:
            <input type="text" name="crfee_amount" id="crfee_amount" class="text-field money iToolTip enterCrAmount"
            size="48" value="<?php echo number_format($crFee,2); ?>"/>
            </p>
            <p class="info-label iToolTip enterNotes" style="display:block">Notes (optional):<br />
                <input type="text" name="crfee_desc" id="crfee_desc" class="text-field iToolTip enterNotes" 
                size="48" /><br />
                <span class="info-whisper">(Enter detailed code review info in Comments Section)</span>
            </p>

            <script type="text/javascript">
                function cancelReview() {
                    var workitem_id = <?php echo $worklist['id'];?>;
                    var user_id = <?php echo !empty($user_id) ? $user_id : "''"; ?>;
                    $.ajax({
                        type: 'post',
                        url: 'jsonserver.php',
                        data: {
                            workitem: workitem_id,
                            userid: user_id,
                            action:'cancelCodeReview'
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                $('#popup-startreview').dialog('close');
                            }
                        }
                    });
                    return false;
                }
                // see http://regexlib.com/REDetails.aspx?regexp_id=318
                // but without  dollar sign 22-NOV-2010 <krumch>
                var regex = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;

                var crfee_amount = new LiveValidation('crfee_amount',{ onlyOnSubmit: true });
                    crfee_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                    crfee_amount.add( Validate.Format, { pattern: regex, failureMessage: "Invalid Input!" });
            </script>
         <input type='hidden' name='mechanic_id' id='mechanic_id' value='<?php echo !empty($_SESSION['userid']) ? $_SESSION['userid'] : ''; ?>' />
        <div class="buttonContainer">
            <input type="button" name="cancel_codereview" value="Cancel" onClick="cancelReview();" />
            <input type="submit" name="finish_codereview" value="End Code Review"/>
        </div>
            </form>
</div><!-- end of popup-addfee -->
