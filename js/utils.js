/**
 * Worklist
 * Copyright (c) 2010 High Fidelity inc.
 * All rights reserved.
 */

var Utils = {
    mustaches: [],

    /**
     * Returns true when the passed mustache has already loaded, 
     * false otherwise
     */
    mustacheLoaded: function(mustacheTemplate) {
        for(var i=0; i < Utils.mustaches.length; i++) {
            if (Utils.mustaches[i].name == mustacheTemplate) {
                return true;
            }
        }
        return false;
    },
    
    /**
     * Loads the mustache from the server and stores it locally 
     */
    loadMustache: function(mustacheTemplate, fAfter) {
        if (Utils.mustacheLoaded(mustacheTemplate)) {
            return;
        }
        var tpath = './views/mustache/' + mustacheTemplate + '.mustache';
        $.get(tpath, function(template) {
            Utils.mustaches.push({name: mustacheTemplate, template: template});
            if (typeof fAfter == 'function') {
                fAfter(template);
            }
        }, 'html');
    },
    
    /**
     * Returns the mustache template if it's loaded, 
     * otherwise will return an empty string
     */
    getMustache: function(mustacheTemplate) {
        for(var i=0; i < Utils.mustaches.length; i++) {
            if (Utils.mustaches[i].name == mustacheTemplate) {
                return Utils.mustaches[i].template;
            }
        }
        return '';
    },
    
    /**
     * Parses a mustache and calls to a specified callback 
     * onced parsed if present
     */
    parseMustache: function(mustacheTemplate, data, fAfter) {
        var parsed = '';
        if (Utils.mustacheLoaded(mustacheTemplate)) {
            parsed = Mustache.render(Utils.getMustache(mustacheTemplate), data);
            if (typeof fAfter == 'function') {
                fAfter(parsed);
            }
            return;
        }
        Utils.loadMustache(mustacheTemplate, function(template) {
            parsed = Mustache.render(template, data);
            if (typeof fAfter == 'function') {
                fAfter(parsed);
            }
        });
    },

    modal: function(name, data) {
        // generates a random id for the new modal (will use it to be removed on close)
        var id = 'modal-' + parseInt(Math.random() * (9999 - 99) + 99);
        while ($('#' + id).length) {
            var id = 'modal-' + parseInt(Math.random() * (9999 - 99) + 99);
        }
        var defaults = {
            modal_id: id,
            open: function() {},
            close: function() {}
        };
        var settings = $.extend({}, defaults, data);
        var path = 'partials/modal/' + name;
        Utils.parseMustache(path, settings, function(parsed) {
            $(parsed).appendTo('body');
            $('#' + id).on('shown.bs.modal', function() {
                $(this).attr('name', name);
                if (typeof settings.open == 'function') {
                    settings.open(this);
                }
            });            
            $('#' + id).on('hidden.bs.modal', function() {
                if (typeof settings.close == 'function') {
                    settings.close(this);
                }
                $(id).remove();
            });
            $('#' + id).modal('show');
        });
    },

    modalRefresh: function(modal, data) {
        var id = $(modal).attr('id');
        var name = $(modal).attr('name');

        var defaults = {
            modal_id: id,
            open: function() {},
            close: function() {}
        };
        var settings = $.extend({}, defaults, data);
        var path = 'partials/modal/' + name;
        Utils.parseMustache(path, settings, function(parsed) {
            $(modal).html($(parsed).html());
            if (typeof settings.success == 'function') {
                settings.success(modal);
            }
        });
    },

    emptyModal: function(data) {
        var defaults = {
            title: '',
            buttons: [],
            open: function() {},
            close: function() {}
        };
        var settings = $.extend({}, defaults, data);
        // if no buttons arep provided, let's use an 'Ok' one by default
        if (settings.buttons.length == 0) {
            settings.buttons = [{
                type: 'button',
                content: 'Ok',
                className: 'btn-primary',
                dismiss: true
            }];
        }
        Utils.modal('empty', settings);
    },

    emptyFormModal: function(data) {
        var defaults = {
            title: '',
            buttons: [],
            open: function() {},
            close: function() {}
        };
        var settings = $.extend({}, defaults, data);
        // if no buttons arep provided, let's use an 'Ok' one by default
        if (settings.buttons.length == 0) {
            settings.buttons = [{
                type: 'button',
                content: 'Ok',
                className: 'btn-primary',
                dismiss: true
            }];
        }
        Utils.modal('empty-form', settings);
    },


    /**
     * Calculates the relative time
     */
    relativeTime: function(time, withIn, justNow, withAgo, specific) {
        var secs = Math.abs(time);
        var mins = 60;
        var hour = mins * 60;
        var day = hour * 24;
        var week = day * 7;
        var month = day * 30;
        var year = day * 365;
        segments = {}
        segments.yr = parseInt(secs / year);
        secs %= year;
        segments.mnth = parseInt(secs / month);
        secs %= month;
        if (!segments.yr) {
            segments.day = parseInt(secs / day);
            secs %= day;
            if (!segments.mnth) {
                segments.hr = parseInt(secs / hour);
                secs %= hour;
                if (!segments.day) {
                    segments.min = parseInt(secs / mins);
                    secs %= mins;
                    if (!segments.hr && !segments.min) {
                        segments.sec = secs;
                    }
                }
            }
        }
        var relTime = '';
        specific = typeof(specific) == 'undefined' ? true : specific;
        for (unit in segments) {
            var cnt = segments[unit];
            if (cnt) {
                if (relTime.length) {
                    relTime += ', ';
                }
                relTime += cnt + ' ' + unit;
                if (cnt > 1) {
                    relTime += 's';
                }
                if (!specific) {
                    break;
                }
            }
        }
        if (relTime) {
            withAgo = typeof(withAgo) == 'undefined' ? true : withAgo;
            withIn = typeof(withIn) == 'undefined' ? true : withIn;
            return (time < 0) ? (withAgo ? '' : '-') + (relTime + (withAgo ? ' ago' : '')) : (withIn ? 'in ' + relTime : relTime);
        } else {
            justNow = typeof(justNow) == 'undefined' ? true : justNow;
            return justNow ? 'just now' : '';
        }
    },

    queryString: function() {
        var query_string = {};
        var query = window.location.search.substring(1);
        var vars = query.split('&');
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split('=');
            if (typeof query_string[pair[0]] === 'undefined') { // If first entry with this name
                query_string[pair[0]] = pair[1];
            } else if (typeof query_string[pair[0]] === 'string') { // If second entry with this name
                var arr = [ query_string[pair[0]], pair[1] ];
                query_string[pair[0]] = arr;
            } else { // If third or later entry with this name
               query_string[pair[0]].push(pair[1]);
            }
        }
        return query_string;
     },

    /**
     * Guess relative coordinates of an input (textarea, input[type="text"])
     * cursor at certain position of a given input, used from the mentions
     * autocomplete feature in order to show the dropdown as close to last typed
     * or current char -based on the cursor position- as possible (kind of a
     * customized typeahead dropdown list).
     *
     * The way it works is pretty tricky but also interesting, it creates a mirror
     * <div> (could be any block element) that mimics the textarea according to
     * many of its css rules (the ones that affects text positionig) and copies
     * its content too. Then, will get the offset between the mirror and the virtual
     * cursor and return its coordinates.
     *
     * Most of this method is 3rd party code, copied and adapted for WL by @kordero.
     * Credits: https://github.com/component/textarea-caret-position
     */
    getCursorCoordinates: function (element, position) {
        // The properties that we copy into a mirrored div.
        // Note that some browsers, such as Firefox,
        // do not concatenate properties, i.e. padding-top, bottom etc. -> padding,
        // so we have to do every single property specifically.
        var properties = [

            'boxSizing',
            'width',  // on Chrome and IE, exclude the scrollbar, so the mirror div wraps exactly as the textarea does
            'height',
            'overflowX', 'overflowY',  // copy the scrollbar for IE

            'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth',

            'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',

            // https://developer.mozilla.org/en-US/docs/Web/CSS/font
            'fontStyle', 'fontVariant', 'fontWeight', 'fontStretch', 'fontSize', 'lineHeight', 'fontFamily',

            'textAlign', 'textTransform', 'textIndent',
            'textDecoration',  // might not make a difference, but better be safe

            'letterSpacing', 'wordSpacing'
        ];

        var isFirefox = !(window.mozInnerScreenX == null);
        var mirrorDiv = $('.autocompleteMirrorDiv', $(element).parent())[0];
        if (!mirrorDiv) {
            var mirrorDiv = $('<div>').addClass('autocompleteMirrorDiv').insertAfter(element)[0];
        }

        var style = mirrorDiv.style;
        var computed = getComputedStyle(element);

        // default textarea styles
        style.whiteSpace = 'pre-wrap';
        if (element.nodeName !== 'INPUT') {
            style.wordWrap = 'break-word';  // only for textarea-s
        }

        // position off-screen
        style.position = 'absolute';  // required to return coordinates properly
        style.top = element.offsetTop + parseInt(computed.borderTopWidth) + 'px';
        style.visibility = 'hidden';  // not 'display: none' because we want rendering

        // transfer the element's properties to the div
        properties.forEach(function (prop) {
            style[prop] = computed[prop];
        });

        if (isFirefox) {
            //style.width = parseInt(computed.width) - 2 + 'px'  // Firefox adds 2 pixels to the padding - https://bugzilla.mozilla.org/show_bug.cgi?id=753662
            // Firefox lies about the overflow property for textareas: https://bugzilla.mozilla.org/show_bug.cgi?id=984275
            if (element.scrollHeight > parseInt(computed.height)) {
                style.overflowY = 'scroll';
            }
        } else {
            style.overflow = 'hidden';  // for Chrome to not render a scrollbar; IE keeps overflowY = 'scroll'
        }

        mirrorDiv.textContent = element.value.substring(0, position);
        // the second special handling for input type="text" vs textarea: spaces need to be replaced with non-breaking spaces - http://stackoverflow.com/a/13402035/1269037
        if (element.nodeName === 'INPUT') {
            mirrorDiv.textContent = mirrorDiv.textContent.replace(/\s/g, "\u00a0");
        }

        var span = document.createElement('span');
        // Wrapping must be replicated *exactly*, including when a long word gets
        // onto the next line, with whitespace at the end of the line before (#7).
        // The  *only* reliable way to do that is to copy the *entire* rest of the
        // textarea's content into the <span> created at the caret position.
        // for inputs, just '.' would be enough, but why bother?
        span.textContent = element.value.substring(position) || '.';  // || because a completely empty faux span doesn't render at all
        span.style.opacity = '1';
        mirrorDiv.appendChild(span);
        return {
            top: span.offsetTop + parseInt(computed['borderTopWidth']),
            left: span.offsetLeft + parseInt(computed['borderLeftWidth'])
        };
    }
};