/* ===================================================
 * ZLUX fields
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
		var Plugin = function(element, options) {
		var $this    = this;

		$this.options = $.extend({}, this.options, options);
		$this.events = {};

		// get the param name
		var field = $this.options.field;
		field = 'fields'+ field.charAt(0).toUpperCase() + field.slice(1) +'Field';

		// remove the not any more necesary param
		delete $this.options.field;

		// init the Field plugin
		$(element).zlux(field, $this.options);
	};
	// Don't touch
	$.zlux['fields'] = Plugin;
})(jQuery, window, document);


/* ===================================================
 * ZLUX fieldsOptionsField
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
		$this.options = $.extend({}, Plugin.prototype.options, options);
		this.events = {};

		// init the script
		$this.initialize();

		$this.element.data(Plugin.prototype.name, $this);
	};
	$.extend(Plugin.prototype, $.zlux.Main.prototype, {
		name: 'fieldsOptionsField',
		options: {},
		initialize: function() {
			var $this = this;

			// init vars
			$this.list = $('ul', $this.element);
			$this.hidden = $('li.hidden', $this.list).detach();

			// set the styling class
			$this.element.addClass('zl-bootstrap');

			$this.element.on('click', '.zlux-x-delete', function() {
				$(this).closest('li').slideUp(400, function() {
					$(this).remove();
					$this.orderOptions();
				});

				return false;
			})

			.on('click', '.zlux-x-add', function() {
				$this.hidden.clone().removeClass('hidden').appendTo($this.list).slideDown(200).effect('highlight', {}, 1000).find('input:first').focus();
				$this.orderOptions();
			})

			.on('blur', '.zlux-x-name input', function() {
				var option = $(this).closest('li');
				var text = option.find('.panel input:text');

				if ($(this).val() !== '' && text.val() === '') {
					var alias = '';
					$this.getAlias($(this).val(), function(data){
						alias = data ? data : '42';
						text.val(alias);
						option.find('a.trigger').text(alias);
					});
				}
			})

			.on('keydown', '.panel input:text', function(event) {
				event.stopPropagation();
				if (event.which === 13) { $this.setOptionValue($(this).closest('li')); }
				if (event.which === 27) { $this.removeOptionPanel($(this).closest('li')); }
			})

			.on('click', 'input.accept', function () { 
				$this.setOptionValue($(this).closest('li'));

				return false;
			})

			.on('click', 'a.cancel', function () {
				$this.removeOptionPanel($(this).closest('li'));

				return false;
			})

			.on('click', 'a.trigger', function() {
				$(this).hide().closest('li').find('div.panel').addClass('active').find('input:text').focus();

				return false;
			});


			this.list.sortable({
				handle: '.zlux-x-sort',
				containment: this.list.parent().parent(),
				placeholder: 'dragging',
				axis: 'y',
				opacity: 1,
				revert: 75,
				delay: 100,
				tolerance: 'pointer',
				zIndex: 99,
				start: function(event, ui) {
					ui.placeholder.height(ui.helper.height());
					$this.list.sortable('refreshPositions');
				},
				stop: function() {
					$this.orderOptions();
				}
			});
		},
		setOptionValue: function(option) {
			var $this = this;
			var text  = option.find('div.panel input:text');

			var alias = text.val();
			if (alias === '') {
				alias = option.find('div.zlux-x-name input').val();
			}

			this.getAlias(alias, function(data) {
				alias = data ? data : '42';
				text.val(alias);
				option.find('a.trigger').text(alias);
				$this.removeOptionPanel(option);
			});
		},
		orderOptions: function() {
			var $this = this;

			var pattern = /(elements\[\S+])\[(-?\d+)\]/g;
			$this.list.children('li').each(function(i) {
				$(this).find('input').each(function() {
					if ($(this).attr('name')) {
						$(this).attr('name', $(this).attr('name').replace(pattern, "$1["+i+"]"));
					}
				});
			});
		},
		getAlias: function(name, callback) {
			var url = $.zlux.url.ajax('manager', 'getalias', {'force_safe':1});
			
			$.getJSON(url, { name: name }, 
				function(data) {
					callback(data);
				}
			);
		},
		removeOptionPanel: function(option){
			option.find('div.panel input:text').val(option.find('a.trigger').show().text());
			option.find('div.panel').removeClass('active');
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);


/* ===================================================
 * ZLUX fieldsAttributesField
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
		$this.options = $.extend({}, Plugin.prototype.options, options);
		this.events = {};

		// init the script
		$this.initialize();

		$this.element.data(Plugin.prototype.name, $this);
	};
	$.extend(Plugin.prototype, $.zlux.Main.prototype, {
		name: 'fieldsAttributesField',
		options: {},
		initialize: function() {
			var $this = this;

			// init vars
			$this.hidden = $('.hidden', $this.element).detach();
			$this.list = $('ul.zlux-field-attributes', $this.element);

			// set the styling class
			$this.element.addClass('zl-bootstrap');

			// set events
			$this.element

			// when deleting option
			.on('click', '.zlux-x-delete', function() {
				$(this).closest('li').slideUp(400, function() {
					$(this).remove();
					$this.updateIndexes($(this).closest('ul'));
				});

				return false;
			})

			// when adding attribute
			.on('click', '.zlux-x-add-attr', function() {
				var $list = $(this).siblings('ul');
				var $attribute = $this.hidden.find('li.zlux-x-attr').clone().removeClass('hidden');

				$attribute.appendTo($list).slideDown(200).effect('highlight', {}, 1000).find('input:first').focus();
				$this.updateIndexes();
			})

			// when adding option
			.on('click', '.zlux-x-add', function() {
				var $list = $(this).siblings('ul');
				var $option = $this.hidden.find('li.zlux-x-option').clone().removeClass('hidden');

				$option.appendTo($list).slideDown(200).effect('highlight', {}, 1000).find('input:first').focus();
				$this.updateIndexes();
			})

			// when editing option name
			.on('blur', '.zlux-x-name input', function() {
				var option = $(this).closest('li');
				var text = option.find('.panel input:text');

				if ($(this).val() !== '' && text.val() === '') {
					var alias = '';
					$this.getAlias($(this).val(), function(data){
						alias = data ? data : '42';
						text.val(alias);
						option.find('a.trigger').text(alias);
					});
				}
			})

			.on('keydown', '.panel input:text', function(event) {
				event.stopPropagation();
				if (event.which === 13) { $this.setOptionValue($(this).closest('li')); }
				if (event.which === 27) { $this.removeOptionPanel($(this).closest('li')); }
			})

			.on('click', 'input.accept', function () { 
				$this.setOptionValue($(this).closest('li'));

				return false;
			})

			.on('click', 'a.cancel', function () {
				$this.removeOptionPanel($(this).closest('li'));

				return false;
			})

			.on('click', 'a.trigger', function() {
				$(this).hide().closest('li').find('div.panel').addClass('active').find('input:text').focus();

				return false;
			});

			// init options for each list
			$('ul', $this.element).each(function(){
				$this.initList($(this));
			})
			
		},
		initList: function($list) {
			var $this = this;
			$list.sortable({
				handle: '.zlux-x-sort',
				containment: $list.parent().parent(),
				placeholder: 'dragging',
				axis: 'y',
				opacity: 1,
				revert: 75,
				delay: 100,
				tolerance: 'pointer',
				zIndex: 99,
				start: function(event, ui) {
					ui.placeholder.height(40);
					$list.sortable('refreshPositions');

					// hide options
					$list.children('li').find('ul').addClass('hidden');
					// update height
					$(ui.item).height('');
				},
				stop: function() {
					$this.updateIndexes();

					// unhide options
					$list.children('li').find('ul').removeClass('hidden');
				}
			});
		},
		setOptionValue: function(option) {
			var $this = this;
			var text  = option.find('div.panel input:text');

			var alias = text.val();
			if (alias === '') {
				alias = option.find('div.zlux-x-name input').val();
			}

			this.getAlias(alias, function(data) {
				alias = data ? data : '42';
				text.val(alias);
				option.find('a.trigger').text(alias);
				$this.removeOptionPanel(option);
			});
		},
		updateIndexes: function() {
			var $this = this;

			var pattern1 = /(elements\[\w{8}-\w{4}-\w{4}-\w{4}-\w{12}\]\[\S+?\])\[(\d+)\]/g; // lazy match
			var pattern2 = /(elements\[\w{8}-\w{4}-\w{4}-\w{4}-\w{12}\]\[\S+\])\[(\d+)\]/g; // greedy match
			$this.list.children('li').each(function(index) {

				// first level index
				$('input', $(this)).each(function() {
					if ($(this).attr('name')) {
						$(this).attr('name', $(this).attr('name').replace(pattern1, "$1["+index+"]"));
					}
				});

				// options sublevel index
				$('ul', $(this)).children('li').each(function(subindex) {
					$('input', $(this)).each(function() {
						if ($(this).attr('name')) {
							$(this).attr('name', $(this).attr('name').replace(pattern2, "$1["+subindex+"]"));
						}
					});
				});
			});
		},
		getAlias: function(name, callback) {
			var url = $.zlux.url.ajax('manager', 'getalias', {'force_safe':1});
			
			$.getJSON(url, { name: name }, 
				function(data) {
					callback(data);
				}
			);
		},
		removeOptionPanel: function(option){
			option.find('div.panel input:text').val(option.find('a.trigger').show().text());
			option.find('div.panel').removeClass('active');
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);


/* ===================================================
 * ZLUX fieldsItemsField
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
		$this.options = $.extend({}, Plugin.prototype.options, options);
		this.events = {};

		// init the script
		$this.initialize();

		$this.element.data(Plugin.prototype.name, $this);
	};
	$.extend(Plugin.prototype, $.zlux.Main.prototype, {
		name: 'fieldsItemsField',
		options: {
			controlName: ''
		},
		initialize: function() {
			var $this = this;

			// init vars
			$this.aRelated = [];

			// save relations list
			$this.list = $('<ul />').addClass('zlux-field-items').appendTo($this.element)

			// init sortable feature
			.sortable({
				handle: '.zlux-x-sort',
				placeholder: 'dragging',
				axis: 'y',
				opacity: 1,
				delay: 100,
				tolerance: 'pointer',
				containment: 'parent',
				forcePlaceholderSize: !0,
				scroll: !1,
				start: function (event, ui) {
					ui.helper.addClass('ghost')
				},
				stop: function (event, ui) {
					ui.item.removeClass('ghost')
				}
			})

			// delete event
			.on('click', '.zlux-x-delete', function () {
				$(this).closest('li').fadeOut(200, function () {
					var $object = {};
					$object.id = $(this).data('id');

					$this.removeRelation($object);
				})
			});

			// get current values from dom
			$('.zlux-x-item', $this.element).each(function(i, item){
				var $object = $(item).data('info');

				// append relation to list
				$this.appendRelation($object);

				// remove the original input
				$(item).remove();
			});
			
			// set the styling class
			$this.element.addClass('zl-bootstrap');

			// set the trigger button
			$this.dialogTrigger = $('<button type="button" class="btn btn-mini"><i class="icon-plus-sign"></i> Add item </button>')

			// add it to dom
			.appendTo($this.element);

			// load assets
			$.zlux.assets.load('items').done(function(){

				// init the dialog manager
				$this.dialogTrigger.zlux("itemsDialogManager", {
					position: {
						of: $this.element,
						my: 'left top',
						at: 'right top'
					},
					apps: $this.options.apps,
					types: $this.options.types
				})

				// on object select event
				.on("zlux.ObjectSelected", function(e, manager, $object){

					// check if already related
					if($.inArray($object.id, $this.aRelated) != '-1') {

						// if so, unrelate
						$this.removeRelation($object);

					} else { // relate

						// set the object checkbox
						$('.column-icon i', $object.dom).removeClass('icon-file-alt').addClass('icon-check');

						// append the object to the relations list dom
						$this.appendRelation($object);
					}
				})

				.on("zlux.TableDrawCallback", function(e, manager){
					
					$('tbody tr', manager.oTable).each(function(i, object_dom){
						if($.inArray(object_dom.getAttribute('data-id'), $this.aRelated) != '-1') {
							$('.column-icon i', object_dom).removeClass('icon-file-alt').addClass('icon-check');
						}
					})

					// save the manager
					$this.manager = manager;
				});
			});
		},
		appendRelation: function ($object) {
			var $this = this;
			
			// prepare object dom
			$('<li class="zlux-x-item" data-id="' + $object.id + '">'
				+'<div class="zlux-x-name">' + $object.name + '</div>'
				+'<span class="zlux-x-tools">'
					+'<i class="zlux-x-delete icon-remove-circle" title="Delete"></i>'
					+'<i class="zlux-x-sort icon-move" title="Sort"></i>'
				+'</span>'
				+'<div class="zlux-x-info">'
					+'<div>' + $object.type.name + ' / ' + $object.application.name + '</div>'
				+'</div>'
				+'<input type="hidden" name="' + $this.options.controlName + '" value="' + $object.id + '"/>'
			+'</li>')

			// add to dom
			.appendTo($this.list);

			// add to related list
			$this.aRelated.push($object.id);
		},
		removeRelation: function ($object) {
			var $this = this;
			
			// remove from dom
			$('li[data-id="' + $object.id + '"]', $this.list).remove();

			// and from related array
			$this.aRelated.splice($.inArray($object.id, $this.aRelated), 1);

			// remove the checkbox from the manager
			if ($this.manager) {
				$('tbody tr[data-id="' + $object.id + '"] .column-icon i', $this.manager.oTable).removeClass('icon-check').addClass('icon-file-alt');
			}
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);