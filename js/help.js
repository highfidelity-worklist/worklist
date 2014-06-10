var Help = {
    init: function() {
        $('#sidebar').affix({
            offset: {
                top: 150,
                bottom: 300
            }
        });
        $('#sidebar').on('affix.bs.affix', function() {
            $(this).css({'width': ''});
        });
        $('#sidebar').on('affixed.bs.affix', Help.setAffixedSidebarWidth);
        if ($('#sidebar').is('.affix')) {
            Help.setAffixedSidebarWidth();
        }

        $('body').scrollspy({
            target: '#sidebar',
            offset: 100
        });

        $('#sidebar a[href]').click(function(e) {
            e.preventDefault();
            var scrollTo = $($(this).attr('href')).offset().top;
            $('body').scrollTo(scrollTo);
        });

        window.addEventListener('resize', Help.setAffixedSidebarWidth, false);
    },

    setAffixedSidebarWidth: function() {
        $('#sidebar').css({'width': $('#sidebar').parent().width() + 'px'});
    }
};
