/* jQuery ui.toaster.js - 0.2
 *
 * (c) Maxime Haineault <haineault@gmail.com>
 * http://haineault.com
 *
 * MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * Inspired by experimental ui.toaster.js by Miksago (miksago.wordpress.com)
 * Thanks a lot.
 *
 * */

if ($.ui) {
$.widget('ui.toaster', {
        _init: function(){
                var self        = this;
                var wrapper = '#ui-toaster-'+ self.options.position;

                if (!$(wrapper).get(0)) {
                        $('<div />').attr('id', 'ui-toaster-'+ self.options.position).appendTo('body');
                }

                self.toaster = $('<div style="display:none;" class="ui-toaster" />')
                        .append($('<span class="ui-toaster-border-tr" /><span class="ui-toaster-border-tl" /><span class="ui-toaster-border-tc" />'))
                        .append($('<span class="ui-toaster-body" />').html($('<div />').append($(self.element).html())))
                        .append($('<span class="ui-toaster-border-br" /><span class="ui-toaster-border-bl" /><span class="ui-toaster-border-bc" />'))
                        .width(self.options.width)
            .hover(function(){ self.pause.apply(self)}, function(){ self.resume.apply(self)})
                        [(self.options.position.match(/bl|br/)) ? 'prependTo': 'appendTo'](wrapper);

                // Closable
                if (self.options.closable) {
                        self.toaster.addClass('ui-toaster-closable');
                        if ($(self.toaster).find('.ui-toaster-close').length > 0) {
                                $('.ui-toaster-close', $(self.toaster)).click(function(){ self.hide.apply(self); });
                        }
                        else {
                                $(self.toaster).click(function(){ self.hide.apply(self); });
                        }
                }

                // Sticky
                if (self.options.sticky) {
                        $(self.toaster).addClass('ui-toaster-sticky');
                }
                else {
                        self.resume();
                }
               
                // Delay
                if (!!self.options.delay) {
                   setTimeout(function(){
                                self.open.apply(self);
                        }, self.options.delay * 1000);
                }
                else {
                        self.open.apply(self);
                }
    },

        open: function() {
                this.options.show.apply(this.toaster);
    },

        hide: function(){
                if (this.options.onHide) this.options.onHide.apply(this.toaster);
                this.close(this.options.hide);
        },

        close: function(effect) {
                var self   = this;
                var effect = effect || self.options.close;
                if (self.options.onClose) {
                        effect.apply(self.toaster);
                }
                effect.apply(self.toaster, [self.options.speed, function(){
                        if (self.options.onClosed) self.options.onClosed.apply(self.toaster);
                        $(self.toaster).remove();
            }]);
    },

        resume: function() {
                var self = this;
                self.timer = setTimeout(function(){
                        self.close.apply(self);
                }, self.options.timeout * 1000 + self.options.delay * 1000);
        },

        pause: function() { clearTimeout(this.timer); }
});

$.ui.toaster.defaults = {
        delay:    0,      // delay before showing (seconds)
        timeout:  3,      // time before hiding (seconds)
        width:    200,    // toast width in pixel
        position: 'br',   // tl, tr, bl, br
        speed:    'slow', // animations speed
        closable: true,   // allow user to close it
        sticky:   false,  // show until user close it
        onClose:  false,  // callback before closing
        onClosed: false,  // callback after closing
        onOpen:   false,  // callback before opening
        onOpened: false,  // callback after opening
        onHide:   false,  // callback when closed by user
        show:     $.fn.slideDown, // showing effect
        hide:     $.fn.fadeOut,   // closing effect (by user)
        close:    $.fn.slideUp    // hiding effect (timeout)
};
}