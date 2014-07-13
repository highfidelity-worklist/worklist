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

    init: function() {
        $("nav.navbar .following").click(function(){
            UserStats.showFollowingJobs(1, userId);
            return false;
        });
        $('#love').click(function(){
            UserStats.showLove();
            return false;
        });
    },

    modal: function(name, page, json, user, title, pagination, fAfter) {
        Utils.modal(name, {
            data: json,
            title: title,
            pages: UserStats.getPaginationData(json, page),
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
                if (fAfter) {
                    fAfter(modal);
                }
            }
        });
    },

    modalRefresh: function(modal, page, json, user, title, pagination, fAfter) {
        Utils.modalRefresh(modal, {
            data: json,
            title: title,
            pages: UserStats.getPaginationData(json, page),
            first: (page == 1),
            last: (page == json.pages),
            success: function(modal) {
                if (pagination) {
                    $('.pagination a', modal).click(function() {
                        UserStats.handlePaginationClick(this, page, function(newpage) {
                            pagination(newpage, user, modal);
                        });
                        return false;
                    });
                }
                if (fAfter) {
                    fAfter(modal);
                }
            }
        });
    },

    hideJobsStatusCol: function(modal) {
        console.log($('tr > th:nth-child(5), tr > td:nth-child(5)', modal).length);
        $('tr > th:nth-child(5), tr > td:nth-child(5)', modal).hide();
    },

    showFollowingJobs: function(page, user, modal) {
        UserStats.page.following = page = (page ? page : 1);
        $.ajax({
            url: './user/following/' + user + '/' + page,
            dataType: 'json',
            success: function(json) {
                var title = "Jobs I'm following";
                var addActionButtons = function(modal) {
                    $('tr', modal).each(function() {
                        header = $(this).parent().is('thead');
                        var html = header ? '<th>' : '<td>';
                        var column = $(html);
                        if (!header) {
                            $('<button>')
                                .html('Un-Follow')
                                .attr('type', 'button')
                                .addClass('btn btn-primary btn-xs')
                                .appendTo(column);
                        }
                        $(this).append(column);
                    });
                    $('tr > td:last-child > button').click(function() {
                        var parentRow = $(this).parent().parent();
                        var job_id = parentRow.attr('job');
                        $.ajax({
                            type: 'post',
                            url: './job/toggleFollowing/' + job_id,
                            dataType: 'json',
                            success: function(data) {
                                if (data.success) {
                                    $(parentRow).remove();
                                }
                            }
                        });
                    });
                };
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, title, UserStats.showFollowingJobs, addActionButtons);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, title, UserStats.showFollowingJobs, addActionButtons);
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
                var title = "Done jobs";
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, title, UserStats.showDoneJobs, UserStats.hideJobsStatusCol);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, title, UserStats.showDoneJobs, UserStats.hideJobsStatusCol);
                }
            }
        });
    },

    showTotalJobs: function(page, user, modal) {
        UserStats.page.doneJobs = page = (page ? page : 1);
        $.ajax({
            url: './user/totalJobs/' + user + '/' + page,
            dataType: 'json',
            success: function(json) {
                var title = "Total jobs for <a href='./user/'" + user + "'>" + user + "</a>";
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, title, UserStats.showTotalJobs);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, title, UserStats.showTotalJobs);
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
                var title = "Jobs as Designer";
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, title, UserStats.showDesignerTotalJobs, UserStats.hideJobsStatusCol);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, title, UserStats.showDesignerTotalJobs, UserStats.hideJobsStatusCol);
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
                var title = "Active jobs as Designer";
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, title, UserStats.showDesignerActiveJobs);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, title, UserStats.showDesignerActiveJobs);
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
                var title = "Active jobs";
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, title, UserStats.showActiveJobs);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, title, UserStats.showActiveJobs);
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
                var title = "Review jobs";
                if (typeof modal == 'undefined') {
                    UserStats.modal('jobs', page, json, user, title, UserStats.showReviewJobs, UserStats.hideJobsStatusCol);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, title, UserStats.showReviewJobs, UserStats.hideJobsStatusCol);
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
                    UserStats.modal('latest-earnings', page, json, user, '', UserStats.showLatestEarnings);
                } else {
                    UserStats.modalRefresh(modal, page, json, user, '', UserStats.showLatestEarnings);
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

    getPaginationData: function(json, page) {
        var pages = [];
        var fromPage = 1;
        if (json.pages > 10 && page > 6) {
            if (page + 4 <= json.pages) {
                fromPage = page - 6;
            } else {
                fromPage = json.pages - 10;
            }
        }
        for (var i = fromPage; (i <= (fromPage +10) && i <= json.pages); i++) {
            pages.push({
                page: i,
                current: (i == page)
            });
        }
        return pages;
    }
};