
var Entries = {
    formatWorklistStatus: function() {
        $('#entries > li[type="worklist"]:not([class^=status])').each(function() {
            var text = '';
            $('p', $(this)).each(function(){
                text += text ? '. ' : '';
                text += $(this).text();
            });

            var updated_regex = /\d+\s(?:updated|created)\sby\s\w+.+Status\sset\sto\s+(\w+)\.?\s*$/i;
            var ret = updated_regex.exec(text);
            if (ret && ret[1]) {
                $(this).addClass('status' + ret[1]);
            }
        });        
    }
}