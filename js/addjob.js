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
                url: 'getjobinformation.php',
                dataType: 'json',
                data: {
                    itemid:id
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
    
    // to add a custom stuff we bind on events
    $('#itemProjectCombo').bind({
        'beforeshow newlist': function(e, o) {
            var div = $('<div/>').attr('id', 'projectPopupActiveBox');

            // now we add a function which gets called on click
            div.click(function(e) {
                // we hide the list and remove the active state
                activeProjectsFlag = 1 - activeProjectsFlag;
                o.list.hide();
                o.container.removeClass('ui-state-active');
                var filterName = filterName || '.worklist';
                // we send an ajax request to get the updated list
                $.ajax({
                    type: 'POST',
                    url: 'refresh-filter.php',
                    data: {
                        name: filterName,
                        active: activeProjectsFlag,
                        filter: 'projects'
                    },
                    dataType: 'json',
                    // on success we update the list
                    success: $.proxy(o.setupNewList, o)
                });
            });
            $('.itemProjectCombo').append(div);
            
            // setup the label and checkbox to put in the div
            var label = $('<label/>').css('color', '#ffffff').attr('for', 'onlyActive');
            var checkbox = $('<input/>').attr({
                type: 'checkbox',
                id: 'onlyActive'
            }).css({
                margin: 0,
                position: 'relative',
                top: '1px',
            });

            // we need to update the checkbox status
            if (activeProjectsFlag) {
                checkbox.prop('checked', true);
            } else {
                checkbox.prop('checked', false);
            }

            // put the label + checkbox in the div
            label.text(' Active only');
            label.prepend(checkbox);
            $('#projectPopupActiveBox').html(label);
        }
    }).comboBox();
    $('#itemStatusCombo').comboBox();
    
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

        if($('#addJob form input[name="is_bug"]').is(':checked')) {
            var bugJobId = new LiveValidation('bug_job_id', {
                onlyOnSubmit: true,
                onInvalid : function() {
                    loaderImg.hide("saveRunning");
                    this.insertMessage(this.createMessageSpan());
                    this.addFieldClass();
                }
            });
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

            massValidation = LiveValidation.massValidate([bugJobId]);
            if (!massValidation) {
                loaderImg.hide("saveRunning");
                event.preventDefault();
                return false;
            }
        }
        var summary = new LiveValidation('summary', {
            onlyOnSubmit: true,
            onInvalid: function() {
                loaderImg.hide("saveRunning");
                this.insertMessage(this.createMessageSpan());
                this.addFieldClass();
            }
        });
        summary.add(Validate.Presence, {failureMessage: "Can't be empty!"});
        massValidation = LiveValidation.massValidate([summary]);
        if (!massValidation) {
            loaderImg.hide("saveRunning");
            event.preventDefault();
            return false;
        }
        var itemProject = new LiveValidation('itemProjectCombo', {
            onlyOnSubmit: true ,
            onInvalid: function() {
                loaderImg.hide("saveRunning");
                this.insertMessage( this.createMessageSpan() );
                this.addFieldClass();
            }});
        itemProject.add( Validate.Exclusion, {
            within: [ 'select' ], partialMatch: true,
            failureMessage: "You have to choose a project!"
        });
        massValidation = LiveValidation.massValidate([itemProject]);
        if (!massValidation) {
            loaderImg.hide("saveRunning");
            event.preventDefault();
            return false;
        }
        addForm = $("#addJob");
        $.ajax({
            url: 'addworkitem.php',
            dataType: 'json',
            data: {
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
                    location.href = 'workitem.php?job_id=' + json.workitem;
                }
            }
        });
        return false;
    });
});