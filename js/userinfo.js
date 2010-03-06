var installPaypalHandler = function() {
    var disabler = function(el, elDisable) {
        if (el.attr('checked')) {
            elDisable.attr('disabled', '');
        } else {
            elDisable.attr('disabled', 'disabled');
        }
    }

    $('#username').change(function(){
        if ($('#paypal_email').val() == '') $('#paypal_email').val($('#username').val());
    });
    $('#paypal').change(function(){
        disabler($(this), $('#paypal_email'));
    });
    disabler($('#paypal'), $('#paypal_email'));
}

$(document).ready(function(){

    installPaypalHandler();

    $("#skills").autocomplete(skills, {
	    width: 320,
	    max: 10,
	    highlight: false,
	    multiple: true,
	    multipleSeparator: ", ",
	    scroll: true,
	    scrollHeight: 300
    });

}); 
