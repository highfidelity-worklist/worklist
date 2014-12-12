var Settings = {
    nclass: '',
    about: null,
    paypal: null,
    w9_accepted: null,
    uploadedFile: null,
    filesUploading: false,

    init: function() {
        $('#timezone, #country').chosen();

        $('.system-add').click(Settings.addSystemForm);
        $('#systems-forms').on('click', '.system-remove', Settings.removeSystemForm);

        $.ajax({
            type: "GET",
            url: './user/isISCitizen/' + user_id,
            dataType: 'json',
            success: function(data) {
                if ((data.success === true) && (data.isuscitizen === true)) {
                    $('#w9upload').show();
                }
            }
        });

        $("#settings").submit(function(event) {
            event.preventDefault();
            Settings.save();
            return false;
        });

        Settings.about = new LiveValidation('about');
        Settings.about.add(Validate.Length, { minimum: 0, maximum: 150 } );

        Settings.paypal = new LiveValidation('paypal_email');
        Settings.paypal.add(Validate.Email);
        // TODO: Review requirements here. We let people signup without paypal, and we let them delete their paypal
        // email, which removes their paypal verification and prevents them from bidding
        // Settings.paypal.add(Validate.Presence, { failureMessage: "Can't be empty!" });

        Settings.w9_accepted = new LiveValidation('w9_accepted', {insertAfterWhatNode: 'w9_accepted_label'});
        Settings.w9_accepted.displayMessageWhenEmpty = true;
        Settings.w9_accepted.add(Validate.Custom, { against: Settings.validateW9Agree, failureMessage: "Please let us know that you'll do your part in keeping your information up to date by checking the final checkbox." });

        Settings.initFileUpload();
    },

    initFileUpload: function() {
        var options = {iframe: {url: './file/add/0/1'}};
        var zone = new FileDrop('attachments', options);

        zone.event('send', function (files) {
            files.each(function (file) {
                if (!/^.*\.(pdf)$/i.test(file.name)) {
                    alert(file.name + ' is not allowed. Please upload a pdf file.');
                    return;
                }
                file.event('done', Settings.fileUploadDone);
                file.event('error', Settings.fileUploadError);
                file.sendTo('./file/add/0/1');
                Settings.filesUploading = false;
                Settings.animateUploadSpin();
            });
        });
        zone.multiple(false);

        $('#attachments > label > em').click(function() {
            $('#attachments input.fd-file').click();
        })
    },

    fileUploadDone: function(xhr) {
        var fileData = $.parseJSON(xhr.responseText);
        $.ajax({
            url: './file/scan/' + fileData.fileid,
            type: 'POST',
            dataType: "json",
            success: function(json) {
                if (json.success == true) {
                    fileData.url = json.url;
                }
                Utils.parseMustache('partials/upload-document', fileData, function(parsed) {
                    $('#attachments > ul').html(parsed);
                    $('#attachments li[attachment=' + fileData.fileid + '] > i').click(Settings.removeFile);
                    Settings.uploadedFile = fileData.fileid;
                });
                Settings.fileUploadFinished();
            }
        });
    },

    fileUploadError: function(e, xhr) {
        Settings.fileUploadFinished();
    },

    fileUploadFinished: function() {
        Settings.filesUploading = false;
        Settings.stopUploadSpin();
    },

    removeFile: function(event) {
        var id = parseInt($(this).parent().attr('attachment'))
        $('#attachments li[attachment=' + id + ']').remove();
        Project.uploadedFile = null;
    },

    animateUploadSpin: function() {
        if ($('#attachments > .loading').length) {
            return;
        }
        $('<div>').addClass('loading').prependTo('#attachments');
        var target = $('#attachments > .loading')[0];
        var spinner = new Spinner({
            lines: 9,
            length: 3,
            width: 4,
            radius: 6,
            corners: 1,
            rotate: 12,
            direction: 1,
            color: '#000',
            speed: 1.1,
            trail: 68
          }).spin(target);
    },

    stopUploadSpin: function() {
        $('#attachments > .loading').remove();
    },

    validateW9Agree: function(value) {
        if (! $('#w9_accepted').is(':checked') && $('#country').val() == 'US') {
            return false;
        }
        return true;
    },

    isJSON: function(json) {
        json = json.replace(/\\["\\\/bfnrtu]/g, '@');
        json = json.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
        json = json.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
        return (/^[\],:{}\s]*$/.test(json))
    },

    save: function() {
        var values;
        var massValidation = LiveValidation.massValidate( [  Settings.paypal, Settings.w9_accepted ], true);
        var arrayValueForTagWithName = function (tag, name) {
          return $(tag + '[name="' + name + '[]"]').map(function(){ return $(this).val(); }).get();
        }
        if (massValidation) {
            values = {
                save: 1,
                timezone: $('#timezone').val(),
                country: $('#country').val(),
                bidding_notif: $('#bidding_notif').prop('checked') ? 1 : 0,
                review_notif: $('#review_notif').prop('checked') ? 1 : 0,
                self_notif: $('#self_notif').prop('checked') ? 1 : 0,
                about: $("#about").val(),
                paypal_email: $("#paypal_email").val(),
                payway: $("#payway").val(),
                system_operating_systems: arrayValueForTagWithName('input', 'system_operating_systems'),
                system_hardware: arrayValueForTagWithName('textarea', 'system_hardware'),
                system_id: arrayValueForTagWithName('input', 'system_id'),
                system_delete: arrayValueForTagWithName('input', 'system_delete'),
                w9_accepted: $('#w9_accepted').is(':checked')
            };
        } else {
            // Validation failed. We use openNotifyOverlay to display messages
            var errorHtml = createMultipleNotifyHtmlMessages(LiveValidation.massValidateErrors);
            openNotifyOverlay(errorHtml, null, null, true);
            return false;
        }

        $('.error').text('');

        $.ajax({
            type: "POST",
            url: './settings',
            data: values,
            success: function(json) {
                var message = 'Account settings saved!';
                var settings_json = Settings.isJSON(json) ? jQuery.parseJSON(json) : null;
                if (settings_json && settings_json.error) {
                    console.log(settings_json);
                    if (settings_json.error == 1) {
                        message = "There was an error updating your information.<br/>Please try again or contact an admin for assistance.<br/>Reason for failure: " + settings_json.message;
                    } else {
                        message = json.message;
                    }
                }
                if(settings_json.error == 1) {
                    openNotifyOverlay(message, false, false, true); // Display with a red border if its an error
                } else {
                    openNotifyOverlay(message);
                }
                Settings.refreshSystemFormsWithData(settings_json['user_systems']);
            },
            error: function(xhdr, status, err) {
                $('#msg-'+type).text('We were unable to save your settings. Please try again.');
            }
        });
    },

    updateSystemTitles: function() {
        $forms_wrap = $('#systems-forms');
        $('.system-wrapper:visible', $forms_wrap).each(function(index, system_wrapper) {
            $('.system-title', system_wrapper).text('System ' + (index + 1));
        });
    },

    addSystemForm: function() {
        $forms_wrap = $('#systems-forms');
        $placeholder_form = $('.system-placeholder-wrapper', $forms_wrap);

        $new_form = $placeholder_form.clone();
        $new_form.attr('class', 'system-wrapper');

        $placeholder_form.before($new_form);

        Settings.updateSystemTitles();
    },

    removeSystemForm: function() {
        $forms_wrap = $(this).parent('.system-wrapper');
        $('[name="system_delete[]"]', $forms_wrap).val(1);
        $forms_wrap.hide();

        Settings.updateSystemTitles();
    },

    refreshSystemFormsWithData: function(system_forms_data) {
        $forms_wrap = $('#systems-forms');
        $placeholder_form = $('.system-placeholder-wrapper', $forms_wrap);

        $('.system-wrapper', $forms_wrap).remove();

        var setValueForFieldName = function (value, name, scope) {
          return $('[name="' + name + '[]"]', scope).val(value);
        }

        $(system_forms_data).each(function(_i, system_data) {
            $new_form = $placeholder_form.clone();
            $new_form.attr('class', 'system-wrapper');

            $title_tag = $('.system-title', $new_form);
            $title_tag.html($title_tag.html() + system_data.index);

            setValueForFieldName(system_data.operating_systems, 'system_operating_systems', $new_form);
            setValueForFieldName(system_data.hardware, 'system_hardware', $new_form);
            setValueForFieldName(system_data.id, 'system_id', $new_form);

            $placeholder_form.before($new_form);
        });
    }
}
