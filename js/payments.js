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
    $('#paymentForm input[type="checkbox"]').each(function() {
        var element = $(this)[0];
        element.checked = (option=='select' ? true : (option=='unselect' ? false : !element.checked));
    });
    
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
    $('#paymentForm input[type="checkbox"]').each(function() {
        var element = $(this)[0];
        if (element.checked) {
            var fee = parseFloat(element.getAttribute("rel")); 
            totalFees = totalFees + fee;
        }
    });
    console.log(totalFees);
    $('#total-selected-fees').val(totalFees.toFixed(2));
    
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
    $('#fund_id').chosen();

    $('#commit-btn').click(function(e) {
        $(this).attr('disabled', true);
        e.preventDefault();
        $(this).unbind('click');
        $('#paymentForm').submit();
    });

    $('#invertSelection').click(function() {
        toggleCBs('toggle');        
    });
    $('#selectAll').click(function() {
        toggleCBs('select');        
    });
    $('#selectNone').click(function() {
        toggleCBs('unselect');        
    });

    updateTotalFees('0');
});
