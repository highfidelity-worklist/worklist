// add project contact form setup
$(function() {
    // setup the contact dialog
    $('#add-projects').click(function() {
        $('#contact-dialog').dialog('open');
    });
    $('#contact-close').click(function() {
        $('#contact-dialog').dialog('close');
    });
    $('#contact-dialog').dialog({
        autoOpen: false,
        modal: true,
        maxWidth: 600,
        width: 450,
        title: 'Tell us about your project!',
        show: 'fade',
        hide: 'fade'
    });
    
    // live validation
    var contact_name = new LiveValidation('name', {onlyOnBlur: true});
    contact_name.add( Validate.Presence, { failureMessage: "Can't be empty!" });
    var contact_phone = new LiveValidation('phone', {onlyOnBlur: true});
    contact_phone.add( Validate.Presence, { failureMessage: "Can't be empty!" });
    var contact_email = new LiveValidation('email', {validMessage: "Valid email address.", onlyOnBlur: true});
    contact_email.add(SLEmail2);
    contact_email.add( Validate.Presence, { failureMessage: "Can't be empty!" });
    var contact_project = new LiveValidation('project', {onlyOnBlur: true});
    contact_project.add( Validate.Presence, { failureMessage: "Can't be empty!" });
    var contact_desc = new LiveValidation('proj-desc', {onlyOnBlur: true});
    contact_desc.add( Validate.Presence, { failureMessage: "Can't be empty!" });
    
    // setup form submit
    $('#contact-form').submit(function(event) {
        event.preventDefault();
        massValidation = LiveValidation.massValidate([contact_name], [contact_phone], [contact_email], [contact_project], [contact_desc]);
        if (!massValidation) {
            return false;
        }
        $.ajax({
            type: 'GET',
            url: 'api.php',
            dataType: 'json',
            data: {
                action: 'sendContactEmail',
                name: $('#name').val(),
                email: $('#email').val(),
                phone: $('#phone').val(),
                project: $('#project').val(),
                proj_desc: $('#proj-desc').val()
            },
            success: function(json) {
                if (json === null) {
                    var msg = $('<div/>').html('<p>There was an error! Please try again</p>').dialog({
                        autoOpen: false,
                        title: "Error!",
                        modal: true,
                        resizable: false,
                        buttons: {
                            Ok: function() {
                                $( this ).dialog( "close" );
                            }
                        }
                    });
                    msg.dialog('open');
                    return false;
                } else if (json.success) {
                    var msg = $('<div/>').html('<p>Your message was sent! Someone will be contacting you soon.</p>').dialog({
                        autoOpen:false,
                        title: "Message Sent",
                        modal: true,
                        resizable: false,
                        buttons: {
                            Ok: function() {
                                $( this ).dialog( "close" );
                            }
                        }
                    });
                    msg.dialog('open');
                } else if (json.error) {
                    var msg = $('<div/>').html('<p>' + json.error + '</p>').dialog({
                        autoOpen: false,
                        title: "Error!",
                        modal: true,
                        resizable: false,
                        buttons: {
                            Ok: function() {
                                $( this ).dialog( "close" );
                            }
                        }
                    });
                    msg.dialog('open');
                    return false;
                }
                $('#name').val('');
                $('#email').val('');
                $('#phone').val('');
                $('#project').val('');
                $('#proj-desc').val('');
                $('#contact-dialog').dialog('close');
            },
            error: function(json){
                var msg = $('<div/>').html('<p>' + json.responseText + '</p>').dialog({
                    autoOpen: false,
                    title: "Error!",
                    modal: true,
                    resizable: false,
                    buttons: {
                        Ok: function() {
                            $( this ).dialog( "close" );
                        }
                    }
                });
                msg.dialog('open');
                return false;
            }
        });
        return false;
    });
})
