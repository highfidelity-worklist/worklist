function ToolTip() {
    xOffset = 10;
    yOffset = 20;
    var el_parent, el_child;
    $(".toolparent").hover(function(e) {
        if (el_child) el_child.appendTo(el_parent).hide();
        el_parent = $(this);
        el_child = el_parent.children(".tooltip")
            .appendTo("body")
            .css("top",(e.pageY - xOffset) + "px")
            .css("left",(e.pageX + yOffset) + "px")
            .fadeIn("fast");
    },
    function() {
        if (el_child) el_child.appendTo(el_parent);
        $(".tooltip").hide();
        el_child = null;
    });
    $(".toolparent").mousemove(function(e) {
        if (el_child) {
            el_child
                .css("top",(e.pageY - xOffset) + "px")
                .css("left",(e.pageX + yOffset) + "px");
        }
    });
}

function validateCodeReviews(control) {
    if (!$('#cr_anyone_field').is(':checked') && !$('#cr_3_favorites_field').is(':checked') && 
        !$('#cr_project_admin_field').is(':checked') && !$('#cr_job_runner_field').is(':checked') &&
        !$('#cr_users_specified_field').is(':checked')) {
        $('#cr_anyone_field').prop('checked', true);
        openNotifyOverlay('One selection must be checked', true);
    };
    if($(control).attr('id') == "cr_users_specified_field") {
        if($('#cr_users_specified_field').is(':checked')) {
            $('.code_review_chks').prop('checked', false);
        } 
    } else if ($(control).is(':checked')) {
        $('#cr_users_specified_field').prop('checked', false);
    }

};

$(document).ready(function() {
    $('.accordion').accordion({
        clearStyle: true,
        collapsible: true,
        active: true
    });

    $('#short_description').bind('keyup', 'keydown', function() {
        if ($(this).val().length > 100) {
            $(this).val($(this).val().substring(0, 100));
        } else {
            $('#charCount').text(100 - $(this).val().length);
        }
    }).trigger('keyup');

    // Validate review input
    // @TODO: The :checkbox selector is too broad, we might
    // have additional checkboxes in the future..   - lithium
    $('.code_review_chks, #cr_users_specified_field').change(function(){
        validateCodeReviews(this);
    });

    // Get the project runners
    getProjectRunners = function() {
        $('#projectRunners tbody').html('Loading ...');
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                action: 'getRunnersForProject'
            },
            dataType: 'json',
            success: function(data) {
                $('#projectRunners tbody').html('');
                $('#remove-runner tbody').html('');
                if (data.success) {
                    runners = data.data.runners;
                    var html = '';
                    if (runners.length > 0) {
                        for(var i=0; i < runners.length; i++) {
                            var runner = runners[i];
                            html =
                                '<tr class="runner">' +
                                    ((is_admin || is_owner) ? '<td class="runnerRemove">' + (runner.owner ? '' : '<input type="checkbox" name="runner' + runner.id + '" />') + '</td>' : '') +
                                    '<td class="runnerName"><a href="./user/' + runner.id + '" >' + runner.nickname + '</a></td>' +
                                    '<td class="runnerJobCount">' + runner.totalJobCount + '</td>' +
                                    '<td class="runnerLastActivity">' + (runner.lastActivity ? runner.lastActivity : '') + '</td>' +
                                '</tr>'
                            $('#projectRunners tbody').append(html);
                        }
                    }
                }
            }
        });
    }
    getProjectRunners();
    

    $('#removerunner').click(function(event) {
        Utils.infoDialog('Removing Designer','Removing selected user(s) as Designer(s) for this project. ' +
            'If this user has active jobs for which they are the Designer, you will need to ' +
            'change the Designer status to an eligible Designer.' );
        
        var runners = '';
        $('#projectRunners input[name^=runner]:checked').each(function() {
            var runner = parseInt($(this).attr('name').substring(6));
            if (runners.length) runners += ';';{
            runners += '' + runner;
            }
        });
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                runners: runners,
                action: 'removeRunnersFromProject'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    getProjectRunners();
                }
            }
        });
        return false;
    });

    // Get the project reviewers
    getProjectCodeReviewers = function() {
        $('#projectCodeReviewRights tbody').html('Loading ...');
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                action: 'getCodeReviewersProject'
            },
            dataType: 'json',
            success: function(data) {
                $('#projectCodeReviewers tbody').html('');
                if (data.success) {
                    codeReviewers = data.data.codeReviewers;
                    var html = '';
                    if (codeReviewers.length > 0) {
                        for(var i=0; i < codeReviewers.length; i++) {
                            var codeReviewer = codeReviewers[i];
                            html =
                                '<tr class="codeReviewer">' +
                                    ((is_admin || is_owner) ? '<td class="codeReviewerRemove">' + (codeReviewer.owner ? '' : '<input type="checkbox" name="codereviewer' + codeReviewer.id + '" />') + '</td>' : '') +
                                    '<td class="codeReviewerName"><a href="./user/' + codeReviewer.id + '" >' + codeReviewer.nickname + '</a></td>' +
                                    '<td class="codeReviewerJobCount">' + codeReviewer.totalJobCount + '</td>' +
                                    '<td class="codeReviewerLastActivity">' + (codeReviewer.lastActivity ? codeReviewer.lastActivity : '') + '</td>' +
                                '</tr>'
                            $('#projectCodeReviewers tbody').append(html);
                        }
                    }
                }
            }
        });
    }
    getProjectCodeReviewers();
    
    $('#removecodereviewer').click(function(event) {
        openNotifyOverlay(
            '<span>Removing selected user(s) as code reviewer(s) for this project. ', true);
        var codeReviewers = '';
        $('#projectCodeReviewRights input[name^=codereviewer]:checked').each(function() {
            var codeReviewer = parseInt($(this).attr('name').substring(12));
            if (codeReviewers.length) codeReviewers += ';';{
            codeReviewers += '' + codeReviewer;
            }
        });
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                codeReviewers: codeReviewers,
                action: 'removeCodeReviewersFromProject'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    getProjectCodeReviewers();
                }
            }
        });
        return false;
    });
    
    $("#testFlightButton").click(function() {
        showTestFlightForm(projectid);
    });

    makeWorkitemTooltip(".payment-joblink, .joblink");
    
    if ($("#projectLogoEdit").length > 0) {
        new AjaxUpload('projectLogoEdit', {
            action: 'jsonserver.php',
            name: 'logoFile',
            data: {
                action: 'logoUpload',
                projectid: projectid,
            },
            autoSubmit: true,
            responseType: 'json',
            onSubmit: validateUploadImage,
            onComplete: function(file, data) {
                $('span.LV_validation_message.upload').css('display', 'none').empty();
                if (!data.success) {
                    $('span.LV_validation_message.upload').css('display', 'inline').append(data.message);
                } else if (data.success == true ) {
                    $("#projectLogoEdit img").attr("src",data.url);
                    $('input[name=logoProject]').val(data.fileName);
                }
            }
        });
    }
    
    //derived from bids to show edit dialog when project owner clicks on a role <mikewasmike 16-jun-2011>
    $('tr.role').click(function() {
        $.metadata.setType("elem", "script");
        var roleData = $(this).metadata();

        // row has role data attached
        if(roleData.id){
            $('#popup-role-info input[name="role_id"]').val(roleData.id);
            $('#popup-role-info #info-title').text(roleData.role_title);
            $('#popup-role-info #info-percentage').text(roleData.percentage);
            $('#popup-role-info #info-min-amount').text(roleData.min_amount);
            //future functions to display more information as well as enable disable removal edition
            $('#popup-role-info').dialog('open');
        }
    });

    $('#editRole').click(function(){
        // row has role data attached
        $('#popup-role-info').dialog('close');
            $('#popup-edit-role input[name="role_id"]').val($('#popup-role-info input[name="role_id"]').val());
            $('#popup-edit-role #role_title_edit').val($('#popup-role-info #info-title').text());
            $('#popup-edit-role #percentage_edit').val($('#popup-role-info #info-percentage').text());
            $('#popup-edit-role #min_amount_edit').val($('#popup-role-info #info-min-amount').text());
            $('#popup-edit-role').dialog('open');
    });

    $('#addrunner').click(function() {
        Utils.emptyFormModal({
            content:
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="newdesigner">New Designer</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    <input type="text" class="form-control" id="newdesigner" name="newdesigner">' +
                '  </div>' +
                '</div>',
            buttons: [
                {
                    type: 'submit',
                    name: 'adddesigner',
                    content: 'Add Designer',
                    className: 'btn-primary',
                    dismiss: false
                }
            ],
            open: function(modal) {
                $('input[name="newdesigner"]', modal).autocomplete({source: autocompleteUserSource});
                $('form', modal).submit(function() {
                    var designer = $('input[name="newdesigner"]', modal).val();
                    $.ajax({
                        type: 'post',
                        url: './project/addDesigner/' + projectName + '/' + designer,
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                getProjectRunners();
                            }
                        }
                    });
                    $(modal).modal('hide');
                    return false;
                });
            }
        });
        return false;
    });
    
    $('#addcodereviewer').click(function() {
        Utils.emptyFormModal({
            content:
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="codereviewer">New Code Reviewer</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    <input type="text" class="form-control" id="codereviewer" name="codereviewer">' +
                '  </div>' +
                '</div>',
            buttons: [
                {
                    type: 'submit',
                    name: 'addcodereviewer',
                    content: 'Add Code Reviewer',
                    className: 'btn-primary',
                    dismiss: false
                }
            ],
            open: function(modal) {
                $('input[name="codereviewer"]', modal).autocomplete({source: autocompleteUserSource});
                $('form', modal).submit(function(e) {
                    var user = $('input[name="codereviewer"]', modal).val();
                    $.ajax({
                        type: 'post',
                        url: './project/addCodeReviewer/' + projectid + '/' + user,
                        dataType: 'json',
                        success: function(data) {
                            getProjectCodeReviewers();
                        }
                    });
                    $(modal).modal('hide')
                    return false;
                });
            }
        });
    });
    
    //popup for removing Project Runner
    $('#remove-runner').dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        show: 'fade',
        hide: 'fade',
        width: 350,
        height: 450,
    });
    
    $('#popup-role-info').dialog({ 
        autoOpen: false, 
        modal: true, 
        maxWidth: 600, 
        width: 350, 
        show: 'fade', 
        hide: 'fade'
    });
    $('#popup-edit-role').dialog({ 
        autoOpen: false, 
        dialogClass: 'white-theme', 
        modal: true, 
        maxWidth: 600, 
        width: 250, 
        show: 'fade', 
        hide: 'fade' 
    });
    $('#popup-testflight').dialog({ 
        autoOpen: false, 
        maxWidth: 600, 
        width: 410, 
        show: 'fade', 
        hide: 'fade'
    });

    if (edit_mode) {
        $('#cancel_project_edit').click(function() {
            location.href = './' + projectName + '?action=view';
            return false;
        });
    } else {
        $('#edit_project').click(function() {
            location.href = './' + projectName + '?action=edit';
            return false;
        });
    }
});

function showAddRoleForm() {
    //$('#popup-addrole').dialog('open');
    Utils.emptyFormModal({
        action: './' + projectName,
        content:
            '<div class="row">' +
            '  <div class="col-md-6">' +
            '    <label for="role_title">Role Title</label>' +
            '  </div>' +
            '  <div class="col-md-6">' +
            '    <input type="text" class="form-control" name="role_title" id="role_title">' +
            '  </div>' +
            '</div>' +
            '<div class="row">' +
            '  <div class="col-md-6">' +
            '    <label for="role_title">Percentage</label>' +
            '  </div>' +
            '  <div class="col-md-6">' +
            '    <div class="input-group">' +
            '      <input type="text" class="form-control" name="percentage" id="percentage">' +
            '      <span class="input-group-addon">%</span>' +
            '    </div>' +
            '  </div>' +
            '</div>' +
            '<div class="row">' +
            '  <div class="col-md-6">' +
            '    <label for="min_amount">Minimum Amount</label>' +
            '  </div>' +
            '  <div class="col-md-6">' +
            '    <div class="input-group">' +
            '      <input type="text" class="form-control" name="min_amount" id="min_amount">' +
            '      <span class="input-group-addon">USD</span>' +
            '    </div>' +
            '  </div>' +
            '</div>',
        buttons: [
            {
                type: 'submit',
                name: 'save_role',
                content: 'Save',
                className: 'btn-primary',
                dismiss: false
            }
        ],
        open: function(modal) {
            $('form', modal).submit(function(e) {
                // see http://regexlib.com/REDetails.aspx?regexp_id=318
                // but without dollar sign 22-NOV-2010 <krumch>
                var regex_amount = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
                var regex_percent = /^100$|^\d{0,2}(\.\d{1,2})?$/;
                var min_amount = new LiveValidation($('input[name="min_amount"]', modal)[0], { onlyOnSubmit: true });
                min_amount.add( Validate.Format, { pattern: regex_amount, failureMessage: "Invalid Input!" });
                var percentage = new LiveValidation($('input[name="min_amount"]', modal)[0],{ onlyOnSubmit: true });
                percentage.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                percentage.add( Validate.Format, { pattern: regex_percent, failureMessage: "Invalid Input!" });
                var role_title = new LiveValidation($('input[name="role_title"]', modal)[0], { onlyOnSubmit: true});
                    role_title.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                var massValidation = LiveValidation.massValidate([min_amount, percentage, role_title]);
                if (!massValidation) {
                    return false;
                }
            });
        }
    });

    return false;
}

function showTestFlightForm(project_id) {
    $('#popup-testflight').dialog('open');
    $('#popup-testflight .error').hide();
    $('#popup-testflight form').hide();
    $('#popup-testflight form #ipa-select input').remove();

    $.getJSON('api.php?action=testFlight&project_id=' + project_id, function(data) {
        $('#popup-testflight .loading').hide()
        if (data['error']) {
            $('#popup-testflight .error')
                .text(data['error'])
                .show();
        } else {
            $('#popup-testflight form #message').val(data['message']);
            $.each(data['ipaFiles'], function(index, value) {
                $('#popup-testflight form #ipa-select').append('<input type="radio" name="ipa" value="' + value + '" /> ' + value + '<br />');
            });
            $('#popup-testflight form #ipa-select input:first').prop('checked', true);
            $('#popup-testflight form').show();
            $('#popup-testflight .right-note').show();

            $('#popup-testflight form #submit_testflight').click(function() {
                var params = 'project_id=' + project_id + '&message=' + $('#popup-testflight form #message').val();
                params += "&ipa_file=" + $('#popup-testflight form #ipa-select input').val();
                params += "&notify="
                params += $('#popup-testflight form input[type=checkbox]').is(':checked');
                $.getJSON('api.php?action=testFlight&' + params, function(data) {
                    if (data == null) {
                        alert("There was an error with publishing to TestFlight. Please try again.");
                    } else if (data['error']) {
                        alert(data['error']);
                    }
                });
                $('#popup-testflight').dialog('close');
            });

        }
    });
    return false;
}

