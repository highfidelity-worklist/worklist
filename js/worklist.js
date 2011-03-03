//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

var activeUsersFlag=1;

function RelativeTime(x){
    var plural = '';
 
    var mins = 60, hour = mins * 60; day = hour * 24,
        week = day * 7, month = day * 30, year = day * 365;

    if (x >= year) { x = (x / year)|0; dformat="yr"; }
    else if (x >= month) { x = (x / month)|0; dformat="mnth"; }
    else if (x >= day*4) { x = (x / day)|0; dformat="day"; }
    else if (x >= hour) { x = (x / hour)|0; dformat="hr"; }
    else if (x >= mins) { x = (x / mins)|0; dformat="min"; }
    else { x |= 0; dformat="sec"; }
    if (x > 1) plural = 's';
    if (x < 0) x = 0;
    return x + ' ' + dformat + plural;
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

/* When applied to a textfield or textarea provides default text which is displayed, and once clicked on it goes away
 Example:  $("#name").DefaultValue("Your fullname.");
*/
jQuery.fn.DefaultValue = function(text){
    return this.each(function(){
    //Make sure we're dealing with text-based form fields
    if(this.type != 'text' && this.type != 'password' && this.type != 'textarea')
      return;
    
    //Store field reference
    var fld_current=this;
    
    //Set value initially if none are specified
        if(this.value=='' || this.value == text) {
      this.value=text;
    } else {
      //Other value exists - ignore
      return;
    }
    
    //Remove values on focus
    $(this).focus(function() {
      if(this.value==text || this.value=='')
        this.value='';
    });
    
    //Place values back on blur
    $(this).blur(function() {
      if(this.value==text || this.value=='')
        this.value=text;
    });
    
    //Capture parent form submission
    //Remove field values that are still default
    $(this).parents("form").each(function() {
      //Bind parent form submit
      $(this).submit(function() {
        if(fld_current.value==text) {
          fld_current.value='';
        }
      });
    });
    });
};

$(function() {
	var hideInputField = function() {
		// if the status is not empty - hide input field, otherwise do not hide input
		if( $('#status-lbl').find('b').html() != "" ) {
			$('#status-update').hide();
			$("#status-share").hide();
			$('#status-lbl').show();
		}
	};
	$("#status-share").hide();
	$('#share-this').hide();
	$("#status-update").DefaultValue("What are you working on?");
	$('#status-update').hide();
	$("#query").DefaultValue("Search...");
    $("#feesDialog").dialog({
        title: "Earnings",
        autoOpen: false,
        height: 'auto',
        width: '200px',
        position: ['center',60],
        modal: true
    });
    //debugger;
	$("#welcome .earnings").click(function(){
        $("#feesDialog").dialog("open");
    });
    if ($("#budgetPopup").length > 0) {
        $("#welcome .budget").html('| <a href="javascript:;" class="budget">Budget</a> ');
        $("#budgetPopup").dialog({
            title: "Budget",
            autoOpen: false,
            height: 'auto',
            width: '250px',
            position: ['center',60],
            modal: true
        });
        $("#welcome .budget").click(function(){
            $("#budgetPopup").dialog("open");
        });
    }
	// if the status is empty, show input field - allow user to enter the status
	if( $.trim($('#status-lbl').find('b').html()) == "" ) {
		$('#status-lbl').hide();
		$('#status-update').show();
		$("#status-share").show();
	} else {
		$('#status-lbl').show();
	}
	
	// When status-update gets focus enlarge and show the share button
	$("#status-update").focus(function() {		
		$("#status-update").data("focus",true);		
		$("#status-share").show();
	});

	//When status-update lost the focus, hide input field ... 
	$("#status-update").blur(function() {
	// if the blur event is coming due to a click on button "Share", we need to delay the hidding process.
	// if not the click event on the hidden button is not triggered.
		setTimeout(function() { 
			hideInputField();
			$("#status-update").data("focus",false);		
		},500);
	});
	
	$("#status-lbl").mouseenter(function() {
		$('#status-lbl').hide();
		$('#status-update').show();
		$('#status-share').show();
	});
	
	//When status-update hasn't the focus and mouse leaves status-wrap, hide input field ...
	$("#status-wrap").mouseleave(function(){
		if ($("#status-update").data("focus") !== true) {
			hideInputField();
		}
	});
	//Submit the form using AJAX to the database
	$("#status-share-btn").click(function() {
		if($("#status-update").val() == "")	{
			//return false;
		}
		if($("#status-update").val() ==  "What are you working on?"){
			$("#status-update").val("");
		}
		$.ajax({
			url: "update_status.php",
			type: "POST",
			data: "action=update&status=" + $("#status-update").val(),
			dataType: "text",
			success: function(){
				// if entered blank status - do not hide input
				if ($("#status-update").val()!="") {
					$('#status-update').hide();$('#status-lbl').show();
					$("#status-share").hide();
					$('#share-this').hide();
				} 
				$('#status-lbl').html( '<b>' + $("#status-update").val() + '</b>' );
			}
		});
		
		return false;
	});
    
});

/* get analytics info for this page */
$(function() {
    $.analytics = $('#analytics');
    if($.analytics) {
        var jobid=$.analytics.attr('data');
        $.ajax({
            url: 'visitQuery.php?jobid='+jobid,
            dataType: 'json',
            success: function(json) {
                if(parseInt(json.visits)+parseInt(json.views) == 0)
                {
                    $.analytics.hide();
                    return;
                }
                var p = $('<p>').html('Page views');
                p.append($('<span>').html(' Unique: ' + json.visits))
                p.append($('<span>').html(' Total: ' + json.views));
                $.analytics.append(p);
            },
        });
    }
});

$(function() {
    $(".actionBidding").attr("href","javascript:;").click(function(){
        $("#search-filter-wrap select[name=status]").comboBox({action:"val",param: ["BIDDING"]});
     //   GetWorklist(1,false);       
        return false;
    });
    $(".actionUnderway").attr("href","javascript:;").click(function(){
        $("#search-filter-wrap select[name=status]").comboBox({action:"val",param: ["WORKING","REVIEW","COMPLETED"]});
        return false;
    });

		// to add a custom stuff we bind on events
		$('select[name=user]').bind({
			'beforeshow newlist': function(e, o) {
				// now we create a new li element with a checkbox in it
				var li = $('<li/>').css({
					left: 0,
					position: 'absolute',
					background: '#AAAAAA',
					width: '123px',
					top: '180px'
				});
				var label = $('<label/>').css('color', '#ffffff').attr('for', 'onlyActive');
				var checkbox = $('<input/>').attr({
					type: 'checkbox',
					id: 'onlyActive'
				}).css({
						margin: 0,
						position: 'relative',
						top: '1px'
				});

				// we need to update the global activeUsersFlag
				if (activeUsersFlag) {
					checkbox.attr('checked', true);
				} else {
					checkbox.attr('checked', false);
				}

				label.text(' Active only');
				label.prepend(checkbox);
				li.append(label);

				// now we add a function which gets called on click
				li.click(function(e) {
					// we hide the list and remove the active state
					activeUsersFlag = 1- activeUsersFlag ;
					o.list.hide();
					o.container.removeClass('ui-state-active');
					// we send an ajax request to get the updated list
					$.ajax({
						type: 'POST',
						url: 'refresh-filter.php',
						data: {
							name: filterName,
							active: activeUsersFlag,
                            filter: 'users'
						},
						dataType: 'json',
						// on success we update the list
						success: $.proxy(o.setupNewList, null,o)
					});
					// just to be shure nothing else gets called we return false
					return false;
				});

				// the scroll handler so our new listelement will stay on the bottom
				o.list.scroll(function() {
					/**
					 * With a move of 180, the position is too far, and the scroll never ends.
					 * The calculation has been made using heights of the elements but it doesn't work on MAC/Firefox (still some px too far, border size ??)
					 * The value has been fixed to 178 under MAC and calculated on other platforms (coder with a MAC could investigate this)
					 * 8-JUNE-2010 <vincent> - Ticket #11458		
					 */
					if (navigator.platform.indexOf("Mac") == 0) {
						li.css('top', ($(this).scrollTop() + 178) + 'px');
					} else {
						li.css('top', ($(this).scrollTop() + $(this).height() - li.outerHeight(true)) + 'px');
					}
				});
				// now we append the list element to the list
				o.list.append($('<li>&nbsp</li>'));
				o.list.append(li);
				o.list.css("z-index","100"); // to be over other elements
			}
		}).comboBox();
		$('#search-filter-wrap select[name=status]').comboBox();

});

function sendInviteForm(){
  var name = $('input[name="invite"]').val();
  var job_id = $('input[name="worklist_id"]').val();
  $.ajax({
    type: "POST",
	url: "workitem.php?job_id="+job_id,
	data: "json=y&invite="+name+"&invite-people=Invite",
	dataType: "json",
	success: function(json) {
		if(json['sent'] =='yes'){
			$("#sent-notify").html("<span>invite sent to <strong>"+name+"</strong></span>");
			$('input[name="invite"]').val('');
		}else{
			$("#sent-notify").html("<span>The user you entered does not exist</span>");
		}
		$("#sent-notify").dialog("open");
	},
	error: function(xhdr, status, err) {
	  $("#sent-notify").html("<span>Error sending invitation</span>");
	}
  });
  return false;
}
