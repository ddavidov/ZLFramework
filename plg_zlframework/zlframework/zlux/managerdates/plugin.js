/* ===================================================
 * ZLUX datesManager
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(){};
	$.extend(Plugin.prototype, $.zlux.Manager.prototype, {
		name: 'datesManager',
		initialize: function(target, options) {
			this.options = $.extend({}, this.options, options);
			var $this = this;

			// run the initial check
			$this.initCheck();

			// save target
			$this.target = target;

			// set wrapper
			$this.date = $('<div class="zl-bootstrap zlux-datesmanager" />').appendTo(target);
		},
		/**
		 * Performs initial tasks
		 */
		initCheck: function() {
			var $this = this;

			// set ID
			$.zlux.datesManager.iNextUnique++;
			$this.ID = $.zlux.datesManager.iNextUnique;
		}
	});
	// save the plugin for global use
	$.zlux[Plugin.prototype.name] = Plugin;
	$.zlux[Plugin.prototype.name].iNextUnique = 0;
})(jQuery, window, document);


/* ===================================================
 * ZLUX datesDialogManager
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(element, options) {
		var $this    = this,
			$element =  $(element);

		if($element.data(Plugin.prototype.name)) return;

		$this.element =  $(element);
		$this.options = $.extend({}, Plugin.prototype.options, $.zlux.datesManager.prototype.options, options);
		this.events = {};

		// init the script
		$this.initialize();

		$this.element.data(Plugin.prototype.name, $this);
	};
	$.extend(Plugin.prototype, $.zlux.datesManager.prototype, {
		name: 'datesDialogManager',
		options: {
			title: 'Dates Manager',
			position: {}, // override the Dialog position
			full_mode: 0,
			dialogClass: '',
			mode: 'date', // date, time or datetime
			firstDay: 0 // week start day
		},
		initialize: function() {
			var $this = this;

			// run initial check
			$this.initCheck();

			// element example, it should be set by the caller script
			// $('<a title="' + $this.options.title + '" class="btn btn-mini zlux-btn-edit" href="#"><i class="icon-edit"></i></a>')

			// set the trigger button event
			$this.element.on('click', function(){
				
				// toggle the dialog
				$this.zluxdialog.toggle();

				// avoid default
				return false;
			});

			$this.initDialog();
			$this.initMainEvents();
		},
		/**
		 * Init the Dialog
		 */
		initDialog: function() {
			var $this = this;

			// prepare the dialog class
			$this.options.dialogClass = 'zl-bootstrap zlux-datesmanager' +
				' zlux-datesmanager-' + $this.options.mode +
				($this.options.full_mode ? ' zlux-dialog-full ' : '') +
				($this.options.dialogClass ? ' ' + $this.options.dialogClass : '');

			// load assets
			$.zlux.assets.load('dialog').done(function(){

				// set the dialog options
				$this.zluxdialog = $.zlux.dialog({
					title: $this.options.title,
					width: $this.options.full_mode ? '75%' : 300,
					dialogClass: $this.options.dialogClass,
					scrollbar: false,
					position: $.extend({
						of: $this.element,
						my: 'left top',
						at: 'right bottom'
					}, $this.options.position)
				})

				.bind("InitComplete", function() {

					// set the dialog unique ID
					$this.zluxdialog.widget.attr('id', 'zluxDatesManager_' + $this.ID);

					// init dialog related functions
					$this.eventDialogLoaded();
				});
			});
		},
		/*
		 * Fires when the dialog has finished it's initial tasks
		 */
		eventDialogLoaded: function() {
			var $this = this;

			// init dates manager
			$this.datesmanager = $('<div class="zlux-datesmanager" />').appendTo($this.zluxdialog.content);

			// set global close event
			$('html').on('mousedown', function(event) {
				// close if target is not the trigger or the dialog it self
				if ($this.zluxdialog.dialog('isOpen') && !$this.element.is(event.target) && !$this.element.find(event.target).length &&
						!$this.zluxdialog.widget.find(event.target).length && !$this.zluxdialog.widget.is(event.target)) {

					$this.zluxdialog.dialog('close');
				}
			});

			// set the settings options
			var settings = {
				dateFormat: $.datepicker.ISO_8601,
				constrainInput: false,
				prevText: '', // important as icons have been modified
				nextText: '', // idem
				firstDay: $this.options.firstDay,
				onSelect: function(dateText){
					// trigger event
					$this.trigger("DateSelected", dateText);
				}
			};

			// load date mode
			if ($this.options.mode === 'date') {

				// init
				$this.datesmanager.datepicker(settings)

				// show it
				.datepicker('show');

			// load time/datetime mode
			} else {

				$this.datesmanager.datetimepicker($.extend(settings, {
					timeFormat: $.ui.timepicker.version = '1.0.1' ? 'hh:mm:ss' : 'HH:mm:ss',
					showSecond: false,
					timeOnly: $this.options.mode === 'time' ? true : false // will hide date if time mode
				}))

				// show it
				.datetimepicker('show');
			}

			// trigger event
			$this.trigger("InitComplete");
		},
		/**
		 * Init the Main Events
		 */
		initMainEvents: function() {
			var $this = this;

			// on manager init
			$this.bind("InitComplete", function() {

				// show the content
				$this.zluxdialog.initContent();
			});
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);