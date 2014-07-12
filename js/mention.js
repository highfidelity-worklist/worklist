/*jslint forin: true */

;(function($) {
    $.fn.extend({
        mention: function(options) {
            this.opts = {
                users: [],
                delimiter: '@',
                sensitive: true,
                emptyQuery: false,
                queryBy: ['name', 'username'],
                ajax: false, 
                ajaxUrl: '',
                ajaxParam: 'contains',
                typeaheadOpts: {}
            };

            var settings = $.extend({}, this.opts, options),
                _checkDependencies = function() {
                    if (typeof $ == 'undefined') {
                        throw new Error("jQuery is Required");
                    }
                    else {
                        if (typeof $.fn.typeahead == 'undefined') {
                            throw new Error("Typeahead is Required");
                        }
                    }
                    return true;
                },
                _extractCurrentQuery = function(query, caratPos) {
                    var i;
                    for (i = caratPos; i >= 0; i--) {
                        if (query[i] == settings.delimiter) {
                            break;
                        }
                    }
                    //alert(query.substring(i, caratPos));
                    return query.substring(i, caratPos);
                },
                _updateSource = function(query, caratPos, items) {
                    var i;
                    foundDelim = false;

                    //CHECK THE INPUTTED TEXT AND FIND DELIMINATOR
                    for (i = caratPos; i >= 0; i--) {
                        if (query[i] == settings.delimiter) {
                            foundDelim = true;
                            break;
                        }
                    }

                    //CHECK IF WE'RE QUERYING A MENTION
                    usernames = (query.toLowerCase()).match(new RegExp(settings.delimiter + '\\w+', "g"));

                    if ( !! usernames) {
                         if(foundDelim){
                            var qUser = query.substring(i, caratPos).substring(1);

                            var hasSpace = /\s/g.test(qUser);

                            //DON'T BOTHER TO QUERY A USERNAME WITH A SPACE
                            if( !hasSpace && qUser != ''){
                                $.ajaxSetup({ async: false });

                                var s;
                                $.getJSON(settings.ajaxUrl + '&' + settings.ajaxParam + '=' + escape(qUser), {}, function(data) {
                                        s = data;
                                });

                                this.source = s;
                                items = this.source;

                                $.ajaxSetup({ async: true });

                                return s;
                            }

                        }
                    }
                    return items;

                },
                _matcher = function(itemProps) {
                    var i;
                    if(settings.emptyQuery){
	                    var q = (this.query.toLowerCase()),
	                    	caratPos = this.$element[0].selectionStart,
	                    	lastChar = q.slice(caratPos-1,caratPos);
	                    if(lastChar==settings.delimiter){
                            return true;
	                    }
                    }

                    for (i in settings.queryBy) {
                        if (itemProps[settings.queryBy[i]]) {
                            var item = itemProps[settings.queryBy[i]].toLowerCase(),
                                usernames = (this.query.toLowerCase()).match(new RegExp(settings.delimiter + '\\w+', "g")),
                                j;
                            if ( !! usernames) {
                                 for (j = 0; j < usernames.length; j++) {
                                    var username = (usernames[j].substring(1)).toLowerCase(),
                                        re = new RegExp(settings.delimiter + item, "g"),
                                        used = ((this.query.toLowerCase()).match(re));

                                    if (item.indexOf(username) != -1 && used === null) {
                                        return true;
                                    }
                                }
                            }
                        }
                    }
                },
                _updater = function(item) {
                    var data = this.query,
                        caratPos = this.$element[0].selectionStart,
                        i;

                    for (i = caratPos; i >= 0; i--) {
                        if (data[i] == settings.delimiter) {
                            break;
                        }
                    }
                    var replace = data.substring(i, caratPos),
                    	textBefore = data.substring(0, i),
                    	textAfter = data.substring(caratPos),
                    	data = textBefore + settings.delimiter + item + textAfter;

                    this.tempQuery = data;

                    return data;
                },
               _sorter = function(items) {

                    if(settings.ajax && settings.ajaxUrl.length){
                        items = _updateSource(this.query, this.$element[0].selectionStart, items);
                    }

                    if (items.length && settings.sensitive) {
                        var currentUser = _extractCurrentQuery(this.query, this.$element[0].selectionStart).substring(1),
                            i, len = items.length,
                            priorities = {
                                highest: [],
                                high: [],
                                med: [],
                                low: []
                            }, finals = [];
                        if (currentUser.length == 1) {
                            for (i = 0; i < len; i++) {
                                var currentRes = items[i];

                                if ((currentRes.username[0] == currentUser)) {
                                    priorities.highest.push(currentRes);
                                }
                                else if ((currentRes.username[0].toLowerCase() == currentUser.toLowerCase())) {
                                    priorities.high.push(currentRes);
                                }
                                else if (currentRes.username.indexOf(currentUser) != -1) {
                                    priorities.med.push(currentRes);
                                }
                                else {
                                    priorities.low.push(currentRes);
                                }
                            }
                            for (i in priorities) {
                                var j;
                                for (j in priorities[i]) {
                                    finals.push(priorities[i][j]);
                                }
                            }
                            return finals;
                        }
                    }

                    return items;
                },
                _render = function(items) {
                    var that = this;

                    items = $(items).map(function(i, item) {

                        i = $(that.options.item).attr('data-value', item.username);

                        var _linkHtml = $('<div />');

                        if (item.image) {
                            _linkHtml.append('<img class="mention_image" src="' + item.image + '">');
                        }
                        if (item.name) {
                            _linkHtml.append('<b class="mention_name">' + item.name + '</b> ');
                        }
                        if (item.username) {
                            _linkHtml.append('<span class="mention_username">' + settings.delimiter + item.username + '</span>');
                        }

                        i.find('a').html(that.highlighter(_linkHtml.html()));
                        return i[0];
                    });

                    items.first().addClass('active');
                    this.$menu.html(items);
                    return this;
                };

            $.fn.typeahead.Constructor.prototype.render = _render;

            return this.each(function() {
                var _this = $(this);
                if (_checkDependencies()) {
                    _this.typeahead($.extend({
                        source: settings.users,
                        matcher: _matcher,
                        updater: _updater,
                        sorter: _sorter
                    }, settings.typeaheadOpts));
                }
            });
        }
    });
})(jQuery);
