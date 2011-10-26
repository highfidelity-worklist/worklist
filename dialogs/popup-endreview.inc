<div id="popup-endreview" class="popup-body" title = "End Code Review">
    <p>
        To finish the review, enter (optional) notes and Review fee, then click the 'Finish Code Review' <br/>
        Click the 'Abort Code Review' button if you are not able to complete the review. <br/>
        Click 'Cancel' to close this dialog if you are not finished."
    </p>
    <form name="popup-form" id="popup-form-endreview" action="" method="post">
            <input type="hidden" name="itemid" value="" />
            <p class="info-label" id="reviewernick" style="display:block">Reviewer: <em>
            <?php 
            $reviewer = new User();
            $reviewer->findUserById($workitem->getCReviewerId());
            echo $reviewer->getNickname();
            ?>
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
                function closeDialog() {
                    $('#popup-endreview').dialog('close');
                }
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
                                var buttonElement = $('.endCr');
                                buttonElement.remove();
                                var startcrButton = '<input class="iToolTip cR" type="submit" value="Start Code Review" onClick="return showReviewForm();"/>';
                                $('.addFee').before(startcrButton);
                                $('#popup-endreview').dialog('close');
                                MapToolTips();
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
        
        <div class="buttonContainer">
            <input type="button" name="cancel" id="cancelendreview" value="Cancel" onClick="closeDialog();"/>
            <input type="button" name="cancel_codereview" value="Abort Code Review" onClick="cancelReview();" />
            <input type="submit" name="finish_codereview" value="Finish Code Review"/>
        </div>
            </form>
</div><!-- end of popup-endreview -->
