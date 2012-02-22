/* Menu Popup Extension for jQuery
 *
 * Creates a popup menu that is displayed when a mouseup event is triggered for
 * one of the elements in the matching set and there is text selected.  The menu
 * may contain static header elements, separators, and regular items.
 *
 * Regular items have a callback associated with them that will be called when
 * the item is clicked.  Callback functions are passed the selected text and
 * the menu data passsed to the menuPopup call for the item selected.
 *
 * Usage:
 *   $('entries-text').menuPopup(
 *      [
 *          { type: 'heading', title: 'Social Links' },
 *          { type: 'separator' },
 *          { type: 'item',  title: 'Twitter', fn: function(){
 *              alert('twitter');
 *              return false; } }
 *      ]);
 */

(function($) {
    $.fn.menuPopup = function(menu, options) {
        var opts = $.extend({}, $.fn.menuPopup.defaults, options);

        return this.each(function() {
            var $this = $(this);
            var o = $.meta ? $.extend({}, opts, $this.data()) : opts;

            $this.unbind('mouseup').mouseup(function(e){
              if((o.context && e.button == 2) || !o.context){
                $('#menuPopup').remove();
                var text = $(this).getSelection();
                if (o.selectionRequired && text.length == 0) return;

		var targetEl = $(this);
                var menuHTML = '<div id="menuPopup"><ul>';
                for (var i = 0; i < menu.length; i++) {
                    if (menu[i].type == 'heading') {
                        menuHTML += '<li class="menuTitle">' + menu[i].title + '</li>';
                    } else if (menu[i].type == 'item') {
                        menuHTML += '<li class="menuItem">' + menu[i].title + '</li>';
                    } else if (menu[i].type == 'separator') {
                        menuHTML += '<li class="menuSeparator">&nbsp;</li>';
                    }
                }
                menuHTML += '<li class="menuFooter">&nbsp;</li>';
                menuHTML += '</ul></div>';

                var menuPopup = $(menuHTML).appendTo('body');
                menuPopup.css({ top: e.pageY - menuPopup.height() - 12, left: e.pageX - 50 });
                menuPopup.find('li.menuItem').mouseup(function(e){
                    menuPopup.remove();

                    /* Call the item callback function */
                    for (var i = 0; i < menu.length; i++) {
                        if (menu[i].title == $(this).text() && menu[i].fn) {
                            return menu[i].fn(targetEl, text, menu[i]);
                        }
                    }
                });

		var popupHide = function(e) {
                    $(this).unbind('mouseup');
                    $('#menuPopup').remove();
                    e.stopPropagation();
                    return false;
                }
                $(document).mouseup(popupHide);

                return false;
              } 

            });
        });
    }

    $.fn.menuPopup.defaults = {
	'selectionRequired': true,
	'context': false
    }

})(jQuery);
