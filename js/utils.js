//  vim:ts=4:et
//
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

var activeUsersFlag = 1;

function refreshUsersFilter() {
	if (activeUsersFlag) {
		activeUsersFlag = 0;
	} else {
		activeUsersFlag = 1;
	}
	
	$.ajax({
		type: 'POST',
		url: 'refresh-filter.php',
		data: 'name='+filterName+'&active='+activeUsersFlag,
		dataType: 'data',
		success:function(data) {
			var parent = $('#userSelection').parent();
			$('#userSelection').remove();
			parent.prepend(data);
            // If we are in worklist reattach the autoupdate event
            if (filterName == ".worklist") {
                reattachAutoUpdate();
            }
		}
	});
}
