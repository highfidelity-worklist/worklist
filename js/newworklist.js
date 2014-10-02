var NewWorklist = {
    init: function() {

        if($( window ).width() <= '450'){
            $(".dropdown-navmenu").on('hover, touchstart', function(){
                $('body').css('overflow', 'hidden');
            });
            $("body > section").hover(function(){
                $('body').css('overflow', 'auto');
            });
        }

        $(document).ajaxSend(function(event, request, settings) {
            if (settings.url == './status' && settings.type.toUpperCase() == 'POST') {
                return;
            }
            $('body').addClass('onAjax');
        });    
        $(document).ajaxComplete(function() {
            $('body').removeClass('onAjax');
        });

        $('a[href^="./github/login"]').click(NewWorklist.loginClick);

        /**
         * Initialize js objects used by worklist
         * previously wrapped on jQuery.load functions
         */
        Budget.init();
        UserStats.init();
        NewWorklist.initJobSearch();
        NewWorklist.initAutocomplete();
    },

    loginClick: function(event) {
        event.preventDefault();

        var href = $(this).attr('href');

        var doNotShowGithubNote = false;

        // Try to get localStorage value, but if it's not available in this browser, use the default value of `false`
        try {
            doNotShowGithubNote = localStorage.getItem('doNotShowGithubNote');
        } catch(e) {
        }

        if (doNotShowGithubNote) {
            window.location = href;
        } else {
            var message = "<strong>We do require access to private repositories</strong>, "
                + "but only those that @highfidelity manages. We will not read or write to your "
                + "private repositories that weren't forked from @highfidelity."
                + "<br><br><label><input type='checkbox' name='doNotShow'> Do not show this message again</input></label>";
            Utils.emptyModal({
                title: "GitHub Authentication",
                content: message,
                buttons: [{
                    type: 'button',
                    content: 'Log in with GitHub',
                    className: 'btn-primary',
                    dismiss: true
                }],
                close: function(el) {
                    var selected = $(el).find('input[name="doNotShow"]')[0].checked;
                    if (selected) {
                        try {
                            localStorage.setItem('doNotShowGithubNote', 'true');
                        } catch(e) {
                        }
                    }
                    window.location = href;
                },
            });
        }
    },

    initJobSearch: function() {
        $('#search-query input[type="text"]').keypress(function(event) {
            if ($.trim($(this).val()).length > 0 && event.keyCode == '13') {
                if(typeof jobs == 'undefined') {
                    window.location = "./jobs?query=" + $(this).val();
                }
            }
        });
        $("#query-search-button").click(function() {
            if($.trim($('#search-query input[type="text"]').val()).length > 0 && typeof jobs == 'undefined') {
                    window.location = "./jobs?query=" + $('#search-query input[type="text"]').val();
            }
        });
    },

    autocompleteInput: undefined,
    initAutocomplete: function() {
        NewWorklist.autocompleteInput = $('.autocomplete > textarea')[0];
        NewWorklist.suggestionsEngine = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('nickname'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: './user/suggestMentions/%QUERY'
            }
        });
        NewWorklist.suggestionsEngine.initialize();
        if (!NewWorklist.autocompleteSuggestionList) {
            NewWorklist.autocompleteSuggestionList = $('<ul>').insertAfter(NewWorklist.autocompleteInput)[0];
        }
        $(NewWorklist.autocompleteInput).on('keydown keypress cut paste', NewWorklist.autocomplete);
    },

    typingStr: '',

    /**
     * Autocomplete manager, handles most of the input events related to text changes
     */
    autocomplete: function(e) {
        // if the dropdown is visible, gotta check pressed keys
        if ($(NewWorklist.autocompleteSuggestionList).is(':visible')) {
            if (!NewWorklist.checkAutocompleteKeys(e)) {
                return false;
            }
        }

        // at any case, ESC key won't fire a new thread at all
        if ((e.type == 'keydown' || e.type == 'keypress') && e.keyCode == 27) {
            return false;
        }

        // let's run the rest of the code as an asynchronous thread
        // in order to return the input control/flow to the UI
        setTimeout(function() {
            var input = NewWorklist.autocompleteInput;
            if (input.selectionStart != input.selectionEnd) {
                return; // let's ignore autocompleter when the user is selecting text
            }
            var mentionDetected = NewWorklist.mentionDetector();
            if (mentionDetected) {
                NewWorklist.typingStr = mentionDetected[1];
                NewWorklist.suggestionsEngine.get(NewWorklist.typingStr.replace(/^@/, ''), NewWorklist.updateSuggestionsList);
            } else {
                NewWorklist.hideSuggestionList();
            }
        }, 3);
    },

    /**
     * Manages special keys being typed when the autocomplete dropdown
     * is being shown, such as up/down arrows, enter/tab and non word
     * chars.
     *
     * @return bool whether to cancel the input or not
     */
    checkAutocompleteKeys: function(e) {
        // let's not do this when no suggestions items exists in the list
        if ($('li', NewWorklist.autocompleteSuggestionList).length == 0) {
            return true;
        }

        var key = e.keyCode;
        var keyPressed = e.type == 'keydown' || e.type == 'keypress';

        // esc key will inmediatly close the suggestions dropdown
        if (keyPressed && key == 27) {
            NewWorklist.hideSuggestionList();
            return false;
        }

        // determines whether it's an up/down arrow key event
        var arrowPressed = keyPressed && (key == 38 || key == 40);

        // tab key works the same as enter
        var enterPressed = keyPressed && (key == 13 || key == 9);

        // stores the typed char as string
        var charTyped = String.fromCharCode(e.charCode);

        if (arrowPressed) {
            // up/down pressed, let's do the maths inc/decrement and toggle active items
            var activeItem = NewWorklist.activeSuggestionItem += (key == 38 ? -1 : 1);
            var itemsCount = $('li', NewWorklist.autocompleteSuggestionList).length;
            if (activeItem < 0) {
                activeItem = NewWorklist.activeSuggestionItem = itemsCount -1;
            } else if (activeItem >= itemsCount) {
                activeItem = NewWorklist.activeSuggestionItem = 0;
            }
            $('li.active', NewWorklist.autocompleteSuggestionList).removeClass('active');
            $('li:eq(' + activeItem + ')', NewWorklist.autocompleteSuggestionList).addClass('active');
            return false;
        } else if (enterPressed) {
            // enter/tab pressed, will choose the active item
            var activeItem = NewWorklist.activeSuggestionItem;
            NewWorklist.chooseAutocompleteItem(activeItem);
            NewWorklist.hideSuggestionList();
            return false;
        } else if (e.type == 'keypress' && e.charCode >= 32 && e.charCode <= 128 && !charTyped.match(/^\w$/)) {
            // non-word char typed, choose active item and append the typed char
            var activeItem = NewWorklist.activeSuggestionItem;
            NewWorklist.chooseAutocompleteItem(activeItem, charTyped);
            NewWorklist.hideSuggestionList();
            return false;
        }
        return true;
    },

    /**
     * Handler caled on nicknames suggestions/autocomplete list
     * item selection (whether mouse clicks and/or by keys)
     */
    chooseAutocompleteItem: function(item, appendChar) {
        var nickname = $('li:eq(' + item + ')', NewWorklist.autocompleteSuggestionList).attr('nickname');
        var input = NewWorklist.autocompleteInput;
        var mention = NewWorklist.mentionDetector()[1].replace(/^@/, '');
        var textBefore = input.value.substring(0, input.selectionEnd).replace(/@\w+$/, '@');
        var textAfter = input.value.substring(input.selectionEnd, input.value.length);
        appendChar = typeof appendChar == 'string' ? appendChar : ' ';
        input.value = textBefore + nickname + appendChar + textAfter;
        input.selectionStart = input.selectionEnd = (textBefore + nickname + ' ').length;
    },

    activeSuggestionItem: 0,

    /**
     * Callback to Bloodhoud#get. Recreates the nicknames autocomplete/suggestions
     * dropdown items according to query results made to the Bloodhound engine
     */
    updateSuggestionsList: function(suggestions) {
        NewWorklist.activeSuggestionItem = 0;
        var html = '';
        for(var i = 0; i < suggestions.length; i++) {
            var suggestion = suggestions[i];
            var nickname = suggestion.nickname;
            var realName = suggestion.first_name && suggestion.first_name != null ? suggestion.first_name : '';
            if (realName && suggestion.last_name && suggestion.last_name != null) {
                realName += ' ' + suggestion.last_name;
            }
            var active = (i == NewWorklist.activeSuggestionItem);
            var typingStr = NewWorklist.typingStr.replace(/^@/, '');
            var replacementRegex = new RegExp('(' + typingStr + ')' , 'gi');
            var nicknameHtml = nickname.replace(replacementRegex, '<b>$1</b>');
            var realNameHtml = realName.replace(replacementRegex, '<b>$1</b>');
            var li_html =
                '<li' + (active ? ' class="active"' : '') + ' nickname="' + nickname + '">' +
                  '<img src="./user/avatar/'+ nickname + '/24x24" title="' + nickname + '" /> ' +
                  nicknameHtml + ' <i>' + realNameHtml + '</i>'
                '</li>';
            html += li_html;
        }
        if (html) {
            $(NewWorklist.autocompleteSuggestionList)[0].innerHTML = html;
            NewWorklist.showAutocomplete(NewWorklist.typingStr.length);
            $('li', NewWorklist.autocompleteSuggestionList).on({
                hover: function() {
                    var index = $(this).prevAll().length;
                    $('li.active', NewWorklist.autocompleteSuggestionList).removeClass('active');
                    $('li:eq(' + index + ')', NewWorklist.autocompleteSuggestionList).addClass('active');
                },
                click: function() {
                    var index = $(this).prevAll().length;
                    NewWorklist.chooseAutocompleteItem(index);
                    NewWorklist.hideSuggestionList();
                    $(NewWorklist.autocompleteInput).focus();
                }
            });
        } else {
            NewWorklist.hideSuggestionList();
        }
    },

    /**
     * shows the nicknames autocomplete/suggestions dropdown
     * at current cursor coordinates
     */
    showAutocomplete: function(typingStrLength) {
        var input = NewWorklist.autocompleteInput;
        var position = Utils.getCursorCoordinates(input, input.selectionEnd -typingStrLength);
        var positionEnd = Utils.getCursorCoordinates(input, input.selectionEnd);
        var listElement = NewWorklist.autocompleteSuggestionList;
        $(listElement).css({
            display: 'block',
            top: (position.top + 20) + 'px',
            left: position.left + 'px',
            width: (positionEnd.left - position.left) + 'px'
        })
    },

    /**
     * removes child items and hides the nicknames
     * autocomplete/suggestions dropdown
     */
    hideSuggestionList: function() {
        $('li', NewWorklist.autocompleteSuggestionList).remove();
        var listElement = NewWorklist.autocompleteSuggestionList;
        $(listElement).css({
            display: 'none'
        });
    },

    /**
     * Detects whether the current cursor is at a probable
     * mention, used to guess whether the user is mentionig
     */
    mentionDetector: function() {
        var input = NewWorklist.autocompleteInput;
        var text = input.value.substring(0, input.selectionEnd);
        return text.match(/(?:^|\s)(@\w+)$/);
    },
}
