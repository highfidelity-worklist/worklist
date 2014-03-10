var NewWorklist = {
    init: function() {
        // proxima-nova fonts
        Typekit.load();

        $(document).ajaxSend(function(event, request, settings) {
            if ($('#ajaxSpin').length > 0) {
                return
            };
            if ($("#loader_img").css('display') != "block") {
                $('<figure id="ajaxSpin"><img src="images/loader.gif" /></figure>').appendTo('body');
            }
        });
        
        var clearAjaxSpin = function() {
            $('#ajaxSpin').remove();
        };
        
        $(document).ajaxComplete(clearAjaxSpin);        
    }
}