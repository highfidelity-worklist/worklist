<div id="popup-edit" title="Add Job to Worklist" class="popup-body">
    <?php if (isset($_SESSION['userid'])) { ?>

        <form name="popup-form" id="popup-form-edit" action="javascript:;" method="post">
            <input type="hidden" name="itemid" value="0" />
            <input type="hidden" name="page" value="<?php echo $page ?>" class="popup-page-value" />
            <input type="hidden" name="files" value="" />
            <div id="for_edit">
                <?php
                require_once("helper/popup-guest-message.inc");
                $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
                if (!$is_runner && isset($_SESSION['userid'])) {
                    $budgetUser = new User();
                    $budgetUser->findUserById($_SESSION['userid']);
                    $has_budget = $budgetUser->getBudget() > 0 ? 1 : 0;
                } else {
                    $has_budget = 0;
                }
                ?>

                <div class="floatLeft">
                    <label for="itemProjectCombo" class="small-label">Job is for this project ...</label>
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
                </div>


                <div class="floatLeft" id="is-bug-section">
                    <label for="is_bug" class="small-label"><input type="checkbox" name="is_bug" id="is_bug" /> This is a bug</label>
                </div>
                <div class="floatRight">
                    <label for="bug_job_id" class="small-label">Bug of job # ... </label>
                    <input type="text" id="bug_job_id" name="bug_job_id" class="bug_job_id iToolTip enterAmount" size="48" value="" />
                </div>

                <div id="input-section" class="clear">

                    <label for="summary">Job title</label>
                    <input type="text" name="summary" id="summary" class="text-field" size="48" />

                    <label for="notes">Full description</label>
                    <textarea name="notes" id="notes" cols="58" rows="11"></textarea>

                    <label for="skills">Skills necessary <small> (separate by commas ... ex: JavaScript, PHP, CSS)</small></label>
                    <input type="text" id="skills" name="skills" class="text-field" size="35" value="" />

                    <label for="ivite" id="label-for-invite"><span></span>Invite Worklist users
                        <small>(separate by commas)</small>
                    </label>
                    <input id="ivite" type="text" name="invite" class="invite" />

                    <div class="upload-section">
                        <div id="addaccordion" class="clear"></div>
                        <div id="uploadButtonDiv" class="buttonContainer uploadbutton fileUploadButton">
                            <span class="smbutton">Attach files</span>
                        </div>
                        <div id="attachments"></div>
                        <input type="hidden" name="files" value="" />
                        <div class="uploadnotice"></div>
                        <script type="text/javascript" src="js/uploadFiles.js"></script>
                    </div>
                </div>

                <div class="button-section">
                    <div class="floatLeft">
                        <?php
                        if ($is_runner) { // if user is a runner - allow to change status ?>
                            <label for="itemStatusCombo" class="small-label">Set the status of this job to:</label>
                            <select id="itemStatusCombo" name="status">
                                <option value="Draft">Draft</option>
                                <option value="Suggested">Suggested</option>
                                <option value="Bidding" selected="selected">Bidding</option>
                                <option value="Working">Working</option>
                                <option value="Done">Done</option>
                            </select>
                        <?php } else { ?>
                            <input type="hidden" id="status" name="status" value="Suggested" />
                        <?php } ?>

                    </div>

                    <div class="floatRight">
                        <div id="bid_action_button" class="bid_action_button">
                            <input type="submit" id="save_item" name="save_item" value="Add job">
                        </div>
                    </div>
                </div>

            </div><!-- end div #for-edit-->
        </form>
        <script type="text/javascript" src="js/addJob.js"></script>
        <script type="text/javascript">
            applyPopupBehavior();
        </script>
    <?php } ?>
</div>