//data-spy="affix" data-offset-top="10" data-offset-bottom="200"
var Help = {
    sidebarWidth: 0,

    init: function() {
        $('#sidebar').affix({
            offset: {
                top: 0,
                bottom: function () {
                    return this.bottom = $("#footer").outerHeight();
                }
            }
        });
        $('#sidebar').on('affix.bs.affix', function() {
            $('#sidebar').css({'width': ''});
            Help.sidebarWidth = $(this).outerWidth();
        });
        $('#sidebar').on('affixed.bs.affix', function() {
            $('#sidebar').css({'width': Help.sidebarWidth + 'px'});
        });

        $('body').scrollspy({ target: '#sidebar' });
    }
};
