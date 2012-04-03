<div id="popup-addproject" class="popup-body" title = "Add Project">
    <?php if (isset($userId) && $userId): ?>
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
        <div id="edit_cr_error_ap"></div>
        
        <div>
            <input type="submit" id="cancel" name="cancel" value="Cancel">
            <input type="submit" class="disableable" id="save_project" name="save_item" value="Create Project">
        </div>
    </form>
    <div class="right-note"><span class="required-field">*</span> Required fields</span></div>
    <?php else: ?>
    To add a project, start by creating a user account on worklist
    <input type="button" id="signup" name="signup" value="Join Worklist" />
    <?php endif; ?>
</div>