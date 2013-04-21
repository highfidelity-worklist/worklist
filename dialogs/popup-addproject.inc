<div id="popup-addproject" class="popup-body" title = "Add Project">
    <?php if (isset($userId) && $userId): ?>
    <?php
        $user = new User();
        $user->findUserById($userId);
        if ($user->getIs_admin() || $user->isRunner() || $user->isPaypalVerified()) { ?>
            <form name="popup-form" id="popup-form-edit" action="javascript:;" method="post">
                <p class="info-label">
                    <img class="addproject_img"
                    id="projectLogo"
                    src=""><br>
                    <span style="display: none;"
                    class="LV_validation_message LV_invalid upload"></span>
                    <input type="hidden" value="" name="logoProject" />
                    <span class="help">
                        <strong>You want to add a project?</strong> Awesome. See our <a href="help.php">Help page</a> for more details,
                        then fill out this form to get yourself started. Later, you'll be able to choose version control options or
                        change any of the info you enter here.
                    </span>
                </p>
                <p class="info-label" id="name_container">
                    <span class="title">Project title</span><br />
                    <input type="text" name="name" id="name" class="text-field" size="48" />
                </p>
                <p class="info-label" id="description_container">
                    <span class="title">Description</span><br />
                    <textarea name="description" id="description" size="48" /></textarea>
                </p>
                <p class="info-label"><span class="title">Website</span><br />
                    <input type="text" name="website" id="website" class="text-field" size="48" />
                </p>
                <div id="edit_cr_error_ap"></div>
                <div id="projectLogoAdd" class="buttonContainer uploadbutton fileUploadButton  ">
                    <span class="smbutton">Upload logo</span>
                </div>
                <span class="upload">Upload PNG, JPG or GIF with <br />square dimensions, at least 80x80 px</span>
                <p class="info-label" id="github_container">
                    <input type="checkbox" id="checkGitHub" name="checkGitHub" title="Link to my GitHub" />If you already have a repository for your code at GitHub, link it to your project.<br/>
                    <span class="title">GitHub repo URL</span><br />
                    <input type="text" name="githubRepoURL" id="githubRepoURL" placeholder="ex. http://github.com/organization/project.git" disabled="disabled" />
                </p>
                <div class="addproject">
                    <input type="submit" id="save_project" name="save_item" value="Add project">
                    <span class="error-submit"><strong>Oops, there were a few errors.</strong> Please check the notes above and make corrections then resubmit.</span>
                </div>
            </form>
         <?php } else {?>
            Click <a href="settings.php">here</a> to verify your paypal address before submitting your new project.
    <?php }?>
    <?php else: ?>
    <img style="display: none;" id="projectLogoAdd" />
    To add a project, start by creating a user account on worklist
    <input type="button" id="signup" name="signup" value="Join Worklist" />
    <?php endif; ?>
</div>