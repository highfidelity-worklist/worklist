<div id="popup-startreview" class="popup-body" title = "Code review this job">
    <form name="popup-form" id="popup-form-startreview" action="" method="post">
            <input type="hidden" name="itemid" value="" />
            <p class="info-label" style="display:block">Reviewer: <em><?php echo $_SESSION['nickname']; ?></em> </p>
            <p class="info-label iToolTip enterCrAmount" style="display:block">Fee:
            <input type="text" name="fee_amount" id="fee_amount" class="text-field money iToolTip enterCrAmount"
            size="48" value="<?php echo number_format($crFee,2); ?>"/>
            </p>
            <p class="info-label iToolTip enterNotes" style="display:block">Notes (optional):<br />
                <input type="text" name="fee_desc" id="fee_desc" class="text-field iToolTip enterNotes" 
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

                var fee_amount = new LiveValidation('fee_amount',{ onlyOnSubmit: true });
                    fee_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                    fee_amount.add( Validate.Format, { pattern: regex, failureMessage: "Invalid Input!" });
            </script>
         <input type='hidden' name='mechanic_id' id='mechanic_id' value='<?php echo !empty($_SESSION['userid']) ? $_SESSION['userid'] : ''; ?>' />
        <div class="buttonContainer">
            <input type="button" name="cancel_codereview" value="Cancel" onClick="cancelReview();" />
            <input type="submit" name="finish_codereview" value="OK"/>
        </div>
            </form>
</div><!-- end of popup-addfee -->
