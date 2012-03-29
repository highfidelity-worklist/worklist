<div id="popup-addproject" class="popup-body" title = "Add Project">
    <form name="popup-form" id="popup-form-edit" action="javascript:;" method="post">
        <p class="info-label"><span class="required-field">*</span> Project Name:<br />
            <input type="text" name="name" id="name" class="text-field" size="48" />
        </p>
        <p class="info-label"><span class="required-field">*</span> Description:<br />
            <textarea name="description" id="description" size="48" /></textarea>
        </p>
        <p class="info-label">
            <img style="width:48px;height:48px;cursor: pointer; margin-top:5px; border: 2px solid rgb(209, 207, 207);"
            id="projectLogoAdd"
            src="images/emptyLogo.png"><br>
            <span style="display: none;"
            class="LV_validation_message LV_invalid upload"></span>
            <input type="hidden" value="" name="logoProject">
        </p>        
        <p class="info-label">Website:<br />
            <input type="text" name="website" id="website" class="text-field" size="48" />
        </p>
        <p class="info-label">SVN Repository:<br />
            <input type="text" name="repository" id="repository" class="text-field" size="48" />
        </p>
        <div class="accordion">
            <h3><a href="#">Allow code reviews from:</a></h3>
            <div id="codeReviewRightsContainerAP">
                <input class="cr_anyone_field_ap" type="checkbox" name="cr_anyone" value="1" <?php echo !empty($_REQUEST['cr_anyone'])? 1:0; ?> />Anyone<br/>
                <input class="cr_3_favorites_field_ap" type="checkbox" name="cr_3_favorites" value="1" <?php echo !empty($_REQUEST['cr_3_favorites'])? 1:0; ?> />Anyone who is trusted by more than [3] people<br/>
                <input class="cr_project_admin_field_ap" type="checkbox" name="cr_project_admin" value="1" <?php echo !empty($_REQUEST['cr_project_admin'])? 1:0; ?> />Anyone who is trusted by the project admin<br/>
                <input class="cr_job_runner_field_ap" type="checkbox" name="cr_job_runner" value="1" <?php echo !empty($_REQUEST['cr_job_runner'])? 1:0; ?> />Anyone who is trusted by the job manager<br/>
            </div>
        </div>
        <div id="edit_cr_error_ap"></div>
        
        <div>
            <input type="submit" id="cancel" name="cancel" value="Cancel">
            <input type="submit" class="disableable" id="save_project" name="save_item" value="Save">
        </div>
    </form>
    <div class="right-note"><span class="required-field">*</span> Required fields</span></div>
</div>
