var available = 0;
var rewarded = 0;
stats.setUserId(userInfo.user_id);

$(document).ready(function(){
    UserInfo.init();
    $('#sent-notify').dialog({
        modal: false,
        autoOpen: false,
        width: 250,
        height: 60,
        position: ['middle'],
        resizable: false,
        open: function() {
            $("#sent-notify").parent().children('.ui-dialog-titlebar').hide();
            setTimeout(function() {
                $("#sent-notify").dialog("close");
            }, 3000);
        }
    });
});
 
var UserInfo = {
    init: function() {
        userNotes.init();
        // admin settings
        // checkboxes and dropdowns are handled by master functions below
        // the dropdown value should always be an integer

        // set the current values
        $('#manager').val(userInfo.manager);
        $('#referrer').val(userInfo.referred_by);

        WLFavorites.init( "profileInfoFavorite",userInfo.user_id, userInfo.nickName );
        // setup the variables needed to call the getFavoriteText function
        var favCount = $('.profileInfoFavorite span').attr('data-favorite_count');
        var isMyFav = false;
        if ($('.profileInfoFavorite .favorite_user').hasClass('myfavorite')) {
            isMyFav = true;
        }
        
        // set the favText with the getFavoriteText function
        var favText = WLFavorites.getFavoriteText(favCount, isMyFav, 'trusted ');
        
        $('.profileInfoFavorite span').html(favText);

        // master function to handle change in dropdowns
        $('select', '#tabs-2').change(function() {
            var value = $(this).val();
            var field = $(this).attr('id');
            if (field == 'w9status') {
                if (value == 'rejected') {
                    $('#reject-w9').dialog('open');
                    // we don't post this here, it's sent along with the reason
                    // for rejection by the dialog handler
                    return;
                }
            }

            $.ajax({
                type: 'post',
                url: 'userinfo.php',
                dataType: 'json',
                data: {
                    value: $(this).val(),
                    field: $(this).attr('id'),
                    user_id: userInfo.user_id
                },
                success: function() {
                }
            });
        });

        // master function to handle checkbox changes
        $('input[type=checkbox]', '#tabs-2').click(function() {
            // get the checkbox value
            var value = $(this).is(':checked') ? 1 : 0;
            // and the id of the field being changed
            var field = $(this).attr('id');

            $.ajax({
                type: 'post',
                url: 'userinfo.php',
                dataType: 'json',
                data: {
                    value: value,
                    field: field,
                    user_id: userInfo.user_id
                },
                success: function(json) {
                    // if (json
                }
            });
        
        });

        $('#reject-w9').dialog({
            autoOpen: false,
            show: 'fade',
            hide: 'fade',
            buttons: [{
                text: 'Send notification',
                click: function() {
                    $.ajax({
                        type: 'post',
                        url: 'userinfo.php',
                        dataType: 'json',
                        data: {
                            value: 'rejected',
                            field: 'w9status',
                            user_id: userInfo.user_id,
                            reason: $('#reject-reason').val()
                        },
                        success: function(json) {
                            $('#reject-w9').dialog('close');
                        }
                    });
                }
            }],
            open: function() {
                $('#reject-reason').bind('keyup', 'keydown', function() {
                    if ($(this).val().length > 100) {
                        $(this).val($(this).val().substring(0, 100));
                    } else {
                        $('#charCount').text(100 - $(this).val().length);
                    }
                });
            }
        });

        // custom function for setting salary
        $('#save_salary').click(function() {
            // Get the specified salary
            var salary = $('#annual_salary').val();
            // Get the manager
            var manager = $('#manager :selected').text();

            // if no manager, and a salary larger than 0, reject
            if (manager === 'None' && salary > 0) {
                alert('Users with salary must have a manager.');
                return false;
            }

            $.ajax({
                type: 'post',
                url: 'userinfo.php',
                dataType: 'json',
                data: {
                    value: $('#annual_salary').val(),
                    'save-salary': true,
                    user_id: userInfo.user_id
                },
                success: function() {
                }
            });
        });

        $("#tabs").tabs({
            cache: true,
            ajaxOptions: {
                cache: true,
                success: function() {
                },                
                error: function( xhr, status, index, anchor ) {
                    $(anchor.hash).html("Couldn't load this tab." );
                }
            }
        });

        $(".tabs-bottom .ui-tabs-nav, .tabs-bottom .ui-tabs-nav > *")
            .removeClass("ui-corner-all ui-corner-top")
            .addClass("ui-corner-bottom");
            
        $("#loading").ajaxStart(function() {
            $(this).show();
        });
        $("#loading").ajaxStop(function(){
            $(this).hide();
        });
        
        // Resize the dialog to fit all data dynamically
        parent.resizeIframeDlg();
       
        $('#popup-pingtask').dialog({
            autoOpen: false, 
            width: 400, 
            position: [ 'top' ],
            show: 'fade',
            hide: 'fade',
            open: function() {
                $('#ping-msg').autogrow(80, 150);
            },
            close: function() {
                $('#ping-msg').autogrow(80, 150);
                $('#ping-msg').val('');
            }
        });
       
        $('#send-ping-btn').click(function() {
            var msg = $('#ping-msg').val();
            // always send email
            var mail = 1;
            var journal = $('#echo-journal').is(':checked') ? 1 : 0;
            var cc = $('#copy-me').is(':checked') ? 1 : 0;
            $.ajax({
                type: "POST",
                url: 'pingtask.php',
                data: 'userid=' + userInfo.user_id + '&msg=' + msg + '&mail=' + mail + '&journal=' + journal + '&cc=' + cc,
                dataType: 'json',
                success: function() {
                    $("#sent-notify").html("<span>Your message has been sent.</span>");
                    $("#sent-notify").dialog("open");
                }
            });
            $('#popup-pingtask').dialog('close');
            return false;
        });
        
        WReview.initList();    
       
        $('#nickname-ping, .nickname-ping').click(function() {
        $('#popup-pingtask').dialog('option', 'title', 'Message user: ' + $(this).text());
        $('#popup-pingtask form h5').html('Ping message:');
        $('#popup-pingtask').dialog('open');
            return false;
        });

        $('#give-budget').dialog({ autoOpen: false, show: 'fade', hide: 'fade'});
        $('#give').click(function(){
            $('#give-budget form input[type="text"]').val('');
            $('#give-budget').dialog('open');
            return false;
        });
        
        $('#give-budget form input[type="submit"]').click(function() {
            $('#give-budget').dialog('close');
            
            var toReward = parseInt(rewarded) + parseInt($('#toreward').val());
            $.ajax({
                url: 'update-budget.php',
                data: 'receiver_id=' + $('#budget-receiver').val() + '&reason=' + encodeURIComponent($('#budget-reason').val()) + '&amount=' + $('#budget-amount').val(),
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    $('#info-budget').text(json);
                }
            });
            return false;
        });
       
        $('#quick-reward').dialog({ autoOpen: false, show: 'fade', hide: 'fade'});
        
        $('a#reward-link').click(function() {
            $('#quick-reward form input[type="text"]').val('');
            //Wire off rewarder functions for now - GJ 5/24
            return false;
            
            $.getJSON('get-rewarder-user.php', {'id': userInfo.user_id}, function(json) {
            
                rewarded = json.rewarded;
                available = json.available;
                $('#quick-reward #already').text(rewarded);
                $('#quick-reward #available').text(available);
               
                $('#quick-reward').dialog('open');
            });
            
            return false;
        });
       
        $('#quick-reward form input[type="submit"]').click(function() {
        
            $('#quick-reward').dialog('close');
            //Wire off rewarder functions for now - GJ 5/24
            return false;
            
            var toReward = parseInt($('#toreward').val());
            
            $.ajax({
                url: 'reward-user.php',
                data: 'id=' + userInfo.user_id + '&points=' + toReward,
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                }
            });
            return false;
        });
       
        $('#create_sandbox').click(function(){
            var projects = '';
            
            // get project ids that are newly checked - setup to allow adding
            // projects to sandbox that is already created, sandbox bash
            // script needs updating to support this
            $('#sandboxForm input[type=checkbox]:checked').not(':disabled').each(function() {
                if ($(this).prop('checked') && !$(this).prop('disabled')) {
                    projects += $(this).next('.repo').val() + ',';
                } 
            });
            
            if (projects != '') {
                // remove the last comma
                projects = projects.slice(0, -1)        
                $.ajax({
                    type: "POST",
                    url: 'userinfo.php',
                    dataType: 'json',
                    data: {
                        action: "create-sandbox",
                        id: userInfo.user_id,
                        unixusername: $('#unixusername').val(),
                        projects: projects
                    },
                    success: function(json) {
                        if(json.error) {
                            alert("Sandbox Creation failed:"+json.error);
                        } else {
                        alert("Sandbox created successfully");
                            $('#popup-user-info').dialog('close');
                        }
                    }
                });
            } else {
                alert('You did not choose any projects to check out.');
            }
            
            return false;
        });

        $('#pay-bonus').dialog({ autoOpen: false, width: 720, show: 'fade', hide: 'fade'});
         
        var bonus_amount;
       
        $('#pay_bonus').click(function(e) {
            // clear form input fields
            $('#pay-bonus form input[type="text"]').val('');
            $('#pay-bonus').dialog('open').dialog('option', 'position', 'top', 0);
            $('#pay-bonus').bind('dialogclose', function(event, ui) {
                parent.resizeIframeDlg();
            });
            
            var regex_bid = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
           
            bonus_amount = new LiveValidation('bonus-amount', {onlyOnSubmit: true });
            bonus_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
            bonus_amount.add( Validate.Format, { pattern: regex_bid, failureMessage: "Invalid Input!" });
            while(!$('#pay-bonus').is(':visible')) {
                sleep(0);
            }
            UserInfo.getBonusHistory(1);
        });
        
        $('#pay-bonus form').submit(function() {
        
            if (bonus_amount.validate()) {
                if (confirm('Are you sure you want to pay $' + $('#bonus-amount').val() + ' to ' + userInfo.nickName + '?')) {
                    $('#pay-bonus').dialog('close');
                    $.ajax({
                        url: 'pay-bonus.php',
                        data: $('#pay-bonus form').serialize(),
                        dataType: 'json',
                        type: "POST",
                        cache: false,
                        success: function(json) {
                            if (json.success) {
                                alert(json.message);
                            } else {
                                alert(json.message);
                            }
                        },
                        error: function(json) {
                            alert('error');
                        }
                    });
                }
            }
            
            return false;
        });

        $('#runnersAccordion').accordion({
            clearStyle: true,
            collapsible: true,
            active: true,
            create: function(event, ui) { 
                var workersIntervalId = setInterval(function() {
                    if($("#runner-workers tr").length) {
                        $('#runner-workers').paginate(5, 500);
                        clearInterval(workersIntervalId);
                    }
                }, 2000);
                var intervalId = setInterval(function() {
                    if($("#runner-projects tr").length) {
                        $('#runner-projects').paginate(3, 500);
                        clearInterval(intervalId);
                    }
                }, 2000);
            }
        });        
        
        if (! admin) {
            $('#ispaypalverified').prop('disabled', true);
            $('#isw9approved').prop('disabled', true);
            $('#isw2employee').prop('disabled', true);
        } else {
            $('#isw2employee').click(function () {
                if ($('#isw2employee').is(':checked')) {
                    $('#ispaypalverified').removeAttr('checked');
                    $('#w9status').val('not-applicable');
                } else {
                    $('#ispaypalverified').removeAttr('disabled');
                }
            });
            $('#ispaypalverified').click(function() {
                if ($(this).is(':checked')) {
                    $('#isw2employee').removeAttr('checked');
                } else {
                    $('#isw2employee') .removeAttr('disabled');
                }
            });
            $('#w9status').change(function() {
                if ($(this).val() == 'not-applicable') {
                    $('#isw2employee') .removeAttr('disabled');
                } else {
                    $('#isw2employee') .removeAttr('checked');
                }
            });
        }
    },
     
    appendPagination: function(page, cPages, table) {
        var cspan = '4'
        var pagination = '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table + '-pagination-row" >' + 
                         '<td colspan="' + cspan + '" style="text-align:center;">';
        if (page > 1) {
            pagination += '<a href="#" onclick="UserInfo.getBonusHistory(' + (page - 1) + ')" title="' +
                          (page - 1) + '">Prev</a> &nbsp;';
        }
        for (var i = 1; i <= cPages; i++) {
            if (i == page) {
                pagination += i + " &nbsp;";
            } else {
                pagination += '<a href="#" onclick="UserInfo.getBonusHistory(' + i + ')" title="' + i + 
                              '">' + i + '</a> &nbsp;';
            }
        }
        if (page < cPages) {
            pagination += '<a href="#" onclick="UserInfo.getBonusHistory(' + (page + 1) + ')" title="' +
                          (page + 1) + '">Next</a> &nbsp;';
        }
        pagination += '</td></tr>';
        $('.table-' + table).append(pagination);
    },
    
    appendRow: function(json, table) {
        var pre = '', post = '';
        var row;
        
        row = '<tr>';

        row += '<td>' + pre + json[0] + post + '</td>'; // Date
        row += '<td>' + pre + '$' + json[1] + post + '</td>'; // Amount
        row += '<td>' + pre + json[2] + post + '</td>'; // Receiver
        row += '<td>' + pre + json[3] + post + '</td>'; // Description

        row += '</tr>';
        
        $('.table-' + table).append(row);
    },

    getBonusHistory: function(page) {
        $.ajax({
            cache: false,
            type: 'GET',
            url: 'getbonushistory.php',
            data: { uid: current_id, rid: user_id, page: page },
            dataType: 'json',
            success: function(json) {
                $('.bonus-history').find("tr:gt(0)").remove();
                
                for (var i = 1; i < json.length; i++) {
                    UserInfo.appendRow(json[i], 'bonus-history');
                }

                // If there's only one page don't add the pagination
                if (json[0][2] > 1) {
                    UserInfo.appendPagination(json[0][1], json[0][2], 'bonus-history');
                } else if(json[0][2] == 0) {
                    var footer = '<tr bgcolor="#FFFFFF"><td colspan="4" style="text-align:center;">No bonus history yet</tr>';
                    $('.bonus-history').append(footer);
                }
                parent.resizeIframeDlg();
            }
        });
    }
 };
