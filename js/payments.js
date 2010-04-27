
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
    //reset action to 'confirm' if it isn't
    action = document.getElementById('action');
    btn = document.getElementById('commit-btn');
    if (action.value == 'pay') { action.value = 'confirm'; }
    if (btn.value != 'Confirm') { btn.value = 'Confirm'; }
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
    //reset action to 'confirm' if it isn't
    action = document.getElementById('action');
    btn = document.getElementById('commit-btn');
    if (action.value == 'pay') { action.value = 'confirm'; }
    if (btn.value != 'Confirm') { btn.value = 'Confirm'; }
}

function toggleBox(box) {
    cbox = document.getElementById(box);
    if (cbox.checked) {   
        cbox.checked = false;
    } else {
        cbox.checked = true;
    }
    //reset action to 'confirm' if it isn't
    action = document.getElementById('action');
    btn = document.getElementById('commit-btn');
    if (action.value == 'pay') { action.value = 'confirm'; }
    if (btn.value != 'Confirm') { btn.value = 'Confirm'; }
}
