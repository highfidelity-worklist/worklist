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
        var y = Math.max(0, ($(window).height() - p.height()) / 2);
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
    $('#user-info').html(
        '<iframe id="modalIframeId" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" />'
    ).dialog('open').centerDialog();
    $('#user-info').dialog( "option", "width", 840 );
    $('#modalIframeId').attr('src', 'userinfo.php?id=' + user_id + tab);
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