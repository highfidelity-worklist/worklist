jQuery.fn.center = function () {
    this.css("position", "absolute");
    this.css("top", (( $(window).height() - this.outerHeight() ) / 2 ) + "px");
    this.css("left", (( $(window).width() - this.outerWidth() ) / 2 ) + "px");
    return this;
}

/*
show a message with a wait image
several asynchronus calls can be made with different messages
*/
var loaderImg = function($)
{
    var aLoading = new Array(),
        _removeLoading = function(id) {
            for (var j=0; j < aLoading.length; j++) {
                if (aLoading[j].id == id) {
                    if (aLoading[j].onHide) {
                        aLoading[j].onHide();
                    }
                    aLoading.splice(j, 1);
                }
            }
        },
        _show = function(id,title,callback) {
            aLoading.push({ id: id, title: title, onHide: callback});
            $("#loader_img_title").append("<div class='" + id + "'>" + title + "</div>");
            if (aLoading.length == 1) {
                $("#loader_img").css("display", "block");
            }
            $("#loader_img_title").center();
        },
        _hide = function(id) {
            _removeLoading(id);
            if (aLoading.length == 0) {
                $("#loader_img").css("display", "none");
                $("#loader_img_title div").remove();
            } else {
                $("#loader_img_title ." + id).remove();
                $("#loader_img_title").center();
            }
        };
    
    return {
        show: _show,
        hide: _hide
    };

}(jQuery); // end of function loaderImg

var favoriteUsers = [];
function getFavoriteUsers() 
{
    $.ajax({
        url: 'api.php',
        type: 'post',
        data: {'action': 'getFavoriteUsers'},
        dataType: 'json',
        success: function(json) {
            if (!json || json === null) {
                return;
            }
            favoriteUsers = json.favorite_users;
        },
    });
}

jQuery.fn.centerDialog = function() {
    return this.each(function() {
        var $this = $(this);
        var p = $this.parent();
        var x = (document.body.clientWidth - p.width()) / 2;
        var y = Math.max(0, ($(window).height() - p.height()) / 2) + $(window).scrollTop();
        p.animate({opacity: 0}, 0).css({left:x, top:y}).animate({opacity: 1}, 300);

    });
};

function showUserInfo(user_id, tab) {
    if (user_id == 0 || user_id == undefined) {
        return false;
    }
    if (tab) {
        tab = "&tab=" + tab;
    } else {
        tab = "";
    }
    // Show user info in new tab
    window.open('userinfo.php?id=' + user_id + tab, '_blank');
    return false;
}

function resizeIframeDlg() {
    var bonus_h = $('#user-info').children().contents().find('#pay-bonus').is(':visible') ?
                  $('#user-info').children().contents().find('#pay-bonus').closest('.ui-dialog').height() : 0;

    var dlg_h = $('#user-info').children()
                               .contents()
                               .find('html body')
                               .height();

    var height = bonus_h > dlg_h ? bonus_h+35 : dlg_h+30;

    $('#user-info').animate({height: height});
}

function outputPagination(page, cPages) {
    var previousLink = page > 1 
            ? '<a href="#?page=' + (page - 1) + '">Previous</a> ' 
            : '<span>Previous</span> ',
        nextLink = page < cPages 
            ? '<a href="#?page=' + (page + 1) + '" class = "ln-last">Next</a> ' 
            : '<span class="ln-last">Next</span>';
    var pagination = previousLink;
    var fromPage = 1;
    if (cPages > 10 && page > 6) {
        if (page + 4 <= cPages) {
            fromPage = page - 6;
        } else {
            fromPage = cPages - 10;
        }
    }
    for (var i = fromPage; (i <= (fromPage +10) && i <= cPages); i++) {
        var sel = '';
        if (i == page) {
            sel = ' class="ln-selected"';
        }
        pagination += '<a href="#?page=' + i + '"' + sel + '>' + i + '</a>';
    }
    pagination += nextLink;
    return pagination;
}

function RelativeTime(x, shortMode) {
    var plural = '';
    var mins = 60, 
        hour = mins * 60; 
        day = hour * 24,
        week = day * 7, 
        month = week * 4, 
        year = day * 365;
    var negative = (x < 0);
    if (negative) {
        x = x * -1;
    }
    if (x >= year) { 
        x = (x / year) | 0; 
        dformat = shortMode ? "yr" : "year"; 
    } else if (x >= month) {
        x = (x / month) | 0;
        dformat = shortMode ? "mnth" : "month";
    } else if (x >= day * 4) {
        x = (x / day) | 0; 
        dformat = "day";
    } else if (x >= hour) {
        x = (x / hour) | 0;
        dformat = shortMode ? "hr" : "hour";
    } else if (x >= mins) {
        x = (x / mins) | 0;
        dformat = shortMode ? "min" : "minute";
    } else {
        x |= 0;
        dformat = "sec";
    }
    if (x > 1) {
        plural = 's';
    }
    if (x < 0) {
        x = 0;
    }
    
    return (negative ? '-' : '') + x + ' ' + dformat + plural;
}

// Code for stats
$(function() {
    $('#popup-user-info').dialog({ autoOpen: false, show: 'fade', hide: 'fade'});
    if (('#stats-text').length > 0) {
        $.ajax({
            type: "POST",
            url: 'getstats.php',
            data: 'req=currentlink',
            dataType: 'json',
            success: function(json) {
                if (!json || json === null) return;
                $("#count_b").text(json.count_b);
                $("#count_w").text(json.count_w);
                $('#stats-text').show();
            }
        });
    }
});

/* When applied to a textfield or textarea provides default text which is displayed, and once clicked on it goes away
 Example:  $("#name").DefaultValue("Your fullname.");
*/
jQuery.fn.DefaultValue = function(text){
    return this.each(function(){
    //Make sure we're dealing with text-based form fields
    if(this.type != 'text' && this.type != 'password' && this.type != 'textarea')
      return;
    
    //Store field reference
    var fld_current=this;
    
    //Set value initially if none are specified
        if(this.value=='' || this.value == text) {
      this.value=text;
    } else {
      //Other value exists - ignore
      return;
    }
    
    //Remove values on focus
    $(this).focus(function() {
      if(this.value==text || this.value=='')
        this.value='';
    });
    
    //Place values back on blur
    $(this).blur(function() {
      if(this.value==text || this.value=='')
        this.value=text;
    });
    
    //Capture parent form submission
    //Remove field values that are still default
    $(this).parents("form").each(function() {
      //Bind parent form submit
      $(this).submit(function() {
        if(fld_current.value==text) {
          fld_current.value='';
        }
      });
    });
    });
};

function openNotifyOverlay(html, autohide) {
    $('#sent-notify').html(html);
    $('#sent-notify').attr('autohide', autohide);
    $('#sent-notify').dialog('open');
}

function closeNotifyOverlay() {
    $('#sent-notify').dialog('close');
}

$(function() {
    $('#sent-notify').dialog({
        modal: false,
        autoOpen: false,
        width: 350,
        height: 70,
        position: ['middle'],
        resizable: false,
        open: function() {
            $('#sent-notify').parent().children('.ui-dialog-titlebar').hide();
            var autoHide = $('#sent-notify').attr('autohide') == 'true' ? true : false;
            if (autoHide) {
                setTimeout(function() {
                    closeNotifyOverlay();
                }, 3000);
            }
        }
    });
});
