var Budget = {
    init : function() {
        Budget.initBudgetList();
        Budget.initUpdateDialog();
        Budget.initAddFunds();

        $('#budget-give-modal form button[type="submit"]').click(function(event) {
            event.preventDefault();
            $('#budget-give-modal form button[type="submit"]').attr("disabled", "disabled");
            $.ajax({
                url: 'api.php',
                data: {
                    action: 'updateBudget',
                    receiver_id: $('#userid').val(),
                    reason: $('#budget-reason').val(),
                    amount: parseFloat($('#budget-give-modal input[name="amount"]').val().replace('$', '')),
                    budget_seed: $('#budget-give-modal input[name="budget-seed"]').is(':checked') ? 1 : 0,
                    budget_source: $('#budget-give-modal input[name="budget-source"]').val(),
                    budget_source_combo: $('#budget-give-modal select[name="budget-source-combo"]').val(),
                    budget_note: $('#budget-note').val(),
                    add_funds_to: 0
                },
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    $('#budget-give-modal form button[type="submit"]').removeAttr('disabled');
                    if (json.success) {
                        $('#budget-give-modal').modal('hide');
                        setTimeout(function() {
                            alert(json.message);
                            Budget.budgetHistory({
                                inDiv: "tabs", 
                                id: $('#userid').val(), 
                                page: 1 
                            });
                        }, 50);
                        $("#isrunner").prop('checked', true);
                    } else {
                        alert(json.message);
                    }
                },
                error: function(json) {
                    if (json.message) {
                        alert(json.message);
                    } else {
                        alert('All fields are required');
                    }
                }
            });
            return false;
        });
        $(".currentBudgetArea").click(function() {
            $('#budget-dialog').data("fromUserid", "n");
            $('#budget-dialog').dialog('open');
            return false;
        });
        $(".givenBudgetArea").click(function() {
            $('#budget-dialog').data("fromUserid", "y");
            $('#budget-dialog').dialog('open');
            return false;
        });
        if ($('#budget-give-modal input[name="budget-seed"]').length == 0) {
            $('#budget-give-modal input[name="budget-source"]').hide();
            $('#budget-give-modal #budget-source-combo-area').show();
        } else {
            var toggleSeedBudget = function() {
                if ($(this).is(':checked')) {
                    $('#budget-give-modal input[name="budget-source"]').show();
                    $('#budget-give-modal #budget-source-combo-area').hide();
                } else {
                    $('#budget-give-modal input[name="budget-source"]').hide();
                    $('#budget-give-modal #budget-source-combo-area').show();
                }
            };
            $('#budget-give-modal input[name="budget-seed"]').click(toggleSeedBudget);
            toggleSeedBudget();
        }
        $('#budget-dialog').dialog({
            autoOpen: false,
            modal: true,
            width: 750,
            height: 340,
            title: 'Budget details',
            show: 'fade',
            hide: 'fade',
            open: function() {
                var fromUserid = "&fromUserid=" + $('#budget-dialog').data("fromUserid");
                if ($('#budget-dialog').data("fromUserid") == "n") {
                    $('#budget-dialog').dialog("option", "title", "All budget grants assigned to user");
                } else {
                    $('#budget-dialog').dialog("option", "title", "All budget grants from you");
                }
                $('#budget-dialog').load("api.php?action=budgetHistory&inDiv=budget-dialog&id=" + $('#budget-dialog').data("userid") + "&page=1" + fromUserid);
            }
        });
        $('#budget-source-combo').chosen({width: 'auto'});

        $("nav.navbar a.budget").click(function(event) {
            event.preventDefault();
            $.ajax({
                url: './user/budget/' + userId,
                dataType: 'json',
                success: function(json) {
                    if (!json.success) {
                        return;
                    }
                    Utils.modal('earnings-and-budget', {
                        isRunner: is_runner,
                        budget: json.budget,
                        open: function(modal) {
                            $('table:eq(1) > tbody > tr', modal).click(function() {
                                Budget.displayHistory(userId);
                            });
                            $('table:eq(2) > tbody td', modal).click(function() {
                                $(modal).modal('hide');
                                var index = $(this).prevAll().length;
                                Budget.budgetExpand(index);
                            });
                        }
                    });
                }
            });
        });
    },
    
    budgetHistory: function(options) {
        if (!options.page) {
            var lastPage = $("#" + options.inDiv + " #budgetHistoryContent").data("lastPage");
            if (!lastPage) {
                options.page = 1;
            } else {
                options.page = lastPage;
            }
        }
        if (!options.fromUserid) {
            options.fromUserid = "";
        }
        $("#" + options.inDiv + " #budgethistory").data("lastPage", options.page);
        $.ajax({
            type: 'post',
            url: 'api.php',
            dataType: 'html',
            data: {
                action: 'budgetHistory',
                inDiv: options.inDiv,
                id: options.id,
                page: options.page,
                fromUserid: options.fromUserid
            },
            success: function(data) {
                $('#budgethistory').html(data);
            }
        });

    },
    
    initAddFunds: function() {
        var budget_seed = $('#add-funds-modal input[name="budget-seed"]').val() != 0 ? 1 : 0;
        if (budget_seed) {
            $('#add-funds-modal input[name="budget-source"]').show();
            $('#add-funds-modal #budget-source-combo-area').hide();
        } else {
            $('#add-funds-modal input[name="budget-source"]').hide();
            $('#add-funds-modal #budget-source-combo-area').show();
        }
        $('#add-funds-modal select[name="budget-source-combo"]').chosen({width: 'auto'});
        $("#amountToAdd").blur(function(){ 
            var amountToAdd = parseFloat($("#amountToAdd").val()),
                budgetAmount = parseFloat($('#budget-update-modal input[name="amount"]').val().replace('$', ''));
            if (!isNaN(amountToAdd + budgetAmount)) {
                $("#newBudgetTotal").html(amountToAdd + budgetAmount);
            } else {
                $("#newBudgetTotal").html("");
            }
        });
        $('#add-funds-modal form button[type="submit"]').click(function() {
            $('#add-funds-modal form button[type="submit"]').attr("disabled", "disabled");
            $.ajax({
                url: 'api.php',
                data: {
                    action: 'updateBudget',
                    receiver_id: $('#userid').val(),
                    reason: "",
                    amount: $('#amountToAdd').val(),
                    budget_seed: budget_seed,
                    budget_source: $('#add-funds-modal input[name="budget-source"]').val(),
                    budget_source_combo: $('#add-funds-modal select[name="budget-source-combo"]').val(),
                    budget_note: "",
                    add_funds_to: $('#add_funds_to').val()
                },
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    $('#add-funds-modal form button[type="submit"]').removeAttr('disabled');
                    if (json.success) {
                        $('#add-funds-modal').modal('hide');
                        $('#budget-update-modal').modal("hide");
                        setTimeout(function() {
                            alert(json.message);
                            Budget.budgetHistory({
                                inDiv: "tabs", 
                                id: $('#userid').val(), 
                                page: 1 
                            });
                        }, 50);
                        $("#isrunner").prop('checked', true);
                    } else {
                        alert(json.message);
                    }
                },
                error: function(json) {
                    if (json.message) {
                        alert(json.message);
                    } else {
                        alert('All fields are required');
                    }
                }
            });
            return false;
        });

    },
    
    initUpdateDialog: function() {
        $('#addFundsButton').click(function(){
            $('#budget-update-modal').modal('hide');
            $('#add-funds-modal').modal('show');
            $('#newBudgetTotal').html('0');
            $.ajax({
                type: "POST",
                url: 'api.php',
                data: {
                    action: "budgetInfo",
                    method: "getViewAddFunds",
                    budgetId: $('#budget-update-modal').data("budgetId")
                },
                dataType: 'json',
                success: function(json) {
                    if (json && json.succeeded) {
                        data = json.params;
                        $("#budget-seed").val(data.seed == 1 ? 1 : 0);
                        $('#add_funds_to').val(data.budget_id);
                     } else {
                        alert(json.message);
                    }
                },
                error: function(json) {
                    alert('error in addFunds');
                }
            });
            return false;
        });  
        $("#updateButton").click(function() {
            $.ajax({
                type: "POST",
                url: 'api.php',
                data: {
                    action: "budgetInfo",
                    method: "updateBudget",
                    budgetId: $('#budget-update-modal').data("budgetId"),
                    budgetReason: $('#budget-reason').val(),
                    budgetNote: $('#budget-note').val()
                },
                dataType: 'json',
                success: function(json) {
                    if (json && json.succeeded) {
                        $('#budget-update-modal').modal("hide");
                        Budget.budgetHistory({
                            inDiv: "tabs", 
                            id: $('#userid').val()
                        });
                     } else {
                        alert(json.message);
                    }
                },
                error: function(json) {
                    alert('error in updateBudget');
                }
            });
        });
        $("#closeOutButton").click(function() {
            $.ajax({
                type: "POST",
                url: 'api.php',
                data: {
                    action: "budgetInfo",
                    method: "closeOutBudget",
                    budgetId: $('#budget-update-modal').data("budgetId")
                },
                dataType: 'json',
                success: function(json) {
                    if (json && json.succeeded) {
                        $('#budget-update-modal').modal("hide");
                        Budget.budgetHistory({
                            inDiv: "tabs", 
                            id: $('#userid').val()
                        });
                     } else {
                        alert(json.message);
                    }
                },
                error: function(json) {
                    alert('error in updateBudget');
                }
            });
        });
    },
    
    initBudgetList: function() {
        $('#budgetAllocated > td').click(function() {
            var budgetId = $('#budget-update-modal').data("budgetId");
            Budget.budgetExpand(0, budgetId);
        });
        $('#budgetSubmitted > td').click(function() {
            var budgetId = $('#budget-update-modal').data("budgetId");
            Budget.budgetExpand(1, budgetId);
        });
        $('#budgetPaid > td').click(function() {
            var budgetId = $('#budget-update-modal').data("budgetId");
            Budget.budgetExpand(2, budgetId);
        });
        $('#budgetTransferred > td').click(function() {
            var budgetId = $('#budget-update-modal').data("budgetId");
            Budget.budgetExpand(3, budgetId);
        });
        $("tr.budgetRow").live("click",function() {
            $('#budget-update-modal').data("budgetId", $(this).attr('data-budgetid'));
            $.ajax({
                type: "POST",
                url: 'api.php',
                data: {
                    action: "budgetInfo",
                    method: "getUpdateView",
                    budgetId: $('#budget-update-modal').data("budgetId")
                },
                dataType: 'json',
                success: function(json) {
                    if (json && json.succeeded) {
                        data = json.params;
                        $('#budget-update-modal input[name="amount"]').val('$' + data.amount);
                        $('#budget-update-modal input[name="budget-reason"]').val(data.reason).attr({disabled: data.closed == 1});
                        if ((data.req_user_authorized && data.seed == 1) || data.seed == 0) {
                            $('#budegt-sources-table').show();
                        } else {
                            $('#budget-sources-table').hide();
                        }
                        $('#budget-sources-table > table > tbody > tr').remove();
                        for(var i=0; i< data.sources.length; i++) {
                            var source = data.sources[i];
                            html = 
                                '<tr>' +
                                '  <td>' + source.transfer_date + '</td>' +
                                '  <td>' + source.nickname + '</td>' +
                                '  <td>' + source.amount_granted + '</td>' +
                                '  <td>' + 
                                    (
                                        data.seed == 0 
                                            ?  'Budget ID: ' + source.budget_id + ' - ' + source.reason
                                            :  'Seed Budget: ' + source.source_data
                                    ) + 
                                '</td>' +
                                '</tr>';
                            $(html).appendTo('#budget-sources-table > table > tbody');
                        }
                        $('#budget-note').val(data.notes).attr({disabled: data.closed == 1});
                        $('#budgetRemainingFunds > td:last-child').text('$' + data.remaining);
                        $('#budgetAllocated > td:last-child').text('$' + data.allocated);
                        $('#budgetSubmitted > td:last-child').text('$' + data.submitted);
                        $('#budgetPaid > td:last-child').text('$' + data.paid);
                        $('#budgetTransferred > td:last-child').text('$' + data.transferred);

                        if (data.closed == 1) {
                            $('#updateButton').hide();
                            $('#closeButton').show();
                        } else {
                            $('#updateButton').show();
                            $('#closeButton').hide();
                        }
                    } else {
                        $('#budget-update-modal').modal("hide");
                        alert(json.message);
                    }
                },
                error: function(json) {
                    $('#budget-update-modal').modal("hide");
                    alert('error in getUpdateView');
                }
            });
            $('#budget-update-modal').modal('show');
            return false;
        });
    },
    
    displayHistory: function(user_id) {
        window.location = './user/' + user_id + '?tab=tabBudgetHistory';
    },
        
    /**
    * Show a dialog with expanded info on the selected @section
    * Sections:
    *  - 0: Allocated
    *  - 1: Submited
    *  - 2: Paid
    *  - 3: Transfered
    */
    budgetExpand: function(section, budget_id) {
        $('#add-funds-modal').modal('hide');
        $('#budget-update-modal').modal("hide");

        $('#be-search-field').val('');
        $('#be-search-field').keyup(function() {
            // Search current text in the table by hiding rows
            var search = $(this).val().toLowerCase();
            
            $('.data_row').each(function() {
                var html = $(this).text().toLowerCase();
                // If the Row doesn't contain the pattern hide it
                if (!html.match(search)) {
                    $(this).fadeOut('fast');
                } else { // If is hidden but matches the pattern, show it
                    if (!$(this).is(':visible')) {
                        $(this).fadeIn('fast');
                    }
                }
            });
        });
        $('#bet-search-field').val('');
        $('#bet-search-field').keyup(function() {
            // Search current text in the table by hiding rows
            var search = $(this).val().toLowerCase();
            
            $('.data_row').each(function() {
                var html = $(this).text().toLowerCase();
                // If the Row doesn't contain the pattern hide it
                if (!html.match(search)) {
                    $(this).fadeOut('fast');
                } else { // If is hidden but matches the pattern, show it
                    if (!$(this).is(':visible')) {
                        $(this).fadeIn('fast');
                    }
                }
            });
        });
        // If clean search, fade in any hidden items
        $('#be-search-clean').click(function() {
            $('#be-search-field').val('');
            $('.data_row').each(function() {
                $(this).fadeIn('fast');
            });
        });
        // If clean search, fade in any hidden items
        $('#bet-search-clean').click(function() {
            $('#bet-search-field').val('');
            $('.data_row').each(function() {
                $(this).fadeIn('fast');
            });
        });

        if (section < 3) {
            var method = (section == 0) ? 'allocated' : (section == 1) ? 'submitted' : 'paid';
            var url = './budget/' + method + '/' + (budget_id ? budget_id : '0');
            $.getJSON(url, function(json) {
                if (!json.success) {
                    return;
                }
                Utils.modal('budget', {
                    items: json.items,
                    title: 'Budget' + method,
                    exportUrl: url + '.csv',
                    open: function(modal) {
                        // todo: add search behavior
                    }
                });
            });
        } else /* if (section == 3) */ {
            var url = './budget/transferred/' + (budget_id ? budget_id : '0');
            $.getJSON(url, function(json) {
                if (!json.success) {
                    return;
                }
                Utils.modal('budget-transfer', {
                    items: json.items,
                    title: 'Budget transferred',
                    exportUrl: url + '.csv',
                    open: function(modal) {
                        // todo: add search behavior
                    }
                });
            });
        }
    }
};
