/*
 * jQuery Autogrow Text Area
 * version 1.0
 * It automatically adjusts the height on text area.
 *
 * Written by Jerry Luk jerry@presdo.com
 *
 * Based on Chrys Bader's Auto Expanding Text area www.chrysbader.com
 * and Craig Buckler's TextAreaExpander  http://www.sitepoint.com/blogs/2009/07/29/build-auto-expanding-textarea-1/
 *
 * Licensed under MIT license.
 */
 
(function($) {
  $.fn.autogrow = function(options) {
    var defaults = {
      expandTolerance: 1,
      enterToSubmit: false,
      autoCollapse: true /* false to have better performance */
    };
    options = $.extend(defaults, options);
    
    // IE and Opera should never set a textarea height of 0px
    var hCheck = !($.browser.msie || $.browser.opera) && options.autoCollapse;
    
    function resize(e, opts) {
      var $e            = $(e.target || e), // event or element
          contentLength = $e.val().length,
          elementWidth  = $e.innerWidth();
      
      opts = opts || { };
      
      if (contentLength != $e.data("autogrow-length") || elementWidth != $e.data("autogrow-width")) {
        var scrollPos = $(document).scrollTop();
        
        // For non-IE and Opera browser, it requires setting the height to 0px to compute the right height
        if (hCheck && (contentLength < $e.data("autogrow-length") || 
          elementWidth != $e.data("autogrow-width"))) {
          $e.css("height", "0px");
        }
        
        var extraLines = opts.extraLines ? opts.extraLines : 0;
        var height = Math.max($e.data("autogrow-min"), Math.ceil(Math.min(
          $e.attr("scrollHeight") + (options.expandTolerance + extraLines) * $e.data("autogrow-line-height"), 
          $e.data("autogrow-max"))));

        $e.css("overflow", ($e.attr("scrollHeight") > height ? "auto" : "hidden"));
        $e.css("height", height + "px");
        $(document).scrollTop(scrollPos);
      }
      
      return $e;
    };
    
    function parseNumericValue(v) {
      var n = parseInt(v, 10);
      return isNaN(n) ? null : n;
    };
    
    function initElement($e) {
      $e.data("autogrow-min", options.minHeight || parseNumericValue($e.css('min-height')) || 0);
      $e.data("autogrow-max", options.maxHeight || parseNumericValue($e.css('max-height')) || 99999);
      $e.data("autogrow-line-height", options.lineHeight || parseNumericValue($e.css('line-height')));
      resize($e);
    };
    
    function getSelectedText()
    {
      if (window.getSelection) {
        return window.getSelection().toString();
      } else if (document.getSelection) {
        return document.getSelection().toString();
      } else if (document.selection){
        return document.selection.createRange().text;
      }
      return "";
    }
    
    this.each(function() {
      var $this = $(this);
            
      if (!$this.data("autogrow-initialized")) {
        $this.css("padding-top", 0).css("padding-bottom", 0);
        $this.bind("keyup", resize).bind("focus", resize);
        $this.data("autogrow-initialized", true);
        $this.keydown(function(event) {
          if (event.keyCode == 13) {
            if (options.enterToSubmit) {
              $($(this).parents('form').get(0)).submit();
              event.preventDefault();
            } else if (getSelectedText() == "") {
              // Prepend an extra line to prevent flickering
              var $e = $(event.target || event);
              resize($e, { extraLines: 1 });
            }
          }
        });
        
      
        initElement($this);
        // Sometimes the CSS attributes are not yet there so the above computation might be wrong
        // 100ms delay will do the job
        setTimeout(function() { initElement($this); }, 100);
      }
    });
    
    return this;
  };
})(jQuery);
