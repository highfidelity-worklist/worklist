var filterName = '.worklist';
var jobs = {
    offset: 0,
    limit: 30,
    query: null,
    following: 0,
    labels: [],

    init: function() {
        if (search_query != '') {
            $('#search-query input[type="text"]').val(search_query);
        }
        jobs.query =  $('#search-query input[type="text"]').val();
        jobs.following = $('#jobs-im-following').is(":checked") ? 1 : 0;
        jobs.labels = search_labels.split(',');
        jobs.fetchJobs(jobs.offset, jobs.limit, search_project_id, search_status, jobs.query, jobs.following, jobs.labels);
        jobs.renderDataOnScroll();
        jobs.filterChangeEventTrigger();
        jobs.jobsEvent();
    },

    fetchJobs: function(offset, limit, project_id, status, query, following, labels, fAfter) {
        $.ajax({
            url: './job/search',
            cache: false,
            data: {
                offset: offset,
                limit: limit,
                project_id: project_id,
                status: query.length ? '' : status,
                query: query,
                following: following,
                participated: $('#only-jobs-i-participated input').is(':checked') ? userId : 0,
                labels: labels.join(',')
            },
            dataType: 'json',
            success: function(data) {
                if (typeof(data.redirect) != 'undefined') {
                    window.location = "./job/" + data.redirect;
                }
                if(offset == 0) {
                    $('#jobs-sections').empty();
                    $('body').data("jobs-total-hit-count", data.total_Hit_count);
                }
                jobs.renderJobs(data, fAfter);
            },
            error: function(xhdr, status, err) {

            }
        });
    },

    renderJobs: function(data, fAfter) {
        for(index in data.search_result) {
            var result = data.search_result[index];
            if (!$('.project-header.project-header-' + result.project_id).length) {
                jobs.renderProjectHeader(result.project_id, result.project_name, result.short_description);
                $('<section>')
                  .attr('id', 'jobs-for-project-' + result.project_id)
                  .appendTo('#jobs-sections');
                jobs.renderProjectStats(result.project_name, data.search_stats);
            }
            if (!$('.project-jobs-status').hasClass("project-"+ result.project_id + "-jobs-status-" + result.status.toLowerCase().replace(/ /g, '-'))) {
                jobs.renderJobStatus(result.project_id, result.status);

            }
            jobs.renderJob(result.id, result.project_id, result.summary, result.labels, result.comments, result.participants);
        }
        if (fAfter) {
            fAfter();
        }
    },
    renderProjectHeader: function(id, project_name, project_short_description, done_job_count, pass_job_count) {
        var html =
            "<div class=\"project-header project-header-" + id + " col-sm-12 col-md-12 dd-max-width\">" +
            "  <h2>"+ project_name + "</h2>" +
            "  <p>"+ project_short_description +"</p>" +
            "  <hr/>" +
            "</div>";
        $('#jobs-sections').append(html);
    },

    renderDataOnScroll: function() {
        $(window).scroll(function() {
            if (jobs.renderingNewPage) {
                return;
            }
            var scrollPos = $(window).scrollTop() + $(window).height();
            var docHeight = $(document).outerHeight();
            var threshold = 800;
            var reset_callback = function() {
                jobs.renderingNewPage = false;
            }
            if (scrollPos + threshold >= docHeight) {
                jobs.renderingNewPage = true;
                jobs.fetchJobs((jobs.offset = jobs.offset + jobs.limit), jobs.limit, search_project_id, search_status, jobs.query, jobs.following, jobs.labels, reset_callback);
            }
        });
    },

    renderJobStatus: function(id, status) {
        var html = "<div class=\"project-jobs-status project-jobs-status-" + status.toLowerCase().replace(/ /g, '-') + "  project-" + id + "-jobs-status-" + status.toLowerCase().replace(/ /g, '-') + " col-sm-12 col-md-12 dd-max-width\"><i></i><span>" + status + "</span></div>";
        $('#jobs-for-project-' + id).append(html);
    },

    renderJob: function(id, project_id, summary, labels, comments, participants) {
        var html = "<ul data-job-id=\""+ id +"\" class=\"project-jobs col-sm-12 col-md-12 dd-max-width\"><li class=\"col-sm-1 col-md-1\"><a href=\"./"+ id +"\">#"+id+"</a></li><li class=\"col-sm-5 col-md-5\"><a href=\"./"+ id +"\">"+summary+"</a></li><li class=\"col-sm-2 col-md-2 project-jobs-label\">";
        if($.trim(labels).length > 0) {
            var labels = labels.split(",");
            for(labelIndex in labels) {
                var label = labels[labelIndex].split(':');
                html += "<a class=" + (label[2] == '1' ? 'active' : '') + ">" + label[1] + "</a>";
            }
        }
        html += "</li><li class=\"col-sm-2 col-md-2 project-jobs-participants\">";
        for (var participantIndex in participants) {
            if (participants[participantIndex].nickname != null) {
                html += (participantIndex > 0 ? ", " : "") + "<a href=\"./user/" + participants[participantIndex].id + "\">" + $.trim(participants[participantIndex].nickname) + "</a>";
            }
        }
        html += "</li>";
        if (comments != "0") {
            html += "<li class=\"col-sm-2 col-md-2 project-jobs-comment-count\"><a>" + comments +" comments</a></li></ul>";
        }
        $('#jobs-for-project-' + project_id).append(html);
    },

    renderProjectStats: function(project_name, stats_data) {
        var html = '<div class="project-jobs-stats">';
        for(var i = 0; i < stats_data.length; i++) {
            var data = stats_data[i];
            if (data.project == project_name) {
                var status = data.status;
                html +=
                    '<span class="project-jobs-' + status.replace(' ', '') + '">' +
                      '<i></i>' +
                      '<a href="./jobs/' + project_name + '/' + status + '">' + status + ' Jobs</a>' +
                    '</span>';
            }
        }
        html += "</div>";
        $('#jobs-sections').append(html);
    },

    filterChangeEventTrigger: function() {
        var query = $('#search-query input[type="text"]').val();
        $("select[name=project]").change(function() {
            var project_name = $(this).find("option:selected").val() != 0 ? $(this).find("option:selected").text().trim() : "";
            window.location.href = "./jobs/" + project_name + ($.trim(jobs.query).length > 0 ? '?query=' + jobs.query : '');
        });
        $('#search-query input[type="text"]').keypress(function(event) {
            if (event.keyCode == '13') {
                event.preventDefault();
                search_query = '';
                jobs.offset = 0;
                jobs.query = $(this).val();
                jobs.fetchJobs(jobs.offset, jobs.limit, search_project_id, search_status, jobs.query, jobs.following, jobs.labels);
            }
        });
        $("#query-search-button").click(function() {
                search_query = '';
                jobs.offset = 0;
                jobs.query = $('#search-query input[type="text"]').val();
                jobs.fetchJobs(jobs.offset, jobs.limit, search_project_id, search_status, jobs.query, jobs.following, jobs.labels);
        });
        $('#jobs-im-following').click(function() {
            var project_name = $("select[name=project] option:selected").val() != 0 ? $("select[name=project] option:selected").text() : "all";
            if ($(this).is(":checked")) {
               window.location.href = "./jobs/" + project_name + "/following" + ($.trim(jobs.query).length > 0 ? '?query=' + jobs.query : '');
            } else {
               window.location.href = "./jobs/" + project_name +  ($.trim(jobs.query).length > 0 ? '?query=' + jobs.query : '');
            }
        });
        $('#only-jobs-i-participated').click(function() {
            jobs.offset = 0;
            jobs.fetchJobs(jobs.offset, jobs.limit, search_project_id, search_status, jobs.query, jobs.following, jobs.labels);
        });
    },

    jobsEvent: function() {
        $("ul.project-jobs").live("click", function(event) {
            if(event.ctrlKey) {
                return true;
            }
            window.location.href = "./" + $(this).data('job-id');
        });
    }

};

$(document).ready(function() {
    jobs.init();
    $('.filter > select').selectpicker();
});
