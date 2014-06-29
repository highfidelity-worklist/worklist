
var Entries = {
    formatWorklistStatus: function() {
        $('#entries > li[type="worklist"]:not([class^=status])').each(function() {
            var text = '';
            $('p', $(this)).each(function(){
                text += text ? '. ' : '';
                text += $(this).text();
            });

            var updated_regex = /\w+\s+(?:accepted|updated|created).+Status\sset\sto\s+(.+?)\.\s*$/i;
            var ret = updated_regex.exec(text);
            if (ret && ret[1]) {
                $(this).addClass('status' + ret[1]);
                $(this).addClass('status' + ret[1].replace(' ', ''));
            }
        });
    }
}
//$string =~ s/\s//g;