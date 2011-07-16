<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

require_once('functions.php');
?>

<div id="outside"> 

<!-- Welcome, login/out -->

    <div id="welcome">
        <?php if ( isset($_SESSION['username'])) {
            $return_from_getfeesums = true;
            include 'getfeesums.php';
            $feeinfo = '<div style="display:none;" id="feesDialog"><table><tr><td><b>Your fees: </b></td></tr><tr><td>this week:</td><td><a href="#feesToolTip" class="feesum" id="fees-week">$'.$sum['week'].
                        '</a></td></tr><tr><td>this month:</td><td><a href="#feesToolTip" class="feesum" id="fees-month">$'.$sum['month'].'</a> </td></tr></table></div>';
            $earnings = ' | <a href="javascript:;" class="earnings">Earnings</a> ';
            if ( isset($_SESSION['is_runner'])) {
                 $budget = ' | <a href="javascript:;" class="budget">Budget</a> ';
            
             } else {
                 $budget = '<span class="budget"></span>';
             }
            if (empty($_SESSION['nickname'])){ 
                $name = getSubNickname($_SESSION['username']);
            } else {
                $name = getSubNickname($_SESSION['nickname']);
            }
            $status = '<span id="status-wrap" style="width:340px;">
                <form action="" style="display:inline" id="status-update-form" style="width:340px;">' . $name .' is <span id="status-lbl"></span>
                    <input style="display: none;" type="text" maxlength="45" id="status-update" name="status-update"
                        value=""></input>
                    <span id="status-share" style="display: none;  width:122px;">
                        <input type="submit" value="Share" id="status-share-btn"></input>
                    </span>
                </form>
            </span>' ;
            echo "Welcome, <span id='user'> $name </span>  $earnings $budget | <a href='logout.php'>Logout</a> | $status";
            echo $feeinfo;  
        } ?>
        
        <div id="tagline">Fast pay for your work, <a href="http://svn.sendlove.us/" target="_blank">open codebase</a>, great community.</div>       
    </div>
    
    <!-- Inline Message Container -->
    <div id="inlineMessage"></div>
    
    <?php if ( basename($_SERVER['PHP_SELF']) == 'worklist.php' && (array_key_exists('inlineHide',$_SESSION) && $_SESSION['inlineHide'] == 0) ) { ?>
    
    <script type="text/javascript">
    
    // html needed for welcome message
    var welcomeHTML = '<p><span class="inlineWelcome">Welcome to Worklist!</span></p>'+
    '<p>Browse the list of jobs below or click on <a href="<?php echo SERVER_BASE ?>/journal/" class="iToolTip menuJournal">Journal</a> to join our chat.</p>'+
    '<input type="submit" id="hideMessage" name="hideMessage" value="Hide this Message" />'+
    '<div id="inlineSource"><a href="http://svn.sendlove.us/" target="_blank">Download our source code</a></div>'
    
    // call the addInlineMessage function to show the inline message for new users
    addInlineMessage(welcomeHTML);
    
    // code for button to hide inline message
    $('#hideMessage').click(function(){
        $('#inlineMessage').fadeOut(750);
    });
    
    </script>
    
    <?php } ?>
    
    
    <div id="container">
        <div id="left"></div>
        

<!-- MAIN BODY -->
        <div id="center">

<!-- LOGO -->
            <div id="stats">
                <span id='stats-text'>
                    <a href='./worklist.php?status=bidding' class='iToolTip jobsBidding actionBidding' ><span id='count_b'></span> jobs</a>
                    bidding, 
                    <a href='./worklist.php?status=underway' class='iToolTip jobsBidding actionUnderway' ><span id='count_w'></span> jobs</a>
                    underway
                </span>
            </div>

<!-- Navigation placeholder -->
        <div id="nav">
        <?php if (isset($_SESSION['username'])) { ?>

            <a href="worklist.php" class="iToolTip menuWorklist">Worklist</a> |
            <a href="<?php echo SERVER_BASE ?>/journal/" class="iToolTip menuJournal">Journal</a> |
            <a href="reports.php" class="iToolTip menuReports">Reports</a> |
            <a href="team.php">Team</a> |
            <a href="settings.php" class="iToolTip menuSettings">Settings</a> |
            <a href="#" id="addproj" name="addproj" class="iToolTip addProj addproj">Add Project</a>
            <?php } else {
            echo '<a href="login.php" title="Login to our Worklist">Login</a> | <a href="signup.php" title="Signup For a New Account"> Signup Now</a> | <a href="../journal" title="Login to our Live Chat Journal"> Live Chat Journal</a>';
            
        } 
            ?>
        </div>

        <script type="text/javascript">
        //Code for Add Project
        $(document).ready(function() {
            $('#addproj').click(function() {
                $('#popup-addproject').dialog({ 
                    autoOpen: false, 
                    show: 'fade', 
                    hide: 'fade',
                    maxWidth: 600, 
                    width: 415,
                    resizable: false
                });
                $('#popup-addproject').data('title.dialog', 'Add Project');
                $('#popup-addproject').dialog('open');

                // clear the form
                $('input[type="text"]', '#popup-addproject').val('');
                $('textarea', '#popup-addproject').val('');
                $('.LV_validation_message', '#popup-addproject').hide();

                // focus the submit button
                $('#save_project').focus();

                var optionsLiveValidation = { onlyOnSubmit: true };

                var project_name = new LiveValidation('name', optionsLiveValidation);
                var project_description = new LiveValidation('description', optionsLiveValidation);
                var project_repository = new LiveValidation('repository', optionsLiveValidation);
                project_name.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                project_description.add( Validate.Presence, { failureMessage: "Can't be empty!" });

                project_name.add(Validate.Format, { failureMessage: 'Alphanumeric, - or _ characters only', pattern: new RegExp(/^[A-Za-z0-9 _-]*$/) });
                project_name.add(Validate.Length, { minimum: 3, tooShortMessage: "Field must contain 3 characters at least!" } );
                project_name.add(Validate.Length, { maximum: 32, tooLongMessage: "Field must contain 32 characters at most!" } );

                project_repository.add(Validate.Format, { failureMessage: 'Alphanumeric, - or _ characters only. No spaces', pattern: new RegExp(/^[A-Za-z0-9_-]*$/) });
                project_repository.add(Validate.Length, { minimum: 3, tooShortMessage: "Field must contain 3 characters at least!" } );
                project_repository.add(Validate.Length, { maximum: 32, tooLongMessage: "Field must contain 32 characters at most!" } );

                $('#cancel').click(function() {
                    $('#popup-addproject').dialog('close');
                });

                $('#save_project').click(function() {
                    massValidation = LiveValidation.massValidate( [ project_name, project_description, project_repository ]);   
                    if (!massValidation) {
                        return false;
                    }
                    addForm = $("#popup-addproject");
                    $.ajax({
                        url: 'addproject.php',
                        dataType: 'json',
                        data: {
                            name: $(':input[name="name"]', addForm).val(),
                            description: $(':input[name="description"]', addForm).val(),
                            repository: $(':input[name="repository"]', addForm).val()
                        },
                        type: 'POST',
                        success: function(json){
                            if ( !json || json === null ) {
                                alert("json null in addproject");
                                return;
                            }
                            if ( json.error ) {
                                alert(json.error);
                            } else {
                                $('#popup-addproject').dialog('close');
                                window.location.href = '<?php echo SERVER_URL ; ?>' + $(':input[name="name"]', addForm).val();
                            }
                        }
                    });
                
                    return false;
                });
            });
        });

        // Code for stats
        $(function() {
            $('#popup-user-info').dialog({ autoOpen: false, show: 'fade', hide: 'fade'});
            $.ajax({
                type: "POST",
                url: 'getstats.php',
                data: 'req=currentlink',
                dataType: 'json',
                success: function(json) {
                    if (!json || json === null) return;
                    $("#count_b").text(json.count_b);
                    $("#count_w").text(json.count_w);
                    $('#stats-text').show();
                    MapToolTips();
                }
            });
        });
        function ShowStats()    {
            // Clear the tables
            $('.row').remove();
            $('.runrow').remove();
            $('.mecrow').remove();
            $('.feerow').remove();
            $('.fee7row').remove();
            $('.fee30row').remove();
            $('.pastrow').remove();
            $('.visitrow').remove();

            // Set loading text and image
            $('.table-visited-list').append("<tr class='visitrow'><td style='text-align:center; vertical-align:middle;' colspan='2'><img src='images/loader.gif'></img></td></tr>");
            $('.table-fees-list').append("<tr class='fee30row'><td style='text-align:center; vertical-align:middle;' colspan='7'><img src='images/loader.gif'></img></td></tr>");
            $('.table-fees-list7').append("<tr class='fee7row'><td style='text-align:center; vertical-align:middle;' colspan='7'><img src='images/loader.gif'></img></td></tr>");
            $('.table-runners').append("<tr class='runrow'><td style='text-align:center; vertical-align:middle;' colspan='3'><img src='images/loader.gif'></td></tr>");
            $('.table-mechanics').append("<tr class='mecrow'><td style='text-align:center; vertical-align:middle;' colspan='3'><img src='images/loader.gif'></td></tr>");
            $('.table-fee-adders').append("<tr class='feerow'><td style='text-align:center; vertical-align:middle;' colspan='3'><img src='images/loader.gif'></td></tr>");
            $('.table-past-due').append("<tr class='pastrow'><td style='text-align:center;  vertical-align:middle;' colspan='2'><img src='images/loader.gif'></td></tr>");

            // From here on we load all the data
            $.ajax({
                type: "GET",
                url: 'visitQuery.php',
                data: 'jobid=0',
                dataType: 'json',
                success: function(json) {
                    $.bidvisits = {};
                    $.bidvisits.visits = json;
                    $.bidvisits.count = json.length;
                    if ($.bidvisits.count > 0) {
                        $.ajax({
                            type: "POST",
                            url: 'getstats.php',
                            data: 'req=bidding',
                            dataType: 'json',
                            success: function(json) {
                                $.bidvisits.jobs = json;
                                var bidcount = 0;
                                var otherTotal = 0;
                                for ( var i = 0; i < $.bidvisits.count; i++) {
                                    var current = $.bidvisits.visits[i];
                                    var job = current.job;
                                    if($.inArray(job, $.bidvisits.jobs) != -1) {
                                        bidcount++
                                        if (bidcount <= 10) {
                                            var href = current.url;
                                            var visitCount = current.visits;
                                            var row = '<tr class="row"><td><a href="' + href + '">#' + job + '</a></td>';
                                            row += '<td>' + visitCount + '</td></tr>';
                                            $('.table-visited-list').append(row);
                                        } else {
                                            otherTotal += parseInt(current.visits);
                                        }
                                    }
                                }
                                if (bidcount > 10) {
                                    var row = '<tr class="row"><td>Other</td>';
                                    row += '<td>' + otherTotal + '</td></tr>';
                                    $('.table-visited-list').append(row);
                                }
                            }
                        });
                    }
                },
                complete: function() {
                    $('.visitrow').remove();
                }
            });

            // Load the bids and works labels
            $.ajax({
                type: "POST",
                url: 'getstats.php',
                data: 'req=current',
                dataType: 'json',
                success: function(json) {
                    $('#span-bids').html(json[0]);
                    $('#span-work').html(json[1]);
                    // Get average fees
                    $.ajax({
                        type: "POST",
                        url: 'getstats.php',
                        data: 'req=fees',
                        dataType: 'json',
                        success: function(json) {
                            var data = json['AVG(amount)'];
                            var shorted = Math.round(data*100)/100;
                            $('#span-fees').html('$' + shorted);
                        }
                    });
                }
            });

            // Get last completed jobs in last 30 days
            $.ajax({
                type: "POST",
                url: 'getstats.php',
                data: 'req=feeslist&interval=30',
                dataType: 'json',
                success: function(json) {
                    $('.fee30row').remove();
                    var fees = json;
                    var rowCount = fees.length;
                    for ( var i = 0; i < rowCount; i++ )    {
                        var user = fees[i][0];
                        var funct = "javascript:ShowUserInfo('" + user + "');";
                        var row = '<tr class="row"><td onclick="' + funct + '">' + user + '</td>';
                        row += '<td>$' + fees[i][1] + '</td>';
                        row += '<td>' + fees[i][2] + '%</td></tr>';
                        $('.table-fees-list').append(row);
                    }
                }
            });

            // Get last completed jobs in last 7 days
            $.ajax({
                type: "POST",
                url: 'getstats.php',
                data: 'req=feeslist&interval=7',
                dataType: 'json',
                success: function(json) {
                    $('.fee7row').remove();
                    var fees = json;
                    var rowCount = fees.length;
                    for ( var i = 0; i < rowCount; i++ )    {
                        var user = fees[i][0];
                        var funct = "javascript:ShowUserInfo('" + user + "');";
                        var row = '<tr class="row"><td onclick="' + funct + '">' + user + '</td>';
                        row += '<td>$' + fees[i][1] + '</td>';
                        row += '<td>' + fees[i][2] + '%</td></tr>';
                        $('.table-fees-list7').append(row);
                    }
                }
            });

            // Get top 10 runners
            $.ajax({
                type: "POST",
                url: 'getstats.php',
                data: 'req=runners',
                dataType: 'json',
                success: function(json) {
                    $('.runrow').remove();
                    var data = json;
                    var total_tasks = 0;
                    var total_working = 0;

                    for ( var i = 0; i < data.length; i++ )    {
                        var user = data[i][0];
                        var funct = "javascript:ShowUserInfo('" + user + "');";
                        total_tasks += parseInt( data[i][1] );
                        total_working += parseInt( data[i][2] );

                        var row = '<tr class="runrow"><td  onclick="' + funct + '" >'+ user + '</td><td style="text-align:right;">' + data[i][1] +
                                        '</td><td style="text-align:right;">' + data[i][2]  + '</td></tr>';

                        $('.table-runners').append(row);
                    }
                    var totals_row = '<tr class="runrow"><td style="font-weight: bold;">Totals</td><td style="text-align: right; font-weight: bold;">'
                                            + total_tasks + '</td><td style="text-align: right; font-weight: bold;">' + total_working + '</td></tr>';
                    $('.table-runners').append(totals_row);
                }
            });

            // Get top 10 mechanics
            $.ajax({
                type: "POST",
                url: 'getstats.php',
                data: 'req=mechanics',
                dataType: 'json',
                success: function(json) {
                    $('.mecrow').remove();
                    var data = json;
                    var total_tasks = 0;
                    var total_working = 0;

                    for ( var i = 0; i < data.length; i++ )    {
                        total_tasks += parseInt( data[i][1] );
                        total_working += parseInt( data[i][2] );

                        var user = data[i][0];
                        var funct = "javascript:ShowUserInfo('" + user + "');";
                        var row = '<tr class="mecrow"><td  onclick="' + funct + '" >'+ user + '</td><td style="text-align:right;">' + data[i][1] +
                                        '</td><td style="text-align:right;">' + data[i][2] + '</td></tr>';

                        $('.table-mechanics').append(row);
                    }
                    var totals_row = '<tr class="mecrow"><td style="font-weight: bold;">Totals</td><td style="text-align: right; font-weight: bold;">'
                                            + total_tasks + '</td><td style="text-align: right; font-weight: bold;">' + total_working + '</td></tr>';
                    $('.table-mechanics').append(totals_row);
                }
            });

            // Get top 10 feed adders
            $.ajax({
                type: "POST",
                url: 'getstats.php',
                data: 'req=feeadders',
                dataType: 'json',
                success: function(json) {
                    $('.feerow').remove();
                    var data = json;
                    var total_tasks = 0;
                    var total_fees = 0;

                    for ( var i = 0; i < data.length; i++ )    {
                        total_tasks += parseInt( data[i][1] );

                        var user = data[i][0];
                        var funct = "javascript:ShowUserInfo('" + user + "');";
                        // Round average fee
                        var avg_fee = Math.round(data[i][2]*10)/10;
                        total_fees += avg_fee;
                        var row = '<tr class="feerow"><td  onclick="' + funct + '" >'+ user + '</td><td style="text-align:right;">' + data[i][1] +
                                        '</td><td style="text-align:right;">$' + avg_fee  + '</td></tr>';

                        $('.table-fee-adders').append(row);
                    }
                    var totals_row = '<tr class="feerow"><td style="font-weight: bold;">Totals</td><td style="text-align: right; font-weight: bold;">'
                                            + total_tasks + '</td><td style="text-align: right; font-weight: bold;">$' + (Math.round( total_fees*10 )/10) + '</td></tr>';
                    $('.table-fee-adders').append(totals_row);
                }
            });

            // Get top 10 mechanics with "Past Due"
            $.ajax({
                type: "POST",
                url: 'getstats.php',
                data: 'req=pastdue',
                dataType: 'json',
                success: function(json) {
                    $('.pastrow').remove();
                    var data = json;
                    var total_tasks = 0;

                    for ( var i = 0; i < data.length; i++ )    {
                        total_tasks += parseInt( data[i][1] );

                        var user = data[i][0];
                        var funct = "javascript:ShowUserInfo('" + user + "');";
                        var row = '<tr class="pastrow"><td  onclick="' + funct + '" >'+ user + '</td><td style="text-align:right;">' + data[i][1] + '</td></tr>';

                        $('.table-past-due').append(row);
                    }
                    var totals_row = '<tr class="pastrow"><td style="font-weight: bold;">Totals</td><td style="text-align: right; font-weight: bold;">'
                                            + total_tasks + '</td></tr>';
                    $('.table-past-due').append(totals_row);
                }
            });

            $('#popup-stats').dialog({ autoOpen: false, maxWidth: 1000, width: 800, maxHeight: 1000, height: 600, show: 'fade', hide: 'fade'});
            $('#popup-stats').data('title.dialog', 'Task Statistics');
            $('#popup-stats').dialog('open');
        }
        // End code for stats

        // Code for showing user info
        function ShowUserInfo( userid )    {
            // Check if the user is real or a message
            if ( userid == 'SVN')    {
                return;
            }    else if ( userid == 'Work List' )    {
                return;
            }
            // If we got an author name, we look the Id on the database
            if( typeof( userid ) != 'number' )    {
                $.ajax({
                    type: "POST",
                    url: 'getuseritem.php',
                    data: 'req=id&nickname='+userid,
                    dataType: 'json',
                    success: function(json)    {
                        userid = json[0];
                        _showInfo( userid );
                    }
                });
            }    else    {
                _showInfo( userid );
            }
        }

        // Helper function needed because of the async nature of ajax
        // * Show the popup
        function _showInfo( userid )    {
            $('#popup-user-info  #popup-form input[type="submit"]').remove();
            $('#roles').show();
            $.ajax({
                type: "POST",
                url: 'getuseritem.php',
                data: 'req=item&item='+userid,
                dataType: 'json',
                success: function(json) {
                    $('#popup-user-info #userid').val(json[0]);
                    $('#popup-user-info #info-nickname').text(json[1]);
                    $('#popup-user-info #info-email').text(json[2]);
                    $('#popup-user-info #info-about').text(json[3]);
                    $('#popup-user-info #info-contactway').text(json[4]);
                    $('#popup-user-info #info-payway').text(json[5]);
                    $('#popup-user-info #info-skills').text(json[6]);
                    $('#popup-user-info #info-timezone').text(json[7]);
                    $('#popup-user-info #info-joined').text(json[8]);
                    if( json[9] == "1" )    {
                        $('#popup-user-info #info-isrunner').attr('checked', 'checked');
                    } else {
                        $('#popup-user-info #info-isrunner').attr('checked', '');
                    }
                    if( json[10] == "1" )    {
                        $('#popup-user-info #info-ispayer').attr('checked', 'checked');
                    } else {
                        $('#popup-user-info #info-ispayer').attr('checked', '');
                    }
                    $('#popup-user-info #info-isrunner').attr('disabled', 'disabled');
                    $('#popup-user-info #info-ispayer').attr('disabled', 'disabled');
                },
                error: function( xhdr, status, err )    {}
            });

            $('#popup-user-info').dialog('open');
        }
        // End of user info code
        
        function RelativeTime(x){
        var plural = '';
 
        var mins = 60, hour = mins * 60; day = hour * 24,
        week = day * 7, month = week * 4, year = day * 365;

        if (x >= year){ x = (x / year)|0; dformat="year"; }
        else if (x >= month) { x = (x / month)|0; dformat="month"; }
        else if (x >= day*4) { x = (x / day)|0; dformat="day"; }
        else if (x >= hour) { x = (x / hour)|0; dformat="hour"; }
        else if (x >= mins) { x = (x / mins)|0; dformat="minute"; }
        else { x |= 0; dformat="sec"; }
        if (x > 1) plural = 's';
        if (x < 0) x = 0;
        return x + ' ' + dformat + plural;
       }
        
        </script>

        <!-- Popup for showing stats-->
        <?php
        $showStats=true;
        //These pages don't display stats so skip the hidden popup
        foreach(array('signup.php','login.php','settings.php') as $hideStats) {
          if (strpos($_SERVER['PHP_SELF'],$hideStats)) { $showStats=false; }
        }
        if ($showStats) { require_once('dialogs/popup-stats.inc'); }
        
        // addproject always available
        require_once('dialogs/popup-addproject.inc');

         ?>

<!-- END Navigation placeholder -->

