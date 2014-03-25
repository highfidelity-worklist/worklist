var NewWorklist = {
    init: function() {
        // proxima-nova fonts
        if (typeof Typekit != 'undefined') {
            Typekit.load();
        }

        $(document).ajaxSend(function(event, request, settings) {
            if (settings.url == './status' && settings.type.toUpperCase() == 'POST') {
                return;
            }
            $('body').addClass('onAjax');
        });    
        $(document).ajaxComplete(function(event, request, settings) {
            $('body').removeClass('onAjax');
        });
    }
}