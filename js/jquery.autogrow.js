/*!
 * Autogrow Textarea Plugin Version v2.0
 * http://www.technoreply.com/autogrow-textarea-plugin-version-2-0
 *
 * Copyright 2011, Jevin O. Sewaruth
 *
 * Date: March 13, 2011
 */
jQuery.fn.autogrow = function() {
    return this.each(function() {
        // Variables
        var colsDefault = this.cols;
        var rowsDefault = 5;
        
        //Functions
        var grow = function() {
            growByRef(this);
        }
        
        var growByRef = function(obj) {
            var linesCount = 0;
            var lines = obj.value.split('\n');
            
            for (var i=lines.length-1; i>=0; --i)
            {
                linesCount += Math.floor((lines[i].length / colsDefault) + 1);
            }

            if (linesCount >= rowsDefault)
                obj.rows = linesCount + 1;
            else
                obj.rows = rowsDefault;
        }
        
        
        // Manipulations
        this.style.overflow = "hidden";
        this.style.height = "auto";
        this.onkeyup = grow;
        this.onfocus = grow;
        this.onblur = grow;
        growByRef(this);
    });
};