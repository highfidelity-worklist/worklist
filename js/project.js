var imageArray = new Array();
var documentsArray = new Array();

function ToolTip() {
    xOffset = 10;
    yOffset = 20;
    var el_parent, el_child;
    $(".toolparent").hover(function(e) {
        if (el_child) el_child.appendTo(el_parent).hide();
        el_parent = $(this);
        el_child = el_parent.children(".tooltip")
            .appendTo("body")
            .css("top",(e.pageY - xOffset) + "px")
            .css("left",(e.pageX + yOffset) + "px")
            .fadeIn("fast");
    },
    function() {
        if (el_child) el_child.appendTo(el_parent);
        $(".tooltip").hide();
        el_child = null;
    });
    $(".toolparent").mousemove(function(e) {
        if (el_child) {
            el_child
                .css("top",(e.pageY - xOffset) + "px")
                .css("left",(e.pageX + yOffset) + "px");
        }
    });
}
var loaderImg = function($) {
    var aLoading = new Array(),
        _removeLoading = function(id) {
            for (var j=0; j < aLoading.length; j++) {
                if (aLoading[j].id == id) {
                    if (aLoading[j].onHide) {
                        aLoading[j].onHide();
                    }
                    aLoading.splice(j,1);
                }
            }
        },
        _show = function(id,title,callback) {
            aLoading.push({ id : id, title : title, onHide : callback});
            $("#loader_img_title").append("<div class='"+id+"'>"+title+"</div>");
            if (aLoading.length == 1) {
                $("#loader_img").css("display","block");
            }
            $("#loader_img_title").center();
        },
        _hide = function(id) {
            _removeLoading(id);
            if (aLoading.length == 0) {
                $("#loader_img").css("display","none");
                $("#loader_img_title div").remove();
            } else {
                $("#loader_img_title ."+id).remove();
                $("#loader_img_title").center();
            }
        };

return {
    show : _show,
    hide : _hide
};

}(jQuery);

function validateCodeReviews(control) {
    if (!$('#cr_anyone_field').is(':checked') && !$('#cr_3_favorites_field').is(':checked') && 
        !$('#cr_project_admin_field').is(':checked') && !$('#cr_job_runner_field').is(':checked') &&
        !$('#cr_users_specified_field').is(':checked')) {
        $('#cr_anyone_field').prop('checked', true);
        openNotifyOverlay('One selection must be checked', true);
    };
    if($(control).attr('id') == "cr_users_specified_field") {
        if($('#cr_users_specified_field').is(':checked')) {
            $('.code_review_chks').prop('checked', false);
        } 
    } else if ($(control).is(':checked')) {
        $('#cr_users_specified_field').prop('checked', false);
    }

};

$(document).ready(function() {
    populateProjectListing();

    $('.accordion').accordion({
        clearStyle: true,
        collapsible: true,
        active: true
    });

    // Validate code review input
    // @TODO: The :checkbox selector is too broad, we might
    // have additional checkboxes in the future..   - lithium
    $('.code_review_chks, #cr_users_specified_field').change(function(){
        validateCodeReviews(this);
    });

});

$(document).ready(function() {
    // get the project files
    $.ajax({
        type: 'post',
        url: 'jsonserver.php',
        data: {
            projectid: projectid,
            userid: user_id,
            action: 'getFilesForProject'
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                var images = data.data.images;
                var documents = data.data.documents;
                for (var i=0; i < images.length; i++) {
                    imageArray.push(images[i].fileid);
                }
                for (var i=0; i < documents.length; i++) {
                    documentsArray.push(documents[i].fileid);
                }
                var files = $('#uploadedFiles').parseTemplate(data.data);
                $('#uploadPanel').append(files);
                $('#accordion').fileUpload({images: imageArray, documents: documentsArray});
                $('#accordion').bind( "accordionchangestart", function(event, ui) {
                    $('#uploadButtonDiv').appendTo(ui.newContent);
                    $('#uploadButtonDiv').css('display', 'block');
                });
            }
        }
    });

    // Get the project runners
    getProjectRunners = function() {
        $('#projectRunners tbody').html('Loading ...');
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                action: 'getRunnersForProject'
            },
            dataType: 'json',
            success: function(data) {
                $('#projectRunners tbody').html('');
                $('#remove-runner tbody').html('');
                if (data.success) {
                    runners = data.data.runners;
                    var html = '';
                    if (runners.length > 0) {
                        for(var i=0; i < runners.length; i++) {
                            var runner = runners[i];
                            html =
                                '<tr class="runner row' + ((i+1) % 2 ? 'odd' : 'even') + '">' +
                                    ((is_admin || is_owner) ? '<td class="runnerRemove">' + (runner.owner ? '' : '<input type="checkbox" name="runner' + runner.id + '" />') + '</td>' : '') +
                                    '<td class="runnerName"><a href="userinfo.php?id=' + runner.id + '" target="_blank">' + runner.nickname + '</a></td>' +
                                    '<td class="runnerJobCount">' + runner.totalJobCount + '</td>' +
                                    '<td class="runnerLastActivity">' + (runner.lastActivity ? runner.lastActivity : '') + '</td>' +
                                '</tr>'
                            $('#projectRunners tbody').append(html);
                        }
                    }
                }
            }
        });
    }
    getProjectRunners();
    
    $("#addrunner-textbox").autocomplete({source: autocompleteUserSource});
    $('#addRunner-form').submit(function(event) {
        openNotifyOverlay('<span>Adding runner to your project...</span>', false);
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                nickname: $('.add-runner').val(),
                action: 'addRunnerToProject'
            },
            dataType: 'json',
            success: function(data) {
                $('.add-runner').val('');
                closeNotifyOverlay();
                openNotifyOverlay('<span>' + data.data + '<span>', true);
                if (data.success) {
                    getProjectRunners();
                    closeAddRunnerForm();
                }
            },
            error: function() {
                closeNotifyOverlay();
            }
        });
        
        return false;
    });

    $('#removerunner').click(function(event) {
        Utils.infoDialog('Removing Runner','Removing selected user(s) as Runner(s) for this project. ' +
            'If this user has active jobs for which they are the Runner, you will need to ' +
            'change the Runner status to an eligible Runner.' );
        
        var runners = '';
        $('#projectRunners input[name^=runner]:checked').each(function() {
            var runner = parseInt($(this).attr('name').substring(6));
            if (runners.length) runners += ';';{
            runners += '' + runner;
            }
        });
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                runners: runners,
                action: 'removeRunnersFromProject'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    getProjectRunners();
                }
            }
        });
        return false;
    });

    $("#addcodereviewer-textbox").autocomplete({source: autocompleteUserSource});
    // Get the project code reviewers
    getProjectCodeReviewers = function() {
        $('#projectCodeReviewRights tbody').html('Loading ...');
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                action: 'getCodeReviewersProject'
            },
            dataType: 'json',
            success: function(data) {
                $('#projectCodeReviewRights tbody').html('');
                if (data.success) {
                    codeReviewers = data.data.codeReviewers;
                    var html = '';
                    if (codeReviewers.length > 0) {
                        for(var i=0; i < codeReviewers.length; i++) {
                            var codeReviewer = codeReviewers[i];
                            html =
                                '<tr class="codeReviewer row' + ((i+1) % 2 ? 'odd' : 'even') + '">' +
                                    ((is_admin || is_owner) ? '<td class="codeReviewerRemove">' + (codeReviewer.owner ? '' : '<input type="checkbox" name="codereviewer' + codeReviewer.id + '" />') + '</td>' : '') +
                                    '<td class="codeReviewerName"><a href="userinfo.php?id=' + codeReviewer.id + '" target="_blank">' + codeReviewer.nickname + '</a></td>' +
                                    '<td class="codeReviewerJobCount">' + codeReviewer.totalJobCount + '</td>' +
                                    '<td class="codeReviewerLastActivity">' + (codeReviewer.lastActivity ? codeReviewer.lastActivity : '') + '</td>' +
                                '</tr>'
                            $('#projectCodeReviewRights tbody').append(html);
                        }
                    }
                }
            }
        });
    }
    getProjectCodeReviewers();

    $('#addcodereviewer-form').submit(function(event) {
        openNotifyOverlay('<span>Adding Code Reviewer to your project...</span>', false);
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                nickname: $('.add-codeReviewer').val(),
                action: 'addCodeReviewerToProject'
            },
            dataType: 'json',
            success: function(data) {
                $('.add-codeReviewer').val('');
                closeNotifyOverlay();
                openNotifyOverlay('<span>' + data.data + '<span>', true);
                if (data.success) {
                    getProjectCodeReviewers();
                    $('#add-codereviewer').dialog('close');
                }
            },
            error: function() {
                closeNotifyOverlay();
            }
        });
        
        return false;
    });
    
    $('#removecodereviewer').click(function(event) {
        openNotifyOverlay(
            '<span>Removing selected user(s) as Code reviewer(s) for this project. ', true);
        var codeReviewers = '';
        $('#projectCodeReviewRights input[name^=codereviewer]:checked').each(function() {
            var codeReviewer = parseInt($(this).attr('name').substring(12));
            if (codeReviewers.length) codeReviewers += ';';{
            codeReviewers += '' + codeReviewer;
            }
        });
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                projectid: projectid,
                codeReviewers: codeReviewers,
                action: 'removeCodeReviewersFromProject'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    getProjectCodeReviewers();
                }
            }
        });
        return false;
    });
    
    $("#testFlightButton").click(function() {
        showTestFlightForm(projectid);
    });

    makeWorkitemTooltip(".payment-joblink, .joblink");
    
    if ($("#projectLogoEdit").length > 0) {
        new AjaxUpload('projectLogoEdit', {
            action: 'jsonserver.php',
            name: 'logoFile',
            data: {
                action: 'logoUpload',
                projectid: projectid,
            },
            autoSubmit: true,
            responseType: 'json',
            onSubmit: validateUploadImage,
            onComplete: function(file, data) {
                $('span.LV_validation_message.upload').css('display', 'none').empty();
                if (!data.success) {
                    $('span.LV_validation_message.upload').css('display', 'inline').append(data.message);
                } else if (data.success == true ) {
                    $("#projectLogoEdit img").attr("src",data.url);
                    $('input[name=logoProject]').val(data.fileName);
                }
            }
        });
    }
    
    //derived from bids to show edit dialog when project owner clicks on a role <mikewasmike 16-jun-2011>
    $('tr.role').click(function() {
        $.metadata.setType("elem", "script");
        var roleData = $(this).metadata();

        // row has role data attached
        if(roleData.id){
            $('#popup-role-info input[name="role_id"]').val(roleData.id);
            $('#popup-role-info #info-title').text(roleData.role_title);
            $('#popup-role-info #info-percentage').text(roleData.percentage);
            $('#popup-role-info #info-min-amount').text(roleData.min_amount);
            //future functions to display more information as well as enable disable removal edition
            $('#popup-role-info').dialog('open');
        }
    });

    $('#editRole').click(function(){
        // row has role data attached
        $('#popup-role-info').dialog('close');
            $('#popup-edit-role input[name="role_id"]').val($('#popup-role-info input[name="role_id"]').val());
            $('#popup-edit-role #role_title_edit').val($('#popup-role-info #info-title').text());
            $('#popup-edit-role #percentage_edit').val($('#popup-role-info #info-percentage').text());
            $('#popup-edit-role #min_amount_edit').val($('#popup-role-info #info-min-amount').text());
            $('#popup-edit-role').dialog('open');
    });

    //popup for adding Project Runner and code reviewers
    $('#add-runner, #add-codereviewer').dialog({
        autoOpen: false,
        dialogClass: 'white-theme',
        resizable: false,
        modal: true,
        show: 'fade',
        hide: 'fade',
        width: 480,
        height: 200
    });
    
    $('#addrunner').click(function() {
        $('#add-runner').dialog('open');
    });
    
    $('#addcodereviewer').click(function() {
        $('#add-codereviewer').dialog('open');
    });
    
    //popup for removing Project Runner
    $('#remove-runner').dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        show: 'fade',
        hide: 'fade',
        width: 350,
        height: 450,
    });
    
    // new dialog for adding and editing roles <mikewasmike 16-jun-2011>
    $('#popup-addrole').dialog({ autoOpen: false, dialogClass: 'white-theme', modal: true, maxWidth: 600, width: 250, show: 'fade', hide: 'fade' });
    $('#popup-role-info').dialog({ autoOpen: false, dialogClass: 'white-theme', modal: true, maxWidth: 600, width: 350, show: 'fade', hide: 'fade' });
    $('#popup-edit-role').dialog({ autoOpen: false, dialogClass: 'white-theme', modal: true, maxWidth: 600, width: 250, show: 'fade', hide: 'fade' });

    $('#popup-testflight').dialog({ autoOpen: false, maxWidth: 600, width: 410, show: 'fade', hide: 'fade' });

    if (edit_mode) {
        $('#cancel_project_edit').click(function() {
            location.href = '?action=view';
            return false;
        });
    } else {
        $('#edit_project').click(function() {
            location.href = '?action=edit';
            return false;
        });
    }
});

function showAddRoleForm() {
    $('#popup-addrole').dialog('open');
    return false;
}

function showAddRunnerForm() {
    $('#add-runner').dialog('open');
    return false;
}

function closeAddRunnerForm() {
    $('#add-runner').dialog('close');
    return false;
}

function showTestFlightForm(project_id) {
    $('#popup-testflight').dialog('open');
    $('#popup-testflight .error').hide();
    $('#popup-testflight form').hide();
    $('#popup-testflight form #ipa-select input').remove();

    $.getJSON('api.php?action=testFlight&project_id=' + project_id, function(data) {
        $('#popup-testflight .loading').hide()
        if (data['error']) {
            $('#popup-testflight .error')
                .text(data['error'])
                .show();
        } else {
            $('#popup-testflight form #message').val(data['message']);
            $.each(data['ipaFiles'], function(index, value) {
                $('#popup-testflight form #ipa-select').append('<input type="radio" name="ipa" value="' + value + '" /> ' + value + '<br />');
            });
            $('#popup-testflight form #ipa-select input:first').prop('checked', true);
            $('#popup-testflight form').show();
            $('#popup-testflight .right-note').show();

            $('#popup-testflight form #submit_testflight').click(function() {
                var params = 'project_id=' + project_id + '&message=' + $('#popup-testflight form #message').val();
                params += "&ipa_file=" + $('#popup-testflight form #ipa-select input').val();
                params += "&notify="
                params += $('#popup-testflight form input[type=checkbox]').is(':checked');
                $.getJSON('api.php?action=testFlight&' + params, function(data) {
                    if (data == null) {
                        alert("There was an error with publishing to TestFlight. Please try again.");
                    } else if (data['error']) {
                        alert(data['error']);
                    }
                });
                $('#popup-testflight').dialog('close');
            });

        }
    });
    return false;
}


function populateProjectListing() {
    $.ajax({
        type: "GET",
        url: 'api.php?action=getProjects',
        dataType: 'json',
        success: function(json) {

            // Clear all contents on screen
            $('#projects').empty();

            for (var i = 0; i < json.length; i++) {
                addProjectDetails(json[i]);
            }

            setTimeout(function() {
                $('#projects').infinitescroll({
                    animate: true,
                    dataType: 'json',
                    debug: true,
                    appendCallback: false,
                    navSelector: '#page-nav',
                    nextSelector: '#page-nav a',
                    itemSelector: '#projects article',
                    extraScrollPx: 350,
                    loading: {
                        msgText: 'Loading the next set of projects...',
                        finishedMsg: 'No more pages to load'
                    }
                }, function(json, opts) {
                    for (var i = 0; i < json.length; i++) {
                        addProjectDetails(json[i]);
                    }
                });

                // kill scroll binding
                $(window).unbind('.infscr');

                // hook up the manual click guy.
                $('#all-projects').on('click', function(event) {
                    event.preventDefault();
                    $('#projects').infinitescroll('retrieve');
                    $(this).text('More');
                    return false;
                });
            }, 1);

            // remove the paginator when we're done.
            /*
            $(document).ajaxError(function(e, xhr, opt){
                if (xhr.status == 404) {
                    $('a#next').remove();
                }
            });
            */

        },
        error: function() {
            alert("error in populateProjectListing");
        }
    })
}

function addProjectDetails(json) {
    var project = '';
    var image_filename;
    var description = '';
    var link = '';

    link = encodeURIComponent(json.name);

    if (json.logo === null || json.logo === "") {
        image_filename = 'images/emptyLogo.png';
    } else {
        image_filename = 'uploads/' + json.logo;
    }

    if (json.description.length > 500) {
        description = json.description.substring(0, 500) + '... <a href="' + link + '">[ read more ]</a>';
    } else {
        description = json.description;
    }

    project += '<article id="project-' + json.project_id + '">';
    project += '<a href="' + link + '"><img src="' + image_filename + '" border="1" width="48" height="48"  title="Last commit: ' + json.last_commit + '" /></a>';

    project += '<h2><div><a href="' + link + '">' + json.name + '</a><span class="fading">&nbsp;</span></div></h2>';
    project += '<section class="description">' + description + '</section>';
    project += '<ul class="stats">';
    project += '<li><a href="./jobs?status=bidding&project=' + json.name + '"><strong>' + json.bCount + ' jobs in bidding</strong></a></li>';
    project += '<li><a href="./jobs?status=completed&project=' + json.name + '">' + json.cCount+ ' jobs completed</a></li>';
    project += '<li>$' + json.feesCount + ' spent</li>';
    project += '</ul>';
    project += '</article>';

    $('#projects').append(project);
}
