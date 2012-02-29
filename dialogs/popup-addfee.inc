    <div id="popup-addfee" class="popup-body" title = "Add Fee">
        <form name="popup-form" id="popup-form-addfee" action="" method="post">
<?php
    if ($is_runner) {
?>      <div class="clear">
            <p class="info-label">Pay To
<?php
    $filter = new Agency_Worklist_Filter();
    echo $filter->getUserSelectbox(1, false, 'mechanicFee', 'mechanicFee');
?>
                <input type='hidden' name='mechanic_id' id='mechanic_id' value='<?php echo !empty($_SESSION['userid']) ? $_SESSION['userid'] : ''; ?>' />
            </p>
        </div>
<?php
    } else  {
?>
        <p class="info-label">Pay To<br />
            <span id="mechanic_id"><?php echo !empty($_SESSION['nickname']) ? $_SESSION['nickname'] : ''; ?></span>
        </p>
            <input type='hidden' name='mechanic_id' id='mechanic_id' value='<?php echo !empty($_SESSION['userid']) ? $_SESSION['userid'] : ''; ?>' />

<?php   }  ?>
            <br/>
            <input type="hidden" name="itemid" value="" />
            <div class="floatLeft">
                <p class="info-label iToolTip enterAmount">Fee ($USD)<br />
                <input type="text" name="fee_amount" id="fee_amount" class="text-field money iToolTip enterAmount" size="48" />
                </p>
            </div>
            <div class="floatRight">
                <p class="info-label iToolTip enterAmount">
                    Description<br />
                    <textarea name="fee_desc" id="fee_desc" class="expand" rows="2" cols="20"></textarea>
                </p>
            </div>

            <script type="text/javascript">
                // see http://regexlib.com/REDetails.aspx?regexp_id=318
                // but without  dollar sign 22-NOV-2010 <krumch>
                var regex = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
                var fee_amount = new LiveValidation('fee_amount', { onlyOnSubmit: true });
                    fee_amount.add(Validate.Presence, { failureMessage: "Can't be empty!" });
                    fee_amount.add(Validate.Format, { pattern: regex, failureMessage: "Invalid Input!" });

                var fee_desc = new LiveValidation('fee_desc', { onlyOnSubmit: true });
                    fee_desc.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                $('#popup-form-addfee').submit(function() {
                    var massValidationFee = LiveValidation.massValidate([fee_amount, fee_desc]);
                    if (!massValidationFee) {
                        return false;
                    }
                    $('#add_fee').click(function() {
                        $('#add_fee').attr('disabled', 'disabled');
                    });
                    $('#popup-form-addfee').submit(function() {
                        return false;
                    });
                    if ($('#mechanicFee').length !== 0) {
                        $('#popup-addfee #mechanic_id').val($('#popup-addfee .mechanicFeeList li.ui-combobox-list-selected').attr('val'));
                    }
                    return true;
                });
                $(function() {
                    if ($('#mechanicFee').length !== 0) {
                        createActiveFilter('#mechanicFee', 'users', 1);
                    }
                });
            </script>
            <div class="clear">
                <input type="submit" name="add_fee" value="Add Fee" class="floatRight" />
            </div>
        </form>
    </div><!-- end of popup-addfee -->
    
    
