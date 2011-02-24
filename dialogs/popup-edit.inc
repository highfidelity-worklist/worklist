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
    <div id="popup-edit" title = "Add Job to Worklist" class = "popup-body">
            <form name="popup-form" id="popup-form-edit" action="javascript:;" method="post">
                <input type="hidden" name="itemid" value="0" />
                <input type="hidden" name="page" value="<?php echo $page ?>" class="popup-page-value" />
                <input type="hidden" name="files" value="" />

		<div id = "for_edit" <?php if(!isset($_SESSION['userid'])){ echo 'style = "display:none;"';}?>>
		  <p class="info-label">Summary<br />
		  <input type="text" name="summary" id="summary" class="text-field" size="48" />
		  </p>

  <?php
  $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
  //Using undefined SESSION['userid'] causes a warning
  if (!$is_runner && !empty($_SESSION['userid'])) {
	$budgetUser = new User();
	$budgetUser->findUserById($_SESSION['userid']);
	$has_budget = $budgetUser->getBudget() > 0 ? 1 : 0;
  } else { $has_budget=0; }
?>
        <p class="info-label" style="margin-bottom: 20px;">Project<br/>
            <select id="itemProject" name="project" class="resetToFirstOption">
                <option value="">-- Select --</option>
                <?php foreach ( $projects as $project): ?>
                    <option value="<?php echo $project['project_id']; ?>" <?php echo $project['project_id'] == $worklist['project_id'] ? ' selected = "selected"' : ''; ?>>
                    <?php echo $project['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
		<p class="info-label">Full Description<br />
		  <textarea name="notes" size="48" /></textarea>
		</p>
		<p class="info-label">Invite<br />
			<input type="text" name="invite" class="invite" />
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
				<div class="uploadnotice"></div>
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
		<div class="floatLeft">
                <p class="info-label iToolTip enterAmount">Fee ($USD)<br />
		  <input type="text" name="bid_fee_amount" id="bid_fee_amount" class="text-field money iToolTip enterAmount" size="48" />
                </p>
		</div>

		<div class="floatRight">

                 <p class="info-label iToolTip enterAmount">Description<br />
		  <input type="text" name="bid_fee_desc" id="bid_fee_desc" class="text-field iToolTip enterAmount" size="48" />
                </p>
        </div>
       	<div class="clear"></div>

<?php
   if ($is_runner || $has_budget) {//if user is a runner - allow to change status
  ?>
		  <p class="info-label">Status<br />
		  <select name="status">
		      <option value="SUGGESTED">SUGGESTED</option>
		      <option value="BUG">BUG</option>
		      <option value="BIDDING" selected="selected">BIDDING</option>
		      <option value="WORKING">WORKING</option>
                      <?php if ($is_runner) { ?>
		      <option value="SKIP">SKIP</option>
		      <option value="DONE">DONE</option>
                      <?php } ?>
		  </select>
		  </p>
  <?php }else {?>
		  <input type="hidden" id="status" name="status" value = "SUGGESTED" />    
  <?php }      if($is_runner)
       {
?>
	 <p class="info-label">Pay To
       	 <select name='bid_fee_mechanic_id' id='bid_fee_mechanic_id'>
<?php
	 $user_array = GetUserList($_SESSION['userid'], $_SESSION['nickname']);

	 foreach($user_array as $userid=>$nickname)
	 {
	   if(!empty($filter_name) && $_SESSION[$filter_name] == $userid)
	   {
	     echo "<option value='{$userid}' selected='selected'>{$nickname}</option>\n";
	   }
	   else
	   {
	     echo "<option value='{$userid}'>{$nickname}</option>\n";
	   }
	 }
?>
	 </select></p>
<?php   } else  {  ?>
         <input type='hidden' name='bid_fee_mechanic_id' id='bid_fee_mechanic_id' value='<?php echo $_SESSION['userid']; ?>' />
<?php   }  ?>
		</div><!-- end of fees_single_block -->
		<div id="bid_action_button" class="bid_action_button">
                <input type="submit" id="save_item" name="save_item" value="Save">
        </div>
                <?php } else { ?>
		<div id = "bid-signup">
		<h3>Want to bid?</h3>
		<a href="signup.php">Sign up now!</a>
		</div>
                <?php } ?>
            </form>
        </div>
