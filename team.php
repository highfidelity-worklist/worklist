<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

ob_start();
include("config.php");
include("class.session_handler.php");
include("check_new_user.php");
include("functions.php");

$cur_letter = isset( $_POST['letter'] ) ? $_POST['letter'] : "all";
$cur_page = isset( $_POST['page'] ) ? intval($_POST['page'] ) : 1;

$sfilter = !empty( $_POST['sfilter'] ) ? $_POST['sfilter'] : 'PAID';
$userId = getSessionUserId();
if( $userId > 0 )   {
    initUserById($userId);
    $user = new User();
    $user->findUserById( $userId );
    $nick = $user->getNickname();
    $userbudget =$user->getBudget();
    $budget = number_format($userbudget);
}

$newStats = UserStats::getNewUserStats();
/*********************************** HTML layout begins here  *************************************/

include("head.html");
include("opengraphmeta.php");
?>

<title>Team Members - Worklist: Develop software fast.</title>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/teamnav.css" rel="stylesheet" type="text/css">
<link href="css/favorites.css" rel="stylesheet" type="text/css" >

<script type="text/javascript" src="js/jquery.timeago.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript">
var current_letter = '<?php echo $cur_letter; ?>';
var runner =  <?php echo !empty($_SESSION['is_runner']) ? 1 : 0; ?>;
var current_page = <?php echo $cur_page; ?>;
var current_sortkey = 'earnings30';
var current_order = false;
var sfilter = '30'; // Default value for the filter
var show_actives = "FALSE";
var show_myfavorites = "FALSE";

$(document).ready(function() {

// Set the users with fees in X days label
    $('.select-days').html(sfilter + ' days');

    getFavoriteUsers();

    fillUserlist(current_page);

    $('.ln-letters a').click(function(){
        var classes = $(this).attr('class').split(' ');
        current_letter = classes[0];
        fillUserlist(1);
        return false;
    });

    //table sorting thing
    $('.table-userlist thead tr th').hover(function(e){
        if(!$('div', this).hasClass('show-arrow')){
            if($(this).data('direction')){
                $('div', this).addClass('arrow-up');
            }else{
                $('div', this).addClass('arrow-down');
            }
        }
    }, function(e){
        if(!$('div', this).hasClass('show-arrow')){
            $('div', this).removeClass('arrow-up');
            $('div', this).removeClass('arrow-down');
        }
    });

    $('.table-userlist thead tr th').data('direction', false); //false == desc order
    $('.table-userlist thead tr th').click(function(e){
        $('.table-userlist thead tr th div').removeClass('show-arrow');
        $('.table-userlist thead tr th div').removeClass('arrow-up');
        $('.table-userlist thead tr th div').removeClass('arrow-down');
        $('div', this).addClass('show-arrow');
        var direction = $(this).data('direction');

        if(direction){
            $('div', this).addClass('arrow-up');
        }else{
            $('div', this).addClass('arrow-down');
        }

        var data = $(this).metadata();
        if (!data.sortkey) return false;
        current_sortkey = data.sortkey;
        current_order = $(this).data('direction');
        fillUserlist(current_page);

        $('.table-userlist thead tr th').data('direction', false); //reseting to default other rows
        $(this).data('direction',!direction); //switching on current
    }); //end of table sorting

    /**
     * Enable filter for users with fees in the last X days
     */
    $('#filter-by-fees').click(function() {
        current_page = 1;
        if (show_actives == "FALSE") {
            show_actives = "TRUE";
            fillUserlist(current_page);
        } else {
            show_actives = "FALSE";
            fillUserlist(current_page);
        }
    });

    /**
     * Select users with fees in XX days
     */
    $('#days').change(function() {
        // Set the days filter
        sfilter = $('#days option:selected').val();

        // If the filter is active reload the list
        if (show_actives === "TRUE") {
            fillUserlist(current_page);
        }
    });

    /**
     * Enable filter for my favorite users
     */
    $('#filter-by-myfavorite').click(function() {
        current_page = 1;
        if (show_myfavorites == "FALSE") {
            show_myfavorites = "TRUE";
            fillUserlist(current_page);
        } else {
            show_myfavorites = "FALSE";
            fillUserlist(current_page);
        }
    });

    $("#query").autocomplete({
        minLength: 0,
        source: function(request, response) {
            $.ajax({
                cache: false,
                url: 'getuserslist.php',
                data: {
                    startsWith: request.term,
                },
                dataType: 'json',
                success: function(users) {
                    response($.map(users, function(item) {
                        return {
                            id: item.id,
                            nickname: item.nickname,
                        }
                    }));
                }
            });
        },
        focus:function(event, ui) {
            return false;
        },
        select:function(event, ui) {
            $("#query").val("");
            $("#search_user-id").val(ui.item.id);
            window.open('userinfo.php?id=' + ui.item.id, '_blank');

            return false;
        }
    }).data("autocomplete")._renderItem = function(ul, item) {
        return $("<li></li>")
            .data("item.autocomplete", item)
            .append("<a>" + item.nickname + "</font></a>").appendTo(ul);
    }
<?php
    if( !empty($_REQUEST['showUser'])) {
        $tab = "";
        if( !empty($_REQUEST['tab'])) {
            $tab = "&tab=" . $_REQUEST['tab'];
        }
        echo "window.open('userinfo.php?id=" . $_REQUEST['showUser'] . $tab . "', '_blank')";
    }
?>
    
    $("#query").DefaultValue("Search team...");
    $('#days').comboBox();
});

function fillUserlist(npage) {
    current_page = npage;
    var order = current_order ? 'ASC' : 'DESC';
    $.ajax({
        type: "POST",
        url: 'getuserlist.php',
        data: 'letter=' + current_letter + '&page=' + npage + '&order=' + current_sortkey + '&order_dir=' + order + '&sfilter=' + sfilter + '&active=' + show_actives + '&myfavorite=' + show_myfavorites,
        dataType: 'json',
        success: function(json) {

            $('.ln-letters a').removeClass('ln-selected');
            $('.ln-letters a.' + current_letter).addClass('ln-selected');

            var page = json[0][1]|0;
            var cPages = json[0][2]|0;

            $('.row-userlist-live').remove();

            if (json.length > 1) {
                $('.table-hdng').show();
                $('#message').hide();
            }else{
                $('.table-hdng').hide();
                $('#message').show();
            }

            var odd = true;
            for (var i = 1; i < json.length; i++) {
                AppendUserRow(json[i], odd);
                odd = !odd;
            }

            $('tr.row-userlist-live').click(function(){
                var match = $(this).attr('class').match(/useritem-\d+/);
                var userid = match[0].substr(9);
                window.open('userinfo.php?id=' + userid, '_blank');
                return false;
            });

            if (cPages > 1) { //showing pagination only if we have more than one page
                $('.ln-pages').html('<span>' + outputPagination(page, cPages) + '</span>');
                $('.ln-pages a').click(function() {
                    page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
                    fillUserlist(page);
                    return false;
                });
            } else {
                $('.ln-pages').html('');
            }
        },
        error: function(xhdr, status, err) {}
    });
}

function AppendUserRow(json, odd) {
    var row;
    var pre = '';
    var post = '';

    var is_myfavorite = $.inArray(json.id, favoriteUsers) != -1;
    var favorite_div = '<div class="favorite_user myfavorite" title="Remove ' + json.nickname + ' as someone you trust. (don\'t worry it\'s anonymous)">&nbsp;</div>';

    row = '<tr class="row-userlist-live ';
    if (odd) {
        row += 'rowodd';
    } else {
        row += 'roweven';
    }

    if (is_myfavorite) {
        row += ' favorite';
    }

    row += ' useritem-' + json.id + '">';
    row += '<td class="name-col">' + (is_myfavorite ? favorite_div : '') + json.nickname + '</td>';
    row += '<td class="age">'+ json.joined + '</td>';
    row += '<td class="jobs money moneyPadding">' + json.jobs_count + '</td>';
    row += '<td class="money moneyPadding">' + json.budget + '</td>';
    row += '<td class="money moneyPadding">$' +addCommas(json.earnings.toFixed(2)) + '</td>';
    row += '<td class="money moneyPadding">$' + addCommas(json.earnings30) + '</td>';
    row += '<td class="money moneyPadding">(' + (Math.round((parseFloat(json.rewarder) / (parseFloat(json.earnings) + 0.000001)) * 100*100)/100)+ '%) $' + addCommas(json.rewarder) +  '</td>';
    $('.table-userlist tbody').append(row);

    var favorite_user_id = json.id;
    var favorite_user_nickname = json.nickname;
    if (is_myfavorite) {
        $('tr.favorite.useritem-' + favorite_user_id + ' .favorite_user.myfavorite').click(function (e) {
            if (!confirm('Are you sure you want to remove ' + favorite_user_nickname + ' as someone you trust?')) {
                return false;
            }
            $.ajax({
                type: 'POST',
                url: 'favorites.php',
                data: {
                    action: 'setFavorite',
                    favorite_user_id: favorite_user_id,
                    newVal: 0
                },
                dataType: 'json',
                success: function(json) {
                    if ((json === null) || (json.error)) {
                        var message="Error returned - f1. ";
                        if (json !== null) {
                            message = message + json.error;
                        }
                        alert(message);
                        return;
                    }
                }
            });
            getFavoriteUsers();
            fillUserlist(current_page);
            return false;
        });
    }
}

function addCommas(nStr) {
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}

</script>
<style>
#welcomeInside .teamBtn {
    color: #ffffff;
    background-position: -356px -119px;
}
</style>
</head>

<body id="team">
<!-- Popup for breakdown of fees-->
<?php require_once('dialogs/popup-fees.inc') ?>
<?php
    require_once('header.php');
    require_once('format.php');
?>
<!-- Popup for transfered info -->
<?php require_once('dialogs/budget-transfer.inc') ?>
<!-- Popup for budget info -->
<?php require_once('dialogs/budget-expanded.inc'); ?>
<!-- Popup for Budget -->
<?php require_once('dialogs/popup-budget.inc'); ?>

<h1>Team Members</h1>
<div id="newUserStats">
    <h2>New user statistics:</h2>
    <p>
        <span>In the past 30 days</span><br /> 
        <?php echo $newStats['newUsers'] == 0 ? 'no' : $newStats['newUsers']; ?> user<?php echo $newStats['newUsers'] > 1 ? 's have' : ' has' ?> signed up,<br />
        <?php echo $newStats['newUsersLoggedIn'] == 0 ? 'no one' : $newStats['newUsersLoggedIn']; ?> <?php echo $newStats['newUsersLoggedIn'] > 1 ? 'have' : ' has' ?> logged in,<br />
        <?php echo $newStats['newUsersWithFees'] == 0 ? 'no one' : $newStats['newUsersWithFees']; ?> <?php echo $newStats['newUsersWithFees'] > 1 ? 'have' : ' has' ?> added fees &amp;<br />
        <?php echo $newStats['newUsersWithBids'] == 0 ? 'no one' : $newStats['newUsersWithBids']; ?> <?php echo $newStats['newUsersWithBids'] > 1 ? 'have' : ' has' ?> added bids
    </p>
</div>
<div id="leftcol">
    <div id="searchbar">
        <div id="search_user_box">
            <div id="search-filter-wrap">
                <div>
                    <div class="input_box">
                        <div class="searchDiv">
                            <input type="text" id="query" name="query" alt="Search team..." size="20" value="" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="filters_box">
            <div class="myfavorite-users" <?php echo (getSessionUserId() == 0 ? 'style="display: none"' : ''); ?>>
                <input type="checkbox" id="filter-by-myfavorite" />
                <label for="filter-by-myfavorite">Trusted by Me</label>
            </div>
            <div class="active-users">
                <div>
                    <input type="checkbox" id="filter-by-fees" />
                    <label for="filter-by-fees">Has fees in the last</label>
                </div>
                <select name="days" id="days">
                    <option value="7">7 days</option>
                    <option value="30" selected="selected">30 days</option>
                    <option value="60">60 days</option>
                    <option value="90">90 days</option>
                    <option value="360">1 year</option>
                </select>
            </div>
        </div>
    </div>
    <div id="message">No results</div>
    <table class="table-userlist" style="width:100%">
        <thead>
            <tr class="table-hdng">
                <th class="sort {sortkey: 'nickname'} clickable">Nickname<div class = "arrow"><div/></th>
                <th class="sort {sortkey: 'added'} clickable age">Age<div class = "arrow"><div/></th>
                <th class="sort {sortkey: 'jobs_count'} clickable jobs">Jobs<div class = "arrow"><div/></th>
                <th class="sort {sortkey: 'budget'} clickable money">Budget<div class = "arrow"><div/></th>
                <th class="sort {sortkey: 'earnings'} clickable money">Total Earnings<div class = "arrow"><div/></th>
                <th class="sort {sortkey: 'earnings30'} clickable money">30 Day Earnings<div class = "arrow"><div/></th>
                <th class="sort {sortkey: 'rewarder'} clickable money">(%) Bonus $<div class = "arrow"><div/></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    <div class="ln-letters">
        <a href="#" class="all ln-selected">ALL</a>
        <a href="#" class="_">0-9</a>
        <a href="#" class="a">A</a>
        <a href="#" class="b">B</a>
        <a href="#" class="c">C</a>
        <a href="#" class="d">D</a>
        <a href="#" class="e">E</a>
        <a href="#" class="f">F</a>
        <a href="#" class="g">G</a>
        <a href="#" class="h">H</a>
        <a href="#" class="i">I</a>
        <a href="#" class="j">J</a>
        <a href="#" class="k">K</a>
        <a href="#" class="l">L</a>
        <a href="#" class="m">M</a>
        <a href="#" class="n">N</a>
        <a href="#" class="o">O</a>
        <a href="#" class="p">P</a>
        <a href="#" class="q">Q</a>
        <a href="#" class="r">R</a>
        <a href="#" class="s">S</a>
        <a href="#" class="t">T</a>
        <a href="#" class="u">U</a>
        <a href="#" class="v">V</a>
        <a href="#" class="w">W</a>
        <a href="#" class="x">X</a>
        <a href="#" class="y">Y</a>
        <a href="#" class="z ln-last">Z</a>
    </div>
    <div class="ln-pages"></div>
</div>
<?php
include("footer.php");?>
