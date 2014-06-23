/**
 * Copyright (c) 2014, High Fidelity Inc.
 * All Rights Reserved. 
 *
 * http://highfidelity.io
 */

var UserStats = {
    page: {
        following: 1,
        doneJobs: 1,
        designerTotalJobs: 1,
        designerActiveJobs: 1,
        activeJobs: 1
    },
    stats_page: 1,

    init: function() {
        var dialog_options = { dialogClass: 'white-theme', autoOpen: false, width: '685px', show: 'fade', hide: 'fade'};
        $('#jobs-popup').dialog(dialog_options);
        $('#lovelist-popup').dialog(dialog_options);
        $('#latest-earnings-popup').dialog(dialog_options);

        $("nav.navbar .following").click(function(){
            UserStats.showFollowingJobs(1, userId);
            return false;
        });

        $('#latest-earnings').click(function(){
            UserStats.showLatestEarnings();
            return false;
        });

        $('#love').click(function(){
            UserStats.showLove();
            return false;
        });

    },

    modal: function(name, page, json, user, pagination) {
        Utils.modal(name, {
            data: json,
            pages: UserStats.getPagination(json, page),
            first: (page == 1),
            last: (page == json.pages),
            open: function(modal) {
                if (pagination) {
                    $('.pagination a', modal).click(function() {
                        UserStats.handlePaginationClick(this, page, function(newpage) {
                            pagination(newpage, user, modal);
                        });
                        return false;
                    });
                }
            }
        });
    },

    modalRefresh: function(modal, page, json, user, pagination) {
        Utils.modalRefresh(modal, {
            data: json,
            pages: UserStats.getPagination(json, page),
            first: (page == 1),
            last: (page == json.pages),
            success: function(modal) {
                $('.pagination a', modal).click(function() {
                    UserStats.handlePaginationClick(this, page, function(newpage) {
                        pagination(newpage, user, modal);
                    });
                    return false;
                })

            }
        });
    },

    showFollowingJobs: function(page, user, modal) {
        UserStats.page.following = page = (page ? page : 1);
        $.ajax({
            url: './user/following/' + user + '/' + page,
            dataType: 'json',
            success: function(json) {
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, UserStats.showFollowingJobs);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, UserStats.showFollowingJobs);
                }
            }
        });
    },

    showDoneJobs: function(page, user, modal) {
        UserStats.page.doneJobs = page = (page ? page : 1);
        $.ajax({
            url: './user/doneJobs/' + user + '/' + page,
            dataType: 'json',
            success: function(json) {
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, UserStats.showDoneJobs);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, UserStats.showDoneJobs);
                }
            }
        });
    },

    showDesignerTotalJobs: function(page, user, modal) {
        UserStats.page.designerTotalJobs = page = (page ? page : 1);
        $.ajax({
            url: './user/designerJobs/' + user + '/total/' + page,
            dataType: 'json',
            success: function(json) {
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, UserStats.showDesignerTotalJobs);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, UserStats.showDesignerTotalJobs);
                }
            }
        });
    },

    showDesignerActiveJobs: function(page, user, modal) {
        UserStats.page.designerActiveJobs = page = (page ? page : 1);
        $.ajax({
            url: './user/designerJobs/' + user + '/active/' + page,
            dataType: 'json',
            success: function(json) {
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, UserStats.showDesignerActiveJobs);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, UserStats.showDesignerActiveJobs);
                }
            }
        });
    },

    showActiveJobs: function(page, user, modal) {
        UserStats.page.activeJobs = page = (page ? page : 1);
        $.ajax({
            url: './user/activeJobs/' + user + '/' + page,
            dataType: 'json',
            success: function(json) {
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, UserStats.showActiveJobs);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, UserStats.showActiveJobs);
                }
            }
        });
    },

    showReviewJobs: function(page, user, modal) {
        UserStats.page.reviewJobs = page = (page ? page : 1);
        $.ajax({
            url: './user/reviewJobs/' + user + '/' + page,
            dataType: 'json',
            success: function(json) {
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, UserStats.showReviewJobs);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, UserStats.showReviewJobs);
                }
            }
        });
    },

    showLatestEarnings: function(page, user, modal) {
        UserStats.page.latestEarnings = page = (page ? page : 1);
        $.ajax({
            url: './user/latestEarnings/' + user + '/' + page,
            dataType: 'json',
            success: function(json) {
                if (typeof modal == 'undefined') {
                    UserStats.modal('latest-earnings', page, json, user, UserStats.showLatestEarnings);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, UserStats.showLatestEarnings);
                }
            }
        });
    },

    showLove: function(page) {
        $.ajax({
            url: './user/love/' + user + '/' + page,
            dataType: 'json',
            success: function(json) {
                UserStats.modal('love', page, json, user);
            }
        });
    },

    handlePaginationClick: function(which, current, fAfter) {
        var newpage = $(which).attr('goto');
        if ($(which).parent().hasClass('disabled')) {
            return;
        }
        if (newpage == 'prev') {
            newpage = parseInt(current) - 1;
        }
        if (newpage == 'next') {
            newpage = parseInt(current) + 1;
        }
        if (fAfter) {
            fAfter(newpage);
        }
    },

    getPagination: function(json, page) {
        var pages = [];
        for (var i = 1; i <= json.pages; i++) {
            pages.push({
                page: i,
                current: (i == page)
            });
        }
        return pages;
    },

    showJobs: function(job_type, popup, container){
        var containerDiv = '';
        var user_id = (stats.user_id == 0) ? window.user_id : stats.user_id;
        if (popup != 0) {
            containerDiv = '#jobs-popup';
        } else {
            if (typeof(container) !== 'undefined') {
                containerDiv = '#' + container;
            } else {
                containerDiv = '#jobs-table';
            }
        }

        $.getJSON('api.php?action=getUserStats',
                    {id: user_id, statstype: job_type, page: stats.stats_page},
                    function(json) {
                        if (job_type == 'activeJobs' || job_type == 'runnerActiveJobs' || job_type == 'following') {
                            $(containerDiv + ' th.status').show();
                        } else {
                            $(containerDiv + ' th.status').hide();
                        }
                        if (job_type == 'following') {
                            $(containerDiv + ' th.unfollow').show();
                        } else {
                            $(containerDiv + ' th.unfollow').hide();
                        }

                        stats.fillJobs(json, stats.partial(stats.showJobs, job_type), job_type, popup, container);

                        if (popup != 0) {
                            $('#jobs-popup').dialog('open');
                        }

                        if (job_type == 'following') {
                            $('a[id^=unfollow-]').click(function() {
                                var workitem_id = $(this).attr('id').split('-')[1];
                                $.ajax({
                                    type: 'post',
                                    url: 'jsonserver.php',
                                    data: {
                                        workitem: workitem_id,
                                        userid: user_id,
                                        action: 'ToggleFollowing'
                                    },
                                    dataType: 'json',
                                    success: function(data) {
                                        if (data.success) {
                                            $('#unfollow-' + workitem_id).parents('tr').remove();
                                        }
                                    }
                                });
                            });
                            $('#jobs-popup').addClass('table-popup');
                        }
                    });
    },

    // func is a functin to be called when clicked on pagination link
    fillJobs: function(json, func, job_type, popup, container) {
        if (popup != 0) {
             table = $('#jobs-popup table tbody');
        } else {
            if (typeof(container) !== 'undefined') {
                table = $('#' + container + ' table tbody');
            } else {
                table = $('#jobs-table table tbody');
            }
        }
        $('tr', table).remove();
        $.each(json.joblist, function(i, jsonjob) {
            var runner_nickname = jsonjob.runner_nickname != null ? jsonjob.runner_nickname : '----';
            if (popup != 0) {
                var toAppend = '<tr>'
                            + '<td class="workitem" id="workitem-' + jsonjob.id + '"><a href = "' + worklistUrl
                            + jsonjob.id
                            + '" target = "_blank">#'+ jsonjob.id + '</a></td>'
                            + '<td>' + jsonjob.summary + '</td>'
                            + '<td>' + jsonjob.creator_nickname + '</td>'
                            + '<td>' + runner_nickname + '</td>'
                            + '<td>' + jsonjob.created + '</td>';

                if (job_type == 'activeJobs' || job_type == 'runnerActiveJobs' || job_type == 'following') {
                    toAppend += '<td>' + jsonjob.status + '</td>';
                }

                if (job_type == 'following') {
                    toAppend += '<td><a href="#" id="unfollow-' + jsonjob.id + '">Un-Follow</a></td>';
                }

            } else {
                var toAppend = '<tr>'
                            + '<td class="workitem" id="workitem-' + jsonjob.id + '"><a href = "' + worklistUrl
                            + jsonjob.id
                            + '" target = "_blank">#'+ jsonjob.id + ' - <span>' + jsonjob.summary + '</span></a></td>';

                if (job_type == 'activeJobs' || job_type == 'runnerActiveJobs' || job_type == 'following') {
                    toAppend += '<td>' + jsonjob.status + '</td>';
                }

                if (job_type == 'following') {
                    toAppend += '<td><a href="#" id="unfollow-' + jsonjob.id + '">Un-Follow</a></td>';
                }
                
            }
            toAppend += '</tr>';
            table.append(toAppend);
        });
        if (popup != 0) {
            table.data('func', func);
            stats.appendStatsPagination(json.page, json.pages, table);
        }
        
        makeWorkitemTooltip($('.workitem'));
    },

    fillEarnings: function(json, func){
        var table = $('#latest-earnings-popup table tbody');
        $('tr', table).remove();
        $.each(json.joblist, function(i, jsonjob){
            var runner_nickname = jsonjob.runner_nickname != null ? jsonjob.runner_nickname : '----';
            var toAppend = '<tr>'
                        + '<td><a href = "' + worklistUrl
                        + jsonjob.worklist_id
                        + '" target = "_blank">#'+ jsonjob.worklist_id + '</a></td>'
                        + '<td>$' + jsonjob.amount + '</td>'
                        + '<td>' + jsonjob.summary + '</td>'
                        + '<td>' + jsonjob.creator_nickname + '</td>'
                        + '<td>' + runner_nickname + '</td>'
                        + '<td>' + jsonjob.paid_formatted + '</td>'
                        + '</tr>';

            table.append(toAppend);
        });
        table.data('func', func);
        stats.appendStatsPagination(json.page, json.pages, table);
    },

    fillLove: function(json, func){
        $('#lovelist-popup table tbody tr').remove();
        $.each(json.love, function(i, jsonlove){
            var toAppend = '<tr>'
                        + '<td>' + jsonlove.giver + '</td>'
                        + '<td>' + jsonlove.why + '</td>'
                        + '<td>' + jsonlove.at_format + '</td>'
                        + '</tr>';

            $('#lovelist-popup table tbody').append(toAppend);
        });

        var table = $('#lovelist-popup table tbody');
        table.data('func', func);
        stats.appendStatsPagination(json.page, json.pages, table);
    },

    appendStatsPagination: function(page, cPages, table){
        stats.stats_page = page;
        var paginationRow = $('<tr bgcolor="#FFFFFF">');
        paginationTD = $('<td colspan="7" style="text-align:center;">');

        if (page > 1) {

            paginationTD.append(stats.getA(page-1, 'Prev'));
            paginationTD.append('&nbsp;');
        }
        for (var i = 1; i <= cPages; i++) {
            if (i == page) {
                paginationTD.append(i + " &nbsp;");
            } else {
                paginationTD.append(stats.getA(i, i));
                paginationTD.append('&nbsp;');
            }
            if(i%20==0) {
                paginationTD.append('<br/>');
            }
        }
        if (page < cPages) {
            paginationTD.append(stats.getA(parseInt(page) + 1, 'Next'));
        }

        paginationRow.append(paginationTD);
        table.append(paginationRow);

        $('.pagination-link', table).click(function(){
            stats.stats_page = $(this).data('page');
            var func = table.data('func');
            func();
            return false;
        });
    },

    getA: function(page, txt){
        var a = $('<a href = "#">');
        a.data('page', page);
        a.addClass('pagination-link');
        a.html(txt);
        return a;
    },

    partial: function(func /*, 0..n args */) {
        var args = Array.prototype.slice.call(arguments, 1);
        return function() {
            var allArguments = args.concat(Array.prototype.slice.call(arguments));
            return func.apply(this, allArguments);
        };
    }
};