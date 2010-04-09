var statsPage = 1;

$(function(){

    $('#done-jobs-popup').dialog({autoOpen: false, width: 'auto'});
    $('#active-jobs-popup').dialog({autoOpen: false, width: 'auto'});
    $('#lovelist-popup').dialog({autoOpen: false, width: 'auto'});

    $('#total-jobs').click(function(){
        statsPage = 1;
        showTotalJobs();
    });

    $('#active-jobs').click(function(){
        statsPage = 1;
        showActiveJobs();
    });

    $('#love').click(function(){
        statsPage = 1;
        showLove();
    });

});

function showTotalJobs(){
    $.getJSON('getuserstats.php', 
                {id: userId, statstype: 'donejobs', page: statsPage},
                function(json){
                    fillJobs(json, 'done', showTotalJobs);
                    $('#done-jobs-popup').dialog('open');
                });
}

function showActiveJobs(){
    $.getJSON('getuserstats.php', 
                {id: userId, statstype: 'activejobs', page: statsPage},
                function(json){
                    fillJobs(json, 'active', showActiveJobs);
                    $('#active-jobs-popup').dialog('open');
                });
}

function showLove(){
    $.getJSON('getuserstats.php', 
                {id: userId, statstype: 'love', page: statsPage},
                function(json){
                    fillLove(json, showLove);
                    $('#lovelist-popup').dialog('open');
                });
}

// func is a functin to be called when clicked on pagination link
function fillJobs(json, table, func){

    table = $('#' + table + '-jobs-popup table tbody');
    $('tr', table).remove();
    $.each(json.joblist, function(i, jsonjob){
        var toAppend = '<tr>'
                    + '<td><a href = "' + worklistUrl 
                    + 'workitem.php?job_id=' + jsonjob.id 
                    + '&action=view" target = "_blank">#'+ jsonjob.id + '</a></td>'
                    + '<td>' + jsonjob.summary + '</td>'
                    + '<td>' + jsonjob.creator_nickname + '</td>'
                    + '<td>' + jsonjob.runner_nickname + '</td>'
                    + '<td>' + jsonjob.created + '</td>'
                    + '</tr>';

        table.append(toAppend);
    });
    table.data('func', func);
    AppendStatsPagination(json.page, json.pages, table);
}

function fillLove(json, func){

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
    AppendStatsPagination(json.page, json.pages, table);
}

// add pagination linnks and assign callbacks on clicking on them
function AppendStatsPagination(page, cPages, table){

    statsPage = page;
    var paginationRow = $('<tr bgcolor="#FFFFFF">');
    paginationTD = $('<td colspan="6" style="text-align:center;">');

    if (page > 1) {

        paginationTD.append(getA(page-1, 'Prev'));
        paginationTD.append('&nbsp;');
    }
    for (var i = 1; i <= cPages; i++) {
        if (i == page) {
            paginationTD.append(i + " &nbsp;");
        } else {
            paginationTD.append(getA(i, i));
            paginationTD.append('&nbsp;');
        }
    }
    if (page < cPages) {
        paginationTD.append(getA(parseInt(page) + 1, 'Next'));
    }

    paginationRow.append(paginationTD);
    table.append(paginationRow);

    $('.pagination-link', table).click(function(){
        statsPage = $(this).data('page');
        var func = table.data('func');
        func();
    });
}

// get an aobject representing link in pagination
function getA(page, txt){
    var a = $('<a href = "#">');
    a.data('page', page);
    a.addClass('pagination-link');
    a.html(txt);
    return a;
}