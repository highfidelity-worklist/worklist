<div id="popup-addrole" class="popup-body" title = "Add Role">
    <form name="popup-form" id="popup-form-edit" action="" method="post">
        <p class="info-label"><span class="required-field">*</span> Role Title:<br />
            <input type="text" name="role_title" id="role_title" class="text-field" size="64" />
        </p>
        <p class="info-label"><span class="required-field">*</span> Percentage<br />
            <input type="text" name="percentage" id="percentage" class="text-field" size="48" />
        </p>
        <p class="info-label">Minimum Amount<br />
            <input type="text" name="min_amount" id="min_amount" class="text-field" size="48" />
        </p>
        <script type="text/javascript">
            // see http://regexlib.com/REDetails.aspx?regexp_id=318
            // but without dollar sign 22-NOV-2010 <krumch>
             var regex_amount = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
             var regex_percent = /^100$|^\d{0,2}(\.\d{1,2})?$/;
            var min_amount = new LiveValidation('min_amount',{ insertAfterWhatNode: 'min_amount', onlyOnSubmit: true });
                min_amount.add( Validate.Format, { pattern: regex_amount, failureMessage: "Invalid Input!" });

            var percentage = new LiveValidation('percentage',{ insertAfterWhatNode: 'percentage', onlyOnSubmit: true });
                percentage.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                percentage.add( Validate.Format, { pattern: regex_percent, failureMessage: "Invalid Input!" });
                
            var role_title = new LiveValidation('role_title', { insertAfterWhatNode: 'role_title', onlyOnSubmit: true});
                role_title.add( Validate.Presence, { failureMessage: "Can't be empty!" });
        </script>
        <div>
            <input type="submit" id="save_role" name="save_role" value="Save">
        </div>
    </form>
    <div class="right-note"><span class="required-field">*</span> Required fields</span></div>
</div>