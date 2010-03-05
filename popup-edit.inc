	<script type="text/html" id="uploadImage">
		<div class="filesIcon">
			<img width="75px" height="75px" src="<#= icon #>" />
		</div>
		<div class="filesDescription">
			<h3 class="edittext" id="fileTitle_<#= fileid #>"><#= title #></h3>
			<p class="edittextarea" id="fileDesc_<#= fileid #>"><#= description #></p>
		</div>
		<div class="clear"></div>
	</script>
	<script type="text/html" id="uploadDocument">
		<div class="filesIcon">
			<img width="32px" height="32px" src="<#= icon #>" />
		</div>
		<div class="documents filesDescription">
			<h3 class="edittext" id="fileTitle_<#= fileid #>"><#= title #></h3>
			<p class="edittextarea" id="fileDesc_<#= fileid #>"><#= description #></p>
		</div>
		<div class="clear"></div>
	</script>
    <div id="popup-edit" title = "Add Worklist Item" class = "popup-body">
            <form name="popup-form" id="popup-form-edit" action="" method="post">
                <input type="hidden" name="itemid" value="0" />
                <input type="hidden" name="page" value="<?php echo $page ?>" class="popup-page-value" />
                <input type="hidden" name="files" value="" />

		<div id = "for_edit" <?php if(!isset($_SESSION['userid'])){ echo 'style = "display:none;"';}?>>
		  <p class="info-label">Summary<br />
		  <input type="text" name="summary" id="summary" class="text-field" size="48" />
		  </p>

		  <input type="hidden" id="owner" name="owner" /><!-- for now -->
                  <script type="text/javascript">
                    var summary = new LiveValidation('summary',{ onlyOnSubmit: true });
                        summary.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                  </script>


  <?php
  $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
  if ($is_runner) {//if user is a runner - allow to change status
  ?>
		  <p class="info-label">Status<br />
		  <select name="status">
		      <option value="SUGGESTED">SUGGESTED</option>
		      <option value="BIDDING" selected = "selected" >BIDDING</option>
		      <option value="WORKING">WORKING</option>
		      <option value="SKIP">SKIP</option>
		      <option value="DONE">DONE</option>
		  </select>
		  </p>
  <?php }else {?>
		  <input type="hidden" id="status" name="status" value = "SUGGESTED" />    
  <?php }?>
		  <p class="info-label">Notes<br />
		  <textarea name="notes" size="48" /></textarea>
		  </p>
		<p class="info-label">Invite<br />
			<input type="text" name="invite" class="invite" />
			<script type="text/javascript">
				$(document).ready(function() {
					$('.invite').autocomplete('getusers.php', {
						multiple: true,
						multipleSeparator: ', ',
						extraParams: { nnonly: 1 }
					});
				});
			</script>
			<small>Invite someone to do this job. (Comma-separated list of developers.)</small>
		</p>
			<p class="info-label">Attached Files <br />
				<div id="accordion">
				    <h3><a href="#">Images (<span id="imageCount">0</span>)</a></h3>
				    <div id="fileimagecontainer">

				    </div>
				    <h3><a href="#">Documents (<span id="documentCount">0</span>)</a></h3>
				    <div id="filedocumentcontainer">
					</div>
				</div>
				<input type="hidden" name="files" value="" />

			
				<div id="fileUploadButton">
					Attach new files
				</div>
				<div class=".uploadnotice"></div>
				<script type="text/javascript" src="js/uploadFiles.js"></script>
			</p>




		</div><!-- end div #for-edit-->
		
<?php if (isset($_SESSION['userid'])) { ?>
		<div id = "fees_block">
		  <span  class="info-label">Fees</span>
		  <table width="100%" class="table-feelist">
		      <thead>
		      <tr class="table-hdng" >
			  <td>Who</td>
			  <td>Amount</td>
			  <td>Description</td>
			  <td>Date</td>
			  <td>Paid</td>
		      </tr>
		      </thead>
		      <tbody>
		      </tbody>
		  </table><br />    

		  <p>
		    <input type="submit" name="add_fee_dialog" value="Add Fee">
		  </p>

		</div><!-- end of fees_block -->

		<div id='fees_single_block'>
		<span  class="info-label">Fees</span>
                <p class="info-label">Amount<br />
		  <input type="text" name="bid_fee_amount" id="bid_fee_amount" class="text-field money" size="48" />
                </p>
                 <p class="info-label">Description<br />
		  <input type="text" name="bid_fee_desc" id="bid_fee_desc" class="text-field" size="48" />
                </p>
<?php
       if($is_runner)
       {
?>
	 <p class="info-label">Pay To
       	 <select name='bid_fee_mechanic_id' id='bid_fee_mechanic_id'>
<?php
	 $user_array = GetUserList($_SESSION['userid'], $_SESSION['nickname']);

	 foreach($user_array as $user_record)
	 {
	   if(!empty($filter_name) && $_SESSION[$filter_name] == $user_record['userid'])
	   {
	     echo "<option value='{$user_record['userid']}' selected='selected'>{$user_record['nickname']}</option>\n";
	   }
	   else
	   {
	     echo "<option value='{$user_record['userid']}'>{$user_record['nickname']}</option>\n";
	   }
	 }
?>
	 </select></p>
<?php   } else  {  ?>

	 <p class="info-label">Pay To<br />
	   <span id="bid_fee_mechanic_id"><?php echo $_SESSION['nickname']; ?></span>
	 </p>
         <input type='hidden' name='bid_fee_mechanic_id' id='bid_fee_mechanic_id' value='<?php echo $_SESSION['userid']; ?>' />

<?php   }  ?>
		</div><!-- end of fees_single_block -->

                <input type="submit" id="save_item" name="save_item" value="Save">
                <input type="submit" name="reset" value="Reset">
                <input type="submit" name="cancel" value="Cancel">
                <?php } else { ?>
		<div id = "bid-signup">
		<h3>Want to bid?</h3>
		<a href="signup.php">Sign up now!</a>
		</div>
                <?php } ?>
            </form>
        </div>
