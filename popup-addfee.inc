    <div id="popup-addfee" class="popup-body" title = "Add Fee">
        <form name="popup-form" id="popup-form-addfee" action="" method="post">
            <input type="hidden" name="itemid" value="" />

            <div style="float:left;width:33%">
                <p class="info-label">Amount<br />
                <input type="text" name="fee_amount" id="fee_amount" class="text-field money" size="48" />
                </p>
            </div>

            <div style="float:left;width:33%">
                <p class="info-label">Reimbursable Expense?<br />
                <input type="checkbox" name="is_expense" id="is_expense" value="1" />
                </p>
            </div>
<?php
         $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
		if ($is_payer)
            {
?>
            <div style="float:left;width:33%">
                <p class="info-label">Or<br />Rewarder?<br />
                <input type="checkbox" name="is_rewarder" id="is_rewarder" value="1" disabled/>
                </p>
            </div>
<?php
            }
?>
            <div style="clear:both"></div>

                <p class="info-label">Description<br />
                <input type="text" name="fee_desc" id="fee_desc" class="text-field" size="48" />
                </p>

                <script type="text/javascript">
                    // see http://regexlib.com/REDetails.aspx?regexp_id=318
                    var regex = /^\$?(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;

                    var fee_amount = new LiveValidation('fee_amount',{ onlyOnSubmit: true });
                        fee_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                        fee_amount.add( Validate.Format, { pattern: regex, failureMessage: "Invalid Input!" });

                    var fee_desc = new LiveValidation('fee_desc',{ onlyOnSubmit: true });
                        fee_desc.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                </script>

<?php
       if($is_runner)
       {
?>
     <p class="info-label">Pay To
            <select name='mechanic_id' id='mechanic_id'>
<?php
     $user_array = GetUserList($_SESSION['userid'], $_SESSION['nickname']);

     foreach($user_array as $userid=>$nickname) {
       if(!empty($filter_name) && $_SESSION[$filter_name] == $userid) {
         echo "<option value='{$userid}' selected='selected'>{$nickname}</option>\n";
       } else {
         echo "<option value='{$userid}'>{$nickname}</option>\n";
       }
     }
?>
     </select></p>

<?php   } else  {  ?>

     <p class="info-label">Pay To<br />
       <span id="mechanic_id"><?php echo !empty($_SESSION['nickname']) ? $_SESSION['nickname'] : ''; ?></span>
     </p>
         <input type='hidden' name='mechanic_id' id='mechanic_id' value='<?php echo !empty($_SESSION['userid']) ? $_SESSION['userid'] : ''; ?>' />

<?php   }  ?>
        <input type="submit" name="add_fee" value="Add Fee" style="position:relative; left:240px;" />
            </form>
    </div><!-- end of popup-addfee -->
