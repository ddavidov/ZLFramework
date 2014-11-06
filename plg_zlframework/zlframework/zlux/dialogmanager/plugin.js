/* ===================================================
 * ZLUX dialog
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(options){
		this.options = $.extend({}, this.options, options);
		this.events = {};
	};
	$.extend(Plugin.prototype, $.zlux.Main.prototype, {
		name: 'dialog',
		options: {
			width: '300',
			height: '150',
			title: 'Dialog',
			scrollbar: true
			// Any jQuery UI Widget Dialog option declared here will be passed on the Widget init
		},
		initDialog: function() {
			var $this = this;

			// set dialog class
			$this.options.dialogClass = 'zlux-dialog ' + $this.options.dialogClass;

			// init dialog
			$this.main = $('<div />').appendTo($('body'))
			.dialog($.extend({
				autoOpen: 1, // auto open on first init
				resizable: !1,
				height: 'auto'
			}, $this.options));

			// hide main so it's content is not accesible during initial loading
			$this.main.hide();

			// set the dialog content wrapper
			$this.content = $('<div class="zlux-dialog-content" />').appendTo($this.main);

			// save a reference to the widget, the wrapper of the entire dialog
			$this.widget = $this.main.dialog('widget');

			// on dialog open
			$this.main.on("dialogopen", function(){
				// once dragged keep the position
				if (!$this.widget.data('dialog-dragged')) {
					$this.widget.position($.extend({
						my: 'center',
						at: 'center',
						of: window
					}, $this.options.position));
				}
			});

			// on drag start
			$this.main.on("dialogdragstart", function(){
				$this.widget.data('dialog-dragged', 1);
			});
			
			// set the spinner
			$this._spinner = $('<i class="zlux-loader-circle-big icon-spinner icon-spin" />').appendTo($this.widget);
			$this._spinner_area = $this.main;

			
			// init scrollbar
			var loadingScrollbar = $.Deferred().resolve(); // by default is resolved as could be not necesary to load it
			if ($this.options.scrollbar) {
				loadingScrollbar = $.zlux.assets.load($.zlux.url.zlfw('assets/js/jquery.plugins/nanoscroller/nanoscroller.min.js'), function(){
					$this.main.addClass('zlux-scroller').nanoScroller({
						preventPageScrolling: true,
						contentClass: 'zlux-dialog-content'
					});
				});
			}

			// change the close icon
			$('.ui-dialog-titlebar-close', $this.widget).html('<i class="icon-remove" />');

			
			// when assets loaded
			loadingScrollbar.done(function(){

				// set init state
				$this.inited = true;

				// resolve
				$this.trigger("InitComplete");
			});
		},
		/**
		 * Called from the outside when the content is ready to be shown
		 */
		initContent: function() {
			var $this = this;

			// show the main content
			$this.main.show();
			
			// set the dom status
			$this.widget.attr('data-zlux-status', 'ready');

			// init dialog scrollbar
			if ($this.options.scrollbar) $this.scrollbar('refresh');

			// set init state
			$this.contentinited = true;

			// hide the spinner, must go after the state
			$this.spinner('hide');
		},
		toggle: function() {
			var $this = this;

			// if not yet inited, start it
			if (!$this.inited) $this.initDialog();

			// if allready inited, toggle
			else $this.dialog($this.dialog("isOpen") ? "close" : "open");
		},
		dialog: function(method) {
			return this.main.dialog(method);
		},
		scrollbar: function(action) {
			var $this = this;

			if (action === 'refresh') {
				var $scroll_y = $('.pane .slider', $this.main);

				$this.scrollbar('show'); // let's be sure is visible
				$this.main.nanoScroller(); // refresh

				// if no scroll necesary, hide it
				var margin = Math.round($this.main.height()) - Math.round($scroll_y.height());
				if (margin >= 0 && margin <= 5) {
					$this.scrollbar('hide');
				}

			} else if (action === 'hide') {
				$this.main.nanoScroller({ stop: true });
				// $this.main.addClass('noscroll'); // is necesary ?
			} else if (action === 'show') {
				$this.main.nanoScroller({ stop: false }); 
				// $this.main.removeClass('noscroll'); // is necesary ?
			} else {
				$this.main.nanoScroller(action);
			}
		},
		/**
		 * The processing display behaviour
		 */
		spinner: function(action) {
			var $this = this;

			if ($this.contentinited) {
				if (action === 'show') {
					$this.main.fadeTo(0, 0.5);
					$this._spinner.show();
					if ($this.options.scrollbar) $this.scrollbar('hide');

					// set state
					$this.spinning = true;

				} else { // hide
					if ($this.options.scrollbar) $this.scrollbar('show');
					$this._spinner.hide();
					var collection = $this.main;

					// add toolbar to collection if any
					if ($this.toolbar) collection = collection.add($this.toolbar.wrapper);

					// fade
					collection.fadeTo('slow', 1);

					// set state
					$this.spinning = false;
				}
			}
		},
		/**
		 * Set a Dialog Toolbar
		 *
		 * @param tools Array An array of tool objects
		 * eg: [{title : "New folder", icon : "folder-close", subicon : "plus-sign", click : function(){}}]
		 */
		newToolbar: function(tools, alias, backtomain) {
			var $this = this;

			// set toolbar
			if (!$this.toolbar) {
				$this.toolbar = {};
				$this.toolbar.wrapper = $('<div class="zlux-dialog-toolbar" />').hide().insertAfter($this.widget.children()[0]);
			}
			
			// set the wrapper
			var toolbar = $('<div class="zlux-dialog-toolbar-' + alias + '"><ul class="inline" /></div>').appendTo($this.toolbar.wrapper);

			// append the tools
			$.each(tools, function(i, tool) {
				$('ul', toolbar).append(
					$('<li />').append(
						$('<i data-id="' + tool.id + '" class="icon-' + tool.icon + ' icon-2x icon-border" title="' + tool.title + '">' +
								(tool.subicon ? '<i class="icon-' + tool.subicon + '"></i>' : '') +
						'</i>')
						.on('click', function(){
							// don't proceede if tool is disabled
							if ($(this).hasClass('disabled')) return false;

							// execute custom functions
							tool.click($(this));
						})
					)
				);
			});

			// set the action when back to main button is clicked
			$('.zlux-dialog-toolbar-backtomain', $this.toolbar.wrapper).on('click', function(){
				backtomain();
			});
		},
		/**
		 * Set a Dialog Sub Toolbar
		 */
		newSubToolbar: function(alias, parent) {
			var $this = this;

			// set the wrapper
			$('<div class="zlux-dialog-subtoolbar-' + alias + '" />').hide().appendTo(
				$('.zlux-dialog-toolbar-' + parent, $this.toolbar.wrapper)
			);
		},
		setMainToolbar: function(tools) {
			var $this = this;
			
			// set toolbar
			$this.newToolbar(tools, 'main');

			// set the back to main button
			$this.toolbar.wrapper.prepend(
				$('<i class="zlux-dialog-toolbar-backtomain icon-reply icon-border" title="Back to Main view" />').on('click', function(){
					
					$('[class^="zlux-dialog-toolbar-"]', $this.toolbar.wrapper).hide();
					$('.zlux-dialog-toolbar-main', $this.toolbar.wrapper).show();
					
				})
			);
		},
		/*
		 * Shows the indicated toolbar hiding the others
		 */
		showToolbar: function(alias) {
			var $this = this;

			// show the desired
			$('.zlux-dialog-toolbar-' + alias, $this.toolbar.wrapper).show();

			// hide the main
			$('.zlux-dialog-toolbar-main', $this.toolbar.wrapper).hide();

			// show the back button
			$('.zlux-dialog-toolbar-backtomain', $this.toolbar.wrapper).show();
		},
		/*
		 * Toggle the Subtoolbar visibility
		 */
		toggleSubtoolbar: function(alias, parent) {
			var $this = this;

			// get parent
			parent = $('.zlux-dialog-toolbar-' + parent, $this.toolbar.wrapper);

			// get toolbar
			var subtoolbar = $('.zlux-dialog-subtoolbar-' + alias, parent);

			// hide all siblings toolbars, unhide the current
			subtoolbar.siblings('div').slideUp('fast', function(){
				subtoolbar.slideToggle('fast');
			});
		},
		/*
		 * Toggle Toolbar button
		 */
		toolbarBtnState: function(toolbar_alias, id, state) {
			var $this = this,
				tool = $('.zlux-dialog-toolbar-' + toolbar_alias + ' i[data-id="' + id + '"]', $this.toolbar.wrapper);

			if (state === 'disabled') {
				tool.animate({
					color: '#C0C0C0'
					}, 200, function() {
						tool.addClass('disabled');
					}
				);
			} else {
				tool.animate({
					color: '#444'
					}, 200, function() {
						tool.removeClass('disabled');
					}
				);
			}
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = function() {
		var args = arguments;
		return new Plugin(args[0] ? args[0] : {});
	};
})(jQuery, window, document);