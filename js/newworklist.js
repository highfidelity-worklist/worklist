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

$(document).ready (function (){
    $('.navbar').affix({
        offset: {
        top: function (){ 
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
         
        if ($('body').scrollTop() < $('.navbar').outerHeight() * 1.5){
            $('.navbar').hide();
            $('.navbar').fadeIn("slow");
        }
    });

    $('.navbar').on('affix-top.bs.affix', function () {
        $('body').css('padding-top', '0px');
    });
});