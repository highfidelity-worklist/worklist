$(function() {
    var activeProjectsFlag = true;
    
    $('select[name=itemProject]').bind({
        'listOpen': function(e, o) {
            $('#projectPopupActiveBox').width($('.itemProjectComboList').outerWidth());
            $('#projectPopupActiveBox').css({
                top: $('.itemProjectComboList').height() + 62,
                left: $('.itemProjectComboList').css('left')
            });
            $('#projectPopupActiveBox').show();
        },
        'listClose': function(e, o) {
            $('#projectPopupActiveBox').hide();
        }
    });

    //Enable/disable job bug id on is_bug checkbox state
    $("#bug_job_id").ready(function() {
        if ( !$("#is_bug").is(":checked")) {
            $("#bug_job_id").prop("disabled", true);
        } else {
            $("#bug_job_id").removeAttr("disabled");
        }
        //bind paste event to lookup for bug job summary
        jQuery(document).bind('paste', function(e){
            $("#bug_job_id").keyup();
        });
    });

    //Checkbox is_bug click event
    $("#is_bug").click(function(){
        if ( !$(this).is ( ":checked" ) ) {
            //Disable and clean bug_job_id
            $("#bug_job_id").prop('disabled' , true);
            $("#bug_job_id").val('');
            $('#bug > p').html('');
            $("#bug > p").attr("title" , 0);
            if (/^\[BUG\]/i.test($('#summary').val())) {
                $('#summary').val($('#summary').val().substring(5).trim());
            }
        } else {
            //Enable bug_job_id textbox
            $("#bug_job_id").prop('disabled' , false);
            if (!/^(\[?)BUG/i.test($('#summary').val())) {
                $('#summary').val('[BUG] ' + $('#summary').val().trim());
            }
        }
    });

    $("#bug_job_id").blur(function() {
        $("#bug_job_id").keyup();
    });

    //lookup and show job summary on bug_job_id change
    $("#bug_job_id").keyup(function() {
        var id=$("#bug_job_id").val();
        if(id.length) {
            $.ajax({
                url: 'api.php',
                dataType: 'json',
                data: {
                    action: 'getJobInformation',
                    itemid: id
                },
                type: 'POST',
                success: function(json) {
                    if (!json || json === null) {
                        alert("json null in getjobinformation");
                        return;
                    }
                    if (json.error) {
                        alert(json.error);
                    } else {
                        if(json.returnString.length > 0) {
                            $('#bug > p').html(json.returnString);
                            $("#bug > p").attr("title" , id);
                        } else {
                            $('#bug > p').html("Item doesn't exist");
                            $("#bug > p").attr("title" , 0);
                        }
                    }
                }
            });
        }
    });
    
    $('#itemProjectCombo').chosen();
    $('#itemStatusCombo').chosen();
    
    var imageArray = new Array();
    var documentsArray = new Array();
    $('#addaccordion').fileUpload({images: imageArray, documents: documentsArray});

    var autoArgs = autocompleteMultiple('getuserslist', null);
    $("#ivite").bind("keydown", autoArgs.bind);
    $("#ivite").autocomplete(autoArgs);  
    
    var autoArgsSkills = autocompleteMultiple('getskills', skillsSet),
        hasAutocompleter = false;
    $("#skills").bind("keydown", autoArgsSkills.bind);
    $("#skills").autocomplete(autoArgsSkills);  
    hasAutocompleter = true;

    $('#save > input').click(function(event){
        var massValidation;
        if ($('#save > input').data("submitIsRunning") === true) {
            event.preventDefault();
            return false;
        }
        $('#save > input').data("submitIsRunning", true);
        loaderImg.show("saveRunning","Saving, please wait ...", function() {
            $('#save > input').data("submitIsRunning", false);
        });

        var bugJobId;
        if($('#addJob input[name="is_bug"]').is(':checked')) {
            bugJobId = new LiveValidation('bug_job_id');
            bugJobId.add( Validate.Custom, {
                against: function(value, args) {
                    id = $('#bug > p').attr('title');
                    if (id == 0) {
                        $('#bug > p').html('');
                        return false; 
                    } else {
                        return true;
                    }
                },
                failureMessage: "Invalid item Id"
            });
        }
        var summary = new LiveValidation('summary');
        summary.add(Validate.Presence, {failureMessage: "You Must Enter The Job Title!"});

        var itemProject = new LiveValidation('itemProjectCombo');
        itemProject.add( Validate.Exclusion, {
            within: [ 'select' ], partialMatch: true,
            failureMessage: "You have to choose a project!"
        });
        
        if($('#addJob input[name="is_bug"]').is(':checked')) { 
            massValidation = LiveValidation.massValidate([itemProject, summary, bugJobId],true);
        } else {
            massValidation = LiveValidation.massValidate([itemProject, summary],true);
        }
                    
        if (!massValidation) {
            event.preventDefault();
            // Validation failed. We use openNotifyOverlay to display messages
            var errorHtml = createMultipleNotifyHtmlMessages(LiveValidation.massValidateErrors);
            openNotifyOverlay(errorHtml, null, null, true);
            $('#save > input').data("submitIsRunning", false);
            return false;
        }
        
        addForm = $("#addJob");
        $.ajax({
            url: 'api.php',
            dataType: 'json',
            data: {
                action: 'addWorkitem',
                summary: $(":input[name='summary']", addForm).val(),
                files: $(":input[name='files']", addForm).val(),
                invite: $(":input[name='invite']", addForm).val(),
                notes: $(":input[name='notes']", addForm).val(),
                page: $(":input[name='page']", addForm).val(),
                project_id: $(":input[name='itemProject']", addForm).val(),
                status: $(":input[name='status']", addForm).val(),
                skills: $(":input[name='skills']", addForm).val(),
                is_bug: $(":input[name='is_bug']", addForm).prop('checked'),
                bug_job_id: $(":input[name='bug_job_id']", addForm).val(),
                fileUpload: $('#addaccordion').data('fileUpload')
            },
            type: 'POST',
            success: function(json) {
                if (!json || json === null) {
                    loaderImg.hide("saveRunning");
                    return;
                }
                loaderImg.hide("saveRunning");
                if (json.error) {
                    alert(json.error);
                } else {
                    location.href = './' + json.workitem;
                }
            }
        });
        return false;
    });
});
