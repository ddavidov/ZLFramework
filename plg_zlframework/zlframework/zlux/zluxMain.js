/* ===================================================
 * ZLUX
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
 ;(function ($, window, document, undefined) {
	"use strict";

	var zlux = $.zlux || {};

	if (zlux.fn) {
		return;
	}

	zlux.version = '1.0';
	zlux.zoo = {};


	/** URL **/
	zlux.url = {};
	zlux.url._root = ''; // populated by zlux helper
	zlux.url.root = function(url) {
		// make sure the url has no the root_path on it
		var patt = new RegExp('^'+zlux.url._root_path);
		url = zlux.utils.toType(url) == 'string' ? url.replace(patt, '') : ''; 
		// rturn the root + the cleaned url
		return zlux.url._root + url;
	};
	zlux.url._base = ''; // populated by zlux helper
	zlux.url.base = function(url) {
		return zlux.url._base + (url ? url : '');
	};
	zlux.url._zlfw = 'plugins/system/zlframework/zlframework/';
	zlux.url.zlfw = function(url) {
		return zlux.url._zlfw + (url ? url : '');
	};
	zlux.url.ajax = function(controller, task, params) {
		var params = params == undefined ? {} : params,
			app_id = params.app_id ? params.app_id : zlux.zoo.app_id,
			option = params.option ? params.option : ( zlux.com_zl ? 'com_zoolanders' : 'com_zoo' );

		// avoid repeating main params
		delete params.option;
		delete params.app_id;

		// prepare the url
		var url = zlux.url._base + 'index.php?option=' + option
		+ '&controller=' + controller
		+ '&task=' + task
		+ (app_id ? '&app_id=' + app_id : '')
		+ '&format=raw'
		+ ($.isEmptyObject(params) ? '' : '&' + $.param(params));

		return url;
	};
	

	/** FN **/
	zlux.fn = function(command, options) {
		var args = arguments, 
			cmd = command.match(/^([a-z\-]+)(?:\.([a-z]+))?/i), 
			plugin = cmd[1], 
			method = cmd[2];

		if (!zlux[plugin] && !zlux.utils[plugin]) {
			$.error("ZLUX plugin [" + plugin + "] does not exist.");
			return this;
		}

		return this.each(function() {
			if(zlux.utils[plugin]) {
				// if the plugin is an utility don't init, just execute
				zlux.utils[plugin](this, method, Array.prototype.slice.call(args, 1));
			} else {
				var $this = $(this), data = $this.data(plugin);
				if (!data) $this.data(plugin, (data = new zlux[plugin](this, method ? undefined : options)));
				if (method) data[method].apply(data, Array.prototype.slice.call(args, 1));
			}
			
		});
	};


	/** LANGUAGE **/
	zlux.lang = {};
	zlux.lang.strings = {};
	/**
	 * Add the language strings to the array
	 * @param {Object} strings Translated string in JSON format.
	 */
	zlux.lang.set = function(strings) {
		$.extend(zlux.lang.strings, strings);
	};
	/**
	 * Retrieves the specified language string
	 * @param {String} string String to look for.
	 * @return {String} Translated string or the input string if it wasn't found.
	 */
	zlux.lang.get = function(string) {
		return zlux.lang.strings[string] || string;
	};


	/** ASSETS **/
	zlux.assets = {};
	zlux.assets._ress = {}; // requested assets
	// store known assets for fast loading
	zlux.assets.known = {
		"dates":{
			"css":"plugins/system/zlframework/zlframework/zlux/datesmanager/style.css",
			"js":"plugins/system/zlframework/zlframework/zlux/datesmanager/plugin.js"
		},
		"dialog":{
			"css":"plugins/system/zlframework/zlframework/zlux/dialogmanager/style.css",
			"js":"plugins/system/zlframework/zlframework/zlux/dialogmanager/plugin.js"
		},
		"fields":{
			"css":"plugins/system/zlframework/zlframework/zlux/fieldsmanager/style.css",
			"js":"plugins/system/zlframework/zlframework/zlux/fieldsmanager/plugin.js"
		},
		"files":{
			"css":"plugins/system/zlframework/zlframework/zlux/managerfiles/style.css",
			"js":"plugins/system/zlframework/zlframework/zlux/managerfiles/plugin.js"
		},
		"items":{
			"css":"plugins/system/zlframework/zlframework/zlux/itemsmanager/style.css",
			"js":"plugins/system/zlframework/zlframework/zlux/itemsmanager/plugin.js"
		}
	};

	/**
	 * Load requested assets and execute callback
	 * @ress String or Array
	 */
	zlux.assets.load = function(ress, callback, failcallback) {
		var req  = [];

		// if is string check for related known ressources
		if(zlux.utils.toType(ress) == 'string' && $.zlux.assets.known[ress] != undefined){
			ress = $.map(zlux.assets.known[ress], function(value, index) {
				return [value];
			});
		}
		
		// clean vars
		ress = $.isArray(ress) ? ress:[ress];

		// load assets
		for (var i=0, len=ress.length; i<len; i++) {

			if(!ress[i]) continue;

			if (!zlux.assets._ress[ress[i]]) {
				if (ress[i].match(/\.js$/)) {
					zlux.assets._ress[ress[i]] = zlux.assets.getScript(zlux.url.root(ress[i]));
				} else {
					zlux.assets._ress[ress[i]] = zlux.assets.getCss(zlux.url.root(ress[i]));
				}
			}
			req.push(zlux.assets._ress[ress[i]]);
		}

		return $.when.apply($, req).done(callback).fail(function(){
			if (failcallback) {
				failcallback();
			} else {
				$.error("Require failed: \n" + ress.join(",\n"));
			}
		});
	};
	zlux.assets.getScript = function(url, callback) {
		var d = $.Deferred(), script = document.createElement('script');

		script.async = true;

		script.onload = function() {
			d.resolve();
			if(callback) { callback(script); }
		};

		script.onerror = function() {
			d.reject(url);
		};

		// IE 8 fix
		script.onreadystatechange = function() {
			if (this.readyState == 'loaded' || this.readyState == 'complete') {
				d.resolve();
				if(callback) { callback(script); }
			}
		}

		script.src = url;

		document.getElementsByTagName('head')[0].appendChild(script);

		return d.promise();
	};
	zlux.assets.getCss = function(url, callback){
		var d         = $.Deferred(),
			link      = document.createElement('link');
			link.type = 'text/css';
			link.rel  = 'stylesheet';
			link.href = url;

		document.getElementsByTagName('head')[0].appendChild(link);

		var img = document.createElement('img');
			img.onerror = function(){
				d.resolve();
				if(callback) callback(link);
			};
			img.src = url;

		return d.promise();
	};


	/** UTILS **/
	zlux.utils = {};
	// returns the object type
	zlux.utils.toType = function(obj) {
		return ({}).toString.call(obj).match(/\s([a-z|A-Z]+)/)[1].toLowerCase();
	};
	zlux.utils.options = function(string) {
		if ($.isPlainObject(string)) return string;

		var start = (string ? string.indexOf("{") : -1), options = {};
		if (start != -1) {
			try {
				options = (new Function("", "var json = " + string.substr(start) + "; return JSON.parse(JSON.stringify(json));"))();
			} catch (e) {}
		}

		return options;
	};


	/** SUPPORT **/
	zlux.support = {};
	zlux.support.touch = (
		('ontouchstart' in window && navigator.userAgent.toLowerCase().match(/mobile|tablet/)) ||
		(window.DocumentTouch && document instanceof window.DocumentTouch)  ||
		(window.navigator['msPointerEnabled'] && window.navigator['msMaxTouchPoints'] > 0) || //IE 10
		(window.navigator['pointerEnabled'] && window.navigator['maxTouchPoints'] > 0) || //IE >=11
		false
	);


	/** AJAX **/
	zlux.ajax = {};
	zlux.ajax.request = function(settings)
	{
		return $.Deferred(function( defer )
		{
			// set response object
			var response = {success:false, errors:[]};

			// request
			$.ajax(settings)

			// response recieved
			.done(function(json)
			{
				try {
					// json response is assumed
					var json = $.parseJSON(json);

					if (json.success)
						defer.resolve(json);
					else
						defer.reject(json);

				// handle exception
				} catch(e) {
					response.errors.push(String(json));
					response.errors.push('An server-side error occurred. ' + String(e));
					defer.reject(response);
				}
			})
			
			// something went wrong
			.fail(function(jqxhr, status, error)
			{
				// handle errors
				switch (jqxhr.status) {
					case 403:
						response.errors.push('The session has expired.');
						break;
					case 404:
						response.errors.push('The requested URL is not accesible.');
						break;
					case 500:
						response.errors.push('A server-side error has occurred.');
						break;

					default:
						response.errors.push('An error occurred: ' + status + 'nError: ' + error);
						break;
				}

				// set response status
				response.status = jqxhr.status;

				// reject
				defer.reject(response);
			});

		}).promise();
	}

	// set zlux
	$.zlux = zlux;
	$.fn.zlux = zlux.fn;
})(jQuery, window, document);


/* ===================================================
 * ZLUX Main
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(){};
	$.extend(Plugin.prototype, {
		name: 'Main',
		options: {},
		// var for internal events, must be reseted when expanding?
		events: {},
		/**
		 * Dispatches the specified event name and it's arguments to all listeners.
		 *
		 * @method trigger
		 * @param {String} name Event name to fire.
		 * @param {Object..} Multiple arguments to pass along to the listener functions.
		 */
		trigger : function(name) {
			var list = this.events[name.toLowerCase()], i, args;

			// Replace name with sender in args
			args = Array.prototype.slice.call(arguments);
			args[0] = this;

			// if event was binded
			if (list) {

				// Dispatch event to all listeners
				for (i = 0; i < list.length; i++) {
					// Fire event, break chain if false is returned
					if (list[i].func.apply(list[i].scope, args) === false) {
						return false;
					}
				}
			}

			// always trigger target for external binds with zlux. namespace
			if (this.element) this.element.trigger('zlux.' + name, args);

			return true;
		},
		/**
		 * Adds an event listener by name.
		 *
		 * @method bind
		 * @param {String} name Event name to listen for.
		 * @param {function} func Function to call ones the event gets fired.
		 * @param {Object} scope Optional scope to execute the specified function in.
		 */
		bind: function(names, func, scope){
			var $this = this;

			names.split(' ').each(function(name){
				var list;

				name = name.toLowerCase();
				list = $this.events[name] || [];
				list.push({func : func, scope : scope || $this});
				$this.events[name] = list;
			});

			// chaining
			return this;
		},
		/**
		 * Removes the specified event listener.
		 *
		 * @method unbind
		 * @param {String} name Name of event to remove.
		 * @param {function} func Function to remove from listener.
		 */
		unbind : function(name) {
			name = name.toLowerCase();

			var list = this.events[name], i, func = arguments[1];

			if (list) {
				if (func !== undefined) {
					for (i = list.length - 1; i >= 0; i--) {
						if (list[i].func === func) {
							list.splice(i, 1);
								break;
						}
					}
				} else {
					list = [];
				}

				// delete event list if it has become empty
				if (!list.length) {
					delete this.events[name];
				}
			}
		},
		/**
		 * Removes all event listeners.
		 *
		 * @method unbindAll
		 */
		unbindAll : function() {
			var $this = this;
			
			$.each($this.events, function(list, name) {
				$this.unbind(name);
			});
		},
		/**
		 * Check whether uploader has any listeners to the specified event.
		 *
		 * @method hasEventListener
		 * @param {String} name Event name to check for.
		 */
		hasEventListener : function(name) {
			return !!this.events[name.toLowerCase()];
		},
		/**
		 * Log an error message
		 *  @param {int} iLevel log error messages, or display them to the user
		 *  @param {string} sMesg error message
		 */
		_ErrorLog: function(iLevel, sMesg ) {
			var $this = this,
				sAlert = ($this.ID === undefined) ?
				$this.name + ": " + sMesg :
				$this.name + " warning (id = '" + $this.ID + "'): " + sMesg;

			if ( iLevel === 0 )
			{
				alert( sAlert );
				return;
			}
			else if ( window.console && console.log )
			{
				console.log( sAlert );
			}
		},
		/**
		 * Shortcut for translate function
		 *
		 * @param {String} str String to look for.
		 * @return {String} Translated string or the input string if it wasn't found.
		 */
		_: function(str) {
			return $.zlux.lang.get(str);
		},
		/**
		 * Pseudo sprintf implementation - simple way to replace tokens with specified values.
		 *
		 * @param {String} str String with tokens
		 * @return {String} String with replaced tokens
		 */
		sprintf: function(str) {
			var args = [].slice.call(arguments, 1), reStr = '';

			str.split(/%[sdf]/).forEach(function(part) {
				reStr += part;
				if (args.length) {
					reStr += args.shift();
				}
			});
			return reStr;
		},
		/**
		 * Clean a path from double / and others
		 *
		 * @method cleanPath
		 * @param {String} path The path to be cleaned
		 */
		cleanPath : function(path) {

			if (!path) return;
			
			// return path and
			return path

			// replace \ with /
			.replace(/\\/g, '/')

			// replace // with /
			.replace(/\/\//g, '/')

			// remove undefined
			.replace(/undefined/g, '')

			// remove / from end
			.replace(/\/$/g, '')

			// recover the http:// if set
			.replace(/:\//g, ':\/\/');
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);


/* ===================================================
 * ZLUX Manager
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(){};
	$.extend(Plugin.prototype, $.zlux.Main.prototype, {
		name: 'Manager',
		/**
		 * Reloads the Table content
		 */
		reload: function() {
			var $this = this,
				oSettings = $this.oTable.fnSettings();

			// set vars
			oSettings.bReloading = true;

			// reload
			$this.oTable.fnReloadAjax(oSettings.sAjaxSource);
		},
		/**
		 * Push a Message specific to the object and manage old ones
		 */
		pushMessageToObject: function($object, message) {

			// wrap if message is plain text
			if (typeof(message) === 'string') {
				message = $('<div>' + message + '</div>');
			}

			// if more than one message wrap in separate divs
			else if (message.length > 1) {
				$.each(message, function(i, v){
					message[i] = $('<div>' + v + '</div>');
				});
			}

			// get current siblings
			var siblings = $('.zlux-x-msg', $object.dom),

			// prepare message wrapper
			msg = $('<div class="zlux-x-details-content zlux-x-msg" />').hide()

			.append(
				// append message content
				message,

				// append remove feature
				$('<i class="zlux-x-msg-remove icon-remove" />').on('click', function(){
					msg.fadeOut();
				})
			)

			// add it to DOM
			.prependTo($('.zlux-x-details', $object.dom))

			// show it with effect
			.slideDown('fast', function(){

				// remove any msg sibling
				siblings.fadeOut('slow', function(){
					$(this).remove();
				});
			});

			return msg;
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);


/* ===================================================
 * ZLUX spin
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(element, method, args) {
		var $element = $(element);

		// set default method
		method = method == null ? 'on' : method;

		// call the method
		Plugin.prototype[method]($element, args);
	};
	$.extend(Plugin.prototype, $.zlux.Main.prototype, {
		name: 'spin',
		on: function($element, args) {
			var $this = this;

			$this.icon_class = false;

			// set options
			var $arg = args[0] ? args[0] : {},
				$options = {
					'class': $arg['class'] ? $arg['class'] : '',
					'affix': $arg.affix ? $arg.affix : 'append' // append, prepend or replace
				};

			// check for icon, use it if found
			if($('i', $element)[0]) {
				$this.icon_class = $('i', $element).attr('class');
				$('i', $element).attr('class', 'uk-icon-spinner uk-icon-spin');

			// create the icon if not
			} else if($options.affix == 'replace') {
				$element.html($('<i class="uk-icon-spinner uk-icon-spin"></i>').addClass($options['class']));
			} else {
				$element[$options.affix]($('<i class="uk-icon-spinner uk-icon-spin"></i>').addClass($options['class']));
			}
		},
		off: function($element, args) {
			var $this = this;

			// remove the spin classes but not the icon
			$('i', $element).removeClass('uk-icon-spinner uk-icon-spin');

			// recover class, if any
			if($this.icon_class) $('i', $element).attr('class', $this.icon_class);
		}
	});
	// Don't touch
	$.zlux.utils[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);


/* ===================================================
 * ZLUX animate
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(element, method, args) {
		var $element = $(element);

		// set default method
		method = method == null ? 'initialize' : method;

		// call the method
		Plugin.prototype[method]($element, args);
	};
	$.extend(Plugin.prototype, $.zlux.Main.prototype, {
		name: 'animate',
		initialize: function(element, args) {
			var $this = this,
				$element = element;

			var animation = args[0].split(' '),
				callback = args[1] ? args[1] : null;

			// add the uikit prefix
			animation = 'uk-animation-' + (animation.length == 1 ? animation : animation.join(' uk-animation-'));

			// animate the element with CSS3
			$element.addClass(animation).one('webkitAnimationEnd oanimationend msAnimationEnd animationend', function(e) {
				// remove the class to allow further animation
				$element.removeClass(animation);

				// execute any callback passing the element as scope
				if (callback) callback.apply($element);
			});
		}
	});
	// Don't touch
	$.zlux.utils[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);


/* ===================================================
 * ZLUX SaveElement
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(){};
	$.extend(Plugin.prototype, {
		name: 'zluxSaveElement',
		options: {
			msgSaveElement: 'Save Element',
			item_id: 0,
			elm_id: ''
		},
		initialize: function(element, options) {
			this.options = $.extend({}, this.options, options);
			var $this = this;

			// append the button
			$('<a class="btn btn-small save" href="javascript:void(0);" />')
			.append('<i class="icon-ok-sign"></i> '+$this.options.msgSaveElement)
			.on('click', function()
			{
				var button = $(this).addClass('btn-working'),
					postData = button.closest('.element').find('input, textarea').serializeArray();

				$.post(
					$this.AjaxUrl + '&task=saveelement&item_id=' + $this.options.item_id + '&elm_id=' + $this.options.elm_id, 
					postData, function() {
						button.removeClass('btn-working');
					}
				);
			}
			).appendTo(element.find('.btn-toolbar'));
		}
	});
	// Don't touch
	$.fn[Plugin.prototype.name] = function() {
		var args   = arguments;
		var method = args[0] ? args[0] : null;
		return this.each(function() {
			var element = $(this);
			if (Plugin.prototype[method] && element.data(Plugin.prototype.name) && method !== 'initialize') {
				element.data(Plugin.prototype.name)[method].apply(element.data(Plugin.prototype.name), Array.prototype.slice.call(args, 1));
			} else if (!method || $.isPlainObject(method)) {
				var plugin = new Plugin();
				if (Plugin.prototype.initialize) {
					plugin.initialize.apply(plugin, $.merge([element], args));
				}
				element.data(Plugin.prototype.name, plugin);
			} else {
				$.error('Method ' +  method + ' does not exist on jQuery.' + Plugin.name);
			}
		});
	};
})(jQuery, window, document);

/* ===================================================
 * ZLUX Example element related plugin
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(element, options) {
		var $this    = this,
			$element = $(element);

		if($element.data(Plugin.prototype.name)) return;

		$this.element = $(element);
		$this.options = $.extend({}, Plugin.prototype.options, options);
		this.events = {};

		// init the script
		$this.initialize();

		$this.element.data(Plugin.prototype.name, $this);
	};
	$.extend(Plugin.prototype, $.zlux.Main.prototype, {
		name: 'Example',
		options: {},
		initialize: function() {
			var $this = this;
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = Plugin;
})(jQuery, window, document);


/* ===================================================
 * ZLUX Example global plugin
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
		this.initialize();
	};
	$.extend(Plugin.prototype, $.zlux.Main.prototype, {
		name: 'Example',
		options: {},
		initialize: function() {
			var $this = this;
		}
	});
	// Don't touch
	$.zlux[Plugin.prototype.name] = function() {
		var args = arguments;
		return new Plugin(args[0] ? args[0] : {});
	};
})(jQuery, window, document);


/* ===================================================
 * ZLUX Example global init on hover dom
 * https://zoolanders.com/extensions/zl-framework
 * ===================================================
 * Copyright (C) JOOlanders SL 
 * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * ========================================================== */
;(function ($, window, document, undefined) {
	"use strict";
	var Plugin = function(element, options) {
		var $this    = this,
			$element = $(element);

		if($element.data(Plugin.prototype.name)) return;

		$this.element = $(element);
		$this.options = $.extend({}, Plugin.prototype.options, options);
		this.events = {};

		// init the script
		$this.initialize();

		$this.element.data(Plugin.prototype.name, $this);
	};
	$.extend(Plugin.prototype, $.zlux.Main.prototype, {
		name: 'Example',
		options: {},
		initialize: function() {
			var $this = this;
		}
	});
	// init code
	var triggerevent = $.zlux.support.touch ? 'click':'mouseenter';
	$(document).on(triggerevent+'.namespace.othernamespace', '[data-example-dom]', function(e) {
		var ele = $(this);

		if (!ele.data('Example')) {
			var dropdown = new Plugin(ele, $.zlux.utils.options(ele.data('example-dom')));
			e.preventDefault();
		}
	});
})(jQuery, window, document);