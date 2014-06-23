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

        $('a[href^="./github/login"]').click(NewWorklist.loginClick);

        /**
         * Initialize js objects used by worklist
         * previously wrapped on jQuery.load functions
         */
        Budget.init();
        UserStats.init();
    },

    loginClick: function(event) {
        event.preventDefault();

        var href = $(this).attr('href');

        var doNotShowGithubNote = false;

        // Try to get localStorage value, but if it's not available in this browser, use the default value of `false`
        try {
            doNotShowGithubNote = localStorage.getItem('doNotShowGithubNote');
        } catch(e) {
        }

        if (doNotShowGithubNote) {
            window.location = href;
        } else {
            var message = "<strong>We do require access to private repositories</strong>, "
                + "but only those that @highfidelity manages. We will not read or write to your "
                + "private repositories that weren't forked from @highfidelity."
                + "<br><br><label><input type='checkbox' name='doNotShow'> Do not show this message again</input></label>";
            Utils.emptyModal({
                title: "GitHub Authentication",
                content: message,
                buttons: [{
                    type: 'button',
                    content: 'Log in with GitHub',
                    className: 'btn-primary',
                    dismiss: true
                }],
                close: function(el) {
                    var selected = $(el).find('input[name="doNotShow"]')[0].checked;
                    if (selected) {
                        try {
                            localStorage.setItem('doNotShowGithubNote', 'true');
                        } catch(e) {
                        }
                    }
                    window.location = href;
                },
            });
        }
    }
}
