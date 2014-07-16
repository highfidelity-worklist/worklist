var Budget = {
    page: {
        budgetHistory: 1
    },

    init : function() {
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
                            $('table:eq(1) > tbody td > a', modal).click(function() {
                                Budget.showBudgetHistory(1, userId);
                                return false;
                            });
                            $('table:eq(2) > tbody td > a', modal).click(function() {
                                $(modal).modal('hide');
                                var index = $(this).parent().prevAll().length;
                                Budget.budgetExpand(index);
                                return false;
                            });
                        }
                    });
                }
            });
        });
    },

    showAddFundsModal: function(budget_id, data) {
        Utils.modal('addfunds', {
            budget_id: budget_id,
            data: data,
            open: function(modal) {
                $.ajax({
                    url: './user/budget/' + userId,
                    dataType: 'json',
                    success: function(json) {
                        if (!json.budget) {
                            return;
                        }
                        var budgets = json.budget.active;
                        for(var i = 0; i < budgets.length; i++) {
                            var budget = budgets[i],
                                link = $('<a>').attr({
                                    budget: budget.id,
                                    reason: budget.reason,
                                    remaining: budget.remaining
                                });
                            link.text(budget.reason + ' ($' + budget.remaining + ')');
                            var item = $('<li>').append(link);
                            $('.modal-footer .dropup ul', modal).append(item);
                        }
                        $('.modal-footer .dropup ul a', modal).click(function(event) {
                            var budget = $(this).attr('budget');
                            $('input[name="source_id"]', modal).val(budget);
                            $('button[name="add"]', modal).html(
                                '<span>' + $(this).attr('reason') + '</span> ' +
                                '($' + $(this).attr('remaining') + ') ' +
                                '<span class="caret"></span>'
                            );
                            if (!$('button[name="add_funds"]', modal).length) {
                                var confirm = $('<button>')
                                    .attr({
                                        type: 'submit',
                                        name: 'add_funds'
                                    })
                                    .addClass('btn btn-primary')
                                    .text('Confirm add');
                                $('.modal-footer', modal).append(confirm);
                            }
                        });
                    }
                });
                $('button[name="add_funds"]', modal).click(function(event) {
                    if (!$('input[name="source_id"]', modal).val()) {
                        $('button[name="add_funds"] + button', modal).click();
                        return false;
                    }
                });

                $('input[name="amountToAdd"]', modal).change(function() {
                    var amountToAdd = parseFloat($('input[name="amountToAdd"]', modal).val()),
                        budgetAmount = parseFloat(data.amount);
                    var res = (isNaN(amountToAdd) ? 0 : amountToAdd) + (isNaN(budgetAmount) ? 0 : budgetAmount);
                    $('input[name="newBudgetTotal"]', modal).val(res.toFixed(2));
                });

                $('form', modal).submit(function() {
                    $.ajax({
                        url: './budget/update/' + $('#add_funds_to').val(),
                        data: {
                            receiver_id: userInfo.user_id,
                            reason: "",
                            amount: $('input[name="amountToAdd"]', modal).val(),
                            budget_seed: data.seed,
                            source_txt: $('input[name="budget-source"]', modal).val(),
                            source_id: $('input[name="source_id"]', modal).val(),
                            budget_note: ""
                        },
                        dataType: 'json',
                        type: "POST",
                        cache: false,
                        success: function(json) {
                            if (json.success) {
                                $(modal).modal('hide');
                            }
                        }
                    });
                    return false;
                });

            }
        });
    },

    showUpdateModal: function(budget_id) {
        $.ajax({
            type: "POST",
            url: './budget/info/' + budget_id,
            dataType: 'json',
            success: function(data) {
                if (!data.success) {
                    return;
                }
                var res = data.data;
                Utils.modal('budget-update', {
                    is_runner: is_runner,
                    data: res,
                    showSources: (res.req_user_authorized && res.seed == 1) || res.seed == 0,
                    open: function(modal) {
                        $('#budgetAllocated > td', modal).click(function() {
                            $(modal).modal('hide');
                            Budget.budgetExpand(0, budget_id);
                        });
                        $('#budgetSubmitted > td', modal).click(function() {
                            $(modal).modal('hide');
                            Budget.budgetExpand(1, budget_id);
                        });
                        $('#budgetPaid > td', modal).click(function() {
                            $(modal).modal('hide');
                            Budget.budgetExpand(2, budget_id);
                        });
                        $('#budgetTransferred > td', modal).click(function() {
                            $(modal).modal('hide');
                            Budget.budgetExpand(3, budget_id);
                        });
                        $('form', modal).submit(function() {
                            $.ajax({
                                type: "POST",
                                url: './budget/update/' + budget_id,
                                data: {
                                    budgetReason: $('input[name="budget-reason"]', modal).val(),
                                    budgetNote: $('textarea[name="budget-note"]', modal).val()
                                },
                                dataType: 'json',
                                success: function(json) {
                                    if (json && json.succeeded) {
                                        $(modal).modal("hide");
                                    }
                                }
                            });
                        });
                        $('button[name="closeOut"]', modal).click(function() {
                            $.ajax({
                                type: "POST",
                                url: './budget/close/' + budget_id,
                                dataType: 'json',
                                success: function(json) {
                                    if (json && json.succeeded) {
                                        $('#budget-update-modal').modal("hide");
                                     } else {
                                        alert(json.message);
                                    }
                                },
                                error: function(json) {
                                    alert('error in updateBudget');
                                }
                            });
                        });
                        $('button[name="addFunds"]', modal).click(function(){
                            $(modal).modal('hide');
                            Budget.showAddFundsModal(budget_id, res); //$('#add-funds-modal').modal('show');
                        });
                    }
                });
                return;
            }
        });
    },
    
    showBudgetHistory: function(page, user, modal) {
        Budget.page.budgetHistory = page = (page ? page : 1);
        $.ajax({
            url: './user/budgetHistory/' + user + '/' + page,
            dataType: 'json',
            success: function(json) {
                var title = "Budget History";
                var modalInit = function(modal) {
                    $('.modal-body tr[budget-id]', modal).click(function() {
                        $(modal).modal('hide');
                        Budget.showUpdateModal($(this).attr('budget-id'));
                    });
                    return false;
                };
                if (typeof modal == 'undefined') {
                    Budget.modal('budgetHistory', page, json, user, title, Budget.showBudgetHistory, modalInit);
                } else {
                    Budget.modalRefresh(modal, page, json, user, title, Budget.showBudgetHistory, modalInit);
                }
            }
        });
    },

    modal: function(name, page, json, user, title, pagination, fAfter) {
        Utils.modal(name, {
            data: json,
            title: title,
            pages: Budget.getPaginationData(json, page),
            first: (page == 1),
            last: (page == json.pages),
            open: function(modal) {
                if (pagination) {
                    $('.pagination a', modal).click(function() {
                        Budget.handlePaginationClick(this, page, function(newpage) {
                            pagination(newpage, user, modal);
                        });
                        return false;
                    });
                }
                if (fAfter) {
                    fAfter(modal);
                }
            }
        });
    },

    modalRefresh: function(modal, page, json, user, title, pagination, fAfter) {
        Utils.modalRefresh(modal, {
            data: json,
            title: title,
            pages: Budget.getPaginationData(json, page),
            first: (page == 1),
            last: (page == json.pages),
            success: function(modal) {
                if (pagination) {
                    $('.pagination a', modal).click(function() {
                        Budget.handlePaginationClick(this, page, function(newpage) {
                            pagination(newpage, user, modal);
                        });
                        return false;
                    });
                }
                if (fAfter) {
                    fAfter(modal);
                }
            }
        });
    },

    handlePaginationClick: function(which, current, fAfter) {
        var newpage = $(which).attr('goto');
        if ($(which).parent().hasClass('disabled')) {
            return;
        }
        if (newpage == 'prev') {
            newpage = parseInt(current) - 1;
        }
        if (newpage == 'next') {
            newpage = parseInt(current) + 1;
        }
        if (fAfter) {
            fAfter(newpage);
        }
    },

    getPaginationData: function(json, page) {
        var pages = [];
        var fromPage = 1;
        if (json.pages > 10 && page > 6) {
            if (page + 4 <= json.pages) {
                fromPage = page - 6;
            } else {
                fromPage = json.pages - 10;
            }
        }
        for (var i = fromPage; (i <= (fromPage +10) && i <= json.pages); i++) {
            pages.push({
                page: i,
                current: (i == page)
            });
        }
        return pages;
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
                // capitalize first letter for method to be displayed in title
                var title = 'Budget ' + method.charAt(0).toUpperCase() + method.slice(1);
                Utils.modal('budget', {
                    items: json.items,
                    title: title,
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
                    exportUrl: url + '.csv',
                    open: function(modal) {
                        // todo: add search behavior
                    }
                });
            });
        }
    }
};
