<div id="popup-edit" title="Add Job to Worklist" class="popup-body">
    <form name="popup-form" id="popup-form-edit" action="javascript:;" method="post">
        <input type="hidden" name="itemid" value="0" />
        <input type="hidden" name="page" value="<?php echo $page ?>" class="popup-page-value" />
        <input type="hidden" name="files" value="" />
        <div id="for_edit" <?php if(!isset($_SESSION['userid'])){ echo 'style = "display:none;"';}?>>
<?php
  $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
  //Using undefined SESSION['userid'] causes a warning
  if (!$is_runner && !empty($_SESSION['userid'])) {
      $budgetUser = new User();
      $budgetUser->findUserById($_SESSION['userid']);
      $has_budget = $budgetUser->getBudget() > 0 ? 1 : 0;
  } else { 
      $has_budget = 0;
  }
?>
        <p class="info-label" style="margin-bottom: 5px;">Project<br/>
            <select id="itemProjectCombo" name="itemProject" class="resetToFirstOption">
                <option value="select" selected="selected">Select project</option>
                <?php $projects = Project::getProjects(true);?>
                <?php foreach ( $projects as $project): ?>
<?php 
    // if a project is active (we are on a project page), select it
    if (isset($worklist) && isset($worklist['project_id']) && $project['project_id'] == $worklist['project_id']) { 
        $selected = 'selected="selected"';
    } else {
        $selected = '';
    }
?>
                    <option value="<?php echo $project['project_id']; ?>" <?php echo $selected; ?>><?php echo $project['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
            <p class="info-label">Summary<br />
                <input type="text" name="summary" id="summary" class="text-field" size="48" />
            </p>
        <p class="info-label">Full Description<br />
          <textarea name="notes" size="48" /></textarea>
        </p>
        <div id="more-accordion">
            <h3><a href="#">More</a></h3>
            <div id="more-panel" >
                <p class="info-label">Skills needed<br/>
                    <input type="text" id="skills" name="skills" class="text-field" size="35" value="" />
                </p>
                <p class="info-label">Invite<br />
                    <input type="text" name="invite" class="invite" />
                    <small>Invite someone to do this job. (Comma-separated list of developers.)</small>
                </p>
        <?php
            if ($is_runner) { // if user is a runner - allow to change status
        ?>
                <p class="info-label">Status<br />
                    <select id="itemStatusCombo" name="status">
                        <option value="Draft">Draft</option>
                        <option value="Suggested">Suggested</option>
                        <option value="Bidding" selected="selected">Bidding</option>
                        <option value="Working">Working</option>
                        <option value="Done">Done</option>
                    </select>
                </p>
        <?php } else { ?>
                <input type="hidden" id="status" name="status" value="Suggested" />
        <?php } ?>
                <p class="info-label">Attached Files <br />
                    <div id="addaccordion">
                        <h3><a href="#">Images (<span class="imageCount">0</span>)</a></h3>
                        <div class="fileimagecontainer">

                        </div>
                        <h3><a href="#">Documents (<span class="documentCount">0</span>)</a></h3>
                        <div class="filedocumentcontainer">
                        </div>
                    </div>
                    
                    <div class="fileUploadButton">
                        Attach new files
                    </div>

                    <input type="hidden" name="files" value="" />
                    
                    <div class="uploadnotice"></div>
                    <script type="text/javascript" src="js/uploadFiles.js"></script>
                </p>
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

                </div>
                <div>
                <div class="clear"></div>
                <div class="floatLeft">
                    <p class="info-label isBug">
                    <label id="bugLabel"><input type="checkbox" name="is_bug" id="is_bug" /> Bug</label>
                    (If known, linked to Job #) <input type="text" id="bug_job_id" name="bug_job_id" class="bug_job_id iToolTip enterAmount" size="48" value="" />                    
                    </p>
                </div>
                <div class="bugJobSummary" id="bugJobSummary" title="0"></div>

                <div class="clear"></div>
                </div><!-- end of fees_single_block -->
           </div>
        </div>
    </div><!-- end div #for-edit-->
        
        <div id="bid_action_button" class="bid_action_button">
                <input type="submit" id="save_item" name="save_item" value="Add Job">
        </div>
                <?php } else { ?>
        <div id = "bid-signup">
        <h3>Want to bid?</h3>
        <a href="signup.php">Sign up now!</a>
        </div>
      </div>
    </div>
  </div>
                <?php } ?>
            </form>
        </div>
<script type="text/javascript" src="js/addJob.js"></script>
<script type="text/javascript">
    applyPopupBehavior();
</script>

