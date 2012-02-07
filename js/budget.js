var Budget = {
    init : function() {
        $('#give-budget form input[type="submit"]').click(function() {
            $('#give-budget form input[type="submit').attr("disabled","disabled");
            
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
                    budget_note: $('#budget-note').val()
                },
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    $('#give-budget form input[type="submit').removeAttr('disabled');
                    if (json.success) {
                        $('#give-budget').dialog('close');
                        setTimeout(function() {
                            alert(json.message);
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
        $('#budget-source-combo').bind({
            'beforeshow newlist': function(e, o) {
                $("ul.budget-source-comboList li").each(function(){
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
        setTimeout(function() {
            var val1 = $($('#budget-source-combo option').get(1)).attr("value");
            $('#budget-source-combo').comboBox({action:"val", param:[val1]});
            var val1 = $($('#budget-source-combo option').get(0)).attr("value");
            $('#budget-source-combo').comboBox({action:"val", param:[val1]});
        },20);
        $("#budget-seed").click(function(){
            if ($(this).is(':checked')) {
                $("#budget-source").show();
                $("#budget-source-combo-area").hide();
            } else {
                $("#budget-source").hide();
                $("#budget-source-combo-area").show();
            }
        });
        $("#budget-amount").blur(function(){
            var targetAmount = parseFloat($("#budget-amount").val());
            $("ul.budget-source-comboList li").removeClass("redBudget");
            $("ul.budget-source-comboList li").each(function(){
                if (parseFloat($(this).data("amount")) < targetAmount) {
                    $(this).addClass("redBudget");
                }
            });
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
                    $('#budget-dialog').dialog("option","title","All budget grants assigned to user");
                } else {
                    $('#budget-dialog').dialog("option","title","All budget grants from you");
                }
                $('#budget-dialog').html("<iframe src='budgetHistory.php?id=" + $('#budget-dialog').data("userid") + "&page=1" + fromUserid + "' width='100%' height='100%' frameborder='0'></iframe>");
            }
        });
    }
};