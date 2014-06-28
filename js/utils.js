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
    }
};

