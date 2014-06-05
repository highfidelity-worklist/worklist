var nclass;
var about, paypal, w9_accepted;


function validateW9Upload(file, extension) {
    nclass = '.uploadnotice-w9';
    return validateUpload(file, extension);
}

function validateUpload(file, extension) {
    if (! (extension && /^(pdf)$/i.test(extension))) {
        // extension is not allowed

        // Restore the styling of upload button
        $('#formupload').attr('value', 'Upload W9');
        $('#formupload').removeClass('w9_upload_disabled');
        $('.w9_loader').css('visibility', 'hidden');

        $(nclass).empty();
        var html = '<div class="ui-state-error ui-corner-all">' +
                        '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                        '<strong>Error:</strong> This filetype is not allowed. Please upload a pdf file.</p>' +
                    '</div>';
        $(nclass).append(html);
        // cancel upload
        return false;
    }else{
        // Inform the user that the file is being uploaded...
        $(nclass).empty();
        $('#formupload').attr('value', 'uploading...');
        $('#formupload').addClass('w9_upload_disabled');
        $('.w9_loader').css('visibility', 'visible');
    }
}

function completeUpload(file, data) {
    $(nclass).empty();
    if (data.success) {
        // Restore the styling of upload button
        $('#formupload').attr('value', 'Success!');
        $('#formupload').removeClass('w9_upload_disabled');
        $('.w9_loader').css('visibility', 'hidden');

        var html = '<div class="ui-state-highlight ui-corner-all">' +
                        '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>' +
                        '<strong>Info:</strong> ' + data.message + '</p>' +
                    '</div>';
        saveSettings();
    } else {
        // Restore the styling of upload button
        $('#formupload').attr('value', 'Fail');
        $('#formupload').removeClass('w9_upload_disabled');
        $('.w9_loader').css('visibility', 'hidden');

        var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-error ui-corner-all">' +
                        '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                        '<strong>Error:</strong> ' + data.message + '</p>' +
                    '</div>';
        this.enable();
    }
    $(nclass).append(html);
}

function validateW9Agree(value) {
    if (! $('#w9_accepted').is(':checked') && $('#country').val() == 'US') {
        return false;
    }
    return true;
}

function isJSON(json) {
    json = json.replace(/\\["\\\/bfnrtu]/g, '@');
    json = json.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
    json = json.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
    return (/^[\],:{}\s]*$/.test(json))
}
function saveSettings() {
    var values;
    var massValidation = LiveValidation.massValidate( [  paypal, w9_accepted ], true);
    var arrayValueForTagWithName = function (tag, name) {
      return $(tag + '[name="' + name + '[]"]').map(function(){ return $(this).val(); }).get();
    }
    if (massValidation) {
        values = {
            save: 1,
            timezone: $('#timezone').val(),
            country: $('#country').val(),
            bid_alerts: $('#bid_alerts').prop('checked') ? 1 : 0,
            self_email_notify: $('input[name="self_email_notify"]').prop('checked') ? 1 : 0,
            bidding_email_notify: $('input[name="bidding_email_notify"]').prop('checked') ? 1 : 0,
            review_email_notify: $('input[name="review_email_notify"]').prop('checked') ? 1 : 0,
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
            var settings_json = isJSON(json) ? jQuery.parseJSON(json) : null;
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
            
            refreshSystemFormsWithData(settings_json['user_systems']);
        },
        error: function(xhdr, status, err) {
            $('#msg-'+type).text('We were unable to save your settings. Please try again.');
        }
    });
}
function updateSystemTitles() {
    $forms_wrap = $('.systems-forms-wrap');
    $('.system-wrapper:visible', $forms_wrap).each(function(index, system_wrapper) {
        $('.system-title', system_wrapper).text('System ' + (index + 1));
    });
}
function addSystemForm() {
    $forms_wrap = $('.systems-forms-wrap');
    $placeholder_form = $('.system-placeholder-wrapper', $forms_wrap);

    $new_form = $placeholder_form.clone();
    $new_form.attr('class', 'system-wrapper');

    $placeholder_form.before($new_form);

    updateSystemTitles();
}
function removeSystemForm() {
    $forms_wrap = $(this).parent('.system-wrapper');
    $('[name="system_delete[]"]', $forms_wrap).val(1);
    $forms_wrap.hide();

    updateSystemTitles();
}
function refreshSystemFormsWithData(system_forms_data) {
    $forms_wrap = $('.systems-forms-wrap');
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

$(function () {
    $('#timezone, #country').chosen();

    if (ppConfirmed || emConfirmed) {
        $('<div id="popup-confirmed"><div class="content"></div></div>').appendTo('body');

        if (ppconfirmed) {
            var $title = 'Your Paypal address was confirmed';
            var $content = 'Thank you for confirming your Paypal address.<br/><br/>You can now bid on items in the Worklist!<br/><br/><input style="" class="closeButton" type="button" value="Close" />';
        } else {
            var $title = 'Your email change is confirmed.';
            var $content = 'Thank you for confirming your changed email address.<br/><br/><input style="" class="closeButton" type="button" value="Close" />';
        }

        $('#popup-confirmed').dialog({
            dialogClass: "white-theme",
            modal: true,
            title: $title,
            autoOpen: true,
            width: 300,
            position: ['top'],
            open: function() {
                $('#popup-confirmed .content').html($content);
                $('#popup-confirmed .closeButton').click(function() {
                    $('#popup-confirmed').dialog('close');
                });
            }
        });
    }

    $('.system-add').click(addSystemForm);
    $('.systems-forms-wrap').on('click', '.system-remove', removeSystemForm);

    new AjaxUpload('formupload', {
        action: 'jsonserver.php',
        name: 'Filedata',
        data: { action: 'w9Upload', userid: user_id },
        autoSubmit: true,
        responseType: 'json',
        onSubmit: validateW9Upload,
        onComplete: completeUpload
    });

    $.ajax({
        type: "POST",
        url: 'jsonserver.php',
        data: {
            action: 'isUSCitizen',
            userid: user_id
        },
        dataType: 'json',
        success: function(data) {
            if ((data.success === true) && (data.isuscitizen === true)) {
                $('#w9upload').show();
            }
        }
    });

    $("#settings").submit(function(event) {
        event.preventDefault();
        saveSettings();
        return false;
    });

    about = new LiveValidation('about');
    about.add(Validate.Length, { minimum: 0, maximum: 150 } );

    paypal = new LiveValidation('paypal_email');
    paypal.add(Validate.Email);
    // TODO: Review requirements here. We let people signup without paypal, and we let them delete their paypal
    // email, which removes their paypal verification and prevents them from bidding
    // paypal.add(Validate.Presence, { failureMessage: "Can't be empty!" });

    w9_accepted = new LiveValidation('w9_accepted', {insertAfterWhatNode: 'w9_accepted_label'});
    w9_accepted.displayMessageWhenEmpty = true;
    w9_accepted.add(Validate.Custom, { against: validateW9Agree, failureMessage: "Please let us know that you'll do your part in keeping your information up to date by checking the final checkbox." });

});
