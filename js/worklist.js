//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

function RelativeTime(x){
    var plural = '';
 
    var mins = 60, hour = mins * 60; day = hour * 24,
        week = day * 7, month = day * 30, year = day * 365;

    if (x >= year) { x = (x / year)|0; dformat="yr"; }
    else if (x >= month) { x = (x / month)|0; dformat="mnth"; }
    else if (x >= day) { x = (x / day)|0; dformat="day"; }
    else if (x >= hour) { x = (x / hour)|0; dformat="hr"; }
    else if (x >= mins) { x = (x / mins)|0; dformat="min"; }
    else { x |= 0; dformat="sec"; }
    if (x > 1) plural = 's';
    return x + ' ' + dformat + plural + ' ago';
}

$(document).ready(function(){
	  $("#search").click(function(e){
    e.preventDefault();
	$("#searchForm").submit();
        return false;
    });
    $("#search_reset").click(function(e){
    	
    	e.preventDefault();
    	
        $("#query").val('');    
   		 
        GetWorklist(1,false);
        
        return false;
    });


    $("#searchForm").submit(function(){   
    	
        $("#loader_img").css("display","block");
        
        GetWorklist(1,false);
                    
        return false;
    });
     
});
