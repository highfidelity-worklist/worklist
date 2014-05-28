var AddJob = {
    submitIsRunning: false,
    init: function() {
        $('select[name="itemProject"]').chosen();

        var imageArray = new Array();
        var documentsArray = new Array();
        $('#addaccordion').fileUpload({images: imageArray, documents: documentsArray});

        var autoArgs = autocompleteMultiple('getuserslist', null);
        $("#invite").bind("keydown", autoArgs.bind);
        $("#invite").autocomplete(autoArgs);

        $('form#addJob').submit(AddJob.formSubmit);
        $("#labels li").click(AddJob.toggleLabel);

    },

    toggleLabel: function(event) {
        $(this).toggleClass('selected');
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
        var skills = '';
        $("#labels li.selected").each(function() {
            skills += (skills.length ? ', ' : '') + $(this).text();
        });

        $.ajax({
            url: './job/add',
            dataType: 'json',
            data: {
                summary: $("input[name='summary']").val(),
                files: $("input[name='files']").val(),
                invite: $("input[name='invite']").val(),
                notes: $("textarea[name='notes']").val(),
                page: $("input[name='page']").val(),
                project_id: $("select[name='itemProject']").val(),
                skills: skills,
                fileUpload: $('#addaccordion').data('fileUpload')
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
    }
}
