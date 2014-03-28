var nclass;

function validateNames(file, extension) {
    if (LiveValidation.massValidate( [ firstname, lastname ] )) {
        openNotifyOverlay('Submitting your W9...', false);
        return validateW9Upload(file, extension);
    } else {
        return false;   
    }        
}
function validateW9Upload(file, extension) {
    nclass = '.uploadnotice-w9';
    return validateUpload(file, extension);
}
function validateUpload(file, extension) {
    if (! (extension && /^(pdf)$/i.test(extension))) {
        // extension is not allowed
        $(nclass).empty();
        var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-error ui-corner-all">' +
                        '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                        '<strong>Error:</strong> This filetype is not allowed. Please upload a pdf file.</p>' +
                    '</div>';
        $(nclass).append(html);
        // cancel upload
        return false;
    }
}

function completeUpload(file, data) {
    $(nclass).empty();
    if (data.success) {
        openNotifyOverlay('W9 Uploaded', true);
        var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-highlight ui-corner-all">' +
                        '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>' +
                        '<strong>Info:</strong> ' + data.message + '</p>' +
                    '</div>';
        saveNames();
    } else {
        openNotifyOverlay('Submitting your W9...', true);
        var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-error ui-corner-all">' +
                        '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                        '<strong>Error:</strong> ' + data.message + '</p>' +
                    '</div>';
        this.enable();
    }
    
    // wait for the upload status to fadeout
    setTimeout(function() {
        $(nclass).append(html);
    }, 2000);
}

function validateW9Agree(value) {
    if (! $('#w9_accepted').is(':checked') && userCountry == 'US') {
        return false;
    }
    return true;
}

function saveNames() {
    var values = {
                first_name: $("#first_name").val(),
                last_name: $("#last_name").val(),
                saveW9Names: 1,
                userid: userId
        };
    $.ajax({
        type: "POST",
        url: 'confirmation',
        data: values,
        success: function(json) {

        },
        error: function(xhdr, status, err) {

        }
    });
}

function openNotifyOverlay(html, autohide) {
    $("#sent-notify").html(html);
    $('#sent-notify').css('display', '');
    $('#sent-notify').css('left', (($('#w9-dialog').width() - $('#sent-notify').width()) / 2) + 'px');
    if (autohide === true) {
           setTimeout(function() {
              $('#sent-notify').fadeOut('slow', function() {
                  $('#sent-notify').css('display', 'none');
              });
         }, 1800);
    }
}

$(document).ready(function () {
    new AjaxUpload('formupload', {
        action: './jsonserver',
        name: 'Filedata',
        data: { action: 'w9Upload', userid: user },
        autoSubmit: true,
        responseType: 'json',
        onSubmit: validateNames,
        onComplete: completeUpload
    });

    $("#uploadw9").click(function() {
        $("#w9-dialog").dialog({
            resizable: false,
            width: 330,
            title: 'W9 form upload',
            autoOpen: true,
            position: ['top']
        });
    });
    
    $('#save_payment').click( function () {
        massValidation = LiveValidation.massValidate( [ paypal, w9_accepted ]);   
        if (!massValidation) {
          return false;
        }
        $.ajax({
            type: 'POST',
            url: 'confirmation',
            data: { 
                newPayPalEmail: $('#paypal_email').val(),
                userId: user
            },
            success: function(json) {
                $('#savePayPalEmailDialog').dialog('open');
            }
        });    
    });

    $('#enter_later').click( function () { 
        $('#saveLaterDialog').dialog('open');
    });
    
    $('#saveLaterDialog').dialog({
        modal: true,
        autoOpen: false,
        width: 350,
        height: 180,
        position: ['top'],
        resizable: false
    });

    $('#savePayPalEmailDialog').dialog({
        modal: true,
        autoOpen: false,
        width: 300,
        height: 130,
        position: ['top'],
        resizable: false
    });
    
    $('.okButton').click( function() {
        window.location = './login';
    });
});
