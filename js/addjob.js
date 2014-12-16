var AddJob = {
    submitIsRunning: false,
    uploadedFiles: [],
    filesUploading: 0,
    editing: false,
    jobId: 0,
    status: ["Bidding", "In Progress", "Suggestion", "Draft"],
    init: function(job_id, editing) {
        AddJob.jobId = job_id;
        AddJob.editing = editing;
        $('select[name="itemProject"], select[name="assigned"], #budget-source-combo, select[name="runner"]').chosen({
            width: '100%',
            disable_search_threshold: 10
        });
        $('select[name="assigned"]').change(AddJob.checkAssignedUser);
        $('form#addJob').submit(AddJob.formSubmit);
        AddJob.initFileUpload();
        if (!AddJob.editing) {
            $('#addJobButton').hide();
        }
        AddJob.initLabels();
        AddJob.refreshLabels();
        AddJob.refreshStatus($("select[name='itemProject']").val());
        $("select[name='itemProject']").on('change', function() {
            AddJob.refreshStatus($(this).val());
        });
    },

    initLabels: function() {
        $("select[name='itemProject']").on('change', AddJob.refreshLabels);
        AddJob.refreshLabels();
    },

    refreshLabels: function() {
        var projectId = $("select[name='itemProject']").val();
        $.ajax({
            url: './project/labels/' + $("select[name='itemProject']").val() + '/active',
            dataType: "json",
            success: function(json) {
                if (!json.success) {
                    return;
                }
                $('ul#labels li').remove();
                var labels = json.data;
                for(var i = 0; i < labels.length; i++) {
                    var label = labels[i];
                    var item = $('<li>');
                    $('<input>').attr({
                        id: 'label' + label.id,
                        name: 'label' + label.id,
                        type: 'checkbox',
                        value: label.label
                    }).appendTo(item);
                    $('<label>')
                        .attr('for', 'label' + label.id)
                        .text(label.label)
                        .appendTo(item);
                    $('ul#labels').append(item);
                }
            }
        });
    },

    showProjectDescription: function() {
        $.ajax({
            url: './project/info/' + $("select[name='itemProject']").val(),
            dataType: "json",
            success: function(json) {
                if (!json.success) {
                    return;
                }
            }
        });
    },

    checkAssignedUser: function() {
        if (parseInt($(this).val()) > 0) {
            $("input[name='is_internal']").prop('checked', true);
            $("select[name='itemStatus']").val('Bidding');
            $("select[name='itemStatus']").trigger("chosen:updated");
        }
    },

    initFileUpload: function() {
        var options = {iframe: {url: './file/add'}};
        var zone = new FileDrop('attachments', options);

        zone.event('send', function (files) {
            files.each(function (file) {
                file.event('done', AddJob.fileUploadDone);
                file.event('error', AddJob.fileUploadError);
                file.sendTo('./file/add');
                AddJob.filesUploading++;
                AddJob.animateUploadSpin();
            });
        });
        zone.multiple(true);

        $('#attachments > label > em').click(function() {
            $('#attachments input.fd-file').click();
        });
        if (AddJob.editing) {
            $.ajax({
                type: 'get',
                url: './file/listForJob/' + AddJob.jobId,
                dataType: 'json',
                success: function(data) {
                    if (!data.success) {
                        return;
                    }
                    for (i = 0; i < data.data.length; i++) {
                        var fileData = data.data[i];
                        AddJob.renderAttachment(fileData);
                    }
                }
            });
        }
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
                AddJob.renderAttachment(fileData);
                AddJob.fileUploadFinished();
            }
        });
    },

    renderAttachment: function(file) {
        Utils.parseMustache('partials/upload-document', file, function(parsed) {
            $('#attachments > ul').append(parsed);
            $('#attachments li[attachment=' + file.fileid + '] > i').click(AddJob.removeFile);
            AddJob.uploadedFiles.push(file.fileid);
        });
    },

    fileUploadError: function(e, xhr) {
        AddJob.fileUploadFinished();
    },

    fileUploadFinished: function() {
        AddJob.filesUploading--;
        if (AddJob.filesUploading == 0) {
            AddJob.stopUploadSpin();
        }
    },

    removeFile: function(event) {
       var id = parseInt($(this).parent().attr('attachment'));
       function removeHtml() {
            $('#attachments li[attachment=' + id + ']').remove();
            for (var i = 0; i < AddJob.uploadedFiles.length; i++) {
                if (AddJob.uploadedFiles[i] == id) {
                    AddJob.uploadedFiles.splice(i, 1);
                }
            }
        }
        if (AddJob.editing) {
            $.ajax({
                url: './file/remove/' + id,
                type: 'POST',
                dataType: "json",
                success: function(json) {
                    if (json.success == true) {
                        removeHtml();
                    }
                }
            });
        }
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

    formSubmit: function(event){
        event.preventDefault();
        if (AddJob.submitIsRunning) {
            return false;
        }
        AddJob.submitIsRunning = true;

        var summary = new LiveValidation('summary');
        summary.add(Validate.Presence, {failureMessage: "You must enter the job title!"});

        var itemProject = new LiveValidation('itemProjectCombo');
        itemProject.add(Validate.Exclusion, {
            within: ['select'],
            partialMatch: true,
            failureMessage: "You have to choose a project!"
        });

        massValidation = LiveValidation.massValidate([itemProject, summary]);

        if (!massValidation) {
            AddJob.submitIsRunning = false;
            return false;
        }
        var labels = '';
        $('#labels li input[name^="label"]').each(function() {
            if ($(this).is(':checked')) {
                labels += (labels.length ? ', ' : '') + $(this).val();
            }
        });
        if (!AddJob.editing) {
            AddJob.save(labels);
        } else {
            AddJob.update(labels);
        }

        return false;
    },

    addLabel: function() {
        var currentLabels = ($('#labels').attr('val') ? $('#labels').attr('val') : '').split(',');
        var newLabels = $('#labels + input').val().split(',');
        var labels = $.unique($.merge(newLabels, currentLabels));
        var html = '', val = '';
        for (var i = 0; i < labels.length; i++) {
            if (!labels[i].trim().length) continue;
            html += '<li> ' + labels[i] + '</li>';
            val += (val.length ? ',' : '') + labels[i];
        }
        $('#labels').attr('val', val).html(html);
        $('#labels + input').val('');
    },
    save: function(labels)  {
          $.ajax({
            url: './job/add',
            dataType: 'json',
            data: {
                summary: $("input[name='summary']").val(),
                is_internal: $("input[name='is_internal']").is(':checked') ? 1 : 0,
                files: $("input[name='files']").val(),
                notes: $("textarea[name='notes']").val(),
                page: $("input[name='page']").val(),
                project_id: $("select[name='itemProject']").val(),
                status: $("select[name='itemStatus']").val(),
                labels: labels,
                fileUpload: {uploads: AddJob.uploadedFiles},
                assigned: $('select[name="assigned"]').val(),
                itemid: AddJob.jobId > 0 ? AddJob.jobId : "",
                budget_id: AddJob.editing ? $('#budget-source-combo').val() : ""
            },
            type: 'POST',
            success: function(json) {
                AddJob.submitIsRunning = false;
                if (json.error) {
                    alert(json.error);
                } else {
                    location.href = './' + json.workitem;
                }
            }
        });
    },
    update: function(labels) {
           $.ajax({
            url: './job/edit',
            dataType: 'json',
            data: {
                summary: $("input[name='summary']").val(),
                is_internal: $("input[name='is_internal']").is(':checked') ? 1 : 0,
                files: $("input[name='files']").val(),
                notes: $.trim($("textarea[name='notes']").val()),
                project_id: $("select[name='itemProject']").val(),
                status: $("select[name='itemStatus']").val(),
                labels: labels,
                fileUpload: {uploads: AddJob.uploadedFiles},
                budget_id: $('#budget-source-combo').val() != null ? $('#budget-source-combo').val() : 0,
                worklist_id: AddJob.jobId,
                runner_id: $("select[name='runner']").val()
            },
            type: 'POST',
            success: function(json) {
                if (json.error) {
                    alert(json.error);
                } else {
                    location.href = './' + json.workitem;
                }
            }
        });
    },
    refreshStatus: function(project_id) {
        if ((is_admin && is_runner) || AddJob.isProjectRunner(project_id)) {
            var selectHtml = '<select id="itemStatusCombo" name="itemStatus">';
            var selectStatusIndex = 0;
            if (AddJob.editing) {
                if ($.inArray(jobStatus, AddJob.status) == -1) {
                    AddJob.status.push(jobStatus);
                }
                selectStatusIndex = AddJob.status.indexOf(jobStatus);
            }
            for (var statusIndex in AddJob.status) {
                var selected = selectStatusIndex == statusIndex ? "selected=selected" : "";
                selectHtml += '<option value="' + AddJob.status[statusIndex] + '" ' + selected +'>' + AddJob.status[statusIndex] + '</option>';
            }
            selectHtml += '</select>';
            $('#choose-job-status').html(selectHtml);
            $('select[name="itemStatus"]').chosen({
                width: '100%',
                disable_search_threshold: 10
            });
        } else {
            $('#choose-job-status').empty();
        }
    },
    isProjectRunner: function(project_id) {
        var isProjectRunner =  false;
        for (var index in projectsAsRunner) {
            if (projectsAsRunner[index].id === project_id) {
                isProjectRunner = true;
                break;
            }
        }
        return isProjectRunner;
    }

}