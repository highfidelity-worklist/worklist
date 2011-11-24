<div id="popup-bid" class="popup-body" title = "Place Bid">
    <form name="popup-form" action="" method="post" >
        <input type="hidden" name="<?php echo $get_variable;?>" value="<?php echo $worklist_id;?>" />
        <p class="floatLeft">
            <label id="lblAmount">Amount<br />
                <input type="text" name="bid_amount" id="bid_amount" class="text-field money" size="48" />&nbsp;USD
            </label>
        </p>
        
        <div class="clear"></div>
        <div id="bidDone" class="">
            <label>Done In</label>
            <div class="timeSlider" id="bidDoneSlider">
                <div class="sliderStepsArea">
                    <div class="sliderStep sliderStepFirst">
                        <div class="sliderStepValue" >2 hours</div>
                    </div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep sliderStepLast"></div>
                    <div class="sliderStep sliderStepLast sliderStepLastEmpty"></div>
                </div>
            </div>
            <input type="hidden" name="done_in" id="done_in" value="2 hours" />
        </div>
        <?php if ($worklist['status'] == 'SUGGESTEDwithBID' || $worklist['status'] == 'SUGGESTED') { ?>
        <input type="hidden" name="bid_expires" id="bid_expires" value="1 years" />
        <?php } else { ?>
        <div class="clear"></div>
        <div id="bidExpires" class="">
            <label>Bid Expires</label>
            <div class="timeSlider" id="bidExpireSlider">
                <div class="sliderStepsArea">
                    <div class="sliderStep sliderStepFirst">
                        <div class="sliderStepValue" >7 days</div>
                    </div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep"></div>
                    <div class="sliderStep sliderStepLast"></div>
                    <div class="sliderStep sliderStepLast sliderStepLastEmpty"></div>
                </div>
            </div>
            <input type="hidden" name="bid_expires" id="bid_expires" value="7 days" />
            
        </div>
       <?php } ?>
        <div class="clear"></div>

        <p><label>Notes <small>(mandatory)</small></label><br />
            <textarea class="notes expand100-200" name="notes" id="notes" /></textarea>
        </p>

        <script type="text/javascript">
            // see http://regexlib.com/REDetails.aspx?regexp_id=318
            // but without dollar sign 22-NOV-2010 <krumch>
            var regex_bid = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
            var regex_date = /^\d{1,2}\/\d{1,2}\/\d{4}$|^\d{1,2}\/\d{1,2}\/\d{4} \d{1,2}:\d{2} (am|pm)$/;

            var bid_amount = new LiveValidation('bid_amount',{ insertAfterWhatNode: 'lblAmount', onlyOnSubmit: true });
                bid_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                bid_amount.add( Validate.Format, { pattern: regex_bid, failureMessage: "Invalid Input!" });

            var notes = new LiveValidation('notes', { onlyOnSubmit: true});
                notes.add( Validate.Presence, { failureMessage: "Can't be empty!" });

            $('#notes').keyup(function() {
                if ($(this).val().length > 0) {
                    $('#bid').removeClass('button-disabled').removeAttr('disabled');
                } else {
                    $('#bid').addClass('button-disabled').prop('disabled', true);
                }
            });
            $('#notes').focus(function() {
                $('#notes').css('color', '#000000');
                $('#notes').css('font-style', 'normal');
                if($("#notes").val() == bidNotesHelper) {
                    $("#notes").val("");
                }
            });

            $('#notes').blur(function() {
                if($("#notes").val() == bidNotesHelper) {
                    $('#notes').css('color', '#999999');
                    $('#notes').css('font-style', 'italic');
                }
            });
        </script>

<?php
    if ($is_runner) {
?>
     <p><label>Mechanic</label>
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

<?php   } else if (isset($_SESSION['userid'])) {  ?>

     <p class="info-label">Mechanic<br />
         <span id="mechanic_id"><?php echo $_SESSION['nickname']; ?></span>
     </p>
         <input type='hidden' name='mechanic_id' id='mechanic_id' value='<?php echo $_SESSION['userid']; ?>' />

<?php   }  ?>
             <input type="submit" id="bid" disabled="disabled" class="button-disabled disableable" name="place_bid" value="Place Bid" />
             <?php if ($worklist['status'] == 'SUGGESTED') { ?>
             <input type="hidden" name="swb" id="swb" value="swb"  />
             <?php   }  ?>
            </form>
    </div><!-- end of popup-bid -->
