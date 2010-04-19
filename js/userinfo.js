$(document).ready(function(){

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
