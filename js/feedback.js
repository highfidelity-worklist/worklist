    $(function(){
	$('.slide-out-div').show(); // to avoid div poping up on page load before Jquery is running
        $('.slide-out-div').tabSlideOut({
            tabHandle: '.handle',                     //class of the element that will become your tab
            pathToTabImage: 'images/feedback_tab.png', //path to the image for the tab //Optionally can be set using css
            imageHeight: '119px',                     //height of tab image           //Optionally can be set using css
            imageWidth: '26px',                       //width of tab image            //Optionally can be set using css
            tabLocation: 'right',                      //side of screen where tab lives, top, right, bottom, or left
            speed: 300,                               //speed of animation
            action: 'click',                          //options: 'click' or 'hover', action to trigger animation
            topPos: '150px',                          //position from the top/ use if tabLocation is left or right
            leftPos: '20px',                          //position from left/ use if tabLocation is bottom or top
            fixedPosition: false                      //options: true makes it stick(fixed position) on scroll
        });

	$('#feedback-submit').click(function(e){
	  var email = $('#feedback-email').val();
	  var message = $('#feedback-message').val();
    
	  if(message != ''){
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
