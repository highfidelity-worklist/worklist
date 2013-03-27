/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 *
 * Development History:
 * 2011-07-30   #14907      Leo
 *
 */

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

    function validateCodeReviews() {
        if (!$('.cr_anyone_field').is(':checked') && !$('.cr_3_favorites_field').is(':checked') && !$('.cr_project_admin_field').is(':checked') && !$('.cr_job_runner_field').is(':checked')) {
            $('.cr_anyone_field').prop('checked', true);
            $('#edit_cr_error').html("One selection must be checked");
            $('#edit_cr_error').fadeIn();
            $('#edit_cr_error').delay(2000).fadeOut();
        };
        if (!$('.cr_anyone_field_ap').is(':checked') && !$('.cr_3_favorites_field_ap').is(':checked') && !$('.cr_project_admin_field_ap').is(':checked') && !$('.cr_job_runner_field_ap').is(':checked')) {
            $('.cr_anyone_field_ap').prop('checked', true);
            $('#edit_cr_error_ap').html("One selection must be checked");
            $('#edit_cr_error_ap').fadeIn();
            $('#edit_cr_error_ap').delay(2000).fadeOut();
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
        $(':checkbox').change(function() {
            validateCodeReviews();
        });

    });

    function populateProjectListing() {
        $.ajax({
            type: "GET",
            url: 'getprojects.php',
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

        project += '<h2><a href="' + link + '">' + json.name + '</a></h2>';
        project += '<section class="description">' + description + '</section>';
        project += '<ul class="stats">';
        project += '<li><a href="worklist.php?status=bidding&project=' + json.name + '"><strong>' + json.bCount + ' jobs in bidding</strong></a></li>';
        project += '<li><a href="./worklist.php?status=completed&project=' + json.name + '">' + json.cCount+ ' jobs completed</a></li>';
        project += '<li>$' + json.feesCount + ' spent</li>';
        project += '</ul>';
        project += '</article>';

        $('#projects').append(project);
    }