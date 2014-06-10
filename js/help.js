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
        $('#sidebar').on('affixed.bs.affix', Help.refresh);
        if ($('#sidebar').is('.affix')) {
            Help.setAffixedSidebarWidth();
        }

        $('body').scrollspy({
            target: '#sidebar',
            offset: 100
        });

        $('#sidebar a[href]').click(function(e) {
            e.preventDefault();
            var scrollTo = $($(this).attr('href')).offset().top - 70;
            $('body').scrollTo(scrollTo, 400);
        });

        window.addEventListener('resize', Help.refresh, false);

        /**
         * refreshing the affix two seconds since loaded seems to give better
         * results with positioning issues, I guess that it might be cause by
         * styling files not loaded already and thus scrollspy has outdated
         * calculation results
         * 10-JUN-2014 <kordero>
         */
        Help.refreshTimeout = setTimeout(Help.refresh, 2000);
    },

    refresh: function() {
        $('#sidebar').css({'width': $('#sidebar').parent().width() + 'px'});
        $('body').scrollspy('refresh');
    }
};
