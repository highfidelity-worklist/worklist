/* journal.js
 *
 * vim:ts=4:et
 */

/* timer setting for the message and speaker polling*/
var pollingInterval = 1000; //ms
var speakerPollingInterval = 10000; //ms

// heartbeat
var heartbeatInterval = 50000; //ms
var heartbeatPaused = false;

// timer for the title change
var titleTimer = 0;

var mouseTrigger = false;
var pendingMessages = 0;
var pendingUpdate = false;

var retries = 0;
var retryMessage = '';

var queryStr = '';
var inThePresent = true;
var scrollBottom = true;
var queryStrIsJob = false;
var inSearchResult = false;

var months = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];

/* Values used by fillSpeakerList. */
var speakerHeight = 0;
var speakerListHeight = 0;
var oldSpeakerList = [];

/* If we need to display system messages in main window */
var systemMsgs = 'system';

/* Set to true when user is in Penalty Box */
var penalized = false;
var suspended = false;

// keep track of last private message sender for bundling
var last_private = '';
var current_ajax = null;

/* SHIFT modifier */
var shiftPressed = false;

/* New Message Alert by danbrown */
var playSoundOnNewMessage = true;
var displayPopupOnNewMessage = false;
var popupHasBeenDisplayed = false;

/* sound */
var chatSound, systemSound, pingSound, botSound, emergencySound;

var newMessageCount = 0;
var focus = true;
$.sendingMessage = 0;

var previousChatInterval, futureChatInterval;

function Sound(playerElement) {
    this.player = playerElement;
    this.muted = false;
}

Sound.prototype.play = function() {
    if (this.muted === false) {
        this.player.play();
    }
};

Sound.prototype.mute = function() {
    this.muted = true;
};

Sound.prototype.unmute = function() {
    this.muted = false;
};

/*
 * This array holds all bot names. 
 * It's initialized via a call to aj.php on page load and is available for later usage.
 */
var allBotNames = [];

$(window).focus(function() {
    focus = true;
    updateTitleTimerInterval();
    $('#message-pane').focus();
});

$(window).blur(function() {
    focus = false;
});

var latencyMonitor = {
    latencies: [],
    sample_rate: latency_sample,
    samples: 0,
    sampling: false,
    start_time: 0,

    getAverageLatency: function() {
        var average = 0;
        var len = Math.min(this.latencies.length, this.samples);
        for (var i = 0; i < len; i++) {
            average += this.latencies[i];
        }
        return average / len;
    },

    isSampling: function() {
        return this.sampling;
    },

    roundNumber: function(x, precision) {
        return Math.round(x * Math.pow(10, precision))/Math.pow(10, precision);
    },

    sample: function() {
        this.sampling = (Math.random() * 100 <= this.sample_rate);
        if (this.sampling) {
            var d = new Date();
            this.start_time = d.getTime();
        }
        return this.sampling;
    },

    saveSample: function(id) {
        if (!this.sampling) return;

        if (this.sample_rate > 0) {
            var d = new Date();
            var receive_time = d.getTime();
            var latency = receive_time - this.start_time;
            this.updateLatencies(latency);
        }

        $.ajax({
            url: 'aj.php',
            type: 'post',
            data: { 'what': 'saveSample', 'start_time': this.start_time, 'receive_time': receive_time, 'csrf_token': csrf_token },
        });

        this.sampling = false;
    },

    updateLatencies: function(latency) {
        this.samples++;
        this.latencies.push(latency);

        var avgLatency = this.roundNumber((this.getAverageLatency()/1000),1);
        var lstLatency = this.roundNumber((latency/1000),1);
        if (this.latencies.length > 10) this.latencies.shift();

        if($('#latency').length == 0){
            var html;
            html  = '<div id="latency" style="display:inline;color:#555;font-size:9px;line-height:16px;margin-left:-10px">';
            html += '    Avg <span id="avgLatency">'+avgLatency+'s</span> / Last <span id="lstLatency">'+lstLatency+'s</span>';
            html += '</div>';
            $('#bottom_right').append(html);
        } else {
            $('#avgLatency').html(avgLatency+'s');
            $('#lstLatency').html(lstLatency+'s');
        }
    }
};

/* textSelection extension
 *
 * Returns the text selected within a jquery set.
 *
 * Usage:
 *     $(selector).getSelection()
 *
 * Based on:
 *    jQuery plugin: fieldSelection - v0.1.0 - last change: 2006-12-16
 *    (c) 2006 Alex Brem <alex@0xab.cd> - http://blog.0xab.cd
 */
(function() {
    var textSelection = {
        getSelection: function() {
            var e = this.jquery ? this[0] : this;
            return (

                ('getSelection' in window && function() {
                    return window.getSelection().toString();
                }) ||

                ('getSelection' in document && function() {
                    return document.getSelection().toString();
                }) ||

                ('selectionStart' in e && function() {
                    var l = e.selectionEnd - e.selectionStart;
                    return e.value.substr(e.selectionStart, l);
                }) ||

                ('selection' in document && function() {
                    return document.selection.createRange().text;
                }) ||

                function() {
                    return '';
                }

        )();
    }
    };

    // This ajax call initializes the allBotNames array
    // 08-MAY-2010 <Yani>
    $.post("aj.php", {'what': 'botnames', 'csrf_token': csrf_token},function(json){
        for(var i=0; i<json.length; i++){
            allBotNames.push(json[i]);
        }
    }, 'json');
    jQuery.each(textSelection, function(i) { jQuery.fn[i] = this; });
})();

// This function receives an argument
// and checks if it exists among the
// the values of allBotNames array
// if it exists returns true, otherwise false
// @param string
// @return boolean
// 08-MAY-2010 <Yani>
var isBot = function(name){
    var retVal = false;
    for(var i=0; i<allBotNames.length; i++){
        if(allBotNames[i] == name){
            retVal = true;
        }
    }
    return retVal;
};

function heartbeat() {
    if (! heartbeatPaused) {
        $.post("aj.php", { 'what': 'speakeronline', 'csrf_token': csrf_token }, function(json) {
            if (!json || json === null) {
                return;
            }
            if(json.idle) {
                customAction('idle', json.message);
            } else {
                customAction('unidle', json.message);
            }
        }, 'json');
    }
}
//make user online
heartbeat();
$.alive = setInterval(heartbeat, heartbeatInterval);

function alertNewMessage(action, entryText) {

    var ping = false;
    var isEmergencySound = false;
    var localSystemSound = false;

    switch (action) {
        case 'ping':
            ping = true;
            break;
        case 'sitescan':
            isEmergencySound = true;
            break;
        case 'emergency':
            isEmergencySound = true;
            break;
        case 'system':
            localSystemSound = true;
            break;
    }
    if ((!focus || inSearchResult) && (entryText != undefined)) {
        newMessageCount++;
        document.title = "Chat (" + newMessageCount + ")";
        var search_string = entryText.entry_text.search('sent a ping to');
        // if the ping comes from the worklist and is public visible
        if (search_string != -1 && 
            search_string < entryText.entry_text.search(entryText.nickname) && 
            entryText.entry_type == 'extra' && 
            entryText.nickname != '') {
            titleTimer = setInterval(changeTitle, 1000);
        }
        search_string = entryText.entry_text.search('PING!');
        // if the ping comes from the journal
        if (search_string != -1 && 
            search_string < entryText.entry_text.search(entryText.nickname) && 
            entryText.entry_type == 'extra' && 
            entryText.nickname != '') {
            titleTimer = setInterval(changeTitle, 1000);
        }
    } else {
        document.title = 'Chat';
    }
    if(playSoundOnNewMessage == true) {
        var lastEntry = $("div.entry:has(h2):last");
        // don't play for guests, they hear re ping everytime an @ sign is typed otherwise
        if (ping && pingSound && (userId != 0)) {
            pingSound.play();
        } else if ((lastEntry.hasClass('svn') ||
                lastEntry.hasClass('worklist') ||
                lastEntry.hasClass('sendlove') ||
                lastEntry.hasClass('autotester') ||
                lastEntry.hasClass('schemaupdate')) &&
                (localSystemSound == true) ) {
            systemSound.play();
        } else if (isEmergencySound) {
            emergencySound.play();
        } else if (lastEntry.hasClass('bot')) {
            if (botSound) {
                botSound.play();
            }
        } else {
            if (chatSound) {
                chatSound.play();
            }
        }
    }
    if(displayPopupOnNewMessage == true && popupHasBeenDisplayed == false) {
        popupHasBeenDisplayed = true;
        alert('New Message!');
        popupHasBeenDisplayed = false;
        displayPopupOnNewMessage = false;
    }
}

function updateTitleTimerInterval() {
    if (focus && !inSearchResult) {
        // Clear current intervals
        clearInterval(titleTimer);
        
        newMessageCount = 0;
        document.title = "Chat";
        if (pendingMessages === 0) $('#entries-pending').hide();
    }
}

/*
 * function handles the title change
 */
function changeTitle () {
    if (document.title == "Chat (" + newMessageCount + ")") {
        document.title = '** New Ping Alert! ** (' + newMessageCount + ')';
    } else {
        document.title = "Chat (" + newMessageCount + ")";
    }
}
// george removed toggleAudio as not being used

 function AudioOn() {
    document.getElementById("chataudioon").checked = true;
    document.getElementById("systemaudioon").checked = true;
    document.getElementById("pingaudioon").checked = true;
    document.getElementById("botaudioon").checked = true;
    document.getElementById("emergencyaudioon").checked = true;
    if (!chatSound) return;
    chatSound.unmute();
    systemSound.unmute();
    pingSound.unmute();
    botSound.unmute();
    emergencySound.unmute();
    hideSettingsPopup();
}
function AudioOff() {
    chatSound.mute();
    systemSound.mute();
    pingSound.mute();
    botSound.mute();
    emergencySound.mute();
    document.getElementById("chataudiooff").checked = true;
    document.getElementById("systemaudiooff").checked = true;
    document.getElementById("pingaudiooff").checked = true;
    document.getElementById("botaudiooff").checked = true;
    document.getElementById("emergencyaudiooff").checked = true;
    hideSettingsPopup();
}
function ChatAudioOn() {
    chatSound.unmute();
    $("#chataudioon").parent(this).addClass("active");
    $("#chataudiooff").parent(this).removeClass("active");
    saveSounds(0, 1);
    // hideSettingsPopup();
}
function ChatAudioOff() {
    chatSound.mute();
    $("#chataudiooff").parent(this).addClass("active");
    $("#chataudioon").parent(this).removeClass("active");
    saveSounds(0, 0);
    // hideSettingsPopup();
}
function SystemAudioOn() {
    systemSound.unmute();
    $("#systemaudioon").parent(this).addClass("active");
    $("#systemaudiooff").parent(this).removeClass("active");
    saveSounds(1, 1);
    // hideSettingsPopup();
}
function SystemAudioOff() {
    systemSound.mute();
    $("#systemaudiooff").parent(this).addClass("active");
    $("#systemaudioon").parent(this).removeClass("active");
    saveSounds(1, 0);
    // hideSettingsPopup();
}
function PingAudioOn() {
    pingSound.unmute();
    $("#pingaudioon").parent(this).addClass("active");
    $("#pingaudiooff").parent(this).removeClass("active");
    saveSounds(3, 1);
    // hideSettingsPopup();
}
function PingAudioOff() {
    pingSound.mute();
    $("#pingaudiooff").parent(this).addClass("active");
    $("#pingaudioon").parent(this).removeClass("active");
    saveSounds(3, 0);
    // hideSettingsPopup();
}
function BotAudioOn() {
    botSound.unmute();
    $("#botaudioon").parent(this).addClass("active");
    $("#botaudiooff").parent(this).removeClass("active");
    saveSounds(2, 1);
    // hideSettingsPopup();
}
function BotAudioOff() {
    botSound.mute();
    $("#botaudiooff").parent(this).addClass("active");
    $("#botaudioon").parent(this).removeClass("active");
    saveSounds(2, 0);
    // hideSettingsPopup();
}
function EmergencyAudioOn() {
    emergencySound.unmute();
    $("#emergencyaudioon").parent(this).addClass("active");
    $("#emergencyaudiooff").parent(this).removeClass("active");
    saveSounds(4, 1);
}
function EmergencyAudioOff() {
    emergencySound.mute();
    $("#emergencyaudiooff").parent(this).addClass("active");
    $("#emergencyaudioon").parent(this).removeClass("active");
    saveSounds(4, 0);
}
function saveSounds(soundID, mode) {
    soundSettings[soundID] = mode;
    $.ajax({
        url: 'api.php',
        type: "POST",
        data: {
            'action': 'saveSoundSettings',
            'settings': soundSettings.join(':')
        },
        dataType: "json",
        success: function(data) {}
    });
}
function hideSettingsPopup(e) {
    if(e) {
        e.preventDefault();
    }
    $('div#SettingsWindow:visible').hide('drop', { direction: 'left' }, 500);
}

$(function($) {
    $('#SettingsWindowClose').click(hideSettingsPopup);
    $('.notTop').hide();
    $('.morebotlinks').click(function(e) {
        e.preventDefault();
        if ($('.notTop').is(":visible")) {
            $(this).html('See more commands')
            $('.notTop').hide('drop', { direction: 'left' }, 500);
        } else {
            $(this).html('See less commands')
            $('.notTop').show('drop', { direction: 'left' }, 500);
        }
    });
    $('.botlink').click(function(e) {
        e.preventDefault();
        retryMessage = $(this).attr('data');
        if(retryMessage) {
            sendEntryRetry();
        }
        hideSettingsPopup();
    }).tooltip({
        showURL:false,
        delay: 0,
        extraClass: "botip",
    });
    
    $("div#SettingsWindow").addClass('settingsboxexpanded');
    $("#settingsSwitch").bind('click', function(event, ui){
        if ($('div#SettingsWindow').is(":visible")) {
            $('div#SettingsWindow').hide('drop', { direction: 'left' }, 500);
        } else {
            $('div#SettingsWindow').show('drop', { direction: 'left' }, 500);
        }
        event.preventDefault();
    });
});

function togglePopup() {
    if(displayPopupOnNewMessage == true) {
        displayPopupOnNewMessage = false;
        return "will not";
    } else {
        displayPopupOnNewMessage = true;
        return "will";
    }
}
/* End New Message Alert by danbrown */

function sendEntry() {
    if (heartbeatPaused) {
        heartbeatPaused = false;
    }
    scrollBottom = true;
    doc = document.location.href.split("chat");
    $.ajax({
        url: 'api.php',
        type: "POST",
        data: {'action': 'updateLastSeen','username': userName},
        dataType: "json",
        success: function(data) {
            if (data.succeeded == true && data.message != null) {
                //success
            }
        }
    });
    var msg = $("#message-pane").val();
    if (   msg.length > 1
            || (msg.length == 1 && msg.charCodeAt(0) != 10 && msg.charCodeAt(0) != 13)) {
        retries = 0;
        retryMessage = msg;
        latencyMonitor.sample();
        // if we're speaking whilst away let's take appropriate action
        if($.away.status == 2) {
            $.away.unAway();
        }
        sendEntryRetry();
    }
    $("#message-pane").val("").focus();
    setLocalTypingStatus(IDLE);
}
	
function sendEntryRetry() {
    $.sendingMessage = true;
    $.ajax({
        url: 'aj.php',
        type: 'post',
        data: { 'what': 'send', 'message': retryMessage, 'tries': retries, 'lastid': lastId, 'lasttouched': lastTouched,
                'filter': systemMsgs, 'last_private': last_private, 'csrf_token': csrf_token, 'sampled': latencyMonitor.isSampling() },
        dataType: 'json',
        success: function(json) {
            latencyMonitor.saveSample();
            // if the user's away or idle, let's bring them back
            if ($.away.status > 0) {
                heartbeat();
            }
            if (!json || json === null) {
                return;
            }
            // custom action provides a way to intercept messages
            if (json.custom) {
                // if it returns true then we carry on
                // if false then lets just leave it there
                cont = customAction(json.custom, json.message);
                if (cont == false) {
                    return;
                }
            }
            if (json.scope == '#private') {
                last_private = json.bot;
                $('#entries').append(json.html);
                finishUpdate('100%', true);
            } else {
                updateNewMessages(json, true);
            }
            $.sendingMessage = false;
        },
        error: function(xhdr, status, err) {
                alert("Error sending entry:" + err);
            if (retries++ < 3) {
                setTimeout(sendEntryRetry, retries * 250);
            } else {
                $.sendingMessage = false;
                alert("Error sending entry.");
            }
        },
        complete: function() {
            if ($.sendingMessage === false) {
                resetLongpoll();
            }
        }
    });
}

function showLatest() {
    /* Reset all of our state */
    var entries = $("#entries");
    $("#query").val('');
    queryStr = '';
    entries.empty();
    pendingMessages = 0;
    inThePresent = true;

    $.polling = true;
    $.pollingLatest = true;

    if (current_ajax) current_ajax.abort(); // the current request won't have the proper params, so stop it
    clearInterval($.timer['getLatestEntries']);
    $.timer['getLatestEntries'] = setInterval(getLatestEntries, pollingInterval);
    $('#loading-spin').show();
    current_ajax = $.ajax({
        url: 'aj.php',
        type: 'post',
        data: { 'what': 'latest_longpoll', 'count': 50, 'csrf_token': csrf_token, 'filter': systemMsgs },
        dataType: 'json',
        success: function(json){
            $('#loading-spin').removeClass().hide();
            $('#entries-pending').hide();
            lastId = json.lastId;
            currentTime = parseInt(json.time);
            firstDate = json.firstDate;
            lastDate = json.lastDate;
            if (lastDate > lastEntryDate) {
                lastEntryDate = lastDate;
            }
            entries.append(json.html);
            last_private = null;
            updateTypingIndicators(json.typingstatus);
            
            var drawer = $("#system-notifications > div");
            drawer.empty();
            drawer.prepend(json.system_html);
            $('#system-notifications > div')[0].scrollTop = 0;
            finishUpdate('100%', false);
        },
        complete: function() {
            resetLongpoll();
        }
    });
}

function resetLongpoll() {
    $.polling = false;
    $.pollingLatest = false;
    clearInterval($.timer['getLatestEntries']);
    $.timer['getLatestEntries'] = setInterval(getLatestEntries, pollingInterval);
}

function getLatestEntries(timeout) {
    if (!(timeout|0)) timeout = 30;
    if ($.polling == true) return;
    $.polling = true;
    current_ajax = $.ajax({
        url: 'aj.php',
        type: 'post',
        data: { 'what': 'latest_longpoll', 'timeout': timeout, 'lastid': lastId, 'lasttouched': lastTouched, 'filter': systemMsgs, 'last_private': last_private, 'csrf_token': csrf_token},
        dataType: 'json',
        timeout: timeout * 1000 * 2,
        success: function(json){
            if (!json || json === null) {
                return;
            }
            lastTouched = json.lasttouched;
            updateTypingIndicators(json.typingstatus);
            // if we've decided to do something else in the meantime then let's bail
            if ($.pollingLatest === true || $.sendingMessage === true) {
                return;
            }
            updateNewMessages(json);
        },
        complete: function(){
            $.polling = false;
        }
    });
}

function updateNewMessages(json) {
    var botAction = null;
    var doFinishUpdate = false; // Only do this if actually added new messages.
    if(json.updates == 0) return;
    currentTime = parseInt(json.time);
    if (json.botdata) {
        if (json.botdata.ping) {
            botAction = 'ping';
        } else if (json.botdata.sitescan) {
            botAction = 'sitescan';
        } else if (json.botdata.emergency) {
            botAction = 'emergency';
        }
    }
    if (json.count > 0) {
        last_private = null;
        if (!focus || inSearchResult) {
            pendingMessages += json.count;
        }
        $('#entries-pending').hide();
        lastDate = json.lastDate;
        if (lastDate > lastEntryDate) {
            lastEntryDate = lastDate;
        }
        if (json.html && !inSearchResult) {
            $('#entries').append(json.html);
            doFinishUpdate = true;
        } else {
            for (entry in json.newentries) {
                if (json.botdata) {
                    if (json.botdata.ping) {
                        botAction = 'ping';
                    } else if (json.botdata.sitescan) {
                        botAction = 'emergency';
                    } else {
                        botAction = false;
                    }
                }
                if (!inSearchResult && lastId < json.newentries[entry].id) {
                    doFinishUpdate = true;
                    $('#entries').append(formatMessage(json.newentries[entry]));
                }
                alertNewMessage(botAction, json.newentries[entry]);
            }
        }
        if (!inSearchResult && doFinishUpdate === true) {
            finishUpdate('100%', true, botAction, json.newentries[entry]);
        }
    }
    if (json.system_count > 0) {
        if (!inSearchResult && json.system_html) {
            $('#system-notifications > div').prepend(json.system_html);
        } else {
            var update = false;
            for (entry in json.newsystementries) {
                if(
                    json.newsystementries[entry].entry_text.indexOf('Bidding') != -1 ||
                    json.newsystementries[entry].entry_text.indexOf('Review') != -1 ||
                    json.newsystementries[entry].entry_text.indexOf('Working') != -1 ||
                    json.newsystementries[entry].entry_text.indexOf('Completed') != -1 ||
                    json.newsystementries[entry].entry_text.indexOf('added item') != -1 ||
                    json.newsystementries[entry].entry_text.indexOf('SuggestedWithBid') != -1 ||
                    json.newsystementries[entry].entry_text.indexOf('code review') != -1 ||
                    json.newsystementries[entry].entry_text.indexOf('authorize sandbox') != -1
                ) {
                    update = true;
                }
                if (!inSearchResult && lastId < json.newsystementries[entry].id) {
                    doFinishUpdate = true;
                    $('#system-notifications > div').prepend(formatMessage(json.newsystementries[entry]));
                }
            }
            if(update == true) getBiddingReviewDrawers();
        }
        
        for (entry in json.newsystementries) {
            if (json.newsystementries[entry].author == 'SiteScan') {
                botAction = 'sitescan';
                break;
            }
        }

        if (botAction == null) botAction = 'system';
        if (!inSearchResult && doFinishUpdate === true) {
            finishUpdate('100%', true, botAction, json.newsystementries[entry]);
        }
        
        $('#system-notifications > div')[0].scrollTop = 0;
        enlivenEntries();
    }
    if (json.lastId && json.lastId > 0) {
        lastId = json.lastId;
    }
}

function formatMessage(entry) {
    if( document.getElementById('entry-' + entry.id) ) return('');

    var d1 = $('<div data="' + entry.time + '" class="entry">');
    d1.attr('id', 'entry-' + entry.id);
    if (entry.custom_class) d1.addClass(entry.custom_class);
    var d2 = $('<div class="entry-text">')
    d2.html(entry.entry_text);

    if (entry.entry_type=='basic') {
        d1.append(d2); 
    } else {
        var h2 = $('<h2>');
        if(isBot(entry.author)){
            var span1 = '<span class="entry-author">' + entry.author + '</span>';
        } else {
            var span1 = '<a class="entry-author" ' + entry.func + ' target="_blank">' + entry.author + '</a>';
        }
        var span2 = '<span class="entry-date" data="' + entry.time + '" title="' + entry.time_title + '">';
        span2 += entry.relative_time + '</span>';
        var spans = span1 + span2;
        h2.append(spans);
        d1.append(h2).append(d2);
    }
    return(d1);
}

function getLatestSpeakers(timeout) {
    $.ajax({
        url: 'aj.php',
        type: 'post',
        data: { 'what': 'speakerList', 'csrf_token': csrf_token },
        dataType: 'json',
        timeout: timeout * 1000 * 2,
        success: function(json){
            if (!json || json === null) {
                return;
            }
            fillSpeakerList(json.speakers, json.current_user);

            // Update relative times
            currentTime = parseInt(json.time);
            updateRelativeTimes();
            updateNicknamesDisplaying();
        },
    });
}

function applyPopupBehavior() {
    $(function() {
        $('#addaccordion').fileUpload({tracker: $('input[name=files]')});
    });

    var entries = $("#entries");

    entries.find("a.attachment").not(".attached").each(function (a,b) {
        var imgURL = $(this).attr('href');
        var myID = imgURL.substring(imgURL.lastIndexOf('=')+1,imgURL.length);
        $(this).wrap('<div title="'+imgURL+'" id="innerLinkAttach_'+myID+a
            +'" style="display:table-cell;padding:3px 0px 3px 0px;vertical-align:middle" />');
        var iconTag = '';
        iconTag+='<div title="'+imgURL+'" class="attachmentIconSmall"style="display:table-cell;padding:3px 2px 3px 0px;vertical-align:middle">';
        iconTag+=       '<img src="images/gif.gif" id="attach_img" height="18" width="18" alt="Attachment"/>'
        iconTag+='</div>';
        $('div#innerLinkAttach_'+myID+a).before(iconTag);
        $(this).html('Attachment #'+myID);
        $(this).addClass("attached");
        $(this).unbind().click(function (evt) {
            evt.preventDefault();
            var dialogUrl = $(this).attr('href');
            $('<img src="'+dialogUrl+'" title="Preview">').dialog({
                modal: false,
                hide: 'drop', resizable: false,
                width: 'auto',
                height: 'auto',
                open:function(evt){
                    $(this).parent().css('opacity','0'); // hide dialog
                    storeCursorStatus = new Array(); // store current cursor values and set all to wait
                    $('*').each(function(){
                        if($(this).css('cursor')!='auto')
                            storeCursorStatus.push([$(this), $(this).css('cursor')]); });
                    $('*:visible').css('cursor','wait');
                    window.imageFiredDialogRedim = [false, evt.target]; // stores [ <was_load_fired?> , <img /> ] in window
                    $(evt.target).load(function(){
                            var image = $(this);
                            var origWidt = parseInt(image.naturalWidth); // get image size
                            var origHeig = parseInt(image.naturalHeight);
                            if(!origWidt||!origHeig){// could't? try again
                                var origWidt = parseInt(image.width());
                                var origHeig = parseInt(image.height()); }
                            var padding = 10; //space to be left around dialog box
                            ratio = Math.min(($('#guideline').innerWidth()-padding*2) / origWidt,
                                            ($('#guideline').innerHeight()-padding*2) / origHeig);
                            var zoom='';
                            if(ratio<1){ // if bigger than viewport
                                image.css({'width':origWidt*ratio,'height':origHeig*ratio});
                            }
                            var dialog = image.parent()
                            dialog.css({ // center dialog to the screen
                                'top':(function(){
                                    return (($('#guideline').innerHeight() - dialog.height())/2)+40;})(),
                                'left':(function(){
                                    return (($('#guideline').innerWidth() - dialog.width())/2);})() });
                            $('*').css('cursor','auto'); // revert cursor to previous values
                            $.each(storeCursorStatus,function(i,v){
                                v[0].css('cursor',v[1]); });
                            if(ratio<1){
                                zoom='('+Math.round(ratio*100)+'%)';
                                image.prev('div').append(
                                    '<span class="dialogZoom" style="margin-left:10px;">'+zoom+'</span>');} // writes srink percentage
                            if (ratio!='Infinity'){
                                image.css({'margin':'12px','padding':'0','border':'1px solid #ccc'}); // set margin, padding and border for image
                                if($.browser.msie){
                                    image.css({'border':'2px solid #000'});} // add thicker border or drop shadow to image if possible
                                else if($.browser.mozilla){
                                    image.css({'-moz-box-shadow':'rgba(169, 169, 169, 0.5) 3px 3px 3px'});}
                                else{
                                    image.css({'-webkit-box-shadow':'rgba(169, 169, 169, 0.5) 3px 3px 3px'});}

                                    image.parent().hide();
                                    image.parent().css('opacity','1').fadeIn();

                                clearInterval(window.imageFiredDialogRedim[3]); // clear image.load event firing monitor interval
                            }
                    })
                    window.imageFiredDialogRedim[3] =
                        setInterval(function(){ // image.load event event monitor
                                if(!window.imageFiredDialogRedim[0]){ // used when image is preloaded
                                    $(window.imageFiredDialogRedim[1]).trigger('load'); // and in browsers that don't fire image.load
                                }
                        },1500);
                },
                resizeStart:function(){
                    $(this).parent().find('.dialogZoom').html(''); }, // hide srink percentage on resize
                dragStop:function(evt){
                    var dialog = $(evt.target); // check if not out of screen
                    if( dialog.position().top <5 ) // if it is, recenter it
                        dialog.css({
                            'top':(function(){
                                return (($('#guideline').innerHeight() - dialog.height())/2)+40; })(),
                            'left':(function(){
                                return (($('#guideline').innerWidth() - dialog.width())/2); })() }); } }); }); });

}

// if a message has responded with a custom action, it's gonna call this to know what to do
function customAction(action, message) {
    switch(action) {
        case 'away': {
            $.away.makeAway(message);
            return(true);
        }
        case 'back': {
            $.away.comeBack(message, 'back');
            return(true);
        }
        case 'history': {
            $.modal.showMessage(message, 'history', false);
            return(false);
        }
        case 'idle': {
            $.away.setIdle(message);
            return(false);
        }
        case 'unidle': {
            $.away.unIdle(message);
            return(false);
        }
    }
}
// live timelinks - handle all .gotoLinks
(function($) {
    $('.gotoLink').live('click', function(e) {
        e.preventDefault();
        var date = $(this).attr('data');
        if (date) {
            gotoTime(date);
            $(this).remove();
        }
    });
})(jQuery);

// away handling - overlay messaging and back behaviour
(function($) {
    $.away = {
        checkAway: function() {
            $.post("aj.php", { 'what': 'isspeakeraway', 'csrf_token': csrf_token }, function(json) {
                if(json.away) {
                    $.modal.addModal(json.message, $.away.minMessage, 'away');
                    $.away.status = 2;
                    StopStatus();
                }
            }, 'json');
        },
        makeAway: function(message) {
            if($.away.status < 2) {
                $.modal.addModal(message, $.away.minMessage, 'away' );
                $.away.status = 2;
                StopStatus();
            }
        },
        minMessage: function() {
            var br = $('#container').append('<div class="actions"></div>');
            $('.actions', br).fadeOut(0).append($(this).parents('.modalOverlay').find('.modalContent a').clone()).fadeIn();
            $.modal.done(this);
        },
        comeBack: function(message, id) {
            $.away.status = 0;
            $.modal.removeModal($('a.awayback'));
            if (typeof(message) == 'string') {
            // we've been called from a back message
                $.modal.showMessage(message, 'away' );
                retryMessage = '@him get';
                sendEntryRetry();
            } else {
            // we've been called from a click back link
                retryMessage = '@me back';
                sendEntryRetry();
            }
            // let's also set the speaker as unidle so they won't get automatically set idle
            $.post("aj.php", { 'what': 'speakerunidle', 'csrf_token': csrf_token } );
        },
        unAway: function() {
            // we need to trick the status into thinking we were idle if we were away
            // so we can let unIdle fire the get messages
            $.modal.removeModal($('a.awayback'));
            $.away.status = 1;
        },
        setIdle: function(message) {
            if($.away.status == 0) {
                $.modal.addModal(message, $.away.minMessage, 'away');
                $.away.status = 1;
            }
        },
        sendUnIdle: function() {
            $.modal.done(this);
            $.post("aj.php", { 'what': 'speakerunidle', 'csrf_token': csrf_token }, function() { heartbeat(); });
        },
        getUpdate: function() {
            $.modal.done(this);
            retryMessage = '@him get';
            sendEntryRetry();
        },
        unIdle: function(message) {
            if($.away.status == 1) {
                $.modal.removeModal($('a.idleback'));
                retryMessage = '@him get';
                sendEntryRetry();
                $.away.status = 0;
            }
        },
        status: 0,
    };
    $.away.checkAway();
    $('a.idleback').live('click', $.away.sendUnIdle);
    $('a.awayback').live('click', $.away.comeBack);
    $('a.getupdate').live('click', $.away.getUpdate);
})(jQuery);

(function($) {
    $.modal = {
        defaultVisible: 5000,
        nextModal: 0,
        defaultSpeed: 500,
        overlays: [],
        showMessage: function(message, id, timer) {
            var id = $.modal.addModal(message, $.modal.done, id);
            if (timer !== false) {
                timer = timer ? timer : $.modal.defaultVisible
                setTimeout(function() {
                    $.modal.removeModal(id);
                }, timer);
            }
        },
        addMasterContainer: function() {
            if(!$.modal.overlay) {
                $('body').append('<div id="modalOverlay">');
                $.modal.overlay = $('#modalOverlay');
            }
        },
        addModal: function(message, click, id, speed) {
            speed = speed ? speed : $.modal.defaultSpeed;
            if (id) {
                if  ($.modal.overlays[id]) {
                    $.modal.removeModal($.modal.overlays[id]);
                }
                $.modal.overlays[id] = 'modalOverlay-' + $.modal.nextModal;
            }
            var modalId = 'modalOverlay-'+$.modal.nextModal++;
            if(!$.modal.overlay) {
                $.modal.addMasterContainer();
            }
            $.modal.overlay.append('<div class="modalOverlay" id="'+modalId+'">');
            var thisModal = $('#'+modalId);
            thisModal.fadeOut(0).html('<div class="modalHeader"><a class="modalX"><span>&times;</span></a></div><div class="modalContent"><p></p></div>');
            $('p', thisModal).html(message);
            $('.modalX', thisModal).unbind('click').click(click);
            thisModal.fadeIn('fast');
            return(modalId);
        },
        removeModal: function(el, speed) {
            // removes modal based on the parentModal of the passed element
            // also accepts string and uses that as id of that item
            speed = speed ? speed : $.modal.defaultSpeed;
            if (typeof( el ) == 'string') {
                rm = $('#' + el);
            } else {
                rm = $(el).parents('.modalOverlay');
                if(!rm.size()) rm = $(el).parents('.actions');
            }
            if(!rm.size()) return;
            if (speed == 0) {
                    rm.remove();
            } else {
                rm.animate({opacity:0, height:0 }, speed, function() {
                    $(this).remove();
                });
            }
        },
        done: function(el) {
            if(el.type == 'click') el = this;
            $.modal.removeModal(el);
        },
    };
})(jQuery);

/*
 * combinedList has the structure:
 *   combinedList[ user_id ] = [ enum:{0=remove,1=keep,2=new}, posDelta, speaker ]
 */
function combineSpeakerLists(oldList, newList) {
    // let's set up a variable to hold this so the code below is easier to read
    var user_id;

    // as we are assigning a numerical value (userid) for the key let's use an object not an array here
    var combinedList = new Object();

    for (var i = 0; i < oldList.length; i++) {
        user_id = oldList[i][0];
        // set all the existing users in the list to delete - we'll overwrite this if the
        // users are in the  new list
        combinedList[user_id] = new Array(0, i);
    }
    
    // if there is a difference in the length then right away we know there's changes
    var changes = Math.abs(oldList.length - newList.length);
    
    for (var i = 0; i < newList.length; i++) {
        user_id = newList[i][0];
        // if user existed
        if (combinedList[user_id] != undefined) {
            // if the position has changed that's another thing to mark off as a change
            if (combinedList[user_id][1] != i) changes++;
            // keep user
            combinedList[user_id][0] = 1;
            // set user position
            combinedList[user_id][1] = i;
        } else {
            changes++;
            // this is a new user
            combinedList[user_id] = new Array(2, i, newList[i]);
        }
    }
    // return an array with the number of changes and the combinedList of updates
    return new Array(changes, combinedList);
}

function buildBookmarkMenu() {
    var menuList = [];

    /* Context: only add 'Go To' if doing a query. */
    if (queryStr != '') {
        menuList[menuList.length] =
        { type: 'item',  title: 'Go To Time', fn: bookmarkHandler, bmtype: 'goto' };
        menuList[menuList.length] = { type: 'separator' };
    }

    menuList[menuList.length] =
    { type: 'item',  title: 'Add Bookmark', fn: bookmarkHandler, bmtype: 'private' };

    if (is_runner) {
        menuList[menuList.length] =
        { type: 'separator' };
        menuList[menuList.length] =
        { type: 'item', title: 'Block IP', fn: blockHandler };
        menuList[menuList.length] =
        { type: 'item', title: 'Mark As Spam', fn: spamHandler };
    }

    //menuList[menuList.length] =
    //{ type: 'item',  title: 'Shared Bookmark', fn: bookmarkHandler, bmtype: 'public' }

    var bookmarks = $.cookie('bookmarks');
    if (bookmarks) {
        bookmarks = bookmarks.split(',');
        menuList[menuList.length] = { type: 'separator' };
        for (var i = 0; bookmarks && i < bookmarks.length; i++) {
            if (bookmarks[i] != 0) {
                bmfields = bookmarks[i].split('/');
                menuList[menuList.length] = { type: 'item', title: bmfields[0], fn: bookmarkHandler, bmtype: 'goto', bmdate: bmfields[1] };
            }
        }
    }

    return menuList;
}

function enlivenEntries() {
    $('.entry-date').menuPopup(buildBookmarkMenu(), { selectionRequired: false } );
    makeWorkitemTooltip('.worklist-item');
}

function getNewSpeaker(newSpeaker) {
    var h6 = document.createElement('h6');
    var span = document.createElement('span');
    span = $(span);
    if (newSpeaker[5] > 0) span.addClass('away');
    span.data('last_entry', newSpeaker[2]);
    span.data('last_login', newSpeaker[3]);
    span.data('status', newSpeaker[4]);
    span.data('nickname', newSpeaker[1]);
    span.data('local_time', newSpeaker[9]);
    span.context.appendChild(document.createTextNode(newSpeaker[1]));
    span.append($('<em></em>').addClass('typing-icon'));

    if(checkPenalty(newSpeaker)){
        span.addClass('penalized');
    }

    if (newSpeaker[0] == userId){
        span.addClass('me');
    }

    // so we can distinguish them while creting context menu
    if (newSpeaker[0] == 0){
        span.addClass('guest');
    }else{
        span.addClass('logged');
    }
    
    if (newSpeaker[0] != 0 && $.inArray(newSpeaker[0], favoriteUsers) != -1) {
        span.addClass('favorite');
        span.append($('<div></div>').addClass('myfavorite'));
    }
    
    h6.className = 'user'+newSpeaker[0];
    var ret = $(h6).append(span).css('opacity', getSpeakerOpacity(newSpeaker[2]));
    return ret;
}

function getSpeakerOpacity(lastEntryDate) {
    /* Set opacity according to the following rules:
     *  - last entry within the last hour: 100%
     *  - last entry within the last 4 hours: linear 50% @ 4hrs - 100% @ 1hr
     *  - last entry withing day or longer: linear 10% @ 24hrs - 50% @ 4 hrs
     */
    var opacity;
    var age = Math.min(currentTime - lastEntryDate, 24 * 60 * 60);
    if (age < 60 * 60) {
        opacity = 1;
    } else if (age < 4 * 60 * 60) {
        age -= 60 * 60;
        opacity = (1 - (age / (3 * 60 * 60))) * .5 + .5;
    } else {
        age -= 4 * 60 * 60;
        opacity = (1 - (age / (20 * 60 * 60))) * .4 + .1;
    }

    return opacity;
}

/*
 * object for handing speakerlist add/remove/repositioning
 * methods names should be self explanatory :)
*/
$speakerList = {
    addSpeaker: function(userId, userStatus) {
        // check we have a speakerList to load this into
        $speakerList.loadSpeakerList();
        speaker = getNewSpeaker(userStatus[2]).css({
            top: userStatus[1] * speakerHeight,
            opacity: getSpeakerOpacity(userStatus[2])
        });
        $speakerList.online.append(speaker);
        speaker.hide().fadeIn('slow');
    },
    repositionSpeaker: function(userId, userStatus) {
        $('#online-users .user'+userId).animate({top: userStatus[1] * speakerHeight}, 'slow');
    },
    removeSpeaker: function(userId, userStatus) {
        $('#online-users .user'+userId).fadeOut('slow').remove();
    },
    loadSpeakerList: function() {
        // get the speakerlist as a jquery object and save for later
        // we're using $speakerList so we don't need to worry about variable scope
        if(!$speakerList.online) {
            $speakerList.online = $('#online-users > div');
        }
    }
}

var speakerCount = 0;
function fillSpeakerList(newSpeakerList, currentUser){
    var online, updateTimes = false;
    var combined = combineSpeakerLists(oldSpeakerList, newSpeakerList);
    /* If no positional changes, just update opacity. */
    if (combined[0] == 0) {
        updateTimes = true;

    /* If this is a fresh load of the speaker list, just fill all the speakers. */
    } else if (oldSpeakerList.length == 0 && newSpeakerList.length > 0) {

        // load the speakerList
        $speakerList.loadSpeakerList();

        for (var i = 0; i < newSpeakerList.length; i++){
            // only load as many as will fit (do we need to do this?)
            // commenting out for now as I don't think we do. We load/save the data anyway
            // if (speakerHeight > 0 && speakerHeight * (i+1) > speakerListHeight) break;

            speaker = getNewSpeaker(newSpeakerList[i]).css('top', i * speakerHeight);
            $speakerList.online.append(speaker);
            if (speakerHeight == 0) speakerHeight = speaker.find('span').outerHeight() + 4;
        }

    /* Real changes including log in new speakers, log out old speakers, reposition speakers. */
    } else {
        updateTimes = true;
        
        // simplified by george. previous code was a recursive mess, looping through the list numerous times
        // functions are defined above in the $speakerList object instead of redeclaring every time through
        
        // loop through the combined speakerlist (once) and perform the correct action
        $.each(combined[1], function(userId, userStatus) {
            switch(userStatus[0]) {
                case 0: // delete from list
                    if (userId == window.userId) {
                        heartbeatPaused = true;
                    }
                    $speakerList.removeSpeaker(userId, userStatus);
                    break;
                case 1: // reposition existing
                    $speakerList.repositionSpeaker(userId, userStatus);
                    break;
                case 2: // add to list
                    if (userId == window.userId) {
                        heartbeatPaused = false;
                    }
                    $speakerList.addSpeaker(userId, userStatus);
                    break;
            }
        });
    }

    for (var i = 0; i < newSpeakerList.length; i++){
        if (newSpeakerList[i][0] == 0) {
            $('#online-users .user'+newSpeakerList[i][0]+' span').text(newSpeakerList[i][1]);
        }
        if ($('#online-users .user'+newSpeakerList[i][0]+' span').text() != newSpeakerList[i][1]) {
            $('#online-users .user'+newSpeakerList[i][0]+' span').text(newSpeakerList[i][1]);
        }
        $('#online-users .user'+newSpeakerList[i][0]).css('opacity', getSpeakerOpacity(newSpeakerList[i][2]));
    }

    // times and status updates
    for (var i = 0; updateTimes && i < newSpeakerList.length; i++){
        var span = $('#online-users .user'+newSpeakerList[i][0]+' span');
        span.data('nickname', newSpeakerList[i][1]);
        span.data('last_entry', newSpeakerList[i][2]);
        span.data('last_login', newSpeakerList[i][3]);
        span.data('status', newSpeakerList[i][4]);
        span.data('local_time', newSpeakerList[i][9]);
        if (newSpeakerList[i][5] > 0) {
            span.addClass('away');
        } else {
                span.removeClass('away');
        }

        if(checkPenalty(newSpeakerList[i])) {
            span.addClass('penalized');
        } else {
            span.removeClass('penalized');
        }

        span.hide().show(); // force ie to resize span
    }

    // typing indicators
    for (var i = 0; i < newSpeakerList.length; i++){
        if (newSpeakerList[i][0] == userId) {
            continue;
        }

        var el = $('#online-users .user'+newSpeakerList[i][0]);

        if (newSpeakerList[i][8] == 3) {
            el.addClass('typing');
        } else {
            el.removeClass('typing');
        }

        if (newSpeakerList[i][8] == 2) {
            el.addClass('stopped');
        } else {
            el.removeClass('stopped');
        }
    }

    $('#online-users span').unbind();
    $('div#online-users h6').css('z-index',3).mouseover(function(e){
        var pos = $(e.target);
        if (pos.hasClass('logged')) {
            pos = pos.parent();
        }
        pos = pos.offset();
        var obj = $($(this).context.firstChild);
        var msg = '';
        
        if (obj.data('local_time')) {
            msg = "(" + obj.data('local_time') + ") ";
        }
        if (obj.data('last_entry')) {
            msg += relativeTime(currentTime - obj.data('last_entry'));
        } else {
            msg += "Never";
        }

        if(obj.data('status')){
            msg += " - " + obj.data('status');
        }

        $('#livetip')
            .css({ top: pos.top + 30, left: pos.left + 6, 'z-index': 4000, 'background-color':'white'})
            .html('<div>' + msg + '</div>')
            .show();

    });
    $('#online-users h6').mouseout(function(){
        $('#livetip').hide();
    });

    $('#online-users span').rightMouseUp( function(e) {
        e.preventDefault();
    });

    if(userId != 0){

        // we have to provide different menus for regular users and for guest users
        var loggedMenuList = [];
        loggedMenuList[loggedMenuList.length] = { type: 'item',  title: 'Current worklist items', fn: userHandler, actionType: 'workitems' };
        loggedMenuList[loggedMenuList.length] = { type: 'item',  title: 'Send to Penalty Box', fn: userHandler, actionType: 'penalty' };
        $('#online-users span.logged').menuPopup(loggedMenuList, { selectionRequired: false, context: true } );

        var guestMenuList = [];
        guestMenuList[guestMenuList.length] = { type: 'item',  title: 'Send to Penalty Box', fn: userHandler, actionType: 'penalty' };
        $('#online-users span.guest').menuPopup(guestMenuList, { selectionRequired: false, context: true } );
    }
    
    $('#online-users span.logged').click(function(e){
        var className = e.currentTarget.parentNode.className;
        var id = className.substring(4);
        var numId = parseInt(id);
        window.open('userinfo.php?id=' + numId, '_blank');
        return false;
    });


    oldSpeakerList = newSpeakerList;

}

function finishUpdate(scrolllTo, notify, botAction, entryText) {
    enlivenEntries();
    applyPopupBehavior();
    updateRelativeTimes();
    updateNicknamesDisplaying();
    if (scrollTo !== false) {
        scrollViewTo(scrolllTo);
    }
    if (notify) {
        alertNewMessage(botAction, entryText);
    }
}
function getEntriesAt(toDate) {
    var now = new Date();
    
    thePresent = (now.getTime() / 1000  - toDate <= 3);
    if (thePresent && inThePresent) {
        scrollViewTo('100%');
        return;
    }
    if (thePresent) pendingMessages = 0;
    inThePresent = thePresent;
    var entries = $("#entries");
    entries.empty();
    $('#loading-spin').show();
    $.post("aj.php", { 'what': 'time', 'count': 50, 'time': toDate, 'prevnext':'', 'query': queryStr, 'csrf_token': csrf_token, 'filter': systemMsgs }, function(json) {
        $('#loading-spin').removeClass().hide();
        firstDate = json.firstDate;
        lastDate = json.lastDate;
        if (lastDate > lastEntryDate) {
            lastEntryDate = lastDate;
        }
        entries.html(json.html);

        var scrolllTo = '50%';
        if (inThePresent) scrolllTo = '100%'
        else if (firstDate == earliestDate) scrolllTo = '0%';

        var drawer = $("#system-notifications > div");
        drawer.empty();
        drawer.prepend(json.system_html);
        $('#system-notifications > div')[0].scrollTop = 0;

        finishUpdate(scrolllTo, false);
    }, 'json');
}

function getEntriesNear(prev, autoScroll) {
    inThePresent = false;
    if (pendingUpdate) return;
    else if (prev && firstDate == earliestDate) return;
    pendingUpdate = true;
    
    var entries = $("#entries");
    var removeChildren = new Array();
    var children = entries.children();
    $('#loading-spin').show();

    if (prev) {
        for (var i = 100; i < children.length; i++) {
            removeChildren[i-100] = children[i];
        }
        if (children.length > 100) {
            lastDate = $(children[99]).attr('data') | 0;
        }

        $.post("aj.php", { 
            'what': 'time', 
            'count': 50, 
            'time': firstDate -1, 
            'prevnext': 'prev', 
            'query': queryStr, 
            'csrf_token': csrf_token, 
            'filter': systemMsgs
        }, function(json) {
            $('#loading-spin').removeClass().hide();

            if (json.count > 0) {
                firstDate = json.firstDate;
                var prevScrollHeight = $('#entries')[0].scrollHeight,
                    prevScrollTop = $('#entries')[0].scrollTop - 80;
                entries.prepend(json.html);
                var scrollTop = $('#entries')[0].scrollHeight - prevScrollHeight + prevScrollTop;
                $('#entries')[0].scrollTop = scrollTop; 
                finishUpdate(false, false);
            }

            pendingUpdate = false;
        }, 'json');
    } else {
        for (var i = 0; i < children.length - 100; i++) {
            removeChildren[i] = children[i];
        }
        if (i < children.length) {
            firstDate = $(children[i]).attr('data') | 0;
        }

        $.post("aj.php", { 
            'what': 'time', 
            'count': 50, 
            'time': lastDate + 1,
            'prevnext':'next', 
            'query': queryStr, 
            'csrf_token': csrf_token, 
            'filter': systemMsgs
        }, function(json) {
            $('#loading-spin').removeClass().hide();

            var height = entries.height();
            if (json.count > 0) {
                lastDate = json.lastDate;
                if (lastDate > lastEntryDate) {
                    lastEntryDate = lastDate;
                }
                entries.append(json.html);
                finishUpdate(false, false);
            } else {
                inThePresent = true;
                pendingMessages = 0;
                $('#entries-pending').hide();
                scrollViewTo('100%');
            }

            pendingUpdate = false;
        }, 'json');
    }

    if (removeChildren.length > 0) {
        var delta = entries.height();
        $(removeChildren).remove();
    }
}

var IDLE = 1,
    STOPPED = 2,
    TYPING = 3;

function sendLocalTypingStatus(status) {
    if (status !== TYPING && status !== STOPPED) {
        status = IDLE;
    }

    $.ajax({
        url: 'aj.php',
        type: 'post',
        data: { 'what': 'typing', 'csrf_token': csrf_token, 'status':status },
        dataType: 'json',
        success: function (json) {
        }
    });
}

var setLocalTypingStatus = (function () {
    var typingStatus = IDLE,
        timeout = null,
        msBeforeStopped = 10000,
        msBeforeIdle = 10000;

    return function (val) {
        var self = arguments.callee;

        if (typingStatus !== val) {
            typingStatus = val;
            sendLocalTypingStatus(val);
        }

        if (timeout !== null) {
            clearTimeout(timeout);
            timeout = null;
        }

        if (typingStatus === TYPING) {
            timeout = setTimeout(
                function () {
                    self(STOPPED);
                },
                msBeforeStopped
            );
        } else if (typingStatus === STOPPED) {
            timeout = setTimeout(
                function () {
                    self(IDLE);
                },
                msBeforeIdle
            );
        }
    };
}());

function updateTypingIndicators(data) {
    if (data) {
        $('#online-users h6').each(function () {
            if (/user(\d+)/.test($(this).attr('class'))) {
                var status = data[RegExp.$1] || 1;

                if (status == TYPING) {
                    $(this).addClass('typing');
                } else {
                    $(this).removeClass('typing');
                }

                if (status == STOPPED) {
                    $(this).addClass('stopped');
                } else {
                    $(this).removeClass('stopped');
                }
            }
        });
    }
}

function updateRelativeTimes() {
    var mins = 60, hour = mins * 60, day = hour * 24;
    var week = day * 7, month = day * 30, year = day * 365;
    var delay = 0, lastTimestamp_time = 0, displayTimestamp = false;

    // Update relative times
    $(".entry-date").each(function() {
        var timestamp = currentTime - ($(this).attr('data')|0);
        $(this).text(relativeTime(timestamp));
    });

    var currentEntry = 0, entriesWithoutTimestamp = 0;
    $('#entries .entry-date').each(function() {
        currentEntry++;
        
        var entry_time = ($(this).attr('data')|0);
        delay = currentTime - entry_time;
        displayTimestamp = false;
        
        if (currentEntry == 1) {
            displayTimestamp = true;
        } else {
            if (delay < hour) {
                displayTimestamp = (entry_time - lastTimestamp_time > (5 * mins) || entriesWithoutTimestamp > 10);
            }
            if (delay > hour && delay < day) {
                displayTimestamp = (entry_time - lastTimestamp_time > hour || entriesWithoutTimestamp > 10);
            }
            if (delay > day && delay < week) {
                displayTimestamp = (entry_time - lastTimestamp_time > (3 * hour) || entriesWithoutTimestamp > 20);
            }
            if (delay > week) {
                displayTimestamp = (entry_time - lastTimestamp_time > day || entriesWithoutTimestamp > 20);
            }
        }
            
        if (displayTimestamp) {
            $(this).parents('.entry').addClass('timestamp');
            entriesWithoutTimestamp = 0;
            lastTimestamp_time = entry_time;
        } else {
            entriesWithoutTimestamp++;
        }
    });
}

function updateNicknamesDisplaying() {
    var author = '',
        prev_author = '';
    $('#entries .entry-author').each(function() {
        author = $(this).text();
        entryWithTimestamp = $(this).parents('.entry').hasClass('timestamp');
        if (prev_author != author || entryWithTimestamp) {
            $(this).parents('.entry').addClass('showNickname');
        }
        prev_author = $(this).text();
    });
}

function updateEntryDates() {
    var entries = $('.entry'),
        firstEntryWithDateFromStart,
        firstEntryWithDateFromEnd;
    if (entries.length == 0) {
        return;
    }
    firstEntryWithDateFromStart = 0;
    firstDate = $('span.entry-date', entries[firstEntryWithDateFromStart]).attr('data');
    while( firstDate === undefined && firstEntryWithDateFromStart < entries.length ) {
        firstEntryWithDateFromStart++;
        firstDate  = $('span.entry-date', entries[firstEntryWithDateFromStart]).attr('data');
    }
    firstEntryWithDateFromEnd = entries.length - 1;
    lastDate  = $('span.entry-date', entries[firstEntryWithDateFromEnd]).attr('data');
    while( lastDate === undefined && firstEntryWithDateFromEnd > 0 ) {
        firstEntryWithDateFromEnd--;
        lastDate  = $('span.entry-date', entries[firstEntryWithDateFromEnd]).attr('data');
    }
    if (lastDate > lastEntryDate) {
        lastEntryDate = lastDate;
    }
}

function initializeFileUploadSupport() {
    var agent=navigator.userAgent.toLowerCase();
    if ((agent.indexOf('iphone')!=-1)) {
        return;
    }
    new AjaxUpload('#uploadButton', {
        action: 'helper/doajaxfileupload.php',
        data : { csrf_token: csrf_token },
        responseType: 'json',
        name: 'attachment',
        title: 'Upload to Chat',
        onComplete : function(file, data){
            if(typeof(data.error) != 'undefined') {
                if(data.error != '') {
                    alert("Error uploading file: " + data.error);
                }
            }
        }
    });
}

function relativeTime(time) {
    var mins = 60, hour = mins * 60, day = hour * 24;
    var week = day * 7, month = day * 30, year = day * 365;
    
    if (time < 0) {
        time = 0;
    }
    if (time >= week)
    {
        var now = new Date() ;
        var tz = now.getTimezoneOffset() ;
        now = (now.getTime() / 1000) | 0;
        // date is set in UTC so remove 5 hours to get the server time
        now  -= time + 5 * 3600 ;
        var d = new Date() ;
        d.setTime(now * 1000) ;
        var hours = d.getUTCHours() ;
        if (hours < 10){
            hours = "0" + hours ;
        }
        var min = d.getUTCMinutes() ;
        if (min < 10){
            min = "0" + min ;
        }

        return d.getUTCDate() + ' ' + months[d.getUTCMonth()] + ' ' + d.getUTCFullYear();
    }

    var segments = new Array();
    segments[5] = (time / year)|0;  time %= year;
    segments[4] = (time / month)|0; time %= month;
    if (!segments[5]) {
        segments[3] = (time / day)|0;   time %= day;
        if (!segments[4]) {
            segments[2] = (time / hour)|0;  time %= hour;
            if (!segments[3]) {
                segments[1] = (time / mins)|0;  time %= mins;
                if (!segments[2] && !segments[1]) {
                    segments[0] = time;
                }
            }
        }
    }

    var cnt, relTime = '';
    var segnames = [ 'sec', 'min', 'hr', 'day', 'mnth', 'yr' ];
    for (var i = segments.length-1; i >= 0; i--) {
        if (segments[i]) {
            relTime += segments[i]+' '+segnames[i];
            if (segments[i] > 1) relTime += 's';
            relTime += ', ';
        }
    }
    relTime = relTime.substr(0, relTime.length-2);
    if (relTime != '') {
        return relTime + " ago";
    } else {
        return "just now";
    }
}

function pausecomp(millis) {
    var date = new Date();
    var curDate = null;

    do { curDate = new Date(); }
    while(curDate-date < millis);
}

function scrollViewTo(scrolllTo) {
    if (scrollBottom === false) {
        return;
    }
    if (scrolllTo == '100%') {
        $('#entries')[0].scrollTop = $('#entries')[0].scrollHeight;
    } else if (scrolllTo == '50%') {
        $('#entries')[0].scrollTop = $('#entries')[0].scrollHeight / 2 - $('#entries').outerHeight() / 2;
    } else if (scrolllTo == '0%') {
        $('#entries')[0].scrollTop = 0;
    }
}

function tooltipTime(x) {
    var plural = '';

    var mins = 60, hour = mins * 60; day = hour * 24,
    week = day * 7, month = day * 30, year = day * 365;

    if (x >= year) { x = (x / year)|0; dformat="yr"; }
    else if (x >= month) { x = (x / month)|0; dformat="mnth"; }
    else if (x >= day) { x = (x / day)|0; dformat="day"; }
    else if (x >= hour) { x = (x / hour)|0; dformat="hr"; }
    else if (x >= mins) { x = (x / mins)|0; dformat="min"; }
    else { x |= 0; dformat="sec"; }
    if (x > 1) plural = 's';
    return x + ' ' + dformat + plural + ' ago';
}

function bookmarkHandler(el, text, menudata) {
    var bmdate = null;

    if (menudata.bmtype == 'private') {
        var date = new Date();
        date.setTime(date.getTime() + (7 * 24 * 60 * 60 * 1000));

        var markerHTML =
            '<div id="marker" title="Bookmarker">' +
            '  <form id="markerForm" method="post">' +
            '    <input type="bmname" name="bmname"/><br />' +
            '    <input type="submit" name="submit" value="Add Bookmark" />' +
            '  </form>';
        '</div>';

        var marker = $(markerHTML).dialog({ modal: true, width: 'auto', height: 'auto', hide: 'drop' });
        $("#markerForm").submit(function(){
            bmdate = el.attr('data');
            var bmname = $('#markerForm input[name="bmname"]').val();
            var bookmarks = $.cookie('bookmarks');
            if (!bookmarks) {
                $.cookie('bookmarks', bmname+'/'+bmdate+',0,0,0', { path: '/', expires: date });
            } else {
                bookmarks = bookmarks.split(',');
                for (var i = 3; i > 0; i--) {
                    bookmarks[i] = bookmarks[i-1];
                }
                bookmarks[0] = bmname+'/'+bmdate;
                $.cookie('bookmarks', bookmarks.join(','), { path: '/', expires: date });
            }
            $('.entry-date').menuPopup(buildBookmarkMenu(), { selectionRequired: false } );
            marker.dialog('close');
            marker.remove();
            return false;
        });
    }

    if (menudata.bmtype == 'goto') {
        if (menudata.bmdate) {
            bmdate = menudata.bmdate;
        } else {
            bmdate = el.attr('data');
        }
        gotoTime(bmdate);
    }
}

function spamHandler(el) {
    var id = $(el).parents('div.entry').attr('id').replace('entry-', '');
    $.ajax({
        url: 'aj.php',
        type: 'post',
        data: {
            what: 'markspam',
            entryid: id,
            csrf_token: csrf_token
        },
        dataType: 'json',
        success: function(json) {
            if (json.success == true) {
                var html = '<div class="info" title="Information">' +
                                '<p style="margin: 0;">' + json.message + '</p>' +
                            '</div>';
                $('#entry-' + id).remove();
            } else {
                var html = '<div class="info" title="An Error Occured">' +
                                '<p style="margin: 0;">' + json.message + '</p>' +
                            '</div>';
            }
            var info = $(html).dialog({
                modal: true,
                autoOpen: true,
                hide: 'drop', resizable: false,
            }).centerDialog();
            var x = (document.body.clientWidth - $(info).parent().width()) / 2;
            var y = Math.max(0, (document.body.clientHeight - $(info).parent().height()) / 2);
            $(info).parent().css({left:x, top:y});
        }
    });
}

function blockHandler(el) {
    var id = $(el).parents('div.entry').attr('id').replace('entry-', '');
    var blockHTML = '<div class="blockForm" title="Block IP">' +
        '   <form method="post">' +
        '       <div class="message"></div>' +
        '       <div class="sliderInfo">Block Duration: <span>1 day and 0 hours</span></div><br />' +
        '       <div class="slider"></div><br />' +
        '       <input type="hidden" name="entryid" value="' + id + '" />' +
        '       <input type="hidden" name="hours" value="24" />' +
        '       <input type="button" name="blockip" value="Block IP" /><br /><br />' +
        '       <input type="text" name="ipv4" value="0.0.0.0" style="width:259px;" /><br /><br />' +
        '       <input type="button" name="unblockip" value="Unblock IP" />' +
        '   </form>' +
        '</div>';
    var block = $(blockHTML).dialog({
        modal: true,
        hide: 'drop', resizable: false,
        width: 300,
        resizable: false,
        draggable: false
    }).centerDialog();

    block.find('.slider').slider({
        min: 1,
        max: 720,
        value: 24,
        slide: function(e, data) {
            var d = Math.floor(data.value / 24);
            var h = ((d == 0) ? data.value : Math.floor(data.value % d));
            block.find('.sliderInfo span').text(d + ((d == 1) ? ' day ' : ' days ') + 'and ' + h + ((h == 1) ? ' hour' : ' hours'));
        },
        change: function(e, data) {
            block.find('input[name=hours]').val(data.value);
        }
    });

    block.find('input[name=blockip]').click(function() {
        $.ajax({
            url: 'aj.php',
            type: 'post',
            data: {
                what: 'blockip',
                entryid: block.find('input[name=entryid]').val(),
                hours: block.find('input[name=hours]').val(),
                csrf_token: csrf_token
            },
            dataType: 'json',
            success: function(json) {
                block.find('div.message').empty();
                var close = false;
                if (json.success == true) {
                    var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:252px;" class="ui-state-highlight ui-corner-all">' +
                                    '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>' +
                                    '<strong>Info:</strong> ' + json.message + '</p>' +
                                '</div>';
                    close = true;
                } else {
                    var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:252px;" class="ui-state-error ui-corner-all">' +
                                    '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                                    '<strong>Error:</strong> ' + json.message + '</p>' +
                                '</div>';
                }
                block.find('div.message').append(html);
                if (close == true) {
                    setTimeout("$('.ui-dialog-titlebar-close.ui-corner-all').click()", 3000);
                }
            }
        });
    });

    block.find('input[name=unblockip]').click(function() {
        $.ajax({
            url: 'aj.php',
            type: 'post',
            data: {
                what: 'unblockip',
                ipv4: block.find('input[name=ipv4]').val(),
                csrf_token: csrf_token
            },
            dataType: 'json',
            success: function(json) {
                block.find('div.message').empty();
                var close = false;
                if (json.success == true) {
                    var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:252px;" class="ui-state-highlight ui-corner-all">' +
                                    '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>' +
                                    '<strong>Info:</strong> ' + json.message + '</p>' +
                                '</div>';
                    close = true;
                } else {
                    var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:252px;" class="ui-state-error ui-corner-all">' +
                                    '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                                    '<strong>Error:</strong> ' + json.message + '</p>' +
                                '</div>';
                }
                block.find('div.message').append(html);
                if (close == true) {
                    setTimeout("$('.ui-dialog-titlebar-close.ui-corner-all').click()", 3000);
                }
            }
        });
    });

    var x = (document.body.clientWidth - $(block).parent().width()) / 2;
    var y = Math.max(0, (document.body.clientHeight - $(block).parent().height()) / 2);
    $(block).parent().css({left:x, top:y});
}

function blockIP(el, text, menudata){
    $.ajax({
        url: 'aj.php',
        type: 'post',
        data: {
            what: 'blockip',
            entryid: el.context.parentNode.id.substring(6),
            hours: 1,
            csrf_token: csrf_token
        },
        dataType: 'json',
        success: function(json) {
            alert(json.message);
        }
    });
}

function twitterHandler(el, text, menudata) {
    text = text.substr(0, 140);

    userValue = pwdValue = '';
    if ($.cookie('twitterUser')) userValue = ' value="'+$.cookie('twitterUser')+'"';
    if ($.cookie('twitterPwd')) pwdValue = ' value="'+$.cookie('twitterPwd')+'"';
    var tweeterHTML =
        '<div id="tweeter" title="LoveMachine Tweeter">' +
        '  <form id="tweeterForm" method="post">' +
        '    <table><tr><th>Username:</th><th>Password</th><tr>'+
        '      <tr><td><input type="input" name="twitterUser"'+userValue+'/></td>' +
        '      <td><input type="password" name="twitterPwd"'+pwdValue+'/></td></tr></table>' +
        '    <textarea name="tweet">'+text+'</textarea><br />' +
        '    <input type="submit" name="submit" value="Send Tweet" />' +
        '  </form>';
    '</div>';

    var tweeter = $(tweeterHTML).dialog({ modal: true, width: 'auto', height: 'auto', hide: 'drop' }).centerDialog();
    $("#tweeterForm").submit(function(){
        var data = '';
        data += 'user=' + $('#tweeterForm input[name="twitterUser"]').val();
        data += '&pwd=' + $('#tweeterForm input[name="twitterPwd"]').val();
        data += '&status='+ $('#tweeterForm textarea').text();
        $.ajax({
            type: "POST",
            url: 'tweet.php',
            data: data,
            dataType: 'json',
            success: function(json) {
            var message;
            if (json.error) {
                if (json.error.indexOf('authenticate') >= 0) {
                    $('#tweeterForm').hide();
                    message = 'We were unable to send your message.<br />Please check your username and password<br />and try again.';
                }
            } else {
                var date = new Date();
                date.setTime(date.getTime() + (7 * 24 * 60 * 60 * 1000));
                $.cookie('twitterUser', $('#tweeterForm input[name="twitterUser"]').val(), { path: '/', expires: date });
                $.cookie('twitterPwd', $('#tweeterForm input[name="twitterPwd"]').val(), { path: '/', expires: date });
                $('#tweeterForm').hide();
                message = 'Congratulations!<br /><br/>Your tweet has been sent!';
            }
            $('#tweeter').append(message+'<br /><br />');
            $('#tweeter').append('<input id="errorok" type="submit" name="ok" value="Ok" />').click(function(){
                tweeter.dialog('close');
                tweeter.remove();
                return false;
            });
        },
        error: function(xhdr, status, err) {
            message = 'We were unable to send your message. An error occured while communicating with twitter.<br />Please try again.';
            $('#tweeter').append(message+'<br /><br />');
            $('#tweeter').append('<input id="errorok" type="submit" name="ok" value="Ok" />').click(function(){
                tweeter.dialog('close');
                tweeter.remove();
                return false;
            });
        }
        });
        return false;
    });

    return false;
}

function userHandler(el, text, menudata) {
    if (menudata.actionType == 'penalty') {
        onPenaltyBox(el);
    }
    if (menudata.actionType == 'workitems') {
        onUserItems(el);
    }
}

function relativity() {
    resizeElements();
    scrollViewTo('100%', true);
    speakerListHeight = $('#online-users > div').outerHeight();
}
$(window).resize(function() {
    relativity();
});

function gotoTime(gotoTime) {
    queryStr = '';
    var entries = $("#entries");
    entries.empty();
    $('#loading-spin').show();
    $.post("aj.php", { 'what': 'time', 'count': 50, 'time': gotoTime, 'csrf_token': csrf_token, 'filter': systemMsgs }, function(json) {
        $('#loading-spin').removeClass().hide();
        inThePresent = false;
        firstDate = json.firstDate;
        lastDate = json.lastDate;
        if (lastDate > lastEntryDate) {
            lastEntryDate = lastDate;
        }
        entries.append(json.html);
        finishUpdate('50%', false);
    }, 'json');
}

function initSound() {
    // Attach player elements
    chatSound      = new Sound(document.getElementById('chatSoundPlayer'));
    systemSound    = new Sound(document.getElementById('systemSoundPlayer'));
    pingSound      = new Sound(document.getElementById('pingSoundPlayer'));
    botSound       = new Sound(document.getElementById('botSoundPlayer'));
    emergencySound = new Sound(document.getElementById('emergencySoundPlayer'));
    
    // Save sound settings
    if(0 == soundSettings[0]) {
        chatSound.mute();
        document.getElementById("chataudiooff").checked = true;
    }
    if(0 == soundSettings[1]) {
        systemSound.mute();
        document.getElementById("systemaudiooff").checked = true;
    }
    if(0 == soundSettings[2]) {
        botSound.mute();
        document.getElementById("botaudiooff").checked = true;
    }
    if(0 == soundSettings[3]) {
        pingSound.mute();
        document.getElementById("pingaudiooff").checked = true;
    }
    if(0 == soundSettings[4]) {
        emergencySound.mute();
        document.getElementById("emergencyaudiooff").checked = true;
    }
}

/**
 * Important, since iPad can only play one stream at a time
 * trying to initialize all sounds at once will fail.
 */
function sndInit() {
    chatSound.player.play();
    chatSound.player.pause();
    
    $('#mobileEnableAlerts').hide();
}

$(window).ready(function() {
    if(!navigator.userAgent.match(/iPhone/i) &&
       !navigator.userAgent.match(/iPod/i) &&
       !navigator.userAgent.match(/iPad/i) &&
       !navigator.userAgent.match(/Android/i)) {
       
       $('#mobileEnableAlerts').remove();
    } else {
       $('#mobileEnableAlerts').show();
    }
    
    resizeElements();
    $('#loading-spin').removeClass().hide();
    applyPopupBehavior();
    initializeFileUploadSupport();
    initSound();

    $('#user-info').dialog({
        autoOpen: false,
        resizable: false,
        modal: false,
        show: 'fade',
        hide: 'fade',
        width: 800,
        height: 480,
        close: function() {
            getFavoriteUsers();
        }
    });

    relativity();
    
    $('#go').click(function(){
        showLatest();
    });
    
    $("#search_reset").click(function(e){
        if (queryStr.length > 0) {
            showLatest();
        }
        e.preventDefault();
        return false;
    });
    
    $("#searchForm").submit(function(){
        queryStr = $('#query').val();
        inThePresent = false;
		queryStrIsJob = false;
		
		// If  query string is a job # open new window with job detail page.
		var re = new RegExp(/^\#?\d+$/);
		if (queryStr.match(re)) {
			$.ajax({
			  type: 'POST',
			  url: "getworklist.php",
			  data: { 'query': queryStr},
			  success: function(json) {
					if (json[0] == "redirect") {
						queryStrIsJob = true;
						queryStr = queryStr.replace('#', '');
						window.open('workitem.php?job_id=' + queryStr + '&action=view', '_newtab');
					}
				},
			  dataType: 'json',
			  async: false
			});
			if (queryStrIsJob == true) {
				return false;
			}
		}

        var entries = $("#entries");
        entries.empty();
        $('#loading-spin').show();
        $.post("aj.php", { 'what': 'latest', 'count': 50, 'query': queryStr, 'csrf_token': csrf_token, 'filter': systemMsgs}, function(json) {
            inThePresent = false;
            $('#loading-spin').removeClass().hide();
            firstDate = json.firstDate;
            lastDate = json.lastDate;
            entries.append(json.html);
            finishUpdate('100%', false);
            
            inSearchResult = ($.trim(queryStr).length > 0);
            updateTitleTimerInterval();

            var drawer = $("#system-notifications > div");
            drawer.empty();
            drawer.prepend(json.system_html);
            $('#system-notifications > div')[0].scrollTop = 0;
        }, 'json');
        return false;
    });

    $("#sub").click(function(){

        // users in penalty box can't send messages
        if(!penalized){
            sendEntry();
        }
        return false;
    });

    $("#message-pane").keyup(function(e) {
        if (e.keyCode == 16) {
            shiftPressed = false;
        }
    });
    $("#message-pane").keydown(function(e) {
        journal.reloadWindowTimerReset();
        // shift
        if (e.keyCode == 16) {
            shiftPressed = true;
        }
        // user hit "Enter" key
        if (e.keyCode == 13) {
            if (shiftPressed) {
                return true;
            }
            sendEntry();
            return false;
        }
    });
    // Prevent arrow keys from bubbling to the scrollbar handler.
    $("#message-pane").keydown(function(e) {
        if (e.keyCode == 38 || e.keyCode == 40) {
            e.stopPropagation();
        }
    });
    
    $('<div id="livetip" class="overlay"></div>').hide().appendTo('body');

    if (gotoDate != 0) {
        getEntriesAt(gotoDate);
    } else {
        getLatestEntries(1);
        scrollViewTo('100%', true);
    }

    for(timer in $.timer)
    {
        clearInterval(timer);
    }
    
    getFavoriteUsers();
    
    $.timer['getLatestEntries'] = setInterval(getLatestEntries, pollingInterval);
    $.timer['getLatestSpeakers'] = setInterval(getLatestSpeakers, speakerPollingInterval);
    journal.reloadWindowTimerAction = function() {
        location.reload(true);
    };
    journal.reloadWindowTimerReset = function() {
        if (journal.timerReloadWindowTimer) {
            window.clearTimeout(journal.timerReloadWindowTimer);
        }
        journal.timerReloadWindowTimer = setTimeout(journal.reloadWindowTimerAction, 1000 * journal.reloadWindowTimer);
    };
    journal.reloadWindowTimerReset();
    scrollViewTo('100%', true);
    enlivenEntries();


    speakerListHeight = $('#online-users > div').height();
    getLatestSpeakers();

    $('#popup-user-info #roles input[type="submit"]').click(function(){
        var name = $(this).attr('name');
        switch(name){
        case "cancel":
            $('#popup-user-info').dialog('close');
            return false;
        }
    });
    AudioOn();

     //fetch TZ from worklist
    doc = document.location.href.split("chat");
    $.ajax({
        url: 'api.php',
        type: "POST",
        data: {'action': 'getTimezone','username': userName},
        dataType: "json",
        success: function(data) {
            if (data.succeeded == true && data.message != null) {
                $.ajax({
                    url: "api.php",
                    type: "POST",
                    data: {'action' : 'updateTimezone','userid': userId, 'timezone':data.message},
                    dataType: "json",
                    success: function(data) {
                    }
                });
            }
        }
    });
    
    $.ajax({
        url: 'api.php',
        type: "POST",
        data: {'action': 'updateLastSeen','username': userName},
        dataType: "json",
        success: function(data) {
            if (data.succeeded == true && data.message != null) {
                //success
            }
        }
    });
    
    $('#entries').scroll(function() {
        if (!previousChatInterval && $('#entries')[0].scrollTop <= $('#entries .entry.timestamp:first-child').outerHeight() / 2) {
            $('#loading-spin').addClass('loadingPreviousEntries').show();
            previousChatInterval = setInterval(function() {
                if ($('#entries')[0].scrollTop <= $('#entries .entry.timestamp:first-child').outerHeight() / 2) {
                    getEntriesNear(true);
                } else {
                    $('#loading-spin').removeClass('loadingPreviousEntries').hide();
                }
                clearInterval(previousChatInterval);
                previousChatInterval = null;
            }, 1000);
        } else if (!futureChatInterval && lastEntryDate > lastDate && ($('#entries')[0].scrollTop >= $('#entries')[0].scrollHeight - $('#entries').outerHeight() - $('#entries .entry:last-child').outerHeight() / 2)) {
            $('#loading-spin').addClass('loadingFutureEntries').show();
            futureChatInterval = setInterval(function() {
                if ($('#entries')[0].scrollTop >= $('#entries')[0].scrollHeight - $('#entries').outerHeight() - $('#entries .entry:last-child').outerHeight() / 2) {
                    getEntriesNear(false);
                } else {
                    $('#loading-spin').removeClass('loadingFutureEntries').hide();
                }
                clearInterval(futureChatInterval);
                futureChatInterval = null;
            }, 1000);
        }
    });
    
    $('#message-pane').focus();
});

$(window).load(function(){
    showLatest();
    resizeElements();
    
    $('#message-pane').focus();
});

/*
 *   Function: AjaxPopup
 *
 *    Purpose: This function is used for popups that require additional information from
 *             the server and uses an Ajax post call to query the server.
 *
 * Parameters: popupId - The id element for the block holding the popup's html
 *             titleString - The title for the popup box
 *             urlString - The URL to issue the Ajax call to
 *             keyId - The database id that will be mapped to 'itemid' in the form
 *             fieldArray - An array containing the list of fields that need
 *                          to be updated on the popup box.
 *                array[0] - Type of element being populated [input|textbox|checkbox|span]
 *                array[1] - Type if of the element being populated
 *                array[2] - The value to be inserted into the element
 *                array[3] - undefined or 'eval' - If eval the array[2] item will
 *                           be passed to eval() for working with json return objects
 *             successFunc - An optional function that gets executed after populating the fields.
 *
 */
function AjaxPopup(popupId,
        titleString,
        urlString,
        keyId,
        fieldArray,
        successFunc)
{
    $(popupId).data('title.dialog', titleString);

    $.ajax({type: "POST",
        url: urlString,
        data: 'item='+keyId,
        dataType: 'json',
        success: function(json) {

        $.each(fieldArray,
                function(key,value){
            if(value[0] == 'input') {
                if(value[3] != undefined && value[3] == 'eval')  {
                    $('.popup-body form input[name="' + value[1] +'"]').val( eval(value[2]) );
                } else {
                    $('.popup-body form input[name="' + value[1] +'"]').val( value[2] );
                }
            }

            if(value[0] == 'textarea') {
                if(value[3] != undefined && value[3] == 'eval')  {
                    $('.popup-body form textarea[name="' + value[1] +'"]').val( eval(value[2]) );
                } else {
                    $('.popup-body form textarea[name="' + value[1] +'"]').val( value[2] );
                }
            }

            if(value[0] == 'checkbox') {
                if(value[3] != undefined && value[3] == 'eval')  {
                    $('.popup-body form checkbox[name="' + value[1] +'"] option[value="'+ eval(value[2])+'"]').prop('checked', true);
                } else {
                    $('.popup-body form checkbox[name="' + value[1] +'"] option[value="'+ value[2] +'"]').prop('checked', true);
                }
            }

            if(value[0] == 'span')  {
                if(value[3] != undefined && value[3] == 'eval')  {
                    $('.popup-body form ' + value[1]).text( eval(value[2]) );
                } else {
                    $('.popup-body form ' + value[1]).text( value[2] );
                }
            }
        });

        if(successFunc !== undefined) {
            successFunc(json);
        }
    }
    });


}

/*
 *   Function: SimplePopup
 *
 *    Purpose: This function is used for popups that do not require additional
 *             calls to the server to grab data.
 *
 * Parameters: popupId - The id element for the block holding the popup's html
 *             titleString - The title for the popup box
 *             keyId - The database id that will be mapped to 'itemid' in the form
 *             fieldArray - An array containing the list of fields that need
 *                          to be updated on the popup box.
 *                array[0] - Type of element being populated [input|textbox|checkbox|span]
 *                array[1] - Name of the element being populated
 *                array[2] - The value to be inserted into the element
 *                array[3] - undefined or 'eval' - If eval the array[2] item will
 *                           be passed to eval() for working with json return objects
 *             successFunc - An optional function that gets executed after populating the fields.
 *
 */
function SimplePopup(popupId,
        titleString,
        keyId,
        fieldArray,
        successFunc)
{
    $(popupId).data('title.dialog', titleString);

    $.each(fieldArray,
            function(key,value){
        if(value[0] == 'input') {
            if(value[3] != undefined && value[3] == 'eval')  {
                $('.popup-body form input[name="' + value[1] +'"]').val( eval(value[2]) );
            } else {
                $('.popup-body form input[name="' + value[1] +'"]').val( value[2] );
            }
        }

        if(value[0] == 'textarea') {
            if(value[3] != undefined && value[3] == 'eval')  {
                $('.popup-body form textarea[name="' + value[1] +'"]').val( eval(value[2]) );
            } else {
                $('.popup-body form textarea[name="' + value[1] +'"]').val( value[2] );
            }
        }

        if(value[0] == 'checkbox') {
            if(value[3] != undefined && value[3] == 'eval')  {
                $('.popup-body form checkbox[name="' + value[1] +'"] option[value="'+ eval(value[2])+'"]').prop('checked', true);
            } else {
                $('.popup-body form checkbox[name="' + value[1] +'"] option[value="'+ value[2] +'"]').prop('checked', true);
            }
        }

        if(value[0] == 'span')  {
            if(value[3] != undefined && value[3] == 'eval')  {
                $('.popup-body form ' + value[1]).text( eval(value[2]) );
            } else {
                $('.popup-body form ' + value[1]).text( value[2] );
            }
        }

    });

    if(successFunc !== undefined) {
        successFunc(json);
    }
}

function resizeElements() {
    var ih = 0;
    var iw = 0;
    if (typeof window.innerWidth != 'undefined') {
        ih = window.innerHeight;
        iw = window.innerWidth;
    } else if (typeof document.documentElement) {
        ih = document.documentElement.clientHeight;
        iw = document.documentElement.clientWidth;
    }
    
    var guideline_height = ih - $('#welcome').outerHeight();
    $('#guideline').css('height', Number(guideline_height) + 'px');
    
    var entries_height = $('#center-container').outerHeight() - $('#bottom-panel').outerHeight() - 1;
    $('#entries').css('height', Number(entries_height) + 'px');
    
    var left_container_width = 
        $('#welcomeInside > .leftMenu > a:first-child + .headerButtonSeparator').offset().left +
        $('#welcomeInside > .leftMenu > a:first-child + .headerButtonSeparator').outerWidth() + 8;
    $('#left-container').css('width', Number(left_container_width) + 'px');
    $('#center-container').css('left', Number(left_container_width) + 'px');
    $('div#SettingsWindow').css('left', Number(left_container_width) + 'px');

    var right_container_width = iw - $('#center-container').offset().left - $('#center-container').outerWidth() - 1;
    $('#right-container').css('width', right_container_width);
    
    var online_users_height = 
        $('#left-container').outerHeight()
      - $('#online-users > h3').outerHeight()
      - $('#search-filter-wrap').outerHeight();
    $('#online-users > div').outerHeight(online_users_height);

    var system_notifications_height = 
        $('#right-container').outerHeight() 
      - $('#current-jobs').outerHeight()
      - $('#system-notifications > h3').outerHeight()
      - ($('#penalty-container').is(':visible') ? $('#penalty-container').outerHeight() : 0)
      - 35;
    $('#system-notifications > div').outerHeight(system_notifications_height);
    
    $('#system-notifications > div')[0].scrollTop = 0;

}

$(function() {
    // Collect Bidding Jobs info
    getBiddingReviewDrawers();
    $('#share-this').hide();
    $("#query").DefaultValue("Chat history...");
});

// --

/*** ajaxupload-3.6.js ***/
/**
/* Ajax upload
* Project page - http://valums.com/ajax-upload/
* Copyright (c) 2008 Andris Valums, http://valums.com
* Licensed under the MIT license (http://valums.com/mit-license/)
* Version 3.6 (26.06.2009)
*/

/**
 * Changes from the previous version:
 * 1. Fixed minor bug where click outside the button
 * would open the file browse window
 *
 * For the full changelog please visit:
 * http://valums.com/ajax-upload-changelog/
 */

(function(){

var d = document, w = window;

/**
 * Get element by id
 */
function get(element){
 if (typeof element == "string")
 element = d.getElementById(element);
 return element;
}

/**
 * Attaches event to a dom element
 */
function addEvent(el, type, fn){
 if (w.addEventListener){
 el.addEventListener(type, fn, false);
 } else if (w.attachEvent){
 var f = function(){
 fn.call(el, w.event);
 };
 el.attachEvent('on' + type, f)
 }
}


/**
 * Creates and returns element from html chunk
 */
var toElement = function(){
 var div = d.createElement('div');
 return function(html){
 div.innerHTML = html;
 var el = div.childNodes[0];
 div.removeChild(el);
 return el;
 }
}();

function hasClass(ele,cls){
 return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
}
function addClass(ele,cls) {
 if (!hasClass(ele,cls)) ele.className += " "+cls;
}
function removeClass(ele,cls) {
 var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
 ele.className=ele.className.replace(reg,' ');
}

// getOffset function copied from jQuery lib (http://jquery.com/)
if (document.documentElement["getBoundingClientRect"]){
 // Get Offset using getBoundingClientRect
 // http://ejohn.org/blog/getboundingclientrect-is-awesome/
 var getOffset = function(el){
 var box = el.getBoundingClientRect(),
 doc = el.ownerDocument,
 body = doc.body,
 docElem = doc.documentElement,

 // for ie
 clientTop = docElem.clientTop || body.clientTop || 0,
 clientLeft = docElem.clientLeft || body.clientLeft || 0,

 // In Internet Explorer 7 getBoundingClientRect property is treated as physical,
 // while others are logical. Make all logical, like in IE8.


 zoom = 1;
 if (body.getBoundingClientRect) {
 var bound = body.getBoundingClientRect();
 zoom = (bound.right - bound.left)/body.clientWidth;
 }
 if (zoom > 1){
 clientTop = 0;
 clientLeft = 0;
 }
 var top = box.top/zoom + (window.pageYOffset || docElem && docElem.scrollTop/zoom || body.scrollTop/zoom) - clientTop,
 left = box.left/zoom + (window.pageXOffset|| docElem && docElem.scrollLeft/zoom || body.scrollLeft/zoom) - clientLeft;

 return {
 top: top,
 left: left
 };
 }

} else {
 // Get offset adding all offsets
 var getOffset = function(el){
 if (w.jQuery){
 return jQuery(el).offset();
 }

 var top = 0, left = 0;
 do {
 top += el.offsetTop || 0;
 left += el.offsetLeft || 0;
 }
 while (el = el.offsetParent);

 return {
 left: left,
 top: top
 };
 }
}

function getBox(el){
 var left, right, top, bottom;
 var offset = getOffset(el);
 left = offset.left;
 top = offset.top;

 right = left + el.offsetWidth;
 bottom = top + el.offsetHeight;

 return {
 left: left,
 right: right,
 top: top,
 bottom: bottom
 };
}

/**
 * Crossbrowser mouse coordinates
 */
function getMouseCoords(e){
 // pageX/Y is not supported in IE
 // http://www.quirksmode.org/dom/w3c_cssom.html
 if (!e.pageX && e.clientX){
 // In Internet Explorer 7 some properties (mouse coordinates) are treated as physical,
 // while others are logical (offset).
 var zoom = 1;
 var body = document.body;

 if (body.getBoundingClientRect) {
 var bound = body.getBoundingClientRect();
 zoom = (bound.right - bound.left)/body.clientWidth;
 }

 return {
 x: e.clientX / zoom + d.body.scrollLeft + d.documentElement.scrollLeft,
 y: e.clientY / zoom + d.body.scrollTop + d.documentElement.scrollTop
 };
 }

 return {
 x: e.pageX,
 y: e.pageY
 };

}
/**
 * Function generates unique id
 */
var getUID = function(){
 var id = 0;
 return function(){
 return 'ValumsAjaxUpload' + id++;
 }
}();

function fileFromPath(file){
 return file.replace(/.*(\/|\\)/, "");
}

function getExt(file){
 return (/[.]/.exec(file)) ? /[^.]+$/.exec(file.toLowerCase()) : '';
}

// Please use AjaxUpload , Ajax_upload will be removed in the next version
Ajax_upload = AjaxUpload = function(button, options){
 if (button.jquery){
 // jquery object was passed
 button = button[0];
 } else if (typeof button == "string" && /^#.*/.test(button)){
 button = button.slice(1);
 }
 button = get(button);

 this._input = null;
 this._button = button;
 this._disabled = false;
 this._submitting = false;
 // Variable changes to true if the button was clicked
 // 3 seconds ago (requred to fix Safari on Mac error)
 this._justClicked = false;
 this._parentDialog = d.body;

 if (window.jQuery && jQuery.ui && jQuery.ui.dialog){
 var parentDialog = jQuery(this._button).parents('.ui-dialog');
 if (parentDialog.length){
 this._parentDialog = parentDialog[0];
 }
 }

 this._settings = {
 // Location of the server-side upload script
 action: 'upload.php',
 // File upload name
 name: 'userfile',
 // Input title used for tooltip
 title: 'File Upload',
 // Additional data to send
 data: {},
 // Submit file as soon as it's selected
 autoSubmit: true,
 // The type of data that you're expecting back from the server.
 // Html and xml are detected automatically.
 // Only useful when you are using json data as a response.
 // Set to "json" in that case.
 responseType: false,
 // When user selects a file, useful with autoSubmit disabled
 onChange: function(file, extension){},
 // Callback to fire before file is uploaded
 // You can return false to cancel upload
 onSubmit: function(file, extension){},
 // Fired when file upload is completed
 // WARNING! DO NOT USE "FALSE" STRING AS A RESPONSE!
 onComplete: function(file, response) {}
 };

 // Merge the users options with our defaults
 for (var i in options) {
 this._settings[i] = options[i];
 }

 this._createInput();
 this._rerouteClicks();
}

// assigning methods to our class
AjaxUpload.prototype = {
 setData : function(data){
 this._settings.data = data;
 },
 disable : function(){
 this._disabled = true;
 },
 enable : function(){
 this._disabled = false;
 },
 // removes ajaxupload
 destroy : function(){
 if(this._input){
 if(this._input.parentNode){
 this._input.parentNode.removeChild(this._input);
 }
 this._input = null;
 }
 },
 /**
 * Creates invisible file input above the button
 */
 _createInput : function(){
 var self = this;
 var input = d.createElement("input");
 input.setAttribute('type', 'file');
 input.setAttribute('name', this._settings.name);
 input.setAttribute('title', this._settings.title);
 var styles = {
 'position' : 'absolute'
 ,'margin': '-5px 0 0 -175px'
 ,'padding': 0
 ,'width': '220px'
 ,'height': '30px'
 ,'fontSize': '14px'
 ,'opacity': 0
 ,'cursor': 'pointer'
 ,'display' : 'none'
 ,'zIndex' : 2147483583 //Max zIndex supported by Opera 9.0-9.2x
 // Strange, I expected 2147483647
 };
 for (var i in styles){
 input.style[i] = styles[i];
 }

 // Make sure that element opacity exists
 // (IE uses filter instead)
 if ( ! (input.style.opacity === "0")){
 input.style.filter = "alpha(opacity=0)";
 }

 this._parentDialog.appendChild(input);

 addEvent(input, 'change', function(){
 // get filename from input
 var file = fileFromPath(this.value);
 if(self._settings.onChange.call(self, file, getExt(file)) == false ){
 return;
 }
 // Submit form when value is changed
 if (self._settings.autoSubmit){
 self.submit();
 }
 });

 // Fixing problem with Safari
 // The problem is that if you leave input before the file select dialog opens
 // it does not upload the file.
 // As dialog opens slowly (it is a sheet dialog which takes some time to open)
 // there is some time while you can leave the button.
 // So we should not change display to none immediately
 addEvent(input, 'click', function(){
 self.justClicked = true;
 setTimeout(function(){
 // we will wait 3 seconds for dialog to open
 self.justClicked = false;
 }, 2500);
 });

 this._input = input;
 },
 _rerouteClicks : function (){
 var self = this;

 // IE displays 'access denied' error when using this method
 // other browsers just ignore click()
 // addEvent(this._button, 'click', function(e){
 // self._input.click();
 // });

 var box, dialogOffset = {top:0, left:0}, over = false;

 addEvent(self._button, 'mouseover', function(e){
 if (!self._input || over) return;

 over = true;
 box = getBox(self._button);

 if (self._parentDialog != d.body){
 dialogOffset = getOffset(self._parentDialog);
 }
 });


 // We can't use mouseout on the button,
 // because invisible input is over it
 addEvent(document, 'mousemove', function(e){
 var input = self._input;
 if (!input || !over) return;

 if (self._disabled){
 removeClass(self._button, 'hover');
 input.style.display = 'none';
 return;
 }

 var c = getMouseCoords(e);

 if ((c.x >= box.left) && (c.x <= box.right) &&
 (c.y >= box.top) && (c.y <= box.bottom)){

 input.style.top = c.y - dialogOffset.top + 'px';
 input.style.left = c.x - dialogOffset.left + 'px';
 input.style.display = 'block';
 addClass(self._button, 'hover');

 } else {
 // mouse left the button
 over = false;

 var check = setInterval(function(){
 // if input was just clicked do not hide it
 // to prevent safari bug

 if (self.justClicked){
 return;
 }

 if ( !over ){
 input.style.display = 'none';
 }

 clearInterval(check);

 }, 25);


 removeClass(self._button, 'hover');
 }
 });

 },
 /**
 * Creates iframe with unique name
 */
 _createIframe : function(){
 // unique name
 // We cannot use getTime, because it sometimes return
 // same value in safari :(
 var id = getUID();

 // Remove ie6 "This page contains both secure and nonsecure items" prompt
 // http://tinyurl.com/77w9wh
 var iframe = toElement('<iframe src="javascript:false;" name="' + id + '" />');
 iframe.id = id;
 iframe.style.display = 'none';
 d.body.appendChild(iframe);
 return iframe;
 },
 /**
 * Upload file without refreshing the page
 */
 submit : function(){
 var self = this, settings = this._settings;

 if (this._input.value === ''){
 // there is no file
 return;
 }

 // get filename from input
 var file = fileFromPath(this._input.value);

 // execute user event
 if (! (settings.onSubmit.call(this, file, getExt(file)) == false)) {
 // Create new iframe for this submission
 var iframe = this._createIframe();

 // Do not submit if user function returns false
 var form = this._createForm(iframe);
 form.appendChild(this._input);

 form.submit();

 d.body.removeChild(form);
 form = null;
 this._input = null;

 // create new input
 this._createInput();

 var toDeleteFlag = false;

 addEvent(iframe, 'load', function(e){

 if (// For Safari
 iframe.src == "javascript:'%3Chtml%3E%3C/html%3E';" ||
 // For FF, IE
 iframe.src == "javascript:'<html></html>';"){

 // First time around, do not delete.
 if( toDeleteFlag ){
 // Fix busy state in FF3
 setTimeout( function() {
 d.body.removeChild(iframe);
 }, 0);
 }
 return;
 }

 var doc = iframe.contentDocument ? iframe.contentDocument : frames[iframe.id].document;

 // fixing Opera 9.26
 if (doc.readyState && doc.readyState != 'complete'){
 // Opera fires load event multiple times
 // Even when the DOM is not ready yet
 // this fix should not affect other browsers
 return;
 }

 // fixing Opera 9.64
 if (doc.body && doc.body.innerHTML == "false"){
 // In Opera 9.64 event was fired second time
 // when body.innerHTML changed from false
 // to server response approx. after 1 sec
 return;
 }

 var response;

 if (doc.XMLDocument){
 // response is a xml document IE property
 response = doc.XMLDocument;
 } else if (doc.body){
 // response is html document or plain text
 response = doc.body.innerHTML;
 if (settings.responseType && settings.responseType.toLowerCase() == 'json'){
 // If the document was sent as 'application/javascript' or
 // 'text/javascript', then the browser wraps the text in a <pre>
 // tag and performs html encoding on the contents. In this case,
 // we need to pull the original text content from the text node's
 // nodeValue property to retrieve the unmangled content.
 // Note that IE6 only understands text/html
 if (doc.body.firstChild && doc.body.firstChild.nodeName.toUpperCase() == 'PRE'){
 response = doc.body.firstChild.firstChild.nodeValue;
 }
 if (response) {
 response = window["eval"]("(" + response + ")");
 } else {
 response = {};
 }
 }
 } else {
 // response is a xml document
 var response = doc;
 }

 settings.onComplete.call(self, file, response);

 // Reload blank page, so that reloading main page
 // does not re-submit the post. Also, remember to
 // delete the frame
 toDeleteFlag = true;

 // Fix IE mixed content issue
 iframe.src = "javascript:'<html></html>';";
 });

 } else {
 // clear input to allow user to select same file
 // Doesn't work in IE6
 // this._input.value = '';
 d.body.removeChild(this._input);
 this._input = null;

 // create new input
 this._createInput();
 }
 },
 /**
 * Creates form, that will be submitted to iframe
 */
 _createForm : function(iframe){
 var settings = this._settings;

 // method, enctype must be specified here
 // because changing this attr on the fly is not allowed in IE 6/7
 var form = toElement('<form method="post" enctype="multipart/form-data"></form>');
 form.style.display = 'none';
 form.action = settings.action;
 form.target = iframe.name;
 d.body.appendChild(form);

 // Create hidden input element for each data key
 for (var prop in settings.data){
 var el = d.createElement("input");
 el.type = 'hidden';
 el.name = prop;
 el.value = settings.data[prop];
 form.appendChild(el);
 }
 return form;
 }
};
})();

/*** fancyalert.js ***/
function fancyAlert(titleString, message) {
    $('body').append(
      '<div id="message_container">' +
      '</div>');
    $('#message_container').attr('title', titleString);
    $('#message_container').html(message);

    $('#message_container').dialog({
        modal: false, hide: 'drop', resizable: false,
        buttons: {
            Ok: function() {

                $(this).dialog('close');
            }
        },
        close: function(){
            $(this).dialog('destroy');
            $('#message_container').remove();
        }
    }).centerDialog();
}

/*** ui.toaster.js ***/
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

$(window).ready(function() {

    $('#popup-penalty').dialog({ autoOpen: false, hide: 'drop'});

    $('#popup-guest-selector').dialog({ autoOpen: false, hide: 'drop'});

    $('#popup-penalty #penalty input[name=penalize-cancel]').click(function(){
        $('#popup-penalty').dialog('close');
        return false;
    });
});

function onPenaltyBox(el){
    var id = el.parent().attr('class').substring(4);

    if(id != 0){
        $('#penalty #nicknamed').html(el.data('nickname'));
        showPenaltyDialog(id);
    }else{
        $('#penalty #nicknamed').html('Guest');
        $.post("penaltyajax.php", { 'what': 'guestlist', 'csrf_token': csrf_token}, function(json) {
            $('#popup-guest-selector #guest-list .guest-entry').remove();
            for(var i = 0; i < json.length; i++){

                var div = $('<div>');
                div.addClass('guest-entry');
                div.data('ip', json[i].ip);
                div.append(json[i].entry);
                $('#popup-guest-selector #guest-list').append(div);
            }
            $('#popup-guest-selector').dialog('open').centerDialog();

            $('#popup-guest-selector #guest-list .guest-entry').click(function(){
                $('#popup-guest-selector').dialog('close');
                var ip = $(this).data('ip');
                showPenaltyDialog(0, ip);
            });
        }, 'json');
    }
}

function showPenaltyDialog(penaltyId, penaltyIp){

    penaltyIp = penaltyIp != undefined ? penaltyIp : 0;

    // first check if we can penalize the user
    $.post("penaltyajax.php",
           {'what': 'checkpenalize', 'penalizer_id' : userId, 'penalated_id': penaltyId, 'penalated_ip': penaltyIp, 'csrf_token': csrf_token},
            function(json) {
            switch(json.status){
              case 'CAN_PENALIZE':
                if(penaltyId != 0){
                    $('#popup-penalty').data('title.dialog', 'Send User to Penalty Box');
                }else{
                    $('#popup-penalty').data('title.dialog', 'Send Guest to Penalty Box');
                }

                $('#popup-penalty #penalty input[name=penalize]').unbind();

                $('#popup-penalty #penalty input[name=penalize]').click(function(){

                    var reason = $('#popup-penalty #penalty textarea[name=penalty-reason]').val();

                    $.post("penaltyajax.php",
                           {'what': 'penalize', 'penalizer_id' : userId, 'penalated_id': penaltyId, 'penalated_ip': penaltyIp, 'reason': reason, 'csrf_token': csrf_token});
                    $('#popup-penalty').dialog('close');
                    return false;
                });

                $('#popup-penalty #penalty textarea[name=penalty-reason]').val('');
                $('#popup-penalty').dialog('open').centerDialog();
              break;

              case 'ALREADY_PENALIZED':
                fancyAlert("Already Penalized", "You already penalized this user. Can't do it again until user goes into Penalty Box");
              break;

              case 'ALREADY_SUSPENDED':
                fancyAlert("Already Suspended", "User is already suspended");
              break;

              case 'IN_BOX':
                fancyAlert("User in Penalty Box", "User is already in penalty box");
              break;

              case 'CANT_PENALIZE':
                fancyAlert("Can't Penalize", "You've been to Penalty Box more than 2 times - you can't penalize other users!");
              break;
            }

    }, 'json');


}

function checkPenalty(speaker){

    // check if it's guest or regular user
    if(speaker[0] == 0 && userId == 0){

        for(var i = 0; i < speaker[5].length; i++ ){
            if(speaker[5][i].ip == userIp && speaker[5][i].timeleft > 0){
                suspended = speaker[5][i].suspended == 1 ? true : false;
                fillPenaltyDescriptions(0, userIp);
                openPenaltyBox(speaker[5][i].timeleft);
            }
        }

        return false;
    }else{

        if(speaker[0] != 0){
            if(speaker[6] > 0 || speaker[7] == 1){

                if(speaker[0] == userId){
                    suspended = speaker[7] == 1 ? true : false;
                    fillPenaltyDescriptions(userId);
                    openPenaltyBox(speaker[6]);
                }
                return true;
            }else{

                return false;
            }
        }

    return false;

    }

}

function openPenaltyBox(timeout){

    penalized = true;
    $('#penalty-container').show();
    openDrawer();
    systemMsgs = 'all'
    $('#system-notifications > div').empty();
    $('#sub').prop('disabled', true);
    $('#message').prop('disabled', true);
    $('input[name="attachment"]').prop('disabled', true);

    if(suspended){

        $('#penalty-countdown').html('Your account has been suspended!');
    }else{

        $('#penalty-countdown').countdown({until: +timeout, format: 'MS', onExpiry:closePenaltyBox});
    }
}
function closePenaltyBox(){

    penalized = false;
    $('#penalty-container').hide();
    closeDrawer();
    $('#sub').prop('disabled', false);
    $('#message').prop('disabled', false);
    $('input[name="attachment"]').prop('disabled', prop);
}

function fillPenaltyDescriptions(penaltyId, penaltyIp){

    penaltyIp = penaltyIp != undefined ? penaltyIp : 0;

    // get reasons why user was penalized
    $.post("penaltyajax.php",
           {'what': 'getreasons', 'penalizer_id' : userId, 'penalated_id': penaltyId, 'penalated_ip': penaltyIp, 'csrf_token': csrf_token},
            function(json) {

            $('#penalty-descriptions .penalty-description-entry').remove();
            for(var i = json.length - 1; i >= 0; i-- ){

                if(json[i].reason != ''){
                      var div = $('<div>');
                      div.addClass('penalty-description-entry');
                      div.html(json[i].reason);
                      div.append('<br /><div class = "given">' + json[i].time + '</div><div style="clear:both; border: none;" />');
                      $('#penalty-descriptions').append(div);
                }
            }

    }, 'json');
}

/*** useritems.js ***/
$(window).ready(function() {
    $('#popup-useritems').dialog({ autoOpen: false, modal: false, hide: 'drop', resizable: false,  buttons: { "Ok": function() { $(this).dialog("close"); }}});
});

function onUserItems(el){

    var id = el.parent().attr('class').substring(4);

    $.getJSON('getuseritems.php?id=' + id,
        function(json){
            if(json.length > 0) {
                $('#popup-useritems #item-list .item-entry').remove();
                for(var i = 0; i < json.length; i++ ){

                    var div = $('<div>');
                    div.addClass('item-entry');
                    div.attr('id', 'worklist-' + json[i].id);
                    div.append('<div class = "item-head">#' + json[i].id + ' - ' + json[i].summary + '</div>');
                    var warn = '';
                    if(json[i].future_delta < 0){

                        warn = ' class = "warn"';
                    }
                    div.append('<div class = "item-body"><div class = "done-in">Done in: <span' + warn + '>' + json[i].relative +
                           '</span></div><div class = "clear"></div></div>');
                    div.data('id', json[i].id);
                    $('#popup-useritems #item-list').append(div);
                }

                // show additional info on item hovering
                makeWorkitemTooltip('.item-entry');

                $('.item-entry').click(function(){
                        window.open('workitem.php?job_id=' + $(this).data('id') + '&action=view');
                        // close popup if it was only one items
                        if(json.length == 1){

                            $('#popup-useritems').dialog('close');
                        }
                });

                var title = 'Items ' + el.data('nickname') + ' is working on';
                $('#popup-useritems').dialog( "option", "title", title )
                    .dialog('open').centerDialog();
            }else{
                fancyAlert("No current worklist items", el.data('nickname') + " is not working on any worklist items at the moment");
            }
        });
}

/*** typing.js ***/
function TypingNotifier(localUser) {
    var self = this;
    var customEventType = 'userkbd';

    $.extend(this, {
        outgoingNotification:$('<span></span>'),
        statusChanged:$('<span></span>'),

        localDispatchDelay:3000,
        localAutoStopDelay:10000,
        localAutoIdleDelay:20000,
        remoteAutoStopDelay:15000,
        remoteAutoIdleDelay:35000,

        _dispatchTimeout:undefined,
        _statusToDispatch:undefined,

        __dispatchStatus:function () {
            if (!this._dispatchTimeout && this._statusToDispatch) {
                this.outgoingNotification.trigger(
                    customEventType,
                    {
                        local:true,
                        user:localUser,
                        status:this._statusToDispatch
                    }
                );

                this._statusToDispatch = undefined;

                var self = this;

                this._dispatchTimeout = setTimeout(function () {
                    self._dispatchTimeout = undefined;
                    self.__dispatchStatus();
                }, this.localDispatchDelay);
            }
        },

        _dispatchStatus:function (value) {
            this._statusToDispatch = value;
            this.__dispatchStatus();
        },

        _status:{},
        _statusChangeTimeout:{},

        isLocal:function (user) {
            return user === localUser;
        },

        _scheduleStatusChange:function (user, value, delay) {
            var self = this;

            this._cancelStatusChange(user);

            this._statusChangeTimeout[user] = setTimeout(
                function () {
                    self._statusChangeTimeout[user] = undefined;
                    self._setStatus(user, value);
                },
                delay
            );
        },

        _cancelStatusChange:function (user) {
            if (this._statusChangeTimeout[user]) {
                clearTimeout(this._statusChangeTimeout[user]);
                this._statusChangeTimeout[user] = undefined;
            }
        },

        getLocalUserStatus:function () {
            return this._status[localUser] || TypingNotifier.IDLE;
        },

        getStatus:function (user) {
            return this._status[user] || TypingNotifier.IDLE;
        },

        _setStatus:function (user, value) {
            if (this.isLocal(user)) {
                this._dispatchStatus(value);
            }

            this.statusChanged.trigger(
                customEventType,
                {
                    local:this.isLocal(user),
                    user:user,
                    status:value
                }
            );

            if (value === TypingNotifier.IDLE) {
                delete this._status[user];
            } else {
                this._status[user] = value;
            }

            switch (value) {
            case TypingNotifier.TYPING:
                this._scheduleStatusChange(user, TypingNotifier.STOPPED, this.isLocal(user) ? this.localAutoStopDelay : this.remoteAutoStopDelay);
                break;

            case TypingNotifier.STOPPED:
                this._scheduleStatusChange(user, TypingNotifier.IDLE, this.isLocal(user) ? this.localAutoIdleDelay : this.remoteAutoIdleDelay);
                break;

            case TypingNotifier.IDLE:
                this._cancelStatusChange(user);
                break;
            }
        },

        setLocalUserStatus:function (value) {
            this._setStatus(localUser, value);
        },

        setRemoteUserStatus:function (user, value) {
            if (user !== localUser) {
                this._setStatus(user, value);
            }
        }
    });
}

TypingNotifier.TYPING = 'typing';
TypingNotifier.STOPPED = 'stopped';
TypingNotifier.IDLE = 'idle';

/*** livevalidation.js ***/
// LiveValidation 1.3 (standalone version)
// Copyright (c) 2007-2008 Alec Hill (www.livevalidation.com)
// LiveValidation is licensed under the terms of the MIT License


// Custom email validation fuctions for SendLove
function SLEmail(B,C){if(B)B=B.replace(/^\s+|\s+$/g,""); Validate.Email(B,C); }
function SLEmail2(B,C){if(B && B.replace(/^\s+|\s+$/g,"").match(/^[\w\d]+ \([\w\d._%-]+@[\w\d.-]+\.[\w]{2,4}\)$/)) return true; return Validate.Email(B,C); }

var LiveValidation=function(B,A){
    this.initialize(B,A);};
    LiveValidation.VERSION="1.3 standalone";
    LiveValidation.TEXTAREA=1;
    LiveValidation.TEXT=2;
    LiveValidation.PASSWORD=3;
    LiveValidation.CHECKBOX=4;
    LiveValidation.SELECT=5;
    LiveValidation.FILE=6;
    LiveValidation.massValidate=function(C){
        var D=true;
        for(var B=0,A=C.length;B<A;++B){
            var E=C[B].validate();if(D){D=E;}
            }return D;
            };
            LiveValidation.prototype={
                validClass:"LV_valid",invalidClass:"LV_invalid",messageClass:"LV_validation_message",validFieldClass:"LV_valid_field",invalidFieldClass:"LV_invalid_field",initialize:function(D,C){var A=this;
                if(!D){throw new Error("LiveValidation::initialize - No element reference or element id has been provided!");}
                this.element=D.nodeName?D:document.getElementById(D);if(!this.element){throw new Error("LiveValidation::initialize - No element with reference or id of '"+D+"' exists!");}
                this.validations=[];this.elementType=this.getElementType();
                this.form=this.element.form;var B=C||{};
                this.validMessage=B.validMessage||"";
                var E=B.insertAfterWhatNode||this.element;
                this.insertAfterWhatNode=E.nodeType?E:document.getElementById(E);
                this.onValid=B.onValid||function(){this.insertMessage(this.createMessageSpan());
                this.addFieldClass();};
                this.onInvalid=B.onInvalid||function(){this.insertMessage(this.createMessageSpan());
                this.addFieldClass();};
                this.onlyOnBlur=B.onlyOnBlur||false;
                this.wait=B.wait||0;this.onlyOnSubmit=B.onlyOnSubmit||false;
                if(this.form){this.formObj=LiveValidationForm.getInstance(this.form);
                this.formObj.addField(this);}
                this.oldOnFocus=this.element.onfocus||function(){};
                this.oldOnBlur=this.element.onblur||function(){};this.oldOnClick=this.element.onclick||function(){};
                this.oldOnChange=this.element.onchange||function(){};
                this.oldOnKeyup=this.element.onkeyup||function(){};
                this.element.onfocus=function(F){A.doOnFocus(F);
                return A.oldOnFocus.call(this,F);};
                if(!this.onlyOnSubmit){switch(this.elementType){case LiveValidation.CHECKBOX:this.element.onclick=function(F){A.validate();
                return A.oldOnClick.call(this,F);};
                case LiveValidation.SELECT:case LiveValidation.FILE:this.element.onchange=function(F){A.validate();
                return A.oldOnChange.call(this,F);};
                break;
                default:if(!this.onlyOnBlur){this.element.onkeyup=function(F){A.deferValidation();
                return A.oldOnKeyup.call(this,F);};}this.element.onblur=function(F){A.doOnBlur(F);
                return A.oldOnBlur.call(this,F);};}}},destroy:function(){if(this.formObj){
                    this.formObj.removeField(this);
                    this.formObj.destroy();}this.element.onfocus=this.oldOnFocus;if(!this.onlyOnSubmit){switch(this.elementType){case LiveValidation.CHECKBOX:this.element.onclick=this.oldOnClick;
                    case LiveValidation.SELECT:case LiveValidation.FILE:this.element.onchange=this.oldOnChange;break;
                    default:if(!this.onlyOnBlur){this.element.onkeyup=this.oldOnKeyup;}this.element.onblur=this.oldOnBlur;}}this.validations=[];
                    this.removeMessageAndFieldClass();},add:function(A,B){this.validations.push({type:A,params:B||{}});
                    return this;},remove:function(B,D){var E=false;for(var C=0,A=this.validations.length;C<A;C++){if(this.validations[C].type==B){if(this.validations[C].params==D){E=true;break;}}}
                    if(E){this.validations.splice(C,1);}return this;},deferValidation:function(B){if(this.wait>=300){this.removeMessageAndFieldClass();}
                    var A=this;if(this.timeout){clearTimeout(A.timeout);}this.timeout=setTimeout(function(){A.validate();},A.wait);},doOnBlur:function(A){this.focused=false;this.validate(A);},doOnFocus:function(A){this.focused=true;this.removeMessageAndFieldClass();},getElementType:function(){switch(true){case (this.element.nodeName.toUpperCase()=="TEXTAREA"):return LiveValidation.TEXTAREA;case (this.element.nodeName.toUpperCase()=="INPUT"&&this.element.type.toUpperCase()=="TEXT"):return LiveValidation.TEXT;case (this.element.nodeName.toUpperCase()=="INPUT"&&this.element.type.toUpperCase()=="PASSWORD"):return LiveValidation.PASSWORD;case (this.element.nodeName.toUpperCase()=="INPUT"&&this.element.type.toUpperCase()=="CHECKBOX"):return LiveValidation.CHECKBOX;case (this.element.nodeName.toUpperCase()=="INPUT"&&this.element.type.toUpperCase()=="FILE"):return LiveValidation.FILE;case (this.element.nodeName.toUpperCase()=="SELECT"):return LiveValidation.SELECT;case (this.element.nodeName.toUpperCase()=="INPUT"):throw new Error("LiveValidation::getElementType - Cannot use LiveValidation on an "+this.element.type+" input!");default:throw new Error("LiveValidation::getElementType - Element must be an input, select, or textarea!");}},doValidations:function(){this.validationFailed=false;for(var C=0,A=this.validations.length;C<A;++C){var B=this.validations[C];switch(B.type){
    case Validate.Presence:case Validate.Confirmation:case Validate.Acceptance:this.displayMessageWhenEmpty=true;this.validationFailed=!this.validateElement(B.type,B.params);break;default:this.validationFailed=!this.validateElement(B.type,B.params);break;}if(this.validationFailed){return false;}}this.message=this.validMessage;return true;},validateElement:function(A,C){var D=(this.elementType==LiveValidation.SELECT)?this.element.options[this.element.selectedIndex].value:this.element.value;if(A==Validate.Acceptance){if(this.elementType!=LiveValidation.CHECKBOX){throw new Error("LiveValidation::validateElement - Element to validate acceptance must be a checkbox!");}D=this.element.checked;}var E=true;try{A(D,C);}catch(B){if(B instanceof Validate.Error){if(D!==""||(D===""&&this.displayMessageWhenEmpty)){this.validationFailed=true;this.message=B.message;E=false;}}else{throw B;}}finally{return E;}},validate:function(){if(!this.element.disabled){var A=this.doValidations();if(A){this.onValid();return true;}else{this.onInvalid();return false;}}else{return true;}},enable:function(){this.element.disabled=false;return this;},disable:function(){this.element.disabled=true;this.removeMessageAndFieldClass();return this;},createMessageSpan:function(){var A=document.createElement("span");var B=document.createTextNode(this.message);A.appendChild(B);return A;},insertMessage:function(B){this.removeMessage();if((this.displayMessageWhenEmpty&&(this.elementType==LiveValidation.CHECKBOX||this.element.value==""))||this.element.value!=""){var A=this.validationFailed?this.invalidClass:this.validClass;B.className+=" "+this.messageClass+" "+A;if(this.insertAfterWhatNode.nextSibling){this.insertAfterWhatNode.parentNode.insertBefore(B,this.insertAfterWhatNode.nextSibling);}else{this.insertAfterWhatNode.parentNode.appendChild(B);}}},addFieldClass:function(){this.removeFieldClass();if(!this.validationFailed){if(this.displayMessageWhenEmpty||this.element.value!=""){if(this.element.className.indexOf(this.validFieldClass)==-1){this.element.className+=" "+this.validFieldClass;}}}else{if(this.element.className.indexOf(this.invalidFieldClass)==-1){this.element.className+=" "+this.invalidFieldClass;}}},removeMessage:function(){var A;var B=this.insertAfterWhatNode;while(B.nextSibling){if(B.nextSibling.nodeType===1){A=B.nextSibling;break;}B=B.nextSibling;}if(A&&A.className.indexOf(this.messageClass)!=-1){this.insertAfterWhatNode.parentNode.removeChild(A);}},removeFieldClass:function(){if(this.element.className.indexOf(this.invalidFieldClass)!=-1){this.element.className=this.element.className.split(this.invalidFieldClass).join("");}if(this.element.className.indexOf(this.validFieldClass)!=-1){this.element.className=this.element.className.split(this.validFieldClass).join(" ");}},removeMessageAndFieldClass:function(){this.removeMessage();this.removeFieldClass();}};var LiveValidationForm=function(A){this.initialize(A);};LiveValidationForm.instances={};LiveValidationForm.getInstance=function(A){var B=Math.random()*Math.random();if(!A.id){A.id="formId_"+B.toString().replace(/\./,"")+new Date().valueOf();}if(!LiveValidationForm.instances[A.id]){LiveValidationForm.instances[A.id]=new LiveValidationForm(A);}return LiveValidationForm.instances[A.id];};LiveValidationForm.prototype={initialize:function(B){this.name=B.id;this.element=B;this.fields=[];this.oldOnSubmit=this.element.onsubmit||function(){};var A=this;this.element.onsubmit=function(C){return(LiveValidation.massValidate(A.fields))?A.oldOnSubmit.call(this,C||window.event)!==false:false;};},addField:function(A){this.fields.push(A);},removeField:function(C){var D=[];for(var B=0,A=this.fields.length;B<A;B++){if(this.fields[B]!==C){D.push(this.fields[B]);}}this.fields=D;},destroy:function(A){if(this.fields.length!=0&&!A){return false;}this.element.onsubmit=this.oldOnSubmit;LiveValidationForm.instances[this.name]=null;return true;}};var Validate={Presence:function(B,C){
        var C=C||{};
        var A=C.failureMessage||"Can't be empty!";

        if(B===""||B===null||B===undefined){
            Validate.fail(A);
            }
            return true;
            },Numericality:function(J,E){
                var A=J;var J=Number(J);var E=E||{};
                var F=((E.minimum)||(E.minimum==0))?E.minimum:null;
                var C=((E.maximum)||(E.maximum==0))?E.maximum:null;
                var D=((E.is)||(E.is==0))?E.is:null;
                var G=E.notANumberMessage||"Must be a number!";
                var H=E.notAnIntegerMessage||"Must be an integer!";
                var I=E.wrongNumberMessage||"Must be "+D+"!";
                var B=E.tooLowMessage||"Must not be less than "+F+"!";
                var K=E.tooHighMessage||"Must not be more than "+C+"!";
                if(!isFinite(J)){Validate.fail(G);}
                if(E.onlyInteger&&(/\.0+$|\.$/.test(String(A))||J!=parseInt(J))){Validate.fail(H);}
switch(true){case (D!==null):if(J!=Number(D)){
    Validate.fail(I);
    }
    break;case (F!==null&&C!==null):Validate.Numericality(J,{tooLowMessage:B,minimum:F});
    Validate.Numericality(J,{tooHighMessage:K,maximum:C});
    break;case (F!==null):if(J<Number(F)){Validate.fail(B);}
    break;case (C!==null):if(J>Number(C)){Validate.fail(K);}
    break;}return true;},Format:function(C,E){var C=String(C);
    var E=E||{};
    var A=E.failureMessage||"Not valid!";
    var B=E.pattern||/./;
    var D=E.negate||false;
    if(!D&&!B.test(C)){Validate.fail(A);}
    if(D&&B.test(C)){Validate.fail(A);}
    return true;},Email:function(B,C){
    var C=C||{};
    var A=C.failureMessage||"Must be a valid email address!";
Validate.Format(B,{
                failureMessage:A,pattern:/^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i});return true;
}
,Length:function(F,G){
    var F=String(F);var G=G||{};
    var E=((G.minimum)||(G.minimum==0))?G.minimum:null;
    var H=((G.maximum)||(G.maximum==0))?G.maximum:null;var C=((G.is)||(G.is==0))?G.is:null;
    var A=G.wrongLengthMessage||"Must be "+C+" characters long!";var B=G.tooShortMessage||"Must not be less than "+E+" characters long!";var D=G.tooLongMessage||"Must not be more than "+H+" characters long!";switch(true){case (C!==null):if(F.length!=Number(C)){Validate.fail(A);}break;case (E!==null&&H!==null):Validate.Length(F,{tooShortMessage:B,minimum:E});Validate.Length(F,{tooLongMessage:D,maximum:H});break;case (E!==null):if(F.length<Number(E)){Validate.fail(B);}break;case (H!==null):if(F.length>Number(H)){Validate.fail(D);}break;default:throw new Error("Validate::Length - Length(s) to validate against must be provided!");}return true;},Inclusion:function(H,F){var F=F||{};var K=F.failureMessage||"Must be included in the list!";var G=(F.caseSensitive===false)?false:true;if(F.allowNull&&H==null){return true;}if(!F.allowNull&&H==null){Validate.fail(K);}var D=F.within||[];if(!G){var A=[];for(var C=0,B=D.length;C<B;++C){var I=D[C];if(typeof I=="string"){I=I.toLowerCase();}A.push(I);}D=A;if(typeof H=="string"){H=H.toLowerCase();}}var J=false;for(var E=0,B=D.length;E<B;++E){if(D[E]==H){J=true;}if(F.partialMatch){if(H.indexOf(D[E])!=-1){J=true;}}}if((!F.negate&&!J)||(F.negate&&J)){Validate.fail(K);}return true;},Exclusion:function(A,B){var B=B||{};B.failureMessage=B.failureMessage||"Must not be included in the list!";B.negate=true;Validate.Inclusion(A,B);return true;},Confirmation:function(C,D){if(!D.match){throw new Error("Validate::Confirmation - Error validating confirmation: Id of element to match must be provided!");}var D=D||{};var B=D.failureMessage||"Does not match!";var A=D.match.nodeName?D.match:document.getElementById(D.match);if(!A){throw new Error("Validate::Confirmation - There is no reference with name of, or element with id of '"+D.match+"'!");}if(C!=A.value){Validate.fail(B);}return true;},Acceptance:function(B,C){var C=C||{};var A=C.failureMessage||"Must be accepted!";if(!B){Validate.fail(A);}return true;},Custom:function(D,E){var E=E||{};var B=E.against||function(){return true;};var A=E.args||{};var C=E.failureMessage||"Not valid!";if(!B(D,A)){Validate.fail(C);}return true;},now:function(A,D,C){if(!A){throw new Error("Validate::now - Validation function must be provided!");}var E=true;try{A(D,C||{});}catch(B){if(B instanceof Validate.Error){E=false;}else{throw B;}}finally{return E;}},fail:function(A){throw new Validate.Error(A);},Error:function(A){this.message=A;this.name="ValidationError";}};


function getBiddingReviewDrawers() {
    doc = document.location.href.split("journal");
    $.ajax({
        url:'api.php',
        type: 'post',
        data: { 'action':'getSystemDrawerJobs'  },
        dataType: 'json',
        success: function(json) {
            fillBiddingReviewDrawers(json);
        }
    });
}

function fillBiddingReviewDrawers(json) {
    if (json == null || !json.success) {
        return;
    }
    var bidding = (json.bidding == 0 || json.bidding == null) ? 'no jobs' : (json.bidding == 1 ? '1 job' : json.bidding + ' jobs');
    var review = (json.review == 0 || json.review == null) ? 'no jobs' : (json.review == 1 ? '1 job' : json.review + ' jobs');
    
    $('#need-review ul li').remove();
    $('#need-review ul + a').remove();
    if (parseInt(review) > 0 && json.need_review) {
        var need_review = json.need_review;
        for (var i = 0; i < need_review.length; i++) {
            workitem = need_review[i];
            var li = $('<li>');
            $('<a>').addClass('worklist-item').attr(
                {
                    id: 'workitem-' + workitem.id,
                    href: 'workitem.php?job_id=' + workitem.id + '&action=view',
                })
                .append('<span>#' + workitem.id + '</span> ' + workitem.summary)
                .appendTo(li);
            $('#need-review ul').append(li);
        }
        if (parseInt(review) > 7) {
            $('<a>').attr(
                {
                    href: 'worklist.php?project=&user=0&status=needs-review',
                    target: '_blank'
                })
                .text('View them all')
                .appendTo('#need-review');
        }
        $('#need-review').show();
    }  else {
        $('#need-review').hide();
    }
    $('#biddingJobs a').text(bidding);
    $('#biddingJobs span').text(parseInt(bidding) == 1 ? 'is' : 'are');
    relativity();
}
