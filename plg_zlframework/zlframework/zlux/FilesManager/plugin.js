/* ===================================================
 * ZLUX filesManager
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(){};
	$.extend(Plugin.prototype, $.zlux.Manager.prototype, {
		name: 'filesManager',
		options: {
			root: 'images', // relative path to the root
			extensions: '', // Array or comma separated values
			storage: 'local',
			storage_params: {},
			max_file_size: '',
			resize: {}
		},
		initialize: function(target, options) {
			this.options = $.extend({}, this.options, options);
			var $this = this;

			// http://srobbin.com/jquery-plugins/approach/
			// if placeholder set the trigger button
			// $('<a class="btn btn-mini" href="#"><i class="icon-plus-sign"></i>Add Item</a>')

			// run the initial check
			$this.initCheck();

			// save target
			$this.target = target;

			// init filesmanager
			$this.filesmanager = $('<div class="zl-bootstrap zlux-filesmanager" />').appendTo(target);
			$this.initDataTable($this.filesmanager);
		},
		/**
		 * Performs initial tasks
		 */
		initCheck: function() {
			var $this = this;

			// set ID
			$.zlux.filesManager.iNextUnique++;
			$this.ID = $.zlux.filesManager.iNextUnique;

			// save the instance reference
			$.zlux.filesManager.instances[$this.ID] = $this;

			// Convert settings
			$this.options.max_file_size = $this.parseSize($this.options.max_file_size);
			$this.options.extensions = $this.options.extensions.replace(/\|/g, ',');

			// check storage param
			if ($this.options.storage === '' || $this.options.storage === undefined || $this.options.storage === null) {
				$this._ErrorLog(0, $this._('STORAGE_PARAM_MISSING'));
				$this.options.storage = 'local';
			}

			// save the start root
			$this.sStartRoot = $this.cleanPath($this.options.root);

			// and the current path
			$this.sCurrentPath = $this.sStartRoot;
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
				"sDom": "F<'row-fluid'<'span12't>>",
				"oLanguage": {
					"sEmptyTable": $this._('EMPTY_FOLDER'),
					"sInfoEmpty": ""
				},
				"sAjaxUrl": $.zlux.url.ajax('zlux'),
				"sAjaxSource": $.zlux.url.ajax('zlux', 'getFilesManagerData'),
				"sServerMethod": "POST",
				"bPaginate": false,
				"aoColumns": [
					{ 
						"sTitle": "", "mData": "type", "bSearchable": false, "sWidth": "14px", "sClass": "column-icon",
						"mRender": function ( data, type ) {
							if (type === 'display') {
								return '<i class="icon-' + (data === 'folder' ? 'folder-close' : 'file-alt') + '"></i>';
							} else {
								return data;
							}
						}
					},
					{ 
						"sTitle": $this._('NAME'), "mData": "name", "sClass": "column-name",
						"mRender": function ( data, type ) {
							return type === 'display' ? '' : data;
						},
						"fnCreatedCell": function (nTd, sData, oData) {
							// store path in data
							$(nTd).parent('tr').attr('data-id', $this.cleanPath( oData.name ));
						}
					}
				],
				"aoColumnDefs": {
					"bVisible": false, "aTargets": [ 2 ]
				},
				"aaSorting": [ [0,'desc'], [1,'asc'] ], // init sort
				"fnServerData": function ( sUrl, aoData, fnCallback, oSettings ) {
					$this._fnServerData(sUrl, aoData, fnCallback, oSettings);
				},
				"fnServerParams": function (aoData) {
					aoData.push({ "name": "extensions", "value": $this.options.extensions });

					// if S3 storage
					if($this.options.storage === 's3') {
						aoData.push({ "name": "storage", "value": "s3" });
						aoData.push({ "name": "accesskey", "value": $this.options.storage_params.accesskey });
						aoData.push({ "name": "key", "value": $this.options.storage_params.secretkey });
						aoData.push({ "name": "bucket", "value": $this.options.storage_params.bucket });
					}
				},
				"fnRowCallback": function(nRow, aData) {
					var $object = aData;
					
					// save object dom
					$object.dom = $(nRow);

					// set object dom properties
					$object.dom.attr('data-type', aData.type).addClass('zlux-object');

					// reset and append the object data
					$('.column-name', $object.dom).html('').removeClass('zlux-ui-open').append(
						// render the object content
						$this.renderObjectDOM($object)
					);

					// append the object edit feature to the name
					$('.zlux-x-name', $object.dom).append(
						'<i class="zlux-x-name-edit icon-edit-sign" title="' + $this._('RENAME') + '" />'
					);
				},
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

					// trigger table init event 50ms after as a workaround if the first ajax call is canceled
					setTimeout(function() {
						$this.trigger("InitComplete");
					}, 50);
				},
				"fnPreDrawCallback": function() {
					// show processing
					$this.zluxdialog.spinner('show');
				},
				"fnDrawCallback": function(oSettings) {
					// pagination hide/show
					var oPaging = oSettings.oInstance.fnPagingInfo(),
						pagination = $('.dataTables_paginate', $(oSettings.nTableWrapper)).closest('.row-fluid');
					
					if (oPaging.iTotalPages <= 1) pagination.hide(); else pagination.show();

					// trigger event
					$this.trigger("DrawCallback");

					// update dialog scrollbar
					$this.zluxdialog.scrollbar('refresh');

					// hide spinner
					$this.zluxdialog.spinner('hide');
				}
			})

			// Trigger Object Selected event
			.on('click', '.zlux-object .zlux-x-name a', function(){
				var object_dom = $(this).closest('tr.zlux-object'),
					$object = $this.oTable.fnGetData( object_dom[0] );

				// set the zlux object
				$object.dom = object_dom;

				if ($object.dom.attr('data-zlux-object-status') !== 'true') {
					$object.dom.attr('data-zlux-object-status', 'true');

					// remove selected status from siblings
					$object.dom.siblings().removeAttr('data-zlux-object-status');

					// if folder
					if ($object.dom.data('type') === 'folder') {
						$this.oTable.fnSettings();

						// update go to path
						$this.sGoToPath = $object.dom.data('id');

						// reload with new path
						$this.oTable.fnReloadAjax();
					}

					// trigger event
					$this.trigger("ObjectSelected", $object);
				}
				
				// prevent default
				return false;
			})

			// Trigger Object Removed event
			.on('click', '.zlux-object .zlux-x-remove', function(){
				var object_dom = $(this).closest('tr.zlux-object'),
					TD = $('td', object_dom),
					$object = $this.oTable.fnGetData( object_dom[0] );

				// set the zlux object
				$object.dom = object_dom;

				// if open, the remove action will delete the file, with confirmation
				if (TD.hasClass('zlux-ui-open')) {
					$this.trigger("BeforeDeleteFile", $object);
				}
				
				// prevent default
				return false;
			});
		},
		_fnServerData: function( sUrl, aoData, fnCallback, oSettings ) {
			var $this = this,
				root;

			/* the Cache Data is stored in the main plugin so it can be shared by all instances */

			// set the root
			root = $this.cleanPath($this.sCurrentPath + '/' + $this.sGoToPath);

			// reset vars
			$this.sGoToPath = '';

			// send root with post data
			aoData.push({ "name": "root", "value": root });

			// ajax
			oSettings.jqXHR = $.ajax({
				"url": sUrl,
				"data": aoData,
				"beforeSend": function(){
					// check if the data is cached
					var cached = false;

					// if not reloading
					if (!$this.bReloading || $this.bRedrawing){

						// check if already cached
						var json = $.zlux.filesManager.aAjaxDataCache[root];
						if (json) {

							// if first time, save real root path, as it can be changed for security reasons by the server
							if (!$this.bCacheInited) $this.sStartRoot = json.root;

							// save root
							$this.sCurrentPath = root;

							// set cache status
							$this.bCacheInited = true;

							// emulate the xhr events
							$(oSettings.oInstance).trigger('xhr', [oSettings, json]);
							fnCallback( json );

							// avoid ajax call
							cached = true;
						}

						// if redrawing
						if ($this.bRedrawing) {
							// reset the param
							$this.bRedrawing = false;

							// save root
							$this.sCurrentPath = root;

							// avoid the ajax call
							return false;
						}
					}

					// if cached abort ajax
					if (cached) return false;

					// else, the ajax proceeds, show the spinner
					$this.zluxdialog.spinner('show');
				},
				"dataType": "json",
				"cache": false,
				"type": oSettings.sServerMethod,
				"error": function (xhr, error) {
					if ( error === "parsererror" ) {
						oSettings.oApi._fnLog( oSettings, 0, "DataTables warning: JSON data from "+
							"server could not be parsed. This is caused by a JSON formatting error." );
					}
				}
			})

			.done(function ( json ) {
				// manage possible errors
				if (json.errors.length) {
					oSettings.oApi._fnLog( oSettings, 0, json.errors );
				}

				// set json
				json = json.result;

				// if first time, save real root path, as it can be changed for security reasons by the server
				if (!$this.bCacheInited) $this.sStartRoot = json.root;

				// save new path
				$this.sCurrentPath = json.root;

				// empty cache if reloading, so the content is retrieved again
				if ($this.bReloading) $.zlux.filesManager.aAjaxDataCache = {};

				// cache the data
				$.zlux.filesManager.aAjaxDataCache[json.root] = json;

				// redraw the other instances
				$this.redrawInstances();

				// set reloading to false
				$this.bReloading = false;

				// set cache status
				$this.bCacheInited = true;

				// trigger events
				$(oSettings.oInstance).trigger('xhr', [oSettings, json]);
				fnCallback( json );
			});
		},
		/**
		 * Reload the data
		 */
		reload: function() {
			var $this = this;

			// set vars
			$this.bReloading = true;

			// reload
			$this.oTable.fnReloadAjax();
		},
		/**
		 * Redraw all other instances
		 */
		redrawInstances: function() {
			var $this = this;

			// redraw instances
			$.each($.zlux.filesManager.instances, function(index, instance){

				// skip current instance
				if (parseInt(index) === $this.ID) return true;

				// if table inited
				if (instance.oTable) {

					// redraw
					instance.bRedrawing = true;
					instance.oTable.fnReloadAjax();
				}
			});
		},
		/**
		 * Init breadcrumb
		 */
		initBreadcrumb: function() {
			var $this = this;

			// set the breadcrumb wrapper
			$this.breadcrumb = $('<ul class="breadcrumb" />').prependTo($this.filesmanager)

			// wrap it
			.wrap('<div class="row-fluid"><div class="span12" /></div>')

			// assign events
			.on('click', 'a', function(){
				// set path values for correct routing
				$this.sCurrentPath = $this.sStartRoot;
				$this.sGoToPath = $(this).data('path');

				// reload
				$this.oTable.fnReloadAjax();
				return false;
			});

			// set event
			$this.bind("DrawCallback initBreadcrumb", function() {
				var path = $this.sCurrentPath ? $this.sCurrentPath : '', // current browsed path
					brcb = [],
					paths,
					txtROOT = $this._('ROOT').toLowerCase();

				// remove the root, is confidential data
				path = path.replace(new RegExp('^' + $this.sStartRoot), '');

				// clean the path
				path = path.replace(/(^\/|\/$)/, '');

				// split the path in folders
				paths = path.length ? path.split('/') : [];

				// if active path is the root
				if (!paths.length) brcb.push('<li class="active">' + txtROOT + '</li>');

				// if not
				else brcb.push('<li><a href="#" data-path="">' + txtROOT + '</a><span class="divider">/</span></li>');

				// populate with the other paths
				path = [];
				$.each(paths, function(i, v){
					path.push(v);
					if (paths.length === i+1 ) {
						brcb.push('<li class="active">' + v.toLowerCase() + '</li>');
					} else {
						brcb.push('<li><a href="#" data-path="' + path.join('/') + '">' + v.toLowerCase() + '</a><span class="divider">/</span></li>');
					}
				});

				// update breadcrumb
				$this.breadcrumb.html(brcb.join(''));
			})

			// first init
			.trigger("initBreadcrumb");
		},
		/**
		 * Render the Object content
		 */
		renderObjectDOM: function($object) {
			var $this = this,
				aDetails;

			// set the details
			if ($object.type === 'folder') {

				aDetails = [
					{name: $this._('NAME'), value: $object.basename}
				];

			} else { // file

				aDetails = [
					{name: $this._('NAME'), value: $object.basename},
					{name: $this._('TYPE'), value: $object.content_type},
					{name: $this._('SIZE'), value: $object.size.display}
				];
			}

			// prepare the details
			var sDetails = '';
			$.each(aDetails, function(i, detail){
				sDetails += '<li><strong>' + detail.name + '</strong>: <span>' + detail.value + '</span></li>';
			});

			// set entry details
			var content = $(
				// btns
				'<div class="zlux-x-tools">' +
					'<i class="zlux-x-details-btn icon-angle-down" />' +
					'<i class="zlux-x-remove icon-minus-sign" title="' + $this._('DELETE') + '" />' +
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
		 * Delete the object from the server
		 */
		deleteObject: function($object) {
			var $this = this,
				aoData = [],

			// save object path
			path = $this.getFullPath($object.name);

			// push the storage related data
			aoData = $this.pushStorageData(aoData);

			// if S3 storage
			if($this.options.storage === 's3') {

				// add a slash if folder
				if($object.type === 'folder') {
					path = path + '/';
				}
			}

			// set path
			aoData.push({ "name": "path", "value": path });

			// make the request and return a promise
			return $.Deferred(function( defer )
			{
				$.ajax({
					"url": $.zlux.url.ajax('zlux', 'deleteObject'),
					"data": aoData,
					"dataType": "json",
					"type": "post"
				})
				
				.done(function(json) {
					if (json.result) {

						defer.resolve();

						// trigger event
						$this.trigger("FileDeleted", path);

					} else {
						// failed with reported error
						defer.reject(json.errors);
					}
				})

				.fail(function(){
					// some unreported error
					defer.reject($this._('SOMETHING_WENT_WRONG'));
				});

			}).promise();
		},
		/**
		 * Change the server Object name
		 */
		createFolder: function(name) {
			var $this = this,
				aoData = [],

			// save folder path
			path = $this.getFullPath(name);

			// push the storage related data
			aoData = $this.pushStorageData(aoData);

			// if S3 storage
			if($this.options.storage === 's3') {
				// add a slash, needed for folders
				path = path + '/';
			}

			// set paths
			aoData.push({ "name": "path", "value": path });

			// make the request and return a promise
			return $.Deferred(function( defer )
			{
				$.ajax({
					"url": $.zlux.url.ajax('zlux', 'newFolder'),
					"data": aoData,
					"dataType": "json",
					"type": "post"
				})
				
				.done(function(json) {
					if (json.result) {

						defer.resolve(json.name);
					} else {
						// failed with reported error
						defer.reject(json.errors);
					}
				})

				.fail(function(){
					// some unreported error
					defer.reject($this._('SOMETHING_WENT_WRONG'));
				});

			}).promise();
		},
		/**
		 * Perform the actions related to rename the Object
		 */
		renameObject: function($object) {
			var $this = this,
				name = $('.zlux-x-name a', $object.dom),
				ext = name.html().match(/\.+[a-zA-Z]+$/g),
				raw_name = name.html().replace(/\.+[a-zA-Z]+$/g, '');

			// if is folder ignore the extension
			ext = ext !== null ? ext : '';

			// prepare and display the confirm message
			var msg = $('<div>' + $this._('INPUT_THE_NEW_NAME') + '<br /><input class="zlux-x-input" type="text" value="' + raw_name + '" /> ' + ext + '<br /><span class="label label-success label-link">' + $this._('CONFIRM').toLowerCase() + '</span></div>')

			// confirm action
			.on('click', '.label-link', function(){
				// only allowed to be submited once
				if ($(this).data('submited')) return; $(this).data('submited', true);

				// set spinner
				$('.column-icon i', $object.dom).addClass('icon-spinner icon-spin');

				// change the object name
				$this._changeObjectName($object, $('input', msg).val() + ext)
				
				// if succesfull
				.done(function(new_name)
				{
					// update the dom name
					name.html(new_name);
					// in details also
					$('.zlux-x-details-content ul li:first span', $object.dom).html(new_name);

					// and path data
					$object.dom.attr('data-id', new_name);

					// update the object data, and as it's a reference to DataTables data it will be also updated :)
					$object.name = new_name;
					$object.basename = new_name.replace(/\.+[a-zA-Z]+$/g, '');
					$object.path = $object.path.replace(/(\w|[-.])+$/, new_name);

					// update the cache
					$.zlux.filesManager.aAjaxDataCache[$this.sCurrentPath].aaData = $this.oTable.fnGetData();

					// redraw the other instances
					$this.redrawInstances();

					// remove selected status as the object has changed
					$object.dom.removeAttr('data-zlux-object-status');

					// remove msg
					$('.zlux-x-msg', $object.dom).remove();
				})

				// if fails
				.fail(function(message) {
					$this.pushMessageToObject($object, message);
				})

				// on result
				.always(function() {
					// remove spinner
					$('.column-icon i', $object.dom).removeClass('icon-spinner icon-spin');
				});
			})

			.on('keypress', 'input', function(e){
				var code = (e.keyCode ? e.keyCode : e.which);
				if (code === 13) {
					// Enter key was pressed, emulate click event
					$('.label-link', msg).trigger('click');
				}
			});

			$this.pushMessageToObject($object, msg);
		},
		/**
		 * Requests the Object name change
		 */
		_changeObjectName: function($object, name) {
			var $this = this,
				aoData = [],
				src = $this.getFullPath($object.name),
				dest = $this.getFullPath(name);

			// push the storage related data
			aoData = $this.pushStorageData(aoData);

			// if S3 storage
			if($this.options.storage === 's3') {

				// add a slash if folder
				if($object.type === 'folder') {
					src = src + '/';
					dest = dest + '/';
				}
			}

			// set paths
			aoData.push({ "name": "src", "value": src });
			aoData.push({ "name": "dest", "value": dest });

			// make the request and return a promise
			return $.Deferred(function( defer )
			{
				$.ajax({
					"url": $.zlux.url.ajax('zlux', 'moveObject'),
					"data": aoData,
					"dataType": "json",
					"type": "post"
				})
				
				.done(function(json) {
					if (json.result) {

						defer.resolve(json.name);
					} else {
						// failed with reported error
						defer.reject(json.errors);
					}
				})

				.fail(function(){
					// some unreported error
					defer.reject($this._('SOMETHING_WENT_WRONG'));
				});

			}).promise();
		},
		/**
		 * Returns the full path prepended to the passed relative path
		 */
		getFullPath: function(path) {
			var cp = this.sCurrentPath;
			return cp ? cp + '/' + path : path;
		},
		/**
		 * Returns the oTable row related to the provided path
		 */
		_getRowFromPath: function(path) {
			var $this = this;
			return $('tr[data-path="' + path + '"]', $this.oTable);
		},
		/**
			Parses the specified size string into a byte value. For example 10kb becomes 10240.
			
			@method parseSize
			@static
			@param {String/Number} size String to parse or number to just pass through.
			@return {Number} Size in bytes.
		*/
		parseSize: function(size) {
			if (typeof(size) !== 'string' || size === '') return size;

			var muls = {
					t: 1099511627776,
					g: 1073741824,
					m: 1048576,
					k: 1024
				},
				mul;

			size = /^([0-9]+)([mgk]?)$/.exec(size.toLowerCase().replace(/[^0-9mkg]/g, ''));
			mul = size[2];
			size = +size[1];
			
			if (muls.hasOwnProperty(mul)) {
				size *= muls[mul];
			}
			return size;
		},
		/**
		 * Push the necesary storage DATA
		 */
		pushStorageData: function(aoData) {
			var $this = this;

			// if S3 storage
			if($this.options.storage === 's3') {
				aoData.push({ "name": "storage", "value": "s3" });
				aoData.push({ "name": "accesskey", "value": $this.options.storage_params.accesskey });
				aoData.push({ "name": "key", "value": $this.options.storage_params.secretkey });
				aoData.push({ "name": "bucket", "value": $this.options.storage_params.bucket });
			}

			return aoData;
		}
	});
	// save the plugin for global use
	$.zlux[Plugin.prototype.name] = Plugin;
	$.zlux[Plugin.prototype.name].iNextUnique = 0;
	$.zlux[Plugin.prototype.name].aAjaxDataCache = {};
	$.zlux[Plugin.prototype.name].instances = {};
})(jQuery, window, document);


/* ===================================================
 * ZLUX filesDialogManager
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
		$this.options = $.extend({}, Plugin.prototype.options, $.zlux.filesManager.prototype.options, options);
		this.events = {};

		// init the script
		$this.initialize();

		$this.element.data(Plugin.prototype.name, $this);
	};
	$.extend(Plugin.prototype, $.zlux.filesManager.prototype, {
		name: 'filesDialogManager',
		options: {
			title: 'Files Manager',
			full_mode: 0,
			dialogClass: ''
		},
		initialize: function() {
			var $this = this;

			// run initial check
			$this.initCheck();

			// element example, it should be set by the caller script
			// $('<a title="' + $this.options.title + '" class="btn btn-mini zlux-btn-edit" href="#"><i class="icon-edit"></i></a>');

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
			$this.options.dialogClass = 'zl-bootstrap zlux-filesmanager' +
				($this.options.full_mode ? ' zlux-dialog-full ' : '') +
				($this.options.dialogClass ? ' ' + $this.options.dialogClass : '');

			// load assets
			$.zlux.assets.load('dialog').done(function(){

				// init the dialog plugin
				$this.zluxdialog = $.zlux.dialog({
					title: $this.options.title,
					width: $this.options.full_mode ? '75%' : 300,
					dialogClass: $this.options.dialogClass,
					position: ($this.options.full_mode === false ? {
						of: $this.element,
						my: 'left top',
						at: 'right bottom'
					} : null)
				})
			
				.bind("InitComplete", function() {

					// set the dialog unique ID
					$this.zluxdialog.widget.attr('id', 'zluxFilesManager_' + $this.ID);

					// init dialog related functions
					$this.eventDialogLoaded();
				});
			});
		},
		/**
		 * Trigger functions when Dialog ready
		 */
		eventDialogLoaded: function() {
			var $this = this;

			// init filesmanager
			$this.filesmanager = $('<div class="zlux-filesmanager" />').appendTo($this.zluxdialog.content);
			$this.initDataTable($this.filesmanager);

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

			// trigger Object rename event on click
			$this.zluxdialog.main.on('click', '.icon-edit-sign', function(){
				var object_dom = $(this).closest('tr.zlux-object'),
					$object;

				// set zlux object
				$object = $this.oTable.fnGetData( object_dom[0] );
				$object.dom = object_dom;

				// rename
				$this.renameObject($object);
			});

			// set global close event
			$('html').on('mousedown', function(event) {
				// close if target is not the trigger or the dialog it self
				if ($this.zluxdialog.dialog('isOpen') && !$this.element.is(event.target) &&
						!$this.element.find(event.target).length && !$this.zluxdialog.widget.find(event.target).length) {

					$this.zluxdialog.dialog('close');
				}
			});


			// init main toolbar
			$this.initMainToolbar();

			// init subtoolbars
			$this.zluxdialog.newSubToolbar('filter', 'main');
			$this.zluxdialog.newSubToolbar('newfolder', 'main');

			// init the uploaded
			$this.initUploader();
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

				// move the search field to the toolbar
				$('.zlux-x-filter-input_wrapper', $this.oTable.fnSettings().nTableWrapper).appendTo(subtoolbar);

				// init breadcrumb
				$this.initBreadcrumb();

				// show the content
				$this.zluxdialog.initContent();
			})

			// before Deleting file
			.bind("BeforeDeleteFile", function(manager, $object){
				// if allready message displayed, abort
				if ($('.zlux-x-details-message-actions')[0]) return;

				var deleteMSG = $object.type === 'folder' ? 'DELETE_THIS_FOLDER' : 'DELETE_THIS_FILE';

				// prepare and display the confirm message
				var msg = $('<div>' + $this._(deleteMSG) + '<span class="label label-warning label-link">' + $this._('CONFIRM').toLowerCase() + '</span></div>')

				// confirm action
				.on('click', '.label-link', function(){

					// only allowed to be submited once
					if ($(this).data('submited')) return; $(this).data('submited', true);

					// set spinner
					$('.column-icon i', $object.dom).addClass('icon-spinner icon-spin');

					// delete the file						
					var deleting = $this.deleteObject($object);
					
					// if succesfull
					deleting.done(function(){
						// hide the object
						$object.dom.fadeOut('slow', function(){
							// remove object from dom
							$(this).remove();

							// remove the object from cache
							var aaData = $.zlux.filesManager.aAjaxDataCache[$this.sCurrentPath].aaData;

							$.each(aaData, function(i, value){
								if ($object.dom.data('id') === value.name && $object.dom.data('type') === value.type) {
									// found, remove
									aaData.splice(i, 1);

									// stop iteration
									return false;
								}
							});

							// redraw the other instances
							$this.redrawInstances();
						});
					})

					// if fails
					.fail(function(message) {
						// pushs the issue message
						$this.pushMessageToObject($object, message);
					})

					// on result
					.always(function() {
						// remove spinner
						$('.column-icon i', $object.dom).removeClass('icon-spinner icon-spin');
					});
				});

				// show the message
				$this.pushMessageToObject($object, msg);
			});

			// on object select example
			// .bind("ObjectSelected", function(manager, $object){
				// var value = $this.sCurrentPath + '/' + $object.name;

				// save new value in input
				// input.val(value).trigger('change');
			// });
		},
		/**
		 * Init the Main Dialog Toolbar
		 */
		initMainToolbar: function() {
			var $this = this;

			$this.zluxdialog.setMainToolbar(
				[{
					title: $this._('APPLY_FILTERS'),
					icon: "filter",
					click: function(tool){
						// toggle the subtoolbar visibility
						$this.zluxdialog.toggleSubtoolbar('filter', 'main');

						tool.parent().siblings().children('i').not(tool).removeClass('zlux-ui-tool-enabled');
						tool.toggleClass('zlux-ui-tool-enabled');
					}
				},{
					title: $this._('NEW_FOLDER'),
					icon: "folder-close",
					subicon: "plus-sign",
					click: function(tool){
						$this.zluxdialog.toggleSubtoolbar('newfolder', 'main');

						// toggle the subtoolbar visibility
						$('.zlux-dialog-subtoolbar-newfolder', $this.zluxdialog.toolbar.wrapper).html('').
						append(
							$('<input type="text" class="zlux-x-input" placeholder="' + $this._('FOLDER_NAME') + '" />').on('keypress', function(e){
								var code = (e.keyCode ? e.keyCode : e.which);
								if (code === 13) {
									// Enter key was pressed, create folder
									$this.createFolder($(this).val());

									// set spinner
									$this.zluxdialog.spinner('show');

									// start creating the folder
									$this.createFolder($(this).val())

									// on result
									.always(function() {
										$this.reload();
									});
								}
							})
						);

						tool.parent().siblings().children('i').not(tool).removeClass('zlux-ui-tool-enabled');
						tool.toggleClass('zlux-ui-tool-enabled');
					}
				},
				{
					title: $this._('UPLOAD_FILES'),
					icon: "cloud-upload",
					click: function(){
						// show the associated toolbar
						$this.zluxdialog.showToolbar(2);

						// disable dialog scroll
						$this.zluxdialog.scrollbar('hide');

						$('.zlux-filesmanager', $this.zluxdialog.content).fadeOut('400', function(){

							// init ZLUX Upload
							if (!$this.zluxupload.inited) $this.zluxupload.init();

							// update upload path
							$this.zluxupload.options.path = $this.sCurrentPath;

							// show the upload view
							$('.zlux-upload', $this.zluxdialog.content).fadeIn('400');
						});
					}
				},
				{
					title: $this._('REFRESH'),
					icon: "refresh",
					click: function(){
						$this.reload();
					}
				}]
			);
		},
		/**
		 * Init the Upload engine
		 */
		initUploader: function() {
			var $this = this;

			// set Upload toolbar
			$this.zluxdialog.newToolbar(
				[{
					title : $this._('ADD_NEW_FILES'),
					icon : "plus-sign",
					id : "add",
					click : function(){
						// already triggered by plupload
					}
				},
				{
					title : $this._('START_UPLOADING'),
					icon : "upload disabled",
					id : "start",
					click : function(){
						$this.zluxupload.uploader.start();
						return false;
					}
				},
				{
					title : $this._('CANCEL_CURRENT_UPLOAD'),
					icon : "ban-circle disabled",
					id : "cancel",
					click : function(){
						// cancel current queue upload
						$this.zluxupload.uploader.stop();

						// disable the btn
						$this.zluxdialog.toolbarBtnState(2, 'ban-circle', 'disabled');
						$this.zluxdialog.toolbarBtnState(2, 'upload', 'enabled');
						$this.zluxdialog.toolbarBtnState(2, 'plus-sign', 'enabled');
					}
				}],
				2,
				// back to main function
				function(){
					$('.zlux-upload', $this.zluxdialog.content).fadeOut('400', function(){

						// empty possible upload queue
						$this.zluxupload.emptyQueue();

						// show the filesmanager view
						$('.zlux-filesmanager', $this.zluxdialog.content).fadeIn('400');

						// refresh the uploader file list
						$this.zluxupload._updateFilelist();

						// refresh dialog scroll
						$this.zluxdialog.scrollbar('refresh');
					});
				}
			);

			// set upload engine
			$this.zluxupload = $.zlux.filesUpload({
				path: 'images',
				wrapper: $this.zluxdialog.content,
				storage: $this.options.storage,
				storage_params: $this.options.storage_params,
				max_file_size: $this.options.max_file_size,
				extensions: $this.options.extensions,
				browse_button: $('.zlux-dialog-toolbar-2 i[data-id="add"]', $this.zluxdialog.toolbar.wrapper),
				resize: $this.options.resize
			})

			// when queue files changes
			.bind('QueueChanged', function(){
				// refresh scroll
				$this.zluxdialog.scrollbar('refresh');
			})

			// when new file added to queue
			.bind('FilesAdded', function($up, files)
			{
				// toggle toolbar buttons
				$this.zluxdialog.toolbarBtnState(2, 'upload', 'enabled');

				// show the filelist
				$up.filelist.show();

				// add the file preview
				$.each(files, function(index, file) {

					// set initial status
					file.status = 'validating';

					// prepare object
					var $object = {
						name: file.name,
						basename: file.name.replace(/(\.[a-z0-9]+)$/, ''),
						type: 'file', // upload folders is not yet posible
						content_type: file.type,
						size: {size: file.size, display: plupload.formatSize(file.size)}
					};

					// render the dom
					$object.dom = $('<tr id="' + file.id + '" class="zlux-object" data-zlux-status="validating" />').append(

						$('<td class="column-icon" />').append('<i class="icon-file-alt zlux-x-object-icon" />'),

						$('<td class="column-name" />').append(
							$this.renderObjectDOM($object)
						)
					)

					// append to the file list
					.appendTo($up.filelist);

					// append the file upload progress bar
					$('.zlux-x-tools', $object.dom).append(
						$('<span class="zlux-upload-file-progress"/>')
					);

					// remove the name link, not needed here
					$('.zlux-x-name', $object.dom).html(file.name);

					// check file size
					if (file.size !== undefined && $this.options.max_file_size !== '' && file.size > $this.options.max_file_size) {
						// set icon
						$('.icon-file-alt', $object.dom).removeClass('icon-file-alt').addClass('icon-warning-sign');
						
						// set msg
						var msg = $this.pushMessageToObject($object, $this.sprintf($this._('FILE_SIZE_ERROR'), plupload.formatSize($this.options.max_file_size)));

						// delete the 'remove' msg option, as this message can not be ignored
						$('.zlux-x-msg-remove', msg).remove();

						// continue
						return true;
					}

					// validate file name
					$.ajax({
						"url": $.zlux.url.ajax('zlux', 'validateObjectName'),
						"type": 'post',
						"data":{
							name: file.name
						},
						"dataType": "json",
						"cache": false,
						"beforeSend": function(){
							// set spinner
							$('.column-icon i', $object.dom).addClass('icon-spinner icon-spin');
						},
						"success": function (json) {
							// update name
							$('.zlux-x-name', $object.dom).html(json.result);

							// update file name
							file.name = json.result;

							// ready to upload, set status
							file.status = 1; 

							// update file status
							$up._handleFileStatus(file);

							// remove spinner
							$('.column-icon i', $object.dom).removeClass('icon-spinner icon-spin');

							// refresh the filelist
							$up._updateFilelist();
						}
					});
				});
			})

			// toogle the buttons on upload events
			.bind('BeforeUpload', function(){
				$this.zluxdialog.toolbarBtnState(2, 'cancel', 'enabled');
				$this.zluxdialog.toolbarBtnState(2, 'start', 'disabled');
				$this.zluxdialog.toolbarBtnState(2, 'add', 'disabled');
			})

			// when file is uploaded
			.bind('FileUploaded', function(up, $object){

				// update the file name to reflect the final result
				$('.zlux-x-name', $object.dom).html($object.name);

				// update progress
				$('.zlux-upload-file-progress', $object.dom).html('100%').fadeOut();

				// set the OK icon
				$('.zlux-x-object-icon', $object.dom).removeClass('icon-file-alt').addClass('icon-ok');

				// set the link for inminent selection
				$('.zlux-x-name', $object.dom).wrapInner('<a class="zlux-x-name-link" href="#" />').end()

				.on('click', 'a', function(){

					// until all loaded return
					if ($this.zluxdialog.spinning) return false;

					// get the uploaded object dom from the files manager, keep the name unmodified
					var object_dom = $('.zlux-object[data-id="' + $object.name + '"]', $this.filesmanager);

					// get updated object data
					$object = $this.oTable.fnGetData( object_dom[0] );

					// trigger event 
					$this.trigger("ObjectSelected", $object);

					return false;
				});
			})

			// when file upload fails
			.bind('FileNotUpload', function(up, $object, message){

				// toogle toolbar buttons
				$this.zluxdialog.toolbarBtnState(2, 'cancel', 'disabled');
				$this.zluxdialog.toolbarBtnState(2, 'start', 'disabled');
				$this.zluxdialog.toolbarBtnState(2, 'add', 'enabled');

				// remove progress indication
				$('.zlux-upload-file-progress', $object.dom).fadeOut();

				// set the fail icon
				$('.zlux-x-object-icon', $object.dom).removeClass('icon-file-alt').addClass('icon-warning-sign');

				// render the message
				var msg = $this.pushMessageToObject($object, message);

				// delete the 'remove' msg option, as this message can not be ignored
				$('.zlux-x-msg-remove', msg).remove();
			})

			// when all files uploaded
			.bind('UploadComplete', function(){
				// toogle the toolbar buttons
				$this.zluxdialog.toolbarBtnState(2, 'cancel', 'disabled');
				$this.zluxdialog.toolbarBtnState(2, 'start', 'disabled');
				$this.zluxdialog.toolbarBtnState(2, 'add', 'enabled');

				// reload the table data
				$this.reload();
			})

			// when uploading canceled by the user
			.bind('CancelUpload', function(){
				// toogle the toolbar buttons
				$this.zluxdialog.toolbarBtnState(2, 'cancel', 'disabled');
				$this.zluxdialog.toolbarBtnState(2, 'add', 'enabled');
			})

			// when the queue changes
			.bind('QueueChanged', function(up, files){
				var queued = false;

				// foreach file
				$.each(files, function(index, file) {
					
					// check if there are files left to upload
					if (file.status !== plupload.DONE && file.status !== 'validating') {
						queued = true;
					}
				});

				// if no files left
				if (!files.length) {

					// disable the upload btn
					$this.zluxdialog.toolbarBtnState(2, 'start', 'disabled');

				// if queued files left
				} else if (queued) {

					// enable the upload btn
					$this.zluxdialog.toolbarBtnState(2, 'start', 'enabled');

				// if uploaded files left
				} else {

					// disable the upload btn
					$this.zluxdialog.toolbarBtnState(2, 'start', 'disabled');
				}
			})

			// listen to File Errors event
			.bind("FileError", function($up, $object, message) {

				// resolve the uploading deferrer, if any
				if ($this.uploading && $this.uploading.state() === 'pending') {
					$this.uploading.reject(message);

					return;
				}

				if (!$object.dom[0]) {

					// render the dom
					$object.dom = $('<tr id="' + $object.id + '" class="zlux-object" />').append(

						$('<td class="column-icon" />').append('<i class="icon-file-alt zlux-x-object-icon" />'),

						$('<td class="column-name" />').append(
							$this.renderObjectDOM($object)
						)
					)

					.appendTo($up.filelist);

					// refresh the filelist
					$up._updateFilelist();
				}

				// set the fail icon
				$('.zlux-x-object-icon', $object.dom).removeClass('icon-file-alt').addClass('icon-warning-sign');

				// render the message
				var msg = $this.pushMessageToObject($object, message);

				// delete the 'remove' msg option, as this message can not be ignored
				$('.zlux-x-msg-remove', msg).remove();

				// set status, necesary?
				// $file.attr('data-zlux-status', 'error');
			});
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);


/* ===================================================
 * ZLUX filesUpload
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
		name: 'filesUpload',
		options: {
			extensions: '', // Array or comma separated values
			path: null,
			fileMode: 'files',
			max_file_size: '1024kb', // Maximum file size. This string can be in 100b, 10kb, 10mb, 1gb format.
			wrapper: null,
			storage: 'local', // local, s3
			storage_params: {},
			browse_button: null,
			resize: {}
		},
		init: function() {
			var $this = this;

			// append the upload to the wrapper
			$this.upload = $('<div class="zlux-upload" />').attr('data-zlux-status', '').appendTo($this.options.wrapper)

			// start hiden
			.hide();

			// set the dropzone
			$this.dropzone = $('<div class="zlux-upload-dropzone" />').appendTo($this.upload).append(
				$('<span class="zlux-upload-dropzone-msg" />')
			);

			// init DnD events
			$this.initDnDevents();

			// bind DnD events
			$this.bind("WindowDragHoverStart", function() {
				// set draghover attr
				$this.dropzone.attr('data-zlux-draghover', true);
			});

			$this.bind("WindowDragHoverEnd", function() {
				// set draghover attr
				$this.dropzone.attr('data-zlux-draghover', false);
			});

			$this.initFilelist();


			// load asset
			$.zlux.assets.load($.zlux.url.zlfw('zlux/assets/plupload/plupload.full.min.js'), function(){

				// init plupload
				$this.initPlupload();

				// on Plupload init
				$this.bind("Init", function() {

					// add drop msg if html5
					if ($this.uploader.runtime === 'html5') {
						$('.zlux-upload-dropzone-msg', $this.dropzone).html($this.sprintf($this._('DROP_FILES'), 'zlux-upload-browse'))

						.on('click', 'a', function(){
							// trigger the upload browse button
							$this.options.browse_button.trigger('click');
							return false;
						});
					}

					// set init state
					$this.inited = true;
				});
			});
		},
		/*
		 * Init Filelist
		 */
		initFilelist: function() {
			var $this = this;

			$this.filelist =
			$('<table cellpadding="0" cellspacing="0" border="0" class="zlux-upload-filelist table table-striped table-bordered"><tbody /></table>')
			.appendTo($this.upload)

			// remove file from files function
			.on('click', '.zlux-x-remove', function(){

				// abort if it's being uploaded
				if ($(this).closest('.zlux-object').data('zlux-status') === 'uploding') return;

				var $object = $(this).closest('.zlux-object'),
					file = $this.uploader.getFile($object[0].id);

				// proceede if file is not being uploaded currently
				// or if file undefined, could happen if file added twice but deleted once
				if (file && (file === 'undefined' || file.zlux_status !== plupload.STARTED && file.status !== plupload.UPLOADING)) {

					// remove from upload queue
					$this.uploader.removeFile(file);

					// remove from dom
					$object.remove();
				} else {
					// just remove from dom
					$object.remove();

					// refresh list
					$this._updateFilelist();
				}
			});
		},
		/*
		 * Init the Plupload plugin
		 */
		initPlupload: function() {
			var $this = this;

			// set basics params
			var params = {
				runtimes: 'html5, flash',
				browse_button: $this.options.browse_button[0],
				drop_element: $this.dropzone[0], 
				max_file_size: undefined, // controlled by ZLUX Upload
				url: $.zlux.url.ajax('zlux', 'upload'),
				filters: [ // Specify what files to browse for
					{title: "Files", extensions: $this.options.extensions}
				],

				// flash runtime settings
				flash_swf_url: $.zlux.url.zlfw('zlux/assets/plupload/Moxie.swf')
			};

			// if S3 storage
			if($this.options.storage === 's3') {
				$.extend(params, {
					url: 'http://' + $this.options.storage_params.bucket + '.s3.amazonaws.com',
					multipart: true,
					multipart_params: {
						'key': '${filename}', // use filename as a key
						'Filename': '${filename}', // adding this to keep consistency across the runtimes
						'acl': 'public-read',
						'success_action_status': '201',
						'AWSAccessKeyId': $this.options.storage_params.accesskey,
						'policy': $this.options.storage_params.policy,
						'signature': $this.options.storage_params.signature
					},
					file_data_name: 'file' // optional, but better be specified directly
				});
			}

			// if Image Resize
			if(!$.isEmptyObject($this.options.resize) &&
				($this.options.resize.width !== '' || $this.options.resize.height !== '')) {

				$.extend(params, {
					resize: {
						width: $this.options.resize.width,
						height: $this.options.resize.height,
						crop: $this.options.resize.crop == '1' ? true : false
					}
				});
			}

			// Post init events, bound after the internal events
			$.extend(params, {
				init : {
					BeforeUpload: function(up, file) {
						$this.eventBeforeUpload(file);
					},
					UploadFile: function(up, file) {
						$this.eventUploadFile(file);
					},
					UploadProgress: function(up, file) {
						$this.eventUploadProgress(file);
					},
					FileUploaded: function(up, file, info) {
						$this.eventFileUploaded(file, info);
					},
					UploadComplete: function(up, files) {
						$this.eventUploadComplete(files);
					},
					CancelUpload: function() {
						$this.eventCancelUpload();
					},
					FilesAdded: function(up, files) {
						$this.eventFilesAdded(files);
					},
					QueueChanged: function() {
						$this.eventQueueChanged();
					},
					StateChanged: function() {
						$this.eventStateChanged();
					},
					Error: function(up, err) {
						$this.eventError(err);
					}
				}
			});

			// set the Plupload uploader
			$this.uploader = new plupload.Uploader(params);

			// workaround to trigger the Init event
			// perhaps Plupload bug but it's not working as the others
			$this.uploader.bind("Init", function(){
				$this.trigger("Init");
			});

			// init the plupload uploader
			$this.uploader.init();
		},
		/*
		 * Fires when a error occurs.
		 */
		eventError: function(err) {
			var $this = this,
				file = err.file,
				message,
				details;

			// file related errors
			if (file) {
				message = '<strong>' + err.message + '</strong>';
				details = err.details;
				
				if (details) {
					message += " <br /><i>" + err.details + "</i>";
				} else {
					
					switch (err.code) {
						case plupload.FILE_EXTENSION_ERROR:
							details = $this._('FILE_EXT_ERROR').replace('%s', file.name);
							break;
						
						case plupload.IMAGE_MEMORY_ERROR :
							details = $this._('RUNTIME_MEMORY_ERROR');
							break;
													
						case plupload.HTTP_ERROR:

							// if S3 storage
							if($this.options.storage === 's3') {
								
								if ($this.options.storage_params.bucket.match(/\./g)) {
									// When using SLL the bucket names can't have dots
									details = $this._('S3_BUCKET_PERIOD_ERROR');
								} else {
									details = $this._('S3_BUCKET_MISSCONFIG_ERROR');
								}

							// if local storage
							} else {
								details = $this._('UPLOAD_URL_ERROR');
							}
							break;
					}
					message += " <br /><i>" + details + "</i>";
				}

				// prepare object
				var $object = {
					name: file.name,
					basename: file.name.replace(/(\.[a-z0-9]+)$/, ''),
					type: 'file', // upload folders is not yet posible
					content_type: file.type,
					size: {size: file.size, display: plupload.formatSize(file.size)}
				};

				// add the file dom
				$object.dom = $('#' + file.id, $this.filelist);
				
				// trigger file error event
				$this.trigger("FileError", $object, message);
			}
		},
		/*
		 * Fires when the overall state is being changed for the upload queue.
		 */
		eventStateChanged: function() {
			var $this = this;

			// update the zlux upload status
			if ($this.uploader.state === plupload.UPLOADING) {
				$this.upload.attr('data-zlux-status', 'uploading');
			}

			if ($this.uploader.state === plupload.STOPPED) {
				$this.upload.attr('data-zlux-status', 'stopped');

				// refresh the file list
				$this._updateFilelist();
			}
		},
		/*
		 * Fires when just before a file is uploaded.
		 */
		eventBeforeUpload: function(file) {
			var $this = this,
				$file = $('#' + file.id, $this.filelist);
			
			// if local storage
			if($this.options.storage === 'local') {
				// update the upload path
				$this.uploader.settings.url = $this.uploader.settings.url + '&path=' + $this.options.path;
			}

			// if S3 storage
			if($this.options.storage === 's3') {
				// update the upload path and file name
				var folder = $this.options.path ? $this.options.path + '/' : '';
				$this.uploader.settings.multipart_params.key = folder + file.name;
			}

			// set progress to 0
			$('.zlux-upload-file-progress', $file).html('0%');

			// set the started status
			file.zlux_status = 2;

			// update status
			$this._handleFileStatus(file);

			// change the buttons/icons
			$('.zlux-upload-file-btn-remove', $file).removeClass('icon-remove').addClass('icon-spinner icon-spin');

			// trigger event
			$this.trigger("BeforeUpload", file);
		},
		/*
		 * Fires when a file is to be uploaded by the runtime.
		 */
		eventUploadFile: function(file) {
			var $this = this;

			// prepare object
			var $object = {
				name: file.name,
				basename: file.name.replace(/(\.[a-z0-9]+)$/, ''),
				type: 'file', // upload folders is not yet posible
				content_type: file.type,
				size: {size: file.size, display: plupload.formatSize(file.size)}
			};

			// add the file dom
			$object.dom = $('#' + file.id, $this.filelist);

			// create and save the upload deferrer
			$this.uploading = $.Deferred()

			// if the upload fails
			.fail(function(msg){
				// trigger file error event
				$this.trigger("FileNotUpload", $object, msg);
			})

			// if succeeds
			.done(function(result){

				// update the file name
				$object.name = result;

				// trigger event
				$this.trigger("FileUploaded", $object);
			})

			// always
			.always(function(){
				// update file status
				$this._handleFileStatus(file);
			});
		},
		/*
		 * Fires while a file is being uploaded.
		 */
		eventUploadProgress: function(file) {
			var $this = this,

			// avoid the NaN value
			percentage = isNaN(file.percent) ? 0 : file.percent;

			// upload the progress info
			$('#' + file.id + ' .zlux-upload-file-progress', $this.filelist).html(percentage + '%');

			// update status
			$this._handleFileStatus(file);
		},
		/*
		 * Fires when a file is successfully uploaded.
		 */
		eventFileUploaded: function(file, info) {
			var $this = this,
				response;

			// if local storage
			if($this.options.storage === 'local') {
				response = $.parseJSON(info.response);

				// resolve/reject the deferrer
				if (response.error) {
					$this.uploading.reject(response.error.message);
				} else {
					$this.uploading.resolve(response.result);
				}
			}

			// if s3 storage
			else if($this.options.storage === 's3') {
				response = $(info.response);

				// resolve/reject the deferrer
				$this.uploading.resolve(response.find('Key').html());
			}
		},
		/*
		 * Fires when all files in a queue are uploaded.
		 */
		eventUploadComplete: function(file) {
			var $this = this;

			// trigger event
			$this.trigger("UploadComplete", file);
		},
		/*
		 * Fires when the uploading is canceled by the user.
		 */
		eventCancelUpload: function() {
			var $this = this;

			// trigger event
			$this.trigger("CancelUpload");
		},
		/*
		 * Fires while when the user selects files to upload.
		 */
		eventFilesAdded: function(files) {
			var $this = this;

			// trigger event
			$this.trigger("FilesAdded", files);
		},
		/*
		 * Fires when the file queue is changed.
		 */
		eventQueueChanged: function() {
			var $this = this;

			// refresh the filelist
			$this._updateFilelist();
		},
		/*
		 * Get files yet to be uploaded
		 */
		getQueuedFiles: function() {
			var $this = this,
				files = [];

			// add the file preview
			$.each($this.uploader.files, function(index, file) {
				if (file.status !== plupload.DONE && file.status !== 'validating') files.push(file);
			});

			return files;
		},
		/*
		 * Empty the file queue and dom
		 */
		emptyQueue: function() {
			var $this = this;

			// removes all file froms queue and dom
			$this.uploader.splice();
			$this.filelist.empty();
		},
		_updateFilelist: function() {
			var $this = this,
				queued = false,
				objects = $('.zlux-object', $this.filelist); // there could be empty objects, wrong file ext for ex

			// foreach file
			$.each($this.uploader.files, function(index, file) {
				
				// check if there are files left to upload
				if (file.status !== plupload.DONE && file.status !== 'validating') {
					queued = true;
				}

				// check for stopped files
				if (file.status === plupload.STOPPED) {
					// refresh file statut
					$this._handleFileStatus(file);
				}
			});

			// if files left
			if ($this.uploader.files.length || objects.length) {
				// hide the dropzone msg
				$('.zlux-upload-dropzone-msg', $this.upload).hide();
			}

			// if no files left
			if (!$this.uploader.files.length && !objects.length) {

				// update the upload status
				$this.upload.attr('data-zlux-status', '');

				// show the dropzone message
				$('.zlux-upload-dropzone-msg', $this.upload).fadeIn();

			// if queued files left
			} else if (queued) {

				// update the upload status
				$this.upload.attr('data-zlux-status', 'queued');

			// if uploaded files left
			} else {

				// update the upload status
				$this.upload.attr('data-zlux-status', 'stopped');
			}

			// fire queue event
			$this.trigger("QueueChanged", $this.uploader.files);
		},
		_handleFileStatus: function(file) {
			var $this = this,
				$file = $('#' + file.id, $this.filelist),
				status = '';

			// check custom status
			if (file.zlux_status === plupload.STARTED) {
				status = 'started';

				// unset the status to avoid further conflicts
				file.zlux_status = '';

			// else check default status
			} else {

				if (file.status === plupload.DONE) {
					status = 'done';
				}

				if (file.status === plupload.FAILED) {
					status = 'failed';
				}

				if (file.status === plupload.QUEUED) {
					status = 'queued';
				}

				if (file.status === plupload.UPLOADING) {
					status = 'uploading';
				}

				if (file.status === plupload.STOPPED) {
					// reset the file upload progress
					$('.zlux-upload-file-progress', $file).html('');
				}
			}

			// set the file status
			$file.attr('data-zlux-status', status);
		},
		/**
			Init the Drag and Drop events

			In order to normalize the window in/out for File dragging a jQuery collection $() is used to keep track of what events were fired on what elements. The event.target is added the collection whenever dragenter was fired and removed whenever dragleave happened. The idea is if the collection is empty it means we have actually left the original element because if we were entering a child element at least one element (the child) would still be in the jQuery collection. This workaround doesn't work with Plupload DnD declared element, additional events must be used instead.

			Original idea - http://stackoverflow.com/a/10310815/698289
		*/
		initDnDevents: function() {
			var $this = this,
			collection = $(),
			dz_collection = $(),
			inWindow = false,
			inDZ = false;

			// Make sure if we drop something on the page we don't navigate away
			$(window).on("drop", function(e) {
				e.preventDefault();
				return false;
			})

			// when enter the window draging a file, fire the event
			.on('dragenter', function(e) {

				if (collection.size() === 0) {
					$this.trigger('WindowDragHoverStart');
				
					// update zones
					inWindow = true;
					inDZ = false;
				}

				collection = collection.add(e.target);
			})

			// when leave the window or drop a file on it, fire the event
			.on('dragleave drop', function(e) {

				// timeout is needed because Firefox 3.6 fires the dragleave event on
				// the previous element before firing dragenter on the next one
				setTimeout(function() {
					var isChild = false;

					// FF workaround, in order to avoid permission errors dragging outside the body, use the try-catch
					// to check if the relatedTarget is a child of the body
					try {
						isChild = $('body').find(e.relatedTarget).length ? true : isChild;
					}
					catch(err){} // do nothing

					// remove target from collection
					collection = collection.not(e.target);

					
					if (collection.size() === 0 && !isChild) {
						inWindow = false;
					}
				}, 1);

				// check a while later if both zones are left
				setTimeout(function() {
					if(!inWindow && !inDZ){
						$this.trigger('WindowDragHoverEnd');
						dz_collection = $();
						collection = $();
					} 
				}, 2);
			});


			// because of plupload events on the dropzone, it's considered like new window, so must be checked separatly
			$this.dropzone.on('dragenter', function(e) {

				if (dz_collection.size() === 0) {
					$this.trigger('WindowDragHoverStart');
					
					// update zones
					inWindow = false;
					inDZ = true;
				}

				dz_collection = dz_collection.add(e.target);
			});

			$this.dropzone.on('dragleave drop', function(e) {

				setTimeout(function() {
					var isChild = false;

					// FF workaround, in order to avoid permission errors dragging outside the body, use the try-catch
					// to check if the relatedTarget is a child of the body
					try {
						isChild = $('body').find(e.relatedTarget).length ? true : isChild;
					}
					catch(err){} // do nothing

					// remove target from collection
					dz_collection = dz_collection.not(e.target);

					// this event could be prevented, once each time
					if (dz_collection.size() === 0 && !isChild) {
						inDZ = false;
					}

				}, 1);

				// check a while later if both zones are left
				setTimeout(function() {
					if(!inWindow && !inDZ){
						$this.trigger('WindowDragHoverEnd');
						dz_collection = $();
						collection = $();
					}
				}, 2);
			});
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = function() {
		var args = arguments;
		return new Plugin(args[0] ? args[0] : {});
	};
})(jQuery, window, document);


/* ===================================================
 * ZLUX filesPreview
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
		name: 'filesPreview',
		renderPreviewDOM: function(oData, preview) {
			var $this = this,
				sThumb,
				aDetails = [];

			// set defaults
			preview = preview === undefined ? false : preview;
			oData.size = oData.size === undefined ? false : oData.size;

			// set name
			aDetails.push({name: 'name', value: oData.basename});

			// prepare the details
			if (oData.type === 'folder') {
				sThumb = '<span class="zlux-x-folder"></span>';
			} else { // file

				// if preview enabled render a mini preview of the file
				if (preview) {
					sThumb = '<div class="zlux-x-image"><img src="' + $.zlux.url.root(oData.path) + '" /></div>';
				} else {
					sThumb = '<span class="zlux-x-filetype">' + oData.ext + '</span>';
				}

				// set size if available
				if (oData.size) aDetails.push({name: 'size', value: oData.size.display});
			}

			var sDetails = '';
			$.each(aDetails, function(i, detail){
				sDetails += '<li>' + detail.value + '</li>';
			});
				
			// set and return the DOM
			return $(
				'<div class="zlux-preview">' +
					// thumbnail
					'<div class="zlux-x-thumbnail">' +
						sThumb +
					'</div>' +

					// details
					'<ul class="zlux-x-details unstyled">' + sDetails + '</ul>' +
				'</div>'
			);
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = function() {
		var args = arguments;
		return new Plugin(args[0] ? args[0] : {});
	};
})(jQuery, window, document);