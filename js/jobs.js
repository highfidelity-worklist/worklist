var filterName = '.worklist';
isJobsPage = true;
var jobs = {
    offset: 0,
    limit: 30,
    query: null,
    following: 0,
    init: function() {
        if (search_query != '') {
            $('#search-query input[type="text"]').val(search_query);
        }
        jobs.query =  $('#search-query input[type="text"]').val();
        jobs.following = $('#jobs-im-following').is(":checked") ? 1 : 0;
        jobs.fetchJobs(jobs.offset, jobs.limit, search_project_id, search_status, jobs.query, jobs.following);
        jobs.renderDataOnScroll();
        jobs.filterChangeEventTrigger();
    },

    fetchJobs: function(offset, limit, project_id, status, query, following) {
        $.ajax({
            url: './jobs/getProjectJobs',
            cache: false,
            data: {
                offset: offset,
                limit: limit,
                project_id: project_id,
                status: status,
                query: query,
                following: following,
                participated: $('#only-jobs-i-participated input').is(':checked') ? 1 : 0
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
                jobs.renderJobs(data.search_result);
            },
            error: function(xhdr, status, err) {

            }
        });
    },

    renderJobs: function(data) {
        for(index in data) {
            if (!$('.project-header').hasClass('project-header-' + data[index].project_id)) {
                if ($('.project-header').length > 0) {
                    $('#jobs-sections').append(jobs.renderPassedJobsLink($('.project-header').last().find("h2").text()));
                }
                $('#jobs-sections').append(jobs.renderProjectHeader(data[index].project_id, data[index].project_name, data[index].short_description));
            }
            if (!$('.project-jobs-status').hasClass("project-"+ data[index].project_id + "-jobs-status-" + data[index].status.toLowerCase().replace(/ /g, '-'))) {
                $('#jobs-sections').append(jobs.renderJobStatus(data[index].project_id, data[index].status));
            }
            $('#jobs-sections').append(jobs.renderJob(data[index].id, data[index].project_id, data[index].summary, data[index].skills, data[index].comments, data[index].participants));
            var totalJobsRender = $('ul.project-jobs').length;
            var totalHitCount = $('body').data("jobs-total-hit-count");
            if (totalHitCount == totalJobsRender) {
                $('#jobs-sections').append(jobs.renderPassedJobsLink(data[index].project_name));
            }
            jobs.jobsEvent();
        }
    },
    renderProjectHeader: function(id, project_name, project_short_description) {
        var html = "<div class=\"project-header project-header-" + id + " col-sm-12 col-md-12 dd-max-width\"><h2>"+ project_name + "</h2><p>"+ project_short_description +"</p><hr/></div>";
        return html;
    },

    renderJobStatus: function(id, status) {
        var html = "<div class=\"project-jobs-status project-jobs-status-" + status.toLowerCase().replace(/ /g, '-') + "  project-" + id + "-jobs-status-" + status.toLowerCase().replace(/ /g, '-') + " col-sm-12 col-md-12 dd-max-width\"><i></i><span>" + status + "</span></div>";
        return html;
    },

    renderJob: function(id, project_id, summary, skills, comments, participants) {
        var html = "<ul data-job-id=\""+ id +"\" class=\"project-jobs col-sm-12 col-md-12 dd-max-width\"><li class=\"col-sm-1 col-md-1\"><a href=\"./"+ id +"\">#"+id+"</a></li><li class=\"col-sm-5 col-md-5\"><p>"+summary+"</p></li><li class=\"col-sm-2 col-md-2 project-jobs-skill\">";
        if($.trim(skills).length > 0) {
            var skills = skills.split(",");
            for(skillIndex in skills) {
                var skill = skills[skillIndex].split("~~");
                html += "<a>" + skill[0] + "</a>";
            }
        }
        html += "</li><li class=\"col-sm-2 col-md-2 project-jobs-participants\">";
        for (var participantIndex in participants) {
            if (participants[participantIndex].nickname != null) {
                html += participantIndex > 0 ? ",  " : "" + "<a href=\"./user/" + participants[participantIndex].id + "\">" + participants[participantIndex].nickname + "</a>";
            }
        }
        html += "</li>";
        if (comments != "0") {
            html += "<li class=\"col-sm-2 col-md-2 project-jobs-comment-count\"><a>" + comments +" comments</a></li></ul>";
        }
        return html;
    },

    renderPassedJobsLink: function(project_name) {
        var html = "";
        if (search_status != "pass") {
            html += "<div class=\"project-jobs-passed col-sm-12 col-md-12 dd-max-width\"><div class=\"project-jobs-pass col-sm-6 col-md-6 dd-max-width\"><i></i><a href=\"./jobs/"+project_name+"/pass\">View Passed Jobs</a></div>";
        }
        if (search_status != "done") {
            html += "<div class=\"project-jobs-done col-sm-6 col-md-6 dd-max-width\"><i></i><a href=\"./jobs/"+project_name+"/done\">View Done Jobs</a></div></div>";
        }
        return html;
    },

    renderDataOnScroll: function() {
        $(window).scroll(function() {
            var scrollTop = $(window).scrollTop() + 500;
            var windowHeight = ($(document).height() - $(window).height());
            var totalJobsRender = $('ul.project-jobs').length;
            if  (scrollTop >= windowHeight  && totalJobsRender < $('body').data("jobs-total-hit-count")) {
                jobs.fetchJobs((jobs.offset = jobs.offset + jobs.limit), jobs.limit, search_project_id, search_status, jobs.query);
            }
        });
    },

    filterChangeEventTrigger: function() {
        var query = $('#search-query input[type="text"]').val();
        $("select[name=project]").change(function() {
            search_project_id = $(this).val();
            jobs.offset = 0;
            jobs.fetchJobs(jobs.offset, jobs.limit, $(this).val(), search_status, jobs.query, jobs.following);
        });
        $('#search-query input[type="text"]').keypress(function(event) {
            if (event.keyCode == '13' && $.trim($(this).val()).length > 0) {
                event.preventDefault();
                search_query = '';
                jobs.offset = 0;
                jobs.query = $(this).val();
                jobs.fetchJobs(jobs.offset, jobs.limit, search_project_id, search_status, jobs.query, jobs.following);
            }
        });
        $("#query-search-button").click(function() {
            if ($.trim($('#search-query input[type="text"]').val()).length > 0) {
                search_query = '';
                jobs.offset = 0;
                jobs.query = $('#search-query input[type="text"]').val();
                jobs.fetchJobs(jobs.offset, jobs.limit, search_project_id, search_status, jobs.query, jobs.following);
            }
        });
        $('#jobs-im-following').click(function() {
            var project_name = $("select[name=project] option:selected").val() != 0 ? $("select[name=project] option:selected").text() : "all";
            if ($(this).is(":checked")) {
               window.location.href = "./jobs/" + project_name + "/following";
            } else {
               window.location.href = "./jobs/" + project_name;
            }
        });
        $('#only-jobs-i-participated').click(function() {
            jobs.offset = 0;
            jobs.fetchJobs(jobs.offset, jobs.limit, search_project_id, search_status, jobs.query, jobs.following);
        });
    },

    jobsEvent: function() {
        $("ul.project-jobs").click(function() {
            var jobId = $(this).data('job-id');
            window.location.href = "./"+ jobId;
        });
    }
};

$(document).ready(function() {
    jobs.init();
    $('.filter > select').selectpicker();
});