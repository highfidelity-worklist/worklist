if (typeof activeProjectsFlag === 'undefined') {
    var activeProjectsFlag = true;
}

$(function() {

    $('select[name=itemProject]').bind({
        'listOpen': function(e,o) {
            $('#projectPopupActiveBox').width($('.itemProjectComboList').outerWidth());
            $('#projectPopupActiveBox').css({
                top: $('.itemProjectComboList').height() + 62,
                left: $('.itemProjectComboList').css('left')
            });
            $('#projectPopupActiveBox').show();
        },
        'listClose': function(e,o) {
            $('#projectPopupActiveBox').hide();
        }
    });

    //Enable/disable job bug id on is_bug checkbox state
    $("#bug_job_id").ready(function() {
        if ( !$("#is_bug").is ( ":checked" ) ) {
            $("#bug_job_id").prop ( "disabled" , true );
        } else {
            $("#bug_job_id").removeAttr ( "disabled" );
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
            $("#bug_job_id").prop ( "disabled" , true );
            $("#bug_job_id").val ("");
            $('#bugJobSummary').html('');
            $("#bugJobSummary").attr("title" , 0);
            if (/^\[BUG\]/i.test($('#summary').val())) {
                $('#summary').val($('#summary').val().substring(5).trim());
            }

        } else {
            //Enable bug_job_id textbox
            $("#bug_job_id").removeAttr ( "disabled" );
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
                    if ( !json || json === null ) {
                        alert("json null in getjobinformation");
                        return;
                    }
                    if ( json.error ) {
                        alert(json.error);
                    } else {
                        if(json.returnString.length>0) {
                            $('#bugJobSummary').html('<p><small>'+json.returnString+'</small></p>');
                            $("#bugJobSummary").attr("title" , id);
                        } else {
                            $('#bugJobSummary').html("<p><small>Item doesn't exist</small></p>");
                            $("#bugJobSummary").attr("title" , 0);
                        }
                    }
                }
            });
        }
    });
});

function ResetPopup() {
    $('#for_edit').show();
    $('#for_view').hide();
    $('.popup-body form input[name="summary"]').val('');
    $('.popup-body form input[name="skills"]').val('');
    $('.popup-body form input[name="invite"]').val('');
    $('.popup-body form input[name="bug_job_id"]').val('');
    $('.popup-body form select.resetToFirstOption option:first').prop('selected', true);
    $('.popup-body form textarea').val('');

    //Reset popup edit form
    $("#bug_job_id").prop( "disabled", true);
    $("#bug_job_id").val("");
    $('#bugJobSummary').html('');
    $("#bugJobSummary").attr("title", 0);
    $("#is_bug").prop('checked',false);
    $('input[name=files]').val('');
    $('#addaccordion .fileimagecontainer').html('').hide();
    $('#addaccordion .filedocumentcontainer ').html('').hide();
    $('#addaccordion .imageCount').text('0');
    $('#addaccordion .documentCount').text('0');
    imageArray = new Array();
    documentsArray = new Array();
    $('#addaccordion').removeData('fileUpload');
    $('#addaccordion').fileUpload({images: imageArray, documents: documentsArray});
}

$('#popup-edit').dialog({
    autoOpen: false,
    dialogClass: 'white-theme',
    show: 'fade',
    hide: 'fade',
    width: 555,
    hasAutocompleter: false,
    hasCombobox: false,
    resizable: false,
    open: function() {
        if (this.hasAutocompleter !== true) {
            $('.invite').autocomplete('getusers.php', {
                multiple: true,
                multipleSeparator: ', ',
                selectFirst: true,
                extraParams: { nnonly: 1 }
            });
            $.get('getskills.php', function(data) {
                var skills = eval(data);
                $("#skills").autocomplete(skills, {
                    width: 320,
                    max: 10,
                    highlight: false,
                    multiple: true,
                    multipleSeparator: ", ",
                    scroll: true,
                    scrollHeight: 300
                });
            });
            this.hasAutocompleter = true;
        }
        $('#more-accordion').accordion({
            clearStyle: true,
            collapsible: true,
            active: false
        });
        if (this.hasCombobox !== true) {
            // to add a custom stuff we bind on events
            $('#popup-edit select[name=itemProject]').bind({
                'beforeshow newlist': function(e, o) {
                    // check if the div for the checkbox already exists
                    if ($('#projectPopupActiveBox').length == 0) {
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
                    }
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
            $('#popup-edit select[name=status]').comboBox();
            this.hasCombobox = true;
        } else {
            $('#popup-edit select[name=itemProject], #popup-edit select[name=status]').next().hide();
            setTimeout(function() {
                var val1 = $($('#popup-edit select[name=itemProject] option').get(1)).attr("value");
                $('#popup-edit select[name=itemProject]').comboBox({action: "val", param: [val1]});
                if ($('#popup-edit select[name=status] option').length) {
                    val1 = $($('#popup-edit select[name=status] option').get(1)).attr("value");
                    $('#popup-edit select[name=status]').comboBox({action: "val", param: [val1]});
                }
                setTimeout(function() {
                    $('#popup-edit select[name=itemProject], #popup-edit select[name=status]').next().show();
                    $('#popup-edit select[name=itemProject], #popup-edit select[name=status]').comboBox({action: "val", param: ["select"]});
                },50);
            },20);

        }
    },
    close: function() {
        $('#popup-edit').unbind('dialogbeforeclose');
    }
});

$('#addJob').unbind('click').click(function() {
    if (userId <= 0) {
        $("#popup-guest-message").dialog({ autoOpen: false, hide: 'drop' });
        $("#addJob").click(function() {
            $("#popup-guest-message").dialog('open').centerDialog();
        });
        return;
    }

    $('#popup-edit').bind('dialogbeforeclose', function() {
        var result = confirm("Are you sure you want to close this dialog? You will lose all the information entered.");
        if (result) {
            //This is a workaround for the delayed dialog close, due to Journal requests being so active - dans
            $(this).parent().hide();
        }
        return result;
    });
    $('#popup-edit form input[name="itemid"]').val('');
    ResetPopup();
    $('#save_item').click(function(event){
        var massValidation;
        if ($('#save_item').data("submitIsRunning") === true) {
            event.preventDefault();
            return false;
        }
        $('#save_item').data("submitIsRunning", true);
        loaderImg.show("saveRunning","Saving, please wait ...", function() {
            $('#save_item').data("submitIsRunning", false);
        });

        if($('#popup-edit form input[name="is_bug"]').is(':checked')) {
            var bugJobId = new LiveValidation('bug_job_id', {
                onlyOnSubmit: true,
                onInvalid : function() {
                    loaderImg.hide("saveRunning");
                    this.insertMessage( this.createMessageSpan() );
                    this.addFieldClass();
                }
            });
            bugJobId.add( Validate.Custom, {
                against: function(value, args) {
                    id = $('#bugJobSummary').attr('title');
                    return (id != 0)
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
        if ($('#popup-edit form input[name="bid_fee_amount"]').val() || $('#popup-edit form input[name="bid_fee_desc"]').val()) {
            // see http://regexlib.com/REDetails.aspx?regexp_id=318
            // but without  dollar sign 22-NOV-2010 <krumch>
            var regex = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
            var optionsLiveValidation = { onlyOnSubmit: true,
                onInvalid: function() {
                    loaderImg.hide("saveRunning");
                    this.insertMessage( this.createMessageSpan() );
                    this.addFieldClass();
                }
            };
            var bid_fee_amount = new LiveValidation('bid_fee_amount', optionsLiveValidation);
            var bid_fee_desc = new LiveValidation('bid_fee_desc', optionsLiveValidation);

            bid_fee_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
            bid_fee_amount.add( Validate.Format, { pattern: regex, failureMessage: "Invalid Input!" });
            bid_fee_desc.add( Validate.Presence, { failureMessage: "Can't be empty!" });
            massValidation = LiveValidation.massValidate([bid_fee_amount, bid_fee_desc]);
            if (!massValidation) {
                loaderImg.hide("saveRunning");
                event.preventDefault();
                return false;
            }
        } else {
            if (bid_fee_amount) bid_fee_amount.destroy();
            if (bid_fee_desc) bid_fee_desc.destroy();
        }
        var summary = new LiveValidation('summary', {
            onlyOnSubmit: true,
            onInvalid: function() {
                loaderImg.hide("saveRunning");
                this.insertMessage( this.createMessageSpan() );
                this.addFieldClass();
            }
        });
        summary.add( Validate.Presence, { failureMessage: "Can't be empty!" });
        massValidation = LiveValidation.massValidate( [ summary ]);
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
        massValidation = LiveValidation.massValidate( [ itemProject ]);
        if (!massValidation) {
            loaderImg.hide("saveRunning");
            event.preventDefault();
            return false;
        }
        addForm = $("#popup-edit");
        $.ajax({
            url: 'addworkitem.php',
            dataType: 'json',
            data: {
                bid_fee_amount: $(":input[name='bid_fee_amount']", addForm).val(),
                bid_fee_mechanic_id: $(":input[name='bid_fee_mechanic_id']", addForm).val(),
                bid_fee_desc: $(":input[name='bid_fee_desc']", addForm).val(),
                itemid: $(":input[name='itemid']", addForm).val(),
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
                if ( !json || json === null ) {
                    alert("json null in addworkitem");
                    loaderImg.hide("saveRunning");
                    return;
                }
                if ( json.error ) {
                    alert(json.error);
                } else {
                    $('#popup-edit').unbind('dialogbeforeclose');
                    $('#popup-edit').dialog('close');
                }
                loaderImg.hide("saveRunning");
                if (!addFromJournal) {
                    if (timeoutId) clearTimeout(timeoutId);
                    timeoutId = setTimeout("GetWorklist("+page+", true, true)", refresh);
                    GetWorklist("+page+", true, true);
                }
            }
        });
        return false;
    });

    $('#fees_single_block').show();
    $('#popup-edit').dialog('open').centerDialog();
});
