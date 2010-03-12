<?php ob_start(); 
//  vim:ts=4:et
//
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

include("config.php");
include("class.session_handler.php");
include("check_session.php");
include("functions.php");

$con=mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD);
mysql_select_db(DB_NAME,$con);

$userList = GetUserList($_SESSION['userid'], $_SESSION['nickname'], true);

$user = new User();
$user->findUserById($_SESSION['userid']);

/* Strip users already in the rewarderList */
$rewarderList = GetRewarderUserList($_SESSION['userid']);
foreach ($rewarderList as $info) {
    unset($userList[$info[0]]);
}


/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->

<link type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<script type="text/javascript">
    var rewarder = {
        availPoints: <?php echo $user->getRewarder_points() ?>,
        maxPoints: 0,
        container: null,
        usersContainer: null,
        chartContainer: null,
        chartWidth: 0,
        thumbWidth: 0,
        userHeight: 0,
        rewarderList: [],

        addNewUser: function(newUser, pos) {
            var span = $('<span></span>')
                .text(newUser[1]);
            var user = $('<h6 class="user'+newUser[0]+'"></h6>').append(span).css('top', pos * this.userHeight);
            this.usersContainer.append(user);

            if (rewarder.userHeight == 0) {
                rewarder.userHeight = user.find('span').outerHeight() + 4;
                user.css('top', pos * this.userHeight);
            }

            var userPoints = newUser[2]|0;

            var remover = $('<div class="chart-remover user'+newUser[0]+'"><div>')
                .data('userid', newUser[0])
                .css('top', pos * rewarder.userHeight)
                .click(function(){
                    rewarder.deleteRewarderUser($(this).data('userid'), 0);
                });
            this.chartContainer.append(remover);

            var thumb = $('<div class="chart-thumb">'+rewarder.getPointsText(newUser[2])+'</div>');
            chart = $('<div class="chart-bar user'+newUser[0]+'"></div>')
                .append(thumb)
                .data('userid', newUser[0])
                .css('top', pos * rewarder.userHeight)
                .css('height', rewarder.userHeight);
            this.chartContainer.append(chart);

            if (rewarder.thumbWidth == 0) rewarder.thumbWidth = thumb.outerWidth();

            rewarder.updateChart(chart, thumb, newUser[2]|0);

            return $(user, remover, chart);
        },

        /* Attach mouse events for capturing dragging of chart thumb.
         *
         * Note: this function called from a jQuery each() method, so 'this', counterintuitively, refers to a DOM
         *       element and not the rewarder object.
         */
        bindDragEvents: function(i) {
            var bar, thumb;
            var dragStartX, thumbStartX, startPoints;

            var capture = function(){
                $(document)
                    .mousemove(function(e){
                        if (!hasStarted) return;

                        var max = bar.width() - rewarder.thumbWidth + 1;
                        var left = Math.max(0, Math.min(max, thumbStartX + e.pageX - dragStartX));
                        var points = Math.min(bar.data('maxPoints'), (rewarder.maxPoints * (left / (rewarder.chartWidth - rewarder.thumbWidth)))|0);
                        thumb.css('left', left).text(rewarder.getPointsText(points - startPoints, true));
                        if (points - startPoints < 0) thumb.addClass('chart-negative');
                        else thumb.removeClass('chart-negative');

                        $('#rewarder-points').text(rewarder.getPointsText(rewarder.availPoints - (points - startPoints)));

                        bar.data('points', points)
                        return false;
                    })
                    .mouseup(function(e){
                        $(document).unbind();
                        thumb.removeClass('chart-negative');
                        rewarder.updateRewarderUser(bar.data('userid'), bar.data('points'));
                    });
            };

            $(this).unbind().mousedown(function(e){
                thumb = $(this);
                bar = $(this).parent();

                var pos = thumb.position();
                dragStartX = e.pageX;
                thumbStartX = pos.left;
                startPoints = bar.data('points');
                hasStarted = true;
                capture();
                return false;
            });
        },

        /*
         * combinedList has the structure:
         *   combinedList[ user_id ] = [ enum:{0=remove,1=keep,2=new}, posDelta, user ]
         */
        combineUserLists: function(oldList, newList) {
            var user_id;
            var combinedList = new Array();

            /* Fill with old users */
            for (var i = 0; i < oldList.length; i++) {
                user_id = oldList[i][0];
                combinedList[user_id] = new Array(0, i);
            }

            for (var i = 0; i < newList.length; i++) {
                user_id = newList[i][0];
                /* Existing users */
                if (combinedList[user_id] != undefined) {
                    combinedList[user_id][0] = 1;
                    combinedList[user_id][1] = i;
                /* Removed users */
                } else {
                    combinedList[user_id] = new Array(2, i, newList[i]);
                }
            }

            return combinedList;
        },

        deleteRewarderUser: function(userid){
            $.ajax({
                url: 'update-rewarder-user.php',
                data: 'id='+userid+'&delete=1',
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    rewarder.availPoints = json[0]|0;
                    $('#rewarder-points').text(rewarder.getPointsText(rewarder.availPoints));

                    rewarder.updateRewarderList(json[1]);
                }
            });
        },

        getMaxPoints: function (rewarderList) {
            var maxPoints = 0;
            for (var i = 0; i < rewarderList.length; i++){
                maxPoints = Math.max(maxPoints, rewarderList[i][2]);
            }
            return maxPoints;
        },

        getPointsText: function (points, includeSign) {
            var txt = '';
            if (includeSign && points > 0) txt += '+';
            txt += points + ' point';
            if (points != -1 && points != 1) txt += 's';
            return txt;
        },

        loadRewarderList: function() {
            $.ajax({
                url: 'get-rewarder-list.php',
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    rewarder.availPoints = json[0]|0;
                    $('#rewarder-points').text(rewarder.getPointsText(rewarder.availPoints));

                    rewarder.updateRewarderList(json[1]);
                }
            });
        },

        updateRewarderList: function(newRewarderList) {
            this.maxPoints = this.getMaxPoints(newRewarderList) + this.availPoints;

            if (!this.usersContainer) {
                this.container = $('#rewarder-container');
                this.usersContainer = $('#rewarder-users');
                this.chartContainer = $('#rewarder-chart');
                this.chartWidth = this.chartContainer.width() * .95;
            }

            /* If this is a fresh load of the rewarder list, just fill all the users. */
            if (rewarder.rewarderList.length == 0 && newRewarderList.length > 0) {
                for (var i = 0; i < newRewarderList.length; i++){
                    this.addNewUser(newRewarderList[i], i);
                }

            /* Real changes including add new users, remove out old users, reposition users. */
            } else {
                var combinedList = rewarder.combineUserLists(rewarder.rewarderList, newRewarderList);

                var animateFadeIn = function() { 
                    var j = 0;
                    for (var i in combinedList) {
                        if (combinedList[i][0] == 2) {
                            var user = rewarder.addNewUser(combinedList[i][2], combinedList[i][1]);
                            user.hide().fadeIn('slow');
                        }
                    }
                };

                var animateReposition = function() { 
                    var j = 0;
                    var fn = animateFadeIn;
                    for (var i in combinedList) {
                        if (combinedList[i][0] == 1) {
                            rewarder.container.find('.user'+i).each(function(){
                                $(this).animate({top: combinedList[i][1] * rewarder.userHeight}, 'slow', fn);
                                fn = null;
                            });
                        }
                    }

                    if (fn) animateFadeIn();
                }

                var animateFadeOut = function() { 
                    var j = 0;
                    var fn = animateReposition;
                    for (var i in combinedList) {
                        if (combinedList[i][0] == 0) {
                            rewarder.container.find('.user'+i).each(function(){
                                $(this).fadeOut('slow', fn).remove();
                                fn = null;
                            });
                        }
                    }

                    if (fn) animateReposition();
                };

                animateFadeOut();
            }

            /* Update points */
            for (var i = 0; i < newRewarderList.length; i++) {
                var chart = this.chartContainer.find('.chart-bar.user'+newRewarderList[i][0]);
                var thumb = chart.find('.chart-thumb');
                rewarder.updateChart(chart, thumb, newRewarderList[i][2]|0);
            }

            this.usersContainer.find('span')
                .unbind()
                .mouseenter(function(e){
                    var msg = 'Never'; 
                    $('#livetip')
                    .css({ top: e.pageY - 8, left: e.pageX + 12 })
                    .html('<div>' + msg + '</div>')
                    .show();
                    })
                .mouseleave(function(){
                    $('#livetip').hide();
                    });

            this.rewarderList = newRewarderList;
        },

        updateRewarderUser: function(userid, points){
            $.ajax({
                url: 'update-rewarder-user.php',
                data: 'id='+userid+'&points='+points,
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    rewarder.availPoints = json[0]|0;
                    $('#rewarder-points').text(rewarder.getPointsText(rewarder.availPoints));

                    rewarder.updateRewarderList(json[1]);
                }
            });
        },

        updateChart: function(chart, thumb, points){
            chart
                .data('points', points)
                .data('maxPoints', points + rewarder.availPoints);

            var width = ((rewarder.chartWidth - rewarder.thumbWidth) * ((points + rewarder.availPoints) / rewarder.maxPoints) + rewarder.thumbWidth)|0;
            chart.css('width', width);

            chart.find('.chart-thumb')
                .each(rewarder.bindDragEvents)
                .css('left', (rewarder.chartWidth - rewarder.thumbWidth) * (points / rewarder.maxPoints));

            thumb.text(rewarder.getPointsText(points));
        }

    };

    $(window).ready(function(){
        $('#user-list').change(function(){
            var userid = $(this).val();
            rewarder.updateRewarderUser(userid, 0);
            $(this).find('option:selected').remove();
        });
        rewarder.loadRewarderList();
    });
</script>

<title>Worklist | Rewarder</title>

</head>

<body>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->


    <h1>Rewarder</h1>

    <div id="rewarder-controls">
        <div id="rewarder-point-info">Your Rewarder balance is <span id="rewarder-points"><?php echo $user->getRewarder_points() ?> points</span></div>
        <div id="rewarder-team">
            <label for="user-list">Reward:</label>&nbsp;
            <select id="user-list" name="user-list">
                <option value="0">-- team member --</option>
                <?php foreach ($userList as $userid=>$nickname) { ?>
                <option value="<?php echo $userid ?>"><?php echo $nickname ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
    <div style="clear:both"></div>

    <div id="rewarder-container">
        <div id="rewarder-users"></div>
        <div id="rewarder-chart"></div>
    </div>
    <div style="clear:both"></div>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
