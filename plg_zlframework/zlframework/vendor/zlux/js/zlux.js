/* ===================================================
 * zlux
 * https://zoolanders.com
 * ===================================================
 * Copyright (C) JOOlanders SL
 * http://www.gnu.org/licenses/gpl-2.0.html
 * ========================================================== */
;(function ($, window, document, undefined) {
    "use strict";

    var ZX = $.zlux || {};

    if (ZX.fn) {
        return ZX;
    }

    ZX.version = '2.0';

    ZX.fn = function(command, options) {

        var args = arguments, cmd = command.match(/^([a-z\-]+)(?:\.([a-z]+))?/i), component = cmd[1], method = cmd[2];

        if (!ZX[component]) {
            $.error("UIkit component [" + component + "] does not exist.");
            return this;
        }

        return this.each(function() {
            var $this = $(this), data = $this.data(component);
            if (!data) $this.data(component, (data = ZX[component](this, method ? undefined : options)));
            if (method) data[method].apply(data, Array.prototype.slice.call(args, 1));
        });
    };


    /** URI **/
    ZX.url = {};
    ZX.url.urls = {
        ajax: '',
        root: '',
        zlux: ''
    };
    /**
     * Push urls to the list
     * @param Object urls List of urls in JSON format
     */
    ZX.url.push = function (urls) {
        $.extend(ZX.url.urls, urls);
    };
    /**
     * Retrieves the specified url
     * @param String url || url:path The url to be retrieved
     * @param Object params List of params tu attach to the url
     * @return String The full url
     */
    ZX.url.get = function (url, params) {
        url = url.split(':');
        params = params === undefined ? {} : params;

        return ZX.url.clean(url.length === 2 ? ZX.url._get(url[0]) + '/' + url[1] : url[0]) +
            ($.isEmptyObject(params) ? '' : '&' + $.param(params));
    };
    ZX.url._get = function (url) {
        return ZX.url.urls[url] !== undefined ? ZX.url.urls[url] : url;
    };
    /**
     * Clean an URL from double slash and others
     * @param String url The url to be cleaned
     */
    ZX.url.clean = function(url) {
        if (!url) return '';
        
        // return url and
        return url

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
    };


    /** LANGUAGE **/
    ZX.lang = {};
    ZX.lang.strings = {};
    /**
     * Push language strings to the list
     * @param Object strings Translated string in JSON format.
     */
    ZX.lang.push = function (strings) {
        $.extend(ZX.lang.strings, strings);
    };
    /**
     * Retrieves the specified language string
     * @param String string String to look for.
     * @return String Translated string or the input string if it wasn't found.
     */
    ZX.lang.get = function (string) {
        return ZX.lang.strings[string] || string;
    };
    // alias
    ZX.lang._ = ZX.lang.get;
    /**
     * Pseudo sprintf implementation - simple way to replace tokens with specified values.
     * @param String str String with tokens
     * @return String String with replaced tokens
     */
    ZX.lang.sprintf = function (str) {
        var args = [].slice.call(arguments, 1);

        str = ZX.lang.get(str);

        return str.replace(/%[a-z]/g, function () {
            var value = args.shift();
            return ZX.utils.typeOf(value) !== 'undefined' ? value : '';
        });
    };


    /** UTILS **/
    ZX.utils = {};
    // returns the object type
    ZX.utils.typeOf = function(obj) {
        return ({}).toString.call(obj).match(/\s([a-z|A-Z]+)/)[1].toLowerCase();
    };
    ZX.utils.options = function(string) {
        if ($.isPlainObject(string)) return string;

        var start = (string ? string.indexOf("{") : -1), options = {};
        if (start != -1) {
            try {
                options = (new Function("", "var json = " + string.substr(start) + "; return JSON.parse(JSON.stringify(json));"))();
            } catch (e) {}
        }

        return options;
    };

    /**
    * @see http://stackoverflow.com/q/7616461/940217
    * @return number The hash number
    */
    String.prototype.hashCode = function(){
       if (Array.prototype.reduce){
           return this.split("").reduce(function(a,b){a=((a<<5)-a)+b.charCodeAt(0);return a&a},0);              
       } 
       var hash = 0;
       if (this.length === 0) return hash;
       for (var i = 0; i < this.length; i++) {
           var character  = this.charCodeAt(i);
           hash  = ((hash<<5)-hash)+character;
           hash = hash & hash; // Convert to 32bit integer
       }
       return hash;
    };


    /** ASSETS **/
    ZX.assets = {};
    ZX.assets._ress = {}; // requested assets
    /**
     * Load requested assets and execute callback
     * @ress String or Array
     */
    ZX.assets.load = function(ress, callback, failcallback) {
        var req  = [];
        
        // clean vars
        ress = $.isArray(ress) ? ress:[ress];

        // load assets
        for (var i=0, len=ress.length; i<len; i++) {

            if(!ress[i]) continue;

            if (!ZX.assets._ress[ress[i]]) {
                if (ress[i].match(/\.js$/)) {
                    ZX.assets._ress[ress[i]] = ZX.assets.getScript(ZX.url.get('root:'+ress[i]));
                } else {
                    ZX.assets._ress[ress[i]] = ZX.assets.getCss(ZX.url.get('root:'+ress[i]));
                }
            }
            req.push(ZX.assets._ress[ress[i]]);
        }

        return $.when.apply($, req).done(callback).fail(function(){
            if (failcallback) {
                failcallback();
            } else {
                $.error("Require failed: \n" + ress.join(",\n"));
            }
        });
    };
    ZX.assets.getScript = function(url, callback) {
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
        };

        script.src = url;

        document.getElementsByTagName('head')[0].appendChild(script);

        return d.promise();
    };
    ZX.assets.getCss = function(url, callback){
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


    // declare zlux
    $.zlux = ZX;
    $.fn.zx = ZX.fn;


    // style workaround, wrap with zlux floating elements
    $(document).on("uk-domready", function(e) {
        $('body > .uk-datepicker, body > .uk-timepicker, body > .uk-tooltip').wrap('<div class="zlux" />');
    });

})(jQuery, window, document);

;(function ($, ZX, window, document, undefined) {
    "use strict";

    ZX.components = {};

    ZX.component = function(name, def) {

        var fn = function(element, options) {

            var $this = this;

            this.element = element ? $(element) : null;
            this.options = $.extend(true, {}, this.defaults, options);
            this.plugins = {};

            if (this.element) {
                this.element.data(name, this);
            }

            this.init();

            (this.options.plugins.length ? this.options.plugins : Object.keys(fn.plugins)).forEach(function(plugin) {

                if (fn.plugins[plugin].init) {
                    fn.plugins[plugin].init($this);
                    $this.plugins[plugin] = true;
                }

            });

            this.trigger('init', [this]);
        };

        fn.plugins = {};

        $.extend(true, fn.prototype, {

            defaults : {plugins: []},

            init: function(){},

            on: function(){
                return $(this.element || this).on.apply(this.element || this, arguments);
            },

            one: function(){
                return $(this.element || this).one.apply(this.element || this, arguments);
            },

            off: function(evt){
                return $(this.element || this).off(evt);
            },

            trigger: function(evt, params) {
                return $(this.element || this).trigger(evt, params);
            },

            find: function(selector) {
                return this.element ? this.element.find(selector) : $([]);
            },

            proxy: function(obj, methods) {

                var $this = this;

                methods.split(' ').forEach(function(method) {
                    if (!$this[method]) $this[method] = function() { return obj[method].apply(obj, arguments); };
                });
            },

            mixin: function(obj, methods) {

                var $this = this;

                methods.split(' ').forEach(function(method) {
                    if (!$this[method]) $this[method] = obj[method].bind($this);
                });
            }

        }, def);

        this.components[name] = fn;

        this[name] = function() {

            var element, options;

            if(arguments.length) {
                switch(arguments.length) {
                    case 1:

                        if (typeof arguments[0] === "string" || arguments[0].nodeType || arguments[0] instanceof jQuery) {
                            element = $(arguments[0]);
                        } else {
                            options = arguments[0];
                        }

                        break;
                    case 2:

                        element = $(arguments[0]);
                        options = arguments[1];
                        break;
                }
            }

            if (element && element.data(name)) {
                return element.data(name);
            }

            return (new ZX.components[name](element, options));
        };

        return fn;
    };

    ZX.plugin = function(component, name, def) {
        this.components[component].plugins[name] = def;
    };

})(jQuery, jQuery.zlux, window, document);

;(function ($, ZX, window, document, undefined) {
    "use strict";

    ZX.ajax = {};

    /**
     * Ajax request
     * @param Object settings The request settings
     * @return Promise The ajax promise
     */
    ZX.ajax.request = function(settings)
    {
        // set defaults
        var response = {success:false, errors:[], notices:[]},
            queue = settings.queue ? settings.queue : null,
            request = null;

        // delete custom params, just in case
        delete settings.queue;

        // set request defaults
        settings = $.extend({
            dataType: 'json',
            type: 'POST'
        }, settings);

        // return a promise
        return $.Deferred(function( defer )
        {
            // perform the request
            if (queue) {
                request = ZX.ajax.queue(queue, settings);
            } else {
                request = $.ajax(settings);
            }
            
            // if response recieved
            request.done(function(result, a, b)
            {
                // json response is assumed
                if (ZX.utils.typeOf(result) !== 'object')
                {
                    try {
                        // parse response detecting if there was some server side error
                        json = $.parseJSON(result);

                    // handle exception
                    } catch(e) {
                        response.errors.push(String(result));
                        response.errors.push('An server-side error occurred. ' + String(e));
                        defer.reject(response);
                    }
                }

                // status must be set
                else if (result.success === undefined) {
                    result.errors = ['Response format error: status not specified'];
                    defer.reject(result);
                } else if (result.success)
                    defer.resolve(result);
                else
                    defer.reject(result);
            })
            
            // if something went wrong
            .fail(function(jqxhr, status, error)
            {
                // handle errors
                switch (jqxhr.status) {
                    case 0: // if request canceled no error is logged
                        break;
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
                        response.errors.push('An error occurred: ' + status + '\n Error: ' + error);
                        break;
                }

                // set response status
                response.status = jqxhr.status;

                // reject
                defer.reject(response);
            });

        }).promise();
    };
    /**
     * Ajax request and notify the answer
     * @param Object request The ajax request
     * @param Object notify The notify settings
     * @return Promise The ajax promise
     */
    ZX.ajax.requestAndNotify = function(request, notify)
    {
        // set defaults
        notify = notify === undefined ? {} : notify;

        // request
        return ZX.ajax.request(request)
        .done(function(response){

            if(notify.group) ZX.notify.closeAll(notify.group);

            // display message
            if(response.message) ZX.notify(response.message, $.extend({
                status: 'success'
            }, notify));
            
        }).fail(function(response){

            if(notify.group) $.UIkit.notify.closeAll(notify.group);

            // display errors
            if(response.errors && response.errors.length) $.each(response.errors, function(){
                ZX.notify(this, $.extend({
                    status: 'danger'
                }, notify));
            });
            // display notices
            if(response.notices && response.notices.length) $.each(response.notices, function(){
                ZX.notify(this, $.extend({
                    status: 'warning'
                }, notify));
            });
        });
    };


    // Original code from AjaxQ jQuery Plugin
    // Copyright (c) 2012 Foliotek Inc.
    // MIT License
    // https://github.com/Foliotek/ajaxq
    var queues = {};

    // Register an $.ajaxq function, which follows the $.ajax interface, but allows a queue name which will force only one request per queue to fire.
    ZX.ajax.queue = function(qname, opts) {

        if (typeof opts === "undefined") {
            throw ("AjaxQ: queue name is not provided");
        }

        // Will return a Deferred promise object extended with success/error/callback, so that this function matches the interface of $.ajax
        var deferred = $.Deferred(),
            promise = deferred.promise();

        promise.success = promise.done;
        promise.error = promise.fail;
        promise.complete = promise.always;

        // Create a deep copy of the arguments, and enqueue this request.
        var clonedOptions = $.extend(true, {}, opts);
        enqueue(function() {

            // Send off the ajax request now that the item has been removed from the queue
            var jqXHR = $.ajax.apply(window, [clonedOptions]).always(dequeue);

            // Notify the returned deferred object with the correct context when the jqXHR is done or fails
            // Note that 'always' will automatically be fired once one of these are called: http://api.jquery.com/category/deferred-object/.
            jqXHR.done(function() {
                deferred.resolve.apply(this, arguments);
            });
            jqXHR.fail(function() {
                deferred.reject.apply(this, arguments);
            });
        });

        return promise;

        // If there is no queue, create an empty one and instantly process this item.
        // Otherwise, just add this item onto it for later processing.
        function enqueue(cb) {
            if (!queues[qname]) {
                queues[qname] = [];
                cb();
            }
            else {
                queues[qname].push(cb);
            }
        }

        // Remove the next callback from the queue and fire it off.
        // If the queue was empty (this was the last item), delete it from memory so the next one can be instantly processed.
        function dequeue() {
            if (!queues[qname]) {
                return;
            }
            var nextCallback = queues[qname].shift();
            if (nextCallback) {
                nextCallback();
            }
            else {
                delete queues[qname];
            }
        }
    };

    // Register a $.postq and $.getq method to provide shortcuts for $.get and $.post
    // Copied from jQuery source to make sure the functions share the same defaults as $.get and $.post.
    $.each( [ "getq", "postq" ], function( i, method ) {
        $[ method ] = function( qname, url, data, callback, type ) {

            if ( $.isFunction( data ) ) {
                type = type || callback;
                callback = data;
                data = undefined;
            }

            return ZX.ajax.queue(qname, {
                type: method === "postq" ? "post" : "get",
                url: url,
                data: data,
                success: callback,
                dataType: type
            });
        };
    });

    var isQueueRunning = function(qname) {
        return queues.hasOwnProperty(qname);
    };

    var isAnyQueueRunning = function() {
        for (var i in queues) {
            if (isQueueRunning(i)) return true;
        }
        return false;
    };

    ZX.ajax.queue.isRunning = function(qname) {
        if (qname) return isQueueRunning(qname);
        else return isAnyQueueRunning();
    };
    
    ZX.ajax.queue.clear = function(qname) {
        if (!qname) {
            for (var i in queues) {
                if (queues.hasOwnProperty(i)) {
                    delete queues[i];
                }
            }
        }
        else {
            if (queues[qname]) {
                delete queues[qname];
            }
        }
    };

})(jQuery, jQuery.zlux, window, document);

;(function ($, ZX, window, document, undefined) {
    "use strict";

    var notify = function(msg, options){

        // display message
        var notify = $.UIkit.notify(msg, options);

        // wrapp for styling
        $('.uk-notify').wrap('<div class="zlux" />');

        return notify;
    },

    confirm = function(msg, options)
    {
        $.extend({}, options, {
            'timeout': false // confirmation must wait user interaction
        });

        return $.Deferred(function( defer )
        {
            var notify = ZX.notify(msg + '<div class="uk-text-center uk-margin-top">\
                    <a class="zx-x-confirm uk-margin-right"><i class="uk-icon-check uk-icon-small"></i></a>\
                    <a class="zx-x-cancel uk-margin-left"><i class="uk-icon-times uk-icon-small"></i></a>\
                </div>',
            options);

            notify.element.on('click', '.zx-x-confirm', function(e, b){
                defer.resolve();
            });

            notify.element.on('click', function(e, b){
                defer.reject();
            });

        }).promise();
    },

    closeAll = function(group, instantly){
        $.UIkit.notify.closeAll(group, instantly);
    };

    ZX.notify          = notify;
    ZX.notify.confirm  = confirm;
    ZX.notify.closeAll = closeAll;

})(jQuery, jQuery.zlux, window, document);

;(function ($, ZX, window, document, undefined) {
    "use strict";
    
    ZX.component('spin', {

        defaults: {
            class: '',
            affix:  'append' // append, prepend or replace
        },

        init: function() {},

        on: function(args) {
            var $this = this;

            $this.icon_class = false;

            // check for icon, use it if found
            if($('i', $this.element)[0]) {
                $this.icon_class = $('i', $this.element).attr('class');
                $('i', $this.element).attr('class', 'uk-icon-spinner uk-icon-spin');

            // create the icon if not
            } else if($this.options.affix == 'replace') {
                $this.element.html($('<i class="uk-icon-spinner uk-icon-spin"></i>').addClass($this.options['class']));
            } else {
                $this.element[$this.options.affix]($('<i class="uk-icon-spinner uk-icon-spin"></i>').addClass($this.options['class']));
            }
        },

        off: function(args) {
            var $this = this;

            // remove the spin classes but not the icon
            $('i', $this.element).removeClass('uk-icon-spinner uk-icon-spin');

            // recover class, if any
            if($this.icon_class) $('i', $this.element).attr('class', $this.icon_class);
        }

    });

})(jQuery, jQuery.zlux, window, document);

;(function ($, ZX, window, document, undefined) {
    "use strict";

    var dropdown;

    ZX.components.manager = {

        id: 0,

        init: function() {
            var $this = this;
           
            // save nav node ref
            // this.nav = ZX.managerNav($('.zx-manager-nav', this.element));

            // this.nav.addChild({icon: 'filter'});
            // this.nav.addChild({icon: 'user'});

        },


        getResource: function(resource) {
            // if already a resource object return directly, else retrieve from node
            return resource instanceof jQuery ? resource.data('managerResource') : resource;
        },

        preResourceDelete: function(resource, request) {},

        deleteResource: function(resource) {
            var $this = this;

            // only allowed to be submited once
            // if ($(this).data('submited')) return; $(this).data('submited', true);

            var request = {
                url: $.zlux.url.ajax('zlux', 'deleteResource'),
                data: {
                    type: $this.type
                }
            };

            // start spinner
            // $('.column-icon i', resource.dom).spin('on');


            // pre action, allow changing request data
            this.preResourceDelete(resource, request);

            // make the request and return a promise
            ZX.ajax.request(request).done(function(json) {

                // remove the resource node
                resource.element.fadeOut('slow', function(){
                    $(this).remove();

                    // trigger event
                    $this.trigger('resourceDeleted', resource);
                });

            }).fail(function(response){
     
                // console.log(response);

                // show the message
                // $this.pushMessageToObject($object, msg);
            })

            // on result
            .always(function(json) {
                // console.log(json);
                // remove spinner
                // $('.column-icon i', $object.dom).removeClass('uk-icon-spinner uk-icon-spin');
            });
        },

        initResources: function(dom) {
            var $this = this;

            dom = dom === undefined ? $this.find('.zx-manager-resources') : dom;

            // load DataTables
            return ZX.assets.load(ZX.url.get('zlux:js/addons/datatables.min.js')).done(function(){

                // init DT
                ZX.datatables();

                // init DataTables instance
                $this.resources = dom.dataTable($.extend(true, {}, ZX.datatables.settings, $this.DTsettings)).DataTable();

                // details
                $this.resources.on('click', '.zx-x-details-btn', function(){
                    var toggle = $(this),
                        resource = toggle.closest('.zx-manager-resource'),
                        details = $('.zx-x-details', resource);

                    // open the details
                    if (!resource.hasClass('zx-open')) {
                        resource.addClass('zx-open');
                        toggle.removeClass('uk-icon-angle-down').addClass('uk-icon-angle-up');

                        // scroll to the Object with animation
                        // $this.zluxdialog.content.stop().animate({
                        //     'scrollTop': $resource.get(0).offsetTop
                        // }, 900, 'swing');

                        // open, when done...
                        details.slideDown('fast', function(){
                            // $this.zluxdialog.scrollbar('refresh');
                        });

                    // close them
                    } else {
                        toggle.addClass('uk-icon-angle-down').removeClass('uk-icon-angle-up');
                        resource.removeClass('zx-open');
                        details.slideUp('fast', function(){
                            // $this.zluxdialog.scrollbar('refresh');
                        });
                    }
                });

                // EVENT trigger resourceSelected
                $this.resources.on('click', '.zx-manager-resource .zx-x-name a', function (e) {
                    e.preventDefault();
                    $this.trigger('resourceSelected', $this.getResource($(this).closest('.zx-manager-resource')));
                });

                // delete resource event
                $this.resources.on('click', '.zx-manager-resource .zx-x-remove', function(e){
                    e.preventDefault();
                    var resource = $this.getResource($(this).closest('.zx-manager-resource'));

                    // prompt confirmation
                    ZX.notify.confirm(ZX.lang._('DELETE_THIS_RESOURCE'), {timeout: false}).done(function(){
                        $this.deleteResource(resource);
                    });
                });
            });
        },

        DTsettings: {
            serverSide: true
        }
    };


    ZX.component('managerResource', {

        // data defaults
        data: {
            editable: false
        },

        // option defaults
        defaults: {
            template      : '<div class="zx-x-tools">\
                                <i class="zx-x-details-btn uk-icon-angle-down" />\
                                {{#editable}}\
                                <i class="zx-x-remove uk-icon-minus-circle" data-uk-tooltip title="' + ZX.lang._('DELETE') + '" />\
                                {{/some}}\
                            </div>\
                            <div class="zx-x-name"><a href="#" class="zx-x-name-link">{{name}}</a></div>\
                            {{#details && details.length}}\
                            <div class="zx-x-details">\
                                <div class="zx-x-messages" />\
                                <div class="zx-x-details-content">\
                                    <ul class="uk-list">\
                                        {{~details}}\
                                        <li>{{$item}}</li>\
                                        {{/details}}\
                                        </ul>\
                                </div>\
                            </div>\
                            {{/end}}'
        },

        init: function() {},

        /* renders the resource content */
        render: function() {
            return $.UIkit.Utils.template(this.options.template, this.data);
        },

        /* push new resource data into the current one */
        pushData: function(data) {
            this.data = $.extend({}, this.data, data);
        }

    });

    
    ZX.component('managerNav', {

        defaults: {
            template:       '<ul class="uk-navbar-nav">\
                                <li class="uk-parent" data-uk-dropdown>\
                                    <a href=""><i class="uk-icon-bars"></i></a>\
                                    <div class="uk-dropdown uk-dropdown-navbar">\
                                        <ul class="uk-nav uk-nav-navbar">\
                                            <li><a href="">Another item</a></li>\
                                        </ul>\
                                    </div>\
                                </li>\
                            </ul>\
                            <div class="uk-navbar-content">\
                                <form class="uk-form uk-margin-remove uk-display-inline-block">\
                                   <input type="text" placeholder="Search">\
                                </form>\
                            </div>',
            option_tmpl    : '<li><a href=""><i class="uk-icon-{{icon}}"></i> Some text </a></li>'
        },

        init: function() {
            this.element.append(this.options.template);
        },

        addChild: function(data) {
            this.find('.uk-dropdown > ul').append($.UIkit.Utils.template(this.options.option_tmpl, data));
        }
    });


    /* Dropdown
    ---------------------------------------------- */

    // extends itemsManager
    ZX.components.managerDropdown = $.extend(true, {}, ZX.components.manager, {

        defaults: {
            init_display: '',
            offsettop: 5,
            template: function(data, opts) {

                var content = '';

                content += '<div class="zx-manager-nav">';
                    content += '<div class="uk-search">';
                        content += '<input class="uk-search-field" type="search" placeholder="search...">';
                        content += '<button class="uk-search-close" type="reset"></button>';
                    content += '</div>';
                content += '</div>';
                content += '<table class="uk-table zx-manager-resources"></table>';

                return content;
            }
        },

        init: function() {
            var $this = this;

            // save current value
            this.current = this.element.val();

            // create a hidden input that will store the real value
            this.hidden = this.element.clone().attr('type', 'hidden').removeAttr('data-zx-itempicker').insertAfter(this.element);

            // set initial display
            this.element.val(this.options.init_display).removeAttr('name');

            // weitch focus from main input
            this.on('focus', function() {
                $('.uk-search-field', dropdown).focus();
            });
        },

        initDropdown: function(dropdown) {
            var $this = this;

            if (!dropdown) {

                dropdown = $('<div class="uk-dropdown zx-manager"></div>');
                
                // init searh feature
                var thread = null;
                dropdown.on('keyup', '.uk-search-field', function(e){
                    var value = $(this).val();

                    // close button
                    if (value === '') {
                        $('.uk-search-close', dropdown).hide();
                    } else {
                        $('.uk-search-close', dropdown).show();
                    }

                    // clear any previous query execution
                    clearTimeout(thread);

                    // if input empty, reset search
                    if (value === '') {
                        $this.resources.search('').draw();
                    }
                    
                    // perform search on enter key press
                    var code = (e.keyCode ? e.keyCode : e.which);
                    if (code == 13) {
                        $this.resources.search(value).draw();
                        return;
                    }

                    // queue the search
                    thread = setTimeout(function() {
                        $this.resources.search(value).draw();
                    }, 500); 
                });

                // reset search action
                dropdown.on('click', '.uk-search-close', function(e){
                    // reset form
                    $('.uk-search-field', dropdown).val('');
                    $(this).hide();

                    // and search
                    $this.resources.search('').draw();
                });

                dropdown.appendTo('body');

                // wrap it for style fix
                dropdown.wrap('<div class="zlux" />');
            }

            // save reference
            this.dropdown = dropdown;
            return dropdown;
        },

        pick: function(inititem) {
            var offset = this.element.offset(),
               css    = {"top": offset.top + this.element.outerHeight() + this.options.offsettop, "left": offset.left, "right":""};

            this.current  = inititem ? inititem : null;
            this.inititem = this.current;

            this.update();

            if ($.UIkit.langdirection == 'right') {
               css.right = window.innerWidth - (css.left + this.element.outerWidth());
               css.left  = "";
            }

            this.dropdown.css(css).show();

            // focus on dropdown search
            $('.uk-search-field', dropdown).focus();
        },

        update: function() {
            var $this = this,
                data = {},
                tpl  = this.options.template(data, this.options);

            this.dropdown.html(tpl);

            // init resources
            this.initResources($('.zx-manager-resources', this.dropdown)).done(function(){

                $this.on('resourceSelected', function(e, resource) {
                    $this.element.val(resource.data.name);
                    $this.hidden.val(resource.data.id);
                });
            });
        }
    });

})(jQuery, jQuery.zlux, window, document);

;(function ($, ZX, window, document, undefined) {
    "use strict";

    var instance_id = 0, active = false, cache = {}, dropdown,
    
    itemsManagerSettings = {

        defaults: {
            apps: '', // Array or comma separated values
            types: '', // idem
            categories: '', // idem
            tags: '', // idem
            authors: '' // idem
        },

        init: function() {
            var $this = this;

            // init main manager
            ZX.components.manager.init.apply(this);
            
            // set instance id
            this.id = instance_id++;

            // set the filter param
            $this.filter = {};

            // override the ajax function
            this.DTsettings.ajax = function (data, callback, settings) {
                $this.ajax(data, callback, settings);
            };

            // set language vars
            $.extend($this.DTsettings.language, {
                emptyTable: ZX.lang._('IM_NO_ITEMS_FOUND'),
                info: ZX.lang._('IM_PAGINATION_INFO')
            });
        },

        DTsettings: {
            pageLength: 5,
            columns:
            [
                {
                    title: '', data: '_itemname', class: 'zx-manager-resource-name uk-width-1-1',
                    render: function (data, type) {
                        return type === 'display' ? '' : data;
                    }
                }
            ],
            initComplete: function(settings) {
                // var input_filter = $('.zlux-x-filter-input_wrapper', wrapper)
                
                // .append(
                //     // set search icon
                //     $('<i class="icon-search" />'),
                //     // and the cancel button
                //     $('<i class="icon-remove zlux-ui-dropdown-unselect" />').hide().on('click', function(){
                //         $('input', input_filter).val('');
                //         $(this).hide();
                //         // reset the filter
                //         $this.oTable.fnFilter('');
                //     })
                // );

                // // set search events
                // $('input', input_filter).on('keyup', function(){
                //     if ($(this).val() === '') {
                //         $('.zlux-ui-dropdown-unselect', input_filter).hide();
                //     } else {
                //         $('.zlux-ui-dropdown-unselect', input_filter).show();
                //     }
                // });

                // // fix the header column order
                // $('thead tr th:last', settings.nTable).prependTo($('thead tr', settings.nTable));

                // // trigger table init event
                // $this.trigger("InitComplete");
            },
            rowCallback: function(row, data) {
                var rsc_data = data;
                    rsc_data.details = [];

                // set resource details
                rsc_data.details.push( data.application.name + ' / ' + data.type.name + ' / ' + data.id );
                rsc_data.details.push( data.created );

                // add Author if known
                if (data.author.name) rsc_data.details.push({name: $this._('AUTHOR'), value: data.author.name});
            
                var resource = ZX.managerResource(row, rsc_data);
                resource.pushData(rsc_data);

                // set resource dom properties
                resource.element.addClass('zx-manager-resource');

                // fix the column order
                // $('td:last', resource.element).prependTo(resource.element);

                // reset and append the resource data
                $('.zx-manager-resource-name', resource.element).html('').append(
                    // render the resource content
                    resource.render()
                );
            },
            preDrawCallback: function(settings) {
                // show processing
                // $this.zluxdialog.spinner('show');

                // trigger event
                // $this.trigger("DTPreDrawCallback", oSettings);
            },
            drawCallback: function(settings) {
                // pagination hide/show
                // var oPaging = oSettings.oInstance.fnPagingInfo(),
                //     pagination = $('.dataTables_paginate', $(oSettings.nTableWrapper)).closest('.row-fluid');
                
                // // hide/show the pagination
                // if (oPaging.iTotalPages <= 1) pagination.hide(); else pagination.show();

                // // update dialog scrollbar
                // $this.zluxdialog.scrollbar('refresh');

                // // hide processing
                // $this.zluxdialog.spinner('hide');

                // // trigger event
                // $this.trigger("TableDrawCallback", oSettings);
            }
        },

        ajax: function (data, callback, settings) {
            var $this = this,

            // determine what filter values to use
            apps = $this.filter.apps ? $this.filter.apps : $this.options.apps,
            types = $this.filter.types ? $this.filter.types : $this.options.types,
            cats = $this.filter.cats ? $this.filter.cats : $this.options.categories,
            tags = $this.filter.tags ? $this.filter.tags : $this.options.tags,
            authors = $this.filter.authors ? $this.filter.authors : $this.options.authors;

            // push the preset filter values
            data.apps = $this.options.apps;
            data.types = $this.options.types;
            data.categories = $this.options.categories;
            data.tags = $this.options.tags;
            data.authors = $this.options.authors;

            // push the new filter values
            data.filter_apps = apps;
            data.filter_types = types;
            data.filter_cats = cats;
            data.filter_tags = tags;

            // specify zlux version
            data.zlux2 = true;

            // save draw value
            var draw = data.draw;

            // hash the request data
            data.draw = 0;
            var hash = String($.param(data)).hashCode();

            // if request cached, use instead and abort ajax
            if (false && cache[hash]) {
                cache[hash].draw = draw;

                callback( cache[hash] );
                return;
            }

            // recover draw value
            data.draw = draw;

            // request
            ZX.ajax.requestAndNotify({
                url: ZX.url.get('ajax:', {controller: 'zlux', task: 'getItemsManagerData'}),
                data: data,
                queue: 'itemsmanager'
            })

            .done(function (json) {

                // cache the retrieved data
                cache[hash] = json;

                // redraw
                callback(json);
            });
        },

        /**
         * Reload the data from source and redraw
         */
        reload: function() {
            var $this = this;

            // reload
            $this.resources.DataTable().ajax.reload();
        },

        preResourceDelete: function(resource, request) {
            var $this = this;

            // adapt request
        }
    };


    ZX.component('itempicker', $.extend(true, {}, itemsManagerSettings, ZX.components.managerDropdown, {

        init: function() {
            var $this = this;

            // init functions
            itemsManagerSettings.init.apply(this);
            ZX.components.managerDropdown.init.apply(this);

            this.on("click", function(){
               if(active!==$this) $this.pick(this.value);
            });

            // init dropdown
            dropdown = this.initDropdown(dropdown).addClass('zx-itempicker');
        },

        DTsettings: {
            ordering: false,
            columns:
            [
                {
                    title: '', data: '_itemname', class: 'zx-x-main-column uk-width-1-1',
                    render: function (data, type) {
                        return type === 'display' ? '' : data;
                    }
                }
            ],
            rowCallback: function(row, data) {
                var rsc_data = data;
                    rsc_data.details = [];

                // set resource details
                rsc_data.details.push( data.application.name + ' / ' + data.type.name + ' / ' + data.id );
            
                var resource = ZX.managerResource(row, rsc_data);
                resource.pushData(rsc_data);

                // set resource dom properties
                resource.element.addClass('zx-manager-resource');

                // append the resource data
                $('.zx-x-main-column', resource.element).append(
                    resource.render()
                );
            }
        },

        pick: function(inititem) {
           ZX.components.managerDropdown.pick.apply(this, [inititem]);

           active = this;
        },
    }));

    // init code
    $(document).on("focus.itempicker.zlux", "[data-zx-itempicker]", function(e) {
        var ele = $(this);

        if (!ele.data("itempicker")) {
            e.preventDefault();
            var obj = ZX.itempicker(ele, $.UIkit.Utils.options(ele.attr("data-zx-itempicker")));
            ele.trigger("focus");
        }
    });

    $(document).on("click.itempicker.zlux", function(e) {
        var target = $(e.target);

        if (active && target[0] != dropdown[0] && !target.data("itempicker") && !target.parents(".zx-itempicker:first").length) {
            dropdown.hide();
            active = false;
        }
    });

    // init code
    $(document).on("uk-domready", function(e) {
        $("[data-zx-itempicker]").each(function() {
            var ele = $(this);

            if (!ele.data("itempicker")) {
                var obj = ZX.itempicker(ele, $.UIkit.Utils.options(ele.attr("data-zx-itempicker")));
            }
        });
    });

})(jQuery, jQuery.zlux, window, document);