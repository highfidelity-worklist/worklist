    $(function(){
	$('.slide-out-div').show(); // to avoid div poping up on page load before Jquery is running
        $('.slide-out-div').tabSlideOut({
            tabHandle: '.handle',                     //class of the element that will become your tab
            pathToTabImage: 'images/feedback_tab.png', //path to the image for the tab //Optionally can be set using css
            imageHeight: '22px',                     //height of tab image           //Optionally can be set using css
            imageWidth: '82px',                       //width of tab image            //Optionally can be set using css
            tabLocation: 'top',                      //side of screen where tab lives, top, right, bottom, or left
            speed: 300,                               //speed of animation
            action: 'click',                          //options: 'click' or 'hover', action to trigger animation
            topPos: '150px',                          //position from the top/ use if tabLocation is left or right
            leftPos: (function(){					  /*center feedback to body's innerWeight*/
				return Math.round(($('body').innerWidth() - $('.slide-out-div').width())/2); 	
			})(),                   				//position from left/ use if tabLocation is bottom or top
            fixedPosition: false                      //options: true makes it stick(fixed position) on scroll
        });
		
	// Since slide-out won't let text into the tab, we'll force it	
	$('.handle').css({
		'text-align':'center',
		'text-indent':'0px',
		'line-height':'22px',
		'font-family':'Arial, Helvetica, Sans-serif',
		'font-size':'13px',
		'font-weight':'bold',
		'color':'#444'
	});
	// ---  
	
	$('#feedback-submit').click(function(e){
	  var email = $('#feedback-email').val();
	  var message = $('#feedback-message').val();
    
	  if(message != ''){
	    $('.LV_validation_message').remove();
	    $('.handle').click();
	    $('#feedback-message').val('');
	    $.post( 
	      'feedback.php', 
	     {message: message, email: email}, 
	     function(){
		$('<div><p>Your feedback was sent.<br />Thank you!</p></div>').toaster({
		  position: 'tr',
		  timeout: 3,
		  show: $.fn.fadeIn,
		  close: $.fn.fadeOut
		});
	      });
	  }

	  return false;
	});

    }); 
