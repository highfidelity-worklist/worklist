<?php
/*
 * Copyright (c) 2011, LoveMachine Inc.
 * All Rights Reserved.
 *
 * http://www.lovemachineinc.com
 *
 * Development History:
 * 2011-07-30   #14907      Leo
 *
 */
error_reporting(E_ALL);
ob_start();
include("config.php");
include("class.session_handler.php");
include("check_new_user.php");
include("functions.php");

$userId = getSessionUserId();
$is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
$is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;

$selectedLetter = isset($_REQUEST['letter']) ? $_REQUEST['letter'] : "all";
$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;

/************************ HTML layout begins here  **************************/
include("head.html");
include("opengraphmeta.php");
?>
<title>Projects - Worklist: Develop software fast.</title>
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<link href="css/projects.css" rel="stylesheet" type="text/css" >
<link rel="shortcut icon" type="image/x-icon" href="images/worklist_favicon.png">

<script type="text/javascript" src="js/jquery.timeago.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript" src="js/ajaxupload.js"></script>
<script type="text/javascript" src="js/userstats.js"></script>
<script type="text/javascript" src="js/budget.js"></script>

<script type="text/javascript">
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
    var current_letter = '<?php echo $selectedLetter; ?>';
    var current_page = '<?php echo $currentPage ?>';
    var user_id = <?php echo isset($_SESSION['userid']) ? $_SESSION['userid'] : 0; ?>;
    var worklistUrl = '<?php echo SERVER_URL; ?>';

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
        populateProjectListing(current_page);
        $('.ln-letters a').click(function() {
            var classes = $(this).attr('class').split(' ');
            current_letter = classes[0];
            populateProjectListing(1);
            return false;
        });

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
    })
    function populateProjectListing(pageNumber) {
        selected_page = pageNumber;
        $.ajax({
            type: "GET",
            url: 'getprojects.php',
            data: 'page=' + selected_page + '&letter=' + current_letter,
            dataType: 'json',
            success: function(json) {

                $('.ln-letters a').removeClass('ln-selected');
                $('.ln-letters a.' + current_letter).addClass('ln-selected');

                var currentPage = json[0][1]|0;
                var totalPages = json[0][2]|0;

                // Clear all contents on screen
                $('.projectDetailsRow').remove();

                if (json.length > 1) {
                    $('#projectListing').show();
                    $('#errorMessage').hide();
                } else {
                    $('#projectListing').hide();
                    $('#errorMessage').show();
                }

                for (var i = 1; i < json.length; i++) {
                    addProjectDetails(json[i]);
                }

                if (totalPages > 1) { //showing pagination only if we have more than one page
                    $('.ln-pages').html('<span>' + outputPagination(currentPage, totalPages) + '</span>');
                    $('.ln-pages a').click(function() {
                        page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
                        populateProjectListing(page);
                        return false;
                    });
                } else {
                    $('.ln-pages').html('');
                }
            },
            error: function() {
                alert("error in populateProjectListing");
            }
        })
    }
    function addProjectDetails(json) {
        var row;
        var image_filename;
        if (json.logo === null || json.logo === "") {
            image_filename = 'images/emptyLogo.png';
        } else {
            image_filename = 'uploads/' + json.logo;
        }
        row = '<tr class="projectDetailsRow projectID_' + json.project_id + '">';
        row += '<td class="projectLogo"><img src="' + image_filename + '" border="1" width="48" height="48"  title="Last commit: ' +
                json.last_commit + '" /></td>';
        row += '<td class="projectDescription"><h2><a href="' + encodeURIComponent(json.name) +'">' + json.name + '</a></h2>';
        row += '<span class="descriptionText" title="Last commit: ' + json.last_commit + '" >';
        if (json.description.length > 500) {
            row += json.description.substring(0,500) + '... <a href="' + encodeURIComponent(json.name) + '">[ read more ]</a>';
        } else {
            row += json.description;
        }
        row += '</span></td>';
        row += '<td class="projectStats"><a href="./worklist.php?status=bidding&project=' + json.name + '" class="iToolTip jobsBidding actionBidding">'
                + json.bCount + ' jobs </a>bidding <br/><a href="./worklist.php?status=underway&project=' + json.name + '" class="iToolTip jobsBidding actionUnderway" >'
                + json.uCount + ' jobs </a>underway<br/><a href="./worklist.php?status=completed&project=' + json.name + '" class="iToolTip jobsBidding actionCompleted" >'
                + json.cCount + ' jobs </a>completed<br/><span>$'
                + json.feesCount + '</span> Total</td>';
        row += '</tr>';
        $('#projectListing tbody').append(row);
    }
</script>
<style>
#welcomeInside .projectsBtn {
    color: #ffffff;
}
</style>
</head>

<body>
<?php
    require_once('header.php');
    require_once('format.php');
?>
    <link rel="shortcut icon" type="image/x-icon" href="images/worklist_favicon.png">
    <h1 class="headerTitle">Worklist Projects</h1>
    <div class="headerButtons">
        <input id="addproj" type="button" value="Add my project" />
    </div>
        <!-- Popup for breakdown of fees-->
        <?php require_once('dialogs/popup-fees.inc') ?>
        <!-- Popup for adding projects -->
        <?php require_once('dialogs/popup-addproject.inc'); ?>
        <!-- Popup for budget info -->
        <?php require_once('dialogs/budget-expanded.inc'); ?>
        <!-- Popup for transfered info -->
        <?php require_once('dialogs/budget-transfer.inc') ?>
        <!-- Popup for budget info-->
        <?php require_once('dialogs/popup-budget.inc'); ?>
    <div class="headerText">
            Worklist allows you to rapidly build software using a global community of developers, designers, and project managers.
    </div>
    <div style="clear:both;"></div>
    <div id="errorMessage">No projects matched your selection</div>
    <table id="projectListing">
        <tbody>
        </tbody>
    </table>
    <div class="ln-letters"><a href="#" class="all ln-selected">ALL</a><a href="#" class="_">0-9</a><a href="#" class="a">A</a><a href="#" class="b">B</a><a href="#" class="c">C</a><a href="#" class="d">D</a><a href="#" class="e">E</a><a href="#" class="f">F</a><a href="#" class="g">G</a><a href="#" class="h">H</a><a href="#" class="i">I</a><a href="#" class="j">J</a><a href="#" class="k">K</a><a href="#" class="l">L</a><a href="#" class="m">M</a><a href="#" class="n">N</a><a href="#" class="o">O</a><a href="#" class="p">P</a><a href="#" class="q">Q</a><a href="#" class="r">R</a><a href="#" class="s">S</a><a href="#" class="t">T</a><a href="#" class="u">U</a><a href="#" class="v">V</a><a href="#" class="w">W</a><a href="#" class="x">X</a><a href="#" class="y">Y</a><a href="#" class="z ln-last">Z</a></div>
    <div class="ln-pages"></div>
<?php
//-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
include("footer.php");
?>
