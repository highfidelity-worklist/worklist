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
        $(document).ajaxComplete(function() {
            $('body').removeClass('onAjax');
        });
    }
}

$(document).ready (function () {
    $('.navbar').affix({
        offset: {
        top: function () { 
                return $('.navbar').outerHeight();
            }
        }
    });
    
    $('.navbar').on('affix.bs.affix', function () {
        if ($('.dropdown').hasClass('open'))
            $('.dropdown-toggle').dropdown('toggle');
        
        if ($('.navbar-collapse').hasClass('in'))
            $('.navbar-collapse').collapse('toggle');
        
        $('body').css('padding-top', $('.navbar').outerHeight() + 'px');
         
        /* Set a range between navbar height and navbar height and a half
           to show a nice fade in effect, this will only happens if the user
           scrolls slowly */
        if ($('body').scrollTop() < $('.navbar').outerHeight() * 1.5) {
            $('.navbar').hide();
            $('.navbar').fadeIn("slow");
        }
    });

    $('.navbar').on('affix-top.bs.affix', function () {
        $('body').css('padding-top', '0px');
    });
});