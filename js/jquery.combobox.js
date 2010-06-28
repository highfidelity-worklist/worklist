/*!
 * jQuery ComboBox Plugin v1.0
 * http://thomas.stachl.me/
 *
 * Copyright 2010, Thomas Stachl
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * @author Thomas Stachl <thomas@stachl.me>
 * @description replaces the combobox with a styleable equivalent
 * @requires jQuery 1.4.2, jQuery UI 1.8.1, Class 0.0.2, jQuery.ScrollTo 1.4.2
 *
 * Date: Fri May 21 04:11:00 2010 +0100
 */
;(function($) {
	var ComboBox = Class.create({
		container: null,
		textbox: null,
		trigger: null,
		list: null,
		el: null,
		values: null,
		showList: false,
		
		init: function(o) {
			// combobox original select box
			this.el = $(o);
			// combobox container
			this.container = $('<div/>');
			// combobox textbox, will become an autocompletebox in a later version
			this.textbox = $('<input type="text"/>');
			// combobox trigger (icon)
			this.trigger = $('<span/>');
			// combobox list
			this.list = $('<ul/>');
			// combobox values array
			this.values = [];
			// fire event initialized
			this.el.trigger('init', this);
			
			// bind click events
			this.container.click($.proxy(this.click, this));
			$(document).click($.proxy(this._outClick, this));
			// bind key events
			$(document).keydown($.proxy(this.keydown, this));
			
			// setup the combobox
			this.el.trigger('beforesetup', this);
			this._setup();
			this.el.trigger('aftersetup', this);
			// get the initial selected value
			this.el.trigger('beforeinitselect', this);
			this._initSelected();
			this.el.trigger('afterinitselect', this);
			// show the combobox
			this.el.trigger('beforeshow', this);
			this.show();
			this.el.trigger('aftershow', this);
		},
		_initSelected: function() {
			// get initial value of original selectbox
			var el = this.getItemByValue(this.el.val());
			// write the text in the textbox
			this.textbox.val(el.text);
		},
		_addClasses: function() {
			// add classes for the container and set it to display no
			this.container.addClass('ui-state-default ui-corner-all ui-combobox').css({
				display: 'none'
			});
			// add classes for the textbox and calculate the with by the original element
			this.textbox.addClass('ui-state-default ui-combobox-textbox').css({
				width: this.el.innerWidth()
			});
			// add classes for the trigger, here we can change the icon
			this.trigger.addClass('ui-state-default ui-icon ui-icon-carat-1-s');
			// add classes for the list and calculate the width
			this.list.addClass('ui-combobox-list ui-corner-bottom').css({
				width: (this.el.innerWidth() + 26)
			});
		},
		_setup: function() {
			// hide the original element (don't remove it or the handlers will be killed)
			this.el.hide();
			// add all classes
			this._addClasses();
			// get the original list values
			this._getListValues();
			// setup the textbox
			this._setupTextbox();
			// generate hover listeners and functions
			this._setupHover();
			// setup the new list
			this._setupList();
			// append all elements to the container
			this.container.append(this.textbox).append(this.trigger).append(this.list);
			// add the combobox to the dom
			this.el.after(this.container);
		},
		_setupTextbox: function() {
			// as we don't provide autocomplete at the moment simply deactivate the textbox
			this.textbox.attr('readonly', true);
			this.textbox.focus(function() {
				$(this).blur();
			});
		},
		_setupHover: function() {
			// add hover states
			this.container.hover(
				$.proxy(function() {
					// the list is shown we don't need the hover effect
					if (!this.showList) {
						this.container.addClass('ui-state-hover');
						this.textbox.addClass('ui-state-hover');
						this.trigger.addClass('ui-state-hover');
					}
				}, this),
				$.proxy(function() {
					this.container.removeClass('ui-state-hover');
					this.textbox.removeClass('ui-state-hover');
					this.trigger.removeClass('ui-state-hover');
				}, this)
			);
		},
		_setupList: function() {
			// create the listitems for the new list
			$.each(this.values, $.proxy(function(i, v) {
				var listItem = $('<li />');
				listItem.text(v.text);
				listItem.attr('val', v.value);
				if (v.selected) {
					listItem.addClass('ui-combobox-list-selected');	
				}
				listItem.hover(
					function() {
						$('.ui-state-hover').removeClass('ui-state-hover');
						$(this).addClass('ui-state-hover');
					}
				);
				listItem.click($.proxy(this._listItemClicked, this));
				this.list.append(listItem);
			}, this));
			this.list.hide();
		},
		_getListValues: function() {
			// get the list values of the original element
			$.each(this.el.children(), $.proxy(function(i, v) {
				this.values.push({
					value: $(v).attr('value'),
					text: $(v).text(),
					selected: $(v).attr('selected')
				});
			}, this));
		},
		_listItemClicked: function(e) {
			// list item was clicked now we select the value
			e.preventDefault();
			this.select($(e.currentTarget).attr('val'));
		},
		_outClick: function() {
			// if we click outside the list while open we want it to close
			if (this.list.is(":visible")) {
				this._hideList();
			}
		},
		_hideList: function() {
			this.list.hide();
			this.container.removeClass('ui-state-active');
			this.container.removeClass('ui-state-hover');
			this.showList = false;
		},
		setupNewList: function(l) {
			// before we can setup a new list we have to hide the current one
			this._hideList();
			// load the new list into values
			this.values = l;
			// empty current list
			this.list.empty();
			// setup new list
			this._setupList();
			// trigger an event
			this.el.triggerHandler('newlist', this);
		},
		getItemByValue: function(v) {
			// get an item form this.values by value
			var el = $.grep(this.values, function(n, i) {
				return (n.value == v);
			});
			// we return only one (first) element
			return el[0];
		},
		keydown: function(e) {
			if (((e.keyCode == 40) || (e.keyCode == 38) || (e.keyCode == 13) || (e.keyCode == 9) || (e.keyCode == 27)) && (this.showList == true)) {
				e.preventDefault();
				if ((e.keyCode == 40) && (this.list.find('.ui-state-hover').next().length > 0)) {
					this.list.find('.ui-state-hover').removeClass('ui-state-hover').next().addClass('ui-state-hover');
				} else if ((e.keyCode == 38) && (this.list.find('.ui-state-hover').prev().length > 0)) {
					this.list.find('.ui-state-hover').removeClass('ui-state-hover').prev().addClass('ui-state-hover');
				} else if ((e.keyCode == 13) || (e.keyCode == 9)) {
					this.list.find('.ui-state-hover').click();
				} else if (e.keyCode == 27) {
					this._hideList();
				}
				this.list.scrollTo(this.list.find('.ui-state-hover'));
			}
		},
		click: function(e) {
			e.preventDefault();
			if (this.list.is(":visible")) {
				// if the list is visible we hide it, set showList to false and remove the active state from the container
				this._hideList();
			} else {
				// if the list is not visible we have to show it
				// get the current position of the container
				var pos = this.container.position();
				// set the list to the correct position
				this.list.css({
					left: pos.left
				});
				this.showList = true;
				// show the list and scroll to the selected item
				this.list.show();
				this.list.scrollTo(this.list.find('.ui-combobox-list-selected'));
				this.list.find('.ui-combobox-list-selected').addClass('ui-state-hover');
				// add the active state class to the container
				this.container.addClass('ui-state-active');
			}
			return false;
		},
		select: function(val) {
			// get the complete element (text, value, selected state)
			val = this.getItemByValue(val);
			// if the value is not currently selected we have to select it
			if (!val.selected) {
				// find the element in our own list and select it, deselect the old value
				$.each(this.values, function(i, v){
					if (v.selected == true) {
						v.selected = false;
					}
					if (v.value == val.value) {
						v.selected = true;
					}
				});
				// remove the selected class from the previous selected item
				this.list.find('.ui-combobox-list-selected').removeClass('ui-combobox-list-selected');
				// and add it to the currently selected
				this.list.find('li[val=' + val.value + ']').addClass('ui-combobox-list-selected');
				// update the textbox with the correct text
				this.textbox.val(val.text);
				// change the value of the original element
				this.el.val(val.value);
				// fire the change event of the original element
				this.el.change();
			}
		},
		show: function() {
			// show the new combobox
			this.container.css('display', 'inline');
		}
	});
	
	$.fn.comboBox = function(settings) {
		return this.each(function() {
			new ComboBox(this);
		});
	}
})(jQuery);
