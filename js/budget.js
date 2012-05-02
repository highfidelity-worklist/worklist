var Budget = {
    initCombo: function(id, idBlur) {
        if (!id) {
            id = '#budget-source-combo';
            classCombo = 'budget-source-combo';
            idBlur = "#budget-amount";
        } else {
            classCombo = id;
            id = '#' + id;
        }
        if ($(id).data("initComboDone") !== true) {
            $(id).bind({
                'beforeshow newlist': function(e, o) {
                    $("ul." + classCombo + "List li").each(function() {
                        var amount = 0,
                            title = "",
                            id = "";
                        var pos = $(this).text().lastIndexOf("|");
                        if (pos != -1) {
                            amount = $(this).text().substr(pos + 1);
                            id = $(this).text().substr(0, pos);
                            pos = id.lastIndexOf("|");
                            if (pos != -1) {
                                title = id.substr(pos + 1);
                                id = id.substr(0, pos);
                            }
                            $(this).attr("title", title).html("<div class='comboListID'>" + id + "</div>" + 
                                    "<div class='comboListTitle'>" + title + "</div>" + 
                                    "<div class='comboListAmount'>$" + amount + "</div>");
                        } else {
                            $(this).attr("title", title).html("<div class='comboListID'>&nbsp;</div>" + 
                                    "<div class='comboListTitle'>" + $(this).text() + "</div>" + 
                                    "<div class='comboListAmount'></div>");
                            }
                        $(this).data("amount", amount);
                        $(this).addClass("comboListClear");
                    });
                }
            }).comboBox();
            $(id).data("initComboDone", true);
            setTimeout(function() {
                var val1 = $($(id + ' option').get(1)).attr("value");
                $(id).comboBox({action: "val", param: [val1]});
                val1 = $($(id + ' option[selected]').get(0)).attr("value");
                $(id).comboBox({action: "val", param: [val1]});
            }, 20);
        }
        $(idBlur).blur(function(){ 
            var targetAmount = parseFloat($(idBlur).val());
            $("ul." + classCombo + "List li").removeClass("redBudget");
            $("ul." + classCombo + "List li").each(function(){
                if (parseFloat($(this).data("amount")) < targetAmount) {
                    $(this).addClass("redBudget");
                }
            });
        });
    },
    init : function() {
        
        $('#give-budget form input[type="submit"]').click(function() {
            $('#give-budget form input[type="submit"]').attr("disabled", "disabled");
            var toReward = parseInt(rewarded) + parseInt($('#toreward').val());
            $.ajax({
                url: 'update-budget.php',
                data: {
                    receiver_id: $('#budget-receiver').val(),
                    reason: $('#budget-reason').val(),
                    amount: $('#budget-amount').val(),
                    budget_seed: $('#budget-seed').is(':checked') ? 1 : 0,
                    budget_source: $('#budget-source').val(),
                    budget_source_combo: $('#budget-source-combo').val(),
                    budget_note: $('#budget-note').val(),
                    add_funds_to: 0
                },
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    $('#give-budget form input[type="submit"]').removeAttr('disabled');
                    if (json.success) {
                        $('#give-budget').dialog('close');
                        setTimeout(function() {
                            alert(json.message);
                            Budget.budgetHistory({
                                inDiv: "tabs", 
                                id: $('#budget-receiver').val(), 
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
        $("#budget-seed").click(function(){
            if ($(this).is(':checked')) {
                $("#budget-source").show();
                $("#budget-source-combo-area").hide();
            } else {
                $("#budget-source").hide();
                $("#budget-source-combo-area").show();
            }
        });
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
                $('#budget-dialog').load("budgetHistory.php?inDiv=budget-dialog&id=" + $('#budget-dialog').data("userid") + "&page=1" + fromUserid);
            }
        });
        Budget.initCombo();
    },
    
    budgetHistory: function(options) {
        if (!options.page) {
            var lastPage = $("#" + options.inDiv + " .budgetHistoryContent").data("lastPage");
            if (!lastPage) {
                options.page = 1;
            } else {
                options.page = lastPage;
            }
        }
        if (!options.fromUserid) {
            options.fromUserid = "";
        }
        $("#" + options.inDiv + " .budgetHistoryContent").data("lastPage", options.page);
        $("#" + options.inDiv + " .budgetHistoryContent").load("budgetHistory.php?inDiv=" + options.inDiv + "&id=" + options.id + "&page=" + options.page + options.fromUserid);
    },
    
    initAddFunds: function() {
        Budget.initCombo('budget-source-combo', '#amountToAdd');
        $("#amountToAdd").blur(function(){ 
            var amountToAdd = parseFloat($("#amountToAdd").val()),
                budgetAmount = parseFloat($("#budget-amount").text());
            if (!isNaN(amountToAdd + budgetAmount)) {
                $("#newBudgetTotal").html(amountToAdd + budgetAmount);
            } else {
                $("#newBudgetTotal").html("");
            }
        });
        $('#addFundsDialog form input[type="submit"]').click(function() {
            $('#addFundsDialog form input[type="submit"]').attr("disabled", "disabled");
            
            $.ajax({
                url: 'update-budget.php',
                data: {
                    receiver_id: $('#budget-receiver').val(),
                    reason: "",
                    amount: $('#amountToAdd').val(),
                    budget_seed: 0,
                    budget_source: "",
                    budget_source_combo: $('#budget-source-combo').val(),
                    budget_note: "",
                    add_funds_to: $('#add_funds_to').val()
                },
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    $('#addFundsDialog form input[type="submit"]').removeAttr('disabled');
                    if (json.success) {
                        $('#addFundsDialog').dialog('close');
                        $('#budget-update-dialog').dialog("close");
                        setTimeout(function() {
                            alert(json.message);
                            Budget.budgetHistory({
                                inDiv: "tabs", 
                                id: $('#budget-receiver').val(), 
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
        $('#budget-update-dialog').dialog('option', 'position', ['center', 'center']);
        $('#addFundsButton').click(function(){
            $('#addFundsDialog').dialog("destroy").remove();
            $('#addFundsArea').remove();
            $("body").append("<div id='addFundsArea'></div>");
            $("#addFundsArea").load('budget.php', {
                action: "getViewAddFunds",
                budgetId: $('#budget-update-dialog').data("budgetId")
                }, function() {
                    $('#addFundsDialog').dialog({ 
                        autoOpen: true, 
                        width: 400, 
                        show: 'fade', 
                        hide: 'fade',
                        open: function() {
                            Budget.initAddFunds();
                        }
                    });
                }
            );
            return false;
        });  
        $("#closeButton").click(function() {
            $('#budget-update-dialog').dialog("close");
        });
        $("#updateButton").click(function() {
            $.ajax({
                type: "POST",
                url: 'budget.php',
                data: {
                    action: "updateBudget",
                    budgetId: $('#budget-update-dialog').data("budgetId"),
                    budgetReason: $('#budget-reason').val(),
                    budgetNote: $('#budget-note').val()
                },
                dataType: 'json',
                success: function(json) {
                    if (json && json.succeeded) {
                        $('#budget-update-dialog').dialog("close");
                        Budget.budgetHistory({
                            inDiv: "tabs", 
                            id: $('#budget-receiver').val()
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
                url: 'budget.php',
                data: {
                    action: "closeOutBudget",
                    budgetId: $('#budget-update-dialog').data("budgetId")
                },
                dataType: 'json',
                success: function(json) {
                    if (json && json.succeeded) {
                        $('#budget-update-dialog').dialog("close");
                        Budget.budgetHistory({
                            inDiv: "tabs", 
                            id: $('#budget-receiver').val()
                        });
                        window.top.$('#user-info').data("budget_update_done", true);
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
    
    createBudgetUpdateDialog: function() {
        if ($('#budget-update-dialog').data("budgetId") == null) {
            $('#budget-update-dialog').dialog({
                autoOpen: false,
                modal: true,
                width: 750,
                height: "auto",
                title: 'Budget details',
                show: 'fade',
                hide: 'fade',
                open: function() {
                    $.ajax({
                        type: "POST",
                        url: 'budget.php',
                        data: {
                            action: "getUpdateView",
                            budgetId: $('#budget-update-dialog').data("budgetId")
                        },
                        dataType: 'json',
                        success: function(json) {
                            if (json && json.succeeded) {
                                $("#budget-update-dialog .content").html(json.params.html);
                                Budget.initUpdateDialog();
                            } else {
                                $('#budget-update-dialog').dialog("close");
                                alert(json.message);
                            }
                        },
                        error: function(json) {
                            $('#budget-update-dialog').dialog("close");
                            alert('error in getUpdateView');
                        }
                    });
                }
            });
        }
    },
    
    initBudgetList: function() {
        $("tr.budgetRow").live("click",function() {
            Budget.createBudgetUpdateDialog();
            $('#budget-update-dialog').data("budgetId", $(this).data("budgetid"));
            $('#budget-update-dialog').dialog('open');
            return false;
        });
    },
    
    displayHistory: function() {
        $('#budgetPopup').dialog('close');
        showUserInfo(0, "tabBudgetHistory");
    },
    
    displayHistoryFromParent: function(user_id) {
        window.parent.$('#user-info').dialog('close');
        window.parent.showUserInfo(user_id, "tabBudgetHistory");
    },
    
    /**
 * Show a dialog with expanded info on the selected @section
 * Sections:
 *  - 0: Allocated
 *  - 1: Submited
 *  - 2: Paid
 */
    budgetExpand: function(section, budget_id) {
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
        // If clean search, fade in any hidden items
        $('#be-search-clean').click(function() {
            $('#be-search-field').val('');
            $('.data_row').each(function() {
                $(this).fadeIn('fast');
            });
        });
        
        $('#budget-expanded').dialog('option', 'position', ['center', 'center']);
        
        switch (section) {
            case 0:
                // Fetch new data via ajax
                $('#budget-expanded').dialog('open');
                Budget.be_getData(section, budget_id);
                break;
            case 1:
                // Fetch new data via ajax
                $('#budget-expanded').dialog('open');
                Budget.be_getData(section, budget_id);
                break;
            case 2:
                // Fetch new data via ajax
                $('#budget-expanded').dialog('open');
                Budget.be_getData(section, budget_id);
                break;
        }
    },

    be_attachEvents: function(section, budget_id) {
        $('#be-id').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-budget').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-summary').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-who').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-amount').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-status').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-created').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-paid').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
    },

    be_getData: function(section, budget_id, item, desc) {
        // Clear old data
        var header = $('#table-budget-expanded').children()[0];
        $('#table-budget-expanded').children().remove();
        $('#table-budget-expanded').append(header);
        Budget.be_attachEvents(section, budget_id);
        
        var params = '?section=' + section + '&budget_id=' + budget_id;
        var sortby = '';
        // If we've got an item sort by it
        if (item) {
            sortby = item.attr('id');
            params += '&sortby='+sortby+'&desc='+desc;
        }
        
        $.getJSON('get-budget-expanded.php'+params, function(data) {
            // Fill the table
            for (var i = 0; i < data.length; i++) {
                var link = '<a href="workitem.php?job_id='+data[i].id+'&action=view" target="_blank">';
                // Separate "who" names into an array so we can add the userinfo for each one
                var who = (data[i].who === false) ? new Array() : data[i].who.split(", ");
                var who_link = '';
                for (var z = 0; z < who.length; z++) {
                    if (z < who.length-1) {
                        who[z] = '<a href="#" onclick="showUserInfo('+data[i].ids[z]+')">'+who[z]+'</a>, ';
                    } else {
                        who[z] = '<a href="#" onclick="showUserInfo('+data[i].ids[z]+')">'+who[z]+'</a>';
                    }
                    who_link += who[z];
                }
                
                var row = '<tr class="data_row"><td>' + link + '#' + data[i].id + '</a></td><td title="' + data[i].budget_title + '">' +
                            data[i].budget_id + '</td><td>' + link+data[i].summary + '</a></td><td>' + who_link +
                          '</td><td>$' + data[i].amount + '</td><td>' + data[i].status + '</td>' +
                          '<td>' + data[i].created + '</td>';
                if (data[i].paid != 'Not Paid') {
                    row += '<td>'+data[i].paid+'</td></tr>';
                } else {
                    row += '<td>'+data[i].paid+'</td></tr>';
                }
                $('#table-budget-expanded').append(row);
            }
        });
        $('#budget-report-export').click(function() {
            window.open('get-budget-expanded.php?section='+section+'&action=export', '_blank');
        });
    },

    be_handleSorting: function(section, item) {
        var desc = true;
        if (item.hasClass('desc')) {
            desc = false;
        }
        
        // Cleanup sorting
        Budget.be_cleaupTableSorting();
        item.removeClass('asc');
        item.removeClass('desc');
        
        // Add arrow
        var arrow_up = '<div style="float:right;">'+
                       '<img src="images/arrow-up.png" height="15" width="15" alt="arrow"/>'+
                       '</div>';

        var arrow_down = '<div style="float:right;">'+
                         '<img src="images/arrow-down.png" height="15" width="15" alt="arrow"/>'+
                         '</div>';

        if (desc) {
            item.append(arrow_down);
            item.addClass('desc');
        } else {
            item.append(arrow_up);
            item.addClass('asc');
        }

        // Update Data
        Budget.be_getData(section, budget_id, item, desc);
    },

    be_cleaupTableSorting: function() {
        $('#be-id').children().remove();
        $('#be-summary').children().remove();
        $('#be-who').children().remove();
        $('#be-amount').children().remove();
        $('#be-status').children().remove();
        $('#be-created').children().remove();
        $('#be-paid').children().remove();
    }

};

$(function() {
    Budget.initBudgetList();
    $('#budget-expanded').dialog({ autoOpen: false, width:780, show:'fade', hide:'drop' });

});



function showUserInfo(userId, tab) {
    if (tab) {
        tab = "&tab=" + tab;
    } else {
        tab = "";
    }
    $('#user-info').html('<iframe id="modalIframeId" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" />').dialog('open');
    $('#modalIframeId').attr('src', 'userinfo.php?id=' + userId + tab);
    return false;
}
