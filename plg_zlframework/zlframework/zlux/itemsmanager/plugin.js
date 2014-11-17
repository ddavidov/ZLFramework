/* ===================================================
 * ZLUX itemsManager
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(){};
	$.extend(Plugin.prototype, $.zlux.Manager.prototype, {
		name: 'itemsManager',
		options: {
			apps: '', // Array or comma separated values
			types: '', // idem
			categories: '', // idem
			tags: '', // idem
			authors: '' // idem
		},
		initialize: function(options) {
			this.options = $.extend({}, this.options, options);
			var $this = this;

			// set the filter param
			$this.filter = {};

			// run the initial check
			$this.initCheck();

			// init itemsmanager
			$this.itemsmanager = $('<div class="zl-bootstrap zlux-itemsmanager" />').appendTo($this.element);
			$this.initDataTable($this.itemsmanager);
		},
		/**
		 * Performs initial tasks
		 */
		initCheck: function() {
			var $this = this;

			// set ID
			$.zlux.itemsManager.iNextUnique++;
			$this.ID = $.zlux.itemsManager.iNextUnique;
		},
		initDataTable: function(wrapper) {
			var $this = this;

			// load asset
			$.zlux.assets.load($.zlux.url.zlfw('zlux/assets/datatables/dataTables.with.plugins.min.js'), function(){
				$this._initDataTable(wrapper);
			});
		},
		_initDataTable: function(wrapper) {
			var $this = this;

			// set table
			$('<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered" />')
			.appendTo(wrapper);

			// init dataTable
			$this.oTable = $('table', wrapper).dataTable({
				"sDom": "F<'row-fluid'<'span12't>><'row-fluid'<'span12'p>><'row-fluid zlux-x-info'<'span12'i>>",
				"bServerSide": true,
				"iDisplayLength": 20,
				"sAjaxSource": $.zlux.url.ajax('zlux', 'getItemsManagerData'),
				"sServerMethod": "POST",
				"fnServerParams": function (aoData) {
					// determine what filter values to use
					var apps = $this.filter.apps ? $this.filter.apps : $this.options.apps,
						types = $this.filter.types ? $this.filter.types : $this.options.types,
						cats = $this.filter.cats ? $this.filter.cats : $this.options.categories,
						tags = $this.filter.tags ? $this.filter.tags : $this.options.tags,
						authors = $this.filter.authors ? $this.filter.authors : $this.options.authors;

					// push the preset filter values
					aoData.push({ "name": "apps", "value": $this.options.apps });
					aoData.push({ "name": "types", "value": $this.options.types });
					aoData.push({ "name": "categories", "value": $this.options.categories });
					aoData.push({ "name": "tags", "value": $this.options.tags });
					aoData.push({ "name": "authors", "value": $this.options.authors });

					// push the new filter values
					aoData.push({ "name": "filter_apps", "value": apps });
					aoData.push({ "name": "filter_types", "value": types });
					aoData.push({ "name": "filter_cats", "value": cats });
					aoData.push({ "name": "filter_tags", "value": tags });
				},
				"oLanguage": {
					"sEmptyTable": $this._('IM_NO_ITEMS_FOUND'),
					"sInfoEmpty": "",
					"sInfo": $this._('IM_PAGINATION_INFO')
				},
				"aoColumns":
				[
					{
						"sTitle": $this._('NAME'), "mData": "_itemname", "sClass":"column-name",
						"mRender": function ( data, type ) {
							return type === 'display' ? '' : data;
						},
						"fnCreatedCell": function (nTd, sData, oData) {
							$(nTd).parent('tr').attr('data-id', oData.id);
						}
					},
					{ 
						"sTitle": "App", "mData": "application", "bSortable": false,
						"mRender": function ( data, type ) {
							return type === 'display' ? data.name : data.id;
						}
					},
					{ 
						"sTitle": "Type", "mData": "type", "bSortable": false,
						"mRender": function ( data, type ) {
							return type === 'display' ? data.name : data.id;
						}
					},
					{ "sTitle": "Access", "mData": "access", "bSearchable": false, "bSortable": false },
					{ "sTitle": "Author", "mData": "author", "bSearchable": false, "bSortable": false,
						"mRender": function ( data, type ) {
							return type === 'display' ? data.name : data.id;
						}
					},
					{
						"sTitle": "ID", "mData": "id", "bSearchable": false, "bSortable": false
					},
					{
						"sTitle": "", "mData": "type", "bSortable": false, "bSearchable": false, "sWidth": "14px", "sClass": "column-icon",
						"mRender": function ( data, type ) {
							if (type === 'display') {
								return '<i class="icon-file-alt"></i>';
							} else {
								return data;
							}
						}
					}
				],
				"aoColumnDefs": [
					{ "bVisible": false, "aTargets": [ 1, 2, 3, 4, 5 ] }
				],
				"fnInitComplete": function() {
					var input_filter = $('.zlux-x-filter-input_wrapper', wrapper)
					
					.append(
						// set search icon
						$('<i class="icon-search" />'),
						// and the cancel button
						$('<i class="icon-remove zlux-ui-dropdown-unselect" />').hide().on('click', function(){
							$('input', input_filter).val('');
							$(this).hide();
							// reset the filter
							$this.oTable.fnFilter('');
						})
					);

					// set search events
					$('input', input_filter).on('keyup', function(){
						if ($(this).val() === '') {
							$('.zlux-ui-dropdown-unselect', input_filter).hide();
						} else {
							$('.zlux-ui-dropdown-unselect', input_filter).show();
						}
					});

					// fix the header column order
					$('thead tr th:last', $this.oTable).prependTo($('thead tr', $this.oTable));

					// trigger table init event
					$this.trigger("InitComplete");
				},
				"fnRowCallback": function( nRow, aData ) {
					var $object = aData;
					
					// save object dom
					$object.dom = $(nRow);

					// set object dom properties
					$object.dom.addClass('zlux-object');

					// fix the column order
					$('td:last', $object.dom).prependTo($object.dom);

					// reset and append the object data
					$('.column-name', $object.dom).html('').removeClass('zlux-ui-open').append(

						// render the object content
						$this.renderObjectDOM($object)
					);
				},
				"fnPreDrawCallback": function(oSettings) {
					// show processing
					$this.zluxdialog.spinner('show');

					// trigger event
					$this.trigger("DTPreDrawCallback", oSettings);
				},
				"fnDrawCallback": function(oSettings) {
					// pagination hide/show
					var oPaging = oSettings.oInstance.fnPagingInfo(),
						pagination = $('.dataTables_paginate', $(oSettings.nTableWrapper)).closest('.row-fluid');
					
					// hide/show the pagination
					if (oPaging.iTotalPages <= 1) pagination.hide(); else pagination.show();

					// update dialog scrollbar
					$this.zluxdialog.scrollbar('refresh');

					// hide processing
					$this.zluxdialog.spinner('hide');

					// trigger event
					$this.trigger("TableDrawCallback", oSettings);
				}
			})

			// Trigger Object Selected event
			.on('click', '.zlux-object .zlux-x-name a', function(){
				var object_dom = $(this).closest('tr.zlux-object'),
					$object = $this.oTable.fnGetData( object_dom[0] );

				// set the zlux object
				$object.dom = object_dom;

				// trigger event
				$this.trigger("ObjectSelected", $object);
				
				// prevent default
				return false;
			});
		},
		/**
		 * Render the Object content
		 */
		renderObjectDOM: function($object) {
			var $this = this,
				aDetails;
			
			// prepare the details
			aDetails = [
				{name: $this._('ROUTE'), value: $object.application.name + ' / ' + $object.type.name + ' / ID ' + $object.id},
				{name: $this._('ACCESS'), value: $object.access},
				{name: $this._('CREATED'), value: $object.created}
			];

			// add Author if known
			if ($object.author.name) aDetails.push({name: $this._('AUTHOR'), value: $object.author.name});
			
			var sDetails = '';
			$.each(aDetails, function(i, detail){
				sDetails += '<li><strong>' + detail.name + '</strong>: <span>' + detail.value + '</span></li>';
			});

			// set object dom
			var content = $(
				// btns
				'<div class="zlux-x-tools">' +
					'<i class="zlux-x-details-btn icon-angle-down" />' +
					// '<i class="zlux-x-remove icon-minus-sign" />' + // remove feature - TODO
				'</div>' +

				// name
				'<div class="zlux-x-name"><a href="#" class="zlux-x-name-link">' + $object.name + '</a></div>' +

				// details
				'<div class="zlux-x-details">' +
					'<div class="zlux-x-messages" />' +
					'<div class="zlux-x-details-content">' +
						'<ul class="unstyled">' + sDetails + '</ul>' +
					'</div>' +
				'</div>'
			);

			return content;
		},
		/**
		 * Reloads the Table content
		 */
		reload: function() {
			this.oTable.fnReloadAjax();
		}
	});
	// save the plugin for global use
	$.zlux[Plugin.prototype.name] = Plugin;
	$.zlux[Plugin.prototype.name].iNextUnique = 0;
})(jQuery, window, document);


/* ===================================================
 * ZLUX itemsDialogManager
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
		$this.options = $.extend({}, Plugin.prototype.options, $.zlux.itemsManager.prototype.options, options);
		this.events = {};

		// init the script
		$this.initialize();

		$this.element.data(Plugin.prototype.name, $this);
	};
	$.extend(Plugin.prototype, $.zlux.itemsManager.prototype, {
		name: 'itemsDialogManager',
		options: {
			title: 'Items Manager',
			position: {}, // override the Dialog position
			full_mode: 0,
			dialogClass: ''
		},
		initialize: function(options) {
			var $this = this;

			// run initial check
			$this.initCheck();

			// set the filter param
			$this.filter = {};

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
			$this.options.dialogClass = 'zl-bootstrap zlux-itemsmanager' +
				($this.options.full_mode ? ' zlux-dialog-full ' : '') +
				($this.options.dialogClass ? ' ' + $this.options.dialogClass : '');

			// load assets
			$.zlux.assets.load('dialog').done(function(){

				// set the dialog options
				$this.zluxdialog = $.zlux.dialog({
					title: $this.options.title,
					width: $this.options.full_mode ? '75%' : 300,
					dialogClass: $this.options.dialogClass,
					position: $.extend({
						of: $this.element,
						my: 'left top',
						at: 'right bottom'
					}, $this.options.position)
				})

				.bind("InitComplete", function() {

					// set the dialog unique ID
					$this.zluxdialog.widget.attr('id', 'zluxItemsManager_' + $this.ID);

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

			// init itemsmanager
			$this.itemsmanager = $('<div class="zlux-itemsmanager" />').appendTo($this.zluxdialog.content);
			$this.initDataTable($this.itemsmanager);


			// set Object details Open event
			$this.zluxdialog.main.on('click', '.zlux-x-details-btn', function(){
				var toggle = $(this),
					$object = toggle.closest('tr.zlux-object'),
					TD = $('td.column-name', $object),
					details = $('.zlux-x-details', $object);

				// open the details
				if (!TD.hasClass('zlux-ui-open')) {
					TD.addClass('zlux-ui-open');
					toggle.removeClass('icon-angle-down').addClass('icon-angle-up');

					// scroll to the Object with animation
					$this.zluxdialog.content.stop().animate({
						'scrollTop': $object.get(0).offsetTop
					}, 900, 'swing');

					// open, when done...
					details.slideDown('fast', function(){
						$this.zluxdialog.scrollbar('refresh');
					});

				// close them
				} else {
					toggle.addClass('icon-angle-down').removeClass('icon-angle-up');
					TD.removeClass('zlux-ui-open');
					details.slideUp('fast', function(){
						$this.zluxdialog.scrollbar('refresh');
					});
				}
			});

			// set global close event
			$('html').on('mousedown', function(event) {
				// close if target is not the trigger or the dialog it self
				if ($this.zluxdialog.dialog('isOpen') && !$this.element.is(event.target) && !$this.element.find(event.target).length &&
						!$this.zluxdialog.widget.find(event.target).length && !$this.zluxdialog.widget.is(event.target)) {

					$this.zluxdialog.dialog('close');
				}
			});

			// init toolbar
			$this.zluxdialog.setMainToolbar(
				[{
					title: $this._('APPLY_FILTERS'),
					icon: "filter",
					click: function(tool){
						// toggle the subtoolbar visibility
						$('.zlux-dialog-subtoolbar-filter', $this.zluxdialog.toolbar.wrapper).slideToggle('fast');

						tool.toggleClass('zlux-ui-tool-enabled');
					}
				},{
					title:  $this._('REFRESH'),
					icon: "refresh",
					click: function(){
						// reload the table data
						$this.reload();
					}
				}]
			);

			// init subtoolbar
			$this.zluxdialog.newSubToolbar('filter', 'main');
		},
		/**
		 * Init the Main Events
		 */
		initMainEvents: function() {
			var $this = this;

			// on manager init
			$this.bind("InitComplete", function() {

				// init dialog scrollbar
				$this.zluxdialog.scrollbar('refresh');

				// get subtoolbar
				var subtoolbar = $('.zlux-dialog-subtoolbar-filter', $this.zluxdialog.toolbar.wrapper);

				// set filter selects
				subtoolbar.append(
					$this.getSelect('aaApps', $this._('IM_FILTER_BY_APP'), function(value){
						// apply the filter
						$this.filter.apps = value;

						// restart type filter
						$this.filter.types = '';
						$('.btn-group[data-id=2]', subtoolbar).attr('data-update', true);
					}, function(){
						// reset the filter
						$this.filter.apps = '';
					}),

					$this.getSelect('aaTypes', $this._('IM_FILTER_BY_TYPE'), function(value){
						// apply the filter
						$this.filter.types = value;
						$('.btn-group[data-id=2]', subtoolbar).attr('data-update', false);
					}, function(){
						// reset the filter
						$this.filter.types = '';
					}),

					$this.getSelect('aaCategories', $this._('IM_FILTER_BY_CATEGORY'), function(value){
						// apply the filter
						$this.filter.cats = value;
					}, function(){
						// reset the filter
						$this.filter.cats = '';
					}),

					$this.getSelect('aaTags', $this._('IM_FILTER_BY_TAG'), function(value){
						// apply the filter
						$this.filter.tags = value;
					}, function(){
						// reset the filter
						$this.filter.tags = '';
					})
				);

				// move the search field to the toolbar
				$('.zlux-x-filter-input_wrapper', $this.oTable.fnSettings().nTableWrapper).appendTo(subtoolbar);

				// show the content
				$this.zluxdialog.initContent();
			});

			/* EXAMPLE events
			$this.bind("ItemSelected", function(manager, id, row){
				// if multiple selection allowed - TODO

				// else unset siblings check
				row.siblings('[data-checked]').removeAttr('data-checked');

				// set item value
				input.val(id).trigger('change');
			});

			// on item unselect
			$this.bind("ItemUnselected", function(manager, id, row){
				// unset item value
				input.val('').trigger('change');
			}); */
		},
		getSelect: function(dataName, text, onChangeCallback, onUnselectCallback) {
			var $this = this,

			// get initial options
			options = $.parseJSON($this.oTable.fnSettings().jqXHR.responseText)[ dataName ];

			// if 0 or 1 option, abort rendering
			if (options.length <= 1) return '';

			// set the toggle btn
			var toggle = $('<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">' + text + '<span class="caret" /></a>'),

			// set the dropdown
			dropdown = $('<ul class="dropdown-menu">' + $this._getSelectOptions(options) + '</ul>'),

			// set the button group
			btnGroup = $('<div class="btn-group" data-text="' + text + '" />').append(toggle, dropdown),

			// set the unselect option
			removeBtn = $('<i class="icon-remove zlux-ui-dropdown-unselect" />').on('click', function(){
				// reset the label
				toggle.html(text + '<span class="caret"></span>');

				// hide it self
				removeBtn.hide();

				// execute on unselect callback
				if ($.isFunction(onUnselectCallback)) onUnselectCallback();

				// reload
				$this.reload();
			})

			// hide and attach to btn group
			.hide().prependTo(btnGroup);

			// if no options disable it
			if (!options.length) {
				toggle.addClass('disabled');
			}

			// set tooggle event
			toggle.on('click', function() {
				// if disabled, do nothing
				if (toggle.hasClass('disabled')) return '';
			});

			// set options event
			dropdown.on('click', 'a', function(e) {
				var value = $(this).data('value') ? $(this).data('value') : '',
					label = text;

				// set the option as label
				label = $(this).text().replace(/^[^a-zA-Z]+/g, '').replace(/[^a-zA-Z]+$/g, '');

				// show the unselect option
				removeBtn.show();

				// update the label
				toggle.html(label+'<span class="caret"></span>')

				// save the selected value
				.data('selected-value', value);

				// execute on change callback
				if ($.isFunction(onChangeCallback)) onChangeCallback(value);

				// reload
				$this.reload();
				e.preventDefault();
			});

			// set update event
			$this.bind("TableDrawCallback", function() {
				// update options
				var options = $.parseJSON($this.oTable.fnSettings().jqXHR.responseText)[ dataName ];
				dropdown.empty().append($this._getSelectOptions(options));

				// if no options disable it
				if (!options.length) {
					btnGroup.addClass('disabled');
				}

				// check if the new options have on it the current selected value
				var match = false;
				$.each(options, function(index, value){
					if (value.id === toggle.data('selected-value')) {
						match = true;
					}
				});

				// if they have not, the current selection must be deselected
				if (!match) {
					removeBtn.hide();
					toggle.data('selected-value', '').html(text + '<span class="caret"></span>');
				}
			});

			// return the dropdown
			return btnGroup;
		},
		_getSelectOptions: function(options) {
			var iLen = options.length,
				asResultData = [],
				i;

			// set options
			for ( i=0 ; i<iLen ; i++ )
			{
				var sValue = '<li><a href="#" data-value="'+options[i].id+'">'+options[i].name+'</a></li>';
				if (jQuery.inArray(sValue, asResultData) > -1) continue;

				// else push the value onto the result data array
				else asResultData.push(sValue);
			}

			// join and return the options
			return asResultData.join('');
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);