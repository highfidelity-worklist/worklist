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

/*
 *   Function: AjaxPopup
 *
 *    Purpose: This function is used for popups that require additional information from
 *             the server and uses an Ajax post call to query the server.
 *
 * Parameters: popupId - The id element for the block holding the popup's html
 *             titleString - The title for the popup box
 *             urlString - The URL to issue the Ajax call to 
 *             keyId - The database id that will be mapped to 'itemid' in the form
 *             fieldArray - An array containing the list of fields that need
 *                          to be updated on the popup box.
 *                array[0] - Type of element being populated [input|textbox|checkbox|span]
 *                array[1] - Type if of the element being populated
 *                array[2] - The value to be inserted into the element 
 *                array[3] - undefined or 'eval' - If eval the array[2] item will
 *                           be passed to eval() for working with json return objects
 *             successFunc - An optional function that gets executed after populating the fields.
 *
 */
function AjaxPopup(popupId,
		   titleString,
		   urlString,
		   keyId,
		   fieldArray,
		   successFunc)
{
  $(popupId).data('title.dialog', titleString);

  $.ajax({type: "POST",
	  url: urlString,
	  data: 'item='+keyId,
	  dataType: 'json',
	  success: function(json) {

	    $.each(fieldArray, 
		   function(key,value){
		     if(value[0] == 'input') {
		       if(value[3] != undefined && value[3] == 'eval')  {
			 $('.popup-body form input[name="' + value[1] +'"]').val( eval(value[2]) );
		       } else {
			 $('.popup-body form input[name="' + value[1] +'"]').val( value[2] );
		       }
		     }
		     
		     if(value[0] == 'textarea') {
		       if(value[3] != undefined && value[3] == 'eval')  {
			 $('.popup-body form textarea[name="' + value[1] +'"]').val( eval(value[2]) );
		       } else {
			 $('.popup-body form textarea[name="' + value[1] +'"]').val( value[2] );
		       }
		     }
		     
		     if(value[0] == 'checkbox') {
		       if(value[3] != undefined && value[3] == 'eval')  {
			 $('.popup-body form checkbox[name="' + value[1] +'"] option[value="'+ eval(value[2])+'"]').attr('checked','checked');	     
		       } else {
			 $('.popup-body form checkbox[name="' + value[1] +'"] option[value="'+ value[2] +'"]').attr('checked','checked');	     
		       }
		     }
		     
		     if(value[0] == 'span')  {
		       if(value[3] != undefined && value[3] == 'eval')  {
			 $('.popup-body form ' + value[1]).text( eval(value[2]) );
		       } else {
			 $('.popup-body form ' + value[1]).text( value[2] );
		       }
		     }
		   });

	    if(successFunc !== undefined) {
	      successFunc(json);
	    }
            }
    });

  
}

/*
 *   Function: SimplePopup
 *
 *    Purpose: This function is used for popups that do not require additional 
 *             calls to the server to grab data.
 *
 * Parameters: popupId - The id element for the block holding the popup's html
 *             titleString - The title for the popup box
 *             keyId - The database id that will be mapped to 'itemid' in the form
 *             fieldArray - An array containing the list of fields that need
 *                          to be updated on the popup box.
 *                array[0] - Type of element being populated [input|textbox|checkbox|span]
 *                array[1] - Type if of the element being populated
 *                array[2] - The value to be inserted into the element 
 *                array[3] - undefined or 'eval' - If eval the array[2] item will
 *                           be passed to eval() for working with json return objects
 *             successFunc - An optional function that gets executed after populating the fields.
 *
 */
function SimplePopup(popupId,
		     titleString,
		     keyId,
		     fieldArray,
		     successFunc)
{
  $(popupId).data('title.dialog', titleString);

  $.each(fieldArray, 
	 function(key,value){
	   if(value[0] == 'input') {
	     if(value[3] != undefined && value[3] == 'eval')  {
	       $('.popup-body form input[name="' + value[1] +'"]').val( eval(value[2]) );
	     } else {
	       $('.popup-body form input[name="' + value[1] +'"]').val( value[2] );
	     }
	   }
	   
	   if(value[0] == 'textarea') {
	     if(value[3] != undefined && value[3] == 'eval')  {
	       $('.popup-body form textarea[name="' + value[1] +'"]').val( eval(value[2]) );
	     } else {
	       $('.popup-body form textarea[name="' + value[1] +'"]').val( value[2] );
	     }
	   }
	   
	   if(value[0] == 'checkbox') {
	     if(value[3] != undefined && value[3] == 'eval')  {
	       $('.popup-body form checkbox[name="' + value[1] +'"] option[value="'+ eval(value[2])+'"]').attr('checked','checked');	     
	     } else {
	       $('.popup-body form checkbox[name="' + value[1] +'"] option[value="'+ value[2] +'"]').attr('checked','checked');	     
	     }
	   }
	   
	   if(value[0] == 'span')  {
	     if(value[3] != undefined && value[3] == 'eval')  {
	       $('.popup-body form ' + value[1]).text( eval(value[2]) );
	     } else {
	       $('.popup-body form ' + value[1]).text( value[2] );
	     }
	   }
	   
	 });

  if(successFunc !== undefined) {
    successFunc(json);
  }
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
