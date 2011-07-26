function toggleVis(el) {
    
    var element = document.getElementById(el)
    if (element.style.display == 'none') {
        element.style.display = '';
    } else {
        element.style.display = 'none';
    }
}

function toggleCBGroup(classname, check) {
    //toggle all checkboxes with classname
    var checklist = document.getElementsByTagName("input");
    for (i = 0; i < checklist.length; i++) {
        if ( (checklist[i].getAttribute("type") == 'checkbox') && (checklist[i].className == classname) ) {
           //if (checklist[i].checked) {   
        if (!check.checked) {   
                checklist[i].checked = false;
            } else {
                checklist[i].checked = true;
            }
        } 
    }
    
    //update Fees Total
    updateTotalFees('1');
}

function toggleCBs(option) {
    //toggle all checkboxes
    var checklist = document.getElementsByTagName("input");
    for (i = 0; i < checklist.length; i++) {
    if ( checklist[i].getAttribute("type") == 'checkbox' ) {
        if (option=='toggle') {
            if (checklist[i].checked) {   
                checklist[i].checked = false;
            } else {
                checklist[i].checked = true;
            }
        } 
        if (option=='select') {
            checklist[i].checked = true;
        }
        if (option=='unselect') {
            checklist[i].checked = false;
        }
    }   
    }
    
    //update Fees Total
    updateTotalFees('1');
}

function toggleBox(box) {
    cbox = document.getElementById(box);
    if (cbox.checked) {   
        cbox.checked = false;
    } else {
        cbox.checked = true;
    }
    
    //update Fees Total
    updateTotalFees('1');
}

function updateTotalFees(resA) {
    
    if (resA == '1') {
        resetAction();
    }
    var totalFees = 0.00;
    var checklist = document.getElementsByTagName("input");
    for (i = 0; i < checklist.length; i++) {
        if (checklist[i].getAttribute("type") == 'checkbox') {
        if (checklist[i].checked) {
        var fee = parseFloat(checklist[i].getAttribute("rel")); 
            totalFees = totalFees + fee;
            }
        }
    }
    var totalBox = document.getElementById("total-selected-fees");
    totalBox.value = totalFees.toFixed(2);
    
}

function resetAction() {
    // reset action to 'confirm' if it isn't
    var action = $('#action');
    var btn = $('#commit-btn');
    if (action.val() == 'pay') { action.val('confirm'); }
    if (btn.val() != 'Confirm') { btn.val('Confirm'); }
}

$(document).ready(function() {
    $('#fund_id').change(function() {
        $('#fundForm').submit();
    });

    $('#commit-btn').click(function(e) {
        $(this).attr('disabled', 'disabled');
        e.preventDefault();
        $(this).unbind('click');
        $('#paymentForm').submit();
    });
});
