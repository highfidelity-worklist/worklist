<?php
    if (isset($fees)) {
        $max_tip = 0;
        foreach ($fees as $fee) {
            if ($fee['desc'] == 'Accepted Bid') {
                $max_tip = $fee['amount'];
                break;
            }
        }
        
        if (! $max_tip) {
           echo '<h1>No fee to tip from!</h1>';
        } else {
?>
    <div id="popup-addtip" class="popup-body" title = "Add Tip">
        <form name="popup-form" id="popup-form-addtip" action="" method="post">
            <input type="hidden" name="itemid" value="" />
            <div class="floatLeft">
                    <p class="info-label iToolTip enterAmount">Tip ($USD)<br />
                        <input type="text" name="tip_amount" id="tip_amount" class="text-field money iToolTip enterAmount" size="48" />
                    </p>
            </div>
            <div class="clear">
                <p class="info-label iToolTip enterAmount">Note<br />
                    <input type="text" name="tip_desc" id="tip_desc" class="text-field iToolTip enterAmount" size="48" />
                </p>
            </div>
            <script type="text/javascript">
                // see http://regexlib.com/REDetails.aspx?regexp_id=318
                // but without  dollar sign 22-NOV-2010 <krumch>
                var regex = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;

                var tip_amount = new LiveValidation('tip_amount',{ onlyOnSubmit: true });
                    tip_amount.add(Validate.Presence, { failureMessage: "Can't be empty!" });
                    tip_amount.add(Validate.Format, { pattern: regex, failureMessage: "Invalid Input!" });
                    tip_amount.add(Validate.Numericality, { maximum: <?php echo $max_tip; ?>, tooHighMessage: "Tip cannot exceed the accepted bid!" });

                var tip_desc = new LiveValidation('tip_desc',{ onlyOnSubmit: true });
                    tip_desc.add( Validate.Presence, { failureMessage: "Can't be empty!" });
            </script>
            <p class="info-label">Pay To
                <select name='mechanic_id' id='mechanic_id'>
<?php
    $user_array = User::getUserList(0, true);
    // print_r($user_array);
    foreach ($user_array as $user) {
            echo "<option value='" . $user->getId() . "'>" . $user->getNickname() . "</option>\n";
    }
?>
                </select>
            </p>
            <input type="submit" name="add_tip" value="Add Tip" style="position:relative; left:240px;" />
        </form>
    </div>
<?php
        }
    }
?>    
    <!-- end of popup-addfee -->
