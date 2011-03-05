<div id="popup-addproject" class="popup-body" title = "Add Project">
    <form name="popup-form" id="popup-form-edit" action="javascript:;" method="post">
        <p class="info-label"><span class="required-field">*</span> Project Name:<br />
            <input type="text" name="name" id="name" class="text-field" size="48" />
        </p>
        <p class="info-label"><span class="required-field">*</span> Description:<br />
            <textarea name="description" id="description" size="48" /></textarea>
        </p>
        <p class="info-label">SVN Repsitory:<br />
            <input type="text" name="repository" id="repository" class="text-field" size="48" />
        </p>
        <div>
            <input type="submit" id="cancel" name="cancel" value="Cancel">
            <input type="submit" id="save_project" name="save_item" value="Save">
        </div>
    </form>
    <div class="right-note"><span class="required-field">*</span> Required fields</span></div>
</div>