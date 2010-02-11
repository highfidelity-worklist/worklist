    <div id="popup-addfee" class="popup-body" title = "Add Fee">
        <form name="popup-form" id="popup-form-addfee" action="" method="post">
            <input type="hidden" name="itemid" value="" />

            <div>
                <p><label>Amount<br />
                <input type="text" name="fee_amount" id="fee_amount" class="text-field money" size="48" />
                </label></p>
            </div>

                <p><label>Description<br />
                <input type="text" name="fee_desc" id="fee_desc" class="text-field" size="48" />
                </label></p>

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
     <p><label>Pay To:
            <select name='mechanic_id' id='mechanic_id'>
<?php
     $user_array = GetUserList($_SESSION['userid'], $_SESSION['nickname']);

     foreach($user_array as $user_record)
     {
       if(!empty($filter_name) && $_SESSION[$filter_name] == $user_record['userid']) {
         echo "<option value='{$user_record['userid']}' selected='selected'>{$user_record['nickname']}</option>\n";
       } else {
         echo "<option value='{$user_record['userid']}'>{$user_record['nickname']}</option>\n";
       }
     }
?>
     </select></label></p>

<?php   } else  {  ?>

     <p class="info-label">Mechanic<br />
       <span id="mechanic_id"><?php echo $_SESSION['nickname']; ?></span>
     </p>
         <input type='hidden' name='mechanic_id' id='mechanic_id' value='<?php echo $_SESSION['userid']; ?>' />

<?php   }  ?>
        <input type="submit" name="add_fee" value="Add Fee" style="position:relative; left:240px;" />
            </form>
    </div><!-- end of popup-addfee -->
