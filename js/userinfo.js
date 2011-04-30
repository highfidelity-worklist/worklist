var available = 0;
var rewarded = 0;
stats.setUserId(userInfo.user_id);

$(document).ready(function(){
    UserInfo.init();
});
 
var UserInfo = {
    init: function() {
        userNotes.init();
       
        $('#select_manager').val(userInfo.manager);
        $('#annual_salary').change(function() {
            $('#salary_changed').val('1');
        });
        $('#select_manager').change(function() {
            $('#manager_changed').val('1');
        });
       
        $("#tabs").tabs({
            cache: true,
            ajaxOptions: {
            cache: true,
            success: function() {
            },                
            error: function( xhr, status, index, anchor ) {
                $( anchor.hash ).html(
                    "Couldn't load this tab." );
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
                $('#ping-msg').TextAreaExpander(80, 150);
            },
            close: function() {
                $('#ping-msg').TextAreaExpander(80, 150);
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
                success: function() {}
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
       
        $('#changeUserStatus').change(function() {
            var change = $.ajax({
                type: 'post',
                url: 'jsonserver.php',
                data: {
                    status: $(this).val(),
                    userid: userInfo.user_id,
                    action: 'changeUserStatus'
                },
                dataType: 'json',
                success: function() {}
            });
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
                data: 'receiver_id=' + $('#budget-receiver').val() + '&reason=' + $('#budget-reason').val() + '&amount=' + $('#budget-amount').val(),
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
                if ($(this).attr('checked') && !$(this).attr('disabled')) {
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
        
        $('#salary').submit(function(e) {
            // Get the specified salary
            var salary = $('#annual_salary').val();
            
            // Get the manager
            var manager = $('#select_manager :selected').text();
            
            if (salary === '' || salary < 0) {
                return true;
            } else {
                if (manager !== 'None') {
                    return true;
                } else {
                    // Show an alert window
                    alert('Users with salary must have a manager.');
                    return false;
                }
            }
        });
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
